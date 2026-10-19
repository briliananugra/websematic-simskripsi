<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
wajib_login();

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare("
    SELECT b.proposal_id, b.nama_file, p.mahasiswa_id, p.dosen_id
    FROM bimbingan b
    JOIN proposal p ON p.id = b.proposal_id
    WHERE b.id = ?
");
$st->execute([$id]);
$b = $st->fetch();

if (!$b || !proposal_boleh_dilihat($b) || !$b['nama_file']) {
    http_response_code(404);
    die('File tidak ditemukan.');
}

$path = __DIR__ . '/../uploads/bimbingan/' . basename($b['nama_file']);
if (!is_file($path)) {
    // contoh: file "catatan1.pdf" dari seeder memang tidak punya file asli
    set_flash('warning', 'File tidak tersedia di server (kemungkinan data contoh).');
    header('Location: ' . BASE_URL . '/pages/proposal_detail.php?id=' . $b['proposal_id']);
    exit;
}

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . nama_file_tampil($b['nama_file']) . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;