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
  <title>Tambah User</title>
  <style>
    body{font-family:Arial;background:#f4f6f9;margin:0;padding:20px;}
    .box{background:#fff;padding:18px;border-radius:10px;box-shadow:0 2px 6px rgba(0,0,0,.1);max-width:650px}
    label{display:block;margin-top:10px}
    input,select{padding:8px;border-radius:8px;border:1px solid #ddd;width:100%;box-sizing:border-box}
    button{padding:10px 14px;border-radius:8px;border:0;background:#007bff;color:#fff;font-weight:bold;cursor:pointer;margin-top:14px}
    .btn{display:inline-block;padding:10px 14px;border-radius:8px;text-decoration:none;background:#999;color:#fff;margin-left:10px}
    .muted{color:#666;font-size:13px}
  </style>
</head>
<body>

<div class="box">
  <h3>Tambah User</h3>
  <p class="muted">Buat akun baru untuk <b>tukang</b> atau <b>spv</b>.</p>

  <form method="POST" action="proses_tambah_user.php" autocomplete="off">
    <label>Username *</label>
    <input type="text" name="username" required placeholder="contoh: tukang2 / spv2">

    <label>Password *</label>
    <input type="password" name="password" required placeholder="contoh: tukang123">

    <label>Role *</label>
    <select name="role" required>
      <option value="">-- Pilih Role --</option>
      <option value="tukang">Tukang</option>
      <option value="spv">SPV</option>
    </select>

    <label>Status</label>
    <select name="aktif">
      <option value="1" selected>Aktif</option>
      <option value="0">Nonaktif</option>
    </select>

    <button type="submit">Simpan</button>
    <a class="btn" href="../dashboard/admin.php">Batal</a>
  </form>
</div>

</body>
</html>