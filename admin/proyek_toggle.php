<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die("Akses ditolak!");
}

$id = (int)($_GET['id'] ?? 0);
$aksi = $_GET['aksi'] ?? '';

if ($id <= 0) die("ID tidak valid.");

if ($aksi === 'on') {
    mysqli_query($conn, "UPDATE proyek SET aktif=1 WHERE id=$id LIMIT 1") or die(mysqli_error($conn));
} elseif ($aksi === 'off') {
    mysqli_query($conn, "UPDATE proyek SET aktif=0 WHERE id=$id LIMIT 1") or die(mysqli_error($conn));
} else {
    die("Aksi tidak valid.");
}

header("Location: proyek.php");
exit;