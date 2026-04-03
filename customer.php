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
if (!in_array($_SESSION['role'], ['penjual','admin_program','admin_asset','sales'])) { header("Location: dashboard.php"); exit; }

$role     = $_SESSION['role'];
$username = $_SESSION['username'];

// Hanya sales, admin_asset, dan penjual yang boleh akses
if (!in_array($role, ['penjual','sales','admin_asset'])) {
    header("Location: dashboard.php"); exit;
}

include 'koneksi.php';

// Filter & search
$search      = trim($_GET['search']   ?? '');
$filterKota  = trim($_GET['kota']     ?? '');
$filterStatus= trim($_GET['status']   ?? '');

$where  = ["1=1"];
$params = [];
$types  = '';

if ($search !== '') {
    $where[]  = "(nama_toko LIKE ? OR id_mesin LIKE ? OR nomor_hp LIKE ?)";
    $like     = "%$search%";
    $params   = array_merge($params, [$like, $like, $like]);
    $types   .= 'sss';
}
if ($filterKota !== '') {
    $where[]  = "kota = ?";
    $params[] = $filterKota;
    $types   .= 's';
}
$filterWilayah = trim($_GET['wilayah_filter'] ?? '');
if ($filterWilayah !== '') {
    $where[]  = "wilayah = ?";
    $params[] = $filterWilayah;
    $types   .= 's';
}
if ($filterStatus !== '') {
    $where[]  = "status = ?";
    $params[] = $filterStatus;
    $types   .= 's';
}

$sql = "SELECT * FROM customer WHERE " . implode(' AND ', $where) . " ORDER BY nama_toko ASC";
$stmt = $conn->prepare($sql);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$customers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Statistik
$totalCustomer = $conn->query("SELECT COUNT(*) as n FROM customer")->fetch_assoc()['n'] ?? 0;
$totalAktif    = $conn->query("SELECT COUNT(*) as n FROM customer WHERE status='aktif'")->fetch_assoc()['n'] ?? 0;
$totalNonaktif = $totalCustomer - $totalAktif;

// Daftar kota untuk filter
$kotaList = $conn->query("SELECT DISTINCT kota FROM customer WHERE kota IS NOT NULL AND kota != '' ORDER BY kota ASC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Customer — PT JUMA TIGA SEANTERO</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Filter bar */
        .filter-bar {
            background: var(--white); border-radius: var(--radius);
            padding: 16px 20px; box-shadow: var(--shadow-sm);
            display: flex; gap: 10px; flex-wrap: wrap;
            align-items: center; margin-bottom: 24px;
        }
        .filter-bar input, .filter-bar select {
            padding: 9px 14px; border: 1.5px solid #e0e4ec;
            border-radius: var(--radius-sm); font-size: 13px;
            font-family: 'DM Sans', sans-serif; background: var(--cream);
            outline: none; transition: border-color 0.2s;
        }
        .filter-bar input  { flex: 1; min-width: 200px; }
        .filter-bar input:focus,
        .filter-bar select:focus { border-color: var(--gold); }
        .btn-cari {
            padding: 9px 20px; background: var(--navy); color: white;
            border: none; border-radius: var(--radius-sm);
            font-family: 'DM Sans', sans-serif; font-size: 13px;
            font-weight: 600; cursor: pointer; transition: background 0.2s;
        }
        .btn-cari:hover { background: var(--navy-mid); }

        /* Table */
        .table-wrap { border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow-md); }
        table { width: 100%; border-collapse: collapse; background: var(--white); }
        thead tr { background: var(--navy); }
        thead th {
            padding: 13px 16px; color: white; font-size: 11px;
            font-weight: 600; letter-spacing: 1px;
            text-transform: uppercase; text-align: left;
        }
        tbody tr { border-bottom: 1px solid #f0f2f7; transition: background 0.15s; }
        tbody tr:hover { background: #fafbff; }
        tbody td { padding: 13px 16px; font-size: 13px; }

        /* Status pill */
        .pill-aktif    { background:#f0fdf4; color:#16a34a; border:1px solid #86efac; padding:3px 10px; border-radius:50px; font-size:11px; font-weight:600; }
        .pill-nonaktif { background:#f9fafb; color:#6b7280; border:1px solid #d1d5db; padding:3px 10px; border-radius:50px; font-size:11px; font-weight:600; }

        /* Role badge */
        .role-info {
            background: rgba(201,168,76,0.12);
            border: 1px solid rgba(201,168,76,0.3);
            border-radius: var(--radius-sm);
            padding: 10px 16px;
            font-size: 12px; color: var(--gold-light);
            margin-bottom: 20px;
            display: flex; align-items: center; gap: 8px;
        }

        /* Aksi tombol */
        .btn-edit-c {
            padding: 5px 12px; background: #eff6ff; color: #1d4ed8;
            border: 1px solid #93c5fd; border-radius: 6px;
            font-size: 11px; font-weight: 600; cursor: pointer;
            font-family: 'DM Sans', sans-serif;
            text-decoration: none; transition: all 0.2s;
            display:inline-flex;align-items:center;gap:3px;
        }
        .btn-edit-c:hover { background:#dbeafe; }
        .btn-del-c {
            padding: 5px 12px; background: #fdf0ef; color: #c0392b;
            border: 1px solid #fca5a5; border-radius: 6px;
            font-size: 11px; font-weight: 600; cursor: pointer;
            font-family: 'DM Sans', sans-serif;
            transition: all 0.2s;
            display:inline-flex;align-items:center;gap:3px;
        }
        .btn-del-c:hover { background:#fee2e2; }
        .btn-print-c {
            padding: 5px 12px; background: #f0fdf4; color: #16a34a;
            border: 1px solid #86efac; border-radius: 6px;
            font-size: 11px; font-weight: 600; cursor: pointer;
            font-family: 'DM Sans', sans-serif;
            text-decoration: none; transition: all 0.2s;
            display:inline-flex;align-items:center;gap:3px;
        }
        .btn-print-c:hover { background:#dcfce7; }

        /* Print styles */
        @media print {
            .back-bar-global, .filter-bar, .btn-edit-c, .btn-del-c,
            .btn-print-c, form[method="POST"], .kat-chips,
            .modal-overlay, .dash-card, .role-info,
            nav, .footer, button { display: none !important; }
            body { background: white !important; }
            .table-wrap { box-shadow: none !important; }
            .page { padding: 0 !important; }
            .print-header { display: block !important; }
        }
        .print-header {
            display: none;
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #0f1e3c;
        }
        .print-header h2 { font-size: 18px; color: #0f1e3c; margin-bottom: 4px; }
        .print-header p  { font-size: 12px; color: #666; }

        /* Modal */
        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(15,30,60,0.55); z-index:200; align-items:center; justify-content:center; padding:20px; }
        .modal-overlay.open { display:flex; animation:fadeIn .2s ease; }
        @keyframes fadeIn{from{opacity:0}to{opacity:1}}
        .modal { background:var(--white); border-radius:var(--radius); width:100%; max-width:560px; max-height:90vh; overflow-y:auto; box-shadow:var(--shadow-lg); animation:slideIn .3s cubic-bezier(.22,.68,0,1.2) both; }
        .modal-header { background:var(--navy); padding:18px 24px; display:flex; justify-content:space-between; align-items:center; }
        .modal-header h3 { font-family:'Playfair Display',serif; color:var(--white); font-size:17px; }
        .modal-close { background:none; border:none; color:rgba(255,255,255,.6); font-size:22px; cursor:pointer; }
        .modal-close:hover { color:white; }
        .modal-body { padding:24px; }
        .modal-footer { padding:14px 24px; border-top:1px solid #f0f2f7; display:flex; justify-content:flex-end; gap:10px; }
        .btn-cancel { padding:10px 20px; background:transparent; color:var(--muted); border:1.5px solid #e0e4ec; border-radius:var(--radius-sm); font-family:'DM Sans',sans-serif; font-size:14px; cursor:pointer; }
        .btn-save   { padding:10px 24px; background:var(--navy); color:white; border:none; border-radius:var(--radius-sm); font-family:'DM Sans',sans-serif; font-size:14px; font-weight:600; cursor:pointer; }

        .form-grid2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
        .form-grid2 .full { grid-column:1/-1; }

        /* Detail view (untuk sales) */
        .detail-row {
            display:flex; align-items:flex-start; gap:12px;
            padding:10px 0; border-bottom:1px solid #f5f5f8;
        }
        .detail-row:last-child { border-bottom:none; }
        .detail-icon { font-size:18px; width:28px; flex-shrink:0; }
        .detail-label { font-size:11px; font-weight:600; letter-spacing:1px; text-transform:uppercase; color:var(--muted); margin-bottom:3px; }
        .detail-val   { font-size:14px; color:var(--text); font-weight:500; }

        @media (max-width:640px) { .form-grid2 { grid-template-columns:1fr; } }
        .dd-btn { width:100%;padding:11px 16px;background:none;border:none;border-bottom:1px solid #f0f2f7;text-align:left;font-size:13px;cursor:pointer;font-family:inherit;color:#0f1e3c;display:block; }
        .dd-btn:hover { background:#f9fafb; }
        .dd-btn:last-child { border-bottom:none; }

        /* PRINT */
        .btn-print { display:inline-flex;align-items:center;gap:7px;padding:9px 20px;background:#f0fdf4;color:#16a34a;border:1.5px solid #86efac;border-radius:var(--radius-sm);font-family:"DM Sans",sans-serif;font-size:13px;font-weight:600;cursor:pointer;transition:all .2s; }
        .btn-print:hover { background:#dcfce7; }
        @media print {
            .no-print, nav, .back-bar-global, .filter-bar, .role-info, .modal-overlay, .page-header { display:none !important; }
            body { background:white !important; }
            #printArea { display:block !important; }
            .table-wrap { box-shadow:none !important; border:1px solid #ccc; }
            .print-only { display:block !important; }
        }
        .print-only { display:none; }
        #printArea {}

        /* ── PRINT STYLES ─────────────────── */
        @media print {
            .back-bar-global, .navbar, .filter-bar,
            .role-info, .dash-card, .btn-print,
            .modal-overlay, .galeri-footer,
            .btn-edit-c, form[method="POST"],
            .page-header button, .btn-cari { display: none !important; }
            body { background: white !important; font-size: 12px; }
            .page { padding: 0 !important; }
            table { font-size: 11px; }
            thead tr { background: #0f1e3c !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .pill-aktif, .pill-nonaktif { border: 1px solid #ccc !important; }
            .print-header { display: block !important; }
            .maps-mini-btn { display: none !important; }
        }
        .print-header { display: none; }
        .btn-print {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 10px 20px; border-radius: var(--radius-sm);
            background: #f0fdf4; color: #16a34a;
            border: 1.5px solid #86efac;
            font-size: 13px; font-weight: 600;
            cursor: pointer; font-family: 'DM Sans', sans-serif;
            transition: all .2s;
        }
        .btn-print:hover { background: #dcfce7; }

        /* Maps */
        .maps-wrap {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            border: 1.5px solid #e0e4ec;
            margin-top: 10px;
        }
        .maps-wrap iframe { display:block; width:100%; height:220px; border:none; }
        .maps-btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 6px 14px; border-radius: 50px;
            font-size: 12px; font-weight: 600;
            text-decoration: none; transition: all .2s;
        }
        .maps-btn-gmaps { background:#e8f5e9; color:#2e7d32; border:1px solid #a5d6a7; }
        .maps-btn-gmaps:hover { background:#c8e6c9; }
        .maps-btn-cari   { background:#eff6ff; color:#1d4ed8; border:1px solid #93c5fd; cursor:pointer; }
        .maps-btn-cari:hover { background:#dbeafe; }

        /* Kolom Maps di tabel */
        .td-maps { font-size:12px; }
        .maps-mini-btn {
            display:inline-flex;align-items:center;gap:4px;
            padding:4px 10px;border-radius:50px;
            background:#e8f5e9;color:#2e7d32;
            font-size:11px;font-weight:600;
            text-decoration:none;border:1px solid #a5d6a7;
            transition:opacity .2s;white-space:nowrap;
        }
        .maps-mini-btn:hover{opacity:.75;}
    </style>
</head>
<body>

<?php $navActive = 'customer'; include 'navbar.php'; ?>

<style>
.back-bar-global { background:var(--white); border-bottom:1px solid #eef0f7; padding:10px 40px; display:flex; align-items:center; gap:16px; box-shadow:0 1px 4px rgba(15,30,60,0.05); }
.back-btn-global { display:inline-flex; align-items:center; gap:7px; color:var(--navy); text-decoration:none; font-size:13px; font-weight:600; padding:6px 16px; border-radius:50px; border:1.5px solid #e0e4ec; background:var(--cream); transition:all .2s; }
.back-btn-global:hover { background:var(--navy); color:white; border-color:var(--navy); }
.breadcrumb-trail { display:flex; align-items:center; gap:6px; font-size:12px; color:var(--muted); }
.breadcrumb-trail a { color:var(--muted); text-decoration:none; }
.breadcrumb-trail a:hover { color:var(--navy); }
.breadcrumb-trail .sep { color:#ccc; }
.breadcrumb-trail .current { color:var(--navy); font-weight:600; }
</style>
<div class="back-bar-global">
    <a href="dashboard.php" class="back-btn-global">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15,18 9,12 15,6"/></svg>
        ← Kembali ke Dashboard
    </a>
    <div class="breadcrumb-trail">
        <a href="dashboard.php">Dashboard</a>
        <span class="sep">›</span>
        <span class="current">Data Customer</span>
    </div>
</div>

<div class="page">
    <!-- Header -->
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;animation:fadeUp .4s ease both;flex-wrap:wrap;gap:12px;">
        <div class="page-header" style="margin-bottom:0;">
            <h1>🏪 Data Customer</h1>
            <p>
                <?php if ($role === 'sales'): ?>
                    Mode <strong>Lihat Saja</strong> — kamu bisa melihat semua data toko
                <?php elseif ($role === 'admin_asset'): ?>
                    Mode <strong>Edit</strong> — kamu bisa mengedit data toko
                <?php else: ?>
                    Kelola semua data customer
                <?php endif; ?>
            </p>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:6px;align-items:center;">
            <?php if ($role === 'admin_asset'): ?>
                <button class="btn btn-primary" style="width:auto;padding:11px 22px;"
                    onclick="openTambah()">+ Tambah Customer</button>
            <?php endif; ?>
            <!-- Tombol Print Dropdown -->
            <div style="position:relative;">
                <button onclick="toggleDropdownPrint(event)"
                        style="padding:11px 20px;background:#f0fdf4;color:#16a34a;border:1.5px solid #86efac;border-radius:var(--radius-sm);font-family:'DM Sans',sans-serif;font-size:13px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:8px;">
                    🖨️ Print Data ▾
                </button>
                <div id="dropdownPrint" style="display:none;position:absolute;top:calc(100% + 6px);right:0;background:white;border-radius:10px;box-shadow:0 8px 32px rgba(0,0,0,.18);border:1px solid #e0e4ec;min-width:240px;z-index:200;overflow:hidden;">
                    <div style="padding:10px 14px;background:#0f1e3c;color:white;font-size:11px;font-weight:700;letter-spacing:1px;">
                        🖨️ PILIH DATA YANG DICETAK
                    </div>
                    <button onclick="bukaPrintModal('semua')" class="dd-btn">👥 Semua Customer</button>
                    <button onclick="bukaPrintModal('Wilayah 1')" class="dd-btn" style="color:#1d4ed8;">📍 Wilayah 1 — Kabanjahe</button>
                    <button onclick="bukaPrintModal('Wilayah 2')" class="dd-btn" style="color:#16a34a;">📍 Wilayah 2 — Lipat Kajang</button>
                    <button onclick="bukaPrintModal('Wilayah 3')" class="dd-btn" style="color:#9333ea;">📍 Wilayah 3 — Sidikalang</button>
                    <button onclick="bukaPrintModal('Wilayah 4')" class="dd-btn" style="color:#c2410c;">📍 Wilayah 4 — Kotacane</button>
                    <button onclick="bukaPrintModal('Wilayah 5')" class="dd-btn" style="color:#c2410c;">📍 Wilayah 5 — Silangit</button> 
                    <button onclick="bukaPrintModal('Wilayah 6')" class="dd-btn" style="color:#c2410c;">📍 Wilayah 6 — Paluta</button>
                    <button onclick="bukaPrintModal('Wilayah 7')" class="dd-btn" style="color:#c2410c;">📍 Wilayah 7 — Simelu</button>
                    <button onclick="bukaPrintModal('Wilayah 8')" class="dd-btn" style="color:#c2410c;">📍 Wilayah 8 — Aceh Selatan</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Info role -->
    <div class="role-info">
        <?php if ($role === 'sales'): ?>
            👁️ Kamu login sebagai <strong>Sales</strong> — hanya dapat melihat data customer.
        <?php elseif ($role === 'admin_program'): ?>
            👁️ Kamu login sebagai <strong>Admin Program</strong> — hanya dapat melihat data customer.
        <?php elseif ($role === 'admin_asset'): ?>
            ✏️ Kamu login sebagai <strong>Admin Asset</strong> — dapat menambah &amp; mengelola data customer.
        <?php else: ?>
            ⚙️ Kamu login sebagai <strong>Penjual</strong> — akses penuh ke semua data customer.
        <?php endif; ?>
    </div>

    <!-- Statistik -->
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px;">
        <div class="dash-card" style="animation-delay:.05s">
            <div class="dash-card-label">Total Customer</div>
            <div class="dash-card-value"><?= $totalCustomer ?></div>
        </div>
        <div class="dash-card" style="animation-delay:.1s;border-left-color:#16a34a;">
            <div class="dash-card-label">Aktif</div>
            <div class="dash-card-value"><?= $totalAktif ?></div>
        </div>
        <div class="dash-card" style="animation-delay:.15s;border-left-color:#6b7280;">
            <div class="dash-card-label">Non-aktif</div>
            <div class="dash-card-value"><?= $totalNonaktif ?></div>
        </div>
    </div>

    <!-- Filter -->
    <form method="GET" class="filter-bar">
        <input type="text" name="search" placeholder="🔍 Cari nama toko, ID mesin, no HP..."
               value="<?= htmlspecialchars($search) ?>">
        <select name="wilayah_filter">
            <option value="">Semua Wilayah</option>
            <option value="Wilayah 1" <?= ($_GET['wilayah_filter']??'')==='Wilayah 1'?'selected':'' ?>>📍 Wilayah 1 — Kabanjahe</option>
            <option value="Wilayah 2" <?= ($_GET['wilayah_filter']??'')==='Wilayah 2'?'selected':'' ?>>📍 Wilayah 2 — Lipat Kajang </option>
            <option value="Wilayah 3" <?= ($_GET['wilayah_filter']??'')==='Wilayah 3'?'selected':'' ?>>📍 Wilayah 3 — Sidikalang</option>
            <option value="Wilayah 4" <?= ($_GET['wilayah_filter']??'')==='Wilayah 4'?'selected':'' ?>>📍 Wilayah 4 — Kotacane</option>
            <option value="Wilayah 4" <?= ($_GET['wilayah_filter']??'')==='Wilayah 5'?'selected':'' ?>>📍 Wilayah 5 — Silangit</option>
            <option value="Wilayah 4" <?= ($_GET['wilayah_filter']??'')==='Wilayah 6'?'selected':'' ?>>📍 Wilayah 6 — Paluta</option>
             <option value="Wilayah 4" <?= ($_GET['wilayah_filter']??'')==='Wilayah 7'?'selected':'' ?>>📍 Wilayah 7 — Simelu</option>
              <option value="Wilayah 4" <?= ($_GET['wilayah_filter']??'')==='Wilayah 8'?'selected':'' ?>>📍 Wilayah 8 — Aceh Silangit</option>
        </select>
        <select name="kota">
            <option value="">Semua Kota</option>
            <?php foreach ($kotaList as $k): ?>
                <option value="<?= htmlspecialchars($k['kota']) ?>" <?= $filterKota===$k['kota']?'selected':'' ?>>
                    <?= htmlspecialchars($k['kota']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="status">
            <option value="">Semua Status</option>
            <option value="aktif"    <?= $filterStatus==='aktif'?'selected':'' ?>>● Aktif</option>
            <option value="nonaktif" <?= $filterStatus==='nonaktif'?'selected':'' ?>>— Non-aktif</option>
        </select>
        <button type="submit" class="btn-cari">Cari</button>
        <?php if ($search || $filterKota || $filterStatus): ?>
            <a href="customer.php" style="font-size:13px;color:var(--muted);text-decoration:none;padding:9px 4px;">Reset</a>
        <?php endif; ?>
    </form>

    <!-- Print Header (hanya muncul saat print) -->
    <div class="print-header" id="printHeader">
        <div style="text-align:center;margin-bottom:20px;border-bottom:2px solid #0f1e3c;padding-bottom:16px;">
            <h2 style="font-size:18px;color:#0f1e3c;margin:0 0 4px;">PT JUMA TIGA SEANTERO</h2>
            <p style="font-size:12px;color:#666;margin:0;">Data Customer — Dicetak: <?= date('d F Y H:i') ?></p>
            <?php if ($filterWilayah): ?>
                <p style="font-size:12px;color:#666;margin:4px 0 0;">Wilayah: <?= htmlspecialchars($filterWilayah) ?></p>
            <?php endif; ?>
            <p style="font-size:12px;color:#666;margin:4px 0 0;">Total: <?= count($customers) ?> data customer</p>
        </div>
    </div>

    <!-- Tabel -->
    <div class="table-wrap" style="animation:fadeUp .5s ease .2s both;" id="tablePrint">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Toko</th>
                    <th>ID Mesin</th>
                    <th>No HP</th>
                    <th>Lokasi</th>
                    <th>Kota</th>
                    <th>Wilayah</th>
                    <th>Status</th>
                    <th>Maps</th>
                    <?php if (in_array($role, ['penjual','admin_asset'])): ?>
                        <th>Aksi</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                    <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:40px;">
                        <?= $search || $filterKota || $filterStatus ? 'Tidak ada data yang cocok' : 'Belum ada data customer' ?>
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($customers as $i => $row): ?>
                    <tr>
                        <td style="color:var(--muted);"><?= $i+1 ?></td>
                        <td>
                            <strong><?= htmlspecialchars($row['nama_toko']) ?></strong>
                            <?php if ($row['catatan']): ?>
                                <div style="font-size:11px;color:var(--muted);margin-top:2px;">📝 <?= htmlspecialchars(substr($row['catatan'],0,40)) ?>...</div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($row['id_mesin']): ?>
                                <span style="font-family:monospace;background:var(--cream);padding:3px 8px;border-radius:4px;font-size:12px;">
                                    <?= htmlspecialchars($row['id_mesin']) ?>
                                </span>
                            <?php else: ?>
                                <span style="color:#ccc;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($row['nomor_hp']): ?>
                                📱 <?= htmlspecialchars($row['nomor_hp']) ?>
                            <?php else: ?>
                                <span style="color:#ccc;">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="max-width:180px;font-size:12px;color:var(--muted);">
                            <?= $row['lokasi'] ? htmlspecialchars(substr($row['lokasi'],0,50)) . (strlen($row['lokasi'])>50?'...':'') : '<span style="color:#ccc;">—</span>' ?>
                        </td>
                        <td><?= $row['kota'] ? htmlspecialchars($row['kota']) : '<span style="color:#ccc;">—</span>' ?></td>
                        <td>
                            <?php
                            $wlabel = [
                                'Wilayah 1'=>['bg'=>'#eff6ff','color'=>'#1d4ed8','icon'=>'📍'],
                                'Wilayah 2'=>['bg'=>'#f0fdf4','color'=>'#16a34a','icon'=>'📍'],
                                'Wilayah 3'=>['bg'=>'#fdf4ff','color'=>'#9333ea','icon'=>'📍'],
                                'Wilayah 4'=>['bg'=>'#fff7ed','color'=>'#c2410c','icon'=>'📍'],
                            ];
                            $wil = $row['wilayah'] ?? '';
                            $wcfg = $wlabel[$wil] ?? null;
                            ?>
                            <?php if ($wcfg): ?>
                                <span style="background:<?= $wcfg['bg'] ?>;color:<?= $wcfg['color'] ?>;padding:3px 10px;border-radius:50px;font-size:11px;font-weight:700;white-space:nowrap;">
                                    <?= $wcfg['icon'] ?> <?= $wil ?>
                                </span>
                            <?php else: ?>
                                <span style="color:#ccc;font-size:11px;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="pill-<?= $row['status'] ?>">
                                <?= $row['status'] === 'aktif' ? '● Aktif' : '— Non-aktif' ?>
                            </span>
                        </td>
                        <td class="td-maps">
                            <?php
                            $linkMaps   = $row['link_maps'] ?? '';
                            $alamatMaps = trim(($row['lokasi']??'') . ' ' . ($row['kota']??'') . ' ' . ($row['provinsi']??''));
                            if ($linkMaps):
                            ?>
                                <a href="<?= htmlspecialchars($linkMaps) ?>" target="_blank" class="maps-mini-btn">
                                    📍 Buka Maps
                                </a>
                            <?php elseif ($alamatMaps): ?>
                                <?php $mapsUrl = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($alamatMaps); ?>
                                <a href="<?= $mapsUrl ?>" target="_blank" class="maps-mini-btn" style="background:#fff3e0;color:#e65100;border-color:#ffcc80;">
                                    🔍 Cari Maps
                                </a>
                            <?php else: ?>
                                <span style="color:#ccc;font-size:11px;">—</span>
                            <?php endif; ?>
                        </td>
                        <?php if (in_array($role, ['penjual','admin_asset'])): ?>
                        <td style="white-space:nowrap;">
                            <div style="display:flex;gap:5px;align-items:center;">
                                <!-- Edit -->
                                <button class="btn-edit-c"
                                   onclick="openEdit(<?= htmlspecialchars(json_encode($row), ENT_QUOTES) ?>)">
                                    ✏️ Edit
                                </button>
                                <!-- Hapus -->
                                <button class="btn-del-c"
                                    onclick="hapusCustomer(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['nama_toko'])) ?>')">
                                    🗑️ Hapus
                                </button>
                                <!-- Print 1 data -->
                                <button class="btn-print-c"
                                    onclick="printSatu(<?= htmlspecialchars(json_encode($row), ENT_QUOTES) ?>)">
                                    🖨️ Print
                                </button>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<p class="footer">© <?= date('Y') ?> PT JUMA TIGA SEANTERO</p>

<!-- Data customer untuk JS print -->
<script>
var _allCustomers = <?php
    $printAll = $conn->query("SELECT * FROM customer ORDER BY wilayah ASC, nama_toko ASC");
    $printArr = [];
    while ($pr = $printAll->fetch_assoc()) $printArr[] = $pr;
    echo json_encode($printArr, JSON_UNESCAPED_UNICODE);
?>;
</script>

<!-- Modal Print Keseluruhan / Per Wilayah -->
<div id="modalPrintAll" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:9999;align-items:center;justify-content:center;padding:20px;">
    <div style="background:white;border-radius:16px;width:100%;max-width:750px;max-height:92vh;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.35);display:flex;flex-direction:column;">
        <div style="background:#0f1e3c;padding:18px 24px;display:flex;justify-content:space-between;align-items:center;flex-shrink:0;">
            <div>
                <h3 id="paTitle" style="font-family:'Playfair Display',serif;color:white;font-size:17px;margin:0 0 3px;"></h3>
                <p  id="paSubtitle" style="color:rgba(255,255,255,.5);font-size:12px;margin:0;"></p>
            </div>
            <button onclick="tutupPrintAll()" style="background:rgba(255,255,255,.1);border:none;color:white;width:32px;height:32px;border-radius:50%;cursor:pointer;font-size:16px;">✕</button>
        </div>
        <div id="paPreview" style="overflow-y:auto;flex:1;padding:0;"></div>
        <div style="padding:14px 24px;border-top:1px solid #f0f2f7;display:flex;justify-content:space-between;align-items:center;flex-shrink:0;">
            <span id="paCount" style="font-size:13px;color:#6b7280;"></span>
            <div style="display:flex;gap:10px;">
                <button onclick="tutupPrintAll()" style="padding:10px 20px;background:transparent;color:#6b7280;border:1.5px solid #e0e4ec;border-radius:8px;font-family:'DM Sans',sans-serif;font-size:14px;cursor:pointer;">Tutup</button>
                <button onclick="eksekusiPrint()" style="padding:10px 24px;background:#16a34a;color:white;border:none;border-radius:8px;font-family:'DM Sans',sans-serif;font-size:14px;font-weight:600;cursor:pointer;">🖨️ Cetak Sekarang</button>
            </div>
        </div>
    </div>
</div>

<!-- Form Hapus (hidden) -->
<form id="formHapusCust" method="POST" action="hapus_customer.php" style="display:none;">
    <input type="hidden" name="id" id="idHapusCust">
</form>

<!-- Modal Print 1 Data -->
<div id="modalPrint1"
     onclick="if(event.target===this)this.style.display='none'"
     style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;
            background:rgba(0,0,0,0.65);z-index:99999;
            align-items:center;justify-content:center;padding:20px;">
    <div style="background:white;border-radius:16px;width:100%;max-width:500px;
                overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.35);"
         onclick="event.stopPropagation()">
        <div style="background:#0f1e3c;padding:16px 22px;display:flex;justify-content:space-between;align-items:center;">
            <h3 style="font-family:'Playfair Display',serif;color:white;font-size:16px;margin:0;">🖨️ Detail Customer</h3>
            <button onclick="document.getElementById('modalPrint1').style.display='none'"
                    style="background:none;border:none;color:rgba(255,255,255,.7);font-size:22px;cursor:pointer;">✕</button>
        </div>
        <div id="isiPrint1" style="padding:20px;max-height:70vh;overflow-y:auto;"></div>
        <div style="padding:14px 22px;border-top:1px solid #f0f2f7;display:flex;gap:10px;justify-content:flex-end;">
            <button onclick="document.getElementById('modalPrint1').style.display='none'"
                    style="padding:9px 20px;background:transparent;color:#6b7280;border:1.5px solid #e0e4ec;border-radius:8px;font-family:inherit;font-size:13px;cursor:pointer;">
                Tutup
            </button>
            <button onclick="cetakSatu()"
                    style="padding:9px 22px;background:#0f1e3c;color:white;border:none;border-radius:8px;font-family:inherit;font-size:13px;font-weight:600;cursor:pointer;">
                🖨️ Cetak
            </button>
        </div>
    </div>
</div>

<!-- Modal Edit/Tambah -->
<div class="modal-overlay" id="modalCustomer" onclick="if(event.target===this)closeModal()">
    <div class="modal">
        <div class="modal-header" style="background:var(--navy);">
            <h3 id="modalTitle">Edit Data Customer</h3>
            <button class="modal-close" onclick="closeModal()">✕</button>
        </div>
        <form method="POST" action="simpan_customer.php">
            <input type="hidden" name="id" id="inputId">
            <div class="modal-body">
                <div class="form-grid2">
                    <div class="field full">
                        <label>Nama Toko <span style="color:red">*</span></label>
                        <input type="text" name="nama_toko" id="inputNamaToko"
                               placeholder="Contoh: Toko Maju Jaya" required>
                    </div>
                    <div class="field">
                        <label>ID Nomor Mesin</label>
                        <input type="text" name="id_mesin" id="inputIdMesin"
                               placeholder="Contoh: MSN-001">
                    </div>
                    <div class="field">
                        <label>Nomor HP</label>
                        <input type="text" name="nomor_hp" id="inputNomorHp"
                               placeholder="Contoh: 08123456789">
                    </div>
                    <div class="field full">
                        <label>Lokasi / Alamat</label>
                        <div style="display:flex;gap:8px;">
                            <input type="text" name="lokasi" id="inputLokasi"
                                   placeholder="Contoh: Jl. Sudirman No. 10"
                                   style="flex:1;" oninput="updateMapsPreview()">
                            <button type="button" class="maps-btn maps-btn-cari" onclick="cariDiMaps()">🔍 Cari</button>
                        </div>
                    </div>
                    <div class="field">
                        <label>Kota</label>
                        <input type="text" name="kota" id="inputKota"
                               placeholder="Contoh: Medan" oninput="updateMapsPreview()">
                    </div>
                    <div class="field">
                        <label>Provinsi</label>
                        <input type="text" name="provinsi" id="inputProvinsi"
                               placeholder="Contoh: Sumatera Utara" oninput="updateMapsPreview()">
                    </div>
                    <div class="field full">
                        <label>🗺️ Wilayah</label>
                        <select name="wilayah" id="inputWilayah">
                            <option value="">— Pilih Wilayah —</option>
                            <option value="Wilayah 1">📍 Wilayah 1 — Kabanjahe</option>
                            <option value="Wilayah 2">📍 Wilayah 2 — Lipat Kajang</option>
                            <option value="Wilayah 3">📍 Wilayah 3 — Sidikalang</option>
                            <option value="Wilayah 4">📍 Wilayah 4 — Kotacane</option>
                            <option value="Wilayah 1">📍 Wilayah 1 — Silangit</option>
                            <option value="Wilayah 2">📍 Wilayah 2 — Paluta</option>
                            <option value="Wilayah 3">📍 Wilayah 3 — Simelu</option>
                            <option value="Wilayah 4">📍 Wilayah 4 — Aceh Selatan</option>
                        </select>
                    </div>
                    <div class="field full">
                        <label>📍 Link Google Maps <span style="font-weight:300;font-size:11px;color:var(--muted);">(opsional)</span></label>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <input type="text" name="link_maps" id="inputLinkMaps"
                                   placeholder="https://maps.app.goo.gl/..."
                                   style="flex:1;" oninput="previewLinkMaps(this.value)">
                            <button type="button" onclick="bukaLinkMaps()"
                                    style="padding:8px 14px;background:#e8f5e9;color:#2e7d32;border:1px solid #a5d6a7;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;font-family:inherit;">
                                🗺️ Buka
                            </button>
                        </div>
                        <div id="linkMapsPreview" style="display:none;margin-top:10px;border-radius:10px;overflow:hidden;border:1.5px solid #e0e4ec;">
                            <iframe id="linkMapsIframe" src="" width="100%" height="200" style="border:none;display:block;" allowfullscreen loading="lazy"></iframe>
                        </div>
                    </div>
                    <div class="field">
                        <label>Status</label>
                        <select name="status" id="inputStatus">
                            <option value="aktif">● Aktif</option>
                            <option value="nonaktif">— Non-aktif</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Catatan</label>
                        <input type="text" name="catatan" id="inputCatatan"
                               placeholder="Catatan tambahan (opsional)">
                    </div>
                    <!-- Preview Maps dari Alamat -->
                    <div class="field full" id="mapsPreviewWrap" style="display:none;">
                        <label style="display:flex;align-items:center;justify-content:space-between;">
                            <span>📍 Preview Lokasi</span>
                            <a id="mapsOpenLink" href="#" target="_blank" class="maps-btn maps-btn-gmaps" style="font-size:11px;">🗺️ Buka Maps</a>
                        </label>
                        <div class="maps-wrap">
                            <iframe id="mapsIframe" src="" allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn-save" id="btnSave">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Print Keseluruhan -->
<div id="modalPrintAll" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:500;align-items:center;justify-content:center;padding:20px;">
    <div style="background:white;border-radius:16px;width:100%;max-width:700px;max-height:90vh;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.3);display:flex;flex-direction:column;">
        <!-- Header -->
        <div style="background:var(--navy);padding:18px 24px;display:flex;justify-content:space-between;align-items:center;flex-shrink:0;">
            <div>
                <h3 style="font-family:'Playfair Display',serif;color:white;font-size:17px;margin:0 0 3px;" id="printAllTitle">Print Data Customer</h3>
                <p style="color:rgba(255,255,255,.5);font-size:12px;margin:0;" id="printAllSubtitle"></p>
            </div>
            <button onclick="tutupPrintAll()" style="background:rgba(255,255,255,.1);border:none;color:white;width:32px;height:32px;border-radius:50%;cursor:pointer;font-size:16px;">✕</button>
        </div>
        <!-- Preview tabel -->
        <div style="overflow-y:auto;flex:1;padding:20px;" id="previewPrintAll"></div>
        <!-- Footer -->
        <div style="padding:14px 24px;border-top:1px solid #f0f2f7;display:flex;justify-content:space-between;align-items:center;flex-shrink:0;">
            <span id="printAllCount" style="font-size:13px;color:var(--muted);"></span>
            <div style="display:flex;gap:10px;">
                <button onclick="tutupPrintAll()" style="padding:10px 20px;background:transparent;color:var(--muted);border:1.5px solid #e0e4ec;border-radius:var(--radius-sm);font-family:'DM Sans',sans-serif;font-size:14px;cursor:pointer;">Tutup</button>
                <button onclick="eksekusiPrintAll()" style="padding:10px 24px;background:#16a34a;color:white;border:none;border-radius:var(--radius-sm);font-family:'DM Sans',sans-serif;font-size:14px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:8px;">🖨️ Cetak Sekarang</button>
            </div>
        </div>
    </div>
</div>

<script>
// ── MODAL EDIT/TAMBAH ─────────────────────────────
function openTambah() {
    document.getElementById('modalTitle').textContent = 'Tambah Customer Baru';
    document.getElementById('btnSave').textContent    = 'Simpan';
    document.getElementById('inputId').value       = '';
    document.getElementById('inputNamaToko').value = '';
    document.getElementById('inputIdMesin').value  = '';
    document.getElementById('inputNomorHp').value  = '';
    document.getElementById('inputLokasi').value   = '';
    document.getElementById('inputKota').value     = '';
    document.getElementById('inputProvinsi').value = '';
    document.getElementById('inputWilayah').value  = '';
    document.getElementById('inputLinkMaps').value = '';
    document.getElementById('inputStatus').value   = 'aktif';
    document.getElementById('inputCatatan').value  = '';
    document.getElementById('linkMapsPreview').style.display = 'none';
    document.getElementById('mapsPreviewWrap').style.display = 'none';
    document.getElementById('modalCustomer').classList.add('open');
}

function openEdit(data) {
    document.getElementById('modalTitle').textContent = 'Edit Data Customer';
    document.getElementById('btnSave').textContent    = 'Update';
    document.getElementById('inputId').value       = data.id;
    document.getElementById('inputNamaToko').value = data.nama_toko    || '';
    document.getElementById('inputIdMesin').value  = data.id_mesin     || '';
    document.getElementById('inputNomorHp').value  = data.nomor_hp     || '';
    document.getElementById('inputLokasi').value   = data.lokasi       || '';
    document.getElementById('inputKota').value     = data.kota         || '';
    document.getElementById('inputProvinsi').value = data.provinsi     || '';
    document.getElementById('inputWilayah').value  = data.wilayah      || '';
    document.getElementById('inputLinkMaps').value = data.link_maps    || '';
    document.getElementById('inputStatus').value   = data.status       || 'aktif';
    document.getElementById('inputCatatan').value  = data.catatan      || '';
    if (data.link_maps) previewLinkMaps(data.link_maps);
    else document.getElementById('linkMapsPreview').style.display = 'none';
    setTimeout(updateMapsPreview, 300);
    document.getElementById('modalCustomer').classList.add('open');
}

function closeModal() { document.getElementById('modalCustomer').classList.remove('open'); }
document.addEventListener('keydown', function(e) {
    if(e.key==='Escape'){ closeModal(); tutupPrintAll(); document.getElementById('dropdownPrint').style.display='none'; }
});

// ── PRINT FILTER WILAYAH ─────────────────────────
var _printWilayah = 'semua';
var _wlFull = {'semua':'Semua Customer','Wilayah 1':'Wilayah 1 — Medan Kota','Wilayah 2':'Wilayah 2 — Medan Utara','Wilayah 3':'Wilayah 3 — Binjai & Langkat','Wilayah 4':'Wilayah 4 — Deli Serdang'};

function toggleDropdownPrint(e) {
    e.stopPropagation();
    var d = document.getElementById('dropdownPrint');
    d.style.display = d.style.display === 'none' ? 'block' : 'none';
}
document.addEventListener('click', function(){ document.getElementById('dropdownPrint').style.display='none'; });

function bukaPrintModal(wil) {
    document.getElementById('dropdownPrint').style.display = 'none';
    _printWilayah = wil;
    var data = wil === 'semua' ? _allCustomers : _allCustomers.filter(function(r){ return r.wilayah === wil; });
    document.getElementById('paTitle').textContent    = '🖨️ Print Data Customer';
    document.getElementById('paSubtitle').textContent = _wlFull[wil] || wil;
    document.getElementById('paCount').textContent    = 'Total: ' + data.length + ' customer';

    var html = '<table style="width:100%;border-collapse:collapse;font-size:12px;">' +
        '<thead><tr style="background:#0f1e3c;color:white;position:sticky;top:0;">' +
        '<th style="padding:10px 12px;text-align:left;font-size:11px;">No</th>' +
        '<th style="padding:10px 12px;text-align:left;font-size:11px;">Nama Toko</th>' +
        '<th style="padding:10px 12px;text-align:left;font-size:11px;">ID Mesin</th>' +
        '<th style="padding:10px 12px;text-align:left;font-size:11px;">No HP</th>' +
        '<th style="padding:10px 12px;text-align:left;font-size:11px;">Kota</th>' +
        '<th style="padding:10px 12px;text-align:left;font-size:11px;">Wilayah</th>' +
        '<th style="padding:10px 12px;text-align:left;font-size:11px;">Status</th>' +
        '</tr></thead><tbody>';

    if (data.length === 0) {
        html += '<tr><td colspan="7" style="text-align:center;padding:40px;color:#9ca3af;">Tidak ada data customer di wilayah ini</td></tr>';
    } else {
        data.forEach(function(r, i) {
            var bg = i%2===0?'white':'#f9fafb';
            var stBg = r.status==='aktif'?'#f0fdf4':'#f9fafb';
            var stCl = r.status==='aktif'?'#16a34a':'#6b7280';
            var stLb = r.status==='aktif'?'● Aktif':'— Nonaktif';
            html += '<tr style="background:'+bg+';border-bottom:1px solid #f0f2f7;">' +
                '<td style="padding:9px 12px;color:#9ca3af;">'+(i+1)+'</td>' +
                '<td style="padding:9px 12px;font-weight:600;color:#0f1e3c;">'+escH(r.nama_toko||'')+'</td>' +
                '<td style="padding:9px 12px;font-family:monospace;font-size:11px;">'+escH(r.id_mesin||'—')+'</td>' +
                '<td style="padding:9px 12px;">'+escH(r.nomor_hp||'—')+'</td>' +
                '<td style="padding:9px 12px;">'+escH(r.kota||'—')+'</td>' +
                '<td style="padding:9px 12px;font-size:11px;font-weight:600;">'+escH(r.wilayah||'—')+'</td>' +
                '<td style="padding:9px 12px;"><span style="padding:3px 10px;border-radius:50px;font-size:10px;font-weight:700;background:'+stBg+';color:'+stCl+';">'+stLb+'</span></td>' +
                '</tr>';
        });
    }
    html += '</tbody></table>';
    document.getElementById('paPreview').innerHTML = html;
    document.getElementById('modalPrintAll').style.display = 'flex';
}

function tutupPrintAll() { document.getElementById('modalPrintAll').style.display = 'none'; }

function eksekusiPrint() {
    var data = _printWilayah==='semua' ? _allCustomers : _allCustomers.filter(function(r){ return r.wilayah===_printWilayah; });
    var label   = _wlFull[_printWilayah]||_printWilayah;
    var tanggal = new Date().toLocaleDateString('id-ID',{day:'2-digit',month:'long',year:'numeric'});

    var rows = '';
    data.forEach(function(r,i){
        var bg = i%2===0?'#ffffff':'#f9fafb';
        rows += '<tr style="background:'+bg+';">' +
            '<td style="padding:7px 10px;border-bottom:1px solid #eee;color:#888;">'+(i+1)+'</td>' +
            '<td style="padding:7px 10px;border-bottom:1px solid #eee;font-weight:600;">'+escH(r.nama_toko||'')+'</td>' +
            '<td style="padding:7px 10px;border-bottom:1px solid #eee;font-family:monospace;font-size:11px;">'+escH(r.id_mesin||'—')+'</td>' +
            '<td style="padding:7px 10px;border-bottom:1px solid #eee;">'+escH(r.nomor_hp||'—')+'</td>' +
            '<td style="padding:7px 10px;border-bottom:1px solid #eee;">'+escH(r.lokasi||'—')+'</td>' +
            '<td style="padding:7px 10px;border-bottom:1px solid #eee;">'+escH(r.kota||'—')+'</td>' +
            '<td style="padding:7px 10px;border-bottom:1px solid #eee;font-weight:600;">'+escH(r.wilayah||'—')+'</td>' +
            '<td style="padding:7px 10px;border-bottom:1px solid #eee;"><span style="padding:2px 8px;border-radius:50px;font-size:10px;font-weight:700;background:'+(r.status==='aktif'?'#e8f5e9':'#f5f5f5')+';color:'+(r.status==='aktif'?'#2e7d32':'#757575')+';">'+(r.status==='aktif'?'Aktif':'Nonaktif')+'</span></td>' +
            '</tr>';
    });

    var win = window.open('','_blank','width=960,height=700');
    win.document.write('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Data Customer</title>' +
    '<style>' +
    '*{box-sizing:border-box;}body{font-family:Arial,sans-serif;margin:0;padding:24px;color:#1a1a2e;font-size:12px;}' +
    '.kop{display:flex;align-items:center;gap:16px;padding-bottom:14px;border-bottom:3px solid #0f1e3c;margin-bottom:16px;}' +
    '.kop h1{font-size:18px;margin:0 0 3px;color:#0f1e3c;}.kop p{font-size:11px;color:#888;margin:2px 0;}' +
    '.subh{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;}' +
    '.subh h2{font-size:14px;color:#0f1e3c;margin:0;}.subh span{font-size:11px;color:#888;}' +
    'table{width:100%;border-collapse:collapse;}' +
    'thead tr{background:#0f1e3c;color:white;}thead th{padding:9px 10px;text-align:left;font-size:11px;}' +
    '.ft{margin-top:16px;text-align:center;font-size:10px;color:#aaa;border-top:1px solid #eee;padding-top:10px;}' +
    '@media print{@page{size:A4 landscape;}body{padding:16px;}}' +
    '</style></head><body>' +
    '<div class="kop"><div style="width:56px;height:56px;border-radius:50%;background:#0f1e3c;border:2px solid #c9a84c;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;">🏢</div>' +
    '<div><h1>PT JUMA TIGA SEANTERO</h1><p>Sistem Manajemen Penjualan — Data Customer</p></div></div>' +
    '<div class="subh"><h2>📊 '+label+'</h2><span>Dicetak: '+tanggal+' &nbsp;·&nbsp; Total: '+data.length+' customer</span></div>' +
    '<table><thead><tr><th>No</th><th>Nama Toko</th><th>ID Mesin</th><th>No HP</th><th>Lokasi</th><th>Kota</th><th>Wilayah</th><th>Status</th></tr></thead>' +
    '<tbody>'+rows+'</tbody></table>' +
    '<div class="ft">PT JUMA TIGA SEANTERO &nbsp;·&nbsp; Data Customer &nbsp;·&nbsp; '+label+'</div>' +
    '<script>window.onload=function(){window.print();};<\/script>' +
    '</body></html>');
    win.document.close();
}

// ── HAPUS CUSTOMER ────────────────────────────────
function hapusCustomer(id, nama) {
    if (!confirm('Hapus customer "' + nama + '"? Data tidak bisa dikembalikan.')) return;
    document.getElementById('idHapusCust').value = id;
    document.getElementById('formHapusCust').submit();
}

// ── PRINT SATU DATA ───────────────────────────────
var _dataPrint = null;
function printSatu(data) {
    _dataPrint = data;
    var wl = {
        'Wilayah 1': '📍 Wilayah 1 — Medan Kota',
        'Wilayah 2': '📍 Wilayah 2 — Medan Utara',
        'Wilayah 3': '📍 Wilayah 3 — Binjai & Langkat',
        'Wilayah 4': '📍 Wilayah 4 — Deli Serdang',
    };
    var rows = [
        ['Nama Toko',   data.nama_toko  || '—'],
        ['ID Mesin',    data.id_mesin   || '—'],
        ['No HP',       data.nomor_hp   || '—'],
        ['Lokasi',      data.lokasi     || '—'],
        ['Kota',        data.kota       || '—'],
        ['Provinsi',    data.provinsi   || '—'],
        ['Wilayah',     wl[data.wilayah] || data.wilayah || '—'],
        ['Status',      data.status === 'aktif' ? '● Aktif' : '— Non-aktif'],
        ['Link Maps',   data.link_maps ? '<a href="'+escH(data.link_maps)+'" target="_blank" style="color:#1d4ed8;">Buka Maps 🗺️</a>' : '—'],
    ];
    var html = '<table style="width:100%;border-collapse:collapse;font-size:13px;">';
    rows.forEach(function(r){
        html += '<tr style="border-bottom:1px solid #f0f2f7;">' +
            '<td style="padding:9px 12px;color:#6b7280;font-weight:500;width:38%;background:#fafbff;font-size:12px;">'+r[0]+'</td>' +
            '<td style="padding:9px 12px;color:#0f1e3c;font-weight:600;">'+r[1]+'</td></tr>';
    });
    html += '</table>';
    document.getElementById('isiPrint1').innerHTML = html;
    document.getElementById('modalPrint1').style.display = 'flex';
}

// ── PRINT SEMUA CUSTOMER ─────────────────────────
function printSemuaCustomer() {
    var wl = {
        'Wilayah 1':'Wilayah 1 — Medan Kota',
        'Wilayah 2':'Wilayah 2 — Medan Utara',
        'Wilayah 3':'Wilayah 3 — Binjai & Langkat',
        'Wilayah 4':'Wilayah 4 — Deli Serdang',
    };

    // Ambil semua baris dari tabel
    var rows = document.querySelectorAll('#tablePrint tbody tr');
    var tblHtml = '';
    var no = 1;
    rows.forEach(function(tr) {
        var tds = tr.querySelectorAll('td');
        if (!tds.length) return;
        tblHtml += '<tr>' +
            '<td style="text-align:center;color:#888;">' + no++ + '</td>' +
            '<td>' + (tds[1] ? tds[1].innerText.trim() : '—') + '</td>' +
            '<td>' + (tds[2] ? tds[2].innerText.trim() : '—') + '</td>' +
            '<td>' + (tds[3] ? tds[3].innerText.trim() : '—') + '</td>' +
            '<td>' + (tds[4] ? tds[4].innerText.trim() : '—') + '</td>' +
            '<td>' + (tds[5] ? tds[5].innerText.trim() : '—') + '</td>' +
            '<td>' + (tds[6] ? tds[6].innerText.trim() : '—') + '</td>' +
            '<td style="text-align:center;">' + (tds[7] ? tds[7].innerText.trim() : '—') + '</td>' +
        '</tr>';
    });

    var now = new Date();
    var tgl = now.toLocaleDateString('id-ID',{day:'2-digit',month:'long',year:'numeric'});
    var jam = now.toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'});

    var win = window.open('','_blank','width=1100,height=800');
    win.document.write(`<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Data Customer — PT JUMA TIGA SEANTERO</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, sans-serif; font-size: 12px; color: #1a1a2e; background: white; }
.page { padding: 24px 30px; }

/* Header */
.hd { display: flex; align-items: center; gap: 16px; margin-bottom: 6px; }
.hd-logo { width: 56px; height: 56px; border-radius: 50%; border: 2px solid #c9a84c; object-fit: contain; }
.hd-title h1 { font-size: 18px; color: #0f1e3c; }
.hd-title p  { font-size: 11px; color: #888; margin-top: 2px; }
.hd-right { margin-left: auto; text-align: right; font-size: 11px; color: #666; }
.hd-right strong { display: block; font-size: 13px; color: #0f1e3c; }

/* Garis pembatas */
.divider { border: none; border-top: 2px solid #0f1e3c; margin: 10px 0 18px; }
.divider2 { border: none; border-top: 1px solid #e0e4ec; margin: 6px 0 14px; }

/* Judul section */
.section-title {
    font-size: 13px; font-weight: 700; color: #0f1e3c;
    background: #f1f5f9; padding: 7px 14px;
    border-left: 4px solid #c9a84c;
    margin-bottom: 12px;
    display: flex; align-items: center; gap: 6px;
}

/* Tabel */
table { width: 100%; border-collapse: collapse; font-size: 11px; }
thead tr { background: #0f1e3c; }
thead th {
    padding: 9px 10px; color: white;
    font-size: 10px; font-weight: 700;
    letter-spacing: 0.8px; text-transform: uppercase;
    text-align: left;
}
thead th:first-child { text-align: center; width: 36px; }
thead th:last-child  { text-align: center; }
tbody tr { border-bottom: 1px solid #f0f2f7; }
tbody tr:nth-child(even) { background: #fafbff; }
tbody td { padding: 8px 10px; vertical-align: middle; }
tbody td:first-child { text-align: center; color: #999; font-size: 10px; }
tbody td:last-child  { text-align: center; }

/* Status badge */
.s-aktif    { background: #f0fdf4; color: #16a34a; padding: 2px 8px; border-radius: 50px; font-size: 10px; font-weight: 700; }
.s-nonaktif { background: #f9fafb; color: #6b7280; padding: 2px 8px; border-radius: 50px; font-size: 10px; font-weight: 700; }

/* Wilayah badge */
.w1 { background: #eff6ff; color: #1d4ed8; }
.w2 { background: #f0fdf4; color: #16a34a; }
.w3 { background: #fdf4ff; color: #9333ea; }
.w4 { background: #fff7ed; color: #c2410c; }
.wb { padding: 2px 8px; border-radius: 50px; font-size: 10px; font-weight: 700; }

/* Footer */
.ft { margin-top: 24px; display: flex; justify-content: space-between; align-items: flex-end; font-size: 10px; color: #999; }
.ft-sign { text-align: center; }
.ft-sign .line { width: 160px; border-top: 1px solid #333; margin: 40px auto 4px; }
.ft-sign p { font-size: 11px; color: #333; }

@media print {
    body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>
</head>
<body>
<div class="page">
    <!-- Header -->
    <div class="hd">
        <div>
            <div style="font-size:10px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#c9a84c;margin-bottom:2px;">DOKUMEN RESMI</div>
            <div style="font-size:20px;font-weight:700;color:#0f1e3c;">PT JUMA TIGA SEANTERO</div>
            <div style="font-size:11px;color:#888;margin-top:2px;">Jl. Perdagangan No. 3, Medan, Sumatera Utara</div>
        </div>
        <div class="hd-right">
            <strong>Data Customer</strong>
            ${tgl} · ${jam} WIB<br>
            Total: ${no - 1} customer
        </div>
    </div>
    <hr class="divider">

    <div class="section-title">📋 Daftar Data Customer</div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Toko</th>
                <th>ID Mesin</th>
                <th>No HP</th>
                <th>Lokasi / Kota</th>
                <th>Provinsi</th>
                <th>Wilayah</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            ${tblHtml || '<tr><td colspan="8" style="text-align:center;padding:20px;color:#999;">Tidak ada data</td></tr>'}
        </tbody>
    </table>

    <!-- Footer -->
    <div class="ft">
        <div>
            <p>Dicetak oleh sistem PT JUMA TIGA SEANTERO</p>
            <p style="margin-top:2px;">${tgl} · ${jam} WIB</p>
        </div>
        <div class="ft-sign">
            <div class="line"></div>
            <p style="font-weight:700;">Mengetahui,</p>
            <p>Admin PT JUMA TIGA SEANTERO</p>
        </div>
    </div>
</div>
<script>window.onload = function(){ window.print(); }<\/script>
</body></html>`);
    win.document.close();
}

function cetakSatu() {
    if (!_dataPrint) return;
    var wl = {
        'Wilayah 1':'Wilayah 1 — Medan Kota',
        'Wilayah 2':'Wilayah 2 — Medan Utara',
        'Wilayah 3':'Wilayah 3 — Binjai & Langkat',
        'Wilayah 4':'Wilayah 4 — Deli Serdang',
    };
    var d = _dataPrint;
    var win = window.open('','_blank','width=620,height=750');
    win.document.write('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Data Customer</title>' +
    '<style>body{font-family:Arial,sans-serif;padding:30px;color:#0f1e3c;margin:0;}' +
    '.head{background:#0f1e3c;color:white;padding:18px 22px;border-radius:10px;margin-bottom:22px;}' +
    '.head h2{margin:0 0 4px;font-size:18px;}.head p{margin:0;font-size:11px;opacity:.7;}' +
    'table{width:100%;border-collapse:collapse;font-size:13px;}' +
    'td{padding:10px 14px;border-bottom:1px solid #f0f2f7;}' +
    'td:first-child{color:#6b7280;width:38%;background:#fafbff;font-weight:500;}' +
    'td:last-child{color:#0f1e3c;font-weight:600;}' +
    '.footer{margin-top:20px;text-align:center;font-size:11px;color:#999;border-top:1px solid #eee;padding-top:12px;}' +
    '</style></head><body>' +
    '<div class="head"><h2>PT JUMA TIGA SEANTERO</h2>' +
    '<p>Data Customer — Dicetak: ' + new Date().toLocaleDateString('id-ID',{day:'2-digit',month:'long',year:'numeric',hour:'2-digit',minute:'2-digit'}) + '</p></div>' +
    '<table>' +
    '<tr><td>Nama Toko</td><td>'+(d.nama_toko||'—')+'</td></tr>' +
    '<tr><td>ID Mesin</td><td>'+(d.id_mesin||'—')+'</td></tr>' +
    '<tr><td>No HP</td><td>'+(d.nomor_hp||'—')+'</td></tr>' +
    '<tr><td>Lokasi</td><td>'+(d.lokasi||'—')+'</td></tr>' +
    '<tr><td>Kota</td><td>'+(d.kota||'—')+'</td></tr>' +
    '<tr><td>Provinsi</td><td>'+(d.provinsi||'—')+'</td></tr>' +
    '<tr><td>Wilayah</td><td>'+(wl[d.wilayah]||d.wilayah||'—')+'</td></tr>' +
    '<tr><td>Status</td><td>'+(d.status==='aktif'?'● Aktif':'— Non-aktif')+'</td></tr>' +
    '<tr><td>Link Maps</td><td>'+(d.link_maps||'—')+'</td></tr>' +
    '</table>' +
    '<div class="footer">PT JUMA TIGA SEANTERO &copy; ' + new Date().getFullYear() + '</div>' +
    '<script>window.onload=function(){window.print();window.close();}<\/script>' +
    '</body></html>');
    win.document.close();
}

function escH(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

// ── GOOGLE MAPS ───────────────────────────────────
var mapsTimer;
function updateMapsPreview() {
    clearTimeout(mapsTimer);
    mapsTimer = setTimeout(function() {
        var lokasi   = document.getElementById('inputLokasi').value.trim();
        var kota     = document.getElementById('inputKota').value.trim();
        var provinsi = document.getElementById('inputProvinsi').value.trim();
        var alamat   = [lokasi,kota,provinsi].filter(Boolean).join(', ');
        var wrap     = document.getElementById('mapsPreviewWrap');
        var iframe   = document.getElementById('mapsIframe');
        var link     = document.getElementById('mapsOpenLink');
        if (alamat.length > 5) {
            var enc = encodeURIComponent(alamat);
            iframe.src = 'https://maps.google.com/maps?q='+enc+'&output=embed&z=15';
            link.href  = 'https://www.google.com/maps/search/?api=1&query='+enc;
            wrap.style.display = 'block';
        } else {
            wrap.style.display = 'none';
            iframe.src = '';
        }
    }, 800);
}

function cariDiMaps() {
    var lokasi   = document.getElementById('inputLokasi').value.trim();
    var kota     = document.getElementById('inputKota').value.trim();
    var provinsi = document.getElementById('inputProvinsi').value.trim();
    var alamat   = [lokasi,kota,provinsi].filter(Boolean).join(', ');
    if (!alamat) { alert('Isi lokasi / alamat terlebih dahulu!'); return; }
    window.open('https://www.google.com/maps/search/?api=1&query='+encodeURIComponent(alamat), '_blank');
}

function previewLinkMaps(url) {
    url = (url||'').trim();
    var wrap = document.getElementById('linkMapsPreview');
    if (!wrap) return;
    if (!url) { wrap.style.display='none'; return; }
    wrap.style.display = 'block';
    wrap.innerHTML = '<div style="padding:14px;background:#f0fdf4;text-align:center;border-radius:8px;">' +
        '<div style="font-size:22px;margin-bottom:6px;">📍</div>' +
        '<p style="font-size:12px;font-weight:600;color:#15803d;margin-bottom:10px;">Link Google Maps tersimpan</p>' +
        '<a href="'+escH(url)+'" target="_blank" style="background:#16a34a;color:white;padding:7px 18px;border-radius:50px;font-size:12px;font-weight:700;text-decoration:none;">🗺️ Buka di Google Maps</a>' +
        '</div>';
}

function bukaLinkMaps() {
    var url = (document.getElementById('inputLinkMaps').value||'').trim();
    if (!url) { alert('Isi link Google Maps terlebih dahulu!'); return; }
    window.open(url, '_blank');
}
</script>
</body>
</html>