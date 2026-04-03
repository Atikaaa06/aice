<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}
include 'koneksi.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: produk.php");
    exit;
}

$id = (int) $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM produk WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$produk = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$produk) {
    echo "Produk tidak ditemukan!";
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beli Produk — PT Juma Tiga</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Tab system */
        .tab-bar {
            display: flex;
            gap: 0;
            border-bottom: 2px solid #e8eaf0;
            margin-bottom: 28px;
        }
        .tab-btn {
            padding: 12px 24px;
            background: none;
            border: none;
            font-family: 'DM Sans', sans-serif;
            font-size: 13px;
            font-weight: 600;
            color: var(--muted);
            cursor: pointer;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            transition: color 0.2s, border-color 0.2s;
            letter-spacing: 0.3px;
        }
        .tab-btn.active {
            color: var(--navy);
            border-bottom-color: var(--gold);
        }
        .tab-btn:hover { color: var(--navy); }
        .tab-panel { display: none; }
        .tab-panel.active { display: block; animation: fadeUp 0.3s ease both; }

        /* Chat styles */
        .chat-type-bar {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
        }
        .type-chip {
            padding: 6px 14px;
            border-radius: 50px;
            border: 1.5px solid #e0e4ec;
            background: var(--white);
            font-size: 12px;
            font-weight: 600;
            color: var(--muted);
            cursor: pointer;
            transition: all 0.2s;
            font-family: 'DM Sans', sans-serif;
        }
        .type-chip:hover { border-color: var(--gold); color: var(--navy); }
        .type-chip.selected { background: var(--navy); color: var(--white); border-color: var(--navy); }

        .chat-window {
            background: var(--cream);
            border-radius: var(--radius-sm);
            border: 1.5px solid #e8eaf0;
            height: 260px;
            overflow-y: auto;
            padding: 16px;
            margin-bottom: 14px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            scroll-behavior: smooth;
        }
        .chat-window::-webkit-scrollbar { width: 4px; }
        .chat-window::-webkit-scrollbar-thumb { background: #ddd; border-radius: 4px; }

        .chat-bubble {
            max-width: 82%;
            padding: 10px 14px;
            border-radius: 14px;
            font-size: 13px;
            line-height: 1.5;
            animation: fadeUp 0.25s ease both;
        }
        .chat-bubble.me {
            background: var(--navy);
            color: var(--white);
            align-self: flex-end;
            border-bottom-right-radius: 4px;
        }
        .chat-bubble.system {
            background: var(--white);
            color: var(--muted);
            align-self: flex-start;
            border-bottom-left-radius: 4px;
            border: 1px solid #e8eaf0;
            font-style: italic;
            font-size: 12px;
        }
        .chat-bubble .time {
            font-size: 10px;
            opacity: 0.6;
            margin-top: 4px;
            display: block;
        }
        .chat-meta {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.5px;
            opacity: 0.7;
            margin-bottom: 2px;
        }

        .chat-input-row {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }
        .chat-input-row textarea {
            flex: 1;
            padding: 12px 14px;
            border: 1.5px solid #e0e4ec;
            border-radius: 10px;
            font-size: 14px;
            font-family: 'DM Sans', sans-serif;
            resize: none;
            height: 48px;
            outline: none;
            background: var(--white);
            transition: border-color 0.2s;
        }
        .chat-input-row textarea:focus { border-color: var(--gold); }
        .btn-send {
            width: 48px; height: 48px;
            background: var(--navy);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
            flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
        }
        .btn-send:hover { background: var(--navy-mid); }
        .btn-send:active { transform: scale(0.94); }

        /* Pesan order status */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-pending  { background: #fef9ec; color: #92680c; border: 1px solid var(--gold); }
        .status-diproses { background: #eff6ff; color: #1d4ed8; border: 1px solid #93c5fd; }
        .status-selesai  { background: #f0fdf4; color: #16a34a; border: 1px solid #86efac; }
        .status-ditolak  { background: #fdf0ef; color: #c0392b; border: 1px solid #fca5a5; }

        .empty-state {
            text-align: center;
            padding: 32px;
            color: var(--muted);
        }
        .empty-state .empty-icon { font-size: 36px; margin-bottom: 8px; }
        .empty-state p { font-size: 13px; }
    </style>
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
    <div class="breadcrumb-trail"><a href="dashboard.php">Dashboard</a><span class="sep">›</span><a href="produk.php">Produk</a><span class="sep">›</span><span class="current">Beli Produk</span></div>
</div>


<div class="page-sm" style="max-width:580px;">
    <div class="card animate">
        <?php
        $uploadDirBeli = 'uploads/produk/';
        $gambarBeli    = $produk['gambar'] ?? null;
        $hasGambarBeli = $gambarBeli && file_exists($uploadDirBeli . $gambarBeli);
        ?>
        <?php if ($hasGambarBeli): ?>
            <div style="width:100%;height:220px;overflow:hidden;background:var(--cream);">
                <img src="<?= $uploadDirBeli . htmlspecialchars($gambarBeli) ?>"
                     alt="<?= htmlspecialchars($produk['nama_produk']) ?>"
                     style="width:100%;height:100%;object-fit:cover;display:block;">
            </div>
        <?php endif; ?>
        <div class="card-header">
            <div class="card-header-icon">🛒</div>
            <div>
                <h2><?= htmlspecialchars($produk['nama_produk']) ?></h2>
                <p>Rp <?= number_format($produk['harga'], 0, ',', '.') ?> per unit</p>
            </div>
        </div>
        <div class="card-body">

            <!-- TAB BAR -->
            <div class="tab-bar">
                <button class="tab-btn active" onclick="switchTab('beli', this)">🛒 Beli Langsung</button>
                <button class="tab-btn" onclick="switchTab('pesan', this)">📋 Pesan / Order</button>
                <button class="tab-btn" onclick="switchTab('chat', this)">💬 Live Chat</button>
            </div>

            <!-- TAB: BELI LANGSUNG -->
            <div class="tab-panel active" id="tab-beli">
                <div class="field">
                    <label>Nama Produk</label>
                    <div class="info"><?= htmlspecialchars($produk['nama_produk']) ?></div>
                </div>
                <div class="field">
                    <label>Harga Satuan</label>
                    <div class="info">Rp <?= number_format($produk['harga'], 0, ',', '.') ?></div>
                </div>
                <?php if (isset($produk['stok'])): ?>
                <div class="field">
                    <label>Stok Tersedia</label>
                    <div class="info"><?= $produk['stok'] ?> unit</div>
                </div>
                <?php endif; ?>
                <div class="divider"></div>
                <form method="POST" action="proses_beli.php">
                    <input type="hidden" name="id_produk" value="<?= $produk['id'] ?>">
                    <div class="field">
                        <label>Jumlah Beli</label>
                        <input type="number" name="jumlah" id="jumlah" min="1"
                            <?= isset($produk['stok']) ? 'max="'.$produk['stok'].'"' : '' ?>
                            placeholder="Masukkan jumlah" required>
                    </div>
                    <div class="total-box">
                        <span>Estimasi Total</span>
                        <strong id="preview-total">Rp 0</strong>
                    </div>
                    <button type="submit" class="btn btn-primary">✔ Konfirmasi Pembelian</button>
                </form>
            </div>

            <!-- TAB: PESAN / ORDER -->
            <div class="tab-panel" id="tab-pesan">
                <p style="font-size:13px; color:var(--muted); margin-bottom:20px;">
                    Buat pesanan terlebih dahulu. Admin akan memproses dan menghubungi kamu.
                </p>
                <form method="POST" action="proses_pesan.php">
                    <input type="hidden" name="id_produk" value="<?= $produk['id'] ?>">
                    <div class="field">
                        <label>Jumlah Pesanan</label>
                        <input type="number" name="jumlah" min="1" placeholder="Masukkan jumlah" required>
                    </div>
                    <div class="field">
                        <label>Catatan untuk Penjual <span style="font-weight:300;text-transform:none;letter-spacing:0">(opsional)</span></label>
                        <textarea name="catatan" rows="3" placeholder="Contoh: Minta dikirim hari Senin, warna preferensi merah, dll..."
                            style="width:100%;padding:12px 16px;border:1.5px solid #e0e4ec;border-radius:8px;font-family:'DM Sans',sans-serif;font-size:14px;resize:vertical;outline:none;background:var(--cream);"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">📋 Kirim Pesanan</button>
                </form>

                <?php
                // Tampilkan riwayat pesanan user ini untuk produk ini
                $u = $_SESSION['username'];
                $stmt2 = $conn->prepare("SELECT * FROM pesan_order WHERE username=? AND id_produk=? ORDER BY created_at DESC LIMIT 5");
                $stmt2->bind_param("si", $u, $id);
                $stmt2->execute();
                $orders = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt2->close();
                if (!empty($orders)):
                ?>
                <div class="divider"></div>
                <p style="font-size:11px;font-weight:600;letter-spacing:1px;text-transform:uppercase;color:var(--muted);margin-bottom:12px;">Riwayat Pesanan</p>
                <?php foreach ($orders as $o): ?>
                    <div style="background:var(--cream);border-radius:8px;padding:14px;margin-bottom:10px;border:1px solid #e8eaf0;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                            <span style="font-size:13px;font-weight:600;">Pesanan #<?= $o['id'] ?> &mdash; <?= $o['jumlah'] ?> unit</span>
                            <span class="status-badge status-<?= $o['status'] ?>">
                                <?= ['pending'=>'⏳ Pending','diproses'=>'⚙️ Diproses','selesai'=>'✅ Selesai','ditolak'=>'❌ Ditolak'][$o['status']] ?>
                            </span>
                        </div>
                        <?php if ($o['catatan']): ?>
                            <p style="font-size:12px;color:var(--muted);">📝 <?= htmlspecialchars($o['catatan']) ?></p>
                        <?php endif; ?>
                        <p style="font-size:11px;color:#bbb;margin-top:4px;"><?= $o['created_at'] ?></p>
                    </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- TAB: LIVE CHAT -->
            <div class="tab-panel" id="tab-chat">
                <div class="chat-type-bar">
                    <button class="type-chip selected" onclick="setTipe('chat', this)">💬 Chat</button>
                    <button class="type-chip" onclick="setTipe('keluhan', this)">⚠️ Keluhan</button>
                    <button class="type-chip" onclick="setTipe('masukan', this)">💡 Masukan</button>
                </div>
                <input type="hidden" id="tipe-pesan" value="chat">

                <div class="chat-window" id="chatWindow">
                    <div class="chat-bubble system">
                        💬 Halo <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>! Ada yang bisa kami bantu? Ketik pesanmu di bawah.
                    </div>
                    <?php
                    $u = $_SESSION['username'];
                    $stmt3 = $conn->prepare("SELECT * FROM pesan_chat WHERE username=? ORDER BY created_at ASC LIMIT 30");
                    $stmt3->bind_param("s", $u);
                    $stmt3->execute();
                    $chats = $stmt3->get_result()->fetch_all(MYSQLI_ASSOC);
                    $stmt3->close();
                    foreach ($chats as $c):
                        $tipeLabel = ['chat'=>'💬','keluhan'=>'⚠️','masukan'=>'💡'][$c['tipe']] ?? '💬';
                        $waktu = date('H:i', strtotime($c['created_at']));
                    ?>
                    <div class="chat-bubble me">
                        <div class="chat-meta"><?= $tipeLabel ?> <?= ucfirst($c['tipe']) ?></div>
                        <?= htmlspecialchars($c['pesan']) ?>
                        <span class="time"><?= $waktu ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="chat-input-row">
                    <textarea id="inputPesan" placeholder="Ketik pesanmu di sini..." onkeydown="handleEnter(event)"></textarea>
                    <button class="btn-send" onclick="kirimPesan()">➤</button>
                </div>
                <p style="font-size:11px;color:var(--muted);margin-top:8px;">Enter untuk kirim · Shift+Enter untuk baris baru</p>
            </div>

        </div><!-- card-body -->
    </div><!-- card -->
</div>

<p class="footer">© <?= date('Y') ?> PT Juma Tiga</p>

<script>
// Harga produk untuk kalkulasi
const harga = <?= (int)$produk['harga'] ?>;
const jumlahInput = document.getElementById('jumlah');
if (jumlahInput) {
    jumlahInput.addEventListener('input', () => {
        const j = parseInt(jumlahInput.value) || 0;
        document.getElementById('preview-total').textContent = 'Rp ' + (j * harga).toLocaleString('id-ID');
    });
}

// Tab switching
function switchTab(name, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    btn.classList.add('active');
}

// Tipe chat chip
function setTipe(tipe, el) {
    document.querySelectorAll('.type-chip').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('tipe-pesan').value = tipe;
}

// Kirim pesan via AJAX
async function kirimPesan() {
    const input = document.getElementById('inputPesan');
    const tipe  = document.getElementById('tipe-pesan').value;
    const pesan = input.value.trim();
    if (!pesan) return;

    // Optimistic UI — tampilkan bubble langsung
    tambahBubble(pesan, tipe);
    input.value = '';

    const fd = new FormData();
    fd.append('pesan', pesan);
    fd.append('tipe', tipe);

    try {
        const res = await fetch('kirim_chat.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (!data.ok) console.error('Gagal kirim:', data.msg);
    } catch(e) {
        console.error(e);
    }
}

function tambahBubble(pesan, tipe) {
    const win = document.getElementById('chatWindow');
    const tipeLabel = { chat:'💬', keluhan:'⚠️', masukan:'💡' }[tipe] || '💬';
    const now = new Date();
    const waktu = now.getHours().toString().padStart(2,'0') + ':' + now.getMinutes().toString().padStart(2,'0');

    const div = document.createElement('div');
    div.className = 'chat-bubble me';
    div.innerHTML = `<div class="chat-meta">${tipeLabel} ${tipe.charAt(0).toUpperCase()+tipe.slice(1)}</div>${escHtml(pesan)}<span class="time">${waktu}</span>`;
    win.appendChild(div);
    win.scrollTop = win.scrollHeight;
}

function handleEnter(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        kirimPesan();
    }
}

function escHtml(s) {
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// Scroll chat ke bawah saat load
const cw = document.getElementById('chatWindow');
if (cw) cw.scrollTop = cw.scrollHeight;
</script>
</body>
</html>