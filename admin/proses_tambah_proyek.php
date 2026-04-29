<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    die("Akses ditolak!");
}

$nama_proyek = trim($_POST['nama_proyek'] ?? '');
$lokasi      = trim($_POST['lokasi'] ?? '');
$tgl_mulai   = $_POST['tanggal_mulai'] ?? '';
$tgl_selesai = $_POST['tanggal_selesai'] ?? '';

if ($nama_proyek === '') {
    die("Nama proyek wajib diisi.");
}

$nama_proyek_sql = mysqli_real_escape_string($conn, $nama_proyek);
$lokasi_sql      = mysqli_real_escape_string($conn, $lokasi);

// validasi tanggal (opsional)
$mulai_sql   = ($tgl_mulai !== '') ? "'" . mysqli_real_escape_string($conn, $tgl_mulai) . "'" : "NULL";
$selesai_sql = ($tgl_selesai !== '') ? "'" . mysqli_real_escape_string($conn, $tgl_selesai) . "'" : "NULL";

if ($tgl_mulai !== '' && $tgl_selesai !== '' && strtotime($tgl_selesai) < strtotime($tgl_mulai)) {
    die("Tanggal selesai tidak boleh lebih kecil dari tanggal mulai.");
}

mysqli_query($conn, "
    INSERT INTO proyek (nama_proyek, lokasi, tanggal_mulai, tanggal_selesai, aktif)
    VALUES ('$nama_proyek_sql', '$lokasi_sql', $mulai_sql, $selesai_sql, 1)
") or die("DB Error: " . mysqli_error($conn));

header("Location: proyek.php");
exit;