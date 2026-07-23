<?php
require_once '../config/database.php';
redirectIfNotAuthorized(['pendaftar']);

$db = new Database();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];

// Cek tahun ajaran aktif
$sqlTa = "SELECT * FROM tahun_ajaran WHERE status = 'aktif' LIMIT 1";
$resultTa = $conn->query($sqlTa);
$tahunAjaran = $resultTa->fetch_assoc();

if (!$tahunAjaran) {
    header("Location: dashboard.php");
    exit();
}

// Generate nomor pendaftaran
$nomorPendaftaran = 'REG/' . date('Ym') . '/' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->begin_transaction();
        
        // Data siswa
        $namaSiswa = $db->escapeString($_POST['nama_siswa']);
        $jenisKelamin = $db->escapeString($_POST['jenis_kelamin']);
        $tempatLahir = $db->escapeString($_POST['tempat_lahir']);
        $tanggalLahir = $db->escapeString($_POST['tanggal_lahir']);
        $agama = $db->escapeString($_POST['agama']);
        $kewarganegaraan = $db->escapeString($_POST['kewarganegaraan']);
        $anakKe = (int)$_POST['anak_ke'];
        $jumlahSaudara = (int)$_POST['jumlah_saudara'];
        $golonganDarah = $db->escapeString($_POST['golongan_darah']);
        $riwayatPenyakit = $db->escapeString($_POST['riwayat_penyakit']);
        $alamat = $db->escapeString($_POST['alamat']);
        
        // Data ayah
        $namaAyah = $db->escapeString($_POST['nama_ayah']);
        $pekerjaanAyah = $db->escapeString($_POST['pekerjaan_ayah']);
        $penghasilanAyah = $db->escapeString($_POST['penghasilan_ayah']);
        $hpAyah = $db->escapeString($_POST['hp_ayah']);
        
        // Data ibu
        $namaIbu = $db->escapeString($_POST['nama_ibu']);
        $pekerjaanIbu = $db->escapeString($_POST['pekerjaan_ibu']);
        $penghasilanIbu = $db->escapeString($_POST['penghasilan_ibu']);
        $hpIbu = $db->escapeString($_POST['hp_ibu']);
        
        // Data wali (opsional)
        $namaWali = $db->escapeString($_POST['nama_wali']);
        $hubunganWali = $db->escapeString($_POST['hubungan_wali']);
        $hpWali = $db->escapeString($_POST['hp_wali']);
        
        // Data sekolah asal
        $namaTkAsal = $db->escapeString($_POST['nama_tk_asal']);
        
        // Data pindahan (opsional)
        $pindahanDari = $db->escapeString($_POST['pindahan_dari']);
        $kelasPindahan = $db->escapeString($_POST['kelas_pindahan']);
        
        // Insert calon siswa
        $sql = "INSERT INTO calon_siswa (
            user_id, tahun_ajaran_id, nomor_pendaftaran,
            nama_siswa, jenis_kelamin, tempat_lahir, tanggal_lahir, agama,
            kewarganegaraan, anak_ke, jumlah_saudara, golongan_darah,
            riwayat_penyakit, alamat,
            nama_ayah, pekerjaan_ayah, penghasilan_ayah, hp_ayah,
            nama_ibu, pekerjaan_ibu, penghasilan_ibu, hp_ibu,
            nama_wali, hubungan_wali, hp_wali,
            nama_tk_asal, pindahan_dari, kelas_pindahan
        ) VALUES (
            $userId, {$tahunAjaran['id']}, '$nomorPendaftaran',
            '$namaSiswa', '$jenisKelamin', '$tempatLahir', '$tanggalLahir', '$agama',
            '$kewarganegaraan', $anakKe, $jumlahSaudara, '$golonganDarah',
            '$riwayatPenyakit', '$alamat',
            '$namaAyah', '$pekerjaanAyah', '$penghasilanAyah', '$hpAyah',
            '$namaIbu', '$pekerjaanIbu', '$penghasilanIbu', '$hpIbu',
            " . ($namaWali ? "'$namaWali'" : "NULL") . ",
            " . ($hubunganWali ? "'$hubunganWali'" : "NULL") . ",
            " . ($hpWali ? "'$hpWali'" : "NULL") . ",
            '$namaTkAsal',
            " . ($pindahanDari ? "'$pindahanDari'" : "NULL") . ",
            " . ($kelasPindahan ? "'$kelasPindahan'" : "NULL") . "
        )";
        
        if ($conn->query($sql)) {
            $calonSiswaId = $conn->insert_id;
            
            // Upload berkas - DIPERBAIKI
            $berkasTypes = [
                'foto' => ['jenis' => 'Foto Siswa', 'folder' => 'foto'],
                'akta' => ['jenis' => 'Akta Kelahiran', 'folder' => 'akta'],
                'kk' => ['jenis' => 'Kartu Keluarga', 'folder' => 'kk'],
                'ktp_ortu' => ['jenis' => 'KTP Orang Tua', 'folder' => 'ktp_ortu'],
                'ijazah_tk' => ['jenis' => 'Ijazah TK', 'folder' => 'ijazah_tk'],
                'surat_pindah' => ['jenis' => 'Surat Pindah', 'folder' => 'surat_pindah']
            ];
            
            $allSuccess = true;
            $uploadErrors = [];
            
            foreach ($berkasTypes as $key => $data) {
                if (isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK) {
                    // Generate nama file yang rapi
                    $cleanNamaSiswa = preg_replace('/[^a-zA-Z0-9]/', '_', $namaSiswa);
                    $prefix = $nomorPendaftaran . '_' . $key . '_' . substr($cleanNamaSiswa, 0, 20);
                    
                    $upload = uploadFile($_FILES[$key], $data['folder'], $prefix);
                    
                    if (isset($upload['success'])) {
                        $insertBerkas = "INSERT INTO berkas 
                            (calon_siswa_id, jenis_berkas, nama_file, file_path) 
                            VALUES ($calonSiswaId, '{$data['jenis']}', '{$upload['file_name']}', '{$data['folder']}')";
                        
                        if (!$conn->query($insertBerkas)) {
                            $uploadErrors[] = "Gagal menyimpan data berkas {$data['jenis']}: " . $conn->error;
                            $allSuccess = false;
                        }
                    } else {
                        $uploadErrors[] = "Gagal upload {$data['jenis']}: " . $upload['error'];
                        $allSuccess = false;
                    }
                } elseif (isset($_FILES[$key]) && $_FILES[$key]['error'] !== UPLOAD_ERR_NO_FILE) {
                    // Jika ada error selain "no file"
                    $uploadErrors[] = "Error upload {$data['jenis']}: " . getUploadError($_FILES[$key]['error']);
                    $allSuccess = false;
                }
            }
            
            if ($allSuccess) {
                $conn->commit();
                $success = "Pendaftaran berhasil! Nomor pendaftaran: <strong>$nomorPendaftaran</strong>";
                
                // Reset form
                echo '<script>
                    setTimeout(function() {
                        window.location.href = "dashboard.php";
                    }, 3000);
                </script>';
            } else {
                $conn->rollback();
                $error = "Beberapa berkas gagal diupload:<br>" . implode("<br>", $uploadErrors);
            }
        } else {
            $error = "Gagal menyimpan data: " . $conn->error;
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Terjadi kesalahan: " . $e->getMessage();
    }
}

// Helper untuk error upload
function getUploadError($errorCode) {
    $errors = [
        UPLOAD_ERR_INI_SIZE => 'Ukuran file melebihi batas server',
        UPLOAD_ERR_FORM_SIZE => 'Ukuran file melebihi batas form',
        UPLOAD_ERR_PARTIAL => 'File hanya terupload sebagian',
        UPLOAD_ERR_NO_FILE => 'Tidak ada file yang diupload',
        UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary tidak ditemukan',
        UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk',
        UPLOAD_ERR_EXTENSION => 'Upload dihentikan oleh ekstensi PHP'
    ];
    
    return $errors[$errorCode] ?? 'Error upload tidak diketahui';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulir Pendaftaran - PPDB SDN Pasir Panjang 02</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="dashboard-header">
            <div class="container">
                <h1><i class="fas fa-file-alt"></i> Formulir Pendaftaran</h1>
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
                <br>
                <small>Anda akan diarahkan ke dashboard dalam 3 detik...</small>
            </div>
            <?php endif; ?>
            
            <?php if($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <?php if($tahunAjaran): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>Tahun Ajaran Aktif:</strong> <?php echo htmlspecialchars($tahunAjaran['tahun']); ?>
                | <strong>Periode:</strong> <?php echo date('d/m/Y', strtotime($tahunAjaran['tanggal_buka'])); ?> - <?php echo date('d/m/Y', strtotime($tahunAjaran['tanggal_tutup'])); ?>
                | <strong>Kuota:</strong> <?php echo $tahunAjaran['kuota']; ?> siswa
            </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" class="form-section">

                <!-- ================= DATA SISWA ================= -->
                <h2><i class="fas fa-user-graduate"></i> Data Calon Siswa</h2>

                <div class="form-row">
                    <div class="form-group">
                        <label>Nama Lengkap *</label>
                        <input type="text" name="nama_siswa" required>
                    </div>

                    <div class="form-group">
                        <label>Jenis Kelamin *</label>
                        <select name="jenis_kelamin" required>
                            <option value="">-- Pilih --</option>
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Tempat Lahir *</label>
                        <input type="text" name="tempat_lahir" required>
                    </div>

                    <div class="form-group">
                        <label>Tanggal Lahir *</label>
                        <input type="date" name="tanggal_lahir" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Agama *</label>
                        <select name="agama" required>
                            <option value="">-- Pilih --</option>
                            <option>Islam</option>
                            <option>Kristen Protestan</option>
                            <option>Katolik</option>
                            <option>Hindu</option>
                            <option>Buddha</option>
                            <option>Khonghucu</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Kewarganegaraan *</label>
                        <select name="kewarganegaraan" required>
                            <option value="WNI">WNI</option>
                            <option value="WNA">WNA</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Anak ke *</label>
                        <input type="number" name="anak_ke" min="1" required>
                    </div>

                    <div class="form-group">
                        <label>Jumlah Saudara *</label>
                        <input type="number" name="jumlah_saudara" min="0" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Golongan Darah</label>
                        <select name="golongan_darah">
                            <option value="">-- Pilih --</option>
                            <option>A</option>
                            <option>B</option>
                            <option>AB</option>
                            <option>O</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Riwayat Penyakit</label>
                        <input type="text" name="riwayat_penyakit">
                    </div>
                </div>

                <div class="form-group">
                    <label>Alamat Lengkap *</label>
                    <textarea name="alamat" rows="3" required></textarea>
                </div>

                <!-- ================= DATA AYAH ================= -->
                <h2><i class="fas fa-male"></i> Data Ayah</h2>

                <div class="form-row">
                    <div class="form-group">
                        <label>Nama Ayah *</label>
                        <input type="text" name="nama_ayah" required>
                    </div>

                    <div class="form-group">
                        <label>Pekerjaan *</label>
                        <input type="text" name="pekerjaan_ayah" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Penghasilan *</label>
                        <input type="text" name="penghasilan_ayah" placeholder="Contoh: 3.000.000" required>
                    </div>

                    <div class="form-group">
                        <label>No HP *</label>
                        <input type="text" name="hp_ayah" pattern="[0-9]{10,13}" placeholder="081234567890" required>
                    </div>
                </div>

                <!-- ================= DATA IBU ================= -->
                <h2><i class="fas fa-female"></i> Data Ibu</h2>

                <div class="form-row">
                    <div class="form-group">
                        <label>Nama Ibu *</label>
                        <input type="text" name="nama_ibu" required>
                    </div>

                    <div class="form-group">
                        <label>Pekerjaan *</label>
                        <input type="text" name="pekerjaan_ibu" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Penghasilan *</label>
                        <input type="text" name="penghasilan_ibu" placeholder="Contoh: 2.500.000" required>
                    </div>

                    <div class="form-group">
                        <label>No HP *</label>
                        <input type="text" name="hp_ibu" pattern="[0-9]{10,13}" placeholder="081234567891" required>
                    </div>
                </div>

                <!-- ================= DATA WALI (OPSIONAL) ================= -->
                <h2><i class="fas fa-user-shield"></i> Data Wali (Opsional)</h2>

                <div class="form-row">
                    <div class="form-group">
                        <label>Nama Wali</label>
                        <input type="text" name="nama_wali">
                    </div>

                    <div class="form-group">
                        <label>Hubungan</label>
                        <input type="text" name="hubungan_wali" placeholder="Contoh: Paman, Kakek">
                    </div>
                </div>

                <div class="form-group">
                    <label>No HP Wali</label>
                    <input type="text" name="hp_wali" pattern="[0-9]{10,13}">
                </div>

                <!-- ================= SEKOLAH ASAL ================= -->
                <h2><i class="fas fa-school"></i> Sekolah Asal</h2>

                <div class="form-group">
                    <label>Nama TK / PAUD *</label>
                    <input type="text" name="nama_tk_asal" required>
                </div>

                <!-- ================= PINDAHAN ================= -->
                <h2><i class="fas fa-exchange-alt"></i> Data Pindahan (Jika Ada)</h2>

                <div class="form-row">
                    <div class="form-group">
                        <label>Pindahan Dari</label>
                        <input type="text" name="pindahan_dari" placeholder="Nama sekolah sebelumnya">
                    </div>

                    <div class="form-group">
                        <label>Kelas Pindahan</label>
                        <input type="text" name="kelas_pindahan" placeholder="Contoh: Kelas 2">
                    </div>
                </div>

                <!-- ================= UPLOAD BERKAS ================= -->
                <h2><i class="fas fa-file-upload"></i> Upload Berkas</h2>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Perhatian:</strong> 
                    <ul style="margin: 0.5rem 0 0 1.5rem;">
                        <li>Ukuran maksimal per file: 5MB</li>
                        <li>Format yang diterima: JPG, PNG, GIF, PDF, DOC, DOCX</li>
                        <li>Semua file akan disimpan dengan nama otomatis</li>
                        <li>Berkas wajib: Foto, Akta, KK, KTP Orang Tua, Ijazah TK</li>
                    </ul>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Foto Siswa (3x4) *</label>
                        <input type="file" name="foto" accept=".jpg,.jpeg,.png,.gif" required>
                        <small class="form-hint">Background merah, formal</small>
                    </div>

                    <div class="form-group">
                        <label>Akta Kelahiran *</label>
                        <input type="file" name="akta" accept=".jpg,.jpeg,.png,.pdf" required>
                        <small class="form-hint">Scan/foto jelas</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Kartu Keluarga *</label>
                        <input type="file" name="kk" accept=".jpg,.jpeg,.png,.pdf" required>
                        <small class="form-hint">Halaman dengan data siswa</small>
                    </div>

                    <div class="form-group">
                        <label>KTP Orang Tua *</label>
                        <input type="file" name="ktp_ortu" accept=".jpg,.jpeg,.png,.pdf" required>
                        <small class="form-hint">KTP ayah dan ibu dalam 1 file</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Ijazah TK/PAUD *</label>
                        <input type="file" name="ijazah_tk" accept=".jpg,.jpeg,.png,.pdf" required>
                        <small class="form-hint">Ijazah terakhir</small>
                    </div>

                    <div class="form-group">
                        <label>Surat Pindah (Jika ada)</label>
                        <input type="file" name="surat_pindah" accept=".jpg,.jpeg,.png,.pdf">
                        <small class="form-hint">Hanya untuk pindahan</small>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-paper-plane"></i> Kirim Pendaftaran
                    </button>
                    <a href="dashboard.php" class="btn-secondary">
                        <i class="fas fa-times"></i> Batal
                    </a>
                </div>
            </form>

        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
    <script>
        
        // Show selected file name
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', function() {
                if (this.files[0]) {
                    const fileName = this.files[0].name;
                    const sizeInMB = (this.files[0].size / (1024*1024)).toFixed(2);
                    
                    // Cek ukuran file
                    if (this.files[0].size > 50 * 1024 * 1024) {
                        alert('Ukuran file terlalu besar! Maksimal 5MB');
                        this.value = '';
                        return;
                    }
                    
                    // Tampilkan info file
                    let infoDiv = this.nextElementSibling.nextElementSibling;
                    if (!infoDiv || !infoDiv.classList.contains('file-info')) {
                        infoDiv = document.createElement('div');
                        infoDiv.className = 'file-info';
                        this.parentNode.appendChild(infoDiv);
                    }
                    infoDiv.innerHTML = `<i class="fas fa-file"></i> ${fileName} (${sizeInMB} MB)`;
                }
            });
        });
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const requiredFiles = ['foto', 'akta', 'kk', 'ktp_ortu', 'ijazah_tk'];
            let missingFiles = [];
            
            requiredFiles.forEach(fileName => {
                const fileInput = document.querySelector(`input[name="${fileName}"]`);
                if (!fileInput.files[0]) {
                    missingFiles.push(fileName.replace('_', ' '));
                }
            });
            
            if (missingFiles.length > 0) {
                e.preventDefault();
                alert('Berkas wajib belum diupload:\n' + missingFiles.join('\n'));
            }
        });
    </script>
    
    <style>
        .file-info {
            margin-top: 0.5rem;
            padding: 0.5rem;
            background: #e9ecef;
            border-radius: 5px;
            font-size: 0.85rem;
            color: #495057;
        }
        
        .file-info i {
            margin-right: 0.5rem;
            color: #6c757d;
        }
        
        .form-hint {
            display: block;
            margin-top: 0.25rem;
            font-size: 0.85rem;
            color: #6c757d;
            font-style: italic;
        }
    </style>
</body>
</html>