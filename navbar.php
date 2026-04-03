<?php
/**
 * navbar.php — Komponen navbar global PT JUMA TIGA SEANTERO
 * Include di setiap halaman: <?php include 'navbar.php'; ?>
 * Variabel yang dibutuhkan: $_SESSION['username'], $_SESSION['role']
 * Opsional: $navActive = 'produk' | 'laporan' | 'karyawan' | 'pengumuman' | 'inbox' | 'profil' | 'riwayat' | 'chat'
 */

if (!isset($navActive)) $navActive = '';

// Normalisasi role — support admin_assets (dengan s) → admin_asset
$_rawRole  = $_SESSION['role'] ?? '';
$_role     = match(true) {
    in_array($_rawRole, ['admin_assets','admin_asset'])   => 'admin_asset',
    in_array($_rawRole, ['admin_programs','admin_program'])=> 'admin_program',
    default => $_rawRole
};
$_SESSION['role'] = $_role; // update session agar konsisten
$_username = $_SESSION['username'] ?? '';

// Hitung notifikasi untuk penjual
$_unread = 0;
if (in_array($_role, ['penjual','admin_program','admin_asset']) && isset($conn)) {
    $r1 = $conn->query("SELECT COUNT(*) as n FROM pesan_chat  WHERE dibaca=0");
    $r2 = $conn->query("SELECT COUNT(*) as n FROM pesan_order WHERE status='pending'");
    $_unread = ($r1 ? $r1->fetch_assoc()['n'] : 0) + ($r2 ? $r2->fetch_assoc()['n'] : 0);
}
?>

<nav class="navbar">
    <!-- ── Baris 1: Logo + User ── -->
    <div class="navbar-top">
        <a href="<?= in_array($_role, ['penjual','admin_program','admin_asset','sales']) ? 'dashboard.php' : 'profil.php' ?>" class="navbar-brand">
            <img src="assets/logo_jt2.jpeg" alt="JT"
                 style="width:36px;height:36px;object-fit:contain;border-radius:50%;
                        background:white;border:2px solid var(--gold);
                        box-shadow:0 2px 8px rgba(201,168,76,0.3);">
            PT <span>JUMA TIGA SEANTERO</span>
        </a>
        <div class="navbar-right">
            <?php if ($_username): ?>
                <span class="nav-user">
                    👤 <strong><?= htmlspecialchars($_username) ?></strong>
                    &nbsp;·&nbsp; <?php
$_roleLabel = [
    'penjual'       => 'Penjual',
    'admin_program' => 'Admin Program',
    'admin_asset'   => 'Admin Asset',
    'sales'         => 'Sales',
    'pembeli'       => 'Pembeli',
];
echo $_roleLabel[$_role] ?? ucfirst($_role);
?>
                </span>
            <?php endif; ?>
            <a href="profil.php"   class="nav-btn-sm">🏢 Profil</a>
            <a href="logout.php"   class="nav-btn-sm logout">Logout</a>
        </div>
    </div>

    <!-- ── Baris 2: Menu Navigasi ── -->
    <div class="navbar-menu">
        <ul class="navbar-menu-list">
            <?php if ($_role === 'penjual'): ?>
                <!-- PENJUAL -->
                <li><a href="dashboard.php" class="nav-link <?= $navActive==='dashboard'?'active':'' ?>">🏠 Dashboard</a></li>
                <li><a href="profil.php"    class="nav-link <?= $navActive==='profil'?'active':'' ?>">🏢 Perusahaan</a></li>
                <li><a href="produk.php"    class="nav-link <?= $navActive==='produk'?'active':'' ?>">📦 Produk</a></li>
                <li><a href="kelola_pengumuman.php" class="nav-link <?= $navActive==='pengumuman'?'active':'' ?>">📢 Info Terkini</a></li>
                <li><a href="kelola_user.php" class="nav-link <?= $navActive==='user'?'active':'' ?>">👤 Kelola User</a></li>
                <li><a href="broadcast.php" class="nav-link <?= $navActive==='broadcast'?'active':'' ?>">📣 Broadcast</a></li>
                <li><a href="kelola_wilayah.php" class="nav-link <?= $navActive==='wilayah'?'active':'' ?>">📍 Wilayah</a></li>
                <li><a href="galeri.php" class="nav-link <?= $navActive==='galeri'?'active':'' ?>">🖼️ Galeri</a></li>
                <li><a href="inbox.php" class="nav-link nav-link-right <?= $navActive==='inbox'?'active':'' ?>">
                    📬 Inbox<?php if ($_unread>0): ?><span class="notif-dot"><?= $_unread ?></span><?php endif; ?>
                </a></li>

            <?php elseif ($_role === 'admin_program'): ?>
                <!-- ADMIN PROGRAM: kelola produk, pengumuman, inbox, kelola user — lihat customer -->
                <li><a href="dashboard.php" class="nav-link <?= $navActive==='dashboard'?'active':'' ?>">🏠 Dashboard</a></li>
                <li><a href="profil.php"    class="nav-link <?= $navActive==='profil'?'active':'' ?>">🏢 Perusahaan</a></li>
                <li><a href="produk.php"    class="nav-link <?= $navActive==='produk'?'active':'' ?>">📦 Produk</a></li>
                <li><a href="kelola_pengumuman.php" class="nav-link <?= $navActive==='pengumuman'?'active':'' ?>">📢 Info Terkini</a></li>
                <li><a href="kelola_user.php" class="nav-link <?= $navActive==='user'?'active':'' ?>">👤 Kelola User</a></li>
                <li><a href="galeri.php" class="nav-link <?= $navActive==='galeri'?'active':'' ?>">🖼️ Galeri</a></li>
                <li><a href="inbox.php" class="nav-link nav-link-right <?= $navActive==='inbox'?'active':'' ?>">
                    📬 Inbox<?php if ($_unread>0): ?><span class="notif-dot"><?= $_unread ?></span><?php endif; ?>
                </a></li>

            <?php elseif ($_role === 'admin_asset'): ?>
                <!-- ADMIN ASSET: profil, customer, chat -->
                <li><a href="dashboard.php" class="nav-link <?= $navActive==='dashboard'?'active':'' ?>">🏠 Dashboard</a></li>
                <li><a href="profil.php"    class="nav-link <?= $navActive==='profil'?'active':'' ?>">🏢 Perusahaan</a></li>
                <li><a href="customer.php"  class="nav-link <?= $navActive==='customer'?'active':'' ?>">🏪 Customer</a></li>
                <li><a href="chat.php"      class="nav-link nav-link-right <?= $navActive==='chat'?'active':'' ?>">💬 Kontak Chat</a></li>

            <?php elseif ($_role === 'sales'): ?>
                <!-- SALES: profil, produk, customer, pengumuman, kontak -->
                <li><a href="dashboard.php" class="nav-link <?= $navActive==='dashboard'?'active':'' ?>">🏠 Dashboard</a></li>
                <li><a href="profil.php"    class="nav-link <?= $navActive==='profil'?'active':'' ?>">🏢 Perusahaan</a></li>
                <li><a href="produk.php"    class="nav-link <?= $navActive==='produk'?'active':'' ?>">📦 Produk</a></li>
                <li><a href="customer.php"  class="nav-link <?= $navActive==='customer'?'active':'' ?>">🏪 Customer</a></li>
                <li><a href="profil.php"    class="nav-link <?= $navActive==='pengumuman'?'active':'' ?>">📢 Info Terkini</a></li>
                <li><a href="chat.php"      class="nav-link nav-link-right <?= $navActive==='chat'?'active':'' ?>">💬 Kontak Chat</a></li>

            <?php else: ?>
                <!-- PEMBELI: profil, produk, pengumuman, kontak -->
                <li><a href="dashboard.php" class="nav-link <?= $navActive==='dashboard'?'active':'' ?>">🏠 Dashboard</a></li>
                <li><a href="profil.php"    class="nav-link <?= $navActive==='profil'?'active':'' ?>">🏢 Tentang Kami</a></li>
                <li><a href="produk.php"    class="nav-link <?= $navActive==='produk'?'active':'' ?>">🛒 Produk</a></li>
                <li><a href="profil.php"    class="nav-link <?= $navActive==='pengumuman'?'active':'' ?>">📢 Info Terkini</a></li>
                <li><a href="chat.php"      class="nav-link nav-link-right <?= $navActive==='chat'?'active':'' ?>">💬 Kontak Kami</a></li>
            <?php endif; ?>

        </ul>    </div>
</nav>