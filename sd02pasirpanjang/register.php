<?php
require_once 'config/database.php';
$db = new Database();
$conn = $db->getConnection();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $db->escapeString($_POST['nama']);
    $username = $db->escapeString($_POST['username']);
    $email = $db->escapeString($_POST['email']);
    $no_hp = $db->escapeString($_POST['no_hp']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validasi
    if ($password !== $confirm_password) {
        $error = "Password tidak sama!";
    } else {
        // Cek username/email sudah ada
        $check = $conn->query("SELECT id FROM users WHERE username = '$username' OR email = '$email'");
        
        if ($check->num_rows > 0) {
            $error = "Username atau email sudah terdaftar!";
        } else {
            // Hash password
            $hashedPassword = hashPassword($password);

            // Insert user with prepared statement
            $stmt = $conn->prepare("INSERT INTO users (nama, username, email, no_hp, password, role) VALUES (?, ?, ?, ?, ?, 'pendaftar')");
            $stmt->bind_param("sssss", $nama, $username, $email, $no_hp, $hashedPassword);

            if ($stmt->execute()) {
                $success = "Pendaftaran berhasil! Silakan login.";
            } else {
                $error = "Gagal mendaftar: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Pendaftar - PPDB SDN Pasir Panjang 02</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="login-page">
    <div class="register-container">
        <div class="register-header">
            <h1><i class="fas fa-user-plus"></i> Registrasi Pendaftar PPDB SDN Pasir Panjang 02</h1>
            <p>Buat akun untuk mendaftarkan calon siswa</p>
        </div>
        
        <?php if($success): ?>
        <div class="alert alert-success">
            <?php echo $success; ?>
            <a href="login.php" class="btn-login-after">Login Sekarang</a>
        </div>
        <?php endif; ?>
        
        <?php if($error): ?>
        <div class="alert alert-error">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" class="register-form">
            <div class="form-group">
                <label for="nama"><i class="fas fa-user"></i> Nama Lengkap</label>
                <input type="text" id="nama" name="nama" required placeholder="Masukkan nama lengkap">
            </div>
            
            <div class="form-group">
                <label for="username"><i class="fas fa-user-tag"></i> Username</label>
                <input type="text" id="username" name="username" required placeholder="Buat username">
            </div>
            
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email</label>
                <input type="email" id="email" name="email" required placeholder="Masukkan email">
            </div>
            
            <div class="form-group">
                <label for="no_hp"><i class="fas fa-phone"></i> No. Handphone</label>
                <input type="tel" id="no_hp" name="no_hp" required placeholder="Masukkan nomor handphone">
            </div>
            
            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Password</label>
                <input type="password" id="password" name="password" required placeholder="Buat password">
            </div>
            
            <div class="form-group">
                <label for="confirm_password"><i class="fas fa-lock"></i> Konfirmasi Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required 
                       placeholder="Ulangi password">
            </div>
            
            <button type="submit" class="btn-primary btn-block">
                <i class="fas fa-user-plus"></i> Daftar
            </button>
            
            <div class="register-footer">
                <p>Sudah punya akun? <a href="login.php">Login disini</a></p>
            </div>
        </form>
    </div>
</body>
</html>