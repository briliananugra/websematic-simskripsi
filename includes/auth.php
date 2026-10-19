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
        ?>
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>Akses Ditolak</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body class="bg-light d-flex align-items-center justify-content-center min-vh-100">
            <div class="text-center p-4">
                <h1 class="display-6 fw-bold">Akses ditolak</h1>
                <p class="text-muted">Role kamu (<?= htmlspecialchars($_SESSION['role']) ?>) tidak diizinkan membuka halaman ini.</p>
                <a href="<?= BASE_URL ?>/auth/logout.php" class="btn btn-primary">Keluar &amp; ganti akun</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}