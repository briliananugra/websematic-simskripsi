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