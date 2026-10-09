<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (
    !isset($_SESSION['logged_in']) ||
    $_SESSION['logged_in'] !== true
) {
    header("Location: loginPage.php");
    exit();
}

$userRole =
    strtolower(
        $_SESSION['user_role']
        ?? $_SESSION['role']
        ?? ''
    );

if (
    $userRole !== 'university_admin' &&
    $userRole !== 'university admin'
) {
    header("Location: loginPage.php");
    exit();
}

/* CONTROLLER */

require_once "../controller/manageUniversityEventController.php";

$controller = new ManageUniversityEventController();

$universityId = (int) ($_SESSION['university_id'] ?? 0);

$createdBy = (int) ($_SESSION['user_id'] ?? 0);

/* TAB */

$activeTab = $_GET['tab'] ?? 'create';

if (
    $activeTab !== 'view'
) {
    $activeTab = 'create';
}

/* FLASH MESSAGES */
$success = $_SESSION['event_success'] ?? null;
$error = $_SESSION['event_error'] ?? null;

unset(
    $_SESSION['event_success'],
    $_SESSION['event_error']
);

/* CREATE FORM VALUES  */
$title = trim($_GET['title'] ?? '');
$description = trim($_GET['description'] ?? '');
$capacity = trim($_GET['capacity'] ?? '');
$startDate = trim($_GET['startDate'] ?? '');
$startTime = trim($_GET['startTime'] ?? '');
$endDate = trim($_GET['endDate'] ?? '');
$endTime = trim($_GET['endTime'] ?? '');

/* RECOMMENDED FACILITIES */
$recommendedFacilities = [];
$searched = isset($_GET['findFacilities']);

if (
    $searched &&
    $universityId > 0 &&
    $capacity !== '' &&
    $startDate !== '' &&
    $startTime !== '' &&
    $endDate !== '' &&
    $endTime !== ''
) {

    $startDatetime =
        $startDate
        . ' '
        . $startTime
        . ':00';

    $endDatetime =
        $endDate
        . ' '
        . $endTime
        . ':00';

    $recommendedFacilities =
        $controller->getRecommendedFacilities(
            $universityId,
            (int) $capacity,
            $startDatetime,
            $endDatetime
        );
}

/* VIEW EVENTS */

$searchQuery = trim($_GET['search'] ?? '');

$eventList = [];

if (
    $activeTab === 'view' &&
    $universityId > 0
) {

    $eventList =
        $controller->getUniversityEvents(
            $universityId,
            $searchQuery
        );
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Manage University Events - UniBee
    </title>

    <link
        rel="stylesheet"
        href="../style.css"
    >

    <style>

        /* PAGE */
        .university-event-page {
            width: 80%;
            max-width: 1050px;
            min-height: calc(100vh - 150px);
            margin: 0 auto;
            padding: 35px 0 70px;
            box-sizing: border-box;
        }

        .event-container {
            width: 100%;
            max-width: none;
            margin: 0 auto;
        }

        .profile-header {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            max-width: none;
            min-height: 45px;
            margin: 15px 0 35px 0;
            box-sizing: border-box;
        }

        .profile-header .btn-back {
            position: absolute;
            left: 0;
        }

        .profile-header .section-label {
            position: static;
            transform: none;
            margin: 0 !important;
            text-align: center !important;
            white-space: nowrap;
            font-size: 1.35rem;
            font-weight: 700;
        }

        /* TABS */
        .event-tabs {
            display: flex;
            gap: 40px;
            width: 100%;
            border-bottom: 1px solid #ddd;
            margin-bottom: 26px;
        }

        .event-tab {
            padding: 0 0 8px;
            color: #222;
            text-decoration: none;
            font-size: 1rem;
            font-weight: 400;
            position: relative;
        }

        .event-tab.active {
            font-weight: 700;
        }

        .event-tab.active::after {
            content: "";
            position: absolute;
            left: 0;
            bottom: 0;
            width: 100%;
            height: 5px;
            background-color: var(--bg-yellow);
        }

        .event-tab:hover {
            color: #000;
            text-decoration: none;
        }

        /* MESSAGES */
        .event-message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .event-success {
            background: #dcfce7;
            border: 1px solid #86efac;
            color: #166534;
        }

        .event-error {
            background: #fee2e2;
            border: 1px solid #fca5a5;
            color: #991b1b;
        }

        /* CARDS */
        .event-card-section {
            width: 100%;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 10px;
            padding: 22px 28px;
            background: #fff;
            margin-bottom: 24px;
        }

        .event-form {
            width: 100%;
            max-width: none;
            margin: 0;
        }

        .event-form-group {
            width: 100%;
            margin-bottom: 22px;
        }

        .event-form-group input[type="text"],
        .event-form-group input[type="number"],
        .event-form-group input[type="date"],
        .event-form-group input[type="time"],
        .event-form-group textarea,
        .event-form-group select {
            width: 100%;
            box-sizing: border-box;
            padding: 11px 14px;
            border: 1px solid #bbb;
            border-radius: 6px;
            background: #fff;
            font-size: 0.88rem;
            font-family: inherit;
        }

        .event-form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .event-datetime-row {
            display: flex;
            gap: 12px;
        }

        .event-datetime-row input {
            flex: 1;
        }

        /* BUTTONS */
        .event-button-row {
            display: flex;
            gap: 10px;
            margin-top: 28px;
        }

        .event-btn {
            padding: 10px 22px;
            border: 1px solid #000;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
            cursor: pointer;
        }

        .event-btn-primary {
            background: var(--bg-yellow);
            color: #222;
        }

        .event-btn-primary:hover {
            background: #e6c23a;
        }

        /* FACILITY RECOMMENDATIONS */
        .find-facilities-section {
            border-top: 1px solid #ddd;
            margin-top: 25px;
            padding-top: 20px;
        }

        .event-divider {
            width: 100%;
            border-top: 1px solid #ddd;
            margin: 25px 0;
        }

        .recommended-list {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .facility-option {
            width: 100%;
            box-sizing: border-box;
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px 18px;
            border: 1px solid #ccc;
            border-radius: 10px;
            background: #fff;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .facility-option:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.07);
        }

        .facility-option-info {
            flex: 1;
        }

        .facility-option-name {
            font-size: 0.95rem;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .facility-option-details {
            font-size: 0.78rem;
            color: #666;
        }

        .facility-capacity {
            font-size: 0.75rem;
            color: #555;
            white-space: nowrap;
        }

        /* FILE INPUT */
        .event-file-input {
            padding: 8px !important;
            background: #fafafa !important;
        }

        .event-file-help {
            margin-top: 5px;
            font-size: 0.72rem;
            color: #777;
        }

        /* VIEW TAB */
        .event-search {
            display: flex;
            gap: 10px;
            margin-bottom: 22px;
        }

        .event-search input {
            flex: 1;
            padding: 11px 18px;
            border: 1px solid #ccc;
            border-radius: 25px;
            font-size: 0.9rem;
            background: #fafafa;
            box-sizing: border-box;
        }

        .event-search button {
            padding: 10px 26px;
            background: var(--bg-yellow);
            border: none;
            border-radius: 25px;
            font-weight: 700;
            cursor: pointer;
        }

        .event-search-clear {
            padding: 10px 20px;
            background: #e0e0e0;
            color: #333;
            border: none;
            border-radius: 25px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }

        .event-list {
            width: 100%;
            max-width: none;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .event-view-card {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            border: 1px solid #ccc;
            border-radius: 10px;
            padding: 18px 22px;
            background: #fff;
            box-sizing: border-box;
        }

        .event-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        .event-view-info {
            flex: 1;
        }

        .event-view-info h3 {
            font-size: 1rem;
            margin: 0 0 5px 0;
        }

        .event-view-info p {
            font-size: 0.82rem;
            color: #666;
            margin: 0 0 8px 0;
        }

        .event-meta {
            font-size: 0.75rem;
            color: #555;
            line-height: 1.6;
        }

        .event-status {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 5px 10px;
            border-radius: 12px;
            white-space: nowrap;
        }

        .status-active {
            background: #dcfce7;
            color: #166534;
        }

        .status-suspended {
            background: #fef3c7;
            color: #92400e;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-completed {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .event-actions {
            display: flex;
            gap: 8px;
            flex-shrink: 0;
        }

        .event-actions button {
            padding: 7px 16px;
            border: none;
            border-radius: 6px;
            font-size: 0.78rem;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-event-edit {
            background: #4ade80;
            color: #064e3b;
        }

        .btn-event-edit:hover {
            background: #22c55e;
            color: #fff;
        }

        .btn-event-suspend {
            background: #f59e0b;
            color: #fff;
        }

        .btn-event-suspend:hover {
            background: #d97706;
        }

        .empty-event {
            padding: 60px 20px;
            text-align: center;
            color: #888;
            font-size: 0.95rem;
            border: 1px dashed #ccc;
            border-radius: 10px;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .university-event-page {
                width: 90%;
            }

            .event-datetime-row {
                flex-direction: column;
            }

            .event-view-card {
                flex-direction: column;
            }
             
            .event-tabs {
                gap: 20px;
                overflow-x: auto;
            }

        }

        /* POSTER BUTTON */

        .btn-event-poster {
            background: #e5e7eb;
            color: #222;
        }

        .btn-event-poster:hover {
            background: #d1d5db;
        }

        /* POSTER MODAL */

        .poster-event-modal {
            position: relative;
            width: 600px;
            max-width: 90vw;
            max-height: 90vh;
            padding: 25px;
            background: #f8f8fa;
            border: 1px solid #555;
            box-sizing: border-box;
            text-align: center;
        }

        .poster-event-modal h2 {
            margin: 0 0 18px;
            font-size: 1rem;
            font-weight: 700;
        }

        .poster-event-modal img {
            display: block;
            max-width: 100%;
            max-height: 70vh;
            margin: 0 auto;
            object-fit: contain;
        }

        /* EVENT MODAL */
        .event-modal-overlay {
            position: fixed;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.25);
            z-index: 99999;
        }

        /* EDIT MODAL */
        .edit-event-modal {
            position: relative;
            width: 550px;
            max-height: 80vh;
            overflow-y: auto;
            padding: 30px;
            background: #f8f8fa;
            border: 1px solid #555;
            box-sizing: border-box;
        }

        .edit-event-modal h2 {
            margin: 0 0 20px;
            font-size: 1rem;
            font-weight: 700;
        }

        .event-modal-close {
            position: absolute;
            top: 8px;
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

        .event-modal-close:hover {
            opacity: 0.6;
        }

        /* EDIT FORM */
        .edit-event-form {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .edit-event-form label {
            margin-top: 7px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .edit-event-form input,
        .edit-event-form textarea {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #999;
            border-radius: 10px;
            background: #fff;
            font-size: 0.75rem;
            box-sizing: border-box;
            outline: none;
        }

        .edit-event-form textarea {
            min-height: 70px;
            resize: vertical;
        }

        .edit-event-form input:focus,
        .edit-event-form textarea:focus {
            border-color: var(--bg-yellow);
        }

        .edit-event-submit {
            width: 120px;
            margin-top: 18px;
            padding: 9px 15px;
            border: 1px solid #000;
            border-radius: 4px;
            background: var(--bg-yellow);
            color: #000;
            font-size: 0.7rem;
            font-weight: 700;
            cursor: pointer;
        }

        .edit-event-submit:hover {
            background: #e6c23a;
        }

        /* SUSPEND CONFIRMATION */
        .suspend-event-modal {
            position: relative;
            width: 420px;
            padding: 35px 30px;
            background: #f8f8fa;
            border: 1px solid #555;
            box-sizing: border-box;
            text-align: center;
        }

        .suspend-event-modal h2 {
            margin: 0 0 25px;
            font-size: 0.95rem;
            font-weight: 700;
        }

        .suspend-event-buttons {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .suspend-cancel-button,
        .suspend-confirm-button {
            padding: 8px 18px;
            border: none;
            border-radius: 8px;
            color: #fff;
            font-size: 0.7rem;
            font-weight: 700;
            cursor: pointer;
        }

        .suspend-cancel-button {
            background: #6b3200;
        }

        .suspend-cancel-button:hover {
            background: #522500;
        }

        .suspend-confirm-button {
            background: #d00000;
        }

        .suspend-confirm-button:hover {
            background: #ae0000;
        }

        /* SUCCESS POPUP */

        .event-success-modal {
            position: relative;
            width: 425px;
            min-height: 175px;
            padding: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f8fa;
            border: 1px solid #555;
            box-sizing: border-box;
            text-align: center;
        }

        .event-success-modal h2 {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 700;
            color: #000;
        }

    </style>

</head>

<body>

<script src="../script.js"></script>

<header class="header">

    <div class="logo-container">

        <a href="universityAdminDashboardPage.php">

            <img
                src="../images/uniBeeLogo.png"
                alt="UniBee Logo"
            >

        </a>

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

<main class="university-event-page">

    <div class="event-container">

        <div class="profile-header">

            <a
                href="universityAdminDashboardPage.php"
                class="btn-back"
            >
                &#8592; Back
            </a>

            <h1 class="section-label">
                Manage University Events
            </h1>

        </div>

    <!-- FLASH MESSAGES -->
    <?php if ($success): ?>

        <div
            class="event-modal-overlay"
            id="eventSuccessPopup"
        >

            <div class="event-success-modal">

                <button
                    type="button"
                    class="event-modal-close"
                    onclick="
                        document
                            .getElementById('eventSuccessPopup')
                            .remove();
                    "
                >
                    &times;
                </button>

                <h2>
                    <?php

                    echo htmlspecialchars($success);
                    ?>
                </h2>

            </div>

        </div>

    <?php endif; ?>


    <?php if ($error): ?>

        <div class="event-message event-error">

            <?php

            echo htmlspecialchars($error);
            ?>

        </div>

    <?php endif; ?>

    <!-- TABS -->
    <div class="event-tabs">

        <a
            class="event-tab
                <?php

                echo $activeTab === 'create'
                    ? 'active'
                    : '';
                ?>"
            href="manageUniversityEventPage.php?tab=create"
        >
            Create University Event
        </a>

        <a
            class="event-tab
                <?php

                echo $activeTab === 'view'
                    ? 'active'
                    : '';
                ?>"
            href="manageUniversityEventPage.php?tab=view"
        >
            View University Events
        </a>

    </div>

    <!-- CREATE TAB -->
    <?php if ($activeTab === 'create'): ?>

        <!-- EVENT DETAILS -->
        <form
            method="GET"
            action="manageUniversityEventPage.php"
        >

            <input
                type="hidden"
                name="tab"
                value="create"
            >

            <div class="event-card-section">

                <h2>
                    Event Details
                </h2>

                <p>
                    Enter the event details to find suitable
                    bookable facilities.
                </p>

                <!-- Event Title -->
                <div class="event-form-group">

                    <label for="title">
                        Event Title
                    </label>

                    <input
                        type="text"
                        id="title"
                        name="title"
                        value="<?php echo htmlspecialchars($title); ?>"
                        required
                    >

                </div>

                <!-- Description -->
                <div class="event-form-group">

                    <label for="description">
                        Description
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        rows="4"
                        required
                    ><?php echo htmlspecialchars($description); ?></textarea>

                </div>

                <!-- Capacity -->
                <div class="event-form-group">

                    <label for="capacity">
                        Event Capacity
                    </label>

                    <input
                        type="number"
                        id="capacity"
                        name="capacity"
                        min="1"
                        value="<?php echo htmlspecialchars($capacity); ?>"
                        required
                    >

                </div>

                <!-- Start -->
                <div class="event-form-group">

                    <label>
                        Start Date & Time
                    </label>

                    <div class="event-datetime-row">

                        <input
                            type="date"
                            name="startDate"
                            value="<?php echo htmlspecialchars($startDate); ?>"
                            required
                        >

                        <input
                            type="time"
                            name="startTime"
                            value="<?php echo htmlspecialchars($startTime); ?>"
                            required
                        >

                    </div>

                </div>

                <!-- End -->
                <div class="event-form-group">

                    <label>
                        End Date & Time
                    </label>

                    <div class="event-datetime-row">

                        <input
                            type="date"
                            name="endDate"
                            value="<?php echo htmlspecialchars($endDate); ?>"
                            required
                        >

                        <input
                            type="time"
                            name="endTime"
                            value="<?php echo htmlspecialchars($endTime); ?>"
                            required
                        >

                    </div>

                </div>

                <!-- FIND FACILITIES -->
                <div class="find-facilities-section">

                    <button
                        type="submit"
                        name="findFacilities"
                        value="1"
                        class="event-btn event-btn-primary"
                    >
                        Find Available Facilities
                    </button>

                </div>

            </div>

        </form>

        <!-- RECOMMENDED FACILITIES -->

        <?php if ($searched): ?>

            <div
                class="event-card-section"
                id="recommendedFacilities"
            >

                <h2>
                    Recommended Facilities
                </h2>

                <p>
                    Facilities shown below are bookable,
                    have sufficient capacity, and are
                    available during the selected period,
                    including the 30-minute buffer before
                    and after the event.
                </p>

                <?php if (empty($recommendedFacilities)): ?>

                    <div class="empty-event">

                        No suitable facilities are available
                        for the selected capacity and time.

                    </div>

                <?php else: ?>

                    <!-- CREATE EVENT FORM -->

                    <form
                        method="POST"
                        action="../controller/manageUniversityEventController.php"
                        enctype="multipart/form-data"
                        class="event-form"
                    >

                        <input
                            type="hidden"
                            name="action"
                            value="createUniversityEvent"
                        >

                        <input
                            type="hidden"
                            name="title"
                            value="<?php echo htmlspecialchars($title); ?>"
                        >

                        <input
                            type="hidden"
                            name="description"
                            value="<?php echo htmlspecialchars($description); ?>"
                        >

                        <input
                            type="hidden"
                            name="capacity"
                            value="<?php echo htmlspecialchars($capacity); ?>"
                        >

                        <input
                            type="hidden"
                            name="startDate"
                            value="<?php echo htmlspecialchars($startDate); ?>"
                        >

                        <input
                            type="hidden"
                            name="startTime"
                            value="<?php echo htmlspecialchars($startTime); ?>"
                        >

                        <input
                            type="hidden"
                            name="endDate"
                            value="<?php echo htmlspecialchars($endDate); ?>"
                        >

                        <input
                            type="hidden"
                            name="endTime"
                            value="<?php echo htmlspecialchars($endTime); ?>"
                        >

                        <!-- FACILITY LIST -->
                        <div class="recommended-list">

                            <?php foreach (
                                $recommendedFacilities
                                as $facility
                            ): ?>

                                <label
                                    class="facility-option"
                                >

                                    <input
                                        type="radio"
                                        name="facilityId"
                                        value="<?php
                                            echo (int)
                                                $facility[
                                                    'bookableFacilityId'
                                                ];
                                        ?>"
                                        required
                                    >


                                    <div
                                        class="facility-option-info"
                                    >

                                        <div
                                            class="facility-option-name"
                                        >

                                            <?php

                                            echo htmlspecialchars(
                                                $facility['name']
                                            );

                                            ?>

                                        </div>


                                        <div
                                            class="facility-option-details"
                                        >

                                            <?php

                                            echo htmlspecialchars(
                                                $facility['type']
                                            );

                                            ?>

                                            &nbsp; • &nbsp;

                                            <?php

                                            echo htmlspecialchars(
                                                $facility['location']
                                            );

                                            ?>,

                                            <?php

                                            echo htmlspecialchars(
                                                $facility['blockFloor']
                                            );

                                            ?>

                                            <?php

                                            if (
                                                !empty(
                                                    $facility[
                                                        'roomCode'
                                                    ]
                                                )
                                            ):

                                            ?>

                                                &nbsp; • &nbsp;

                                                Room

                                                <?php

                                                echo htmlspecialchars(
                                                    $facility[
                                                        'roomCode'
                                                    ]
                                                );

                                                ?>

                                            <?php endif; ?>

                                        </div>

                                    </div>


                                    <span
                                        class="facility-capacity"
                                    >

                                        Capacity:

                                        <?php

                                        echo (int)
                                            $facility[
                                                'capacity'
                                            ];

                                        ?>

                                    </span>

                                </label>

                            <?php endforeach; ?>

                        </div>

                        <!-- SEPARATOR -->
                        <div class="event-divider"></div>


                        <!-- EVENT MATERIALS -->
                        <h2>
                            Event Materials
                        </h2>


                        <!-- Event Poster -->

                        <div class="event-form-group">

                            <label for="eventPoster">
                                Event Poster
                            </label>

                            <input
                                type="file"
                                id="eventPoster"
                                name="eventPoster"
                                class="event-file-input"
                                accept=".jpg,.jpeg,.png,.webp"
                            >

                            <div class="event-file-help">
                                Accepted formats:
                                JPG, JPEG, PNG and WEBP.
                            </div>

                        </div>


                        <!-- Event Information -->

                        <div class="event-form-group">

                            <label for="eventInfo">
                                Event Information
                            </label>

                            <textarea
                                id="eventInfo"
                                name="eventInfo"
                                rows="5"
                                placeholder="Enter additional event information such as agenda, instructions, or requirements."
                            ></textarea>

                        </div>


                        <!-- CREATE BUTTON -->

                        <div class="event-button-row">

                            <button
                                type="submit"
                                class="event-btn event-btn-primary"
                            >
                                Create Event
                            </button>

                        </div>

                    </form>

                <?php endif; ?>

            </div>

        <?php endif; ?>

    <!-- VIEW TAB -->

    <?php else: ?>

        <form
            class="event-search"
            method="GET"
            action="manageUniversityEventPage.php"
        >

            <input
                type="hidden"
                name="tab"
                value="view"
            >

            <input
                type="text"
                name="search"
                placeholder="Search events by title, description or facility..."
                value="<?php

                echo htmlspecialchars(
                    $searchQuery
                );
                ?>"
            >

            <button type="submit">Search</button>

            <?php

            if (
                $searchQuery !== ''
            ): ?>

                <a
                    class="event-search-clear"
                    href="manageUniversityEventPage.php?tab=view"
                >
                    Clear
                </a>

            <?php endif; ?>

        </form>

        <?php

        if (
            empty($eventList)
        ): ?>

            <div class="empty-event">

                <?php

                if (
                    $searchQuery !== ''
                ): ?>

                    No events match your search.

                <?php else: ?>

                    No university events found.

                <?php endif; ?>

            </div>

        <?php else: ?>

            <div class="event-list">

                <?php

                foreach (
                    $eventList
                    as $event
                ): ?>

                    <div class="event-view-card">

                        <div class="event-view-info">

                            <h3>

                                <?php

                                echo htmlspecialchars(
                                    $event['title']
                                );
                                ?>

                            </h3>

                            <p>

                                <?php

                                echo htmlspecialchars(
                                    $event['description']
                                    ?? ''
                                );
                                ?>

                            </p>

                            <div
                                class="event-meta"
                            >

                                <?php

                                echo htmlspecialchars(
                                    $event['location']
                                    ?? 'Facility'
                                );
                                ?>

                                <br>

                                <?php

                                echo date(
                                    'd/m/Y h:i A',
                                    strtotime(
                                        $event[
                                            'startDatetime'
                                        ]
                                    )
                                );
                                ?>

                                &nbsp; –
                                &nbsp;

                                <?php

                                echo date(
                                    'd/m/Y h:i A',
                                    strtotime(
                                        $event[
                                            'endDatetime'
                                        ]
                                    )
                                );
                                ?>

                                <br>

                                Capacity:
                                <?php

                                echo (int) 
                                    $event['capacity'];
                                ?>

                            </div>

                        </div>

                        <span
                            class="event-status status-<?php

                            echo strtolower(
                                $event['status']
                            );
                            ?>"
                        >

                            <?php

                            echo htmlspecialchars(
                                ucfirst($event['status'])
                            );
                            ?>

                        </span>

                        <div class="event-actions">

                            <?php if (strtolower($event['status']) === 'active'): ?>

                                <?php if (!empty($event['eventPoster'])): ?>
                                    <button
                                        type="button"
                                        class="btn-event-poster"
                                        onclick='openPosterModal(
                                            "../<?php echo htmlspecialchars($event["eventPoster"], ENT_QUOTES); ?>",
                                            <?php echo json_encode($event["title"]); ?>
                                        )'
                                    >
                                        View Poster
                                    </button>
                                <?php endif; ?>

                                <button
                                    type="button"
                                    class="btn-event-edit"
                                    onclick='openEditEventModal(
                                        <?php echo json_encode($event, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>
                                    )'
                                >
                                    Edit
                                </button>

                                <button
                                    type="button"
                                    class="btn-event-suspend"
                                    onclick="openSuspendEventModal(<?php echo (int) $event['id']; ?>)"
                                >
                                    Suspend
                                </button>

                            <?php endif; ?>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    <?php endif; ?>

    <!-- EDIT EVENT MODAL -->
    <div
        class="event-modal-overlay"
        id="editEventPopup"
        style="display: none;"
    >

        <div class="edit-event-modal">

            <button
                type="button"
                class="event-modal-close"
                onclick="closeEditEventModal()"
            >
                &times;
            </button>

            <h2>
                Edit University Event
            </h2>

            <form
                method="POST"
                action="../controller/manageUniversityEventController.php"
                enctype="multipart/form-data"
                class="edit-event-form"
            >

                <input
                    type="hidden"
                    name="action"
                    value="updateUniversityEvent"
                >

                <input
                    type="hidden"
                    id="editEventId"
                    name="eventId"
                >

                <input
                    type="hidden"
                    id="editFacilityId"
                    name="facilityId"
                >

                <label for="editTitle">
                    Event Title
                </label>

                <input
                    type="text"
                    id="editTitle"
                    name="title"
                    required
                >

                <label for="editDescription">
                    Description
                </label>

                <textarea
                    id="editDescription"
                    name="description"
                    required
                ></textarea>

                <label for="editCapacity">
                    Event Capacity
                </label>

                <input
                    type="number"
                    id="editCapacity"
                    name="capacity"
                    min="1"
                    required
                >

                <label for="editStartDate">
                    Start Date
                </label>

                <input
                    type="date"
                    id="editStartDate"
                    name="startDate"
                    required
                >

                <label for="editStartTime">
                    Start Time
                </label>

                <input
                    type="time"
                    id="editStartTime"
                    name="startTime"
                    required
                >

                <label for="editEndDate">
                    End Date
                </label>

                <input
                    type="date"
                    id="editEndDate"
                    name="endDate"
                    required
                >

                <label for="editEndTime">
                    End Time
                </label>

                <input
                    type="time"
                    id="editEndTime"
                    name="endTime"
                    required
                >

                <label for="editEventInfo">
                    Event Information
                </label>

                <textarea
                    id="editEventInfo"
                    name="eventInfo"
                ></textarea>

                <label for="editEventPoster">
                    Event Poster
                </label>

                <input
                    type="file"
                    id="editEventPoster"
                    name="eventPoster"
                    accept=".jpg,.jpeg,.png,.webp"
                >

                <button
                    type="submit"
                    class="edit-event-submit"
                >
                    Update Event
                </button>

            </form>

        </div>

    </div>

    <!-- SUSPEND EVENT MODAL -->
    <div
        class="event-modal-overlay"
        id="suspendEventPopup"
        style="display: none;"
    >

        <div class="suspend-event-modal">

            <button
                type="button"
                class="event-modal-close"
                onclick="closeSuspendEventModal()"
            >
                &times;
            </button>

            <h2>
                Are you sure you want to suspend this university event?
            </h2>

            <form
                method="POST"
                action="../controller/manageUniversityEventController.php"
            >

                <input
                    type="hidden"
                    name="action"
                    value="suspendUniversityEvent"
                >

                <input
                    type="hidden"
                    id="suspendEventId"
                    name="eventId"
                >

                <div class="suspend-event-buttons">

                    <button
                        type="button"
                        class="suspend-cancel-button"
                        onclick="closeSuspendEventModal()"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="suspend-confirm-button"
                    >
                        Confirm
                    </button>

                </div>

            </form>

        </div>

    </div>

    <!-- VIEW POSTER MODAL -->
    <div
        class="event-modal-overlay"
        id="posterEventPopup"
        style="display: none;"
    >

        <div class="poster-event-modal">

            <button
                type="button"
                class="event-modal-close"
                onclick="closePosterModal()"
            >
                &times;
            </button>

            <h2 id="posterEventTitle">
                Event Poster
            </h2>

            <img
                id="posterEventImage"
                src=""
                alt="Event Poster"
            >

        </div>

    </div>

</main>

<footer>

    <div class="footer-bottom-bar">

        &copy; 2026 UniBee.
        All rights reserved.

    </div>

</footer>

 <script>

    /* FIND AVAILABLE FACILITIES SCROLL */
    window.addEventListener(
        'load',
        function () {

            const params =
                new URLSearchParams(
                    window.location.search
                );

            if (
                params.get('findFacilities') === '1'
            ) {

                const section =
                    document.getElementById(
                        'recommendedFacilities'
                    );

                if (section) {

                    section.scrollIntoView({
                        behavior: 'smooth'
                    });

                }
            }
        }
    );

    /* EDIT EVENT MODAL */
    function openEditEventModal(event)
    {
        document.getElementById(
            'editEventPopup'
        ).style.display = 'flex';

        document.getElementById(
            'editEventId'
        ).value = event.id;

        document.getElementById(
            'editFacilityId'
        ).value = event.facilityId;

        document.getElementById(
            'editTitle'
        ).value = event.title || '';

        document.getElementById(
            'editDescription'
        ).value = event.description || '';

        document.getElementById(
            'editCapacity'
        ).value = event.capacity || '';

        document.getElementById(
            'editEventInfo'
        ).value = event.eventInfo || '';

        if (event.startDatetime) {

            document.getElementById(
                'editStartDate'
            ).value =
                event.startDatetime.substring(
                    0,
                    10
                );

            document.getElementById(
                'editStartTime'
            ).value =
                event.startDatetime.substring(
                    11,
                    16
                );
        }

        if (event.endDatetime) {

            document.getElementById(
                'editEndDate'
            ).value =
                event.endDatetime.substring(
                    0,
                    10
                );

            document.getElementById(
                'editEndTime'
            ).value =
                event.endDatetime.substring(
                    11,
                    16
                );
        }
    }

    function closeEditEventModal()
    {
        document.getElementById(
            'editEventPopup'
        ).style.display = 'none';
    }

    /* SUSPEND MODAL */
    function openSuspendEventModal(eventId)
    {
        document.getElementById(
            'suspendEventId'
        ).value = eventId;

        document.getElementById(
            'suspendEventPopup'
        ).style.display = 'flex';
    }

    function closeSuspendEventModal()
    {
        document.getElementById(
            'suspendEventPopup'
        ).style.display = 'none';
    }

    /* POSTER MODAL */
    function openPosterModal(
        posterPath,
        eventTitle
    )
    {
        document.getElementById(
            'posterEventImage'
        ).src = posterPath;

        document.getElementById(
            'posterEventTitle'
        ).textContent =
            eventTitle + ' - Event Poster';

        document.getElementById(
            'posterEventPopup'
        ).style.display = 'flex';
    }

    function closePosterModal()
    {
        document.getElementById(
            'posterEventPopup'
        ).style.display = 'none';

        document.getElementById(
            'posterEventImage'
        ).src = '';
    }

    /* CLOSE MODALS BY CLICKING OUTSIDE */
    document.addEventListener(
        'click',
        function (event) {

            const editPopup =
                document.getElementById(
                    'editEventPopup'
                );

            const suspendPopup =
                document.getElementById(
                    'suspendEventPopup'
                );

            const posterPopup =
                document.getElementById(
                    'posterEventPopup'
                );


            if (
                event.target === editPopup
            ) {

                closeEditEventModal();

            }


            if (
                event.target === suspendPopup
            ) {

                closeSuspendEventModal();

            }


            if (
                event.target === posterPopup
            ) {

                closePosterModal();

            }

        }
    );

</script>

</body>

</html>