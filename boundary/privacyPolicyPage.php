<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - UniBee</title>
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
                <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>
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
            <h2 class="section-label">UniBee Privacy Policy</h2>
            <div></div>
        </div>

        <div class="profile-view"
            style="padding: 30px; background: #ffffff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); line-height: 1.6; color: #333333;">
            <p style="font-size: 14px; color: #666666; margin-bottom: 20px;"><strong>Effective Date:</strong> September
                18, 2026</p>

            <p style="margin-bottom: 20px;">
                At <strong>UniBee</strong>, we prioritize the privacy and security of our students, lecturers, course
                coordinators and university administrators. This Privacy Policy outlines how UniBee collects, uses, and
                protects your
                personal information across our smart campus services, modules, and mobile platform.
            </p>

            <hr class="profile-divider" style="margin: 20px 0;">

            <h3 style="color: #2c3e50; margin-top: 20px; margin-bottom: 10px;">1. Information We Collect</h3>
            <p>We collect personal information to provide efficient campus management and learning services:</p>
            <ul style="margin-left: 20px; margin-bottom: 20px;">
                <li><strong>Information You Provide Directly:</strong> Name, university email address, contact details,
                    profile photo, and role (Student, Lecturer, Course Coordinator, or University Admin) created upon
                    account registration.</li>
                <li><strong>Academic & Campus Activity Data:</strong> Module enrollments, timetable schedules, facility
                    booking requests, study group memberships, and feedback submissions.</li>
                <li><strong>Technical & Device Log Information:</strong> Diagnostic logs, IP addresses, login
                    timestamps, and access activity recorded while using our portal or AI chatbot features.</li>
            </ul>

            <h3 style="color: #2c3e50; margin-top: 20px; margin-bottom: 10px;">2. How We Use Your Information</h3>
            <p>Your data is used strictly to operate and enhance campus workflows:</p>
            <ul style="margin-left: 20px; margin-bottom: 20px;">
                <li>To display personalized dashboards, timetables, and notification reminders.</li>
                <li>To process facility reservations, study group requests, and campus event registrations.</li>
                <li>To enable communication between university members and administrators.</li>
                <li>To maintain system performance, security diagnostics, and resolve technical issues.</li>
            </ul>

            <h3 style="color: #2c3e50; margin-top: 20px; margin-bottom: 10px;">3. Sharing Your Information</h3>
            <p style="margin-bottom: 20px;">
                We do not sell or rent your personal information to third parties. Information is only shared within
                your registered university institution or authorized system administrators strictly for academic and
                operational support.
            </p>

            <h3 style="color: #2c3e50; margin-top: 20px; margin-bottom: 10px;">4. Data Security</h3>
            <p style="margin-bottom: 20px;">
                We implement industry-standard encryption, password hashing, and role-based access controls to safeguard
                your data against unauthorized access, alteration, or disclosure.
            </p>

            <h3 style="color: #2c3e50; margin-top: 20px; margin-bottom: 10px;">5. Contact Support</h3>
            <p style="margin-bottom: 0;">
                If you have questions about your privacy preferences or need to update your stored profile details,
                please reach out to your institution's system administrator or visit the <strong>Submit
                    Feedback</strong> section in your dashboard.
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