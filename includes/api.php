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

// Basic Auth (kalau dikirim) SELALU didahulukan dari sesi login browser
function api_wajib_login() {
    global $pdo;

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
        // Ada kredensial: hanya ini yang dipakai. Salah = 401, tidak boleh jatuh ke sesi.
        $st = $pdo->prepare("SELECT id, nama, role, password FROM users WHERE username = ?");
        $st->execute([$user]);
        $u = $st->fetch();
        if ($u && password_verify((string)$pass, $u['password'])) {
            session_write_close(); // tutup sesi: perubahan di bawah TIDAK tersimpan ke server
            $_SESSION = [
                'user_id' => $u['id'],
                'nama'    => $u['nama'],
                'role'    => $u['role'],
            ];
            return;
        }
    } elseif (sudah_login()) {
        return; // tanpa Basic Auth: pakai sesi login browser
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