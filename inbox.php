<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Normalisasi role (sama seperti di Navbar.php)
$_rawRole = $_SESSION['role'] ?? '';
$_SESSION['role'] = match(true) {
    in_array($_rawRole, ['admin_assets','admin_asset'])    => 'admin_asset',
    in_array($_rawRole, ['admin_programs','admin_program']) => 'admin_program',
    default => $_rawRole
};

// Inbox bisa diakses oleh penjual, admin_program, dan admin_asset
$_rolesYangBoleh = ['penjual', 'admin_program', 'admin_asset'];
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], $_rolesYangBoleh)) {
    header("Location: login.php");
    exit;
}
include 'koneksi.php';

// Tandai semua chat sebagai sudah dibaca
$conn->query("UPDATE pesan_chat SET dibaca=1 WHERE dibaca=0");

// Ambil semua pesan order
$orders = $conn->query("
    SELECT po.*, p.nama_produk
    FROM pesan_order po
    JOIN produk p ON po.id_produk = p.id
    ORDER BY po.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

// Ambil semua chat, kelompokkan per user
$chats = $conn->query("
    SELECT * FROM pesan_chat ORDER BY username, created_at ASC
")->fetch_all(MYSQLI_ASSOC);

$chatPerUser = [];
foreach ($chats as $c) {
    $chatPerUser[$c['username']][] = $c;
}

// Hitung unread untuk badge (sudah di-reset di atas, tapi pakai cache sebelumnya)
$unreadCount = count($chats); // just untuk demo

// Update status pesanan jika ada aksi
if (isset($_POST['update_status'])) {
    $oid    = (int) $_POST['order_id'];
    $status = $_POST['status'];
    $valid  = ['pending','diproses','selesai','ditolak'];
    if (in_array($status, $valid)) {
        $stmt = $conn->prepare("UPDATE pesan_order SET status=? WHERE id=?");
        $stmt->bind_param("si", $status, $oid);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: inbox.php?updated=1");
    exit;
}

$activeUser = $_GET['user'] ?? (array_key_first($chatPerUser) ?? null);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inbox — PT Juma Tiga</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .inbox-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            align-items: start;
        }

        /* Order cards */
        .order-card {
            background: var(--white);
            border-radius: var(--radius-sm);
            border: 1.5px solid #e8eaf0;
            padding: 16px;
            margin-bottom: 12px;
            animation: fadeUp 0.3s ease both;
        }
        .order-card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        .order-title { font-weight: 600; font-size: 14px; color: var(--navy); }
        .order-meta  { font-size: 12px; color: var(--muted); margin-top: 2px; }

        .status-badge { display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:50px;font-size:11px;font-weight:600; }
        .status-pending  { background:#fef9ec; color:#92680c; border:1px solid var(--gold); }
        .status-diproses { background:#eff6ff; color:#1d4ed8; border:1px solid #93c5fd; }
        .status-selesai  { background:#f0fdf4; color:#16a34a; border:1px solid #86efac; }
        .status-ditolak  { background:#fdf0ef; color:#c0392b; border:1px solid #fca5a5; }

        .order-actions {
            display: flex;
            gap: 6px;
            margin-top: 12px;
            flex-wrap: wrap;
        }
        .act-btn {
            padding: 5px 12px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 600;
            border: 1.5px solid;
            cursor: pointer;
            font-family: 'DM Sans', sans-serif;
            transition: all 0.2s;
        }
        .act-proses  { border-color:#93c5fd; color:#1d4ed8; background:#eff6ff; }
        .act-selesai { border-color:#86efac; color:#16a34a; background:#f0fdf4; }
        .act-tolak   { border-color:#fca5a5; color:#c0392b; background:#fdf0ef; }
        .act-btn:hover { opacity: 0.75; }

        /* Chat panel */
        .chat-layout {
            display: grid;
            grid-template-columns: 180px 1fr;
            gap: 0;
            border: 1.5px solid #e8eaf0;
            border-radius: var(--radius);
            overflow: hidden;
            background: var(--white);
            min-height: 460px;
        }
        .chat-sidebar {
            background: var(--cream);
            border-right: 1.5px solid #e8eaf0;
            overflow-y: auto;
        }
        .chat-sidebar-title {
            padding: 14px 16px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--muted);
            border-bottom: 1px solid #e8eaf0;
        }
        .user-item {
            padding: 12px 16px;
            cursor: pointer;
            border-bottom: 1px solid #e8eaf0;
            transition: background 0.15s;
            text-decoration: none;
            display: block;
        }
        .user-item:hover { background: #f0f2f7; }
        .user-item.active { background: var(--navy); }
        .user-item.active .user-name { color: var(--white); }
        .user-item.active .user-tipe { color: var(--gold-light); }
        .user-name { font-size: 13px; font-weight: 600; color: var(--navy); }
        .user-tipe { font-size: 11px; color: var(--muted); margin-top: 2px; }

        .chat-main { display: flex; flex-direction: column; }
        .chat-main-header {
            padding: 14px 18px;
            border-bottom: 1px solid #e8eaf0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .chat-main-header h3 { font-size: 15px; font-weight: 600; color: var(--navy); }

        .chat-messages {
            flex: 1;
            padding: 16px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
            min-height: 340px;
            max-height: 340px;
        }
        .msg-bubble {
            max-width: 80%;
            padding: 10px 14px;
            border-radius: 14px;
            font-size: 13px;
            line-height: 1.5;
            align-self: flex-start;
            border-bottom-left-radius: 4px;
        }
        .msg-bubble.chat     { background: #eff6ff; color: #1e3a5f; }
        .msg-bubble.keluhan  { background: #fdf0ef; color: #7f1d1d; }
        .msg-bubble.masukan  { background: #f0fdf4; color: #14532d; }
        .msg-tipe {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.5px;
            opacity: 0.7;
            margin-bottom: 3px;
        }
        .msg-time { font-size: 10px; opacity: 0.5; margin-top: 4px; }

        .empty-chat {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 8px;
            color: var(--muted);
            font-size: 13px;
            padding: 40px;
            text-align: center;
        }
        .empty-chat span { font-size: 32px; }

        @media (max-width: 768px) {
            .inbox-layout { grid-template-columns: 1fr; }
            .chat-layout { grid-template-columns: 1fr; }
            .chat-sidebar { display: none; }
        }
    </style>
</head>
<body>

<?php $navActive = 'inbox'; include 'navbar.php'; ?>
<style>
.back-bar-global {
    background: var(--white);
    border-bottom: 1px solid #eef0f7;
    padding: 10px 40px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 1px 4px rgba(15,30,60,0.05);
}
.back-btn-global {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    color: var(--navy);
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    padding: 6px 16px;
    border-radius: 50px;
    border: 1.5px solid #e0e4ec;
    background: var(--cream);
    transition: all 0.2s;
    white-space: nowrap;
}
.back-btn-global:hover {
    background: var(--navy);
    color: white;
    border-color: var(--navy);
    transform: translateX(-2px);
}
.back-btn-global svg {
    transition: transform 0.2s;
}
.back-btn-global:hover svg {
    transform: translateX(-2px);
}
.breadcrumb-trail {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: var(--muted);
}
.breadcrumb-trail a {
    color: var(--muted);
    text-decoration: none;
    transition: color 0.2s;
}
.breadcrumb-trail a:hover { color: var(--navy); }
.breadcrumb-trail .sep { color: #ccc; }
.breadcrumb-trail .current { color: var(--navy); font-weight: 600; }
@media (max-width: 640px) {
    .back-bar-global { padding: 10px 16px; }
    .breadcrumb-trail { display: none; }
}
</style>
<div class="back-bar-global">
    <a href="dashboard.php" class="back-btn-global">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15,18 9,12 15,6"/></svg>
        ← Kembali ke Dashboard
    </a>
    <div class="breadcrumb-trail"><a href="dashboard.php">Dashboard</a><span class="sep">›</span><span class="current">Inbox</span></div>
</div>


<div class="page">
    <div class="page-header" style="animation:fadeUp .4s ease both;">
        <h1>📬 Inbox</h1>
        <p>Kelola pesanan & pesan dari pembeli</p>
    </div>

    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success" style="margin-bottom:20px;">✅ Status pesanan berhasil diperbarui.</div>
    <?php endif; ?>

    <div class="inbox-layout">

        <!-- KOLOM KIRI: Pesanan/Order -->
        <div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                <p style="font-size:11px;font-weight:600;letter-spacing:1px;text-transform:uppercase;color:var(--muted);">
                    📋 Pesanan Masuk (<?= count($orders) ?>)
                </p>
            </div>

            <?php if (empty($orders)): ?>
                <div class="card">
                    <div class="card-body" style="text-align:center;padding:40px;color:var(--muted);">
                        <div style="font-size:36px;margin-bottom:8px;">📭</div>
                        <p style="font-size:13px;">Belum ada pesanan masuk</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $i => $o):
                    $delay = ($i * 0.05) . 's';
                ?>
                <div class="order-card" style="animation-delay:<?= $delay ?>">
                    <div class="order-card-top">
                        <div>
                            <div class="order-title">📦 <?= htmlspecialchars($o['nama_produk']) ?></div>
                            <div class="order-meta">
                                👤 <?= htmlspecialchars($o['username']) ?> &middot;
                                <?= $o['jumlah'] ?> unit &middot;
                                <?= date('d M Y, H:i', strtotime($o['created_at'])) ?>
                            </div>
                            <?php if ($o['catatan']): ?>
                                <div style="font-size:12px;color:var(--muted);margin-top:6px;background:var(--cream);padding:8px 10px;border-radius:6px;">
                                    📝 <?= htmlspecialchars($o['catatan']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <span class="status-badge status-<?= $o['status'] ?>">
                            <?= ['pending'=>'⏳ Pending','diproses'=>'⚙️ Diproses','selesai'=>'✅ Selesai','ditolak'=>'❌ Ditolak'][$o['status']] ?>
                        </span>
                    </div>

                    <?php if ($o['status'] !== 'selesai' && $o['status'] !== 'ditolak'): ?>
                    <div class="order-actions">
                        <?php if ($o['status'] === 'pending'): ?>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                            <input type="hidden" name="status" value="diproses">
                            <button type="submit" name="update_status" class="act-btn act-proses">⚙️ Proses</button>
                        </form>
                        <?php endif; ?>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                            <input type="hidden" name="status" value="selesai">
                            <button type="submit" name="update_status" class="act-btn act-selesai">✅ Selesai</button>
                        </form>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                            <input type="hidden" name="status" value="ditolak">
                            <button type="submit" name="update_status" class="act-btn act-tolak">❌ Tolak</button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- KOLOM KANAN: Live Chat -->
        <div>
            <p style="font-size:11px;font-weight:600;letter-spacing:1px;text-transform:uppercase;color:var(--muted);margin-bottom:16px;">
                💬 Pesan dari Pembeli (<?= count($chatPerUser) ?> pengguna)
            </p>

            <?php if (empty($chatPerUser)): ?>
                <div class="card">
                    <div class="card-body" style="text-align:center;padding:40px;color:var(--muted);">
                        <div style="font-size:36px;margin-bottom:8px;">💬</div>
                        <p style="font-size:13px;">Belum ada pesan masuk</p>
                    </div>
                </div>
            <?php else: ?>
            <div class="chat-layout">
                <!-- Sidebar daftar user -->
                <div class="chat-sidebar">
                    <div class="chat-sidebar-title">Pengguna</div>
                    <?php foreach ($chatPerUser as $uname => $msgs):
                        $lastMsg = end($msgs);
                        $tipes = array_unique(array_column($msgs, 'tipe'));
                        $tipeIkon = in_array('keluhan', $tipes) ? '⚠️' : (in_array('masukan', $tipes) ? '💡' : '💬');
                    ?>
                    <a href="?user=<?= urlencode($uname) ?>"
                       class="user-item <?= ($activeUser === $uname) ? 'active' : '' ?>">
                        <div class="user-name"><?= htmlspecialchars($uname) ?></div>
                        <div class="user-tipe"><?= $tipeIkon ?> <?= count($msgs) ?> pesan</div>
                    </a>
                    <?php endforeach; ?>
                </div>

                <!-- Chat messages -->
                <div class="chat-main">
                    <?php if ($activeUser && isset($chatPerUser[$activeUser])): ?>
                    <div class="chat-main-header">
                        <span style="font-size:22px;">👤</span>
                        <h3><?= htmlspecialchars($activeUser) ?></h3>
                    </div>
                    <div class="chat-messages" id="adminChat">
                        <?php foreach ($chatPerUser[$activeUser] as $msg):
                            $tipeLabel = ['chat'=>'💬 Chat','keluhan'=>'⚠️ Keluhan','masukan'=>'💡 Masukan'][$msg['tipe']] ?? '💬';
                            $waktu = date('H:i · d M', strtotime($msg['created_at']));
                        ?>
                        <div class="msg-bubble <?= $msg['tipe'] ?>">
                            <div class="msg-tipe"><?= $tipeLabel ?></div>
                            <?= htmlspecialchars($msg['pesan']) ?>
                            <div class="msg-time"><?= $waktu ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="empty-chat">
                        <span>👈</span>
                        <p>Pilih pengguna untuk melihat percakapan</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

    </div><!-- inbox-layout -->
</div>

<p class="footer">© <?= date('Y') ?> PT Juma Tiga</p>

<script>
const ac = document.getElementById('adminChat');
if (ac) ac.scrollTop = ac.scrollHeight;
</script>
</body>
</html>