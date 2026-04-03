<?php
session_start();
if (isset($_SESSION['username'])) {
    header("Location: dashboard.php");
    exit;
}
include 'koneksi.php';

// Ambil pengumuman aktif
$pengumumans = $conn->query("SELECT * FROM pengumuman WHERE aktif=1 ORDER BY created_at DESC LIMIT 6")->fetch_all(MYSQLI_ASSOC);

// Produk unggulan
$produkUnggulan = $conn->query("SELECT * FROM produk ORDER BY id DESC LIMIT 6")->fetch_all(MYSQLI_ASSOC);

// Galeri aktif
$galeriLanding = $conn->query("SELECT * FROM galeri WHERE aktif=1 ORDER BY urutan ASC, created_at DESC LIMIT 8")->fetch_all(MYSQLI_ASSOC);

// Wilayah dari database
$wilayahList = [];
$cekWilayah = $conn->query("SHOW TABLES LIKE 'wilayah'")->fetch_assoc();
if ($cekWilayah) {
    $wilayahList = $conn->query("SELECT * FROM wilayah WHERE aktif=1 ORDER BY urutan ASC")->fetch_all(MYSQLI_ASSOC);
}

// Error login
$loginError = isset($_GET['error']) ? $_GET['error'] : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PT JUMA TIGA SEANTERO — Distributor Resmi Aice Kabanjahe</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --navy:   #0f1e3c;
            --navy2:  #1a3060;
            --gold:   #c9a84c;
            --gold2:  #e8c97a;
            --cream:  #f9f6f0;
            --bg:     #f0f2f7;
            --white:  #ffffff;
            --text:   #1a1a2e;
            --muted:  #6b7280;
            --red:    #c0392b;
            --green:  #16a34a;
        }
        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            overflow-x: hidden;
        }

        /* ── TOP STRIP ─────────────────────────── */
        .top-strip {
            background: var(--gold);
            padding: 7px 0;
            text-align: center;
            font-size: 12px;
            font-weight: 600;
            color: var(--navy);
            position: relative;
            overflow: hidden;
        }
        .top-strip::before {
            content:'';
            position:absolute;inset:0;
            background:repeating-linear-gradient(90deg,transparent,transparent 40px,rgba(255,255,255,.15) 40px,rgba(255,255,255,.15) 41px);
        }
        .ticker { display:flex; gap:40px; animation:ticker 25s linear infinite; white-space:nowrap; position:relative;z-index:1; }
        .ticker span { display:inline-flex; align-items:center; gap:6px; }
        @keyframes ticker { 0%{transform:translateX(0)} 100%{transform:translateX(-50%)} }

        /* ── HEADER UTAMA ──────────────────────── */
        .site-header {
            background: url('assets/foto_kantor.jpeg') center/cover no-repeat;
            padding: 0;
            position: relative;
            overflow: hidden;
        }
        /* Overlay gelap agar teks tetap terbaca */
        .site-header::before {
            content:'';
            position:absolute;inset:0;
            background: linear-gradient(
                135deg,
                rgba(10,22,40,0.88) 0%,
                rgba(15,30,60,0.82) 50%,
                rgba(10,22,40,0.90) 100%
            );
            z-index: 0;
        }
        /* Dot grid overlay */
        .site-header::after {
            content:'';
            position:absolute;inset:0;
            background-image: radial-gradient(rgba(255,255,255,0.04) 1px, transparent 1px);
            background-size:24px 24px;
            z-index: 0;
        }
        .header-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 28px 30px;
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 24px;
            align-items: center;
            position: relative;
            z-index: 2;
        }

        /* Logo area */
        .header-logo {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .logo-img {
            width: 90px; height: 90px;
            border-radius: 50%;
            border: 3px solid var(--gold);
            object-fit: contain;
            background: white;
            padding: 5px;
            box-shadow: 0 0 30px rgba(201,168,76,0.4);
            flex-shrink: 0;
        }
        .header-title h1 {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            color: var(--white);
            line-height: 1.1;
            letter-spacing: 0.5px;
        }
        .header-title h1 span { color: var(--gold); }
        .header-title .tagline {
            font-size: 11px;
            color: rgba(255,255,255,0.55);
            font-weight: 300;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-top: 5px;
        }
        .header-title .akreditasi {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: rgba(201,168,76,0.15);
            border: 1px solid rgba(201,168,76,0.3);
            color: var(--gold2);
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.5px;
            padding: 3px 10px;
            border-radius: 50px;
            margin-top: 6px;
        }

        /* Foto gedung */
        .header-building {
            text-align: center;
        }
        .header-building img {
            height: 110px;
            object-fit: cover;
            border-radius: 8px;
            opacity: 0.85;
            box-shadow: 0 8px 24px rgba(0,0,0,0.3);
        }

        /* Form Login di kanan */
        .header-login {
            background: rgba(255,255,255,0.07);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 12px;
            padding: 20px;
            min-width: 240px;
        }
        .header-login h4 {
            color: var(--gold);
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .login-field {
            display: flex;
            align-items: center;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 8px;
            margin-bottom: 10px;
            overflow: hidden;
            transition: border-color 0.2s;
        }
        .login-field:focus-within {
            border-color: var(--gold);
        }
        .login-field-icon {
            padding: 0 12px;
            color: rgba(255,255,255,0.4);
            font-size: 14px;
            flex-shrink: 0;
        }
        .login-field input {
            flex: 1;
            background: transparent;
            border: none;
            outline: none;
            color: white;
            font-size: 13px;
            font-family: 'DM Sans', sans-serif;
            padding: 10px 12px 10px 0;
        }
        .login-field input::placeholder { color: rgba(255,255,255,0.35); }
        .btn-login-header {
            width: 100%;
            background: var(--gold);
            color: var(--navy);
            border: none;
            border-radius: 8px;
            padding: 10px;
            font-size: 13px;
            font-weight: 700;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            transition: all 0.2s;
            letter-spacing: 0.5px;
            margin-top: 4px;
        }
        .btn-login-header:hover {
            background: var(--gold2);
            transform: translateY(-1px);
        }
        .login-error {
            background: rgba(192,57,43,0.2);
            border: 1px solid rgba(192,57,43,0.4);
            color: #fca5a5;
            font-size: 11px;
            padding: 7px 10px;
            border-radius: 6px;
            margin-bottom: 10px;
        }
        .login-register-link {
            text-align: center;
            margin-top: 10px;
            font-size: 11px;
            color: rgba(255,255,255,0.4);
        }
        .login-register-link a {
            color: var(--gold2);
            text-decoration: none;
            font-weight: 600;
        }

        /* ── NAVBAR MENU ───────────────────────── */
        .site-nav {
            background: #1e0d3d;
            position: sticky;
            top: 0;
            z-index: 500;
            box-shadow: 0 2px 12px rgba(0,0,0,0.3);
        }
        .site-nav::before {
            content:'';
            position:absolute;inset:0;
            background: repeating-linear-gradient(-60deg,transparent,transparent 28px,rgba(255,255,255,.025) 28px,rgba(255,255,255,.025) 29px);
            pointer-events:none;
        }
        .nav-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 30px;
            display: flex;
            align-items: center;
            position: relative; z-index:1;
        }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 6px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding: 0 18px;
            height: 46px;
            border-right: 1px solid rgba(255,255,255,0.07);
            transition: all 0.2s;
            position: relative;
            white-space: nowrap;
        }
        .nav-item::after {
            content:'';
            position:absolute;
            bottom:0;left:0;right:0;
            height:3px;
            background:var(--gold);
            transform:scaleX(0);
            transition:transform .2s;
        }
        .nav-item:hover { background:rgba(255,255,255,.08); color:white; }
        .nav-item:hover::after, .nav-item.active::after { transform:scaleX(1); }
        .nav-item.active { color:var(--gold); }
        .nav-item:first-child { padding-left: 0; }

        /* ── MAIN LAYOUT ───────────────────────── */
        .main-wrap {
            max-width: 1200px;
            margin: 24px auto;
            padding: 0 30px;
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 24px;
            align-items: start;
        }

        /* ── SLIDER FLYER ──────────────────────── */
        .slider-wrap {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            position: relative;
        }
        .slider-track {
            display: flex;
            transition: transform 0.5s cubic-bezier(.4,0,.2,1);
        }
        .slide {
            min-width: 100%;
            position: relative;
        }
        .slide-img {
            width: 100%;
            height: 320px;
            object-fit: cover;
            display: block;
        }
        /* Slide placeholder jika tidak ada gambar */
        .slide-placeholder {
            width: 100%;
            height: 320px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
            position: relative;
            overflow: hidden;
        }
        .slide-placeholder.s1 { background: linear-gradient(135deg, #0f1e3c, #1e3a6e); }
        .slide-placeholder.s2 { background: linear-gradient(135deg, #c9a84c, #8b6914); }
        .slide-placeholder.s3 { background: linear-gradient(135deg, #1e3a6e, #0f1e3c); }
        .slide-text {
            text-align: center;
            color: white;
            z-index: 1;
            padding: 30px;
        }
        .slide-text h2 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(22px, 3vw, 36px);
            margin-bottom: 10px;
        }
        .slide-text h2 span { color: var(--gold); }
        .slide-text p { font-size: 14px; opacity: 0.8; margin-bottom: 16px; }
        .slide-btn {
            display: inline-block;
            padding: 10px 28px;
            background: var(--gold);
            color: var(--navy);
            border-radius: 50px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s;
        }
        .slide-btn:hover { background: var(--gold2); transform: translateY(-2px); }

        /* Slider controls */
        .slider-btn {
            position: absolute;
            top: 50%; transform: translateY(-50%);
            width: 36px; height: 36px;
            background: rgba(255,255,255,0.9);
            border: none; border-radius: 50%;
            font-size: 16px; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            transition: all 0.2s; z-index: 10;
            color: var(--navy);
        }
        .slider-btn:hover { background: var(--gold); color: var(--navy); }
        .slider-btn.prev { left: 12px; }
        .slider-btn.next { right: 12px; }

        /* Dots */
        .slider-dots {
            position: absolute;
            bottom: 14px;
            left: 50%; transform: translateX(-50%);
            display: flex; gap: 6px; z-index: 10;
        }
        .dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: rgba(255,255,255,0.5);
            cursor: pointer;
            transition: all 0.2s;
            border: none;
        }
        .dot.active {
            background: var(--gold);
            width: 22px;
            border-radius: 50px;
        }

        /* ── SIDEBAR KANAN ─────────────────────── */
        .sidebar { display: flex; flex-direction: column; gap: 20px; }

        .side-box {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }
        .side-box-head {
            background: var(--navy);
            padding: 12px 18px;
            display: flex; align-items: center; gap: 8px;
        }
        .side-box-head h3 {
            font-family: 'Playfair Display', serif;
            color: white;
            font-size: 14px;
        }
        .side-box-body { padding: 16px; }

        /* Pengumuman sidebar */
        .pengumuman-item {
            padding: 10px 0;
            border-bottom: 1px solid #f0f2f7;
            display: flex; gap: 10px; align-items: flex-start;
        }
        .pengumuman-item:last-child { border-bottom: none; padding-bottom: 0; }
        .p-icon {
            width: 32px; height: 32px;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 14px; flex-shrink: 0;
        }
        .p-icon.diskon  { background: #fef2f2; }
        .p-icon.promo   { background: #f0fdf4; }
        .p-icon.info    { background: #eff6ff; }
        .p-icon.penting { background: #fffbeb; }
        .p-title { font-size: 12px; font-weight: 600; color: var(--navy); line-height: 1.3; margin-bottom: 2px; }
        .p-date  { font-size: 10px; color: var(--muted); }

        /* Statistik */
        .stat-row {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 0; border-bottom: 1px solid #f0f2f7;
        }
        .stat-row:last-child { border-bottom: none; padding-bottom: 0; }
        .stat-icon { font-size: 22px; flex-shrink: 0; }
        .stat-num  { font-family: 'Playfair Display', serif; font-size: 22px; color: var(--navy); line-height: 1; }
        .stat-lbl  { font-size: 11px; color: var(--muted); }

        /* ── PRODUK SECTION ────────────────────── */
        .section-wrap {
            max-width: 1200px;
            margin: 0 auto 30px;
            padding: 0 30px;
        }
        .section-head {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 3px solid var(--gold);
        }
        .section-head h2 {
            font-family: 'Playfair Display', serif;
            font-size: 20px; color: var(--navy);
            display: flex; align-items: center; gap: 8px;
        }
        .section-head a {
            font-size: 12px; color: var(--navy);
            text-decoration: none; font-weight: 600;
            border: 1.5px solid var(--navy);
            padding: 5px 14px; border-radius: 50px;
            transition: all 0.2s;
        }
        .section-head a:hover { background: var(--navy); color: white; }

        .produk-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
            gap: 16px;
        }
        .produk-card {
            background: white;
            border-radius: 10px;
            padding: 18px 14px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            border-top: 3px solid transparent;
            transition: all 0.2s;
            animation: fadeUp 0.4s ease both;
        }
        .produk-card:hover {
            border-top-color: var(--gold);
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        }
        .produk-card-img {
            width: 100%; height: 100px;
            object-fit: cover;
            border-radius: 6px;
            margin-bottom: 8px;
            display: block;
        }
        .produk-card-icon { font-size: 36px; margin-bottom: 8px; }
        .produk-card-name { font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 4px; }
        .produk-card-price { font-size: 14px; font-weight: 700; color: var(--green); margin-bottom: 10px; }
        .produk-card-btn {
            display: block; padding: 7px;
            background: var(--navy); color: white;
            border-radius: 6px; font-size: 11px;
            font-weight: 600; text-decoration: none;
            transition: background 0.2s;
        }
        .produk-card-btn:hover { background: #1a3060; }

        /* ── PENGUMUMAN BAWAH ──────────────────── */
        .pengumuman-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 16px;
        }
        .p-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            border-left: 4px solid var(--gold);
            transition: all 0.2s;
            animation: fadeUp 0.4s ease both;
        }
        .p-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.12); }
        .p-card.diskon  { border-left-color: #e53e3e; }
        .p-card.promo   { border-left-color: #16a34a; }
        .p-card.info    { border-left-color: #1d4ed8; }
        .p-card.penting { border-left-color: #d97706; }
        .p-card-body { padding: 16px; }
        .p-card-top { display:flex;align-items:center;gap:8px;margin-bottom:8px; }
        .p-card-icon { font-size:20px; }
        .p-card-title { font-size:14px;font-weight:700;color:var(--navy); }
        .p-card-isi   { font-size:12px;color:var(--muted);line-height:1.6; }
        .p-card-date  { font-size:10px;color:#bbb;margin-top:8px; }

        /* ── FOOTER ────────────────────────────── */
        .site-footer {
            background: var(--navy);
            padding: 30px;
            margin-top: 30px;
        }
        .footer-inner {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 30px;
        }
        .footer-col h4 {
            font-family: 'Playfair Display', serif;
            color: var(--gold);
            font-size: 14px;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid rgba(201,168,76,0.3);
        }
        .footer-col p, .footer-col a {
            font-size: 12px;
            color: rgba(255,255,255,0.55);
            line-height: 1.8;
            text-decoration: none;
            display: block;
        }
        .footer-col a:hover { color: var(--gold2); }
        .footer-bottom {
            max-width: 1200px;
            margin: 20px auto 0;
            padding-top: 16px;
            border-top: 1px solid rgba(255,255,255,0.08);
            text-align: center;
            font-size: 11px;
            color: rgba(255,255,255,0.25);
        }

        @keyframes fadeUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }

        /* ── GALERI LANDING ──────────────────── */
        .galeri-landing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 16px;
        }
        .galeri-landing-card {
            border-radius: 10px;
            overflow: hidden;
            position: relative;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform .2s, box-shadow .2s;
            animation: fadeUp .4s ease both;
            aspect-ratio: 4/3;
            background: var(--cream);
        }
        .galeri-landing-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 28px rgba(0,0,0,0.18);
        }
        .galeri-landing-card img {
            width: 100%; height: 100%;
            object-fit: cover; display: block;
            transition: transform .4s;
        }
        .galeri-landing-card:hover img { transform: scale(1.07); }
        .galeri-landing-overlay {
            position: absolute; inset: 0;
            background: linear-gradient(to top, rgba(15,30,60,0.85) 0%, transparent 50%);
            opacity: 0; transition: opacity .2s;
            display: flex; align-items: flex-end; padding: 14px;
        }
        .galeri-landing-card:hover .galeri-landing-overlay { opacity: 1; }
        .galeri-landing-overlay-text {
            color: white;
        }
        .galeri-landing-overlay-text h4 { font-size: 13px; font-weight: 600; margin-bottom: 2px; }
        .galeri-landing-overlay-text p  { font-size: 11px; opacity: .75; }
        .galeri-kat-pill {
            position: absolute; top: 10px; left: 10px;
            background: var(--gold); color: var(--navy);
            font-size: 9px; font-weight: 700;
            padding: 3px 9px; border-radius: 50px;
        }
        /* ── LIVE CHAT WIDGET ───────────────── */
        .chat-widget-btn {
            position: fixed;
            bottom: 28px; right: 28px;
            z-index: 999999;
            display: flex; flex-direction: column; align-items: flex-end; gap: 10px;
            pointer-events: all;
        }
        .chat-fab {
            width: 60px; height: 60px;
            background: var(--gold);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 26px;
            cursor: pointer !important;
            box-shadow: 0 6px 20px rgba(201,168,76,0.5);
            border: none;
            transition: all .2s;
            position: relative;
            z-index: 999999;
            pointer-events: all !important;
        }
        .chat-fab:hover { transform: scale(1.1); background: var(--gold2); }
        .chat-fab .chat-notif {
            position: absolute; top: -4px; right: -4px;
            width: 18px; height: 18px;
            background: #e53e3e; color: white;
            border-radius: 50%; font-size: 10px; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            border: 2px solid white;
            animation: pulse-badge 1.5s ease infinite;
        }
        @keyframes pulse-badge {
            0%,100%{transform:scale(1)} 50%{transform:scale(1.2)}
        }
        .chat-label {
            background: var(--navy); color: white;
            padding: 7px 14px; border-radius: 50px;
            font-size: 12px; font-weight: 600;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            animation: fadeUp .3s ease both;
            white-space: nowrap;
        }

        /* Chat Box */
        .chat-box-widget {
            position: fixed;
            bottom: 100px; right: 28px;
            width: 340px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            z-index: 999998;
            display: none;
            flex-direction: column;
            overflow: hidden;
            animation: slideUp .3s cubic-bezier(.22,.68,0,1.2) both;
            pointer-events: all;
        }
        .chat-box-widget.open { display: flex; }
        @keyframes slideUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }

        .chat-box-head {
            background: var(--navy);
            padding: 16px 18px;
            display: flex; align-items: center; gap: 12px;
        }
        .chat-head-avatar {
            width: 40px; height: 40px;
            border-radius: 50%;
            border: 2px solid var(--gold);
            background: rgba(255,255,255,0.1);
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; flex-shrink: 0;
        }
        .chat-head-info h4 { color: white; font-size: 14px; font-weight: 600; }
        .chat-head-info p  { color: var(--gold2); font-size: 11px; display:flex;align-items:center;gap:4px; }
        .online-dot { width:7px;height:7px;background:#22c55e;border-radius:50%;display:inline-block;animation:pulse-badge 1.5s ease infinite; }
        .chat-box-close { margin-left:auto; background:none; border:none; color:rgba(255,255,255,.6); font-size:20px; cursor:pointer; }
        .chat-box-close:hover { color:white; }

        /* Tab tipe */
        .chat-tipe-bar {
            display: flex; border-bottom: 1px solid #f0f2f7;
            background: var(--cream);
        }
        .chat-tipe-btn {
            flex: 1; padding: 9px 6px;
            background: none; border: none;
            font-size: 11px; font-weight: 600;
            color: var(--muted); cursor: pointer;
            border-bottom: 2px solid transparent;
            font-family: 'DM Sans', sans-serif;
            transition: all .2s;
        }
        .chat-tipe-btn.active { color: var(--navy); border-bottom-color: var(--gold); }

        /* Messages */
        .chat-messages {
            flex: 1; overflow-y: auto;
            padding: 14px; min-height: 180px; max-height: 220px;
            display: flex; flex-direction: column; gap: 8px;
            scroll-behavior: smooth;
        }
        .chat-messages::-webkit-scrollbar { width: 3px; }
        .chat-messages::-webkit-scrollbar-thumb { background: #ddd; border-radius: 3px; }

        .chat-msg {
            padding: 9px 12px; border-radius: 12px;
            font-size: 12px; line-height: 1.5;
            max-width: 85%; animation: fadeUp .2s ease both;
        }
        .chat-msg.system {
            background: var(--cream); color: var(--muted);
            align-self: center; text-align: center;
            font-style: italic; border-radius: 8px;
            font-size: 11px;
        }
        .chat-msg.sent {
            background: var(--navy); color: white;
            align-self: flex-end; border-bottom-right-radius: 4px;
        }
        .chat-msg-time { font-size: 9px; opacity: .5; margin-top: 3px; text-align: right; }

        /* Form input chat */
        .chat-form-area { padding: 12px 14px; border-top: 1px solid #f0f2f7; }
        .chat-name-input {
            width: 100%; padding: 8px 12px; margin-bottom: 8px;
            border: 1.5px solid #e0e4ec; border-radius: 8px;
            font-size: 12px; font-family: 'DM Sans', sans-serif;
            outline: none; background: var(--cream);
        }
        .chat-name-input:focus { border-color: var(--gold); }
        .chat-input-row { display: flex; gap: 8px; }
        .chat-input-row textarea {
            flex: 1; padding: 9px 12px;
            border: 1.5px solid #e0e4ec; border-radius: 8px;
            font-size: 12px; font-family: 'DM Sans', sans-serif;
            resize: none; height: 40px; max-height: 80px;
            outline: none; background: var(--cream);
            transition: border-color .2s;
        }
        .chat-input-row textarea:focus { border-color: var(--gold); }
        .chat-send-btn {
            width: 40px; height: 40px;
            background: var(--navy); color: white;
            border: none; border-radius: 8px;
            font-size: 16px; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: background .2s;
            flex-shrink: 0;
        }
        .chat-send-btn:hover { background: #1a3060; }
        .chat-hint { font-size: 10px; color: var(--muted); margin-top: 6px; text-align: center; }

        /* ── WILAYAH ──────────────────────── */
        .wilayah-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }
        .wilayah-card {
            background: white;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            transition: transform .25s, box-shadow .25s;
            animation: fadeUp .4s ease both;
        }
        .wilayah-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 32px rgba(0,0,0,0.14);
        }
        .wilayah-head {
            background: linear-gradient(135deg, var(--navy), #1e3a6e);
            padding: 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .wilayah-head::before {
            content:'';
            position:absolute;inset:0;
            background: radial-gradient(circle at 70% 20%, rgba(201,168,76,0.2), transparent 60%);
        }
        .wilayah-num {
            position: absolute; top: 10px; left: 14px;
            font-size: 10px; font-weight: 700;
            color: rgba(255,255,255,.35);
            letter-spacing: 1.5px;
        }
        .wilayah-avatar {
            width: 72px; height: 72px;
            border-radius: 50%;
            border: 3px solid var(--gold);
            object-fit: cover;
            margin: 0 auto 10px;
            display: block;
            background: rgba(255,255,255,.1);
            position: relative; z-index: 1;
        }
        .wilayah-avatar-placeholder {
            width: 72px; height: 72px;
            border-radius: 50%;
            border: 3px solid var(--gold);
            background: rgba(255,255,255,.15);
            display: flex; align-items: center; justify-content: center;
            font-size: 30px; margin: 0 auto 10px;
            position: relative; z-index: 1;
        }
        .wilayah-nama {
            font-family: 'Playfair Display', serif;
            color: white; font-size: 15px; font-weight: 600;
            position: relative; z-index: 1;
        }
        .wilayah-jabatan {
            color: var(--gold2); font-size: 11px;
            margin-top: 3px; position: relative; z-index: 1;
        }
        .wilayah-body { padding: 16px; }
        .wilayah-name-badge {
            display: flex; align-items: center; gap: 6px;
            background: var(--cream); border-radius: 8px;
            padding: 8px 12px; margin-bottom: 10px;
        }
        .wilayah-name-badge span { font-size: 12px; font-weight: 700; color: var(--navy); }
        .wilayah-area {
            font-size: 11px; color: var(--muted);
            line-height: 1.7; margin-bottom: 12px;
            padding-left: 4px;
            border-left: 2px solid var(--gold);
            padding-left: 10px;
        }
        .wilayah-wa {
            display: flex; align-items: center; gap: 6px;
            background: #dcfce7; color: #16a34a;
            border: 1px solid #86efac;
            padding: 8px 14px; border-radius: 50px;
            font-size: 12px; font-weight: 700;
            text-decoration: none; transition: all .2s;
            justify-content: center;
        }
        .wilayah-wa:hover { background: #bbf7d0; transform: scale(1.02); }

        @media (max-width: 900px) { .wilayah-grid { grid-template-columns: repeat(2,1fr); } }
        @media (max-width: 500px) { .wilayah-grid { grid-template-columns: 1fr; } }

        /* Lightbox galeri landing */
        .lb-wrap { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.92); z-index:99997; align-items:center; justify-content:center; padding:20px; cursor:zoom-out; }
        .lb-wrap.open { display:flex; animation:fadeIn .2s ease; }
        @keyframes fadeIn{from{opacity:0}to{opacity:1}}
        .lb-wrap img { max-width:90vw; max-height:85vh; object-fit:contain; border-radius:8px; cursor:default; box-shadow:0 20px 60px rgba(0,0,0,0.5); }
        .lb-info { position:absolute; bottom:24px; left:50%; transform:translateX(-50%); text-align:center; color:white; }
        .lb-info h4 { font-size:15px; margin-bottom:3px; }
        .lb-info p  { font-size:12px; opacity:.6; }
        .lb-close { position:absolute; top:20px; right:24px; color:white; font-size:28px; background:none; border:none; cursor:pointer; opacity:.7; }
        .lb-close:hover { opacity:1; }
        .lb-nav { position:absolute; top:50%; transform:translateY(-50%); background:rgba(255,255,255,.15); border:none; color:white; font-size:28px; width:48px; height:48px; border-radius:50%; cursor:pointer; display:flex;align-items:center;justify-content:center; transition:background .2s; }
        .lb-nav:hover { background:rgba(255,255,255,.3); }
        .lb-nav.prev { left:16px; }
        .lb-nav.next { right:16px; }

        @media (max-width: 900px) {
            .header-inner { grid-template-columns: 1fr auto; }
            .header-building { display: none; }
            .main-wrap { grid-template-columns: 1fr; }
            .footer-inner { grid-template-columns: 1fr; }
        }
        @media (max-width: 600px) {
            .header-inner { grid-template-columns: 1fr; }
            .header-login { display: none; }
            .header-title h1 { font-size: 22px; }
        }
    </style>
</head>
<body>

<!-- Top strip ticker -->
<div class="top-strip">
    <div class="ticker">
        <span>🎉 Selamat datang di PT JUMA TIGA SEANTERO</span>
        <span>🏷️ Cek promo &amp; diskon terbaru</span>
        <span>📦 Pengiriman ke seluruh Sumatera Utara</span>
        <span>📞 Hubungi kami: (061) 123-4567</span>
        <span>🕐 Operasional: Senin–Sabtu 08.00–17.00 WIB</span>
        <span>🎉 Selamat datang di PT JUMA TIGA SEANTERO</span>
        <span>🏷️ Cek promo &amp; diskon terbaru</span>
        <span>📦 Pengiriman ke seluruh Sumatera Utara</span>
        <span>📞 Hubungi kami: (061) 123-4567</span>
        <span>🕐 Operasional: Senin–Sabtu 08.00–17.00 WIB</span>
    </div>
</div>

<!-- Header Utama -->
<div class="site-header">
    <div class="header-inner">
        <!-- Logo + Nama -->
        <div class="header-logo">
            <img src="assets/logo_jt2.jpeg" alt="Logo PT JUMA TIGA SEANTERO" class="logo-img">
            <div class="header-title">
                <h1>PT <span>JUMA TIGA SEANTERO</span></h1>
                <div class="tagline">Sistem Manajemen Penjualan</div>
                <div class="akreditasi">🏆 Distributor Aice Kabanjahe </div>
            </div>
        </div>


        <!-- Form Login -->
        <div class="header-login">
            <h4>🔐 Login</h4>
            <?php if ($loginError): ?>
                <div class="login-error">⚠️ Username atau password salah!</div>
            <?php endif; ?>
            <form method="POST" action="proses_login.php">
                <div class="login-field">
                    <span class="login-field-icon">👤</span>
                    <input type="text" name="username" placeholder="Username" required autofocus>
                </div>
                <div class="login-field">
                    <span class="login-field-icon">🔑</span>
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <button type="submit" class="btn-login-header">MASUK →</button>
            </form>
            <div class="login-register-link">
                Belum punya akun? <a href="register.php">Daftar</a>
            </div>
        </div>
    </div>
</div>

<!-- Navbar Menu -->
<nav class="site-nav">
    <div class="nav-inner">
        <a href="#home"       class="nav-item active">🏠 Beranda</a>
        <a href="#pengumuman" class="nav-item">📢 Info Terkini</a>
        <a href="#produk"     class="nav-item">📦 Produk</a>
        <a href="#tentang"    class="nav-item">🏢 Tentang Kami</a>
        <a href="#kontak"     class="nav-item">📞 Kontak</a>
        <a href="#galeri"     class="nav-item">🖼️ Galeri</a>
        <a href="#wilayah"   class="nav-item">📍 Wilayah</a>
        <a href="login.php"   class="nav-item" style="margin-left:auto;color:var(--gold);border-right:none;">🔐 Login</a>
    </div>
</nav>

<!-- Main Content -->
<div class="main-wrap" id="home">

    <!-- Slider Flyer -->
    <div>
        <div class="slider-wrap">
            <div class="slider-track" id="sliderTrack">

                <!-- Slide 1 -->
                <div class="slide">
                    <div class="slide-placeholder s1">
                        <div style="position:absolute;inset:0;background-image:radial-gradient(rgba(255,255,255,0.05) 1px,transparent 1px);background-size:20px 20px;"></div>
                        <div class="slide-text">
                            <div style="font-size:40px;margin-bottom:10px;">🏢</div>
                            <h2>Selamat Datang di<br><span>PT JUMA TIGA SEANTERO</span></h2>
                            <p>Distributor Resmi Aice Kabanjahe</p>
                            <a href="register.php" class="slide-btn">📝 Daftar Sekarang</a>
                        </div>
                    </div>
                </div>

                <!-- Slide 2: Promo -->
                <div class="slide">
                    <div class="slide-placeholder s2">
                        <div style="position:absolute;top:-40px;right:-40px;width:200px;height:200px;border-radius:50%;background:rgba(255,255,255,0.1);"></div>
                        <div style="position:absolute;bottom:-30px;left:-30px;width:150px;height:150px;border-radius:50%;background:rgba(255,255,255,0.08);"></div>
                        <div class="slide-text">
                            <div style="font-size:48px;margin-bottom:10px;">🏷️</div>
                            <h2>Promo <span style="color:var(--navy);">Spesial</span></h2>
                            <p style="color:rgba(15,30,60,0.7);">Dapatkan penawaran terbaik untuk produk pilihan kami</p>
                            <a href="#pengumuman" class="slide-btn" style="background:var(--navy);color:white;">Lihat Promo →</a>
                        </div>
                    </div>
                </div>

                <!-- Slide 3: Produk -->
                <div class="slide">
                    <div class="slide-placeholder s3">
                        <div style="position:absolute;inset:0;background:radial-gradient(circle at 30% 50%,rgba(201,168,76,0.2),transparent 60%);"></div>
                        <div class="slide-text">
                            <div style="font-size:48px;margin-bottom:10px;">📦</div>
                            <h2>Produk <span>Berkualitas</span></h2>
                            <p>Ribuan produk pilihan dengan harga terbaik</p>
                            <a href="#produk" class="slide-btn">Lihat Produk →</a>
                        </div>
                    </div>
                </div>

                <?php
                // Slide dari foto kantor
                ?>
                <div class="slide">
                    <div style="position:relative;height:320px;overflow:hidden;">
                        <img src="assets/foto_kantor.jpeg" alt="Kantor PT JUMA TIGA SEANTERO"
                             style="width:100%;height:100%;object-fit:cover;display:block;filter:brightness(0.6);">
                        <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:30px;">
                            <h2 style="font-family:'Playfair Display',serif;color:white;font-size:28px;margin-bottom:10px;">Kantor &amp; Fasilitas Kami</h2>
                            <p style="color:rgba(255,255,255,0.8);font-size:14px;margin-bottom:16px;">Jl. Perdagangan No. 3, Medan, Sumatera Utara</p>
                            <a href="#kontak" class="slide-btn">📍 Lihat Lokasi</a>
                        </div>
                    </div>
                </div>

            </div><!-- slider-track -->

            <button class="slider-btn prev" onclick="slideMove(-1)">‹</button>
            <button class="slider-btn next" onclick="slideMove(1)">›</button>
            <div class="slider-dots" id="sliderDots"></div>
        </div><!-- slider-wrap -->

        <!-- Logo Mitra di bawah slider -->
        <div style="background:white;border-radius:12px;padding:16px 20px;margin-top:16px;box-shadow:0 2px 8px rgba(0,0,0,0.07);">
            <p style="font-size:10px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:var(--muted);margin-bottom:12px;">Mitra &amp; Brand Kami</p>
            <div style="display:flex;gap:20px;align-items:center;flex-wrap:wrap;">
                <div style="background:var(--cream);border:1.5px solid #e8eaf0;border-radius:8px;padding:10px 20px;display:flex;align-items:center;justify-content:center;">
                    <img src="assets/logo_aice.jpeg" alt="Aice" style="height:36px;object-fit:contain;max-width:100px;">
                </div>
                <div style="font-size:12px;color:var(--muted);">+ brand lainnya</div>
            </div>
        </div>

        <!-- Produk Unggulan (di bawah mitra) -->
        <?php if (!empty($produkUnggulan)): ?>
        <div style="margin-top:16px;" id="produk">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;padding-bottom:10px;border-bottom:3px solid var(--gold);">
                <h2 style="font-family:'Playfair Display',serif;font-size:18px;color:var(--navy);display:flex;align-items:center;gap:8px;">📦 Produk Unggulan</h2>
                <a href="login.php" style="font-size:11px;color:var(--navy);text-decoration:none;font-weight:600;border:1.5px solid var(--navy);padding:4px 12px;border-radius:50px;transition:all .2s;"
                   onmouseover="this.style.background='var(--navy)';this.style.color='white';"
                   onmouseout="this.style.background='';this.style.color='var(--navy)';">
                    Login untuk Beli →
                </a>
            </div>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;">
                <?php
                $pIcons = ['📦','🎁','🛍️','💼','🏷️','🎀'];
                foreach (array_slice($produkUnggulan,0,6) as $i => $p):
                    $delay = ($i*0.06).'s';
                    $uploadDir = 'uploads/produk/';
                    $hasImg = !empty($p['gambar']) && file_exists($uploadDir.$p['gambar']);
                ?>
                <div class="produk-card" style="animation-delay:<?= $delay ?>;padding:12px 10px;">
                    <?php if ($hasImg): ?>
                        <img src="<?= $uploadDir.htmlspecialchars($p['gambar']) ?>"
                             alt="<?= htmlspecialchars($p['nama_produk']) ?>"
                             class="produk-card-img" style="height:70px;">
                    <?php else: ?>
                        <div class="produk-card-icon" style="font-size:28px;"><?= $pIcons[$i%count($pIcons)] ?></div>
                    <?php endif; ?>
                    <div class="produk-card-name" style="font-size:12px;"><?= htmlspecialchars(substr($p['nama_produk'],0,20)) ?></div>
                    <div class="produk-card-price" style="font-size:12px;">Rp <?= number_format($p['harga'],0,',','.') ?></div>
                    <a href="login.php" class="produk-card-btn" style="font-size:10px;padding:5px;">🔐 Beli</a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- Sidebar Kanan -->
    <div class="sidebar">

        <!-- Statistik -->
        <div class="side-box">
            <div class="side-box-head"><span>📊</span><h3>Statistik</h3></div>
            <div class="side-box-body">
                <?php
                $totalProduk  = $conn->query("SELECT COUNT(*) as n FROM produk")->fetch_assoc()['n'] ?? 0;
                $totalUser    = $conn->query("SELECT COUNT(*) as n FROM users")->fetch_assoc()['n'] ?? 0;
                $totalTrx     = $conn->query("SELECT COUNT(*) as n FROM transaksi")->fetch_assoc()['n'] ?? 0;
                ?>
                <div class="stat-row">
                    <div class="stat-icon">📦</div>
                    <div><div class="stat-num"><?= $totalProduk ?></div><div class="stat-lbl">Produk Tersedia</div></div>
                </div>
                <div class="stat-row">
                    <div class="stat-icon">👤</div>
                    <div><div class="stat-num"><?= $totalUser ?></div><div class="stat-lbl">Pengguna Terdaftar</div></div>
                </div>
                <div class="stat-row">
                    <div class="stat-icon">🛒</div>
                    <div><div class="stat-num"><?= $totalTrx ?></div><div class="stat-lbl">Total Transaksi</div></div>
                </div>
                <div class="stat-row">
                    <div class="stat-icon">🏆</div>
                    <div><div class="stat-num">9+</div><div class="stat-lbl">Tahun Berpengalaman</div></div>
                </div>
            </div>
        </div>

        <!-- Pengumuman terbaru -->
        <?php if (!empty($pengumumans)): ?>
        <div class="side-box" id="pengumuman">
            <div class="side-box-head"><span>📢</span><h3>Info Terkini</h3></div>
            <div class="side-box-body">
                <?php
                $tipeIcon = ['diskon'=>'🏷️','promo'=>'🎁','info'=>'ℹ️','penting'=>'⚠️'];
                foreach (array_slice($pengumumans, 0, 4) as $p):
                ?>
                <div class="pengumuman-item">
                    <div class="p-icon <?= $p['tipe'] ?>"><?= $tipeIcon[$p['tipe']] ?? 'ℹ️' ?></div>
                    <div>
                        <div class="p-title"><?= htmlspecialchars($p['judul']) ?></div>
                        <div class="p-date">📅 <?= date('d M Y', strtotime($p['created_at'])) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Kontak -->
        <div class="side-box" id="kontak">
            <div class="side-box-head"><span>📞</span><h3>Kontak Kami</h3></div>
            <div class="side-box-body">
                <div style="font-size:13px;color:var(--text);display:flex;flex-direction:column;gap:8px;">
                    <span>📍 Jl. Letjen Jamin Ginting Kel No.22, Gung Negeri, Kec. Kabanjahe, Kabupaten Karo, Sumatera Utara 22152</span>
                    <span>📞 (061) 123-4567</span>
                    <span>✉️ info@jumatigaseantero.co.id</span>
                    <span>🕐 Senin–Sabtu, 08.00–17.00 WIB</span>
                </div>
            </div>
        </div>

    </div>
</div><!-- main-wrap -->

<!-- Pengumuman Lengkap -->
<?php if (!empty($pengumumans)): ?>
<div class="section-wrap">
    <div class="section-head">
        <h2>📢 Info Terkini &amp; Promo</h2>
    </div>
    <div class="pengumuman-grid">
        <?php
        $tipeIcon = ['diskon'=>'🏷️','promo'=>'🎁','info'=>'ℹ️','penting'=>'⚠️'];
        foreach ($pengumumans as $i => $p):
            $delay = ($i*0.06).'s';
        ?>
        <div class="p-card <?= $p['tipe'] ?>" style="animation-delay:<?= $delay ?>">
            <?php if (!empty($p['gambar']) && file_exists('uploads/pengumuman/'.$p['gambar'])): ?>
                <img src="uploads/pengumuman/<?= htmlspecialchars($p['gambar']) ?>"
                     style="width:100%;height:120px;object-fit:cover;display:block;">
            <?php endif; ?>
            <div class="p-card-body">
                <div class="p-card-top">
                    <span class="p-card-icon"><?= $tipeIcon[$p['tipe']] ?? 'ℹ️' ?></span>
                    <span class="p-card-title"><?= htmlspecialchars($p['judul']) ?></span>
                </div>
                <div class="p-card-isi"><?= htmlspecialchars(substr($p['isi'],0,100)).(strlen($p['isi'])>100?'...':'') ?></div>
                <div class="p-card-date">📅 <?= date('d M Y', strtotime($p['created_at'])) ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Galeri -->
<?php if (!empty($galeriLanding)): ?>
<div class="section-wrap" id="galeri">
    <div class="section-head">
        <h2>🖼️ Galeri</h2>
        <a href="login.php">Login untuk lebih →</a>
    </div>
    <div class="galeri-landing-grid">
        <?php
        $katLabel = ['produk'=>'📦 Produk','kegiatan'=>'🎉 Kegiatan','kantor'=>'🏢 Kantor','promosi'=>'🏷️ Promosi','lainnya'=>'📷'];
        foreach ($galeriLanding as $i => $g):
            $delay   = ($i * 0.05) . 's';
            $imgPath = 'uploads/galeri/' . $g['gambar'];
        ?>
        <div class="galeri-landing-card" style="animation-delay:<?= $delay ?>"
             onclick="openLb(<?= $i ?>)">
            <?php if (file_exists($imgPath)): ?>
                <img src="<?= $imgPath ?>" alt="<?= htmlspecialchars($g['judul']) ?>" loading="lazy">
            <?php else: ?>
                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:48px;color:#ccc;background:var(--cream);">🖼️</div>
            <?php endif; ?>
            <div class="galeri-landing-overlay">
                <div class="galeri-landing-overlay-text">
                    <h4><?= htmlspecialchars($g['judul']) ?></h4>
                    <?php if ($g['deskripsi']): ?>
                        <p><?= htmlspecialchars(substr($g['deskripsi'],0,60)) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <span class="galeri-kat-pill"><?= $katLabel[$g['kategori']] ?? '📷' ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Tentang Kami -->
<div class="section-wrap" id="tentang">
    <div class="section-head">
        <h2>🏢 Tentang PT JUMA TIGA SEANTERO</h2>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
        <div style="background:white;border-radius:12px;padding:24px;box-shadow:0 2px 8px rgba(0,0,0,0.07);">
            <h3 style="font-family:'Playfair Display',serif;color:var(--navy);margin-bottom:12px;">🔭 Visi</h3>
            <p style="font-size:13px;color:var(--muted);line-height:1.8;">Menjadi perusahaan distribusi terkemuka di Sumatera Utara yang dipercaya mitra dan pelanggan dengan mengutamakan kualitas, integritas, dan inovasi berkelanjutan.</p>
        </div>
        <div style="background:white;border-radius:12px;padding:24px;box-shadow:0 2px 8px rgba(0,0,0,0.07);">
            <h3 style="font-family:'Playfair Display',serif;color:var(--navy);margin-bottom:12px;">🚀 Misi</h3>
            <p style="font-size:13px;color:var(--muted);line-height:1.8;">Menyediakan produk berkualitas tinggi, membangun kemitraan yang saling menguntungkan, menerapkan sistem manajemen yang efisien, dan berkontribusi pada pertumbuhan ekonomi lokal.</p>
        </div>
    </div>
</div>

<!-- Jangkauan Wilayah -->
<div class="section-wrap" id="wilayah">
    <div class="section-head">
        <h2>📍 Jangkauan Wilayah</h2>
    </div>
    <p style="color:var(--muted);font-size:14px;margin-bottom:24px;line-height:1.7;">
        PT JUMA TIGA SEANTERO melayani distribusi di <?= count($wilayahList) ?> wilayah utama.
        Hubungi admin wilayah kamu untuk informasi lebih lanjut.
    </p>

    <?php if (!empty($wilayahList)): ?>
    <div class="wilayah-grid">
        <?php
        $wilayahColors = [
            'linear-gradient(135deg,#0f1e3c,#1e3a6e)',
            'linear-gradient(135deg,#1a3060,#0f2d5c)',
            'linear-gradient(135deg,#0a1628,#1a2e50)',
            'linear-gradient(135deg,#162040,#0f1e3c)',
        ];
        foreach ($wilayahList as $i => $w):
            $bg  = $wilayahColors[$i % count($wilayahColors)];
            $hp  = $w['no_hp'];
            $waMsg = urlencode("Halo Admin Wilayah ".$w['nama_wilayah'].", saya ingin bertanya tentang produk PT JUMA TIGA SEANTERO");
            $waUrl = "https://wa.me/$hp?text=$waMsg";
            $delay = ($i*.06).'s';
        ?>
        <div class="wilayah-card" style="animation-delay:<?= $delay ?>">
            <div class="wilayah-head" style="background:<?= $bg ?>;">
                <div class="wilayah-num">WILAYAH <?= str_pad($w['urutan']?:($i+1),2,'0',STR_PAD_LEFT) ?></div>
                <div class="wilayah-avatar-placeholder">👤</div>
                <div class="wilayah-nama"><?= htmlspecialchars($w['nama_admin']) ?></div>
                <div class="wilayah-jabatan">Admin Wilayah <?= htmlspecialchars($w['nama_wilayah']) ?></div>
            </div>
            <div class="wilayah-body">
                <div class="wilayah-name-badge">
                    <span>📍 <?= htmlspecialchars($w['nama_wilayah']) ?></span>
                </div>
                <?php if (!empty($w['area_coverage'])): ?>
                <div class="wilayah-area">
                    <?= nl2br(htmlspecialchars($w['area_coverage'])) ?>
                </div>
                <?php endif; ?>
                <div style="font-size:12px;color:var(--muted);margin-bottom:10px;display:flex;align-items:center;gap:6px;">
                    📱 <?php
                    $hpDisplay = '0'.substr($hp,2);
                    echo htmlspecialchars($hpDisplay);
                    ?>
                </div>
                <a href="<?= $waUrl ?>" target="_blank" class="wilayah-wa">
                    💬 Hubungi via WhatsApp
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <!-- Fallback jika belum ada data di DB -->
    <div style="text-align:center;padding:40px;background:white;border-radius:12px;color:var(--muted);">
        <div style="font-size:40px;margin-bottom:12px;">📍</div>
        <p>Data wilayah sedang diperbarui.</p>
    </div>
    <?php endif; ?>
</div>

<!-- Footer -->
<div class="site-footer">
    <div class="footer-inner">
        <div class="footer-col">
            <h4>PT JUMA TIGA SEANTERO</h4>
            <p>Mitra terpercaya distribusi produk berkualitas di Sumatera Utara sejak 2015.</p>
            <div style="margin-top:10px;display:flex;align-items:center;gap:8px;">
                <img src="assets/logo_jt2.jpeg" alt="JT" style="width:32px;height:32px;border-radius:50%;border:1.5px solid var(--gold);background:white;object-fit:contain;">
                <span style="color:rgba(255,255,255,0.4);font-size:11px;">JUMA TIGA SEANTERO © <?= date('Y') ?></span>
            </div>
        </div>
        <div class="footer-col">
            <h4>Tautan</h4>
            <a href="#home">🏠 Beranda</a>
            <a href="#pengumuman">📢 Info Terkini</a>
            <a href="#produk">📦 Produk</a>
            <a href="#tentang">🏢 Tentang Kami</a>
            <a href="login.php">🔐 Login</a>
            <a href="register.php">📝 Daftar</a>
        </div>
        <div class="footer-col">
            <h4>Kontak</h4>
            <p>📍 Jl. Letjen Jamin Ginting Kel No.22, Gung Negeri, Kec. Kabanjahe, Kabupaten Karo, Sumatera Utara 22152</p>
            <p>📞 (061) 123-4567</p>
            <p>✉️ info@jumatigaseantero.co.id</p>
            <p>🕐 Senin–Sabtu, 08.00–17.00 WIB</p>
        </div>
    </div>
    <div class="footer-bottom">
        © <?= date('Y') ?> PT JUMA TIGA SEANTERO. All rights reserved. · Sistem Manajemen Penjualan
    </div>
</div>

<script>
// ── SLIDER ──────────────────────────────────────
const slides     = document.querySelectorAll('.slide');
const track      = document.getElementById('sliderTrack');
const dotsWrap   = document.getElementById('sliderDots');
let current      = 0;
let autoTimer;

// Buat dots
slides.forEach((_, i) => {
    const d = document.createElement('button');
    d.className = 'dot' + (i===0?' active':'');
    d.onclick = () => goTo(i);
    dotsWrap.appendChild(d);
});

function goTo(n) {
    current = (n + slides.length) % slides.length;
    track.style.transform = `translateX(-${current * 100}%)`;
    document.querySelectorAll('.dot').forEach((d,i) => d.classList.toggle('active', i===current));
}
function slideMove(dir) { goTo(current + dir); resetAuto(); }
function resetAuto() {
    clearInterval(autoTimer);
    autoTimer = setInterval(() => goTo(current + 1), 4000);
}
resetAuto();

// ── LIGHTBOX GALERI ─────────────────────────
const galeriLandingData = <?php
    $gld = array_map(function($g) {
        return [
            'img'   => 'uploads/galeri/' . $g['gambar'],
            'judul' => $g['judul'],
            'desc'  => $g['deskripsi'] ?? '',
        ];
    }, $galeriLanding);
    echo json_encode($gld);
?>;

let lbIdx = 0;
function openLb(i) {
    lbIdx = i;
    updateLb();
    document.getElementById('lbWrap').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function updateLb() {
    const d = galeriLandingData[lbIdx];
    if (!d) return;
    document.getElementById('lbImg').src   = d.img;
    document.getElementById('lbTitle').textContent = d.judul;
    document.getElementById('lbDesc').textContent  = d.desc;
}
function lbNav(dir) {
    lbIdx = (lbIdx + dir + galeriLandingData.length) % galeriLandingData.length;
    updateLb();
}
function closeLb() {
    document.getElementById('lbWrap').classList.remove('open');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', e => {
    if (!document.getElementById('lbWrap').classList.contains('open')) return;
    if (e.key === 'Escape')      closeLb();
    if (e.key === 'ArrowLeft')   lbNav(-1);
    if (e.key === 'ArrowRight')  lbNav(1);
});

// Smooth scroll navbar
document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
        const target = document.querySelector(a.getAttribute('href'));
        if (target) {
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});
</script>

<!-- Lightbox Galeri Landing -->
<div class="lb-wrap" id="lbWrap" onclick="closeLb()">
    <button class="lb-close" onclick="closeLb()">✕</button>
    <button class="lb-nav prev" onclick="event.stopPropagation();lbNav(-1)">&#8249;</button>
    <div style="display:flex;flex-direction:column;align-items:center;" onclick="event.stopPropagation()">
        <img id="lbImg" src="" alt="">
        <div class="lb-info">
            <h4 id="lbTitle"></h4>
            <p id="lbDesc"></p>
        </div>
    </div>
    <button class="lb-nav next" onclick="event.stopPropagation();lbNav(1)">&#8250;</button>
</div>

<!-- ═══════════════════════════════════════
     WIDGET CHAT - DIALOG PERCAKAPAN
═══════════════════════════════════════ -->

<!-- Tombol FAB -->
<div id="chatFab" onclick="toggleChat()" style="
    position: fixed;
    bottom: 28px;
    right: 28px;
    z-index: 999999;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 10px;
">
    <!-- Label -->
    <div id="chatFabLabel" style="
        background: #0f1e3c;
        color: white;
        padding: 8px 16px;
        border-radius: 50px;
        font-size: 12px;
        font-weight: 600;
        font-family: 'DM Sans', sans-serif;
        box-shadow: 0 4px 14px rgba(0,0,0,0.25);
        white-space: nowrap;
        pointer-events: none;
    ">💬 Hubungi Kami</div>

    <!-- Tombol bulat -->
    <div style="
        width: 60px;
        height: 60px;
        background: #c9a84c;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 26px;
        box-shadow: 0 6px 20px rgba(201,168,76,0.55);
        position: relative;
        transition: transform 0.2s;
    " onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
        💬
        <div style="
            position: absolute;
            top: -3px; right: -3px;
            width: 20px; height: 20px;
            background: #e53e3e;
            border-radius: 50%;
            color: white;
            font-size: 11px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid white;
            font-family: 'DM Sans', sans-serif;
        ">1</div>
    </div>
</div>

<!-- Dialog Chat -->
<div id="chatDialog" style="
    display: none;
    position: fixed;
    bottom: 100px;
    right: 28px;
    z-index: 999998;
    width: 340px;
    background: white;
    border-radius: 18px;
    box-shadow: 0 12px 48px rgba(0,0,0,0.22);
    font-family: 'DM Sans', sans-serif;
    overflow: hidden;
    animation: chatSlideUp 0.3s cubic-bezier(0.22,0.68,0,1.2);
">
<style>
@keyframes chatSlideUp {
    from { opacity:0; transform: translateY(20px) scale(0.95); }
    to   { opacity:1; transform: translateY(0)    scale(1);    }
}
.chat-bubble-in {
    background: #f1f5f9;
    color: #334155;
    padding: 10px 14px;
    border-radius: 16px 16px 16px 4px;
    font-size: 13px;
    line-height: 1.6;
    max-width: 80%;
    align-self: flex-start;
    animation: chatSlideUp 0.2s ease;
}
.chat-bubble-out {
    background: #0f1e3c;
    color: white;
    padding: 10px 14px;
    border-radius: 16px 16px 4px 16px;
    font-size: 13px;
    line-height: 1.6;
    max-width: 80%;
    align-self: flex-end;
    animation: chatSlideUp 0.2s ease;
}
.chat-bubble-reply {
    background: #f0fdf4;
    color: #15803d;
    border: 1px solid #86efac;
    padding: 10px 14px;
    border-radius: 16px 16px 16px 4px;
    font-size: 13px;
    line-height: 1.6;
    max-width: 82%;
    align-self: flex-start;
    animation: chatSlideUp 0.2s ease;
}
.chat-time {
    font-size: 10px;
    opacity: 0.45;
    margin-top: 3px;
}
.chat-tab-btn {
    flex: 1;
    padding: 9px 6px;
    background: none;
    border: none;
    border-bottom: 2.5px solid transparent;
    font-size: 11px;
    font-weight: 600;
    color: #6b7280;
    cursor: pointer;
    font-family: 'DM Sans', sans-serif;
    transition: all 0.2s;
}
.chat-tab-btn.aktif {
    color: #0f1e3c;
    border-bottom-color: #c9a84c;
    font-weight: 700;
}
</style>

    <!-- Header -->
    <div style="background:#0f1e3c;padding:14px 16px;display:flex;align-items:center;gap:12px;">
        <div style="width:40px;height:40px;border-radius:50%;background:rgba(255,255,255,.12);border:2px solid #c9a84c;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;">🏢</div>
        <div style="flex:1;">
            <div style="color:white;font-weight:700;font-size:13px;margin-bottom:2px;">PT JUMA TIGA SEANTERO</div>
            <div style="color:#e8c97a;font-size:11px;display:flex;align-items:center;gap:5px;">
                <span style="width:7px;height:7px;background:#22c55e;border-radius:50%;display:inline-block;animation:pulse 1.5s ease infinite;"></span>
                Admin Asset siap membantu
            </div>
        </div>
        <button onclick="tutupChat()" style="background:rgba(255,255,255,.1);border:none;color:rgba(255,255,255,.7);width:30px;height:30px;border-radius:50%;cursor:pointer;font-size:16px;display:flex;align-items:center;justify-content:center;">✕</button>
    </div>

    <!-- Tab tipe pesan -->
    <div style="display:flex;background:#f8fafc;border-bottom:1px solid #e8eaf0;">
        <button class="chat-tab-btn aktif" id="tabC1" onclick="gantiTabChat('chat')">💬 Chat</button>
        <button class="chat-tab-btn"       id="tabC2" onclick="gantiTabChat('keluhan')">⚠️ Keluhan</button>
        <button class="chat-tab-btn"       id="tabC3" onclick="gantiTabChat('masukan')">💡 Masukan</button>
    </div>

    <!-- Area percakapan -->
    <div id="chatBubbles" style="
        height: 220px;
        overflow-y: auto;
        padding: 14px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        background: #f9fbff;
        scroll-behavior: smooth;
    ">
        <!-- Pesan sambutan dari admin -->
        <div style="display:flex;align-items:flex-start;gap:8px;">
            <div style="width:28px;height:28px;border-radius:50%;background:#0f1e3c;display:flex;align-items:center;justify-content:center;font-size:12px;flex-shrink:0;">🏢</div>
            <div>
                <div style="font-size:10px;font-weight:700;color:#0f1e3c;margin-bottom:3px;">Admin Asset</div>
                <div class="chat-bubble-in">
                    👋 Halo! Selamat datang di <strong>PT JUMA TIGA SEANTERO</strong>.<br>
                    Ada yang bisa kami bantu? Silakan kirim pesan 😊
                </div>
                <div class="chat-time" style="margin-left:2px;"><?php echo date('H:i'); ?></div>
            </div>
        </div>
    </div>

    <!-- Indikator mengetik (tersembunyi) -->
    <div id="chatTyping" style="display:none;padding:8px 14px;background:#f9fbff;border-top:1px solid #f0f2f7;">
        <div style="display:flex;align-items:center;gap:8px;">
            <div style="width:24px;height:24px;border-radius:50%;background:#0f1e3c;display:flex;align-items:center;justify-content:center;font-size:10px;flex-shrink:0;">🏢</div>
            <div style="background:#e2e8f0;padding:8px 12px;border-radius:12px;display:flex;gap:4px;align-items:center;">
                <span style="width:6px;height:6px;background:#94a3b8;border-radius:50%;animation:dotBounce 1.2s ease infinite;"></span>
                <span style="width:6px;height:6px;background:#94a3b8;border-radius:50%;animation:dotBounce 1.2s ease 0.2s infinite;"></span>
                <span style="width:6px;height:6px;background:#94a3b8;border-radius:50%;animation:dotBounce 1.2s ease 0.4s infinite;"></span>
            </div>
            <span style="font-size:11px;color:#94a3b8;">Admin mengetik...</span>
        </div>
    </div>

    <!-- Form input -->
    <div style="padding:12px 14px;background:white;border-top:1px solid #f0f2f7;">
        <!-- Input nama (hanya muncul pertama kali) -->
        <div id="inputNamaWrap" style="margin-bottom:8px;">
            <input id="inputNamaChat" type="text" placeholder="👤 Nama kamu (wajib diisi)" maxlength="60"
                style="width:100%;padding:9px 13px;border:1.5px solid #e0e4ec;border-radius:10px;font-size:12px;font-family:'DM Sans',sans-serif;outline:none;background:#f9fafb;box-sizing:border-box;transition:border-color .2s;">
        </div>
        <!-- Input pesan -->
        <div style="display:flex;gap:8px;align-items:flex-end;">
            <textarea id="inputPesanChat" placeholder="Ketik pesan di sini..." rows="1"
                style="flex:1;padding:9px 13px;border:1.5px solid #e0e4ec;border-radius:10px;font-size:12px;font-family:'DM Sans',sans-serif;outline:none;background:#f9fafb;resize:none;min-height:38px;max-height:80px;line-height:1.5;transition:border-color .2s;overflow:hidden;"></textarea>
            <button onclick="kirimChatDialog()" id="chatSendBtn"
                style="width:38px;height:38px;background:#0f1e3c;color:white;border:none;border-radius:10px;font-size:17px;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:background .2s;"
                onmouseover="this.style.background='#1e3a6e'" onmouseout="this.style.background='#0f1e3c'">➤</button>
        </div>
        <div style="text-align:center;font-size:10px;color:#cbd5e1;margin-top:6px;">
            Enter = kirim &nbsp;·&nbsp; Shift+Enter = baris baru
        </div>
    </div>
</div>

<style>
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }
@keyframes dotBounce { 0%,80%,100%{transform:translateY(0)} 40%{transform:translateY(-6px)} }
</style>

<script>
// ═══ CHAT WIDGET — PT JUMA TIGA SEANTERO ════════════════
var _chatOpen   = false;
var _chatTipe   = 'chat';
var _sudahNama  = false;
var _namaUser   = '';

// Sembunyikan label FAB otomatis setelah 5 detik
setTimeout(function(){
    var el = document.getElementById('chatFabLabel');
    if (el) {
        el.style.transition = 'opacity .5s';
        el.style.opacity = '0';
        setTimeout(function(){ if (el) el.style.display = 'none'; }, 600);
    }
}, 5000);

// Toggle buka/tutup dialog
function toggleChat() {
    _chatOpen = !_chatOpen;
    var dialog = document.getElementById('chatDialog');
    var fab    = document.getElementById('chatFab');

    if (_chatOpen) {
        // Sembunyikan label
        var lbl = document.getElementById('chatFabLabel');
        if (lbl) lbl.style.display = 'none';

        // Tampilkan dialog dengan animasi
        dialog.style.display = 'block';
        dialog.style.animation = 'none';
        void dialog.offsetWidth; // force reflow
        dialog.style.animation = 'chatSlideUp 0.3s cubic-bezier(0.22,0.68,0,1.2)';

        // Fokus ke input yang tepat
        setTimeout(function(){
            var target = _sudahNama
                ? document.getElementById('inputPesanChat')
                : document.getElementById('inputNamaChat');
            if (target) target.focus();
            scrollBubbles();
        }, 320);
    } else {
        dialog.style.display = 'none';
    }
}

// Alias agar tombol ✕ di dalam dialog tetap berfungsi
function tutupChat() {
    _chatOpen = false;
    var dialog = document.getElementById('chatDialog');
    if (dialog) dialog.style.display = 'none';
}

// Ganti tipe pesan (Chat / Keluhan / Masukan)
function gantiTabChat(tipe) {
    _chatTipe = tipe;
    var tabs = ['tabC1','tabC2','tabC3'];
    var tipes = ['chat','keluhan','masukan'];
    tabs.forEach(function(id, i){
        var el = document.getElementById(id);
        if (el) el.className = 'chat-tab-btn' + (tipes[i] === tipe ? ' aktif' : '');
    });
    var ta = document.getElementById('inputPesanChat');
    if (ta) {
        ta.placeholder = tipe === 'keluhan' ? 'Ceritakan keluhan kamu...'
                       : tipe === 'masukan' ? 'Berikan masukan kamu...'
                       : 'Ketik pesan di sini...';
    }
}

// Scroll area percakapan ke paling bawah
function scrollBubbles() {
    var el = document.getElementById('chatBubbles');
    if (el) el.scrollTop = el.scrollHeight;
}

// Tambah bubble pesan keluar (pengunjung)
function tambahBubbleKeluar(nama, pesan, tipe) {
    var bubbles = document.getElementById('chatBubbles');
    var jam = new Date().toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'});
    var icon = {chat:'💬', keluhan:'⚠️', masukan:'💡'}[tipe] || '💬';

    var wrap = document.createElement('div');
    wrap.style.cssText = 'display:flex;flex-direction:column;align-items:flex-end;gap:2px;animation:chatSlideUp .25s ease both;';
    wrap.innerHTML =
        '<div style="font-size:10px;font-weight:700;color:#64748b;margin-bottom:2px;">' + escH(nama) + '</div>' +
        '<div class="chat-bubble-out">' +
            '<span style="font-size:10px;opacity:.65;display:block;margin-bottom:2px;">' + icon + ' ' + tipe + '</span>' +
            escH(pesan).replace(/\n/g, '<br>') +
        '</div>' +
        '<div class="chat-time">' + jam + '</div>';
    bubbles.appendChild(wrap);
    scrollBubbles();
}

// Tambah bubble pesan masuk (admin)
function tambahBubbleMasuk(html) {
    var bubbles = document.getElementById('chatBubbles');
    var jam = new Date().toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'});

    var wrap = document.createElement('div');
    wrap.style.cssText = 'display:flex;align-items:flex-start;gap:8px;animation:chatSlideUp .25s ease both;';
    wrap.innerHTML =
        '<div style="width:28px;height:28px;border-radius:50%;background:#0f1e3c;display:flex;align-items:center;justify-content:center;font-size:12px;flex-shrink:0;">🏢</div>' +
        '<div>' +
            '<div style="font-size:10px;font-weight:700;color:#0f1e3c;margin-bottom:3px;">Admin Asset</div>' +
            '<div class="chat-bubble-reply">' + html + '</div>' +
            '<div class="chat-time" style="margin-left:2px;">' + jam + '</div>' +
        '</div>';

    // Sisipkan sebelum typing indicator jika ada
    var typing = document.getElementById('chatTyping');
    if (typing && typing.parentNode === bubbles) {
        bubbles.insertBefore(wrap, typing);
    } else {
        bubbles.appendChild(wrap);
    }
    scrollBubbles();
}

// Tampilkan / sembunyikan typing indicator
function tampilTyping()   { var el = document.getElementById('chatTyping'); if (el) { el.style.display = 'block'; scrollBubbles(); } }
function sembunyiTyping() { var el = document.getElementById('chatTyping'); if (el) el.style.display = 'none'; }

// Kirim pesan ke server
function kirimChatDialog() {
    var namaEl  = document.getElementById('inputNamaChat');
    var pesanEl = document.getElementById('inputPesanChat');
    var sendBtn = document.getElementById('chatSendBtn');
    var nama    = namaEl  ? namaEl.value.trim()  : _namaUser;
    var pesan   = pesanEl ? pesanEl.value.trim() : '';

    // Validasi nama
    if (!_sudahNama && !nama) {
        namaEl.style.borderColor = '#ef4444';
        namaEl.placeholder = '⚠️ Nama wajib diisi!';
        namaEl.focus();
        setTimeout(function(){
            namaEl.style.borderColor = '';
            namaEl.placeholder = '👤 Nama kamu (wajib diisi)';
        }, 2500);
        return;
    }

    // Validasi pesan
    if (!pesan) {
        if (pesanEl) { pesanEl.style.borderColor = '#ef4444'; pesanEl.focus(); }
        setTimeout(function(){ if (pesanEl) pesanEl.style.borderColor = ''; }, 2000);
        return;
    }

    // Simpan nama & sembunyikan input nama setelah kirim pertama
    if (!_sudahNama) {
        _namaUser  = nama;
        _sudahNama = true;
        var wrap = document.getElementById('inputNamaWrap');
        if (wrap) {
            wrap.style.transition = 'opacity .3s';
            wrap.style.opacity = '0';
            setTimeout(function(){ if (wrap) wrap.style.display = 'none'; }, 300);
        }
    }

    // Tampilkan bubble langsung (optimistic UI)
    tambahBubbleKeluar(_namaUser, pesan, _chatTipe);
    pesanEl.value = '';
    pesanEl.style.height = 'auto';
    if (sendBtn) sendBtn.style.opacity = '0.5';

    // Kirim ke server
    var fd = new FormData();
    fd.append('nama',  _namaUser);
    fd.append('pesan', pesan);
    fd.append('tipe',  _chatTipe);

    tampilTyping();

    fetch('kirim_chat_publik.php', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            setTimeout(function() {
                sembunyiTyping();
                var balasan = [
                    'Terima kasih <strong>' + escH(_namaUser) + '</strong>! 😊 Pesan kamu sudah kami terima. Tim kami akan segera merespons.',
                    'Halo <strong>' + escH(_namaUser) + '</strong>! Pesan sudah masuk ke tim kami. Kami akan segera menghubungi kamu.',
                    'Siap! Pesan kamu sudah kami catat 👍 Tim Admin Asset akan merespons secepatnya.',
                ];
                var txt = d.ok
                    ? balasan[Math.floor(Math.random() * balasan.length)]
                    : 'Maaf, ada gangguan teknis. Silakan coba lagi 🙏';
                tambahBubbleMasuk(txt);
                if (sendBtn) sendBtn.style.opacity = '1';
            }, 1000 + Math.random() * 800);
        })
        .catch(function() {
            setTimeout(function() {
                sembunyiTyping();
                tambahBubbleMasuk('Maaf, koneksi bermasalah. Silakan coba lagi 🙏');
                if (sendBtn) sendBtn.style.opacity = '1';
            }, 1000);
        });
}

// Escape HTML
function escH(s) {
    return String(s)
        .replace(/&/g,'&amp;')
        .replace(/</g,'&lt;')
        .replace(/>/g,'&gt;');
}

// Setup event listeners saat DOM siap
document.addEventListener('DOMContentLoaded', function() {
    // Auto-resize textarea
    var ta = document.getElementById('inputPesanChat');
    if (ta) {
        ta.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 80) + 'px';
        });
        ta.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                kirimChatDialog();
            }
        });
    }

    // Focus style input nama
    var na = document.getElementById('inputNamaChat');
    if (na) {
        na.addEventListener('focus', function() { this.style.borderColor = '#c9a84c'; });
        na.addEventListener('blur',  function() { this.style.borderColor = ''; });
        na.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                var tp = document.getElementById('inputPesanChat');
                if (tp) tp.focus();
            }
        });
    }
});

// Tutup dialog dengan tombol Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && _chatOpen) tutupChat();
});
</script>

</body>
</html>