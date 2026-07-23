<?php
session_start();

// Simpan role sebelum session dihancurkan
$role = $_SESSION['role'] ?? 'pendaftar';

// Hapus session
session_unset();
session_destroy();

// Redirect berdasarkan role
if ($role === 'admin' || $role === 'panitia') {

    header("Location: /sd02pasirpanjang/admin/area/login_admin.php");

} else {

    header("Location: index.php");
}

exit();
?>