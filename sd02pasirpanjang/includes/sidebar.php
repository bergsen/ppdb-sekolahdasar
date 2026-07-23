<?php
$role = $_SESSION['role'] ?? '';

$menuItems = [];

if ($role === 'pendaftar') {
    $menuItems = [
        'dashboard.php' => ['icon' => 'tachometer-alt', 'label' => 'Dashboard'],
        'formulir.php'  => ['icon' => 'file-alt', 'label' => 'Formulir Pendaftaran'],
        'profil.php'    => ['icon' => 'user', 'label' => 'Profil'],
        '../logout.php' => ['icon' => 'sign-out-alt', 'label' => 'Logout']
    ];
} elseif ($role === 'panitia') {
    $menuItems = [
        'dashboard.php' => ['icon' => 'tachometer-alt', 'label' => 'Dashboard'],
        'data_calon.php'=> ['icon' => 'users', 'label' => 'Data Calon'],
        'laporan.php'   => ['icon' => 'file-pdf', 'label' => 'Laporan'],
        'profil.php'    => ['icon' => 'user', 'label' => 'Profil'],
        '../logout.php' => ['icon' => 'sign-out-alt', 'label' => 'Logout']
    ];
} elseif ($role === 'admin') {
    $menuItems = [
        'dashboard.php'        => ['icon' => 'tachometer-alt', 'label' => 'Dashboard'],
        'kelola_akun.php'      => ['icon' => 'user-cog', 'label' => 'Kelola Akun'],
        'kelola_pendaftar.php' => ['icon' => 'user-friends', 'label' => 'Kelola Pendaftar'],
        'kelola_tahun_ajaran.php'=> ['icon' => 'calendar', 'label' => 'Tahun Ajaran'],
        'kelola_kuota.php'     => ['icon' => 'chart-bar', 'label' => 'Kelola Kuota'],
        'kelola_informasi.php' => ['icon' => 'info-circle', 'label' => 'Informasi'],
        'kelola_pengumuman.php'=> ['icon' => 'bullhorn', 'label' => 'Pengumuman'],
        'profil.php'           => ['icon' => 'user', 'label' => 'Profil'],
        '../logout.php'        => ['icon' => 'sign-out-alt', 'label' => 'Logout']
    ];
}

$currentPage = basename($_SERVER['PHP_SELF']);
?>

<style>
/* ===== SIDEBAR BASE ===== */
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 250px;
    height: 100vh;
    transition: width .3s ease;
    z-index: 1000;
}

/* ===== COLLAPSE MODE (ICON ONLY) ===== */
.sidebar.collapsed {
    width: 70px;
}

.sidebar.collapsed .menu-text,
.sidebar.collapsed .sidebar-header h3,
.sidebar.collapsed .sidebar-header p {
    display: none;
}

/* ===== TOGGLE BUTTON ===== */
.sidebar-toggle {
    background: none;
    border: none;
    font-size: 1.4rem;
    cursor: pointer;
    margin-right: .5rem;
}

/* ===== MAIN CONTENT SHIFT ===== */
.main-content {
    margin-left: 250px;
    transition: margin-left .3s ease;
}

.sidebar.collapsed ~ .main-content {
    margin-left: 70px;
}

/* ===== MOBILE: ICON ONLY, NEVER HIDE ===== */
@media (max-width: 768px) {
    .sidebar {
        width: 60px;
        overflow-x: hidden;
    }

    .sidebar .menu-text,
    .sidebar .sidebar-header h3,
    .sidebar .sidebar-header p {
        display: none;
    }

    .sidebar-header {
        padding: 0 0.5rem 1rem;
        text-align: center;
    }

    .sidebar-menu li a {
        padding: 0.8rem;
        justify-content: center;
        gap: 0;
    }

    .sidebar-menu li a i {
        width: auto;
        font-size: 1.1rem;
    }

    .main-content {
        margin-left: 60px;
    }

    /* tombol toggle tidak perlu di mobile */
    .sidebar-toggle {
        display: none;
    }
}
</style>

<nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
        <h3><i class="fas fa-school"></i> PPDB SDN Pasirpanjang02</h3>
        <p><?= ucfirst($role); ?> Panel</p>
    </div>

    <ul class="sidebar-menu">
        <?php foreach ($menuItems as $page => $item): ?>
        <li class="<?= ($currentPage === $page) ? 'active' : ''; ?>">
            <a href="<?= $page; ?>">
                <i class="fas fa-<?= $item['icon']; ?>"></i>
                <span class="menu-text"><?= $item['label']; ?></span>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
</nav>

<script>
document.getElementById('sidebarToggle')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('collapsed');
});
</script>
