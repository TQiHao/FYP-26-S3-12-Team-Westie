<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: loginPage.php");
    exit();
}

require_once "../controller/authGuard.php";
requireRole(['university_admin']);

require_once "../controller/manageUniversityInformationController.php";


// Get selected faculty ID from URL
$facultyId = isset($_GET['facultyId']) ? (int) $_GET['facultyId'] : 0;

//Get current university ID from session
$universityId = $_SESSION['university_id'] ?? null;

// Check required information
if ($facultyId <= 0 || $universityId === null) {
    header("Location: uploadFacultyListPage.php");
    exit();
}

// Create controller
$controller = new ManageUniversityInformationController();

// Retrieve selected faculty from database
$faculty = $controller->getFacultyById(
    $facultyId,
    $universityId
);

// Check whether faculty exists
if ($faculty === false) {
    header("Location: uploadFacultyListPage.php");
    exit();
}


// Retrieve programmes belonging to selected faculty
$programmes = $controller->getProgrammesByFaculty($facultyId);
$coordinators = $controller->getCourseCoordinatorList($universityId);

$success = $_SESSION['upload_success'] ?? null;
$error = $_SESSION['upload_error'] ?? null;

unset($_SESSION['upload_success']);
unset($_SESSION['upload_error']);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo htmlspecialchars($faculty['name']); ?> - UniBee</title>

    <link rel="stylesheet" href="../style.css">

    <style>

        /* Programme information - retain the existing page style */
        .programme-info {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 3px;
            min-width: 0;
        }

        .programme-name {
            font-size: 0.78rem;
            font-weight: 700;
            color: #222222;
            line-height: 1.5;
        }

        /* Coordinator appears as secondary information */
        .programme-coordinator {
            font-size: 0.72rem;
            font-weight: 400;
            color: #666666;
            line-height: 1.5;
        }

        .programme-coordinator strong {
            color: #444444;
            font-weight: 600;
            margin-right: 3px;
        }

        /* Keep the original compact row and button style */
        .programme-list-page .programme-row {
            padding: 10px;
            border: 1px solid #bbb;
            border-radius: 10px;
            box-shadow: none;
            gap: 16px;
        }

        .programme-list-page .programme-row:hover {
            border-color: #bbb;
            box-shadow: none;
            transform: none;
        }

        .programme-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        .action-button {
            padding: 8px 16px;
            border: 1px solid #333;
            border-radius: 20px;
            background: white;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
        }

        .view-button {
            color: #333;
        }

        .action-button:hover {
            opacity: 0.8;
        }

        .message-overlay {
            position: fixed;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.25);
            z-index: 99999;
        }

        .message-modal {
            position: relative;
            width: 425px;
            height: 175px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f8fa;
            border: 1px solid #555;
            box-sizing: border-box;
        }

        .message-modal h2 {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 700;
            color: #000;
        }

        .message-close {
            position: absolute;
            top: 10px;
            right: 14px;
            padding: 0;
            border: none;
            background: transparent;
            font-size: 28px;
            font-weight: 700;
            line-height: 1;
            color: #000;
            cursor: pointer;
        }

        /* Programme Popup */
        .programme-modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
            justify-content: center;
            align-items: center;
            z-index: 100000;
        }

        .programme-modal {
            position: relative;
            background: white;
            width: 500px;
            max-width: 90%;
            padding: 30px;
            border-radius: 12px;
            border: 1px solid #ccc;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        }

        .programme-modal h2 {
            margin-top: 0;
            margin-bottom: 25px;
            font-size: 1.2rem;
        }

        .programme-modal-close {
            position: absolute;
            top: 12px;
            right: 18px;
            border: none;
            background: none;
            font-size: 28px;
            cursor: pointer;
        }

        .programme-details p {
            margin: 14px 0;
            font-size: 0.85rem;
            line-height: 1.6;
        }

        .programme-details strong {
            display: inline-block;
            min-width: 145px;
        }

        .programme-modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 25px;
        }

        .programme-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .programme-modal form {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .programme-modal input,
        .programme-modal textarea,
        .programme-modal select {
            width: 100%;
            padding: 9px;
            border: 1px solid #bbb;
            border-radius: 5px;
            box-sizing: border-box;
        }

        .programme-modal textarea {
            min-height: 90px;
            resize: vertical;
        }

        @media (max-width: 768px) {

            .programme-list-page .profile-header .btn-back {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 8px 16px;
                min-width: 85px;
                height: 36px;
                width: auto;
                font-size: 14px;
                line-height: normal;
                white-space: nowrap;
                box-sizing: border-box;
            }
        }
    </style>

</head>

<body>

    <!-- Header -->
    <header class="header">

        <div class="logo-container">

            <img
                src="../images/uniBeeLogo.png"
                alt="UniBee Logo"
            >

        </div>

        <div class="dashboard-header-right">

            <div class="profile-dropdown">

                <div
                    class="profile-container"
                    onclick="toggleDropdown()"
                >

                    <img
                        src="../images/profilePic.png"
                        alt="Profile"
                        class="profile-icon"
                    >

                    <img
                        src="../images/dropdown.png"
                        alt="Menu"
                        class="dropdown-arrow"
                    >

                </div>

                <div id="profileMenu" class="dropdown-menu">
                    <a href="renewLicensePage.php">Renew License</a>
                    <a href="aiChatbotPage.php">AI Chatbot</a>
                    <a href="submitFeedbackPage.php">Submit Feedback</a>
                    <a href="../controller/logOutController.php">Log Out</a>
                </div>

            </div>

        </div>

    </header>

    <!-- Main -->
    <main class="programme-list-page">

        <!-- Back -->
        <div class="profile-header">

            <a
                href="uploadFacultyListPage.php"
                class="btn-back"
            >
                &#8592; Back
            </a>

        </div>


        <!-- Faculty Header -->
        <section class="programme-header">

            <img
                src="../images/faculty.png"
                alt="Faculty"
            >

            <div>

                <h1> Programmes</h1>

                <p>
                    <strong>Faculty: <?php echo htmlspecialchars($faculty['code']); ?> - <?php echo htmlspecialchars($faculty['name']); ?></strong>
                    <br />
                    <strong>Description:</strong><br>
                    <?php echo htmlspecialchars($programme['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                </p>

            </div>

        </section>

        <?php if ($success): ?>

            <div class="message-overlay" id="successPopup">

                <div class="message-modal">

                    <button
                        type="button"
                        class="message-close"
                        onclick="document.getElementById('successPopup').style.display='none';"
                    >
                        &times;
                    </button>

                    <h2>Upload Successfully</h2>

                </div>

            </div>

        <?php endif; ?>

        <?php if ($error): ?>

            <div class="upload-message upload-error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>

        <!-- Programme List -->
        <?php if (empty($programmes)): ?>

            <div class="empty-message">
                No programme has been uploaded yet.
            </div>
        
        <?php else: ?>

            <div class="programme-list">

                <?php foreach ($programmes as $programme): ?>

                    <div class="programme-row">

                        <div class="programme-info">
                            <span class="programme-name">
                                <?php echo htmlspecialchars($programme['code']); ?>
                                -
                                <?php echo htmlspecialchars($programme['name']); ?>
                                (<?php echo htmlspecialchars($programme['durationYears']); ?> Years)
                            </span>

                            <div class="programme-coordinator">
                                <strong>Course Coordinator:</strong>
                                <?php

                                echo htmlspecialchars(
                                    $programme['courseCoordinatorName'] ?? 'Not assigned'
                                );
                                ?>
                            </div>
                        </div>

                        <div class="programme-actions">

                            <!-- View Button -->
                            <button
                                type="button"
                                class="action-button view-button"
                                onclick="openViewProgramme(this)"
                                data-code="<?php echo htmlspecialchars($programme['code'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                data-name="<?php echo htmlspecialchars($programme['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                data-duration="<?php echo htmlspecialchars($programme['durationYears'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                data-description="<?php echo htmlspecialchars($programme['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                data-faculty="<?php echo htmlspecialchars(($faculty['code'] ?? '') . ' - ' . ($faculty['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                data-coordinator="<?php echo htmlspecialchars($programme['courseCoordinatorName'] ?? 'Not assigned', ENT_QUOTES, 'UTF-8'); ?>"
                                data-email="<?php echo htmlspecialchars($programme['courseCoordinatorEmail'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            >
                                View
                            </button>

                            <!-- Edit Button -->
                                <button
                                    type="button"
                                    class="action-button edit-button"
                                    onclick="openEditProgramme(this)"
                                    data-id="<?php echo htmlspecialchars($programme['id']); ?>"
                                    data-name="<?php echo htmlspecialchars($programme['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                    data-code="<?php echo htmlspecialchars($programme['code'], ENT_QUOTES, 'UTF-8'); ?>"
                                    data-duration="<?php echo htmlspecialchars($programme['durationYears']); ?>"
                                    data-description="<?php echo htmlspecialchars($programme['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                    data-coordinator="<?php echo htmlspecialchars($programme['courseCoordinatorId'] ?? ''); ?>"
                                >
                                    Edit
                                </button>

                            <!-- Existing Select Button -->
                            <a href="uploadModuleListPage.php?programmeId=<?php echo urlencode($programme['id']); ?>&facultyId=<?php echo urlencode($facultyId); ?>"
                               class="select-button">
                                Select
                            </a>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

        <!-- Upload Programme -->
        <div class="upload-programme-container">

            <a href="uploadListPage.php?type=programme&facultyId=<?php echo urlencode($facultyId); ?>" class="upload-programme-button">
                Upload Programme
            </a>

        </div>

        <!-- View Programme Popup -->
        <div id="viewProgrammePopup" class="programme-modal-overlay">

            <div class="programme-modal">

                <h2>Programme Details</h2>

                <div class="programme-details">

                    <p>
                        <strong>Programme Code:</strong>
                        <span id="viewProgrammeCode"></span>
                    </p>

                    <p>
                        <strong>Programme Name:</strong>
                        <span id="viewProgrammeName"></span>
                    </p>

                    <p>
                        <strong>Faculty:</strong>
                        <span id="viewProgrammeFaculty"></span>
                    </p>

                    <p>
                        <strong>Duration:</strong>
                        <span id="viewProgrammeDuration"></span> Years
                    </p>

                    <p>
                        <strong>Course Coordinator:</strong>
                        <span id="viewProgrammeCoordinator"></span>
                    </p>

                    <p>
                        <strong>Coordinator Email:</strong>
                        <span id="viewProgrammeEmail"></span>
                    </p>

                    <p>
                        <strong>Description:</strong><br>
                        <span id="viewProgrammeDescription"></span>
                    </p>

                </div>

                <div class="programme-modal-actions">

                    <button
                        type="button"
                        class="select-button"
                        onclick="closeProgrammePopup()"
                    >
                        Close
                    </button>

                </div>

            </div>

        </div>

        <!-- Edit Programme Popup -->
        <div id="editProgrammePopup" class="programme-modal-overlay">

            <div class="programme-modal">

                <h2>Edit Programme</h2>

                <form action="../controller/manageUniversityInformationController.php" method="POST">

                    <input type="hidden" name="action" value="updateProgramme">
                    <input type="hidden" name="programmeId" id="editProgrammeId">
                    <input type="hidden" name="facultyId" value="<?php echo $facultyId; ?>">

                    <label>Programme Name</label>
                    <input type="text" name="name" id="editProgrammeName" required>

                    <label>Programme Code</label>
                    <input type="text" name="code" id="editProgrammeCode" required>

                    <label>Duration (Years)</label>
                    <input type="number" name="durationYears" id="editProgrammeDuration" min="1" required>

                    <label>Description</label>
                    <textarea name="description" id="editProgrammeDescription"></textarea>

                    <label>Course Coordinator</label>
                    <select name="courseCoordinatorId" id="editProgrammeCoordinator" required>

                        <?php foreach ($coordinators as $coordinator): ?>
                            <option value="<?php echo htmlspecialchars($coordinator['id']); ?>">
                                <?php echo htmlspecialchars($coordinator['fullName']); ?>
                            </option>
                        <?php endforeach; ?>

                    </select>

                    <div class="programme-modal-actions">

                        <button type="button" class="action-button" onclick="closeEditProgramme()">
                            Cancel
                        </button>

                        <button type="submit" class="select-button">
                            Save Changes
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </main>


    <!-- Footer -->
    <footer>

        <div class="footer-bottom-bar">
            &copy; 2026 UniBee. All rights reserved.
        </div>

    </footer>


    <script src="../script.js"></script>

    <script>
        function openViewProgramme(button) {

            document.getElementById('viewProgrammeCode').textContent =
                button.dataset.code;

            document.getElementById('viewProgrammeName').textContent =
                button.dataset.name;

            document.getElementById('viewProgrammeFaculty').textContent =
                button.dataset.faculty;

            document.getElementById('viewProgrammeDuration').textContent =
                button.dataset.duration;

            document.getElementById('viewProgrammeCoordinator').textContent =
                button.dataset.coordinator;

            document.getElementById('viewProgrammeEmail').textContent =
                button.dataset.email || 'Not available';

            document.getElementById('viewProgrammeDescription').textContent =
                button.dataset.description || 'No description available';

            document.getElementById('viewProgrammePopup').style.display = 'flex';
        }


        function closeProgrammePopup() {

            document.getElementById('viewProgrammePopup').style.display = 'none';

        }

        function openEditProgramme(button) {

            document.getElementById('editProgrammeId').value =
                button.dataset.id;

            document.getElementById('editProgrammeName').value =
                button.dataset.name;

            document.getElementById('editProgrammeCode').value =
                button.dataset.code;

            document.getElementById('editProgrammeDuration').value =
                button.dataset.duration;

            document.getElementById('editProgrammeDescription').value =
                button.dataset.description;

            document.getElementById('editProgrammeCoordinator').value =
                button.dataset.coordinator;

            document.getElementById('editProgrammePopup').style.display = 'flex';
        }

        function closeEditProgramme() {
            document.getElementById('editProgrammePopup').style.display = 'none';
        }
    </script>

</body>

</html>