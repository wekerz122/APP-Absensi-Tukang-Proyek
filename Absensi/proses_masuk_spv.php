<?php
session_start();
include "../config/db.php";

if(!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit; }
if($_SESSION['role'] != 'spv') { die("Akses ditolak"); }

$spv_id    = (int)$_SESSION['user_id'];
$tanggal   = date("Y-m-d");
$jam       = date("H:i:s");

$proyek_id = (int)($_POST['proyek_id'] ?? 0);
$lat       = $_POST['lat'] ?? '';
$lng       = $_POST['lng'] ?? '';

$target    = trim($_POST['note'] ?? ''); // dari form masuk.php pakai name="note"

if($proyek_id <= 0) die("Proyek wajib dipilih.");
if($target === '') die("Target wajib diisi untuk SPV.");

// anti dobel (1x per hari)
$cek = mysqli_query($conn,"SELECT id FROM absensi_spv WHERE spv_id=$spv_id AND tanggal='$tanggal' LIMIT 1");
if(mysqli_num_rows($cek)>0){
  header("Location: ../dashboard/spv.php"); exit;
}

// upload foto masuk
$foto_path = null;
if(isset($_FILES['foto']) && $_FILES['foto']['error']===UPLOAD_ERR_OK){

  if($_FILES['foto']['size'] > 5 * 1024 * 1024) die("Ukuran foto terlalu besar. Maks 5MB.");

  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $mime  = finfo_file($finfo, $_FILES['foto']['tmp_name']);
  finfo_close($finfo);

  $allowedMime = ['image/jpeg','image/png','image/webp'];
  if(!in_array($mime, $allowedMime)) die("Format foto tidak didukung. Gunakan JPG/PNG/WebP.");

  $dir = "../foto/";
  if(!is_dir($dir)) mkdir($dir,0777,true);

  $ext = 'jpg';
  if($mime === 'image/png')  $ext = 'png';
  if($mime === 'image/webp') $ext = 'webp';

  $name = "spv_masuk_{$spv_id}_".date("Ymd_His").".".$ext;
  $dest = $dir.$name;

  if(!move_uploaded_file($_FILES['foto']['tmp_name'], $dest)) die("Gagal upload foto.");
  $foto_path = "foto/".$name;
}

$sql = "
INSERT INTO absensi_spv
(spv_id, tanggal, jam_masuk, proyek_id, foto_masuk, lat_masuk, long_masuk, target)
VALUES
(
  $spv_id,
  '$tanggal',
  '$jam',
  $proyek_id,
  ".($foto_path?("'".mysqli_real_escape_string($conn,$foto_path)."'"):"NULL").",
  ".($lat!==''?("'".mysqli_real_escape_string($conn,$lat)."'"):"NULL").",
  ".($lng!==''?("'".mysqli_real_escape_string($conn,$lng)."'"):"NULL").",
  '".mysqli_real_escape_string($conn,$target)."'
)";
mysqli_query($conn, $sql) or die(mysqli_error($conn));

header("Location: ../dashboard/spv.php");
exit;