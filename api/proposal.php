<?php
require_once __DIR__ . '/../includes/api.php';
api_hanya_get();
api_wajib_login();

$role = $_SESSION['role'];

// Bentuk satu proposal menjadi struktur JSON yang rapi
function bentuk_proposal(array $r): array {
    return [
        'id'                => (int)$r['id'],
        'judul'             => $r['judul'],
        'status'            => $r['status'],
        'tanggal_pengajuan' => $r['tanggal_pengajuan'],
        'mahasiswa'         => [
            'nama'  => $r['nama'],
            'npm'   => $r['npm'],
            'prodi' => $r['nama_prodi'],
        ],
        'dosen_pembimbing'  => $r['nama_dosen'],
    ];
}

$kolom = "p.id, p.judul, p.status, p.tanggal_pengajuan,
          u.nama, u.username AS npm, pr.nama_prodi, d.nama AS nama_dosen";
$dari  = "FROM proposal p
          JOIN users u       ON u.id  = p.mahasiswa_id
          LEFT JOIN prodi pr ON pr.id = u.prodi_id
          LEFT JOIN users d  ON d.id  = p.dosen_id";

// ================= DETAIL: ?id=... =================
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $st = $pdo->prepare("SELECT p.mahasiswa_id, p.dosen_id, $kolom $dari WHERE p.id = ?");
    $st->execute([$id]);
    $p = $st->fetch();

    if (!$p)                           json_error(404, 'Proposal tidak ditemukan.');
    if (!proposal_boleh_dilihat($p))   json_error(403, 'Kamu tidak berhak melihat proposal ini.');

    $st = $pdo->prepare("SELECT status, tanggal FROM status_log WHERE proposal_id = ? ORDER BY tanggal ASC, id ASC");
    $st->execute([$id]);
    $riwayat = $st->fetchAll();

    $st = $pdo->prepare("SELECT id, tanggal, catatan, nama_file FROM bimbingan WHERE proposal_id = ? ORDER BY tanggal ASC, id ASC");
    $st->execute([$id]);
    $bimbingan = array_map(fn($b) => [
        'id'      => (int)$b['id'],
        'tanggal' => $b['tanggal'],
        'catatan' => $b['catatan'],
        'file'    => $b['nama_file'] ? nama_file_tampil($b['nama_file']) : null,
    ], $st->fetchAll());

    $data = bentuk_proposal($p);
    $data['riwayat_status'] = $riwayat;
    $data['bimbingan']      = $bimbingan;

    json_out(['status' => 'ok', 'data' => $data]);
}

// ================= DAFTAR: pencarian, filter, halaman =================
$where = [];
$param = [];

if ($role === 'mahasiswa') {                 // mahasiswa hanya melihat miliknya
    $where[] = "p.mahasiswa_id = ?";
    $param[] = (int)$_SESSION['user_id'];
}

$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $where[] = "(p.judul LIKE ? OR u.nama LIKE ? OR u.username LIKE ?)";
    $like = '%' . $q . '%';
    array_push($param, $like, $like, $like);
}

$status = $_GET['status'] ?? '';
if ($status !== '') {
    if (!in_array($status, daftar_status(), true)) {
        json_error(400, 'Nilai status tidak valid. Pilihan: ' . implode(', ', daftar_status()));
    }
    $where[] = "p.status = ?";
    $param[] = $status;
}

$prodi = (int)($_GET['prodi'] ?? 0);
if ($prodi > 0) {
    $where[] = "u.prodi_id = ?";
    $param[] = $prodi;
}
$sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$halaman = max(1, (int)($_GET['page'] ?? 1));
$perHal  = min(50, max(1, (int)($_GET['per_page'] ?? 10)));
$offset  = ($halaman - 1) * $perHal;

$st = $pdo->prepare("SELECT COUNT(*) FROM proposal p JOIN users u ON u.id = p.mahasiswa_id $sqlWhere");
$st->execute($param);
$total = (int)$st->fetchColumn();

// $perHal & $offset sudah pasti integer, aman ditulis langsung
$st = $pdo->prepare("SELECT $kolom $dari $sqlWhere ORDER BY p.id DESC LIMIT $perHal OFFSET $offset");
$st->execute($param);

json_out([
    'status' => 'ok',
    'meta'   => [
        'halaman'        => $halaman,
        'per_halaman'    => $perHal,
        'total'          => $total,
        'jumlah_halaman' => max(1, (int)ceil($total / $perHal)),
    ],
    'data'   => array_map('bentuk_proposal', $st->fetchAll()),
]);