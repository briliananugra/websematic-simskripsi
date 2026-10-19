<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
wajib_role(['admin', 'kaprodi']);

$id         = (int)($_GET['id'] ?? 0);
$edit       = $id > 0;
$statusList = daftar_status();

// Nilai awal form (untuk mode tambah)
$data = [
    'mahasiswa_id'      => '',
    'dosen_id'          => '',
    'judul'             => '',
    'status'            => 'diajukan',
    'tanggal_pengajuan' => date('Y-m-d'),
];

// Mode ubah: ambil data lama dari database
if ($edit) {
    $st = $pdo->prepare("SELECT * FROM proposal WHERE id = ?");
    $st->execute([$id]);
    $row = $st->fetch();
    if (!$row) {
        set_flash('danger', 'Proposal tidak ditemukan.');
        header('Location: ' . BASE_URL . '/pages/proposal.php');
        exit;
    }
    $data = $row;
}
$statusLama = $data['status'];
$errors     = [];

// ---------- Proses form saat disubmit ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();

    $data['mahasiswa_id']      = (int)($_POST['mahasiswa_id'] ?? 0);
    $data['dosen_id']          = ($_POST['dosen_id'] ?? '') !== '' ? (int)$_POST['dosen_id'] : null;
    $data['judul']             = trim($_POST['judul'] ?? '');
    $data['status']            = $_POST['status'] ?? '';
    $data['tanggal_pengajuan'] = $_POST['tanggal_pengajuan'] ?? '';

    // Validasi
    $cek = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id = ? AND role = ?");

    $cek->execute([$data['mahasiswa_id'], 'mahasiswa']);
    if (!$cek->fetchColumn()) {
        $errors[] = 'Mahasiswa wajib dipilih.';
    }
    if ($data['dosen_id'] !== null) {
        $cek->execute([$data['dosen_id'], 'dosen']);
        if (!$cek->fetchColumn()) {
            $errors[] = 'Dosen pembimbing tidak valid.';
        }
    }
    if (mb_strlen($data['judul']) < 10) {
        $errors[] = 'Judul minimal 10 karakter.';
    }
    if (mb_strlen($data['judul']) > 255) {
        $errors[] = 'Judul maksimal 255 karakter.';
    }
    if (!in_array($data['status'], $statusList, true)) {
        $errors[] = 'Status tidak valid.';
    }
    $tgl = DateTime::createFromFormat('Y-m-d', $data['tanggal_pengajuan']);
    if (!$tgl || $tgl->format('Y-m-d') !== $data['tanggal_pengajuan']) {
        $errors[] = 'Tanggal pengajuan tidak valid.';
    }

    // Simpan kalau tidak ada error
    if (!$errors) {
        if ($edit) {
            $st = $pdo->prepare("UPDATE proposal
                SET mahasiswa_id = ?, dosen_id = ?, judul = ?, status = ?, tanggal_pengajuan = ?
                WHERE id = ?");
            $st->execute([$data['mahasiswa_id'], $data['dosen_id'], $data['judul'],
                          $data['status'], $data['tanggal_pengajuan'], $id]);

            // Status berubah -> catat di riwayat (muncul di Aktivitas Terbaru)
            if ($data['status'] !== $statusLama) {
                $pdo->prepare("INSERT INTO status_log (proposal_id, status, tanggal) VALUES (?,?,?)")
                    ->execute([$id, $data['status'], date('Y-m-d')]);
            }
            set_flash('success', 'Proposal berhasil diubah.');
        } else {
            $st = $pdo->prepare("INSERT INTO proposal
                (mahasiswa_id, dosen_id, judul, status, tanggal_pengajuan) VALUES (?,?,?,?,?)");
            $st->execute([$data['mahasiswa_id'], $data['dosen_id'], $data['judul'],
                          $data['status'], $data['tanggal_pengajuan']]);
            $baruId = $pdo->lastInsertId();

            $pdo->prepare("INSERT INTO status_log (proposal_id, status, tanggal) VALUES (?,?,?)")
                ->execute([$baruId, $data['status'], $data['tanggal_pengajuan']]);
            set_flash('success', 'Proposal berhasil ditambahkan.');
        }
        header('Location: ' . BASE_URL . '/pages/proposal.php');
        exit;
    }
}

// Isi dropdown
$listMhs   = $pdo->query("SELECT id, username, nama FROM users WHERE role = 'mahasiswa' ORDER BY nama")->fetchAll();
$listDosen = $pdo->query("SELECT id, nama FROM users WHERE role = 'dosen' ORDER BY nama")->fetchAll();

$pageTitle  = $edit ? 'Ubah Proposal' : 'Ajukan Proposal';
$activeMenu = 'proposal';
require_once __DIR__ . '/../includes/header.php';
?>

<h4 class="fw-bold mb-1"><?= e($pageTitle) ?></h4>
<p class="text-muted">
    <a href="<?= BASE_URL ?>/pages/proposal.php" class="text-decoration-none">Proposal</a> &rsaquo; <?= e($pageTitle) ?>
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
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold">Mahasiswa</label>
                <select name="mahasiswa_id" class="form-select" required>
                    <option value="">-- Pilih mahasiswa --</option>
                    <?php foreach ($listMhs as $m): ?>
                        <option value="<?= $m['id'] ?>" <?= (int)$data['mahasiswa_id'] === (int)$m['id'] ? 'selected' : '' ?>>
                            <?= e($m['username'] . ' - ' . $m['nama']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Dosen Pembimbing</label>
                <select name="dosen_id" class="form-select">
                    <option value="">-- Belum ditentukan --</option>
                    <?php foreach ($listDosen as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= (int)$data['dosen_id'] === (int)$d['id'] ? 'selected' : '' ?>>
                            <?= e($d['nama']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12">
                <label class="form-label fw-semibold">Judul Proposal</label>
                <textarea name="judul" class="form-control" rows="3" maxlength="255" required><?= e($data['judul']) ?></textarea>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Status</label>
                <select name="status" class="form-select" required>
                    <?php foreach ($statusList as $s): ?>
                        <option value="<?= $s ?>" <?= $data['status'] === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Tanggal Pengajuan</label>
                <input type="date" name="tanggal_pengajuan" class="form-control"
                       value="<?= e($data['tanggal_pengajuan']) ?>" required>
            </div>
        </div>

        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan</button>
            <a href="<?= BASE_URL ?>/pages/proposal.php" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>