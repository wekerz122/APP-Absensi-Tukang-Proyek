<?php
session_start();
include "../config/db.php";

// Proteksi login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Proteksi role
if ($_SESSION['role'] != 'tukang') {
    echo "Akses ditolak!";
    exit;
}

$tukang_id = (int)$_SESSION['user_id'];
$tanggal   = date("Y-m-d");

// Ambil absensi hari ini
$cek = mysqli_query($conn, "SELECT * FROM absensi WHERE tukang_id=$tukang_id AND tanggal='$tanggal' LIMIT 1");
$data_hari_ini = mysqli_fetch_assoc($cek);

$sudah_masuk  = ($data_hari_ini && !empty($data_hari_ini['jam_masuk']));
$sudah_pulang = ($data_hari_ini && !empty($data_hari_ini['jam_pulang']));

// Riwayat 10 terakhir
$riwayat = mysqli_query($conn, "
    SELECT id, tanggal, jam_masuk, jam_pulang, lembur_menit
    FROM absensi
    WHERE tukang_id=$tukang_id
    ORDER BY tanggal DESC, id DESC
    LIMIT 10
");

$username = htmlspecialchars($_SESSION['username'] ?? 'Tukang');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Dashboard Tukang</title>
    <link rel="stylesheet" href="../assets/css/app.css">
</head>
<body>

<div class="container">

    <div class="topbar">
        <div class="brand">
            <div class="logo"></div>
            <div class="title">
                <b>Dashboard Kamu</b>
                <small>Halo, <?= $username; ?> • Tanggal: <?= htmlspecialchars($tanggal); ?></small>
            </div>
        </div>

        <div class="actions">
            <a class="btn btn-ghost" href="../auth/logout.php">Logout</a>
        </div>
    </div>

    <div class="grid">
        <!-- Status hari ini -->
        <div class="card">
            <div class="card-header">
                <div>
                    <h2>Status Hari Ini</h2>
                    <p>Absen masuk / pulang & catatan hari ini</p>
                </div>

                <?php if (!$data_hari_ini) { ?>
                    <span class="status warn"><span class="dot"></span>Belum Absen</span>
                <?php } else if ($sudah_masuk && !$sudah_pulang) { ?>
                    <span class="status"><span class="dot"></span>Sudah Masuk</span>
                <?php } else { ?>
                    <span class="status ok"><span class="dot"></span>Lengkap</span>
                <?php } ?>
            </div>

            <div class="card-body">

                <?php if (!$data_hari_ini) { ?>
                    <div class="alert warn">
                        Belum ada absensi hari ini. Silakan absen masuk dulu.
                    </div>

                    <div class="row" style="margin-top:12px;">
                        <a class="btn btn-primary" href="../absensi/masuk.php">Absen Masuk</a>
                    </div>

                <?php } else { ?>

                    <div class="kpi">
                        <div class="kpi-item">
                            <div class="kpi-label">Jam Masuk</div>
                            <div class="kpi-value">
                                <?= !empty($data_hari_ini['jam_masuk']) ? htmlspecialchars($data_hari_ini['jam_masuk']) : '-'; ?>
                            </div>
                        </div>

                        <div class="kpi-item">
                            <div class="kpi-label">Jam Pulang</div>
                            <div class="kpi-value">
                                <?= !empty($data_hari_ini['jam_pulang']) ? htmlspecialchars($data_hari_ini['jam_pulang']) : '-'; ?>
                            </div>
                        </div>

                        <div class="kpi-item">
                            <div class="kpi-label">Lembur (menit)</div>
                            <div class="kpi-value">
                                <?= ($data_hari_ini['lembur_menit'] !== null && $data_hari_ini['lembur_menit'] !== '') ? (int)$data_hari_ini['lembur_menit'] : '-'; ?>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($data_hari_ini['note_user'])) { ?>
                        <div class="note-box" style="margin-top:12px;">
                            <div class="note-title">Catatan</div>
                            <div class="note-body"><?= nl2br(htmlspecialchars($data_hari_ini['note_user'])); ?></div>
                        </div>
                    <?php } ?>

                    <div class="row" style="margin-top:12px;">
                        <?php if (!$sudah_masuk) { ?>
                            <a class="btn btn-primary" href="../absensi/masuk.php">Absen Masuk</a>
                        <?php } ?>

                        <?php if ($sudah_masuk && !$sudah_pulang) { ?>
                            <a class="btn btn-danger" href="../absensi/pulang.php">Absen Pulang</a>
                        <?php } ?>

                        <?php if ($sudah_pulang) { ?>
                            <span class="badge">✅ Absensi hari ini sudah lengkap</span>
                        <?php } ?>
                    </div>

                    <div style="margin-top:12px;">
                        <?php if (!empty($data_hari_ini['foto_masuk'])) { ?>
                            <a class="link" href="../<?= htmlspecialchars($data_hari_ini['foto_masuk']); ?>" target="_blank">Lihat Foto Masuk</a>
                        <?php } ?>

                        <?php if (!empty($data_hari_ini['foto_pulang'])) { ?>
                            <?php if (!empty($data_hari_ini['foto_masuk'])) echo " <span class='sep'>•</span> "; ?>
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
                <h2>Riwayat Absensi (10 Terakhir)</h2>
                <p>Catatan absen terakhir untuk <?= $username; ?></p>
            </div>
        </div>

        <div class="card-body">
            <div class="table-wrap">
<table border="1" style="width:100%; border-collapse:collapse; color:white;">
    <thead style="background:rgba(255,255,255,0.1);">
        <tr>
            <th style="padding:10px;">Tanggal</th>
            <th style="padding:10px;">Masuk</th>
            <th style="padding:10px;">Pulang</th>
            <th style="padding:10px;">Lembur (menit)</th>
        </tr>
    </thead>

    <tbody>
    <?php if(mysqli_num_rows($riwayat) > 0){ ?>
        <?php while($r = mysqli_fetch_assoc($riwayat)){ ?>
            <tr>
                <td style="padding:10px;"><?= htmlspecialchars($r['tanggal']); ?></td>
                <td style="padding:10px;"><?= !empty($r['jam_masuk']) ? $r['jam_masuk'] : '-'; ?></td>
                <td style="padding:10px;"><?= !empty($r['jam_pulang']) ? $r['jam_pulang'] : '-'; ?></td>
                <td style="padding:10px;"><?= !empty($r['lembur_menit']) ? $r['lembur_menit'] : '-'; ?></td>
            </tr>
        <?php } ?>
    <?php } else { ?>
        <tr>
            <td colspan="4" style="text-align:center; padding:15px;">
                Belum ada riwayat absensi
            </td>
        </tr>
    <?php } ?>
    </tbody>
</table>
</div>
        </div>
    </div>

    <div class="footer">
        © <?= date('Y'); ?> Dewan Nanda
    </div>

</div>

</body>
</html>