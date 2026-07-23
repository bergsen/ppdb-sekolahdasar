<?php
require_once '../config/database.php';
redirectIfNotAuthorized(['admin']);

$db = new Database();
$conn = $db->getConnection();

$success = '';
$error = '';

/* ================= TAMBAH / EDIT INFORMASI ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul  = $db->escapeString($_POST['judul']);
    $konten = $db->escapeString($_POST['konten']);
    $jenis  = $db->escapeString($_POST['jenis']);

    $uploadFile = null;

    if (!empty($_FILES['file']['name']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadFile($_FILES['file'], 'informasi', 'INFORMASI_' . time());
        if (isset($upload['success'])) {
            $uploadFile = $upload['file_name'];
        } else {
            $error = $upload['error'];
        }
    }

    if (!$error) {
        /* ========== EDIT ========== */
        if (!empty($_POST['id'])) {
            $id = (int) $_POST['id'];

            $sql = "UPDATE informasi SET 
                        judul = '$judul',
                        konten = '$konten',
                        jenis = '$jenis'";

            if ($uploadFile) {
                // Ambil file lama
                $sqlOld = "SELECT file FROM informasi WHERE id = $id";
                $resultOld = $conn->query($sqlOld);
                $rowOld = $resultOld ? $resultOld->fetch_assoc() : null;

                if ($rowOld && !empty($rowOld['file'])) {
                    $oldPath = "../assets/uploads/informasi/" . $rowOld['file'];
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }

                $sql .= ", file = '$uploadFile'";
            }

            $sql .= " WHERE id = $id";
        }
        /* ========== TAMBAH ========== */
        else {
            $sql = "INSERT INTO informasi (judul, konten, file, jenis) VALUES (
                        '$judul',
                        '$konten',
                        " . ($uploadFile ? "'$uploadFile'" : "NULL") . ",
                        '$jenis'
                    )";
        }

        if ($conn->query($sql)) {
            $success = !empty($_POST['id'])
                ? "Informasi berhasil diperbarui!"
                : "Informasi berhasil ditambahkan!";
        } else {
            $error = "Gagal menyimpan: " . $conn->error;
        }
    }
}

/* ================= HAPUS INFORMASI ================= */
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];

    // Ambil file
    $sqlFile = "SELECT file FROM informasi WHERE id = $id";
    $resultFile = $conn->query($sqlFile);
    $row = $resultFile ? $resultFile->fetch_assoc() : null;

    if ($row && !empty($row['file'])) {
        $filePath = "../assets/uploads/informasi/" . $row['file'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    $sql = "DELETE FROM informasi WHERE id = $id";
    if ($conn->query($sql)) {
        $success = "Informasi berhasil dihapus!";
    } else {
        $error = "Gagal menghapus: " . $conn->error;
    }
}

/* ================= AMBIL DATA EDIT ================= */
$editData = null;
if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $result = $conn->query("SELECT * FROM informasi WHERE id = $id");
    if ($result && $result->num_rows > 0) {
        $editData = $result->fetch_assoc();
    }
}

/* ================= AMBIL SEMUA DATA ================= */
$sql = "SELECT * FROM informasi ORDER BY 
        CASE jenis 
            WHEN 'jadwal' THEN 1
            WHEN 'persyaratan' THEN 2
            WHEN 'lokasi' THEN 3
            WHEN 'alur' THEN 4
            WHEN 'profil' THEN 5
            ELSE 6
        END, id DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Informasi - PPDB SD</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .info-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .info-header {
            background: #f8f9fa;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
        }
        
        .info-header h3 {
            margin: 0;
            font-size: 1.1rem;
            color: #333;
        }
        
        .info-content {
            padding: 1rem;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .info-footer {
            padding: 0.75rem 1rem;
            background: #f8f9fa;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .jenis-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .jenis-jadwal { background: #d4edda; color: #155724; }
        .jenis-persyaratan { background: #d1ecf1; color: #0c5460; }
        .jenis-lokasi { background: #fff3cd; color: #856404; }
        .jenis-alur { background: #f8d7da; color: #721c24; }
        .jenis-profil { background: #e2e3e5; color: #383d41; }
        .jenis-informasi { background: #cce5ff; color: #004085; }
        
        .file-preview {
            margin-top: 0.5rem;
            padding: 0.5rem;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .file-preview img {
            max-width: 100%;
            height: auto;
            border-radius: 5px;
        }
        
        .form-row-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="dashboard-header">
            <div class="container">
                <h1><i class="fas fa-info-circle"></i> Kelola Informasi</h1>
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
            
            <!-- Form Informasi -->
            <div class="form-section">
                <h2>
                    <i class="fas fa-<?php echo $editData ? 'edit' : 'plus'; ?>"></i>
                    <?php echo $editData ? 'Edit' : 'Tambah'; ?> Informasi
                </h2>
                <form method="POST" enctype="multipart/form-data">
                    <?php if($editData): ?>
                    <input type="hidden" name="id" value="<?php echo $editData['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-row-3">
                        <div class="form-group">
                            <label for="judul">Judul Informasi *</label>
                            <input type="text" id="judul" name="judul" 
                                   value="<?php echo htmlspecialchars($editData['judul'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="jenis">Jenis Informasi *</label>
                            <select id="jenis" name="jenis" required>
                                <option value="">Pilih Jenis</option>
                                <option value="jadwal" <?php echo ($editData['jenis'] ?? '') == 'jadwal' ? 'selected' : ''; ?>>
                                    Jadwal
                                </option>
                                <option value="persyaratan" <?php echo ($editData['jenis'] ?? '') == 'persyaratan' ? 'selected' : ''; ?>>
                                    Persyaratan
                                </option>
                                <option value="lokasi" <?php echo ($editData['jenis'] ?? '') == 'lokasi' ? 'selected' : ''; ?>>
                                    Lokasi
                                </option>
                                <option value="alur" <?php echo ($editData['jenis'] ?? '') == 'alur' ? 'selected' : ''; ?>>
                                    Alur Pendaftaran
                                </option>
                                <option value="profil" <?php echo ($editData['jenis'] ?? '') == 'profil' ? 'selected' : ''; ?>>
                                    Profil Sekolah
                                </option>
                                <option value="informasi" <?php echo ($editData['jenis'] ?? '') == 'informasi' ? 'selected' : ''; ?>>
                                    Informasi Lainnya
                                </option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="file">File Lampiran (Opsional)</label>
                                <label class="file-upload-label">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <span>Upload File</span>
                                    <input type="file" id="file" name="file" 
                                           accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                                </label>
                            <small class="form-hint">Maks 5MB</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="konten">Isi Informasi *</label>
                        <textarea id="konten" name="konten" rows="8" required><?php echo htmlspecialchars($editData['konten'] ?? ''); ?></textarea>
                    </div>
                    
                    <?php if($editData && $editData['file']): ?>
                    <div class="current-file">
                        <strong>File saat ini:</strong>
                        <a href="../assets/uploads/informasi/<?php echo $editData['file']; ?>" 
                           target="_blank" class="btn-secondary btn-sm">
                            <i class="fas fa-download"></i> <?php echo $editData['file']; ?>
                        </a>
                        <?php 
                        $isImage = preg_match('/\.(jpg|jpeg|png|gif)$/i', $editData['file']);
                        if ($isImage): 
                        ?>
                        <div class="file-preview">
                            <img src="../assets/uploads/informasi/<?php echo $editData['file']; ?>" 
                                 alt="Preview">
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> 
                            <?php echo $editData ? 'Update' : 'Simpan'; ?>
                        </button>
                        
                        <?php if($editData): ?>
                        <a href="kelola_informasi.php" class="btn-secondary">
                            <i class="fas fa-times"></i> Batal Edit
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Daftar Informasi -->
            <div class="form-section">
                <div class="section-header">
                    <h2><i class="fas fa-list"></i> Daftar Informasi</h2>
                    <div class="section-info">
                        Total: <strong><?php echo $result->num_rows; ?></strong> informasi
                    </div>
                </div>
                
                <?php if($result->num_rows > 0): ?>
                <div class="info-grid">
                    <?php while($row = $result->fetch_assoc()): ?>
                    <div class="info-card">
                        <div class="info-header">
                            <h3><?php echo htmlspecialchars($row['judul']); ?></h3>
                            <div class="info-actions">
                                <a href="kelola_informasi.php?edit=<?php echo $row['id']; ?>" 
                                   class="btn-primary btn-sm">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="kelola_informasi.php?hapus=<?php echo $row['id']; ?>" 
                                   class="btn-danger btn-sm"
                                   onclick="return confirm('Yakin menghapus informasi ini?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                        
                        <div class="info-content">
                            <p><?php echo nl2br(htmlspecialchars(substr($row['konten'], 0, 300))); ?>
                            <?php if(strlen($row['konten']) > 300): ?>...<?php endif; ?></p>
                            
                            <?php if($row['file']): ?>
                            <div class="file-info">
                                <small>
                                    <i class="fas fa-paperclip"></i> 
                                    <a href="../assets/uploads/informasi/<?php echo $row['file']; ?>" 
                                       target="_blank">
                                        <?php echo basename($row['file']); ?>
                                    </a>
                                </small>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="info-footer">
                            <span class="jenis-badge jenis-<?php echo $row['jenis']; ?>">
                                <?php 
                                $jenisText = [
                                    'jadwal' => 'Jadwal',
                                    'persyaratan' => 'Persyaratan',
                                    'lokasi' => 'Lokasi',
                                    'alur' => 'Alur',
                                    'profil' => 'Profil',
                                    'informasi' => 'Informasi'
                                ];
                                echo $jenisText[$row['jenis']] ?? $row['jenis'];
                                ?>
                            </span>
                            <small><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></small>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-info-circle fa-3x"></i>
                    <h3>Belum ada informasi</h3>
                    <p>Tambah informasi baru di form atas</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
    <script>
        // Preview file sebelum upload
        document.getElementById('file').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Tampilkan preview untuk gambar
                    if (file.type.startsWith('image/')) {
                        let preview = document.querySelector('.current-file');
                        if (!preview) {
                            preview = document.createElement('div');
                            preview.className = 'current-file';
                            document.querySelector('form').insertBefore(preview, document.querySelector('.form-actions'));
                        }
                        
                        preview.innerHTML = `
                            <strong>Preview:</strong>
                            <div class="file-preview">
                                <img src="${e.target.result}" alt="Preview" style="max-width: 200px;">
                            </div>
                        `;
                    }
                }
                reader.readAsDataURL(file);
            }
        });
        
        // Auto expand textarea
        const textarea = document.getElementById('konten');
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    </script>
</body>
</html>