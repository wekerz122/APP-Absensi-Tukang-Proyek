<?php
session_start();
include "../config/db.php";

// proteksi admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die("Akses ditolak!");
}

// filter tanggal (opsional)
$tanggal = $_GET['tanggal'] ?? '';

$where = "";
if ($tanggal != '') {
    $tanggal_safe = mysqli_real_escape_string($conn, $tanggal);
    $where = "WHERE a.tanggal = '$tanggal_safe'";
}

// ambil data
$query = mysqli_query($conn, "
SELECT 
    a.tanggal,
    u.username AS nama_spv,
    p.nama_proyek,
    a.jam_masuk,
    a.jam_pulang,
    a.lembur_menit,
    a.target,
    a.target_selesai
FROM absensi_spv a
LEFT JOIN users u ON u.id = a.spv_id
LEFT JOIN proyek p ON p.id = a.proyek_id
$where
ORDER BY a.tanggal DESC
");

// header excel
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=rekap_absensi_spv.xls");
header("Pragma: no-cache");
header("Expires: 0");

echo "<table border='1'>";
echo "<tr>
        <th>Tanggal</th>
        <th>SPV</th>
        <th>Proyek</th>
        <th>Jam Masuk</th>
        <th>Jam Pulang</th>
        <th>Lembur (menit)</th>
        <th>Target</th>
        <th>Laporan</th>
      </tr>";

while ($row = mysqli_fetch_assoc($query)) {
    echo "<tr>";
    echo "<td>".$row['tanggal']."</td>";
    echo "<td>".$row['nama_spv']."</td>";
    echo "<td>".$row['nama_proyek']."</td>";
    echo "<td>".$row['jam_masuk']."</td>";
    echo "<td>".$row['jam_pulang']."</td>";
    echo "<td>".$row['lembur_menit']."</td>";
    echo "<td>".$row['target']."</td>";
    echo "<td>".$row['target_selesai']."</td>";
    echo "</tr>";
}

echo "</table>";
exit;