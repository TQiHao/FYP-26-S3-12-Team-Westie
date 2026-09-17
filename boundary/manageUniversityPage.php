<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: LoginPage.php");
    exit();
}

require_once "../controller/manageUniversityController.php";

$controller=new ManageUniversityController();
$pendingRegistrations=$controller->getPendingRegistrations();
$existingUniversities=$controller->getExistingUniversities();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage University - UniBee</title>

    <link rel="stylesheet" href="../style.css">
</head>

<body>
    <script src="../script.js"></script>

    <!-- Header -->
    <header class="header">

        <div class="logo-container">
            <img src="../images/uniBeeLogo.png" alt="UniBee Logo">
        </div>

        <!-- Header Icons -->
        <div class="dashboard-header-right">

            <!-- Profile + Dropdown -->
            <div class="profile-dropdown">

                <div class="profile-container" onclick="toggleDropdown()">
                    <img src="../images/profilePic.png" alt="Profile" class="profile-icon">
                    <img src="../images/dropdown.png" alt="Menu" class="dropdown-arrow">

                </div>

                <div id="profileMenu" class="dropdown-menu">

                    <a href="systemAdminDashboardPage.php">Dashboard</a>
                    <a href="">Profile</a>
                    <a href="">Universities</a>
                    <a href="">Manage Landing Page</a>
                    <a href="">Update AI Model</a>
                    <a href="">System Operations</a>
                    <a href="../controller/logoutController.php">Log Out</a>

                </div>

            </div>

        </div>

    </header>

    <main class="admin-table-page">

    <!-- Pending Registration -->
    <section class="table-section">
        <h3 class="section-label">Pending University Registrations</h3>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>SID</th>
                    <th>University Name</th>
                    <th>Registration Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>

                <?php if (empty($pendingRegistrations)): ?>
                    <tr>
                        <td colspan="6" class="empty-row">No pending registrations.</td>
                    </tr>
                
                <?php else: ?>
                    <?php foreach ($pendingRegistrations as $reg): ?>
                        <tr>
                            <td>UNI-<?php echo str_pad($reg['id'], 4, '0', STR_PAD_LEFT); ?></td>
                            <td><?php echo htmlspecialchars($reg['applicantName']); ?></td>
                            <td><?php echo htmlspecialchars($reg['applicantEmail']); ?></td>
                            <td><?php echo date('d F Y', strtotime($reg['createdAt'])); ?></td>
                            <td><span class="status-badge status-pending">Pending</span></td>

                            <td>
                                <a href="ReviewRegistrationPage.php?id=<?php echo $reg['id']; ?>" class="action-btn view-btn">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

    </section>

    <!-- Existing Universities -->
    <section class="table-section">
        <h3 class="section-label">Exsting Universities</h3>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>SID</th>
                    <th>University Name</th>
                    <th>Status</th>
                    <th>License</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($exsistingUniversities)): ?>
                    <tr>
                        <td colspan="5" class="empty-row">No Universities Found.</td>
                    </tr>
                
                <?php else: ?>
                    <?php foreach ($existingUniversities as $uni): ?>
                        <tr>
                            <td>UNI-<?php echo str_pad($uni['id'], 4, 0, STR_PAD_LEFT); ?></td>
                            <td><?php echo htmlspecialchars($uni['name']); ?></td>
                            <td>
                                <?php if ($uni['status'] === 'active'): ?>
                                    <span class="status-badge status-active">Active</span>
                                <?php else: ?>
                                    <span class="status-badge status-suspended">Suspended</span>

                                <?php endif; ?>
                            </td>

                            <td>
                                <?php echo $uni['licenseExpiryDate']
                                    ? date('d F Y', strtotime($uni['licenseExpiryDate'])): 'No license on record'; ?>
                            </td>
                            
                            <td class="action-cell">
                                <a href="approvalUniversityPage.php?id=<?php echo $uni['id']; ?>" class="action-btn view-btn">View</a>
                                <?php if ($uni['status'] === 'active'): ?>
                                    <form action="../controller/universityManagementController.php" method="POST" class="inline-form">
                                        <input type="hidden" name="university_id" value="<?php echo $uni['id']; ?>">
                                        <input type="hidden" name="action" value="suspend">
                                        <button type="submit" class="action-btn suspend-btn" onclick="return confirm('Suspend this university?');">
                                            Suspend
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form action="../controller/universityManagementController.php" method="POST" class="inline-form">
                                        <input type="hidden" name="university_id" value="<?php echo $uni['id']; ?>">
                                        <input type="hidden" name="action" value="reactivate">
                                        <button type="submit" class="action-btn reactivate-btn" onclick="return confirm('Reactivate this university?');">
                                            Reactivate
                                        </button>
                                        </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
    </main>

    <footer>
        <div class="footer-bottom-bar">
            &copy; 2026 UniBee. All rights reserved.
        </div>
    </footer>
 
</body>
</html>