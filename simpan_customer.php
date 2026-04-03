<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
// Normalisasi role
if (isset($_SESSION['role'])) {
    $roleMap = ['admin_assets'=>'admin_asset','admin_programs'=>'admin_program'];
    $_SESSION['role'] = $roleMap[$_SESSION['role']] ?? $_SESSION['role'];
}
if (!isset($_SESSION['username'])) { header("Location: login.php"); exit; }
if (!in_array($_SESSION['role'], ['penjual','admin_asset'])) { header("Location: dashboard.php"); exit; }

include 'koneksi.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: customer.php"); exit; }

$role     = $_SESSION['role'];
$username = $_SESSION['username'];

// Ambil semua field
$id        = (int)  ($_POST['id']        ?? 0);
$nama_toko = trim($_POST['nama_toko']    ?? '');
$id_mesin  = trim($_POST['id_mesin']     ?? '');
$nomor_hp  = trim($_POST['nomor_hp']     ?? '');
$lokasi    = trim($_POST['lokasi']       ?? '');
$kota      = trim($_POST['kota']         ?? '');
$provinsi  = trim($_POST['provinsi']     ?? '');
$link_maps = trim($_POST['link_maps']    ?? '');
$wilayah   = trim($_POST['wilayah']      ?? '');
$status    = trim($_POST['status']       ?? 'aktif');
$catatan   = trim($_POST['catatan']      ?? '');

// Validasi
if ($nama_toko === '') {
    echo "<script>alert('Nama toko tidak boleh kosong!'); history.back();</script>"; exit;
}
if (!in_array($status, ['aktif','nonaktif'])) $status = 'aktif';

// Validasi link_maps
if ($link_maps && !filter_var($link_maps, FILTER_VALIDATE_URL)) {
    $link_maps = '';
}

// Cek kolom yang tersedia
$hasLinkMaps = $conn->query("SHOW COLUMNS FROM customer LIKE 'link_maps'")->fetch_assoc();
$hasWilayah  = $conn->query("SHOW COLUMNS FROM customer LIKE 'wilayah'")->fetch_assoc();

if ($id === 0) {
    // ── INSERT: penjual DAN admin_asset bisa tambah ──
    $fields = ['nama_toko','id_mesin','nomor_hp','lokasi','kota','provinsi','status','catatan','dibuat_oleh'];
    $vals   = [$nama_toko,$id_mesin,$nomor_hp,$lokasi,$kota,$provinsi,$status,$catatan,$username];
    $types  = 'sssssssss';

    if ($hasLinkMaps) { $fields[]='link_maps'; $vals[]=$link_maps; $types.='s'; }
    if ($hasWilayah)  { $fields[]='wilayah';   $vals[]=$wilayah;   $types.='s'; }

    $ph   = implode(',', array_fill(0, count($fields), '?'));
    $flds = implode(',', $fields);
    $stmt = $conn->prepare("INSERT INTO customer ($flds) VALUES ($ph)");
    $stmt->bind_param($types, ...$vals);

} else {
    // ── UPDATE ──
    if ($role === 'admin_asset') {
        // Admin asset: edit data toko (tanpa status & catatan)
        $sets  = ['nama_toko=?','id_mesin=?','nomor_hp=?','lokasi=?','kota=?','provinsi=?','diedit_oleh=?'];
        $vals  = [$nama_toko,$id_mesin,$nomor_hp,$lokasi,$kota,$provinsi,$username];
        $types = 'sssssss';
        if ($hasLinkMaps) { $sets[]='link_maps=?'; $vals[]=$link_maps; $types.='s'; }
        if ($hasWilayah)  { $sets[]='wilayah=?';   $vals[]=$wilayah;   $types.='s'; }
        $vals[] = $id; $types .= 'i';
        $stmt = $conn->prepare("UPDATE customer SET ".implode(',',$sets)." WHERE id=?");
        $stmt->bind_param($types, ...$vals);
    } else {
        // Penjual: edit semua field
        $sets  = ['nama_toko=?','id_mesin=?','nomor_hp=?','lokasi=?','kota=?','provinsi=?','status=?','catatan=?','diedit_oleh=?'];
        $vals  = [$nama_toko,$id_mesin,$nomor_hp,$lokasi,$kota,$provinsi,$status,$catatan,$username];
        $types = 'sssssssss';
        if ($hasLinkMaps) { $sets[]='link_maps=?'; $vals[]=$link_maps; $types.='s'; }
        if ($hasWilayah)  { $sets[]='wilayah=?';   $vals[]=$wilayah;   $types.='s'; }
        $vals[] = $id; $types .= 'i';
        $stmt = $conn->prepare("UPDATE customer SET ".implode(',',$sets)." WHERE id=?");
        $stmt->bind_param($types, ...$vals);
    }
}

$ok = $stmt->execute();
$stmt->close();
header("Location: customer.php?" . ($ok ? "saved=1" : "error=1")); exit;