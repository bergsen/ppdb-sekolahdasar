<?php
session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Ditolak - PPDB SD</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <div class="error-container">
        <div class="error-content">
            <h1><i class="fas fa-exclamation-triangle"></i> 403</h1>
            <h2>Akses Ditolak</h2>
            <p>Anda tidak memiliki izin untuk mengakses halaman ini.</p>
            <a href="index.php" class="btn-primary">Kembali ke Beranda</a>
            <a href="login.php" class="btn-secondary">Login dengan akun lain</a>
        </div>
    </div>
</body>
</html>