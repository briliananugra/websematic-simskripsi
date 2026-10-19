<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
wajib_role(['mahasiswa']);

$uid = (int)$_SESSION['user_id'];

// ---------- Aturan: hanya boleh punya satu proposal aktif ----------
$st = $pdo->prepare("SELECT status FROM proposal WHERE mahasiswa_id = ? ORDER BY id DESC LIMIT 1");
$st->execute([$uid]);
$terakhir = $st->fetchColumn(); // false kalau belum pernah mengajukan

if ($terakhir !== false && $terakhir !== 'ditolak') {
    set_flash('warning', 'Kamu masih punya proposal aktif, jadi belum bisa mengajukan yang baru.');
    header('Location: ' . BASE_URL . '/pages/dashboard_mahasiswa.php');
    exit;
}

$judul  = '';
$errors = [];

// ---------- Proses form ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    $judul = trim($_POST['judul'] ?? '');

    if (mb_strlen($judul) < 10)  $errors[] = 'Judul minimal 10 karakter.';
    if (mb_strlen($judul) > 255) $errors[] = 'Judul maksimal 255 karakter.';

    if (!$errors) {
        $hari = date('Y-m-d');
        $pdo->prepare("INSERT INTO proposal (mahasiswa_id, dosen_id, judul, status, tanggal_pengajuan)
                       VALUES (?, NULL, ?, 'diajukan', ?)")
            ->execute([$uid, $judul, $hari]);
        $baruId = $pdo->lastInsertId();

        $pdo->prepare("INSERT INTO status_log (proposal_id, status, tanggal) VALUES (?, 'diajukan', ?)")
            ->execute([$baruId, $hari]);

        set_flash('success', 'Proposal berhasil diajukan. Tunggu persetujuan dari prodi.');
        header('Location: ' . BASE_URL . '/pages/dashboard_mahasiswa.php');
        exit;
    }
}

$pageTitle  = 'Ajukan Proposal';
$activeMenu = 'proposal';
require_once __DIR__ . '/../includes/header.php';
?>

<h4 class="fw-bold mb-1">Ajukan Proposal</h4>
<p class="text-muted">
    <a href="<?= BASE_URL ?>/pages/dashboard_mahasiswa.php" class="text-decoration-none">Dashboard</a> &rsaquo; Ajukan Proposal
</p>

<?php if ($errors): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card-box">
    <form method="post">
        <?= csrf_field() ?>
        <label class="form-label fw-semibold">Judul Proposal</label>
        <textarea name="judul" class="form-control" rows="3" maxlength="255" required
                  placeholder="Tulis judul skripsi yang kamu usulkan"><?= e($judul) ?></textarea>
        <div class="form-text">Status awal otomatis <b>Diajukan</b>, dosen pembimbing ditentukan oleh prodi.</div>

        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i> Ajukan</button>
            <a href="<?= BASE_URL ?>/pages/dashboard_mahasiswa.php" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>