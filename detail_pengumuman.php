<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['username'])) {
    header("Location: login.php"); exit;
}
include 'koneksi.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: profil.php"); exit;
}

$id   = (int) $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM pengumuman WHERE id = ? AND aktif = 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$p) {
    header("Location: profil.php"); exit;
}

// Pengumuman lainnya (selain yang sedang dibuka)
$lainnya = $conn->query("SELECT * FROM pengumuman WHERE aktif=1 AND id != $id ORDER BY created_at DESC LIMIT 4")->fetch_all(MYSQLI_ASSOC);

$username = $_SESSION['username'];
$role     = $_SESSION['role'];

$tipeConfig = [
    'diskon'  => ['icon'=>'🏷️','color'=>'#e53e3e','bg'=>'#fef2f2','border'=>'#fca5a5','label'=>'DISKON'],
    'promo'   => ['icon'=>'🎁','color'=>'#16a34a','bg'=>'#f0fdf4','border'=>'#86efac','label'=>'PROMO'],
    'info'    => ['icon'=>'ℹ️','color'=>'#1d4ed8','bg'=>'#eff6ff','border'=>'#93c5fd','label'=>'INFO'],
    'penting' => ['icon'=>'⚠️','color'=>'#d97706','bg'=>'#fffbeb','border'=>'#fcd34d','label'=>'PENTING'],
];
$cfg = $tipeConfig[$p['tipe']] ?? $tipeConfig['info'];

$ts   = strtotime($p['created_at']);
$diff = time() - $ts;
if ($diff < 3600)       $timeAgo = round($diff/60) . ' menit lalu';
elseif ($diff < 86400)  $timeAgo = round($diff/3600) . ' jam lalu';
elseif ($diff < 604800) $timeAgo = round($diff/86400) . ' hari lalu';
else                    $timeAgo = date('d F Y', $ts);

$uploadDir = 'uploads/pengumuman/';
$isPdf = $p['gambar'] && strtolower(pathinfo($p['gambar'], PATHINFO_EXTENSION)) === 'pdf';
$isImg = $p['gambar'] && !$isPdf && file_exists($uploadDir . $p['gambar']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($p['judul']) ?> — PT Juma Tiga</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* ── HERO PENGUMUMAN ─────────────────── */
        .detail-hero {
            background: var(--navy);
            padding: 48px 40px 60px;
            position: relative;
            overflow: hidden;
        }
        .detail-hero::before {
            content: '';
            position: absolute;
            top: -100px; right: -100px;
            width: 360px; height: 360px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(201,168,76,0.13), transparent 65%);
            pointer-events: none;
        }
        .detail-hero-inner {
            max-width: 780px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }
        .detail-tipe-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 6px 16px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            margin-bottom: 20px;
            animation: fadeUp 0.4s ease both;
        }
        .detail-hero h1 {
            font-family: 'Playfair Display', serif;
            color: var(--white);
            font-size: clamp(22px, 4vw, 34px);
            line-height: 1.25;
            margin-bottom: 16px;
            animation: fadeUp 0.4s ease 0.06s both;
        }
        .detail-meta {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            animation: fadeUp 0.4s ease 0.1s both;
        }
        .detail-meta span {
            color: rgba(255,255,255,0.55);
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .detail-meta strong { color: rgba(255,255,255,0.85); }

        /* ── LAYOUT ──────────────────────────── */
        .detail-layout {
            max-width: 1020px;
            margin: 36px auto 60px;
            padding: 0 24px;
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 28px;
            align-items: start;
        }

        /* ── KONTEN UTAMA ────────────────────── */
        .detail-card {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            animation: fadeUp 0.5s ease both;
        }

        /* Gambar banner */
        .detail-banner {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
            display: block;
        }

        .detail-card-body { padding: 32px; }

        .detail-tipe-strip {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid #f0f2f7;
        }
        .detail-icon-big {
            width: 48px; height: 48px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
        }
        .detail-tipe-info h2 {
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            color: var(--navy);
            line-height: 1.3;
        }
        .detail-tipe-info p {
            font-size: 12px;
            color: var(--muted);
            margin-top: 2px;
        }

        /* Isi teks */
        .detail-isi {
            font-size: 15px;
            color: var(--text);
            line-height: 1.85;
            white-space: pre-line;
        }

        /* PDF lampiran */
        .pdf-cta {
            display: flex;
            align-items: center;
            gap: 14px;
            background: var(--cream);
            border: 1.5px solid #e0e4ec;
            border-radius: var(--radius-sm);
            padding: 18px 22px;
            margin-top: 24px;
            text-decoration: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .pdf-cta:hover {
            border-color: var(--gold);
            box-shadow: var(--shadow-sm);
        }
        .pdf-icon-big { font-size: 36px; flex-shrink: 0; }
        .pdf-info p   { font-size: 14px; font-weight: 600; color: var(--navy); }
        .pdf-info span { font-size: 12px; color: var(--muted); }
        .pdf-arrow { margin-left: auto; color: var(--muted); font-size: 18px; }

        /* Divider */
        .detail-divider {
            height: 1px;
            background: #f0f2f7;
            margin: 24px 0;
        }

        /* Share & kembali */
        .detail-footer-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid #f0f2f7;
        }
        .share-btns { display: flex; gap: 8px; }
        .share-btn {
            padding: 7px 14px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
            border: 1.5px solid #e0e4ec;
            background: var(--white);
            cursor: pointer;
            font-family: 'DM Sans', sans-serif;
            transition: all 0.2s;
            display: flex; align-items: center; gap: 5px;
        }
        .share-btn:hover { border-color: var(--navy); color: var(--navy); }
        .share-btn.copied { border-color: var(--success); color: var(--success); }

        /* ── SIDEBAR ─────────────────────────── */
        .sidebar { position: sticky; top: 84px; }

        .sidebar-card {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            animation: fadeUp 0.5s ease 0.1s both;
            margin-bottom: 20px;
        }
        .sidebar-head {
            background: var(--navy);
            padding: 14px 18px;
            display: flex; align-items: center; gap: 8px;
        }
        .sidebar-head h3 {
            font-family: 'Playfair Display', serif;
            color: var(--white);
            font-size: 14px;
        }
        .sidebar-body { padding: 8px 0; }

        /* Item pengumuman lainnya */
        .lain-item {
            display: flex;
            gap: 12px;
            padding: 12px 16px;
            border-bottom: 1px solid #f5f5f8;
            text-decoration: none;
            transition: background 0.15s;
            align-items: flex-start;
        }
        .lain-item:last-child { border-bottom: none; }
        .lain-item:hover { background: #fafbff; }
        .lain-dot {
            width: 30px; height: 30px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
            margin-top: 1px;
        }
        .lain-text {}
        .lain-judul {
            font-size: 13px;
            font-weight: 600;
            color: var(--navy);
            line-height: 1.3;
            margin-bottom: 3px;
        }
        .lain-meta { font-size: 11px; color: var(--muted); }

        /* Tipe badge kecil */
        .chip {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 50px;
            font-size: 10px;
            font-weight: 700;
        }
        .chip-diskon  { background:#fef2f2; color:#b91c1c; border:1px solid #fca5a5; }
        .chip-promo   { background:#f0fdf4; color:#15803d; border:1px solid #86efac; }
        .chip-info    { background:#eff6ff; color:#1e40af; border:1px solid #93c5fd; }
        .chip-penting { background:#fffbeb; color:#b45309; border:1px solid #fcd34d; }

        /* Kembali link */
        .back-bar {
            max-width: 1020px;
            margin: 24px auto 0;
            padding: 0 24px;
            animation: fadeUp 0.35s ease both;
        }

        @media (max-width: 760px) {
            .detail-layout { grid-template-columns: 1fr; }
            .sidebar { position: static; }
            .detail-hero { padding: 36px 20px 48px; }
            .detail-card-body { padding: 22px 18px; }
        }
    </style>
</head>
<body>

<!-- Navbar -->
<?php $navActive = 'profil'; include 'navbar.php'; ?>
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
    <a href="profil.php" class="back-btn-global">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15,18 9,12 15,6"/></svg>
        ← Kembali ke Profil
    </a>
    <div class="breadcrumb-trail"><a href="dashboard.php">Dashboard</a><span class="sep">›</span><a href="profil.php">Profil</a><span class="sep">›</span><span class="current">Detail Pengumuman</span></div>
</div>


<!-- Hero -->
<div class="detail-hero">
    <div class="detail-hero-inner">
        <div class="detail-tipe-badge"
             style="background:<?= $cfg['bg'] ?>;color:<?= $cfg['color'] ?>;border:1px solid <?= $cfg['border'] ?>">
            <?= $cfg['icon'] ?> <?= $cfg['label'] ?>
            <?php if ($p['badge']): ?>
                &nbsp;·&nbsp; <?= htmlspecialchars($p['badge']) ?>
            <?php endif; ?>
        </div>
        <h1><?= htmlspecialchars($p['judul']) ?></h1>
        <div class="detail-meta">
            <span>🕐 <strong><?= $timeAgo ?></strong></span>
            <span>📅 <?= date('d F Y, H:i', $ts) ?> WIB</span>
            <?php if ($isImg || $isPdf): ?>
                <span>📎 Ada lampiran</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Back link -->
<div class="back-bar">
    </div>

<!-- Main layout -->
<div class="detail-layout">

    <!-- Konten Utama -->
    <div>
        <div class="detail-card">

            <!-- Banner gambar (jika ada & bukan PDF) -->
            <?php if ($isImg): ?>
                <img src="<?= $uploadDir . htmlspecialchars($p['gambar']) ?>"
                     alt="<?= htmlspecialchars($p['judul']) ?>"
                     class="detail-banner">
            <?php endif; ?>

            <div class="detail-card-body">
                <!-- Header tipe -->
                <div class="detail-tipe-strip">
                    <div class="detail-icon-big"
                         style="background:<?= $cfg['bg'] ?>;border:1.5px solid <?= $cfg['border'] ?>">
                        <?= $cfg['icon'] ?>
                    </div>
                    <div class="detail-tipe-info">
                        <h2><?= htmlspecialchars($p['judul']) ?></h2>
                        <p>
                            <span class="chip chip-<?= $p['tipe'] ?>"><?= strtoupper($p['tipe']) ?></span>
                            &nbsp;·&nbsp; Dipublikasikan <?= $timeAgo ?>
                        </p>
                    </div>
                </div>

                <!-- Isi lengkap -->
                <div class="detail-isi"><?= htmlspecialchars($p['isi']) ?></div>

                <!-- Lampiran PDF -->
                <?php if ($isPdf && file_exists($uploadDir . $p['gambar'])): ?>
                <div class="detail-divider"></div>
                <a href="<?= $uploadDir . htmlspecialchars($p['gambar']) ?>" target="_blank" class="pdf-cta">
                    <div class="pdf-icon-big">📄</div>
                    <div class="pdf-info">
                        <p>Lampiran PDF</p>
                        <span>Klik untuk membuka dokumen di tab baru</span>
                    </div>
                    <span class="pdf-arrow">→</span>
                </a>
                <?php endif; ?>

                <!-- Footer bar -->
                <div class="detail-footer-bar">
                    <a href="profil.php" class="btn btn-secondary" style="padding:9px 20px;font-size:13px;">
                        ← Kembali
                    </a>
                    <div class="share-btns">
                        <button class="share-btn" onclick="salinLink(this)">
                            🔗 Salin Link
                        </button>
                        <?php if ($role === 'penjual'): ?>
                        <a href="kelola_pengumuman.php" class="share-btn" style="text-decoration:none;">
                            ✏️ Kelola
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="sidebar">
        <?php if (!empty($lainnya)): ?>
        <div class="sidebar-card">
            <div class="sidebar-head">
                <span>📢</span>
                <h3>Pengumuman Lainnya</h3>
            </div>
            <div class="sidebar-body">
                <?php
                $lainIcons = ['diskon'=>'🏷️','promo'=>'🎁','info'=>'ℹ️','penting'=>'⚠️'];
                $lainBg    = ['diskon'=>'#fef2f2','promo'=>'#f0fdf4','info'=>'#eff6ff','penting'=>'#fffbeb'];
                $lainColor = ['diskon'=>'#b91c1c','promo'=>'#15803d','info'=>'#1e40af','penting'=>'#b45309'];
                foreach ($lainnya as $l):
                    $lTs   = strtotime($l['created_at']);
                    $lDiff = time() - $lTs;
                    if ($lDiff < 86400)       $lTime = round($lDiff/3600)  . ' jam lalu';
                    elseif ($lDiff < 604800)  $lTime = round($lDiff/86400) . ' hari lalu';
                    else                       $lTime = date('d M Y', $lTs);
                ?>
                <a href="detail_pengumuman.php?id=<?= $l['id'] ?>" class="lain-item">
                    <div class="lain-dot"
                         style="background:<?= $lainBg[$l['tipe']] ?? '#eff6ff' ?>;
                                border:1.5px solid <?= $lainColor[$l['tipe']] ?? '#93c5fd' ?>">
                        <?= $lainIcons[$l['tipe']] ?? 'ℹ️' ?>
                    </div>
                    <div class="lain-text">
                        <div class="lain-judul"><?= htmlspecialchars($l['judul']) ?></div>
                        <div class="lain-meta">
                            <span class="chip chip-<?= $l['tipe'] ?>"><?= strtoupper($l['tipe']) ?></span>
                            · <?= $lTime ?>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Info card -->
        <div class="sidebar-card" style="animation-delay:.15s">
            <div class="sidebar-head">
                <span>🏢</span>
                <h3>PT Juma Tiga</h3>
            </div>
            <div class="sidebar-body" style="padding:16px;">
                <p style="font-size:13px;color:var(--muted);line-height:1.7;margin-bottom:14px;">
                    Informasi resmi dari PT Juma Tiga. Hubungi kami jika ada pertanyaan lebih lanjut.
                </p>
                <div style="font-size:12px;color:var(--text);display:flex;flex-direction:column;gap:6px;">
                    <span>📞 (061) 123-4567</span>
                    <span>✉️ info@jumatiga.co.id</span>
                    <span>🕐 Senin–Sabtu, 08.00–17.00</span>
                </div>
            </div>
        </div>
    </div>

</div>

<p class="footer">© <?= date('Y') ?> PT Juma Tiga</p>

<script>
function salinLink(btn) {
    navigator.clipboard.writeText(window.location.href).then(() => {
        btn.textContent = '✅ Link Disalin!';
        btn.classList.add('copied');
        setTimeout(() => {
            btn.textContent = '🔗 Salin Link';
            btn.classList.remove('copied');
        }, 2500);
    });
}
</script>
</body>
</html>