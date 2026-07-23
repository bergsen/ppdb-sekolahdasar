<?php
require_once '../config/database.php';
redirectIfNotAuthorized(['admin']);

$db = new Database();
$conn = $db->getConnection();

$success = '';
$error   = '';

/* ================= FILTER ================= */
$filterTahun  = $_GET['tahun'] ?? '';
$filterStatus = $_GET['status'] ?? '';

/* ================= AMBIL TAHUN AJARAN ================= */
$tahunAjaranList = [];
$qTahun = $conn->query("SELECT * FROM tahun_ajaran ORDER BY tahun DESC");
while ($r = $qTahun->fetch_assoc()) $tahunAjaranList[] = $r;

if (!$filterTahun) {
    foreach ($tahunAjaranList as $ta) {
        if ($ta['status'] === 'aktif') {
            $filterTahun = $ta['tahun'];
            break;
        }
    }
}

/* ================= DATA PENDAFTAR ================= */
$where = "WHERE u.role='pendaftar'";
$join  = "";
$params = [];
$types  = "";

if ($filterTahun) {
    $safeFilterTahun = $conn->real_escape_string($filterTahun);
    $join = "
        LEFT JOIN calon_siswa cs ON cs.user_id=u.id
        LEFT JOIN tahun_ajaran ta ON cs.tahun_ajaran_id=ta.id
    ";
    $where .= " AND (ta.tahun='" . $safeFilterTahun . "' OR cs.id IS NULL)";
}

if ($filterStatus === 'memiliki_calon') {
    $where .= " AND cs.id IS NOT NULL";
} elseif ($filterStatus === 'tidak_memiliki_calon') {
    $where .= " AND cs.id IS NULL";
}

$sql = "
SELECT DISTINCT u.*,
(SELECT COUNT(*) FROM calon_siswa c WHERE c.user_id=u.id) total_calon,
(SELECT GROUP_CONCAT(DISTINCT ta2.tahun SEPARATOR ', ')
 FROM calon_siswa c2
 JOIN tahun_ajaran ta2 ON c2.tahun_ajaran_id=ta2.id
 WHERE c2.user_id=u.id) tahun_daftar
FROM users u
$join
$where
ORDER BY u.created_at DESC
";
$result = $conn->query($sql);

/* ================= RESET PASSWORD ================= */
if (isset($_GET['reset_password'])) {
    $id = (int)$_GET['reset_password'];
    $newPass = substr(md5(uniqid(mt_rand(), true)), 0, 8);
    $hash = hashPassword($newPass);

    // Ambil data user untuk mendapatkan no_hp dan nama
    $stmtUser = $conn->prepare("SELECT nama, no_hp, username FROM users WHERE id = ? AND role = 'pendaftar'");
    $stmtUser->bind_param("i", $id);
    $stmtUser->execute();
    $userData = $stmtUser->get_result()->fetch_assoc();
    $stmtUser->close();

    if ($userData) {
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hash, $id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            // Buat link WhatsApp ke nomor pendaftar
            $noHp = trim($userData['no_hp'] ?? '');
            $namaUser = htmlspecialchars($userData['nama']);
            $usernameUser = htmlspecialchars($userData['username']);

            $waMessage = "Halo " . $userData['nama'] . ",\n\n"
                . "Password akun PPDB SDN Pasir Panjang 02 Anda telah direset oleh Admin.\n\n"
                . "Username: " . $userData['username'] . "\n"
                . "Password baru: " . $newPass . "\n\n"
                . "Silakan login di website PPDB dan segera ganti password Anda.\n"
                . "Terima kasih.";

            $waLink = '';
            if (!empty($noHp)) {
                // Format nomor HP ke format internasional
                $cleanHp = preg_replace('/[^0-9]/', '', $noHp);
                if (substr($cleanHp, 0, 1) === '0') {
                    $cleanHp = '62' . substr($cleanHp, 1);
                }
                $waLink = "https://wa.me/" . $cleanHp . "?text=" . urlencode($waMessage);
            }

            $success = "Password berhasil direset untuk <strong>{$namaUser}</strong>.<br>"
                . "Password baru: <strong class='no-auto-hide'>" . htmlspecialchars($newPass) . "</strong>";

            if (!empty($waLink)) {
                $success .= "<br><br><a href='{$waLink}' target='_blank' "
                    . "style='display:inline-flex;align-items:center;gap:8px;background:#25d366;color:white;"
                    . "padding:10px 20px;border-radius:25px;text-decoration:none;font-weight:600;margin-top:10px;'>"
                    . "<i class='fab fa-whatsapp'></i> Kirim Password ke WhatsApp {$namaUser}</a>";
            } else {
                $success .= "<br><small style='color:#856404;'>No. HP pendaftar tidak tersedia. Harap informasikan password secara manual.</small>";
            }
        } else {
            $error = "Gagal reset password.";
        }
        $stmt->close();
    } else {
        $error = "Data pendaftar tidak ditemukan.";
    }
}

/* =====================================================
   HAPUS PENDAFTAR PER AKUN (FIX)
===================================================== */
if (isset($_GET['hapus_user'])) {
    $userId = (int)$_GET['hapus_user'];

    try {
        $conn->begin_transaction();

        $qCalon = $conn->query("SELECT id FROM calon_siswa WHERE user_id=$userId");
        while ($c = $qCalon->fetch_assoc()) {
            $cid = $c['id'];

            $qFile = $conn->query("SELECT file_path,nama_file FROM berkas WHERE calon_siswa_id=$cid");
            while ($f = $qFile->fetch_assoc()) {
                $file = "../assets/uploads/{$f['file_path']}/{$f['nama_file']}";
                if (file_exists($file)) unlink($file);
            }

            $conn->query("DELETE FROM berkas WHERE calon_siswa_id=$cid");
        }

        $conn->query("DELETE FROM calon_siswa WHERE user_id=$userId");
        $conn->query("DELETE FROM users WHERE id=$userId AND role='pendaftar'");

        $conn->commit();
        $success = "Akun pendaftar dan seluruh data berhasil dihapus.";

    } catch (Exception $e) {
        $conn->rollback();
        $error = "Gagal menghapus akun pendaftar.";
    }
}

/* =====================================================
   HAPUS PENDAFTAR PER TAHUN AJARAN (ANGKATAN)
===================================================== */
if (isset($_GET['hapus_tahun'])) {
    $tahun = $conn->real_escape_string($_GET['hapus_tahun']);

    try {
        $conn->begin_transaction();

        $sql = "
            SELECT cs.id calon_id, cs.user_id
            FROM calon_siswa cs
            JOIN tahun_ajaran ta ON cs.tahun_ajaran_id=ta.id
            WHERE ta.tahun='$tahun'
        ";
        $res = $conn->query($sql);

        $userIds = [];

        while ($r = $res->fetch_assoc()) {
            $cid = $r['calon_id'];
            $userIds[] = $r['user_id'];

            $qFile = $conn->query("SELECT file_path,nama_file FROM berkas WHERE calon_siswa_id=$cid");
            while ($f = $qFile->fetch_assoc()) {
                $file = "../assets/uploads/{$f['file_path']}/{$f['nama_file']}";
                if (file_exists($file)) unlink($file);
            }

            $conn->query("DELETE FROM berkas WHERE calon_siswa_id=$cid");
        }

        $conn->query("
            DELETE cs FROM calon_siswa cs
            JOIN tahun_ajaran ta ON cs.tahun_ajaran_id=ta.id
            WHERE ta.tahun='$tahun'
        ");

        if ($userIds) {
            $ids = implode(',', array_unique($userIds));
            $conn->query("DELETE FROM users WHERE id IN ($ids) AND role='pendaftar'");
        }

        $conn->commit();
        $success = "Semua data & akun pendaftar tahun $tahun berhasil dihapus.";

    } catch (Exception $e) {
        $conn->rollback();
        $error = "Gagal menghapus data tahun ajaran.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pendaftar - PPDB SDN Pasirpanjang02</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .filter-section {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }
        
        .tahun-actions {
            margin-top: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 5px;
            border-left: 4px solid #dc3545;
        }
        
        .tahun-actions h4 {
            margin: 0 0 0.5rem 0;
            color: #721c24;
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
            margin: 10% auto;
            padding: 2rem;
            width: 90%;
            max-width: 500px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        
        .modal-header {
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #dee2e6;
        }
        
        .modal-header h3 {
            margin: 0;
            color: #333;
        }
        
        .modal-body {
            margin-bottom: 1.5rem;
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
        }
        /* ================= DESKTOP / LAPTOP ================= */
.user-card {
    padding: 1rem;
    border-radius: 12px;
    margin-bottom: 0.8rem;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
    gap: 0.75rem;
}

.user-name {
    font-size: 1rem;
}

.detail-item {
    font-size: 0.85rem;
}

.user-actions a,
.user-actions button {
    padding: 0.45rem 0.75rem;
    font-size: 0.8rem;
}

/* ================= MOBILE ================= */
@media (max-width: 768px) {

    .user-card {
        padding: 0.75rem;
        margin-bottom: 0.6rem;
        border-radius: 10px;
        gap: 0.5rem;
    }

    .user-name {
        font-size: 0.9rem;
    }

    .detail-item {
        font-size: 0.75rem;
    }

    .user-actions {
        gap: 0.35rem;
    }

    /* Tombol aksi */
.user-actions {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    align-items: stretch;
}

.user-actions a,
.user-actions button {
    width: 100%;
}

}  
        .badge-role {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
            background: #e2e3e5;
            color: #383d41;
        }
        
        .badge-calon {
            padding: 0.25rem 0.5rem;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .calon-ada { background: #d4edda; color: #155724; }
        .calon-tidak { background: #f8d7da; color: #721c24; }
        
        .tahun-badge {
            padding: 0.2rem 0.5rem;
            border-radius: 10px;
            font-size: 0.75rem;
            background: #d1ecf1;
            color: #0c5460;
            margin: 0.1rem;
            display: inline-block;
        }
        
        .btn-sm {
            padding: 0.5rem 0.75rem;
            font-size: 0.85rem;
        }
        
        .btn-delete-tahun {
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 0.5rem 1rem;
            cursor: pointer;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-delete-tahun:hover {
            background: #c82333;
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
        
        @media (max-width: 768px) {
            .user-card {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .user-actions {
                width: 100%;
                justify-content: flex-start;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="dashboard-header">
            <div class="container">
                <h1><i class="fas fa-user-friends"></i> Kelola Pendaftar</h1>
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
            
            <!-- Filter Section -->
            <div class="filter-section">
                <h3><i class="fas fa-filter"></i> Filter Data Pendaftar</h3>
                <form method="GET" class="filter-row">
                    <div class="form-group">
                        <label for="tahun">Tahun Ajaran</label>
                        <select id="tahun" name="tahun" class="form-control">
                            <option value="">Semua Tahun</option>
                            <?php foreach ($tahunAjaranList as $ta): ?>
                            <option value="<?php echo htmlspecialchars($ta['tahun']); ?>" 
                                <?php echo $filterTahun == $ta['tahun'] ? 'selected' : ''; ?>
                                <?php echo $ta['status'] == 'aktif' ? 'style="font-weight: bold;"' : ''; ?>>
                                <?php echo htmlspecialchars($ta['tahun']); ?>
                                <?php echo $ta['status'] == 'aktif' ? ' (Aktif)' : ''; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status Pendaftaran</label>
                        <select id="status" name="status" class="form-control">
                            <option value="">Semua Status</option>
                            <option value="memiliki_calon" <?php echo $filterStatus == 'memiliki_calon' ? 'selected' : ''; ?>>Memiliki Calon Siswa</option>
                            <option value="tidak_memiliki_calon" <?php echo $filterStatus == 'tidak_memiliki_calon' ? 'selected' : ''; ?>>Tidak Memiliki Calon</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <a href="kelola_pendaftar.php" class="btn-secondary">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </div>
                </form>
                
                <!-- Opsi Hapus Data per Tahun -->
                <?php if($filterTahun): ?>
                <div class="tahun-actions">
                    <h4><i class="fas fa-trash"></i> Hapus Data Tahun <?php echo htmlspecialchars($filterTahun); ?></h4>
                    <p>Hapus semua data pendaftaran dan pendaftar pada tahun ini:</p>
                    <button type="button" onclick="hapusTahunAjaran('<?php echo htmlspecialchars($filterTahun); ?>')" 
                            class="btn-delete-tahun">
                        <i class="fas fa-trash"></i> Hapus Semua Data Tahun <?php echo htmlspecialchars($filterTahun); ?>
                    </button>
                    <small style="display: block; margin-top: 0.5rem; color: #666;">
                        <i class="fas fa-exclamation-triangle"></i> 
                        Peringatan: Aksi ini akan menghapus semua data pendaftaran dan berkas pada tahun <?php echo htmlspecialchars($filterTahun); ?>.
                        Akun pendaftar yang tidak memiliki data lagi juga akan dihapus.
                    </small>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Daftar Pendaftar -->
            <div class="section-header">
                <h2><i class="fas fa-users"></i> Daftar Pendaftar</h2>
                <div class="total-info">
                    Total: <strong><?php echo $result->num_rows; ?></strong> pendaftar/Wali
                    <?php if($filterTahun): ?> | Tahun: <strong><?php echo htmlspecialchars($filterTahun); ?></strong><?php endif; ?>
                </div>
            </div>
            
            <?php if($result->num_rows > 0): ?>
                <?php 
                $no = 1;
                $result->data_seek(0);
                while($row = $result->fetch_assoc()): 
                    $tahunDaftar = $row['tahun_daftar'] ? explode(', ', $row['tahun_daftar']) : [];
                ?>
                <div class="user-card">
                    <div class="user-info">
                        <div class="user-name">
                            <?php echo htmlspecialchars($row['nama']); ?>
                            <span class="badge-role">Pendaftar/Wali</span>
                            <?php if($row['total_calon'] > 0): ?>
                            <span class="badge-calon calon-ada">
                                <?php echo $row['total_calon']; ?> calon siswa
                            </span>
                            <?php else: ?>
                            <span class="badge-calon calon-tidak">
                                Tidak ada calon
                            </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="user-details">
                            <div class="detail-item">
                                <i class="fas fa-user-tag"></i>
                                <span><?php echo htmlspecialchars($row['username']); ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <i class="fas fa-envelope"></i>
                                <span><?php echo htmlspecialchars($row['email']); ?></span>
                            </div>
                            
                            <?php if($row['no_hp']): ?>
                            <div class="detail-item">
                                <i class="fas fa-phone"></i>
                                <span><?php echo htmlspecialchars($row['no_hp']); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <div class="detail-item">
                                <i class="far fa-calendar"></i>
                                <span>Bergabung: <?php echo date('d/m/Y', strtotime($row['created_at'])); ?></span>
                            </div>
                            
                            <?php if(!empty($tahunDaftar)): ?>
                            <div class="detail-item">
                                <i class="fas fa-calendar-check"></i>
                                <span>Tahun Daftar: 
                                    <?php foreach($tahunDaftar as $tahun): ?>
                                        <span class="tahun-badge"><?php echo $tahun; ?></span>
                                    <?php endforeach; ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="user-actions">
                        <?php if($row['total_calon'] > 0): ?>
                        <a href="laporan_pendaftar.php?user_id=<?php echo $row['id']; ?>&tahun=<?php echo urlencode($filterTahun); ?>" 
                           class="btn-info btn-sm" title="Lihat Data Pendaftaran">
                            <i class="fas fa-eye"></i> Lihat
                        </a>
                        <?php endif; ?>
                        
                        <a href="kelola_pendaftar.php?reset_password=<?php echo $row['id']; ?>&tahun=<?php echo urlencode($filterTahun); ?>" 
                           class="btn-warning btn-sm"
                           onclick="return confirm('Reset password untuk <?php echo htmlspecialchars(addslashes($row['nama'])); ?>?')"
                           title="Reset Password">
                            <i class="fas fa-key"></i>
                        </a>
        

                        <!-- Opsi Hapus: Semua atau per Tahun -->
                        <button type="button"
    onclick="showDeleteOptions(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['nama'])); ?>')"
    class="btn-danger btn-sm"
    title="Hapus Akun Pendaftar">
    <i class="fas fa-trash"></i>
</button>

                    </div>
                </div>
                <?php 
                $no++;
                endwhile; 
                ?>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-users fa-3x"></i>
                <h3>Tidak ada data pendaftar</h3>
                <p><?php echo $filterTahun ? "Tidak ada pendaftar untuk tahun $filterTahun" : "Belum ada pendaftar terdaftar"; ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modal untuk opsi hapus -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Hapus Data Pendaftar</h3>
                <button type="button" class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <p id="deleteMessage">Pilih opsi penghapusan:</p>
                
                <div id="deleteOptions">
                    <!-- Opsi akan diisi dengan JavaScript -->
                </div>
                
                <div id="deleteWarning" class="alert alert-warning" style="margin-top: 1rem; display: none;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Peringatan:</strong> 
                    <span id="warningText"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary close-modal">Batal</button>
            </div>
        </div>
    </div>
    
    <!-- Modal untuk hapus tahun ajaran -->
    <div id="deleteTahunModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Hapus Data Tahun Ajaran</h3>
                <button type="button" class="close-tahun-modal">&times;</button>
            </div>
            <div class="modal-body">
                <p id="tahunDeleteMessage"></p>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>PERINGATAN TINGGI:</strong> 
                    <ul style="margin: 0.5rem 0 0 1.5rem;">
                        <li>Semua data pendaftaran pada tahun ini akan dihapus</li>
                        <li>Semua file berkas akan dihapus dari server</li>
                        <li>Akun pendaftar yang tidak memiliki data lagi akan dihapus</li>
                        <li>Aksi ini TIDAK DAPAT DIBATALKAN</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary close-tahun-modal">Batal</button>
                <a href="#" id="confirmDeleteTahun" class="btn-danger">
                    <i class="fas fa-trash"></i> Ya, Hapus Semua Data
                </a>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
<script>
const BASE_URL = "kelola_pendaftar.php";

/* ================= MODAL ================= */
const deleteModal = document.getElementById('deleteModal');
const deleteTahunModal = document.getElementById('deleteTahunModal');

const deleteMessage = document.getElementById('deleteMessage');
const deleteOptions = document.getElementById('deleteOptions');
const deleteWarning = document.getElementById('deleteWarning');

const closeBtns = document.querySelectorAll('.close-modal, .close-tahun-modal');

let currentUserId = null;
let currentUserName = '';

/* ================= HAPUS AKUN ================= */
function showDeleteOptions(userId, userName) {
    currentUserId = userId;
    currentUserName = userName;

    deleteMessage.innerHTML = `
        Anda akan menghapus <strong>AKUN ${userName}</strong> beserta:
        <ul>
            <li>Data pendaftaran</li>
            <li>Data calon siswa</li>
            <li>Seluruh berkas</li>
        </ul>
    `;

    deleteOptions.innerHTML = `
        <a href="${BASE_URL}?hapus_user=${userId}"
           class="btn-danger"
           onclick="return confirmDeleteUser()"
           style="display:block;text-align:center">
            <i class="fas fa-trash"></i> Ya, Hapus Akun
        </a>
    `;

    deleteWarning.style.display = 'block';
    deleteModal.style.display = 'block';
}

function confirmDeleteUser() {
    return confirm(
        `Yakin menghapus AKUN ${currentUserName}?\n\n` +
        `Semua data & berkas akan dihapus.\n` +
        `AKSI INI TIDAK DAPAT DIBATALKAN.`
    );
}

/* ================= HAPUS SEMUA DATA TAHUN ================= */
function hapusTahunAjaran(tahun) {
    document.getElementById('tahunDeleteMessage').innerHTML = `
        Anda akan menghapus <strong>SEMUA DATA</strong> pada tahun ajaran <strong>${tahun}</strong>.
    `;

    document.getElementById('confirmDeleteTahun').href =
        `${BASE_URL}?hapus_tahun=${encodeURIComponent(tahun)}`;

    deleteTahunModal.style.display = 'block';
}

/* ================= CLOSE MODAL ================= */
closeBtns.forEach(btn => {
    btn.onclick = () => {
        deleteModal.style.display = 'none';
        deleteTahunModal.style.display = 'none';
    };
});

window.onclick = function(e) {
    if (e.target === deleteModal) deleteModal.style.display = 'none';
    if (e.target === deleteTahunModal) deleteTahunModal.style.display = 'none';
};
</script>


</body>
</html>