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

    case 'student':
        $title = 'Student';
        $uploadType = 'student';
        $backPage = 'UploadStudentListPage.php';
        $buttonText = 'Upload Student';
        $icon = 'student.png';
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

            <?php if ($type === 'faculty'): ?>

                <div class="csv-guide">

                    <h3>Faculty CSV Upload Guide</h3>

                    <p>
                        <strong>File Name:</strong>
                    </p>

                    <pre>U01_FacultyList.csv</pre>

                    <p>
                        <strong>CSV Format:</strong>
                    </p>

                    <pre>name,code,description</pre>

                    <p>
                        <strong>Example:</strong>
                    </p>

                    <pre>Faculty of Computing,FC,Computing and IT programmes</pre>

                    <p>
                        <strong>Requirements:</strong>
                    </p>

                    <ul>
                        <li>File name must follow the required naming convention.</li>
                        <li>Faculty name and code are required.</li>
                        <li>Only .csv files will be accepted.</li>
                    </ul>

                </div>


            <?php elseif ($type === 'programme'): ?>

                <div class="csv-guide">

                    <h3>Programme CSV Upload Guide</h3>

                    <p>
                        <strong>File Name:</strong>
                    </p>

                    <pre>U01_ProgrammeList_FC.csv</pre>

                    <p>
                        <strong>CSV Format:</strong>
                    </p>

                    <pre>name,code,durationYears,description</pre>

                    <p>
                        <strong>Example:</strong>
                    </p>

                    <pre>Bachelor of Computer Science,BSCS,4,Computing programme</pre>

                    <p>
                        <strong>Requirements:</strong>
                    </p>

                    <ul>
                        <li>File name must include the university code and faculty code.</li>
                        <li>Programme name, code and duration are required.</li>
                        <li>Duration must be a positive number.</li>
                        <li>Only .csv files will be accepted.</li>
                    </ul>

                </div>


            <?php elseif ($type === 'module'): ?>

                <div class="csv-guide">

                    <h3>Module CSV Upload Guide</h3>

                    <p>
                        <strong>File Name:</strong>
                    </p>

                    <pre>U01_ModuleList_BSCS.csv</pre>

                    <p>
                        <strong>CSV Format:</strong>
                    </p>

                    <pre>name,code,credits,semester,description</pre>

                    <p>
                        <strong>Example:</strong>
                    </p>

                    <pre>Programming Fundamentals,CS101,4,1,Introduction to programming</pre>

                    <p>
                        <strong>Requirements:</strong>
                    </p>

                    <ul>
                        <li>File name must include the university code and programme code.</li>
                        <li>Module name, code, credits and semester are required.</li>
                        <li>Only .csv files will be accepted.</li>
                    </ul>

                </div>

            <?php elseif ($type === 'facility'): ?>

            <div class="csv-guide">

                <h3>Facility CSV Upload Guide</h3>

                <p>
                    <strong>File Name:</strong>
                </p>

                <pre>U01_FacilityList.csv</pre>

                <p>
                    <strong>CSV Format:</strong>
                </p>

                <pre>name,type,description,location,blockFloor,capacity</pre>

                <p>
                    <strong>Example:</strong>
                </p>

                <pre>Central Study Room,study room,Quiet study area,Block A,Level 2,30</pre>

                <p>
                    <strong>Requirements:</strong>
                </p>

                <ul>
                    <li>File name must follow the required naming convention.</li>
                    <li>Facility name and type are required.</li>
                    <li>Type must be study room, gym, lecture hall, or lab.</li>
                    <li>Capacity must be a positive number.</li>
                    <li>Only .csv files will be accepted.</li>
                </ul>

            </div>

            <?php elseif ($type === 'courseCoordinator'): ?>

                <div class="csv-guide">

                    <h3>Course Coordinator CSV Upload Guide</h3>

                    <p>
                        <strong>File Name:</strong>
                    </p>

                    <pre>U01_CourseCoordinatorList.csv</pre>

                    <p>
                        <strong>CSV Format:</strong>
                    </p>

                    <pre>fullName,email,password</pre>

                    <p>
                        <strong>Example:</strong>
                    </p>

                    <pre>Alice Tan,alice.tan@unibee.edu.sg,Coord@123</pre>

                    <p>
                        <strong>Requirements:</strong>
                    </p>

                    <ul>
                        <li>Full name, email and password are required.</li>
                        <li>Email must be a valid email address.</li>
                        <li>Only .csv files will be accepted.</li>
                    </ul>

                </div>


            <?php elseif ($type === 'lecturer'): ?>

                <div class="csv-guide">

                    <h3>Lecturer CSV Upload Guide</h3>

                    <p>
                        <strong>File Name:</strong>
                    </p>

                    <pre>U01_LecturerList.csv</pre>

                    <p>
                        <strong>CSV Format:</strong>
                    </p>

                    <pre>fullName,email,password</pre>

                    <p>
                        <strong>Example:</strong>
                    </p>

                    <pre>John Lim,john.lim@unibee.com,Lecturer@123</pre>

                    <p>
                        <strong>Requirements:</strong>
                    </p>

                    <ul>
                        <li>Full name, email and password are required.</li>
                        <li>Email must be a valid email address.</li>
                        <li>Only .csv files will be accepted.</li>
                    </ul>

                </div>


            <?php elseif ($type === 'student'): ?>

                <div class="csv-guide">

                    <h3>Student List CSV Upload Guide</h3>

                    <p>
                        <strong>File Name Format:</strong>
                    </p>

                    <pre>{universityCode}_{programmeCode}_{academicYear}_{semester}_StudentList.csv</pre>

                    <p>
                        <strong>Example:</strong>
                    </p>

                    <pre>U01_BSCS_2026-2027_S1_StudentList.csv</pre>

                    <p>
                        <strong>The system will parse the file name to determine:</strong>
                    </p>

                    <ul>
                        <li>University Code: U01</li>
                        <li>Programme: BSCS</li>
                        <li>Academic Year: 2026-2027</li>
                        <li>Semester: S1</li>
                    </ul>

                    <p>
                        <strong>CSV Format:</strong>
                    </p>

                    <pre>email,fullName,password,semesterStart,semesterEnd</pre>

                    <p>
                        <strong>Or:</strong>
                    </p>

                   <pre>student_id,fullName,email,password,semesterStart,semesterEnd</pre>

                    <p>
                        <strong>Requirements:</strong>
                    </p>

                    <ul>
                        <li>The first column must be <strong>email</strong> or <strong>student_id</strong>.</li>
                        <li>Student name and password must be included.</li>
                        <li>File name must follow the required naming convention.</li>
                        <li>Only .csv files will be accepted.</li>
                    </ul>

                </div>


            <?php elseif ($type === 'floorPlan'): ?>

                <div class="csv-guide">

                    <h3>Campus Floor Plan Upload Guide</h3>

                    <p>
                        <strong>Accepted File Formats:</strong>
                    </p>

                    <pre>PNG, JPG, JPEG</pre>

                    <p>
                        <strong>Requirements:</strong>
                    </p>

                    <ul>
                        <li>Only PNG, JPG or JPEG files will be accepted.</li>
                        <li>Upload a clear and readable campus floor plan.</li>
                    </ul>

                </div>

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