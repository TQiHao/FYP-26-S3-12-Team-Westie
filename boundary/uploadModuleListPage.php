<?php

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: LoginPage.php");
    exit();
}


/*
 * Get selected programme ID and faculty ID from URL.
 */
$programmeId = isset($_GET['programmeId']) ? (int) $_GET['programmeId'] : 0;
$facultyId = isset($_GET['facultyId']) ? (int) $_GET['facultyId'] : 0;


/*
 * Temporary programme data.
 * Later, retrieve this from the database.
 */
$programmes = [
    1 => [
        'name' => 'Bachelor of Computer Science',
        'faculty' => 'Faculty of Computing',
        'duration' => '3 Years',
        'description' => 'A programme focusing on computer science and software development'
    ],
    2 => [
        'name' => 'Bachelor of Information Technology',
        'faculty' => 'Faculty of Computing',
        'duration' => '3 Years',
        'description' => 'A programme focusing on information technology and systems'
    ]
];


/*
 * Check whether the selected programme exists.
 */
if (!isset($programmes[$programmeId])) {
    header("Location: UploadProgrammeListPage.php?facultyId=" . urlencode($facultyId));
    exit();
}

$programme = $programmes[$programmeId];


/*
 * Temporary module data.
 * Later, retrieve this from the database.
 */
$modules = [
    [
        'id' => 1,
        'name' => 'Introduction to Programming'
    ],
    [
        'id' => 2,
        'name' => 'Database Systems'
    ]
];

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
                        <?php echo htmlspecialchars($programme['faculty']); ?>
                    </p>

                    <p>
                        <strong>Duration:</strong>
                        <?php echo htmlspecialchars($programme['duration']); ?>
                    </p>

                    <p>
                        <strong>Description:</strong>
                        <?php echo htmlspecialchars($programme['description']); ?>
                    </p>

                </div>

            </div>

        </section>


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