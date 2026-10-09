<?php

session_start();

$success = $_SESSION['upload_success'] ?? '';
$error = $_SESSION['upload_error'] ?? '';

unset($_SESSION['upload_success']);
unset($_SESSION['upload_error']);

require_once "../controller/manageUniversityInformationController.php";

$controller = new ManageUniversityInformationController();

$universityId = $_SESSION['university_id'] ?? null;

if (!$universityId) {
    die("University ID not found.");
}

$buildings = $controller->getCampusBuildings($universityId);

$campusMap = $controller->getCampusMap($universityId);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Campus Floor Plan - UniBee</title>

    <link rel="stylesheet" href="../style.css">

    <style>

        /* ==============================
           General Page
        ============================== */

        .campus-floor-plan-page {
            width: 80%;
            max-width: 1050px;
            margin: 0 auto;
            min-height: calc(100vh - 150px);
            padding: 35px 0 70px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
        }

        .campus-floor-plan-page .profile-header {
            display: block;
            margin: 0 0 28px;
            padding: 0;
            text-align: left;
        }

        .campus-floor-plan-page .btn-back {
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        /* ==============================
           Header
        ============================== */

        .page-title-section {
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 15px;
            margin-bottom: 18px;
            border-bottom: 1px solid #ccc;
        }

        .page-title-section img {
            width: 24px;
            height: 24px;
            object-fit: contain;
        }

        .page-title-section h1 {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 700;
        }

        /* ==============================
           Section
        ============================== */

        .campus-section {
            margin-top: 30px;
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
        }

        .section-header h2 {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 700;
        }

        /* ==============================
           Buttons
        ============================== */

        .primary-button,
        .secondary-button,
        .danger-button {
            display: inline-block;
            padding: 10px 18px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }

        .primary-button {
            background: #f5c400;
            color: #000;
        }

        .secondary-button {
            background: #eeeeee;
            color: #222;
        }

        .add-level-button {
            background: #ffcc00;
            color: #000;
            border: none;
            border-radius: 15px;
            padding: 9px 18px;
            font-size: 0.7rem;
            font-weight: 700;
            cursor: pointer;
            margin-top: 15px;
        }

        .add-level-button:hover {
            background: #f2bd00;
        }

        .danger-button {
            background: #dc3545;
            color: white;
        }

        .primary-button,
        .secondary-button,
        .danger-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 9px 18px;
            border: none;
            border-radius: 15px;
            text-decoration: none;
            cursor: pointer;
            font-size: 0.7rem;
            font-weight: 700;
        }

        .delete-button {
            background: #e63946;
            color: white;
            border: none;
            padding: 10px 16px;
            border-radius: 20px;
            font-weight: 600;
            cursor: pointer;
        }

        .delete-button:hover {
            background: #c92f3b;
        }

        /* ==============================
           Campus Map
        ============================== */

        .campus-map-card {
            border: 1px solid #ddd;
            border-radius: 12px;
            padding: 20px;
            background: #fff;
        }

        .campus-map-card form {
            margin-top: 20px;
            text-align: center;
        }

        .campus-map-image {
            display: block;
            width: 100%;
            max-height: 500px;
            object-fit: contain;
            border-radius: 8px;
        }

        .empty-map-message {
            text-align: center;
            padding: 35px 20px;
            border: none;
        }

        .empty-map-message p {
            margin-bottom: 20px;
        }

        .map-actions {
            margin-top: 20px;
        }

        /* ==============================
           Buildings
        ============================== */

        .building-card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 10px;
            margin-bottom: 20px;
            overflow: hidden;
        }

        .building-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px;
            background: #f7f7f7;
            border-bottom: 1px solid #ddd;
        }

        .building-information h3 {
            margin: 0 0 5px 0;
            font-size: 0.82rem;
        }

        .building-information p {
            margin: 0;
            color: #666;
            font-size: 0.68rem;
        }

        .building-actions {
            display: flex;
            gap: 8px;
        }

        /* ==============================
           Floors
        ============================== */

        .floor-section {
            padding: 20px;
        }

        .floor-section-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .floor-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .floor-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 16px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            background: #fafafa;
        }

        .floor-information {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .floor-name {
            font-weight: 600;
        }

        .floor-status {
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 12px;
        }

        .floor-status.uploaded {
            background: #d4edda;
            color: #155724;
        }

        .floor-status.not-uploaded {
            background: #fff3cd;
            color: #856404;
        }

        .floor-actions {
            display: flex;
            gap: 8px;
        }

        .empty-levels {
            color: #777;
            font-size: 0.72rem;
            padding: 8px 0 12px 0;
        }

        .add-level-container {
            margin-top: 4px;
        }

        /* ==============================
           Empty Buildings
        ============================== */

        .empty-buildings {
            padding: 50px 20px;
            text-align: center;
            border: 2px dashed #ccc;
            border-radius: 8px;
            color: #666;
        }

        /* ==============================
           Popup
        ============================== */

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
            min-height: 175px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f8fa;
            border: 1px solid #555;
            box-sizing: border-box;
            padding: 30px;
        }

        .message-modal h2 {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 700;
            color: #000;
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

        /* Add Building Modal */

        .building-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.35);
            align-items: center;
            justify-content: center;
        }


        .building-modal-content {
            width: 90%;
            max-width: 450px;
            background: #fff;
            border-radius: 10px;
            padding: 25px;
            box-sizing: border-box;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }


        .building-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid #ddd;
        }


        .building-modal-header h2 {
            margin: 0;
            font-size: 1.05rem;
        }


        .building-modal-close {
            border: none;
            background: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }


        .building-form-group {
            margin-bottom: 15px;
        }


        .building-form-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 0.72rem;
            font-weight: 700;
        }


        .building-form-group input,
        .building-form-group textarea {
            width: 100%;
            box-sizing: border-box;
            padding: 9px 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-family: inherit;
            font-size: 0.72rem;
        }


        .building-form-group textarea {
            resize: vertical;
        }


        .building-modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }


        .building-modal-actions button {
            cursor: pointer;
        }

        .floor-details {
            margin-bottom: 20px;
        }

        .floor-detail-row {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .floor-detail-label {
            width: 150px;
            font-weight: 600;
        }

        .floor-detail-value {
            flex: 1;
        }

        .floor-plan-preview {
            width: 100%;
            max-height: 55vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: auto;
            margin-top: 20px;
        }

        .floor-plan-preview img {
            max-width: 100%;
            max-height: 55vh;
            object-fit: contain;
            display: block;
        }

        .floor-plan-modal-content {
            width: 90%;
            max-width: 900px;
            max-height: 90vh;
            overflow-y: auto;
        }

    </style>

</head>

<body>

    <!-- Header -->
    <header class="header">

        <div class="logo-container">

            <img
                src="../images/uniBeeLogo.png"
                alt="UniBee Logo"
            >

        </div>

        <div class="dashboard-header-right">

            <div class="profile-dropdown">

                <div
                    class="profile-container"
                    onclick="toggleDropdown()"
                >

                    <img
                        src="../images/profilePic.png"
                        alt="Profile"
                        class="profile-icon"
                    >

                    <img
                        src="../images/dropdown.png"
                        alt="Menu"
                        class="dropdown-arrow"
                    >

                </div>

                <div id="profileMenu" class="dropdown-menu">
                    <a href="renewLicensePage.php">Renew License</a>
                    <a href="aiChatbotPage.php">AI Chatbot</a>
                    <a href="submitFeedbackPage.php">Submit Feedback</a>
                    <a href="../controller/logoutController.php">Log Out</a>
                </div>

            </div>

        </div>

    </header>


    <!-- Main -->
    <main class="campus-floor-plan-page">

        <!-- Back -->
        <div class="profile-header">

            <a
                href="ManageUniversityInformationPage.php"
                class="btn-back"
            >
                &#8592; Back
            </a>

        </div>


        <!-- Page Title -->
        <section class="page-title-section">

            <img
                src="../images/floorPlan.png"
                alt="Campus Floor Plan"
            >

            <h1>
                Campus Floor Plans
            </h1>

        </section>


        <!-- Success Message -->
        <?php if ($success): ?>

            <div class="message-overlay" id="successPopup">

                <div class="message-modal">

                    <button
                        type="button"
                        class="message-close"
                        onclick="document.getElementById('successPopup').style.display='none';"
                    >
                        &times;
                    </button>

                    <h2>
                        <?php echo htmlspecialchars($success); ?>
                    </h2>

                </div>

            </div>

        <?php endif; ?>


        <!-- Error Message -->
        <?php if ($error): ?>

            <div class="upload-message upload-error">

                <?php echo htmlspecialchars($error); ?>

            </div>

        <?php endif; ?>


        <!-- ==========================================
             OVERALL CAMPUS MAP
        =========================================== -->

        <section class="campus-section">

            <div class="section-header">
                <h2>Overall Campus Map</h2>
            </div>

            <div class="campus-map-card">

                <div class="empty-map-message">

                    <?php if (!empty($campusMap)): ?>

                        <img
                            src="../<?php echo htmlspecialchars($campusMap['filePath']); ?>"
                            alt="Overall Campus Map"
                            class="campus-map-image"
                        >

                    <?php else: ?>

                        <div class="empty-map-message">
                            <p>No overall campus map has been uploaded yet.</p>
                            ...
                        </div>

                    <?php endif; ?>

                    <form
                        action="../controller/manageUniversityInformationController.php"
                        method="POST"
                        enctype="multipart/form-data"
                        id="campusMapForm"
                    >
                        <input
                            type="hidden"
                            name="uploadType"
                            value="campusMap"
                        >

                        <input
                            type="file"
                            name="uploadFile"
                            id="campusMapFile"
                            accept=".png,.jpg,.jpeg"
                            style="display: none;"
                            onchange="document.getElementById('campusMapForm').submit();"
                        >

                        <label
                            for="campusMapFile"
                            class="primary-button"
                        >
                            <?php if (!empty($campusMap)): ?>
                                Update Campus Map
                            <?php else: ?>
                                Upload Campus Map
                            <?php endif; ?>
                        </label>

                    </form>

                </div>

            </div>

        </section>


        <!-- ==========================================
             CAMPUS BUILDINGS
        =========================================== -->

        <section class="campus-section">

            <div class="section-header">

                <h2>
                    Campus Buildings
                </h2>

                <button
                    type="button"
                    class="primary-button"
                    onclick="openBuildingModal()"
                >
                    + Add Building
                </button>

            </div>


            <?php if (!empty($buildings)): ?>

                <?php foreach ($buildings as $building): ?>

                    <div class="building-card">

                        <!-- Building Header -->
                        <div class="building-header">

                            <div class="building-information">

                                <h3>
                                    <?php

                                    echo htmlspecialchars(
                                        $building['buildingName']
                                    );
                                    ?>
                                </h3>

                                <?php if (!empty($building['buildingCode'])): ?>

                                    <p>
                                        Building Code:
                                        <?php

                                        echo htmlspecialchars(
                                            $building['buildingCode']
                                        );
                                        ?>
                                    </p>

                                <?php endif; ?>

                            </div>


                            <div class="building-actions">

                                <a
                                    href="editCampusBuildingPage.php?id=<?php echo (int) $building['id']; ?>"
                                    class="secondary-button"
                                >
                                    Edit
                                </a>

                                <a
                                    href="../controller/manageUniversityInformationController.php?action=deleteBuilding&id=<?php echo (int) $building['id']; ?>"
                                    class="danger-button"
                                    onclick="return confirm('Are you sure you want to delete this building?');"
                                >
                                    Delete
                                </a>

                            </div>

                        </div>

                        <!-- Floors -->
                        <div class="floor-section">

                            <div class="floor-section-title">
                                Levels
                            </div>

                            <div class="floor-list">

                                <?php if (!empty($building['floors'])): ?>

                                    <?php foreach ($building['floors'] as $floor): ?>

                                        <div class="floor-row">

                                            <div class="floor-information">

                                                <span class="floor-name">
                                                    <?php

                                                    echo htmlspecialchars(
                                                        $floor['floorName']
                                                    );
                                                    ?>
                                                </span>

                                                <?php if (!empty($floor['floorPlan'])): ?>

                                                    <span class="floor-status uploaded">
                                                        Floor Plan Uploaded
                                                    </span>

                                                <?php else: ?>

                                                    <span class="floor-status not-uploaded">
                                                        No Floor Plan
                                                    </span>

                                                <?php endif; ?>

                                            </div>

                                            <div class="floor-actions">

                                                <!-- Edit Level -->
                                                <button
                                                    type="button"
                                                    class="secondary-button"
                                                    onclick="openEditFloorModal(
                                                        <?php echo (int) $floor['id']; ?>,
                                                        <?php

                                                        echo htmlspecialchars(
                                                            json_encode($floor['floorName'])
                                                        ); ?>,
                                                        <?php echo (int) $floor['floorNumber']; ?>,
                                                        <?php

                                                        echo htmlspecialchars(
                                                            json_encode($floor['description'] ?? '')
                                                        ); ?>
                                                    )"
                                                >
                                                    Edit
                                                </button>

                                                <!-- Delete Level -->
                                                <form
                                                    action="../controller/manageUniversityInformationController.php"
                                                    method="POST"
                                                    style="display: inline;"
                                                    onsubmit="return confirm('Are you sure you want to delete this level?');"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="action"
                                                        value="deleteCampusFloor"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="floorId"
                                                        value="<?php echo (int) $floor['id']; ?>"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="secondary-button delete-floor-button"
                                                    >
                                                        Delete
                                                    </button>

                                                </form>

                                                <!-- Floor Plan -->
                                                <?php if (!empty($floor['floorPlan'])): ?>

                                                    <button
                                                        type="button"
                                                        class="secondary-button"
                                                        onclick="openFloorPlanModal(
                                                            <?php echo (int) $floor['id']; ?>,
                                                            <?php echo htmlspecialchars(json_encode($floor['floorName'])); ?>,
                                                            <?php echo (int) $floor['floorNumber']; ?>,
                                                            <?php echo htmlspecialchars(json_encode($floor['description'] ?? '')); ?>,
                                                            <?php echo htmlspecialchars(json_encode($floor['floorPlan']['filePath'])); ?>
                                                        )"
                                                    >
                                                        View
                                                    </button>

                                                    <form
                                                        id="replaceFloorPlanForm<?php echo (int) $floor['id']; ?>"
                                                        action="../controller/manageUniversityInformationController.php"
                                                        method="POST"
                                                        enctype="multipart/form-data"
                                                        style="display: inline;"
                                                    >

                                                        <input
                                                            type="hidden"
                                                            name="uploadType"
                                                            value="floorPlan"
                                                        >

                                                        <input
                                                            type="hidden"
                                                            name="floorId"
                                                            value="<?php echo (int) $floor['id']; ?>"
                                                        >

                                                        <input
                                                            type="file"
                                                            id="replaceFloorPlan<?php echo (int) $floor['id']; ?>"
                                                            name="uploadFile"
                                                            accept=".png,.jpg,.jpeg,.pdf"
                                                            style="display: none;"
                                                            onchange="document.getElementById('replaceFloorPlanForm<?php echo (int) $floor['id']; ?>').submit();"
                                                        >

                                                        <label
                                                            for="replaceFloorPlan<?php echo (int) $floor['id']; ?>"
                                                            class="secondary-button"
                                                        >
                                                            Replace
                                                        </label>

                                                    </form>


                                                <?php else: ?>

                                                    <form
                                                        id="uploadFloorPlanForm<?php echo (int) $floor['id']; ?>"
                                                        action="../controller/manageUniversityInformationController.php"
                                                        method="POST"
                                                        enctype="multipart/form-data"
                                                        style="display: inline;"
                                                    >

                                                        <input
                                                            type="hidden"
                                                            name="uploadType"
                                                            value="floorPlan"
                                                        >

                                                        <input
                                                            type="hidden"
                                                            name="floorId"
                                                            value="<?php echo (int) $floor['id']; ?>"
                                                        >

                                                        <input
                                                            type="file"
                                                            id="uploadFloorPlan<?php echo (int) $floor['id']; ?>"
                                                            name="uploadFile"
                                                            accept=".png,.jpg,.jpeg,.pdf"
                                                            style="display: none;"
                                                            onchange="document.getElementById('uploadFloorPlanForm<?php echo (int) $floor['id']; ?>').submit();"
                                                        >

                                                        <label
                                                            for="uploadFloorPlan<?php echo (int) $floor['id']; ?>"
                                                            class="primary-button"
                                                        >
                                                            Upload Floor Plan
                                                        </label>

                                                    </form>

                                                <?php endif; ?>

                                            </div>

                                        </div>

                                    <?php endforeach; ?>

                                <?php else: ?>

                                    <div class="empty-levels">
                                        <span>No levels added yet.</span>
                                    </div>

                                <?php endif; ?>

                            </div>

                            <!-- Add Level -->
                            <button
                                type="button"
                                class="primary-button add-level-button"
                                onclick="openFloorModal(
                                    <?php echo (int) $building['id']; ?>
                                )"
                            >
                                + Add Level
                            </button>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="empty-levels">
                    No levels added yet.
                </div>


            <?php endif; ?>

        </section>

    </main>


    <!-- Footer -->
    <footer>

        <div class="footer-bottom-bar">

            &copy; 2026 UniBee. All rights reserved.

        </div>

    </footer>

    <!-- Add Building Modal -->
    <div id="buildingModal" class="building-modal">

        <div class="building-modal-content">

            <div class="building-modal-header">

                <h2>Add Campus Building</h2>

                <button
                    type="button"
                    class="building-modal-close"
                    onclick="closeBuildingModal()"
                >
                    &times;
                </button>

            </div>


            <form
                action="../controller/manageUniversityInformationController.php"
                method="POST"
            >

                <input
                    type="hidden"
                    name="action"
                    value="createCampusBuilding"
                >

                <div class="building-form-group">

                    <label for="buildingName">
                        Building Name
                    </label>

                    <input
                        type="text"
                        id="buildingName"
                        name="buildingName"
                        placeholder="Enter building name"
                        required
                    >

                </div>


                <div class="building-form-group">

                    <label for="buildingCode">
                        Building Code
                    </label>

                    <input
                        type="text"
                        id="buildingCode"
                        name="buildingCode"
                        placeholder="Enter building code"
                        required
                    >

                </div>


                <div class="building-form-group">

                    <label for="buildingDescription">
                        Description
                    </label>

                    <textarea
                        id="buildingDescription"
                        name="description"
                        placeholder="Enter building description"
                        rows="3"
                    ></textarea>

                </div>


                <div class="building-modal-actions">

                    <button
                        type="button"
                        class="secondary-button"
                        onclick="closeBuildingModal()"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="primary-button"
                    >
                        Add Building
                    </button>

                </div>

            </form>

        </div>

    </div>

    <!-- Add Level Modal -->
    <div id="floorModal" class="building-modal">

        <div class="building-modal-content">

            <div class="building-modal-header">

                <h2>Add Level</h2>

                <button
                    type="button"
                    class="building-modal-close"
                    onclick="closeFloorModal()"
                >
                    &times;
                </button>

            </div>


            <form
                action="../controller/manageUniversityInformationController.php"
                method="POST"
            >

                <input
                    type="hidden"
                    name="action"
                    value="createCampusFloor"
                >

                <input
                    type="hidden"
                    name="buildingId"
                    id="floorBuildingId"
                >


                <div class="building-form-group">

                    <label for="floorName">
                        Level Name
                    </label>

                    <input
                        type="text"
                        id="floorName"
                        name="floorName"
                        placeholder="e.g. Level 1"
                        required
                    >

                </div>


                <div class="building-form-group">

                    <label for="floorNumber">
                        Level Number
                    </label>

                    <input
                        type="number"
                        id="floorNumber"
                        name="floorNumber"
                        placeholder="e.g. 1"
                        required
                    >

                </div>


                <div class="building-form-group">

                    <label for="floorDescription">
                        Description
                    </label>

                    <textarea
                        id="floorDescription"
                        name="description"
                        placeholder="Enter level description"
                        rows="3"
                    ></textarea>

                </div>

                <div class="building-modal-actions">

                    <button
                        type="button"
                        class="secondary-button"
                        onclick="closeFloorModal()"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="primary-button"
                    >
                        Add Level
                    </button>

                </div>

            </form>

        </div>

    </div>

    <!-- Edit Level Modal -->
    <div id="editFloorModal" class="building-modal">

        <div class="building-modal-content">

            <div class="building-modal-header">

                <h2>Edit Level</h2>

                <button
                    type="button"
                    class="building-modal-close"
                    onclick="closeEditFloorModal()"
                >
                    &times;
                </button>

            </div>


            <form
                action="../controller/manageUniversityInformationController.php"
                method="POST"
            >

                <input
                    type="hidden"
                    name="action"
                    value="updateCampusFloor"
                >

                <input
                    type="hidden"
                    name="floorId"
                    id="editFloorId"
                >


                <div class="building-form-group">

                    <label for="editFloorName">
                        Level Name
                    </label>

                    <input
                        type="text"
                        id="editFloorName"
                        name="floorName"
                        required
                    >

                </div>


                <div class="building-form-group">

                    <label for="editFloorNumber">
                        Level Number
                    </label>

                    <input
                        type="number"
                        id="editFloorNumber"
                        name="floorNumber"
                        required
                    >

                </div>


                <div class="building-form-group">

                    <label for="editFloorDescription">
                        Description
                    </label>

                    <textarea
                        id="editFloorDescription"
                        name="description"
                        rows="3"
                    ></textarea>

                </div>


                <div class="building-modal-actions">

                    <button
                        type="button"
                        class="secondary-button"
                        onclick="closeEditFloorModal()"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="primary-button"
                    >
                        Save Changes
                    </button>

                </div>

            </form>

        </div>

    </div>

    <!-- View Floor Plan Modal -->
    <div id="floorPlanModal" class="building-modal">

        <div class="building-modal-content floor-plan-modal-content">

            <div class="building-modal-header">

                <h2 id="viewFloorName">
                    Floor Plan
                </h2>

                <button
                    type="button"
                    class="building-modal-close"
                    onclick="closeFloorPlanModal()"
                >
                    &times;
                </button>

            </div>


            <div class="floor-details">

                <div class="floor-detail-row">

                    <span class="floor-detail-label">
                        Level Name
                    </span>

                    <span
                        class="floor-detail-value"
                        id="viewFloorNameDetail"
                    >
                    </span>

                </div>


                <div class="floor-detail-row">

                    <span class="floor-detail-label">
                        Level Number
                    </span>

                    <span
                        class="floor-detail-value"
                        id="viewFloorNumber"
                    >
                    </span>

                </div>


                <div class="floor-detail-row">

                    <span class="floor-detail-label">
                        Description
                    </span>

                    <span
                        class="floor-detail-value"
                        id="viewFloorDescription"
                    >
                    </span>

                </div>

            </div>


            <div class="floor-plan-preview">

                <img
                    id="floorPlanPreview"
                    src=""
                    alt="Floor Plan"
                >

            </div>

        </div>

    </div>

    <script src="../script.js"></script>

    <script>

        function openBuildingModal() {

            document.getElementById("buildingModal").style.display = "flex";

        }


        function closeBuildingModal() {

            document.getElementById("buildingModal").style.display = "none";

        }


        window.onclick = function(event) {

            const modal =
                document.getElementById("buildingModal");

            if (event.target === modal) {

                closeBuildingModal();

            }

        };

        function openFloorModal(buildingId) {

            document.getElementById("floorBuildingId").value = buildingId;

            document.getElementById("floorModal").style.display = "flex";

        }


        function closeFloorModal() {

            document.getElementById("floorModal").style.display = "none";

        }


        window.addEventListener("click", function (event) {

            const modal =
                document.getElementById("floorModal");

            if (event.target === modal) {

                closeFloorModal();

            }

        });

        function openEditFloorModal(
            floorId,
            floorName,
            floorNumber,
            description
        ) {

            document.getElementById("editFloorId").value =
                floorId;

            document.getElementById("editFloorName").value =
                floorName;

            document.getElementById("editFloorNumber").value =
                floorNumber;

            document.getElementById("editFloorDescription").value =
                description;

            document.getElementById("editFloorModal").style.display =
                "flex";
        }


        function closeEditFloorModal() {

            document.getElementById("editFloorModal").style.display =
                "none";
        }

        function openFloorPlanModal(
            floorId,
            floorName,
            floorNumber,
            description,
            filePath
        ) {

            document.getElementById("viewFloorName").textContent =
                floorName;

            document.getElementById("viewFloorNameDetail").textContent =
                floorName;

            document.getElementById("viewFloorNumber").textContent =
                floorNumber;

            document.getElementById("viewFloorDescription").textContent =
                description || "No description provided.";

            document.getElementById("floorPlanPreview").src =
                "../" + filePath;

            document.getElementById("floorPlanModal").style.display =
                "flex";
        }


        function closeFloorPlanModal() {

            document.getElementById("floorPlanModal").style.display =
                "none";

            document.getElementById("floorPlanPreview").src = "";
        }

    </script>

</body>

</html>