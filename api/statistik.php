<?php
require_once __DIR__ . '/../includes/api.php';
api_hanya_get();
api_wajib_role(['admin', 'kaprodi', 'dosen']);

$perStatus = $pdo->query("SELECT status, COUNT(*) FROM proposal GROUP BY status")
                 ->fetchAll(PDO::FETCH_KEY_PAIR);

$hasil = [];
foreach (daftar_status() as $s) {
    $hasil[$s] = (int)($perStatus[$s] ?? 0);   // status kosong tetap tampil 0
}

json_out([
    'status' => 'ok',
    'data'   => [
        'total_mahasiswa' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'mahasiswa'")->fetchColumn(),
        'total_proposal'  => (int)$pdo->query("SELECT COUNT(*) FROM proposal")->fetchColumn(),
        'per_status'      => $hasil,
    ],
]);