<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['username'])) { header("Location: login.php"); exit; }

// Normalisasi role
if (isset($_SESSION['role'])) {
    $roleMap = ['admin_assets'=>'admin_asset','admin_programs'=>'admin_program'];
    $_SESSION['role'] = $roleMap[$_SESSION['role']] ?? $_SESSION['role'];
}

$role     = $_SESSION['role'];
$username = $_SESSION['username'];

// Hanya penjual dan admin_program yang bisa kelola
$bisaKelola = in_array($role, ['penjual', 'admin_program']);

include 'koneksi.php';

// Handle hapus
if ($bisaKelola && isset($_POST['hapus_id'])) {
    $hid = (int) $_POST['hapus_id'];
    $row = $conn->query("SELECT gambar FROM galeri WHERE id=$hid")->fetch_assoc();
    if ($row && $row['gambar'] && file_exists('uploads/galeri/' . $row['gambar'])) {
        unlink('uploads/galeri/' . $row['gambar']);
    }
    $conn->query("DELETE FROM galeri WHERE id=$hid");
    header("Location: galeri.php?deleted=1"); exit;
}

// Handle toggle aktif
if ($bisaKelola && isset($_POST['toggle_id'])) {
    $tid = (int) $_POST['toggle_id'];
    $conn->query("UPDATE galeri SET aktif = NOT aktif WHERE id=$tid");
    header("Location: galeri.php?toggled=1"); exit;
}

// Filter
$filterKat = trim($_GET['kat'] ?? '');
$where     = ["1=1"];
if ($filterKat) { $where[] = "kategori = '" . $conn->real_escape_string($filterKat) . "'"; }
if (!$bisaKelola) { $where[] = "aktif = 1"; }

$galeris = $conn->query("SELECT * FROM galeri WHERE " . implode(' AND ', $where) . " ORDER BY urutan ASC, created_at DESC")->fetch_all(MYSQLI_ASSOC);
$totalGaleri  = count($galeris);
$totalAktif   = $conn->query("SELECT COUNT(*) as n FROM galeri WHERE aktif=1")->fetch_assoc()['n'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galeri — PT Juma Tiga</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Filter kategori chips */
        .kat-chips { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:24px; }
        .kat-chip {
            padding:7px 16px; border-radius:50px;
            border:1.5px solid #e0e4ec; background:var(--cream);
            font-size:12px; font-weight:600; color:var(--navy);
            text-decoration:none; transition:all .2s;
        }
        .kat-chip:hover { border-color:var(--gold); }
        .kat-chip.active { background:var(--navy); color:white; border-color:var(--navy); }

        /* Grid galeri */
        .galeri-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 20px;
        }
        .galeri-card {
            background: var(--white);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: transform .2s, box-shadow .2s;
            animation: fadeUp .4s ease both;
            cursor: pointer;
        }
        .galeri-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); }
        .galeri-card.nonaktif { opacity: 0.5; }

        .galeri-img-wrap {
            position: relative;
            height: 200px;
            overflow: hidden;
            background: var(--cream);
        }
        .galeri-img-wrap img {
            width: 100%; height: 100%;
            object-fit: cover;
            transition: transform .3s;
            display: block;
        }
        .galeri-card:hover .galeri-img-wrap img { transform: scale(1.05); }

        .galeri-overlay {
            position: absolute; inset: 0;
            background: rgba(15,30,60,0.6);
            display: flex; align-items: center; justify-content: center;
            opacity: 0; transition: opacity .2s;
        }
        .galeri-card:hover .galeri-overlay { opacity: 1; }
        .galeri-overlay span {
            color: white; font-size: 13px; font-weight: 600;
            background: rgba(0,0,0,0.3); padding: 8px 16px;
            border-radius: 50px; border: 1px solid rgba(255,255,255,0.3);
        }

        .galeri-kat-badge {
            position: absolute; top: 10px; left: 10px;
            padding: 3px 10px; border-radius: 50px;
            font-size: 10px; font-weight: 700;
            background: var(--gold); color: var(--navy);
        }
        .galeri-status-badge {
            position: absolute; top: 10px; right: 10px;
            padding: 3px 8px; border-radius: 50px;
            font-size: 10px; font-weight: 700;
        }

        .galeri-body { padding: 14px 16px; }
        .galeri-title {
            font-size: 14px; font-weight: 600;
            color: var(--navy); margin-bottom: 4px;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .galeri-desc {
            font-size: 12px; color: var(--muted);
            line-height: 1.5;
            display: -webkit-box; -webkit-line-clamp: 2;
            -webkit-box-orient: vertical; overflow: hidden;
        }
        .galeri-footer {
            padding: 10px 16px;
            border-top: 1px solid #f0f2f7;
            display: flex; gap: 6px;
        }
        .btn-g {
            padding: 5px 12px; border-radius: 6px;
            font-size: 11px; font-weight: 600;
            border: none; cursor: pointer;
            font-family: 'DM Sans', sans-serif;
            transition: opacity .2s;
        }
        .btn-g:hover { opacity: .75; }
        .btn-g-edit   { background:#eff6ff; color:#1d4ed8; }
        .btn-g-toggle { background:#f0fdf4; color:#16a34a; }
        .btn-g-off    { background:#fdf0ef; color:#c0392b; }
        .btn-g-del    { background:#f9fafb; color:var(--muted); }

        /* Empty state */
        .empty-galeri {
            text-align:center; padding:60px 20px;
            background:var(--white); border-radius:var(--radius);
            color:var(--muted); box-shadow:var(--shadow-sm);
            grid-column: 1/-1;
        }
        .empty-galeri .ei { font-size:48px; margin-bottom:12px; }

        /* Modal tambah/edit */
        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(15,30,60,0.55); z-index:200; align-items:center; justify-content:center; padding:20px; }
        .modal-overlay.open { display:flex; animation:fadeIn .2s ease; }
        @keyframes fadeIn{from{opacity:0}to{opacity:1}}
        .modal { background:var(--white); border-radius:var(--radius); width:100%; max-width:520px; max-height:92vh; overflow-y:auto; box-shadow:var(--shadow-lg); animation:slideIn .3s cubic-bezier(.22,.68,0,1.2) both; }
        .modal-header { background:var(--navy); padding:18px 24px; display:flex; justify-content:space-between; align-items:center; }
        .modal-header h3 { font-family:'Playfair Display',serif; color:var(--white); font-size:17px; }
        .modal-close { background:none; border:none; color:rgba(255,255,255,.6); font-size:22px; cursor:pointer; }
        .modal-close:hover { color:white; }
        .modal-body { padding:24px; }
        .modal-footer { padding:14px 24px; border-top:1px solid #f0f2f7; display:flex; justify-content:flex-end; gap:10px; }
        .btn-cancel { padding:10px 20px; background:transparent; color:var(--muted); border:1.5px solid #e0e4ec; border-radius:var(--radius-sm); font-family:'DM Sans',sans-serif; font-size:14px; cursor:pointer; }
        .btn-save   { padding:10px 24px; background:var(--navy); color:white; border:none; border-radius:var(--radius-sm); font-family:'DM Sans',sans-serif; font-size:14px; font-weight:600; cursor:pointer; }

        /* Upload zone */
        .upload-zone {
            border:2px dashed #d0d5e0; border-radius:var(--radius-sm);
            padding:24px; text-align:center; cursor:pointer;
            transition:all .2s; background:var(--cream); position:relative;
        }
        .upload-zone:hover, .upload-zone.dragover { border-color:var(--gold); background:#fefdf7; }
        .upload-zone input[type="file"] { position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%; }

        /* Lightbox */
        .lightbox { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.92); z-index:999; align-items:center; justify-content:center; padding:20px; cursor:zoom-out; }
        .lightbox.open { display:flex; animation:fadeIn .2s ease; }
        .lightbox img { max-width:90vw; max-height:85vh; object-fit:contain; border-radius:8px; box-shadow:0 20px 60px rgba(0,0,0,0.5); cursor:default; }
        .lightbox-info { position:absolute; bottom:30px; left:50%; transform:translateX(-50%); text-align:center; color:white; }
        .lightbox-info h4 { font-size:16px; margin-bottom:4px; }
        .lightbox-info p  { font-size:12px; opacity:.6; }
        .lightbox-close { position:absolute; top:20px; right:24px; color:white; font-size:28px; background:none; border:none; cursor:pointer; opacity:.7; transition:opacity .2s; }
        .lightbox-close:hover { opacity:1; }
        .lightbox-nav { position:absolute; top:50%; transform:translateY(-50%); background:rgba(255,255,255,.15); border:none; color:white; font-size:28px; width:48px; height:48px; border-radius:50%; cursor:pointer; display:flex;align-items:center;justify-content:center; transition:background .2s; }
        .lightbox-nav:hover { background:rgba(255,255,255,.3); }
        .lightbox-nav.prev { left:16px; }
        .lightbox-nav.next { right:16px; }
    </style>
</head>
<body>

<?php $navActive = 'galeri'; include 'navbar.php'; ?>

<style>
.back-bar-global{background:var(--white);border-bottom:1px solid #eef0f7;padding:10px 40px;display:flex;align-items:center;gap:16px;box-shadow:0 1px 4px rgba(15,30,60,0.05);}
.back-btn-global{display:inline-flex;align-items:center;gap:7px;color:var(--navy);text-decoration:none;font-size:13px;font-weight:600;padding:6px 16px;border-radius:50px;border:1.5px solid #e0e4ec;background:var(--cream);transition:all .2s;}
.back-btn-global:hover{background:var(--navy);color:white;border-color:var(--navy);}
.breadcrumb-trail{display:flex;align-items:center;gap:6px;font-size:12px;color:var(--muted);}
.breadcrumb-trail a{color:var(--muted);text-decoration:none;}
.breadcrumb-trail .sep{color:#ccc;}
.breadcrumb-trail .current{color:var(--navy);font-weight:600;}
</style>
<div class="back-bar-global">
    <a href="dashboard.php" class="back-btn-global">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15,18 9,12 15,6"/></svg>
        ← Kembali ke Dashboard
    </a>
    <div class="breadcrumb-trail">
        <a href="dashboard.php">Dashboard</a>
        <span class="sep">›</span>
        <span class="current">Galeri</span>
    </div>
</div>

<div class="page">
    <!-- Header -->
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;animation:fadeUp .4s ease both;flex-wrap:wrap;gap:12px;">
        <div class="page-header" style="margin-bottom:0;">
            <h1>🖼️ Galeri</h1>
            <p><?= $bisaKelola ? 'Kelola foto &amp; gambar untuk ditampilkan di landing page' : 'Kumpulan foto &amp; dokumentasi PT Juma Tiga' ?></p>
        </div>
        <?php if ($bisaKelola): ?>
            <button class="btn btn-primary" style="width:auto;padding:11px 22px;margin-top:6px;" onclick="openModal()">
                + Tambah Foto
            </button>
        <?php endif; ?>
    </div>

    <!-- Alert -->
    <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success" style="margin-bottom:20px;">✅ Foto berhasil disimpan ke galeri.</div>
    <?php elseif (isset($_GET['deleted'])): ?>
        <div class="alert alert-error" style="margin-bottom:20px;">🗑️ Foto berhasil dihapus.</div>
    <?php elseif (isset($_GET['toggled'])): ?>
        <div class="alert alert-success" style="margin-bottom:20px;">🔄 Status foto berhasil diubah.</div>
    <?php endif; ?>

    <!-- Statistik (hanya admin) -->
    <?php if ($bisaKelola): ?>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px;">
        <div class="dash-card" style="animation-delay:.05s;">
            <div class="dash-card-label">Total Foto</div>
            <div class="dash-card-value"><?= $totalGaleri ?></div>
        </div>
        <div class="dash-card" style="animation-delay:.1s;border-left-color:#16a34a;">
            <div class="dash-card-label">Ditampilkan</div>
            <div class="dash-card-value"><?= $totalAktif ?></div>
        </div>
        <div class="dash-card" style="animation-delay:.15s;border-left-color:#6b7280;">
            <div class="dash-card-label">Disembunyikan</div>
            <div class="dash-card-value"><?= $totalGaleri - $totalAktif ?></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filter Kategori -->
    <div class="kat-chips">
        <a href="galeri.php" class="kat-chip <?= !$filterKat?'active':'' ?>">🏷️ Semua</a>
        <a href="galeri.php?kat=produk"    class="kat-chip <?= $filterKat==='produk'?'active':'' ?>">📦 Produk</a>
        <a href="galeri.php?kat=kegiatan"  class="kat-chip <?= $filterKat==='kegiatan'?'active':'' ?>">🎉 Kegiatan</a>
        <a href="galeri.php?kat=kantor"    class="kat-chip <?= $filterKat==='kantor'?'active':'' ?>">🏢 Kantor</a>
        <a href="galeri.php?kat=promosi"   class="kat-chip <?= $filterKat==='promosi'?'active':'' ?>">🏷️ Promosi</a>
        <a href="galeri.php?kat=lainnya"   class="kat-chip <?= $filterKat==='lainnya'?'active':'' ?>">📷 Lainnya</a>
    </div>

    <!-- Grid Galeri -->
    <div class="galeri-grid">
        <?php if (empty($galeris)): ?>
            <div class="empty-galeri">
                <div class="ei">🖼️</div>
                <p>Belum ada foto di galeri<?= $filterKat ? ' kategori ini' : '' ?>.</p>
                <?php if ($bisaKelola): ?>
                    <button onclick="openModal()" class="btn btn-primary" style="width:auto;padding:10px 24px;display:inline-flex;margin-top:16px;">+ Tambah Foto Pertama</button>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php foreach ($galeris as $i => $g):
                $delay   = ($i * 0.04) . 's';
                $imgPath = 'uploads/galeri/' . $g['gambar'];
                $katLabel = ['produk'=>'📦 Produk','kegiatan'=>'🎉 Kegiatan','kantor'=>'🏢 Kantor','promosi'=>'🏷️ Promosi','lainnya'=>'📷 Lainnya'];
            ?>
            <div class="galeri-card <?= !$g['aktif'] ? 'nonaktif' : '' ?>"
                 style="animation-delay:<?= $delay ?>"
                 onclick="openLightbox(<?= $i ?>)">
                <div class="galeri-img-wrap">
                    <?php if (file_exists($imgPath)): ?>
                        <img src="<?= $imgPath ?>" alt="<?= htmlspecialchars($g['judul']) ?>" loading="lazy">
                    <?php else: ?>
                        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:48px;color:#ddd;">🖼️</div>
                    <?php endif; ?>
                    <div class="galeri-overlay"><span>🔍 Perbesar</span></div>
                    <span class="galeri-kat-badge"><?= $katLabel[$g['kategori']] ?? '📷' ?></span>
                    <?php if ($bisaKelola && !$g['aktif']): ?>
                        <span class="galeri-status-badge" style="background:#f9fafb;color:var(--muted);">⏸ Nonaktif</span>
                    <?php endif; ?>
                </div>
                <div class="galeri-body">
                    <div class="galeri-title"><?= htmlspecialchars($g['judul']) ?></div>
                    <?php if ($g['deskripsi']): ?>
                        <div class="galeri-desc"><?= htmlspecialchars($g['deskripsi']) ?></div>
                    <?php endif; ?>
                </div>
                <?php if ($bisaKelola): ?>
                <div class="galeri-footer" onclick="event.stopPropagation()">
                    <button class="btn-g btn-g-edit"
                        onclick="openEdit(<?= htmlspecialchars(json_encode($g), ENT_QUOTES) ?>)">✏️ Edit</button>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="toggle_id" value="<?= $g['id'] ?>">
                        <button type="submit" class="btn-g <?= $g['aktif'] ? 'btn-g-off' : 'btn-g-toggle' ?>">
                            <?= $g['aktif'] ? '⏸ Sembunyikan' : '▶ Tampilkan' ?>
                        </button>
                    </form>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus foto ini?')">
                        <input type="hidden" name="hapus_id" value="<?= $g['id'] ?>">
                        <button type="submit" class="btn-g btn-g-del">🗑️</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<p class="footer">© <?= date('Y') ?> PT Juma Tiga</p>

<!-- Modal Tambah/Edit -->
<?php if ($bisaKelola): ?>
<div class="modal-overlay" id="modalGaleri" onclick="if(event.target===this)closeModal()">
    <div class="modal">
        <div class="modal-header">
            <h3 id="modalTitle">Tambah Foto Galeri</h3>
            <button class="modal-close" onclick="closeModal()">✕</button>
        </div>
        <form method="POST" action="simpan_galeri.php" enctype="multipart/form-data">
            <input type="hidden" name="id" id="inputId">
            <div class="modal-body">

                <div class="field">
                    <label>Judul Foto <span style="color:red">*</span></label>
                    <input type="text" name="judul" id="inputJudul" placeholder="Contoh: Kegiatan Packing Produk" required>
                </div>

                <div class="field">
                    <label>Kategori</label>
                    <select name="kategori" id="inputKategori">
                        <option value="produk">📦 Produk</option>
                        <option value="kegiatan">🎉 Kegiatan</option>
                        <option value="kantor">🏢 Kantor</option>
                        <option value="promosi">🏷️ Promosi</option>
                        <option value="lainnya">📷 Lainnya</option>
                    </select>
                </div>

                <div class="field">
                    <label>Deskripsi <span style="font-weight:300;text-transform:none;letter-spacing:0">(opsional)</span></label>
                    <input type="text" name="deskripsi" id="inputDeskripsi" placeholder="Keterangan singkat foto">
                </div>

                <div class="field">
                    <label>Foto <span style="color:red">*</span> <span style="font-weight:300;text-transform:none;letter-spacing:0">· Maks 5MB</span></label>
                    <!-- Existing image (edit mode) -->
                    <div id="existingWrap" style="display:none;margin-bottom:12px;background:var(--cream);border:1.5px solid #e0e4ec;border-radius:8px;padding:12px;">
                        <p style="font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:8px;">Foto Saat Ini</p>
                        <img id="existingImg" src="" style="width:100%;max-height:140px;object-fit:cover;border-radius:6px;">
                        <div style="margin-top:8px;display:flex;align-items:center;gap:8px;">
                            <input type="checkbox" name="hapus_gambar" id="hapusGambar" value="1"
                                   onchange="toggleZone(this)">
                            <label for="hapusGambar" style="font-size:13px;color:var(--muted);cursor:pointer;">Ganti dengan foto baru</label>
                        </div>
                    </div>
                    <!-- Upload zone -->
                    <div class="upload-zone" id="uploadZone"
                         ondragover="this.classList.add('dragover')"
                         ondragleave="this.classList.remove('dragover')">
                        <input type="file" name="gambar" id="inputFile"
                               accept="image/jpeg,image/png,image/webp,image/gif"
                               onchange="previewFoto(this)">
                        <div id="uploadPrompt">
                            <div style="font-size:36px;margin-bottom:8px;">📷</div>
                            <div style="font-size:14px;font-weight:600;color:var(--navy);">Klik atau seret foto ke sini</div>
                            <div style="font-size:12px;color:var(--muted);margin-top:4px;">JPG, PNG, WEBP, GIF · Maks 5MB</div>
                        </div>
                        <div id="previewWrap" style="display:none;">
                            <img id="previewImg" style="width:100%;max-height:200px;object-fit:cover;border-radius:8px;display:block;">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-top:8px;">
                                <span id="previewName" style="font-size:12px;color:var(--muted);"></span>
                                <button type="button" onclick="clearFoto()"
                                        style="background:#fdf0ef;color:#c0392b;border:none;padding:4px 10px;border-radius:6px;font-size:11px;cursor:pointer;font-family:inherit;font-weight:600;">✕</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="field">
                    <label>Status</label>
                    <select name="aktif" id="inputAktif">
                        <option value="1">▶ Aktif (tampil di galeri)</option>
                        <option value="0">⏸ Nonaktif (disembunyikan)</option>
                    </select>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn-save" id="btnSave">Simpan</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Lightbox -->
<div class="lightbox" id="lightbox" onclick="closeLightbox()">
    <button class="lightbox-close" onclick="closeLightbox()">✕</button>
    <button class="lightbox-nav prev" onclick="event.stopPropagation();lightboxNav(-1)">‹</button>
    <div style="display:flex;flex-direction:column;align-items:center;" onclick="event.stopPropagation()">
        <img id="lightboxImg" src="" alt="">
        <div class="lightbox-info">
            <h4 id="lightboxTitle"></h4>
            <p id="lightboxDesc"></p>
        </div>
    </div>
    <button class="lightbox-nav next" onclick="event.stopPropagation();lightboxNav(1)">›</button>
</div>

<script>
// Data galeri untuk lightbox
const galeriData = <?php
    $galeriJs = array_map(function($g) {
        return [
            'img'   => 'uploads/galeri/' . $g['gambar'],
            'judul' => $g['judul'],
            'desc'  => $g['deskripsi'] ?? '',
        ];
    }, $galeris);
    echo json_encode($galeriJs);
?>;

let lbIndex = 0;

function openLightbox(i) {
    lbIndex = i;
    updateLightbox();
    document.getElementById('lightbox').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function updateLightbox() {
    const d = galeriData[lbIndex];
    if (!d) return;
    document.getElementById('lightboxImg').src      = d.img;
    document.getElementById('lightboxTitle').textContent = d.judul;
    document.getElementById('lightboxDesc').textContent  = d.desc;
}
function lightboxNav(dir) {
    lbIndex = (lbIndex + dir + galeriData.length) % galeriData.length;
    updateLightbox();
}
function closeLightbox() {
    document.getElementById('lightbox').classList.remove('open');
    document.body.style.overflow = '';
}

// Modal
function openModal() {
    document.getElementById('modalTitle').textContent = 'Tambah Foto Galeri';
    document.getElementById('btnSave').textContent    = 'Simpan';
    document.getElementById('inputId').value       = '';
    document.getElementById('inputJudul').value    = '';
    document.getElementById('inputDeskripsi').value= '';
    document.getElementById('inputKategori').value = 'lainnya';
    document.getElementById('inputAktif').value    = '1';
    document.getElementById('existingWrap').style.display = 'none';
    clearFoto();
    document.getElementById('modalGaleri').classList.add('open');
}
function openEdit(data) {
    document.getElementById('modalTitle').textContent = 'Edit Foto Galeri';
    document.getElementById('btnSave').textContent    = 'Update';
    document.getElementById('inputId').value        = data.id;
    document.getElementById('inputJudul').value     = data.judul;
    document.getElementById('inputDeskripsi').value = data.deskripsi || '';
    document.getElementById('inputKategori').value  = data.kategori;
    document.getElementById('inputAktif').value     = data.aktif;
    document.getElementById('hapusGambar').checked  = false;
    clearFoto();
    if (data.gambar) {
        document.getElementById('existingWrap').style.display = 'block';
        document.getElementById('existingImg').src = 'uploads/galeri/' + data.gambar;
        document.getElementById('uploadZone').style.display = 'none';
    }
    document.getElementById('modalGaleri').classList.add('open');
}
function closeModal() { document.getElementById('modalGaleri').classList.remove('open'); }

function toggleZone(cb) {
    const zone = document.getElementById('uploadZone');
    zone.style.display = cb.checked ? 'block' : 'none';
    if (!cb.checked) clearFoto();
}

function previewFoto(input) {
    const file = input.files[0];
    if (!file) return;
    if (file.size > 5*1024*1024) { alert('Ukuran file maksimal 5MB!'); input.value=''; return; }
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('previewImg').src = e.target.result;
        document.getElementById('previewName').textContent = file.name + ' (' + (file.size/1024).toFixed(1) + ' KB)';
        document.getElementById('uploadPrompt').style.display = 'none';
        document.getElementById('previewWrap').style.display  = 'block';
    };
    reader.readAsDataURL(file);
}
function clearFoto() {
    document.getElementById('inputFile').value = '';
    document.getElementById('previewImg').src  = '';
    document.getElementById('previewWrap').style.display  = 'none';
    document.getElementById('uploadPrompt').style.display = 'block';
}

// Keyboard
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { closeModal(); closeLightbox(); }
    if (document.getElementById('lightbox').classList.contains('open')) {
        if (e.key === 'ArrowLeft')  lightboxNav(-1);
        if (e.key === 'ArrowRight') lightboxNav(1);
    }
});
</script>
</body>
</html>