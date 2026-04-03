<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'koneksi.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok'=>false,'msg'=>'Method tidak valid']); exit;
}

$nama  = trim($_POST['nama']  ?? '');
$pesan = trim($_POST['pesan'] ?? '');
$tipe  = $_POST['tipe'] ?? 'chat';

if (!$nama || !$pesan) {
    echo json_encode(['ok'=>false,'msg'=>'Nama dan pesan wajib diisi']); exit;
}

$tipeValid = ['chat','keluhan','masukan'];
if (!in_array($tipe, $tipeValid)) $tipe = 'chat';

// Selalu simpan ke pesan_chat dengan role='publik' dan dibaca=0
// Ini yang akan dibaca admin_asset di chat.php
$role = 'publik';
$stmt = $conn->prepare("INSERT INTO pesan_chat (username, role, pesan, tipe, dibaca) VALUES (?,?,?,?,0)");
$stmt->bind_param("ssss", $nama, $role, $pesan, $tipe);
$ok = $stmt->execute();
$stmt->close();

echo json_encode(['ok'=>$ok, 'msg'=> $ok ? 'Pesan terkirim!' : $conn->error]);