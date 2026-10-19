<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
wajib_role(['mahasiswa']);

$st = $pdo->prepare("SELECT id FROM proposal WHERE mahasiswa_id = ? ORDER BY id DESC LIMIT 1");
$st->execute([$_SESSION['user_id']]);
$id = $st->fetchColumn();

if ($id) {
    header('Location: ' . BASE_URL . '/pages/proposal_detail.php?id=' . $id);
} else {
    set_flash('warning', 'Kamu belum mengajukan proposal.');
    header('Location: ' . BASE_URL . '/pages/dashboard_mahasiswa.php');
}
exit;