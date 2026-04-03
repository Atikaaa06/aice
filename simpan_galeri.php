<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['username'])) { header("Location: login.php"); exit; }
if (!in_array($_SESSION['role'], ['penjual','admin_program'])) {
    header("Location: dashboard.php"); exit;
}
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: galeri.php"); exit;
}

$id        = (int)   ($_POST['id']        ?? 0);
$judul     = trim($_POST['judul']         ?? '');
$deskripsi = trim($_POST['deskripsi']     ?? '');
$kategori  = trim($_POST['kategori']      ?? 'lainnya');
$aktif     = (int)   ($_POST['aktif']     ?? 1);
$username  = $_SESSION['username'];
$uploadDir = 'uploads/galeri/';

if (!$judul) {
    echo "<script>alert('Judul tidak boleh kosong!'); history.back();</script>"; exit;
}
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

$namaFile = null;

// Ambil file lama jika edit
$fileLama = null;
if ($id > 0) {
    $r = $conn->query("SELECT gambar FROM galeri WHERE id=$id")->fetch_assoc();
    $fileLama  = $r['gambar'] ?? null;
    $namaFile  = $fileLama;
}

// Upload file baru
if (!empty($_FILES['gambar']['name'])) {
    $file    = $_FILES['gambar'];
    $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp','gif'];
    if (!in_array($ext, $allowed)) {
        echo "<script>alert('Format tidak didukung!'); history.back();</script>"; exit;
    }
    if ($file['size'] > 5*1024*1024) {
        echo "<script>alert('Ukuran file maksimal 5MB!'); history.back();</script>"; exit;
    }
    $namaFile = 'gal_' . time() . '_' . uniqid() . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $namaFile)) {
        echo "<script>alert('Gagal upload foto!'); history.back();</script>"; exit;
    }
    // Hapus file lama
    if ($fileLama && file_exists($uploadDir . $fileLama)) {
        unlink($uploadDir . $fileLama);
    }
}

// Hapus gambar jika dicentang
if (isset($_POST['hapus_gambar']) && $_POST['hapus_gambar'] == '1' && empty($_FILES['gambar']['name'])) {
    if ($fileLama && file_exists($uploadDir . $fileLama)) unlink($uploadDir . $fileLama);
    $namaFile = null;
}

if ($id === 0) {
    if (!$namaFile) {
        echo "<script>alert('Foto wajib diisi!'); history.back();</script>"; exit;
    }
    $stmt = $conn->prepare("INSERT INTO galeri (judul, deskripsi, gambar, kategori, aktif, dibuat_oleh) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param("ssssii", $judul, $deskripsi, $namaFile, $kategori, $aktif, $username);
} else {
    $stmt = $conn->prepare("UPDATE galeri SET judul=?, deskripsi=?, gambar=?, kategori=?, aktif=? WHERE id=?");
    $stmt->bind_param("sssiii", $judul, $deskripsi, $namaFile, $kategori, $aktif, $id);
}

$ok = $stmt->execute();
$stmt->close();
header("Location: galeri.php?" . ($ok ? "saved=1" : "error=1"));
exit;