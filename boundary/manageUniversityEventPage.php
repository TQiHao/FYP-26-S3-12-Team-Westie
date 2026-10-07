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
            margin: 0 auto;
            padding: 30px 0 60px;
            box-sizing: border-box;
        }

        /* HEADER */
        .profile-header {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%;
            min-height: 45px;
            margin: 15px 0 25px 0;
        }

        .profile-header .section-label {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            margin: 0 !important;
            text-align: center !important;
            white-space: nowrap;
            font-size: 1.3rem;
            font-weight: 700;
        }

        /* TABS */
        .event-tabs {
            display: flex;
            gap: 40px;
            border-bottom: 1px solid #ddd;
            margin-bottom: 26px;
        }

        .event-tab {
            padding: 10px 4px;
            font-size: 1.05rem;
            font-weight: 700;
            color: #888;
            text-decoration: none;
            border-bottom: 3px solid transparent;
            margin-bottom: -1px;
        }

        .event-tab.active {
            color: #000;
            border-bottom-color: var(--bg-yellow);
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
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .event-view-card {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            border: 1px solid #ccc;
            border-radius: 10px;
            padding: 18px 22px;
            background: #fff;
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

            <div
                id="profileMenu"
                class="dropdown-menu"
            >

                <a href="universityAdminDashboardPage.php">
                    Dashboard
                </a>

                <a href="../controller/logoutController.php">
                    Log Out
                </a>

            </div>

        </div>

    </div>

</header>

<main class="university-event-page">

    <!-- HEADER -->
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

        <div class="event-message event-success">

            <?php

            echo htmlspecialchars($success);
            ?>

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
                                $event['status']
                            );
                            ?>

                        </span>

                        <div class="event-actions">

                            <button
                                type="button"
                                class="btn-event-edit"
                            >
                                Edit
                            </button>

                            <?php if (
                                strtolower($event['status'])
                                === 'active'
                            ): ?>

                                <button
                                    type="button"
                                    class="btn-event-suspend"
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

</main>

<footer>

    <div class="footer-bottom-bar">

        &copy; 2026 UniBee.
        All rights reserved.

    </div>

</footer>

    <script>

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

    </script>

</body>

</html>