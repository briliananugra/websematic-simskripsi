<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_role(['admin', 'kaprodi', 'dosen']); // mahasiswa punya dashboard sendiri (CP7)

$pageTitle  = 'Dashboard';
$activeMenu = 'dashboard';
require_once __DIR__ . '/../includes/header.php';

// ---------- 1. Kartu statistik ----------
$totalMahasiswa = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'mahasiswa'")->fetchColumn();
$totalProposal  = $pdo->query("SELECT COUNT(*) FROM proposal")->fetchColumn();
$sedangBimbing  = $pdo->query("SELECT COUNT(*) FROM proposal WHERE status = 'bimbingan'")->fetchColumn();
$sidangSelesai  = $pdo->query("SELECT COUNT(*) FROM proposal WHERE status = 'selesai'")->fetchColumn();

// ---------- 2. Data donut: jumlah proposal per status ----------
$perStatus = $pdo->query("SELECT status, COUNT(*) FROM proposal GROUP BY status")
                 ->fetchAll(PDO::FETCH_KEY_PAIR);

// [label tampil, warna]
$daftarStatus = [
    'diajukan'  => ['Diajukan',  '#3b82f6'],
    'disetujui' => ['Disetujui', '#22c55e'],
    'bimbingan' => ['Bimbingan', '#f59e0b'],
    'seminar'   => ['Seminar',   '#fb923c'],
    'sidang'    => ['Sidang',    '#8b5cf6'],
    'selesai'   => ['Selesai',   '#64748b'],
    'ditolak'   => ['Ditolak',   '#ef4444'],
];
$chartLabel = $chartNilai = $chartWarna = [];
foreach ($daftarStatus as $kunci => $info) {
    $chartLabel[] = $info[0];
    $chartNilai[] = (int)($perStatus[$kunci] ?? 0);
    $chartWarna[] = $info[1];
}

// ---------- 3. Aktivitas terbaru ----------
$aktivitas = $pdo->query("
    SELECT l.status, l.tanggal, u.nama
    FROM status_log l
    JOIN proposal p ON p.id = l.proposal_id
    JOIN users u    ON u.id = p.mahasiswa_id
    ORDER BY l.tanggal DESC, l.id DESC
    LIMIT 4
")->fetchAll();

$kalimat = [
    'diajukan'  => 'mengajukan proposal',
    'disetujui' => 'proposalnya disetujui',
    'bimbingan' => 'memulai tahap bimbingan',
    'seminar'   => 'masuk tahap seminar proposal',
    'sidang'    => 'masuk tahap sidang skripsi',
    'selesai'   => 'menyelesaikan skripsi',
    'ditolak'   => 'proposalnya ditolak',
];

// ---------- 4. Proposal terbaru ----------
$terbaru = $pdo->query("
    SELECT p.id, p.judul, p.status, p.tanggal_pengajuan, u.nama, pr.nama_prodi
    FROM proposal p
    JOIN users u      ON u.id = p.mahasiswa_id
    LEFT JOIN prodi pr ON pr.id = u.prodi_id
    ORDER BY p.tanggal_pengajuan DESC, p.id DESC
    LIMIT 5
")->fetchAll();
?>

<h4 class="fw-bold mb-1">Dashboard</h4>
<p class="text-muted">Selamat datang di Sistem Informasi Skripsi</p>

<!-- KARTU STATISTIK -->
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
        <div class="card-box stat-card">
            <div class="stat-icon bg-biru"><i class="bi bi-people-fill"></i></div>
            <div><div class="angka"><?= e($totalMahasiswa) ?></div><div class="label">Total Mahasiswa</div></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card-box stat-card">
            <div class="stat-icon bg-hijau"><i class="bi bi-file-earmark-text-fill"></i></div>
            <div><div class="angka"><?= e($totalProposal) ?></div><div class="label">Proposal</div></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card-box stat-card">
            <div class="stat-icon bg-kuning"><i class="bi bi-chat-left-text-fill"></i></div>
            <div><div class="angka"><?= e($sedangBimbing) ?></div><div class="label">Sedang Bimbingan</div></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card-box stat-card">
            <div class="stat-icon bg-merah"><i class="bi bi-mortarboard-fill"></i></div>
            <div><div class="angka"><?= e($sidangSelesai) ?></div><div class="label">Sidang Selesai</div></div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <!-- DONUT STATUS PROPOSAL -->
    <div class="col-lg-5">
        <div class="card-box">
            <h6>Status Proposal</h6>
            <div class="d-flex align-items-center gap-4 flex-wrap">
                <div class="donut-wrap">
                    <canvas id="chartStatus"></canvas>
                    <div class="donut-tengah"><b><?= e($totalProposal) ?></b><small>Proposal</small></div>
                </div>
                <div class="flex-grow-1">
                    <?php foreach ($daftarStatus as $kunci => $info): ?>
                        <div class="legend-item">
                            <span class="legend-dot" style="background: <?= $info[1] ?>"></span>
                            <?= $info[0] ?>
                            <span class="jml"><?= (int)($perStatus[$kunci] ?? 0) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- AKTIVITAS TERBARU -->
    <div class="col-lg-7">
        <div class="card-box">
            <h6>Aktivitas Terbaru</h6>
            <?php if (!$aktivitas): ?>
                <p class="text-muted small mb-0">Belum ada aktivitas.</p>
            <?php endif; ?>
            <?php foreach ($aktivitas as $a): ?>
                <div class="aktivitas">
                    <div class="dot"><i class="bi bi-bell"></i></div>
                    <div>
                        <b><?= e($a['nama']) ?></b> <?= e($kalimat[$a['status']] ?? $a['status']) ?><br>
                        <small class="text-muted"><?= e(tgl($a['tanggal'])) ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- PROPOSAL TERBARU -->
<div class="card-box">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0">Proposal Terbaru</h6>
        <a href="<?= BASE_URL ?>/pages/proposal.php" class="small text-decoration-none">Lihat Semua</a>
    </div>
    <div class="table-responsive">
        <table class="table tabel-simple mb-0">
            <thead>
                <tr><th>No</th><th>Nama</th><th>Judul Proposal</th><th>Prodi</th><th>Status</th><th>Tanggal</th></tr>
            </thead>
            <tbody>
                <?php foreach ($terbaru as $i => $p): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= e($p['nama']) ?></td>
                        <td><?= e(mb_strimwidth($p['judul'], 0, 40, '...')) ?></td>
                        <td><?= e($p['nama_prodi']) ?></td>
                        <td><?= badge_status($p['status']) ?></td>
                        <td><?= e(tgl($p['tanggal_pengajuan'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Chart.js dari CDN, dipanggil sama seperti Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('chartStatus'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($chartLabel) ?>,
        datasets: [{
            data: <?= json_encode($chartNilai) ?>,
            backgroundColor: <?= json_encode($chartWarna) ?>,
            borderWidth: 2
        }]
    },
    options: { cutout: '68%', plugins: { legend: { display: false } } }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>