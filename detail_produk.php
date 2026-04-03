<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (isset($_SESSION['role'])) {
    $roleMap = ['admin_assets'=>'admin_asset','admin_programs'=>'admin_program'];
    $_SESSION['role'] = $roleMap[$_SESSION['role']] ?? $_SESSION['role'];
}
if (!isset($_SESSION['username'])) { header("Location: login.php"); exit; }

include 'koneksi.php';
$role = $_SESSION['role'];
$id   = (int)($_GET['id'] ?? 0);
if (!$id) { header("Location: produk.php"); exit; }

$stmt = $conn->prepare("SELECT * FROM produk WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$p) { header("Location: produk.php"); exit; }

// Cek kolom tersedia
$cols = [];
$r = $conn->query("SHOW COLUMNS FROM produk");
while ($row = $r->fetch_assoc()) $cols[] = $row['Field'];

// Produk lain (rekomendasi)
$lain = $conn->query("SELECT * FROM produk WHERE id != $id ORDER BY RAND() LIMIT 4")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($p['nama_produk']) ?> — PT JUMA TIGA SEANTERO</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .detail-wrap {
            max-width: 1000px; margin: 30px auto; padding: 0 24px;
            display: grid; grid-template-columns: 380px 1fr; gap: 28px;
            align-items: start;
        }
        /* Gambar produk */
        .produk-img-box {
            background: white; border-radius: var(--radius);
            overflow: hidden; box-shadow: var(--shadow-md);
            position: sticky; top: 100px;
        }
        .produk-img-main {
            width: 100%; height: 320px; object-fit: cover; display: block;
        }
        .produk-img-placeholder {
            width: 100%; height: 320px;
            display: flex; align-items: center; justify-content: center;
            font-size: 80px; background: var(--cream);
        }
        .produk-img-footer {
            padding: 14px 18px;
            border-top: 1px solid #f0f2f7;
            display: flex; gap: 8px; align-items: center;
        }

        /* Info produk */
        .produk-info { display: flex; flex-direction: column; gap: 18px; }

        .badge-kat {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 5px 14px; border-radius: 50px;
            font-size: 12px; font-weight: 700;
            background: var(--cream); color: var(--navy);
            border: 1.5px solid #e0e4ec;
        }
        .produk-nama {
            font-family: 'Playfair Display', serif;
            font-size: 28px; color: var(--navy); line-height: 1.3;
        }
        .produk-desc { font-size: 14px; color: var(--muted); line-height: 1.8; }

        /* Harga box */
        .harga-box {
            background: linear-gradient(135deg, var(--navy), #1e3a6e);
            border-radius: var(--radius); padding: 20px 24px;
            color: white;
        }
        .harga-label { font-size: 11px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; opacity: .6; margin-bottom: 4px; }
        .harga-eceren-val { font-family: 'Playfair Display', serif; font-size: 32px; color: var(--gold); font-weight: 700; }
        .harga-row { display: flex; align-items: center; gap: 12px; margin-top: 14px; padding-top: 14px; border-top: 1px solid rgba(255,255,255,.1); flex-wrap: wrap; }
        .harga-item { text-align: center; }
        .harga-item-val { font-size: 16px; font-weight: 700; color: white; }
        .harga-item-lbl { font-size: 10px; opacity: .55; margin-top: 2px; }
        .harga-divider { width: 1px; height: 36px; background: rgba(255,255,255,.15); }

        /* Spesifikasi */
        .spek-box {
            background: white; border-radius: var(--radius);
            box-shadow: var(--shadow-sm); overflow: hidden;
        }
        .spek-head {
            background: var(--navy); padding: 12px 18px;
            color: white; font-size: 13px; font-weight: 700;
            letter-spacing: 0.5px;
        }
        .spek-table { width: 100%; border-collapse: collapse; }
        .spek-table tr { border-bottom: 1px solid #f0f2f7; }
        .spek-table tr:last-child { border-bottom: none; }
        .spek-table td { padding: 11px 18px; font-size: 13px; }
        .spek-table td:first-child { color: var(--muted); font-weight: 500; width: 40%; background: #fafbff; }
        .spek-table td:last-child { color: var(--navy); font-weight: 600; }

        /* Stok badge */
        .stok-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 5px 14px; border-radius: 50px; font-size: 12px; font-weight: 700;
        }
        .stok-ok    { background: #f0fdf4; color: #16a34a; border: 1px solid #86efac; }
        .stok-habis { background: #fdf0ef; color: #c0392b; border: 1px solid #fca5a5; }
        .stok-minim { background: #fffbeb; color: #d97706; border: 1px solid #fcd34d; }

        /* Action buttons */
        .action-box { display: flex; gap: 10px; flex-wrap: wrap; }
        .btn-beli-now {
            flex: 1; padding: 14px;
            background: var(--gold); color: var(--navy);
            border: none; border-radius: var(--radius-sm);
            font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 700;
            cursor: pointer; transition: all .2s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            text-decoration: none;
        }
        .btn-beli-now:hover { background: var(--gold2); transform: translateY(-2px); box-shadow: 0 6px 16px rgba(201,168,76,0.4); }
        .btn-wa {
            padding: 14px 20px;
            background: #dcfce7; color: #16a34a;
            border: 1.5px solid #86efac; border-radius: var(--radius-sm);
            font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 700;
            cursor: pointer; transition: all .2s; text-decoration: none;
            display: flex; align-items: center; gap: 6px;
        }
        .btn-wa:hover { background: #bbf7d0; }

        /* Rekomendasi */
        .rek-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 16px; }
        .rek-card {
            background: white; border-radius: var(--radius-sm);
            padding: 14px; text-align: center;
            box-shadow: var(--shadow-sm); border-top: 3px solid transparent;
            transition: all .2s; text-decoration: none; color: inherit;
            display: block;
        }
        .rek-card:hover { border-top-color: var(--gold); transform: translateY(-3px); box-shadow: var(--shadow-md); }
        .rek-img { width:100%; height:80px; object-fit:cover; border-radius:6px; margin-bottom:8px; }
        .rek-icon { font-size:28px; margin-bottom:8px; display:block; }
        .rek-nama { font-size:12px; font-weight:600; color:var(--navy); margin-bottom:4px; }
        .rek-harga { font-size:13px; font-weight:700; color:var(--green); }

        @media (max-width: 768px) {
            .detail-wrap { grid-template-columns: 1fr; }
            .produk-img-box { position: static; }
            .rek-grid { grid-template-columns: repeat(2,1fr); }
            .harga-eceren-val { font-size: 24px; }
        }
    </style>
</head>
<body>
<?php $navActive = 'produk'; include 'navbar.php'; ?>
<style>
.back-bar-global{background:var(--white);border-bottom:1px solid #eef0f7;padding:10px 40px;display:flex;align-items:center;gap:16px;box-shadow:0 1px 4px rgba(15,30,60,0.05);}
.back-btn-global{display:inline-flex;align-items:center;gap:7px;color:var(--navy);text-decoration:none;font-size:13px;font-weight:600;padding:6px 16px;border-radius:50px;border:1.5px solid #e0e4ec;background:var(--cream);transition:all .2s;}
.back-btn-global:hover{background:var(--navy);color:white;border-color:var(--navy);}
.breadcrumb-trail{display:flex;align-items:center;gap:6px;font-size:12px;color:var(--muted);}
.breadcrumb-trail a{color:var(--muted);text-decoration:none;}.breadcrumb-trail .sep{color:#ccc;}.breadcrumb-trail .current{color:var(--navy);font-weight:600;}
</style>
<div class="back-bar-global">
    <a href="produk.php" class="back-btn-global">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15,18 9,12 15,6"/></svg>
        ← Kembali ke Produk
    </a>
    <div class="breadcrumb-trail">
        <a href="dashboard.php">Dashboard</a><span class="sep">›</span>
        <a href="produk.php">Produk</a><span class="sep">›</span>
        <span class="current"><?= htmlspecialchars($p['nama_produk']) ?></span>
    </div>
</div>

<div class="detail-wrap">
    <!-- Kiri: Gambar -->
    <div class="produk-img-box" style="animation:fadeUp .4s ease both;">
        <?php
        $uploadDir = 'uploads/produk/';
        $hasImg = !empty($p['gambar']) && file_exists($uploadDir.$p['gambar']);
        $katIcons = ['Cone'=>'🍦','Paket Keluarga'=>'👨‍👩‍👧','Gelas'=>'🥤','Premium'=>'👑','Es Krim'=>'🍨','Minuman'=>'🧃','Makanan'=>'🍱','Snack'=>'🍿','Umum'=>'📦'];
        $ikon = $katIcons[$p['kategori'] ?? 'Umum'] ?? '📦';
        ?>
        <?php if ($hasImg): ?>
            <img src="<?= $uploadDir.htmlspecialchars($p['gambar']) ?>"
                 alt="<?= htmlspecialchars($p['nama_produk']) ?>"
                 class="produk-img-main">
        <?php else: ?>
            <div class="produk-img-placeholder"><?= $ikon ?></div>
        <?php endif; ?>
        <div class="produk-img-footer">
            <span style="font-size:12px;color:var(--muted);">Kode Produk:</span>
            <span style="font-family:monospace;background:var(--cream);padding:3px 10px;border-radius:6px;font-size:12px;font-weight:600;color:var(--navy);">
                PRD-<?= str_pad($p['id'], 4, '0', STR_PAD_LEFT) ?>
            </span>
        </div>
    </div>

    <!-- Kanan: Info -->
    <div class="produk-info" style="animation:fadeUp .4s ease .1s both;">

        <!-- Kategori badge -->
        <div>
            <span class="badge-kat"><?= $ikon ?> <?= htmlspecialchars($p['kategori'] ?? 'Umum') ?></span>
        </div>

        <!-- Nama & Deskripsi -->
        <div>
            <h1 class="produk-nama"><?= htmlspecialchars($p['nama_produk']) ?></h1>
            <?php if (!empty($p['deskripsi'])): ?>
                <p class="produk-desc" style="margin-top:10px;"><?= htmlspecialchars($p['deskripsi']) ?></p>
            <?php endif; ?>
        </div>

        <!-- Harga Box -->
        <div class="harga-box">
            <div class="harga-label">Harga Eceran</div>
            <div class="harga-eceren-val">
                Rp <?= number_format($p['harga_eceren'] ?? $p['harga'], 0, ',', '.') ?>
                <span style="font-size:14px;opacity:.7;">/ pcs</span>
            </div>

            <?php if (!empty($p['harga_per_dus']) && !empty($p['isi_per_dus'])): ?>
            <div class="harga-row">
                <div class="harga-item">
                    <div class="harga-item-val">Rp <?= number_format($p['harga_per_dus'], 0, ',', '.') ?></div>
                    <div class="harga-item-lbl">Harga per Dus</div>
                </div>
                <div class="harga-divider"></div>
                <div class="harga-item">
                    <div class="harga-item-val"><?= $p['isi_per_dus'] ?> pcs</div>
                    <div class="harga-item-lbl">Isi per Dus</div>
                </div>
                <?php if (!empty($p['harga_per_dus']) && !empty($p['isi_per_dus'])): ?>
                <div class="harga-divider"></div>
                <div class="harga-item">
                    <div class="harga-item-val" style="color:var(--gold2);">
                        Rp <?= number_format($p['harga_per_dus'] / $p['isi_per_dus'], 0, ',', '.') ?>
                    </div>
                    <div class="harga-item-lbl">Harga/pcs (dus)</div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Stok & Spesifikasi -->
        <div class="spek-box">
            <div class="spek-head">📋 Detail & Spesifikasi</div>
            <table class="spek-table">
                <?php
                $stok = $p['stok'] ?? null;
                if ($stok !== null):
                    $stokClass = $stok == 0 ? 'stok-habis' : ($stok < 10 ? 'stok-minim' : 'stok-ok');
                    $stokLabel = $stok == 0 ? '❌ Habis' : ($stok < 10 ? "⚠️ Sisa $stok pcs" : "✅ Tersedia ($stok pcs)");
                ?>
                <tr>
                    <td>Stok</td>
                    <td><span class="stok-badge <?= $stokClass ?>"><?= $stokLabel ?></span></td>
                </tr>
                <?php endif; ?>
                <?php if (!empty($p['ml_per_satuan'])): ?>
                <tr><td>Volume / ML</td><td><?= htmlspecialchars($p['ml_per_satuan']) ?></td></tr>
                <?php endif; ?>
                <?php if (!empty($p['berat_satuan'])): ?>
                <tr><td>Berat</td><td><?= htmlspecialchars($p['berat_satuan']) ?></td></tr>
                <?php endif; ?>
                <?php if (!empty($p['isi_per_dus'])): ?>
                <tr><td>Isi per Dus</td><td><?= $p['isi_per_dus'] ?> pcs</td></tr>
                <?php endif; ?>
                <?php if (!empty($p['harga_per_dus'])): ?>
                <tr><td>Harga per Dus</td><td style="color:var(--green);">Rp <?= number_format($p['harga_per_dus'],0,',','.') ?></td></tr>
                <?php endif; ?>
                <tr><td>Kategori</td><td><?= $ikon ?> <?= htmlspecialchars($p['kategori'] ?? 'Umum') ?></td></tr>
                <tr><td>Kode Produk</td><td style="font-family:monospace;">PRD-<?= str_pad($p['id'],4,'0',STR_PAD_LEFT) ?></td></tr>
            </table>
        </div>

        <!-- Tombol Aksi -->
        <?php if ($role === 'pembeli'): ?>
        <div class="action-box">
            <a href="beli.php?id=<?= $p['id'] ?>" class="btn-beli-now">
                🛒 Beli Sekarang
            </a>
            <a href="https://wa.me/6281234567890?text=<?= urlencode('Halo, saya tertarik dengan produk: '.$p['nama_produk'].' (Rp '.number_format($p['harga_eceren']??$p['harga'],0,',','.').')') ?>"
               target="_blank" class="btn-wa">
                💬 Tanya WA
            </a>
        </div>
        <?php elseif (in_array($role, ['penjual','admin_program'])): ?>
        <div class="action-box">
            <a href="edit_produk.php?id=<?= $p['id'] ?>" class="btn-beli-now" style="background:var(--navy);color:white;">
                ✏️ Edit Produk
            </a>
        </div>
        <?php endif; ?>

    </div>
</div>

<!-- Produk Rekomendasi -->
<?php if (!empty($lain)): ?>
<div style="max-width:1000px;margin:0 auto 40px;padding:0 24px;">
    <h2 style="font-family:'Playfair Display',serif;font-size:20px;color:var(--navy);margin-bottom:16px;padding-bottom:10px;border-bottom:3px solid var(--gold);">
        📦 Produk Lainnya
    </h2>
    <div class="rek-grid">
        <?php
        $katIcons = ['Cone'=>'🍦','Paket Keluarga'=>'👨‍👩‍👧','Gelas'=>'🥤','Premium'=>'👑','Es Krim'=>'🍨','Minuman'=>'🧃','Makanan'=>'🍱','Snack'=>'🍿','Umum'=>'📦'];
        foreach ($lain as $l):
            $lIkon = $katIcons[$l['kategori']??'Umum']??'📦';
            $lImg  = 'uploads/produk/'.$l['gambar'];
            $lHasImg = !empty($l['gambar']) && file_exists($lImg);
        ?>
        <a href="detail_produk.php?id=<?= $l['id'] ?>" class="rek-card">
            <?php if ($lHasImg): ?>
                <img src="<?= $lImg ?>" alt="<?= htmlspecialchars($l['nama_produk']) ?>" class="rek-img">
            <?php else: ?>
                <span class="rek-icon"><?= $lIkon ?></span>
            <?php endif; ?>
            <div class="rek-nama"><?= htmlspecialchars(substr($l['nama_produk'],0,30)) ?></div>
            <div class="rek-harga">Rp <?= number_format($l['harga_eceren']??$l['harga'],0,',','.') ?></div>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<p class="footer">© <?= date('Y') ?> PT JUMA TIGA SEANTERO</p>
</body>
</html>