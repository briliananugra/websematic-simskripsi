<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
wajib_role(['admin', 'kaprodi']);

// Hanya menerima POST (bukan dibuka langsung lewat alamat)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/proposal.php');
    exit;
}
csrf_cek();

$id = (int)($_POST['id'] ?? 0);

$st = $pdo->prepare("DELETE FROM proposal WHERE id = ?");
$st->execute([$id]);

if ($st->rowCount() > 0) {
    set_flash('success', 'Proposal berhasil dihapus.');
} else {
    set_flash('danger', 'Proposal tidak ditemukan.');
}
header('Location: ' . BASE_URL . '/pages/proposal.php');
exit;