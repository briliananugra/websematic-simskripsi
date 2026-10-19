<?php
// Menampilkan teks dengan aman (mencegah serangan XSS)
function e($teks) {
    return htmlspecialchars((string)$teks, ENT_QUOTES, 'UTF-8');
}

// Warna label status (mengikuti mockup)
function badge_status($status) {
    $map = [
        'diajukan'  => 'info',
        'disetujui' => 'success',
        'bimbingan' => 'warning',
        'seminar'   => 'orange',
        'sidang'    => 'purple',
        'selesai'   => 'dark',
        'ditolak'   => 'danger',
    ];
    $kelas = $map[$status] ?? 'secondary';
    return '<span class="badge badge-' . $kelas . '">' . e(ucfirst($status)) . '</span>';
}

// Mengubah 2026-10-10 menjadi 10-10-2026
function tgl($tanggal) {
    return $tanggal ? date('d-m-Y', strtotime($tanggal)) : '-';
}

// Daftar status proposal (satu sumber, dipakai banyak halaman)
function daftar_status() {
    return ['diajukan','disetujui','bimbingan','seminar','sidang','selesai','ditolak'];
}

// ---------- Pesan sekali tampil (flash message) ----------
function set_flash($tipe, $pesan) {
    $_SESSION['flash'] = ['tipe' => $tipe, 'pesan' => $pesan];
}
function tampil_flash() {
    if (empty($_SESSION['flash'])) return '';
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']); // hanya tampil sekali
    return '<div class="alert alert-' . e($f['tipe']) . ' alert-dismissible fade show">'
         . e($f['pesan'])
         . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
}

// ---------- Token CSRF: mencegah form dikirim dari situs lain ----------
function csrf_token() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}
function csrf_field() {
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}
function csrf_cek() {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(400);
        die('Permintaan tidak valid (token CSRF salah).');
    }
}

// ---------- Hak akses proposal & bimbingan ----------
// $p adalah satu baris proposal (butuh kolom mahasiswa_id dan dosen_id)
function proposal_boleh_dilihat(array $p) {
    $role = $_SESSION['role'] ?? '';
    if (in_array($role, ['admin', 'kaprodi', 'dosen'], true)) return true;
    return $role === 'mahasiswa' && (int)$p['mahasiswa_id'] === (int)$_SESSION['user_id'];
}
function bimbingan_boleh_tambah(array $p) {
    $role = $_SESSION['role'] ?? '';
    if (in_array($role, ['admin', 'kaprodi'], true)) return true;
    if ($role === 'dosen')     return (int)$p['dosen_id']     === (int)$_SESSION['user_id'];
    if ($role === 'mahasiswa') return (int)$p['mahasiswa_id'] === (int)$_SESSION['user_id'];
    return false;
}
function bimbingan_boleh_hapus(array $p) {
    $role = $_SESSION['role'] ?? '';
    if (in_array($role, ['admin', 'kaprodi'], true)) return true;
    return $role === 'dosen' && (int)$p['dosen_id'] === (int)$_SESSION['user_id'];
}

// "a1b2c3d4e5f6_laporan.pdf" -> "laporan.pdf" (awalan acak disembunyikan)
function nama_file_tampil($nama) {
    $pos = strpos($nama, '_');
    return $pos !== false ? substr($nama, $pos + 1) : $nama;
}

// Alamat dashboard sesuai role yang sedang login
function url_dashboard() {
    $mhs = ($_SESSION['role'] ?? '') === 'mahasiswa';
    return BASE_URL . ($mhs ? '/pages/dashboard_mahasiswa.php' : '/pages/dashboard.php');
}