<?php
session_start();
include "../config/db.php";
include "../config/watermak.php";

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../auth/login.php");
    exit;
}

$role = $_SESSION['role'];
if ($role != 'tukang' && $role != 'spv') {
    die("Akses ditolak");
}

$user_id   = (int)$_SESSION['user_id'];
$proyek_id = (int)($_POST['proyek_id'] ?? 0);

// tukang wajib pilih spv, spv tidak perlu
$spv_id = 0;
if ($role === 'tukang') {
    $spv_id = (int)($_POST['spv_id'] ?? 0);
    if ($spv_id <= 0) die("Nama SPV wajib dipilih.");
}

if ($proyek_id <= 0) die("Proyek wajib dipilih.");

$lat = trim($_POST['lat'] ?? '');
$lng = trim($_POST['lng'] ?? '');

$note_user = trim($_POST['note'] ?? ''); // tukang: catatan, spv: target

// ambil nama proyek untuk watermark
$nama_proyek = "-";
$qP = mysqli_query($conn, "SELECT nama_proyek FROM proyek WHERE id=$proyek_id LIMIT 1");
if ($qP && ($rowP = mysqli_fetch_assoc($qP))) {
    $nama_proyek = $rowP['nama_proyek'] ?? '-';
}

// anti dobel: 1x per hari per user
if ($role === 'spv') {
    $cek = mysqli_query($conn, "SELECT id FROM absensi_spv WHERE spv_id=$user_id AND tanggal=CURDATE() LIMIT 1");
    if (mysqli_num_rows($cek) > 0) {
        header("Location: ../dashboard/spv.php");
        exit;
    }
} else {
    $cek = mysqli_query($conn, "SELECT id FROM absensi WHERE tukang_id=$user_id AND tanggal=CURDATE() LIMIT 1");
    if (mysqli_num_rows($cek) > 0) {
        header("Location: ../dashboard/tukang.php");
        exit;
    }
}

// upload foto masuk
$foto_path = null;

if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {

    if ($_FILES['foto']['size'] > 5 * 1024 * 1024) {
        die("Ukuran foto terlalu besar. Maks 5MB.");
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $_FILES['foto']['tmp_name']);
    finfo_close($finfo);

    $allowedMime = ['image/jpeg','image/png','image/webp'];
    if (!in_array($mime, $allowedMime)) {
        die("Format foto tidak didukung. Gunakan JPG/PNG/WebP.");
    }

    $dir = "../foto/";
    if (!is_dir($dir)) mkdir($dir, 0777, true);

    $ext = 'jpg';
    if ($mime === 'image/png')  $ext = 'png';
    if ($mime === 'image/webp') $ext = 'webp';

    $prefix = ($role === 'spv') ? "spv_masuk" : "masuk";
    $name = "{$prefix}_{$user_id}_" . date("Ymd_His") . "." . $ext;

    $dest = $dir . $name;

    if (!move_uploaded_file($_FILES['foto']['tmp_name'], $dest)) {
        die("Gagal upload foto.");
    }

    // WATERMARK
    $tgl = date("Y-m-d");
    $jam = date("H:i:s");

    $lines = [
        "Tanggal: $tgl  Jam: $jam",
        "Proyek: $nama_proyek",
        "GPS: " . ($lat !== '' ? $lat : '-') . ", " . ($lng !== '' ? $lng : '-')
    ];
    watermark_image($dest, $lines);

    $foto_path = "foto/" . $name;
}

// SQL values aman
$foto_sql = $foto_path ? "'" . mysqli_real_escape_string($conn, $foto_path) . "'" : "NULL";
$lat_sql  = ($lat !== '') ? "'" . mysqli_real_escape_string($conn, $lat) . "'" : "NULL";
$lng_sql  = ($lng !== '') ? "'" . mysqli_real_escape_string($conn, $lng) . "'" : "NULL";
$note_sql = ($note_user !== '') ? "'" . mysqli_real_escape_string($conn, $note_user) . "'" : "NULL";

// simpan sesuai role
if ($role === 'spv') {

    $sql = "
    INSERT INTO absensi_spv
    (spv_id, tanggal, jam_masuk, proyek_id, foto_masuk, lat_masuk, long_masuk, target)
    VALUES
    ($user_id, CURDATE(), CURRENT_TIME(), $proyek_id, $foto_sql, $lat_sql, $lng_sql, $note_sql)
    ";

    mysqli_query($conn, $sql) or die("DB Error: " . mysqli_error($conn));
    header("Location: ../dashboard/spv.php");
    exit;

} else {

    $sql = "
    INSERT INTO absensi
    (tukang_id, tanggal, jam_masuk, proyek_id, spv_id, foto_masuk, lat_masuk, long_masuk, note_user)
    VALUES
    ($user_id, CURDATE(), CURRENT_TIME(), $proyek_id, $spv_id, $foto_sql, $lat_sql, $lng_sql, $note_sql)
    ";

    mysqli_query($conn, $sql) or die("DB Error: " . mysqli_error($conn));
    header("Location: ../dashboard/tukang.php");
    exit;
}