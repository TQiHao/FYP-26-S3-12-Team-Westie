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

// Fetch modules for the course dropdown
$modules = $controller->getModules($universityId);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['create_group'])) {

    $data = [
        'universityId' => $universityId,
        'moduleId'     => $_POST['moduleId'] ?? null,
        'name'         => trim($_POST['name'] ?? ''),
        'description'  => trim($_POST['description'] ?? ''),
        'maxMembers'   => $_POST['maxMembers'] ?? null
    ];

    if ($controller->createStudyGroup($studentId, $data)) {
        $_SESSION['group_success'] = "Group Created Successfully";
    } else {
        $_SESSION['group_error'] = "Failed to create study group, please try again later";
    }

    header("Location: createStudyGroupPage.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Study Group - UniBee</title>
    <link rel="stylesheet" href="../style.css">
</head>

<body>
    <script src="../script.js"></script>

    <!-- Header -->
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
            <h2 class="section-label">Create Study Group</h2>
            <div></div>
        </div>

        <form action="createStudyGroupPage.php" method="POST" class="create-group-form">

            <div class="form-group">
                <label for="name">Group Name</label>
                <input type="text" id="name" name="name" placeholder="Enter group name" required>
            </div>

            <div class="form-group">
                <label for="moduleId">Course</label>
                <select id="moduleId" name="moduleId" required>
                    <option value="">-- Select a course --</option>
                    <?php foreach ($modules as $module): ?>
                        <option value="<?php echo $module['id']; ?>">
                            <?php echo htmlspecialchars($module['code'] . ' - ' . $module['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="5" placeholder="Describe the study group..."></textarea>
            </div>

            <div class="form-group">
                <label for="maxMembers">Max Members</label>
                <input type="number" id="maxMembers" name="maxMembers" min="2" max="50" placeholder="e.g. 10" required>
            </div>

            <div class="create-group-actions">
                <button type="submit" name="create_group" class="btn-create">Create</button>
                <a href="academicsPage.php?tab=interaction" class="btn-cancel-create">Cancel</a>
            </div>

        </form>

    </main>

    <!-- Success Modal -->
    <?php if (isset($_SESSION['group_success'])): ?>
        <div class="modal-overlay" id="successModal">
            <div class="modal-box">
                <span class="modal-close" onclick="closeModal()">&times;</span>
                <p class="modal-message"><?php echo htmlspecialchars($_SESSION['group_success']); ?></p>
            </div>
        </div>
        <?php unset($_SESSION['group_success']); ?>
    <?php endif; ?>

    <!-- Error Modal -->
    <?php if (isset($_SESSION['group_error'])): ?>
        <div class="modal-overlay" id="errorModal">
            <div class="modal-box">
                <span class="modal-close" onclick="closeErrorModal()">&times;</span>
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
        function closeModal() {
            var modal = document.getElementById("successModal");
            if (modal) modal.style.display = "none";
            window.location.href = "academicsPage.php?tab=interaction";
        }

        function closeErrorModal() {
            var modal = document.getElementById("errorModal");
            if (modal) modal.style.display = "none";
        }
    </script>
</body>

</html>