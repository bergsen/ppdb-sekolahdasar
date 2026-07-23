<?php
require_once '../config/database.php';
redirectIfNotAuthorized(['admin']);

$db = new Database();
$conn = $db->getConnection();

// Ambil parameter
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$filterTahun = $_GET['tahun'] ?? '';

// Jika ada user_id, tampilkan laporan spesifik
if ($userId) {
    // Ambil data user
    $sqlUser = "SELECT * FROM users WHERE id = $userId";
    $resultUser = $conn->query($sqlUser);
    $user = $resultUser->fetch_assoc();
    
    if (!$user) {
        header("Location: kelola_pendaftar.php");
        exit();
    }
    
    // Ambil data calon siswa
    $sqlCalon = "SELECT cs.*, ta.tahun 
                FROM calon_siswa cs 
                JOIN tahun_ajaran ta ON cs.tahun_ajaran_id = ta.id 
                WHERE cs.user_id = $userId";
    
    if ($filterTahun) {
        $sqlCalon .= " AND ta.tahun = '$filterTahun'";
    }
    
    $sqlCalon .= " ORDER BY cs.created_at DESC";
    $resultCalon = $conn->query($sqlCalon);
    
    // Hitung statistik
    $stats = [
        'total' => 0,
        'diterima' => 0,
        'pending' => 0,
        'ditolak' => 0,
        'berkas_valid' => 0,
        'berkas_total' => 0
    ];
    
    $calonSiswaList = [];
    while($calon = $resultCalon->fetch_assoc()) {
        $stats['total']++;
        $stats[$calon['status_terima']]++;
        
        // Hitung berkas
        $sqlBerkas = "SELECT COUNT(*) as total, 
                     SUM(CASE WHEN status = 'valid' THEN 1 ELSE 0 END) as valid 
                     FROM berkas WHERE calon_siswa_id = {$calon['id']}";
        $resultBerkas = $conn->query($sqlBerkas);
        $berkas = $resultBerkas->fetch_assoc();
        
        $calon['berkas_valid'] = $berkas['valid'] ?? 0;
        $calon['berkas_total'] = $berkas['total'] ?? 0;
        
        $stats['berkas_valid'] += $berkas['valid'] ?? 0;
        $stats['berkas_total'] += $berkas['total'] ?? 0;
        
        $calonSiswaList[] = $calon;
    }
    
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Detail Pendaftar - PPDB SDN Pasirpanjang 02</title>
        <link rel="stylesheet" href="../assets/css/style.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            .report-header {
                background: #4a6fa5;
                color: white;
                padding: 2rem;
                border-radius: 10px;
                margin-bottom: 2rem;
            }
            
            .report-header h1 {
                margin: 0;
                font-size: 1.5rem;
            }
            
            .report-header h2 {
                margin: 0.5rem 0 0 0;
                font-size: 1rem;
                opacity: 0.9;
            }
            
            .user-info-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 1rem;
                margin-bottom: 2rem;
            }
            
            .info-card {
                background: white;
                border-radius: 10px;
                padding: 1.5rem;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            
            .info-card h3 {
                color: #4a6fa5;
                margin-bottom: 1rem;
                font-size: 1rem;
            }
            
            .info-item {
                display: flex;
                margin-bottom: 0.5rem;
            }
            
            .info-label {
                min-width: 120px;
                font-weight: 600;
                color: #666;
            }
            
            .info-value {
                color: #333;
            }
            
            .stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 1rem;
                margin-bottom: 2rem;
            }
            
            .stat-card {
                background: white;
                border-radius: 10px;
                padding: 1.5rem;
                text-align: center;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            
            .stat-number {
                font-size: 2rem;
                font-weight: bold;
                color: #4a6fa5;
                margin-bottom: 0.5rem;
            }
            
            .stat-label {
                font-size: 0.9rem;
                color: #666;
            }
            
            .calon-table {
                width: 100%;
                border-collapse: collapse;
                background: white;
                border-radius: 10px;
                overflow: hidden;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            
            .calon-table th {
                background: #f8f9fa;
                padding: 1rem;
                text-align: left;
                font-weight: 600;
                color: #495057;
                border-bottom: 2px solid #dee2e6;
            }
            
            .calon-table td {
                padding: 1rem;
                border-bottom: 1px solid #dee2e6;
            }
            
            .badge {
                padding: 0.25rem 0.75rem;
                border-radius: 10px;
                font-size: 0.85rem;
                font-weight: 600;
            }
            
            .badge-success {
                background: #d4edda;
                color: #155724;
            }
            
            .badge-warning {
                background: #fff3cd;
                color: #856404;
            }
            
            .badge-danger {
                background: #f8d7da;
                color: #721c24;
            }
            
            .badge-info {
                background: #d1ecf1;
                color: #0c5460;
            }
            
            .report-actions {
                margin-top: 2rem;
                display: flex;
                gap: 1rem;
                justify-content: center;
            }
            
            .empty-state {
                text-align: center;
                padding: 3rem;
                color: #666;
            }
            
            .empty-state i {
                margin-bottom: 1rem;
                color: #ddd;
            }
        </style>
    </head>
    <body>
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="dashboard-header">
                <div class="container">
                    <h1><i class="fas fa-file-alt"></i> Detail Pendaftar</h1>
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
                <div class="report-header">
                    <h1>Detail Data Pendaftar</h1>
                    <h2><?php echo htmlspecialchars($user['nama']); ?></h2>
                </div>
                
                <div class="form-section">
                    <!-- Informasi Pendaftar -->
                    <h2><i class="fas fa-user"></i> Informasi Pendaftar</h2>
                    <div class="user-info-grid">
                        <div class="info-card">
                            <h3><i class="fas fa-id-card"></i> Identitas</h3>
                            <div class="info-item">
                                <span class="info-label">Nama:</span>
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
                        </div>
                        
                        <div class="info-card">
                            <h3><i class="fas fa-phone"></i> Kontak</h3>
                            <div class="info-item">
                                <span class="info-label">No. HP:</span>
                                <span class="info-value"><?php echo htmlspecialchars($user['no_hp']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Bergabung:</span>
                                <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Filter Tahun:</span>
                                <span class="info-value"><?php echo $filterTahun ?: 'Semua Tahun'; ?></span>
                            </div>
                        </div>
                        
                        <div class="info-card">
                            <h3><i class="fas fa-file-alt"></i> Informasi Data Calon Siswa</h3>
                            <div class="info-item">
                                <span class="info-label">Total Data:</span>
                                <span class="info-value"><?php echo count($calonSiswaList); ?> calon siswa</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Statistik -->
                <div class="form-section">
                    <h2><i class="fas fa-chart-bar"></i> Statistik</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['total']; ?></div>
                            <div class="stat-label">Total Calon</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['diterima']; ?></div>
                            <div class="stat-label">Diterima</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['pending']; ?></div>
                            <div class="stat-label">Pending</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['ditolak']; ?></div>
                            <div class="stat-label">Tidak Diterima</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['berkas_valid']; ?>/<?php echo $stats['berkas_total']; ?></div>
                            <div class="stat-label">Berkas Valid</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['total'] > 0 ? round(($stats['diterima'] / $stats['total']) * 100, 1) : 0; ?>%</div>
                            <div class="stat-label">Persentase Diterima</div>
                        </div>
                    </div>
                </div>
                
                <!-- Daftar Calon Siswa -->
                <div class="form-section">
                    <h2><i class="fas fa-user-graduate"></i> Daftar Calon Siswa</h2>
                    
                    <?php if(count($calonSiswaList) > 0): ?>
                    <div class="table-container table-responsive">
                        <table class="calon-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>No. Pendaftaran</th>
                                    <th>Nama Siswa</th>
                                    <th>Tahun Ajaran</th>
                                    <th>Tanggal Daftar</th>
                                    <th>Berkas</th>
                                    <th>Status</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; foreach($calonSiswaList as $calon): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($calon['nomor_pendaftaran']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($calon['nama_siswa']); ?></td>
                                    <td>
                                        <span class="badge badge-info"><?php echo htmlspecialchars($calon['tahun']); ?></span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($calon['created_at'])); ?></td>
                                    <td>
                                        <?php if($calon['berkas_total'] > 0): ?>
                                        <span class="badge <?php echo $calon['berkas_valid'] == $calon['berkas_total'] ? 'badge-success' : ($calon['berkas_valid'] > 0 ? 'badge-warning' : 'badge-danger'); ?>">
                                            <?php echo $calon['berkas_valid']; ?>/<?php echo $calon['berkas_total']; ?> valid
                                        </span>
                                        <?php else: ?>
                                        <span class="badge badge-danger">Belum upload</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $statusClass = [
                                            'pending' => 'badge-warning',
                                            'diterima' => 'badge-success',
                                            'tidak_diterima' => 'badge-danger'
                                        ];
                                        ?>
                                        <span class="badge <?php echo $statusClass[$calon['status_terima']]; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $calon['status_terima'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars(substr($calon['catatan_panitia'] ?? '-', 0, 50)); ?>
                                        <?php if(strlen($calon['catatan_panitia'] ?? '') > 50): ?>...<?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-user-graduate fa-3x"></i>
                        <h3>Tidak ada data calon siswa</h3>
                        <p><?php echo $filterTahun ? "Tidak ada data untuk tahun $filterTahun" : "Belum ada data pendaftaran"; ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Tombol Aksi -->
                    <a href="kelola_pendaftar.php" class="btn-primary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
        
        <script src="../assets/js/script.js"></script>
    </body>
    </html>
    <?php
} else {
    // Jika tidak ada user_id, redirect ke kelola pendaftar
    header("Location: kelola_pendaftar.php");
    exit();
}
?>