<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    die("Akses ditolak!");
}

$my_id = (int)$_SESSION['user_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) die("ID tidak valid");

$q = mysqli_query($conn, "SELECT id, username, role, aktif, created_at FROM users WHERE id=$id LIMIT 1");
if (!$q || mysqli_num_rows($q) === 0) die("User tidak ditemukan");
$u = mysqli_fetch_assoc($q);

$is_me = ((int)$u['id'] === $my_id);
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Edit User</title>
  <style>
    body{font-family:Arial;background:#f4f6f9;margin:0;padding:20px;}
    .box{background:#fff;padding:18px;border-radius:10px;box-shadow:0 2px 6px rgba(0,0,0,.1);max-width:720px}
    label{display:block;margin-top:10px}
    input,select{padding:10px;border-radius:8px;border:1px solid #ddd;width:100%;max-width:520px}
    .btn{display:inline-block;background:#007bff;color:#fff;padding:10px 14px;border-radius:8px;text-decoration:none;border:0;cursor:pointer;margin-right:8px}
    .btn-gray{background:#6c757d}
    .muted{color:#666;font-size:13px}
    .pill{display:inline-block;padding:4px 8px;border-radius:999px;background:#eef2ff;font-size:12px}
  </style>
</head>
<body>

<h2>Edit User</h2>
<p class="muted">ID: <b><?php echo (int)$u['id']; ?></b> | Dibuat: <?php echo htmlspecialchars($u['created_at'] ?? '-'); ?></p>

<div class="box">
  <?php if($is_me){ ?>
    <p class="pill">Ini akun kamu (ada proteksi supaya tidak bisa nonaktif / ganti role)</p>
  <?php } ?>

  <form method="POST" action="proses_edit_user.php">
    <input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>">

    <label>Username</label>
    <input type="text" name="username" value="<?php echo htmlspecialchars($u['username']); ?>" required>

    <label>Role</label>
    <select name="role" <?php echo $is_me ? 'disabled' : ''; ?> required>
      <?php
        $roles = ['admin'=>'admin', 'spv'=>'spv', 'tukang'=>'tukang'];
        foreach($roles as $val=>$label){
          $sel = ($u['role'] === $val) ? 'selected' : '';
          echo "<option value='".htmlspecialchars($val)."' $sel>".htmlspecialchars(strtoupper($label))."</option>";
        }
      ?>
    </select>
    <?php if($is_me){ ?>
      <input type="hidden" name="role" value="<?php echo htmlspecialchars($u['role']); ?>">
    <?php } ?>

    <label>Status</label>
    <select name="aktif" <?php echo $is_me ? 'disabled' : ''; ?> required>
      <option value="1" <?php echo ((int)$u['aktif'] === 1) ? 'selected' : ''; ?>>Aktif</option>
      <option value="0" <?php echo ((int)$u['aktif'] === 0) ? 'selected' : ''; ?>>Nonaktif</option>
    </select>
    <?php if($is_me){ ?>
      <input type="hidden" name="aktif" value="<?php echo (int)$u['aktif']; ?>">
    <?php } ?>

    <div style="margin-top:14px;">
      <button class="btn" type="submit">Simpan</button>
      <a class="btn btn-gray" href="users.php">Batal</a>
    </div>
  </form>
</div>

</body>
</html>