<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    die("Akses ditolak!");
}

$id = (int)($_GET['id'] ?? 0);
$aksi = $_GET['aksi'] ?? '';

if ($id <= 0) die("ID tidak valid.");
if ($aksi !== 'on' && $aksi !== 'off') die("Aksi tidak valid.");

$val = ($aksi === 'on') ? 1 : 0;

// jangan sampai admin menonaktifkan dirinya sendiri (opsional tapi aman)
if ($id === (int)$_SESSION['user_id'] && $val === 0) {
    die("Tidak bisa menonaktifkan akun admin yang sedang login.");
}

mysqli_query($conn, "UPDATE users SET aktif=$val WHERE id=$id LIMIT 1") or die(mysqli_error($conn));

header("Location: users.php");
exit;