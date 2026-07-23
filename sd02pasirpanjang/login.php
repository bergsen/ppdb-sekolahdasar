<?php
require_once 'config/database.php';
$db = new Database();
$conn = $db->getConnection();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $db->escapeString($_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE (username = '$username' OR email = '$username') AND role = 'pendaftar'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (verifyPassword($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];

            header("Location: pendaftar/dashboard.php");
            exit();
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Username/email tidak ditemukan atau bukan akun pendaftar!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Pendaftar - PPDB SDN Pasir Panjang 02</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .login-tabs {
            display: flex;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid #4a6fa5;
        }
        .login-tab {
            flex: 1;
            text-align: center;
            padding: 0.75rem 1rem;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        .login-tab.active {
            background: #4a6fa5;
            color: white;
        }
        .login-tab:not(.active) {
            background: white;
            color: #4a6fa5;
        }
        .login-tab:not(.active):hover {
            background: #f0f4f8;
        }
        .wa-lupa-password {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #25d366;
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.3s;
        }
        .wa-lupa-password:hover {
            opacity: 0.8;
            text-decoration: underline;
        }
    </style>
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-header">
            <h1><i class="fas fa-school"></i> PPDB SDN Pasir Panjang 02</h1>
            <p>Login Akun Pendaftar</p>
        </div>

        <!-- Tab navigasi login -->
        <div class="login-tabs">
            <span class="login-tab active">
                <i class="fas fa-user"></i> Pendaftar
            </span>
        </div>

        <?php if($error): ?>
        <div class="alert alert-error">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>

        <form method="POST" class="login-form">
            <div class="form-group">
                <label for="username"><i class="fas fa-user"></i> Username atau Email</label>
                <input type="text" id="username" name="username" required placeholder="Masukkan username atau email">
            </div>

            <div class="form-group" style="position: relative;">
                <label for="password"><i class="fas fa-lock"></i> Password</label>
                <input type="password" id="password" name="password" required placeholder="Masukkan password">
                <button type="button" class="toggle-password" onclick="togglePassword()">
                    <i class="fas fa-eye"></i>
                </button>
            </div>

            <div class="form-options">
            <a href="lupa_password.php" class="wa-lupa-password">
            <i class="fas fa-key"></i> Lupa Password?</a>
           </div>

            <button type="submit" class="btn-primary btn-block">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>

            <div class="login-footer">
                <p>Belum punya akun? <a href="register.php">Daftar sebagai Pendaftar</a></p>
            </div>
        </form>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleBtn = document.querySelector('.toggle-password i');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.classList.remove('fa-eye');
                toggleBtn.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleBtn.classList.remove('fa-eye-slash');
                toggleBtn.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
