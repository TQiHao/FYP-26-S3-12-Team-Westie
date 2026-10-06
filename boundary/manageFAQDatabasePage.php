<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: loginPage.php");
    exit();
}

$userRole = strtolower($_SESSION['user_role'] ?? $_SESSION['role'] ?? '');
if ($userRole !== 'university_admin' && $userRole !== 'university admin') {
    header("Location: loginPage.php");
    exit();
}

require_once "../controller/manageFAQDatabaseController.php";

$controller = new ManageFAQDatabaseController();
$universityId = (int) ($_SESSION['university_id'] ?? 0);
$userId = (int) ($_SESSION['user_id'] ?? 0);

$activeTab = $_GET['tab'] ?? 'create';
if ($activeTab !== 'view')
    $activeTab = 'create';

$searchQuery = trim($_GET['search'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'import_csv') {
        if (!empty($_FILES['csv_file']['tmp_name'])) {
            $result = $controller->importFromCSV($universityId, $userId, $_FILES['csv_file']['tmp_name']);

            if ($result['status'] === 'ok') {
                $msg = "Imported {$result['imported']} FAQ(s).";
                if ($result['skipped'] > 0) {
                    $msg .= " Skipped {$result['skipped']} row(s).";
                    $_SESSION['faq_skip_reasons'] = $result['reasons'];
                }
                $_SESSION['flash_message'] = $msg;
            } else {
                $_SESSION['flash_error'] = $result['message'];
            }
        } else {
            $_SESSION['flash_error'] = 'Please choose a CSV file before uploading.';
        }
        header("Location: manageFAQDatabasePage.php?tab=view");
        exit();
    }

    if ($action === 'update_faq') {
        $id = (int) ($_POST['faq_id'] ?? 0);
        $question = trim($_POST['question'] ?? '');
        $answer = trim($_POST['answer'] ?? '');

        $result = $controller->updateFAQ($id, $universityId, $question, $answer);
        if ($result['status'] === 'ok') {
            $_SESSION['flash_message'] = 'FAQ updated successfully.';
        } else {
            $_SESSION['flash_error'] = $result['message'];
        }
        header("Location: manageFAQDatabasePage.php?tab=view");
        exit();
    }

    if ($action === 'delete_faq') {
        $id = (int) ($_POST['faq_id'] ?? 0);
        if ($controller->deleteFAQ($id, $universityId)) {
            $_SESSION['flash_message'] = 'FAQ deleted successfully.';
        } else {
            $_SESSION['flash_error'] = 'Failed to delete FAQ.';
        }
        header("Location: manageFAQDatabasePage.php?tab=view");
        exit();
    }
}

$faqList = ($activeTab === 'view')
    ? $controller->getAllFAQs($universityId, $searchQuery)
    : [];

$flashMessage = $_SESSION['flash_message'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
$skipReasons = $_SESSION['faq_skip_reasons'] ?? [];
unset($_SESSION['flash_message'], $_SESSION['flash_error'], $_SESSION['faq_skip_reasons']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage FAQ Database - UniBee</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .profile-header {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%;
            min-height: 45px;
            margin: 15px 0 25px 0;
        }


        .profile-header .section-label {
            position: absolute;
            left: 70%;
            transform: translateX(-50%);
            margin: 0 !important;
            text-align: center !important;
            white-space: nowrap;
        }

        .faq-page {
            width: 80%;
            max-width: 1050px;
            margin: 0 auto;
            padding: 30px 0 60px;
            box-sizing: border-box;
        }

        .faq-page .page-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 22px;
        }

        .faq-page .page-top h1 {
            font-size: 1.3rem;
            font-weight: 700;
            margin: 0;
        }

        .faq-tabs {
            display: flex;
            gap: 40px;
            border-bottom: 1px solid #ddd;
            margin-bottom: 26px;
        }

        .faq-tab {
            padding: 10px 4px;
            font-size: 1.05rem;
            font-weight: 700;
            color: #888;
            text-decoration: none;
            border-bottom: 3px solid transparent;
            margin-bottom: -1px;
        }

        .faq-tab.active {
            color: #000;
            border-bottom-color: var(--bg-yellow);
        }

        .faq-tab:hover {
            color: #000;
            text-decoration: none;
        }

        .alert-box {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .alert-success {
            background: #dcfce7;
            border: 1px solid #86efac;
            color: #166534;
        }

        .alert-danger {
            background: #fee2e2;
            border: 1px solid #fca5a5;
            color: #991b1b;
        }

        .alert-warning {
            background: #fef3c7;
            border: 1px solid #fcd34d;
            color: #92400e;
        }

        .alert-warning ul {
            margin: 8px 0 0 18px;
            padding: 0;
        }

        /* ===== Create tab ===== */
        .template-card {
            border: 1px solid #ccc;
            border-radius: 10px;
            padding: 22px 26px;
            background: #fafafa;
            margin-bottom: 24px;
        }

        .template-card h2 {
            font-size: 1.05rem;
            font-weight: 700;
            margin: 0 0 6px 0;
        }

        .template-card p {
            font-size: 0.85rem;
            color: #555;
            margin: 0 0 14px 0;
        }

        .template-card pre {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 12px 14px;
            font-family: Consolas, "Courier New", monospace;
            font-size: 0.8rem;
            line-height: 1.5;
            white-space: pre-wrap;
            word-break: break-word;
            color: #222;
            margin: 0 0 14px 0;
        }

        .template-rules {
            font-size: 0.8rem;
            color: #555;
            padding-left: 18px;
            margin: 0 0 16px 0;
        }

        .template-rules li {
            margin-bottom: 4px;
        }

        .btn-download {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 20px;
            border: 1px solid #777;
            background: #fff;
            color: #222;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 700;
        }

        .btn-download:hover {
            background: #f8ea9b;
            text-decoration: none;
        }

        .upload-card {
            border: 1px solid #ccc;
            border-radius: 10px;
            padding: 22px 26px;
            background: #fff;
        }

        .upload-card h2 {
            font-size: 1.05rem;
            font-weight: 700;
            margin: 0 0 4px 0;
        }

        .upload-card p {
            font-size: 0.85rem;
            color: #555;
            margin: 0 0 16px 0;
        }

        .upload-row {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .upload-row input[type="file"] {
            padding: 8px;
            border: 1px solid #bbb;
            border-radius: 6px;
            font-size: 0.85rem;
            background: #fafafa;
        }

        .btn-upload {
            padding: 10px 26px;
            border: 1px solid #000;
            border-radius: 20px;
            background: var(--bg-yellow);
            font-size: 0.85rem;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-upload:hover {
            background: #e6c23a;
        }

        /* ===== View tab ===== */
        .faq-search-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 22px;
        }

        .faq-search-bar input {
            flex: 1;
            padding: 11px 18px;
            border: 1px solid #ccc;
            border-radius: 25px;
            font-size: 0.9rem;
            background: #fafafa;
            box-sizing: border-box;
        }

        .faq-search-bar input:focus {
            outline: none;
            border-color: var(--bg-yellow);
            box-shadow: 0 0 0 3px rgba(255, 216, 72, 0.2);
        }

        .faq-search-bar .btn-search {
            padding: 10px 26px;
            background: var(--bg-yellow);
            color: #222;
            border: none;
            border-radius: 25px;
            font-weight: 700;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .faq-search-bar .btn-search:hover {
            background: #e6c23a;
        }

        .faq-search-bar .btn-clear {
            padding: 10px 20px;
            background: #e0e0e0;
            color: #333;
            border: none;
            border-radius: 25px;
            font-weight: 700;
            font-size: 0.9rem;
            text-decoration: none;
            cursor: pointer;
        }

        .faq-search-bar .btn-clear:hover {
            background: #ccc;
            text-decoration: none;
        }

        .faq-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .faq-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            border: 1px solid #ccc;
            border-radius: 10px;
            padding: 18px 22px;
            background: #fff;
            transition: 0.2s ease;
        }

        .faq-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.07);
        }

        .faq-info {
            flex: 1;
            min-width: 0;
        }

        .faq-info h3 {
            font-size: 1rem;
            font-weight: 700;
            margin: 0 0 5px 0;
            color: #222;
        }

        .faq-info p {
            font-size: 0.82rem;
            color: #666;
            margin: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .faq-actions {
            display: flex;
            gap: 8px;
            flex-shrink: 0;
        }

        .faq-actions button {
            padding: 7px 16px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 0.78rem;
            font-weight: 700;
        }

        .btn-faq-view {
            background: #fde047;
            color: #222;
        }

        .btn-faq-update {
            background: #4ade80;
            color: #064e3b;
        }

        .btn-faq-delete {
            background: #ef4444;
            color: #fff;
        }

        .btn-faq-view:hover {
            background: #e6c23a;
        }

        .btn-faq-update:hover {
            background: #22c55e;
            color: #fff;
        }

        .btn-faq-delete:hover {
            background: #dc2626;
        }

        .empty-faq {
            padding: 60px 20px;
            text-align: center;
            color: #888;
            font-size: 0.95rem;
            border: 1px dashed #ccc;
            border-radius: 10px;
        }

        /* ===== Modals ===== */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-card {
            background: #fff;
            border-radius: 12px;
            width: 90%;
            max-width: 560px;
            padding: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
            max-height: 85vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 700;
        }

        .modal-close {
            cursor: pointer;
            font-size: 1.4rem;
            line-height: 1;
            color: #666;
        }

        .modal-close:hover {
            color: #000;
        }

        .modal-field {
            margin-bottom: 14px;
        }

        .modal-field label {
            display: block;
            font-size: 0.8rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .modal-field textarea,
        .modal-field input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 0.9rem;
            font-family: inherit;
            box-sizing: border-box;
            background: #fafafa;
        }

        .modal-field textarea {
            min-height: 90px;
            resize: vertical;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 18px;
        }

        .modal-btn {
            padding: 9px 22px;
            border-radius: 20px;
            border: none;
            font-weight: 700;
            font-size: 0.85rem;
            cursor: pointer;
        }

        .modal-btn-cancel {
            background: #e0e0e0;
            color: #333;
        }

        .modal-btn-save {
            background: var(--bg-yellow);
            color: #222;
        }

        .modal-btn-delete {
            background: #ef4444;
            color: #fff;
        }

        .modal-btn-cancel:hover {
            background: #ccc;
        }

        .modal-btn-save:hover {
            background: #e6c23a;
        }

        .modal-btn-delete:hover {
            background: #dc2626;
        }

        @media (max-width: 768px) {
            .faq-page {
                width: 90%;
            }

            .faq-card {
                flex-direction: column;
                align-items: flex-start;
            }

            .faq-actions {
                width: 100%;
            }

            .faq-actions button {
                flex: 1;
            }
        }
    </style>
</head>

<body>

    <script src="../script.js"></script>

    <header class="header">
        <div class="logo-container">
            <a href="universityAdminDashboardPage.php">
                <img src="../images/uniBeeLogo.png" alt="UniBee Logo">
            </a>
        </div>

        <div class="welcome-message">
            <span>Welcome back,</span>
            <strong>
                <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'University Admin'); ?>
            </strong>
        </div>

        <div class="dashboard-header-right">
            <div class="profile-dropdown">
                <div class="profile-container" onclick="toggleDropdown()">
                    <img src="../images/profilePic.png" alt="Profile" class="profile-icon">
                    <img src="../images/dropdown.png" alt="Menu" class="dropdown-arrow">
                </div>
                <div id="profileMenu" class="dropdown-menu">
                    <a href="renewLicensePage.php">Renew License</a>
                    <a href="aiChatbotPage.php">AI Chatbot</a>
                    <a href="submitFeedbackPage.php">Submit Feedback</a>
                    <a href="../controller/logoutController.php">Log Out</a>
                </div>
            </div>
        </div>
    </header>

    <main class="faq-page">

        <div class="profile-header">
            <a href="universityAdminDashboardPage.php" class="btn-back">&#8592; Back</a>
            <h1 class="section-label">Manage FAQ Database</h1>
        </div>

        <?php if ($flashMessage): ?>
            <div class="alert-box alert-success">
                <?php echo htmlspecialchars($flashMessage); ?>
            </div>
        <?php endif; ?>

        <?php if ($flashError): ?>
            <div class="alert-box alert-danger">
                <?php echo htmlspecialchars($flashError); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($skipReasons)): ?>
            <div class="alert-box alert-warning">
                <strong>Some rows were skipped during import:</strong>
                <ul>
                    <?php foreach ($skipReasons as $reason): ?>
                        <li>
                            <?php echo htmlspecialchars($reason); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="faq-tabs">
            <a class="faq-tab <?php echo $activeTab === 'create' ? 'active' : ''; ?>"
                href="manageFAQDatabasePage.php?tab=create">Create FAQ</a>
            <a class="faq-tab <?php echo $activeTab === 'view' ? 'active' : ''; ?>"
                href="manageFAQDatabasePage.php?tab=view">View FAQ</a>
        </div>

        <?php if ($activeTab === 'create'): ?>

            <div class="template-card">
                <h2>CSV Template Guide</h2>
                <p>Prepare your FAQ file using the format shown below. Save it as a <strong>.csv</strong> file.</p>

                <pre>question,answer
    "What are the library operating hours?","The library is open Monday to Friday from 8:00 AM to 10:00 PM, and Saturday from 9:00 AM to 6:00 PM. It is closed on Sundays and public holidays."
    "Where is the food court located?","The main food court is on Level 2 of the Student Centre, next to the bookstore."
    "How do I book a study room?","Go to Facilities Booking > Book Facilities > Study Rooms, pick a slot, and confirm your booking."
    "Who do I contact for IT support?","Please call the IT Helpdesk hotline at +65 6123 4567, or email ithelp@university.edu."</pre>
                <ul class="template-rules">
                    <li>The file must have a header row: <code>question,answer</code>.</li>
                    <li>Every row must contain both a question and an answer.</li>
                    <li>Wrap fields in double quotes if they contain commas.</li>
                    <li>Only <strong>school-related</strong> content is allowed. Rows containing inappropriate or negative
                        language will be automatically rejected during import.</li>
                </ul>

                <a class="btn-download" href="../csv/faq_template.csv" download>Download Template</a>
            </div>

            <div class="upload-card">
                <h2>Upload FAQ CSV</h2>
                <p>Select your prepared CSV file and click Upload to import FAQs.</p>
                <form method="POST" action="manageFAQDatabasePage.php" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="import_csv">
                    <div class="upload-row">
                        <input type="file" name="csv_file" accept=".csv" required>
                        <button type="submit" class="btn-upload">Upload FAQ</button>
                    </div>
                </form>
            </div>

        <?php else: ?>

            <form class="faq-search-bar" method="GET" action="manageFAQDatabasePage.php">
                <input type="hidden" name="tab" value="view">
                <input type="text" name="search" placeholder="Search FAQ by question or answer..."
                    value="<?php echo htmlspecialchars($searchQuery); ?>">
                <button type="submit" class="btn-search">Search</button>
                <?php if ($searchQuery !== ''): ?>
                    <a class="btn-clear" href="manageFAQDatabasePage.php?tab=view">Clear</a>
                <?php endif; ?>
            </form>

            <?php if (empty($faqList)): ?>
                <div class="empty-faq">
                    <?php if ($searchQuery !== ''): ?>
                        No FAQs match your search.
                    <?php else: ?>
                        No FAQs yet. Go to the <strong>Create FAQ</strong> tab to import some.
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="faq-list">
                    <?php foreach ($faqList as $faq): ?>
                        <div class="faq-card">
                            <div class="faq-info">
                                <h3>
                                    <?php echo htmlspecialchars($faq->getQuestion()); ?>
                                </h3>
                                <p>
                                    <?php echo htmlspecialchars($faq->getAnswer()); ?>
                                </p>
                            </div>
                            <div class="faq-actions">
                                <button type="button" class="btn-faq-view"
                                    onclick='openViewFAQ(<?php echo htmlspecialchars(json_encode($faq->toArray()), ENT_QUOTES); ?>)'>View</button>
                                <button type="button" class="btn-faq-update"
                                    onclick='openUpdateFAQ(<?php echo htmlspecialchars(json_encode($faq->toArray()), ENT_QUOTES); ?>)'>Update</button>
                                <button type="button" class="btn-faq-delete"
                                    onclick="openDeleteFAQ(<?php echo (int) $faq->getId(); ?>)">Delete</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php endif; ?>

    </main>

    <div id="viewFAQModal" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-header">
                <h3>FAQ Details</h3>
                <span class="modal-close" onclick="closeModal('viewFAQModal')">&times;</span>
            </div>
            <div class="modal-field">
                <label>Question</label>
                <div id="viewFAQQuestion" style="font-size:0.9rem; color:#222;"></div>
            </div>
            <div class="modal-field">
                <label>Answer</label>
                <div id="viewFAQAnswer" style="font-size:0.9rem; color:#333; line-height:1.5;"></div>
            </div>
            <div class="modal-actions">
                <button type="button" class="modal-btn modal-btn-cancel"
                    onclick="closeModal('viewFAQModal')">Close</button>
            </div>
        </div>
    </div>

    <div id="updateFAQModal" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-header">
                <h3>Update FAQ</h3>
                <span class="modal-close" onclick="closeModal('updateFAQModal')">&times;</span>
            </div>
            <form method="POST" action="manageFAQDatabasePage.php">
                <input type="hidden" name="action" value="update_faq">
                <input type="hidden" name="faq_id" id="updateFAQId">
                <div class="modal-field">
                    <label>Question</label>
                    <textarea name="question" id="updateFAQQuestion" required></textarea>
                </div>
                <div class="modal-field">
                    <label>Answer</label>
                    <textarea name="answer" id="updateFAQAnswer" required></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="modal-btn modal-btn-cancel"
                        onclick="closeModal('updateFAQModal')">Cancel</button>
                    <button type="submit" class="modal-btn modal-btn-save">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <div id="deleteFAQModal" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-header">
                <h3>Delete FAQ</h3>
                <span class="modal-close" onclick="closeModal('deleteFAQModal')">&times;</span>
            </div>
            <p style="font-size:0.9rem; color:#444; margin:0 0 8px 0;">
                Are you sure you want to delete this FAQ? This action cannot be undone.
            </p>
            <form method="POST" action="manageFAQDatabasePage.php">
                <input type="hidden" name="action" value="delete_faq">
                <input type="hidden" name="faq_id" id="deleteFAQId">
                <div class="modal-actions">
                    <button type="button" class="modal-btn modal-btn-cancel"
                        onclick="closeModal('deleteFAQModal')">Cancel</button>
                    <button type="submit" class="modal-btn modal-btn-delete">Delete</button>
                </div>
            </form>
        </div>
    </div>

    <footer>
        <div class="footer-bottom-bar">
            &copy; 2026 UniBee. All rights reserved.
        </div>
    </footer>

    <script>
        function openModal(id) { document.getElementById(id).classList.add('active'); }
        function closeModal(id) { document.getElementById(id).classList.remove('active'); }

        function openViewFAQ(data) {
            document.getElementById('viewFAQQuestion').textContent = data.question || '';
            document.getElementById('viewFAQAnswer').textContent = data.answer || '';
            openModal('viewFAQModal');
        }

        function openUpdateFAQ(data) {
            document.getElementById('updateFAQId').value = data.id || '';
            document.getElementById('updateFAQQuestion').value = data.question || '';
            document.getElementById('updateFAQAnswer').value = data.answer || '';
            openModal('updateFAQModal');
        }

        function openDeleteFAQ(id) {
            document.getElementById('deleteFAQId').value = id;
            openModal('deleteFAQModal');
        }
    </script>

</body>

</html>