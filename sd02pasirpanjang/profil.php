<?php
require_once '../config/database.php';
redirectIfNotLoggedIn();

$db = new Database();
$conn = $db->getConnection();
$userId = (int) $_SESSION['user_id'];

$success = '';
$error = '';

/* ================= AMBIL DATA USER ================= */
$sql = "SELECT * FROM users WHERE id = $userId LIMIT 1";
$result = $conn->query($sql);

if (!$result || $result->num_rows === 0) {
    die("Data user tidak ditemukan");
}

$user = $result->fetch_assoc();

/* ================= UPDATE PROFIL ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profil'])) {
    $nama  = $db->escapeString($_POST['nama']);
    $email = $db->escapeString($_POST['email']);
    $no_hp = $db->escapeString($_POST['no_hp']);

    // Cek email jika berubah
    if ($email !== $user['email']) {
        $check = $conn->query("SELECT id FROM users WHERE email = '$email' AND id != $userId");
        if ($check && $check->num_rows > 0) {
            $error = "Email sudah digunakan oleh akun lain!";
        }
    }

    if (!$error) {
        $update = "
            UPDATE users 
            SET nama = '$nama', email = '$email', no_hp = '$no_hp'
            WHERE id = $userId
        ";

        if ($conn->query($update)) {
            $_SESSION['nama']  = $nama;
            $_SESSION['email'] = $email;
            $success = "Profil berhasil diperbarui!";

            // AMBIL ULANG DATA USER (INI YANG FIX ERROR)
            $result = $conn->query("SELECT * FROM users WHERE id = $userId LIMIT 1");
            $user   = $result->fetch_assoc();
        } else {
            $error = "Gagal memperbarui profil: " . $conn->error;
        }
    }
}

/* ================= UPDATE PASSWORD ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $password_lama        = $_POST['password_lama'];
    $password_baru        = $_POST['password_baru'];
    $konfirmasi_password  = $_POST['konfirmasi_password'];

    if (!verifyPassword($password_lama, $user['password'])) {
        $error = "Password lama salah!";
    } elseif ($password_baru !== $konfirmasi_password) {
        $error = "Password baru tidak sama dengan konfirmasi!";
    } elseif (strlen($password_baru) < 6) {
        $error = "Password baru minimal 6 karakter!";
    } else {
        $hashedPassword = hashPassword($password_baru);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashedPassword, $userId);

        if ($stmt->execute()) {
            $stmt->close();
            $success = "Password berhasil diperbarui!";
        } else {
            $error = "Gagal memperbarui password: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - PPDB SDN Pasirpanjang 02</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="dashboard-header">
            <div class="container">
                <h1><i class="fas fa-user"></i> Profil Pengguna</h1>
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['nama'], 0, 1)); ?>
                    </div>
                    <div>
                        <strong><?php echo htmlspecialchars($_SESSION['nama']); ?></strong>
                        <small><?php echo htmlspecialchars($_SESSION['role']); ?></small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="container">
            <?php if($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
            </div>
            <?php endif; ?>
            
            <?php if($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <!-- Info Profil -->
            <div class="form-section">
                <h2><i class="fas fa-user-circle"></i> Informasi Profil</h2>
                <div class="profile-info">
                    <div class="info-item">
                        <span class="info-label">Nama Lengkap:</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['nama']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Username:</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['username']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">No. Handphone:</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['no_hp'] ?? '-'); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Role:</span>
                        <span class="info-value">
                            <span class="status-badge <?php echo $user['role'] == 'admin' ? 'status-valid' : 'status-pending'; ?>">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Tanggal Bergabung:</span>
                        <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Form Edit Profil -->
            <div class="form-section">
                <h2><i class="fas fa-edit"></i> Edit Profil</h2>
                <form method="POST">
                    <input type="hidden" name="update_profil" value="1">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nama">Nama Lengkap *</label>
                            <input type="text" id="nama" name="nama" 
                                   value="<?php echo htmlspecialchars($user['nama']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="no_hp">No. Handphone</label>
                            <input type="tel" id="no_hp" name="no_hp" 
                                   value="<?php echo htmlspecialchars($user['no_hp'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                </form>
            </div>
            
            <!-- Form Ganti Password -->
            <div class="form-section">
                <h2><i class="fas fa-key"></i> Ganti Password</h2>
                <form method="POST">
                    <input type="hidden" name="update_password" value="1">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="password_lama">Password Lama *</label>
                            <input type="password" id="password_lama" name="password_lama" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="password_baru">Password Baru *</label>
                            <input type="password" id="password_baru" name="password_baru" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="konfirmasi_password">Konfirmasi Password Baru *</label>
                            <input type="password" id="konfirmasi_password" name="konfirmasi_password" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-key"></i> Ganti Password
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <style>
        .profile-info {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #495057;
            min-width: 200px;
        }
        
        .info-value {
            color: #212529;
            text-align: right;
            flex: 1;
        }
    </style>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>