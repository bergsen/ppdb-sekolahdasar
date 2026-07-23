<?php
require_once '../config/database.php';
redirectIfNotAuthorized(['panitia']);

$file = basename($_GET['file'] ?? '');
if (!$file) {
    die('File tidak valid');
}

$zipPath = realpath(__DIR__ . '/../assets/uploads/' . $file);
if (!$zipPath || !is_file($zipPath)) {
    die('File ZIP tidak ditemukan');
}

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . basename($zipPath) . '"');
header('Content-Length: ' . filesize($zipPath));
header('Pragma: public');
header('Cache-Control: must-revalidate');

readfile($zipPath);
unlink($zipPath); // optional: auto hapus
exit;
