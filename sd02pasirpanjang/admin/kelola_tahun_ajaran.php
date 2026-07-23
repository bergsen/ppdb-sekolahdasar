<?php
require_once '../config/database.php';
redirectIfNotAuthorized(['admin']);

$db = new Database();
$conn = $db->getConnection();

$success = '';
$error = '';

// Tambah/Edit Tahun Ajaran
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tahun = $db->escapeString($_POST['tahun']);
    $kuota = (int) $_POST['kuota'];
    $status = $db->escapeString($_POST['status']);
    $tanggal_buka = $db->escapeString($_POST['tanggal_buka']);
    $tanggal_tutup = $db->escapeString($_POST['tanggal_tutup']);

    // Jika edit
    if (isset($_POST['id'])) {
        $id = (int) $_POST['id'];
        $sql = "UPDATE tahun_ajaran SET 
                tahun = '$tahun', 
                kuota = $kuota, 
                status = '$status',
                tanggal_buka = '$tanggal_buka',
                tanggal_tutup = '$tanggal_tutup'
                WHERE id = $id";

        if ($conn->query($sql)) {
            $success = "Tahun ajaran berhasil diperbarui!";
        } else {
            $error = "Gagal memperbarui: " . $conn->error;
        }
    } else {
        // Jika tambah baru
        $sql = "INSERT INTO tahun_ajaran (tahun, kuota, status, tanggal_buka, tanggal_tutup) 
                VALUES ('$tahun', $kuota, '$status', '$tanggal_buka', '$tanggal_tutup')";

        if ($conn->query($sql)) {
            $success = "Tahun ajaran berhasil ditambahkan!";
        } else {
            $error = "Gagal menambahkan: " . $conn->error;
        }
    }
}

// ================== HAPUS TAHUN AJARAN (HARD DELETE) ==================
if (isset($_POST['hapus'])) {
    $id = (int) $_POST['hapus'];

    // Ambil data tahun ajaran
    $ta = $conn->query("SELECT tahun, status FROM tahun_ajaran WHERE id = $id");

    if ($ta->num_rows == 0) {
        $error = "Tahun ajaran tidak ditemukan!";
    } else {
        $taData = $ta->fetch_assoc();

        // Cegah hapus jika masih aktif
        if ($taData['status'] === 'aktif') {
            $error = "Tahun ajaran masih AKTIF. Nonaktifkan terlebih dahulu!";
        } else {
            // Hapus (CASCADE akan jalan)
            if ($conn->query("DELETE FROM tahun_ajaran WHERE id = $id")) {
                $success = "Tahun ajaran <strong>{$taData['tahun']}</strong> beserta seluruh data pendaftar BERHASIL dihapus!";
            } else {
                $error = "Gagal menghapus: " . $conn->error;
            }
        }
    }
}



// Set nonaktif tahun ajaran lain jika ada yang diaktifkan
if (isset($_GET['aktifkan'])) {
    $id = (int) $_GET['aktifkan'];

    $conn->begin_transaction();
    try {
        // Set semua nonaktif
        $conn->query("UPDATE tahun_ajaran SET status = 'nonaktif'");

        // Aktifkan yang dipilih
        $conn->query("UPDATE tahun_ajaran SET status = 'aktif' WHERE id = $id");

        $conn->commit();
        $success = "Tahun ajaran berhasil diaktifkan!";
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Gagal mengaktifkan: " . $e->getMessage();
    }
}

// Ambil data untuk edit
$editData = null;
if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $result = $conn->query("SELECT * FROM tahun_ajaran WHERE id = $id");
    if ($result->num_rows > 0) {
        $editData = $result->fetch_assoc();
    }
}

// Ambil semua tahun ajaran
$sql = "SELECT * FROM tahun_ajaran ORDER BY tahun DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Tahun Ajaran - PPDB SDN Pasirpanjang02</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="dashboard-header">
            <div class="container">
                <h1><i class="fas fa-calendar-alt"></i> Kelola Tahun Ajaran</h1>
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
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Form Tahun Ajaran -->
            <div class="form-section">
                <h2>
                    <i class="fas fa-<?php echo $editData ? 'edit' : 'plus'; ?>"></i>
                    <?php echo $editData ? 'Edit' : 'Tambah'; ?> Tahun Ajaran
                </h2>
                <form method="POST">
                    <?php if ($editData): ?>
                        <input type="hidden" name="id" value="<?php echo $editData['id']; ?>">
                    <?php endif; ?>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="tahun">Tahun Ajaran *</label>
                            <input type="text" id="tahun" name="tahun" value="<?php echo $editData['tahun'] ?? ''; ?>"
                                placeholder="Contoh: 2024/2025" required>
                        </div>

                        <div class="form-group">
                            <label for="kuota">Kuota Siswa *</label>
                            <input type="number" id="kuota" name="kuota" value="<?php echo $editData['kuota'] ?? 0; ?>"
                                min="0" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="tanggal_buka">Tanggal Buka *</label>
                            <input type="date" id="tanggal_buka" name="tanggal_buka"
                                value="<?php echo $editData['tanggal_buka'] ?? ''; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="tanggal_tutup">Tanggal Tutup *</label>
                            <input type="date" id="tanggal_tutup" name="tanggal_tutup"
                                value="<?php echo $editData['tanggal_tutup'] ?? ''; ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="status">Status *</label>
                            <select id="status" name="status" required>
                                <option value="">Pilih Status</option>
                                <option value="aktif" <?php echo ($editData['status'] ?? '') == 'aktif' ? 'selected' : ''; ?>>
                                    Aktif
                                </option>
                                <option value="nonaktif" <?php echo ($editData['status'] ?? '') == 'nonaktif' ? 'selected' : ''; ?>>
                                    Nonaktif
                                </option>
                            </select>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-save"></i>
                                <?php echo $editData ? 'Update' : 'Simpan'; ?>
                            </button>

                            <?php if ($editData): ?>
                                <a href="kelola_tahun_ajaran.php" class="btn-secondary">
                                    <i class="fas fa-times"></i> Batal Edit
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Daftar Tahun Ajaran -->
            <div class="form-section">
                <div class="section-header">
                    <h2><i class="fas fa-list"></i> Daftar Tahun Ajaran</h2>
                    <div class="section-info">
                        Total: <strong><?php echo $result->num_rows; ?></strong> tahun ajaran
                    </div>
                </div>

                <?php if ($result->num_rows > 0): ?>
                    <div class="table-container table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Tahun Ajaran</th>
                                    <th>Kuota</th>
                                    <th>Periode</th>
                                    <th>Status</th>
                                    <th>Jumlah Pendaftar</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                while ($row = $result->fetch_assoc()):
                                    // Hitung jumlah pendaftar
                                    $countSql = "SELECT COUNT(*) as total FROM calon_siswa WHERE tahun_ajaran_id = {$row['id']}";
                                    $countResult = $conn->query($countSql);
                                    $countData = $countResult->fetch_assoc();
                                    ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo htmlspecialchars($row['tahun']); ?></td>
                                        <td><?php echo number_format($row['kuota']); ?></td>
                                        <td>
                                            <?php echo date('d/m/Y', strtotime($row['tanggal_buka'])); ?> -
                                            <?php echo date('d/m/Y', strtotime($row['tanggal_tutup'])); ?>
                                        </td>
                                        <td>
                                            <?php if ($row['status'] == 'aktif'): ?>
                                                <span class="status-badge status-valid">
                                                    <i class="fas fa-check"></i> Aktif
                                                </span>
                                            <?php else: ?>
                                                <span class="status-badge status-pending">
                                                    <i class="fas fa-clock"></i> Nonaktif
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo $countData['total']; ?> / <?php echo $row['kuota']; ?>
                                            (<?php echo round(($countData['total'] / max(1, $row['kuota'])) * 100, 1); ?>%)
                                        </td>
                                        <td>
                                            <a href="kelola_tahun_ajaran.php?edit=<?php echo $row['id']; ?>"
                                                class="btn-primary btn-sm">
                                                <i class="fas fa-edit"></i>
                                            </a>

                                            <?php if ($row['status'] != 'aktif'): ?>
                                                <a href="kelola_tahun_ajaran.php?aktifkan=<?php echo $row['id']; ?>"
                                                    class="btn-success btn-sm"
                                                    onclick="return confirm('Aktifkan tahun ajaran <?php echo $row['tahun']; ?>?')">
                                                    <i class="fas fa-power-off"></i>
                                                </a>
                                            <?php endif; ?>

                                            <?php if ($row['status'] === 'nonaktif'): ?>
                                                <form method="POST" style="display:inline"
                                                    onsubmit="return confirm(
      'PERINGATAN!\n\nMenghapus tahun ajaran ini akan MENGHAPUS SEMUA DATA PENDAFTAR & BERKAS.\n\nYakin ingin melanjutkan?')">
                                                    <input type="hidden" name="hapus" value="<?php echo $row['id']; ?>">
                                                    <button type="submit" class="btn-danger btn-sm">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar fa-3x"></i>
                        <h3>Belum ada tahun ajaran</h3>
                        <p>Tambah tahun ajaran baru di form atas</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
</body>

</html>