<?php
// Seeder data contoh SIMSKRIPSI. Jalankan SEKALI lewat browser.
$host = 'localhost';
$db   = 'simskripsi';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

if ($pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() > 0) {
    die("Data sudah pernah diisi. Seeder dihentikan agar tidak dobel.");
}

$pw = password_hash('password123', PASSWORD_DEFAULT);

function tambahUser($pdo, $username, $pw, $nama, $role, $prodi) {
    $st = $pdo->prepare("INSERT INTO users (username,password,nama,role,prodi_id) VALUES (?,?,?,?,?)");
    $st->execute([$username, $pw, $nama, $role, $prodi]);
    return $pdo->lastInsertId();
}

// Akun staf
tambahUser($pdo, 'admin',   $pw, 'Administrator',        'admin',   null);
tambahUser($pdo, 'kaprodi', $pw, 'Dr. Hendra, M.T.',     'kaprodi', 1);
$d1 = tambahUser($pdo, '198001012005', $pw, 'Dr. Ahmad, M.Kom', 'dosen', 1);
$d2 = tambahUser($pdo, '198202022008', $pw, 'Siti Aminah, M.M.', 'dosen', 2);

// Mahasiswa: [username(NPM), nama, prodi]
$mhs = [
    ['221010001', 'Andi Pratama',   2],
    ['221010002', 'Siti Rahmawati', 1],
    ['221010003', 'Budi Santoso',   2],
    ['221010004', 'Dela Anggraini', 1],
    ['221010005', 'Rizky Maulana',  1],
];
$m = [];
foreach ($mhs as $x) {
    $m[] = tambahUser($pdo, $x[0], $pw, $x[1], 'mahasiswa', $x[2]);
}

// Proposal: [index mahasiswa, judul, status, dosen, tanggal pengajuan]
$proposal = [
    [0, 'Analisis Sistem Informasi Penjualan UMKM',         'diajukan',  null, '2026-10-10'],
    [1, 'Implementasi Web Service dan Web Semantik pada Sistem Informasi Akademik', 'bimbingan', $d1, '2026-10-01'],
    [2, 'Pengaruh Digital Marketing terhadap Minat Beli',   'bimbingan', $d2, '2026-09-28'],
    [3, 'Sistem Pendukung Keputusan Pemilihan Beasiswa',    'seminar',   $d1, '2026-09-15'],
    [4, 'Implementasi Web Semantik untuk Katalog Perpustakaan', 'ditolak', null, '2026-09-20'],
];

$urutan = ['diajukan','disetujui','bimbingan','seminar','sidang','selesai'];

$stP = $pdo->prepare("INSERT INTO proposal (mahasiswa_id,dosen_id,judul,status,tanggal_pengajuan) VALUES (?,?,?,?,?)");
$stL = $pdo->prepare("INSERT INTO status_log (proposal_id,status,tanggal) VALUES (?,?,?)");
$stB = $pdo->prepare("INSERT INTO bimbingan (proposal_id,tanggal,catatan,nama_file) VALUES (?,?,?,?)");

foreach ($proposal as $p) {
    $stP->execute([$m[$p[0]], $p[3], $p[1], $p[2], $p[4]]);
    $pid = $pdo->lastInsertId();

    // Buat riwayat status, tiap tahap berselang 2 hari
    $tgl = new DateTime($p[4]);
    if ($p[2] === 'ditolak') {
        $tahap = ['diajukan', 'ditolak'];
    } else {
        $tahap = array_slice($urutan, 0, array_search($p[2], $urutan) + 1);
    }
    foreach ($tahap as $s) {
        $stL->execute([$pid, $s, $tgl->format('Y-m-d')]);
        $tgl->modify('+2 days');
    }

    // Catatan bimbingan contoh untuk proposal yang sudah masuk tahap bimbingan
    if (in_array($p[2], ['bimbingan', 'seminar'])) {
        $stB->execute([$pid, '2026-10-03', 'Perbaiki latar belakang dan kajian pustaka', 'catatan1.pdf']);
        $stB->execute([$pid, '2026-10-08', 'Lanjut ke metode penelitian', 'catatan2.pdf']);
    }
}

echo "<h2>Seeder berhasil ✅</h2>";
echo "<p>Semua akun memakai password: <b>password123</b></p>";
echo "<p>Username: admin, kaprodi, 198001012005 (dosen), 221010002 (mahasiswa)</p>";