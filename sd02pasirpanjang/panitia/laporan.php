<?php
require_once '../config/database.php';
redirectIfNotAuthorized(['panitia']);

$db = new Database();
$conn = $db->getConnection();

// Ambil tahun ajaran aktif
$sqlTaAktif = "
    SELECT tahun
    FROM tahun_ajaran
    WHERE status = 'aktif'
    LIMIT 1
";

$resultTaAktif = $conn->query($sqlTaAktif);

// Default null
$taAktif = null;

if ($resultTaAktif && $resultTaAktif->num_rows > 0) {

    $taAktif = $resultTaAktif->fetch_assoc();
}

// Filter
$filterTahun = $_GET['tahun'] ?? ($taAktif['tahun'] ?? '');
$filterStatus = $_GET['status'] ?? '';

// Query dengan filter
$whereClause = "WHERE 1=1";
if (!empty($filterTahun)) {
    $whereClause .= " AND ta.tahun = '$filterTahun'";
}

if (!empty($filterStatus)) {
    $whereClause .= " AND cs.status_terima = '$filterStatus'";
}

$sql = "SELECT cs.*, u.nama as nama_pendaftar, u.email, u.no_hp, ta.tahun,
        (SELECT COUNT(*) FROM berkas WHERE calon_siswa_id = cs.id AND status = 'valid') as berkas_valid,
        (SELECT COUNT(*) FROM berkas WHERE calon_siswa_id = cs.id) as total_berkas
        FROM calon_siswa cs 
        JOIN users u ON cs.user_id = u.id 
        JOIN tahun_ajaran ta ON cs.tahun_ajaran_id = ta.id 
        $whereClause 
        ORDER BY cs.created_at DESC";
$result = $conn->query($sql);

// Hitung statistik
$result->data_seek(0);
$stats = ['diterima' => 0, 'pending' => 0, 'tidak_diterima' => 0];
$totalBerkas = 0;
$validBerkas = 0;

while($row = $result->fetch_assoc()) {
    $stats[$row['status_terima']]++;
    $totalBerkas += $row['total_berkas'];
    $validBerkas += $row['berkas_valid'];
}

// Reset pointer untuk digunakan lagi
$result->data_seek(0);

// Ambil tahun ajaran untuk filter
$sqlTahun = "SELECT DISTINCT tahun FROM tahun_ajaran ORDER BY tahun DESC";
$resultTahun = $conn->query($sqlTahun);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - PPDB SDN Pasirpanjang 02</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- jsPDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="dashboard-header">
            <div class="container">
                <h1><i class="fas fa-file-pdf"></i> Laporan Penerimaan Siswa SDN Pasirpanjang 02</h1>
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
            <!-- Filter -->
            <div class="form-section">
                <h2><i class="fas fa-filter"></i> Filter Laporan</h2>
                <form method="GET" class="form-row">
                    <div class="form-group">
                        <label for="tahun">Tahun Ajaran</label>
                        <select id="tahun" name="tahun">
                            <option value="">Semua Tahun</option>
                            <?php while($row = $resultTahun->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($row['tahun']); ?>" 
                                <?php echo $filterTahun == $row['tahun'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($row['tahun']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="">Semua Status</option>
                            <option value="pending" <?php echo $filterStatus == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="diterima" <?php echo $filterStatus == 'diterima' ? 'selected' : ''; ?>>Diterima</option>
                            <option value="tidak_diterima" <?php echo $filterStatus == 'tidak_diterima' ? 'selected' : ''; ?>>Tidak Diterima</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <a href="laporan.php" class="btn-secondary">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                        <button type="button" onclick="exportToPDF()" class="btn-danger">
                            <i class="fas fa-file-pdf"></i> Download PDF
                        </button>
                        <button type="button" onclick="exportToExcel()" class="btn-success">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Laporan -->
            <div class="form-section" id="reportContent">
                <div class="section-header">
                    <h2><i class="fas fa-list"></i> Data Calon Siswa</h2>
                    <div class="report-info">
                        <small>
                            Total Data: <strong><?php echo $result->num_rows; ?></strong> 
                            <?php if($filterTahun): ?> | Tahun: <strong><?php echo htmlspecialchars($filterTahun); ?></strong><?php endif; ?>
                            <?php if($filterStatus): ?> | Status: <strong><?php echo ucfirst(str_replace('_', ' ', $filterStatus)); ?></strong><?php endif; ?>
                        </small>
                    </div>
                </div>
                
                <?php if($result->num_rows > 0): ?>
                <div class="table-container table-responsive">
                    <table class="table" id="reportTable">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>No. Pendaftaran</th>
                                <th>Nama Siswa</th>
                                <th>Tahun Ajaran</th>
                                <th>Nama Orang Tua/Wali</th>
                                <th>Tanggal Daftar</th>
                                <th>Status Berkas</th>
                                <th>Status Penerimaan</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1; 
                            $result->data_seek(0);
                            while($row = $result->fetch_assoc()): 
                            ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo htmlspecialchars($row['nomor_pendaftaran']); ?></td>
                                <td><?php echo htmlspecialchars($row['nama_siswa']); ?></td>
                                <td><?php echo htmlspecialchars($row['tahun']); ?></td>
                                <td><?php echo htmlspecialchars($row['nama_pendaftar']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <?php 
                                    if ($row['total_berkas'] >= 6) {
                                        echo '<span class="badge badge-success">Lengkap (' . $row['berkas_valid'] . '/' . $row['total_berkas'] . ' valid)</span>';
                                    } elseif ($row['total_berkas'] > 0) {
                                        echo '<span class="badge badge-warning">' . $row['total_berkas'] . '/6 berkas</span>';
                                    } else {
                                        echo '<span class="badge badge-danger">Tidak ada berkas</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    $statusClass = [
                                        'pending' => 'badge-warning',
                                        'diterima' => 'badge-success',
                                        'tidak_diterima' => 'badge-danger'
                                    ];
                                    ?>
                                    <span class="badge <?php echo $statusClass[$row['status_terima']]; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $row['status_terima'])); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($row['catatan_panitia'] ?? '-'); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Statistik -->
                <div class="stats-grid" style="margin-top: 2rem;">
                    <div class="stat-card">
                        <div class="stat-icon primary">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Total Data</h3>
                            <div class="number"><?php echo $result->num_rows; ?></div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Diterima</h3>
                            <div class="number"><?php echo $stats['diterima']; ?></div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon warning">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Pending</h3>
                            <div class="number"><?php echo $stats['pending']; ?></div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon danger">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Tidak Diterima</h3>
                            <div class="number"><?php echo $stats['tidak_diterima']; ?></div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon info">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Total Berkas</h3>
                            <div class="number"><?php echo $totalBerkas; ?></div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon success">
                            <i class="fas fa-file-check"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Berkas Valid</h3>
                            <div class="number"><?php echo $validBerkas; ?></div>
                        </div>
                    </div>
                </div>
                
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-file-excel fa-3x"></i>
                    <h3>Tidak ada data laporan</h3>
                    <p>Coba gunakan filter yang berbeda</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
    <script src="https://unpkg.com/xlsx/dist/xlsx.full.min.js"></script>
    <script>
        
        // Export to Excel
        function exportToExcel() {
            const table = document.getElementById('reportTable');
            const ws = XLSX.utils.table_to_sheet(table);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, "Laporan");
            
            const fileName = `Laporan_PPDB_<?php echo date('Y-m-d'); ?>.xlsx`;
            XLSX.writeFile(wb, fileName);
        }

        // Export to PDF menggunakan jsPDF
        function exportToPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('landscape', 'mm', 'a4');
            
            // Header
            doc.setFontSize(16);
            doc.text("LAPORAN DATA CALON SISWA PPDB", 148, 15, { align: 'center' });
            
            doc.setFontSize(12);
            doc.text("SD NEGERI PASIRPANJANG 02", 148, 22, { align: 'center' });
            doc.text("Jl. Raya No. 96, Desa Pasirpanjang, Kecamatan Salem, Kabupaten Brebes, Jawa Tengah", 148, 28, { align: 'center' });
            
            // Data tabel
            const tableColumn = [
                "No", 
                "No. Pendaftaran", 
                "Nama Siswa", 
                "Tahun Ajaran", 
                "Nama Orangtua/Wali",
                "Tanggal Daftar", 
                "Status Berkas", 
                "Status Penerimaan", 
                "Catatan"
            ];
            
            const tableRows = [];
            
            <?php
            $result->data_seek(0);
            $no = 1;
            while($row = $result->fetch_assoc()): 
                // Tentukan status berkas
                $statusBerkasText = '';
                if ($row['total_berkas'] >= 6) {
                    $statusBerkasText = 'Lengkap (' . $row['berkas_valid'] . '/' . $row['total_berkas'] . ' valid)';
                } elseif ($row['total_berkas'] > 0) {
                    $statusBerkasText = $row['total_berkas'] . '/6 berkas';
                } else {
                    $statusBerkasText = 'Tidak ada berkas';
                }
                
                // Status penerimaan
                $statusText = ucfirst(str_replace('_', ' ', $row['status_terima']));
                
                // Catatan panitia
                $catatan = $row['catatan_panitia'] ?? '-';
                // Escape karakter untuk JavaScript
                $catatan = str_replace(["\n", "\r"], " ", $catatan);
                $catatan = addslashes($catatan);
            ?>
            tableRows.push([
                "<?php echo $no++; ?>",
                "<?php echo addslashes($row['nomor_pendaftaran']); ?>",
                "<?php echo addslashes($row['nama_siswa']); ?>",
                "<?php echo addslashes($row['tahun']); ?>",
                "<?php echo addslashes($row['nama_pendaftar']); ?>",
                "<?php echo date('d/m/Y', strtotime($row['created_at'])); ?>",
                "<?php echo $statusBerkasText; ?>",
                "<?php echo $statusText; ?>",
                "<?php echo $catatan; ?>"
            ]);
            <?php endwhile; ?>
            
            // Buat tabel
            doc.autoTable({
                head: [tableColumn],
                body: tableRows,
                startY: 35,
                theme: 'grid',
                styles: { 
                    fontSize: 7, 
                    cellPadding: 2,
                    overflow: 'linebreak',
                    cellWidth: 'wrap'
                },
                headStyles: { 
                    fillColor: [41, 128, 185],
                    fontSize: 8
                },
                columnStyles: {
                    0: { cellWidth: 15 }, // No
                    1: { cellWidth: 30 }, // No. Pendaftaran
                    2: { cellWidth: 35 }, // Nama Siswa
                    3: { cellWidth: 25 }, // Tahun Ajaran
                    4: { cellWidth: 30 }, // Nama Orangtua/Wali
                    5: { cellWidth: 25 }, // Tanggal Daftar
                    6: { cellWidth: 30 }, // Status Berkas
                    7: { cellWidth: 30 }, // Status Penerimaan
                    8: { cellWidth: 50 }  // Catatan Panitia
                },
                margin: { top: 35 }
            });
            
            // Footer - hanya halaman
            const pageCount = doc.internal.getNumberOfPages();
            for (let i = 1; i <= pageCount; i++) {
                doc.setPage(i);
                doc.setFontSize(8);
                doc.text(
                    `Halaman ${i} dari ${pageCount}`,
                    doc.internal.pageSize.width - 20,
                    doc.internal.pageSize.height - 10
                );
            }
            
            // Simpan PDF
            doc.save(`Laporan_PPDB_${new Date().toISOString().slice(0,10)}.pdf`);
        }
    </script>
</body>
</html>