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

$eventId =
    (int) ($_GET['id'] ?? 0);


if ($universityId === null || $eventId <= 0) {

    header(
        "Location: ViewUniversityEventPage.php"
    );

    exit();
}


$controller =
    new ManageUniversityEventController();


$event =
    $controller->getUniversityEvent(
        $eventId,
        $universityId
    );


if ($event === false) {

    header(
        "Location: ViewUniversityEventPage.php"
    );

    exit();
}


// =========================================================
// SESSION MESSAGES
// =========================================================

$updateSuccess =
    $_SESSION['event_update_success'] ?? null;

$updateError =
    $_SESSION['event_update_error'] ?? null;

$suspendSuccess =
    $_SESSION['event_suspend_success'] ?? null;

$suspendError =
    $_SESSION['event_suspend_error'] ?? null;


unset(
    $_SESSION['event_update_success']
);

unset(
    $_SESSION['event_update_error']
);

unset(
    $_SESSION['event_suspend_success']
);

unset(
    $_SESSION['event_suspend_error']
);


// =========================================================
// DATE / TIME FORMAT
// =========================================================

$startDate = '';
$startTime = '';
$endDate = '';
$endTime = '';


if (!empty($event['startDatetime'])) {

    $startDate =
        date(
            'Y-m-d',
            strtotime(
                $event['startDatetime']
            )
        );

    $startTime =
        date(
            'H:i',
            strtotime(
                $event['startDatetime']
            )
        );
}


if (!empty($event['endDatetime'])) {

    $endDate =
        date(
            'Y-m-d',
            strtotime(
                $event['endDatetime']
            )
        );

    $endTime =
        date(
            'H:i',
            strtotime(
                $event['endDatetime']
            )
        );
}

// DISPLAY DATE / TIME
$displayDate = '-';
$displayTime = '-';

if (!empty($event['startDatetime'])) {

    $displayDate =
        date(
            'd M Y',
            strtotime(
                $event['startDatetime']
            )
        );
}

if (
    !empty($event['startDatetime']) &&
    !empty($event['endDatetime'])
) {

    $displayTime =
        date(
            'h:i A',
            strtotime(
                $event['startDatetime']
            )
        )
        . ' – ' .
        date(
            'h:i A',
            strtotime(
                $event['endDatetime']
            )
        );
}

// STATUS
$status =
    strtolower(
        $event['status'] ?? ''
    );

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
        Update University Event - UniBee
    </title>

    <link
        rel="stylesheet"
        href="../style.css"
    >

    <style>

        /* PAGE */
        .update-event-page {

            width: 80%;

            max-width: 1050px;

            min-height:
                calc(100vh - 150px);

            margin: 0 auto;

            padding:
                35px 0 70px;

            box-sizing: border-box;
        }

        /* BACK BUTTON */
        .update-event-page .profile-header {
            width: 800px;
            max-width: 800px;
            margin: 20px auto 28px;
            padding: 0;
            box-sizing: border-box;
        }

        /* EVENT HEADER */
        .update-event-header {
            width: 800px;
            max-width: 800px;
            margin: 0 auto;
            padding-bottom: 12px;
            border-bottom: 1px solid #bbb;
            box-sizing: border-box;
        }

        .update-event-header h1 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 700;
        }

        /* EVENT DETAILS */
        .update-event-content {
            width: 800px;
            max-width: 800px;
            margin: 28px auto 0;
            display: grid;
            grid-template-columns: 1fr 1fr;
            column-gap: 60px;
            box-sizing: border-box;
        }

        .event-detail-group {
            margin-bottom: 24px;
        }

        .event-detail-group strong {
            display: block;
            margin-bottom: 6px;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .event-detail-group p {
            margin: 0;
            font-size: 0.8rem;
            line-height: 1.5;
            color: #222;
        }

        /* STATUS */
        .detail-status-active {
            color: #18a84b !important;
            font-weight: 700;
        }

        .detail-status-suspended {
            color: #e6a700 !important;
            font-weight: 700;
        }

        .detail-status-cancelled {
            color: #e53935 !important;
            font-weight: 700;
        }

        .detail-status-completed {
            color: #007bff !important;
            font-weight: 700;
        }

        /* ACTION BUTTONS */

        .event-action-buttons {
            width: 800px;
            max-width: 800px;
            margin: 10px auto 0;
            display: flex;
            gap: 28px;
            box-sizing: border-box;
        }

        .event-update-button,
        .event-suspend-button {
            min-width: 150px;
            padding: 10px 25px;
            border: 1px solid #000;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 700;
            cursor: pointer;
        }

        .event-update-button {
            background-color: #00d719;
            color: #000;
        }

        .event-update-button:hover {
            background-color: #00b814;
        }

        .event-suspend-button {
            background-color: #ff0000;
            color: #000;
        }

        .event-suspend-button:hover {

            background-color: #d90000;
        }

        /* GENERAL POPUP */

        .event-modal-overlay {
            position: fixed;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.25);
            z-index: 99999;
        }

        /* UPDATE POPUP */

        .update-event-modal {
            position: relative;
            width: 550px;
            max-height: 80vh;
            overflow-y: auto;
            padding: 30px;
            background: #f8f8fa;
            border: 1px solid #555;
            box-sizing: border-box;
        }

        .update-event-modal h2 {
            margin: 0 0 20px;
            font-size: 1rem;
            font-weight: 700;
        }


        /* POPUP CLOSE */

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

        /* UPDATE FORM */
        .update-event-form {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .update-event-form label {
            margin-top: 7px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .update-event-form input,
        .update-event-form textarea {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #999;
            border-radius: 10px;
            background: #fff;
            font-size: 0.75rem;
            box-sizing: border-box;
            outline: none;
        }

        .update-event-form textarea {
            min-height: 70px;
            resize: vertical;
        }

        .update-event-form input:focus,
        .update-event-form textarea:focus {
            border-color: var(--bg-yellow);
        }

        .update-event-submit {
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

        .update-event-submit:hover {
            background:
                #e6c23a;
        }

        /* SUSPEND CONFIRMATION */
        .suspend-confirm-modal {
            position: relative;
            width: 420px;
            padding: 35px 30px;
            background: #f8f8fa;
            border: 1px solid #555;
            box-sizing: border-box;
            text-align: center;
        }

        .suspend-confirm-modal h2 {
            margin: 0 0 25px;
            font-size: 0.95rem;
            font-weight: 700;
        }

        .suspend-confirm-buttons {
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
            background:
                #6b3200;
        }

        .suspend-cancel-button:hover {
            background:
                #522500;
        }

        .suspend-confirm-button {
            background:
                #d00000;
        }

        .suspend-confirm-button:hover {
            background:
                #ae0000;
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

        /* ERROR MESSAGE */
        .event-error-message {
            width: 800px;
            max-width: 800px;
            margin: 20px auto;
            padding: 10px 12px;
            border-radius: 6px;
            background: #ffebee;
            color: #c62828;
            font-size: 0.75rem;
            box-sizing: border-box;
        }

        /* MOBILE */
        @media (max-width: 768px) {

            .update-event-page {

                width: 90%;
            }

            .update-event-page .profile-header,
            .update-event-header,
            .update-event-content,
            .event-action-buttons,
            .event-error-message {

                width: 100%;

                max-width: 100%;
            }

            .update-event-content {

                grid-template-columns: 1fr;

                row-gap: 10px;
            }

            .event-action-buttons {

                flex-direction: column;
            }

            .event-update-button,
            .event-suspend-button {

                width: 150px;
            }

            .update-event-modal {

                width: 90%;
            }

            .suspend-confirm-modal,
            .event-success-modal {

                width: 90%;
            }

        }

    </style>

</head>

<body>

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

<main class="update-event-page">


    <!-- Back -->

    <div class="profile-header">

        <a
            href="ViewUniversityEventPage.php"
            class="btn-back"
        >
            &#8592; Back
        </a>

    </div>


    <!-- Event title -->

    <section class="update-event-header">

        <h1>
            <?php

            echo htmlspecialchars(
                $event['title']
            );
            ?>
        </h1>

    </section>

    <?php if ($updateSuccess): ?>

        <div
            class="event-modal-overlay"
            id="updateSuccessPopup"
        >

            <div class="event-success-modal">

                <button
                    type="button"
                    class="event-modal-close"
                    onclick="
                        document
                            .getElementById('updateSuccessPopup')
                            .remove();
                    "
                >
                    &times;
                </button>


                <h2>
                    University Event has been updated successfully
                </h2>

            </div>

        </div>

    <?php endif; ?>

    <?php if ($suspendSuccess): ?>

        <div
            class="event-modal-overlay"
            id="suspendSuccessPopup"
        >

            <div class="event-success-modal">

                <button
                    type="button"
                    class="event-modal-close"
                    onclick="
                        document.getElementById('suspendSuccessPopup').remove();"
                >
                    &times;
                </button>


                <h2>
                    University Event has been Suspended
                </h2>

            </div>

        </div>

    <?php endif; ?>

    <?php if ($updateError): ?>

        <div class="event-error-message">

            <?php

            echo htmlspecialchars(
                $updateError
            );
            ?>

        </div>

    <?php endif; ?>

    <?php if ($suspendError): ?>

        <div class="event-error-message">

            <?php

            echo htmlspecialchars(
                $suspendError
            );
            ?>

        </div>

    <?php endif; ?>

    <div class="update-event-content">

        <!-- LEFT -->
        <div>

            <!-- Description -->

            <div class="event-detail-group">

                <strong>
                    Description:
                </strong>

                <p>
                    <?php

                    echo nl2br(
                        htmlspecialchars(
                            $event['description'] ?? ''
                        )
                    );
                    ?>
                </p>

            </div>

            <!-- Date -->
            <div class="event-detail-group">

                <strong>
                    Date:
                </strong>

                <p>
                    <?php

                    echo htmlspecialchars(
                        $displayDate
                    );
                    ?>
                </p>

            </div>

            <!-- Time -->
            <div class="event-detail-group">

                <strong>
                    Time:
                </strong>

                <p>
                    <?php

                    echo htmlspecialchars(
                        $displayTime
                    );
                    ?>
                </p>

            </div>

            <!-- Location -->
            <div class="event-detail-group">

                <strong>
                    Location:
                </strong>

                <p>
                    <?php

                    echo htmlspecialchars(
                        $event['location'] ?? '-'
                    );
                    ?>
                </p>

            </div>

            <!-- Capacity -->
            <div class="event-detail-group">

                <strong>
                    Capacity:
                </strong>

                <p>
                    <?php

                    echo htmlspecialchars(
                        $event['capacity'] ?? '-'
                    );
                    ?>
                </p>

            </div>

        </div>

        <!-- RIGHT -->
        <div>

            <!-- Status -->
            <div class="event-detail-group">

                <strong>
                    Status:
                </strong>

                <p class="
                    <?php

                    if ($status === 'active') {
                        echo 'detail-status-active';
                    } elseif ($status === 'suspended') {
                        echo 'detail-status-suspended';
                    } elseif ($status === 'cancelled') {
                        echo 'detail-status-cancelled';
                    } elseif ($status === 'completed') {
                        echo 'detail-status-completed';
                    }
                    ?>
                ">
                    <?php

                    echo htmlspecialchars(
                        strtoupper($event['status'])
                    );
                    ?>
                </p>
            </div>

            <!-- Created By -->
            <div class="event-detail-group">

                <strong>
                    Created By:
                </strong>

                <p>
                    <?php

                    echo htmlspecialchars(
                        $event['createdBy'] ?? '-'
                    );
                    ?>
                </p>

            </div>

            <!-- Last Updated -->
            <div class="event-detail-group">

                <strong>
                    Last Updated:
                </strong>

                <p>
                    <?php

                    echo htmlspecialchars(
                        $event['updatedAt'] ?? '-'
                    );
                    ?>
                </p>

            </div>

        </div>

    </div>

    <?php if ($status === 'active'): ?>

        <div class="event-action-buttons">

            <button
                type="button"
                class="event-update-button"
                onclick="openUpdateEventModal()"
            >
                UPDATE
            </button>

            <button
                type="button"
                class="event-suspend-button"
                onclick="openSuspendEventModal()"
            >
                SUSPEND
            </button>

        </div>

    <?php endif; ?>

</main>

<div
    class="event-modal-overlay"
    id="updateEventModal"
    style="display: none;"
>

    <div class="update-event-modal">

        <button
            type="button"
            class="event-modal-close"
            onclick="closeUpdateEventModal()"
        >
            &times;
        </button>

        <h2>
            Update University Event
        </h2>

        <form
            action="../controller/ManageUniversityEventController.php"
            method="POST"
            class="update-event-form"
        >

            <!-- Action -->
            <input
                type="hidden"
                name="action"
                value="updateUniversityEvent"
            >

            <!-- Event ID -->
            <input
                type="hidden"
                name="eventId"
                value="<?php

                echo htmlspecialchars(
                    $event['id']
                );
                ?>"
            >

            <!-- Title -->
            <label for="updateTitle">
                Title
            </label>

            <input
                type="text"
                id="updateTitle"
                name="title"
                value="<?php

                echo htmlspecialchars(
                    $event['title']
                );
                ?>"
                required
            >

            <!-- Description -->
            <label for="updateDescription">
                Description
            </label>

            <textarea
                id="updateDescription"
                name="description"
                required
            ><?php

            echo htmlspecialchars(
                $event['description'] ?? ''
            );
            ?></textarea>

            <!-- Location -->
            <label for="updateLocation">
                Location
            </label>

            <input
                type="text"
                id="updateLocation"
                name="location"
                value="<?php

                echo htmlspecialchars(
                    $event['location'] ?? ''
                );
                ?>"
                readonly
            >

            <!-- Capacity -->
            <label for="updateCapacity">
                Capacity
            </label>

            <input
                type="number"
                id="updateCapacity"
                name="capacity"
                min="1"
                value="<?php

                echo htmlspecialchars(
                    $event['capacity'] ?? ''
                );
                ?>"
                required
            >

            <!-- Start Date -->
            <label for="updateStartDate">
                Start Date
            </label>

            <input
                type="date"
                id="updateStartDate"
                name="startDate"
                value="<?php

                echo htmlspecialchars(
                    $startDate
                );
                ?>"
                required
            >

            <!-- Start Time -->
            <label for="updateStartTime">
                Start Time
            </label>

            <input
                type="time"
                id="updateStartTime"
                name="startTime"
                value="<?php

                echo htmlspecialchars(
                    $startTime
                );
                ?>"
                required
            >

            <!-- End Date -->
            <label for="updateEndDate">
                End Date
            </label>

            <input
                type="date"
                id="updateEndDate"
                name="endDate"
                value="<?php

                echo htmlspecialchars(
                    $endDate
                );
                ?>"
                required
            >

            <!-- End Time -->
            <label for="updateEndTime">
                End Time
            </label>

            <input
                type="time"
                id="updateEndTime"
                name="endTime"
                value="<?php

                echo htmlspecialchars(
                    $endTime
                );
                ?>"
                required
            >

            <!-- Submit -->
            <button
                type="submit"
                class="update-event-submit"
            >
                UPDATE
            </button>

        </form>

    </div>

</div>

<div
    class="event-modal-overlay"
    id="suspendEventModal"
    style="display: none;"
>

    <div class="suspend-confirm-modal">

        <h2>
            Are you sure you want to suspend?
        </h2>

        <div class="suspend-confirm-buttons">

            <button
                type="button"
                class="suspend-cancel-button"
                onclick="closeSuspendEventModal()"
            >
                Cancel
            </button>

            <form
                action="../controller/ManageUniversityEventController.php"
                method="POST"
            >

                <input
                    type="hidden"
                    name="action"
                    value="suspendUniversityEvent"
                >

                <input
                    type="hidden"
                    name="eventId"
                    value="<?php

                    echo htmlspecialchars(
                        $event['id']
                    );
                    ?>"
                >

                <button
                    type="submit"
                    class="suspend-confirm-button"
                >
                    Suspend
                </button>

            </form>

        </div>

    </div>

</div>

<footer>

    <div class="footer-bottom-bar">

        &copy; 2026 UniBee. All rights reserved.

    </div>

</footer>

<script src="../script.js"></script>

    <script>

        function openUpdateEventModal() {

            var modal =
                document.getElementById(
                    "updateEventModal"
                );

            if (modal) {

                modal.style.display =
                    "flex";
            }
        }


        function closeUpdateEventModal() {

            var modal =
                document.getElementById(
                    "updateEventModal"
                );

            if (modal) {

                modal.style.display =
                    "none";
            }
        }


        function openSuspendEventModal() {

            var modal =
                document.getElementById(
                    "suspendEventModal"
                );

            if (modal) {

                modal.style.display =
                    "flex";
            }
        }


        function closeSuspendEventModal() {

            var modal =
                document.getElementById(
                    "suspendEventModal"
                );

            if (modal) {

                modal.style.display =
                    "none";
            }
        }

    </script>

</body>

</html>