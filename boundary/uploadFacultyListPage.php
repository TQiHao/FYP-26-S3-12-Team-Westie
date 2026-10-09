<?php

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: LoginPage.php");
    exit();
}

require_once "../controller/manageUniversityInformationController.php";

$success = $_SESSION['upload_success'] ?? null;
$error = $_SESSION['upload_error'] ?? null;

unset($_SESSION['upload_success']);
unset($_SESSION['upload_error']);

$universityId = $_SESSION['university_id'] ?? null;

$faculties = [];

if ($universityId !== null) {
    $controller = new ManageUniversityInformationController();
    $faculties = $controller->getFacultyList($universityId);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Upload Faculty List - UniBee</title>

    <link rel="stylesheet" href="../style.css">

    <style>
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
    </style>

</head>

<body>

    <!-- Header -->
    <header class="header">

        <div class="logo-container">
            <img src="../images/uniBeeLogo.png" alt="UniBee Logo">
        </div>

        <div class="dashboard-header-right">

            <div class="profile-dropdown">

                <div class="profile-container" onclick="toggleDropdown()">

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
                    <a href="../controller/logoutController.php">Log Out</a>
                </div>

            </div>

        </div>

    </header>


    <!-- Main -->
    <main class="faculty-list-page">

       <div class="profile-header">
            <a href="manageUniversityInformationPage.php" class="btn-back">
                &#8592; Back
            </a>
        </div>

        <!-- Faculty Header -->
        <section class="faculty-header">

            <img
                src="../images/faculty.png"
                alt="Faculty"
            >

            <h1>Faculty</h1>

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


        <?php if (empty($faculties)): ?>

            <div class="empty-message">
                No faculty has been uploaded yet.
            </div>

        <?php else: ?>

            <div class="faculty-list">

                <?php foreach ($faculties as $faculty): ?>

                    <div class="faculty-row">

                        <span>
                            <?php echo htmlspecialchars($faculty['name']); ?>
                        </span>

                        <a
                            href="UploadProgrammeListPage.php?facultyId=<?php echo urlencode($faculty['id']); ?>"
                            class="select-button"
                        >
                            Select
                        </a>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>


        <!-- Upload Faculty -->
        <div class="upload-faculty-container">

            <a href="uploadListPage.php?type=faculty" class="upload-faculty-button">
                Upload Faculty
            </a>

        </div>

    </main>

    <!-- Footer -->
    <footer>

        <div class="footer-bottom-bar">
            &copy; 2026 UniBee. All rights reserved.
        </div>

    </footer>


    <script src="../script.js"></script>

</body>
</html>