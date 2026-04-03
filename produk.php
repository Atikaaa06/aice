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
if (!isset($_SESSION['username'])) { header("Location: login.php"); exit; }
include 'koneksi.php';

$role     = $_SESSION['role'];
$username = $_SESSION['username'];

// Filter & search
$search    = trim($_GET['search']    ?? '');
$sort      = $_GET['sort']           ?? 'terbaru';
$kategori  = trim($_GET['kategori']  ?? '');

// Cek apakah kolom kategori ada di tabel
$hasKategori = $conn->query("SHOW COLUMNS FROM produk LIKE 'kategori'")->fetch_assoc();

// Ambil semua kategori unik (hanya jika kolom ada)
$kategoriList = [];
if ($hasKategori) {
    $kRows = $conn->query("SELECT DISTINCT kategori FROM produk WHERE kategori IS NOT NULL AND kategori != '' ORDER BY kategori ASC")->fetch_all(MYSQLI_ASSOC);
    $kategoriList = array_column($kRows, 'kategori');
}

$where  = ["1=1"];
$params = [];
$types  = '';

if ($search !== '') {
    $where[]  = "nama_produk LIKE ?";
    $params[] = "%$search%";
    $types   .= 's';
}
if ($hasKategori && $kategori !== '' && $kategori !== 'semua') {
    $where[]  = "kategori = ?";
    $params[] = $kategori;
    $types   .= 's';
}

$orderBy = match($sort) {
    'termurah' => 'harga ASC',
    'termahal' => 'harga DESC',
    'nama'     => 'nama_produk ASC',
    default    => 'id DESC'
};

$sql = "SELECT * FROM produk WHERE " . implode(' AND ', $where) . " ORDER BY $orderBy";
$stmt = $conn->prepare($sql);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$produkList = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$totalProduk = count($produkList);

// Icon & warna per kategori
$katConfig = [
    'Cone'           => ['icon'=>'🍦','bg'=>'#fff3e0','border'=>'#f97316','color'=>'#ea580c'],
    'Paket Keluarga' => ['icon'=>'👨‍👩‍👧','bg'=>'#f0fdf4','border'=>'#22c55e','color'=>'#16a34a'],
    'Gelas'          => ['icon'=>'🥤','bg'=>'#eff6ff','border'=>'#60a5fa','color'=>'#2563eb'],
    'Premium'        => ['icon'=>'👑','bg'=>'#fdf4ff','border'=>'#c084fc','color'=>'#9333ea'],
    'Umum'           => ['icon'=>'📦','bg'=>'#f8fafc','border'=>'#94a3b8','color'=>'#64748b'],
    'Es Krim'        => ['icon'=>'🍨','bg'=>'#fdf2f8','border'=>'#f472b6','color'=>'#db2777'],
    'Minuman'        => ['icon'=>'🧃','bg'=>'#ecfdf5','border'=>'#34d399','color'=>'#059669'],
    'Makanan'        => ['icon'=>'🍱','bg'=>'#fffbeb','border'=>'#fbbf24','color'=>'#d97706'],
    'Snack'          => ['icon'=>'🍿','bg'=>'#fef2f2','border'=>'#fca5a5','color'=>'#dc2626'],
];

function getKatCfg($nama, $katConfig) {
    return $katConfig[$nama] ?? ['icon'=>'🏷️','bg'=>'#f1f5f9','border'=>'#cbd5e1','color'=>'#475569'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produk — PT JUMA TIGA SEANTERO</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* ── HERO PRODUK ─────────────────────── */
        .produk-hero {
            background: var(--navy);
            padding: 36px 40px 80px;
            position: relative;
            overflow: hidden;
        }
        .produk-hero::before {
            content: '';
            position: absolute;
            top: -100px; right: -80px;
            width: 350px; height: 350px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(201,168,76,0.14), transparent 65%);
            pointer-events: none;
        }
        .produk-hero::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image: radial-gradient(rgba(255,255,255,0.03) 1px, transparent 1px);
            background-size: 24px 24px;
        }
        .produk-hero-inner {
            position: relative; z-index: 1;
            max-width: 1060px; margin: 0 auto;
        }
        .hero-label {
            display: inline-flex; align-items: center; gap: 6px;
            background: var(--gold);
            color: var(--navy);
            font-size: 11px; font-weight: 700;
            letter-spacing: 2px; text-transform: uppercase;
            padding: 5px 14px; border-radius: 50px;
            margin-bottom: 14px;
            animation: fadeUp 0.4s ease both;
        }
        .produk-hero h1 {
            font-family: 'Playfair Display', serif;
            color: white; font-size: clamp(28px, 4vw, 42px);
            margin-bottom: 6px;
            animation: fadeUp 0.4s ease 0.06s both;
        }
        .produk-hero p {
            color: rgba(255,255,255,0.55);
            font-size: 14px; font-weight: 300;
            animation: fadeUp 0.4s ease 0.1s both;
        }

        /* ── KATEGORI CAROUSEL ───────────────── */
        .kategori-wrap {
            max-width: 1060px;
            margin: -40px auto 0;
            padding: 0 24px;
            position: relative;
            z-index: 10;
        }
        .kategori-card {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            padding: 24px 16px 20px;
            animation: fadeUp 0.5s ease 0.15s both;
        }
        .kategori-scroll-wrap {
            position: relative;
        }
        .kategori-list {
            display: flex;
            gap: 12px;
            overflow-x: auto;
            scroll-behavior: smooth;
            padding: 4px 8px 8px;
            scrollbar-width: none;
        }
        .kategori-list::-webkit-scrollbar { display: none; }

        .kat-item {
            display: flex; flex-direction: column;
            align-items: center; gap: 10px;
            cursor: pointer; flex-shrink: 0;
            width: 90px;
            text-decoration: none;
            transition: transform 0.2s;
        }
        .kat-item:hover { transform: translateY(-3px); }
        .kat-item.active .kat-circle {
            box-shadow: 0 0 0 3px var(--gold);
        }

        .kat-circle {
            width: 68px; height: 68px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 28px;
            border: 2.5px solid transparent;
            transition: all 0.2s;
            position: relative;
        }
        .kat-circle.active {
            border-color: var(--gold) !important;
            box-shadow: 0 4px 16px rgba(201,168,76,0.35);
        }
        .kat-label {
            font-size: 11px; font-weight: 700;
            text-align: center; line-height: 1.3;
            text-transform: uppercase; letter-spacing: 0.5px;
            color: var(--navy);
        }
        .kat-badge {
            position: absolute; top: -3px; right: -3px;
            background: var(--navy); color: white;
            font-size: 9px; font-weight: 700;
            width: 18px; height: 18px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            border: 2px solid white;
        }

        /* Scroll arrows */
        .kat-arrow {
            position: absolute; top: 50%; transform: translateY(-60%);
            width: 32px; height: 32px;
            background: white;
            border: 1.5px solid #e0e4ec;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; font-size: 14px;
            box-shadow: var(--shadow-sm);
            transition: all 0.2s; z-index: 2;
            color: var(--navy);
        }
        .kat-arrow:hover { background: var(--navy); color: white; border-color: var(--navy); }
        .kat-arrow.left  { left: -8px; }
        .kat-arrow.right { right: -8px; }

        /* ── PRODUK AREA ─────────────────────── */
        .produk-area {
            max-width: 1060px;
            margin: 36px auto 60px;
            padding: 0 24px;
        }

        /* Filter bar */
        .filter-bar {
            background: var(--white);
            border-radius: var(--radius);
            padding: 14px 18px;
            box-shadow: var(--shadow-sm);
            display: flex; gap: 10px; flex-wrap: wrap;
            align-items: center; margin-bottom: 22px;
        }
        .filter-bar input,
        .filter-bar select {
            padding: 9px 14px;
            border: 1.5px solid #e0e4ec;
            border-radius: var(--radius-sm);
            font-size: 13px; font-family: 'DM Sans', sans-serif;
            background: var(--cream); outline: none;
            transition: border-color 0.2s;
        }
        .filter-bar input  { flex: 1; min-width: 180px; }
        .filter-bar input:focus,
        .filter-bar select:focus { border-color: var(--gold); }
        .btn-cari {
            padding: 9px 20px; background: var(--navy); color: white;
            border: none; border-radius: var(--radius-sm);
            font-family: 'DM Sans', sans-serif; font-size: 13px;
            font-weight: 600; cursor: pointer; transition: background 0.2s;
        }
        .btn-cari:hover { background: var(--navy-mid); }

        /* Hasil header */
        .hasil-header {
            display: flex; align-items: center;
            justify-content: space-between;
            margin-bottom: 18px; flex-wrap: wrap; gap: 10px;
        }
        .hasil-label {
            font-size: 14px; color: var(--muted); font-weight: 500;
        }
        .hasil-label strong { color: var(--navy); }

        /* Product card override */
        .product-card { position: relative; }
        .kat-tag {
            display: inline-block;
            padding: 2px 9px; border-radius: 50px;
            font-size: 10px; font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        /* Penjual action buttons */
        .penjual-actions { display: flex; gap: 6px; margin-top: 10px; }
        .btn-edit-p {
            flex:1; padding:7px 10px;
            background:#eff6ff; color:#1d4ed8;
            border:1.5px solid #93c5fd; border-radius:var(--radius-sm);
            font-size:12px; font-weight:600; cursor:pointer;
            font-family:'DM Sans',sans-serif; text-decoration:none;
            text-align:center; transition:all .2s;
            display:flex; align-items:center; justify-content:center; gap:4px;
        }
        .btn-edit-p:hover { background:#dbeafe; }
        .btn-del-p {
            flex:1; padding:7px 10px;
            background:#fdf0ef; color:#c0392b;
            border:1.5px solid #fca5a5; border-radius:var(--radius-sm);
            font-size:12px; font-weight:600; cursor:pointer;
            font-family:'DM Sans',sans-serif; transition:all .2s;
            display:flex; align-items:center; justify-content:center; gap:4px;
        }
        .btn-del-p:hover { background:#fee2e2; }

        /* Empty state */
        .empty-state {
            text-align:center; padding:60px 20px;
            background:var(--white); border-radius:var(--radius);
            color:var(--muted); box-shadow:var(--shadow-sm);
        }
        .empty-state .ei { font-size:48px; margin-bottom:12px; }

        /* Modal hapus */
        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(15,30,60,0.55); z-index:200; align-items:center; justify-content:center; padding:20px; }
        .modal-overlay.open { display:flex; animation:fadeIn .2s ease; }
        @keyframes fadeIn{from{opacity:0}to{opacity:1}}
        .modal-konfirm { background:white; border-radius:var(--radius); width:100%; max-width:380px; box-shadow:var(--shadow-lg); animation:slideIn .3s cubic-bezier(.22,.68,0,1.2) both; overflow:hidden; }
        .modal-konfirm-head { padding:20px 24px; display:flex; align-items:center; gap:12px; }
        .modal-konfirm-head h3 { color:white; font-family:'Playfair Display',serif; font-size:17px; }
        .modal-konfirm-body { padding:20px 24px; }
        .modal-konfirm-body p { font-size:14px; color:var(--text); line-height:1.6; }
        .modal-konfirm-footer { padding:14px 24px; border-top:1px solid #f0f2f7; display:flex; justify-content:flex-end; gap:10px; }
        .btn-batal-hapus { padding:9px 18px; background:transparent; color:var(--muted); border:1.5px solid #e0e4ec; border-radius:var(--radius-sm); font-family:'DM Sans',sans-serif; font-size:14px; cursor:pointer; transition:all .2s; }
        .btn-batal-hapus:hover { border-color:var(--navy); color:var(--navy); }
        .btn-hapus-ok { padding:9px 18px; background:#c0392b; color:white; border:none; border-radius:var(--radius-sm); font-family:'DM Sans',sans-serif; font-size:14px; font-weight:600; cursor:pointer; transition:background .2s; }
        .btn-hapus-ok:hover { background:#a93226; }

        @media (max-width: 640px) {
            .produk-hero { padding: 28px 20px 70px; }
            .kat-item { width: 75px; }
            .kat-circle { width: 58px; height: 58px; font-size: 24px; }
        }
    </style>
</head>
<body>

<?php $navActive = 'produk'; include 'navbar.php'; ?>
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
    <div class="breadcrumb-trail"><a href="dashboard.php">Dashboard</a><span class="sep">›</span><span class="current">Produk</span></div>
</div>


<!-- Hero -->
<div class="produk-hero">
    <div class="produk-hero-inner">
        <div class="hero-label">🛒 PRODUCTS</div>
        <h1><?= $kategori && $kategori !== 'semua' ? htmlspecialchars($kategori) : 'Semua Produk' ?></h1>
        <p><?= $totalProduk ?> produk<?= $kategori && $kategori !== 'semua' ? ' dalam kategori ini' : ' tersedia untuk kamu' ?></p>
    </div>
</div>

<!-- Kategori Carousel -->
<div class="kategori-wrap">
    <div class="kategori-card">
        <div class="kategori-scroll-wrap">
            <button class="kat-arrow left"  onclick="scrollKat(-1)" id="arrowLeft">‹</button>
            <button class="kat-arrow right" onclick="scrollKat(1)"  id="arrowRight">›</button>

            <div class="kategori-list" id="katList">
                <?php
                // Hitung jumlah produk per kategori
                $katCount = [];
                if ($hasKategori) {
                    $allKatResult = $conn->query("SELECT kategori, COUNT(*) as n FROM produk GROUP BY kategori");
                    while ($kr = $allKatResult->fetch_assoc()) {
                        $katCount[$kr['kategori']] = $kr['n'];
                    }
                }
                $totalAllProduk = $hasKategori ? array_sum($katCount) : ($conn->query("SELECT COUNT(*) as n FROM produk")->fetch_assoc()['n'] ?? 0);

                // Item "Semua"
                $isSemua = ($kategori === '' || $kategori === 'semua');
                ?>
                <a href="produk.php" class="kat-item <?= $isSemua ? 'active' : '' ?>">
                    <div class="kat-circle <?= $isSemua ? 'active' : '' ?>"
                         style="background:#f1f5f9;border-color:#cbd5e1;">
                        🏪
                        <span class="kat-badge"><?= $totalAllProduk ?></span>
                    </div>
                    <span class="kat-label">SEMUA</span>
                </a>

                <?php foreach ($kategoriList as $kat):
                    $cfg     = getKatCfg($kat, $katConfig);
                    $isAktif = ($kategori === $kat);
                    $cnt     = $katCount[$kat] ?? 0;
                ?>
                <a href="produk.php?kategori=<?= urlencode($kat) ?>" class="kat-item <?= $isAktif ? 'active' : '' ?>">
                    <div class="kat-circle <?= $isAktif ? 'active' : '' ?>"
                         style="background:<?= $cfg['bg'] ?>;border-color:<?= $cfg['border'] ?>;">
                        <?= $cfg['icon'] ?>
                        <?php if ($cnt > 0): ?>
                            <span class="kat-badge"><?= $cnt ?></span>
                        <?php endif; ?>
                    </div>
                    <span class="kat-label" style="color:<?= $isAktif ? $cfg['color'] : 'var(--navy)' ?>">
                        <?= htmlspecialchars(strtoupper($kat)) ?>
                    </span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Alert -->
<div class="produk-area">
    <?php if (isset($_GET['edited'])): ?>
        <div class="alert alert-success" style="margin-bottom:20px;">✅ Produk berhasil diperbarui.</div>
    <?php elseif (isset($_GET['deleted'])): ?>
        <div class="alert alert-error" style="margin-bottom:20px;">🗑️ Produk berhasil dihapus.</div>
    <?php elseif (isset($_GET['added'])): ?>
        <div class="alert alert-success" style="margin-bottom:20px;">✅ Produk baru berhasil ditambahkan.</div>
    <?php endif; ?>

    <!-- Filter & Sort -->
    <form method="GET" class="filter-bar">
        <?php if ($kategori): ?><input type="hidden" name="kategori" value="<?= htmlspecialchars($kategori) ?>"><?php endif; ?>
        <input type="text" name="search" placeholder="🔍 Cari nama produk..." value="<?= htmlspecialchars($search) ?>">
        <select name="sort">
            <option value="terbaru"  <?= $sort==='terbaru'  ?'selected':'' ?>>Terbaru</option>
            <option value="termurah" <?= $sort==='termurah' ?'selected':'' ?>>Harga Termurah</option>
            <option value="termahal" <?= $sort==='termahal' ?'selected':'' ?>>Harga Termahal</option>
            <option value="nama"     <?= $sort==='nama'     ?'selected':'' ?>>A–Z Nama</option>
        </select>
        <button type="submit" class="btn-cari">Cari</button>
        <?php if ($search || ($sort !== 'terbaru')): ?>
            <a href="produk.php<?= $kategori ? '?kategori='.urlencode($kategori) : '' ?>" style="font-size:13px;color:var(--muted);text-decoration:none;padding:9px 4px;">Reset</a>
        <?php endif; ?>
        <?php if (in_array($role, ['penjual','admin_program'])): ?>
            <a href="tambah_produk.php" class="btn-cari" style="background:var(--gold);color:var(--navy);text-decoration:none;display:inline-flex;align-items:center;gap:6px;">+ Tambah</a>
        <?php endif; ?>
    </form>

    <!-- Hasil header -->
    <div class="hasil-header">
        <div class="hasil-label">
            Menampilkan <strong><?= $totalProduk ?></strong> produk
            <?php if ($kategori && $kategori !== 'semua'): ?>
                dalam kategori <strong><?= htmlspecialchars($kategori) ?></strong>
            <?php endif; ?>
            <?php if ($search): ?>
                untuk pencarian <strong>"<?= htmlspecialchars($search) ?>"</strong>
            <?php endif; ?>
        </div>
    </div>

    <!-- Grid Produk -->
    <?php if (empty($produkList)): ?>
        <div class="empty-state">
            <div class="ei">📦</div>
            <p>Tidak ada produk<?= $search ? ' yang cocok' : '' ?>.</p>
            <?php if ($role === 'penjual' && !$search): ?>
                <a href="tambah_produk.php" class="btn btn-primary" style="width:auto;padding:10px 24px;display:inline-flex;margin-top:16px;">+ Tambah Produk</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="product-grid">
            <?php
            $produkIcons = ['📦','🎁','🛍️','💼','🏷️','🎀','🍦','🍨','🥤','🍱'];
            foreach ($produkList as $i => $row):
                $delay   = ($i * 0.05) . 's';
                $kat     = ($hasKategori && isset($row['kategori'])) ? $row['kategori'] : 'Umum';
                $cfg     = getKatCfg($kat, $katConfig);
                $icon    = $cfg['icon'] ?? $produkIcons[$i % count($produkIcons)];
            ?>
            <div class="product-card" style="animation-delay:<?= $delay ?>">
                <!-- Badge kategori -->
                <span class="kat-tag"
                      style="background:<?= $cfg['bg'] ?>;color:<?= $cfg['color'] ?>;border:1px solid <?= $cfg['border'] ?>">
                    <?= $icon ?> <?= htmlspecialchars($kat) ?>
                </span>
                <?php
                $uploadDir   = 'uploads/produk/';
                $gambarProd  = $row['gambar'] ?? null;
                $hasGambar   = $gambarProd && file_exists($uploadDir . $gambarProd);
                ?>
                <?php if ($hasGambar): ?>
                    <div style="width:100%;height:140px;overflow:hidden;border-radius:8px;margin-bottom:8px;background:var(--cream);">
                        <img src="<?= $uploadDir . htmlspecialchars($gambarProd) ?>"
                             alt="<?= htmlspecialchars($row['nama_produk']) ?>"
                             style="width:100%;height:100%;object-fit:cover;transition:transform .3s;"
                             onmouseover="this.style.transform='scale(1.05)'"
                             onmouseout="this.style.transform='scale(1)'">
                    </div>
                <?php else: ?>
                    <div class="product-icon"><?= $icon ?></div>
                <?php endif; ?>
                <div class="product-name"><?= htmlspecialchars($row['nama_produk']) ?></div>
                <div class="product-price">Rp <?= number_format($row['harga'], 0, ',', '.') ?></div>
                <?php if (isset($row['stok'])): ?>
                    <div style="font-size:12px;color:var(--muted);">Stok: <?= $row['stok'] ?> unit</div>
                <?php endif; ?>

                <?php if (in_array($role, ['penjual','admin_program'])): ?>
                    <div class="penjual-actions">
                        <a href="edit_produk.php?id=<?= $row['id'] ?>" class="btn-edit-p">✏️ Edit</a>
                        <button class="btn-del-p"
                            onclick="konfirmasiHapus(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['nama_produk'])) ?>')">
                            🗑️ Hapus
                        </button>
                    </div>
                <?php elseif ($role === 'sales'): ?>
                    <div style="margin-top:auto;padding:8px 12px;background:var(--cream);border-radius:8px;font-size:12px;color:var(--muted);text-align:center;border:1px solid #e8eaf0;">
                        👁️ Lihat Saja
                    </div>
                <?php elseif ($role === 'pembeli'): ?>
                    <div style="display:flex;gap:6px;margin-top:auto;">
                        <a href="detail_produk.php?id=<?= $row['id'] ?>" class="btn btn-gold" style="flex:1;font-size:12px;padding:8px;">🔍 Detail</a>
                        <a href="beli.php?id=<?= $row['id'] ?>" class="btn" style="flex:1;background:var(--navy);color:white;font-size:12px;padding:8px;text-align:center;text-decoration:none;border-radius:var(--radius-sm);">🛒 Beli</a>
                    </div>
                <?php else: ?>
                    <a href="beli.php?id=<?= $row['id'] ?>" class="btn btn-gold" style="margin-top:auto;">Beli Sekarang</a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<p class="footer">© <?= date('Y') ?> PT JUMA TIGA SEANTERO</p>

<!-- Modal Konfirmasi Hapus -->
<div class="modal-overlay" id="modalHapus" onclick="if(event.target===this)tutupModal()">
    <div class="modal-konfirm">
        <div class="modal-konfirm-head" style="background:#c0392b;">
            <span style="font-size:24px;">🗑️</span>
            <h3>Hapus Produk?</h3>
        </div>
        <div class="modal-konfirm-body">
            <p>Kamu akan menghapus produk:</p>
            <p style="margin:12px 0;padding:12px 16px;background:var(--cream);border-radius:8px;font-weight:600;color:var(--navy);" id="namaProdukHapus"></p>
            <p style="color:var(--muted);font-size:13px;">⚠️ Data yang dihapus <strong>tidak bisa dikembalikan</strong>.</p>
        </div>
        <div class="modal-konfirm-footer">
            <button class="btn-batal-hapus" onclick="tutupModal()">Batal</button>
            <form method="POST" action="hapus_produk.php" id="formHapus" style="display:inline;">
                <input type="hidden" name="id" id="hapusId">
                <button type="submit" class="btn-hapus-ok">Ya, Hapus</button>
            </form>
        </div>
    </div>
</div>

<script>
// Scroll kategori
const katList = document.getElementById('katList');
function scrollKat(dir) {
    katList.scrollBy({ left: dir * 220, behavior: 'smooth' });
}

// Hapus modal
function konfirmasiHapus(id, nama) {
    document.getElementById('hapusId').value = id;
    document.getElementById('namaProdukHapus').textContent = nama;
    document.getElementById('modalHapus').classList.add('open');
}
function tutupModal() { document.getElementById('modalHapus').classList.remove('open'); }
document.addEventListener('keydown', e => { if(e.key==='Escape') tutupModal(); });

// Scroll ke kategori aktif
window.addEventListener('load', () => {
    const aktif = katList.querySelector('.kat-item.active');
    if (aktif) aktif.scrollIntoView({ behavior:'smooth', block:'nearest', inline:'center' });
});
</script>
</body>
</html>