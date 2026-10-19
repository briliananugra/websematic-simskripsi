<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
wajib_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/proposal.php');
    exit;
}
csrf_cek();

$id = (int)($_POST['id'] ?? 0);
$st = $pdo->prepare("
    SELECT b.proposal_id, b.nama_file, p.mahasiswa_id, p.dosen_id
    FROM bimbingan b JOIN proposal p ON p.id = b.proposal_id
    WHERE b.id = ?
");
$st->execute([$id]);
$b = $st->fetch();

if (!$b || !bimbingan_boleh_hapus($b)) {
    set_flash('danger', 'Catatan tidak ditemukan atau kamu tidak berhak menghapusnya.');
    header('Location: ' . BASE_URL . '/pages/proposal.php');
    exit;
}

$pdo->prepare("DELETE FROM bimbingan WHERE id = ?")->execute([$id]);

// hapus juga file fisiknya kalau ada
if ($b['nama_file']) {
    $path = __DIR__ . '/../uploads/bimbingan/' . basename($b['nama_file']);
    if (is_file($path)) unlink($path);
}

set_flash('success', 'Catatan bimbingan dihapus.');
header('Location: ' . BASE_URL . '/pages/proposal_detail.php?id=' . $b['proposal_id']);
exit;