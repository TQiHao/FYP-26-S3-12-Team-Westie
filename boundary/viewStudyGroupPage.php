<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: loginPage.php");
    exit();
}

require_once "../controller/studentInteractionController.php";

$controller = new StudentInteractionController();
$studentId = $_SESSION['user_id'] ?? null;
$groupId = isset($_GET['groupId']) ? (int) $_GET['groupId'] : 0;

$data = $controller->getStudyGroupDetails($groupId, $studentId);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Study Group - UniBee</title>
    <link rel="stylesheet" href="../style.css">
</head>

<body>
    <script src="../script.js"></script>

    <header class="header">
        <div class="logo-container">
            <a href="studentDashboardPage.php">
                <img src="../images/uniBeeLogo.png" alt="UniBee Logo">
            </a>
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
                    <a href="academicsPage.php">Academics</a>
                    <a href="viewFacilitiesPage.php">Facility Booking</a>
                    <a href="viewEventsPage.php">University Campus Event</a>
                    <a href="../controller/logoutController.php">Log Out</a>
                </div>
            </div>
        </div>
    </header>

    <main class="dashboard">

        <div class="profile-header">
            <a href="academicsPage.php?tab=interaction" class="btn-back">&#8592; Back to study groups</a>
            <h2 class="section-label"></h2>
            <div></div>
        </div>

        <?php if ($data === false): ?>
            <p class="error-message">Unable to retrieve study groups. Please try again later.</p>

        <?php elseif ($data === null): ?>
            <p class="no-notifications">You are not part of any study group.</p>

        <?php else: ?>

            <?php
            $group = $data['group'];
            $isMember = $data['isMember'];
            $isAdmin = $data['isAdmin'];
            $members = $data['members'];
            $currentCount = count($members);
            ?>

            <div class="group-view-container">

                <h2 class="group-view-title"><?php echo htmlspecialchars($group['name']); ?></h2>
                <p class="group-view-meta">
                    <?php echo htmlspecialchars($group['moduleCode'] . ' · ' . $group['moduleName']); ?>
                    · <?php echo $currentCount; ?>/<?php echo htmlspecialchars($group['maxMembers']); ?> members
                </p>

                <h3 class="group-section-title">Description</h3>
                <p class="group-view-description">
                    <?php echo htmlspecialchars($group['description'] ?? 'No description provided.'); ?>
                </p>

                <h3 class="group-section-title">Members (<?php echo $currentCount; ?>)</h3>
                <div class="group-members-row">
                    <?php
                    $displayCount = min(5, $currentCount);
                    for ($i = 0; $i < $displayCount; $i++):
                    ?>
                        <img src="../images/profilePic.png" alt="Member" class="group-member-avatar">
                    <?php endfor; ?>

                    <?php if ($currentCount > 5): ?>
                        <span class="group-member-overflow">+<?php echo $currentCount - 5; ?></span>
                    <?php endif; ?>
                </div>

                <?php if ($isMember): ?>

                 <p class="group-member-note">You are already a member of this group.</p>
                <?php else: ?>
                    <form action="joinStudyGroupPage.php" method="POST" class="group-join-form">
                        <input type="hidden" name="groupId" value="<?php echo $group['id']; ?>">
                        <button type="submit" name="join_group" class="btn-join-group">Join Group</button>
                    </form>
                <?php endif; ?>

            </div>

        <?php endif; ?>

    </main>

        <!-- Success Modal -->
        <?php if (isset($_SESSION['join_success'])): ?>
            <div class="modal-overlay" id="joinSuccessModal">
                <div class="modal-box">
                    <span class="modal-close" onclick="closeJoinModal()">&times;</span>
                    <p class="modal-message"><?php echo htmlspecialchars($_SESSION['join_success']); ?></p>
                </div>
            </div>
            <?php unset($_SESSION['join_success']); ?>
        <?php endif; ?>

        <!-- Error Modal -->
        <?php if (isset($_SESSION['join_error'])): ?>
            <div class="modal-overlay" id="joinErrorModal">
                <div class="modal-box">
                    <span class="modal-close" onclick="closeErrorModal()">&times;</span>
                    <p class="modal-message"><?php echo htmlspecialchars($_SESSION['join_error']); ?></p>
                </div>
            </div>
            <?php unset($_SESSION['join_error']); ?>
        <?php endif; ?>

        <script>
            function closeJoinModal() {
                var modal = document.getElementById("joinSuccessModal");
                if (modal) {
                    modal.style.display = "none";
                }
                window.location.href = "academicsPage.php?tab=interaction";
            }

            function closeErrorModal() {
                var modal = document.getElementById("joinErrorModal");
                if (modal) {
                    modal.style.display = "none";
                    window.location.reload();
                }
            }
        </script>

    <footer>
        <div class="footer-bottom-bar">
            &copy; 2026 UniBee. All rights reserved.
        </div>
    </footer>
</body>

</html>