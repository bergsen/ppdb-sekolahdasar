<?php
require_once '../config/database.php';
redirectIfNotAuthorized(['admin']);

$db = new Database();
$conn = $db->getConnection();

$role = $_SESSION['role'] ?? '';

/*
|--------------------------------------------------------------------------
| Ambil Tahun Ajaran Aktif
|--------------------------------------------------------------------------
*/

if ($role == 'admin') {

    // Coba ambil tahun ajaran aktif (optional)
    $sqlTaAktif = "
        SELECT id, tahun
        FROM tahun_ajaran
        WHERE status = 'aktif'
        LIMIT 1
    ";

    $resultTaAktif = $conn->query($sqlTaAktif);

    // Jika ada tahun aktif
    if ($resultTaAktif && $resultTaAktif->num_rows > 0) {

        $tahunAktif = $resultTaAktif->fetch_assoc();
        $tahunId = $tahunAktif['id'];

    } else {

        // Tetap bisa masuk dashboard
        $tahunAktif = null;
        $tahunId = null;
    }

} else {

    // User biasa wajib ada tahun aktif
    $sqlTaAktif = "
        SELECT id, tahun
        FROM tahun_ajaran
        WHERE status = 'aktif'
        LIMIT 1
    ";

    $resultTaAktif = $conn->query($sqlTaAktif);

    if (!$resultTaAktif || $resultTaAktif->num_rows == 0) {

        die("❌ Tidak ada tahun ajaran aktif");
    }

    $tahunAktif = $resultTaAktif->fetch_assoc();
    $tahunId = $tahunAktif['id'];
}
/*
|--------------------------------------------------------------------------
| Fungsi cek apakah kolom ada
|--------------------------------------------------------------------------
*/
function columnExists($conn, $table, $column)
{
    $query = "SHOW COLUMNS FROM $table LIKE '$column'";
    $result = $conn->query($query);
    return $result && $result->num_rows > 0;
}

/*
|--------------------------------------------------------------------------
| Statistik
|--------------------------------------------------------------------------
*/

// Total user
$total_admin = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='admin'")
    ->fetch_assoc()['total'];

$total_panitia = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='panitia'")
    ->fetch_assoc()['total'];

$total_pendaftar = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='pendaftar'")
    ->fetch_assoc()['total'];

/*
|--------------------------------------------------------------------------
| Total calon siswa
|--------------------------------------------------------------------------
*/
if (columnExists($conn, 'calon_siswa', 'tahun_ajaran_id')) {

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM calon_siswa WHERE tahun_ajaran_id=?");
    $stmt->bind_param("i", $tahunId);
    $stmt->execute();
    $total_calon = $stmt->get_result()->fetch_assoc()['total'];

} else {
    $total_calon = $conn->query("SELECT COUNT(*) as total FROM calon_siswa")
        ->fetch_assoc()['total'];
}

/*
|--------------------------------------------------------------------------
| Total informasi
|--------------------------------------------------------------------------
*/
if (columnExists($conn, 'informasi', 'tahun_ajaran_id')) {

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM informasi WHERE tahun_ajaran_id=?");
    $stmt->bind_param("i", $tahunId);
    $stmt->execute();
    $total_informasi = $stmt->get_result()->fetch_assoc()['total'];

} else {
    $total_informasi = $conn->query("SELECT COUNT(*) as total FROM informasi")
        ->fetch_assoc()['total'];
}

/*
|--------------------------------------------------------------------------
| Total pengumuman
|--------------------------------------------------------------------------
*/
if (columnExists($conn, 'pengumuman', 'tahun_ajaran_id')) {

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM pengumuman WHERE tahun_ajaran_id=?");
    $stmt->bind_param("i", $tahunId);
    $stmt->execute();
    $total_pengumuman = $stmt->get_result()->fetch_assoc()['total'];

} else {
    $total_pengumuman = $conn->query("SELECT COUNT(*) as total FROM pengumuman")
        ->fetch_assoc()['total'];
}

/*
|--------------------------------------------------------------------------
| Satukan ke array stats (biar desain tidak berubah)
|--------------------------------------------------------------------------
*/
$stats = [
    'total_admin' => $total_admin,
    'total_panitia' => $total_panitia,
    'total_pendaftar' => $total_pendaftar,
    'total_calon' => $total_calon,
    'total_informasi' => $total_informasi,
    'total_pengumuman' => $total_pengumuman
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - PPDB SD</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="dashboard-header">
            <div class="container">
                <h1><i class="fas fa-tachometer-alt"></i> Dashboard Admin</h1>
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
       <div class="alert alert-info">
    <strong>Tahun Ajaran Aktif:</strong>
    <?= isset($tahunAktif['tahun']) ? htmlspecialchars($tahunAktif['tahun']) : 'Belum ditentukan' ?>
</div>


        
        
        <div class="container">
            <!-- Statistik -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon info">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Admin</h3>
                        <div class="number"><?php echo $stats['total_admin'] ?? 0; ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon warning">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Panitia</h3>
                        <div class="number"><?php echo $stats['total_panitia'] ?? 0; ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Pendaftar</h3>
                        <div class="number"><?php echo $stats['total_pendaftar'] ?? 0; ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon danger">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Calon Siswa</h3>
                        <div class="number"><?php echo $stats['total_calon'] ?? 0; ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon info">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Informasi</h3>
                        <div class="number"><?php echo $stats['total_informasi'] ?? 0; ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon warning">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Pengumuman</h3>
                        <div class="number"><?php echo $stats['total_pengumuman'] ?? 0; ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="form-section">
                <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
                <div class="quick-actions-grid">
                    <a href="kelola_akun.php" class="quick-action">
                        <i class="fas fa-user-cog"></i>
                        <span>Kelola Akun</span>
                    </a>
                    
                    <a href="kelola_tahun_ajaran.php" class="quick-action">
                        <i class="fas fa-calendar-plus"></i>
                        <span>Tambah Tahun Ajaran</span>
                    </a>
                    
                    <a href="kelola_informasi.php" class="quick-action">
                        <i class="fas fa-info-circle"></i>
                        <span>Tambah Informasi</span>
                    </a>
                    
                    <a href="kelola_pengumuman.php" class="quick-action">
                        <i class="fas fa-bullhorn"></i>
                        <span>Tambah Pengumuman</span>
                    </a>
                    
                    <a href="kelola_kuota.php" class="quick-action">
                        <i class="fas fa-chart-bar"></i>
                        <span>Kelola Kuota</span>
                    </a>
                    
                    <a href="kelola_pendaftar.php" class="quick-action">
                        <i class="fas fa-users-cog"></i>
                        <span>Kelola Pendaftar</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .quick-action {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            text-decoration: none;
            color: #333;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
        }
        
        .quick-action:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            color: #4a6fa5;
        }
        
        .quick-action i {
            font-size: 2rem;
            color: #4a6fa5;
        }
        
        .sys-info {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
        }
    </style>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>