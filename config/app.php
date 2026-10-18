<?php
// Alamat dasar aplikasi, dipakai untuk link CSS dan menu.
// Di localhost aplikasi ada di /simskripsi, di hosting biasanya di root.
$isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']);
define('BASE_URL', $isLocal ? '/simskripsi' : '');
define('APP_NAME', 'SIMSKRIPSI');