<?php
session_start();
include "../config/db.php";

if(!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit; }
if($_SESSION['role'] != 'spv') { die("Akses ditolak"); }

$spv_id     = (int)$_SESSION['user_id'];
$tanggal    = date("Y-m-d");
$jam_pulang = date("H:i:s");

$proyek_id = (int)($_POST['proyek_id'] ?? 0);
$lat       = $_POST['lat_pulang'] ?? '';
$lng       = $_POST['long_pulang'] ?? '';

$target_selesai = trim($_POST['note_user'] ?? '');

if($proyek_id <= 0) die("Proyek wajib dipilih.");
if($target_selesai === '') die("Laporan target selesai wajib diisi untuk SPV.");

$cek = mysqli_query($conn,"SELECT * FROM absensi_spv WHERE spv_id=$spv_id AND tanggal='$tanggal' LIMIT 1");
if(mysqli_num_rows($cek)==0){
  header("Location: ../dashboard/spv.php"); exit;
}

$row = mysqli_fetch_assoc($cek);
$absensi_id = (int)$row['id'];

if(!empty($row['jam_pulang'])){
  header("Location: ../dashboard/spv.php"); exit;
}

// upload foto pulang
$foto_path = null;
if(isset($_FILES['foto_pulang']) && $_FILES['foto_pulang']['error']===UPLOAD_ERR_OK){

  if($_FILES['foto_pulang']['size'] > 5 * 1024 * 1024) die("Ukuran foto terlalu besar. Maks 5MB.");

  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $mime  = finfo_file($finfo, $_FILES['foto_pulang']['tmp_name']);
  finfo_close($finfo);

  $allowedMime = ['image/jpeg','image/png','image/webp'];
  if(!in_array($mime, $allowedMime)) die("Format foto tidak didukung. Gunakan JPG/PNG/WebP.");

  $dir = "../foto/";
  if(!is_dir($dir)) mkdir($dir,0777,true);

  $ext = 'jpg';
  if($mime === 'image/png')  $ext = 'png';
  if($mime === 'image/webp') $ext = 'webp';

  $name = "spv_pulang_{$spv_id}_".date("Ymd_His").".".$ext;
  $dest = $dir.$name;

  if(!move_uploaded_file($_FILES['foto_pulang']['tmp_name'], $dest)) die("Gagal upload foto pulang.");
  $foto_path = "foto/".$name;
}

$updates = [];
$updates[] = "jam_pulang='".mysqli_real_escape_string($conn,$jam_pulang)."'";
$updates[] = "proyek_id=".$proyek_id;
$updates[] = "target_selesai='".mysqli_real_escape_string($conn,$target_selesai)."'";

if($foto_path) $updates[] = "foto_pulang='".mysqli_real_escape_string($conn,$foto_path)."'";
if($lat!=='')  $updates[] = "lat_pulang='".mysqli_real_escape_string($conn,$lat)."'";
if($lng!=='')  $updates[] = "long_pulang='".mysqli_real_escape_string($conn,$lng)."'";

mysqli_query($conn,"UPDATE absensi_spv SET ".implode(", ",$updates)." WHERE id=$absensi_id LIMIT 1") or die(mysqli_error($conn));

// hitung lembur (lebih dari 8 jam)
if(!empty($row['jam_masuk'])){
  $start = new DateTime($row['jam_masuk']);
  $end   = new DateTime($jam_pulang);
  $diffSeconds = $end->getTimestamp() - $start->getTimestamp();
  if($diffSeconds < 0) $diffSeconds = 0;
  $menit = (int) floor($diffSeconds/60);
  $lembur = ($menit > 480) ? ($menit - 480) : 0;
  mysqli_query($conn,"UPDATE absensi_spv SET lembur_menit=$lembur WHERE id=$absensi_id LIMIT 1");
}

header("Location: ../dashboard/spv.php");
exit;