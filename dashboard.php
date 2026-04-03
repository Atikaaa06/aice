<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
// Normalisasi role (support admin_assets dan admin_asset)
if (isset($_SESSION['role'])) {
    $_SESSION['role'] = rtrim($_SESSION['role'], 's') === 'admin_asset'
        ? 'admin_asset'
        : $_SESSION['role'];
    $_SESSION['role'] = rtrim($_SESSION['role'], 's') === 'admin_program'
        ? 'admin_program'
        : $_SESSION['role'];
}
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}
$role     = $_SESSION['role'];
$username = $_SESSION['username'];

// Hitung notifikasi untuk role yang punya inbox
$unread = 0;
if (in_array($role, ['penjual','admin_program','admin_asset'])) {
    if (!isset($conn)) include 'koneksi.php';
    $r1 = $conn->query("SELECT COUNT(*) as n FROM pesan_chat WHERE dibaca=0");
    $r2 = $conn->query("SELECT COUNT(*) as n FROM pesan_order WHERE status='pending'");
    $unread = ($r1->fetch_assoc()['n'] ?? 0) + ($r2->fetch_assoc()['n'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — PT JUMA TIGA SEANTERO</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .notif-dot {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 18px;
            height: 18px;
            background: #e53e3e;
            color: white;
            font-size: 10px;
            font-weight: 700;
            border-radius: 50px;
            padding: 0 5px;
            margin-left: 5px;
            vertical-align: middle;
        }
    </style>
</head>
<body>

<?php $navActive = 'dashboard'; include 'navbar.php'; ?>

<div class="page">

    <!-- Header -->
    <div style="animation:fadeUp .4s ease both;margin-bottom:20px;">
        <h1 style="font-family:'Playfair Display',serif;font-size:28px;color:var(--navy);margin-bottom:6px;">
            Selamat Datang, <?= htmlspecialchars($username) ?> 👋
        </h1>

        <!-- Role badge -->
        <?php
        $roleBadge = [
            'penjual'       => ['bg'=>'#fef9ec','color'=>'#92680c','border'=>'var(--gold)', 'icon'=>'⭐','label'=>'Penjual / Admin',  'desc'=>'Akses penuh semua fitur'],
            'admin_program' => ['bg'=>'#fef3c7','color'=>'#d97706','border'=>'#fcd34d',     'icon'=>'💻','label'=>'Admin Program',    'desc'=>'Kelola produk, pengumuman & user'],
            'admin_asset'   => ['bg'=>'#fdf4ff','color'=>'#9333ea','border'=>'#d8b4fe',     'icon'=>'✏️','label'=>'Admin Asset',      'desc'=>'Kelola data customer'],
            'sales'         => ['bg'=>'#f0fdf4','color'=>'#16a34a','border'=>'#86efac',     'icon'=>'👁️','label'=>'Sales',            'desc'=>'Lihat data produk & customer'],
            'pembeli'       => ['bg'=>'#eff6ff','color'=>'#1d4ed8','border'=>'#93c5fd',     'icon'=>'🛒','label'=>'Pembeli',          'desc'=>'Belanja produk'],
        ];
        $rb = $roleBadge[$role] ?? $roleBadge['pembeli'];
        ?>
        <div style="display:inline-flex;align-items:center;gap:8px;
                    background:<?= $rb['bg'] ?>;color:<?= $rb['color'] ?>;
                    border:1px solid <?= $rb['border'] ?>;
                    padding:7px 16px;border-radius:50px;font-size:12px;font-weight:600;
                    margin-top:8px;">
            <?= $rb['icon'] ?> <?= $rb['label'] ?> &nbsp;·&nbsp; <span style="font-weight:400;opacity:.8;"><?= $rb['desc'] ?></span>
        </div>
    </div>

    <!-- Menu Grid -->
    <div class="menu-grid">

        <?php if ($role === 'penjual'): ?>
            <a href="profil.php" class="menu-btn" style="animation-delay:0.04s">
                <div class="menu-btn-icon">🏢</div>
                <div class="menu-btn-label">Profil Perusahaan</div>
                <div class="menu-btn-desc">Info &amp; pengumuman perusahaan</div>
            </a>
            <a href="produk.php" class="menu-btn" style="animation-delay:0.08s">
                <div class="menu-btn-icon">📦</div>
                <div class="menu-btn-label">Kelola Produk</div>
                <div class="menu-btn-desc">Tambah, edit &amp; hapus produk</div>
            </a>
            <a href="kelola_pengumuman.php" class="menu-btn" style="animation-delay:0.12s">
                <div class="menu-btn-icon">📢</div>
                <div class="menu-btn-label">Info Terkini</div>
                <div class="menu-btn-desc">Kelola info &amp; promo terkini</div>
            </a>
            <a href="kelola_user.php" class="menu-btn" style="animation-delay:0.16s">
                <div class="menu-btn-icon">👤</div>
                <div class="menu-btn-label">Kelola User</div>
                <div class="menu-btn-desc">Tambah &amp; atur akun pengguna</div>
            </a>
            <a href="galeri.php" class="menu-btn" style="animation-delay:0.22s">
                <div class="menu-btn-icon">🖼️</div>
                <div class="menu-btn-label">Galeri</div>
                <div class="menu-btn-desc">Kelola foto &amp; dokumentasi</div>
            </a>
            <a href="broadcast.php" class="menu-btn" style="animation-delay:0.23s">
                <div class="menu-btn-icon">📣</div>
                <div class="menu-btn-label">Broadcast</div>
                <div class="menu-btn-desc">Kirim pesan ke semua user</div>
            </a>
            <a href="kelola_wilayah.php" class="menu-btn" style="animation-delay:0.25s">
                <div class="menu-btn-icon">📍</div>
                <div class="menu-btn-label">Kelola Wilayah</div>
                <div class="menu-btn-desc">Atur admin &amp; jangkauan wilayah</div>
            </a>
            <a href="inbox.php" class="menu-btn" style="animation-delay:0.27s">
                <div class="menu-btn-icon">
                    📬<?php if ($unread > 0): ?><span class="notif-dot"><?= $unread ?></span><?php endif; ?>
                </div>
                <div class="menu-btn-label">Inbox</div>
                <div class="menu-btn-desc"><?= $unread > 0 ? "$unread notifikasi baru" : "Pesan & pesanan pembeli" ?></div>
            </a>

        <?php elseif ($role === 'admin_program'): ?>
            <a href="profil.php" class="menu-btn" style="animation-delay:0.04s">
                <div class="menu-btn-icon">🏢</div>
                <div class="menu-btn-label">Profil Perusahaan</div>
                <div class="menu-btn-desc">Lihat info terkini</div>
            </a>
            <a href="produk.php" class="menu-btn" style="animation-delay:0.08s">
                <div class="menu-btn-icon">📦</div>
                <div class="menu-btn-label">Kelola Produk</div>
                <div class="menu-btn-desc">Edit harga &amp; kelola produk</div>
            </a>
            <a href="kelola_pengumuman.php" class="menu-btn" style="animation-delay:0.12s">
                <div class="menu-btn-icon">📢</div>
                <div class="menu-btn-label">Info Terkini</div>
                <div class="menu-btn-desc">Kelola info &amp; promo terkini</div>
            </a>
            <a href="kelola_user.php" class="menu-btn" style="animation-delay:0.16s">
                <div class="menu-btn-icon">👤</div>
                <div class="menu-btn-label">Kelola User</div>
                <div class="menu-btn-desc">Tambah &amp; atur akun pengguna</div>
            </a>
            <a href="galeri.php" class="menu-btn" style="animation-delay:0.22s">
                <div class="menu-btn-icon">🖼️</div>
                <div class="menu-btn-label">Galeri</div>
                <div class="menu-btn-desc">Kelola foto &amp; dokumentasi</div>
            </a>
            <a href="broadcast.php" class="menu-btn" style="animation-delay:0.23s">
                <div class="menu-btn-icon">📣</div>
                <div class="menu-btn-label">Broadcast</div>
                <div class="menu-btn-desc">Kirim pesan ke semua user</div>
            </a>
            <a href="inbox.php" class="menu-btn" style="animation-delay:0.24s">
                <div class="menu-btn-icon">
                    📬<?php if ($unread > 0): ?><span class="notif-dot"><?= $unread ?></span><?php endif; ?>
                </div>
                <div class="menu-btn-label">Inbox</div>
                <div class="menu-btn-desc"><?= $unread > 0 ? "$unread notifikasi baru" : "Pesan & pesanan masuk" ?></div>
            </a>

        <?php elseif ($role === 'admin_asset'): ?>
            <a href="profil.php" class="menu-btn" style="animation-delay:0.05s">
                <div class="menu-btn-icon">🏢</div>
                <div class="menu-btn-label">Profil Perusahaan</div>
                <div class="menu-btn-desc">Lihat info terkini</div>
            </a>
            <a href="customer.php" class="menu-btn" style="animation-delay:0.1s">
                <div class="menu-btn-icon">🏪</div>
                <div class="menu-btn-label">Data Customer</div>
                <div class="menu-btn-desc">Kelola nama toko, mesin &amp; lokasi</div>
            </a>
            <a href="inbox.php" class="menu-btn" style="animation-delay:0.15s">
                <div class="menu-btn-icon">📬</div>
                <div class="menu-btn-label">Inbox</div>
                <div class="menu-btn-desc">Lihat pesan &amp; pesanan masuk</div>
            </a>
            <a href="chat.php" class="menu-btn" style="animation-delay:0.2s">
                <div class="menu-btn-icon">💬</div>
                <div class="menu-btn-label">Kontak Chat</div>
                <div class="menu-btn-desc">Hubungi tim PT JUMA TIGA SEANTERO</div>
            </a>

        <?php elseif ($role === 'sales'): ?>
            <a href="profil.php" class="menu-btn" style="animation-delay:0.05s">
                <div class="menu-btn-icon">🏢</div>
                <div class="menu-btn-label">Profil Perusahaan</div>
                <div class="menu-btn-desc">Lihat info perusahaan</div>
            </a>
            <a href="produk.php" class="menu-btn" style="animation-delay:0.1s">
                <div class="menu-btn-icon">📦</div>
                <div class="menu-btn-label">Data Produk</div>
                <div class="menu-btn-desc">Lihat daftar produk</div>
            </a>
            <a href="customer.php" class="menu-btn" style="animation-delay:0.15s">
                <div class="menu-btn-icon">🏪</div>
                <div class="menu-btn-label">Data Customer</div>
                <div class="menu-btn-desc">Lihat data toko &amp; mesin</div>
            </a>
            <a href="profil.php" class="menu-btn" style="animation-delay:0.2s">
                <div class="menu-btn-icon">📢</div>
                <div class="menu-btn-label">Info Terkini</div>
                <div class="menu-btn-desc">Lihat info terkini</div>
            </a>
            <a href="chat.php" class="menu-btn" style="animation-delay:0.25s">
                <div class="menu-btn-icon">💬</div>
                <div class="menu-btn-label">Kontak Chat</div>
                <div class="menu-btn-desc">Hubungi tim PT JUMA TIGA SEANTERO</div>
            </a>

        <?php else: ?>
            <a href="profil.php" class="menu-btn" style="animation-delay:0.05s">
                <div class="menu-btn-icon">🏢</div>
                <div class="menu-btn-label">Profil Perusahaan</div>
                <div class="menu-btn-desc">Info &amp; pengumuman perusahaan</div>
            </a>
            <a href="produk.php" class="menu-btn" style="animation-delay:0.1s">
                <div class="menu-btn-icon">🛒</div>
                <div class="menu-btn-label">Belanja Produk</div>
                <div class="menu-btn-desc">Lihat &amp; beli produk tersedia</div>
            </a>
            <a href="chat.php" class="menu-btn" style="animation-delay:0.15s">
                <div class="menu-btn-icon">💬</div>
                <div class="menu-btn-label">Kontak Chat</div>
                <div class="menu-btn-desc">Hubungi penjual langsung</div>
            </a>
        <?php endif; ?>

    </div></div>

<p class="footer">© <?= date('Y') ?> PT JUMA TIGA SEANTERO</p>
</body>
</html>