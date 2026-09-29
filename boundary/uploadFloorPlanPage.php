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

$floorPlan = null;

if ($universityId !== null) {

    $controller = new ManageUniversityInformationController();

    $floorPlan = $controller->getLatestFloorPlan(
        $universityId
    );
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Campus Floor Plan - UniBee</title>

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
    <main class="floor-plan-page">

        <!-- Back -->
        <div class="profile-header">

            <a
                href="ManageUniversityInformationPage.php"
                class="btn-back"
            >
                &#8592; Back
            </a>

        </div>


        <!-- Floor Plan Header -->
        <section class="floor-plan-header">

            <img
                src="../images/floorPlan.png"
                alt="Campus Floor Plan"
            >

            <h1>
                Campus Floor Plan
            </h1>

        </section>


        <!-- Upload Message -->
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


        <!-- Floor Plan Image -->
        <?php if ($floorPlan !== null): ?>

            <div class="floor-plan-image-container">

                <img
                    src="../<?php echo htmlspecialchars($floorPlan['filePath']); ?>"
                    alt="Campus Floor Plan"
                    class="floor-plan-image"
                >

            </div>

        <?php else: ?>

            <div class="empty-floor-plan-message">

                No campus floor plan has been uploaded yet.

            </div>

        <?php endif; ?>


        <!-- Upload Button -->
        <div class="upload-floor-plan-container">

            <a
                href="UploadListPage.php?type=floorPlan"
                class="upload-floor-plan-button"
            >
                Upload Floor Plan
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