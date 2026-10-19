<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
wajib_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/proposal.php');
    exit;
}
csrf_cek();

$pid = (int)($_POST['proposal_id'] ?? 0);
$st  = $pdo->prepare("SELECT * FROM proposal WHERE id = ?");
$st->execute([$pid]);
$p = $st->fetch();

if (!$p || !bimbingan_boleh_tambah($p)) {
    set_flash('danger', 'Proposal tidak ditemukan atau kamu tidak berhak menambah catatan.');
    header('Location: ' . BASE_URL . '/pages/proposal.php');
    exit;
}
$kembali = BASE_URL . '/pages/proposal_detail.php?id=' . $pid;

// ---------- Validasi teks ----------
$tanggal = $_POST['tanggal'] ?? '';
$catatan = trim($_POST['catatan'] ?? '');
$errors  = [];

$d = DateTime::createFromFormat('Y-m-d', $tanggal);
if (!$d || $d->format('Y-m-d') !== $tanggal) $errors[] = 'Tanggal tidak valid.';
if (mb_strlen($catatan) < 5)    $errors[] = 'Catatan minimal 5 karakter.';
if (mb_strlen($catatan) > 2000) $errors[] = 'Catatan maksimal 2000 karakter.';

// ---------- Validasi file (kalau ada) ----------
$namaFile = null;
$tmp      = null;
if (isset($_FILES['file']) && $_FILES['file']['error'] !== UPLOAD_ERR_NO_FILE) {
    $f = $_FILES['file'];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Upload file gagal (kode ' . $f['error'] . ').';
    } elseif ($f['size'] > 2 * 1024 * 1024) {
        $errors[] = 'Ukuran file maksimal 2 MB.';
    } else {
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['pdf', 'doc', 'docx'], true)) {
            $errors[] = 'Jenis file harus PDF, DOC, atau DOCX.';
        } else {
            // nama aman: awalan acak + nama asli yang sudah dibersihkan
            $dasar    = substr(preg_replace('/[^A-Za-z0-9-]/', '-', pathinfo($f['name'], PATHINFO_FILENAME)), 0, 60);
            $namaFile = bin2hex(random_bytes(6)) . '_' . $dasar . '.' . $ext;
            $tmp      = $f['tmp_name'];
        }
    }
}

if ($errors) {
    set_flash('danger', implode(' ', $errors));
    header('Location: ' . $kembali);
    exit;
}

// ---------- Simpan file & data ----------
if ($tmp !== null) {
    $folder = __DIR__ . '/../uploads/bimbingan';
    if (!is_dir($folder)) mkdir($folder, 0755, true);
    if (!move_uploaded_file($tmp, $folder . '/' . $namaFile)) {
        set_flash('danger', 'File gagal disimpan di server.');
        header('Location: ' . $kembali);
        exit;
    }
}

$pdo->prepare("INSERT INTO bimbingan (proposal_id, tanggal, catatan, nama_file) VALUES (?,?,?,?)")
    ->execute([$pid, $tanggal, $catatan, $namaFile]);

set_flash('success', 'Catatan bimbingan berhasil ditambahkan.');
header('Location: ' . $kembali);
exit;