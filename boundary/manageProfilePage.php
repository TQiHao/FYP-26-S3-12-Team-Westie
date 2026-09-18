<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: loginPage.php");
    exit();
}

require_once "../controller/ManageProfileController.php";

$controller = new ManageProfileController();
$user = $controller->getUserById($_SESSION['user_id']);

if (!$user) {
    $_SESSION['profile_error'] = "Unable to load profile information. Please try again.";
}

// Determine user role and corresponding dashboard back link
$userRole = $_SESSION['role'] ?? 'student';
$backDashboard = ($userRole === 'lecturer') ? 'lecturerDashboardPage.php' : 'studentDashboardPage.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Profile - UniBee</title>
    <link rel="stylesheet" href="../style.css">
</head>

<body>
    <script src="../script.js"></script>

    <!-- Header -->
    <header class="header">
        <div class="logo-container">
            <img src="../images/uniBeeLogo.png" alt="UniBee Logo">
        </div>

        <div class="welcome-message">
            <span>Welcome back,</span>
            <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>
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
                    <a href="ManageProfilePage.php">Manage Profile</a>

                    <?php if ($userRole === 'lecturer'): ?>
                        <a href="TeachingPage.php">Teaching</a>
                        <a href="CampusEventsPage.php">University Campus Events</a>
                        <a href="SubmitFeedbackPage.php">Submit Feedback</a>
                    <?php else: ?>
                        <a href="AIChatbotPage.php">AI Chatbot</a>
                        <a href="AcademicsPage.php">Academics</a>
                        <a href="FacilitiesBookingPage.php">Facility Booking</a>
                        <a href="CampusEventsPage.php">University Campus Event</a>
                    <?php endif; ?>

                    <a href="../controller/logoutController.php">Log Out</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Manage Profile Section -->
    <main class="dashboard">

        <?php
        if (isset($_SESSION['profile_success'])) {
            echo '<div class="success-message">' . $_SESSION['profile_success'] . '</div>';
            unset($_SESSION['profile_success']);
        }
        if (isset($_SESSION['profile_error'])) {
            echo '<div class="error-message">' . $_SESSION['profile_error'] . '</div>';
            unset($_SESSION['profile_error']);
        }
        ?>

        <?php if ($user): ?>

            <div class="profile-header">
                <!-- Dynamic Back Button -->
                <a href="<?php echo $backDashboard; ?>" class="btn-back">&#8592; Back</a>

                <h2 class="section-label">My Profile</h2>

                <a href="UpdateProfilePage.php" class="btn-update">Update</a>
            </div>

            <div class="profile-view">

                <!-- Profile Icon and Name -->
                <div class="profile-identity">
                    <img src="../images/profilePic.png" alt="Profile" class="profile-large-icon">
                    <div>
                        <h2><?php echo htmlspecialchars($user['fullName']); ?></h2>
                        <p>User ID: <?php echo htmlspecialchars($user['id']); ?></p>
                    </div>
                </div>

                <hr class="profile-divider">

                <!-- Profile Details -->
                <div class="profile-details">

                    <div class="profile-field">
                        <label>Email</label>
                        <p><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>

                    <div class="profile-field">
                        <label>Contact Number</label>
                        <p><?php echo htmlspecialchars($user['contactNumber'] ?? 'Not set'); ?></p>
                    </div>

                    <div class="profile-field">
                        <label>Address</label>
                        <p><?php echo htmlspecialchars($user['address'] ?? 'Not set'); ?></p>
                    </div>

                    <div class="profile-field">
                        <label>Role</label>
                        <p><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $user['role']))); ?></p>
                    </div>

                </div>

            </div>

        <?php else: ?>
            <p class="error-message">Unable to load profile information. Please try again later.</p>
        <?php endif; ?>

    </main>

    <!-- Footer -->
    <footer>
        <div class="footer-bottom-bar">
            &copy; 2026 UniBee. All rights reserved.
        </div>
    </footer>
</body>

</html>