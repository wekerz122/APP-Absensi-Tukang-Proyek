<?php
session_start();
include "../config/db.php";

if(!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../auth/login.php");
    exit;
}

$role = $_SESSION['role'];
if($role!='tukang' && $role!='spv') die("Akses ditolak");

$user_id = (int)$_SESSION['user_id'];
$tanggal = date("Y-m-d");

if($role === 'spv'){
    $cek = mysqli_query($conn, "SELECT * FROM absensi_spv WHERE spv_id=$user_id AND tanggal='$tanggal' LIMIT 1");
} else {
    $cek = mysqli_query($conn, "SELECT * FROM absensi WHERE tukang_id=$user_id AND tanggal='$tanggal' LIMIT 1");
}

$data_hari_ini = mysqli_fetch_assoc($cek);

$proyek = mysqli_query($conn,"SELECT id, nama_proyek FROM proyek ORDER BY nama_proyek ASC");
$spv    = mysqli_query($conn,"SELECT id, username FROM users WHERE role='spv' AND aktif=1 ORDER BY username ASC");

$action = ($role === 'spv') ? "proses_pulang_spv.php" : "proses_pulang.php";

$username = htmlspecialchars($_SESSION['username'] ?? $role);
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Absen Pulang</title>
<link rel="stylesheet" href="../assets/css/app.css">
</head>

<body>

<div class="container">

<div class="topbar">
<div class="brand">
<div class="logo"></div>
<div class="title">
<b>Absen Pulang</b>
<small>
Halo, <?= $username ?><br>
Tanggal: <?= $tanggal ?>
</small>
</div>
</div>

<div class="actions">
<a class="btn btn-ghost" href="<?php echo ($role==='spv') ? '../dashboard/spv.php' : '../dashboard/tukang.php'; ?>">Kembali</a>
</div>
</div>


<div class="grid" style="margin-top:16px;">

<div class="card">
<div class="card-header">
<div>
<h2>Form Absen Pulang</h2>
<p>Lengkapi foto dan catatan sebelum pulang</p>
</div>

<span id="gpsBadge" class="status warn"><span class="dot"></span>GPS: memuat...</span>

</div>

<div class="card-body">

<?php if(!$data_hari_ini){ ?>

<div class="alert danger">
Belum ada absen masuk hari ini.
</div>

<div class="row" style="margin-top:12px;">
<a class="btn btn-primary btn-block" href="<?php echo ($role==='spv') ? '../dashboard/spv.php' : '../dashboard/tukang.php'; ?>">Kembali ke Dashboard</a>
</div>

<?php exit; } ?>


<div class="kpi">

<div class="kpi-item">
<div class="kpi-label">Jam Masuk</div>
<div class="kpi-value"><?php echo htmlspecialchars($data_hari_ini['jam_masuk'] ?? '-'); ?></div>
</div>

<div class="kpi-item">
<div class="kpi-label">Status</div>
<div class="kpi-value">Sudah Masuk</div>
</div>

</div>


<form method="POST" action="<?php echo $action; ?>" enctype="multipart/form-data" style="margin-top:14px;">

<?php if($role === 'tukang'){ ?>

<div class="field">
<label>Nama SPV</label>

<select name="spv_id" required>
<option value="">-- Pilih SPV --</option>

<?php while($s=mysqli_fetch_assoc($spv)){ ?>

<option value="<?php echo (int)$s['id']; ?>">
<?php echo htmlspecialchars($s['username']); ?>
</option>

<?php } ?>

</select>
</div>

<?php } ?>


<div class="field">

<label>Nama Proyek</label>

<select name="proyek_id" required>

<option value="">-- Pilih Proyek --</option>

<?php while($p=mysqli_fetch_assoc($proyek)){ ?>

<option value="<?php echo (int)$p['id']; ?>">
<?php echo htmlspecialchars($p['nama_proyek']); ?>
</option>

<?php } ?>

</select>

</div>


<input type="hidden" name="lat_pulang" id="lat_pulang">
<input type="hidden" name="long_pulang" id="long_pulang">


<div class="field">

<label>Foto Absen Pulang</label>

<input type="file" id="foto" name="foto_pulang" accept="image/*" capture="environment" required>

</div>


<div id="previewWrap" class="preview" style="display:none;">
<img id="previewImg">
</div>


<?php if($role === 'spv'){ ?>

<div class="field">

<label>Laporan Pekerjaan Hari Ini</label>

<textarea name="note_user" rows="4" required
placeholder="Contoh: cor lantai 2 selesai, pasang besi selesai"></textarea>

</div>

<?php } else { ?>

<div class="field">

<label>Catatan (opsional)</label>

<textarea name="note_user" rows="3"
placeholder="Opsional..."></textarea>

</div>

<?php } ?>


<div class="row" style="margin-top:12px;">

<button class="btn btn-danger btn-block" type="submit">
Kirim Absen Pulang
</button>

<a class="btn btn-ghost btn-block"
href="<?php echo ($role==='spv') ? '../dashboard/spv.php' : '../dashboard/tukang.php'; ?>">
Batal
</a>

</div>

</form>

<div class="alert warn" style="margin-top:12px;">
Jika GPS tidak muncul, aktifkan lokasi di browser.
</div>

</div>
</div>


<div class="card">
<div class="card-header">
<div>
<h2>Tips</h2>
<p>Biar absen tidak gagal</p>
</div>
</div>

<div class="card-body">

<ul class="checklist">
<li>Pastikan foto jelas</li>
<li>GPS aktif</li>
<li>Proyek sesuai lokasi kerja</li>
</ul>

</div>
</div>

</div>

</div>


<script>

const foto = document.getElementById('foto');
const previewWrap = document.getElementById('previewWrap');
const previewImg = document.getElementById('previewImg');

foto.addEventListener('change', () => {

const file = foto.files[0];

if(!file) return;

previewImg.src = URL.createObjectURL(file);

previewWrap.style.display = "block";

});


const gpsBadge = document.getElementById('gpsBadge');

function setGPS(status){

gpsBadge.classList.remove('ok','warn','bad');

gpsBadge.classList.add(status);

}

if(navigator.geolocation){

navigator.geolocation.getCurrentPosition(

(pos)=>{

document.getElementById('lat_pulang').value = pos.coords.latitude;
document.getElementById('long_pulang').value = pos.coords.longitude;

gpsBadge.innerHTML = '<span class="dot"></span>GPS siap';
setGPS('ok');

},

(err)=>{

gpsBadge.innerHTML = '<span class="dot"></span>GPS tidak aktif';
setGPS('bad');

},

{enableHighAccuracy:true, timeout:8000}

);

}

</script>

</body>
</html>