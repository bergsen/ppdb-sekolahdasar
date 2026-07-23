<?php
require_once '../config/database.php';
redirectIfNotAuthorized(['admin']);

$db = new Database();
$conn = $db->getConnection();

$success = '';
$error = '';

/* ===================== TAMBAH AKUN ===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_akun'])) {
    $nama = $db->escapeString($_POST['nama']);
    $username = $db->escapeString($_POST['username']);
    $email = $db->escapeString($_POST['email']);
    $no_hp = $db->escapeString($_POST['no_hp']);
    $password = $_POST['password'];
    $role = $db->escapeString($_POST['role']);

    $cek = $conn->query("SELECT id FROM users WHERE username='$username' OR email='$email'");
    if ($cek->num_rows > 0) {
        $error = "Username atau email sudah terdaftar!";
    } else {
        $hash = hashPassword($password);
        $stmt = $conn->prepare("INSERT INTO users (nama, username, email, no_hp, password, role) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $nama, $username, $email, $no_hp, $hash, $role);
        if ($stmt->execute()) {
            $success = "Akun berhasil ditambahkan!";
        } else {
            $error = "Gagal menambah akun: " . $stmt->error;
        }
        $stmt->close();
    }
}

/* ===================== EDIT AKUN ===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_akun'])) {
    $id = (int) $_POST['id'];
    $nama = $db->escapeString($_POST['nama']);
    $username = $db->escapeString($_POST['username']);
    $email = $db->escapeString($_POST['email']);
    $no_hp = $db->escapeString($_POST['no_hp']);
    $role = $db->escapeString($_POST['role']);
    $password = $_POST['password'];

    $cek = $conn->query("SELECT id FROM users WHERE id=$id AND role IN ('admin','panitia')");
    if ($cek->num_rows == 0) {
        $error = "Akun tidak valid!";
    } else {
        $cekDupe = $conn->query("
            SELECT id FROM users 
            WHERE (username='$username' OR email='$email') AND id!=$id
        ");

        if ($cekDupe->num_rows > 0) {
            $error = "Username atau email sudah digunakan!";
        } else {
            if (!empty($password)) {
                $hash = hashPassword($password);
                $stmt = $conn->prepare("UPDATE users SET nama=?, username=?, email=?, no_hp=?, password=?, role=? WHERE id=?");
                $stmt->bind_param("ssssssi", $nama, $username, $email, $no_hp, $hash, $role, $id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET nama=?, username=?, email=?, no_hp=?, role=? WHERE id=?");
                $stmt->bind_param("sssssi", $nama, $username, $email, $no_hp, $role, $id);
            }

            if ($stmt->execute()) {
                $success = "Akun berhasil diperbarui!";
            } else {
                $error = "Gagal update akun: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

/* ===================== HAPUS AKUN ===================== */
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];
    if ($id == $_SESSION['user_id']) {
        $error = "Tidak dapat menghapus akun sendiri!";
    } else {
        if ($conn->query("DELETE FROM users WHERE id=$id AND role!='pendaftar'")) {
            $success = "Akun berhasil dihapus!";
        } else {
            $error = "Gagal menghapus akun!";
        }
    }
}

/* ===================== DATA EDIT ===================== */
$editData = null;
if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $res = $conn->query("SELECT * FROM users WHERE id=$id AND role IN ('admin','panitia')");
    if ($res->num_rows > 0) {
        $editData = $res->fetch_assoc();
    }
}

/* ===================== LIST AKUN ===================== */
$result = $conn->query("
    SELECT * FROM users 
    WHERE role IN ('admin','panitia')
    ORDER BY role, nama
");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Akun</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>

    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">

        <div class="dashboard-header">

            <div class="container">
                <h1><i class="fas fa-user-cog"></i> Kelola Akun (Admin & Panitia)</h1>
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

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- ===================== FORM ===================== -->
            <div class="form-section">
                <h2>
                    <i class="fas fa-<?php echo $editData ? 'edit' : 'user-plus'; ?>"></i>
                    <?php echo $editData ? 'Edit Akun' : 'Tambah Akun'; ?>
                </h2>

                <form method="POST">
                    <?php if ($editData): ?>
                        <input type="hidden" name="id" value="<?php echo $editData['id']; ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Nama</label>
                        <input type="text" name="nama" required value="<?php echo $editData['nama'] ?? ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" required value="<?php echo $editData['username'] ?? ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" required value="<?php echo $editData['email'] ?? ''; ?>">
                    </div>

                    <div class="form-group">
                         <label>No HP</label>
                         <input type="text" name="no_hp" required value="<?php echo $editData['no_hp'] ?? ''; ?>">
                      </div>

                    <div class="form-group">
                        <label>Password <?php echo $editData ? '(kosongkan jika tidak diubah)' : ''; ?></label>
                        <input type="password" name="password" <?php echo $editData ? '' : 'required'; ?>>
                    </div>

                    <div class="form-group">
                        <label>Role</label>
                        <select name="role" required>
                            <option value="admin" <?php if (($editData['role'] ?? '') == 'admin')
                                echo 'selected'; ?>>Admin
                            </option>
                            <option value="panitia" <?php if (($editData['role'] ?? '') == 'panitia')
                                echo 'selected'; ?>>
                                Panitia</option>
                        </select>
                    </div>

                    <button class="btn-primary" type="submit"
                        name="<?php echo $editData ? 'edit_akun' : 'tambah_akun'; ?>">
                        <i class="fas fa-save"></i>
                        <?php echo $editData ? 'Update' : 'Tambah'; ?>
                    </button>

                    <?php if ($editData): ?>
                        <a href="kelola_akun.php" class="btn-secondary">Batal</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- ===================== TABLE ===================== -->
            <div class="form-section">
                <h2><i class="fas fa-list"></i> Daftar Akun</h2>

                <table class="table table-responsive">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>No HP</th>
                            <th>Role</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1;
                        while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo htmlspecialchars($row['nama']); ?></td>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['no_hp']); ?></td>
                                <td><?php echo ucfirst($row['role']); ?></td>
                                <td>
                                    <a href="?edit=<?php echo $row['id']; ?>" class="btn-primary btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <?php if ($row['id'] != $_SESSION['user_id']): ?>
                                        <a href="?hapus=<?php echo $row['id']; ?>" class="btn-danger btn-sm"
                                            onclick="return confirm('Hapus akun ini?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="badge">Akun Anda</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <script src="../assets/js/script.js"></script>
</body>

</html>