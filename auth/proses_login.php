<?php
session_start();
include "../config/db.php";

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// basic guard
if ($username === '' || $password === '') {
    echo "Username / password kosong";
    exit;
}

// amankan input sedikit (minimal)
$username_safe = mysqli_real_escape_string($conn, $username);

// Ambil user + pastikan aktif
$query = mysqli_query($conn, "SELECT * FROM users WHERE username='$username_safe' AND aktif=1");
$data  = mysqli_fetch_assoc($query);

if ($data) {

    if (password_verify($password, $data['password'])) {

        $_SESSION['user_id']  = (int)$data['id'];
        $_SESSION['username'] = $data['username'];
        $_SESSION['role']     = $data['role'];

        // Redirect berdasarkan role
        if ($data['role'] == 'admin') {
            header("Location: ../dashboard/admin.php");
        } elseif ($data['role'] == 'spv') {
            header("Location: ../dashboard/spv.php");
        } elseif ($data['role'] == 'tukang') {
            header("Location: ../dashboard/tukang.php");
        } else {
            // role tidak dikenal
            session_destroy();
            echo "Role tidak dikenali";
        }
        exit;

    } else {
        echo "Password salah";
        exit;
    }

} else {
    echo "User tidak ditemukan / tidak aktif";
    exit;
}