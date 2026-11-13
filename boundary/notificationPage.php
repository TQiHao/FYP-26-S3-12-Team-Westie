<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    $_SESSION['login_error'] = "Session expired. Please log in again.";
    header("Location: loginPage.php");
    exit();
}

require_once "../controller/authGuard.php";
requireRole(['student', 'lecturer']);

require_once "../controller/notificationController.php";

$controller = new NotificationController();
$userId = $_SESSION['user_id'] ?? null;

$controller->markAllAsRead($userId); 

$userRole = strtolower($_SESSION['user_role'] ?? $_SESSION['role'] ?? 'student');
$isLecturer = in_array($userRole, ['lecturer', 'staff', 'teacher']);

$backDashboard = $isLecturer ? 'lecturerDashboardPage.php' : 'studentDashboardPage.php';

$notifications = $controller->getNotification($userId);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - UniBee</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .profile-header {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%;
            min-height: 45px;
            margin: 15px 0 25px 0;
        }

        .btn-back {
            position: absolute;
            left: 0;
            margin: 0;
            z-index: 10;
        }

        .profile-header .section-label {
            position: absolute;
            left: 70%;
            transform: translateX(-50%);
            margin: 0 !important;
            text-align: center !important;
            white-space: nowrap;
        }

        .notification-message {
            font-size: 14px;
            color: #475569;
            margin: 6px 0 0 0;
            line-height: 1.4;
        }

        @media (max-width: 768px) {
            .profile-header {
                flex-direction: row;
                flex-wrap: nowrap;
                align-items: center;
                justify-content: flex-start;
                gap: 12px;
                margin: 10px 0 20px 0;
            }

            .btn-back {
                position: static;
                flex-shrink: 0;
            }

            .profile-header .section-label {
                position: static;
                left: auto;
                transform: none;
                text-align: left !important;
                white-space: normal;
                min-width: 0;
                font-size: 1.3rem;
            }
        }
    </style>
</head>

<body>
    <script src="../script.js"></script>

    <header class="header">
        <div class="logo-container">
            <img src="../images/uniBeeLogo.png" alt="UniBee Logo">
        </div>

        <div class="welcome-message">
            <span>Welcome back,</span>
            <strong><?php echo htmlspecialchars(($_SESSION['user_name'] ?? 'User') . ' (' . ucwords(str_replace('_', ' ', strtolower($_SESSION['user_role'] ?? $_SESSION['role'] ?? 'User'))) . ')'); ?></strong>
        </div>

        <div class="dashboard-header-right">
            <a href="notificationPage.php" class="header-icon">
                <img src="../images/notification.png" alt="Notifications">
            </a>

            <div class="profile-dropdown">
                <div class="profile-container" onclick="toggleDropdown()">
                    <img src="../images/profilePic.png" alt="Profile" class="profile-icon">
                    <img src="../images/dropdown.png" alt="Menu" class="dropdown-arrow">
                </div>

                <div id="profileMenu" class="dropdown-menu">
                    <a href="manageProfilePage.php">Manage Profile</a>
                    <?php if ($isLecturer): ?>
                        <a href="teachingPage.php">Teaching</a>
                        <a href="viewEventsPage.php">University Campus Events</a>
                        <a href="submitFeedbackPage.php">Submit Feedback</a>
                    <?php else: ?>
                        <a href="aiChatbotPage.php">AI Chatbot</a>
                        <a href="academicsPage.php">Academics</a>
                        <a href="facilitiesBookingPage.php">Facility Booking</a>
                        <a href="viewEventsPage.php">University Campus Event</a>
                    <?php endif; ?>
                    <a href="../controller/logOutController.php">Log Out</a>
                </div>
            </div>
        </div>
    </header>

    <main class="dashboard">

        <div class="profile-header">
            <a href="<?php echo $backDashboard; ?>" class="btn-back">&#8592; Back</a>
            <h2 class="section-label">Notifications</h2>
        </div>

        <?php if ($notifications === false): ?>
            <p class="error-message">Unable to load notifications. Please try again later.</p>

        <?php elseif (empty($notifications)): ?>
            <p class="no-notifications">You have no new notifications.</p>

        <?php else: ?>
            <div class="notifications-list">

                <?php foreach ($notifications as $n): ?>

                    <?php
                    $icon = '../images/notification.png';
                    switch ($n['type'] ?? '') {
                        case 'event':
                            $icon = '../images/upcomingEvent.png';
                            break;
                        case 'booking':
                            $icon = '../images/facilityBooking.png';
                            break;
                        case 'exam':
                        case 'class':
                            $icon = '../images/academic.png';
                            break;
                        case 'study_grp':
                            $icon = '../images/studyGrp.png';
                            break;
                        case 'system':
                        default:
                            $icon = '../images/notification.png';
                            break;
                    }
                    ?>

                    <div class="notification-item">
                        <img src="<?php echo $icon; ?>" alt="Icon" class="notification-icon">

                        <div class="notification-content">
                            <p class="notification-title">
                                <?php echo htmlspecialchars($n['title'] ?? ''); ?>
                            </p>
                            <?php if (!empty($n['message'])): ?>
                                <p class="notification-message">
                                    <?php echo nl2br(htmlspecialchars($n['message'])); ?>
                                </p>
                            <?php endif; ?>
                            <p class="notification-time">
                                <?php echo htmlspecialchars(date('d M Y, g:i A', strtotime($n['createdAt'] ?? ''))); ?>
                            </p>
                        </div>
                    </div>

                <?php endforeach; ?>

            </div>
        <?php endif; ?>

    </main>

    <footer>
        <div class="footer-bottom-bar">
            &copy; 2026 UniBee. All rights reserved.
        </div>
    </footer>
</body>

</html>