<?php
/**
 * CHAT WIDGET — PT JUMA TIGA SEANTERO
 * Sisipkan kode ini di bagian BAWAH index.php, sebelum tag </body>
 * Menggantikan / melengkapi chat dialog yang sudah ada.
 * Terhubung ke: kirim_chat_publik.php
 */
?>

<!-- ═══════════════════════════════════════════════════
     CHAT FAB BUTTON
═══════════════════════════════════════════════════ -->
<div id="jt-chat-fab" onclick="jtChatToggle()" title="Hubungi Kami">
    <div class="jt-fab-ring"></div>
    <div class="jt-fab-icon" id="jtFabIcon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
        </svg>
    </div>
    <span class="jt-fab-label" id="jtFabLabel">Hubungi Kami</span>
    <span class="jt-notif-dot" id="jtNotifDot">1</span>
</div>

<!-- ═══════════════════════════════════════════════════
     CHAT DIALOG
═══════════════════════════════════════════════════ -->
<div id="jt-chat-dialog" aria-hidden="true">

    <!-- Header -->
    <div class="jt-chat-head">
        <div class="jt-chat-head-left">
            <div class="jt-chat-avatar">🏢</div>
            <div>
                <div class="jt-chat-title">PT Juma Tiga Seantero</div>
                <div class="jt-chat-status"><span class="jt-online-dot"></span> Tim Admin siap membantu</div>
            </div>
        </div>
        <button class="jt-close-btn" onclick="jtChatToggle()" title="Tutup">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </div>

    <!-- Tipe Tab -->
    <div class="jt-tipe-bar">
        <button class="jt-tipe-btn active" onclick="jtSetTipe('chat',this)">💬 Chat</button>
        <button class="jt-tipe-btn" onclick="jtSetTipe('keluhan',this)">⚠️ Keluhan</button>
        <button class="jt-tipe-btn" onclick="jtSetTipe('masukan',this)">💡 Masukan</button>
    </div>

    <!-- Bubble Area -->
    <div class="jt-bubbles" id="jtBubbles">
        <!-- Greeting bubble muncul otomatis -->
        <div class="jt-bubble jt-bubble-admin" id="jtGreeting">
            <div class="jt-bubble-avatar">🏢</div>
            <div class="jt-bubble-content">
                <div class="jt-bubble-name">Admin Asset</div>
                <div class="jt-bubble-text">
                    Halo! 👋 Selamat datang di <strong>PT Juma Tiga Seantero</strong>.<br>
                    Ada yang bisa kami bantu? Silakan ketik nama dan pesan kamu.
                </div>
                <div class="jt-bubble-time" id="jtGreetTime"></div>
            </div>
        </div>
        <!-- Typing indicator -->
        <div class="jt-bubble jt-bubble-admin" id="jtTyping" style="display:none;">
            <div class="jt-bubble-avatar">🏢</div>
            <div class="jt-bubble-content">
                <div class="jt-typing-dots">
                    <span></span><span></span><span></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Input area -->
    <div class="jt-input-area">
        <!-- Nama pengunjung (hanya muncul sebelum kirim pertama) -->
        <div class="jt-nama-wrap" id="jtNamaWrap">
            <input type="text" id="jtNama" placeholder="👤 Nama kamu (wajib)" maxlength="60"
                   onkeydown="if(event.key==='Enter')document.getElementById('jtPesan').focus()">
        </div>
        <div class="jt-input-row">
            <textarea id="jtPesan" placeholder="Ketik pesan di sini..." rows="1"
                      onkeydown="jtHandleEnter(event)"
                      oninput="jtAutoResize(this)"></textarea>
            <button class="jt-send-btn" id="jtSendBtn" onclick="jtKirim()" title="Kirim">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                </svg>
            </button>
        </div>
        <div class="jt-input-hint">Enter = kirim &nbsp;·&nbsp; Shift+Enter = baris baru</div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════
     STYLES
═══════════════════════════════════════════════════ -->
<style>
/* ── FAB ────────────────────────────────────── */
#jt-chat-fab {
    position: fixed;
    bottom: 28px;
    right: 28px;
    z-index: 9999;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0;
    user-select: none;
}

.jt-fab-ring {
    position: absolute;
    width: 58px; height: 58px;
    border-radius: 50%;
    border: 2px solid rgba(201,168,76,0.4);
    animation: jtPulseRing 2.5s ease-out infinite;
    pointer-events: none;
}
@keyframes jtPulseRing {
    0%   { transform: scale(1);   opacity: 0.8; }
    70%  { transform: scale(1.45); opacity: 0; }
    100% { transform: scale(1.45); opacity: 0; }
}

.jt-fab-icon {
    width: 54px; height: 54px;
    background: linear-gradient(135deg, #0f1e3c 0%, #1a3060 100%);
    border-radius: 50%;
    border: 2.5px solid #c9a84c;
    display: flex; align-items: center; justify-content: center;
    color: white;
    box-shadow: 0 6px 24px rgba(15,30,60,0.45), 0 0 0 0 rgba(201,168,76,0.3);
    transition: transform 0.25s cubic-bezier(.22,.68,0,1.2), box-shadow 0.2s;
    position: relative; z-index: 1;
}
#jt-chat-fab:hover .jt-fab-icon {
    transform: scale(1.1);
    box-shadow: 0 8px 28px rgba(15,30,60,0.55), 0 0 0 6px rgba(201,168,76,0.15);
}
#jt-chat-fab.open .jt-fab-icon {
    background: linear-gradient(135deg, #c0392b 0%, #e74c3c 100%);
    border-color: #e74c3c;
}

.jt-fab-label {
    background: #0f1e3c;
    color: #c9a84c;
    font-family: 'DM Sans', sans-serif;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.5px;
    padding: 6px 14px 6px 10px;
    border-radius: 50px 50px 50px 4px;
    margin-left: -8px;
    box-shadow: 0 4px 14px rgba(0,0,0,0.2);
    white-space: nowrap;
    animation: jtLabelIn 0.4s 1.2s cubic-bezier(.22,.68,0,1.2) both;
    border: 1px solid rgba(201,168,76,0.3);
}
@keyframes jtLabelIn {
    from { opacity:0; transform: translateX(10px); }
    to   { opacity:1; transform: translateX(0); }
}

.jt-notif-dot {
    position: absolute;
    top: -2px; right: -2px;
    width: 20px; height: 20px;
    background: #e53e3e;
    color: white;
    border-radius: 50%;
    font-size: 11px;
    font-weight: 700;
    font-family: 'DM Sans', sans-serif;
    display: flex; align-items: center; justify-content: center;
    border: 2px solid white;
    z-index: 2;
    animation: jtBounce 2s ease infinite;
}
@keyframes jtBounce {
    0%,100% { transform: translateY(0); }
    50%      { transform: translateY(-3px); }
}

/* ── DIALOG ─────────────────────────────────── */
#jt-chat-dialog {
    position: fixed;
    bottom: 96px;
    right: 28px;
    width: 360px;
    max-height: 540px;
    background: #ffffff;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(15,30,60,0.22), 0 4px 16px rgba(15,30,60,0.12);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    z-index: 9998;
    transform: scale(0.85) translateY(20px);
    opacity: 0;
    pointer-events: none;
    transform-origin: bottom right;
    transition: transform 0.3s cubic-bezier(.22,.68,0,1.2), opacity 0.25s ease;
    border: 1px solid rgba(201,168,76,0.15);
}
#jt-chat-dialog.open {
    transform: scale(1) translateY(0);
    opacity: 1;
    pointer-events: all;
}

/* Header */
.jt-chat-head {
    background: linear-gradient(135deg, #0f1e3c 0%, #1a3060 100%);
    padding: 16px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
    position: relative;
    overflow: hidden;
}
.jt-chat-head::before {
    content: '';
    position: absolute;
    top: -30px; right: -30px;
    width: 100px; height: 100px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(201,168,76,0.15), transparent 70%);
    pointer-events: none;
}
.jt-chat-head-left {
    display: flex;
    align-items: center;
    gap: 12px;
}
.jt-chat-avatar {
    width: 40px; height: 40px;
    background: rgba(201,168,76,0.18);
    border: 2px solid rgba(201,168,76,0.5);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}
.jt-chat-title {
    font-family: 'Playfair Display', serif;
    color: #fff;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.3px;
}
.jt-chat-status {
    display: flex;
    align-items: center;
    gap: 5px;
    color: rgba(201,168,76,0.9);
    font-size: 11px;
    margin-top: 2px;
    font-family: 'DM Sans', sans-serif;
}
.jt-online-dot {
    width: 7px; height: 7px;
    background: #22c55e;
    border-radius: 50%;
    display: inline-block;
    animation: jtOnlinePulse 2s ease infinite;
}
@keyframes jtOnlinePulse {
    0%,100% { opacity:1; } 50% { opacity:0.4; }
}
.jt-close-btn {
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.15);
    color: rgba(255,255,255,0.7);
    width: 30px; height: 30px;
    border-radius: 8px;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: background 0.2s, color 0.2s;
    flex-shrink: 0;
}
.jt-close-btn:hover {
    background: rgba(255,255,255,0.2);
    color: #fff;
}

/* Tipe bar */
.jt-tipe-bar {
    display: flex;
    gap: 6px;
    padding: 10px 14px;
    background: #f8f9ff;
    border-bottom: 1px solid #eef0f7;
    flex-shrink: 0;
}
.jt-tipe-btn {
    padding: 4px 12px;
    border-radius: 50px;
    border: 1.5px solid #e0e4ec;
    background: #fff;
    font-size: 11px;
    font-weight: 600;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.18s;
    font-family: 'DM Sans', sans-serif;
    letter-spacing: 0.2px;
}
.jt-tipe-btn:hover { border-color: #c9a84c; color: #0f1e3c; }
.jt-tipe-btn.active {
    background: #0f1e3c;
    color: #fff;
    border-color: #0f1e3c;
}

/* Bubbles */
.jt-bubbles {
    flex: 1;
    overflow-y: auto;
    padding: 16px 14px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    background: #f8f9ff;
    scroll-behavior: smooth;
    min-height: 0;
}
.jt-bubbles::-webkit-scrollbar { width: 3px; }
.jt-bubbles::-webkit-scrollbar-thumb { background: #dde0ea; border-radius: 4px; }

/* Admin bubble (kiri) */
.jt-bubble-admin {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    animation: jtBubbleIn 0.3s cubic-bezier(.22,.68,0,1.2) both;
}
.jt-bubble-admin .jt-bubble-avatar {
    width: 30px; height: 30px;
    background: #0f1e3c;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px;
    flex-shrink: 0;
    margin-top: 2px;
}
.jt-bubble-content { max-width: 82%; }
.jt-bubble-name {
    font-size: 10px;
    font-weight: 700;
    color: #0f1e3c;
    margin-bottom: 3px;
    letter-spacing: 0.3px;
    font-family: 'DM Sans', sans-serif;
}
.jt-bubble-text {
    background: #fff;
    color: #1a1a2e;
    padding: 10px 13px;
    border-radius: 4px 14px 14px 14px;
    font-size: 13px;
    line-height: 1.55;
    box-shadow: 0 2px 8px rgba(15,30,60,0.07);
    font-family: 'DM Sans', sans-serif;
    border: 1px solid #eef0f7;
}
.jt-bubble-time {
    font-size: 10px;
    color: #aaa;
    margin-top: 4px;
    padding-left: 2px;
    font-family: 'DM Sans', sans-serif;
}

/* User bubble (kanan) */
.jt-bubble-user {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    animation: jtBubbleIn 0.3s cubic-bezier(.22,.68,0,1.2) both;
}
.jt-bubble-user-inner {
    max-width: 82%;
    text-align: right;
}
.jt-bubble-user-name {
    font-size: 10px;
    font-weight: 700;
    color: #6b7280;
    margin-bottom: 3px;
    font-family: 'DM Sans', sans-serif;
}
.jt-bubble-user-text {
    background: linear-gradient(135deg, #0f1e3c, #1a3060);
    color: #fff;
    padding: 10px 13px;
    border-radius: 14px 4px 14px 14px;
    font-size: 13px;
    line-height: 1.55;
    font-family: 'DM Sans', sans-serif;
    display: inline-block;
    text-align: left;
}
.jt-bubble-user-tipe {
    font-size: 10px;
    color: #c9a84c;
    opacity: 0.85;
    margin-bottom: 3px;
    font-family: 'DM Sans', sans-serif;
}
.jt-bubble-user-time {
    font-size: 10px;
    color: #aaa;
    margin-top: 4px;
    font-family: 'DM Sans', sans-serif;
}

/* Typing dots */
.jt-typing-dots {
    background: #fff;
    border-radius: 4px 14px 14px 14px;
    padding: 12px 16px;
    display: flex;
    gap: 4px;
    align-items: center;
    border: 1px solid #eef0f7;
    box-shadow: 0 2px 8px rgba(15,30,60,0.07);
}
.jt-typing-dots span {
    width: 7px; height: 7px;
    background: #c9a84c;
    border-radius: 50%;
    animation: jtDotBounce 1.2s ease infinite;
}
.jt-typing-dots span:nth-child(2) { animation-delay: 0.2s; }
.jt-typing-dots span:nth-child(3) { animation-delay: 0.4s; }
@keyframes jtDotBounce {
    0%,60%,100% { transform: translateY(0); opacity:0.5; }
    30%          { transform: translateY(-6px); opacity:1; }
}

@keyframes jtBubbleIn {
    from { opacity:0; transform: translateY(8px) scale(0.96); }
    to   { opacity:1; transform: translateY(0) scale(1); }
}

/* Input area */
.jt-input-area {
    padding: 12px 14px 14px;
    background: #fff;
    border-top: 1px solid #eef0f7;
    flex-shrink: 0;
}
.jt-nama-wrap {
    margin-bottom: 8px;
}
.jt-nama-wrap input {
    width: 100%;
    padding: 9px 12px;
    border: 1.5px solid #e0e4ec;
    border-radius: 10px;
    font-size: 13px;
    font-family: 'DM Sans', sans-serif;
    outline: none;
    background: #f8f9ff;
    color: #1a1a2e;
    transition: border-color 0.2s;
    box-sizing: border-box;
}
.jt-nama-wrap input:focus {
    border-color: #c9a84c;
    background: #fff;
}
.jt-input-row {
    display: flex;
    gap: 8px;
    align-items: flex-end;
}
.jt-input-row textarea {
    flex: 1;
    padding: 10px 12px;
    border: 1.5px solid #e0e4ec;
    border-radius: 12px;
    font-size: 13px;
    font-family: 'DM Sans', sans-serif;
    resize: none;
    min-height: 40px;
    max-height: 100px;
    outline: none;
    background: #f8f9ff;
    color: #1a1a2e;
    line-height: 1.5;
    transition: border-color 0.2s;
    box-sizing: border-box;
}
.jt-input-row textarea:focus {
    border-color: #c9a84c;
    background: #fff;
}
.jt-send-btn {
    width: 40px; height: 40px;
    background: linear-gradient(135deg, #0f1e3c, #1a3060);
    color: #fff;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: transform 0.15s, background 0.2s, opacity 0.2s;
    flex-shrink: 0;
}
.jt-send-btn:hover  { background: linear-gradient(135deg, #1a3060, #243d70); }
.jt-send-btn:active { transform: scale(0.9); }
.jt-send-btn.loading { opacity: 0.5; pointer-events: none; }
.jt-input-hint {
    font-size: 10px;
    color: #aaa;
    margin-top: 6px;
    font-family: 'DM Sans', sans-serif;
    text-align: center;
}

/* ── TOAST ──────────────────────────────────── */
#jt-toast {
    position: fixed;
    bottom: 100px;
    right: 28px;
    background: #0f1e3c;
    color: #fff;
    padding: 10px 18px;
    border-radius: 50px;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    font-weight: 600;
    box-shadow: 0 6px 20px rgba(15,30,60,0.3);
    border: 1px solid rgba(201,168,76,0.3);
    opacity: 0;
    transform: translateY(10px);
    transition: opacity 0.3s, transform 0.3s;
    pointer-events: none;
    z-index: 9997;
}
#jt-toast.show {
    opacity: 1;
    transform: translateY(0);
}

/* Mobile responsive */
@media (max-width: 480px) {
    #jt-chat-dialog {
        width: calc(100vw - 24px);
        right: 12px;
        bottom: 86px;
        max-height: 70vh;
    }
    #jt-chat-fab { bottom: 18px; right: 16px; }
    .jt-fab-label { display: none; }
}
</style>

<!-- Toast -->
<div id="jt-toast">✅ Pesan terkirim!</div>

<!-- ═══════════════════════════════════════════════════
     JAVASCRIPT
═══════════════════════════════════════════════════ -->
<script>
(function() {
    /* ── State ── */
    var _open     = false;
    var _tipe     = 'chat';
    var _sudahNama = false;
    var _nama     = '';
    var _toastTimer;

    /* ── Set jam greeting ── */
    var now = new Date();
    var jam = now.getHours().toString().padStart(2,'0') + ':' + now.getMinutes().toString().padStart(2,'0');
    var el = document.getElementById('jtGreetTime');
    if (el) el.textContent = jam;

    /* Sembunyikan label FAB setelah 6 detik */
    setTimeout(function(){
        var lbl = document.getElementById('jtFabLabel');
        if (lbl) { lbl.style.transition='opacity .5s'; lbl.style.opacity='0'; setTimeout(function(){ if(lbl) lbl.style.display='none'; },500); }
    }, 6000);

    /* ── Toggle dialog ── */
    window.jtChatToggle = function() {
        _open = !_open;
        var fab    = document.getElementById('jt-chat-fab');
        var dialog = document.getElementById('jt-chat-dialog');
        var notif  = document.getElementById('jtNotifDot');

        if (_open) {
            fab.classList.add('open');
            dialog.classList.add('open');
            dialog.setAttribute('aria-hidden','false');
            // Sembunyikan notif dot
            if (notif) notif.style.display = 'none';
            // Fokus input
            setTimeout(function(){
                var target = _sudahNama
                    ? document.getElementById('jtPesan')
                    : document.getElementById('jtNama');
                if (target) target.focus();
                jtScrollBottom();
            }, 320);
        } else {
            fab.classList.remove('open');
            dialog.classList.remove('open');
            dialog.setAttribute('aria-hidden','true');
        }
    };

    /* ── Set tipe pesan ── */
    window.jtSetTipe = function(tipe, btn) {
        _tipe = tipe;
        document.querySelectorAll('.jt-tipe-btn').forEach(function(b){ b.classList.remove('active'); });
        btn.classList.add('active');
        var ta = document.getElementById('jtPesan');
        if (ta) {
            ta.placeholder = tipe==='keluhan' ? 'Ceritakan keluhan kamu...'
                           : tipe==='masukan'  ? 'Berikan masukan kamu...'
                           : 'Ketik pesan di sini...';
        }
    };

    /* ── Kirim pesan ── */
    window.jtKirim = function() {
        var namaEl  = document.getElementById('jtNama');
        var pesanEl = document.getElementById('jtPesan');
        var nama    = namaEl ? namaEl.value.trim() : _nama;
        var pesan   = pesanEl ? pesanEl.value.trim() : '';

        /* Validasi nama */
        if (!_sudahNama && !nama) {
            namaEl.style.borderColor = '#e53e3e';
            namaEl.placeholder = '⚠️ Nama wajib diisi!';
            namaEl.focus();
            setTimeout(function(){
                namaEl.style.borderColor = '';
                namaEl.placeholder = '👤 Nama kamu (wajib)';
            }, 2500);
            return;
        }
        /* Validasi pesan */
        if (!pesan) {
            pesanEl.style.borderColor = '#e53e3e';
            pesanEl.focus();
            setTimeout(function(){ pesanEl.style.borderColor = ''; }, 2000);
            return;
        }

        /* Simpan nama, sembunyikan input nama setelah kirim pertama */
        if (!_sudahNama) {
            _nama = nama;
            _sudahNama = true;
            var wrap = document.getElementById('jtNamaWrap');
            if (wrap) { wrap.style.transition='opacity .3s'; wrap.style.opacity='0'; setTimeout(function(){ wrap.style.display='none'; },300); }
        }

        /* Tampilkan bubble user (optimistic UI) */
        jtAddUserBubble(_nama, pesan, _tipe);
        pesanEl.value = '';
        pesanEl.style.height = 'auto';

        /* Loading state */
        var btn = document.getElementById('jtSendBtn');
        if (btn) btn.classList.add('loading');

        /* Kirim ke server */
        var fd = new FormData();
        fd.append('nama',  _nama);
        fd.append('pesan', pesan);
        fd.append('tipe',  _tipe);

        /* Tampilkan typing indicator */
        var typing = document.getElementById('jtTyping');
        if (typing) { typing.style.display='flex'; jtScrollBottom(); }

        fetch('kirim_chat_publik.php', { method:'POST', body:fd })
            .then(function(r){ return r.json(); })
            .then(function(d){
                setTimeout(function(){
                    if (typing) typing.style.display = 'none';
                    var replies = [
                        'Terima kasih, <strong>' + jtEsc(_nama) + '</strong>! 😊 Pesan kamu sudah kami terima. Tim kami akan segera merespons.',
                        'Halo <strong>' + jtEsc(_nama) + '</strong>! Pesan sudah masuk. Kami akan menghubungi kamu secepatnya.',
                        'Siap! Pesan kamu sudah kami catat 👍 Tim Admin Asset akan segera merespons.',
                    ];
                    var txt = d.ok
                        ? replies[Math.floor(Math.random()*replies.length)]
                        : 'Maaf, ada gangguan teknis. Silakan coba lagi 🙏';
                    jtAddAdminBubble(txt);
                    if (d.ok) jtShowToast('✅ Pesan berhasil terkirim!');
                    else      jtShowToast('⚠️ Gagal kirim. Coba lagi.');
                    if (btn) btn.classList.remove('loading');
                }, 1000 + Math.random()*800);
            })
            .catch(function(){
                setTimeout(function(){
                    if (typing) typing.style.display = 'none';
                    jtAddAdminBubble('Maaf, koneksi bermasalah. Silakan coba lagi 🙏');
                    if (btn) btn.classList.remove('loading');
                }, 1200);
            });
    };

    /* ── Buat bubble user ── */
    function jtAddUserBubble(nama, pesan, tipe) {
        var icon = {chat:'💬',keluhan:'⚠️',masukan:'💡'}[tipe] || '💬';
        var tipeLbl = {chat:'Chat',keluhan:'Keluhan',masukan:'Masukan'}[tipe] || 'Chat';
        var jam = _nowJam();
        var div = document.createElement('div');
        div.className = 'jt-bubble-user';
        div.innerHTML =
            '<div class="jt-bubble-user-inner">' +
                '<div class="jt-bubble-user-name">' + jtEsc(nama) + '</div>' +
                '<div class="jt-bubble-user-tipe">' + icon + ' ' + tipeLbl + '</div>' +
                '<div class="jt-bubble-user-text">' + jtEsc(pesan).replace(/\n/g,'<br>') + '</div>' +
                '<div class="jt-bubble-user-time">' + jam + '</div>' +
            '</div>';
        _insertBubble(div);
    }

    /* ── Buat bubble admin ── */
    function jtAddAdminBubble(html) {
        var jam = _nowJam();
        var div = document.createElement('div');
        div.className = 'jt-bubble jt-bubble-admin';
        div.innerHTML =
            '<div class="jt-bubble-avatar">🏢</div>' +
            '<div class="jt-bubble-content">' +
                '<div class="jt-bubble-name">Admin Asset</div>' +
                '<div class="jt-bubble-text">' + html + '</div>' +
                '<div class="jt-bubble-time">' + jam + '</div>' +
            '</div>';
        _insertBubble(div);
    }

    function _insertBubble(el) {
        var bubbles = document.getElementById('jtBubbles');
        var typing  = document.getElementById('jtTyping');
        if (typing && typing.parentNode === bubbles) {
            bubbles.insertBefore(el, typing);
        } else {
            bubbles.appendChild(el);
        }
        jtScrollBottom();
    }

    /* ── Scroll to bottom ── */
    window.jtScrollBottom = function() {
        var b = document.getElementById('jtBubbles');
        if (b) b.scrollTop = b.scrollHeight;
    };

    /* ── Toast ── */
    window.jtShowToast = function(msg) {
        var t = document.getElementById('jt-toast');
        if (!t) return;
        t.textContent = msg;
        t.classList.add('show');
        clearTimeout(_toastTimer);
        _toastTimer = setTimeout(function(){ t.classList.remove('show'); }, 3000);
    };

    /* ── Auto resize textarea ── */
    window.jtAutoResize = function(el) {
        el.style.height = 'auto';
        el.style.height = Math.min(el.scrollHeight, 100) + 'px';
    };

    /* ── Enter handler ── */
    window.jtHandleEnter = function(e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); jtKirim(); }
    };

    /* ── Helpers ── */
    function _nowJam() {
        var d = new Date();
        return d.getHours().toString().padStart(2,'0') + ':' + d.getMinutes().toString().padStart(2,'0');
    }
    window.jtEsc = function(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    };

    /* ── Tutup dengan Escape ── */
    document.addEventListener('keydown', function(e){
        if (e.key==='Escape' && _open) jtChatToggle();
    });

})();
</script>