<?php
// Fungsi tambahan untuk sistem

// Generate nomor pendaftaran
function generateNomorPendaftaran($tahunAjaranId, $conn) {
    $prefix = date('Y');
    $sql = "SELECT COUNT(*) as total FROM calon_siswa WHERE YEAR(created_at) = YEAR(CURDATE())";
    $result = $conn->query($sql);
    $data = $result->fetch_assoc();
    
    $sequence = str_pad($data['total'] + 1, 4, '0', STR_PAD_LEFT);
    return "PPDB/{$prefix}/{$sequence}";
}

// Cek kuota tersedia
function cekKuotaTersedia($tahunAjaranId, $conn) {
    $sql = "SELECT 
                ta.kuota,
                (SELECT COUNT(*) FROM calon_siswa cs 
                 WHERE cs.tahun_ajaran_id = ta.id AND cs.status_terima = 'diterima') as terpakai
            FROM tahun_ajaran ta 
            WHERE ta.id = $tahunAjaranId";
    
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        return max(0, $data['kuota'] - $data['terpakai']);
    }
    return 0;
}

// Kirim email notifikasi
function kirimEmail($to, $subject, $message) {
    // Ini adalah implementasi sederhana
    // Dalam produksi, gunakan library seperti PHPMailer
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: PPDB SD <noreply@ppsd.sch.id>' . "\r\n";
    
    return mail($to, $subject, $message, $headers);
}

// Validasi NISN
function validasiNISN($nisn) {
    if (strlen($nisn) != 10) return false;
    if (!is_numeric($nisn)) return false;
    return true;
}

// Format tanggal Indonesia
function tanggalIndo($date, $withTime = false) {
    $hari = array('Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu');
    $bulan = array(
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    );
    
    $timestamp = strtotime($date);
    $hariNum = date('w', $timestamp);
    $tanggal = date('j', $timestamp);
    $bulanNum = date('n', $timestamp);
    $tahun = date('Y', $timestamp);
    $jam = date('H:i', $timestamp);
    
    $result = "{$hari[$hariNum]}, {$tanggal} {$bulan[$bulanNum]} {$tahun}";
    
    if ($withTime) {
        $result .= " pukul {$jam}";
    }
    
    return $result;
}

// Generate QR Code untuk bukti pendaftaran
function generateQRCode($data) {
    // Implementasi sederhana, bisa menggunakan library seperti endroid/qr-code
    $url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($data);
    return $url;
}

// Backup database
function backupDatabase($conn, $backupPath) {
    $tables = array();
    $result = $conn->query("SHOW TABLES");
    
    while($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }
    
    $return = '';
    
    foreach($tables as $table) {
        $result = $conn->query("SELECT * FROM $table");
        $numColumns = $result->field_count;
        
        $return .= "DROP TABLE IF EXISTS $table;";
        
        $result2 = $conn->query("SHOW CREATE TABLE $table");
        $row2 = $result2->fetch_row();
        $return .= "\n\n" . $row2[1] . ";\n\n";
        
        for($i = 0; $i < $numColumns; $i++) {
            while($row = $result->fetch_row()) {
                $return .= "INSERT INTO $table VALUES(";
                for($j = 0; $j < $numColumns; $j++) {
                    $row[$j] = addslashes($row[$j]);
                    $row[$j] = str_replace("\n", "\\n", $row[$j]);
                    if (isset($row[$j])) {
                        $return .= '"' . $row[$j] . '"';
                    } else {
                        $return .= '""';
                    }
                    if ($j < ($numColumns - 1)) {
                        $return .= ',';
                    }
                }
                $return .= ");\n";
            }
        }
        $return .= "\n\n\n";
    }
    
    // Save file
    $handle = fopen($backupPath, 'w+');
    fwrite($handle, $return);
    fclose($handle);
    
    return file_exists($backupPath);
}

// Clean input
function cleanInput($input) {
    $search = array(
        '@<script[^>]*?>.*?</script>@si',
        '@<[\/\!]*?[^<>]*?>@si',
        '@<style[^>]*?>.*?</style>@siU',
        '@<![\s\S]*?--[ \t\n\r]*>@'
    );
    
    $output = preg_replace($search, '', $input);
    return $output;
}

// Sanitize array
function sanitize($input) {
    if (is_array($input)) {
        foreach($input as $var=>$val) {
            $output[$var] = sanitize($val);
        }
    } else {
        $output = cleanInput($input);
    }
    return $output;
}
?>