<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_role(['admin', 'kaprodi', 'dosen']);

$pageTitle  = 'Data Proposal';
$activeMenu = 'proposal';
require_once __DIR__ . '/../includes/header.php';

$bisaKelola = in_array($_SESSION['role'], ['admin', 'kaprodi'], true);
$statusList = daftar_status();

// ---------- 1. Ambil nilai pencarian & filter dari URL ----------
$q          = trim($_GET['q'] ?? '');
$prodiId    = (int)($_GET['prodi'] ?? 0);
$status     = $_GET['status'] ?? '';
$halaman    = max(1, (int)($_GET['hal'] ?? 1));
$perHalaman = 5;

// ---------- 2. Susun kondisi WHERE secara dinamis ----------
$where = [];
$param = [];

if ($q !== '') {
    $where[] = "(p.judul LIKE ? OR u.nama LIKE ? OR u.username LIKE ?)";
    $like = '%' . $q . '%';
    array_push($param, $like, $like, $like);
}
if ($prodiId > 0) {
    $where[] = "u.prodi_id = ?";
    $param[] = $prodiId;
}
if (in_array($status, $statusList, true)) {
    $where[] = "p.status = ?";
    $param[] = $status;
} else {
    $status = '';
}
$sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ---------- 3. Hitung total data untuk pagination ----------
$st = $pdo->prepare("SELECT COUNT(*) FROM proposal p JOIN users u ON u.id = p.mahasiswa_id $sqlWhere");
$st->execute($param);
$total      = (int)$st->fetchColumn();
$jumlahHal  = max(1, (int)ceil($total / $perHalaman));
$halaman    = min($halaman, $jumlahHal);
$offset     = ($halaman - 1) * $perHalaman;

// ---------- 4. Ambil data untuk halaman ini ----------
// $perHalaman & $offset sudah pasti angka (int), jadi aman ditulis langsung
$st = $pdo->prepare("
    SELECT p.id, p.judul, p.status, u.nama, u.username AS npm, pr.nama_prodi
    FROM proposal p
    JOIN users u       ON u.id  = p.mahasiswa_id
    LEFT JOIN prodi pr ON pr.id = u.prodi_id
    $sqlWhere
    ORDER BY p.id DESC
    LIMIT $perHalaman OFFSET $offset
");
$st->execute($param);
$data = $st->fetchAll();

$listProdi = $pdo->query("SELECT id, nama_prodi FROM prodi ORDER BY nama_prodi")->fetchAll();

// Helper link pagination: pertahankan pencarian/filter
function link_hal($n) {
    return '?' . http_build_query(array_merge($_GET, ['hal' => $n]));
}
?>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
        <h4 class="fw-bold mb-1">Data Proposal</h4>
        <p class="text-muted mb-0">Kelola seluruh proposal skripsi mahasiswa</p>
    </div>
    <?php if ($bisaKelola): ?>
        <a href="<?= BASE_URL ?>/pages/proposal_form.php" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Ajukan Proposal
        </a>
    <?php endif; ?>
</div>

<?= tampil_flash() ?>

<div class="card-box">
    <!-- PENCARIAN & FILTER -->
    <form method="get" class="row g-2 mb-3">
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" name="q" class="form-control"
                       placeholder="Cari nama, NPM, atau judul..." value="<?= e($q) ?>">
            </div>
        </div>
        <div class="col-6 col-md-2">
            <select name="prodi" class="form-select">
                <option value="0">Semua Prodi</option>
                <?php foreach ($listProdi as $pr): ?>
                    <option value="<?= $pr['id'] ?>" <?= $prodiId === (int)$pr['id'] ? 'selected' : '' ?>>
                        <?= e($pr['nama_prodi']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <select name="status" class="form-select">
                <option value="">Semua Status</option>
                <?php foreach ($statusList as $s): ?>
                    <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button class="btn btn-primary"><i class="bi bi-funnel"></i> Cari</button>
            <a href="<?= BASE_URL ?>/pages/proposal.php" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>

    <!-- TABEL -->
    <div class="table-responsive">
        <table class="table tabel-simple align-middle mb-0">
            <thead>
                <tr><th>No</th><th>Nama</th><th>NPM</th><th>Judul Proposal</th><th>Prodi</th><th>Status</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                <?php if (!$data): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Data tidak ditemukan.</td></tr>
                <?php endif; ?>
                <?php foreach ($data as $i => $p): ?>
                    <tr>
                        <td><?= $offset + $i + 1 ?></td>
                        <td><?= e($p['nama']) ?></td>
                        <td><?= e($p['npm']) ?></td>
                        <td><?= e(mb_strimwidth($p['judul'], 0, 45, '...')) ?></td>
                        <td><?= e($p['nama_prodi']) ?></td>
                        <td><?= badge_status($p['status']) ?></td>
                        <td class="text-nowrap">
                            <a href="<?= BASE_URL ?>/pages/proposal_detail.php?id=<?= $p['id'] ?>"
                               class="btn btn-primary btn-aksi" title="Lihat"><i class="bi bi-eye"></i></a>
                            <?php if ($bisaKelola): ?>
                                <a href="<?= BASE_URL ?>/pages/proposal_form.php?id=<?= $p['id'] ?>"
                                   class="btn btn-success btn-aksi" title="Ubah"><i class="bi bi-pencil"></i></a>
                                <form method="post" action="<?= BASE_URL ?>/pages/proposal_hapus.php"
                                      class="d-inline"
                                      onsubmit="return confirm('Yakin hapus proposal ini? Riwayat status dan bimbingannya ikut terhapus.');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <button class="btn btn-danger btn-aksi" title="Hapus"><i class="bi bi-trash"></i></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- PAGINATION -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
        <small class="text-muted">
            <?php if ($total > 0): ?>
                Menampilkan <?= $offset + 1 ?> - <?= $offset + count($data) ?> dari <?= $total ?> data
            <?php else: ?>
                Menampilkan 0 data
            <?php endif; ?>
        </small>
        <?php if ($jumlahHal > 1): ?>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $halaman <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= e(link_hal($halaman - 1)) ?>">&laquo;</a>
                </li>
                <?php for ($n = 1; $n <= $jumlahHal; $n++): ?>
                    <li class="page-item <?= $n === $halaman ? 'active' : '' ?>">
                        <a class="page-link" href="<?= e(link_hal($n)) ?>"><?= $n ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= $halaman >= $jumlahHal ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= e(link_hal($halaman + 1)) ?>">&raquo;</a>
                </li>
            </ul>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>