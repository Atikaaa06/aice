<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'penjual') {
    header("Location: login.php"); exit;
}
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: produk.php"); exit;
}

$id       = (int)   ($_POST['id']           ?? 0);
$nama     = trim($_POST['nama_produk']      ?? '');
$harga    = (int)   ($_POST['harga']        ?? 0);
$stok     = isset($_POST['stok']) && $_POST['stok'] !== '' ? (int)$_POST['stok'] : null;
$kategori = trim($_POST['kategori']         ?? 'Umum');

if (!$id || !$nama || $harga <= 0) {
    echo "<script>alert('Data tidak valid!'); history.back();</script>"; exit;
}

// Cek kolom yang tersedia
$hasStok = $conn->query("SHOW COLUMNS FROM produk LIKE 'stok'")->fetch_assoc();
$hasKat  = $conn->query("SHOW COLUMNS FROM produk LIKE 'kategori'")->fetch_assoc();
$hasImg  = $conn->query("SHOW COLUMNS FROM produk LIKE 'gambar'")->fetch_assoc();

// ── HANDLE UPLOAD / HAPUS GAMBAR ─────────────────────
$namaGambar = null;
$uploadDir  = 'uploads/produk/';

if ($hasImg) {
    // Ambil gambar lama
    $stmtOld = $conn->prepare("SELECT gambar FROM produk WHERE id=?");
    $stmtOld->bind_param("i", $id);
    $stmtOld->execute();
    $gambarLama = $stmtOld->get_result()->fetch_assoc()['gambar'] ?? null;
    $stmtOld->close();
    $namaGambar = $gambarLama; // default: tetap pakai lama

    // Hapus gambar jika dicentang
    if (isset($_POST['hapus_gambar']) && $_POST['hapus_gambar'] == '1') {
        if ($gambarLama && file_exists($uploadDir . $gambarLama)) {
            unlink($uploadDir . $gambarLama);
        }
        $namaGambar = null;
    }

    // Upload gambar baru jika ada
    if (!empty($_FILES['gambar']['name'])) {
        $file    = $_FILES['gambar'];
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp','gif'];

        if (in_array($ext, $allowed) && $file['size'] <= 2*1024*1024) {
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $namaGambar = 'prod_' . time() . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $uploadDir . $namaGambar)) {
                // Hapus gambar lama kalau ada
                if ($gambarLama && file_exists($uploadDir . $gambarLama)) {
                    unlink($uploadDir . $gambarLama);
                }
            } else {
                $namaGambar = $gambarLama; // rollback
            }
        }
    }
}
// ─────────────────────────────────────────────────────

// Build UPDATE query berdasarkan kolom yang ada
if ($hasImg && $hasKat && $hasStok && $stok !== null) {
    $stmt = $conn->prepare("UPDATE produk SET nama_produk=?, kategori=?, gambar=?, harga=?, stok=? WHERE id=?");
    $stmt->bind_param("sssiii", $nama, $kategori, $namaGambar, $harga, $stok, $id);
} elseif ($hasImg && $hasKat) {
    $stmt = $conn->prepare("UPDATE produk SET nama_produk=?, kategori=?, gambar=?, harga=? WHERE id=?");
    $stmt->bind_param("sssii", $nama, $kategori, $namaGambar, $harga, $id);
} elseif ($hasImg) {
    $stmt = $conn->prepare("UPDATE produk SET nama_produk=?, gambar=?, harga=? WHERE id=?");
    $stmt->bind_param("ssii", $nama, $namaGambar, $harga, $id);
} elseif ($hasKat) {
    $stmt = $conn->prepare("UPDATE produk SET nama_produk=?, kategori=?, harga=? WHERE id=?");
    $stmt->bind_param("ssii", $nama, $kategori, $harga, $id);
} else {
    $stmt = $conn->prepare("UPDATE produk SET nama_produk=?, harga=? WHERE id=?");
    $stmt->bind_param("sii", $nama, $harga, $id);
}

$ok = $stmt->execute();
$stmt->close();

header("Location: produk.php?" . ($ok ? "edited=1" : "error=1"));
exit;