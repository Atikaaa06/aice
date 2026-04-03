<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

include 'koneksi.php';

$username = $_SESSION['username'];
$role     = $_SESSION['role'] ?? 'user';

// ================== AMBIL DATA CHAT ==================
if ($role === 'admin_asset') {
    $result = mysqli_query($conn, "SELECT * FROM pesan_chat ORDER BY created_at ASC");
} else {
    $result = mysqli_query($conn, "SELECT * FROM pesan_chat WHERE pengirim='$username' OR penerima='$username' ORDER BY created_at ASC");
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Chat</title>

    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #eef1f7;
        }

        .text-grey {
            color: #424242;
        }

        .chat-box {
            max-width: 700px;
            margin: 30px auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .chat-header {
            background: #0f1e3c;
            color: white;
            padding: 15px;
            font-weight: bold;
        }

        .chat-window {
            height: 420px;
            overflow-y: auto;
            padding: 15px;
            background: #f5f7fb;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .bubble {
            position: relative;
            padding: 10px 14px;
            border-radius: 14px;
            max-width: 70%;
            font-size: 14px;
        }

        .kanan {
            background: #0f1e3c;
            color: white;
            align-self: flex-end;
            border-bottom-right-radius: 4px;
        }

        .kiri {
            background: #e4e6eb;
            align-self: flex-start;
            border-bottom-left-radius: 4px;
        }

        .bubble small {
            display: block;
            font-size: 10px;
            margin-top: 5px;
            opacity: 0.7;
        }

        .chat-input {
            padding: 12px;
            border-top: 1px solid #ddd;
            background: #fff;
        }

        .chat-input form {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .chat-input input[type="text"] {
            flex: 1;
            padding: 10px;
            border-radius: 10px;
            border: 1px solid #ccc;
        }

        .chat-input button {
            background: #0f1e3c;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 10px;
            cursor: pointer;
        }

        .chat-input button:hover {
            background: #1c2f5a;
        }

        img.chat-img {
            max-width: 150px;
            margin-top: 5px;
            border-radius: 10px;
        }

        .bubble:hover .action {
            bottom: -10px;
            opacity: 1;
        }

        .action {
            position: absolute;
            opacity: 0;
            bottom: 0;
            right: 5px;
            transition: all 0.2s ease-in-out;
        }

        .action-chat {
            position: relative;
            font-size: 10px;
            padding: 0;
            aspect-ratio: 1 / 1;
            width: 20px;
            border-radius: 50%;
            border: 1px #0f1e3c solid;
            color: #0f1e3c;
            cursor: pointer;
            transition: transform 0.2s ease-in-out;
        }

        .action-chat:hover {
            filter: brightness(0.9);
            transform: translateY(-3px);
        }

        .edit-chat {
            opacity: 0;
            transition: opacity 0.2s ease-in-out;
            pointer-events: none;
        }

        .edit-chat.active {
            opacity: 1;
            pointer-events: auto;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            padding: 12px;
            border: none;
            background: rgba(0, 0, 0, 0.5);
            width: 100%;
            height: 100%;
        }

        .input-edit-chat {
            position: relative;
        }

        #close-edit {
            background-color: transparent;
            position: absolute;
            right: 10px;
            top: 50%;
            padding: 0;
            transform: translateY(-50%);
            color: black;
            opacity: 60%;
        }

        .form-edit-chat {
            position: absolute;
            justify-content: center;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
    </style>

</head>

<body>

    <div class="chat-box">

        <div class="chat-header">
            💬 Live Chat
        </div>

        <div class="chat-window" id="chatWindow"></div>

        <!-- ================== FORM ================== -->
        <div class="chat-input">
            <form id="formChat" enctype="multipart/form-data">

                <?php if ($role === 'admin_asset'): ?>
                    <input type="text" name="penerima" placeholder="Username tujuan" required>
                <?php else: ?>
                    <input type="hidden" name="penerima" value="admin_asset">
                <?php endif; ?>

                <input type="text" name="pesan" placeholder="Tulis pesan..." required>
                <input type="file" name="gambar">
                <button type="submit">➤</button>
            </form>
        </div>
    </div>

    <div class="chat-input edit-chat" id="window-edit-chat">
        <form class="form-edit-chat" id="formUbahChat">
            <div class="input-edit-chat">
                <input type="text" hidden name="tipe" value="ubah">
                <input type="text" name="ubah_pesan" id="pesan">
                <input type="text" hidden name="id_pesan" id="id_pesan">
                <button type="button" id="close-edit">✖</button>
            </div>
            <button type="submit">Ubah</button>
        </form>
    </div>

    <script>
        // auto scroll ke bawah
        const chat = document.getElementById("chatWindow");
        chat.scrollTop = chat.scrollHeight;


        const formChat = document.getElementById("formChat");
        const chatEdits = document.getElementsByClassName("edit_chat");
        const chatHapus = document.getElementsByClassName("hapus_chat");

        const windowChatEdit = document.getElementById("window-edit-chat");
        const formUbahChat = document.getElementById("formUbahChat");
        const inputPesan = document.getElementById("pesan");
        const inputIdPesan = document.getElementById("id_pesan");
        const closeEdit = document.getElementById("close-edit");

        formChat.addEventListener("submit", function(e) {
            e.preventDefault();
            let formData = new FormData(this);

            fetch("kirim_chat.php", {
                    method: "POST",
                    body: formData
                })
                .then(res => res.json())
                .then(res => {
                    if (res.ok) {
                        formChat.reset();
                        loadChat();
                    } else {
                        alert(res.msg);
                    }
                });
        });

        function loadChat() {
            fetch("get_chat.php")
                .then(res => res.json())
                .then(data => {
                    let html = "";
                    data.forEach(row => {
                        let isMe = row.pengirim === "<?= $_SESSION['username'] ?>";
                        html += `
                        <div class="bubble ${isMe ? 'kanan' : 'kiri'}">
                            <div>
                                <b>${row.pengirim}</b><br>
                                ${row.gambar ? `<img src="uploads/chats/${row.gambar}" class="chat-img"><br>` : ''}
                                <span class="text-pesan">${row.pesan ?? ''}</span>
                                <small>${row.created_at.substring(11,16)}</small>
                            </div>

                            ${isMe ? `
                            <div class="action">
                                <button type="button" class="edit_chat action-chat" data-id="${row.id}">✐</button>
                                <button type="button" class="hapus_chat action-chat" data-id="${row.id}">🗑️</button>
                            </div>
                            ` : ''}
                        </div>`;
                    });

                    chat.innerHTML = html;
                    chat.scrollTop = chat.scrollHeight;
                });
        }
        loadChat();

        chat.addEventListener("click", function(e) {
            const editBtn = e.target.closest(".edit_chat");
            const hapusBtn = e.target.closest(".hapus_chat");
            if (editBtn) {
                const pesan = editBtn.closest(".bubble").querySelector("span").innerText;
                inputPesan.value = pesan;
                inputIdPesan.value = editBtn.dataset.id;
                windowChatEdit.classList.add("active");
            }

            if (hapusBtn) {
                const id = hapusBtn.dataset.id;
                if (!confirm("Yakin ingin menghapus pesan ini?")) {
                    return;
                }

                let formData = new FormData();
                formData.append("tipe", "hapus");
                formData.append("id_pesan", id);

                fetch("kirim_chat.php", {
                        method: "POST",
                        body: formData
                    })
                    .then(res => res.json())
                    .then(res => {
                        if (res.ok) {
                            loadChat();
                        } else {
                            alert(res.msg);
                        }
                    });
            }
        });

        closeEdit.addEventListener("click", function(e) {
            inputPesan.value = '';
            inputIdPesan.value = '';
            windowChatEdit.classList.remove("active");
        });

        formUbahChat.addEventListener("submit", function(e) {
            e.preventDefault();
            let formData = new FormData(this);

            fetch("kirim_chat.php", {
                    method: "POST",
                    body: formData
                })
                .then(res => res.json())
                .then(res => {
                    if (res.ok) {
                        inputPesan.value = '';
                        inputIdPesan.value = '';
                        windowChatEdit.classList.remove("active");
                        formUbahChat.reset();
                        loadChat();
                    } else {
                        alert(res.msg);
                    }
                });
        });


        //jika ada action klik maka console
    </script>

</body>

</html>