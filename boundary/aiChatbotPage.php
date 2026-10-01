<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: loginPage.php");
    exit();
}

require_once "../controller/AIChatbotController.php";

$controller = new AIChatbotController();
$studentId = $_SESSION['user_id'];
$history = $controller->getChatHistory($studentId);
if ($history === false) $history = [];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Chatbot - UniBee</title>
    <link rel="stylesheet" href="../style.css">
</head>

<body>
    <script src="../script.js"></script>

    <header class="header">
        <div class="logo-container">
            <a href="studentDashboardPage.php">
                <img src="../images/uniBeeLogo.png" alt="UniBee Logo">
            </a>
        </div>

        <div class="welcome-message">
            <span>Welcome back,</span>
            <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>
        </div>

        <div class="dashboard-header-right">
            <a href="NotificationPage.php" class="header-icon">
                <img src="../images/notification.png" alt="Notifications">
            </a>

            <div class="profile-dropdown">
                <div class="profile-container" onclick="toggleDropdown()">
                    <img src="../images/profilePic.png" alt="Profile" class="profile-icon">
                    <img src="../images/dropdown.png" alt="Menu" class="dropdown-arrow">
                </div>

                <div id="profileMenu" class="dropdown-menu">
                    <a href="ManageProfilePage.php">Manage Profile</a>
                    <a href="AIChatbotPage.php">AI Chatbot</a>
                    <a href="academicsPage.php">Academics</a>
                    <a href="viewFacilitiesPage.php">Facility Booking</a>
                    <a href="viewEventsPage.php">University Campus Event</a>
                    <a href="../controller/logoutController.php">Log Out</a>
                </div>
            </div>
        </div>
    </header>

    <main class="chatbot-layout chatbot-full-width">

        <!-- LEFT: Chat History Sidebar -->
        <aside class="chat-history-panel">
            <a href="studentDashboardPage.php" class="btn-back chat-back-btn">&#8592; Back</a>
            <div class="chat-history-header">
                <h3>Chat History</h3>
                <button type="button" class="btn-clear-chat" title="Clear chat history"
                        onclick="clearChatHistory()">
                    &#128465;
                </button>
            </div>

            <ul class="chat-history-list" id="chatHistoryList">
                <?php if (empty($history)): ?>
                    <li class="chat-empty">No chat history yet.</li>
                <?php else: ?>
                    <?php foreach ($history as $h): ?>
                        <li onclick="loadPastQuestion(<?php echo $h['id']; ?>)">
                            <?php echo htmlspecialchars($h['question']); ?>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>

            <button type="button" class="btn-new-chat" onclick="newChat()">+ New chat</button>
        </aside>

        <!-- RIGHT: Main Chat Area -->
        <section class="chat-main">
            <h2>Ask a Question</h2>

            <div class="chat-messages" id="chatMessages">
                <?php if (!empty($history)): ?>
                    <?php
                    // Show most recent Q&A
                    $recent = $history[0];
                    ?>
                    <div class="chat-bubble user-bubble">
                        <?php echo htmlspecialchars($recent['question']); ?>
                    </div>
                    <div class="chat-bubble bot-bubble">
                        <?php echo htmlspecialchars($recent['answer']); ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="chat-input-bar">
                <input type="text" id="questionInput" placeholder="Type your Question..."
                       onkeydown="if(event.key==='Enter')sendQuestion()">
                <button type="button" class="btn-send" onclick="sendQuestion()">Send</button>
            </div>
        </section>

    </main>

    <!-- Confirmation Modal for Clear Chat -->
    <div class="modal-overlay" id="clearConfirmModal" style="display:none;">
        <div class="modal-box">
            <span class="modal-close" onclick="closeClearModal()">&times;</span>
            <p class="modal-message">Are you sure you want to clear all chat history?</p>
            <div class="clear-actions">
                <button type="button" class="btn-cancel-clear" onclick="closeClearModal()">Cancel</button>
                <button type="button" class="btn-confirm-clear" onclick="confirmClearChat()">Clear</button>
            </div>
        </div>
    </div>

    <!-- Success Toast -->
    <div class="modal-overlay" id="successToast" style="display:none;">
        <div class="modal-box">
            <span class="modal-close" onclick="document.getElementById('successToast').style.display='none'">&times;</span>
            <p class="modal-message" id="successToastText"></p>
        </div>
    </div>

    <footer>
        <div class="footer-bottom-bar">
            &copy; 2026 UniBee. All rights reserved.
        </div>
    </footer>

    <script>
        const CONTROLLER = "../controller/AIChatbotController.php";

        function sendQuestion() {
            const input = document.getElementById("questionInput");
            const question = input.value.trim();
            if (!question) return;

            appendBubble(question, "user");
            input.value = "";

            fetch(CONTROLLER, {
                method: "POST",
                headers: { "X-Requested-With": "XMLHttpRequest" },
                body: new URLSearchParams({ action: "ask", question: question })
            })
            .then(res => res.json())
            .then(json => {
                if (json.ok) {
                    renderRichResponse(json.data);
                    refreshHistory();
                } else {
                    appendBubble("Unable to process your question. Please try again later.", "bot");
                }
            })
            .catch(() => {
                appendBubble("Unable to process your question. Please try again later.", "bot");
            });
        }

        function appendBubble(text, type, source) {
            const box = document.getElementById("chatMessages");
            const bubble = document.createElement("div");
            bubble.className = "chat-bubble " + (type === "user" ? "user-bubble" : "bot-bubble");
            bubble.textContent = text;

            if (source) {
                const tag = document.createElement("small");
                tag.textContent = " (" + source + ")";
                tag.style.opacity = "0.55";
                tag.style.fontSize = "0.7rem";
                tag.style.marginLeft = "6px";
                bubble.appendChild(tag);
            }

            box.appendChild(bubble);
            box.scrollTop = box.scrollHeight;
            return bubble;
        }

        function renderRichResponse(data) {
            const bubble = appendBubble(data.answer, "bot", data.source);

            // --- ROOMS ---
            if (Array.isArray(data.rooms) && data.rooms.length > 0) {
                const list = document.createElement("div");
                list.className = "chat-room-list";
                data.rooms.forEach(r => {
                    const card = document.createElement("div");
                    card.className = "chat-room-card";
                    card.innerHTML =
                        '<strong>' + r.name + '</strong>' +
                        '<span>' + r.location + '</span>' +
                        '<small>Capacity ' + r.capacity + '</small>';
                    list.appendChild(card);
                });
                bubble.appendChild(list);
            }

            // --- TIMETABLE ---
            if (Array.isArray(data.timetable) && data.timetable.length > 0) {
                const list = document.createElement("div");
                list.className = "chat-timetable-list";
                data.timetable.forEach(t => {
                    const row = document.createElement("div");
                    row.className = "chat-timetable-row";
                    row.innerHTML =
                        '<strong>' + t.dayOfWeek.toUpperCase() + '</strong> ' +
                        t.startTime + ' – ' + t.endTime +
                        ' &nbsp; ' + t.title +
                        ' <small>(' + t.location + ')</small>';
                    list.appendChild(row);
                });
                bubble.appendChild(list);
            }

            // --- NOTIFICATIONS ---
            if (Array.isArray(data.notifications) && data.notifications.length > 0) {
                const list = document.createElement("div");
                list.className = "chat-notif-list";
                data.notifications.forEach(n => {
                    const item = document.createElement("div");
                    item.className = "chat-notif-item";
                    item.innerHTML = '<strong>' + n.title + '</strong><span>' + n.message + '</span>';
                    list.appendChild(item);
                });
                bubble.appendChild(list);
            }

            // --- EVENTS ---
            if (Array.isArray(data.events) && data.events.length > 0) {
                const list = document.createElement("div");
                list.className = "chat-event-list";
                data.events.forEach(e => {
                    const item = document.createElement("div");
                    item.className = "chat-event-item";
                    const dt = new Date(e.startDatetime);
                    item.innerHTML =
                        '<strong>' + e.title + '</strong>' +
                        '<span>' + dt.toLocaleString() + '</span>' +
                        '<small>' + e.location + '</small>';
                    list.appendChild(item);
                });
                bubble.appendChild(list);
            }

            // --- ACTION BUTTON ---
            if (data.action && data.action.url) {
                const btn = document.createElement("a");
                btn.className = "chat-action-btn";
                btn.href = data.action.url;
                btn.textContent = data.action.label;
                bubble.appendChild(btn);
            }
        }

        function refreshHistory() {
            fetch(CONTROLLER + "?action=history", {
                headers: { "X-Requested-With": "XMLHttpRequest" }
            })
            .then(res => res.json())
            .then(json => {
                if (!json.ok || !Array.isArray(json.data)) return;
                const list = document.getElementById("chatHistoryList");
                list.innerHTML = "";
                if (json.data.length === 0) {
                    list.innerHTML = '<li class="chat-empty">No chat history yet.</li>';
                    return;
                }
                json.data.forEach(h => {
                    const li = document.createElement("li");
                    li.textContent = h.question;
                    li.onclick = () => loadPastQuestion(h.id);
                    list.appendChild(li);
                });
            });
        }

        function loadPastQuestion(chatId) {
            fetch(CONTROLLER, {
                method: "POST",
                headers: { "X-Requested-With": "XMLHttpRequest" },
                body: new URLSearchParams({ action: "view_chat", chatId: chatId })
            })
            .then(res => res.json())
            .then(json => {
                if (json.ok && json.data) {
                    document.getElementById("chatMessages").innerHTML = "";
                    appendBubble(json.data.question, "user");
                    appendBubble(json.data.answer, "bot");
                }
            });
        }

                // ===== Clear Chat History =====
        function clearChatHistory() {
            const list = document.getElementById("chatHistoryList");
            const isEmpty = list.querySelector(".chat-empty") !== null;
            if (isEmpty) {
                showToast("You have no chat history");
                return;
            }
            document.getElementById("clearConfirmModal").style.display = "flex";
        }

        function closeClearModal() {
            document.getElementById("clearConfirmModal").style.display = "none";
        }

        function confirmClearChat() {
            fetch(CONTROLLER, {
                method: "POST",
                headers: { "X-Requested-With": "XMLHttpRequest" },
                body: new URLSearchParams({ action: "clear" })
            })
            .then(res => res.json())
            .then(json => {
                closeClearModal();
                if (json.ok) {
                    document.getElementById("chatHistoryList").innerHTML =
                        '<li class="chat-empty">No chat history yet.</li>';
                    document.getElementById("chatMessages").innerHTML = "";
                    showToast("Chat history cleared successfully");
                } else {
                    showToast("Unable to clear chat history. Please try again later.");
                }
            })
            .catch(() => {
                closeClearModal();
                showToast("Unable to clear chat history. Please try again later.");
            });
        }

        // ===== New Chat =====
        function newChat() {
            document.getElementById("chatMessages").innerHTML = "";
            document.getElementById("questionInput").value = "";
            document.getElementById("questionInput").focus();
            fetch(CONTROLLER, {
                method: "POST",
                headers: { "X-Requested-With": "XMLHttpRequest" },
                body: new URLSearchParams({ action: "new_session" })
            }).catch(() => {});
        }

        // ===== Toast =====
        function showToast(message) {
            const toast = document.getElementById("successToast");
            const text = document.getElementById("successToastText");
            if (!toast || !text) {
                alert(message);
                return;
            }
            text.textContent = message;
            toast.style.display = "flex";
            setTimeout(() => { toast.style.display = "none"; }, 2500);
        }
    </script>
</body>

</html>