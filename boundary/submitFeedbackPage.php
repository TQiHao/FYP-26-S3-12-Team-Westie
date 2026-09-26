<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: loginPage.php");
    exit();
}

// Role Filter: Allow Student, Lecturer, Course Coordinator, and University Admin
$user_role = strtolower($_SESSION['user_role'] ?? $_SESSION['role'] ?? '');
$allowed_roles = ['student', 'lecturer', 'course_coordinator', 'course coordinator', 'university_admin', 'university admin'];

if (!in_array($user_role, $allowed_roles)) {
    header("Location: loginPage.php");
    exit();
}

// Determine role-specific dashboard link
switch ($user_role) {
    case 'lecturer':
        $dashboardPage = "lecturerDashboardPage.php";
        break;
    case 'course_coordinator':
    case 'course coordinator':
        $dashboardPage = "courseCoordinatorDashboardPage.php";
        break;
    case 'university_admin':
    case 'university admin':
        $dashboardPage = "universityAdminDashboardPage.php";
        break;
    case 'student':
    default:
        $dashboardPage = "studentDashboardPage.php";
        break;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Feedback - UniBee</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        /* Profile Header matched to Feedback Box Width */
        .profile-header {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            max-width: 800px;
            /* Matches feedback form width */
            margin: 15px auto 25px auto;
            min-height: 40px;
        }

        .btn-back {
            position: absolute;
            left: 0;
            margin: 0;
            z-index: 10;
        }

        .profile-header .section-label {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            margin: 0 !important;
            text-align: center !important;
            white-space: nowrap;
        }

        /* Friendly Reminder Box */
        .feedback-notice-box {
            background-color: #fffbeb;
            border: 1px solid #fde68a;
            border-left: 5px solid #f59e0b;
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 25px;
            color: #1e293b;
            font-size: 14px;
            line-height: 1.6;
        }

        .notice-header {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: bold;
            font-size: 15px;
            color: #b45309;
            margin-bottom: 8px;
        }

        .notice-text {
            margin: 0 0 12px 0;
            color: #334155;
        }

        .notice-contacts {
            background-color: #ffffff;
            border: 1px solid #fef08a;
            border-radius: 6px;
            padding: 10px 14px;
        }

        .notice-contacts p {
            margin: 4px 0;
            font-size: 13.5px;
            color: #0f172a;
        }

        .notice-contacts a {
            color: #2563eb;
            text-decoration: none;
        }

        .notice-contacts a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <script src="../script.js"></script>

    <!-- Header -->
    <header class="header">
        <div class="logo-container">
            <a href="<?php echo htmlspecialchars($dashboardPage); ?>">
                <img src="../images/uniBeeLogo.png" alt="UniBee Logo">
            </a>
        </div>

        <div class="welcome-message">
            <span>Welcome back,</span>
            <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></strong>
        </div>

        <div class="dashboard-header-right">
            <a href="NotificationPage.php" class="header-icon">
                <img src="../images/notification.png" alt="Notifications">
            </a>

            <div class="profile-dropdown">
                <div class="profile-container" onclick="toggleDropdown()">
                    <img src="../images/profilePic.png" alt="Profile" class="profile-icon">
                    <img src="../images/dropdown.png" alt="Menu" class="dropdown-arrow">
                </div>

                <div id="profileMenu" class="dropdown-menu">
                    <?php if ($user_role === 'lecturer'): ?>
                        <a href="manageProfilePage.php">Manage Profile</a>
                        <a href="teachingPage.php">Teaching</a>
                        <a href="campusEventsPage.php">University Campus Events</a>
                    <?php elseif ($user_role === 'course_coordinator' || $user_role === 'course coordinator'): ?>
                        <a href="manageClassesPage.php">Manage Classes</a>
                        <a href="AIChatbotPage.php">AI Chatbot</a>
                    <?php elseif ($user_role === 'university_admin' || $user_role === 'university admin'): ?>
                        <a href="renewLicensePage.php">Renew License</a>
                        <a href="AIChatbotPage.php">AI Chatbot</a>
                    <?php else: ?>
                        <a href="manageProfilePage.php">Manage Profile</a>
                        <a href="AIChatbotPage.php">AI Chatbot</a>
                        <a href="academicsPage.php">Academics</a>
                        <a href="facilitiesBookingPage.php">Facility Booking</a>
                        <a href="campusEventsPage.php">University Campus Event</a>
                    <?php endif; ?>
                    <a href="submitFeedbackPage.php">Submit Feedback</a>
                    <a href="../controller/logoutController.php">Log Out</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Submit Feedback Section -->
    <main class="dashboard">

        <!-- Top Header Aligned with Feedback Box Width -->
        <div class="profile-header">
            <a href="<?php echo htmlspecialchars($dashboardPage); ?>" class="btn-back">&#8592; Back</a>
            <h2 class="section-label">Submit Feedback</h2>
        </div>

        <form action="../controller/SubmitFeedbackController.php" method="POST" class="feedback-form">

            <!-- ===== FRIENDLY REMINDER NOTICE ===== -->
            <div class="feedback-notice-box">
                <div class="notice-header">
                    <span>💡</span> Friendly Reminder
                </div>
                <p class="notice-text">
                    Feedback is limited to <strong>UniBee application-related matters</strong>. For course, lecturer,
                    administrative, or other university-related issues, please contact the relevant university
                    department.
                </p>
                <div class="notice-contacts">
                    <p><strong>University Support Email:</strong> <a
                            href="mailto:support@university.edu.sg">support@university.edu.sg</a></p>
                    <p><strong>Student Service Hotline:</strong> +65 6767 8888</p>
                </div>
            </div>

            <!-- ===== STAR RATING ===== -->
            <div class="form-group">
                <label>How would you rate your experience?</label>

                <div class="star-rating">
                    <input type="radio" id="star5" name="rating" value="5" required>
                    <label for="star5" title="Excellent">&#9733;</label>

                    <input type="radio" id="star4" name="rating" value="4">
                    <label for="star4" title="Good">&#9733;</label>

                    <input type="radio" id="star3" name="rating" value="3">
                    <label for="star3" title="Average">&#9733;</label>

                    <input type="radio" id="star2" name="rating" value="2">
                    <label for="star2" title="Poor">&#9733;</label>

                    <input type="radio" id="star1" name="rating" value="1">
                    <label for="star1" title="Very Poor">&#9733;</label>
                </div>
            </div>

            <!-- ===== MESSAGE ===== -->
            <div class="form-group">
                <label for="message">Feedback</label>
                <textarea id="message" name="message" rows="8" placeholder="Write your feedback here..."
                    required></textarea>
            </div>

            <button type="submit" name="submit_feedback" class="btn-primary">Submit</button>

        </form>

    </main>

    <!-- Success Modal -->
    <?php if (isset($_SESSION['feedback_success'])): ?>
        <div class="modal-overlay" id="successModal">
            <div class="modal-box">
                <span class="modal-close" onclick="closeModal()">&times;</span>
                <p class="modal-message"><?php echo htmlspecialchars($_SESSION['feedback_success']); ?></p>
            </div>
        </div>
        <?php unset($_SESSION['feedback_success']); ?>
    <?php endif; ?>

    <!-- Error Message -->
    <?php if (isset($_SESSION['feedback_error'])): ?>
        <div class="error-message" style="max-width:800px; margin: 20px auto;">
            <?php echo htmlspecialchars($_SESSION['feedback_error']); ?>
        </div>
        <?php unset($_SESSION['feedback_error']); ?>
    <?php endif; ?>

    <!-- Footer -->
    <footer>
        <div class="footer-bottom-bar">
            &copy; 2026 UniBee. All rights reserved.
        </div>
    </footer>

    <script>
        function closeModal() {
            var modal = document.getElementById("successModal");
            if (modal) modal.style.display = "none";
            window.location.href = "<?php echo htmlspecialchars($dashboardPage); ?>";
        }
    </script>
</body>

</html>