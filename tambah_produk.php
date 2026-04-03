<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
// Normalisasi role
if (isset($_SESSION['role'])) {
    $roleMap = ['admin_assets'=>'admin_asset','admin_programs'=>'admin_program'];
    $_SESSION['role'] = $roleMap[$_SESSION['role']] ?? $_SESSION['role'];
}
if (!isset($_SESSION['username'])) { header("Location: login.php"); exit; }
if (!in_array($_SESSION['role'], ['penjual','admin_program'])) { header("Location: dashboard.php"); exit; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Produk — PT JUMA TIGA SEANTERO</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .form-section { background:white; border-radius:var(--radius); padding:20px 24px; margin-bottom:18px; box-shadow:var(--shadow-sm); }
        .form-section-title { font-family:'Playfair Display',serif; font-size:15px; color:var(--navy); margin-bottom:16px; padding-bottom:10px; border-bottom:2px solid var(--gold); display:flex; align-items:center; gap:8px; }
        .form-grid2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
        .form-grid3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; }
        .full { grid-column:1/-1; }
        .harga-preview { font-size:12px; color:var(--green); margin-top:4px; font-weight:600; }
        .upload-zone { border:2px dashed #d0d5e0; border-radius:var(--radius-sm); padding:24px; text-align:center; cursor:pointer; transition:all .2s; background:var(--cream); position:relative; }
        .upload-zone:hover,.upload-zone.dragover { border-color:var(--gold); background:#fefdf7; }
        .upload-zone input[type="file"] { position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%; }
        @media(max-width:640px){ .form-grid2,.form-grid3{grid-template-columns:1fr;} }
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
    <div class="breadcrumb-trail"><a href="dashboard.php">Dashboard</a><span class="sep">›</span><a href="produk.php">Produk</a><span class="sep">›</span><span class="current">Tambah Produk</span></div>
</div>

<div class="page-sm" style="max-width:720px;">
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error" style="margin-bottom:16px;">⚠️ Gagal menyimpan produk. Coba lagi.</div>
    <?php endif; ?>

    <form method="POST" action="simpan_produk.php" enctype="multipart/form-data">

        <!-- Info Dasar -->
        <div class="form-section">
            <div class="form-section-title">📦 Informasi Dasar</div>
            <div class="form-grid2">
                <div class="field full">
                    <label>Nama Produk <span style="color:red">*</span></label>
                    <input type="text" name="nama_produk" placeholder="Contoh: Aice Ice Cream Cone" required>
                </div>
                <div class="field">
                    <label>Kategori</label>
                    <select name="kategori">
                        <option value="Umum">📦 Umum</option>
                        <option value="Cone">🍦 Cone</option>
                        <option value="Paket Keluarga">👨‍👩‍👧 Paket Keluarga</option>
                        <option value="Gelas">🥤 Gelas</option>
                        <option value="Premium">👑 Premium</option>
                        <option value="Es Krim">🍨 Es Krim</option>
                        <option value="Minuman">🧃 Minuman</option>
                        <option value="Makanan">🍱 Makanan</option>
                        <option value="Snack">🍿 Snack</option>
                    </select>
                </div>
                <div class="field">
                    <label>Deskripsi Produk</label>
                    <input type="text" name="deskripsi" placeholder="Keterangan singkat produk">
                </div>
            </div>
        </div>

        <!-- Harga & Kemasan -->
        <div class="form-section">
            <div class="form-section-title">💰 Harga &amp; Kemasan</div>
            <div class="form-grid2">
                <div class="field">
                    <label>Harga Eceran (Rp) <span style="color:red">*</span></label>
                    <input type="number" name="harga_eceren" id="hargaEceren"
                           placeholder="Contoh: 5000" min="0"
                           oninput="previewHarga('hargaEceren','prevEceren')" required>
                    <div class="harga-preview" id="prevEceren"></div>
                    <small style="color:var(--muted);font-size:11px;">💡 Harga ini yang ditampilkan ke publik</small>
                </div>
                <div class="field">
                    <label>Harga Per Dus (Rp)</label>
                    <input type="number" name="harga_per_dus" id="hargaDus"
                           placeholder="Contoh: 120000" min="0"
                           oninput="previewHarga('hargaDus','prevDus')">
                    <div class="harga-preview" id="prevDus"></div>
                </div>
                <div class="field">
                    <label>Isi Per Dus (pcs)</label>
                    <input type="number" name="isi_per_dus" placeholder="Contoh: 24" min="1">
                </div>
                <div class="field">
                    <label>Stok</label>
                    <input type="number" name="stok" placeholder="Contoh: 100" min="0" value="0">
                </div>
            </div>
        </div>

        <!-- Spesifikasi -->
        <div class="form-section">
            <div class="form-section-title">📐 Spesifikasi Satuan</div>
            <div class="form-grid3">
                <div class="field">
                    <label>Volume / ML per Satuan</label>
                    <input type="text" name="ml_per_satuan" placeholder="Contoh: 75ml">
                </div>
                <div class="field">
                    <label>Berat per Satuan</label>
                    <input type="text" name="berat_satuan" placeholder="Contoh: 65gr">
                </div>
                <div class="field">
                    <label>Harga (diisi otomatis)</label>
                    <input type="number" name="harga" id="hargaHidden" placeholder="Sama dgn harga eceran" min="0">
                    <small style="color:var(--muted);font-size:11px;">Otomatis = harga eceran</small>
                </div>
            </div>
        </div>

        <!-- Foto Produk -->
        <div class="form-section">
            <div class="form-section-title">🖼️ Foto Produk</div>
            <div class="upload-zone" id="uploadZone"
                 ondragover="this.classList.add('dragover')"
                 ondragleave="this.classList.remove('dragover')">
                <input type="file" name="gambar" id="inputGambar"
                       accept="image/jpeg,image/png,image/webp,image/gif"
                       onchange="previewGambar(this)">
                <div id="uploadPrompt">
                    <div style="font-size:36px;margin-bottom:8px;">🖼️</div>
                    <div style="font-size:14px;font-weight:600;color:var(--navy);">Klik atau seret foto produk</div>
                    <div style="font-size:12px;color:var(--muted);margin-top:4px;">JPG, PNG, WEBP · Maks 2MB</div>
                </div>
                <div id="previewWrap" style="display:none;">
                    <img id="previewImg" style="width:100%;max-height:200px;object-fit:cover;border-radius:8px;display:block;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-top:8px;">
                        <span id="previewName" style="font-size:12px;color:var(--muted);"></span>
                        <button type="button" onclick="hapusPreview()"
                                style="background:#fdf0ef;color:#c0392b;border:none;padding:4px 10px;border-radius:6px;font-size:11px;cursor:pointer;font-family:inherit;font-weight:600;">✕ Hapus</button>
                    </div>
                </div>
            </div>
        </div>

        <div style="display:flex;gap:12px;">
            <button type="submit" class="btn btn-primary" style="flex:1;">✅ Simpan Produk</button>
            <a href="produk.php" class="btn" style="flex:1;background:var(--cream);color:var(--navy);border:1.5px solid #e0e4ec;text-align:center;text-decoration:none;display:flex;align-items:center;justify-content:center;">Batal</a>
        </div>
    </form>
</div>

<p class="footer">© <?= date('Y') ?> PT JUMA TIGA SEANTERO</p>

<script>
function previewHarga(inputId, previewId) {
    const val = parseInt(document.getElementById(inputId).value) || 0;
    const el  = document.getElementById(previewId);
    el.textContent = val > 0 ? '→ Rp ' + val.toLocaleString('id-ID') : '';
    // Sync ke harga hidden jika eceren
    if (inputId === 'hargaEceren') {
        document.getElementById('hargaHidden').value = val || '';
    }
}
function previewGambar(input) {
    const file = input.files[0];
    if (!file) return;
    if (file.size > 2*1024*1024) { alert('Maksimal 2MB!'); input.value=''; return; }
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('previewImg').src = e.target.result;
        document.getElementById('previewName').textContent = file.name + ' (' + (file.size/1024).toFixed(1) + ' KB)';
        document.getElementById('uploadPrompt').style.display = 'none';
        document.getElementById('previewWrap').style.display  = 'block';
    };
    reader.readAsDataURL(file);
}
function hapusPreview() {
    document.getElementById('inputGambar').value = '';
    document.getElementById('previewWrap').style.display  = 'none';
    document.getElementById('uploadPrompt').style.display = 'block';
}
</script>
</body>
</html>