<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    $_SESSION['login_error'] = "Session expired. Please log in again.";
    header("Location: loginPage.php");
    exit();
}

require_once __DIR__ . '/../controller/FacilityLocationTrackingController.php';
require_once __DIR__ . '/../database/database.php';

$controller   = new FacilityLocationTrackingController();
$universityId = $_SESSION['university_id'] ?? 0;

$building = $controller->getBuildingForUniversity($universityId);
$floors   = $building ? $controller->getFloors($building['id']) : [];
$nodes    = $building ? $controller->getNodes($building['id'])   : [];
$edges    = $building ? $controller->getEdges($building['id'])   : [];

// Index nodes by floor for drawing
$nodesByFloor = [];
foreach ($nodes as $n) {
    $nodesByFloor[(int) $n['floorId']][] = $n;
}

// Index nodes by ID for fast lookup
$nodeById = [];
foreach ($nodes as $n) {
    $nodeById[(int) $n['id']] = $n;
}

// Dropdown options
$fromOptions = [];
$toOptions   = [];
foreach ($nodes as $n) {
    $label = $n['name'] . ' (' . $n['floorName'] . ')';
    $fromOptions[] = ['id' => (int) $n['id'], 'label' => $label];
    if (strtolower($n['type']) !== 'junction') {
        $toOptions[] = ['id' => (int) $n['id'], 'label' => $label];
    }
}

// Read form state
// Support redirect from Events page: ?facility=<BookableFacilities.id>
if (isset($_GET['facility']) && !isset($_GET['to'])) {
    $bookableFacilityId = (int) $_GET['facility'];
    if ($bookableFacilityId > 0) {
        try {
            $db = new Database();
            $db = $db->connect();
            $sql = "SELECT n.id AS nodeId
                    FROM BookableFacilities bf
                    INNER JOIN Facilities f ON f.id = bf.facilityId
                    INNER JOIN NavigationNodes n ON n.facilityId = f.id
                    WHERE bf.id = ?
                    LIMIT 1";
            $stmt = $db->prepare($sql);
            $stmt->execute([$bookableFacilityId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $_GET['to'] = (int) $row['nodeId'];
            }
        } catch (PDOException $e) {
            error_log("Facility redirect lookup error: " . $e->getMessage());
        }
    }
}
$fromId   = isset($_GET['from']) ? (int) $_GET['from'] : 0;
$toId     = isset($_GET['to'])   ? (int) $_GET['to']   : 0;
$stepFree = isset($_GET['step_free']) && $_GET['step_free'] === '1';

// Defaults: first entrance -> first room (not hardcoded to "Room 201")
if ($fromId === 0) {
    foreach ($nodes as $n) {
        if (strtolower($n['type']) === 'entrance') { $fromId = (int) $n['id']; break; }
    }
}
if ($toId === 0) {
    foreach ($nodes as $n) {
        if (strtolower($n['type']) === 'room') { $toId = (int) $n['id']; break; }
    }
}

// Same from/to → show message
$sameLocation = ($fromId > 0 && $fromId === $toId);

// Compute route
$route = null;
$routeNodeIds = [];
if (!$sameLocation && $fromId > 0 && $toId > 0 && $building) {
    $route = $controller->getRoute($fromId, $toId, $building['id'], $stepFree);
    if ($route) {
        foreach ($route['nodes'] as $rn) $routeNodeIds[] = (int) $rn['id'];
    }
}

// Build route-edge lookup (both directions)
$routeEdges = [];
for ($i = 0; $i < count($routeNodeIds) - 1; $i++) {
    $a = $routeNodeIds[$i];
    $b = $routeNodeIds[$i + 1];
    $routeEdges["$a-$b"] = true;
    $routeEdges["$b-$a"] = true;
}

// Dynamic viewBox for the SVG so all node coordinates fit
$pad = 40;
$minX = !empty($nodes) ? min(array_column($nodes, 'x')) - $pad : 0;
$minY = !empty($nodes) ? min(array_column($nodes, 'y')) - $pad : 0;
$maxX = !empty($nodes) ? max(array_column($nodes, 'x')) + $pad : 340;
$maxY = !empty($nodes) ? max(array_column($nodes, 'y')) + $pad : 220;
$viewBox = $minX . ' ' . $minY . ' ' . ($maxX - $minX) . ' ' . ($maxY - $minY);

$buildingTitle = $building ? $building['buildingName'] : 'Campus';
$blockLabel    = $building ? $building['buildingCode'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facility Location Tracking - UniBee</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .flt-wrapper { max-width: 900px; margin: 0 auto; padding: 0 12px 40px; }
        .flt-title-row { display:flex; align-items:center; justify-content:space-between; margin:10px 0 18px; }
        .flt-title-left { display:flex; align-items:center; gap:8px; }
        .flt-title-left h2 { margin:0; font-size:1.15rem; font-weight:700; }
        .flt-pin { font-size:1rem; }
        .flt-title-right { font-size:0.85rem; color:#555; }
        .flt-form { background:white; border:1px solid #ccc; border-radius:10px; padding:18px 20px; margin-bottom:20px; }
        .flt-form-row { display:flex; gap:20px; margin-bottom:14px; flex-wrap:wrap; }
        .flt-form-col { flex:1; min-width:220px; display:flex; flex-direction:column; gap:6px; }
        .flt-form-col label { font-size:0.85rem; font-weight:600; color:#333; }
        .flt-form-col select { padding:10px 14px; border:1px solid #ccc; border-radius:8px; font-size:0.95rem; background:#fafafa; }
        .flt-checkbox { display:flex; align-items:center; gap:8px; font-size:0.9rem; color:#333; flex:1; }
        .flt-go-btn { padding:10px 26px; background:var(--bg-yellow); color:#222; border:none; border-radius:25px; font-weight:700; cursor:pointer; }
        .flt-go-btn:hover { background:#e6c23a; }
        .flt-floors { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px; }
        .flt-floor-panel { background:#1b1b1b; border-radius:8px; padding:14px; }
        .flt-floor-label { color:#eee; font-size:0.85rem; font-weight:600; margin-bottom:8px; }
        .flt-svg { width:100%; height:auto; display:block; background:#1b1b1b; border-radius:4px; }
        .flt-svg text { fill:#e0e0e0; }
        .flt-legend { display:flex; gap:22px; font-size:0.8rem; color:#333; margin: 0 0 18px 4px; flex-wrap:wrap; }
        .flt-legend span { display:flex; align-items:center; gap:6px; }
        .flt-dot { width:12px; height:12px; border-radius:50%; display:inline-block; }
        .flt-line { width:22px; height:3px; background:#1e90ff; display:inline-block; }
        .flt-summary { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px; }
        .flt-summary-card { background:white; border:1px solid #ccc; border-radius:10px; padding:16px 20px; }
        .flt-summary-title { font-size:0.85rem; color:#666; margin-bottom:4px; }
        .flt-summary-value { font-size:1.5rem; font-weight:700; color:#222; }
        .flt-steps { background:white; border:1px solid #ccc; border-radius:10px; padding:20px 24px; }
        .flt-steps-title { font-size:1rem; font-weight:700; margin-bottom:12px; }
        .flt-step { display:flex; align-items:center; gap:10px; padding:8px 0; border-bottom:1px solid #f0f0f0; font-size:0.9rem; }
        .flt-step:last-child { border-bottom:none; }
        .flt-step-icon { width:22px; text-align:center; }
        @media (max-width:768px) {
            .flt-floors { grid-template-columns:1fr; }
            .flt-summary { grid-template-columns:1fr; }
        }
    </style>
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
            <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?></strong>
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
                    <a href="ViewFacilitiesPage.php">Facility Booking</a>
                    <a href="viewEventsPage.php">University Campus Event</a>
                    <a href="../controller/logoutController.php">Log Out</a>
                </div>
            </div>
        </div>
    </header>

    <nav class="facilities-nav">
        <a href="ViewFacilitiesPage.php" class="facilities-tab">View Available Facilities</a>
        <a href="FacilityLocationTrackingPage.php" class="facilities-tab active">Facility Location Tracking</a>
        <a href="BookFacilityPage.php" class="facilities-tab">Book Facilities</a>
        <a href="ViewMyBookingPage.php" class="facilities-tab">View My Booking</a>
        <a href="CancelMyBookingPage.php" class="facilities-tab">Cancel My Booking</a>
    </nav>

    <main class="dashboard">
        <div class="facility-back-bar">
            <a href="ViewFacilitiesPage.php" class="btn-back">&#8592; Back</a>
        </div>

        <div class="flt-wrapper">
            <div class="flt-title-row">
                <div class="flt-title-left">
                    <span class="flt-pin">📍</span>
                    <h2><?php echo htmlspecialchars($buildingTitle); ?></h2>
                </div>
                <div class="flt-title-right"><?php echo htmlspecialchars($blockLabel); ?></div>
            </div>

            <?php if (!$building || empty($floors)): ?>

                <p class="no-notifications">Campus navigation data is not available yet.</p>

            <?php else: ?>

                <form method="GET" action="FacilityLocationTrackingPage.php" class="flt-form">
                    <div class="flt-form-row">
                        <div class="flt-form-col">
                            <label>From</label>
                            <select name="from">
                                <option value="">-- Select starting point --</option>
                                <?php foreach ($fromOptions as $opt): ?>
                                    <option value="<?php echo $opt['id']; ?>" <?php echo ($opt['id'] === $fromId) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($opt['label']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="flt-form-col">
                            <label>To</label>
                            <select name="to">
                                <option value="">-- Select destination --</option>
                                <?php foreach ($toOptions as $opt): ?>
                                    <option value="<?php echo $opt['id']; ?>" <?php echo ($opt['id'] === $toId) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($opt['label']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="flt-form-row">
                        <label class="flt-checkbox">
                            <input type="checkbox" name="step_free" value="1" <?php echo $stepFree ? 'checked' : ''; ?>>
                            Step-free route (lifts only)
                        </label>
                        <button type="submit" class="flt-go-btn">Find Route</button>
                    </div>
                </form>

                <div class="flt-floors">
                    <?php foreach ($floors as $floor): ?>
                        <?php
                            $fid = (int) $floor['id'];
                            $floorNodes = $nodesByFloor[$fid] ?? [];
                            $floorEdges = array_filter($edges, function ($e) use ($fid) {
                                return (int) $e['fromFloorId'] === $fid && (int) $e['toFloorId'] === $fid;
                            });
                        ?>
                        <div class="flt-floor-panel">
                            <div class="flt-floor-label"><?php echo htmlspecialchars($floor['floorName']); ?></div>
                            <svg class="flt-svg" viewBox="<?php echo $viewBox; ?>" preserveAspectRatio="xMidYMid meet">

                                <!-- Grey edges -->
                                <?php foreach ($floorEdges as $e):
                                    $a = $nodeById[(int) $e['fromNodeId']] ?? null;
                                    $b = $nodeById[(int) $e['toNodeId']]   ?? null;
                                    if (!$a || !$b) continue;
                                ?>
                                    <line x1="<?php echo $a['x']; ?>" y1="<?php echo $a['y']; ?>"
                                          x2="<?php echo $b['x']; ?>" y2="<?php echo $b['y']; ?>"
                                          stroke="#5a5a5a" stroke-width="2"/>
                                <?php endforeach; ?>

                                <!-- Blue route edges (on top) -->
                                <?php foreach ($floorEdges as $e):
                                    $aId = (int) $e['fromNodeId'];
                                    $bId = (int) $e['toNodeId'];
                                    if (!isset($routeEdges["$aId-$bId"])) continue;
                                    $a = $nodeById[$aId] ?? null;
                                    $b = $nodeById[$bId] ?? null;
                                    if (!$a || !$b) continue;
                                ?>
                                    <line x1="<?php echo $a['x']; ?>" y1="<?php echo $a['y']; ?>"
                                          x2="<?php echo $b['x']; ?>" y2="<?php echo $b['y']; ?>"
                                          stroke="#1e90ff" stroke-width="4"/>
                                <?php endforeach; ?>

                                <!-- Nodes -->
                                <?php foreach ($floorNodes as $n):
                                    $type = strtolower($n['type']);
                                    $fill = '#4CAF50';
                                    if ($type === 'lift')     $fill = '#6a5acd';
                                    if ($type === 'stairs')   $fill = '#1e90ff';
                                    if ($type === 'entrance') $fill = '#f5f5f5';
                                    if ($type === 'junction') $fill = '#999999';

                                    $isStart = ($n['id'] == $fromId);
                                    $isEnd   = ($n['id'] == $toId);
                                ?>
                                    <circle cx="<?php echo $n['x']; ?>" cy="<?php echo $n['y']; ?>" r="7"
                                            fill="<?php echo $fill; ?>"
                                            stroke="#222" stroke-width="1.5"/>
                                    <?php if ($isStart): ?>
                                        <circle cx="<?php echo $n['x']; ?>" cy="<?php echo $n['y']; ?>" r="12"
                                                fill="none" stroke="#1e90ff" stroke-width="2"/>
                                    <?php endif; ?>
                                    <?php if ($isEnd): ?>
                                        <circle cx="<?php echo $n['x']; ?>" cy="<?php echo $n['y']; ?>" r="12"
                                                fill="none" stroke="#4CAF50" stroke-width="2"/>
                                    <?php endif; ?>
                                    <text x="<?php echo $n['x']; ?>" y="<?php echo $n['y'] - 12; ?>"
                                          text-anchor="middle" font-size="9">
                                        <?php echo htmlspecialchars($n['name']); ?>
                                    </text>
                                <?php endforeach; ?>
                            </svg>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="flt-legend">
                    <span><span class="flt-dot" style="background:#4CAF50"></span> Room</span>
                    <span><span class="flt-dot" style="background:#1e90ff"></span> Stairs</span>
                    <span><span class="flt-dot" style="background:#6a5acd"></span> Lift</span>
                    <span><span class="flt-dot" style="background:#f5f5f5;border:1px solid #222;"></span> Entrance</span>
                    <span><span class="flt-dot" style="background:#999999"></span> Junction</span>
                    <span><span class="flt-line"></span> Your route</span>
                </div>

                <?php if ($sameLocation): ?>

                    <p class="no-notifications">Please choose two different locations.</p>

                <?php elseif ($route): ?>

                    <div class="flt-summary">
                        <div class="flt-summary-card">
                            <div class="flt-summary-title">Distance</div>
                            <div class="flt-summary-value"><?php echo htmlspecialchars((string) round($route['distance'])); ?> m</div>
                        </div>
                        <div class="flt-summary-card">
                            <div class="flt-summary-title">Walking time</div>
                            <div class="flt-summary-value"><?php echo htmlspecialchars((string) $route['walkingTime']); ?> min</div>
                        </div>
                    </div>

                    <div class="flt-steps">
                        <div class="flt-steps-title">Step-by-step</div>
                        <?php foreach ($route['steps'] as $step): ?>
                            <div class="flt-step">
                                <span class="flt-step-icon">
                                    <?php
                                        $icon = $step['icon'];
                                        if ($icon === 'start')       echo '🟦';
                                        elseif ($icon === 'stairs')  echo '🪜';
                                        elseif ($icon === 'lift')    echo '🛗';
                                        elseif ($icon === 'arrive')  echo '🎯';
                                        else                          echo '🚶';
                                    ?>
                                </span>
                                <span><?php echo htmlspecialchars($step['text']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                <?php elseif ($fromId && $toId): ?>

                    <p class="no-notifications">No route found between the selected points.</p>

                <?php endif; ?>

            <?php endif; ?>
        </div>
    </main>

    <footer>
        <div class="footer-bottom-bar">
            &copy; 2026 UniBee. All rights reserved.
        </div>
    </footer>
    <script>
    <?php if (isset($_GET['facility'])): ?>
    document.addEventListener('DOMContentLoaded', function () {
        var summary = document.querySelector('.flt-summary');
        if (summary) summary.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
    <?php endif; ?>
    </script>
</body>
</html>