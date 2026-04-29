<?php
session_start();
include "../config/db.php";
include "../config/watermark.php";

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

if ($proyek_id <= 0) die("Proyek wajib dipilih.");

// tukang wajib pilih spv, spv tidak perlu
$spv_id = 0;
if ($role === 'tukang') {
    $spv_id = (int)($_POST['spv_id'] ?? 0);
    if ($spv_id <= 0) die("Nama SPV wajib dipilih.");
}

$lat_pulang  = trim($_POST['lat_pulang'] ?? '');
$long_pulang = trim($_POST['long_pulang'] ?? '');

$note_user = trim($_POST['note_user'] ?? ''); 
// tukang: catatan pulang
// spv: laporan/target selesai

// ambil nama proyek untuk watermark
$nama_proyek = "-";
$qP = mysqli_query($conn, "SELECT nama_proyek FROM proyek WHERE id=$proyek_id LIMIT 1");
if ($qP && ($rowP = mysqli_fetch_assoc($qP))) {
    $nama_proyek = $rowP['nama_proyek'] ?? '-';
}

// ambil record masuk hari ini
if ($role === 'spv') {
    $cek = mysqli_query($conn, "SELECT * FROM absensi_spv WHERE spv_id=$user_id AND tanggal=CURDATE() LIMIT 1");
    if (mysqli_num_rows($cek) == 0) {
        header("Location: ../dashboard/spv.php");
        exit;
    }
    $row = mysqli_fetch_assoc($cek);
    $absensi_id = (int)$row['id'];
} else {
    $cek = mysqli_query($conn, "SELECT * FROM absensi WHERE tukang_id=$user_id AND tanggal=CURDATE() LIMIT 1");
    if (mysqli_num_rows($cek) == 0) {
        header("Location: ../dashboard/tukang.php");
        exit;
    }
    $row = mysqli_fetch_assoc($cek);
    $absensi_id = (int)$row['id'];
}

// upload foto pulang
$foto_path = null;
$input_name = ($role === 'spv') ? "foto_pulang" : "foto_pulang"; // sama

if (isset($_FILES[$input_name]) && $_FILES[$input_name]['error'] === UPLOAD_ERR_OK) {

    if ($_FILES[$input_name]['size'] > 5 * 1024 * 1024) {
        die("Ukuran foto terlalu besar. Maks 5MB.");
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $_FILES[$input_name]['tmp_name']);
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

    $prefix = ($role === 'spv') ? "spv_pulang" : "pulang";
    $name = "{$prefix}_{$user_id}_" . date("Ymd_His") . "." . $ext;

    $dest = $dir . $name;

    if (!move_uploaded_file($_FILES[$input_name]['tmp_name'], $dest)) {
        die("Gagal upload foto pulang.");
    }

    // WATERMARK
    $tgl = date("Y-m-d");
    $jam = date("H:i:s");

    $lines = [
        "Tanggal: $tgl  Jam: $jam",
        "Proyek: $nama_proyek",
        "GPS: " . ($lat_pulang !== '' ? $lat_pulang : '-') . ", " . ($long_pulang !== '' ? $long_pulang : '-')
    ];
    watermark_image($dest, $lines);

    $foto_path = "foto/" . $name;
}

// hitung lembur (8 jam = 480 menit) jika jam_masuk ada
$lembur_menit = null;
if (!empty($row['jam_masuk'])) {
    $start = strtotime($row['jam_masuk']);
    $end   = strtotime(date("H:i:s")); // sekarang
    $menit = (int)(($end - $start) / 60);
    if ($menit > 480) $lembur_menit = $menit - 480;
}

// SQL value aman
$foto_sql = $foto_path ? "'" . mysqli_real_escape_string($conn, $foto_path) . "'" : null;
$lat_sql  = ($lat_pulang !== '') ? "'" . mysqli_real_escape_string($conn, $lat_pulang) . "'" : null;
$lng_sql  = ($long_pulang !== '') ? "'" . mysqli_real_escape_string($conn, $long_pulang) . "'" : null;
$note_sql = ($note_user !== '') ? "'" . mysqli_real_escape_string($conn, $note_user) . "'" : null;

// update sesuai role
if ($role === 'spv') {

    $set = [];
    $set[] = "jam_pulang = CURRENT_TIME()";
    $set[] = "proyek_id = $proyek_id";

    if ($foto_sql !== null) $set[] = "foto_pulang = $foto_sql";
    if ($lat_sql !== null)  $set[] = "lat_pulang = $lat_sql";
    if ($lng_sql !== null)  $set[] = "long_pulang = $lng_sql";
    if ($note_sql !== null) $set[] = "target_selesai = $note_sql";
    if ($lembur_menit !== null) $set[] = "lembur_menit = $lembur_menit";

    $sql = "UPDATE absensi_spv SET " . implode(", ", $set) . " WHERE id=$absensi_id LIMIT 1";
    mysqli_query($conn, $sql) or die("DB Error: " . mysqli_error($conn));

    header("Location: ../dashboard/spv.php");
    exit;

} else {

    $set = [];
    $set[] = "jam_pulang = CURRENT_TIME()";
    $set[] = "proyek_id = $proyek_id";
    $set[] = "spv_id = $spv_id";

    if ($foto_sql !== null) $set[] = "foto_pulang = $foto_sql";
    if ($lat_sql !== null)  $set[] = "lat_pulang = $lat_sql";
    if ($lng_sql !== null)  $set[] = "long_pulang = $lng_sql";
    if ($note_sql !== null) $set[] = "note_user = $note_sql";
    if ($lembur_menit !== null) $set[] = "lembur_menit = $lembur_menit";

    $sql = "UPDATE absensi SET " . implode(", ", $set) . " WHERE id=$absensi_id LIMIT 1";
    mysqli_query($conn, $sql) or die("DB Error: " . mysqli_error($conn));

    header("Location: ../dashboard/tukang.php");
    exit;
}