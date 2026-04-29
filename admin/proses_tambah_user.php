<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    die("Akses ditolak!");
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$role     = $_POST['role'] ?? '';
$aktif    = (int)($_POST['aktif'] ?? 1);

if ($username === '' || $password === '' || $role === '') {
    die("Username, password, dan role wajib diisi.");
}

if (!in_array($role, ['tukang','spv'], true)) {
    die("Role tidak valid.");
}

if ($aktif !== 0 && $aktif !== 1) $aktif = 1;

// username aman + sederhana
if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
    die("Username hanya boleh huruf/angka/underscore, panjang 3-30.");
}

// cek duplikat
$u = mysqli_real_escape_string($conn, $username);
$cek = mysqli_query($conn, "SELECT id FROM users WHERE username='$u' LIMIT 1");
if (mysqli_num_rows($cek) > 0) {
    die("Username sudah dipakai. Silakan pakai username lain.");
}

// hash password
$hash = password_hash($password, PASSWORD_DEFAULT);
$hash_sql = mysqli_real_escape_string($conn, $hash);

mysqli_query($conn, "
    INSERT INTO users (username, password, role, aktif)
    VALUES ('$u', '$hash_sql', '$role', $aktif)
") or die("DB Error: " . mysqli_error($conn));

// balik dashboard admin
header("Location: ../dashboard/admin.php");
exit;