<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_role(['mahasiswa']);

$pageTitle  = 'Dashboard Mahasiswa';
$activeMenu = 'dashboard';
require_once __DIR__ . '/../includes/header.php';

// ---------- 1. Proposal terakhir milik mahasiswa yang login ----------
$st = $pdo->prepare("
    SELECT p.*, pr.nama_prodi, d.nama AS nama_dosen
    FROM proposal p
    JOIN users u       ON u.id  = p.mahasiswa_id
    LEFT JOIN prodi pr ON pr.id = u.prodi_id
    LEFT JOIN users d  ON d.id  = p.dosen_id
    WHERE p.mahasiswa_id = ?
    ORDER BY p.id DESC
    LIMIT 1
");
$st->execute([$_SESSION['user_id']]);
$p = $st->fetch();

$bisaAjukan = !$p || $p['status'] === 'ditolak';

// ---------- 2. Susun stepper (kalau proposal ada dan tidak ditolak) ----------
$stepper = [];
if ($p && $p['status'] !== 'ditolak') {
    $st = $pdo->prepare("SELECT status, tanggal FROM status_log WHERE proposal_id = ? ORDER BY tanggal ASC, id ASC");
    $st->execute([$p['id']]);
    $tglLog = [];
    foreach ($st->fetchAll() as $r) { $tglLog[$r['status']] = $r['tanggal']; }

    $tahap  = ['diajukan', 'disetujui', 'bimbingan', 'seminar', 'sidang', 'selesai'];
    $idxNow = array_search($p['status'], $tahap, true);
    foreach ($tahap as $i => $s) {
        $state     = $i < $idxNow ? 'done' : ($i === $idxNow ? 'now' : 'wait');
        $stepper[] = [ucfirst($s), $state, $tglLog[$s] ?? null];
    }
}
?>

<h4 class="fw-bold mb-1">Dashboard Mahasiswa</h4>
<p class="text-muted">Selamat datang, <?= e($_SESSION['nama']) ?></p>

<?= tampil_flash() ?>

<?php if (!$p): ?>
    <!-- BELUM PERNAH MENGAJUKAN -->
    <div class="card-box text-center py-5">
        <i class="bi bi-file-earmark-plus fs-1 text-primary"></i>
        <h5 class="fw-bold mt-2">Kamu belum mengajukan proposal</h5>
        <p class="text-muted">Mulai proses skripsimu dengan mengajukan judul proposal.</p>
        <a href="<?= BASE_URL ?>/pages/proposal_ajukan.php" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Ajukan Proposal
        </a>
    </div>

<?php else: ?>

    <?php if ($p['status'] === 'ditolak'): ?>
        <div class="alert alert-danger">
            <b>Proposalmu ditolak.</b> Kamu bisa mengajukan judul baru.
            <a href="<?= BASE_URL ?>/pages/proposal_ajukan.php" class="alert-link">Ajukan proposal baru</a>
        </div>
    <?php else: ?>
        <!-- STEPPER -->
        <div class="card-box mb-3">
            <h6>Status Skripsi Saya</h6>
            <div class="stepper">
                <?php foreach ($stepper as [$label, $state, $tanggal]): ?>
                    <div class="step <?= $state ?>">
                        <div class="step-dot">
                            <?php if ($state === 'done'): ?><i class="bi bi-check-lg"></i>
                            <?php elseif ($state === 'now'): ?><i class="bi bi-circle-fill" style="font-size:.5rem"></i>
                            <?php endif; ?>
                        </div>
                        <b><?= e($label) ?></b><br>
                        <small class="text-muted"><?= $tanggal ? e(tgl($tanggal)) : '&nbsp;' ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-3">
        <!-- INFORMASI PROPOSAL -->
        <div class="col-lg-7">
            <div class="card-box">
                <h6>Informasi Proposal</h6>
                <table class="info-tabel">
                    <tr><td>Judul</td><td>: <?= e($p['judul']) ?></td></tr>
                    <tr><td>Dosen Pembimbing</td><td>: <?= e($p['nama_dosen'] ?? 'Belum ditentukan') ?></td></tr>
                    <tr><td>Program Studi</td><td>: <?= e($p['nama_prodi']) ?></td></tr>
                    <tr><td>Tanggal Pengajuan</td><td>: <?= e(tgl($p['tanggal_pengajuan'])) ?></td></tr>
                    <tr><td>Status</td><td>: <?= badge_status($p['status']) ?></td></tr>
                </table>
            </div>
        </div>

        <!-- MENU CEPAT -->
        <div class="col-lg-5">
            <div class="card-box menu-cepat">
                <h6>Menu Cepat</h6>
                <a href="<?= BASE_URL ?>/pages/proposal_detail.php?id=<?= $p['id'] ?>">
                    <i class="bi bi-eye"></i> Lihat Proposal Saya
                </a>
                <a href="<?= BASE_URL ?>/pages/proposal_detail.php?id=<?= $p['id'] ?>">
                    <i class="bi bi-chat-left-text"></i> Catatan Bimbingan
                </a>
                <?php if ($bisaAjukan): ?>
                    <a href="<?= BASE_URL ?>/pages/proposal_ajukan.php">
                        <i class="bi bi-plus-lg"></i> Ajukan Proposal Baru
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>