<?php
require_once __DIR__ . '/../includes/api.php';
api_hanya_get();
api_wajib_login();

$pid = (int)($_GET['proposal_id'] ?? 0);
if ($pid <= 0) json_error(400, 'Parameter proposal_id wajib diisi.');

$st = $pdo->prepare("SELECT id, mahasiswa_id, dosen_id FROM proposal WHERE id = ?");
$st->execute([$pid]);
$p = $st->fetch();

if (!$p)                         json_error(404, 'Proposal tidak ditemukan.');
if (!proposal_boleh_dilihat($p)) json_error(403, 'Kamu tidak berhak melihat bimbingan proposal ini.');

$st = $pdo->prepare("SELECT id, tanggal, catatan, nama_file FROM bimbingan WHERE proposal_id = ? ORDER BY tanggal ASC, id ASC");
$st->execute([$pid]);

json_out([
    'status'      => 'ok',
    'proposal_id' => $pid,
    'data'        => array_map(fn($b) => [
        'id'      => (int)$b['id'],
        'tanggal' => $b['tanggal'],
        'catatan' => $b['catatan'],
        'file'    => $b['nama_file'] ? nama_file_tampil($b['nama_file']) : null,
    ], $st->fetchAll()),
]);