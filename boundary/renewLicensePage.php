<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: loginPage.php");
    exit();
}

$userRole = strtolower($_SESSION['user_role'] ?? $_SESSION['role'] ?? '');
if ($userRole !== 'university_admin' && $userRole !== 'university admin') {
    header("Location: loginPage.php");
    exit();
}

require_once "../controller/renewLicenseController.php";

$controller = new RenewLicenseController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plan = (int) ($_POST['plan'] ?? 0);
    $result = $controller->renewLicense($plan);
    if (!empty($result['success'])) {
        $_SESSION['renew_success'] = $result['message'];
    } else {
        $_SESSION['renew_error'] = $result['message'];
    }
    header("Location: renewLicensePage.php");
    exit();
}

$university = $controller->getUniversity();
$currentLicense = $university ? $university->getCurrentLicense() : null;

$success = $_SESSION['renew_success'] ?? null;
$error = $_SESSION['renew_error'] ?? null;
unset($_SESSION['renew_success'], $_SESSION['renew_error']);

$daysLeft = 0;
$expiryFormatted = '—';
$statusLabel = 'No License';
$statusClass = 'status-inactive';

if ($currentLicense) {
    $expiryTs = strtotime($currentLicense['expiryDate']);
    $daysLeft = max(0, (int) ceil(($expiryTs - time()) / 86400));
    $expiryFormatted = date('d M Y', $expiryTs);
    $statusLabel = ucfirst($currentLicense['status']);
    $statusClass = strtolower($currentLicense['status']) === 'active'
        ? 'status-active'
        : 'status-inactive';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Renew License - UniBee</title>
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
            left: 60%;
            transform: translateX(-50%);
            margin: 0 !important;
            text-align: center !important;
            white-space: nowrap;
        }

        .renew-page {
            width: 80%;
            max-width: 1050px;
            margin: 0 auto;
            padding: 30px 0 60px;
            box-sizing: border-box;
        }

        .renew-card {
            border: 1px solid #ddd;
            border-radius: 10px;
            background: #fff;
            padding: 30px 34px 40px;
            margin-top: 10px;
        }

        .license-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 30px;
            padding-bottom: 22px;
            border-bottom: 1px solid #eee;
            margin-bottom: 28px;
        }

        .license-info-item .label {
            display: block;
            font-size: 0.95rem;
            font-weight: 700;
            color: #222;
            margin-bottom: 10px;
        }

        .license-info-item .value {
            font-size: 1rem;
            color: #333;
        }

        .license-info-item .status-active {
            color: #16a34a;
            font-weight: 700;
        }

        .license-info-item .status-inactive {
            color: #b91c1c;
            font-weight: 700;
        }

        .duration-title {
            font-size: 1rem;
            font-weight: 700;
            color: #222;
            margin: 0 0 20px 0;
        }

        .duration-buttons {
            display: flex;
            gap: 40px;
            flex-wrap: wrap;
        }

        .duration-btn {
            flex: 1;
            min-width: 180px;
            padding: 16px 24px;
            background: var(--bg-yellow);
            color: #111;
            border: 1px solid #111;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .duration-btn:hover {
            background: #e6c23a;
        }

        .renew-error {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            background: #fee2e2;
            border: 1px solid #fca5a5;
            color: #991b1b;
        }

        .message-overlay {
            position: fixed;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.25);
            z-index: 99999;
        }

        .message-modal {
            position: relative;
            width: 425px;
            height: 175px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f8fa;
            border: 1px solid #555;
            box-sizing: border-box;
        }

        .message-modal h2 {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 700;
            color: #000;
            text-align: center;
            padding: 0 20px;
        }

        .message-close {
            position: absolute;
            top: 10px;
            right: 14px;
            padding: 0;
            border: none;
            background: transparent;
            font-size: 28px;
            font-weight: 700;
            line-height: 1;
            color: #000;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .renew-page {
                width: 90%;
            }

            .license-info-grid {
                grid-template-columns: 1fr;
                gap: 18px;
            }

            .duration-buttons {
                gap: 15px;
            }
        }
    </style>
</head>

<body>

    <script src="../script.js"></script>

    <header class="header">
        <div class="logo-container">
            <a href="universityAdminDashboardPage.php">
                <img src="../images/uniBeeLogo.png" alt="UniBee Logo">
            </a>
        </div>

        <div class="welcome-message">
            <span>Welcome back,</span>
            <strong>
                <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'University Admin'); ?>
            </strong>
        </div>

        <div class="dashboard-header-right">
            <div class="profile-dropdown">
                <div class="profile-container" onclick="toggleDropdown()">
                    <img src="../images/profilePic.png" alt="Profile" class="profile-icon">
                    <img src="../images/dropdown.png" alt="Menu" class="dropdown-arrow">
                </div>
                <div id="profileMenu" class="dropdown-menu">
                    <a href="manageClassPage.php">Manage Classes</a>
                    <a href="aiChatbotPage.php">AI Chatbot</a>
                    <a href="submitFeedbackPage.php">Submit Feedback</a>
                    <a href="../controller/logoutController.php">Log Out</a>
                </div>
            </div>
        </div>
    </header>

    <main class="renew-page">

        <div class="profile-header">
            <a href="universityAdminDashboardPage.php" class="btn-back">&#8592; Back</a>
            <h1 class="section-label">Renew License</h1>
        </div>

        <?php if ($error): ?>
            <div class="renew-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="renew-card">

            <div class="license-info-grid">
                <div class="license-info-item">
                    <span class="label">Current License</span>
                    <span class="value">
                        <?php echo $currentLicense ? $daysLeft . ' Days Left' : '—'; ?>
                    </span>
                </div>

                <div class="license-info-item">
                    <span class="label">License Expiry Date:</span>
                    <span class="value">
                        <?php echo htmlspecialchars($expiryFormatted); ?>
                    </span>
                </div>

                <div class="license-info-item">
                    <span class="label">Status:</span>
                    <span class="value <?php echo htmlspecialchars($statusClass); ?>">
                        <?php echo htmlspecialchars($statusLabel); ?>
                    </span>
                </div>
            </div>

            <h2 class="duration-title">Select Renewal Duration</h2>

            <div class="duration-buttons">
                <button type="button" class="duration-btn" onclick="confirmRenew(1)">1 Year</button>
                <button type="button" class="duration-btn" onclick="confirmRenew(2)">2 Years</button>
                <button type="button" class="duration-btn" onclick="confirmRenew(5)">5 Years</button>
            </div>

        </div>

    </main>

    <form id="renewForm" method="POST" action="renewLicensePage.php" style="display:none;">
        <input type="hidden" name="plan" id="planInput" value="">
    </form>

    <?php if ($success): ?>
        <div class="message-overlay" id="successPopup">
            <div class="message-modal">
                <button type="button" class="message-close"
                    onclick="document.getElementById('successPopup').style.display='none';">
                    &times;
                </button>
                <h2>
                    <?php echo htmlspecialchars($success); ?>
                </h2>
            </div>
        </div>
    <?php endif; ?>

    <footer>
        <div class="footer-bottom-bar">
            &copy; 2026 UniBee. All rights reserved.
        </div>
    </footer>

    <script>
        function confirmRenew(years) {
            var label = years === 1 ? '1 year' : years + ' years';
            if (confirm('Renew your license for ' + label + '? The expiry date will be extended immediately.')) {
                document.getElementById('planInput').value = years;
                document.getElementById('renewForm').submit();
            }
        }
    </script>

</body>

</html>