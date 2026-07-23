<?php
require_once '../config/database.php';
redirectIfNotAuthorized(['admin']);

$db = new Database();
$conn = $db->getConnection();

$success = '';
$error = '';

// Update kuota
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_kuota'])) {
    $tahunAjaranId = (int)$_POST['tahun_ajaran_id'];
    $kuota = (int)$_POST['kuota'];
    
    // Validasi kuota tidak kurang dari yang sudah diterima
    $sqlCheck = "SELECT COUNT(*) as diterima FROM calon_siswa 
                 WHERE tahun_ajaran_id = $tahunAjaranId AND status_terima = 'diterima'";
    $resultCheck = $conn->query($sqlCheck);
    $data = $resultCheck->fetch_assoc();
    
    if ($kuota < $data['diterima']) {
        $error = "Kuota tidak boleh kurang dari jumlah siswa yang sudah diterima ({$data['diterima']})";
    } else {
        $sql = "UPDATE tahun_ajaran SET kuota = $kuota WHERE id = $tahunAjaranId";
        if ($conn->query($sql)) {
            $success = "Kuota berhasil diperbarui!";
        } else {
            $error = "Gagal memperbarui kuota: " . $conn->error;
        }
    }
}

// Update status tahun ajaran
if (isset($_POST['update_status'])) {
    $id = (int)$_POST['id'];
    $status = $db->escapeString($_POST['status']);
    
    $conn->begin_transaction();
    try {
        // Jika mengaktifkan tahun ajaran baru, nonaktifkan yang lain
        if ($status == 'aktif') {
            $conn->query("UPDATE tahun_ajaran SET status = 'nonaktif'");
        }
        
        $sql = "UPDATE tahun_ajaran SET status = '$status' WHERE id = $id";
        $conn->query($sql);
        
        $conn->commit();
        $success = "Status tahun ajaran berhasil diperbarui!";
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Gagal memperbarui status: " . $e->getMessage();
    }
}

// Ambil semua tahun ajaran dengan statistik
$sql = "SELECT ta.*, 
        (SELECT COUNT(*) FROM calon_siswa cs WHERE cs.tahun_ajaran_id = ta.id) as total_daftar,
        (SELECT COUNT(*) FROM calon_siswa cs WHERE cs.tahun_ajaran_id = ta.id AND cs.status_terima = 'diterima') as diterima,
        (SELECT COUNT(*) FROM calon_siswa cs WHERE cs.tahun_ajaran_id = ta.id AND cs.status_terima = 'pending') as pending,
        (SELECT COUNT(*) FROM calon_siswa cs WHERE cs.tahun_ajaran_id = ta.id AND cs.status_terima = 'tidak_diterima') as ditolak
        FROM tahun_ajaran ta 
        ORDER BY ta.tahun DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kuota - PPDB SD</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .kuota-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .kuota-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .kuota-header {
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .kuota-header h3 {
            color: #166088;
            margin-bottom: 0.5rem;
        }
        
        .kuota-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-aktif { background: #d4edda; color: #155724; }
        .status-nonaktif { background: #f8d7da; color: #721c24; }
        
        .kuota-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-item {
            text-align: center;
            padding: 0.75rem;
            border-radius: 5px;
            background: #f8f9fa;
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            font-size: 0.85rem;
            color: #666;
        }
        
        .progress-container {
            margin: 1.5rem 0;
        }
        
        .progress-bar {
            height: 20px;
            background: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #4a6fa5, #166088);
            border-radius: 10px;
            transition: width 0.3s;
        }
        
        .progress-info {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            color: #666;
        }
        
        .form-inline {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        
        .form-inline input {
            width: 100px;
            padding: 0.5rem;
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="dashboard-header">
            <div class="container">
                <h1><i class="fas fa-chart-bar"></i> Kelola Kuota</h1>
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
            
            <!-- Statistik Keseluruhan -->
            <div class="form-section">
                <h2><i class="fas fa-chart-pie"></i> Statistik Kuota</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon info">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Tahun Ajaran Aktif</h3>
                            <div class="number">
                                <?php 
                                $sqlAktif = "SELECT COUNT(*) as total FROM tahun_ajaran WHERE status = 'aktif'";
                                $resultAktif = $conn->query($sqlAktif);
                                echo $resultAktif->fetch_assoc()['total'];
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon success">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Total Kuota Tersedia</h3>
                            <div class="number">
                                <?php 
                                $sqlTotal = "SELECT SUM(kuota) as total FROM tahun_ajaran WHERE status = 'aktif'";
                                $resultTotal = $conn->query($sqlTotal);
                                echo $resultTotal->fetch_assoc()['total'] ?? 0;
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon warning">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Total Pendaftar</h3>
                            <div class="number">
                                <?php 
                                $sqlPendaftar = "SELECT COUNT(*) as total FROM calon_siswa cs 
                                               JOIN tahun_ajaran ta ON cs.tahun_ajaran_id = ta.id 
                                               WHERE ta.status = 'aktif'";
                                $resultPendaftar = $conn->query($sqlPendaftar);
                                echo $resultPendaftar->fetch_assoc()['total'] ?? 0;
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon danger">
                            <i class="fas fa-percentage"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Rata-rata Terisi</h3>
                            <div class="number">
                                <?php 
                                $sqlRata = "SELECT 
                                            ROUND((SUM(CASE WHEN cs.status_terima = 'diterima' THEN 1 ELSE 0 END) / 
                                            SUM(ta.kuota)) * 100, 1) as persen
                                            FROM tahun_ajaran ta 
                                            LEFT JOIN calon_siswa cs ON ta.id = cs.tahun_ajaran_id 
                                            WHERE ta.status = 'aktif'";
                                $resultRata = $conn->query($sqlRata);
                                echo $resultRata->fetch_assoc()['persen'] ?? 0;
                                ?>%
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Daftar Tahun Ajaran -->
            <div class="form-section">
                <h2><i class="fas fa-calendar"></i> Kelola Kuota per Tahun Ajaran</h2>
                
                <?php if($result->num_rows > 0): ?>
                <div class="kuota-grid">
                    <?php while($row = $result->fetch_assoc()): 
                        $persenDiterima = $row['kuota'] > 0 ? round(($row['diterima'] / $row['kuota']) * 100, 1) : 0;
                        $persenDaftar = $row['kuota'] > 0 ? round(($row['total_daftar'] / $row['kuota']) * 100, 1) : 0;
                    ?>
                    <div class="kuota-card">
                        <div class="kuota-header">
                            <h3><?php echo htmlspecialchars($row['tahun']); ?></h3>
                            <span class="kuota-status status-<?php echo $row['status']; ?>">
                                <?php echo ucfirst($row['status']); ?>
                            </span>
                        </div>
                        
                        <div class="kuota-stats">
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $row['total_daftar']; ?></div>
                                <div class="stat-label">Total Pendaftar</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $row['diterima']; ?></div>
                                <div class="stat-label">Diterima</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $row['pending']; ?></div>
                                <div class="stat-label">Pending</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $row['ditolak']; ?></div>
                                <div class="stat-label">Ditolak</div>
                            </div>
                        </div>
                        
                        <div class="progress-container">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo min($persenDiterima, 100); ?>%;"></div>
                            </div>
                            <div class="progress-info">
                                <span>Kuota Terisi: <?php echo $persenDiterima; ?>%</span>
                                <span><?php echo $row['diterima']; ?> / <?php echo $row['kuota']; ?></span>
                            </div>
                        </div>
                        
                        <!-- Form Update Kuota -->
                        <form method="POST" class="form-inline">
                            <input type="hidden" name="tahun_ajaran_id" value="<?php echo $row['id']; ?>">
                            <input type="number" name="kuota" value="<?php echo $row['kuota']; ?>" 
                                   min="<?php echo $row['diterima']; ?>" required>
                            <button type="submit" name="update_kuota" class="btn-primary btn-sm">
                                <i class="fas fa-sync"></i> Update
                            </button>
                        </form>
                        
                        <!-- Form Update Status -->
                        <form method="POST" class="form-inline" style="margin-top: 1rem;">
                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                            <select name="status" class="form-control" style="flex: 1;">
                                <option value="aktif" <?php echo $row['status'] == 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                                <option value="nonaktif" <?php echo $row['status'] == 'nonaktif' ? 'selected' : ''; ?>>Nonaktif</option>
                            </select>
                            <button type="submit" name="update_status" class="btn-secondary btn-sm">
                                <i class="fas fa-check"></i> Ubah
                            </button>
                        </form>
                        
                        <!-- Info Periode -->
                        <div class="period-info" style="margin-top: 1rem; font-size: 0.85rem; color: #666;">
                            <i class="far fa-calendar"></i>
                            <?php echo date('d/m/Y', strtotime($row['tanggal_buka'])); ?> - 
                            <?php echo date('d/m/Y', strtotime($row['tanggal_tutup'])); ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-calendar fa-3x"></i>
                    <h3>Belum ada tahun ajaran</h3>
                    <p>Tambah tahun ajaran terlebih dahulu di menu Tahun Ajaran</p>
                    <a href="kelola_tahun_ajaran.php" class="btn-primary">
                        <i class="fas fa-plus"></i> Tambah Tahun Ajaran
                    </a>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Chart Visualisasi -->
            <div class="form-section">
                <h2><i class="fas fa-chart-line"></i> Visualisasi Kuota</h2>
                <div id="chartContainer" style="height: 400px; width: 100%;">
                    <!-- Chart akan diisi dengan JavaScript -->
                </div>
            </div>
        </div>
    </div>
    
    <!-- Include Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Prepare data for chart
        const tahunData = [
            <?php 
            $result->data_seek(0); // Reset pointer
            while($row = $result->fetch_assoc()): 
                $persen = $row['kuota'] > 0 ? round(($row['diterima'] / $row['kuota']) * 100, 1) : 0;
            ?>
            {
                tahun: '<?php echo $row['tahun']; ?>',
                kuota: <?php echo $row['kuota']; ?>,
                diterima: <?php echo $row['diterima']; ?>,
                persen: <?php echo $persen; ?>,
                status: '<?php echo $row['status']; ?>'
            },
            <?php endwhile; ?>
        ];
        
        // Create chart
        const ctx = document.createElement('canvas');
        document.getElementById('chartContainer').appendChild(ctx);
        
        const chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: tahunData.map(item => item.tahun),
                datasets: [
                    {
                        label: 'Kuota',
                        data: tahunData.map(item => item.kuota),
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Diterima',
                        data: tahunData.map(item => item.diterima),
                        backgroundColor: 'rgba(75, 192, 192, 0.5)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Jumlah'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Tahun Ajaran'
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const item = tahunData[context.dataIndex];
                                if (context.dataset.label === 'Diterima') {
                                    return `${context.dataset.label}: ${context.raw} (${item.persen}% dari kuota)`;
                                }
                                return `${context.dataset.label}: ${context.raw}`;
                            }
                        }
                    }
                }
            }
        });
        
        // Auto-update progress bars on input change
        document.querySelectorAll('input[name="kuota"]').forEach(input => {
            input.addEventListener('input', function() {
                const card = this.closest('.kuota-card');
                const diterima = parseInt(card.querySelector('.stat-item:nth-child(2) .stat-number').textContent);
                const kuota = parseInt(this.value);
                const persen = Math.min(Math.round((diterima / kuota) * 100), 100);
                
                const progressFill = card.querySelector('.progress-fill');
                const progressInfo = card.querySelector('.progress-info span:first-child');
                
                progressFill.style.width = persen + '%';
                progressInfo.textContent = 'Kuota Terisi: ' + persen + '%';
            });
        });
    </script>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>