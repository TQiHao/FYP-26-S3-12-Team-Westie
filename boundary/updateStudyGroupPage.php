<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: loginPage.php");
    exit();
}

require_once "../controller/studentInteractionController.php";

$controller = new StudentInteractionController();
$studentId = $_SESSION['user_id'] ?? null;
$universityId = $_SESSION['university_id'] ?? null;
$groupId = isset($_GET['groupId']) ? (int) $_GET['groupId'] : 0;

// Load the group + modules
$data = $controller->getStudyGroupDetails($groupId, $studentId);
$modules = $controller->getModules($universityId);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_group'])) {

    $formData = [
        'name'        => trim($_POST['name'] ?? ''),
        'moduleId'    => $_POST['moduleId'] ?? null,
        'description' => trim($_POST['description'] ?? ''),
        'maxMembers'  => $_POST['maxMembers'] ?? null
    ];

    if ($controller->updateStudyGroup($groupId, $studentId, $formData)) {
        $_SESSION['group_success'] = "Update Group Successfully";
    } else {
        $_SESSION['group_error'] = "Failed to update study group, please try again later";
    }

    header("Location: updateStudyGroupPage.php?groupId=" . urlencode($groupId));
    exit();
}

// Validate access
if ($data === false) {
    $_SESSION['group_error'] = "Unable to retrieve study group. Please try again later.";
    header("Location: academicsPage.php?tab=interaction");
    exit();
}
if ($data === null || !$data['isAdmin']) {
    header("Location: academicsPage.php?tab=interaction");
    exit();
}

$group = $data['group'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Study Group - UniBee</title>
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
            <a href="academicsPage.php?tab=interaction" class="btn-back">&#8592; Back</a>
            <h2 class="section-label"><?php echo htmlspecialchars($group['name']); ?></h2>
            <div></div>
        </div>

        <p class="semester-label" style="max-width: 700px; margin: 0 auto 15px auto;">
            <?php echo htmlspecialchars($group['moduleCode'] . ' · ' . $group['moduleName']); ?>
        </p>

        <form action="updateStudyGroupPage.php?groupId=<?php echo $groupId; ?>" method="POST" class="create-group-form">

            <div class="form-group">
                <label for="name">Group Name</label>
                <input type="text" id="name" name="name"
                       value="<?php echo htmlspecialchars($group['name']); ?>" required>
            </div>

            <div class="form-group">
                <label for="moduleId">Course</label>
                <select id="moduleId" name="moduleId" required>
                    <option value="">-- Select a course --</option>
                    <?php foreach ($modules as $module): ?>
                        <option value="<?php echo $module['id']; ?>"
                            <?php echo ((int)$module['id'] === (int)$group['moduleId']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($module['code'] . ' - ' . $module['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="5"><?php echo htmlspecialchars($group['description'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label for="maxMembers">Max Members</label>
                <input type="number" id="maxMembers" name="maxMembers" min="2" max="50"
                       value="<?php echo htmlspecialchars($group['maxMembers']); ?>" required>
            </div>

            <div class="create-group-actions">
                <button type="submit" name="update_group" class="btn-create">Save</button>
                <a href="academicsPage.php?tab=interaction" class="btn-cancel-create">Cancel</a>
            </div>

        </form>

    </main>

    <!-- Success Modal -->
    <?php if (isset($_SESSION['group_success'])): ?>
        <div class="modal-overlay" id="groupSuccessModal">
            <div class="modal-box">
                <span class="modal-close" onclick="closeGroupModal()">&times;</span>
                <p class="modal-message"><?php echo htmlspecialchars($_SESSION['group_success']); ?></p>
            </div>
        </div>
        <?php unset($_SESSION['group_success']); ?>
    <?php endif; ?>

    <!-- Error Modal -->
    <?php if (isset($_SESSION['group_error'])): ?>
        <div class="modal-overlay" id="groupErrorModal">
            <div class="modal-box">
                <span class="modal-close" onclick="closeGroupErrorModal()">&times;</span>
                <p class="modal-message"><?php echo htmlspecialchars($_SESSION['group_error']); ?></p>
            </div>
        </div>
        <?php unset($_SESSION['group_error']); ?>
    <?php endif; ?>

    <footer>
        <div class="footer-bottom-bar">
            &copy; 2026 UniBee. All rights reserved.
        </div>
    </footer>

    <script>
        function closeGroupModal() {
            var modal = document.getElementById("groupSuccessModal");
            if (modal) modal.style.display = "none";
            window.location.href = "academicsPage.php?tab=interaction";
        }

        function closeGroupErrorModal() {
            var modal = document.getElementById("groupErrorModal");
            if (modal) modal.style.display = "none";
            window.location.reload();
        }
    </script>
</body>

</html>