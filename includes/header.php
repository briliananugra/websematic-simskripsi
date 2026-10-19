<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
wajib_login(); // semua halaman yang memakai header otomatis terkunci

$pageTitle  = $pageTitle  ?? 'SIMSKRIPSI';
$activeMenu = $activeMenu ?? '';

// Data pengguna asli dari session
$currentUser = ['nama' => $_SESSION['nama'], 'role' => ucfirst($_SESSION['role'])];

// Daftar menu sidebar: [kunci, label, ikon, alamat]
$menu = [
    ['dashboard', 'Dashboard',        'bi-speedometer2',  '/pages/dashboard.php'],
    ['proposal',  'Proposal',         'bi-file-earmark-text', '/pages/proposal.php'],
    ['bimbingan', 'Bimbingan',        'bi-chat-left-text', '/pages/bimbingan.php'],
    ['seminar',   'Seminar',          'bi-easel',         '#'],
    ['sidang',    'Sidang',           'bi-mortarboard',   '#'],
    ['mahasiswa', 'Mahasiswa',        'bi-people',        '#'],
    ['dosen',     'Dosen Pembimbing', 'bi-person-badge',  '#'],
    ['jadwal',    'Jadwal',           'bi-calendar-event','#'],
    ['laporan',   'Laporan',          'bi-bar-chart',     '#'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="app">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <img src="<?= BASE_URL ?>/assets/img/logo_umb.png" alt="Logo UMB">
            <span>SIMSKRIPSI<br><small>UMB</small></span>
        </div>
        <nav class="sidebar-menu">
            <?php foreach ($menu as $m): ?>
                <a href="<?= $m[3] === '#' ? '#' : BASE_URL . $m[3] ?>"
                   class="<?= $activeMenu === $m[0] ? 'active' : '' ?>">
                    <i class="bi <?= $m[2] ?>"></i> <?= $m[1] ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </aside>

    <!-- AREA UTAMA -->
    <div class="main">
        <header class="topbar">
            <div class="ms-auto d-flex align-items-center gap-3">
                <i class="bi bi-bell fs-5"></i>
                <div class="text-end lh-sm">
                    <div class="fw-semibold"><?= e($currentUser['nama']) ?></div>
                    <small class="text-muted"><?= e($currentUser['role']) ?></small>
                </div>
                <i class="bi bi-person-circle fs-2 text-secondary"></i>
                <a href="<?= BASE_URL ?>/auth/logout.php" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-box-arrow-right"></i> Keluar
                </a>
            </div>
        </header>

        <main class="content">