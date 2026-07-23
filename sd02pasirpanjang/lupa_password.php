<?php
require_once 'config/database.php';
// Halaman lupa password - langsung redirect ke WhatsApp call center
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - PPDB SDN Pasir Panjang 02</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .wa-section {
            text-align: center;
            padding: 2rem 1rem;
        }
        .wa-icon {
            font-size: 4rem;
            color: #25d366;
            margin-bottom: 1.5rem;
        }
        .wa-section h2 {
            color: #333;
            margin-bottom: 1rem;
        }
        .wa-section p {
            color: #666;
            margin-bottom: 1.5rem;
            line-height: 1.7;
        }
        .wa-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: #25d366;
            color: white;
            padding: 15px 30px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }
        .wa-btn:hover {
            background: #128C7E;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(37, 211, 102, 0.3);
        }
        .wa-steps {
            text-align: left;
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin: 1.5rem 0;
        }
        .wa-steps h4 {
            margin-bottom: 0.75rem;
            color: #333;
        }
        .wa-steps ol {
            padding-left: 1.2rem;
            color: #555;
        }
        .wa-steps ol li {
            margin-bottom: 0.5rem;
            line-height: 1.5;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 1.5rem;
            color: #4a6fa5;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .wa-number {
            font-size: 1.2rem;
            font-weight: 600;
            color: #25d366;
            margin: 0.5rem 0;
        }
    </style>
</head>
<body class="login-page">

<div class="login-container" style="max-width: 520px;">
    <div class="login-header">
        <h1><i class="fas fa-unlock-alt"></i> Lupa Password</h1>
        <p>Reset password melalui WhatsApp</p>
    </div>

    <div class="wa-section">
        <div class="wa-icon">
            <i class="fab fa-whatsapp"></i>
        </div>

        <h2>Hubungi Call Center via WhatsApp</h2>
        <p>
            Untuk keamanan akun Anda, reset password dilakukan melalui WhatsApp.
            Silakan hubungi call center PPDB SDN Pasir Panjang 02.
        </p>

        <div class="wa-number">
            <i class="fas fa-phone-alt"></i> +62 822-2159-9435
        </div>

        <div class="wa-steps">
            <h4><i class="fas fa-list-ol"></i> Langkah-langkah:</h4>
            <ol>
                <li>Klik tombol <strong>"Hubungi via WhatsApp"</strong> di bawah</li>
                <li>Isi data diri Anda (Nama, Username/Email)</li>
                <li>Kirim pesan ke admin</li>
                <li>Admin akan memproses dan mengirimkan password baru</li>
            </ol>
        </div>

        <a href="https://wa.me/6282221599435?text=Halo%20Admin%20PPDB%20SDN%20Pasir%20Panjang%2002%2C%0A%0ASaya%20ingin%20mengajukan%20reset%20password%20akun%20PPDB.%0A%0ABerikut%20data%20akun%20saya%3A%0A-%20Nama%20Lengkap%20%20%20%20%20%20%20%20%3A%20%0A-%20Username%20%20%20%20%20%20%20%20%20%20%20%20%20%3A%20%0A-%20Email%20Terdaftar%20%20%20%20%20%3A%20%0A-%20Nomor%20HP%20Terdaftar%20%3A%20%0A-%20Tahun%20Ajaran%20Terdaftar%20%20%20%20%20%3A%20%0A-%20Saya%20siap%20melakukan%20verifikasi%20data%20jika%20dibutuhkan.%0A%0ATerima%20kasih."
                   target="_blank" class="wa-lupa-password">
                    <i class="fab fa-whatsapp"></i> Hubungi Admin via WhatsApp
                </a>
    </div>

    <a href="login.php" class="back-link">
        <i class="fas fa-arrow-left"></i> Kembali ke Login
    </a>
</div>

</body>
</html>
