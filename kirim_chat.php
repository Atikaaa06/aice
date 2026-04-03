<?php
session_start();
include 'koneksi.php';
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['ok' => false, 'msg' => 'Tidak terautentikasi']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'msg' => 'Method tidak valid']);
    exit;
}

$pesan    = trim($_POST['pesan'] ?? '');
$username = $_SESSION['username'];
$role     = $_SESSION['role'];
$tipe     = $_POST['tipe'] ?? 'chat';
$gambar   = null; //penambahan variabel untuk menyimpan gambar
$penerima = null;
$pengirim = null;

if ($tipe === 'hapus') {
    $id = $_POST['id_pesan'] ?? null;

    if (!$id) {
        echo json_encode(['ok' => false, 'msg' => 'ID tidak ditemukan']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM pesan_chat WHERE id = ?");
    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();
    $stmt->close();

    echo json_encode(['ok' => $ok, 'msg' => $ok ? 'Berhasil dihapus' : 'Gagal hapus']);
    exit;
}

if ($tipe === 'ubah') {
    $newpesan = trim($_POST['ubah_pesan'] ?? '');
    $idpesan  = $_POST['id_pesan'] ?? null;

    if ($newpesan === '' || !$idpesan) {
        echo json_encode(['ok' => false, 'msg' => 'Pesan kosong atau ID tidak ada']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE pesan_chat SET pesan = ? WHERE id = ?");
    $stmt->bind_param("si", $newpesan, $idpesan);
    $ok = $stmt->execute();
    $stmt->close();

    echo json_encode(['ok' => $ok, 'msg' => $ok ? 'Berhasil diubah' : 'Gagal update']);
    exit;
}


// validasi di ubah menjadi pesan dan gambar
if ($pesan === '' && empty($_FILES['gambar']['name'])) {
    echo json_encode(['ok' => false, 'msg' => 'Pesan kosong']);
    exit;
}

// upload gambar, jika ada
if (!empty($_FILES['gambar']['name'])) {
    $namaFile = time() . '_' . $_FILES['gambar']['name'];
    move_uploaded_file($_FILES['gambar']['tmp_name'], "uploads/chats/" . $namaFile);
    $gambar = $namaFile;
}

if ($tipe === 'chat') {
    $pengirim = $username;
    if ($role === 'sales' || $role === 'pembeli') {
        $penerima = 'admin_asset';
    } else {
        $penerima = $_POST['penerima'];
    }
}

$stmt = $conn->prepare("INSERT INTO pesan_chat (username, role, pesan, tipe, pengirim, penerima, gambar) VALUES (?,?,?,?,?,?,?)");
$stmt->bind_param("sssssss", $username, $role, $pesan, $tipe, $pengirim, $penerima, $gambar);
$ok = $stmt->execute();
$stmt->close();

echo json_encode(['ok' => $ok, 'msg' => $ok ? 'Terkirim' : $conn->error]);
