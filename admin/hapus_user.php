<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    die("Akses ditolak!");
}

$my_id = (int)$_SESSION['user_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) die("ID tidak valid");
if ($id === $my_id) die("Tidak boleh hapus akun sendiri");

// cek user ada
$q = mysqli_query($conn, "SELECT id, role FROM users WHERE id=$id LIMIT 1");
if (!$q || mysqli_num_rows($q) === 0) die("User tidak ditemukan");
$u = mysqli_fetch_assoc($q);

// proteksi: jangan hapus admin terakhir
if ($u['role'] === 'admin') {
    $qa = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role='admin' AND aktif=1");
    $totalAdmin = (int)mysqli_fetch_assoc($qa)['total'];
    if ($totalAdmin <= 1) die("Tidak bisa menghapus admin terakhir.");
}

// (opsional) bersihkan relasi absensi supaya tidak ada data yatim
if ($u['role'] === 'spv') {
    mysqli_query($conn, "DELETE FROM absensi_spv WHERE spv_id=$id");
    mysqli_query($conn, "UPDATE absensi SET spv_id=NULL WHERE spv_id=$id");
} else {
    mysqli_query($conn, "DELETE FROM absensi WHERE tukang_id=$id");
}

mysqli_query($conn, "DELETE FROM users WHERE id=$id LIMIT 1") or die(mysqli_error($conn));

header("Location: users.php");
exit;