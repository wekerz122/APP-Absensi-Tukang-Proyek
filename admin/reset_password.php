<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    die("Akses ditolak!");
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die("ID user tidak valid.");

// ambil user
$u = mysqli_query($conn, "SELECT id, username, role, aktif FROM users WHERE id=$id LIMIT 1");
$user = mysqli_fetch_assoc($u);
if (!$user) die("User tidak ditemukan.");
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Reset Password</title>
  <style>
    body{font-family:Arial;background:#f4f6f9;margin:0;padding:20px;}
    .box{background:#fff;padding:18px;border-radius:10px;box-shadow:0 2px 6px rgba(0,0,0,.1);max-width:650px}
    label{display:block;margin-top:10px}
    input{padding:8px;border-radius:8px;border:1px solid #ddd;width:100%;box-sizing:border-box}
    button{padding:10px 14px;border-radius:8px;border:0;background:#007bff;color:#fff;font-weight:bold;cursor:pointer;margin-top:14px}
    .btn{display:inline-block;padding:10px 14px;border-radius:8px;text-decoration:none;background:#999;color:#fff;margin-left:10px}
    .pill{display:inline-block;padding:4px 10px;border-radius:999px;background:#eef2ff;font-size:12px}
  </style>
</head>
<body>

<div class="box">
  <h3>Reset Password User</h3>

  <p>
    Username: <b><?php echo htmlspecialchars($user['username']); ?></b>
    <span class="pill"><?php echo htmlspecialchars($user['role']); ?></span>
    <span class="pill"><?php echo ((int)$user['aktif']===1) ? 'Aktif' : 'Nonaktif'; ?></span>
  </p>

  <form method="POST" action="proses_reset_password.php">
    <input type="hidden" name="id" value="<?php echo (int)$user['id']; ?>">

    <label>Password Baru *</label>
    <input type="password" name="password" required placeholder="contoh: tukang123">

    <label>Ulangi Password Baru *</label>
    <input type="password" name="password2" required placeholder="ulang lagi">

    <button type="submit">Reset Password</button>
    <a class="btn" href="users.php">Batal</a>
  </form>
</div>

</body>
</html>