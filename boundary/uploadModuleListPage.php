<?php

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: LoginPage.php");
    exit();
}

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
    header("Location: UploadFacultyListPage.php");
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
        "Location: UploadProgrammeListPage.php?facultyId="
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
        "Location: UploadFacultyListPage.php"
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
    <main class="module-list-page">

        <!-- Back -->
        <div class="profile-header">

            <a
                href="UploadProgrammeListPage.php?facultyId=<?php echo urlencode($facultyId); ?>"
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

                <h1>
                    <?php echo htmlspecialchars($programme['name']); ?> (Modules)
                </h1>

                <div class="module-details">

                    <p>
                        <strong>Faculty:</strong>
                        <?php echo htmlspecialchars($faculty['name']); ?>
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

            <div class="upload-message upload-success">
                <?php echo htmlspecialchars($success); ?>
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
                            <?php echo htmlspecialchars($module['name']); ?>
                        </span>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>


        <!-- Upload Module -->
        <div class="upload-module-container">

            <a href="UploadListPage.php?type=module&facultyId=<?php echo urlencode($facultyId); ?>&programmeId=<?php echo urlencode($programmeId); ?>" class="upload-module-button">
                Upload Module
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