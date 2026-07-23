<?php
session_start();

class Database {
    private $host = "localhost";
    private $user = "root";
    private $pass = "";
    private $dbname = "ppdb";
    private $conn;
    
    public function __construct() {
        try {
            $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->dbname);
            
            if ($this->conn->connect_error) {
                throw new Exception("Koneksi gagal: " . $this->conn->connect_error);
            }
            
            $this->conn->set_charset("utf8");
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    public function escapeString($string) {
        return $this->conn->real_escape_string($string);
    }
}

// Fungsi helper
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit();
    }
}

function getUserRole() {
    return $_SESSION['role'] ?? null;
}

function redirectIfNotAuthorized($allowedRoles) {
    if (!isLoggedIn()) {
        // Redirect admin/panitia ke login khusus
        if (in_array('admin', $allowedRoles) || in_array('panitia', $allowedRoles)) {
            header("Location: ../login_admin.php");
        } else {
            header("Location: ../login.php");
        }
        exit();
    }

    if (!in_array($_SESSION['role'], $allowedRoles)) {
        header("Location: ../unauthorized.php");
        exit();
    }
}

// Hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Upload file - DIPERBAIKI
function uploadFile($file, $folder, $prefix = '') {
    // Path upload relatif dari config/database.php
    $baseUploadDir = dirname(__DIR__) . "/assets/uploads/";
    $targetDir = $baseUploadDir . $folder . "/";
    
    // Buat folder jika belum ada
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    // Bersihkan nama file
    $originalName = basename($file["name"]);
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    
    // Generate nama file unik
    $cleanPrefix = preg_replace('/[^a-zA-Z0-9_-]/', '', $prefix);
    $fileName = ($cleanPrefix ? $cleanPrefix . '_' : '') . uniqid() . '.' . $extension;
    $targetFile = $targetDir . $fileName;
    
    // Validasi ukuran file (max 5MB)
    if ($file["size"] > 5000000) {
        return ['error' => 'Ukuran file terlalu besar. Maksimal 5MB'];
    }
    
    // Validasi tipe file
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
    $fileExtension = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    
    if (!in_array($fileExtension, $allowedExtensions)) {
        return ['error' => 'Format file tidak didukung. Gunakan JPG, PNG, GIF, PDF, DOC, DOCX'];
    }
    
    // Validasi khusus untuk gambar
    if (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif'])) {
        $check = getimagesize($file["tmp_name"]);
        if ($check === false) {
            return ['error' => 'File bukan gambar yang valid'];
        }
    }
    
    // Coba upload
    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        return [
            'success' => true, 
            'file_name' => $fileName,
            'file_path' => $folder . '/' . $fileName
        ];
    }
    
    return ['error' => 'Gagal mengupload file. Error: ' . $file['error']];
}

// Fungsi untuk membuat path relatif untuk akses file
function getFilePath($folder, $fileName) {
    return "assets/uploads/{$folder}/{$fileName}";
}

// Cek apakah ZIP extension tersedia
?>