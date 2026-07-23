<?php
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

$token   = $_GET['token'] ?? '';
$error   = $success = '';
$userId  = null;

// validasi token
$stmt = $conn->prepare("
    SELECT id 
    FROM users 
    WHERE reset_token = ? 
      AND reset_expired > NOW()
    LIMIT 1
");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    die("❌ Token tidak valid atau sudah kedaluwarsa.");
}

$user = $result->fetch_assoc();
$userId = $user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // validasi password
    if (strlen($password) < 8) {
        $error = "Password minimal 8 karakter.";
    } elseif ($password !== $confirm) {
        $error = "Konfirmasi password tidak cocok.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("
            UPDATE users 
            SET password = ?, 
                reset_token = NULL, 
                reset_expired = NULL
            WHERE id = ?
        ");
        $stmt->bind_param("si", $hash, $userId);
        $stmt->execute();

        $success = "Password berhasil direset. Silakan login.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Reset Password - PPDB SD</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">

<div class="login-container">
    <div class="login-header">
        <h1>🔐 Reset Password</h1>
        <p>Buat password baru Anda</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <div style="text-align:center">
            <a href="login.php">➡️ Login</a>
        </div>
    <?php else: ?>
        <form method="POST" class="login-form">
            <div class="form-group">
                <label>Password Baru</label>
                <input type="password" name="password" required>
            </div>

            <div class="form-group">
                <label>Konfirmasi Password</label>
                <input type="password" name="confirm_password" required>
            </div>

            <button class="btn-primary btn-block">Reset Password</button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>
