<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    die("Akses ditolak!");
}

$proyek = mysqli_query($conn, "
    SELECT id, nama_proyek, lokasi, tanggal_mulai, tanggal_selesai, aktif
    FROM proyek
    ORDER BY id DESC
");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Manajemen Proyek</title>
    <style>
        body{font-family:Arial;background:#f4f6f9;margin:0;padding:20px;}
        .box{background:#fff;padding:18px;border-radius:10px;box-shadow:0 2px 6px rgba(0,0,0,.1);margin-bottom:18px;}
        table{width:100%;border-collapse:collapse;background:white;}
        th,td{padding:10px;border-bottom:1px solid #ddd;font-size:13px;vertical-align:top;}
        th{background:#eee;text-align:left;}
        .btn{display:inline-block;background:#007bff;color:white;padding:8px 12px;text-decoration:none;border-radius:8px;margin-right:8px;}
        .btn-red{background:#dc3545;}
        .btn-green{background:#28a745;}
        .muted{color:#666;font-size:13px;}
        .pill{display:inline-block;padding:4px 8px;border-radius:999px;background:#eef2ff;font-size:12px}
        .pill-off{background:#ffe8e8;}
        .aksi a{margin-bottom:6px;}
    </style>
</head>
<body>

<h2>Manajemen Proyek</h2>
<p class="muted">Admin: <b><?php echo htmlspecialchars($_SESSION['username'] ?? 'admin'); ?></b></p>

<div class="box">
    <a class="btn" href="tambah_proyek.php">+ Tambah Proyek</a>
    <a class="btn" href="../dashboard/admin.php">← Kembali ke Dashboard</a>
</div>

<div class="box">
    <h3>Daftar Proyek</h3>

    <table>
        <tr>
            <th>ID</th>
            <th>Nama Proyek</th>
            <th>Lokasi</th>
            <th>Tgl Mulai</th>
            <th>Tgl Selesai</th>
            <th>Status</th>
            <th>Aksi</th>
        </tr>

        <?php if($proyek && mysqli_num_rows($proyek) > 0){ ?>
            <?php while($p = mysqli_fetch_assoc($proyek)){ ?>
                <tr>
                    <td><?php echo (int)$p['id']; ?></td>
                    <td><?php echo htmlspecialchars($p['nama_proyek']); ?></td>
                    <td><?php echo htmlspecialchars($p['lokasi'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($p['tanggal_mulai'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($p['tanggal_selesai'] ?? '-'); ?></td>

                    <!-- STATUS -->
                    <td>
                        <?php if((int)$p['aktif'] === 1){ ?>
                            <span class="pill">Aktif</span>
                        <?php } else { ?>
                            <span class="pill pill-off">Nonaktif</span>
                        <?php } ?>
                    </td>

                    <!-- AKSI -->
                    <td class="aksi">
                        <a class="btn" href="proyek_edit.php?id=<?php echo (int)$p['id']; ?>">Edit</a>

                        <?php if((int)$p['aktif'] === 1){ ?>
                            <a class="btn btn-red"
                               href="proyek_toggle.php?id=<?php echo (int)$p['id']; ?>&aksi=off"
                               onclick="return confirm('Nonaktifkan proyek ini?')">
                               Nonaktifkan
                            </a>
                        <?php } else { ?>
                            <a class="btn btn-green"
                               href="proyek_toggle.php?id=<?php echo (int)$p['id']; ?>&aksi=on"
                               onclick="return confirm('Aktifkan proyek ini?')">
                               Aktifkan
                            </a>
                        <?php } ?>
                    </td>
                </tr>
            <?php } ?>
        <?php } else { ?>
            <tr><td colspan="7" style="text-align:center;">Belum ada proyek</td></tr>
        <?php } ?>
    </table>
</div>

</body>
</html>