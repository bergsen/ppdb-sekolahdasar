<?php
require_once '../config/database.php';
redirectIfNotAuthorized(['pendaftar']);

$db = new Database();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];

// Ambil ID dari URL
$calonSiswaId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if (!$calonSiswaId) {
    header("Location: dashboard.php");
    exit();
}

// Ambil data calon siswa
$sql = "SELECT cs.*, ta.tahun, ta.status as status_tahun
        FROM calon_siswa cs 
        JOIN tahun_ajaran ta ON cs.tahun_ajaran_id = ta.id 
        WHERE cs.id = $calonSiswaId AND cs.user_id = $userId";
$result = $conn->query($sql);

if ($result->num_rows === 0) {
    header("Location: dashboard.php");
    exit();
}

$calonSiswa = $result->fetch_assoc();

$bolehEdit = true;
$alasanTolak = '';

if ($calonSiswa['status_terima'] !== 'pending') {
    $bolehEdit = false;
    $alasanTolak = 'Status pendaftaran sudah diproses';
}

if ($calonSiswa['status_tahun'] !== 'aktif') {
    $bolehEdit = false;
    $alasanTolak = 'Tahun ajaran sudah tidak aktif';
}

// Ambil berkas yang sudah diupload
$sqlBerkas = "SELECT * FROM berkas WHERE calon_siswa_id = $calonSiswaId";
$resultBerkas = $conn->query($sqlBerkas);
$berkas = [];
while ($row = $resultBerkas->fetch_assoc()) {
    $berkas[$row['jenis_berkas']] = $row;
}

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
        $anakKe = (int) $_POST['anak_ke'];
        $jumlahSaudara = (int) $_POST['jumlah_saudara'];
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

        // Update calon siswa
        $sql = "UPDATE calon_siswa SET
                nama_siswa = '$namaSiswa',
                jenis_kelamin = '$jenisKelamin',
                tempat_lahir = '$tempatLahir',
                tanggal_lahir = '$tanggalLahir',
                agama = '$agama',
                kewarganegaraan = '$kewarganegaraan',
                anak_ke = $anakKe,
                jumlah_saudara = $jumlahSaudara,
                golongan_darah = '$golonganDarah',
                riwayat_penyakit = '$riwayatPenyakit',
                alamat = '$alamat',
                nama_ayah = '$namaAyah',
                pekerjaan_ayah = '$pekerjaanAyah',
                penghasilan_ayah = '$penghasilanAyah',
                hp_ayah = '$hpAyah',
                nama_ibu = '$namaIbu',
                pekerjaan_ibu = '$pekerjaanIbu',
                penghasilan_ibu = '$penghasilanIbu',
                hp_ibu = '$hpIbu',
                nama_wali = " . ($namaWali ? "'$namaWali'" : "NULL") . ",
                hubungan_wali = " . ($hubunganWali ? "'$hubunganWali'" : "NULL") . ",
                hp_wali = " . ($hpWali ? "'$hpWali'" : "NULL") . ",
                nama_tk_asal = '$namaTkAsal',
                pindahan_dari = " . ($pindahanDari ? "'$pindahanDari'" : "NULL") . ",
                kelas_pindahan = " . ($kelasPindahan ? "'$kelasPindahan'" : "NULL") . "
                WHERE id = $calonSiswaId";

        if ($conn->query($sql)) {
            // Upload berkas
            $berkasTypes = [
                'foto' => ['jenis' => 'Foto Siswa', 'folder' => 'foto'],
                'akta' => ['jenis' => 'Akta Kelahiran', 'folder' => 'akta'],
                'kk' => ['jenis' => 'Kartu Keluarga', 'folder' => 'kk'],
                'ktp_ortu' => ['jenis' => 'KTP Orang Tua', 'folder' => 'ktp_ortu'],
                'ijazah_tk' => ['jenis' => 'Ijazah TK', 'folder' => 'ijazah_tk'],
                'surat_pindah' => ['jenis' => 'Surat Pindah', 'folder' => 'surat_pindah']
            ];

            $allSuccess = true;

            foreach ($berkasTypes as $key => $data) {
                if (isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK) {
                    // Generate nama file
                    $prefix = $calonSiswa['nomor_pendaftaran'] . '_' . $key . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $namaSiswa);
                    $upload = uploadFile($_FILES[$key], $data['folder'], $prefix);

                    if (isset($upload['success'])) {
                        // Cek apakah sudah ada berkas dengan jenis yang sama
                        if (isset($berkas[$data['jenis']])) {
                            // Update berkas yang sudah ada
                            $updateBerkas = "UPDATE berkas SET 
                                nama_file = '{$upload['file_name']}',
                                status = 'belum_diperiksa'
                                WHERE id = {$berkas[$data['jenis']]['id']}";
                        } else {
                            // Insert berkas baru
                            $insertBerkas = "INSERT INTO berkas 
                                (calon_siswa_id, jenis_berkas, nama_file, file_path) 
                                VALUES ($calonSiswaId, '{$data['jenis']}', '{$upload['file_name']}', '{$data['folder']}')";
                        }

                        $query = isset($updateBerkas) ? $updateBerkas : $insertBerkas;
                        $conn->query($query);
                    } else {
                        $allSuccess = false;
                        $error .= "Gagal upload {$data['jenis']}: " . $upload['error'] . "<br>";
                    }
                }
            }

            if ($allSuccess) {
                $conn->commit();
                $success = "Data berhasil diperbarui!";
            } else {
                $conn->rollback();
            }
        } else {
            $error = "Gagal menyimpan data: " . $conn->error;
        }

    } catch (Exception $e) {
        $conn->rollback();
        $error = "Terjadi kesalahan: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Formulir Pendaftaran - PPDB SDN Pasir Panjang 02</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="dashboard-header">
            <div class="container">
                <h1><i class="fas fa-edit"></i> Edit Formulir Pendaftaran</h1>
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
        <?php if (!$bolehEdit): ?>
            <div class="alert alert-error">
                <i class="fas fa-lock"></i>
                Data tidak dapat diedit.<br>
                Alasan: <strong><?php echo $alasanTolak; ?></strong>
            </div>
            <a href="dashboard.php" class="btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
            </a>
            <?php exit; ?>
        <?php endif; ?>

        <div class="container">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                Anda hanya dapat mengedit data selama status masih <strong>PENDING</strong>
                dan tahun ajaran masih <strong>AKTIF</strong>.
                <br>
                No. Pendaftaran: <strong><?php echo htmlspecialchars($calonSiswa['nomor_pendaftaran']); ?></strong>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="form-section">

                <!-- ================= DATA SISWA ================= -->
                <h2><i class="fas fa-user-graduate"></i> Data Calon Siswa</h2>

                <div class="form-row">
                    <div class="form-group">
                        <label>Nama Lengkap *</label>
                        <input type="text" name="nama_siswa"
                            value="<?php echo htmlspecialchars($calonSiswa['nama_siswa']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Jenis Kelamin *</label>
                        <select name="jenis_kelamin" required>
                            <option value="">-- Pilih --</option>
                            <option value="L" <?php echo $calonSiswa['jenis_kelamin'] == 'L' ? 'selected' : ''; ?>>
                                Laki-laki</option>
                            <option value="P" <?php echo $calonSiswa['jenis_kelamin'] == 'P' ? 'selected' : ''; ?>>
                                Perempuan</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Tempat Lahir *</label>
                        <input type="text" name="tempat_lahir"
                            value="<?php echo htmlspecialchars($calonSiswa['tempat_lahir']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Tanggal Lahir *</label>
                        <input type="date" name="tanggal_lahir" value="<?php echo $calonSiswa['tanggal_lahir']; ?>"
                            required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Agama *</label>
                        <select name="agama" required>
                            <option value="">-- Pilih --</option>
                            <option value="Islam" <?php echo $calonSiswa['agama'] == 'Islam' ? 'selected' : ''; ?>>Islam
                            </option>
                            <option value="Kristen Protestan" <?php echo $calonSiswa['agama'] == 'Kristen Protestan' ? 'selected' : ''; ?>>Kristen Protestan</option>
                            <option value="Katolik" <?php echo $calonSiswa['agama'] == 'Katolik' ? 'selected' : ''; ?>>
                                Katolik</option>
                            <option value="Hindu" <?php echo $calonSiswa['agama'] == 'Hindu' ? 'selected' : ''; ?>>Hindu
                            </option>
                            <option value="Buddha" <?php echo $calonSiswa['agama'] == 'Buddha' ? 'selected' : ''; ?>>
                                Buddha</option>
                            <option value="Khonghucu" <?php echo $calonSiswa['agama'] == 'Khonghucu' ? 'selected' : ''; ?>>Khonghucu</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Kewarganegaraan *</label>
                        <select name="kewarganegaraan" required>
                            <option value="WNI" <?php echo $calonSiswa['kewarganegaraan'] == 'WNI' ? 'selected' : ''; ?>>
                                WNI</option>
                            <option value="WNA" <?php echo $calonSiswa['kewarganegaraan'] == 'WNA' ? 'selected' : ''; ?>>
                                WNA</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Anak ke *</label>
                        <input type="number" name="anak_ke" value="<?php echo $calonSiswa['anak_ke']; ?>" min="1"
                            required>
                    </div>

                    <div class="form-group">
                        <label>Jumlah Saudara *</label>
                        <input type="number" name="jumlah_saudara" value="<?php echo $calonSiswa['jumlah_saudara']; ?>"
                            min="0" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Golongan Darah</label>
                        <select name="golongan_darah">
                            <option value="">-- Pilih --</option>
                            <option value="A" <?php echo $calonSiswa['golongan_darah'] == 'A' ? 'selected' : ''; ?>>A
                            </option>
                            <option value="B" <?php echo $calonSiswa['golongan_darah'] == 'B' ? 'selected' : ''; ?>>B
                            </option>
                            <option value="AB" <?php echo $calonSiswa['golongan_darah'] == 'AB' ? 'selected' : ''; ?>>AB
                            </option>
                            <option value="O" <?php echo $calonSiswa['golongan_darah'] == 'O' ? 'selected' : ''; ?>>O
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Riwayat Penyakit</label>
                        <input type="text" name="riwayat_penyakit"
                            value="<?php echo htmlspecialchars($calonSiswa['riwayat_penyakit']); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Alamat Lengkap *</label>
                    <textarea name="alamat" rows="3"
                        required><?php echo htmlspecialchars($calonSiswa['alamat']); ?></textarea>
                </div>

                <!-- ================= DATA AYAH ================= -->
                <h2><i class="fas fa-male"></i> Data Ayah</h2>

                <div class="form-row">
                    <div class="form-group">
                        <label>Nama Ayah *</label>
                        <input type="text" name="nama_ayah"
                            value="<?php echo htmlspecialchars($calonSiswa['nama_ayah']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Pekerjaan *</label>
                        <input type="text" name="pekerjaan_ayah"
                            value="<?php echo htmlspecialchars($calonSiswa['pekerjaan_ayah']); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Penghasilan *</label>
                        <input type="text" name="penghasilan_ayah"
                            value="<?php echo htmlspecialchars($calonSiswa['penghasilan_ayah']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>No HP *</label>
                        <input type="text" name="hp_ayah"
                            value="<?php echo htmlspecialchars($calonSiswa['hp_ayah']); ?>" required>
                    </div>
                </div>

                <!-- ================= DATA IBU ================= -->
                <h2><i class="fas fa-female"></i> Data Ibu</h2>

                <div class="form-row">
                    <div class="form-group">
                        <label>Nama Ibu *</label>
                        <input type="text" name="nama_ibu"
                            value="<?php echo htmlspecialchars($calonSiswa['nama_ibu']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Pekerjaan *</label>
                        <input type="text" name="pekerjaan_ibu"
                            value="<?php echo htmlspecialchars($calonSiswa['pekerjaan_ibu']); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Penghasilan *</label>
                        <input type="text" name="penghasilan_ibu"
                            value="<?php echo htmlspecialchars($calonSiswa['penghasilan_ibu']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>No HP *</label>
                        <input type="text" name="hp_ibu" value="<?php echo htmlspecialchars($calonSiswa['hp_ibu']); ?>"
                            required>
                    </div>
                </div>

                <!-- ================= DATA WALI (OPSIONAL) ================= -->
                <h2><i class="fas fa-user-shield"></i> Data Wali (Opsional)</h2>

                <div class="form-row">
                    <div class="form-group">
                        <label>Nama Wali</label>
                        <input type="text" name="nama_wali"
                            value="<?php echo htmlspecialchars($calonSiswa['nama_wali'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>Hubungan</label>
                        <input type="text" name="hubungan_wali"
                            value="<?php echo htmlspecialchars($calonSiswa['hubungan_wali'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>No HP Wali</label>
                    <input type="text" name="hp_wali"
                        value="<?php echo htmlspecialchars($calonSiswa['hp_wali'] ?? ''); ?>">
                </div>

                <!-- ================= SEKOLAH ASAL ================= -->
                <h2><i class="fas fa-school"></i> Sekolah Asal</h2>

                <div class="form-group">
                    <label>Nama TK / PAUD *</label>
                    <input type="text" name="nama_tk_asal"
                        value="<?php echo htmlspecialchars($calonSiswa['nama_tk_asal']); ?>" required>
                </div>

                <!-- ================= PINDAHAN ================= -->
                <h2><i class="fas fa-exchange-alt"></i> Data Pindahan (Jika Ada)</h2>

                <div class="form-row">
                    <div class="form-group">
                        <label>Pindahan Dari</label>
                        <input type="text" name="pindahan_dari"
                            value="<?php echo htmlspecialchars($calonSiswa['pindahan_dari'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>Kelas Pindahan</label>
                        <input type="text" name="kelas_pindahan"
                            value="<?php echo htmlspecialchars($calonSiswa['kelas_pindahan'] ?? ''); ?>">
                    </div>
                </div>

                <!-- ================= UPLOAD BERKAS ================= -->
                <h2><i class="fas fa-file-upload"></i> Upload Berkas</h2>
                <p class="form-hint">Upload ulang untuk mengganti file yang sudah ada</p>

                <div class="form-row">
                    <div class="form-group">
                        <label>Foto Siswa</label>
                        <input type="file" name="foto" accept=".jpg,.jpeg,.png">
                        <?php if (isset($berkas['Foto Siswa'])): ?>
                            <small class="file-info">
                                File saat ini: <?php echo htmlspecialchars($berkas['Foto Siswa']['nama_file']); ?>
                            </small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Akta Kelahiran</label>
                        <input type="file" name="akta" accept=".jpg,.jpeg,.png,.pdf">
                        <?php if (isset($berkas['Akta Kelahiran'])): ?>
                            <small class="file-info">
                                File saat ini: <?php echo htmlspecialchars($berkas['Akta Kelahiran']['nama_file']); ?>
                            </small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Kartu Keluarga</label>
                        <input type="file" name="kk" accept=".jpg,.jpeg,.png,.pdf">
                        <?php if (isset($berkas['Kartu Keluarga'])): ?>
                            <small class="file-info">
                                File saat ini: <?php echo htmlspecialchars($berkas['Kartu Keluarga']['nama_file']); ?>
                            </small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>KTP Orang Tua</label>
                        <input type="file" name="ktp_ortu" accept=".jpg,.jpeg,.png,.pdf">
                        <?php if (isset($berkas['KTP Orang Tua'])): ?>
                            <small class="file-info">
                                File saat ini: <?php echo htmlspecialchars($berkas['KTP Orang Tua']['nama_file']); ?>
                            </small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Ijazah TK</label>
                        <input type="file" name="ijazah_tk" accept=".jpg,.jpeg,.png,.pdf">
                        <?php if (isset($berkas['Ijazah TK'])): ?>
                            <small class="file-info">
                                File saat ini: <?php echo htmlspecialchars($berkas['Ijazah TK']['nama_file']); ?>
                            </small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Surat Pindah</label>
                        <input type="file" name="surat_pindah" accept=".jpg,.jpeg,.png,.pdf">
                        <?php if (isset($berkas['Surat Pindah'])): ?>
                            <small class="file-info">
                                File saat ini: <?php echo htmlspecialchars($berkas['Surat Pindah']['nama_file']); ?>
                            </small>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                    <a href="dashboard.php" class="btn-secondary">
                        <i class="fas fa-times"></i> Batal
                    </a>
                </div>
            </form>

        </div>
    </div>

    <script src="../assets/js/script.js"></script>
    <style>
        .file-info {
            display: block;
            margin-top: 0.5rem;
            padding: 0.5rem;
            background: #f8f9fa;
            border-radius: 5px;
            font-size: 0.9rem;
            color: #666;
        }
    </style>
</body>

</html>