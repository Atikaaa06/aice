<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'penjual') {
    header("Location: login.php"); exit;
}
include 'koneksi.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: produk.php"); exit;
}

$id   = (int) $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM produk WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$produk = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$produk) {
    header("Location: produk.php"); exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Produk — PT Juma Tiga</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php $navActive = 'produk'; include 'navbar.php'; ?>
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
    <a href="produk.php" class="back-btn-global">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15,18 9,12 15,6"/></svg>
        ← Kembali ke Produk
    </a>
    <div class="breadcrumb-trail"><a href="dashboard.php">Dashboard</a><span class="sep">›</span><a href="produk.php">Produk</a><span class="sep">›</span><span class="current">Edit Produk</span></div>
</div>


<div class="page-sm">
    <div class="card animate">
        <div class="card-header">
            <div class="card-header-icon">✏️</div>
            <div>
                <h2>Edit Produk</h2>
                <p>Perbarui informasi produk #<?= $id ?></p>
            </div>
        </div>
        <div class="card-body">

            <!-- Preview produk saat ini -->
            <div style="background:var(--cream);border-radius:var(--radius-sm);padding:14px 18px;margin-bottom:22px;border:1.5px solid #e8eaf0;display:flex;align-items:center;gap:14px;">
                <span style="font-size:28px;">📦</span>
                <div>
                    <div style="font-size:11px;font-weight:600;letter-spacing:1px;text-transform:uppercase;color:var(--muted);margin-bottom:3px;">Data Saat Ini</div>
                    <div style="font-size:15px;font-weight:600;color:var(--navy);"><?= htmlspecialchars($produk['nama_produk']) ?></div>
                    <div style="font-size:13px;color:var(--success);">Rp <?= number_format($produk['harga'],0,',','.') ?></div>
                </div>
            </div>

            <form method="POST" action="update_produk.php" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= $produk['id'] ?>">

                <div class="field">
                    <label>Nama Produk <span style="color:red">*</span></label>
                    <input type="text" name="nama_produk"
                        value="<?= htmlspecialchars($produk['nama_produk']) ?>"
                        placeholder="Nama produk" required>
                </div>

                <div class="field">
                    <label>Kategori</label>
                    <?php
                    $katOptions = ['Umum','Cone','Paket Keluarga','Gelas','Premium','Es Krim','Minuman','Makanan','Snack'];
                    $katIcons   = ['Umum'=>'📦','Cone'=>'🍦','Paket Keluarga'=>'👨‍👩‍👧','Gelas'=>'🥤','Premium'=>'👑','Es Krim'=>'🍨','Minuman'=>'🧃','Makanan'=>'🍱','Snack'=>'🍿'];
                    $katSel     = $produk['kategori'] ?? 'Umum';
                    ?>
                    <select name="kategori">
                        <?php foreach($katOptions as $ko): ?>
                            <option value="<?= $ko ?>" <?= $katSel===$ko?'selected':'' ?>>
                                <?= ($katIcons[$ko]??'📦') . ' ' . $ko ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label>Harga (Rp) <span style="color:red">*</span></label>
                    <input type="number" name="harga"
                        value="<?= $produk['harga'] ?>"
                        placeholder="Harga produk" min="1" required
                        oninput="formatPreview(this.value)">
                    <div id="hargaPreview" style="font-size:12px;color:var(--success);margin-top:5px;font-weight:600;"></div>
                </div>

                <?php if (isset($produk['stok'])): ?>
                <div class="field">
                    <label>Stok</label>
                    <input type="number" name="stok"
                        value="<?= $produk['stok'] ?>"
                        placeholder="Jumlah stok" min="0">
                </div>
                <?php endif; ?>

                <div class="field">
                    <label>Gambar Produk <span style="font-weight:300;text-transform:none;letter-spacing:0">(opsional · maks 2MB)</span></label>

                    <!-- Gambar saat ini -->
                    <?php
                    $hasImgKol = $conn->query("SHOW COLUMNS FROM produk LIKE 'gambar'")->fetch_assoc();
                    $gambarLama = ($hasImgKol && !empty($produk['gambar'])) ? $produk['gambar'] : null;
                    $uploadDir  = 'uploads/produk/';
                    ?>
                    <?php if ($gambarLama && file_exists($uploadDir . $gambarLama)): ?>
                    <div id="gambarLamaWrap" style="background:var(--cream);border:1.5px solid #e0e4ec;border-radius:8px;padding:12px;margin-bottom:12px;">
                        <p style="font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:8px;">Gambar Saat Ini</p>
                        <img src="<?= $uploadDir . htmlspecialchars($gambarLama) ?>"
                             style="width:100%;max-height:180px;object-fit:cover;border-radius:6px;display:block;">
                        <div style="display:flex;align-items:center;gap:8px;margin-top:10px;">
                            <input type="checkbox" name="hapus_gambar" id="hapusGambar" value="1"
                                   onchange="toggleUploadZone(this)">
                            <label for="hapusGambar" style="font-size:13px;color:var(--muted);cursor:pointer;">
                                Hapus gambar ini
                            </label>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Upload zona -->
                    <div class="upload-zone" id="uploadZoneEdit"
                         ondragover="this.classList.add('dragover')"
                         ondragleave="this.classList.remove('dragover')"
                         ondrop="this.classList.remove('dragover')">
                        <input type="file" name="gambar" id="inputGambarEdit"
                               accept="image/jpeg,image/png,image/webp,image/gif"
                               onchange="previewGambarEdit(this)">
                        <div id="uploadPromptEdit">
                            <div style="font-size:32px;margin-bottom:6px;">🖼️</div>
                            <div style="font-size:13px;font-weight:600;color:var(--navy);">
                                <?= $gambarLama ? 'Ganti dengan gambar baru' : 'Klik atau seret gambar ke sini' ?>
                            </div>
                            <div style="font-size:11px;color:var(--muted);margin-top:3px;">JPG, PNG, WEBP · Maks 2MB</div>
                        </div>
                        <div id="previewWrapEdit" style="display:none;">
                            <img id="previewImgEdit" style="width:100%;max-height:180px;object-fit:cover;border-radius:8px;display:block;">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-top:8px;">
                                <span id="previewNameEdit" style="font-size:12px;color:var(--muted);"></span>
                                <button type="button" onclick="hapusPreviewEdit()"
                                        style="background:#fdf0ef;color:#c0392b;border:none;padding:4px 10px;border-radius:6px;font-size:11px;cursor:pointer;font-family:inherit;font-weight:600;">
                                    ✕ Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="divider"></div>

                <div style="display:flex;gap:10px;">
                    <a href="produk.php" class="btn btn-secondary" style="flex:1;justify-content:center;">Batal</a>
                    <button type="submit" class="btn btn-primary" style="flex:2;">💾 Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<p class="footer">© <?= date('Y') ?> PT Juma Tiga</p>

<script>
function formatPreview(val) {
    const num = parseInt(val) || 0;
    const preview = document.getElementById('hargaPreview');
    if (num > 0) {
        preview.textContent = '→ Rp ' + num.toLocaleString('id-ID');
    } else {
        preview.textContent = '';
    }
}
// Jalankan saat load
formatPreview(document.querySelector('input[name="harga"]').value);
</script>

<style>
.upload-zone {
    border: 2px dashed #d0d5e0;
    border-radius: var(--radius-sm);
    padding: 22px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
    background: var(--cream);
    position: relative;
}
.upload-zone:hover, .upload-zone.dragover {
    border-color: var(--gold); background: #fefdf7;
}
.upload-zone input[type="file"] {
    position: absolute; inset: 0;
    opacity: 0; cursor: pointer;
    width: 100%; height: 100%;
}
</style>
<script>
function previewGambarEdit(input) {
    const file = input.files[0];
    if (!file) return;
    if (file.size > 2 * 1024 * 1024) {
        alert('Ukuran file terlalu besar! Maksimal 2MB.');
        input.value = '';
        return;
    }
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('previewImgEdit').src    = e.target.result;
        document.getElementById('previewNameEdit').textContent = file.name + ' (' + (file.size/1024).toFixed(1) + ' KB)';
        document.getElementById('uploadPromptEdit').style.display = 'none';
        document.getElementById('previewWrapEdit').style.display  = 'block';
    };
    reader.readAsDataURL(file);
}
function hapusPreviewEdit() {
    document.getElementById('inputGambarEdit').value = '';
    document.getElementById('previewImgEdit').src = '';
    document.getElementById('previewWrapEdit').style.display  = 'none';
    document.getElementById('uploadPromptEdit').style.display = 'block';
}
function toggleUploadZone(cb) {
    const zone = document.getElementById('uploadZoneEdit');
    zone.style.opacity       = cb.checked ? '0.35' : '1';
    zone.style.pointerEvents = cb.checked ? 'none'  : 'auto';
    if (cb.checked) hapusPreviewEdit();
}
</script>
</body>
</html>