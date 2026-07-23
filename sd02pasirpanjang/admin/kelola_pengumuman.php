<?php
require_once '../config/database.php';
redirectIfNotAuthorized(['admin']);

$db = new Database();
$conn = $db->getConnection();

$success = '';
$error   = '';

/* ================= PROSES TAMBAH / EDIT ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $judul          = $db->escapeString($_POST['judul'] ?? '');
    $isi            = $db->escapeString($_POST['isi'] ?? '');
    $tampil_index   = $db->escapeString($_POST['tampil_index'] ?? 'tidak');
    $allow_download = isset($_POST['allow_download']) ? 1 : 0;

    $uploadFile = null;

    // Upload file (jika ada)
    if (!empty($_FILES['file']['name']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadFile($_FILES['file'], 'pengumuman', 'PENGUMUMAN_' . time());

        if (isset($upload['success'])) {
            $uploadFile = $upload['file_name'];
        } else {
            $error = $upload['error'];
        }
    }

    if (!$error) {

        // ================= EDIT =================
        if (!empty($_POST['id'])) {
            $id = (int) $_POST['id'];

            // Ambil file lama
            $oldFile = null;
            $qOld = $conn->query("SELECT file FROM pengumuman WHERE id = $id");
            if ($qOld && $qOld->num_rows > 0) {
                $old = $qOld->fetch_assoc();
                $oldFile = $old['file'];
            }

            $sql = "UPDATE pengumuman SET 
                        judul = '$judul',
                        isi = '$isi',
                        tampil_index = '$tampil_index',
                        allow_download = $allow_download";

            if ($uploadFile) {
                $sql .= ", file = '$uploadFile'";
            }

            $sql .= " WHERE id = $id";

            if ($conn->query($sql)) {

                // Hapus file lama jika upload baru
                if ($uploadFile && $oldFile) {
                    $path = "../assets/uploads/pengumuman/" . $oldFile;
                    if (file_exists($path)) {
                        unlink($path);
                    }
                }

                $success = "Pengumuman berhasil diperbarui!";
            } else {
                $error = "Gagal update: " . $conn->error;
            }

        // ================= TAMBAH =================
        } else {
            $sql = "INSERT INTO pengumuman (judul, isi, file, tampil_index, allow_download)
                    VALUES (
                        '$judul',
                        '$isi',
                        " . ($uploadFile ? "'$uploadFile'" : "NULL") . ",
                        '$tampil_index',
                        $allow_download
                    )";

            if ($conn->query($sql)) {
                $success = "Pengumuman berhasil ditambahkan!";
            } else {
                $error = "Gagal menyimpan: " . $conn->error;
            }
        }
    }
}

/* ================= HAPUS PENGUMUMAN ================= */
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];

    // Ambil file
    $qFile = $conn->query("SELECT file FROM pengumuman WHERE id = $id");
    if ($qFile && $qFile->num_rows > 0) {
        $row = $qFile->fetch_assoc();
        if (!empty($row['file'])) {
            $filePath = "../assets/uploads/pengumuman/" . $row['file'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }

    // Hapus data
    if ($conn->query("DELETE FROM pengumuman WHERE id = $id")) {
        $success = "Pengumuman berhasil dihapus!";
    } else {
        $error = "Gagal menghapus: " . $conn->error;
    }
}

/* ================= DATA EDIT ================= */
$editData = null;
if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $res = $conn->query("SELECT * FROM pengumuman WHERE id = $id");
    if ($res && $res->num_rows > 0) {
        $editData = $res->fetch_assoc();
    }
}

/* ================= LIST PENGUMUMAN ================= */
$result = $conn->query("SELECT * FROM pengumuman ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengumuman - PPDB SDN Pasirpanjang02</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="dashboard-header">
            <div class="container">
                <h1><i class="fas fa-bullhorn"></i> Kelola Pengumuman</h1>
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
            
            <!-- Form Pengumuman -->
            <div class="form-section">
                <h2>
                    <i class="fas fa-<?php echo $editData ? 'edit' : 'plus'; ?>"></i>
                    <?php echo $editData ? 'Edit' : 'Tambah'; ?> Pengumuman
                </h2>
                <form method="POST" enctype="multipart/form-data">
                    <?php if($editData): ?>
                    <input type="hidden" name="id" value="<?php echo $editData['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="judul">Judul Pengumuman *</label>
                            <input type="text" id="judul" name="judul" 
                                   value="<?php echo $editData['judul'] ?? ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="isi">Isi Pengumuman *</label>
                        <textarea id="isi" name="isi" rows="5" required><?php echo $editData['isi'] ?? ''; ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="file-upload-label">
                              <i class="fas fa-cloud-upload-alt"></i>
                                <span>Klik untuk upload file</span>
                                   <input
                                  type="file"
                                  name="file"
                                  accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"
                                      >
                             </label>

                            </div>
                            <small class="form-hint">Format: JPG, PNG, PDF, DOC (maks 5MB)</small>
                            
                            <?php if($editData && $editData['file']): ?>
                            <div class="current-file">
                                File saat ini: 
                                <a href="../assets/uploads/pengumuman/<?php echo $editData['file']; ?>" 
                                   target="_blank">
                                    <?php echo $editData['file']; ?>
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="tampil_index">Tampil di Beranda</label>
                            <select id="tampil_index" name="tampil_index">
                                <option value="ya" <?php echo ($editData['tampil_index'] ?? '') == 'ya' ? 'selected' : ''; ?>>
                                    Ya
                                </option>
                                <option value="tidak" <?php echo ($editData['tampil_index'] ?? '') == 'tidak' ? 'selected' : ''; ?>>
                                    Tidak
                                </option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="allow_download" value="1"
                                    <?php echo ($editData['allow_download'] ?? 0) ? 'checked' : ''; ?>>
                                <span>Izinkan download file</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> 
                            <?php echo $editData ? 'Update' : 'Simpan'; ?>
                        </button>
                        
                        <?php if($editData): ?>
                        <a href="kelola_pengumuman.php" class="btn-secondary">
                            <i class="fas fa-times"></i> Batal Edit
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Daftar Pengumuman -->
            <div class="form-section">
                <div class="section-header">
                    <h2><i class="fas fa-list"></i> Daftar Pengumuman</h2>
                    <div class="section-info">
                        Total: <strong><?php echo $result->num_rows; ?></strong> pengumuman
                    </div>
                </div>
                
                <?php if($result->num_rows > 0): ?>
                <div class="pengumuman-grid">
                    <?php while($row = $result->fetch_assoc()): ?>
                    <div class="pengumuman-card">
                        <div class="pengumuman-header">
                            <h3><?php echo htmlspecialchars($row['judul']); ?></h3>
                            <div class="pengumuman-actions">
                                <a href="kelola_pengumuman.php?edit=<?php echo $row['id']; ?>" 
                                   class="btn-primary btn-sm">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="kelola_pengumuman.php?hapus=<?php echo $row['id']; ?>" 
                                   class="btn-danger btn-sm"
                                   onclick="return confirm('Yakin menghapus pengumuman ini?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                        
                        <div class="pengumuman-content">
                            <p><?php echo nl2br(htmlspecialchars(substr($row['isi'], 0, 200))); ?>...</p>
                            
                            <?php if($row['file']): ?>
                            <div class="pengumuman-file">
                                <i class="fas fa-paperclip"></i>
                                <span>File: <?php echo $row['file']; ?></span>
                                <?php if($row['allow_download']): ?>
                                <a href="../assets/uploads/pengumuman/<?php echo $row['file']; ?>" 
                                   class="btn-download-sm" download>
                                    <i class="fas fa-download"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="pengumuman-footer">
                            <span class="badge <?php echo $row['tampil_index'] == 'ya' ? 'badge-success' : 'badge-secondary'; ?>">
                                <?php echo $row['tampil_index'] == 'ya' ? 'Tampil di Beranda' : 'Tidak Tampil'; ?>
                            </span>
                            <span class="badge <?php echo $row['allow_download'] ? 'badge-success' : 'badge-secondary'; ?>">
                                <?php echo $row['allow_download'] ? 'Download Diizinkan' : 'Download Tidak Diizinkan'; ?>
                            </span>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-bullhorn fa-3x"></i>
                    <h3>Belum ada pengumuman</h3>
                    <p>Tambah pengumuman baru di form atas</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <style>
        .pengumuman-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }
        
        .pengumuman-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .pengumuman-header {
            background: #f8f9fa;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
        }
        
        .pengumuman-header h3 {
            margin: 0;
            font-size: 1.1rem;
            color: #333;
        }
        
        .pengumuman-content {
            padding: 1rem;
        }
        
        .pengumuman-file {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1rem;
            padding: 0.5rem;
            background: #f8f9fa;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        
        .pengumuman-footer {
            padding: 0.75rem 1rem;
            background: #f8f9fa;
            border-top: 1px solid #eee;
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .badge {
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-secondary {
            background: #e2e3e5;
            color: #383d41;
        }
        
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }
        
        .checkbox-label input[type="checkbox"] {
            width: auto;
        }
        
        .current-file {
            margin-top: 0.5rem;
            padding: 0.5rem;
            background: #f8f9fa;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        
        .btn-download-sm {
            padding: 0.25rem 0.5rem;
            background: #28a745;
            color: white;
            border-radius: 3px;
            text-decoration: none;
            font-size: 0.8rem;
        }
    </style>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>