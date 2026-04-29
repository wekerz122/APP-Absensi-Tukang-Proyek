<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    die("Akses ditolak!");
}

$my_id = (int)$_SESSION['user_id'];

$users = mysqli_query($conn, "
  SELECT id, username, role, aktif, created_at
  FROM users
  ORDER BY id DESC
");
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Manajemen User</title>
  <style>
    body{font-family:Arial;background:#f4f6f9;margin:0;padding:20px;}
    .box{background:#fff;padding:18px;border-radius:10px;box-shadow:0 2px 6px rgba(0,0,0,.1);margin-bottom:18px;}
    table{width:100%;border-collapse:collapse;background:#fff}
    th,td{padding:10px;border-bottom:1px solid #ddd;font-size:13px;vertical-align:top}
    th{background:#eee;text-align:left}
    .btn{display:inline-block;background:#007bff;color:#fff;padding:8px 12px;border-radius:8px;text-decoration:none;margin-right:8px}
    .btn-red{background:#dc3545}
    .btn-green{background:#28a745}
    .btn-gray{background:#6c757d}
    .pill{display:inline-block;padding:4px 8px;border-radius:999px;background:#eef2ff;font-size:12px}
    .pill-off{background:#ffe8e8}
  </style>
</head>
<body>

<h2>Manajemen User</h2>

<div class="box">
  <a class="btn" href="tambah_user.php">+ Tambah User</a>
  <a class="btn btn-gray" href="../dashboard/admin.php">← Kembali</a>
</div>

<div class="box">
  <table>
    <tr>
      <th>ID</th>
      <th>Username</th>
      <th>Role</th>
      <th>Status</th>
      <th>Dibuat</th>
      <th>Aksi</th>
    </tr>

    <?php if($users && mysqli_num_rows($users) > 0){ ?>
      <?php while($u = mysqli_fetch_assoc($users)){ 
        $id = (int)$u['id'];
        $is_me = ($id === $my_id);
      ?>
        <tr>
          <td><?php echo $id; ?></td>
          <td><?php echo htmlspecialchars($u['username']); ?></td>
          <td><?php echo htmlspecialchars($u['role']); ?></td>
          <td>
            <?php if((int)$u['aktif'] === 1){ ?>
              <span class="pill">Aktif</span>
            <?php } else { ?>
              <span class="pill pill-off">Nonaktif</span>
            <?php } ?>
          </td>
          <td><?php echo htmlspecialchars($u['created_at'] ?? '-'); ?></td>
          <td>
            <a class="btn" href="edit_user.php?id=<?php echo $id; ?>">Edit</a>
            <a class="btn" href="reset_password.php?id=<?php echo $id; ?>">Reset Password</a>

            <?php if(!$is_me){ ?>
              <?php if((int)$u['aktif'] === 1){ ?>
                <a class="btn btn-red"
                  href="users_toggle.php?id=<?php echo $id; ?>&aksi=off"
                  onclick="return confirm('Nonaktifkan user ini?')">Nonaktifkan</a>
              <?php } else { ?>
                <a class="btn btn-green"
                  href="users_toggle.php?id=<?php echo $id; ?>&aksi=on"
                  onclick="return confirm('Aktifkan user ini?')">Aktifkan</a>
              <?php } ?>

              <a class="btn btn-red"
                 href="hapus_user.php?id=<?php echo $id; ?>"
                 onclick="return confirm('Yakin hapus user ini? Data absensi terkait tetap ada, tapi user akan hilang.')">Hapus</a>
            <?php } else { ?>
              <span class="pill">Ini akun kamu</span>
            <?php } ?>
          </td>
        </tr>
      <?php } ?>
    <?php } else { ?>
      <tr><td colspan="6" style="text-align:center;">Belum ada user</td></tr>
    <?php } ?>

  </table>
</div>

</body>
</html>