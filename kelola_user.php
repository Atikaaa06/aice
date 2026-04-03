<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'penjual') {
    header("Location: login.php"); exit;
}
include 'koneksi.php';

// Ambil semua user
$search = trim($_GET['search'] ?? '');
$filterRole = trim($_GET['role'] ?? '');

$where = ["1=1"];
$params = []; $types = '';

if ($search !== '') {
    $where[] = "username LIKE ?";
    $params[] = "%$search%"; $types .= 's';
}
if ($filterRole !== '') {
    $where[] = "role = ?";
    $params[] = $filterRole; $types .= 's';
}

$sql = "SELECT * FROM users WHERE " . implode(' AND ', $where) . " ORDER BY role, username ASC";
$stmt = $conn->prepare($sql);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Statistik
$totalPenjual = 0; $totalPembeli = 0;
foreach ($users as $u) {
    if ($u['role'] === 'penjual') $totalPenjual++;
    else $totalPembeli++;
}

// Handle hapus
if (isset($_POST['hapus_id'])) {
    $hid = (int) $_POST['hapus_id'];
    // Jangan hapus diri sendiri
    $selfStmt = $conn->prepare("SELECT username FROM users WHERE id=?");
    $selfStmt->bind_param("i", $hid);
    $selfStmt->execute();
    $target = $selfStmt->get_result()->fetch_assoc();
    $selfStmt->close();

    if ($target && $target['username'] !== $_SESSION['username']) {
        $del = $conn->prepare("DELETE FROM users WHERE id=?");
        $del->bind_param("i", $hid);
        $del->execute();
        $del->close();
    }
    header("Location: kelola_user.php?deleted=1"); exit;
}

// Handle reset password
if (isset($_POST['reset_id'])) {
    $rid = (int) $_POST['reset_id'];
    $newpw = trim($_POST['new_password'] ?? '');
    if ($newpw && strlen($newpw) >= 4) {
        $rst = $conn->prepare("UPDATE users SET password=? WHERE id=?");
        $rst->bind_param("si", $newpw, $rid);
        $rst->execute();
        $rst->close();
        header("Location: kelola_user.php?reset=1"); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User — PT Juma Tiga</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .user-table-wrap {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
            overflow: hidden;
        }
        table { width: 100%; border-collapse: collapse; }
        thead tr { background: var(--navy); }
        thead th {
            padding: 13px 18px;
            color: var(--white);
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            text-align: left;
        }
        tbody tr { border-bottom: 1px solid #f0f2f7; transition: background 0.15s; }
        tbody tr:hover { background: #fafbff; }
        tbody td { padding: 13px 18px; font-size: 14px; }

        .avatar-mini {
            width: 36px; height: 36px;
            border-radius: 50%;
            background: var(--navy);
            color: var(--gold-light);
            display: inline-flex; align-items: center; justify-content: center;
            font-family: 'Playfair Display', serif;
            font-size: 14px;
            font-weight: 700;
            margin-right: 10px;
            vertical-align: middle;
        }
        .role-chip {
            padding: 3px 10px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 600;
        }
        .role-penjual { background: #fef9ec; color: #92680c; border: 1px solid var(--gold); }
        .role-pembeli { background: #eff6ff; color: #1d4ed8; border: 1px solid #93c5fd; }
        .role-sales   { background: #f0fdf4; color: #16a34a; border: 1px solid #86efac; }
        .role-asset   { background: #fdf4ff; color: #9333ea; border: 1px solid #d8b4fe; }
        .role-program { background: #fef3c7; color: #d97706; border: 1px solid #fcd34d; }

        .td-actions { display: flex; gap: 6px; align-items: center; }
        .btn-sm {
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            font-family: 'DM Sans', sans-serif;
            transition: opacity 0.2s;
        }
        .btn-sm:hover { opacity: 0.75; }
        .btn-reset-pw { background: #fffbeb; color: #d97706; }
        .btn-del-user { background: #fdf0ef; color: #c0392b; }

        /* Tambah user panel */
        .add-panel {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            margin-bottom: 28px;
            animation: fadeUp 0.4s ease 0.1s both;
        }
        .add-panel-head {
            background: var(--navy);
            padding: 16px 24px;
            display: flex; align-items: center; gap: 10px;
            cursor: pointer;
            user-select: none;
        }
        .add-panel-head h3 { font-family: 'Playfair Display', serif; color: var(--white); font-size: 16px; flex:1; }
        .add-panel-head span { color: var(--gold-light); font-size: 18px; transition: transform 0.3s; }
        .add-panel-head span.open { transform: rotate(45deg); }
        .add-panel-body {
            padding: 24px;
            display: none;
            background: var(--cream);
        }
        .add-panel-body.open { display: block; animation: fadeUp 0.3s ease both; }
        .add-form-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            align-items: end;
        }

        .filter-bar {
            background: var(--white);
            border-radius: var(--radius);
            padding: 16px 20px;
            box-shadow: var(--shadow-sm);
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 20px;
            align-items: center;
        }
        .filter-bar input,
        .filter-bar select {
            padding: 9px 14px;
            border: 1.5px solid #e0e4ec;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-family: 'DM Sans', sans-serif;
            background: var(--cream);
            outline: none;
            transition: border-color 0.2s;
        }
        .filter-bar input { flex: 1; min-width: 160px; }
        .filter-bar input:focus,
        .filter-bar select:focus { border-color: var(--gold); }
        .btn-cari {
            padding: 9px 20px;
            background: var(--navy);
            color: white;
            border: none;
            border-radius: var(--radius-sm);
            font-family: 'DM Sans', sans-serif;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        /* Modal reset pw */
        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(15,30,60,0.55); z-index:200; align-items:center; justify-content:center; padding:20px; }
        .modal-overlay.open { display:flex; }
        .modal { background:var(--white); border-radius:var(--radius); width:100%; max-width:380px; box-shadow:var(--shadow-lg); animation:slideIn .3s cubic-bezier(.22,.68,0,1.2) both; overflow:hidden; }
        .modal-header { background:var(--navy); padding:18px 24px; display:flex; justify-content:space-between; align-items:center; }
        .modal-header h3 { font-family:'Playfair Display',serif; color:var(--white); font-size:17px; }
        .modal-close { background:none; border:none; color:rgba(255,255,255,.6); font-size:22px; cursor:pointer; }
        .modal-close:hover { color:white; }
        .modal-body { padding:24px; }
        .modal-footer { padding:14px 24px; border-top:1px solid #f0f2f7; display:flex; justify-content:flex-end; gap:10px; }
        .btn-cancel { padding:9px 18px; background:transparent; color:var(--muted); border:1.5px solid #e0e4ec; border-radius:var(--radius-sm); font-family:'DM Sans',sans-serif; font-size:13px; cursor:pointer; }
        .btn-save   { padding:9px 18px; background:var(--navy); color:white; border:none; border-radius:var(--radius-sm); font-family:'DM Sans',sans-serif; font-size:13px; font-weight:600; cursor:pointer; }

        @media (max-width: 640px) {
            .add-form-grid { grid-template-columns: 1fr; }
            .td-actions { flex-wrap: wrap; }
        }
    </style>
</head>
<body>

<?php $navActive = 'user'; include 'navbar.php'; ?>
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
    <div class="breadcrumb-trail"><a href="dashboard.php">Dashboard</a><span class="sep">›</span><span class="current">Kelola User</span></div>
</div>


<div class="page">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:28px;animation:fadeUp .4s ease both;">
        <div class="page-header" style="margin-bottom:0">
            <h1>👤 Kelola User</h1>
            <p>Tambah, reset password, dan hapus akun pengguna</p>
        </div>
    </div>

    <!-- Alert -->
    <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success" style="margin-bottom:20px;">✅ User baru berhasil ditambahkan.</div>
    <?php elseif (isset($_GET['deleted'])): ?>
        <div class="alert alert-error" style="margin-bottom:20px;">🗑️ User berhasil dihapus.</div>
    <?php elseif (isset($_GET['reset'])): ?>
        <div class="alert alert-success" style="margin-bottom:20px;">🔑 Password berhasil direset.</div>
    <?php endif; ?>

    <!-- Statistik -->
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px;">
        <div class="dash-card" style="animation-delay:.05s">
            <div class="dash-card-label">Total User</div>
            <div class="dash-card-value"><?= count($users) ?></div>
        </div>
        <div class="dash-card" style="animation-delay:.1s;border-left-color:#92680c;">
            <div class="dash-card-label">Penjual / Admin</div>
            <div class="dash-card-value"><?= $totalPenjual ?></div>
        </div>
        <div class="dash-card" style="animation-delay:.15s;border-left-color:#1d4ed8;">
            <div class="dash-card-label">Pembeli</div>
            <div class="dash-card-value"><?= $totalPembeli ?></div>
        </div>
    </div>

    <!-- Panel Tambah User -->
    <div class="add-panel">
        <div class="add-panel-head" onclick="togglePanel()">
            <h3>➕ Tambah User Baru</h3>
            <span id="panelIcon">+</span>
        </div>
        <div class="add-panel-body" id="panelBody">
            <form method="POST" action="proses_tambah_user.php">
                <div class="add-form-grid">
                    <div class="field">
                        <label>Username <span style="color:red">*</span></label>
                        <input type="text" name="username" placeholder="Minimal 4 karakter" required>
                    </div>
                    <div class="field">
                        <label>Password <span style="color:red">*</span></label>
                        <input type="password" name="password" placeholder="Minimal 6 karakter" required>
                    </div>
                    <div class="field">
                        <label>Role <span style="color:red">*</span></label>
                        <select name="role" required>
                            <option value="pembeli">🛒 Pembeli</option>
                            <option value="sales">👁️ Sales (lihat data)</option>
                            <option value="admin_asset">✏️ Admin Asset (kelola customer)</option>
                            <option value="admin_program">💻 Admin Program (kelola produk &amp; pengumuman)</option>
                            <option value="penjual">⭐ Penjual / Admin (akses penuh)</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" style="width:auto;padding:10px 28px;margin-top:16px;">
                    Simpan User →
                </button>
            </form>
        </div>
    </div>

    <!-- Filter -->
    <form method="GET" class="filter-bar">
        <input type="text" name="search" placeholder="🔍 Cari username..." value="<?= htmlspecialchars($search) ?>">
        <select name="role">
            <option value="">Semua Role</option>
            <option value="penjual" <?= $filterRole==='penjual'?'selected':'' ?>>Penjual</option>
            <option value="pembeli" <?= $filterRole==='pembeli'?'selected':'' ?>>Pembeli</option>
        </select>
        <button type="submit" class="btn-cari">Cari</button>
        <a href="kelola_user.php" style="font-size:13px;color:var(--muted);text-decoration:none;padding:9px 4px;">Reset</a>
    </form>

    <!-- Tabel User -->
    <div class="user-table-wrap" style="animation:fadeUp .5s ease .2s both;">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr><td colspan="4" style="text-align:center;color:var(--muted);padding:40px;">Tidak ada user ditemukan</td></tr>
                <?php else: ?>
                <?php foreach ($users as $i => $u): ?>
                    <tr>
                        <td style="color:var(--muted);font-size:13px;"><?= $i+1 ?></td>
                        <td>
                            <span class="avatar-mini"><?= strtoupper(substr($u['username'],0,1)) ?></span>
                            <strong><?= htmlspecialchars($u['username']) ?></strong>
                            <?php if ($u['username'] === $_SESSION['username']): ?>
                                <span style="font-size:11px;color:var(--gold);margin-left:4px;">(Anda)</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $roleLabel = [
                                'penjual'       => '⭐ Penjual',
                                'pembeli'       => '🛒 Pembeli',
                                'sales'         => '👁️ Sales',
                                'admin_asset'   => '✏️ Admin Asset',
                                'admin_program' => '💻 Admin Program',
                            ];
                            $roleColor = [
                                'penjual'       => 'role-penjual',
                                'pembeli'       => 'role-pembeli',
                                'sales'         => 'role-sales',
                                'admin_asset'   => 'role-asset',
                                'admin_program' => 'role-program',
                            ];
                            ?>
                            <span class="role-chip <?= $roleColor[$u['role']] ?? 'role-pembeli' ?>">
                                <?= $roleLabel[$u['role']] ?? $u['role'] ?>
                            </span>
                        </td>
                        <td>
                            <div class="td-actions">
                                <button class="btn-sm btn-reset-pw"
                                    onclick="openReset(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username']) ?>')">
                                    🔑 Reset PW
                                </button>
                                <?php if ($u['username'] !== $_SESSION['username']): ?>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Hapus user <?= htmlspecialchars($u['username']) ?>?')">
                                    <input type="hidden" name="hapus_id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="btn-sm btn-del-user">🗑️ Hapus</button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<p class="footer">© <?= date('Y') ?> PT Juma Tiga</p>

<!-- Modal Reset Password -->
<div class="modal-overlay" id="modalReset" onclick="if(event.target===this)closeReset()">
    <div class="modal">
        <div class="modal-header">
            <h3>🔑 Reset Password</h3>
            <button class="modal-close" onclick="closeReset()">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="reset_id" id="resetId">
            <div class="modal-body">
                <p style="font-size:13px;color:var(--muted);margin-bottom:16px;">
                    Reset password untuk: <strong id="resetUsername"></strong>
                </p>
                <div class="field">
                    <label>Password Baru <span style="color:red">*</span></label>
                    <input type="text" name="new_password" id="newPw" placeholder="Minimal 4 karakter" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeReset()">Batal</button>
                <button type="submit" class="btn-save">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function togglePanel() {
    const body = document.getElementById('panelBody');
    const icon = document.getElementById('panelIcon');
    body.classList.toggle('open');
    icon.classList.toggle('open');
}

function openReset(id, username) {
    document.getElementById('resetId').value = id;
    document.getElementById('resetUsername').textContent = username;
    document.getElementById('newPw').value = '';
    document.getElementById('modalReset').classList.add('open');
}
function closeReset() {
    document.getElementById('modalReset').classList.remove('open');
}
document.addEventListener('keydown', e => { if(e.key==='Escape') closeReset(); });

// Buka panel otomatis jika baru save
<?php if (isset($_GET['error_user'])): ?>
togglePanel();
<?php endif; ?>
</script>
</body>
</html>