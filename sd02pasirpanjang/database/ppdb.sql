-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               10.4.27-MariaDB - mariadb.org binary distribution
-- Server OS:                    Win64
-- HeidiSQL Version:             12.4.0.6659
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for ppdb
CREATE DATABASE IF NOT EXISTS `ppdb` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;
USE `ppdb`;

-- Dumping structure for table ppdb.berkas
CREATE TABLE IF NOT EXISTS `berkas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `calon_siswa_id` int(11) NOT NULL,
  `jenis_berkas` varchar(50) NOT NULL,
  `nama_file` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `status` enum('valid','tidak_valid','belum_diperiksa') DEFAULT 'belum_diperiksa',
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `calon_siswa_id` (`calon_siswa_id`),
  CONSTRAINT `berkas_ibfk_1` FOREIGN KEY (`calon_siswa_id`) REFERENCES `calon_siswa` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=73 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table ppdb.berkas: ~10 rows (approximately)
DELETE FROM `berkas`;
INSERT INTO `berkas` (`id`, `calon_siswa_id`, `jenis_berkas`, `nama_file`, `file_path`, `status`, `catatan`, `created_at`) VALUES
	(63, 18, 'Foto Siswa', 'REG2026027983_foto_Bergas_Eko_Nugroho_6989397797d3a.png', 'foto', 'valid', '', '2026-02-09 01:33:43'),
	(64, 18, 'Akta Kelahiran', 'REG2026027983_akta_Bergas_Eko_Nugroho_69893977989f5.jpg', 'akta', 'valid', '', '2026-02-09 01:33:43'),
	(65, 18, 'Kartu Keluarga', 'REG2026027983_kk_Bergas_Eko_Nugroho_698939779914c.png', 'kk', 'valid', '', '2026-02-09 01:33:43'),
	(66, 18, 'KTP Orang Tua', 'REG2026027983_ktp_ortu_Bergas_Eko_Nugroho_698939779a063.jpg', 'ktp_ortu', 'valid', '', '2026-02-09 01:33:43'),
	(67, 18, 'Ijazah TK', 'REG2026027983_ijazah_tk_Bergas_Eko_Nugroho_698939779a84f.pdf', 'ijazah_tk', 'valid', '', '2026-02-09 01:33:43'),
	(68, 19, 'Foto Siswa', 'REG2026055146_foto_Bayu_Dwi_Haryanto_6a09625795562.png', 'foto', 'valid', '', '2026-05-17 06:38:15'),
	(69, 19, 'Akta Kelahiran', 'REG2026055146_akta_Bayu_Dwi_Haryanto_6a09625795ebf.jpeg', 'akta', 'valid', '', '2026-05-17 06:38:15'),
	(70, 19, 'Kartu Keluarga', 'REG2026055146_kk_Bayu_Dwi_Haryanto_6a09625796881.jpeg', 'kk', 'valid', '', '2026-05-17 06:38:15'),
	(71, 19, 'KTP Orang Tua', 'REG2026055146_ktp_ortu_Bayu_Dwi_Haryanto_6a096257971a4.jpeg', 'ktp_ortu', 'valid', '', '2026-05-17 06:38:15'),
	(72, 19, 'Ijazah TK', 'REG2026055146_ijazah_tk_Bayu_Dwi_Haryanto_6a09625797aa0.jpeg', 'ijazah_tk', 'valid', '', '2026-05-17 06:38:15');

-- Dumping structure for table ppdb.calon_siswa
CREATE TABLE IF NOT EXISTS `calon_siswa` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `tahun_ajaran_id` int(11) NOT NULL,
  `nomor_pendaftaran` varchar(20) NOT NULL,
  `nama_siswa` varchar(100) NOT NULL,
  `jenis_kelamin` enum('L','P') DEFAULT NULL,
  `tempat_lahir` varchar(50) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `agama` enum('Islam','Kristen Protestan','Katolik','Hindu','Buddha','Khonghucu') DEFAULT NULL,
  `kewarganegaraan` enum('WNI','WNA') DEFAULT 'WNI',
  `anak_ke` int(11) DEFAULT NULL,
  `jumlah_saudara` int(11) DEFAULT NULL,
  `golongan_darah` varchar(3) DEFAULT NULL,
  `riwayat_penyakit` text DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `nama_ayah` varchar(100) DEFAULT NULL,
  `pekerjaan_ayah` varchar(50) DEFAULT NULL,
  `penghasilan_ayah` varchar(50) DEFAULT NULL,
  `hp_ayah` varchar(20) DEFAULT NULL,
  `nama_ibu` varchar(100) DEFAULT NULL,
  `pekerjaan_ibu` varchar(50) DEFAULT NULL,
  `penghasilan_ibu` varchar(50) DEFAULT NULL,
  `hp_ibu` varchar(20) DEFAULT NULL,
  `nama_wali` varchar(100) DEFAULT NULL,
  `hubungan_wali` varchar(50) DEFAULT NULL,
  `hp_wali` varchar(20) DEFAULT NULL,
  `nama_tk_asal` varchar(100) DEFAULT NULL,
  `pindahan_dari` varchar(100) DEFAULT NULL,
  `kelas_pindahan` varchar(50) DEFAULT NULL,
  `status_terima` enum('pending','diterima','tidak_diterima') DEFAULT 'pending',
  `catatan_panitia` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nomor_pendaftaran` (`nomor_pendaftaran`),
  KEY `user_id` (`user_id`),
  KEY `tahun_ajaran_id` (`tahun_ajaran_id`),
  CONSTRAINT `calon_siswa_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `calon_siswa_ibfk_2` FOREIGN KEY (`tahun_ajaran_id`) REFERENCES `tahun_ajaran` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table ppdb.calon_siswa: ~2 rows (approximately)
DELETE FROM `calon_siswa`;
INSERT INTO `calon_siswa` (`id`, `user_id`, `tahun_ajaran_id`, `nomor_pendaftaran`, `nama_siswa`, `jenis_kelamin`, `tempat_lahir`, `tanggal_lahir`, `agama`, `kewarganegaraan`, `anak_ke`, `jumlah_saudara`, `golongan_darah`, `riwayat_penyakit`, `alamat`, `nama_ayah`, `pekerjaan_ayah`, `penghasilan_ayah`, `hp_ayah`, `nama_ibu`, `pekerjaan_ibu`, `penghasilan_ibu`, `hp_ibu`, `nama_wali`, `hubungan_wali`, `hp_wali`, `nama_tk_asal`, `pindahan_dari`, `kelas_pindahan`, `status_terima`, `catatan_panitia`, `created_at`) VALUES
	(18, 10, 2, 'REG/202602/7983', 'Bergas Eko Nugroho', 'L', 'purbalingga', '2019-11-11', 'Islam', 'WNI', 2, 1, 'O', 'flek paru', 'kutasari, purbalingga, jawatengah', 'hafiz muzaki', 'buruh pabrik', '4.000.000', '08955645234', 'sartiyem', 'ibu rumah tangga', '-', '0876665453435', NULL, NULL, NULL, 'tk abasiyah', NULL, NULL, 'diterima', '                                            ', '2026-02-09 01:33:43'),
	(19, 11, 2, 'REG/202605/5146', 'Bayu Dwi Haryanto', 'L', 'Purbalingga', '2020-12-20', 'Islam', 'WNI', 1, 0, 'A', '-', 'Brebes salem, desa pasir panjang rt 11 rw 06', 'Sadirun', 'Wiraswasta', '1500000', '085210449458', 'Tilarsih', 'Asisten Rumah Tangga', '700000', '085210449458', 'Muhammad Bergas Eko', 'Paman', '081227615406', 'TK Semut Purbalingga', NULL, NULL, 'diterima', '                                                                                        ', '2026-05-17 06:38:15');

-- Dumping structure for table ppdb.informasi
CREATE TABLE IF NOT EXISTS `informasi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `judul` varchar(100) NOT NULL,
  `konten` text NOT NULL,
  `file` varchar(255) DEFAULT NULL,
  `jenis` enum('informasi','jadwal','persyaratan','lokasi','alur','profil') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table ppdb.informasi: ~2 rows (approximately)
DELETE FROM `informasi`;
INSERT INTO `informasi` (`id`, `judul`, `konten`, `file`, `jenis`, `created_at`) VALUES
	(2, 'Persyaratan', '1. FC Ijazah TK\n2. FC Akta Kelahiran\n3. FC KK\n4. FC KTP Orang Tua', NULL, 'persyaratan', '2024-01-01 00:00:00'),
	(11, 'Demo Akreditasi', 'sdn pasirpanjang sudah berakreditasi baik sekali dan berjalan sesuai visi misai yang ada untuk tumbuh kembang generasi bangsa', 'INFORMASI_1778998566_6a095d26624ed.png', 'informasi', '2026-05-17 06:16:06');

-- Dumping structure for table ppdb.pengumuman
CREATE TABLE IF NOT EXISTS `pengumuman` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `judul` varchar(100) NOT NULL,
  `isi` text NOT NULL,
  `file` varchar(255) DEFAULT NULL,
  `tampil_index` enum('ya','tidak') DEFAULT 'ya',
  `allow_download` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table ppdb.pengumuman: ~1 rows (approximately)
DELETE FROM `pengumuman`;
INSERT INTO `pengumuman` (`id`, `judul`, `isi`, `file`, `tampil_index`, `allow_download`, `created_at`) VALUES
	(6, 'penerimaan siswa baru', 'penerimaan siswa baru 2025/2026', 'PENGUMUMAN_1778998608_6a095d5025e28.pdf', 'ya', 1, '2026-05-17 06:16:48');

-- Dumping structure for table ppdb.tahun_ajaran
CREATE TABLE IF NOT EXISTS `tahun_ajaran` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tahun` varchar(20) NOT NULL,
  `kuota` int(11) DEFAULT 0,
  `status` enum('aktif','nonaktif') DEFAULT 'nonaktif',
  `tanggal_buka` date DEFAULT NULL,
  `tanggal_tutup` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tahun` (`tahun`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table ppdb.tahun_ajaran: ~2 rows (approximately)
DELETE FROM `tahun_ajaran`;
INSERT INTO `tahun_ajaran` (`id`, `tahun`, `kuota`, `status`, `tanggal_buka`, `tanggal_tutup`) VALUES
	(1, '2024/2025', 48, 'nonaktif', '2024-01-01', '2024-12-31'),
	(2, '2025/2026', 31, 'aktif', '2025-01-01', '2026-12-30');

-- Dumping structure for table ppdb.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','panitia','pendaftar') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reset_token` varchar(100) DEFAULT NULL,
  `reset_expired` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table ppdb.users: ~8 rows (approximately)
DELETE FROM `users`;
INSERT INTO `users` (`id`, `nama`, `username`, `no_hp`, `email`, `password`, `role`, `created_at`, `reset_token`, `reset_expired`) VALUES
	(4, 'zen inari', 'zen', '081227615406', 'zenina@gmail.com', '$2y$10$MYAPPLpy53yW0OnI0uqX2uFD2bqq91veB3b/WWju5boJsOJ0qx2oO', 'admin', '2026-01-27 10:02:56', NULL, NULL),
	(5, 'amar hamzey', 'amar', '081343553656', 'amarhzy@gmail.com', '$2y$10$SPhmy4VzrN.rtSA6fH4C8ud6mnMsJ39ODLUZKKDgCndWy5pHWrNSy', 'panitia', '2026-01-27 10:04:59', NULL, NULL),
	(8, 'shibto mikazuki', 'mikazuki', '081223943375', 'shibitomikadzuki@gmail.com', '$2y$10$xwdTe8imVghC/XM6Dhp8/eU/RUStvlVOO/P0YfZa8q9zBpIoENKWK', 'pendaftar', '2026-01-31 11:33:44', '8c76c091892b32d355c6ad9a4b8f24d35f1ce41679301bc14aa44161ad651be3', '2026-01-31 13:12:11'),
	(10, 'afifuddinmunir', 'afif', '9076544767', 'affmunir44@gmail.com', '$2y$10$wYX8DvoQ5XbtSG8mNVhQher8GRSL4BAsIdrcaSdNiFnUXdtOt1TEO', 'pendaftar', '2026-02-09 01:32:26', NULL, NULL),
	(11, 'Muhammad Bergas Eko', 'bergas', '081227615406', 'bergasekonugroho6@gmail.com', '$2y$10$ziJ9H409FQXKTmcC/nSfROwzjZoTZg2xmppFB/qI3QBPrOJ9R/x4i', 'pendaftar', '2026-05-17 06:00:32', NULL, NULL),
	(12, 'Haikal Admin', 'haikaladmin', '085210449458', 'haikaldewa@gmail.com', '$2y$10$et4vRLbI/DM1w4SYDoFKxOdjAL58YckHiHQhhgoWESBY9kN/MXoem', 'admin', '2026-05-17 06:58:48', NULL, NULL),
	(13, 'Haikal Panitia', 'haikalpanitia', '085210449458', 'haikaldewa11@gmail.com', '$2y$10$5kCfsbKdtDP6qw7vDZm8huTndECVMIypJs2.zG6S9lPURi/F9dsXi', 'admin', '2026-05-17 07:49:17', NULL, NULL),
	(14, 'Haikal Dewanata', 'haikal', '085210449458', 'haikaldewa98@gmail.com', '$2y$10$NFsrKNukINAHS4H8vht3juAMGndJp4fvtqmpfBW7.O7gNDOSIqVfS', 'pendaftar', '2026-05-17 07:50:37', NULL, NULL);

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
