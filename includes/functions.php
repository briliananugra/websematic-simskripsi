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