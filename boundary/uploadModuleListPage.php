<?php

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: loginPage.php");
    exit();
}

require_once "../controller/authGuard.php";
requireRole(['university_admin']);

require_once "../controller/manageUniversityInformationController.php";


// Get the programme id and faculty id
$programmeId = isset($_GET['programmeId'])
    ? (int) $_GET['programmeId']
    : 0;

$facultyId = isset($_GET['facultyId'])
    ? (int) $_GET['facultyId']
    : 0;

// Get current university ID
$universityId = $_SESSION['university_id'] ?? null;

if (
    $programmeId <= 0 ||
    $facultyId <= 0 ||
    $universityId === null
) {
    header("Location: uploadFacultyListPage.php");
    exit();
}

$controller = new ManageUniversityInformationController();

// Get selected programme from database
$programme = $controller->getProgrammeById(
    $programmeId,
    $facultyId,
    $universityId
);

// Check whether programme exists
if ($programme === false) {
    header(
        "Location: uploadProgrammeListPage.php?facultyId="
        . urlencode($facultyId)
    );
    exit();
}

// Get selected faculty from database
$faculty = $controller->getFacultyById(
    $facultyId,
    $universityId
);

// Check whether faculty exists
if ($faculty === false) {
    header(
        "Location: uploadFacultyListPage.php"
    );
    exit();
}

// Get modules under selected programme
$modules = $controller->getModulesByProgramme(
    $programmeId
);

// Get upload messages
$success = $_SESSION['upload_success'] ?? null;
$error = $_SESSION['upload_error'] ?? null;

// Clear messages after reading
unset($_SESSION['upload_success']);
unset($_SESSION['upload_error']);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?php echo htmlspecialchars($programme['name']); ?> - Modules - UniBee
    </title>

    <link rel="stylesheet" href="../style.css">

    <style>
        .module-row {
            padding: 16px 12px;
            border: 1px solid #bbb;
            border-radius: 10px;
            box-shadow: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
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

        /* Module View Popup - same style as Programme popup */
        .module-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
            justify-content: center;
            align-items: center;
            z-index: 100000;
        }

        .module-modal-content {
            position: relative;
            background: white;
            width: 500px;
            max-width: 90%;
            padding: 30px;
            border-radius: 12px;
            border: 1px solid #ccc;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
            box-sizing: border-box;
        }

        .module-modal-content h2 {
            margin-top: 0;
            margin-bottom: 25px;
            font-size: 1.2rem;
        }

        .module-popup-details p {
            margin: 14px 0;
            font-size: 0.85rem;
            line-height: 1.6;
        }

        .module-popup-details strong {
            display: inline-block;
            min-width: 145px;
        }

        .module-modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 25px;
        }

        /* Module action buttons - match Programme List */
        .module-list-page .module-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        .module-list-page .module-actions .action-button {
            padding: 8px 16px;
            border: 1px solid #333;
            border-radius: 20px;
            background: white;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            color: #333;
        }

        .module-list-page .module-actions .action-button:hover {
            opacity: 0.8;
        }

        /* Edit Module Form - match Edit Programme */
        .module-modal-content form {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .module-modal-content label {
            font-size: 0.85rem;
            font-weight: 600;
        }

        .module-modal-content input,
        .module-modal-content textarea {
            width: 100%;
            padding: 9px;
            border: 1px solid #bbb;
            border-radius: 5px;
            box-sizing: border-box;
        }

        .module-modal-content textarea {
            min-height: 90px;
            resize: vertical;
        }

        .module-modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 25px;
        }

        .module-modal-actions .action-button {
            padding: 8px 16px;
            border: 1px solid #333;
            border-radius: 20px;
            background: white;
            color: #333;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
        }

        .module-modal-actions .action-button:hover {
            opacity: 0.8;
        }

        @media (max-width: 768px) {

            .module-list-page .profile-header .btn-back {
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
    <main class="module-list-page">

        <!-- Back -->
        <div class="profile-header">

            <a
                href="uploadProgrammeListPage.php?facultyId=<?php echo urlencode($facultyId); ?>"
                class="btn-back"
            >
                &#8592; Back
            </a>

        </div>


        <!-- Programme Header -->
        <section class="module-header">

            <img
                src="../images/faculty.png"
                alt="Faculty"
            >

            <div>

                <h1>Modules</h1>

                <div class="module-details">

                    <p>
                        <strong>Faculty:
                            <?php echo htmlspecialchars($faculty['code']); ?> - 
                            <?php echo htmlspecialchars($faculty['name']); ?>
                        </strong>
                    </p>

                    <p>
                        <strong>Programme:
                            <?php echo htmlspecialchars($programme['code']); ?> - 
                            <?php echo htmlspecialchars($programme['name']); ?>
                        </strong>
                    </p>


                    <p>
                        <strong>Duration:</strong>
                        <?php echo htmlspecialchars($programme['durationYears']); ?> Years
                    </p>

                    <p>
                        <strong>Description:</strong>
                        <?php echo htmlspecialchars($programme['description']); ?>
                    </p>

                </div>

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

        <!-- Module List -->
        <?php if (empty($modules)): ?>

            <div class="empty-module-message">
                No module has been uploaded yet.
            </div>

        <?php else: ?>

            <div class="module-list">

                <?php foreach ($modules as $module): ?>

                    <div class="module-row">

                        <span>
                            <?php echo htmlspecialchars($module['code']); ?>
                            -
                            <?php echo htmlspecialchars($module['name']); ?>
                        </span>

                        <div class="module-actions">

                            <button
                                type="button"
                                class="action-button view-button"
                                onclick='viewModule(<?php echo json_encode($module); ?>)'
                            >
                                View
                            </button>

                            <button
                                type="button"
                                class="action-button edit-button"
                                onclick="openEditModule(this)"
                                data-id="<?php echo htmlspecialchars($module['id']); ?>"
                                data-name="<?php echo htmlspecialchars($module['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                data-code="<?php echo htmlspecialchars($module['code'], ENT_QUOTES, 'UTF-8'); ?>"
                                data-credits="<?php echo htmlspecialchars($module['credits']); ?>"
                                data-semester="<?php echo htmlspecialchars($module['semester']); ?>"
                                data-description="<?php echo htmlspecialchars($module['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            >
                                Edit
                            </button>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>


        <!-- Upload Module -->
        <div class="upload-module-container">

            <a href="uploadListPage.php?type=module&facultyId=<?php echo urlencode($facultyId); ?>&programmeId=<?php echo urlencode($programmeId); ?>" class="upload-module-button">
                Upload Module
            </a>

        </div>

        <!-- View Module Popup -->
        <div id="viewModuleModal" class="module-modal">

            <div class="module-modal-content">

                <h2>Module Details</h2>

                <div class="module-popup-details">

                    <p>
                        <strong>Module Code:</strong>
                        <span id="viewModuleCode"></span>
                    </p>

                    <p>
                        <strong>Module Name:</strong>
                        <span id="viewModuleName"></span>
                    </p>

                    <p>
                        <strong>Credits:</strong>
                        <span id="viewModuleCredits"></span>
                    </p>

                    <p>
                        <strong>Semester:</strong>
                        <span id="viewModuleSemester"></span>
                    </p>

                    <p>
                        <strong>Description:</strong>
                        <span id="viewModuleDescription"></span>
                    </p>

                </div>

                <div class="module-modal-actions">

                    <button
                        type="button"
                        class="select-button"
                        onclick="closeViewModule()"
                    >
                        Close
                    </button>

                </div>

            </div>

        </div>

        <!-- Edit Module Popup -->
        <div id="editModuleModal" class="module-modal">

            <div class="module-modal-content">

                <h2>Edit Module</h2>

                <form
                    action="../controller/manageUniversityInformationController.php"
                    method="POST"
                >

                    <input type="hidden" name="action" value="updateModule">

                    <input type="hidden" name="moduleId" id="editModuleId">

                    <input type="hidden" name="programmeId"
                        value="<?php echo htmlspecialchars($programmeId); ?>">

                    <input type="hidden" name="facultyId"
                        value="<?php echo htmlspecialchars($facultyId); ?>">

                    <label>Module Name</label>
                    <input
                        type="text"
                        name="name"
                        id="editModuleName"
                        required
                    >

                    <label>Module Code</label>
                    <input
                        type="text"
                        name="code"
                        id="editModuleCode"
                        required
                    >

                    <label>Credits</label>
                    <input
                        type="number"
                        name="credits"
                        id="editModuleCredits"
                        min="1"
                        required
                    >

                    <label>Semester</label>
                    <input
                        type="number"
                        name="semester"
                        id="editModuleSemester"
                        min="1"
                        required
                    >

                    <label>Description</label>
                    <textarea
                        name="description"
                        id="editModuleDescription"
                    ></textarea>

                    <div class="module-modal-actions">

                        <button
                            type="button"
                            class="action-button"
                            onclick="closeEditModule()"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            class="select-button"
                        >
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
        let selectedModule = null;

        function viewModule(module) {
            selectedModule = module;

            document.getElementById('viewModuleCode').textContent = module.code || '';
            document.getElementById('viewModuleName').textContent = module.name || '';
            document.getElementById('viewModuleCredits').textContent = module.credits || '';
            document.getElementById('viewModuleSemester').textContent = module.semester || '';
            document.getElementById('viewModuleDescription').textContent = module.description || 'No description';

            document.getElementById('viewModuleModal').style.display = 'flex';
        }

        function closeViewModule() {
            document.getElementById('viewModuleModal').style.display = 'none';
        }

        // Open Edit Module Popup
        function openEditModule(button) {

            document.getElementById('editModuleId').value =
                button.dataset.id;

            document.getElementById('editModuleName').value =
                button.dataset.name;

            document.getElementById('editModuleCode').value =
                button.dataset.code;

            document.getElementById('editModuleCredits').value =
                button.dataset.credits;

            document.getElementById('editModuleSemester').value =
                button.dataset.semester;

            document.getElementById('editModuleDescription').value =
                button.dataset.description;

            document.getElementById('editModuleModal').style.display = 'flex';
        }

        // Close Edit Module Popup
        function closeEditModule() {
            document.getElementById('editModuleModal').style.display = 'none';
        }
    </script>

</body>

</html>