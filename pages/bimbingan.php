<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_role(['admin', 'kaprodi', 'dosen']);

$pageTitle  = 'Bimbingan';
$activeMenu = 'bimbingan';
require_once __DIR__ . '/../includes/header.php';

// Dosen hanya melihat bimbingannya sendiri; admin & kaprodi melihat semua
$sql = "
    SELECT b.tanggal, b.catatan, b.nama_file, p.id AS proposal_id, p.judul, u.nama
    FROM bimbingan b
    JOIN proposal p ON p.id = b.proposal_id
    JOIN users u    ON u.id = p.mahasiswa_id
";
$param = [];
if ($_SESSION['role'] === 'dosen') {
    $sql .= " WHERE p.dosen_id = ?";
    $param[] = $_SESSION['user_id'];
}
$sql .= " ORDER BY b.tanggal DESC, b.id DESC LIMIT 30";

$st = $pdo->prepare($sql);
$st->execute($param);
$data = $st->fetchAll();
?>

<h4 class="fw-bold mb-1">Bimbingan</h4>
<p class="text-muted">Catatan bimbingan terbaru (maksimal 30)</p>

<div class="card-box">
    <div class="table-responsive">
        <table class="table tabel-simple align-middle mb-0">
            <thead><tr><th>Tanggal</th><th>Mahasiswa</th><th>Catatan</th><th>File</th><th>Aksi</th></tr></thead>
            <tbody>
                <?php if (!$data): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">Belum ada catatan bimbingan.</td></tr>
                <?php endif; ?>
                <?php foreach ($data as $b): ?>
                    <tr>
                        <td class="text-nowrap"><?= e(tgl($b['tanggal'])) ?></td>
                        <td><?= e($b['nama']) ?></td>
                        <td><?= e(mb_strimwidth($b['catatan'], 0, 60, '...')) ?></td>
                        <td><?= $b['nama_file'] ? e(nama_file_tampil($b['nama_file'])) : '-' ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>/pages/proposal_detail.php?id=<?= $b['proposal_id'] ?>"
                               class="btn btn-primary btn-aksi" title="Buka proposal"><i class="bi bi-eye"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>