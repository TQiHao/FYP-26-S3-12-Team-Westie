<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms & Conditions - UniBee</title>
    <link rel="stylesheet" href="../style.css">
</head>

<body>
    <script src="../script.js"></script>

    <!-- Header -->
    <header class="header">
        <div class="logo-container">
            <a href="viewLandingPage.php">
                <img src="../images/uniBeeLogo.png" alt="UniBee Logo">
            </a>
        </div>

        <div class="welcome-message">
            <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
                <span>Welcome back,</span>
                <strong>
                    <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                </strong>
            <?php else: ?>
                <span>Smart Campus Hub</span>
            <?php endif; ?>
        </div>

        <div class="dashboard-header-right">
            <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
                <a href="NotificationPage.php" class="header-icon">
                    <img src="../images/notification.png" alt="Notifications">
                </a>
            <?php else: ?>
                <a href="loginPage.php" class="btn-primary" style="padding: 6px 16px; font-size: 14px;">Log In</a>
            <?php endif; ?>
        </div>
    </header>

    <!-- Main Content -->
    <main class="dashboard" style="max-width: 900px; margin: 0 auto; padding: 30px 20px;">
        <div class="profile-header" style="margin-bottom: 25px;">
            <a href="javascript:history.back()" class="btn-back">&#8592; Back</a>
            <h2 class="section-label">Terms and Conditions</h2>
            <div></div>
        </div>

        <div class="profile-view"
            style="padding: 30px; background: #ffffff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); line-height: 1.6; color: #333333;">
            <p style="font-size: 14px; color: #666666; margin-bottom: 20px;"><strong>Last Updated:</strong> September
                18, 2026</p>

            <hr class="profile-divider" style="margin: 20px 0;">

            <h3 style="color: #2c3e50; margin-top: 20px; margin-bottom: 10px;">1. Acceptance of Terms</h3>
            <p style="margin-bottom: 20px;">
                By creating an account and using UniBee, you agree to comply with these Terms and Conditions.
            </p>

            <h3 style="color: #2c3e50; margin-top: 20px; margin-bottom: 10px;">2. User Account</h3>
            <p style="margin-bottom: 20px;">
                Users are responsible for providing accurate information and keeping their account credentials secure.
            </p>

            <h3 style="color: #2c3e50; margin-top: 20px; margin-bottom: 10px;">3. Use of UniBee</h3>
            <p style="margin-bottom: 20px;">
                UniBee provides university-related services including academic information, facility booking, events,
                study groups, and other features.
            </p>

            <h3 style="color: #2c3e50; margin-top: 20px; margin-bottom: 10px;">4. Facility Booking</h3>
            <p style="margin-bottom: 20px;">
                Users must use booked facilities responsibly and follow university rules. Users should cancel bookings
                they no longer require.
            </p>

            <h3 style="color: #2c3e50; margin-top: 20px; margin-bottom: 10px;">5. Account Suspension</h3>
            <p style="margin-bottom: 20px;">
                Accounts may be suspended or restricted if users violate these Terms and Conditions.
            </p>

            <h3 style="color: #2c3e50; margin-top: 20px; margin-bottom: 10px;">6. Changes to Terms</h3>
            <p style="margin-bottom: 0;">
                UniBee may update these Terms and Conditions when necessary.
            </p>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <div class="footer-bottom-bar">
            &copy; 2026 UniBee. All rights reserved.
        </div>
    </footer>
</body>

</html>