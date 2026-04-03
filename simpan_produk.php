<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (isset($_SESSION['role'])) {
    $roleMap = ['admin_assets'=>'admin_asset','admin_programs'=>'admin_program'];
    $_SESSION['role'] = $roleMap[$_SESSION['role']] ?? $_SESSION['role'];
}
include 'koneksi.php';
if (!in_array($_SESSION['role'], ['penjual','admin_program'])) { header("Location: dashboard.php"); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: produk.php"); exit; }

$nama         = trim($_POST['nama_produk']  ?? '');
$kategori     = trim($_POST['kategori']     ?? 'Umum');
$deskripsi    = trim($_POST['deskripsi']    ?? '');
$harga_eceren = (float)($_POST['harga_eceren']  ?? 0);
$harga_per_dus= (float)($_POST['harga_per_dus'] ?? 0);
$isi_per_dus  = (int)  ($_POST['isi_per_dus']   ?? 0);
$ml_per_satuan= trim($_POST['ml_per_satuan'] ?? '');
$berat_satuan = trim($_POST['berat_satuan']  ?? '');
$stok         = (int)  ($_POST['stok']       ?? 0);
$harga        = $harga_eceren > 0 ? $harga_eceren : (float)($_POST['harga'] ?? 0);

if (!$nama || $harga_eceren <= 0) {
    header("Location: tambah_produk.php?error=invalid"); exit;
}

// Upload gambar
$namaGambar = null;
if (!empty($_FILES['gambar']['name'])) {
    $file    = $_FILES['gambar'];
    $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp','gif'];
    if (in_array($ext,$allowed) && $file['size'] <= 2*1024*1024) {
        $uploadDir = 'uploads/produk/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $namaGambar = 'prod_'.time().'_'.uniqid().'.'.$ext;
        if (!move_uploaded_file($file['tmp_name'], $uploadDir.$namaGambar)) $namaGambar = null;
    }
}

// Cek kolom yang ada
$cols = [];
$r = $conn->query("SHOW COLUMNS FROM produk");
while($row = $r->fetch_assoc()) $cols[] = $row['Field'];

$fields = ['nama_produk','harga'];
$vals   = [$nama, $harga];
$types  = 'sd';

if (in_array('kategori',   $cols)) { $fields[]='kategori';    $vals[]=$kategori;    $types.='s'; }
if (in_array('gambar',     $cols)) { $fields[]='gambar';      $vals[]=$namaGambar;  $types.='s'; }
if (in_array('deskripsi',  $cols)) { $fields[]='deskripsi';   $vals[]=$deskripsi;   $types.='s'; }
if (in_array('harga_eceren',$cols)){ $fields[]='harga_eceren';$vals[]=$harga_eceren;$types.='d'; }
if (in_array('harga_per_dus',$cols)){ $fields[]='harga_per_dus';$vals[]=$harga_per_dus;$types.='d'; }
if (in_array('isi_per_dus',$cols)) { $fields[]='isi_per_dus'; $vals[]=$isi_per_dus; $types.='i'; }
if (in_array('ml_per_satuan',$cols)){ $fields[]='ml_per_satuan';$vals[]=$ml_per_satuan;$types.='s'; }
if (in_array('berat_satuan',$cols)) { $fields[]='berat_satuan';$vals[]=$berat_satuan;$types.='s'; }
if (in_array('stok',       $cols)) { $fields[]='stok';        $vals[]=$stok;        $types.='i'; }

$ph   = implode(',', array_fill(0, count($fields), '?'));
$flds = implode(',', $fields);
$stmt = $conn->prepare("INSERT INTO produk ($flds) VALUES ($ph)");
$stmt->bind_param($types, ...$vals);
$ok = $stmt->execute();
$stmt->close();

header("Location: produk.php?".($ok?"added=1":"error=1")); exit;