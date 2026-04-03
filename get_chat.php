<?php
session_start();
include 'koneksi.php';
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['ok' => false, 'msg' => 'Tidak terautentikasi']);
    exit;
}

$username = $_SESSION['username'];
$role = $_SESSION['role'];
$stmt = $conn->prepare("SELECT * FROM pesan_chat WHERE (username = ? OR penerima = ?) AND tipe = 'chat' ORDER BY created_at ASC");
$tipe = 'chat';
$stmt->bind_param("ss", $username, $role);
$stmt->execute();

$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
