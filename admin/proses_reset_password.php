<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    die("Akses ditolak!");
}

$id = (int)($_POST['id'] ?? 0);
$pw1 = $_POST['password'] ?? '';
$pw2 = $_POST['password2'] ?? '';

if ($id <= 0) die("ID tidak valid.");
if ($pw1 === '' || $pw2 === '') die("Password tidak boleh kosong.");
if ($pw1 !== $pw2) die("Konfirmasi password tidak sama.");
if (strlen($pw1) < 6) die("Password minimal 6 karakter.");

// cek user ada
$cek = mysqli_query($conn, "SELECT id FROM users WHERE id=$id LIMIT 1");
if (mysqli_num_rows($cek) === 0) die("User tidak ditemukan.");

// hash & update
$hash = password_hash($pw1, PASSWORD_DEFAULT);
$hash_sql = mysqli_real_escape_string($conn, $hash);

mysqli_query($conn, "UPDATE users SET password='$hash_sql' WHERE id=$id LIMIT 1")
  or die("DB error: " . mysqli_error($conn));

header("Location: users.php");
exit;