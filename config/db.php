<?php
date_default_timezone_set('Asia/Jakarta');

$host = "127.0.0.1";
$user = "root";
$pass = "";
$database = "absensi_tukang";

$conn = mysqli_connect($host, $user, $pass, $database);
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// charset biar aman untuk utf8
mysqli_set_charset($conn, "utf8mb4");

// set timezone MySQL session ke WIB
if (!mysqli_query($conn, "SET time_zone = '+07:00'")) {
    // optional: kalau error, tampilkan untuk debug
    // die("Gagal set time_zone: " . mysqli_error($conn));
}