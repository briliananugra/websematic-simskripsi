<?php
// Pembantu untuk semua endpoint web service (jawaban selalu JSON)
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

ini_set('display_errors', '0'); // error PHP jangan bocor sebagai HTML, merusak JSON
header('Content-Type: application/json; charset=utf-8');

function json_out($data, int $kode = 200) {
    http_response_code($kode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

function json_error(int $kode, string $pesan) {
    json_out(['status' => 'error', 'pesan' => $pesan], $kode);
}

// Error tak terduga (misalnya query gagal) -> JSON 500, detailnya masuk log server
set_exception_handler(function ($ex) {
    error_log($ex->getMessage());
    json_error(500, 'Terjadi kesalahan pada server.');
});

function api_hanya_get() {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
        header('Allow: GET');
        json_error(405, 'Metode tidak diizinkan. Endpoint ini hanya menerima GET.');
    }
}

// Boleh masuk lewat sesi login ATAU Basic Auth (username:password)
function api_wajib_login() {
    global $pdo;
    if (sudah_login()) return;

    $user = $_SERVER['PHP_AUTH_USER'] ?? null;
    $pass = $_SERVER['PHP_AUTH_PW'] ?? null;

    // Cadangan: sebagian server tidak mengisi PHP_AUTH_*, tapi header mentahnya ada
    $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if ($user === null && stripos($hdr, 'basic ') === 0) {
        $dec = base64_decode(substr($hdr, 6), true);
        if ($dec !== false && strpos($dec, ':') !== false) {
            [$user, $pass] = explode(':', $dec, 2);
        }
    }

    if ($user !== null) {
        $st = $pdo->prepare("SELECT id, nama, role, password FROM users WHERE username = ?");
        $st->execute([$user]);
        $u = $st->fetch();
        if ($u && password_verify((string)$pass, $u['password'])) {
            $_SESSION['user_id'] = $u['id'];
            $_SESSION['nama']    = $u['nama'];
            $_SESSION['role']    = $u['role'];
            return;
        }
    }

    header('WWW-Authenticate: Basic realm="SIMSKRIPSI API"');
    json_error(401, 'Belum login. Login lewat aplikasi atau gunakan Basic Auth.');
}

function api_wajib_role(array $roleBoleh) {
    api_wajib_login();
    if (!in_array($_SESSION['role'], $roleBoleh, true)) {
        json_error(403, 'Role kamu tidak diizinkan mengakses endpoint ini.');
    }
}