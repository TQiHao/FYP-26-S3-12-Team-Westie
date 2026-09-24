<?php

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: LoginPage.php");
    exit();
}

/*
 * Get the selected faculty ID from the URL.
 * Example:
 * UploadProgrammeListPage.php?facultyId=1
 */
$facultyId = isset($_GET['facultyId']) ? (int) $_GET['facultyId'] : 0;

/*
 * Temporary faculty data.
 * Later, retrieve this from the Faculties table.
 */
$faculties = [
    1 => [
        'name' => 'Faculty of Computing',
        'description' => 'Offers computing and technology-related programmes'
    ],
    2 => [
        'name' => 'Faculty of Business',
        'description' => 'Offers business and management-related programmes'
    ],
    3 => [
        'name' => 'Faculty of Engineering',
        'description' => 'Offers engineering-related programmes'
    ]
];

/*
 * Check whether the selected faculty exists.
 */
if (!isset($faculties[$facultyId])) {
    header("Location: UploadFacultyListPage.php");
    exit();
}

$faculty = $faculties[$facultyId];

/*
 * Later, retrieve the programme list from the database
 * using the selected faculty ID.
 */
$programmes = [
    [
        'id' => 1,
        'name' => 'Bachelor of Computer Science'
    ],
    [
        'id' => 2,
        'name' => 'Bachelor of Information Technology'
    ]
];

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