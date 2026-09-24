<?php

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: LoginPage.php");
    exit();
}

/*
 * Later, retrieve the faculty list from the database.
 */
$faculties = [
    [
        'id' => 1,
        'name' => 'Faculty of Computing'
    ],
    [
        'id' => 2,
        'name' => 'Faculty of Business'
    ],
    [
        'id' => 3,
        'name' => 'Faculty of Engineering'
    ]
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Upload Faculty List - UniBee</title>

    <link rel="stylesheet" href="../style.css">

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