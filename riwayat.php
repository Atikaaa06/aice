<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['username'])) {
    header("Location: login.php"); exit;
}
include 'koneksi.php';

$username = $_SESSION['username'];
$role     = $_SESSION['role'];

// Filter
$filterStatus = trim($_GET['status'] ?? '');
$filterBulan  = trim($_GET['bulan']  ?? '');
$search       = trim($_GET['search'] ?? '');

// ── Query transaksi ──────────────────────────────
$where  = ["t.username = ?"];
$params = [$username];
$types  = "s";

if ($filterStatus !== '') {
    $where[]  = "po.status = ?";
    $params[] = $filterStatus;
    $types   .= "s";
}
if ($filterBulan !== '') {
    $where[]  = "DATE_FORMAT(t.tanggal, '%Y-%m') = ?";
    $params[] = $filterBulan;
    $types   .= "s";
}
if ($search !== '') {
    $where[]  = "p.nama_produk LIKE ?";
    $params[] = "%$search%";
    $types   .= "s";
}

$sql = "
    SELECT t.*, p.nama_produk, p.harga
    FROM transaksi t
    JOIN produk p ON t.id_produk = p.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY t.tanggal DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$transaksis = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ── Query pesanan/order ──────────────────────────
$sqlOrder = "
    SELECT po.*, p.nama_produk
    FROM pesan_order po
    JOIN produk p ON po.id_produk = p.id
    WHERE po.username = ?
    ORDER BY po.created_at DESC
";
$stmtO = $conn->prepare($sqlOrder);
$stmtO->bind_param("s", $username);
$stmtO->execute();
$orders = $stmtO->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtO->close();

// ── Statistik ────────────────────────────────────
$totalBeli    = count($transaksis);
$totalOrder   = count($orders);
$totalBayar   = array_sum(array_column($transaksis, 'total'));
$orderPending = count(array_filter($orders, fn($o) => $o['status'] === 'pending'));

// Daftar bulan untuk filter
$bulanList = $conn->prepare("SELECT DISTINCT DATE_FORMAT(tanggal,'%Y-%m') as bln, DATE_FORMAT(tanggal,'%M %Y') as label FROM transaksi WHERE username=? ORDER BY bln DESC");
$bulanList->bind_param("s", $username);
$bulanList->execute();
$bulanOptions = $bulanList->get_result()->fetch_all(MYSQLI_ASSOC);
$bulanList->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Transaksi — PT Juma Tiga</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Tab sistem */
        .tab-bar {
            display: flex;
            gap: 0;
            border-bottom: 2px solid #e8eaf0;
            margin-bottom: 28px;
            background: var(--white);
            border-radius: var(--radius) var(--radius) 0 0;
            padding: 0 24px;
            box-shadow: var(--shadow-sm);
        }
        .tab-btn {
            padding: 16px 22px;
            background: none; border: none;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px; font-weight: 600;
            color: var(--muted); cursor: pointer;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            transition: color 0.2s, border-color 0.2s;
            display: flex; align-items: center; gap: 8px;
        }
        .tab-btn.active { color: var(--navy); border-bottom-color: var(--gold); }
        .tab-btn:hover  { color: var(--navy); }
        .tab-count {
            background: var(--navy); color: white;
            font-size: 10px; font-weight: 700;
            padding: 2px 7px; border-radius: 50px;
        }
        .tab-btn.active .tab-count { background: var(--gold); color: var(--navy); }
        .tab-panel { display: none; }
        .tab-panel.active { display: block; animation: fadeUp 0.3s ease both; }

        /* Filter bar */
        .filter-bar {
            background: var(--white);
            border-radius: var(--radius);
            padding: 16px 20px;
            box-shadow: var(--shadow-sm);
            display: flex; gap: 10px; flex-wrap: wrap;
            align-items: center; margin-bottom: 20px;
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
        .filter-bar input  { flex: 1; min-width: 160px; }
        .filter-bar input:focus,
        .filter-bar select:focus { border-color: var(--gold); }
        .btn-cari {
            padding: 9px 20px; background: var(--navy); color: white;
            border: none; border-radius: var(--radius-sm);
            font-family: 'DM Sans', sans-serif; font-size: 13px;
            font-weight: 600; cursor: pointer; transition: background 0.2s;
        }
        .btn-cari:hover { background: var(--navy-mid); }

        /* Transaksi card */
        .trx-card {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            margin-bottom: 14px;
            overflow: hidden;
            transition: box-shadow 0.2s, transform 0.2s;
            animation: fadeUp 0.4s ease both;
            border-left: 4px solid var(--gold);
        }
        .trx-card:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }

        .trx-head {
            padding: 16px 20px;
            display: flex; align-items: center;
            justify-content: space-between; gap: 12px;
            border-bottom: 1px solid #f5f5f8;
            flex-wrap: wrap;
        }
        .trx-produk {
            display: flex; align-items: center; gap: 12px;
        }
        .trx-icon {
            width: 44px; height: 44px;
            background: var(--cream);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; flex-shrink: 0;
            border: 1.5px solid #e8eaf0;
        }
        .trx-nama { font-family: 'Playfair Display', serif; font-size: 16px; color: var(--navy); }
        .trx-id   { font-size: 11px; color: var(--muted); margin-top: 2px; }

        .trx-total {
            font-family: 'Playfair Display', serif;
            font-size: 20px; color: var(--success); font-weight: 700;
        }

        .trx-body {
            padding: 14px 20px;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }
        .trx-info-item {}
        .trx-info-label { font-size: 10px; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; color: var(--muted); margin-bottom: 4px; }
        .trx-info-value { font-size: 14px; font-weight: 500; color: var(--text); }

        /* Status badge */
        .status-pill {
            padding: 4px 12px; border-radius: 50px;
            font-size: 11px; font-weight: 600;
        }
        .s-selesai  { background:#f0fdf4; color:#16a34a; border:1px solid #86efac; }
        .s-pending  { background:#fef9ec; color:#92680c; border:1px solid var(--gold); }
        .s-diproses { background:#eff6ff; color:#1d4ed8; border:1px solid #93c5fd; }
        .s-ditolak  { background:#fdf0ef; color:#c0392b; border:1px solid #fca5a5; }

        /* Order card */
        .order-card {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            margin-bottom: 14px;
            overflow: hidden;
            animation: fadeUp 0.4s ease both;
            border-left: 4px solid #6b7280;
        }
        .order-card.pending  { border-left-color: var(--gold); }
        .order-card.diproses { border-left-color: #1d4ed8; }
        .order-card.selesai  { border-left-color: #16a34a; }
        .order-card.ditolak  { border-left-color: #c0392b; }

        .order-head {
            padding: 16px 20px;
            display: flex; align-items: center;
            justify-content: space-between; gap: 12px;
            flex-wrap: wrap;
        }
        .order-body {
            padding: 14px 20px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            border-top: 1px solid #f5f5f8;
        }
        .order-catatan {
            grid-column: 1/-1;
            background: var(--cream);
            border-radius: var(--radius-sm);
            padding: 10px 14px;
            font-size: 13px;
            color: var(--muted);
            border-left: 3px solid #e0e4ec;
        }

        /* Progress tracker */
        .progress-track {
            display: flex;
            align-items: center;
            gap: 0;
            margin: 14px 20px;
        }
        .pt-step {
            display: flex; flex-direction: column; align-items: center;
            flex: 1; position: relative;
        }
        .pt-dot {
            width: 28px; height: 28px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 700;
            border: 2px solid #e0e4ec;
            background: var(--white);
            color: var(--muted);
            z-index: 1; position: relative;
            transition: all 0.3s;
        }
        .pt-dot.done  { background: #16a34a; border-color: #16a34a; color: white; }
        .pt-dot.active{ background: #1d4ed8; border-color: #1d4ed8; color: white; }
        .pt-dot.reject{ background: #c0392b; border-color: #c0392b; color: white; }
        .pt-label { font-size: 10px; color: var(--muted); margin-top: 4px; text-align: center; font-weight: 600; }
        .pt-label.done   { color: #16a34a; }
        .pt-label.active { color: #1d4ed8; }
        .pt-line {
            flex: 1; height: 2px; background: #e0e4ec;
            margin-top: -20px; z-index: 0;
        }
        .pt-line.done { background: #16a34a; }

        /* Empty state */
        .empty-state {
            text-align: center; padding: 60px 20px;
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            color: var(--muted);
        }
        .empty-state .ei { font-size: 48px; margin-bottom: 12px; }
        .empty-state p   { font-size: 14px; margin-bottom: 20px; }

        @media (max-width: 640px) {
            .trx-body  { grid-template-columns: 1fr 1fr; }
            .order-body{ grid-template-columns: 1fr 1fr; }
            .tab-btn   { padding: 12px 14px; font-size: 13px; }
        }
    </style>
</head>
<body>

<?php $navActive = 'riwayat'; include 'navbar.php'; ?>
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
    <div class="breadcrumb-trail"><a href="dashboard.php">Dashboard</a><span class="sep">›</span><span class="current">Riwayat Transaksi</span></div>
</div>


<div class="page">
    <div class="page-header" style="animation:fadeUp .4s ease both;margin-bottom:24px;">
        <h1>📋 Riwayat Transaksi</h1>
        <p>Halo <strong><?= htmlspecialchars($username) ?></strong> — semua riwayat pembelian dan pesanan kamu ada di sini</p>
    </div>

    <?php if (isset($_GET['sukses'])): ?>
    <div class="alert alert-success" style="margin-bottom:20px;animation:fadeUp .4s ease both;">
        🎉 Transaksi berhasil! Total pembayaran: <strong>Rp <?= htmlspecialchars($_GET['total'] ?? '') ?></strong>
    </div>
    <?php endif; ?>

    <!-- Statistik -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px;">
        <div class="dash-card" style="animation-delay:.05s">
            <div class="dash-card-label">Total Pembelian</div>
            <div class="dash-card-value"><?= $totalBeli ?></div>
        </div>
        <div class="dash-card" style="animation-delay:.1s;border-left-color:#16a34a;">
            <div class="dash-card-label">Total Pengeluaran</div>
            <div class="dash-card-value" style="font-size:18px;">Rp <?= number_format($totalBayar,0,',','.') ?></div>
        </div>
        <div class="dash-card" style="animation-delay:.15s;border-left-color:#1d4ed8;">
            <div class="dash-card-label">Total Pesanan</div>
            <div class="dash-card-value"><?= $totalOrder ?></div>
        </div>
        <div class="dash-card" style="animation-delay:.2s;border-left-color:var(--gold);">
            <div class="dash-card-label">Pesanan Pending</div>
            <div class="dash-card-value"><?= $orderPending ?></div>
        </div>
    </div>

    <!-- Tab -->
    <div class="tab-bar">
        <button class="tab-btn active" onclick="switchTab('beli',this)">
            🛒 Pembelian <span class="tab-count"><?= $totalBeli ?></span>
        </button>
        <button class="tab-btn" onclick="switchTab('order',this)">
            📋 Pesanan/Order <span class="tab-count"><?= $totalOrder ?></span>
        </button>
    </div>

    <!-- ── TAB PEMBELIAN ── -->
    <div class="tab-panel active" id="tab-beli">

        <!-- Filter -->
        <form method="GET" class="filter-bar">
            <input type="hidden" name="tab" value="beli">
            <input type="text" name="search" placeholder="🔍 Cari nama produk..." value="<?= htmlspecialchars($search) ?>">
            <select name="bulan">
                <option value="">Semua Bulan</option>
                <?php foreach ($bulanOptions as $b): ?>
                    <option value="<?= $b['bln'] ?>" <?= $filterBulan===$b['bln']?'selected':'' ?>>
                        <?= $b['label'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-cari">Cari</button>
            <a href="riwayat.php" style="font-size:13px;color:var(--muted);text-decoration:none;padding:9px 4px;">Reset</a>
        </form>

        <?php if (empty($transaksis)): ?>
            <div class="empty-state">
                <div class="ei">🛒</div>
                <p>Belum ada riwayat pembelian<?= $search ? ' yang cocok' : '' ?>.</p>
                <a href="produk.php" class="btn btn-primary" style="width:auto;padding:11px 28px;display:inline-flex;">Mulai Belanja →</a>
            </div>
        <?php else: ?>
            <?php
            $icons = ['📦','🎁','🛍️','💼','🏷️','🎀'];
            foreach ($transaksis as $i => $t):
                $delay = ($i * 0.04) . 's';
                $tgl   = date('d M Y', strtotime($t['tanggal']));
                $jam   = date('H:i', strtotime($t['tanggal']));
            ?>
            <div class="trx-card" style="animation-delay:<?= $delay ?>">
                <div class="trx-head">
                    <div class="trx-produk">
                        <div class="trx-icon"><?= $icons[$i % count($icons)] ?></div>
                        <div>
                            <div class="trx-nama"><?= htmlspecialchars($t['nama_produk']) ?></div>
                            <div class="trx-id">Transaksi #<?= $t['id'] ?></div>
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <div class="trx-total">Rp <?= number_format($t['total'],0,',','.') ?></div>
                        <span class="status-pill s-selesai">✅ Selesai</span>
                    </div>
                </div>
                <div class="trx-body">
                    <div class="trx-info-item">
                        <div class="trx-info-label">Tanggal</div>
                        <div class="trx-info-value">📅 <?= $tgl ?></div>
                    </div>
                    <div class="trx-info-item">
                        <div class="trx-info-label">Jam</div>
                        <div class="trx-info-value">🕐 <?= $jam ?></div>
                    </div>
                    <div class="trx-info-item">
                        <div class="trx-info-label">Jumlah</div>
                        <div class="trx-info-value">📦 <?= $t['jumlah'] ?> unit</div>
                    </div>
                    <div class="trx-info-item">
                        <div class="trx-info-label">Harga Satuan</div>
                        <div class="trx-info-value">Rp <?= number_format($t['harga'],0,',','.') ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- ── TAB PESANAN/ORDER ── -->
    <div class="tab-panel" id="tab-order">

        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <div class="ei">📋</div>
                <p>Belum ada riwayat pesanan.</p>
                <a href="produk.php" class="btn btn-primary" style="width:auto;padding:11px 28px;display:inline-flex;">Lihat Produk →</a>
            </div>
        <?php else: ?>
            <?php
            $statusLabel = [
                'pending'  => ['label'=>'⏳ Menunggu Konfirmasi', 'class'=>'s-pending'],
                'diproses' => ['label'=>'⚙️ Sedang Diproses',     'class'=>'s-diproses'],
                'selesai'  => ['label'=>'✅ Selesai',              'class'=>'s-selesai'],
                'ditolak'  => ['label'=>'❌ Ditolak',              'class'=>'s-ditolak'],
            ];
            // Step index per status
            $stepIndex = ['pending'=>0,'diproses'=>1,'selesai'=>2,'ditolak'=>-1];

            foreach ($orders as $i => $o):
                $delay  = ($i * 0.04) . 's';
                $sl     = $statusLabel[$o['status']] ?? $statusLabel['pending'];
                $sidx   = $stepIndex[$o['status']] ?? 0;
                $tgl    = date('d M Y, H:i', strtotime($o['created_at']));
            ?>
            <div class="order-card <?= $o['status'] ?>" style="animation-delay:<?= $delay ?>">
                <div class="order-head">
                    <div>
                        <div style="font-family:'Playfair Display',serif;font-size:16px;color:var(--navy);margin-bottom:3px;">
                            📦 <?= htmlspecialchars($o['nama_produk']) ?>
                        </div>
                        <div style="font-size:12px;color:var(--muted);">Pesanan #<?= $o['id'] ?> · <?= $tgl ?></div>
                    </div>
                    <span class="status-pill <?= $sl['class'] ?>"><?= $sl['label'] ?></span>
                </div>

                <!-- Progress tracker -->
                <?php if ($o['status'] !== 'ditolak'): ?>
                <div class="progress-track">
                    <?php
                    $steps = [
                        ['icon'=>'📋','label'=>'Dikirim'],
                        ['icon'=>'⚙️','label'=>'Diproses'],
                        ['icon'=>'✅','label'=>'Selesai'],
                    ];
                    foreach ($steps as $si => $step):
                        $isDone   = $si < $sidx;
                        $isActive = $si === $sidx;
                        $dotClass = $isDone ? 'done' : ($isActive ? 'active' : '');
                        $lblClass = $isDone ? 'done' : ($isActive ? 'active' : '');
                    ?>
                    <?php if ($si > 0): ?>
                        <div class="pt-line <?= $isDone || $isActive ? 'done' : '' ?>"></div>
                    <?php endif; ?>
                    <div class="pt-step">
                        <div class="pt-dot <?= $dotClass ?>"><?= $isDone ? '✓' : ($isActive ? $step['icon'] : $si+1) ?></div>
                        <div class="pt-label <?= $lblClass ?>"><?= $step['label'] ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div style="padding:10px 20px;">
                    <div style="background:#fdf0ef;border-left:3px solid #c0392b;border-radius:6px;padding:10px 14px;font-size:13px;color:#c0392b;">
                        ❌ Pesanan ini ditolak. Silakan hubungi penjual untuk informasi lebih lanjut.
                    </div>
                </div>
                <?php endif; ?>

                <div class="order-body">
                    <div class="trx-info-item">
                        <div class="trx-info-label">Jumlah</div>
                        <div class="trx-info-value">📦 <?= $o['jumlah'] ?> unit</div>
                    </div>
                    <div class="trx-info-item">
                        <div class="trx-info-label">Tanggal Pesan</div>
                        <div class="trx-info-value">📅 <?= date('d M Y', strtotime($o['created_at'])) ?></div>
                    </div>
                    <div class="trx-info-item">
                        <div class="trx-info-label">Status</div>
                        <div class="trx-info-value"><?= ucfirst($o['status']) ?></div>
                    </div>
                    <?php if ($o['catatan']): ?>
                    <div class="order-catatan">
                        📝 <strong>Catatan:</strong> <?= htmlspecialchars($o['catatan']) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<p class="footer">© <?= date('Y') ?> PT Juma Tiga</p>

<script>
function switchTab(name, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    btn.classList.add('active');
}
// Buka tab order jika dari URL
const urlTab = new URLSearchParams(window.location.search).get('tab');
if (urlTab === 'order') {
    document.querySelector('[onclick*="order"]').click();
}
</script>
</body>
</html>