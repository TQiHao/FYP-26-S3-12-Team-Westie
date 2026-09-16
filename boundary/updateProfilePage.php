<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: loginPage.php");
    exit();
}

require_once "../controller/UpdateProfileController.php";

$controller = new UpdateProfileController();
$user = $controller->getUserById($_SESSION['user_id']);

if (!$user) {
    $_SESSION['profile_error'] = "Unable to load profile information. Please try again.";
    header("Location: ManageProfilePage.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile - UniBee</title>
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
                    <a href="AIChatbotPage.php">AI Chatbot</a>
                    <a href="AcademicsPage.php">Academics</a>
                    <a href="FacilitiesBookingPage.php">Facility Booking</a>
                    <a href="CampusEventsPage.php">University Campus Event</a>
                    <a href="../controller/logoutController.php">Log Out</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Update Profile Section -->
    <main class="dashboard">

        <?php
        if (isset($_SESSION['profile_error'])) {
            echo '<div class="error-message">' . $_SESSION['profile_error'] . '</div>';
            unset($_SESSION['profile_error']);
        }
        ?>

        <div class="profile-header">
            <h2 class="section-label">Update Profile</h2>
            <a href="ManageProfilePage.php" class="btn-secondary">Cancel</a>
        </div>

        <form action="../controller/UpdateProfileController.php" method="POST" class="profile-form">

            <div class="form-group">
                <label>Email address (cannot be changed)</label>
                <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
            </div>

            <div class="form-group">
                <label for="fullName">Full Name</label>
                <input type="text" id="fullName" name="fullName"
                       value="<?php echo htmlspecialchars($user['fullName']); ?>" required>
            </div>

            <div class="form-group">
                <label for="contactNumber">Contact Number</label>
                <input type="text" id="contactNumber" name="contactNumber"
                       value="<?php echo htmlspecialchars($user['contactNumber'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
            </div>

            <button type="submit" name="update_profile" class="btn-primary">Save Changes</button>

        </form>

    </main>

    <!-- Footer -->
    <footer>
        <div class="footer-bottom-bar">
            &copy; 2026 UniBee. All rights reserved.
        </div>
    </footer>
</body>
</html>