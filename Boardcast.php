<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (isset($_SESSION['role'])) {
    $roleMap = ['admin_assets'=>'admin_asset','admin_programs'=>'admin_program'];
    $_SESSION['role'] = $roleMap[$_SESSION['role']] ?? $_SESSION['role'];
}
if (!isset($_SESSION['username'])) { header("Location: login.php"); exit; }
if (!in_array($_SESSION['role'], ['penjual','admin_program'])) { header("Location: dashboard.php"); exit; }

include 'koneksi.php';
$role     = $_SESSION['role'];
$username = $_SESSION['username'];

// Handle hapus
if (isset($_POST['hapus_id'])) {
    $hid = (int)$_POST['hapus_id'];
    $conn->query("DELETE FROM broadcast WHERE id=$hid");
    header("Location: broadcast.php?deleted=1"); exit;
}

// Handle toggle aktif
if (isset($_POST['toggle_id'])) {
    $tid = (int)$_POST['toggle_id'];
    $conn->query("UPDATE broadcast SET aktif = NOT aktif WHERE id=$tid");
    header("Location: broadcast.php?toggled=1"); exit;
}

$broadcasts = $conn->query("SELECT * FROM broadcast ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Broadcast — PT JUMA TIGA SEANTERO</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .bc-card { background:white; border-radius:var(--radius); padding:20px 24px; margin-bottom:16px; box-shadow:var(--shadow-sm); border-left:4px solid var(--gold); animation:fadeUp .4s ease both; transition:box-shadow .2s; }
        .bc-card:hover { box-shadow:var(--shadow-md); }
        .bc-card.nonaktif { opacity:.5; border-left-color:#ccc; }
        .bc-head { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; margin-bottom:10px; flex-wrap:wrap; }
        .bc-title { font-family:'Playfair Display',serif; font-size:16px; color:var(--navy); }
        .bc-target { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:50px; font-size:11px; font-weight:700; background:#eff6ff; color:#1d4ed8; }
        .bc-pesan { font-size:13px; color:var(--muted); line-height:1.7; margin-bottom:12px; }
        .bc-footer { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
        .bc-date { font-size:11px; color:#bbb; margin-right:auto; }
        .btn-bc { padding:5px 14px; border-radius:6px; font-size:11px; font-weight:600; border:none; cursor:pointer; font-family:'DM Sans',sans-serif; transition:opacity .2s; }
        .btn-bc:hover { opacity:.75; }

        /* Modal */
        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(15,30,60,0.55); z-index:200; align-items:center; justify-content:center; padding:20px; }
        .modal-overlay.open { display:flex; animation:fadeIn .2s ease; }
        @keyframes fadeIn{from{opacity:0}to{opacity:1}}
        .modal { background:var(--white); border-radius:var(--radius); width:100%; max-width:540px; max-height:90vh; overflow-y:auto; box-shadow:var(--shadow-lg); animation:slideIn .3s cubic-bezier(.22,.68,0,1.2) both; }
        .modal-header { background:var(--navy); padding:18px 24px; display:flex; justify-content:space-between; align-items:center; }
        .modal-header h3 { font-family:'Playfair Display',serif; color:white; font-size:17px; }
        .modal-close { background:none; border:none; color:rgba(255,255,255,.6); font-size:22px; cursor:pointer; }
        .modal-body { padding:24px; }
        .modal-footer { padding:14px 24px; border-top:1px solid #f0f2f7; display:flex; justify-content:flex-end; gap:10px; }
        .btn-cancel { padding:10px 20px; background:transparent; color:var(--muted); border:1.5px solid #e0e4ec; border-radius:var(--radius-sm); font-family:'DM Sans',sans-serif; font-size:14px; cursor:pointer; }
        .btn-save   { padding:10px 24px; background:var(--navy); color:white; border:none; border-radius:var(--radius-sm); font-family:'DM Sans',sans-serif; font-size:14px; font-weight:600; cursor:pointer; }
    </style>
</head>
<body>
<?php $navActive = 'broadcast'; include 'navbar.php'; ?>
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
    <div class="breadcrumb-trail"><a href="dashboard.php">Dashboard</a><span class="sep">›</span><span class="current">Broadcast</span></div>
</div>

<div class="page">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
        <div class="page-header" style="margin-bottom:0;">
            <h1>📣 Broadcast</h1>
            <p>Kirim pesan/pengumuman ke semua pengguna atau role tertentu</p>
        </div>
        <button class="btn btn-primary" style="width:auto;padding:11px 22px;margin-top:6px;" onclick="openModal()">
            + Buat Broadcast
        </button>
    </div>

    <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success" style="margin-bottom:20px;">
            ✅ Broadcast berhasil disimpan!
            <?php if (!empty($_GET['wa']) && !empty($_GET['msg'])): ?>
                &nbsp;—&nbsp;
                <a href="https://wa.me/<?= htmlspecialchars($_GET['wa']) ?>?text=<?= urlencode(urldecode($_GET['msg'])) ?>"
                   target="_blank"
                   style="background:#16a34a;color:white;padding:5px 14px;border-radius:50px;font-size:12px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                    💬 Kirim via WhatsApp Sekarang
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?><div class="alert alert-error" style="margin-bottom:20px;">🗑️ Broadcast dihapus.</div><?php endif; ?>
    <?php if (isset($_GET['toggled'])): ?><div class="alert alert-success" style="margin-bottom:20px;">🔄 Status broadcast diubah.</div><?php endif; ?>

    <!-- Statistik -->
    <?php
    $totalBc  = count($broadcasts);
    $aktifBc  = array_reduce($broadcasts, fn($c,$r) => $c + ($r['aktif']?1:0), 0);
    ?>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px;">
        <div class="dash-card" style="animation-delay:.05s"><div class="dash-card-label">Total Broadcast</div><div class="dash-card-value"><?= $totalBc ?></div></div>
        <div class="dash-card" style="animation-delay:.1s;border-left-color:#16a34a;"><div class="dash-card-label">Aktif</div><div class="dash-card-value"><?= $aktifBc ?></div></div>
        <div class="dash-card" style="animation-delay:.15s;border-left-color:#6b7280;"><div class="dash-card-label">Nonaktif</div><div class="dash-card-value"><?= $totalBc-$aktifBc ?></div></div>
    </div>

    <?php if (empty($broadcasts)): ?>
        <div style="text-align:center;padding:60px;background:white;border-radius:var(--radius);color:var(--muted);box-shadow:var(--shadow-sm);">
            <div style="font-size:48px;margin-bottom:12px;">📣</div>
            <p>Belum ada broadcast. Buat broadcast pertama kamu!</p>
            <button onclick="openModal()" class="btn btn-primary" style="width:auto;padding:10px 24px;display:inline-flex;margin-top:16px;">+ Buat Broadcast</button>
        </div>
    <?php else: ?>
        <?php
        $targetLabel = [
            'semua'         => '👥 Semua',
            'pembeli'       => '🛒 Pembeli',
            'sales'         => '👁️ Sales',
            'admin_asset'   => '✏️ Admin Asset',
            'admin_program' => '💻 Admin Program',
        ];
        foreach ($broadcasts as $i => $bc):
            $delay = ($i*0.04).'s';
        ?>
        <div class="bc-card <?= !$bc['aktif']?'nonaktif':'' ?>" style="animation-delay:<?= $delay ?>">
            <div class="bc-head">
                <div>
                    <div class="bc-title">📣 <?= htmlspecialchars($bc['judul']) ?></div>
                    <div style="margin-top:5px;">
                        <span class="bc-target"><?= $targetLabel[$bc['target_role']] ?? $bc['target_role'] ?></span>
                        <?php if (!$bc['aktif']): ?>
                            <span style="background:#f9fafb;color:var(--muted);padding:3px 10px;border-radius:50px;font-size:11px;font-weight:700;margin-left:4px;">⏸ Nonaktif</span>
                        <?php else: ?>
                            <span style="background:#f0fdf4;color:#16a34a;padding:3px 10px;border-radius:50px;font-size:11px;font-weight:700;margin-left:4px;">▶ Aktif</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="bc-pesan"><?= nl2br(htmlspecialchars($bc['pesan'])) ?></div>
            <div class="bc-footer">
                <span class="bc-date">📅 <?= date('d M Y H:i', strtotime($bc['created_at'])) ?> · oleh <?= htmlspecialchars($bc['dibuat_oleh'] ?? '-') ?></span>
                <?php if (!empty($bc['no_hp'])): ?>
                    <?php
                    $waMsg = urlencode("📣 *" . $bc['judul'] . "*

" . $bc['pesan'] . "

_PT JUMA TIGA SEANTERO_");
                    $waUrl = "https://wa.me/" . $bc['no_hp'] . "?text=" . $waMsg;
                    ?>
                    <a href="<?= $waUrl ?>" target="_blank"
                       class="btn-bc" style="background:#dcfce7;color:#16a34a;text-decoration:none;">
                        💬 Kirim WA
                    </a>
                <?php endif; ?>
                <button class="btn-bc" style="background:#eff6ff;color:#1d4ed8;"
                    onclick="openEdit(<?= htmlspecialchars(json_encode($bc), ENT_QUOTES) ?>)">✏️ Edit</button>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="toggle_id" value="<?= $bc['id'] ?>">
                    <button type="submit" class="btn-bc" style="background:<?= $bc['aktif']?'#fdf0ef':'#f0fdf4' ?>;color:<?= $bc['aktif']?'#c0392b':'#16a34a' ?>">
                        <?= $bc['aktif']?'⏸ Nonaktifkan':'▶ Aktifkan' ?>
                    </button>
                </form>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus broadcast ini?')">
                    <input type="hidden" name="hapus_id" value="<?= $bc['id'] ?>">
                    <button type="submit" class="btn-bc" style="background:#f9fafb;color:var(--muted);">🗑️</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<p class="footer">© <?= date('Y') ?> PT JUMA TIGA SEANTERO</p>

<!-- Modal -->
<div class="modal-overlay" id="modalBc" onclick="if(event.target===this)closeModal()">
    <div class="modal">
        <div class="modal-header">
            <h3 id="modalTitle">Buat Broadcast Baru</h3>
            <button class="modal-close" onclick="closeModal()">✕</button>
        </div>
        <form method="POST" action="simpan_broadcast.php">
            <input type="hidden" name="id" id="inputId">
            <div class="modal-body">
                <div class="field">
                    <label>Judul Broadcast <span style="color:red">*</span></label>
                    <input type="text" name="judul" id="inputJudul" placeholder="Contoh: Promo Akhir Tahun!" required>
                </div>
                <div class="field">
                    <label>Target Penerima</label>
                    <select name="target_role" id="inputTarget">
                        <option value="semua">👥 Semua Pengguna</option>
                        <option value="pembeli">🛒 Pembeli</option>
                        <option value="sales">👁️ Sales</option>
                        <option value="admin_asset">✏️ Admin Asset</option>
                        <option value="admin_program">💻 Admin Program</option>
                    </select>
                </div>
                <div class="field">
                    <label>Isi Pesan <span style="color:red">*</span></label>
                    <textarea name="pesan" id="inputPesan" rows="5"
                              style="width:100%;padding:10px 14px;border:1.5px solid #e0e4ec;border-radius:var(--radius-sm);font-family:'DM Sans',sans-serif;font-size:13px;resize:vertical;outline:none;background:var(--cream);"
                              placeholder="Tuliskan isi pesan broadcast..." required></textarea>
                </div>
                <div class="field">
                    <label>No HP / WhatsApp Penerima</label>
                    <div style="display:flex;align-items:center;background:var(--cream);border:1.5px solid #e0e4ec;border-radius:var(--radius-sm);overflow:hidden;transition:border-color .2s;" onfocusin="this.style.borderColor='var(--gold)'" onfocusout="this.style.borderColor='#e0e4ec'">
                        <span style="padding:0 12px;color:var(--muted);font-size:14px;flex-shrink:0;">📱</span>
                        <input type="text" name="no_hp" id="inputNoHp"
                               placeholder="Contoh: 08123456789 (opsional)"
                               style="flex:1;border:none;background:transparent;padding:10px 12px 10px 0;font-size:13px;font-family:'DM Sans',sans-serif;outline:none;">
                    </div>
                    <small style="color:var(--muted);font-size:11px;margin-top:4px;display:block;">💡 Jika diisi, akan muncul tombol kirim via WhatsApp</small>
                </div>
                <div class="field">
                    <label>Status</label>
                    <select name="aktif" id="inputAktif">
                        <option value="1">▶ Aktif</option>
                        <option value="0">⏸ Nonaktif</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn-save" id="btnSave">📣 Kirim Broadcast</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal() {
    document.getElementById('modalTitle').textContent = 'Buat Broadcast Baru';
    document.getElementById('btnSave').textContent    = '📣 Kirim Broadcast';
    document.getElementById('inputId').value     = '';
    document.getElementById('inputJudul').value  = '';
    document.getElementById('inputPesan').value  = '';
    document.getElementById('inputTarget').value = 'semua';
    document.getElementById('inputAktif').value  = '1';
    document.getElementById('modalBc').classList.add('open');
}
function openEdit(data) {
    document.getElementById('modalTitle').textContent = 'Edit Broadcast';
    document.getElementById('btnSave').textContent    = '💾 Simpan Perubahan';
    document.getElementById('inputId').value     = data.id;
    document.getElementById('inputJudul').value  = data.judul;
    document.getElementById('inputPesan').value  = data.pesan;
    document.getElementById('inputTarget').value = data.target_role;
    document.getElementById('inputAktif').value  = data.aktif;
    document.getElementById('inputNoHp').value   = data.no_hp || '';
    document.getElementById('modalBc').classList.add('open');
}
function closeModal() { document.getElementById('modalBc').classList.remove('open'); }
document.addEventListener('keydown', e => { if(e.key==='Escape') closeModal(); });
</script>
</body>
</html>