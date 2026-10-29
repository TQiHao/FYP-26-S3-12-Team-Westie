<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: loginPage.php");
    exit();
}

require_once "../database/database.php";
$database = new Database();
$db = $database->connect();
$universityId = $_SESSION['university_id'] ?? 0;

$licenseStmt = $db->prepare("SELECT expiryDate FROM UniversityLicenses WHERE universityId = ? ORDER BY expiryDate DESC LIMIT 1");
$licenseStmt->execute([$universityId]);
$license = $licenseStmt->fetch(PDO::FETCH_ASSOC);
$licenseText = "No active license";
if ($license) {
    $expiryTs = strtotime($license['expiryDate']);
    $now = time();
    if ($expiryTs > $now) {
        $daysLeft = ceil(($expiryTs - $now) / 86400);
        $years = floor($daysLeft / 365);
        $months = floor(($daysLeft % 365) / 30);
        $licenseText = "";
        if ($years > 0)
            $licenseText .= "$years year" . ($years > 1 ? "s" : "") . " ";
        if ($months > 0)
            $licenseText .= "$months month" . ($months > 1 ? "s" : "") . " ";
        $licenseText = trim($licenseText) . " left";
        if (empty(trim($licenseText)))
            $licenseText = "Expires soon";
    } else {
        $licenseText = "License expired";
    }
}

$eventStmt = $db->prepare("SELECT COUNT(*) FROM Events WHERE universityId = ? AND status = 'active' AND endDatetime >= NOW()");
$eventStmt->execute([$universityId]);
$eventCount = (int) $eventStmt->fetchColumn();

$facStmt = $db->prepare("SELECT COUNT(*) FROM BookableFacilities bf INNER JOIN Facilities f ON bf.facilityId = f.id WHERE f.universityId = ?");
$facStmt->execute([$universityId]);
$facCount = (int) $facStmt->fetchColumn();

$faqStmt = $db->prepare("SELECT COUNT(*) FROM FAQs WHERE universityId = ?");
$faqStmt->execute([$universityId]);
$faqCount = (int) $faqStmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University Admin Dashboard - UniBee</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        html,
        body {
            height: 100vh;
            margin: 0;
            overflow: hidden;
        }

        body {
            display: flex;
            flex-direction: column;
        }

        .header {
            flex-shrink: 0;
        }

        .dashboard {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            padding-top: 10px;
            padding-bottom: 10px;
        }

        .dashboard-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .dashboard-grid {
            flex: 1;
            overflow-y: auto;
            align-content: start;
        }

        footer {
            flex-shrink: 0;
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
            <strong>
                <?php echo htmlspecialchars($_SESSION['user_name']); ?>
            </strong>
        </div>

        <div class="dashboard-header-right">

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

    <main class="dashboard">

        <section class="dashboard-section">

            <h3 class="section-label">Explore</h3>

            <div class="dashboard-grid">

                <a href="renewLicensePage.php" class="dashboard-card">
                    <img src="../images/license.png" alt="Renew License" class="card-icon">
                    <h2>Renew License</h2>
                    <p><?php echo htmlspecialchars($licenseText); ?></p>
                </a>

                <a href="manageUniversityInformationPage.php" class="dashboard-card">
                    <img src="../images/info.png" alt="Manage University Information" class="card-icon">
                    <h2>Manage University Information</h2>
                    <p>Upload University Documents</p>
                </a>

                <a href="manageUniversityEventPage.php" class="dashboard-card">
                    <img src="../images/campusEvent.png" alt="Manage University Events" class="card-icon">
                    <h2>Manage University Events</h2>
                    <p><?php echo $eventCount; ?> Ongoing Event<?php echo $eventCount === 1 ? '' : 's'; ?></p>
                </a>

                <a href="manageBookableFacilitiesPage.php" class="dashboard-card">
                    <img src="../images/facilityBooking.png" alt="Manage Facilities Booking" class="card-icon">
                    <h2>Manage Bookable Facilities</h2>
                    <p><?php echo $facCount; ?> Active Bookable Facilit<?php echo $facCount === 1 ? 'y' : 'ies'; ?></p>
                </a>

                <a href="manageFAQDatabasePage.php" class="dashboard-card">
                    <img src="../images/aichatbot.png" alt="Manage FAQ Database" class="card-icon">
                    <h2>Manage FAQ Database</h2>
                    <p><?php echo $faqCount; ?> active FAQ<?php echo $faqCount === 1 ? '' : 's'; ?></p>
                </a>

                <a href="aiChatbotPage.php" class="dashboard-card">
                    <img src="../images/aichatbot.png" alt="AI Chatbot" class="card-icon">
                    <h2>AI Chatbot</h2>
                    <p>Ask a Question</p>
                </a>

                <a href="submitFeedbackPage.php" class="dashboard-card">
                    <img src="../images/feedback.png" alt="Submit Feedback" class="card-icon">
                    <h2>Submit Feedback</h2>
                    <p>Share your thoughts</p>
                </a>

            </div>

        </section>

    </main>

    <footer>
        <div class="footer-bottom-bar">
            &copy; 2026 UniBee. All rights reserved.
        </div>
    </footer>

</body>

</html>