<?php
require_once '../config/database.php';
redirectIfNotAuthorized(['panitia']);

$db = new Database();
$conn = $db->getConnection();

$calonSiswaId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($calonSiswaId) {
    $sql = "SELECT cs.*, u.nama as nama_pendaftar, u.email, u.no_hp, ta.tahun,
            (SELECT GROUP_CONCAT(CONCAT(jenis_berkas, ':', status) SEPARATOR '|') 
             FROM berkas WHERE calon_siswa_id = cs.id) as berkas_info
            FROM calon_siswa cs 
            JOIN users u ON cs.user_id = u.id 
            JOIN tahun_ajaran ta ON cs.tahun_ajaran_id = ta.id 
            WHERE cs.id = $calonSiswaId";
    
    $result = $conn->query($sql);
    $data = $result->fetch_assoc();
    
    if ($data) {
        ?>
        <div class="detail-container">
            <h3>Detail Calon Siswa</h3>
            
            <div class="detail-section">
                <h4><i class="fas fa-user-graduate"></i> Data Siswa</h4>
                <div class="detail-row">
                    <div class="detail-label">Nama Lengkap:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($data['nama_siswa']); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">No. Pendaftaran:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($data['nomor_pendaftaran']); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Tempat/Tgl Lahir:</div>
                    <div class="detail-value">
                        <?php echo htmlspecialchars($data['tempat_lahir']); ?>, 
                        <?php echo date('d/m/Y', strtotime($data['tanggal_lahir'])); ?>
                    </div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Jenis Kelamin:</div>
                    <div class="detail-value"><?php echo $data['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan'; ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Agama:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($data['agama']); ?></div>
                </div>
            </div>
            
            <div class="detail-section">
                <h4><i class="fas fa-home"></i> Data Orang Tua</h4>
                <div class="detail-row">
                    <div class="detail-label">Nama Ayah:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($data['nama_ayah']); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Pekerjaan Ayah:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($data['pekerjaan_ayah']); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Nama Ibu:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($data['nama_ibu']); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Pekerjaan Ibu:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($data['pekerjaan_ibu']); ?></div>
                </div>
            </div>
            
            <div class="detail-section">
                <h4><i class="fas fa-info-circle"></i> Informasi Lain</h4>
                <div class="detail-row">
                    <div class="detail-label">Pendaftar:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($data['nama_pendaftar']); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Email:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($data['email']); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">No. HP:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($data['no_hp']); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Tahun Ajaran:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($data['tahun']); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Tanggal Daftar:</div>
                    <div class="detail-value"><?php echo date('d/m/Y H:i', strtotime($data['created_at'])); ?></div>
                </div>
            </div>
            
            <?php if($data['catatan_panitia']): ?>
            <div class="detail-section">
                <h4><i class="fas fa-sticky-note"></i> Catatan Panitia</h4>
                <div class="catatan-box">
                    <?php echo nl2br(htmlspecialchars($data['catatan_panitia'])); ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="detail-actions">
                <a href="verifikasi.php?id=<?php echo $data['id']; ?>" class="btn-primary">
                    <i class="fas fa-edit"></i> Verifikasi
                </a>
                <button type="button" class="btn-secondary close-modal">
                    <i class="fas fa-times"></i> Tutup
                </button>
            </div>
        </div>
        
        <style>
            .detail-container {
                max-width: 100%;
            }
            
            .detail-section {
                margin-bottom: 1.5rem;
                padding: 1rem;
                background: #f8f9fa;
                border-radius: 5px;
            }
            
            .detail-section h4 {
                color: #4a6fa5;
                margin-bottom: 1rem;
                border-bottom: 1px solid #dee2e6;
                padding-bottom: 0.5rem;
            }
            
            .detail-row {
                display: flex;
                margin-bottom: 0.5rem;
            }
            
            .detail-label {
                font-weight: 600;
                min-width: 150px;
                color: #495057;
            }
            
            .detail-value {
                flex: 1;
                color: #212529;
            }
            
            .catatan-box {
                background: white;
                padding: 1rem;
                border-radius: 5px;
                border-left: 4px solid #4a6fa5;
            }
            
            .detail-actions {
                margin-top: 1.5rem;
                display: flex;
                gap: 0.5rem;
                justify-content: flex-end;
            }
        </style>
        <?php
    }
}
?>