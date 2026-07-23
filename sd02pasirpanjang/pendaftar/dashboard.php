<?php
require_once '../config/database.php';
redirectIfNotAuthorized(['pendaftar']);

$db = new Database();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];

// Ambil data calon siswa yang didaftarkan
$sql = "SELECT cs.*, ta.tahun 
        FROM calon_siswa cs 
        JOIN tahun_ajaran ta ON cs.tahun_ajaran_id = ta.id 
        WHERE cs.user_id = $userId 
        ORDER BY cs.created_at DESC";
$result = $conn->query($sql);

// Ambil tahun ajaran aktif
$sqlTa = "SELECT * FROM tahun_ajaran WHERE status = 'aktif' LIMIT 1";
$resultTa = $conn->query($sqlTa);
$tahunAjaran = $resultTa->fetch_assoc();

/* ===================== HAPUS CALON SISWA + SEMUA BERKAS ===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_id'])) {

    $hapusId = (int) $_POST['hapus_id'];

    // pastikan data milik user login
    $cek = $conn->query("
        SELECT id 
        FROM calon_siswa 
        WHERE id = $hapusId AND user_id = $userId
        LIMIT 1
    ");

    if ($cek && $cek->num_rows === 1) {

        // ambil semua berkas calon siswa
        $qBerkas = $conn->query("
            SELECT nama_file, file_path 
            FROM berkas 
            WHERE calon_siswa_id = $hapusId
        ");

        if ($qBerkas) {
            while ($b = $qBerkas->fetch_assoc()) {
                $file = "../uploads/{$b['file_path']}/{$b['nama_file']}";
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }

        // hapus data berkas
        $conn->query("DELETE FROM berkas WHERE calon_siswa_id = $hapusId");

        // hapus data calon siswa
        $conn->query("DELETE FROM calon_siswa WHERE id = $hapusId");

        header("Location: dashboard.php?hapus=ok");
        exit;
    }

    header("Location: dashboard.php?hapus=gagal");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pendaftar - PPDB SDN Pasir Panjang 02</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="dashboard-header">
            <div class="container">
                <h1><i class="fas fa-tachometer-alt"></i> Dashboard Pendaftar</h1>
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
            <?php if ($tahunAjaran): ?>
                <div class="alert alert-info">
                    <i class="fas fa-calendar"></i>
                    Tahun Ajaran Aktif: <strong><?php echo $tahunAjaran['tahun']; ?></strong>
                    (<?php echo date('d/m/Y', strtotime($tahunAjaran['tanggal_buka'])); ?> -
                    <?php echo date('d/m/Y', strtotime($tahunAjaran['tanggal_tutup'])); ?>)
                    <br>
                    Kuota Tersedia: <strong><?php echo $tahunAjaran['kuota']; ?></strong> siswa
                </div>
            <?php else: ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    Tidak ada tahun ajaran aktif saat ini.
                </div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon info">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Calon Siswa</h3>
                        <div class="number"><?php echo $result->num_rows; ?></div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="section-header">
                    <h2><i class="fas fa-list"></i> Daftar Calon Siswa</h2>
                    <?php if ($tahunAjaran): ?>
                        <a href="formulir.php" class="btn-primary">
                            <i class="fas fa-plus"></i> Tambah Calon Siswa
                        </a>
                    <?php endif; ?>
                </div>

                <?php if (isset($_GET['hapus']) && $_GET['hapus'] === 'ok'): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check"></i> Data calon siswa & seluruh berkas berhasil dihapus
                    </div>
                <?php elseif (isset($_GET['hapus']) && $_GET['hapus'] === 'gagal'): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-times"></i> Gagal menghapus data
                    </div>
                <?php endif; ?>
                <?php if ($result->num_rows > 0): ?>
                    <div class="table-container table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>No. Pendaftaran</th>
                                    <th>Nama Siswa</th>
                                    <th>Tahun Ajaran</th>
                                    <th>Tanggal Daftar</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['nomor_pendaftaran']); ?></td>
                                        <td><strong><?php echo htmlspecialchars($row['nama_siswa']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['tahun']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></td>
                                        <td>
                                            <?php
                                            $statusClass = [
                                                'pending' => 'status-pending',
                                                'diterima' => 'status-valid',
                                                'tidak_diterima' => 'status-tidak-valid'
                                            ];
                                            ?>
                                            <span class="status-badge <?php echo $statusClass[$row['status_terima']]; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $row['status_terima'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (
                                                $row['status_terima'] === 'pending'
                                                && $tahunAjaran
                                                && $tahunAjaran['status'] === 'aktif'
                                            ): ?>
                                                <a href="edit_formulir.php?id=<?php echo $row['id']; ?>"
                                                    class="btn-secondary btn-sm">
                                                    Ubah Data <i class="fas fa-edit"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="btn-disabled btn-sm">
                                                    Tidak Bisa Diedit
                                                </span>
                                            <?php endif; ?>

                                            <form method="POST" class="form-hapus"
                                                onsubmit="return confirm('Yakin hapus data calon siswa BESERTA SEMUA BERKAS?');">
                                                <input type="hidden" name="hapus_id" value="<?php echo $row['id']; ?>">
                                                <button type="submit" class="btn-danger btn-sm">
                                                    Hapus <i class="fas fa-trash"></i>
                                                </button>
                                            </form>

                                            <?php if ($row['status_terima'] === 'diterima'): ?>
                                                <a href="bukti_pendaftaran.php?id=<?php echo $row['id']; ?>"
                                                    class="btn-success btn-sm">
                                                    Cetak Bukti <i class="fas fa-print"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>


                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-user-graduate fa-3x"></i>
                        <h3>Belum ada calon siswa terdaftar</h3>
                        <p>Mulai dengan mendaftarkan calon siswa baru</p>
                        <?php if ($tahunAjaran): ?>
                            <a href="formulir.php" class="btn-primary">
                                <i class="fas fa-plus"></i> Daftarkan Calon Siswa
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
</body>

</html>