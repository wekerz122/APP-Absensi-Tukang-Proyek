<?php
session_start();
include "../config/db.php";

// Proteksi login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Proteksi admin
if ($_SESSION['role'] != 'admin') {
    echo "Akses ditolak!";
    exit;
}

$tanggal = date("Y-m-d");

// helper aman ambil angka
function qnum($conn, $sql, $key='total'){
    $res = mysqli_query($conn, $sql);
    if(!$res) return 0;
    $row = mysqli_fetch_assoc($res);
    return isset($row[$key]) ? (int)$row[$key] : 0;
}
// ==================
// FILTER TANGGAL (GET)
// ==================
$date_from = $_GET['from'] ?? date("Y-m-d");
$date_to   = $_GET['to']   ?? date("Y-m-d");

// validasi format YYYY-MM-DD
if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) $date_from = date("Y-m-d");
if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to))   $date_to   = date("Y-m-d");

// kalau user kebalik input (to < from), tukar DULU sebelum query
if(strtotime($date_to) < strtotime($date_from)){
    $tmp = $date_from;
    $date_from = $date_to;
    $date_to = $tmp;
}

// versi aman untuk SQL
$date_from_sql = mysqli_real_escape_string($conn, $date_from);
$date_to_sql   = mysqli_real_escape_string($conn, $date_to);

// ==================
// STATISTIK ABSENSI (PAKAI RANGE TANGGAL)
// ==================
$t_hadir = qnum($conn, "
    SELECT COUNT(DISTINCT tukang_id) AS total
    FROM absensi
    WHERE tanggal BETWEEN '$date_from_sql' AND '$date_to_sql'
");

$t_masuk = qnum($conn, "
    SELECT COUNT(*) AS total
    FROM absensi
    WHERE tanggal BETWEEN '$date_from_sql' AND '$date_to_sql'
      AND jam_masuk IS NOT NULL
");

$t_pulang = qnum($conn, "
    SELECT COUNT(*) AS total
    FROM absensi
    WHERE tanggal BETWEEN '$date_from_sql' AND '$date_to_sql'
      AND jam_pulang IS NOT NULL
");

$t_lembur = qnum($conn, "
    SELECT COUNT(*) AS total
    FROM absensi
    WHERE tanggal BETWEEN '$date_from_sql' AND '$date_to_sql'
      AND lembur_menit IS NOT NULL AND lembur_menit > 0
");

$s_hadir = qnum($conn, "
    SELECT COUNT(DISTINCT spv_id) AS total
    FROM absensi_spv
    WHERE tanggal BETWEEN '$date_from_sql' AND '$date_to_sql'
");

$s_masuk = qnum($conn, "
    SELECT COUNT(*) AS total
    FROM absensi_spv
    WHERE tanggal BETWEEN '$date_from_sql' AND '$date_to_sql'
      AND jam_masuk IS NOT NULL
");

$s_pulang = qnum($conn, "
    SELECT COUNT(*) AS total
    FROM absensi_spv
    WHERE tanggal BETWEEN '$date_from_sql' AND '$date_to_sql'
      AND jam_pulang IS NOT NULL
");

$s_lembur = qnum($conn, "
    SELECT COUNT(*) AS total
    FROM absensi_spv
    WHERE tanggal BETWEEN '$date_from_sql' AND '$date_to_sql'
      AND lembur_menit IS NOT NULL AND lembur_menit > 0
");
// ==================
// STATISTIK USERS
// ==================
$tukang_aktif = qnum($conn, "SELECT COUNT(*) as total FROM users WHERE role='tukang' AND aktif=1");
$spv_aktif    = qnum($conn, "SELECT COUNT(*) as total FROM users WHERE role='spv' AND aktif=1");

// total proyek (boleh semua / atau aktif saja)
$total_proyek = qnum($conn, "SELECT COUNT(*) as total FROM proyek"); 
// kalau kamu mau hanya aktif:
// $total_proyek = qnum($conn, "SELECT COUNT(*) as total FROM proyek WHERE aktif=1");

// ==================
// STATISTIK ABSENSI TUKANG HARI INI
// ==================
$t_hadir  = qnum($conn, "SELECT COUNT(DISTINCT tukang_id) as total FROM absensi WHERE tanggal=CURDATE()");
$t_masuk  = qnum($conn, "SELECT COUNT(*) as total FROM absensi WHERE tanggal=CURDATE() AND jam_masuk IS NOT NULL");
$t_pulang = qnum($conn, "SELECT COUNT(*) as total FROM absensi WHERE tanggal=CURDATE() AND jam_pulang IS NOT NULL");
$t_lembur = qnum($conn, "SELECT COUNT(*) as total FROM absensi WHERE tanggal=CURDATE() AND lembur_menit IS NOT NULL AND lembur_menit > 0");

// ==================
// STATISTIK ABSENSI SPV HARI INI
// ==================
$s_hadir  = qnum($conn, "SELECT COUNT(DISTINCT spv_id) as total FROM absensi_spv WHERE tanggal=CURDATE()");
$s_masuk  = qnum($conn, "SELECT COUNT(*) as total FROM absensi_spv WHERE tanggal=CURDATE() AND jam_masuk IS NOT NULL");
$s_pulang = qnum($conn, "SELECT COUNT(*) as total FROM absensi_spv WHERE tanggal=CURDATE() AND jam_pulang IS NOT NULL");
$s_lembur = qnum($conn, "SELECT COUNT(*) as total FROM absensi_spv WHERE tanggal=CURDATE() AND lembur_menit IS NOT NULL AND lembur_menit > 0");

// ==================
// DATA ABSENSI TUKANG HARI INI
// ==================
$absensi_tukang = mysqli_query($conn, "
SELECT
    a.*,
    u.username AS nama_tukang,
    p.nama_proyek AS nama_proyek,
    s.username AS nama_spv
FROM absensi a
LEFT JOIN users u ON u.id = a.tukang_id
LEFT JOIN proyek p ON p.id = a.proyek_id
LEFT JOIN users s ON s.id = a.spv_id
WHERE a.tanggal BETWEEN '$date_from_sql' AND '$date_to_sql'
ORDER BY a.tanggal DESC, a.jam_masuk DESC
");

// ==================
// DATA ABSENSI SPV HARI INI
// ==================
$absensi_spv = mysqli_query($conn, "
SELECT
    a.*,
    u.username AS nama_spv,
    p.nama_proyek AS nama_proyek
FROM absensi_spv a
LEFT JOIN users u ON u.id = a.spv_id
LEFT JOIN proyek p ON p.id = a.proyek_id
WHERE a.tanggal BETWEEN '$date_from_sql' AND '$date_to_sql'
ORDER BY a.tanggal DESC, a.jam_masuk DESC
");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Dashboard Admin</title>
    <style>
        body{font-family:Arial;background:#f4f6f9;margin:0;padding:20px;}
        .box{background:white;padding:20px;border-radius:10px;box-shadow:0 2px 6px rgba(0,0,0,.1);margin-bottom:18px;}
        .grid{display:grid;grid-template-columns:repeat(6,1fr);gap:12px;margin-bottom:18px;}
        .btn{display:inline-block;background:#007bff;color:white;padding:8px 12px;text-decoration:none;border-radius:8px;margin-right:8px;}
        table{width:100%;border-collapse:collapse;background:white;}
        table th,table td{padding:10px;border-bottom:1px solid #ddd;font-size:12.5px;vertical-align:top;}
        table th{background:#eee;text-align:left;}
        .green{color:green;font-weight:bold;}
        .red{color:red;font-weight:bold;}
        .muted{color:#666;font-size:13px;}
        .row{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
        .section-title{margin:0 0 10px 0;}
        .pill{display:inline-block;padding:4px 8px;border-radius:999px;background:#eef2ff;font-size:12px}
    </style>
</head>
<body>

<h2>Dashboard Admin</h2>
<p class="muted">Selamat datang, <b><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></b></p>
<p class="muted">Tanggal: <?php echo htmlspecialchars($tanggal); ?></p>

<div class="box">
  <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:end">
    <div>
      <label class="muted">Dari</label><br>
      <input type="date" name="from" value="<?php echo htmlspecialchars($date_from); ?>" required>
    </div>
    <div>
      <label class="muted">Sampai</label><br>
      <input type="date" name="to" value="<?php echo htmlspecialchars($date_to); ?>" required>
    </div>
    <div>
      <button class="btn" type="submit">Terapkan</button>
      <a class="btn" href="admin.php" style="background:#6c757d;">Reset</a>
    </div>
  </form>
</div>

<!-- STATISTIK UMUM -->
<div class="grid">
    <div class="box"><b>Tukang Aktif</b><h2><?php echo $tukang_aktif; ?></h2></div>
    <div class="box"><b>SPV Aktif</b><h2><?php echo $spv_aktif; ?></h2></div>
    <div class="box"><b>Total Proyek</b><h2><?php echo $total_proyek; ?></h2></div>

    <div class="box">
        <b>Tukang Hadir</b>
        <h2><?php echo $t_hadir; ?></h2>
        <span class="pill">Masuk: <?php echo $t_masuk; ?> | Pulang: <?php echo $t_pulang; ?></span>
    </div>

    <div class="box">
        <b>SPV Hadir</b>
        <h2><?php echo $s_hadir; ?></h2>
        <span class="pill">Masuk: <?php echo $s_masuk; ?> | Pulang: <?php echo $s_pulang; ?></span>
    </div>

    <div class="box">
        <b>Lembur</b>
        <h2><?php echo ($t_lembur + $s_lembur); ?></h2>
        <span class="pill">Tukang: <?php echo $t_lembur; ?> | SPV: <?php echo $s_lembur; ?></span>
    </div>
</div>

<div class="box">
    <div class="row">
        <a class="btn" href="../admin/tambah_user.php">Tambah User</a>
        <a class="btn" href="../admin/tambah_proyek.php">Tambah Proyek</a>
        <a class="btn" href="../admin/proyek.php">List Proyek</a>
        <a class="btn" href="../admin/users.php"> List User</a>
    </div>
</div>

<!-- TABEL ABSENSI TUKANG -->
<div class="box">
    <h3 class="section-title">Absensi Tukang Hari Ini</h3>

    <table>
        <tr>
            <th>Tukang</th>
            <th>SPV</th>
            <th>Proyek</th>
            <th>Masuk</th>
            <th>Status</th>
            <th>Pulang</th>
            <th>Lembur</th>
            <th>Catatan</th>
            <th>Foto</th>
        </tr>

        <?php if($absensi_tukang && mysqli_num_rows($absensi_tukang) > 0){ ?>
            <?php while($r = mysqli_fetch_assoc($absensi_tukang)){ ?>
                <?php
                // RULE KAMU:
                // 01:00:00 - 08:00:00 => Good (hijau)
                // 08:01:00 - 23:59:59 => Telat (merah)
                $statusTelat = '-';
                if (!empty($r['jam_masuk'])) {
                    $jm = $r['jam_masuk']; // format HH:MM:SS
                    if ($jm >= '01:00:00' && $jm <= '08:00:00') {
                        $statusTelat = '<span class="green">Good</span>';
                    } else {
                        $statusTelat = '<span class="red">Telat</span>';
                    }
                }
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($r['nama_tukang'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($r['nama_spv'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($r['nama_proyek'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($r['jam_masuk'] ?? '-'); ?></td>
                    <td><?php echo $statusTelat; ?></td>
                    <td><?php echo htmlspecialchars($r['jam_pulang'] ?? '-'); ?></td>
                    <td><?php echo !empty($r['lembur_menit']) ? ((int)$r['lembur_menit'] . ' m') : '-'; ?></td>
                    <td><?php echo !empty($r['note_user']) ? nl2br(htmlspecialchars($r['note_user'])) : '-'; ?></td>
                    <td>
                        <?php if(!empty($r['foto_masuk'])){ ?>
                            <a href="../<?php echo htmlspecialchars($r['foto_masuk']); ?>" target="_blank">Masuk</a>
                        <?php } else { echo '-'; } ?>

                        <?php if(!empty($r['foto_pulang'])){ ?>
                            <?php echo " | "; ?>
                            <a href="../<?php echo htmlspecialchars($r['foto_pulang']); ?>" target="_blank">Pulang</a>
                        <?php } ?>
                    </td>
                </tr>
            <?php } ?>
        <?php } else { ?>
            <tr><td colspan="9" style="text-align:center;">Belum ada absensi tukang hari ini</td></tr>
        <?php } ?>
    </table>
</div>

<div class="box">
    <div class="row">
        <a class="btn" href="../admin/export_excel.php?from=<?php echo urlencode($date_from); ?>&to=<?php echo urlencode($date_to); ?>">
            Download Excel (Tukang)
        </a>
        
    </div>
    <p class="muted" style="margin-top:10px;">
        Catatan: export Excel yang sekarang masih untuk data tukang (tabel <b>absensi</b>).
    </p>
</div>

<!-- TABEL ABSENSI SPV -->
<div class="box">
    <h3 class="section-title">Absensi SPV Hari Ini</h3>

    <table>
        <tr>
            <th>SPV</th>
            <th>Proyek</th>
            <th>Masuk</th>
            <th>Pulang</th>
            <th>Lembur</th>
            <th>Target</th>
            <th>Laporan</th>
            <th>Foto</th>
        </tr>

        <?php if($absensi_spv && mysqli_num_rows($absensi_spv) > 0){ ?>
            <?php while($r = mysqli_fetch_assoc($absensi_spv)){ ?>
                <tr>
                    <td><?php echo htmlspecialchars($r['nama_spv'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($r['nama_proyek'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($r['jam_masuk'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($r['jam_pulang'] ?? '-'); ?></td>
                    <td><?php echo !empty($r['lembur_menit']) ? ((int)$r['lembur_menit'] . ' m') : '-'; ?></td>
                    <td><?php echo !empty($r['target']) ? nl2br(htmlspecialchars($r['target'])) : '-'; ?></td>
                    <td><?php echo !empty($r['target_selesai']) ? nl2br(htmlspecialchars($r['target_selesai'])) : '-'; ?></td>
                    <td>
                        <?php if(!empty($r['foto_masuk'])){ ?>
                            <a href="../<?php echo htmlspecialchars($r['foto_masuk']); ?>" target="_blank">Masuk</a>
                        <?php } else { echo '-'; } ?>

                        <?php if(!empty($r['foto_pulang'])){ ?>
                            <?php echo " | "; ?>
                            <a href="../<?php echo htmlspecialchars($r['foto_pulang']); ?>" target="_blank">Pulang</a>
                        <?php } ?>
                    </td>
                </tr>
            <?php } ?>
        <?php } else { ?>
            <tr><td colspan="8" style="text-align:center;">Belum ada absensi SPV hari ini</td></tr>
        <?php } ?>
    </table>
</div>

<div class="box">
    <div class="row">
        <a class="btn" href="../admin/export_excel_spv.php">Download Excel (SPV)</a>
    </div>
    <p class="muted" style="margin-top:10px;">
        Catatan: kalau file export SPV belum ada, nanti kita buat.
    </p>
</div>
<div class="box">
    <div class="row">
        <a class="btn" href="../auth/logout.php" style="background:#dc3545;">Logout</a>
    </div>
</div>
</body>
</html>