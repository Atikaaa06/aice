<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (isset($_SESSION['role'])) {
    $roleMap = ['admin_assets'=>'admin_asset','admin_programs'=>'admin_program'];
    $_SESSION['role'] = $roleMap[$_SESSION['role']] ?? $_SESSION['role'];
}
if (!isset($_SESSION['username'])) { header("Location: login.php"); exit; }
if (!in_array($_SESSION['role'], ['penjual','admin_program'])) { header("Location: dashboard.php"); exit; }

include 'koneksi.php';
$username = $_SESSION['username'];

// Handle hapus
if (isset($_POST['hapus_id'])) {
    $hid = (int)$_POST['hapus_id'];
    $conn->query("DELETE FROM wilayah WHERE id=$hid");
    header("Location: kelola_wilayah.php?deleted=1"); exit;
}

// Handle toggle aktif
if (isset($_POST['toggle_id'])) {
    $tid = (int)$_POST['toggle_id'];
    $conn->query("UPDATE wilayah SET aktif = NOT aktif WHERE id=$tid");
    header("Location: kelola_wilayah.php?toggled=1"); exit;
}

$wilayahs = $conn->query("SELECT * FROM wilayah ORDER BY urutan ASC, id ASC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Wilayah — PT JUMA TIGA SEANTERO</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .wilayah-table-card {
            background: white; border-radius: var(--radius);
            box-shadow: var(--shadow-sm); overflow: hidden;
        }
        .wil-row {
            display: grid;
            grid-template-columns: 40px 1fr 160px 160px 100px 130px;
            align-items: center;
            padding: 14px 20px;
            border-bottom: 1px solid #f0f2f7;
            gap: 12px;
            transition: background .15s;
            animation: fadeUp .3s ease both;
        }
        .wil-row:hover { background: #fafbff; }
        .wil-row.header {
            background: var(--navy); color: white;
            font-size: 11px; font-weight: 700;
            letter-spacing: 1px; text-transform: uppercase;
            animation: none;
        }
        .wil-num { font-size:20px; font-weight:700; color:var(--gold); font-family:'Playfair Display',serif; text-align:center; }
        .wil-nama { font-weight:700; color:var(--navy); font-size:15px; }
        .wil-admin { font-size:13px; color:var(--text); }
        .wil-admin small { color:var(--muted); font-size:11px; display:block; margin-top:2px; }
        .wil-area { font-size:11px; color:var(--muted); line-height:1.6; }
        .wil-hp {
            font-size:12px; font-weight:600; color:#16a34a;
            display:flex; align-items:center; gap:4px;
        }
        .wil-actions { display:flex; gap:6px; flex-wrap:wrap; }
        .btn-wil { padding:5px 12px; border-radius:6px; font-size:11px; font-weight:600; border:none; cursor:pointer; font-family:'DM Sans',sans-serif; transition:opacity .2s; text-decoration:none; display:inline-flex; align-items:center; gap:4px; }
        .btn-wil:hover { opacity:.75; }
        .btn-wil-edit   { background:#eff6ff; color:#1d4ed8; }
        .btn-wil-toggle { background:#f0fdf4; color:#16a34a; }
        .btn-wil-off    { background:#fdf0ef; color:#c0392b; }
        .btn-wil-del    { background:#f9fafb; color:var(--muted); }
        .pill-aktif    { background:#f0fdf4;color:#16a34a;border:1px solid #86efac;padding:3px 10px;border-radius:50px;font-size:11px;font-weight:600; }
        .pill-nonaktif { background:#f9fafb;color:#6b7280;border:1px solid #d1d5db;padding:3px 10px;border-radius:50px;font-size:11px;font-weight:600; }

        /* Modal */
        .modal-overlay{display:none;position:fixed;inset:0;background:rgba(15,30,60,.55);z-index:200;align-items:center;justify-content:center;padding:20px;}
        .modal-overlay.open{display:flex;animation:fadeIn .2s ease;}
        @keyframes fadeIn{from{opacity:0}to{opacity:1}}
        .modal{background:var(--white);border-radius:var(--radius);width:100%;max-width:520px;max-height:92vh;overflow-y:auto;box-shadow:var(--shadow-lg);animation:slideIn .3s cubic-bezier(.22,.68,0,1.2) both;}
        .modal-header{background:var(--navy);padding:18px 24px;display:flex;justify-content:space-between;align-items:center;}
        .modal-header h3{font-family:'Playfair Display',serif;color:white;font-size:17px;}
        .modal-close{background:none;border:none;color:rgba(255,255,255,.6);font-size:22px;cursor:pointer;}
        .modal-close:hover{color:white;}
        .modal-body{padding:24px;}
        .modal-footer{padding:14px 24px;border-top:1px solid #f0f2f7;display:flex;justify-content:flex-end;gap:10px;}
        .btn-cancel{padding:10px 20px;background:transparent;color:var(--muted);border:1.5px solid #e0e4ec;border-radius:var(--radius-sm);font-family:'DM Sans',sans-serif;font-size:14px;cursor:pointer;}
        .btn-save{padding:10px 24px;background:var(--navy);color:white;border:none;border-radius:var(--radius-sm);font-family:'DM Sans',sans-serif;font-size:14px;font-weight:600;cursor:pointer;}
    </style>
</head>
<body>
<?php $navActive = 'wilayah'; include 'navbar.php'; ?>
<style>
.back-bar-global{background:var(--white);border-bottom:1px solid #eef0f7;padding:10px 40px;display:flex;align-items:center;gap:16px;box-shadow:0 1px 4px rgba(15,30,60,0.05);}
.back-btn-global{display:inline-flex;align-items:center;gap:7px;color:var(--navy);text-decoration:none;font-size:13px;font-weight:600;padding:6px 16px;border-radius:50px;border:1.5px solid #e0e4ec;background:var(--cream);transition:all .2s;}
.back-btn-global:hover{background:var(--navy);color:white;border-color:var(--navy);}
.breadcrumb-trail{display:flex;align-items:center;gap:6px;font-size:12px;color:var(--muted);}
.breadcrumb-trail a{color:var(--muted);text-decoration:none;}.breadcrumb-trail .sep{color:#ccc;}.breadcrumb-trail .current{color:var(--navy);font-weight:600;}
</style>
<div class="back-bar-global">
    <a href="dashboard.php" class="back-btn-global">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15,18 9,12 15,6"/></svg>
        ← Kembali ke Dashboard
    </a>
    <div class="breadcrumb-trail"><a href="dashboard.php">Dashboard</a><span class="sep">›</span><span class="current">Kelola Wilayah</span></div>
</div>

<div class="page">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
        <div class="page-header" style="margin-bottom:0;">
            <h1>📍 Kelola Wilayah</h1>
            <p>Atur data admin dan jangkauan wilayah distribusi PT JUMA TIGA SEANTERO</p>
        </div>
        <button class="btn btn-primary" style="width:auto;padding:11px 22px;" onclick="openModal()">
            + Tambah Wilayah
        </button>
    </div>

    <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success" style="margin-bottom:20px;">✅ Data wilayah berhasil disimpan.</div>
    <?php elseif (isset($_GET['deleted'])): ?>
        <div class="alert alert-error" style="margin-bottom:20px;">🗑️ Wilayah berhasil dihapus.</div>
    <?php elseif (isset($_GET['toggled'])): ?>
        <div class="alert alert-success" style="margin-bottom:20px;">🔄 Status wilayah diubah.</div>
    <?php endif; ?>

    <!-- Statistik -->
    <?php
    $total  = count($wilayahs);
    $aktif  = array_reduce($wilayahs, fn($c,$r)=>$c+($r['aktif']?1:0), 0);
    ?>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px;">
        <div class="dash-card" style="animation-delay:.04s"><div class="dash-card-label">Total Wilayah</div><div class="dash-card-value"><?= $total ?></div></div>
        <div class="dash-card" style="animation-delay:.08s;border-left-color:#16a34a;"><div class="dash-card-label">Aktif</div><div class="dash-card-value"><?= $aktif ?></div></div>
        <div class="dash-card" style="animation-delay:.12s;border-left-color:#6b7280;"><div class="dash-card-label">Nonaktif</div><div class="dash-card-value"><?= $total-$aktif ?></div></div>
    </div>

    <!-- Tabel Wilayah -->
    <div class="wilayah-table-card">
        <!-- Header -->
        <div class="wil-row header">
            <div style="text-align:center;">No</div>
            <div>Wilayah & Admin</div>
            <div>No HP / WA</div>
            <div>Area Coverage</div>
            <div>Status</div>
            <div>Aksi</div>
        </div>

        <?php if (empty($wilayahs)): ?>
            <div style="text-align:center;padding:50px;color:var(--muted);">
                <div style="font-size:40px;margin-bottom:12px;">📍</div>
                <p>Belum ada data wilayah. Tambahkan wilayah pertama!</p>
            </div>
        <?php else: ?>
            <?php foreach ($wilayahs as $i => $w): $delay=($i*.05).'s'; ?>
            <div class="wil-row" style="animation-delay:<?= $delay ?>">
                <!-- Nomor -->
                <div class="wil-num"><?= str_pad($w['urutan']?:($i+1), 2, '0', STR_PAD_LEFT) ?></div>

                <!-- Nama wilayah & admin -->
                <div>
                    <div class="wil-nama">📍 <?= htmlspecialchars($w['nama_wilayah']) ?></div>
                    <div class="wil-admin">
                        👤 <?= htmlspecialchars($w['nama_admin']) ?>
                    </div>
                </div>

                <!-- No HP -->
                <div>
                    <?php
                    $hp = $w['no_hp'];
                    $hpDisplay = '0' . substr($hp, 2);
                    $waUrl = "https://wa.me/$hp?text=" . urlencode("Halo Admin Wilayah ".$w['nama_wilayah'].", saya ingin bertanya tentang produk PT JUMA TIGA SEANTERO");
                    ?>
                    <a href="<?= $waUrl ?>" target="_blank" class="wil-hp">
                        💬 <?= htmlspecialchars($hpDisplay) ?>
                    </a>
                </div>

                <!-- Area -->
                <div class="wil-area">
                    <?= nl2br(htmlspecialchars($w['area_coverage']??'-')) ?>
                </div>

                <!-- Status -->
                <div>
                    <span class="pill-<?= $w['aktif']?'aktif':'nonaktif' ?>">
                        <?= $w['aktif']?'● Aktif':'— Nonaktif' ?>
                    </span>
                </div>

                <!-- Aksi -->
                <div class="wil-actions">
                    <button class="btn-wil btn-wil-edit"
                        onclick="openEdit(<?= htmlspecialchars(json_encode($w), ENT_QUOTES) ?>)">
                        ✏️ Edit
                    </button>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="toggle_id" value="<?= $w['id'] ?>">
                        <button type="submit" class="btn-wil <?= $w['aktif']?'btn-wil-off':'btn-wil-toggle' ?>">
                            <?= $w['aktif']?'⏸':'▶' ?>
                        </button>
                    </form>
                    <form method="POST" style="display:inline;"
                          onsubmit="return confirm('Hapus wilayah <?= htmlspecialchars(addslashes($w['nama_wilayah'])) ?>?')">
                        <input type="hidden" name="hapus_id" value="<?= $w['id'] ?>">
                        <button type="submit" class="btn-wil btn-wil-del">🗑️</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<p class="footer">© <?= date('Y') ?> PT JUMA TIGA SEANTERO</p>

<!-- Modal Tambah/Edit -->
<div class="modal-overlay" id="modalWilayah" onclick="if(event.target===this)closeModal()">
    <div class="modal">
        <div class="modal-header">
            <h3 id="modalTitle">Tambah Wilayah</h3>
            <button class="modal-close" onclick="closeModal()">✕</button>
        </div>
        <form method="POST" action="simpan_wilayah.php">
            <input type="hidden" name="id" id="inputId">
            <div class="modal-body">
                <div class="field">
                    <label>Nama Wilayah <span style="color:red">*</span></label>
                    <input type="text" name="nama_wilayah" id="inputNamaWilayah"
                           placeholder="Contoh: Medan" required>
                </div>
                <div class="field">
                    <label>Nama Admin <span style="color:red">*</span></label>
                    <input type="text" name="nama_admin" id="inputNamaAdmin"
                           placeholder="Nama lengkap admin wilayah" required>
                </div>
                <div class="field">
                    <label>No HP / WhatsApp <span style="color:red">*</span></label>
                    <div style="display:flex;align-items:center;background:var(--cream);border:1.5px solid #e0e4ec;border-radius:var(--radius-sm);overflow:hidden;">
                        <span style="padding:0 12px;color:var(--muted);font-size:14px;flex-shrink:0;">📱</span>
                        <input type="text" name="no_hp" id="inputNoHp"
                               placeholder="08123456789"
                               style="flex:1;border:none;background:transparent;padding:10px 12px 10px 0;font-size:13px;font-family:'DM Sans',sans-serif;outline:none;"
                               required>
                    </div>
                    <small style="color:var(--muted);font-size:11px;margin-top:4px;display:block;">Format: 08xx atau +628xx</small>
                </div>
                <div class="field">
                    <label>Area Coverage</label>
                    <textarea name="area_coverage" id="inputArea" rows="3"
                              style="width:100%;padding:10px 14px;border:1.5px solid #e0e4ec;border-radius:var(--radius-sm);font-family:'DM Sans',sans-serif;font-size:13px;resize:vertical;outline:none;background:var(--cream);"
                              placeholder="Contoh: Banda Aceh · Aceh Besar · Lhokseumawe"></textarea>
                </div>
                <div class="field">
                    <label>Urutan Tampil</label>
                    <input type="number" name="urutan" id="inputUrutan"
                           placeholder="1, 2, 3, 4..." min="1" value="1">
                </div>
                <div class="field">
                    <label>Status</label>
                    <select name="aktif" id="inputAktif">
                        <option value="1">● Aktif (tampil di landing page)</option>
                        <option value="0">⏸ Nonaktif</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn-save" id="btnSave">💾 Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal() {
    document.getElementById('modalTitle').textContent = 'Tambah Wilayah Baru';
    document.getElementById('btnSave').textContent    = '💾 Simpan';
    document.getElementById('inputId').value          = '';
    document.getElementById('inputNamaWilayah').value = '';
    document.getElementById('inputNamaAdmin').value   = '';
    document.getElementById('inputNoHp').value        = '';
    document.getElementById('inputArea').value        = '';
    document.getElementById('inputUrutan').value      = '<?= count($wilayahs)+1 ?>';
    document.getElementById('inputAktif').value       = '1';
    document.getElementById('modalWilayah').classList.add('open');
}
function openEdit(data) {
    document.getElementById('modalTitle').textContent = 'Edit Wilayah';
    document.getElementById('btnSave').textContent    = '💾 Update';
    document.getElementById('inputId').value          = data.id;
    document.getElementById('inputNamaWilayah').value = data.nama_wilayah;
    document.getElementById('inputNamaAdmin').value   = data.nama_admin;
    // Konversi 628xx → 08xx untuk tampilan form
    let hp = data.no_hp || '';
    if (hp.startsWith('62')) hp = '0' + hp.substring(2);
    document.getElementById('inputNoHp').value        = hp;
    document.getElementById('inputArea').value        = data.area_coverage || '';
    document.getElementById('inputUrutan').value      = data.urutan || '1';
    document.getElementById('inputAktif').value       = data.aktif;
    document.getElementById('modalWilayah').classList.add('open');
}
function closeModal() { document.getElementById('modalWilayah').classList.remove('open'); }
document.addEventListener('keydown', e => { if(e.key==='Escape') closeModal(); });
</script>
</body>
</html>