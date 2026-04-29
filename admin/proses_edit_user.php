<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    die("Akses ditolak!");
}

$my_id = (int)$_SESSION['user_id'];

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$username = trim($_POST['username'] ?? '');
$role = trim($_POST['role'] ?? '');
$aktif = isset($_POST['aktif']) ? (int)$_POST['aktif'] : 1;

if ($id <= 0) die("ID tidak valid");
if ($username === '') die("Username wajib diisi");

$allowed_roles = ['admin','spv','tukang'];
if (!in_array($role, $allowed_roles, true)) die("Role tidak valid");
if (!in_array($aktif, [0,1], true)) $aktif = 1;

// Ambil data existing
$q = mysqli_query($conn, "SELECT id, username, role, aktif FROM users WHERE id=$id LIMIT 1");
if (!$q || mysqli_num_rows($q) === 0) die("User tidak ditemukan");
$old = mysqli_fetch_assoc($q);

// Proteksi akun sendiri: tidak boleh ganti role & tidak boleh nonaktif
if ($id === $my_id) {
    $role  = $old['role'];
    $aktif = (int)$old['aktif'];
}

// Cek username unik (kecuali dirinya sendiri)
$username_esc = mysqli_real_escape_string($conn, $username);
$cek = mysqli_query($conn, "SELECT id FROM users WHERE username='$username_esc' AND id<>$id LIMIT 1");
if ($cek && mysqli_num_rows($cek) > 0) {
    die("Username sudah dipakai user lain. Silakan pilih username lain.");
}

// Update
mysqli_query($conn, "
  UPDATE users
  SET username='$username_esc',
      role='".mysqli_real_escape_string($conn,$role)."',
      aktif=$aktif
  WHERE id=$id
  LIMIT 1
") or die("DB error: " . mysqli_error($conn));

header("Location: users.php");
exit;