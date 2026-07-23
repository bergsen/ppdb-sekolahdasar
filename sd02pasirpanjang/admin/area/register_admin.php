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
    $password = $_POST['password'];
    $role = $db->escapeString($_POST['role']);
    $secret_code = $_POST['secret_code'];
    
    // Kode rahasia untuk trial (dalam produksi, ini harus lebih aman)
    if ($secret_code !== 'TRIAL123') {
        $error = "Kode trial salah!";
    } else {
        // Cek username/email sudah ada
        $check = $conn->query("SELECT id FROM users WHERE username = '$username' OR email = '$email'");
        
        if ($check->num_rows > 0) {
            $error = "Username atau email sudah terdaftar!";
        } else {
            // Hash password
            $hashedPassword = hashPassword($password);

            // Insert user with prepared statement
            $stmt = $conn->prepare("INSERT INTO users (nama, username, email, password, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $nama, $username, $email, $hashedPassword, $role);

            if ($stmt->execute()) {
                $success = "Registrasi berhasil! Silakan login sebagai " . ucfirst($role);
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
    <title>Registrasi Trial - PPDB SD</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="login-page">
    <div class="register-container">
        <div class="register-header">
            <h1><i class="fas fa-user-shield"></i> Registrasi Trial</h1>
            <p>Daftar akun Admin/Panitia untuk uji coba sistem</p>
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
                <label for="password"><i class="fas fa-lock"></i> Password</label>
                <input type="password" id="password" name="password" required placeholder="Buat password">
            </div>
            
            <div class="form-group">
                <label for="role"><i class="fas fa-user-tie"></i> Role</label>
                <select id="role" name="role" required>
                    <option value="">Pilih Role</option>
                    <option value="admin">Admin</option>
                    <option value="panitia">Panitia</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="secret_code"><i class="fas fa-key"></i> Kode Trial</label>
                <input type="text" id="secret_code" name="secret_code" required 
                       placeholder="Masukkan kode trial: TRIAL123">
                <small class="form-hint">Gunakan kode: TRIAL123</small>
            </div>
            
            <button type="submit" class="btn-primary btn-block">
                <i class="fas fa-user-plus"></i> Daftar Trial
            </button>
            
            <div class="register-footer">
                <p>Untuk pendaftar biasa? <a href="register.php">Daftar disini</a></p>
                <p>Sudah punya akun? <a href="login.php">Login disini</a></p>
            </div>
        </form>
    </div>
</body>
</html>