<?php

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: LoginPage.php");
    exit();
}

require_once "../controller/manageUniversityInformationController.php";


// Get selected faculty ID from URL
$facultyId = isset($_GET['facultyId']) ? (int) $_GET['facultyId'] : 0;


//Get current university ID from session
$universityId = $_SESSION['university_id'] ?? null;


// Check required information
if ($facultyId <= 0 || $universityId === null) {
    header("Location: UploadFacultyListPage.php");
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
    header("Location: UploadFacultyListPage.php");
    exit();
}


// Retrieve programmes belonging to selected faculty
$programmes = $controller->getProgrammesByFaculty(
    $facultyId
);

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

                    <a href="UniversityAdminDashboardPage.php">
                        Dashboard
                    </a>

                    <a href="ManageUniversityInformationPage.php">
                        Manage University Information
                    </a>

                    <a href="../controller/logoutController.php">
                        Log Out
                    </a>

                </div>

            </div>

        </div>

    </header>

    <!-- Main -->
    <main class="programme-list-page">

        <!-- Back -->
        <div class="profile-header">

            <a
                href="UploadFacultyListPage.php"
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

                <h1>
                    <?php echo htmlspecialchars($faculty['name']); ?> (Programmes)
                </h1>

                <p>
                    <strong>Description:</strong><br>
                    <?php echo htmlspecialchars($faculty['description']); ?>
                </p>

            </div>

        </section>

         <?php if ($success): ?>

            <div class="upload-message upload-success">
                <?php echo htmlspecialchars($success); ?>
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

                        <span>
                            <?php echo htmlspecialchars($programme['name']); ?>
                            (<?php echo htmlspecialchars($programme['durationYears']); ?> Years)
                        </span>

                        <a href="UploadModuleListPage.php?programmeId=<?php echo urlencode($programme['id']); ?>&facultyId=<?php echo urlencode($facultyId); ?>" class="select-button">
                            Select
                        </a>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>


        <!-- Upload Programme -->
        <div class="upload-programme-container">

            <a href="UploadListPage.php?type=programme&facultyId=<?php echo urlencode($facultyId); ?>" class="upload-programme-button">
                Upload Programme
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