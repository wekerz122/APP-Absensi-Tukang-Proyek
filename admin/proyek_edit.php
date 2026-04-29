<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die("Akses ditolak!");
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die("ID tidak valid.");

$proyek = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT * FROM proyek WHERE id=$id LIMIT 1")
);

if (!$proyek) die("Proyek tidak ditemukan.");

// proses update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama   = mysqli_real_escape_string($conn, $_POST['nama_proyek']);
    $lokasi = mysqli_real_escape_string($conn, $_POST['lokasi']);
    $mulai  = $_POST['tanggal_mulai'] ?: null;
    $selesai= $_POST['tanggal_selesai'] ?: null;

    mysqli_query($conn, "
        UPDATE proyek SET
        nama_proyek='$nama',
        lokasi='$lokasi',
        tanggal_mulai=".($mulai?"'$mulai'":"NULL").",
        tanggal_selesai=".($selesai?"'$selesai'":"NULL")."
        WHERE id=$id
        LIMIT 1
    ") or die(mysqli_error($conn));

    header("Location: proyek.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Edit Proyek</title>
    <style>
        body{font-family:Arial;background:#f4f6f9;margin:0;padding:20px;}
        .box{background:#fff;padding:18px;border-radius:10px;box-shadow:0 2px 6px rgba(0,0,0,.1);max-width:600px}
        input{padding:8px;border-radius:8px;border:1px solid #ddd;width:100%;margin-bottom:12px}
        button{padding:10px 14px;border-radius:8px;border:0;background:#007bff;color:#fff;font-weight:bold;cursor:pointer}
        .btn{display:inline-block;padding:8px 12px;border-radius:8px;text-decoration:none;background:#999;color:#fff}
    </style>
</head>
<body>

<div class="box">
    <h3>Edit Proyek</h3>

    <form method="POST">
        <label>Nama Proyek</label>
        <input type="text" name="nama_proyek" required
               value="<?php echo htmlspecialchars($proyek['nama_proyek']); ?>">

        <label>Lokasi</label>
        <input type="text" name="lokasi"
               value="<?php echo htmlspecialchars($proyek['lokasi']); ?>">

        <label>Tanggal Mulai</label>
        <input type="date" name="tanggal_mulai"
               value="<?php echo htmlspecialchars($proyek['tanggal_mulai']); ?>">

        <label>Tanggal Selesai</label>
        <input type="date" name="tanggal_selesai"
               value="<?php echo htmlspecialchars($proyek['tanggal_selesai']); ?>">

        <button type="submit">Simpan Perubahan</button>
        <a class="btn" href="proyek.php">Batal</a>
    </form>
</div>

</body>
</html>