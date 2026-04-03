<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (isset($_SESSION['role'])) {
    $roleMap = ['admin_assets'=>'admin_asset','admin_programs'=>'admin_program'];
    $_SESSION['role'] = $roleMap[$_SESSION['role']] ?? $_SESSION['role'];
}
if (!in_array($_SESSION['role'], ['penjual','admin_program'])) { 
    header("Location: dashboard.php"); exit; 
}
include 'koneksi.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { 
    header("Location: broadcast.php"); exit; 
}

$id          = (int)  ($_POST['id']          ?? 0);
$judul       = trim($_POST['judul']          ?? '');
$pesan       = trim($_POST['pesan']          ?? '');
$target_role = trim($_POST['target_role']    ?? 'semua');
$aktif       = (int)  ($_POST['aktif']       ?? 1);
$no_hp       = trim($_POST['no_hp']          ?? '');
$username    = $_SESSION['username'];

if (!$judul || !$pesan) {
    echo "<script>alert('Judul dan pesan wajib diisi!'); history.back();</script>"; exit;
}

// Bersihkan nomor HP - hilangkan karakter non-angka
$no_hp_clean = preg_replace('/[^0-9]/', '', $no_hp);
// Ubah 08xx → 628xx
if (substr($no_hp_clean, 0, 1) === '0') {
    $no_hp_clean = '62' . substr($no_hp_clean, 1);
}

if ($id === 0) {
    $stmt = $conn->prepare("INSERT INTO broadcast (judul, pesan, target_role, aktif, dibuat_oleh, no_hp) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param("sssiis", $judul, $pesan, $target_role, $aktif, $username, $no_hp_clean);
} else {
    $stmt = $conn->prepare("UPDATE broadcast SET judul=?, pesan=?, target_role=?, aktif=?, no_hp=? WHERE id=?");
    $stmt->bind_param("sssisi", $judul, $pesan, $target_role, $aktif, $no_hp_clean, $id);
}
$ok = $stmt->execute();
$stmt->close();

if ($ok) {
    // Redirect ke broadcast.php dengan info WA
    $waParam = $no_hp_clean ? '&wa=' . urlencode($no_hp_clean) . '&msg=' . urlencode($judul . "\n\n" . $pesan) : '';
    header("Location: broadcast.php?saved=1" . $waParam);
} else {
    header("Location: broadcast.php?error=1");
}
exit;