<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: loginPage.php");
    exit();
}

require_once "../controller/AIChatbotController.php";

$controller = new AIChatbotController();
$studentId = $_SESSION['user_id'];

// ===== Session timeout: if user was away > 30 min, start fresh =====
$SESSION_RESET_TIMEOUT = 1800; // 30 minutes
$lastActive = $_SESSION['chat_last_active'] ?? 0;
$shouldStartFresh = false;

if ($lastActive > 0 && (time() - $lastActive) > $SESSION_RESET_TIMEOUT) {
    $shouldStartFresh = true;
    unset($_SESSION['chat_session_id']);
}
$_SESSION['chat_last_active'] = time();

$sessions = $controller->getChatSessions($studentId);
if ($sessions === false)
    $sessions = [];

$rawRole = $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'student';
$userRole = strtolower(trim($rawRole));

switch ($userRole) {
    case 'lecturer':
        $dashboardUrl = 'lecturerDashboardPage.php';
        break;
    case 'course_coordinator':
    case 'course coordinator':
        $dashboardUrl = 'courseCoordinatorDashboardPage.php';
        break;
    case 'university_admin':
    case 'university admin':
        $dashboardUrl = 'universityAdminDashboardPage.php';
        break;
    case 'system_admin':
    case 'system admin':
        $dashboardUrl = 'systemAdminDashboardPage.php';
        break;
    case 'student':
    default:
        $dashboardUrl = 'studentDashboardPage.php';
        break;
}

$showNotifications = in_array($userRole, ['student', 'lecturer']);

$initialMessages = [];
if (!$shouldStartFresh && !empty($sessions)) {
    $initialMessages = $controller->getChatsBySession($sessions[0]['sessionId'], $studentId);
    if ($initialMessages === false)
        $initialMessages = [];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Chatbot - UniBee</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .chat-history-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 14px;
            border-bottom: 1px solid #e0e0e0;
            flex-shrink: 0;
        }

        .chat-history-header .btn-back {
            padding: 0;
            width: 32px;
            height: 32px;
            min-width: 32px;
            border-radius: 50%;
            font-size: 1rem;
            font-weight: 900;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: none;
            flex-shrink: 0;
        }

        .chat-history-header h3 {
            margin: 0;
            flex: 1;
            font-size: 1.15rem;
            font-weight: 700;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .chat-history-header .btn-clear-chat {
            flex-shrink: 0;
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            color: #333;
            padding: 5px;
            border-radius: 6px;
            transition: 0.2s ease;
        }

        .chat-history-header .btn-clear-chat:hover {
            background-color: #ffb3b3;
        }

        .chat-session-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
        }

        .chat-session-item .session-title {
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .chat-session-item .session-count {
            background: #e0e0e0;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
            color: #666;
            flex-shrink: 0;
        }

        .chat-session-item.active {
            background-color: #f8ea9b;
            border-color: #e6c23a;
        }

        .session-tooltip {
            position: fixed;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.12);
            padding: 8px 0;
            max-width: 320px;
            z-index: 9999;
            font-size: 0.82rem;
        }

        .session-tooltip-row {
            padding: 6px 14px;
            color: #333;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 320px;
            cursor: pointer;
            transition: background-color 0.15s ease;
        }

        .session-tooltip-row:hover {
            background-color: #f8ea9b;
            color: #1e40af;
        }

        .session-tooltip-row+.session-tooltip-row {
            border-top: 1px solid #f0f0f0;
        }

        html,
        body {
            height: 100%;
            overflow: hidden;
        }

        body {
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        header {
            flex-shrink: 0;
        }

        footer {
            flex-shrink: 0;
        }

        .chatbot-layout.chatbot-full-width {
            flex: 1;
            min-height: 0;
            height: auto !important;
            overflow: hidden;
            margin: 0;
            padding: 0;
            max-width: none;
            width: 100%;
        }

        .chat-history-panel {
            min-height: 0;
            overflow: hidden;
            flex-shrink: 0;
        }

        .chat-history-list {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            padding-right: 4px;
        }

        .btn-new-chat {
            flex-shrink: 0;
        }

        .chat-main {
            display: flex;
            flex-direction: column;
            min-height: 0;
            overflow: hidden;
        }

        .chat-main h2 {
            flex-shrink: 0;
        }

        .chat-messages {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 16px;
            padding: 10px 0;
        }

        .chat-input-bar {
            flex-shrink: 0;
            display: flex;
            align-items: flex-end;
            gap: 12px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .chat-input-bar textarea {
            flex: 1;
            min-height: 44px;
            max-height: 160px;
            padding: 12px 20px;
            border: 1px solid #ccc;
            border-radius: 22px;
            font-size: 0.95rem;
            font-family: inherit;
            line-height: 1.4;
            background: #fafafa;
            resize: none;
            overflow-y: auto;
            box-sizing: border-box;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .chat-input-bar textarea:focus {
            outline: none;
            border-color: var(--bg-yellow);
            box-shadow: 0 0 0 3px rgba(255, 216, 72, 0.2);
        }

        .chat-input-bar .btn-send {
            flex-shrink: 0;
            padding: 12px 28px;
            height: 44px;
            border: none;
            border-radius: 22px;
            background-color: var(--bg-yellow);
            color: #222;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .chat-input-bar .btn-send:hover {
            background-color: #e6c23a;
        }

        .chat-bubble {
            max-width: 85%;
            padding: 12px 18px;
            border-radius: 14px;
            font-size: 0.92rem;
            line-height: 1.55;
            word-wrap: break-word;
            white-space: pre-line;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            transition: outline 0.2s ease;
        }

        .chat-bubble.chat-highlight {
            outline: 3px solid var(--bg-yellow);
            outline-offset: 3px;
        }

        .user-bubble {
            align-self: flex-end;
            background-color: var(--bg-yellow);
            color: #222;
            border-bottom-right-radius: 4px;
        }

        .bot-bubble {
            align-self: flex-start;
            background-color: #f5f5f5;
            color: #222;
            border-bottom-left-radius: 4px;
            border: none;
        }
    </style>
</head>

<body>
    <script src="../script.js"></script>

    <header class="header">
        <div class="logo-container">
            <a href="<?php echo $dashboardUrl; ?>">
                <img src="../images/uniBeeLogo.png" alt="UniBee Logo">
            </a>
        </div>

        <div class="welcome-message">
            <span>Welcome back,</span>
            <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>
        </div>

        <div class="dashboard-header-right">
            <?php if ($showNotifications): ?>
                <a href="NotificationPage.php" class="header-icon">
                    <img src="../images/notification.png" alt="Notifications">
                </a>
            <?php endif; ?>

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

        <aside class="chat-history-panel">
            <div class="chat-history-header">
                <a href="<?php echo htmlspecialchars($dashboardUrl); ?>" class="btn-back">&#8592;</a>
                <h3>Chat History</h3>
                <button type="button" class="btn-clear-chat" title="Clear this chat session"
                    onclick="clearChatHistory()">
                    &#128465;
                </button>
            </div>

            <ul class="chat-history-list" id="chatHistoryList">
                <?php if (empty($sessions)): ?>
                    <li class="chat-empty">No chat history yet.</li>
                <?php else: ?>
                    <?php $markActive = !$shouldStartFresh; ?>
                    <?php $firstSessionId = $sessions[0]['sessionId']; ?>
                    <?php foreach ($sessions as $s): ?>
                        <li class="chat-session-item <?php echo ($markActive && $s['sessionId'] == $firstSessionId) ? 'active' : ''; ?>"
                            data-session-id="<?php echo (int) $s['sessionId']; ?>"
                            data-questions='<?php echo htmlspecialchars(json_encode($s['questions']), ENT_QUOTES); ?>'
                            onclick="loadSession(<?php echo (int) $s['sessionId']; ?>)">
                            <span class="session-title"><?php echo htmlspecialchars($s['title']); ?></span>
                            <span class="session-count"><?php echo count($s['questions']); ?></span>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>

            <button type="button" class="btn-new-chat" onclick="newChat()">+ New chat</button>
        </aside>

        <section class="chat-main">
            <h2>Ask a Question</h2>

            <div class="chat-messages" id="chatMessages">
                <?php if (!empty($initialMessages)): ?>     <?php foreach ($initialMessages as $m): ?>
                        <div class="chat-bubble user-bubble" data-chat-id="<?php echo (int) $m['id']; ?>">
                            <?php echo htmlspecialchars(trim($m['question'])); ?>
                        </div>
                        <div class="chat-bubble bot-bubble" data-chat-id="<?php echo (int) $m['id']; ?>">
                            <?php echo htmlspecialchars(trim($m['answer'])); ?>
                        </div><?php endforeach; ?><?php endif; ?>
            </div>

            <div class="chat-input-bar">
                <textarea id="questionInput" placeholder="Type your Question..." rows="1"></textarea>
                <button type="button" class="btn-send" onclick="sendQuestion()">Send</button>
            </div>
        </section>

    </main>

    <div class="modal-overlay" id="clearConfirmModal" style="display:none;">
        <div class="modal-box">
            <span class="modal-close" onclick="closeClearModal()">&times;</span>
            <p class="modal-message">Are you sure you want to clear this chat session?</p>
            <div class="clear-actions">
                <button type="button" class="btn-cancel-clear" onclick="closeClearModal()">Cancel</button>
                <button type="button" class="btn-confirm-clear" onclick="confirmClearChat()">Clear</button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="successToast" style="display:none;">
        <div class="modal-box">
            <span class="modal-close"
                onclick="document.getElementById('successToast').style.display='none'">&times;</span>
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

        let currentSessionId = null;

        function autoResizeInput() {
            const ta = document.getElementById("questionInput");
            ta.style.height = 'auto';
            ta.style.height = Math.min(ta.scrollHeight, 160) + 'px';
        }

        document.getElementById("questionInput").addEventListener("input", autoResizeInput);

        document.getElementById("questionInput").addEventListener("keydown", function (e) {
            if (e.key === "Enter" && !e.shiftKey) {
                e.preventDefault();
                sendQuestion();
            }
        });

        function sendQuestion() {
            const input = document.getElementById("questionInput");
            const question = input.value.trim();
            if (!question) return;

            appendBubble(question, "user");
            input.value = "";
            input.style.height = "44px";

            fetch(CONTROLLER, {
                method: "POST",
                headers: { "X-Requested-With": "XMLHttpRequest" },
                body: new URLSearchParams({ action: "ask", question: question })
            })
                .then(res => res.json())
                .then(json => {
                    if (json.ok) {
                        renderRichResponse(json.data);
                        refreshSessions();
                    } else {
                        appendBubble("Unable to process your question. Please try again later.", "bot");
                    }
                })
                .catch(() => {
                    appendBubble("Unable to process your question. Please try again later.", "bot");
                });
        }

        function appendBubble(text, type, source, chatId) {
            const box = document.getElementById("chatMessages");
            const bubble = document.createElement("div");
            bubble.className = "chat-bubble " + (type === "user" ? "user-bubble" : "bot-bubble");
            bubble.textContent = (text || '').replace(/^\s+/, '').replace(/\s+$/, '');

            if (chatId) {
                bubble.setAttribute("data-chat-id", chatId);
            }

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

            if (data.action && data.action.url) {
                const btn = document.createElement("a");
                btn.className = "chat-action-btn";
                btn.href = data.action.url;
                btn.textContent = data.action.label;
                bubble.appendChild(btn);
            }
        }

        function attachTooltip(li) {
            let hideTimer = null;

            function hide() {
                if (li._tooltip) {
                    li._tooltip.remove();
                    li._tooltip = null;
                }
            }

            function show() {
                if (li._tooltip) return;

                const qJson = li.getAttribute('data-questions');
                if (!qJson) return;

                let questions;
                try { questions = JSON.parse(qJson); } catch (e) { return; }
                if (!questions || !questions.length) return;

                const sessionId = li.getAttribute('data-session-id');

                const tooltip = document.createElement('div');
                tooltip.className = 'session-tooltip';

                questions.forEach(function (q) {
                    const row = document.createElement('div');
                    row.className = 'session-tooltip-row';
                    row.textContent = q.question;
                    row.setAttribute('title', 'Click to jump to this question');
                    row.addEventListener('click', function (e) {
                        e.stopPropagation();
                        hide();
                        jumpToMessage(sessionId, q.id);
                    });
                    tooltip.appendChild(row);
                });

                document.body.appendChild(tooltip);

                const rect = li.getBoundingClientRect();
                tooltip.style.top = rect.top + 'px';
                tooltip.style.left = (rect.right + 8) + 'px';

                requestAnimationFrame(function () {
                    const tRect = tooltip.getBoundingClientRect();
                    if (tRect.bottom > window.innerHeight) {
                        tooltip.style.top = (window.innerHeight - tRect.height - 10) + 'px';
                    }
                });

                tooltip.addEventListener('mouseenter', function () {
                    if (hideTimer) { clearTimeout(hideTimer); hideTimer = null; }
                });
                tooltip.addEventListener('mouseleave', function () {
                    hideTimer = setTimeout(hide, 150);
                });

                li._tooltip = tooltip;
            }

            li.addEventListener('mouseenter', function () {
                if (hideTimer) { clearTimeout(hideTimer); hideTimer = null; }
                show();
            });

            li.addEventListener('mouseleave', function () {
                hideTimer = setTimeout(hide, 150);
            });
        }

        function jumpToMessage(sessionId, chatId) {
            if (String(currentSessionId) !== String(sessionId)) {
                loadSession(sessionId, function () {
                    setTimeout(function () { scrollToChat(chatId); }, 80);
                });
            } else {
                scrollToChat(chatId);
            }
        }

        function scrollToChat(chatId) {
            const el = document.querySelector('.chat-bubble[data-chat-id="' + chatId + '"]');
            if (!el) return;
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            el.classList.add('chat-highlight');
            setTimeout(function () {
                el.classList.remove('chat-highlight');
            }, 1600);
        }

        function refreshSessions() {
            fetch(CONTROLLER + "?action=sessions", {
                headers: { "X-Requested-With": "XMLHttpRequest" }
            })
                .then(res => res.json())
                .then(json => {
                    if (!json.ok || !Array.isArray(json.data)) return;
                    const list = document.getElementById("chatHistoryList");
                    list.innerHTML = "";
                    if (json.data.length === 0) {
                        list.innerHTML = '<li class="chat-empty">No chat history yet.</li>';
                        currentSessionId = null;
                        return;
                    }
                    json.data.forEach(function (s, idx) {
                        let isActive;
                        if (currentSessionId !== null) {
                            isActive = String(s.sessionId) === String(currentSessionId);
                        } else {
                            isActive = idx === 0;
                            if (isActive) currentSessionId = s.sessionId;
                        }

                        const li = document.createElement("li");
                        li.className = "chat-session-item" + (isActive ? " active" : "");
                        li.setAttribute("data-session-id", s.sessionId);
                        li.setAttribute("data-questions", JSON.stringify(s.questions));

                        const title = document.createElement("span");
                        title.className = "session-title";
                        title.textContent = s.title;

                        const count = document.createElement("span");
                        count.className = "session-count";
                        count.textContent = s.questions.length;

                        li.appendChild(title);
                        li.appendChild(count);
                        li.onclick = function () { loadSession(s.sessionId); };

                        attachTooltip(li);
                        list.appendChild(li);
                    });
                });
        }

        function loadSession(sessionId, callback) {
            currentSessionId = sessionId;
            fetch(CONTROLLER, {
                method: "POST",
                headers: { "X-Requested-With": "XMLHttpRequest" },
                body: new URLSearchParams({ action: "view_session", sessionId: sessionId })
            })
                .then(res => res.json())
                .then(json => {
                    if (json.ok && Array.isArray(json.data)) {
                        const box = document.getElementById("chatMessages");
                        box.innerHTML = "";
                        json.data.forEach(function (m) {
                            appendBubble(m.question, "user", null, m.id);
                            appendBubble(m.answer, "bot", m.source, m.id);
                        });
                        document.querySelectorAll('.chat-session-item').forEach(x => x.classList.remove('active'));
                        const el = document.querySelector('.chat-session-item[data-session-id="' + sessionId + '"]');
                        if (el) el.classList.add('active');
                        if (typeof callback === 'function') callback();
                    }
                });
        }

        function clearChatHistory() {
            if (currentSessionId === null) {
                showToast("No chat session selected");
                return;
            }
            document.getElementById("clearConfirmModal").style.display = "flex";
        }

        function closeClearModal() {
            document.getElementById("clearConfirmModal").style.display = "none";
        }

        function confirmClearChat() {
            const sid = currentSessionId;
            if (sid === null) {
                closeClearModal();
                return;
            }

            fetch(CONTROLLER, {
                method: "POST",
                headers: { "X-Requested-With": "XMLHttpRequest" },
                body: new URLSearchParams({ action: "clear_session", sessionId: sid })
            })
                .then(res => res.json())
                .then(json => {
                    closeClearModal();
                    if (json.ok) {
                        const el = document.querySelector('.chat-session-item[data-session-id="' + sid + '"]');
                        if (el) el.remove();

                        document.getElementById("chatMessages").innerHTML = "";
                        currentSessionId = null;

                        if (!document.querySelector('.chat-session-item')) {
                            document.getElementById("chatHistoryList").innerHTML =
                                '<li class="chat-empty">No chat history yet.</li>';
                        }

                        showToast("Chat session cleared successfully");
                    } else {
                        showToast("Unable to clear chat session. Please try again later.");
                    }
                })
                .catch(() => {
                    closeClearModal();
                    showToast("Unable to clear chat session. Please try again later.");
                });
        }

        function newChat() {
            currentSessionId = null;
            document.getElementById("chatMessages").innerHTML = "";
            document.getElementById("questionInput").value = "";
            document.getElementById("questionInput").style.height = "44px";
            document.getElementById("questionInput").focus();
            document.querySelectorAll('.chat-session-item').forEach(x => x.classList.remove('active'));
            fetch(CONTROLLER, {
                method: "POST",
                headers: { "X-Requested-With": "XMLHttpRequest" },
                body: new URLSearchParams({ action: "new_session" })
            }).catch(() => { });
        }

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

        document.addEventListener('DOMContentLoaded', function () {
            const activeEl = document.querySelector('.chat-session-item.active');
            if (activeEl) {
                currentSessionId = activeEl.getAttribute('data-session-id');
            }
            document.querySelectorAll('.chat-session-item').forEach(attachTooltip);
        });
    </script>
</body>

</html>