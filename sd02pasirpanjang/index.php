<?php
require_once 'config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Ambil informasi untuk ditampilkan
$sqlInfo = "SELECT * FROM informasi WHERE jenis != 'profil' ORDER BY id DESC LIMIT 6";
$resultInfo = $conn->query($sqlInfo);

$sqlPengumuman = "SELECT * FROM pengumuman WHERE tampil_index = 'ya' ORDER BY id DESC LIMIT 4";
$resultPengumuman = $conn->query($sqlPengumuman);

// Ambil informasi sekolah (profil)
$sqlProfil = "SELECT * FROM informasi WHERE jenis = 'profil' LIMIT 1";
$resultProfil = $conn->query($sqlProfil);
$profil = $resultProfil->fetch_assoc();

// Ambil tahun ajaran aktif
$sqlTahun = "SELECT * FROM tahun_ajaran WHERE status = 'aktif' LIMIT 1";
$resultTahun = $conn->query($sqlTahun);
$tahunAjaran = $resultTahun->fetch_assoc();

$status = 'tutup'; // default

if ($tahunAjaran && !empty($tahunAjaran['tanggal_buka']) && !empty($tahunAjaran['tanggal_tutup'])) {

    $today = date('Y-m-d');

    if ($today < $tahunAjaran['tanggal_buka']) {
        $status = 'belum';
    } elseif ($today > $tahunAjaran['tanggal_tutup']) {
        $status = 'tutup';
    } else {
        $status = 'aktif';
    }
}

$statusText  = 'Pendaftaran Ditutup';
$statusClass = 'tutup';

if ($tahunAjaran && !empty($tahunAjaran['tanggal_buka']) && !empty($tahunAjaran['tanggal_tutup'])) {

    $today = date('Y-m-d');

    if ($today < $tahunAjaran['tanggal_buka']) {
        $statusText  = 'Belum Dibuka';
        $statusClass = 'belum';
    } elseif ($today > $tahunAjaran['tanggal_tutup']) {
        $statusText  = 'Sudah Ditutup';
        $statusClass = 'tutup';
    } else {
        $statusText  = 'Sedang Berlangsung';
        $statusClass = 'aktif';
    }
}
// Hitung statistik
$sqlStats = "SELECT 
    COUNT(*) as total_pendaftar,
    COUNT(CASE WHEN status_terima = 'diterima' THEN 1 END) as diterima,
    COUNT(CASE WHEN status_terima = 'cadangan' THEN 1 END) as cadangan
    FROM calon_siswa cs
    JOIN tahun_ajaran ta ON cs.tahun_ajaran_id = ta.id
    WHERE ta.status = 'aktif'";
$resultStats = $conn->query($sqlStats);
$stats = $resultStats->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PPDB SD Negeri Pasirpanjan 02 -PPDB</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        /* Global Styles */
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --accent: #e74c3c;
            --success: #2ecc71;
            --warning: #f39c12;
            --light: #ecf0f1;
            --dark: #2c3e50;
            --gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: url("./assets/sd2pasirpanjang.png") no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
        }

        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(165, 180, 243, 0.6);
            /* ubah 0.6 sesuai selera */
            z-index: -1;
        }


        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            padding: 8px 0;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logo-icon {
             font-size: 2rem;
            color: var(--secondary);
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .logo-text h1 {
            font-size: 1.4rem;
            color: var(--primary);
            margin: 0;
        }

        .logo-text p {
            color: #666;
            font-size: 0.9rem;
            margin: 0;
        }
        .nav a.active{
    background:linear-gradient(135deg,var(--secondary),var(--primary));
    color:white;
}

.header{
    transition:.3s;
}

.header.scrolled{
    padding:8px 0;
    box-shadow:0 10px 40px rgba(0,0,0,.15);
}


        .nav {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .nav a {
            text-decoration: none;
            color: var(--primary);
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 8px;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav a:hover {
            color: var(--secondary);
            background: rgba(52, 152, 219, 0.1);
        }

        .nav a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 3px;
            background: var(--secondary);
            transition: width 0.3s ease;
        }

        .nav a:hover::after {
            width: 80%;
        }

        .btn-login,
        .btn-register {
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-login {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-login:hover {
            background: var(--primary);
            color: white;
        }

        .btn-register {
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            color: white;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }

        /* Hero Section */
        .hero {
            padding: 150px 0 100px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            align-items: center;
            min-height: 100vh;
        }

        .hero-content {
            animation: fadeInLeft 1s ease;
        }

        .hero-content h1 {
            font-size: 3.5rem;
            color: var(--primary);
            margin-bottom: 20px;
            line-height: 1.2;
        }

        .hero-content h1 span {
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-content p {
            font-size: 1.2rem;
            color: #555;
            margin-bottom: 30px;
            line-height: 1.8;
        }

        .hero-buttons {
            display: flex;
            gap: 20px;
            margin-top: 30px;
        }

        .btn-primary,
        .btn-secondary {
            padding: 15px 35px;
            border-radius: 25px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            font-size: 1.1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            color: white;
            border: none;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(52, 152, 219, 0.3);
        }

        .btn-secondary {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-secondary:hover {
            background: var(--primary);
            color: white;
        }

        .hero-image {
            animation: fadeInRight 1s ease;
            position: relative;
        }

        .hero-image img {
            width: 100%;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            transition: transform 0.5s ease;
        }

        .hero-image:hover img {
            transform: scale(1.05);
        }
.hero-slider{
    position:relative;
    overflow:hidden;
    border-radius:20px;
}

.hero-slide{
    width:100%;
    display:none;
    animation:fade 1s;
}

.hero-slide.active{
    display:block;
}

.hero-nav{
    position:absolute;
    top:50%;
    transform:translateY(-50%);
    background:rgba(0,0,0,.5);
    color:white;
    border:none;
    width:45px;
    height:45px;
    border-radius:50%;
    cursor:pointer;
}

.hero-nav.prev{left:15px;}
.hero-nav.next{right:15px;}

@keyframes fade{
    from{opacity:0}
    to{opacity:1}
}

        /* Stats Section */
        .stats-section {
            background: var(--gradient);
            color: white;
            padding: 80px 0;
            border-radius: 20px;
            margin: 50px 0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }

        .stat-card {
            text-align: center;
            padding: 30px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.2);
        }

        .stat-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            opacity: 0.8;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        /* Information Section */
        .section {
            padding: 100px 0;
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 50px;
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            border-radius: 2px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
        }

        .info-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .info-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .info-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(135deg, var(--secondary), var(--primary));
        }

        .info-icon {
            font-size: 2.5rem;
            color: var(--secondary);
            margin-bottom: 20px;
        }

        .info-card h3 {
            font-size: 1.5rem;
            color: var(--primary);
            margin-bottom: 15px;
        }

        .info-card p {
            color: #666;
            line-height: 1.7;
            margin-bottom: 15px;
        }

        /* Preview Image Container */
        .image-preview-container {
            position: relative;
            margin-top: 15px;
            border-radius: 10px;
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .image-preview-container:hover {
            transform: scale(1.02);
        }

        .image-preview {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
        }

        .preview-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .preview-overlay:hover {
            opacity: 1;
        }

        .preview-icon {
            color: white;
            font-size: 2rem;
            background: rgba(0, 0, 0, 0.7);
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Pengumuman Section */
        .pengumuman-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .pengumuman-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            position: relative;
        }

        .pengumuman-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        .pengumuman-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(135deg, #f39c12, #e74c3c);
        }

        .pengumuman-card h3 {
            color: var(--primary);
            margin-bottom: 15px;
            font-size: 1.4rem;
        }

        .btn-download {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            margin-top: 15px;
            transition: all 0.3s ease;
        }

        .btn-download:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 204, 113, 0.3);
        }

        .file-preview-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            margin-top: 15px;
            margin-left: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            font-family: inherit;
        }

        .file-preview-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }

        /* Contact & Map Section */
        .contact-section {
            background: white;
            border-radius: 20px;
            padding: 60px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            margin: 50px 0;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
        }

        .contact-info h3 {
            color: var(--primary);
            margin-bottom: 30px;
            font-size: 2rem;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .contact-item:hover {
            background: #e9ecef;
            transform: translateX(10px);
        }

        .contact-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .contact-text h4 {
            color: var(--primary);
            margin-bottom: 5px;
        }

        .contact-text a {
            color: #666;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .contact-text a:hover {
            color: var(--secondary);
        }

        .map-container {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .map-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .modal-content {
            position: relative;
            margin: 5% auto;
            padding: 20px;
            width: 90%;
            max-width: 900px;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-image {
            width: 100%;
            max-height: 80vh;
            object-fit: contain;
            border-radius: 10px;
        }

        .modal-document {
            width: 100%;
            height: 80vh;
            border: none;
            border-radius: 10px;
        }

        .modal-pdf {
            width: 100%;
            height: 80vh;
            border: none;
            border-radius: 10px;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            color: white;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: bold;
        }

        .close-modal {
            color: white;
            font-size: 2rem;
            cursor: pointer;
            background: none;
            border: none;
            transition: transform 0.3s ease;
        }

        .close-modal:hover {
            transform: rotate(90deg);
        }

        .modal-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            color: white;
            font-size: 2rem;
            background: rgba(0, 0, 0, 0.5);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .modal-nav:hover {
            background: rgba(0, 0, 0, 0.8);
        }

        .modal-nav.prev {
            left: -70px;
        }

        .modal-nav.next {
            right: -70px;
        }

        .modal-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }

        .modal-btn {
            padding: 10px 25px;
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .modal-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }

        /* Footer */
        .footer {
            background: var(--primary);
            color: white;
            padding: 60px 0 20px;
            margin-top: 100px;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-section h3 {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: var(--light);
        }

        .footer-section p {
            color: #bdc3c7;
            line-height: 1.8;
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 10px;
        }

        .footer-links a {
            color: #bdc3c7;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: white;
        }

        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .social-links a {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .social-links a:hover {
            background: var(--secondary);
            transform: translateY(-3px);
        }

        .footer-bottom {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: #bdc3c7;
        }

        /* Animations */
        @keyframes fadeInLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes fadeInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--primary);
            padding: 8px;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .hero {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .contact-grid {
                grid-template-columns: 1fr;
            }

            .nav {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: rgba(255,255,255,0.98);
                backdrop-filter: blur(10px);
                flex-direction: column;
                padding: 1rem;
                box-shadow: 0 10px 30px rgba(0,0,0,0.15);
                gap: 5px;
                z-index: 999;
            }

            .nav.active {
                display: flex;
            }

            .nav a {
                padding: 12px 16px;
                width: 100%;
                text-align: left;
                border-radius: 8px;
            }

            .mobile-menu-btn {
                display: block;
            }

            .modal-nav {
                position: fixed;
                top: auto;
                bottom: 20px;
            }

            .modal-nav.prev {
                left: 20px;
            }

            .modal-nav.next {
                right: 20px;
            }
        }

        @media (max-width: 768px) {
            .hero {
                padding: 120px 0 60px;
                min-height: auto;
            }

            .hero-content h1 {
                font-size: 1.8rem;
            }

            .hero-content p {
                font-size: 1rem;
            }

            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }

            .hero-buttons .btn-primary,
            .hero-buttons .btn-secondary {
                width: 100%;
                justify-content: center;
                padding: 12px 20px;
                font-size: 1rem;
            }

            .info-grid,
            .pengumuman-list {
                grid-template-columns: 1fr;
            }

            .info-grid {
                gap: 20px;
            }

            .section {
                padding: 60px 0;
            }

            .section-title {
                font-size: 1.8rem;
                margin-bottom: 30px;
            }

            .stats-section {
                padding: 40px 0;
                border-radius: 10px;
                margin: 30px 0;
            }

            .stat-number {
                font-size: 2rem;
            }

            .stat-label {
                font-size: 1rem;
            }

            .contact-section {
                padding: 30px 20px;
            }

            .contact-item {
                padding: 15px;
                gap: 15px;
            }

            .tahun-banner h3 {
                font-size: 1.1rem;
            }

            .jadwal-card {
                padding: 25px;
            }

            .footer {
                margin-top: 50px;
                padding: 40px 0 20px;
            }

            .footer-content {
                grid-template-columns: 1fr;
                gap: 25px;
            }

            .logo-text h1 {
                font-size: 1rem;
            }

            .logo-text p {
                font-size: 0.75rem;
            }

            .modal-content {
                width: 95%;
                margin: 10% auto;
            }

            .modal-pdf,
            .modal-document {
                height: 60vh;
            }

            .whatsapp-float {
                width: 50px;
                height: 50px;
                bottom: 20px;
                right: 20px;
                font-size: 24px;
            }
        }

        @media (max-width: 480px) {
            .hero-content h1 {
                font-size: 1.5rem;
            }

            .container {
                padding: 0 15px;
            }

            .info-card {
                padding: 20px;
            }

            .pengumuman-card {
                padding: 20px;
            }
        }

        /* Floating Button */
        .whatsapp-float {
            position: fixed;
            width: 60px;
            height: 60px;
            bottom: 40px;
            right: 40px;
            background-color: #25d366;
            color: white;
            border-radius: 50px;
            text-align: center;
            font-size: 30px;
            box-shadow: 2px 2px 3px #999;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .whatsapp-float:hover {
            background-color: #128C7E;
            transform: scale(1.1);
        }

        /* Tahun Ajaran Banner */
        .tahun-banner {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
            padding: 15px 30px;
            border-radius: 10px;
            text-align: center;
            margin: 30px 0;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.02);
            }

            100% {
                transform: scale(1);
            }
        }

        .jadwal-wrapper {
            display: flex;
            justify-content: center;
        }

        .jadwal-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 20px 40px rgba(0, 0, 0, .2);
            transition: .3s;
        }

        .jadwal-card:hover {
            transform: translateY(-10px);
        }

        .jadwal-icon {
            font-size: 3rem;
            margin-bottom: 20px;
        }

        .jadwal-tanggal {
            font-size: 1.2rem;
            margin: 20px 0;
            line-height: 1.8;
        }

        .jadwal-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, .2);
            padding: 8px 18px;
            border-radius: 25px;
            font-size: .9rem;
        }

        .jadwal-badge.aktif i {
            color: #2ecc71;
            animation: pulseDot 1.5s infinite;
        }

        @keyframes pulseDot {
            0% {
                opacity: .3
            }

            50% {
                opacity: 1
            }

            100% {
                opacity: .3
            }
        }
        
    </style>
</head>

<body>
    <!-- Header -->
    <header class="header">
        <div class="container header-content">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-school"></i>
                </div>
                <div class="logo-text">
                    <h1>PPDB SD Negeri Pasir Panjang 02</h1>
                    <p>Penerimaan Peserta Didik Baru</p>
                </div>
            </div>
            <button class="mobile-menu-btn" onclick="document.querySelector('.nav').classList.toggle('active')">
                <i class="fas fa-bars"></i>
            </button>
            <nav class="nav">
                <a href="#home"><i class="fas fa-home"></i> Beranda</a>
                <a href="#informasi"><i class="fas fa-info-circle"></i> Informasi</a>
                <a href="#pengumuman"><i class="fas fa-bullhorn"></i> Pengumuman</a>
                <a href="#kontak"><i class="fas fa-address-book"></i> Kontak</a>
                <a href="login.php" class="btn-login"><i class="fas fa-sign-in-alt"></i> Login Pendaftar</a>
                <a href="register.php" class="btn-register"><i class="fas fa-user-plus"></i> Daftar</a>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container">
        <!-- Hero Section -->
        <section id="home" class="hero">
            <div class="hero-content">
                <h1>Selamat Datang di <span>PPDB Online SD Negeri Pasir Panjang 02</span></h1>
                <p>Sistem pendaftaran peserta didik baru yang modern dan efisien. Daftarkan putra/putri Anda dengan
                    mudah melalui platform digital kami. Proses cepat, aman, dan transparan.</p>

                <?php if ($tahunAjaran): ?>
                    <div class="tahun-banner">
                        <h3><i class="fas fa-calendar-check"></i> Tahun Ajaran
                            <?php echo htmlspecialchars($tahunAjaran['tahun']); ?> Telah Dibuka!
                        </h3>
                        <p>Kuota tersedia: <strong><?php echo $tahunAjaran['kuota']; ?> siswa</strong></p>
                    </div>
                <?php endif; ?>

                <div class="hero-buttons">

    <?php if ($status === 'aktif'): ?>
        <a href="register.php"
           class="btn-primary animate__animated animate__pulse animate__infinite">
            <i class="fas fa-rocket"></i> Daftar Sekarang
        </a>
    <?php else: ?>
        <a href="javascript:void(0)"
           class="btn-primary btn-disabled">
            <i class="fas fa-lock"></i>
            <?= $status === 'belum'
                ? 'Pendaftaran Belum Dibuka'
                : 'Pendaftaran Ditutup'; ?>
        </a>
    <?php endif; ?>

    <a href="#informasi" class="btn-secondary">
        <i class="fas fa-book-open"></i> Pelajari Selengkapnya
    </a>

</div>

            </div>
            <div class="hero-image">
    <div class="hero-slider">
        <img src="./assets/sd2pasirpanjang.png" alt="SD Negeri Pasirpanjang 02"  class="hero-slide active">
        <img src="./assets/psrpjg.jpeg" class="hero-slide">
        <!-- <img src="assets/hero/2.jpg" class="hero-slide">
        <img src="assets/hero/3.jpg" class="hero-slide"> -->
    </div>

    <button class="hero-nav prev" onclick="prevHero()">❮</button>
    <button class="hero-nav next" onclick="nextHero()">❯</button>
</div>

        </section>

       <?php if ($tahunAjaran && $tahunAjaran['tanggal_buka'] && $tahunAjaran['tanggal_tutup']): ?>

<section class="section">
    <h2 class="section-title">Jadwal Pendaftaran</h2>

    <div class="jadwal-wrapper">
        <div class="jadwal-card utama">
            <div class="jadwal-icon">
                <i class="fas fa-calendar-check"></i>
            </div>

            <h3>Pendaftaran Peserta Didik Baru</h3>

            <p class="jadwal-tanggal">
                <strong><?= date('d F Y', strtotime($tahunAjaran['tanggal_buka'])) ?></strong>
                <br>sampai<br>
                <strong><?= date('d F Y', strtotime($tahunAjaran['tanggal_tutup'])) ?></strong>
            </p>

            <span class="jadwal-badge <?= $statusClass ?>">
                <i class="fas fa-circle"></i> <?= $statusText ?>
            </span>
        </div>
    </div>
</section>

<?php endif; ?>


        <!-- Stats Section -->
        <section class="stats-section">
            <div class="container">
                <div class="stats-grid">
                    <div class="stat-card animate__animated animate__fadeInUp">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-number"><?php echo $stats['total_pendaftar'] ?? '0'; ?></div>
                        <div class="stat-label">Total Pendaftar</div>
                    </div>

                    <div class="stat-card animate__animated animate__fadeInUp animate__delay-1s">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-number"><?php echo $stats['diterima'] ?? '0'; ?></div>
                        <div class="stat-label">Siswa Diterima</div>
                    </div>

                    <div class="stat-card animate__animated animate__fadeInUp animate__delay-3s">
                        <div class="stat-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-number">
                            <?php echo $tahunAjaran ? max(0, $tahunAjaran['kuota'] - ($stats['diterima'] ?? 0)) : '0'; ?>
                        </div>
                        <div class="stat-label">Kuota Tersisa</div>
                    </div>
                </div>
            </div>
        </section>

      <!-- Information Section -->
<section id="informasi" class="section">
    <h2 class="section-title">Informasi Penting</h2>

    <div class="info-grid">
        <?php
        $resultInfo->data_seek(0);
        $infoIndex = 0;

        while ($row = $resultInfo->fetch_assoc()):
            $infoIndex++;

            // ICON
            $icons = [
                'jadwal' => 'fa-calendar-alt',
                'persyaratan' => 'fa-file-contract',
                'lokasi' => 'fa-map-marked-alt',
                'alur' => 'fa-sitemap',
                'biaya' => 'fa-money-check-alt',
                'informasi' => 'fa-info-circle',
                'fasilitas' => 'fa-school'
            ];
            $icon = $icons[$row['jenis']] ?? 'fa-info-circle';
        ?>
            <div class="info-card animate__animated animate__fadeInUp"
                 data-animation-delay="<?= $infoIndex * 0.2 ?>">

                <div class="info-icon">
                    <i class="fas <?= $icon ?>"></i>
                </div>

                <h3><?= htmlspecialchars($row['judul']) ?></h3>

                <p>
                    <?= nl2br(htmlspecialchars(
                        substr($row['konten'], 0, 200) .
                        (strlen($row['konten']) > 200 ? '...' : '')
                    )) ?>
                </p>

                <?php if (!empty($row['file'])):
                    $fileUrl = "assets/uploads/informasi/" . htmlspecialchars($row['file']);
                    $fileExt = strtolower(pathinfo($row['file'], PATHINFO_EXTENSION));

                    $isImage = in_array($fileExt, ['jpg','jpeg','png','gif','bmp','webp']);
                    $isPdf   = ($fileExt === 'pdf');
                ?>

                    <?php if ($isImage): ?>
                        <!-- IMAGE PREVIEW -->
                        <div class="image-preview-container"
                             onclick="openModal('<?= $fileUrl ?>','<?= htmlspecialchars($row['judul']) ?>','image','<?= $fileExt ?>')">

                            <img src="<?= $fileUrl ?>"
                                 alt="<?= htmlspecialchars($row['judul']) ?>"
                                 class="image-preview">

                            <div class="preview-overlay">
                                <div class="preview-icon">
                                    <i class="fas fa-search-plus"></i>
                                </div>
                            </div>
                        </div>

                    <?php else: ?>
                        <!-- FILE PREVIEW BUTTON -->
                        <button class="file-preview-btn"
                            onclick="openModal(
                                '<?= $fileUrl ?>',
                                '<?= htmlspecialchars($row['judul']) ?>',
                                '<?= $isPdf ? 'pdf' : 'document' ?>',
                                '<?= $fileExt ?>'
                            )">

                            <i class="fas fa-eye"></i>
                            <?= $isPdf ? 'Preview PDF' : 'Lihat File' ?>
                        </button>
                    <?php endif; ?>

                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    </div>
</section>


        <!-- Pengumuman Section -->
        <section id="pengumuman" class="section">
            <h2 class="section-title">Pengumuman Terbaru</h2>
            <div class="pengumuman-list">
                <?php
                $resultPengumuman->data_seek(0);
                $pengumumanIndex = 0;
                while ($row = $resultPengumuman->fetch_assoc()):
                    $pengumumanIndex++;
                    ?>
                    <div class="pengumuman-card animate__animated animate__fadeInUp"
                        data-animation-delay="<?php echo $pengumumanIndex * 0.2; ?>">
                        <h3><i class="fas fa-bullhorn"></i> <?php echo htmlspecialchars($row['judul']); ?></h3>
                        <p><?php echo nl2br(htmlspecialchars($row['isi'])); ?></p>

                        <?php if ($row['file']):
                            $fileUrl = "assets/uploads/pengumuman/" . htmlspecialchars($row['file']);
                            $fileExt = strtolower(pathinfo($row['file'], PATHINFO_EXTENSION));
                            $isImage = in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']);
                            $isPdf = ($fileExt === 'pdf');
                            ?>
                            <div>
                                <a href="<?php echo $fileUrl; ?>" class="btn-download" download>
                                    <i class="fas fa-download"></i> Download File
                                </a>

                                <button class="file-preview-btn"
                                    onclick="openModal('<?php echo $fileUrl; ?>', '<?php echo htmlspecialchars($row['judul']); ?>', '<?php echo $isImage ? 'image' : ($isPdf ? 'pdf' : 'document'); ?>', '<?php echo $fileExt; ?>')">
                                    <i class="fas fa-eye"></i> Preview File
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        </section>

        <!-- Contact & Map Section -->
        <section id="kontak" class="contact-section animate__animated animate__fadeIn">
            <div class="contact-grid">
                <div class="contact-info">
                    <h3><i class="fas fa-address-book"></i> Hubungi Kami</h3>

                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="contact-text">
                            <h4>Alamat Sekolah</h4>
                            <p><?php echo $profil ? htmlspecialchars($profil['konten']) : 'Desa Pasirpanjang RT.02/RW.01, Kec. Salem, Kab. Brebes, Jawa Tengah'; ?>
                            </p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fab fa-whatsapp"></i>
                        </div>
                        <div class="contact-text">
                            <h4>WhatsApp</h4>
                            <a href="https://wa.me/6282221599435" target="_blank">+62 822-2159-9435</a>
                        </div>
                    </div>

                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="contact-text">
                            <h4>Email</h4>
                            <a href="mailto:info@sdnegeri01.sch.id">info@sdnegeri01.sch.id</a>
                        </div>
                    </div>

                    <div class="social-links">
                        <a href="#" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" title="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" title="YouTube"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>

                <div class="map-container">
                    <!-- Ganti dengan embed Google Maps sesuai alamat sekolah -->
                    <div class="map-placeholder">
                        <div style="text-align: center; padding: 20px;">
                            <i class="fas fa-map-marked-alt fa-3x" style="margin-bottom: 20px;"></i>
                            <p>SD Negeri Pasirpanjang 02<br>Google Maps Location</p>
                            <a href="https://maps.app.goo.gl/bHmNKb3GiBuLGTqR7?g_st=iw" target="_blank"
                                style="display: inline-block; margin-top: 15px; padding: 10px 20px; background: white; color: #667eea; text-decoration: none; border-radius: 25px;">
                                <i class="fas fa-external-link-alt"></i> Buka di Google Maps
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3><i class="fas fa-school"></i> SD Negeri Pasirpanjang 02</h3>
                    <p>NPSN: 20326230</p>
                    <p>Sekolah dasar negeri yang mencetak generasi berakhlak mulia, berprestasi, dan berwawasan tinggi.
                    </p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>

                <div class="footer-section">
                    <h3>Link Cepat</h3>
                    <ul class="footer-links">
                        <li><a href="#home"><i class="fas fa-chevron-right"></i> Beranda</a></li>
                        <li><a href="#informasi"><i class="fas fa-chevron-right"></i> Informasi</a></li>
                        <li><a href="#pengumuman"><i class="fas fa-chevron-right"></i> Pengumuman</a></li>
                        <li><a href="#kontak"><i class="fas fa-chevron-right"></i> Kontak</a></li>
                        <li><a href="login.php"><i class="fas fa-chevron-right"></i> Login Pendaftar</a></li>
                        <li><a href="login_admin.php"><i class="fas fa-chevron-right"></i> Login Admin/Panitia</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h3>Jam Operasional</h3>
                    <p><strong>Senin - Jumat:</strong> 07:00 - 16:00</p>
                    <p><strong>Sabtu:</strong> 08:00 - 14:00</p>
                    <p><strong>Minggu & Hari Libur:</strong> Tutup</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Modal untuk Preview File -->
    <div id="fileModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title" id="modalTitle"></div>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>

            <div id="modalBody">
                <!-- Konten akan diisi oleh JavaScript -->
            </div>

            <div class="modal-nav prev" onclick="navigateModal('prev')" style="display: none;">
                <i class="fas fa-chevron-left"></i>
            </div>
            <div class="modal-nav next" onclick="navigateModal('next')" style="display: none;">
                <i class="fas fa-chevron-right"></i>
            </div>

            <div class="modal-actions" id="modalActions">
                <!-- Tombol aksi akan diisi oleh JavaScript -->
            </div>
        </div>
    </div>

    <!-- WhatsApp Float Button -->
    <a href="https://wa.me/6282221599435" class="whatsapp-float" target="_blank">
        <i class="fab fa-whatsapp"></i>
    </a>

    <script src="assets/js/script.js"></script>
    <script>
        // Data untuk gallery modal
        let modalFiles = [];
        let currentModalIndex = 0;

        // Fungsi untuk membuka modal
        function openModal(fileUrl, title, fileType, fileExt) {
    const modal = document.getElementById('fileModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalBody = document.getElementById('modalBody');
    const modalActions = document.getElementById('modalActions');

    modalTitle.textContent = title;
    modalBody.innerHTML = '';

    /* ===== IMAGE ===== */
    if (fileType === 'image') {
        modalBody.innerHTML = `
            <img src="${fileUrl}" class="modal-image">
        `;
    }

    /* ===== PDF ===== */
    else if (fileExt === 'pdf') {
        modalBody.innerHTML = `
            <iframe src="${fileUrl}" class="modal-pdf"></iframe>
        `;
    }

    /* ===== DOCX / XLS via Google Viewer ===== */
    else if (['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'].includes(fileExt)) {
        modalBody.innerHTML = `
            <iframe
                src="https://docs.google.com/gview?url=${encodeURIComponent(location.origin + '/' + fileUrl)}&embedded=true"
                class="modal-document">
            </iframe>
        `;
    }

    /* ===== OTHER FILE ===== */
    else {
        modalBody.innerHTML = `
            <div style="text-align:center;padding:50px;color:white">
                <i class="fas fa-file fa-5x"></i>
                <h3>Preview tidak tersedia</h3>
                <p>Silakan download file</p>
            </div>
        `;
    }

    modalActions.innerHTML = `
        <button class="modal-btn" onclick="downloadFile('${fileUrl}')">
            <i class="fas fa-download"></i> Download
        </button>
        <button class="modal-btn" onclick="closeModal()">
            <i class="fas fa-times"></i> Tutup
        </button>
    `;

    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';

            // Simpan data file saat ini untuk navigasi
            currentModalIndex = 0;
        }

        // Fungsi untuk membuka modal dengan gallery
        function openModalGallery(files, startIndex) {
            modalFiles = files;
            currentModalIndex = startIndex;

            if (modalFiles.length > 0) {
                const file = modalFiles[currentModalIndex];
                openModal(file.url, file.title, file.type, file.ext);

                // Tampilkan navigasi jika ada lebih dari 1 file
                const prevBtn = document.querySelector('.modal-nav.prev');
                const nextBtn = document.querySelector('.modal-nav.next');

                if (modalFiles.length > 1) {
                    prevBtn.style.display = 'flex';
                    nextBtn.style.display = 'flex';
                }
            }
        }

        // Fungsi untuk navigasi modal gallery
        function navigateModal(direction) {
            if (direction === 'prev') {
                currentModalIndex = (currentModalIndex - 1 + modalFiles.length) % modalFiles.length;
            } else {
                currentModalIndex = (currentModalIndex + 1) % modalFiles.length;
            }

            const file = modalFiles[currentModalIndex];
            openModal(file.url, file.title, file.type, file.ext);
        }

        // Fungsi untuk menutup modal
        function closeModal() {
            const modal = document.getElementById('fileModal');
            const prevBtn = document.querySelector('.modal-nav.prev');
            const nextBtn = document.querySelector('.modal-nav.next');

            modal.style.display = 'none';
            document.body.style.overflow = 'auto';

            // Sembunyikan navigasi
            prevBtn.style.display = 'none';
            nextBtn.style.display = 'none';

            // Reset data
            modalFiles = [];
            currentModalIndex = 0;
        }

        // Fungsi untuk mendownload file
        function downloadFile(fileUrl) {
            const link = document.createElement('a');
            link.href = fileUrl;
            link.download = fileUrl.split('/').pop();
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Fungsi untuk mendapatkan icon berdasarkan ekstensi file
        function getFileIcon(ext) {
            const icons = {
                'pdf': 'pdf',
                'doc': 'word',
                'docx': 'word',
                'xls': 'excel',
                'xlsx': 'excel',
                'ppt': 'powerpoint',
                'pptx': 'powerpoint',
                'txt': 'alt',
                'zip': 'archive',
                'rar': 'archive',
                'jpg': 'image',
                'jpeg': 'image',
                'png': 'image',
                'gif': 'image',
                'mp4': 'video',
                'avi': 'video',
                'mp3': 'audio'
            };
            return icons[ext] || 'file';
        }

        // Tutup modal ketika klik di luar konten
        window.onclick = function (event) {
            const modal = document.getElementById('fileModal');
            if (event.target == modal) {
                closeModal();
            }
        }

        // Tutup modal dengan tombol ESC
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeModal();
            }

            // Navigasi dengan arrow keys
            if (modalFiles.length > 0) {
                if (event.key === 'ArrowLeft') {
                    navigateModal('prev');
                } else if (event.key === 'ArrowRight') {
                    navigateModal('next');
                }
            }
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();

                const targetId = this.getAttribute('href');
                if (targetId === '#') return;

                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 80,
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Animate elements on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const delay = entry.target.getAttribute('data-animation-delay') || 0;
                    setTimeout(() => {
                        entry.target.classList.add('animate__fadeInUp');
                    }, delay * 1000);
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        // Observe all cards
        document.querySelectorAll('.info-card, .pengumuman-card').forEach(card => {
            observer.observe(card);
        });

        // Tahun ajaran countdown
        <?php if ($tahunAjaran && isset($tahunAjaran['tgl_selesai'])): ?>
            function updateCountdown() {
                const endDate = new Date('<?php echo $tahunAjaran['tgl_selesai']; ?>').getTime();
                const now = new Date().getTime();
                const timeLeft = endDate - now;

                if (timeLeft > 0) {
                    const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));

                    const countdownElement = document.getElementById('countdown');
                    if (countdownElement) {
                        countdownElement.innerHTML = `${days} hari ${hours} jam ${minutes} menit lagi`;
                    }
                }
            }

            // Update countdown every minute
            setInterval(updateCountdown, 60000);
            updateCountdown();
        <?php endif; ?>

        // Parallax effect for hero image
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const heroImage = document.querySelector('.hero-image img');
            if (heroImage) {
                heroImage.style.transform = `translateY(${scrolled * 0.05}px) scale(${1 + scrolled * 0.0002})`;
            }
        });
    </script>
    <script>
window.addEventListener('scroll',()=>{
    document.querySelector('.header')
        .classList.toggle('scrolled',window.scrollY>50)
});

// active link
document.querySelectorAll('.nav a').forEach(link=>{
    link.addEventListener('click',()=>{
        document.querySelectorAll('.nav a')
            .forEach(l=>l.classList.remove('active'));
        link.classList.add('active');
    });
});
</script>
<script>
let heroIndex = 0;
const slides = document.querySelectorAll('.hero-slide');

function showHero(n){
    slides.forEach(s=>s.classList.remove('active'));
    slides[n].classList.add('active');
}

function nextHero(){
    heroIndex = (heroIndex+1)%slides.length;
    showHero(heroIndex);
}

function prevHero(){
    heroIndex = (heroIndex-1+slides.length)%slides.length;
    showHero(heroIndex);
}

// auto slide
setInterval(nextHero,5000);
</script>

</body>

</html>