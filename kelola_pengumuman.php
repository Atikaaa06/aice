<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'penjual') {
    header("Location: login.php"); exit;
}
include 'koneksi.php';

// Handle toggle aktif/nonaktif
if (isset($_POST['toggle_id'])) {
    $tid = (int) $_POST['toggle_id'];
    $conn->query("UPDATE pengumuman SET aktif = NOT aktif WHERE id=$tid");
    header("Location: kelola_pengumuman.php?toggled=1"); exit;
}

// Handle hapus
if (isset($_POST['hapus_id'])) {
    $hid = (int) $_POST['hapus_id'];
    // Hapus file gambar juga
    $row = $conn->query("SELECT gambar FROM pengumuman WHERE id=$hid")->fetch_assoc();
    if ($row && $row['gambar'] && file_exists('uploads/pengumuman/' . $row['gambar'])) {
        unlink('uploads/pengumuman/' . $row['gambar']);
    }
    $conn->query("DELETE FROM pengumuman WHERE id=$hid");
    header("Location: kelola_pengumuman.php?deleted=1"); exit;
}

$list = $conn->query("SELECT * FROM pengumuman ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
$totalAktif = count(array_filter($list, fn($p) => $p['aktif']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengumuman — PT Juma Tiga</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .p-list-card {
            background: var(--white); border-radius: var(--radius);
            box-shadow: var(--shadow-sm); overflow: hidden;
            margin-bottom: 14px; display: flex;
            transition: box-shadow 0.2s; animation: fadeUp 0.4s ease both;
            border-left: 4px solid transparent;
        }
        .p-list-card:hover { box-shadow: var(--shadow-md); }
        .p-list-card.diskon  { border-left-color: #e53e3e; }
        .p-list-card.promo   { border-left-color: #16a34a; }
        .p-list-card.info    { border-left-color: #1d4ed8; }
        .p-list-card.penting { border-left-color: #d97706; }
        .p-list-card.nonaktif { opacity: 0.55; }

        /* Thumbnail gambar di list */
        .p-list-thumb {
            width: 90px; flex-shrink: 0;
            background: var(--cream);
            display: flex; align-items: center; justify-content: center;
            overflow: hidden; position: relative;
        }
        .p-list-thumb img {
            width: 100%; height: 100%;
            object-fit: cover;
        }
        .p-list-thumb .no-img {
            font-size: 28px; color: #ddd;
            display: flex; flex-direction: column; align-items: center; gap: 4px;
        }
        .p-list-thumb .no-img span { font-size: 10px; color: #ccc; }
        .p-list-thumb .pdf-icon {
            font-size: 28px; display: flex; flex-direction: column; align-items: center; gap: 4px;
        }
        .p-list-thumb .pdf-icon span { font-size: 9px; color: #e53e3e; font-weight: 700; }

        .p-list-body { flex: 1; padding: 16px 20px; min-width: 0; }
        .p-list-top  { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 6px; }
        .p-list-judul { font-family: 'Playfair Display', serif; font-size: 16px; color: var(--navy); }
        .p-list-isi   { font-size: 13px; color: var(--muted); line-height: 1.6; }
        .p-list-meta  { font-size: 11px; color: #bbb; margin-top: 8px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

        .file-tag {
            display: inline-flex; align-items: center; gap: 4px;
            background: #eff6ff; color: #1d4ed8; border: 1px solid #93c5fd;
            padding: 2px 8px; border-radius: 50px; font-size: 10px; font-weight: 600;
            text-decoration: none;
        }
        .file-tag:hover { background: #dbeafe; }

        .p-list-actions { padding: 14px 16px; display: flex; flex-direction: column; gap: 6px; justify-content: center; flex-shrink: 0; }
        .btn-toggle-on  { background:#f0fdf4; color:#16a34a; border:1px solid #86efac; }
        .btn-toggle-off { background:#fdf0ef; color:#c0392b; border:1px solid #fca5a5; }
        .btn-del-p      { background:#f9fafb; color:var(--muted); border:1px solid #e0e4ec; }
        .act-sm { padding:5px 12px; border-radius:6px; font-size:11px; font-weight:600; cursor:pointer; font-family:'DM Sans',sans-serif; white-space:nowrap; transition:opacity 0.2s; }
        .act-sm:hover { opacity:0.75; }

        .tipe-chip { padding:3px 10px; border-radius:50px; font-size:10px; font-weight:700; letter-spacing:0.5px; }
        .chip-diskon  { background:#fef2f2; color:#b91c1c; border:1px solid #fca5a5; }
        .chip-promo   { background:#f0fdf4; color:#15803d; border:1px solid #86efac; }
        .chip-info    { background:#eff6ff; color:#1e40af; border:1px solid #93c5fd; }
        .chip-penting { background:#fffbeb; color:#b45309; border:1px solid #fcd34d; }

        /* Modal */
        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(15,30,60,0.55); z-index:200; align-items:center; justify-content:center; padding:20px; }
        .modal-overlay.open { display:flex; animation:fadeIn .2s ease; }
        @keyframes fadeIn{from{opacity:0}to{opacity:1}}
        .modal { background:var(--white); border-radius:var(--radius); width:100%; max-width:560px; max-height:92vh; overflow-y:auto; box-shadow:var(--shadow-lg); animation:slideIn .3s cubic-bezier(.22,.68,0,1.2) both; }
        .modal-header { background:var(--navy); padding:20px 26px; display:flex; justify-content:space-between; align-items:center; }
        .modal-header h3 { font-family:'Playfair Display',serif; color:var(--white); font-size:18px; }
        .modal-close { background:none; border:none; color:rgba(255,255,255,.6); font-size:22px; cursor:pointer; }
        .modal-close:hover { color:white; }
        .modal-body { padding:26px; }
        .modal-footer { padding:16px 26px; border-top:1px solid #f0f2f7; display:flex; justify-content:flex-end; gap:10px; }
        .btn-cancel { padding:10px 20px; background:transparent; color:var(--muted); border:1.5px solid #e0e4ec; border-radius:var(--radius-sm); font-family:'DM Sans',sans-serif; font-size:14px; cursor:pointer; }
        .btn-save   { padding:10px 24px; background:var(--navy); color:white; border:none; border-radius:var(--radius-sm); font-family:'DM Sans',sans-serif; font-size:14px; font-weight:600; cursor:pointer; }

        .tipe-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:8px; margin-top:6px; }
        .tipe-opt  { border:1.5px solid #e0e4ec; border-radius:8px; padding:10px 6px; text-align:center; cursor:pointer; transition:all .15s; }
        .tipe-opt input { display:none; }
        .tipe-opt:has(input:checked) { border-color:var(--gold); background:var(--cream); }
        .tipe-opt .tipe-lbl { font-size:12px; font-weight:600; color:var(--navy); margin-top:4px; display:block; }
        .tipe-opt .tipe-ico { font-size:22px; display:block; }

        /* Upload zone */
        .upload-zone {
            border: 2px dashed #d0d5e0;
            border-radius: var(--radius-sm);
            padding: 24px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: var(--cream);
            position: relative;
        }
        .upload-zone:hover, .upload-zone.dragover {
            border-color: var(--gold);
            background: #fefdf7;
        }
        .upload-zone input[type="file"] {
            position: absolute; inset: 0;
            opacity: 0; cursor: pointer; width: 100%; height: 100%;
        }
        .upload-icon { font-size: 32px; margin-bottom: 8px; }
        .upload-text { font-size: 13px; color: var(--navy); font-weight: 600; }
        .upload-sub  { font-size: 11px; color: var(--muted); margin-top: 3px; }

        /* Preview gambar di modal */
        .preview-wrap {
            margin-top: 12px;
            display: none;
        }
        .preview-wrap.show { display: block; }
        .preview-img {
            width: 100%; max-height: 180px;
            object-fit: cover;
            border-radius: var(--radius-sm);
            border: 1.5px solid #e0e4ec;
        }
        .preview-name {
            font-size: 12px; color: var(--muted); margin-top: 6px;
            display: flex; align-items: center; gap: 6px;
        }
        .btn-remove-preview {
            background: #fdf0ef; color: #c0392b; border: none;
            padding: 3px 8px; border-radius: 4px;
            font-size: 11px; cursor: pointer; font-family: 'DM Sans', sans-serif;
        }

        /* Gambar existing di edit */
        .existing-img-wrap {
            margin-bottom: 14px;
            padding: 12px;
            background: var(--cream);
            border-radius: var(--radius-sm);
            border: 1.5px solid #e0e4ec;
        }
        .existing-img-wrap p { font-size: 11px; font-weight: 600; color: var(--muted); letter-spacing: 1px; text-transform: uppercase; margin-bottom: 8px; }
        .existing-img-wrap img { width: 100%; max-height: 140px; object-fit: cover; border-radius: 6px; }
        .existing-pdf { display: flex; align-items: center; gap: 8px; padding: 10px; background: #eff6ff; border-radius: 6px; font-size: 13px; }
        .hapus-gambar-row { display: flex; align-items: center; gap: 8px; margin-top: 8px; font-size: 12px; color: var(--muted); }
        .hapus-gambar-row input { width: auto; }

        @media (max-width: 640px) {
            .p-list-card { flex-wrap: wrap; }
            .p-list-thumb { width: 100%; height: 140px; }
            .p-list-actions { flex-direction: row; padding: 0 16px 14px; flex-wrap: wrap; }
            .tipe-grid { grid-template-columns: repeat(2,1fr); }
        }
    </style>
</head>
<body>

<?php $navActive = 'pengumuman'; include 'navbar.php'; ?>
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
    <div class="breadcrumb-trail"><a href="dashboard.php">Dashboard</a><span class="sep">›</span><span class="current">Pengumuman</span></div>
</div>


<div class="page">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:28px;animation:fadeUp .4s ease both;">
        <div class="page-header" style="margin-bottom:0">
            <h1>📢 Kelola Pengumuman</h1>
            <p>Buat dan atur pengumuman yang tampil di halaman profil</p>
        </div>
        <button class="btn btn-primary" style="width:auto;padding:11px 22px;margin-top:6px;" onclick="openModal()">
            + Buat Pengumuman
        </button>
    </div>

    <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success" style="margin-bottom:20px;">✅ Pengumuman berhasil disimpan.</div>
    <?php elseif (isset($_GET['deleted'])): ?>
        <div class="alert alert-error" style="margin-bottom:20px;">🗑️ Pengumuman berhasil dihapus.</div>
    <?php elseif (isset($_GET['toggled'])): ?>
        <div class="alert alert-success" style="margin-bottom:20px;">🔄 Status berhasil diubah.</div>
    <?php endif; ?>

    <!-- Statistik -->
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:28px;">
        <div class="dash-card" style="animation-delay:.05s">
            <div class="dash-card-label">Total</div>
            <div class="dash-card-value"><?= count($list) ?></div>
        </div>
        <div class="dash-card" style="animation-delay:.1s;border-left-color:#16a34a;">
            <div class="dash-card-label">Aktif</div>
            <div class="dash-card-value"><?= $totalAktif ?></div>
        </div>
        <div class="dash-card" style="animation-delay:.15s;border-left-color:#6b7280;">
            <div class="dash-card-label">Nonaktif</div>
            <div class="dash-card-value"><?= count($list) - $totalAktif ?></div>
        </div>
    </div>

    <!-- List -->
    <?php if (empty($list)): ?>
        <div style="text-align:center;padding:60px;background:var(--white);border-radius:var(--radius);color:var(--muted);">
            <div style="font-size:44px;margin-bottom:10px;">📭</div>
            <p>Belum ada pengumuman.</p>
        </div>
    <?php else: ?>
        <?php
        $icons = ['diskon'=>'🏷️','promo'=>'🎁','info'=>'ℹ️','penting'=>'⚠️'];
        $uploadDir = 'uploads/pengumuman/';
        foreach ($list as $i => $p):
            $delay  = ($i * 0.05) . 's';
            $isPdf  = $p['gambar'] && strtolower(pathinfo($p['gambar'], PATHINFO_EXTENSION)) === 'pdf';
            $isImg  = $p['gambar'] && !$isPdf;
        ?>
        <div class="p-list-card <?= $p['tipe'] ?> <?= !$p['aktif'] ? 'nonaktif' : '' ?>" style="animation-delay:<?= $delay ?>">

            <!-- Thumbnail -->
            <div class="p-list-thumb">
                <?php if ($isImg && file_exists($uploadDir . $p['gambar'])): ?>
                    <img src="<?= $uploadDir . htmlspecialchars($p['gambar']) ?>" alt="gambar">
                <?php elseif ($isPdf): ?>
                    <div class="pdf-icon">📄<span>PDF</span></div>
                <?php else: ?>
                    <div class="no-img"><?= $icons[$p['tipe']] ?? 'ℹ️' ?><span>No img</span></div>
                <?php endif; ?>
            </div>

            <div class="p-list-body">
                <div class="p-list-top">
                    <div>
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                            <span class="tipe-chip chip-<?= $p['tipe'] ?>"><?= strtoupper($p['tipe']) ?></span>
                            <?php if ($p['badge']): ?><span style="font-size:10px;font-weight:700;color:var(--gold);">· <?= htmlspecialchars($p['badge']) ?></span><?php endif; ?>
                            <?php if (!$p['aktif']): ?><span style="font-size:10px;color:var(--muted);">· NONAKTIF</span><?php endif; ?>
                        </div>
                        <div class="p-list-judul"><?= htmlspecialchars($p['judul']) ?></div>
                    </div>
                </div>
                <div class="p-list-isi"><?= htmlspecialchars($p['isi']) ?></div>
                <div class="p-list-meta">
                    📅 <?= date('d M Y · H:i', strtotime($p['created_at'])) ?>
                    <?php if ($p['gambar']): ?>
                        <a href="<?= $uploadDir . htmlspecialchars($p['gambar']) ?>" target="_blank" class="file-tag">
                            <?= $isPdf ? '📄' : '🖼️' ?> Lihat File
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="p-list-actions">
                <form method="POST" style="margin:0">
                    <input type="hidden" name="toggle_id" value="<?= $p['id'] ?>">
                    <button type="submit" class="act-sm <?= $p['aktif'] ? 'btn-toggle-off' : 'btn-toggle-on' ?>">
                        <?= $p['aktif'] ? '⏸ Nonaktif' : '▶ Aktifkan' ?>
                    </button>
                </form>
                <button class="act-sm" style="background:#eff6ff;color:#1d4ed8;border:1px solid #93c5fd;"
                    onclick="openEdit(<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>)">
                    ✏️ Edit
                </button>
                <form method="POST" style="margin:0" onsubmit="return confirm('Hapus pengumuman ini?')">
                    <input type="hidden" name="hapus_id" value="<?= $p['id'] ?>">
                    <button type="submit" class="act-sm btn-del-p">🗑️ Hapus</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<p class="footer">© <?= date('Y') ?> PT Juma Tiga</p>

<!-- ── MODAL ─────────────────────────────────── -->
<div class="modal-overlay" id="modalOverlay" onclick="if(event.target===this)closeModal()">
    <div class="modal">
        <div class="modal-header">
            <h3 id="modalTitle">Buat Pengumuman</h3>
            <button class="modal-close" onclick="closeModal()">✕</button>
        </div>
        <form method="POST" action="simpan_pengumuman.php" enctype="multipart/form-data" id="formPengumuman">
            <input type="hidden" name="id" id="inputId">
            <div class="modal-body">

                <!-- Tipe -->
                <div class="field">
                    <label>Tipe Pengumuman</label>
                    <div class="tipe-grid">
                        <label class="tipe-opt"><input type="radio" name="tipe" value="diskon" id="t1" checked><span class="tipe-ico">🏷️</span><span class="tipe-lbl">Diskon</span></label>
                        <label class="tipe-opt"><input type="radio" name="tipe" value="promo"  id="t2"><span class="tipe-ico">🎁</span><span class="tipe-lbl">Promo</span></label>
                        <label class="tipe-opt"><input type="radio" name="tipe" value="info"   id="t3"><span class="tipe-ico">ℹ️</span><span class="tipe-lbl">Info</span></label>
                        <label class="tipe-opt"><input type="radio" name="tipe" value="penting" id="t4"><span class="tipe-ico">⚠️</span><span class="tipe-lbl">Penting</span></label>
                    </div>
                </div>

                <!-- Judul -->
                <div class="field" style="margin-top:16px;">
                    <label>Judul <span style="color:red">*</span></label>
                    <input type="text" name="judul" id="inputJudul" placeholder="Judul pengumuman" required>
                </div>

                <!-- Isi -->
                <div class="field">
                    <label>Isi Pengumuman <span style="color:red">*</span></label>
                    <textarea name="isi" id="inputIsi" rows="4" required
                        placeholder="Tulis detail pengumuman..."
                        style="width:100%;padding:12px 16px;border:1.5px solid #e0e4ec;border-radius:8px;
                               font-family:'DM Sans',sans-serif;font-size:14px;resize:vertical;outline:none;
                               background:var(--cream);transition:border-color .2s;"
                        onfocus="this.style.borderColor='var(--gold)'" onblur="this.style.borderColor='#e0e4ec'"></textarea>
                </div>

                <!-- Badge -->
                <div class="field">
                    <label>Badge <span style="font-weight:300;text-transform:none;letter-spacing:0">(opsional)</span></label>
                    <input type="text" name="badge" id="inputBadge" placeholder="Contoh: HOT, NEW, LIMITED" maxlength="20">
                </div>

                <!-- Upload Gambar/File -->
                <div class="field">
                    <label>Lampiran Gambar / File <span style="font-weight:300;text-transform:none;letter-spacing:0">(opsional · maks 5MB)</span></label>

                    <!-- Existing image saat edit -->
                    <div id="existingImgWrap" class="existing-img-wrap" style="display:none;">
                        <p>File saat ini</p>
                        <div id="existingImgContent"></div>
                        <div class="hapus-gambar-row">
                            <input type="checkbox" name="hapus_gambar" id="hapusGambar" value="1"
                                onchange="toggleHapusGambar(this)">
                            <label for="hapusGambar" style="cursor:pointer;">Hapus gambar/file ini</label>
                        </div>
                    </div>

                    <!-- Upload zone -->
                    <div class="upload-zone" id="uploadZone"
                        ondragover="this.classList.add('dragover')"
                        ondragleave="this.classList.remove('dragover')"
                        ondrop="this.classList.remove('dragover')">
                        <input type="file" name="gambar" id="inputFile" accept="image/*,.pdf"
                            onchange="previewFile(this)">
                        <div id="uploadPrompt">
                            <div class="upload-icon">📎</div>
                            <div class="upload-text">Klik atau seret file ke sini</div>
                            <div class="upload-sub">JPG, PNG, GIF, WEBP, PDF · Maks 5MB</div>
                        </div>
                        <div class="preview-wrap" id="previewWrap">
                            <img id="previewImg" class="preview-img" src="" alt="" style="display:none;">
                            <div id="previewPdf" style="display:none;padding:12px;background:#eff6ff;border-radius:6px;font-size:13px;color:#1d4ed8;">📄 <span id="previewPdfName"></span></div>
                            <div class="preview-name">
                                <span id="previewFileName"></span>
                                <button type="button" class="btn-remove-preview" onclick="clearFile()">✕ Hapus</button>
                            </div>
                        </div>
                    </div>
                    <p style="font-size:11px;color:var(--muted);margin-top:6px;">
                        💡 Gambar akan ditampilkan sebagai thumbnail di daftar pengumuman dan pada timeline profil.
                    </p>
                </div>

                <!-- Status -->
                <div class="field">
                    <label>Status</label>
                    <select name="aktif" id="inputAktif">
                        <option value="1">▶ Aktif (tampil di halaman profil)</option>
                        <option value="0">⏸ Nonaktif (tersimpan tapi tidak tampil)</option>
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

<script>
const uploadDir = 'uploads/pengumuman/';

function openModal() {
    document.getElementById('modalTitle').textContent = 'Buat Pengumuman';
    document.getElementById('btnSave').textContent    = 'Simpan';
    document.getElementById('inputId').value    = '';
    document.getElementById('inputJudul').value = '';
    document.getElementById('inputIsi').value   = '';
    document.getElementById('inputBadge').value = '';
    document.getElementById('inputAktif').value = '1';
    document.getElementById('t1').checked = true;
    document.getElementById('existingImgWrap').style.display = 'none';
    clearFile();
    document.getElementById('modalOverlay').classList.add('open');
}

function openEdit(data) {
    document.getElementById('modalTitle').textContent = 'Edit Pengumuman';
    document.getElementById('btnSave').textContent    = 'Update';
    document.getElementById('inputId').value    = data.id;
    document.getElementById('inputJudul').value = data.judul;
    document.getElementById('inputIsi').value   = data.isi;
    document.getElementById('inputBadge').value = data.badge || '';
    document.getElementById('inputAktif').value = data.aktif;
    const r = document.querySelector('input[name="tipe"][value="'+data.tipe+'"]');
    if (r) r.checked = true;
    clearFile();
    document.getElementById('hapusGambar').checked = false;

    // Tampilkan gambar existing
    const wrap = document.getElementById('existingImgWrap');
    const content = document.getElementById('existingImgContent');
    if (data.gambar) {
        wrap.style.display = 'block';
        const ext = data.gambar.split('.').pop().toLowerCase();
        if (ext === 'pdf') {
            content.innerHTML = `<div class="existing-pdf">📄 <a href="${uploadDir+data.gambar}" target="_blank" style="color:#1d4ed8;">${data.gambar}</a></div>`;
        } else {
            content.innerHTML = `<img src="${uploadDir+data.gambar}" style="width:100%;max-height:140px;object-fit:cover;border-radius:6px;">`;
        }
    } else {
        wrap.style.display = 'none';
    }
    document.getElementById('modalOverlay').classList.add('open');
}

function closeModal() { document.getElementById('modalOverlay').classList.remove('open'); }
document.addEventListener('keydown', e => { if(e.key==='Escape') closeModal(); });

// Preview file sebelum upload
function previewFile(input) {
    const file = input.files[0];
    if (!file) return;
    const wrap   = document.getElementById('previewWrap');
    const prompt = document.getElementById('uploadPrompt');
    const img    = document.getElementById('previewImg');
    const pdf    = document.getElementById('previewPdf');
    const fname  = document.getElementById('previewFileName');
    const pdfName= document.getElementById('previewPdfName');

    wrap.classList.add('show');
    prompt.style.display = 'none';
    fname.textContent = file.name + ' (' + (file.size/1024).toFixed(1) + ' KB)';

    const ext = file.name.split('.').pop().toLowerCase();
    if (ext === 'pdf') {
        img.style.display  = 'none';
        pdf.style.display  = 'block';
        pdfName.textContent = file.name;
    } else {
        const reader = new FileReader();
        reader.onload = e => {
            img.src = e.target.result;
            img.style.display = 'block';
            pdf.style.display = 'none';
        };
        reader.readAsDataURL(file);
    }
}

function clearFile() {
    document.getElementById('inputFile').value   = '';
    document.getElementById('previewWrap').classList.remove('show');
    document.getElementById('uploadPrompt').style.display = 'block';
    document.getElementById('previewImg').style.display   = 'none';
    document.getElementById('previewImg').src             = '';
    document.getElementById('previewPdf').style.display   = 'none';
    document.getElementById('previewFileName').textContent = '';
}

function toggleHapusGambar(cb) {
    const zone = document.getElementById('uploadZone');
    zone.style.opacity = cb.checked ? '0.4' : '1';
    zone.style.pointerEvents = cb.checked ? 'none' : 'auto';
    if (cb.checked) clearFile();
}
</script>
</body>
</html>