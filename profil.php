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
$username = $_SESSION['username'];
$role     = $_SESSION['role'];

include 'koneksi.php';

// Ambil pengumuman aktif, terbaru di atas
$pengumumans = $conn->query("SELECT * FROM pengumuman WHERE aktif=1 ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil & Pengumuman — PT JUMA TIGA SEANTERO</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* ── HERO ───────────────────────────── */
        .hero {
            background: var(--navy);
            padding: 60px 40px 80px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .hero::before {
            content: '';
            position: absolute;
            top: -120px; right: -120px;
            width: 400px; height: 400px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(201,168,76,0.15), transparent 65%);
            pointer-events: none;
        }
        .hero::after {
            content: '';
            position: absolute;
            bottom: -80px; left: -80px;
            width: 300px; height: 300px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(201,168,76,0.1), transparent 65%);
            pointer-events: none;
        }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(201,168,76,0.15);
            border: 1px solid rgba(201,168,76,0.4);
            color: var(--gold-light);
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            padding: 6px 16px;
            border-radius: 50px;
            margin-bottom: 24px;
            animation: fadeUp 0.4s ease both;
        }
        .hero-logo {
            width: 90px; height: 90px;
            border-radius: 50%;
            border: 2px solid var(--gold);
            display: flex; align-items: center; justify-content: center;
            font-size: 38px;
            margin: 0 auto 24px;
            background: rgba(255,255,255,0.04);
            animation: fadeUp 0.45s ease 0.05s both;
            box-shadow: 0 0 40px rgba(201,168,76,0.2);
        }
        .hero h1 {
            font-family: 'Playfair Display', serif;
            color: var(--white);
            font-size: 36px;
            margin-bottom: 10px;
            animation: fadeUp 0.45s ease 0.1s both;
        }
        .hero h1 span { color: var(--gold); }
        .hero-tagline {
            color: rgba(255,255,255,0.6);
            font-size: 15px;
            font-weight: 300;
            animation: fadeUp 0.45s ease 0.15s both;
            max-width: 480px;
            margin: 0 auto;
        }

        /* ── WELCOME STRIP ──────────────────── */
        .welcome-strip {
            background: var(--gold);
            padding: 14px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            animation: fadeUp 0.4s ease 0.2s both;
        }
        .welcome-strip p { color: var(--navy); font-size: 13px; font-weight: 500; }
        .welcome-strip strong { font-weight: 700; }
        .btn-enter {
            background: var(--navy);
            color: var(--white);
            padding: 9px 22px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.2s, transform 0.15s;
            white-space: nowrap;
        }
        .btn-enter:hover { background: #1a3060; transform: scale(1.03); }

        /* ── MAIN LAYOUT ────────────────────── */
        .main-layout {
            max-width: 1060px;
            margin: 40px auto 60px;
            padding: 0 24px;
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 28px;
            align-items: start;
        }

        /* ── PROFIL CARDS (kolom kiri) ──────── */
        .profil-card {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            animation: fadeUp 0.5s ease both;
            margin-bottom: 20px;
        }
        .profil-card:last-child { margin-bottom: 0; }
        .profil-card-head {
            background: var(--navy);
            padding: 16px 22px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .profil-card-head span { font-size: 18px; }
        .profil-card-head h3 {
            font-family: 'Playfair Display', serif;
            color: var(--white);
            font-size: 15px;
        }
        .profil-card-body { padding: 20px 22px; }
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 10px 0;
            border-bottom: 1px solid #f0f2f7;
            gap: 16px;
        }
        .info-row:last-child { border-bottom: none; }
        .info-label {
            font-size: 11px; font-weight: 600;
            letter-spacing: 1px; text-transform: uppercase;
            color: var(--muted); min-width: 100px; padding-top: 1px;
        }
        .info-value { font-size: 13px; color: var(--text); font-weight: 500; text-align: right; flex: 1; }

        .visi-misi-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .vm-box {
            background: var(--cream); border-radius: var(--radius-sm);
            padding: 16px; border-left: 3px solid var(--gold);
        }
        .vm-box h4 { font-family: 'Playfair Display', serif; font-size: 13px; color: var(--navy); margin-bottom: 6px; }
        .vm-box p  { font-size: 12px; color: var(--muted); line-height: 1.7; }

        .nilai-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
        .nilai-item {
            text-align: center; padding: 16px 10px;
            background: var(--cream); border-radius: var(--radius-sm);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .nilai-item:hover { transform: translateY(-3px); box-shadow: var(--shadow-sm); }
        .nilai-icon  { font-size: 24px; margin-bottom: 6px; }
        .nilai-title { font-size: 12px; font-weight: 600; color: var(--navy); margin-bottom: 3px; }
        .nilai-desc  { font-size: 11px; color: var(--muted); line-height: 1.5; }

        /* ── DASHBOARD BUTTON ───────────────── */
        .btn-dashboard {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: var(--navy);
            color: var(--white);
            padding: 15px 32px;
            border-radius: 50px;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
            box-shadow: 0 8px 24px rgba(15,30,60,0.2);
            position: relative;
            overflow: hidden;
            margin-top: 24px;
        }
        .btn-dashboard::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 3px;
            background: var(--gold);
        }
        .btn-dashboard:hover {
            background: var(--navy-mid);
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(15,30,60,0.28);
        }

        /* ── TIMELINE PENGUMUMAN (kolom kanan) ─ */
        .timeline-panel {
            position: sticky;
            top: 80px;
            animation: fadeUp 0.5s ease 0.15s both;
        }
        .timeline-header {
            background: var(--navy);
            border-radius: var(--radius) var(--radius) 0 0;
            padding: 16px 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .timeline-header h3 {
            font-family: 'Playfair Display', serif;
            color: var(--white);
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .timeline-count {
            background: var(--gold);
            color: var(--navy);
            font-size: 10px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 50px;
        }
        .timeline-body {
            background: var(--white);
            border-radius: 0 0 var(--radius) var(--radius);
            box-shadow: var(--shadow-md);
            max-height: 640px;
            overflow-y: auto;
            padding: 6px 0;
        }
        .timeline-body::-webkit-scrollbar { width: 4px; }
        .timeline-body::-webkit-scrollbar-thumb { background: #ddd; border-radius: 4px; }

        /* Timeline item */
        .tl-item {
            display: flex;
            gap: 0;
            padding: 0;
            position: relative;
            animation: fadeUp 0.4s ease both;
        }
        /* Garis vertikal */
        .tl-line {
            width: 44px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-top: 18px;
        }
        .tl-dot {
            width: 32px; height: 32px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
            z-index: 1;
            position: relative;
        }
        .dot-diskon  { background: #fef2f2; border: 2px solid #fca5a5; }
        .dot-promo   { background: #f0fdf4; border: 2px solid #86efac; }
        .dot-info    { background: #eff6ff; border: 2px solid #93c5fd; }
        .dot-penting { background: #fffbeb; border: 2px solid #fcd34d; }

        .tl-connector {
            width: 2px;
            flex: 1;
            background: #f0f2f7;
            min-height: 20px;
            margin-top: 4px;
        }
        .tl-item:last-child .tl-connector { display: none; }

        .tl-content {
            flex: 1;
            padding: 16px 18px 16px 4px;
            border-bottom: 1px solid #f5f5f8;
        }
        .tl-item:last-child .tl-content { border-bottom: none; }
        .tl-link {
            text-decoration: none;
            display: flex;
            cursor: pointer;
            transition: background 0.15s;
            border-radius: 0;
        }
        .tl-link:hover { background: #f8f9ff; }
        .tl-link:hover .tl-judul { color: var(--gold); }
        .tl-link:hover .tl-arrow { opacity: 1; transform: translateX(3px); }
        .tl-arrow {
            font-size: 11px;
            color: var(--gold);
            opacity: 0;
            transition: opacity 0.2s, transform 0.2s;
            display: inline-block;
        }
        .tl-judul { transition: color 0.2s; }

        .tl-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 5px;
        }
        .tl-judul {
            font-size: 13px;
            font-weight: 600;
            color: var(--navy);
            line-height: 1.3;
            flex: 1;
        }
        .tl-badge {
            padding: 2px 8px;
            border-radius: 50px;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.5px;
            flex-shrink: 0;
        }
        .tbadge-diskon  { background: #fef2f2; color: #b91c1c; border: 1px solid #fca5a5; }
        .tbadge-promo   { background: #f0fdf4; color: #15803d; border: 1px solid #86efac; }
        .tbadge-info    { background: #eff6ff; color: #1e40af; border: 1px solid #93c5fd; }
        .tbadge-penting { background: #fffbeb; color: #b45309; border: 1px solid #fcd34d; }

        .tl-isi  { font-size: 12px; color: var(--muted); line-height: 1.6; }
        .tl-date { font-size: 10px; color: #bbb; margin-top: 6px; display: flex; align-items: center; gap: 4px; }

        /* Gambar di timeline */
        .tl-img-wrap {
            margin-top: 10px;
            border-radius: var(--radius-sm);
            overflow: hidden;
            position: relative;
            cursor: pointer;
            max-height: 160px;
        }
        .tl-img-wrap img {
            width: 100%;
            height: 160px;
            object-fit: cover;
            display: block;
            transition: transform 0.3s;
        }
        .tl-img-wrap:hover img { transform: scale(1.03); }
        .tl-img-overlay {
            position: absolute;
            inset: 0;
            background: rgba(15,30,60,0.45);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: 600;
            opacity: 0;
            transition: opacity 0.2s;
        }
        .tl-img-wrap:hover .tl-img-overlay { opacity: 1; }
        .tl-file-tag {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-top: 8px;
            padding: 5px 12px;
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #93c5fd;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.2s;
        }
        .tl-file-tag:hover { background: #dbeafe; }



        /* Empty timeline */
        .tl-empty {
            padding: 40px 20px;
            text-align: center;
            color: var(--muted);
        }
        .tl-empty .tl-empty-icon { font-size: 32px; margin-bottom: 8px; }
        .tl-empty p { font-size: 12px; }

        /* ── RESPONSIVE ─────────────────────── */
        @media (max-width: 860px) {
            .main-layout {
                grid-template-columns: 1fr;
            }
            .timeline-panel { position: static; }
            .timeline-body { max-height: 400px; }
        }
        @media (max-width: 600px) {
            .hero { padding: 40px 20px 60px; }
            .hero h1 { font-size: 28px; }
            .welcome-strip { flex-direction: column; gap: 12px; text-align: center; padding: 14px 20px; }
            .visi-misi-grid { grid-template-columns: 1fr; }
            .nilai-grid { grid-template-columns: 1fr 1fr; }
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
    <a href="dashboard.php" class="back-btn-global">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15,18 9,12 15,6"/></svg>
        ← Kembali ke Dashboard
    </a>
    <div class="breadcrumb-trail"><a href="dashboard.php">Dashboard</a><span class="sep">›</span><span class="current">Profil Perusahaan</span></div>
</div>


<!-- Hero -->
<div class="hero">
    <div class="hero-badge">✦ Selamat Datang Kembali</div>
    <div class="hero-logo" style="width:130px;height:130px;border:3px solid var(--gold);overflow:hidden;background:white;padding:6px;box-shadow:0 0 50px rgba(201,168,76,0.4);border-radius:50%;margin:0 auto 24px;">
        <img src="assets/logo_jt2.jpeg" alt="Logo PT JUMA TIGA SEANTERO"
             style="width:100%;height:100%;object-fit:contain;">
    </div>
    <h1>PT <span>JUMA TIGA SEANTERO</span></h1>
    <p class="hero-tagline">Perusahaan Distributor Resmi Aice yang berlokasi di Kabanjahe</p>
</div>

<!-- Welcome strip -->
<div class="welcome-strip">
    <p>Halo, <strong><?= htmlspecialchars($username) ?></strong> &middot; Role: <strong><?= ucfirst($role) ?></strong> &middot; <?= date('l, d F Y') ?></p>
    <a href="dashboard.php" class="btn-enter">Masuk Dashboard →</a>
</div>

<!-- Main 2-column layout -->
<div class="main-layout">

    <!-- ── KOLOM KIRI: Profil Perusahaan ── -->
    <div>
        <!-- Identitas -->
        <div class="profil-card" style="animation-delay:.08s">
            <div class="profil-card-head"><span>🏛️</span><h3>Identitas Perusahaan</h3></div>
            <div class="profil-card-body">
                <div class="info-row"><span class="info-label">Nama</span><span class="info-value">PT JUMA TIGA SEANTERO</span></div>
                <div class="info-row"><span class="info-label">Bidang</span><span class="info-value">Perdagangan &amp; Distribusi</span></div>
                <div class="info-row"><span class="info-label">Berdiri</span><span class="info-value">2015</span></div>
                <div class="info-row"><span class="info-label">Status</span><span class="info-value"><span class="badge badge-green">● Aktif</span></span></div>
            </div>
        </div>

        <!-- Kontak -->
        <div class="profil-card" style="animation-delay:.13s">
            <div class="profil-card-head"><span>📍</span><h3>Kontak &amp; Lokasi</h3></div>
            <div class="profil-card-body">
                <div class="info-row"><span class="info-label">Alamat</span><span class="info-value">Jl. Jumatiga, Perumahan Griya Ulih Latih Nusantara 22152/span></div>
                <div class="info-row"><span class="info-label">Telepon</span><span class="info-value">(061) 123-4567</span></div>
                <div class="info-row"><span class="info-label">Email</span><span class="info-value">info@jumatigaseantero.co.id</span></div>
                <div class="info-row"><span class="info-label">Website</span><span class="info-value">www.jumatigaseantero.co.id</span></div>
            </div>
        </div>

        <!-- Visi & Misi -->
        <div class="profil-card" style="animation-delay:.18s">
            <div class="profil-card-head"><span>🎯</span><h3>Visi &amp; Misi</h3></div>
            <div class="profil-card-body">
                <div class="visi-misi-grid">
                    <div class="vm-box">
                        <h4>🔭 Visi</h4>
                        <p>Menjadi perusahaan distribusi terkemuka di Sumatera Utara yang dipercaya mitra dan pelanggan dengan mengutamakan kualitas, integritas, dan inovasi berkelanjutan.</p>
                    </div>
                    <div class="vm-box">
                        <h4>🚀 Misi</h4>
                        <p>Menyediakan produk berkualitas tinggi, membangun kemitraan yang saling menguntungkan, menerapkan sistem manajemen yang efisien, dan berkontribusi pada pertumbuhan ekonomi lokal.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Nilai Perusahaan -->
        <div class="profil-card" style="animation-delay:.23s">
            <div class="profil-card-head"><span>⭐</span><h3>Nilai Perusahaan</h3></div>
            <div class="profil-card-body">
                <div class="nilai-grid">
                    <div class="nilai-item">
                        <div class="nilai-icon">🤝</div>
                        <div class="nilai-title">Integritas</div>
                        <div class="nilai-desc">Jujur dan transparan dalam setiap transaksi</div>
                    </div>
                    <div class="nilai-item">
                        <div class="nilai-icon">💡</div>
                        <div class="nilai-title">Inovasi</div>
                        <div class="nilai-desc">Terus berkembang mengikuti kebutuhan pasar</div>
                    </div>
                    <div class="nilai-item">
                        <div class="nilai-icon">🏆</div>
                        <div class="nilai-title">Kualitas</div>
                        <div class="nilai-desc">Produk dan layanan terbaik tanpa kompromi</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Foto Kantor -->
        <div class="profil-card" style="animation-delay:.28s">
            <div class="profil-card-head"><span>🏗️</span><h3>Kantor &amp; Fasilitas</h3></div>
            <div class="profil-card-body" style="padding:0;overflow:hidden;">
                <img src="assets/foto_kantor.jpeg" alt="Kantor PT JUMA TIGA SEANTERO"
                     style="width:100%;height:220px;object-fit:cover;display:block;">
                <div style="padding:14px 20px;font-size:13px;color:var(--muted);line-height:1.6;">
                    📍 Kantor &amp; gudang PT JUMA TIGA SEANTERO berlokasi di Medan, Sumatera Utara — dilengkapi fasilitas penyimpanan dan distribusi modern.
                </div>
            </div>
        </div>

        <!-- Mitra Brand -->
        <div class="profil-card" style="animation-delay:.32s">
            <div class="profil-card-head"><span>🤝</span><h3>Mitra &amp; Brand Kami</h3></div>
            <div class="profil-card-body">
                <p style="font-size:13px;color:var(--muted);margin-bottom:16px;">
                    PT JUMA TIGA SEANTERO bermitra dengan brand-brand terpercaya dalam mendistribusikan produk berkualitas.
                </p>
                <div style="display:flex;gap:16px;align-items:center;flex-wrap:wrap;">
                    <div style="background:var(--cream);border:1.5px solid #e8eaf0;border-radius:10px;padding:14px 20px;display:flex;align-items:center;justify-content:center;min-width:120px;">
                        <img src="assets/logo_aice.jpeg" alt="Aice"
                             style="height:44px;object-fit:contain;max-width:110px;">
                    </div>
                </div>
            </div>
        </div>

        <!-- CTA Dashboard -->
        <a href="dashboard.php" class="btn-dashboard" style="animation:fadeUp .5s ease .28s both;">
            Masuk ke Dashboard →
        </a>

        <p style="text-align:center;font-size:11px;color:var(--muted);margin-top:14px;">
            Sistem Manajemen Penjualan · PT JUMA TIGA SEANTERO © <?= date('Y') ?>
        </p>
    </div>

    <!-- ── KOLOM KANAN: Timeline Pengumuman ── -->
    <div class="timeline-panel">
        <div class="timeline-header">
            <h3>📢 Pengumuman</h3>
            <?php if (!empty($pengumumans)): ?>
                <span class="timeline-count"><?= count($pengumumans) ?> info</span>
            <?php endif; ?>
        </div>
        <div class="timeline-body">
            <?php if (empty($pengumumans)): ?>
                <div class="tl-empty">
                    <div class="tl-empty-icon">📭</div>
                    <p>Belum ada pengumuman terbaru.</p>
                </div>
            <?php else: ?>
                <?php
                $tipeConfig = [
                    'diskon'  => ['icon'=>'🏷️','dot'=>'dot-diskon', 'badge'=>'tbadge-diskon', 'label'=>'DISKON'],
                    'promo'   => ['icon'=>'🎁','dot'=>'dot-promo',  'badge'=>'tbadge-promo',  'label'=>'PROMO'],
                    'info'    => ['icon'=>'ℹ️','dot'=>'dot-info',   'badge'=>'tbadge-info',   'label'=>'INFO'],
                    'penting' => ['icon'=>'⚠️','dot'=>'dot-penting','badge'=>'tbadge-penting','label'=>'PENTING'],
                ];
                foreach ($pengumumans as $i => $p):
                    $cfg   = $tipeConfig[$p['tipe']] ?? $tipeConfig['info'];
                    $delay = ($i * 0.06) . 's';
                    $badgeTxt = $p['badge'] ?: $cfg['label'];
                    // Format waktu relatif
                    $ts   = strtotime($p['created_at']);
                    $diff = time() - $ts;
                    if ($diff < 3600)       $timeAgo = round($diff/60) . ' menit lalu';
                    elseif ($diff < 86400)  $timeAgo = round($diff/3600) . ' jam lalu';
                    elseif ($diff < 604800) $timeAgo = round($diff/86400) . ' hari lalu';
                    else                    $timeAgo = date('d M Y', $ts);
                ?>
                <a href="detail_pengumuman.php?id=<?= $p['id'] ?>" class="tl-item tl-link" style="animation-delay:<?= $delay ?>" title="Klik untuk baca selengkapnya">
                    <div class="tl-line">
                        <div class="tl-dot <?= $cfg['dot'] ?>"><?= $cfg['icon'] ?></div>
                        <div class="tl-connector"></div>
                    </div>
                    <div class="tl-content">
                        <div class="tl-top">
                            <div class="tl-judul"><?= htmlspecialchars($p['judul']) ?> <span class="tl-arrow">→</span></div>
                            <span class="tl-badge <?= $cfg['badge'] ?>"><?= htmlspecialchars($badgeTxt) ?></span>
                        </div>
                        <div class="tl-isi"><?= htmlspecialchars($p['isi']) ?></div>
                        <?php
                        // Tampilkan gambar/file jika ada
                        $uploadDir = 'uploads/pengumuman/';
                        if (!empty($p['gambar']) && file_exists($uploadDir . $p['gambar'])):
                            $ext = strtolower(pathinfo($p['gambar'], PATHINFO_EXTENSION));
                        ?>
                        <?php if ($ext === 'pdf'): ?>
                            <a href="<?= $uploadDir . htmlspecialchars($p['gambar']) ?>" target="_blank"
                               class="tl-file-tag">
                                📄 Lihat Lampiran PDF
                            </a>
                        <?php else: ?>
                            <div class="tl-img-wrap">
                                <img src="<?= $uploadDir . htmlspecialchars($p['gambar']) ?>"
                                     alt="<?= htmlspecialchars($p['judul']) ?>">
                                <div class="tl-img-overlay">📖 Baca selengkapnya</div>
                            </div>
                        <?php endif; ?>
                        <?php endif; ?>
                        <div class="tl-date">
                            <span>🕐</span> <?= $timeAgo ?>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if ($role === 'penjual'): ?>
        <div style="margin-top:12px;text-align:center;">
            <a href="kelola_pengumuman.php"
               style="font-size:12px;color:var(--navy);font-weight:600;text-decoration:none;
                      display:inline-flex;align-items:center;gap:5px;
                      padding:8px 18px;border:1.5px solid #e0e4ec;border-radius:50px;
                      transition:all .2s;background:var(--white);"
               onmouseover="this.style.borderColor='var(--gold)'"
               onmouseout="this.style.borderColor='#e0e4ec'">
                ✏️ Kelola Pengumuman
            </a>
        </div>
        <?php endif; ?>
    </div>

</div><!-- main-layout -->


</body>
</html>