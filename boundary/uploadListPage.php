<?php

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: LoginPage.php");
    exit();
}

$type = $_GET['type'] ?? '';

$facultyId = isset($_GET['facultyId']) ? (int) $_GET['facultyId'] : null;
$programmeId = isset($_GET['programmeId']) ? (int) $_GET['programmeId'] : null;

switch ($type) {

    case 'faculty':
        $title = 'Faculty';
        $uploadType = 'faculty';
        $backPage = 'UploadFacultyListPage.php';
        $buttonText = 'Upload Faculty';
        $icon = 'faculty.png';
        break;

    case 'programme':
        $title = 'Programme';
        $uploadType = 'programme';
        $backPage = 'UploadProgrammeListPage.php?facultyId=' . urlencode($facultyId);
        $buttonText = 'Upload Programme';
        $icon = 'faculty.png';
        break;

    case 'module':
        $title = 'Module';
        $uploadType = 'module';
        $backPage = 'UploadModuleListPage.php?facultyId='
            . urlencode($facultyId)
            . '&programmeId='
            . urlencode($programmeId);
        $buttonText = 'Upload Module';
        $icon = 'faculty.png';
        break;

    case 'facility':
        $title = 'Facility';
        $uploadType = 'facility';
        $backPage = 'UploadFacilityListPage.php';
        $buttonText = 'Upload Facility';
        $icon = 'facilityBooking.png';
        break;

    case 'courseCoordinator':
        $title = 'Course Coordinator';
        $uploadType = 'courseCoordinator';
        $backPage = 'uploadCourseCoordinatorPage.php';
        $buttonText = 'Upload Course Coordinator';
        $icon = 'courseCoordinator.png';
        break;

    case 'lecturer':
        $title = 'Lecturer';
        $uploadType = 'lecturer';
        $backPage = 'uploadLecturerPage.php';
        $buttonText = 'Upload Lecturer';
        $icon = 'faculty.png';
        break;

    case 'floorPlan':
        $title = 'Campus Floor Plan';
        $uploadType = 'floorPlan';
        $backPage = 'uploadFloorPlanPage.php';
        $buttonText = 'Upload Floor Plan';
        $icon = 'floorPlan.png';
        break;

    default:
        header("Location: ManageUniversityInformationPage.php");
        exit();
}

$acceptTypes = '.csv';
$formatText = 'CSV file only';

if ($type === 'floorPlan') {
    $acceptTypes = '.png,.jpg,.jpeg';
    $formatText = 'PNG, JPG, or JPEG image';
}

$error = $_SESSION['upload_error'] ?? null;

unset($_SESSION['upload_error']);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Upload - UniBee</title>

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


    <!-- Main Content -->
    <main class="upload-page">

        <div class="profile-header">

            <a href="<?php echo htmlspecialchars($backPage); ?>" class="btn-back">
                &#8592; Back
            </a>

        </div>


        <section class="upload-page-header">

            <img
                src="../images/<?php echo htmlspecialchars($icon); ?>"
                alt="<?php echo htmlspecialchars($title); ?>"
            >

            <h1>
                <?php echo htmlspecialchars($title); ?>
            </h1>

        </section>

        <?php if ($error): ?>

            <div class="upload-message upload-error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>

        <form
            action="../controller/manageUniversityInformationController.php"
            method="POST"
            enctype="multipart/form-data"
            class="upload-form"
        >

            <label for="uploadFile">
                <?php
                    echo $type === 'floorPlan'
                        ? 'Attach Floor Plan to Upload'
                        : 'Attach CSV File to Upload';
                ?>
            </label>

            <div class="upload-format">
                Supported Format: 
                <?php echo htmlspecialchars($formatText); ?>
            </div>

            <div class="upload-file-row">

               <input
                    type="file"
                    name="uploadFile"
                    class="upload-file-input"
                    accept="<?php echo htmlspecialchars($acceptTypes); ?>"
                    required
                >

            </div>

            <input
                type="hidden"
                name="uploadType"
                value="<?php echo htmlspecialchars($uploadType); ?>"
            >

            <!-- Keep the selected IDs -->
            <?php if ($facultyId !== null): ?>

                <input
                    type="hidden"
                    name="facultyId"
                    value="<?php echo htmlspecialchars($facultyId); ?>"
                >

            <?php endif; ?>

            <?php if ($programmeId !== null): ?>

                <input
                    type="hidden"
                    name="programmeId"
                    value="<?php echo htmlspecialchars($programmeId); ?>"
                >

            <?php endif; ?>

            <button
                type="submit"
                name="upload"
                class="upload-button"
            >
                Upload
            </button>

        </form>

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