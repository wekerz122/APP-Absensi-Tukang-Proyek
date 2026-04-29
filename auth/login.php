<?php
session_start();
if(isset($_SESSION['user_id'])){
    header("Location: ../dashboard/");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Absensi</title>
    <link rel="stylesheet" href="../assets/css/app.css">
</head>
<body class="auth-body">

<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-head">
            <img src="../assets/images/bba&mcc.png" class="auth-logo-img">
            <div>
                <h1 class="auth-title">Absensi Proyek MCC & BBA</h1>
                <p class="auth-subtitle">Masuk untuk melanjutkan</p>
            </div>
        </div>

        <form class="auth-form" action="proses_login.php" method="POST" autocomplete="on">
            <div class="field">
                <label for="username">Username</label>
                <input id="username" type="text" name="username" required placeholder="Masukkan username">
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" required placeholder="Masukkan password">
            </div>

            <button class="btn btn-primary btn-block" type="submit">Login</button>

            <div class="auth-help">
                <span class="muted">Jika lupa akses, hubungi admin.</span>
            </div>
        </form>
    </div>

    <div class="auth-footer">
        <span class="muted">© <?= date('Y'); ?> Dewan Nanda (Dn)</span>
    </div>
</div>

</body>
</html>