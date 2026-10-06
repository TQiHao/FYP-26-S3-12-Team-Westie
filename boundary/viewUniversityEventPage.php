<?php

session_start();

if (
    !isset($_SESSION['logged_in']) ||
    $_SESSION['logged_in'] !== true
) {
    header("Location: LoginPage.php");
    exit();
}

require_once "../controller/ManageUniversityEventController.php";

$universityId =
    $_SESSION['university_id'] ?? null;

$keyword =
    trim($_GET['keyword'] ?? '');

$events = [];

if ($universityId !== null) {

    $controller =
        new ManageUniversityEventController();

    $events =
        $controller->getUniversityEvents(
            $universityId,
            $keyword
        );
}

if ($events === false) {
    $events = [];
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

    <title>View University Events - UniBee</title>

    <link rel="stylesheet" href="../style.css">

    <style>

        .university-event-page {
            width: 80%;
            max-width: 1050px;
            margin: 0 auto;
            min-height: calc(100vh - 150px);
            padding: 35px 0 70px;
            box-sizing: border-box;
        }

        .event-top-section {
            width: 800px;
            max-width: 800px;
            margin: 0 auto;
            box-sizing: border-box;
        }

        /* Back button area */

        .event-top-section .profile-header {
            width: 800px;
            max-width: 800px;
            height: 45px;
            margin: 20px 0 28px;
            padding: 0;
            box-sizing: border-box;
        }

        /* Tabs */
        .event-top-section .event-tabs {
            width: 800px;
            max-width: 800px;
            height: 40px;
            margin: 0 0 14px;
            padding: 0;
            display: flex;
            align-items: flex-start;
            border-bottom: 1px solid #bbb;
            box-sizing: border-box;
        }

        /* Tab */
        .event-top-section .event-tab {
            position: relative;
            display: block;
            height: 40px;
            padding: 0 0 8px;
            color: #222;
            text-decoration: none;
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.2;
            box-sizing: border-box;
        }

        /* Fixed tab positions */
        .event-top-section .event-tab:nth-child(1) {
            width: 185px;
        }

        .event-top-section .event-tab:nth-child(2) {
            width: 180px;
            margin-left: 30px;
        }

        /* Active */
        .event-top-section .event-tab.active {
            font-weight: 700;
        }

        /* Underline */
        .event-top-section .event-tab.active::after {
            content: "";
            position: absolute;
            left: 0;
            bottom: 0;
            width: 100%;
            height: 5px;
            background-color: var(--bg-yellow);
        }

        .event-top-section .event-tab:hover {
            text-decoration: none;
        }

        /* SEARCH */

        .event-search-form {
            width: 800px;
            max-width: 800px;
            margin: 0 auto 20px;
            display: flex;
            justify-content: flex-end;
            gap: 5px;
            box-sizing: border-box;
        }

        .event-search-input {
            width: 215px;
            padding: 7px 10px;
            border: 1px solid #999;
            border-radius: 4px;
            font-size: 0.75rem;
            box-sizing: border-box;
        }

        .event-search-button {
            width: 55px;
            padding: 7px 10px;
            border: 1px solid #000;
            border-radius: 3px;
            background-color: var(--bg-yellow);
            cursor: pointer;
            font-size: 0.75rem;
        }

        .event-search-button:hover {
            background-color: #e6c23a;
        }

        /* EVENT LIST */

        .event-list {
            width: 800px;
            max-width: 800px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 12px;
            box-sizing: border-box;
        }

        /* EVENT CARD */
        .event-card {
            width: 100%;
            min-height: 88px;
            padding: 16px 20px;
            border: 1px solid #bbb;
            border-radius: 12px;
            box-sizing: border-box;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .event-card-main {
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .event-title {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 700;
        }

        .event-card-info {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            font-size: 0.7rem;
            color: #666;
        }

        .event-card-right {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-shrink: 0;
        }

        /* STATUS */
        .event-status-active {
            font-size: 0.7rem;
            font-weight: 600;
            color: #18a84b;
            white-space: nowrap;
        }

        .event-status-suspended {
            font-size: 0.7rem;
            font-weight: 600;
            color: #e6a700;
            white-space: nowrap;
        }

        .event-status-cancelled {
            font-size: 0.7rem;
            font-weight: 600;
            color: #e53935;
            white-space: nowrap;
        }

        .event-status-completed {
            font-size: 0.7rem;
            font-weight: 600;
            color: #007bff;
            white-space: nowrap;
        }

        /* VIEW BUTTON */

        .event-view-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 55px;
            padding: 7px 12px;
            border: 1px solid #000;
            border-radius: 3px;
            background-color: var(--bg-yellow);
            color: #000;
            text-decoration: none;
            font-size: 0.7rem;
            font-weight: 700;
            box-sizing: border-box;
        }

        .event-view-button:hover {
            background-color: #e6c23a;
            text-decoration: none;
        }


        /* EMPTY MESSAGE */
        .empty-event-message {
            width: 800px;
            max-width: 800px;
            margin: 0 auto;
            padding: 25px;
            box-sizing: border-box;
            border: 1px solid #ddd;
            border-radius: 10px;
            text-align: center;
            color: #777;
            font-size: 0.75rem;
        }

        /* ERROR MESSAGE */
        .university-event-page .upload-message {
            width: 800px;
            max-width: 800px;
            margin: 0 auto 25px;
            box-sizing: border-box;
        }

        /* SUCCESS POPUP */

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

        .message-close:hover {
            opacity: 0.6;
        }

        /* MOBILE */

        @media (max-width: 768px) {

            .university-event-page {
                width: 90%;
            }

            .university-event-page .profile-header,
            .university-event-page .event-tabs,
            .event-search-form,
            .event-list,
            .empty-event-message,
            .university-event-page .upload-message {
                width: 100%;
                max-width: 100%;
            }

            .university-event-page .event-tabs {
                overflow-x: auto;
            }

            .event-search-form {
                justify-content: flex-start;
            }

            .event-card {
                align-items: flex-start;

                flex-direction: column;
            }

            .event-card-right {
                width: 100%;

                justify-content: space-between;
            }

            .event-card-info {
                flex-direction: column;

                gap: 5px;
            }

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


                <div
                    id="profileMenu"
                    class="dropdown-menu"
                >

                    <a href="UniversityAdminDashboardPage.php">
                        Dashboard
                    </a>

                    <a href="ManageUniversityEventsPage.php">
                        Manage University Events
                    </a>

                    <a href="../controller/logoutController.php">
                        Log Out
                    </a>

                </div>

            </div>

        </div>

    </header>

    <!-- Main -->
    <main class="university-event-page">
        <div class="event-top-section">

            <div class="profile-header">

                <a
                    href="universityAdminDashboardPage.php"
                    class="btn-back"
                >
                    &#8592; Back
                </a>

            </div>

            <div class="event-tabs">

                <a
                    href="CreateUniversityEventPage.php"
                    class="event-tab"
                >
                    Create University Event
                </a>

                <a
                    href="ViewUniversityEventsPage.php"
                    class="event-tab active"
                >
                    View University Events
                </a>

            </div>

        </div>

        <!-- Search -->
        <form
            action="ViewUniversityEventsPage.php"
            method="GET"
            class="event-search-form"
        >

            <input
                type="text"
                name="keyword"
                value="<?php echo htmlspecialchars($keyword); ?>"
                placeholder="Search events"
                class="event-search-input"
            >

            <button
                type="submit"
                class="event-search-button"
            >
                🔍
            </button>

        </form>

        <!-- Event List -->
        <?php if (empty($events)): ?>

            <div class="empty-event-message">

                <?php if ($keyword !== ''): ?>

                    No matching events found.

                <?php else: ?>

                    No events found.

                <?php endif; ?>

            </div>

        <?php else: ?>

            <div class="event-list">

                <?php foreach ($events as $event): ?>

                    <?php

                    $startDate =
                        !empty($event['startDatetime'])
                        ? date(
                            'd M Y',
                            strtotime(
                                $event['startDatetime']
                            )
                        )
                        : '-';

                    $startTime =
                        !empty($event['startDatetime'])
                        ? date(
                            'h:i A',
                            strtotime(
                                $event['startDatetime']
                            )
                        )
                        : '-';

                    $endTime =
                        !empty($event['endDatetime'])
                        ? date(
                            'h:i A',
                            strtotime(
                                $event['endDatetime']
                            )
                        )
                        : '-';

                    $status =
                        strtolower(
                            $event['status'] ?? ''
                        );

                    ?>

                    <div class="event-card">

                        <div class="event-card-main">

                            <h2 class="event-title">
                                <?php

                                echo htmlspecialchars(
                                    $event['title']
                                );
                                ?>
                            </h2>

                            <div class="event-card-info">

                                <?php if ($startDate !== '-'): ?>

                                    <span>
                                        <?php

                                        echo htmlspecialchars(
                                            $startDate
                                        );
                                        ?>
                                    </span>

                                <?php endif; ?>

                                <span>
                                    <?php

                                    echo htmlspecialchars(
                                        $startTime
                                    );
                                    ?>
                                    -
                                    <?php

                                    echo htmlspecialchars(
                                        $endTime
                                    );
                                    ?>
                                </span>


                                <span>
                                    <?php

                                    echo htmlspecialchars(
                                        $event['location'] ?? '-'
                                    );
                                    ?>
                                </span>

                            </div>

                        </div>

                        <div class="event-card-right">

                            <span
                                class="event-status
                                <?php

                                        if ($status === 'active') {
                                            echo ' event-status-active';
                                        } elseif ($status === 'suspended') {
                                            echo ' event-status-suspended';
                                        } elseif ($status === 'cancelled') {
                                            echo ' event-status-cancelled';
                                        } elseif ($status === 'completed') {
                                            echo ' event-status-completed';
                                        }
                                        ?>"
                            >
                                <?php
                                        echo htmlspecialchars(
                                            strtoupper($event['status'])
                                        );
                                        ?>
                            </span>

                            <a
                                href="UpdateUniversityEventPage.php?id=<?php echo urlencode($event['id']); ?>"
                                class="event-view-button"
                            >
                                View
                            </a>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </main>

    <!-- Footer -->
    <footer>

        <div class="footer-bottom-bar">

            &copy; 2026 UniBee. All rights reserved.

        </div>

    </footer>

    <script src="../script.js"></script>

</body>

</html>