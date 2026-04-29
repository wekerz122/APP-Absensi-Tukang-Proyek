<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    die("Akses ditolak!");
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Tambah Proyek</title>
    <style>
        body{font-family:Arial;background:#f4f6f9;margin:0;padding:20px;}
        .box{background:#fff;padding:18px;border-radius:10px;box-shadow:0 2px 6px rgba(0,0,0,.1);max-width:650px}
        label{display:block;margin-top:10px}
        input,textarea{padding:8px;border-radius:8px;border:1px solid #ddd;width:100%;box-sizing:border-box}
        button{padding:10px 14px;border-radius:8px;border:0;background:#007bff;color:#fff;font-weight:bold;cursor:pointer;margin-top:14px}
        .btn{display:inline-block;padding:10px 14px;border-radius:8px;text-decoration:none;background:#999;color:#fff;margin-left:10px}
        .muted{color:#666;font-size:13px}
    </style>
</head>
<body>

<div class="box">
    <h3>Tambah Proyek</h3>
    <p class="muted">Isi data proyek. Proyek baru otomatis dibuat <b>aktif</b>.</p>

    <form method="POST" action="proses_tambah_proyek.php">
        <label>Nama Proyek *</label>
        <input type="text" name="nama_proyek" required>

        <label>Lokasi (opsional)</label>
        <input type="text" name="lokasi" placeholder="Contoh: Bekasi, Rawalumbu">

        <label>Tanggal Mulai (opsional)</label>
        <input type="date" name="tanggal_mulai">

        <label>Tanggal Selesai (opsional)</label>
        <input type="date" name="tanggal_selesai">

        <button type="submit">Simpan</button>
        <a class="btn" href="proyek.php">Batal</a>
    </form>
</div>

</body>
</html>