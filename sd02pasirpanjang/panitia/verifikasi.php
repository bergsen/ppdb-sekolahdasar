<?php
require_once '../config/database.php';
redirectIfNotAuthorized(['panitia']);

error_reporting(E_ALL);
ini_set('display_errors', 1);

$db   = new Database();
$conn = $db->getConnection();

/* ================= VALIDASI ID ================= */
$calonSiswaId = (int)($_GET['id'] ?? 0);
if ($calonSiswaId <= 0) {
    header("Location: data_calon.php");
    exit;
}

/* ================= DATA SISWA ================= */
$sql = "
    SELECT cs.*, u.nama AS nama_pendaftar, u.email, u.no_hp, ta.tahun
    FROM calon_siswa cs
    JOIN users u ON cs.user_id = u.id
    JOIN tahun_ajaran ta ON cs.tahun_ajaran_id = ta.id
    WHERE cs.id = $calonSiswaId
";
$res = $conn->query($sql);
if (!$res || $res->num_rows === 0) {
    header("Location: data_calon.php");
    exit;
}
$calonSiswa = $res->fetch_assoc();

/* ================= DEFINISI BERKAS ================= */
$semuaJenisBerkas = [
    ['jenis' => 'Foto Siswa',     'required' => true],
    ['jenis' => 'Akta Kelahiran', 'required' => true],
    ['jenis' => 'Kartu Keluarga', 'required' => true],
    ['jenis' => 'KTP Orang Tua',  'required' => true],
    ['jenis' => 'Ijazah TK',      'required' => true],
    ['jenis' => 'Surat Pindah',   'required' => false],
];

/* ================= BERKAS TERUPLOAD ================= */
$berkasTersedia = [];
$q = $conn->query("SELECT * FROM berkas WHERE calon_siswa_id = $calonSiswaId");
while ($row = $q->fetch_assoc()) {
    $berkasTersedia[$row['jenis_berkas']] = $row;
}


/* ================= DOWNLOAD ZIP ================= */
/* ================= DOWNLOAD ZIP LANGSUNG ================= */
if (isset($_GET['download_zip'])) {

    if (!class_exists('ZipArchive')) {
        die('ZipArchive tidak aktif');
    }

    $zip = new ZipArchive();

    // bikin ZIP di memory
    $tmpZip = tempnam(sys_get_temp_dir(), 'zip_');

    if ($zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        die('Gagal membuat ZIP');
    }

    $adaFile = false;
    $listBerkas = [];

    foreach ($semuaJenisBerkas as $j) {
        $label = $j['jenis'];

        if (!isset($berkasTersedia[$label])) {
            continue;
        }

        $b = $berkasTersedia[$label];

        // path ASLI file
        $fullPath = realpath(
            __DIR__ . '/../assets/uploads/' .
            $b['file_path'] . '/' .
            $b['nama_file']
        );

        if ($fullPath && is_file($fullPath)) {
            $zip->addFile(
                $fullPath,
                strtoupper(str_replace(' ', '_', $label)) . '_' . basename($b['nama_file'])
            );
            $adaFile = true;
            $listBerkas[] = $label;
        }
    }

    if (!$adaFile) {
        $zip->close();
        unlink($tmpZip);
        die('Tidak ada berkas valid');
    }

    // FILE INFO
    $info  = "DATA CALON SISWA\n";
    $info .= "====================\n";
    $info .= "No Pendaftaran : {$calonSiswa['nomor_pendaftaran']}\n";
    $info .= "Nama Siswa     : {$calonSiswa['nama_siswa']}\n";
    $info .= "Tanggal Unduh  : " . date('d/m/Y H:i:s') . "\n\n";
    $info .= "DAFTAR BERKAS:\n";

    foreach ($listBerkas as $b) {
        $info .= "- $b\n";
    }

    $zip->addFromString('INFO_SISWA.txt', $info);
    $zip->close();

    // HEADER DOWNLOAD
    $zipName =
        $calonSiswa['nomor_pendaftaran'] . '_' .
        preg_replace('/[^a-zA-Z0-9]/', '_', $calonSiswa['nama_siswa']) . '.zip';

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zipName . '"');
    header('Content-Length: ' . filesize($tmpZip));

    readfile($tmpZip);
    unlink($tmpZip);
    exit;
}


/* ================= FLASH ================= */
$success = '';
$error   = '';

if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

/* ================= SIMPAN VERIFIKASI ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $status  = $db->escapeString($_POST['status']);
    $catatan = $db->escapeString($_POST['catatan_panitia']);

    try {
        $conn->begin_transaction();

        $conn->query("
            UPDATE calon_siswa
            SET status_terima = '$status',
                catatan_panitia = '$catatan'
            WHERE id = $calonSiswaId
        ");

        if (!empty($_POST['berkas_status'])) {
            foreach ($_POST['berkas_status'] as $id => $st) {
                $id = (int)$id;
                $st = $db->escapeString($st);
                $ct = $db->escapeString($_POST['berkas_catatan'][$id] ?? '');

                $conn->query("
                    UPDATE berkas
                    SET status = '$st', catatan = '$ct'
                    WHERE id = $id
                ");
            }
        }

        $conn->commit();
        $success = 'Status berhasil diperbarui!';
    } catch (Exception $e) {
        $conn->rollback();
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi - PPDB SDN Pasirpanjang 02</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .data-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .data-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .data-card h3 {
            color: #166088;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #f5f5f5;
        }
        
        .info-label {
            font-weight: 600;
            color: #555;
        }
        
        .info-value {
            color: #333;
        }
        
        .file-preview {
            max-width: 100%;
            height: auto;
            border-radius: 5px;
            margin-top: 0.5rem;
            border: 1px solid #ddd;
        }
        
        .berkas-container {
            margin: 1.5rem 0;
        }
        
        .berkas-item {
            margin-bottom: 1.5rem;
            padding: 1.5rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border-left: 4px solid #4a6fa5;
        }
        
        .berkas-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .berkas-title {
            font-weight: 600;
            color: #495057;
            font-size: 1.1rem;
        }
        
        .berkas-status-indicator {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-ada { background: #d4edda; color: #155724; }
        .status-tidak-ada { background: #f8d7da; color: #721c24; }
        
        .berkas-content {
            margin-bottom: 1rem;
        }
        
        .file-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            padding: 0.5rem;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .file-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .btn-view {
            padding: 0.5rem 1rem;
            background: #17a2b8;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-view:hover {
            background: #138496;
        }
        
        .btn-download {
            padding: 0.5rem 1rem;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-download:hover {
            background: #218838;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .download-all {
            margin: 1rem 0;
            text-align: center;
        }
        
        .btn-zip {
            background: #ffc107;
            color: #212529;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-zip:hover {
            background: #e0a800;
        }
        
        .alert-berkas {
            margin: 1rem 0;
            padding: 1rem;
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            color: #856404;
        }
        
        .berkas-count {
            font-weight: bold;
            color: #333;
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="dashboard-header">
            <div class="container">
                <h1><i class="fas fa-check-circle"></i> Verifikasi Pendaftaran</h1>
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
            
            <!-- Header dengan tombol download ZIP -->
            <div class="form-section">
                <div class="section-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h2><i class="fas fa-user-graduate"></i> Informasi Calon Siswa</h2>
                    <div class="action-buttons">
                        <?php if(count($berkasTersedia) > 0): ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="data-grid">
                    <div class="data-card">
                        <h3><i class="fas fa-id-card"></i> Identitas</h3>
                        <div class="info-item">
                            <span class="info-label">No. Pendaftaran:</span>
                            <span class="info-value"><?php echo htmlspecialchars($calonSiswa['nomor_pendaftaran']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Nama Siswa:</span>
                            <span class="info-value"><?php echo htmlspecialchars($calonSiswa['nama_siswa']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Jenis Kelamin:</span>
                            <span class="info-value"><?php echo $calonSiswa['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan'; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Tempat/Tgl Lahir:</span>
                            <span class="info-value">
                                <?php echo htmlspecialchars($calonSiswa['tempat_lahir']); ?>, 
                                <?php echo date('d/m/Y', strtotime($calonSiswa['tanggal_lahir'])); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="data-card">
                        <h3><i class="fas fa-user-friends"></i> Orang Tua</h3>
                        <div class="info-item">
                            <span class="info-label">Nama Ayah:</span>
                            <span class="info-value"><?php echo htmlspecialchars($calonSiswa['nama_ayah']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Pekerjaan Ayah:</span>
                            <span class="info-value"><?php echo htmlspecialchars($calonSiswa['pekerjaan_ayah']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Nama Ibu:</span>
                            <span class="info-value"><?php echo htmlspecialchars($calonSiswa['nama_ibu']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Pekerjaan Ibu:</span>
                            <span class="info-value"><?php echo htmlspecialchars($calonSiswa['pekerjaan_ibu']); ?></span>
                        </div>
                    </div>
                    
                    <div class="data-card">
                        <h3><i class="fas fa-info-circle"></i> Informasi Lain</h3>
                        <div class="info-item">
                            <span class="info-label">Nama Pendaftar:</span>
                            <span class="info-value"><?php echo htmlspecialchars($calonSiswa['nama_pendaftar']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email:</span>
                            <span class="info-value"><?php echo htmlspecialchars($calonSiswa['email']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">No. HP:</span>
                            <span class="info-value"><?php echo htmlspecialchars($calonSiswa['no_hp']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Tanggal Daftar:</span>
                            <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($calonSiswa['created_at'])); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Form Verifikasi -->
            <form method="POST" class="form-section">
                <h2><i class="fas fa-file-upload"></i> Verifikasi Berkas (<?php echo count($berkasTersedia); ?> dari 6 berkas)</h2>
                
                <?php if(count($berkasTersedia) > 0): ?>
                <div class="download-all">
                    <a href="verifikasi.php?id=<?php echo $calonSiswaId; ?>&download_zip=1" 
                       class="btn-zip">
                        <i class="fas fa-file-archive"></i> Download Semua Berkas dalam ZIP
                    </a>
                </div>
                
                <!-- Tampilkan info jumlah berkas -->
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    Total berkas yang diupload: <span class="berkas-count"><?php echo count($berkasTersedia); ?></span> dari 6 berkas
                    <?php if(count($berkasTersedia) < 6): ?>
                    | <strong>Berkas yang belum diupload:</strong> 
                    <?php 
                    $missing = [];
                    foreach ($semuaJenisBerkas as $jenis) {
                        if (!isset($berkasTersedia[$jenis['jenis']]) && $jenis['required']) {
                            $missing[] = $jenis['jenis'];
                        }
                    }
                    echo implode(', ', $missing);
                    ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="berkas-container">
                    <?php 
                    // Tampilkan semua 6 jenis berkas
                    foreach ($semuaJenisBerkas as $jenis): 
                        $berkasAda = isset($berkasTersedia[$jenis['jenis']]);
                        $berkasData = $berkasAda ? $berkasTersedia[$jenis['jenis']] : null;
                    ?>
                    <div class="berkas-item">
                        <div class="berkas-header">
                            <div class="berkas-title">
                                <?php echo htmlspecialchars($jenis['jenis']); ?>
                                <?php if($jenis['required']): ?>
                                <span style="color: #dc3545; font-size: 0.8rem;">*</span>
                                <?php endif; ?>
                            </div>
                            <span class="berkas-status-indicator <?php echo $berkasAda ? 'status-ada' : 'status-tidak-ada'; ?>">
                                <?php echo $berkasAda ? 'TERUPLOAD' : 'BELUM DIUPLOAD'; ?>
                            </span>
                        </div>
                        
                        <div class="berkas-content">
                            <?php if($berkasAda): ?>
                            <div class="file-info">
                                <i class="fas fa-file"></i>
                                <span><?php echo htmlspecialchars($berkasData['nama_file']); ?></span>
                            </div>
                            
                            <div class="file-actions">
                                <a href="../assets/uploads/<?php echo htmlspecialchars($berkasData['file_path']); ?>/<?php echo htmlspecialchars($berkasData['nama_file']); ?>" 
                                   target="_blank" class="btn-view">
                                    <i class="fas fa-eye"></i> Lihat File
                                </a>
                                <a href="../assets/uploads/<?php echo htmlspecialchars($berkasData['file_path']); ?>/<?php echo htmlspecialchars($berkasData['nama_file']); ?>" 
                                   download class="btn-download">
                                    <i class="fas fa-download"></i> Download
                                </a>
                            </div>
                            
                            <?php 
                            // Tampilkan preview jika file gambar
                            $isImage = $berkasAda && preg_match('/\.(jpg|jpeg|png|gif)$/i', $berkasData['nama_file']);
                            if ($isImage): 
                            ?>
                            <div style="margin-top: 1rem;">
                                <img src="../assets/uploads/<?php echo htmlspecialchars($berkasData['file_path']); ?>/<?php echo htmlspecialchars($berkasData['nama_file']); ?>" 
                                     alt="<?php echo htmlspecialchars($jenis['jenis']); ?>" 
                                     class="file-preview" 
                                     style="max-width: 300px; max-height: 200px; cursor: pointer;"
                                     onclick="openImageModal(this.src, '<?php echo htmlspecialchars($jenis['jenis']); ?>')">
                            </div>
                            <?php endif; ?>
                            
                            <!-- Form verifikasi untuk berkas yang ada -->
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Status Berkas:</label>
                                    <select name="berkas_status[<?php echo $berkasData['id']; ?>]" 
                                            class="status-select" <?php echo !$berkasAda ? 'disabled' : ''; ?>>
                                        <option value="belum_diperiksa" <?php echo $berkasData['status'] == 'belum_diperiksa' ? 'selected' : ''; ?>>
                                            Belum Diperiksa
                                        </option>
                                        <option value="valid" <?php echo $berkasData['status'] == 'valid' ? 'selected' : ''; ?>>
                                            Valid
                                        </option>
                                        <option value="tidak_valid" <?php echo $berkasData['status'] == 'tidak_valid' ? 'selected' : ''; ?>>
                                            Tidak Valid
                                        </option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>Catatan Berkas:</label>
                                    <input type="text" 
                                           name="berkas_catatan[<?php echo $berkasData['id']; ?>]" 
                                           value="<?php echo htmlspecialchars($berkasData['catatan'] ?? ''); ?>"
                                           placeholder="Catatan untuk berkas ini"
                                           <?php echo !$berkasAda ? 'disabled' : ''; ?>>
                                </div>
                            </div>
                            
                            <?php else: ?>
                            <div class="alert alert-warning" style="margin: 1rem 0;">
                                <i class="fas fa-exclamation-triangle"></i>
                                <?php echo $jenis['required'] ? 'Berkas WAJIB belum diupload' : 'Berkas OPSIONAL belum diupload'; ?>
                            </div>
                            
                            <!-- Form untuk berkas yang tidak ada -->
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Status Berkas:</label>
                                    <select disabled>
                                        <option>Berkas Belum Diupload</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>Catatan Berkas:</label>
                                    <input type="text" 
                                           placeholder="Berkas belum diupload" 
                                           disabled>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <h2><i class="fas fa-clipboard-check"></i> Status Penerimaan</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="status">Status Penerimaan *</label>
                        <select id="status" name="status" required>
                            <option value="pending" <?php echo $calonSiswa['status_terima'] == 'pending' ? 'selected' : ''; ?>>
                                Pending
                            </option>
                            <option value="diterima" <?php echo $calonSiswa['status_terima'] == 'diterima' ? 'selected' : ''; ?>>
                                Diterima
                            </option>
                            <option value="tidak_diterima" <?php echo $calonSiswa['status_terima'] == 'tidak_diterima' ? 'selected' : ''; ?>>
                                Tidak Diterima
                            </option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="status_berkas">Status Berkas Keseluruhan</label>
                        <select id="status_berkas" name="status_berkas" disabled>
                            <option value="">Otomatis berdasarkan verifikasi</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="catatan_panitia">Catatan untuk Pendaftar</label>
                    <textarea id="catatan_panitia" name="catatan_panitia" rows="3" 
                              placeholder="Berikan catatan jika ada data atau berkas yang tidak valid">
                        <?php echo htmlspecialchars($calonSiswa['catatan_panitia'] ?? ''); ?>
                    </textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Simpan Verifikasi
                    </button>
                    <a href="data_calon.php" class="btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                    
                    <!-- Tombol untuk tindakan cepat -->
                    <div style="margin-top: 1rem; display: flex; gap: 0.5rem;">
                        <button type="button" class="btn-success" onclick="setAllValid()">
                            <i class="fas fa-check"></i> Set Semua Valid
                        </button>
                        <button type="button" class="btn-danger" onclick="setAllInvalid()">
                            <i class="fas fa-times"></i> Set Semua Tidak Valid
                        </button>
                        <button type="button" class="btn-warning" onclick="resetAll()">
                            <i class="fas fa-redo"></i> Reset Semua
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal untuk gambar besar -->
    <div id="imageModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 90%; max-height: 90%; background: transparent; box-shadow: none;">
            <div class="modal-header" style="background: transparent; justify-content: flex-end;">
                <button type="button" class="close-modal" style="color: white; font-size: 2rem;">&times;</button>
            </div>
            <div class="modal-body" style="text-align: center; padding: 0;">
                <img id="modalImage" src="" alt="" style="max-width: 100%; max-height: 80vh;">
                <p id="modalCaption" style="color: white; margin-top: 1rem;"></p>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
    <script>
        // Modal untuk gambar
        const imageModal = document.getElementById('imageModal');
        const modalImage = document.getElementById('modalImage');
        const modalCaption = document.getElementById('modalCaption');
        const closeModalBtn = document.querySelector('.close-modal');
        
        function openImageModal(src, caption) {
            modalImage.src = src;
            modalCaption.textContent = caption;
            imageModal.style.display = 'block';
        }
        
        closeModalBtn.onclick = function() {
            imageModal.style.display = 'none';
        }
        
        window.onclick = function(event) {
            if (event.target == imageModal) {
                imageModal.style.display = 'none';
            }
        }
        
        // Update warna status select
        document.querySelectorAll('.status-select').forEach(select => {
            updateSelectColor(select);
            select.addEventListener('change', function() {
                updateSelectColor(this);
            });
        });
        
        function updateSelectColor(select) {
            const colors = {
                'belum_diperiksa': '#ffc107',
                'valid': '#28a745',
                'tidak_valid': '#dc3545'
            };
            select.style.color = colors[select.value] || '#333';
        }
        
        // Fungsi untuk tindakan cepat
        function setAllValid() {
            if (confirm('Set semua berkas menjadi VALID?')) {
                document.querySelectorAll('.status-select').forEach(select => {
                    if (!select.disabled) {
                        select.value = 'valid';
                        updateSelectColor(select);
                    }
                });
            }
        }
        
        function setAllInvalid() {
            if (confirm('Set semua berkas menjadi TIDAK VALID?')) {
                document.querySelectorAll('.status-select').forEach(select => {
                    if (!select.disabled) {
                        select.value = 'tidak_valid';
                        updateSelectColor(select);
                    }
                });
            }
        }
        
        function resetAll() {
            if (confirm('Reset semua status berkas?')) {
                document.querySelectorAll('.status-select').forEach(select => {
                    if (!select.disabled) {
                        select.value = 'belum_diperiksa';
                        updateSelectColor(select);
                    }
                });
                
                // Reset catatan
                document.querySelectorAll('input[name^="berkas_catatan"]').forEach(input => {
                    if (!input.disabled) {
                        input.value = '';
                    }
                });
            }
        }
        
        // Auto-update status berkas keseluruhan
        function updateOverallStatus() {
            const selects = document.querySelectorAll('.status-select:not([disabled])');
            let semuaValid = true;
            let adaTidakValid = false;
            
            selects.forEach(select => {
                if (select.value === 'tidak_valid') {
                    adaTidakValid = true;
                    semuaValid = false;
                } else if (select.value !== 'valid') {
                    semuaValid = false;
                }
            });
            
            const overallSelect = document.getElementById('status_berkas');
            if (semuaValid) {
                overallSelect.value = 'semua_valid';
                overallSelect.style.color = '#28a745';
            } else if (adaTidakValid) {
                overallSelect.value = 'ada_tidak_valid';
                overallSelect.style.color = '#dc3545';
            } else {
                overallSelect.value = 'sebagian_belum';
                overallSelect.style.color = '#ffc107';
            }
        }
        
        // Panggil updateOverallStatus saat select berubah
        document.querySelectorAll('.status-select').forEach(select => {
            select.addEventListener('change', updateOverallStatus);
        });
        
        // Inisialisasi
        updateOverallStatus();
    </script>
    
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.9);
        }
        
        .modal-content {
            position: relative;
            margin: auto;
            padding: 0;
            width: auto;
            animation: zoom 0.3s;
        }
        
        @keyframes zoom {
            from {transform: scale(0.8);}
            to {transform: scale(1);}
        }
        
        .close-modal {
            position: absolute;
            top: 10px;
            right: 25px;
            color: white;
            font-size: 35px;
            font-weight: bold;
            cursor: pointer;
            background: none;
            border: none;
            z-index: 10001;
        }
        
        .close-modal:hover {
            color: #ccc;
        }
    </style>
</body>
</html>