<?php
require_once '../config/database.php';
redirectIfNotAuthorized(['panitia']);

$db = new Database();
$conn = $db->getConnection();

// Ambil tahun ajaran aktif
$sqlTa = "SELECT * FROM tahun_ajaran WHERE status = 'aktif' LIMIT 1";
$resultTa = $conn->query($sqlTa);
$tahunAjaran = $resultTa->fetch_assoc();

$tahunAjaranId = $tahunAjaran['id'] ?? 0;

// Ambil statistik untuk tahun ajaran aktif
$sqlStats = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status_terima = 'diterima' THEN 1 ELSE 0 END) as diterima,
        SUM(CASE WHEN status_terima = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status_terima = 'tidak_diterima' THEN 1 ELSE 0 END) as ditolak,
        SUM(CASE WHEN status_terima = 'cadangan' THEN 1 ELSE 0 END) as cadangan
    FROM calon_siswa 
    WHERE tahun_ajaran_id = ?
";
$stmt = $conn->prepare($sqlStats);
$stmt->bind_param("i", $tahunAjaranId);
$stmt->execute();
$resultStats = $stmt->get_result();
$stats = $resultStats->fetch_assoc() ?? ['total' => 0, 'diterima' => 0, 'pending' => 0, 'ditolak' => 0, 'cadangan' => 0];
$stmt->close();

// Statistik berkas
$sqlBerkas = "
    SELECT 
        COUNT(*) as total_berkas,
        SUM(CASE WHEN status = 'valid' THEN 1 ELSE 0 END) as valid,
        SUM(CASE WHEN status = 'tidak_valid' THEN 1 ELSE 0 END) as tidak_valid,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_berkas
    FROM berkas b
    JOIN calon_siswa cs ON b.calon_siswa_id = cs.id
    WHERE cs.tahun_ajaran_id = ?
";
$stmt = $conn->prepare($sqlBerkas);
$stmt->bind_param("i", $tahunAjaranId);
$stmt->execute();
$resultBerkas = $stmt->get_result();
$berkasStats = $resultBerkas->fetch_assoc() ?? ['total_berkas' => 0, 'valid' => 0, 'tidak_valid' => 0, 'pending_berkas' => 0];
$stmt->close();

// Statistik berkas per status siswa
$sqlBerkasPerStatus = "
    SELECT 
        cs.status_terima,
        COUNT(DISTINCT b.id) as total_berkas,
        SUM(CASE WHEN b.status = 'valid' THEN 1 ELSE 0 END) as valid
    FROM berkas b
    JOIN calon_siswa cs ON b.calon_siswa_id = cs.id
    WHERE cs.tahun_ajaran_id = ?
    GROUP BY cs.status_terima
";
$stmt = $conn->prepare($sqlBerkasPerStatus);
$stmt->bind_param("i", $tahunAjaranId);
$stmt->execute();
$resultBerkasStatus = $stmt->get_result();
$berkasPerStatus = [];
while($row = $resultBerkasStatus->fetch_assoc()) {
    $berkasPerStatus[$row['status_terima']] = $row;
}
$stmt->close();

// Data untuk chart pie (status pendaftaran)
$sqlPieChart = "
    SELECT 
        status_terima,
        COUNT(*) as jumlah
    FROM calon_siswa 
    WHERE tahun_ajaran_id = ?
    GROUP BY status_terima
";
$stmt = $conn->prepare($sqlPieChart);
$stmt->bind_param("i", $tahunAjaranId);
$stmt->execute();
$resultPie = $stmt->get_result();
$pieData = [];
while($row = $resultPie->fetch_assoc()) {
    $pieData[$row['status_terima']] = (int)$row['jumlah'];
}
$stmt->close();

// Data untuk chart bar (pendaftaran per bulan)
$sqlMonthly = "
    SELECT 
        MONTH(cs.created_at) as bulan,
        COUNT(*) as total,
        SUM(CASE WHEN cs.status_terima = 'diterima' THEN 1 ELSE 0 END) as diterima,
        SUM(CASE WHEN cs.status_terima = 'tidak_diterima' THEN 1 ELSE 0 END) as ditolak
    FROM calon_siswa cs
    WHERE cs.tahun_ajaran_id = ? 
    AND YEAR(cs.created_at) = YEAR(CURDATE())
    GROUP BY MONTH(cs.created_at)
    ORDER BY bulan
";
$stmt = $conn->prepare($sqlMonthly);
$stmt->bind_param("i", $tahunAjaranId);
$stmt->execute();
$resultMonthly = $stmt->get_result();
$monthlyData = [];
$monthLabels = [];
$monthlyTotal = [];
$monthlyDiterima = [];
$monthlyDitolak = [];

$namaBulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

while($row = $resultMonthly->fetch_assoc()) {
    $bulanIndex = $row['bulan'] - 1;
    $monthLabels[] = $namaBulan[$bulanIndex];
    $monthlyTotal[] = (int)$row['total'];
    $monthlyDiterima[] = (int)$row['diterima'];
    $monthlyDitolak[] = (int)$row['ditolak'];
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Panitia - PPDB SDN Pasirpanjang02</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .chart-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .chart-box {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .chart-box h3 {
            margin-top: 0;
            color: #333;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .chart-wrapper {
            height: 300px;
            position: relative;
        }
        .stats-summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            text-align: center;
        }
        .summary-item h4 {
            margin: 0 0 10px 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .summary-item .number {
            font-size: 32px;
            font-weight: bold;
            margin: 5px 0;
        }
        .summary-item .label {
            font-size: 12px;
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="dashboard-header">
            <div class="container">
                <h1><i class="fas fa-tachometer-alt"></i> Dashboard Panitia</h1>
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
            <?php if($tahunAjaran): ?>
            <div class="alert alert-info">
                <i class="fas fa-calendar"></i>
                Tahun Ajaran Aktif: <strong><?php echo $tahunAjaran['tahun']; ?></strong>
                | Kuota: <strong><?php echo $tahunAjaran['kuota']; ?></strong> siswa
                | Tersisa: <strong><?php echo max(0, $tahunAjaran['kuota'] - $stats['diterima']); ?></strong> siswa
            </div>
            <?php endif; ?>
            
            <!-- Ringkasan Statistik -->
            <div class="stats-summary">
                <div class="summary-item">
                    <h4><i class="fas fa-users"></i> TOTAL PENDAFTAR</h4>
                    <div class="number"><?php echo (int)$stats['total']; ?></div>
                    <div class="label">Calon Siswa</div>
                </div>
                <div class="summary-item">
                    <h4><i class="fas fa-check-circle"></i> DITERIMA</h4>
                    <div class="number"><?php echo (int)$stats['diterima']; ?></div>
                    <div class="label"><?php echo $tahunAjaran ? round(($stats['diterima'] / $tahunAjaran['kuota']) * 100, 1) . '% kuota' : 'siswa'; ?></div>
                </div>
                <div class="summary-item">
                    <h4><i class="fas fa-file-check"></i> BERKAS VALID</h4>
                    <div class="number"><?php echo (int)$berkasStats['valid']; ?></div>
                    <div class="label"><?php echo $berkasStats['total_berkas'] > 0 ? round(($berkasStats['valid'] / $berkasStats['total_berkas']) * 100, 1) . '%' : '0%'; ?> valid</div>
                </div>
                <div class="summary-item">
                    <h4><i class="fas fa-percentage"></i> KELENGKAPAN</h4>
                    <div class="number"><?php echo $stats['total'] > 0 ? round((($stats['total'] - $stats['pending']) / $stats['total']) * 100, 1) : '0'; ?>%</div>
                    <div class="label">Data Tervalidasi</div>
                </div>
            </div>
            
            <!-- Statistik Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon info">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Pendaftar</h3>
                        <div class="number"><?php echo (int)$stats['total']; ?></div>
                        <small>Calon Siswa</small>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Diterima</h3>
                        <div class="number"><?php echo (int)$stats['diterima']; ?></div>
                        <small><?php echo $tahunAjaran ? round(($stats['diterima'] / $tahunAjaran['kuota']) * 100, 1) . '% kuota' : 'siswa'; ?></small>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Pending</h3>
                        <div class="number"><?php echo (int)$stats['pending']; ?></div>
                        <small>Menunggu Verifikasi</small>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon danger">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Tidak Diterima</h3>
                        <div class="number"><?php echo (int)$stats['ditolak']; ?></div>
                        <small><?php echo $stats['total'] > 0 ? round(($stats['ditolak'] / $stats['total']) * 100, 1) : '0'; ?>% dari total</small>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-file-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Berkas Valid</h3>
                        <div class="number"><?php echo (int)$berkasStats['valid']; ?></div>
                        <small><?php echo $berkasStats['total_berkas'] > 0 ? round(($berkasStats['valid'] / $berkasStats['total_berkas']) * 100, 1) . '%' : '0%'; ?> valid</small>
                    </div>
                </div>
            </div>
            
            <!-- Chart Area -->
            <div class="form-section">
                <h2><i class="fas fa-chart-bar"></i> Visualisasi Data</h2>
                <div class="chart-container">
                    <div class="chart-box">
                        <h3><i class="fas fa-chart-pie"></i> Status Pendaftaran</h3>
                        <div class="chart-wrapper">
                            <canvas id="pieChart"></canvas>
                        </div>
                    </div>
                    
                    <div class="chart-box">
                        <h3><i class="fas fa-chart-bar"></i> Berkas per Status</h3>
                        <div class="chart-wrapper">
                            <canvas id="barChart"></canvas>
                        </div>
                    </div>
                    
                    <div class="chart-box">
                        <h3><i class="fas fa-percentage"></i> Perbandingan Status</h3>
                        <div class="chart-wrapper">
                            <canvas id="doughnutChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Daftar Pendaftaran Terbaru -->
            <div class="form-section">
                <h2><i class="fas fa-history"></i> Pendaftaran Terbaru</h2>
                <?php
                $sqlRecent = "SELECT cs.*, u.nama as nama_pendaftar, ta.tahun 
                             FROM calon_siswa cs 
                             JOIN users u ON cs.user_id = u.id 
                             JOIN tahun_ajaran ta ON cs.tahun_ajaran_id = ta.id 
                             WHERE cs.tahun_ajaran_id = ?
                             ORDER BY cs.created_at DESC LIMIT 10";
                $stmt = $conn->prepare($sqlRecent);
                $stmt->bind_param("i", $tahunAjaranId);
                $stmt->execute();
                $resultRecent = $stmt->get_result();
                ?>
                
                <?php if($resultRecent->num_rows > 0): ?>
                <div class="table-container table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>No. Pendaftaran</th>
                                <th>Nama Siswa</th>
                                <th>Nama Orangtua/Wali</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $resultRecent->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['nomor_pendaftaran']); ?></td>
                                <td><?php echo htmlspecialchars($row['nama_siswa']); ?></td>
                                <td><?php echo htmlspecialchars($row['nama_pendaftar']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <?php 
                                    $statusClass = [
                                        'pending' => 'status-pending',
                                        'diterima' => 'status-valid',
                                        'tidak_diterima' => 'status-tidak-valid'
                                    ];
                                    $statusText = [
                                        'pending' => 'Pending',
                                        'diterima' => 'Diterima',
                                        'tidak_diterima' => 'Tidak Diterima'
                                    ];
                                    ?>
                                    <span class="status-badge <?php echo $statusClass[$row['status_terima']]; ?>">
                                        <?php echo $statusText[$row['status_terima']]; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="verifikasi.php?id=<?php echo $row['id']; ?>" 
                                       class="btn-primary btn-sm">
                                        <i class="fas fa-eye"></i> Lihat
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-users fa-3x"></i>
                    <p>Belum ada pendaftaran</p>
                </div>
                <?php endif; ?>
                <?php $stmt->close(); ?>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Chart Pie - Status Pendaftaran
        const pieCtx = document.getElementById('pieChart').getContext('2d');
        const pieChart = new Chart(pieCtx, {
            type: 'pie',
            data: {
                labels: ['Diterima', 'Pending', 'Tidak Diterima'],
                datasets: [{
                    data: [
                        <?php echo (int)($pieData['diterima'] ?? 0); ?>,
                        <?php echo (int)($pieData['pending'] ?? 0); ?>,
                        <?php echo (int)($pieData['tidak_diterima'] ?? 0); ?>,
                    ],
                    backgroundColor: [
                        '#28a745', // Hijau untuk Diterima
                        '#ffc107', // Kuning untuk Pending
                        '#dc3545', // Merah untuk Tidak Diterima
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
        
        
        // Bar Chart - Berkas per Status
        const barCtx = document.getElementById('barChart').getContext('2d');
        const barChart = new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: ['Diterima', 'Pending', 'Tidak Diterima'],
                datasets: [
                    {
                        label: 'Total Berkas',
                        data: [
                            <?php echo (int)($berkasPerStatus['diterima']['total_berkas'] ?? 0); ?>,
                            <?php echo (int)($berkasPerStatus['pending']['total_berkas'] ?? 0); ?>,
                            <?php echo (int)($berkasPerStatus['tidak_diterima']['total_berkas'] ?? 0); ?>
                        ],
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Berkas Valid',
                        data: [
                            <?php echo (int)($berkasPerStatus['diterima']['valid'] ?? 0); ?>,
                            <?php echo (int)($berkasPerStatus['pending']['valid'] ?? 0); ?>,
                            <?php echo (int)($berkasPerStatus['tidak_diterima']['valid'] ?? 0); ?>
                        ],
                        backgroundColor: 'rgba(75, 192, 192, 0.5)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            callback: function(value) {
                                if (value % 1 === 0) {
                                    return value;
                                }
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: ${Math.round(context.raw)}`;
                            }
                        }
                    }
                }
            }
        });
        
        // Doughnut Chart - Perbandingan
        const doughnutCtx = document.getElementById('doughnutChart').getContext('2d');
        const doughnutChart = new Chart(doughnutCtx, {
            type: 'doughnut',
            data: {
                labels: ['Diterima', 'Pending', 'Tidak Diterima', 'Kuota Tersisa'],
                datasets: [{
                    data: [
                        <?php echo (int)$stats['diterima']; ?>,
                        <?php echo (int)$stats['pending']; ?>,
                        <?php echo (int)$stats['ditolak']; ?>,
                        <?php echo $tahunAjaran ? max(0, $tahunAjaran['kuota'] - $stats['diterima']) : 0; ?>
                    ],
                    backgroundColor: [
                        '#28a745',
                        '#ffc107',
                        '#dc3545',
                        '#17a2b8'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = <?php echo $tahunAjaran ? $tahunAjaran['kuota'] + $stats['pending'] + $stats['ditolak'] : $stats['total']; ?>;
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
        
        // Auto refresh data setiap 30 detik
        setInterval(() => {
            fetch('?refresh=true')
                .then(response => response.text())
                .then(html => {
                    console.log('Data refreshed');
                });
        }, 30000);
    </script>
</body>
</html>