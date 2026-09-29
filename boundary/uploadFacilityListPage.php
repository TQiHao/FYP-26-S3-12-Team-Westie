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

$facilities = [];

if ($universityId !== null) {

    $controller = new ManageUniversityInformationController();

    $facilities = $controller->getFacilityList(
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

    <title>Upload Facility List - UniBee</title>

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
    <main class="facility-list-page">

        <!-- Back -->
        <div class="profile-header">

            <a
                href="ManageUniversityInformationPage.php"
                class="btn-back"
            >
                &#8592; Back
            </a>

        </div>


        <!-- Facility Header -->
        <section class="facility-header">

            <img
                src="../images/facilityBooking.png"
                alt="Facility"
            >

            <h1>
                Facility
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


        <!-- Facility List -->
        <?php if (empty($facilities)): ?>

            <div class="empty-message">
                No facility has been uploaded yet.
            </div>

        <?php else: ?>

            <div class="facility-list">

                <?php foreach ($facilities as $facility): ?>

                    <div class="facility-row">

                        <div class="facility-main">

                            <span class="facility-name">
                                <?php echo htmlspecialchars($facility['name']); ?>
                            </span>

                            <span class="facility-type">
                                <?php echo htmlspecialchars($facility['type']); ?>
                            </span>

                        </div>


                        <div class="facility-details">

                            <?php if (!empty($facility['location'])): ?>

                                <span>
                                    Location:
                                    <?php echo htmlspecialchars($facility['location']); ?>
                                </span>

                            <?php endif; ?>


                            <?php if (!empty($facility['blockFloor'])): ?>

                                <span>
                                    <?php echo htmlspecialchars($facility['blockFloor']); ?>
                                </span>

                            <?php endif; ?>


                            <?php

                            if (
                                isset($facility['capacity']) &&
                                $facility['capacity'] !== ''
                            ): ?>

                                <span>
                                    Capacity:
                                    <?php echo htmlspecialchars($facility['capacity']); ?>
                                </span>

                            <?php endif; ?>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>


        <!-- Upload Facility -->
        <div class="upload-facility-container">

            <a
                href="UploadListPage.php?type=facility"
                class="upload-facility-button"
            >
                Upload Facility
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