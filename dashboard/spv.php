<?php
session_start();
include "../config/db.php";

// Proteksi login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Proteksi role SPV
if ($_SESSION['role'] != 'spv') {
    echo "Akses ditolak!";
    exit;
}

$spv_id  = (int)$_SESSION['user_id'];
$tanggal = date("Y-m-d");

// Ambil absensi SPV hari ini
$cek = mysqli_query($conn, "SELECT * FROM absensi_spv WHERE spv_id=$spv_id AND tanggal='$tanggal' LIMIT 1");
$data_hari_ini = mysqli_fetch_assoc($cek);

$sudah_masuk  = ($data_hari_ini && !empty($data_hari_ini['jam_masuk']));
$sudah_pulang = ($data_hari_ini && !empty($data_hari_ini['jam_pulang']));

// Riwayat 10 terakhir (SPV)
$riwayat = mysqli_query($conn, "
    SELECT tanggal, jam_masuk, jam_pulang, lembur_menit, target, target_selesai, proyek_id
    FROM absensi_spv
    WHERE spv_id=$spv_id
    ORDER BY tanggal DESC
    LIMIT 10
");

// Mapping proyek_id -> nama_proyek
$proyek_map = [];
$pq = mysqli_query($conn, "SELECT id, nama_proyek FROM proyek");
while($p = mysqli_fetch_assoc($pq)){
    $proyek_map[(int)$p['id']] = $p['nama_proyek'];
}

$username = htmlspecialchars($_SESSION['username'] ?? 'SPV');

// Info hari ini
$pid_today = (int)($data_hari_ini['proyek_id'] ?? 0);
$nama_proyek_today = htmlspecialchars($proyek_map[$pid_today] ?? '-');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Panel SPV</title>
  <link rel="stylesheet" href="../assets/css/app.css">
</head>

<body class="spv-theme">

<div class="container">

  <!-- Topbar -->
  <div class="topbar spv-topbar">
    <div class="brand">
      <div class="logo spv-logo"></div>
      <div class="title">
        <b>Panel SPV</b>
        <small>
          Monitoring Proyek<br>
          Halo, <?= $username; ?> • <?= htmlspecialchars($tanggal); ?>
        </small>
      </div>
    </div>

    <div class="actions">
      <a class="btn btn-ghost" href="../auth/logout.php">Logout</a>
    </div>
  </div>

  <!-- Row 1: Panel status SPV -->
  <div class="spv-layout" style="margin-top:16px;">

    <div class="card">
      <div class="card-header">
        <div>
          <h2>Status SPV Hari Ini</h2>
          <p>Absen masuk (isi target) dan absen pulang (isi laporan)</p>
        </div>

        <?php if(!$data_hari_ini){ ?>
          <span class="status warn"><span class="dot"></span>Belum Absen</span>
        <?php } else if($sudah_masuk && !$sudah_pulang){ ?>
          <span class="status"><span class="dot"></span>Sudah Masuk</span>
        <?php } else { ?>
          <span class="status ok"><span class="dot"></span>Lengkap</span>
        <?php } ?>
      </div>

      <div class="card-body">

        <?php if(!$data_hari_ini){ ?>

          <div class="alert warn">
            Anda belum melakukan absensi hari ini. Silakan absen masuk sambil isi target.
          </div>

          <div class="row" style="margin-top:12px;">
            <a class="btn btn-primary btn-block" href="../absensi/masuk.php">Absen Masuk + Target</a>
          </div>

        <?php } else { ?>

          <div class="spv-kpi">
            <div class="kpi-item">
              <div class="kpi-label">Proyek</div>
              <div class="kpi-value spv-kpi-text"><?= $nama_proyek_today; ?></div>
            </div>

            <div class="kpi-item">
              <div class="kpi-label">Masuk</div>
              <div class="kpi-value"><?= htmlspecialchars($data_hari_ini['jam_masuk'] ?? '-'); ?></div>
            </div>

            <div class="kpi-item">
              <div class="kpi-label">Pulang</div>
              <div class="kpi-value"><?= htmlspecialchars($data_hari_ini['jam_pulang'] ?? '-'); ?></div>
            </div>

            <div class="kpi-item">
              <div class="kpi-label">Lembur (menit)</div>
              <div class="kpi-value"><?= !empty($data_hari_ini['lembur_menit']) ? (int)$data_hari_ini['lembur_menit'] : '-'; ?></div>
            </div>
          </div>

          <?php if(!empty($data_hari_ini['target'])){ ?>
            <div class="note-box" style="margin-top:12px;">
              <div class="note-title">Target</div>
              <div class="note-body"><?= nl2br(htmlspecialchars($data_hari_ini['target'])); ?></div>
            </div>
          <?php } ?>

          <?php if(!empty($data_hari_ini['target_selesai'])){ ?>
            <div class="note-box" style="margin-top:12px;">
              <div class="note-title">Laporan Selesai</div>
              <div class="note-body"><?= nl2br(htmlspecialchars($data_hari_ini['target_selesai'])); ?></div>
            </div>
          <?php } ?>

          <div class="row" style="margin-top:12px;">
            <?php if(!$sudah_masuk){ ?>
              <a class="btn btn-primary" href="../absensi/masuk.php">Absen Masuk + Target</a>
            <?php } ?>

            <?php if($sudah_masuk && !$sudah_pulang){ ?>
              <a class="btn btn-danger" href="../absensi/pulang.php">Absen Pulang + Laporan</a>
            <?php } ?>

            <?php if($sudah_pulang){ ?>
              <span class="badge">✅ Absensi hari ini sudah lengkap</span>
            <?php } ?>
          </div>

          <div style="margin-top:12px;">
            <?php if(!empty($data_hari_ini['foto_masuk'])){ ?>
              <a class="link" href="../<?= htmlspecialchars($data_hari_ini['foto_masuk']); ?>" target="_blank">Lihat Foto Masuk</a>
            <?php } ?>
            <?php if(!empty($data_hari_ini['foto_pulang'])){ ?>
              <?php if(!empty($data_hari_ini['foto_masuk'])) echo " <span class='sep'>•</span> "; ?>
              <a class="link" href="../<?= htmlspecialchars($data_hari_ini['foto_pulang']); ?>" target="_blank">Lihat Foto Pulang</a>
            <?php } ?>
          </div>

        <?php } ?>

      </div>
    </div>
  </div>

  <!-- Riwayat -->
  <div class="card" style="margin-top:16px;">
    <div class="card-header">
      <div>
        <h2>Riwayat Absensi SPV (10 Terakhir)</h2>
        <p>Scroll tabel ke kanan jika layar HP kecil</p>
      </div>
    </div>

    <div class="card-body">
      <div class="table-wrap spv-table">
        <table>
          <thead>
            <tr>
              <th>Tanggal</th>
              <th>Proyek</th>
              <th>Masuk</th>
              <th>Pulang</th>
              <th>Lembur</th>
              <th>Target</th>
              <th>Laporan</th>
            </tr>
          </thead>

          <tbody>
          <?php if(mysqli_num_rows($riwayat) > 0){ ?>
            <?php while($r = mysqli_fetch_assoc($riwayat)){ ?>
              <?php $pid = (int)($r['proyek_id'] ?? 0); ?>
              <tr>
                <td><?= htmlspecialchars($r['tanggal']); ?></td>
                <td><?= htmlspecialchars($proyek_map[$pid] ?? '-'); ?></td>
                <td><?= htmlspecialchars($r['jam_masuk'] ?? '-'); ?></td>
                <td><?= htmlspecialchars($r['jam_pulang'] ?? '-'); ?></td>
                <td><?= !empty($r['lembur_menit']) ? ((int)$r['lembur_menit'].' m') : '-'; ?></td>
                <td><?= !empty($r['target']) ? nl2br(htmlspecialchars($r['target'])) : '-'; ?></td>
                <td><?= !empty($r['target_selesai']) ? nl2br(htmlspecialchars($r['target_selesai'])) : '-'; ?></td>
              </tr>
            <?php } ?>
          <?php } else { ?>
            <tr><td colspan="7" style="text-align:center;" class="muted">Belum ada riwayat absensi</td></tr>
          <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="footer">© <?= date('Y'); ?> Absensi Proyek</div>

</div>

</body>
</html>