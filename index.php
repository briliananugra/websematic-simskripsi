<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$alur = [
    ['bi-file-earmark-arrow-up', 'Pengajuan'],
    ['bi-patch-check',           'Persetujuan Judul'],
    ['bi-chat-left-text',        'Bimbingan'],
    ['bi-easel',                 'Seminar Proposal'],
    ['bi-mortarboard',           'Sidang Skripsi'],
    ['bi-award',                 'Yudisium'],
];

$fitur = [
    ['bi-file-earmark-text', 'Pengajuan Proposal Online'],
    ['bi-journal-check',     'Bimbingan Terstruktur'],
    ['bi-graph-up-arrow',    'Monitoring Progress'],
    ['bi-folder-check',      'Dokumen Terintegrasi'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= APP_NAME ?> - Sistem Informasi Skripsi Mahasiswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/landing.css" rel="stylesheet">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-md bg-white shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="#">
            <img src="<?= BASE_URL ?>/assets/img/logo_umb.png" alt="Logo UMB" height="40">
            <span class="lh-1"><strong>SIMSKRIPSI</strong><br>
                <small class="text-muted" style="font-size:.65rem">Universitas Muhammadiyah Bengkulu</small></span>
        </a>
        <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#menuNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="menuNav">
            <ul class="navbar-nav ms-auto align-items-md-center gap-md-2">
                <li class="nav-item"><a class="nav-link" href="#beranda">Beranda</a></li>
                <li class="nav-item"><a class="nav-link" href="#alur">Informasi</a></li>
                <li class="nav-item"><a class="nav-link" href="#alur">Panduan</a></li>
                <li class="nav-item"><a class="nav-link" href="#footer">Kontak</a></li>
                <li class="nav-item">
                    <?php if (sudah_login()): ?>
                        <a class="btn btn-primary btn-sm px-3" href="<?= url_dashboard() ?>">Dashboard</a>
                    <?php else: ?>
                        <a class="btn btn-primary btn-sm px-3" href="<?= BASE_URL ?>/auth/login.php">Login</a>
                    <?php endif; ?>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- HERO -->
<section class="hero" id="beranda">
    <div class="container">
        <h1 class="display-5 fw-bold">Sistem Informasi<br>Skripsi Mahasiswa</h1>
        <p class="lead col-md-6 px-0">
            Mengelola proses pengajuan, bimbingan, seminar hingga yudisium
            secara online, cepat, transparan dan terintegrasi.
        </p>
        <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-primary btn-lg">
            Login ke Sistem <i class="bi bi-arrow-right"></i>
        </a>
    </div>
</section>

<!-- KARTU FITUR -->
<section class="container fitur-wrap">
    <div class="row g-3">
        <?php foreach ($fitur as $f): ?>
            <div class="col-6 col-md-3">
                <div class="fitur-card">
                    <i class="bi <?= $f[0] ?>"></i>
                    <div><?= $f[1] ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- ALUR PROSES -->
<section class="container py-5" id="alur">
    <h3 class="text-center fw-bold mb-4">Alur Proses Skripsi</h3>
    <div class="row text-center g-3">
        <?php foreach ($alur as $i => $a): ?>
            <div class="col-4 col-md-2">
                <div class="alur-ikon"><i class="bi <?= $a[0] ?>"></i></div>
                <div class="small mt-2"><b><?= $i + 1 ?></b><br><?= $a[1] ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<footer class="text-center text-muted small py-4 border-top bg-white" id="footer">
    &copy; <?= date('Y') ?> SIMSKRIPSI - Universitas Muhammadiyah Bengkulu
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>