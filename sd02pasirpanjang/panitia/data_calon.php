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

// Ambil tahun ajaran untuk filter
$sqlTahun = "SELECT tahun FROM tahun_ajaran ORDER BY tahun DESC";
$resultTahun = $conn->query($sqlTahun);

// Ambil semua berkas untuk modal
$sqlAllBerkas = "SELECT b.*, cs.nomor_pendaftaran, cs.nama_siswa 
                 FROM berkas b 
                 JOIN calon_siswa cs ON b.calon_siswa_id = cs.id 
                 ORDER BY b.calon_siswa_id, b.jenis_berkas";
$resultAllBerkas = $conn->query($sqlAllBerkas);
$allBerkas = [];
while($row = $resultAllBerkas->fetch_assoc()) {
    $allBerkas[$row['calon_siswa_id']][] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Calon Siswa - PPDB SDN Pasirpanjang 02</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .filter-container {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }
        
        .badge-berkas {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
        }
        
        .badge-berkas:hover {
            opacity: 0.8;
        }
        
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        
        .action-buttons {
            display: flex;
            gap: 0.25rem;
            flex-wrap: wrap;
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.85rem;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            width: 90%;
            max-width: 800px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        
        .modal-header {
            padding: 1.5rem;
            background: #4a6fa5;
            color: white;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            margin: 0;
        }
        
        .close-modal {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            line-height: 1;
        }
        
        .modal-body {
            padding: 1.5rem;
            max-height: 70vh;
            overflow-y: auto;
        }
        
        .berkas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .berkas-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            border: 1px solid #dee2e6;
        }
        
        .berkas-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .berkas-title {
            font-weight: 600;
            color: #495057;
        }
        
        .berkas-status {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
            border-radius: 10px;
        }
        
        .berkas-file {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
            word-break: break-all;
        }
        
        .berkas-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .btn-view {
            background: #17a2b8;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 0.25rem 0.5rem;
            cursor: pointer;
            font-size: 0.8rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .btn-view:hover {
            background: #138496;
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
                <h1><i class="fas fa-users"></i> Data Calon Siswa</h1>
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
            <!-- Filter Section -->
            <div class="filter-container">
                <h3><i class="fas fa-filter"></i> Filter Data</h3>
                <form method="GET" class="filter-form">
                    <div class="form-group">
                        <label for="tahun">Tahun Ajaran</label>
                        <select id="tahun" name="tahun" class="form-control">
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
                        <label for="status">Status Penerimaan</label>
                        <select id="status" name="status" class="form-control">
                            <option value="">Semua Status</option>
                            <option value="pending" <?php echo $filterStatus == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="diterima" <?php echo $filterStatus == 'diterima' ? 'selected' : ''; ?>>Diterima</option>
                            <option value="tidak_diterima" <?php echo $filterStatus == 'tidak_diterima' ? 'selected' : ''; ?>>Tidak Diterima</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="berkas">Status Berkas</label>
                        <select id="berkas" name="berkas" class="form-control">
                            <option value="">Semua Berkas</option>
                            <option value="lengkap" <?php echo $filterBerkas == 'lengkap' ? 'selected' : ''; ?>>Berkas Lengkap (6)</option>
                            <option value="tidak_lengkap" <?php echo $filterBerkas == 'tidak_lengkap' ? 'selected' : ''; ?>>Berkas Tidak Lengkap</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <a href="data_calon.php" class="btn-secondary">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
            
            <!-- Data Table -->
            <div class="form-section">
                <div class="section-header">
                    <h2><i class="fas fa-list"></i> Daftar Calon Siswa</h2>
                    <div class="section-info">
                        Total Data: <strong><?php echo $result->num_rows; ?></strong>
                        <?php if($filterTahun): ?> | Tahun: <strong><?php echo htmlspecialchars($filterTahun); ?></strong><?php endif; ?>
                        <?php if($filterStatus): ?> | Status: <strong><?php echo ucfirst(str_replace('_', ' ', $filterStatus)); ?></strong><?php endif; ?>
                    </div>
                </div>
                
                <?php if($result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>No. Pendaftaran</th>
                                <th>Nama Siswa</th>
                                <th>Nama Orangtua/Wali</th>
                                <th>Tahun Ajaran</th>
                                <th>Tanggal Daftar</th>
                                <th>Status Berkas</th>
                                <th>Status Penerimaan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1; 
                            $result->data_seek(0); // Reset pointer
                            while($row = $result->fetch_assoc()): 
                                $berkasCount = isset($allBerkas[$row['id']]) ? count($allBerkas[$row['id']]) : 0;
                                $berkasValidCount = 0;
                                if (isset($allBerkas[$row['id']])) {
                                    foreach ($allBerkas[$row['id']] as $berkas) {
                                        if ($berkas['status'] == 'valid') {
                                            $berkasValidCount++;
                                        }
                                    }
                                }
                            ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['nomor_pendaftaran']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($row['nama_siswa']); ?></td>
                                <td><?php echo htmlspecialchars($row['nama_pendaftar']); ?></td>
                                <td><?php echo htmlspecialchars($row['tahun']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <?php if($berkasCount > 0): ?>
                                        <span class="badge-berkas <?php echo $berkasCount >= 6 ? 'badge-success' : ($berkasCount > 0 ? 'badge-warning' : 'badge-danger'); ?>"
                                              onclick="lihatBerkas(<?php echo $row['id']; ?>)"
                                              title="Klik untuk lihat detail berkas">
                                            <?php echo $berkasCount; ?>/6 berkas
                                            (<?php echo $berkasValidCount; ?> valid)
                                        </span>
                                    <?php else: ?>
                                        <span class="badge-berkas badge-danger">
                                            Belum upload
                                        </span>
                                    <?php endif; ?>
                                </td>
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
                                    <div class="action-buttons">
                                        <a href="verifikasi.php?id=<?php echo $row['id']; ?>" 
                                           class="btn-primary btn-sm" title="Verifikasi"> Verifikasi Berkas
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button type="button" onclick="lihatBerkas(<?php echo $row['id']; ?>)" 
                                                class="btn-info btn-sm" title="Lihat Berkas">Lihat Berkas
                                            <i class="fas fa-file-alt"></i>
                                        </button>
                                        <?php if($row['status_terima'] == 'diterima'): ?>
                                        <?php endif; ?>
                                        <?php if($berkasCount > 0): ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-users fa-3x"></i>
                    <h3>Tidak ada data calon siswa</h3>
                    <p>Coba gunakan filter yang berbeda</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Modal Berkas -->
    <div id="berkasModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Detail Berkas Calon Siswa</h3>
                <button type="button" class="close-modal">&times;</button>
            </div>
            <div class="modal-body" id="modalBerkasBody">
                <!-- Content akan diisi via JavaScript -->
            </div>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
    <script>
        // Modal functions
        const modal = document.getElementById('berkasModal');
        const closeBtn = document.querySelector('.close-modal');
        
        // Data berkas dari PHP
        const allBerkas = <?php echo json_encode($allBerkas); ?>;
        
        function lihatBerkas(calonSiswaId) {
            if (allBerkas[calonSiswaId]) {
                let html = '<h4>Daftar Berkas:</h4>';
                html += '<div class="berkas-grid">';
                
                allBerkas[calonSiswaId].forEach(berkas => {
                    const statusClass = {
                        'valid': 'badge-success',
                        'tidak_valid': 'badge-danger',
                        'belum_diperiksa': 'badge-warning'
                    };
                    
                    const statusText = {
                        'valid': 'Valid',
                        'tidak_valid': 'Tidak Valid',
                        'belum_diperiksa': 'Belum Diperiksa'
                    };
                    
                    const filePath = '../assets/uploads/' + berkas.file_path + '/' + berkas.nama_file;
                    const fileExtension = berkas.nama_file.split('.').pop().toLowerCase();
                    const isImage = ['jpg', 'jpeg', 'png', 'gif'].includes(fileExtension);
                    
                    html += `
                        <div class="berkas-item">
                            <div class="berkas-header">
                                <div class="berkas-title">${berkas.jenis_berkas}</div>
                                <span class="berkas-status ${statusClass[berkas.status] || 'badge-info'}">
                                    ${statusText[berkas.status] || berkas.status}
                                </span>
                            </div>
                            <div class="berkas-file">
                                <i class="fas fa-file"></i> ${berkas.nama_file}
                            </div>
                            <div class="berkas-actions">
                                <a href="${filePath}" target="_blank" class="btn-view">
                                    <i class="fas fa-eye"></i> Lihat
                                </a>
                                <a href="${filePath}" download class="btn-view">
                                    <i class="fas fa-download"></i> Download
                                </a>
                            </div>
                            ${berkas.catatan ? `<div class="berkas-catatan" style="margin-top: 0.5rem; font-size: 0.8rem; color: #666;">
                                <strong>Catatan:</strong> ${berkas.catatan}
                            </div>` : ''}
                        </div>
                    `;
                });
                
                html += '</div>';
                
                // Tambahkan info jika ada berkas yang kurang
                const totalBerkas = allBerkas[calonSiswaId].length;
                if (totalBerkas < 6) {
                    html += `<div class="alert alert-warning" style="margin-top: 1rem;">
                        <i class="fas fa-exclamation-triangle"></i>
                        Berkas belum lengkap: ${totalBerkas} dari 6 berkas
                    </div>`;
                }
                
                document.getElementById('modalBerkasBody').innerHTML = html;
                modal.style.display = 'block';
            } else {
                document.getElementById('modalBerkasBody').innerHTML = 
                    '<div class="empty-state">' +
                    '<i class="fas fa-file fa-3x"></i>' +
                    '<h3>Belum ada berkas</h3>' +
                    '<p>Calon siswa ini belum mengupload berkas</p>' +
                    '</div>';
                modal.style.display = 'block';
            }
        }
        
        closeBtn.onclick = function() {
            modal.style.display = 'none';
        }
        
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
        
        // Export to Excel
        function exportToExcel() {
            const table = document.querySelector('.table');
            const ws = XLSX.utils.table_to_sheet(table);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, "Data Calon");
            
            const fileName = `Data_Calon_<?php echo date('Y-m-d'); ?>.xlsx`;
            XLSX.writeFile(wb, fileName);
        }
    </script>
</body>
</html>