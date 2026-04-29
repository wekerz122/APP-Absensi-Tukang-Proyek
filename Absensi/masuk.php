<?php
session_start();
include "../config/db.php";

if(!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../auth/login.php");
    exit;
}

$role = $_SESSION['role'];
if($role!='tukang' && $role!='spv') die("Akses ditolak");

// dropdown data
$proyek = mysqli_query($conn,"SELECT id, nama_proyek FROM proyek WHERE aktif=1 ORDER BY nama_proyek ASC");
$spv    = mysqli_query($conn,"SELECT id, username FROM users WHERE role='spv' AND aktif=1 ORDER BY username ASC");

// action otomatis berdasarkan role
$action = ($role === 'spv') ? "proses_masuk_spv.php" : "proses_masuk.php";

$username = htmlspecialchars($_SESSION['username'] ?? $role);
$tanggal  = date("Y-m-d");
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Absen Masuk</title>
  <link rel="stylesheet" href="../assets/css/app.css">
</head>
<body>

<div class="container">

  <div class="topbar">
    <div class="brand">
      <div class="logo"></div>
      <div class="title">
        <b>Absen Masuk</b>
        <small>
          Halo, <?= $username; ?><br>
          Role: <?= htmlspecialchars($role); ?> • Tanggal: <?= htmlspecialchars($tanggal); ?>
        </small>
      </div>
    </div>

    <div class="actions">
      <a class="btn btn-ghost" href="<?php echo ($role==='spv') ? '../dashboard/spv.php' : '../dashboard/tukang.php'; ?>">Kembali</a>
    </div>
  </div>

  <div class="grid" style="margin-top:16px;">
    <!-- Form -->
    <div class="card">
      <div class="card-header">
        <div>
          <h2>Form Absen Masuk</h2>
          <p>Isi proyek, ambil foto, lalu kirim</p>
        </div>
        <span id="gpsBadge" class="status warn"><span class="dot"></span>GPS: memuat…</span>
      </div>

      <div class="card-body">
        <form method="POST" action="<?php echo $action; ?>" enctype="multipart/form-data" class="form">
          <?php if($role === 'tukang'){ ?>
            <div class="field">
              <label>Nama SPV</label>
              <select name="spv_id" required>
                <option value="">-- Pilih SPV --</option>
                <?php while($s=mysqli_fetch_assoc($spv)){ ?>
                  <option value="<?php echo (int)$s['id']; ?>"><?php echo htmlspecialchars($s['username']); ?></option>
                <?php } ?>
              </select>
            </div>
          <?php } ?>

          <div class="field">
            <label>Nama Proyek</label>
            <select name="proyek_id" required>
              <option value="">-- Pilih Proyek --</option>
              <?php while($p=mysqli_fetch_assoc($proyek)){ ?>
                <option value="<?php echo (int)$p['id']; ?>"><?php echo htmlspecialchars($p['nama_proyek']); ?></option>
              <?php } ?>
            </select>
          </div>

          <input type="hidden" name="lat" id="lat">
          <input type="hidden" name="lng" id="lng">

          <div class="field">
            <label>Foto Absen Masuk</label>
            <div class="upload">
              <input id="foto" type="file" name="foto" accept="image/*" capture="environment" required>
              <div class="muted" style="margin-top:8px;">Gunakan kamera (lebih disarankan) agar bukti jelas.</div>
            </div>
          </div>

          <div id="previewWrap" class="preview" style="display:none;">
            <img id="previewImg" alt="Preview foto">
          </div>

          <?php if($role === 'spv'){ ?>
            <div class="field">
              <label>Target Hari Ini (wajib untuk SPV)</label>
              <textarea name="note" rows="4" placeholder="Contoh: cor lantai 2, pasang besi, dll" required></textarea>
            </div>
          <?php } else { ?>
            <div class="field">
              <label>Catatan (opsional)</label>
              <textarea name="note" rows="3" placeholder="Opsional..."></textarea>
            </div>
          <?php } ?>

          <div class="row" style="margin-top:12px;">
            <button class="btn btn-primary btn-block" type="submit">Kirim Absen Masuk</button>
            <a class="btn btn-ghost btn-block" href="<?php echo ($role==='spv') ? '../dashboard/spv.php' : '../dashboard/tukang.php'; ?>">Batal</a>
          </div>

          <div class="alert warn" style="margin-top:12px;">
            Jika GPS tidak muncul, izinkan lokasi di browser / aktifkan GPS HP.
          </div>
        </form>
      </div>
    </div>

    <!-- Tips -->
    <div class="card">
      <div class="card-header">
        <div>
          <h2>Checklist</h2>
          <p>Biar absen tidak gagal</p>
        </div>
      </div>
      <div class="card-body">
        <ul class="checklist">
          <li>Pastikan <b>Proyek</b> sudah dipilih</li>
          <?php if($role === 'tukang'){ ?>
            <li>Pastikan <b>SPV</b> sudah dipilih</li>
          <?php } ?>
          <li>Foto harus terlihat jelas (wajah/lokasi kerja)</li>
          <li>GPS aktif (cek badge “GPS” di atas)</li>
        </ul>
        <div class="muted" style="margin-top:10px;">
          Jika tombol kamera tidak muncul, coba ganti browser (Chrome) atau izinkan akses kamera.
        </div>
      </div>
    </div>
  </div>

  <div class="footer">© <?= date('Y'); ?> Absensi Tukang</div>
</div>

<script>
  // Preview foto
  const foto = document.getElementById('foto');
  const previewWrap = document.getElementById('previewWrap');
  const previewImg = document.getElementById('previewImg');

  foto?.addEventListener('change', () => {
    const f = foto.files && foto.files[0];
    if(!f){ previewWrap.style.display='none'; return; }
    const url = URL.createObjectURL(f);
    previewImg.src = url;
    previewWrap.style.display = 'block';
  });

  // GPS badge
  const gpsBadge = document.getElementById('gpsBadge');

  function setGPS(status){
    // status: ok | warn | bad
    gpsBadge.classList.remove('ok','warn','bad');
    gpsBadge.classList.add(status);
  }

  if(navigator.geolocation){
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        document.getElementById('lat').value = pos.coords.latitude;
        document.getElementById('lng').value = pos.coords.longitude;
        gpsBadge.innerHTML = '<span class="dot"></span>GPS: siap';
        setGPS('ok');
      },
      (err) => {
        console.log(err);
        gpsBadge.innerHTML = '<span class="dot"></span>GPS: tidak aktif';
        setGPS('bad');
      },
      { enableHighAccuracy:true, timeout:8000 }
    );
  } else {
    gpsBadge.innerHTML = '<span class="dot"></span>GPS: tidak didukung';
    setGPS('bad');
  }
</script>

</body>
</html>