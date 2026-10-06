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


$success =
    $_SESSION['event_success'] ?? null;

$error =
    $_SESSION['event_error'] ?? null;

$oldInput = $_SESSION['event_old_input'] ?? [];

unset($_SESSION['event_success']);
unset($_SESSION['event_error']);


$universityId =
    $_SESSION['university_id'] ?? null;


$facilities = [];


if ($universityId !== null) {

    $controller =
        new ManageUniversityEventController();

    $facilities =
        $controller->getAvailableFacilities(
            $universityId
        );
}

$selectedFacilityId =
    $oldInput['facilityId'] ?? '';

unset($_SESSION['event_old_input']);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Create University Event - UniBee</title>

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

        /* CREATE EVENT FORM */
        .university-event-form {
            width: 800px;
            max-width: 800px;
            margin: 0 auto;
            box-sizing: border-box;
        }

        .event-form-group {
            margin-bottom: 20px;
        }

        .event-form-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 0.85rem;
        }

        /* Text inputs */
        .event-form-group input[type="text"],
        .event-form-group input[type="number"],
        .event-form-group textarea {
            width: 100%;
            padding: 8px 12px;
            box-sizing: border-box;
            border: 1px solid #999;
            border-radius: 15px;
            font-size: 0.8rem;
            outline: none;
        }

        .event-form-group textarea {
            resize: vertical;
            border-radius: 12px;
        }

        /* LOCATION DROPDOWN */
        .event-location-select {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid #999;
            border-radius: 15px;
            background-color: #fff;
            font-size: 0.8rem;
            outline: none;
            box-sizing: border-box;
        }

        .event-location-select:focus {
            border-color: var(--bg-yellow);
        }

        .event-form-help {
            margin-top: 6px;
            font-size: 0.7rem;
            color: #777;
        }

        /* DATE AND TIME */
        .event-datetime-row {
            display: flex;
            gap: 10px;
        }

        .event-datetime-row input {
            padding: 8px 10px;
            border: 1px solid #999;
            border-radius: 15px;
            font-size: 0.8rem;
        }

        /* CREATE BUTTON */
        .event-create-button {
            padding: 8px 15px;
            border: 1px solid #000;
            border-radius: 3px;
            background-color: var(--bg-yellow);
            color: #000;
            font-size: 0.7rem;
            font-weight: 700;
            cursor: pointer;
        }

        .event-create-button:hover {
            background-color: #e6c23a;
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
            .university-event-form,
            .university-event-page .upload-message {
                width: 100%;
                max-width: 100%;
            }

            .university-event-page .event-tabs {
                overflow-x: auto;
            }

            .event-datetime-row {
                flex-direction: column;
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
                    class="event-tab active"
                >
                    Create University Event
                </a>

                <a
                    href="ViewUniversityEventPage.php"
                    class="event-tab"
                >
                    View University Events
                </a>

            </div>

        </div>

        <!-- Success Popup -->
        <?php if ($success): ?>

            <div
                class="message-overlay"
                id="successPopup"
            >

                <div class="message-modal">

                    <button
                        type="button"
                        class="message-close"
                        onclick="document.getElementById('successPopup').remove();"
                    >
                        &times;
                    </button>

                    <h2>
                        Event created successfully.
                    </h2>

                </div>

            </div>

        <?php endif; ?>

        <!-- Error Message -->
        <?php if ($error): ?>

            <div class="upload-message upload-error">

                <?php

                echo htmlspecialchars($error);
                ?>

            </div>

        <?php endif; ?>

        <!-- Create Event Form -->
        <form
            action="../controller/ManageUniversityEventController.php"
            method="POST"
            class="university-event-form"
        >

            <!-- Title -->
            <div class="event-form-group">

                <label for="title">
                    Title
                </label>

                <input
                    type="text"
                    id="title"
                    name="title"
                    value="<?php echo htmlspecialchars($oldInput['title'] ?? ''); ?>"
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
                    rows="2"
                    required
                ><?php echo htmlspecialchars($oldInput['description'] ?? ''); ?></textarea>

            </div>

            <!-- Location -->
            <div class="event-form-group">

                <label for="facilityId">
                    Location
                </label>

                <?php if (empty($facilities)): ?>

                    <select
                        id="facilityId"
                        name="facilityId"
                        class="event-location-select"
                        disabled
                    >

                        <option value="">
                            No available facilities
                        </option>

                    </select>

                    <p class="event-form-help">
                        No facilities are currently available
                        for a university event.
                    </p>

                <?php else: ?>

                    <select
                        id="facilityId"
                        name="facilityId"
                        class="event-location-select"
                        required
                    >

                        <option value="">
                            Select Facility
                        </option>

                        <?php foreach ($facilities as $facility): ?>

                            <option
                                value="<?php

                                echo htmlspecialchars(
                                    $facility['id']
                                ); ?>"
                            >

                                <?php

                                echo htmlspecialchars(
                                    $facility['eventLocation']
                                );
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                <?php endif; ?>

            </div>

            <!-- Capacity -->
            <div class="event-form-group">

                <label for="capacity">
                    Capacity
                </label>

                <input
                    type="number"
                    id="capacity"
                    name="capacity"
                    value="<?php echo htmlspecialchars($oldInput['capacity'] ?? ''); ?>"
                    min="1"
                    required
                >

            </div>

            <!-- Start Date and Time -->
            <div class="event-form-group">

                <label>
                    Start Date & Time
                </label>

                <div class="event-datetime-row">

                    <input
                        type="date"
                        name="startDate"
                        value="<?php echo htmlspecialchars($oldInput['startDate'] ?? ''); ?>"
                        required
                    >

                   <input
                        type="time"
                        name="startTime"
                        value="<?php echo htmlspecialchars($oldInput['startTime'] ?? ''); ?>"
                        required
                    >

                </div>

            </div>


            <!-- End Date and Time -->
            <div class="event-form-group">

                <label>
                    End Date & Time
                </label>

                <div class="event-datetime-row">

                    <input
                        type="date"
                        name="endDate"
                        value="<?php echo htmlspecialchars($oldInput['endDate'] ?? ''); ?>"
                        required
                    >

                    <input
                        type="time"
                        name="endTime"
                        value="<?php echo htmlspecialchars($oldInput['endTime'] ?? ''); ?>"
                        required
                    >

                </div>

            </div>

            <!-- Action -->
            <input
                type="hidden"
                name="action"
                value="createUniversityEvent"
            >

            <!-- Create -->
            <button
                type="submit"
                class="event-create-button"
            >
                Create
            </button>

        </form>

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