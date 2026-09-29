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

$lecturers = [];

if ($universityId !== null) {

    $controller = new ManageUniversityInformationController();

    $lecturers = $controller->getLecturerList(
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

    <title>Upload Lecturer List - UniBee</title>

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
    <main class="lecturer-list-page">

        <!-- Back -->
        <div class="profile-header">

            <a
                href="ManageUniversityInformationPage.php"
                class="btn-back"
            >
                &#8592; Back
            </a>

        </div>


        <!-- Lecturer Header -->
        <section class="lecturer-header">

            <img
                src="../images/faculty.png"
                alt="Lecturer"
            >

            <h1>
                Lecturer
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


        <!-- Lecturer List -->
        <?php if (empty($lecturers)): ?>

            <div class="empty-message">
                No lecturer has been uploaded yet.
            </div>

        <?php else: ?>

            <div class="lecturer-list">

                <?php foreach ($lecturers as $lecturer): ?>

                    <div class="lecturer-row">

                        <div class="lecturer-main">

                            <span class="lecturer-name">
                                <?php

                                echo htmlspecialchars(
                                    $lecturer['fullName']
                                );
                                ?>
                            </span>

                            <div class="lecturer-info">

                                <span class="lecturer-id">
                                    ID:
                                    <?php

                                    echo htmlspecialchars(
                                        $lecturer['id']
                                    );
                                    ?>
                                </span>

                                <span class="lecturer-email">
                                    <?php

                                    echo htmlspecialchars(
                                        $lecturer['email']
                                    );
                                    ?>
                                </span>

                            </div>

                        </div>


                        <div class="lecturer-status
                            <?php

                            echo strtolower($lecturer['status']) === 'active'
                                ? 'status-active'
                                : 'status-inactive';
                            ?>">

                            <?php

                            echo htmlspecialchars(
                                $lecturer['status']
                            );
                            ?>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>


        <!-- Upload Lecturer -->
        <div class="upload-lecturer-container">

            <a
                href="UploadListPage.php?type=lecturer"
                class="upload-lecturer-button"
            >
                Upload Lecturer
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