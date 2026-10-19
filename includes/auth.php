<?php
// Penjaga login berbasis session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/app.php';

function sudah_login() {
    return isset($_SESSION['user_id']);
}

// Panggil di halaman yang wajib login
function wajib_login() {
    if (!sudah_login()) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
}

// Panggil di halaman yang hanya boleh dibuka role tertentu
// contoh: wajib_role(['admin', 'kaprodi']);
function wajib_role(array $roleBoleh) {
    wajib_login();
    if (!in_array($_SESSION['role'], $roleBoleh, true)) {
        http_response_code(403);
        die('Akses ditolak: role kamu tidak diizinkan membuka halaman ini.');
    }
}