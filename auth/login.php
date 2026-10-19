<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Kalau sudah login, tidak perlu melihat form lagi
if (sudah_login()) {
    header('Location: ' . BASE_URL . '/pages/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Prepared statement: mencegah SQL Injection
    $st = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $st->execute([$username]);
    $user = $st->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true); // ganti ID session demi keamanan
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nama']    = $user['nama'];
        $_SESSION['role']    = $user['role'];

        // Sementara semua role ke dashboard yang sama.
        // Di Checkpoint 7, mahasiswa akan diarahkan ke dashboard-nya sendiri.
        header('Location: ' . BASE_URL . '/pages/dashboard.php');
        exit;
    }
    $error = 'Username atau password salah.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/landing.css" rel="stylesheet">
</head>
<body class="login-page">

<div class="login-card">
    <div class="text-center mb-3">
        <img src="<?= BASE_URL ?>/assets/img/logo_umb.png" alt="Logo UMB" class="login-logo">
        <h3 class="fw-bold mt-2 mb-0">SIMSKRIPSI</h3>
        <small class="text-muted">Universitas Muhammadiyah Bengkulu</small>
    </div>

    <ul class="nav nav-tabs nav-fill mb-3">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-login" type="button">Login</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-info" type="button">Informasi</button>
        </li>
    </ul>

    <div class="tab-content">
        <!-- TAB LOGIN -->
        <div class="tab-pane fade show active" id="tab-login">
            <?php if ($error): ?>
                <div class="alert alert-danger py-2"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" autocomplete="off">
                <div class="input-group mb-3">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" name="username" class="form-control"
                           placeholder="Username / NPM / NIP"
                           value="<?= e($_POST['username'] ?? '') ?>" required autofocus>
                </div>
                <div class="input-group mb-3">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" id="password" class="form-control"
                           placeholder="Password" required>
                    <button class="btn btn-outline-secondary" type="button" id="togglePw">
                        <i class="bi bi-eye" id="iconPw"></i>
                    </button>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2">Login</button>
            </form>

            <div class="info-box mt-3">
                <strong>Untuk login gunakan akun:</strong>
                <table class="table table-sm table-borderless mb-0 mt-1">
                    <tr><td>Mahasiswa</td><td>: NPM</td></tr>
                    <tr><td>Dosen</td><td>: NIP</td></tr>
                    <tr><td>Kaprodi</td><td>: Akun Dosen</td></tr>
                    <tr><td>Admin</td><td>: Akun yang diberikan</td></tr>
                </table>
            </div>
        </div>

        <!-- TAB INFORMASI -->
        <div class="tab-pane fade" id="tab-info">
            <p class="small text-muted">
                SIMSKRIPSI adalah sistem informasi untuk mengelola pengajuan proposal,
                bimbingan, seminar, hingga sidang skripsi mahasiswa secara online.
            </p>
            <p class="small text-muted mb-0">
                Akun demo (password: <b>password123</b>):<br>
                admin, kaprodi, 198001012005 (dosen), 221010002 (mahasiswa)
            </p>
        </div>
    </div>

    <div class="text-center mt-3">
        <a href="<?= BASE_URL ?>/index.php" class="small text-decoration-none">← Kembali ke beranda</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Tombol mata: tampil / sembunyikan password
document.getElementById('togglePw').addEventListener('click', function () {
    var input = document.getElementById('password');
    var icon  = document.getElementById('iconPw');
    var tampil = input.type === 'password';
    input.type = tampil ? 'text' : 'password';
    icon.className = tampil ? 'bi bi-eye-slash' : 'bi bi-eye';
});
</script>
</body>
</html>