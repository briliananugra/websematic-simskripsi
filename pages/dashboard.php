<?php
$pageTitle  = 'Dashboard';
$activeMenu = 'dashboard';
require_once __DIR__ . '/../includes/header.php';

// Uji koneksi: hitung jumlah user dan proposal
$totalUser     = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalProposal = $pdo->query("SELECT COUNT(*) FROM proposal")->fetchColumn();
?>

<h4 class="fw-bold mb-1">Dashboard</h4>
<p class="text-muted">Selamat datang di Sistem Informasi Skripsi</p>

<div class="alert alert-success">
    ✅ Koneksi database berhasil.
    Jumlah user: <b><?= e($totalUser) ?></b>,
    jumlah proposal: <b><?= e($totalProposal) ?></b>
</div>

<p>Contoh label status:</p>
<?= badge_status('diajukan') ?>
<?= badge_status('disetujui') ?>
<?= badge_status('bimbingan') ?>
<?= badge_status('ditolak') ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>