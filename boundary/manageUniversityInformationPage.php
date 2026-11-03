<?php

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: loginPage.php");
    exit();
}

require_once "../controller/authGuard.php";
requireRole(['university_admin']);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage University Information - UniBee</title>

    <link rel="stylesheet" href="../style.css">
    <style>
        .university-management-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            column-gap: 35px;
            row-gap: 18px;
            width: 100%;
        }

        /* University Management - Mobile Only */
        @media (max-width: 768px) {
            main.manage-university-information
            .university-management-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                column-gap: 18px;
                row-gap: 20px;
            }

            .university-management-card {
                min-width: 0;
                min-height: 150px;
                padding: 16px;
            }

            .university-management-card h2 {
                font-size: 1rem;
                line-height: 1.25;
                overflow-wrap: normal;
                word-break: normal;
            }

            .university-management-card p {
                font-size: 0.8rem;
                line-height: 1.5;
            }

            .manage-university-information .profile-header .btn-back {
                display: inline-flex;
                grid-column: 1;
                justify-self: start;
                align-items: center;
                justify-content: center;
                padding: 8px 16px;
                min-width: 85px;
                height: 36px;
                font-size: 14px;
                white-space: nowrap;
                box-sizing: border-box;
            }
        }
    </style>
</head>

<body>

    <!-- Header -->
    <header class="header">

        <div class="logo-container">
            <img src="../images/uniBeeLogo.png" alt="UniBee Logo">
        </div>

        <div class="dashboard-header-right">

            <!-- Profile + Dropdown -->
            <div class="profile-dropdown">

                <div class="profile-container" onclick="toggleDropdown()">

                    <img src="../images/profilePic.png" alt="Profile" class="profile-icon">

                    <img src="../images/dropdown.png" alt="Menu" class="dropdown-arrow">

                </div>

                <div id="profileMenu" class="dropdown-menu">
                    <a href="renewLicensePage.php">Renew License</a>
                    <a href="aiChatbotPage.php">AI Chatbot</a>
                    <a href="submitFeedbackPage.php">Submit Feedback</a>
                    <a href="../controller/logOutController.php">Log Out</a>
                </div>

            </div>

        </div>

    </header>


    <!-- Main Content -->
    <main class="manage-university-information">

        <div class="profile-header">
            <a href="universityAdminDashboardPage.php" class="btn-back">
                &#8592; Back
            </a>
        </div>

        <!-- University Information -->
        <section class="university-information-header">

            <div class="university-information-icon">
                <img src="../images/info.png" alt="Information">
            </div>

            <div>
                <h1>
                    <?php echo htmlspecialchars($_SESSION['university_name']); ?>
                </h1>

                <p>
                    University ID:
                    <?php echo htmlspecialchars($_SESSION['university_id']); ?>
                </p>
            </div>

        </section>


        <!-- Management Options -->
        <section class="university-management-grid">

            <!-- Faculty -->
            <a href="uploadFacultyListPage.php?type=faculty" class="university-management-card">
                <div class="management-card-icon">
                    <img src="../images/faculty.png" alt="Faculty" class="icon-faculty">
                </div>

                <h2>Upload Faculty</h2>
                <p>Import Faculty Information</p>
            </a>

            <!-- Facility -->
            <a href="uploadFacilityListPage.php?type=facility" class="university-management-card">
                <div class="management-card-icon">
                    <img src="../images/facilityBooking.png" alt="Facility" class="icon-facility">
                </div>

                <h2>Upload Facility</h2>
                <p>Import All Facility Information</p>
            </a>

            <!-- Course Coordinator -->
            <a href="uploadCourseCoordinatorPage.php?type=courseCoordinator" class="university-management-card">
                <div class="management-card-icon">
                    <img src="../images/courseCoordinator.png" alt="Course Coordinator" class="icon-course-coordinator">
                </div>

                <h2>Upload Course Coordinator</h2>
                <p>Import Course Coordinator Info</p>
            </a>

            <!-- Lecturer -->
            <a href="uploadLecturerPage.php?type=lecturer" class="university-management-card">
                <div class="management-card-icon">
                    <img src="../images/lecturer.png" alt="Lecturer" class="icon-lecturer">
                </div>

                <h2>Upload Lecturer</h2>
                <p>Import Lecturer Information</p>
            </a>

            <!-- Student -->
            <a href="uploadStudentListPage.php?type=student" class="university-management-card">
                <div class="management-card-icon">
                    <img src="../images/student.png" alt="Student" class="icon-student">
                </div>

                <h2>Upload Student</h2>
                <p>Import Student Information</p>
            </a>

            <!-- Floor Plan -->
            <a href="uploadFloorPlanPage.php?type=floorPlan" class="university-management-card">
                <div class="management-card-icon">
                    <img src="../images/floorPlan.png" alt="Floor Plan" class="icon-floor-plan">
                </div>

                <h2>Upload Floor Plan</h2>
                <p>Import Floor Plan</p>
            </a>

        </section>

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