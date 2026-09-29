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

$courseCoordinators = [];

if ($universityId !== null) {

    $controller = new ManageUniversityInformationController();

    $courseCoordinators = $controller->getCourseCoordinatorList(
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

    <title>Upload Course Coordinator List - UniBee</title>

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
    <main class="course-coordinator-list-page">

        <!-- Back -->
        <div class="profile-header">

            <a
                href="ManageUniversityInformationPage.php"
                class="btn-back"
            >
                &#8592; Back
            </a>

        </div>


        <!-- Header -->
        <section class="course-coordinator-header">

            <img
                src="../images/faculty.png"
                alt="Course Coordinator"
            >

            <h1>
                Course Coordinator
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


        <!-- Course Coordinator List -->
        <?php if (empty($courseCoordinators)): ?>

            <div class="empty-message">
                No course coordinator has been uploaded yet.
            </div>

        <?php else: ?>

            <div class="course-coordinator-list">

                <?php foreach ($courseCoordinators as $coordinator): ?>

                    <div class="course-coordinator-row">

                        <div class="course-coordinator-main">

                            <span class="course-coordinator-name">
                                <?php

                                echo htmlspecialchars(
                                    $coordinator['fullName']
                                );
                                ?>
                            </span>

                            <div class="course-coordinator-info">

                                <span class="course-coordinator-id">
                                    ID: <?php echo htmlspecialchars($coordinator['id']); ?>
                                </span>

                                <span class="course-coordinator-email">
                                    Email: <?php echo htmlspecialchars($coordinator['email']); ?>
                                </span>

                            </div>

                        </div>


                        <div class="course-coordinator-status 
                            <?php

                                    echo strtolower($coordinator['status']) === 'active'
                                        ? 'status-active'
                                        : 'status-inactive'; ?>">

                            <?php echo htmlspecialchars($coordinator['status']); ?>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>


        <!-- Upload Button -->
        <div class="upload-course-coordinator-container">

            <a
                href="UploadListPage.php?type=courseCoordinator"
                class="upload-course-coordinator-button"
            >
                Upload Course Coordinator
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