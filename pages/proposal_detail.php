<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
wajib_login();

// ---------- 1. Ambil proposal + cek hak akses (sebelum ada HTML) ----------
$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare("
    SELECT p.*, u.nama, u.username AS npm, pr.nama_prodi, d.nama AS nama_dosen
    FROM proposal p
    JOIN users u       ON u.id  = p.mahasiswa_id
    LEFT JOIN prodi pr ON pr.id = u.prodi_id
    LEFT JOIN users d  ON d.id  = p.dosen_id
    WHERE p.id = ?
");
$st->execute([$id]);
$p = $st->fetch();

if (!$p) {
    set_flash('danger', 'Proposal tidak ditemukan.');
    header('Location: ' . BASE_URL . '/pages/proposal.php');
    exit;
}
if (!proposal_boleh_dilihat($p)) {
    http_response_code(403);
    die('Akses ditolak. <a href="' . BASE_URL . '/auth/logout.php">Keluar &amp; ganti akun</a>');
}

// ---------- 2. Ambil riwayat & bimbingan ----------
$st = $pdo->prepare("SELECT status, tanggal FROM status_log WHERE proposal_id = ? ORDER BY tanggal ASC, id ASC");
$st->execute([$id]);
$riwayat = $st->fetchAll();

$st = $pdo->prepare("SELECT * FROM bimbingan WHERE proposal_id = ? ORDER BY tanggal ASC, id ASC");
$st->execute([$id]);
$listBimbingan = $st->fetchAll();

// ---------- 3. Susun timeline ----------
$tahap  = ['diajukan', 'disetujui', 'bimbingan', 'seminar', 'sidang', 'selesai'];
$tglLog = [];
foreach ($riwayat as $r) { $tglLog[$r['status']] = $r['tanggal']; } // yang terakhir menimpa

$timeline = [];
if ($p['status'] === 'ditolak') {
    $timeline[] = ['Diajukan', 'done',   $tglLog['diajukan'] ?? null];
    $timeline[] = ['Ditolak',  'reject', $tglLog['ditolak']  ?? null];
} else {
    $idxNow = array_search($p['status'], $tahap, true);
    foreach ($tahap as $i => $s) {
        $state = $i < $idxNow ? 'done' : ($i === $idxNow ? 'now' : 'wait');
        $timeline[] = [ucfirst($s), $state, $tglLog[$s] ?? null];
    }
}

$bisaTambah = bimbingan_boleh_tambah($p);
$bisaHapus  = bimbingan_boleh_hapus($p);
$bisaEdit   = in_array($_SESSION['role'], ['admin', 'kaprodi'], true);
$urlKembali = $_SESSION['role'] === 'mahasiswa'
    ? BASE_URL . '/pages/dashboard_mahasiswa.php'   // dibuat di CP7
    : BASE_URL . '/pages/proposal.php';

$pageTitle  = 'Detail Proposal';
$activeMenu = 'proposal';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
        <h4 class="fw-bold mb-1">Detail Proposal</h4>
        <p class="text-muted mb-0">Proposal &rsaquo; Detail Proposal</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= $urlKembali ?>" class="btn btn-primary btn-sm"><i class="bi bi-arrow-left"></i> Kembali</a>
        <?php if ($bisaEdit): ?>
            <a href="<?= BASE_URL ?>/pages/proposal_form.php?id=<?= $p['id'] ?>" class="btn btn-success btn-sm">
                <i class="bi bi-pencil"></i> Edit Data
            </a>
        <?php endif; ?>
    </div>
</div>

<?= tampil_flash() ?>

<div class="row g-3 mb-3">
    <!-- INFO MAHASISWA -->
    <div class="col-lg-7">
        <div class="card-box">
            <h6>Informasi Mahasiswa</h6>
            <table class="info-tabel">
                <tr><td>Nama</td><td>: <?= e($p['nama']) ?></td></tr>
                <tr><td>NPM</td><td>: <?= e($p['npm']) ?></td></tr>
                <tr><td>Program Studi</td><td>: <?= e($p['nama_prodi']) ?></td></tr>
                <tr><td>Judul Proposal</td><td>: <?= e($p['judul']) ?></td></tr>
                <tr><td>Dosen Pembimbing</td><td>: <?= e($p['nama_dosen'] ?? 'Belum ditentukan') ?></td></tr>
                <tr><td>Tanggal Pengajuan</td><td>: <?= e(tgl($p['tanggal_pengajuan'])) ?></td></tr>
                <tr><td>Status</td><td>: <?= badge_status($p['status']) ?></td></tr>
            </table>
        </div>
    </div>

    <!-- TIMELINE STATUS -->
    <div class="col-lg-5">
        <div class="card-box">
            <h6>Status Proposal</h6>
            <ul class="timeline-v">
                <?php foreach ($timeline as [$label, $state, $tanggal]): ?>
                    <li class="tl-<?= $state ?>">
                        <span class="tl-dot">
                            <?php if ($state === 'done'): ?><i class="bi bi-check"></i>
                            <?php elseif ($state === 'reject'): ?><i class="bi bi-x"></i>
                            <?php endif; ?>
                        </span>
                        <div>
                            <b><?= e($label) ?></b>
                            <?php if ($state === 'now'): ?><small class="text-muted">(tahap sekarang)</small><?php endif; ?>
                            <?php if ($tanggal): ?><br><small class="text-muted"><?= e(tgl($tanggal)) ?></small><?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<!-- TAB -->
<div class="card-box">
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-bimbingan" type="button">Bimbingan</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-riwayat" type="button">Riwayat</button></li>
    </ul>

    <div class="tab-content">
        <!-- TAB BIMBINGAN -->
        <div class="tab-pane fade show active" id="tab-bimbingan">
            <div class="table-responsive">
                <table class="table tabel-simple align-middle mb-0">
                    <thead><tr><th>No</th><th>Tanggal</th><th>Catatan Bimbingan</th><th>File</th><?php if ($bisaHapus): ?><th>Aksi</th><?php endif; ?></tr></thead>
                    <tbody>
                        <?php if (!$listBimbingan): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">Belum ada catatan bimbingan.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($listBimbingan as $i => $b): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td class="text-nowrap"><?= e(tgl($b['tanggal'])) ?></td>
                                <td><?= nl2br(e($b['catatan'])) ?></td>
                                <td>
                                    <?php if ($b['nama_file']): ?>
                                        <a href="<?= BASE_URL ?>/pages/bimbingan_unduh.php?id=<?= $b['id'] ?>" class="text-decoration-none">
                                            <i class="bi bi-download"></i> <?= e(nama_file_tampil($b['nama_file'])) ?>
                                        </a>
                                    <?php else: ?>-<?php endif; ?>
                                </td>
                                <?php if ($bisaHapus): ?>
                                    <td>
                                        <form method="post" action="<?= BASE_URL ?>/pages/bimbingan_hapus.php" class="d-inline"
                                              onsubmit="return confirm('Hapus catatan bimbingan ini?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                            <button class="btn btn-danger btn-aksi" title="Hapus"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($bisaTambah): ?>
                <button class="btn btn-primary btn-sm mt-3" data-bs-toggle="modal" data-bs-target="#modalBimbingan">
                    <i class="bi bi-plus-lg"></i> Tambah Catatan Bimbingan
                </button>
            <?php endif; ?>
        </div>

        <!-- TAB RIWAYAT -->
        <div class="tab-pane fade" id="tab-riwayat">
            <div class="table-responsive">
                <table class="table tabel-simple mb-0">
                    <thead><tr><th>No</th><th>Status</th><th>Tanggal</th></tr></thead>
                    <tbody>
                        <?php foreach ($riwayat as $i => $r): ?>
                            <tr><td><?= $i + 1 ?></td><td><?= badge_status($r['status']) ?></td><td><?= e(tgl($r['tanggal'])) ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL TAMBAH BIMBINGAN -->
<?php if ($bisaTambah): ?>
<div class="modal fade" id="modalBimbingan" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="post" enctype="multipart/form-data"
              action="<?= BASE_URL ?>/pages/bimbingan_simpan.php">
            <?= csrf_field() ?>
            <input type="hidden" name="proposal_id" value="<?= $p['id'] ?>">
            <div class="modal-header">
                <h6 class="modal-title fw-bold">Tambah Catatan Bimbingan</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Tanggal</label>
                    <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Catatan</label>
                    <textarea name="catatan" class="form-control" rows="4" maxlength="2000" required></textarea>
                </div>
                <div>
                    <label class="form-label fw-semibold">File (opsional)</label>
                    <input type="file" name="file" class="form-control" accept=".pdf,.doc,.docx">
                    <small class="text-muted">PDF, DOC, atau DOCX. Maksimal 2 MB.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>