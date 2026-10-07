<?php

session_start();

if (
    !isset($_SESSION['logged_in']) ||
    $_SESSION['logged_in'] !== true
) {
    header("Location: LoginPage.php");
    exit();
}

require_once "../controller/manageBookableFacilitiesController.php";

$universityId =
    $_SESSION['university_id'] ?? null;

$controller =
    new ManageBookableFacilitiesController();

$tab =
    $_GET['tab'] ?? 'create';

$allowedTabs = [
    'create',
    'view',
    'report'
];

if (!in_array($tab, $allowedTabs, true)) {
    $tab = 'create';
}


// Messages
$success =
    $_SESSION['bookable_facility_success'] ?? null;

$error =
    $_SESSION['bookable_facility_error'] ?? null;

unset(
    $_SESSION['bookable_facility_success']
);

unset(
    $_SESSION['bookable_facility_error']
);

// Data
$facilities = [];

$bookableFacilities = [];

$facilityType =
    $_GET['facilityType'] ?? '';

$usageType =
    $_GET['usageType'] ?? '';

$utilisationReport = [];

$utilisationSummary = [];

if ($universityId !== null) {

    $facilities =
        $controller->getAvailableFacilities(
            $universityId
        );

    $bookableFacilities =
        $controller->getBookableFacilities(
            $universityId
        );

    if ($tab === 'report') {

        $utilisationReport =
            $controller->getFacilityUtilisationReport(
                $universityId,
                $facilityType,
                $usageType
            );

        $utilisationSummary =
            $controller->getFacilityUtilisationSummary(
                $universityId
            );
    }
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
        Manage Facilities Booking - UniBee
    </title>

    <link
        rel="stylesheet"
        href="../style.css"
    >

    <style>
        .bookable-page {
            width: 80%;
            max-width: 1050px;
            margin: 0 auto;
            padding: 30px 0 60px;
            box-sizing: border-box;
        }

        .bookable-page-title {
            text-align: center;
            font-size: 1.35rem;
            font-weight: 700;
            margin: 0 0 40px;
        }

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
            left: 70%;
            transform: translateX(-50%);
            margin: 0 !important;
            text-align: center !important;
            white-space: nowrap;
        }

        /* Back */
        .bookable-back {
            margin-bottom: 35px;
        }

        /* Tabs */
        .bookable-tabs {
            display: flex;
            gap: 40px;
            border-bottom: 1px solid #ddd;
            margin-bottom: 26px;
        }

        .bookable-tab {
            padding: 10px 4px;
            font-size: 1.05rem;
            font-weight: 700;
            color: #888;
            text-decoration: none;
            border-bottom: 3px solid transparent;
            margin-bottom: -1px;
        }

        .bookable-tab.active {
            color: #000;
            border-bottom-color: var(--bg-yellow);
        }

        .bookable-tab:hover {
            color: #000;
            text-decoration: none;
        }

        /* Messages */
        .bookable-message {
            padding: 10px 14px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-size: 0.8rem;
        }

        .bookable-success {
            background-color: #e7f7e7;
            border: 1px solid #8bc98b;
        }

        .bookable-error {
            background-color: #fde8e8;
            border: 1px solid #e29a9a;
        }

        /* Form */
        .bookable-form {
            width: 100%;
            max-width: none;
            margin: 0;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;
            font-size: 1rem;
        }

        .form-group select {
            width: 100%;
            height: 42px;
            padding: 8px 12px;
            border: 1px solid #999;
            border-radius: 6px;
            background: #fafafa;
            font-size: 0.9rem;
            box-sizing: border-box;
        }

        .create-button-area {
            margin-top: 40px;
        }

        .btn-create-bookable {
            padding: 7px 16px;
            border: 1px solid #000;
            border-radius: 3px;
            background-color: var(--bg-yellow);
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-create-bookable:hover {
            background-color: #e6c23a;
        }

        /* View */
        .bookable-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .bookable-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 18px;
            border: 1px solid #bbb;
            border-radius: 10px;
        }

        .bookable-main {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .bookable-name {
            font-size: 0.95rem;
            font-weight: 700;
        }

        .bookable-details {
            font-size: 0.7rem;
            color: #666;
        }

        .bookable-status {
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-available {
            color: green;
        }

        .status-unavailable {
            color: #d88900;
        }

        .empty-message {
            width: 100%;
            text-align: center;
            color: #777;
            padding: 70px 0;
            font-size: 0.85rem;
            box-sizing: border-box;
        }

        /* FACILITY UTILISATION REPORT */
        .report-filter-form {
            width: 100%;
            display: flex;
            align-items: flex-end;
            gap: 18px;
            margin-bottom: 25px;
        }

        .report-filter-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .report-filter-group label {
            font-size: 0.78rem;
            font-weight: 600;
        }

        .report-filter-group select {
            width: 190px;
            height: 36px;
            padding: 6px 10px;
            border: 1px solid #999;
            border-radius: 4px;
            background-color: white;
            font-size: 0.78rem;
            box-sizing: border-box;
        }

        .report-filter-button {
            height: 36px;
            padding: 0 16px;
            border: 1px solid #000;
            border-radius: 3px;
            background-color: var(--bg-yellow);
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
        }

        .report-filter-button:hover {
            background-color: #e6c23a;
        }

        /* Summary cards */
        .report-summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 28px;
        }

        .summary-card {
            min-height: 85px;
            padding: 14px 16px;
            border: 1px solid #ccc;
            border-radius: 10px;
            background-color: #fff;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 6px;
        }

        .summary-label {
            font-size: 0.68rem;
            color: #777;
            text-transform: uppercase;
            font-weight: 600;
        }

        .summary-value {
            font-size: 1.1rem;
            font-weight: 700;
        }

        .summary-facility {
            font-size: 0.82rem;
        }


        /* Report table */
        .utilisation-table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .utilisation-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.75rem;
        }

        .utilisation-table th {
            text-align: left;
            padding: 10px 8px;
            border-bottom: 1px solid #999;
            font-weight: 700;
            white-space: nowrap;
        }

        .utilisation-table td {
            padding: 13px 8px;
            border-bottom: 1px solid #ddd;
            vertical-align: middle;
        }

        .utilisation-table tbody tr:hover {
            background-color: #fafafa;
        }

        .facility-location {
            display: block;
            margin-top: 3px;
            color: #777;
            font-size: 0.65rem;
        }

        /* Utilisation */
        .utilisation-cell {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 115px;
        }

        .utilisation-value {
            min-width: 32px;
            font-weight: 600;
        }

        .utilisation-bar {
            width: 60px;
            height: 7px;
            background-color: #eee;
            border-radius: 5px;
            overflow: hidden;
        }

        .utilisation-fill {
            height: 100%;
            background-color: var(--bg-yellow);
            border-radius: 5px;
        }

        .report-empty {
            text-align: center !important;
            padding: 70px 0 !important;
            color: #777;
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

                <a
                    href="UniversityAdminDashboardPage.php"
                >
                    Dashboard
                </a>

                <a
                    href="UniversityAdminDashboardPage.php"
                >
                    Manage Facilities Booking
                </a>

                <a
                    href="../controller/logoutController.php"
                >
                    Log Out
                </a>

            </div>

        </div>

    </div>

</header>

<main class="bookable-page">

    <div class="bookable-container">

        <div class="profile-header">
            <a
                href="universityAdminDashboardPage.php"
                class="btn-back"
            >
                &#8592; Back
            </a>

            <h1 class="section-label">
                Manage Facilities Booking
            </h1>
        </div>

        <!-- TABS -->
        <div class="bookable-tabs">

            <a
                href="?tab=create"
                class="bookable-tab
                    <?php

                    echo $tab === 'create'
                        ? 'active'
                        : ''; ?>"
            >
                Create Bookable Facilities 
            </a>

            <a
                href="?tab=view"
                class="bookable-tab
                    <?php

                    echo $tab === 'view'
                        ? 'active'
                        : ''; ?>"
            >
                View Bookable Facilities
            </a>

            <a
                href="?tab=report"
                class="bookable-tab
                    <?php

                    echo $tab === 'report'
                        ? 'active'
                        : ''; ?>"
            >
                Facility Utilisation Report
            </a>

        </div>

        <!-- MESSAGE -->
        <?php if ($success): ?>

            <div
                class="bookable-message bookable-success"
            >
                <?php

                echo htmlspecialchars($success);
                ?>
            </div>

        <?php endif; ?>


        <?php if ($error): ?>

            <div
                class="bookable-message bookable-error"
            >
                <?php

                echo htmlspecialchars($error);
                ?>
            </div>

        <?php endif; ?>


        <!-- CREATE -->

        <?php if ($tab === 'create'): ?>

            <form
                method="POST"
                action="../controller/manageBookableFacilitiesController.php"
                class="bookable-form"
            >

                <input
                    type="hidden"
                    name="action"
                    value="createBookableFacility"
                >

                <!-- FACILITY TYPE -->

                <div class="form-group">

                    <label for="facilityType">
                        Facility Type
                    </label>

                    <select
                        id="facilityType"
                        name="facilityType"
                        onchange="filterFacilities()"
                    >

                        <option value="">
                            Select facility type
                        </option>

                        <option value="study room">
                            Study Room
                        </option>

                        <option value="gym">
                            Gym
                        </option>

                        <option value="lecture hall">
                            Lecture Hall
                        </option>

                        <option value="lab">
                            Lab
                        </option>

                    </select>

                </div>

                <!-- FACILITY NAME -->

                <div class="form-group">

                    <label for="facilityId">
                        Facility Name
                    </label>

                    <select
                        id="facilityId"
                        name="facilityId"
                        required
                    >

                        <option
                            value=""
                            data-type=""
                        >
                            Select facility
                        </option>


                        <?php

                        foreach (
                            $facilities
                            as $facility
                        ): ?>

                            <option
                                value="<?php

                                echo (int) 
                                    $facility['id'];
                                ?>"
                                data-type="<?php

                                echo htmlspecialchars(
                                    $facility['type']
                                );
                                ?>"
                            >
                                <?php

                                echo htmlspecialchars(
                                    $facility['name']
                                );
                                ?>
                                —
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
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="create-button-area">

                    <button
                        type="submit"
                        class="btn-create-bookable"
                    >
                        Create
                    </button>

                </div>

            </form>

        <!-- VIEW -->
        <?php elseif ($tab === 'view'): ?>


            <?php

            if (
                empty($bookableFacilities)
            ): ?>

                <div class="empty-message">

                    No bookable facilities found.

                </div>

            <?php else: ?>

                <div class="bookable-list">

                    <?php

                    foreach (
                        $bookableFacilities
                        as $facility
                    ): ?>

                        <div class="bookable-row">

                            <div class="bookable-main">

                                <span
                                    class="bookable-name"
                                >
                                    <?php

                                    echo htmlspecialchars(
                                        $facility['name']
                                    );
                                    ?>
                                </span>

                                <span
                                    class="bookable-details"
                                >
                                    <?php

                                    echo htmlspecialchars(
                                        $facility['type']
                                    );
                                    ?>

                                    &nbsp; | &nbsp;

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
                                </span>

                            </div>


                            <span
                                class="bookable-status
                                    <?php

                                    echo strtolower(
                                        $facility['status']
                                    )
                                        === 'available'
                                        ? 'status-available'
                                        : 'status-unavailable';
                                    ?>"
                            >
                                <?php

                                echo htmlspecialchars(
                                    $facility['status']
                                );
                                ?>
                            </span>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        <!-- REPORT -->
        <?php else: ?>

            <!-- Report Filters -->
            <form
                method="GET"
                class="report-filter-form"
            >

                <input
                    type="hidden"
                    name="tab"
                    value="report"
                >

                <div class="report-filter-group">

                    <label for="facilityType">
                        Facility Type
                    </label>

                    <select
                        id="facilityType"
                        name="facilityType"
                    >

                        <option value="">
                            All Facility Types
                        </option>

                        <option
                            value="study room"
                            <?php

                            echo $facilityType === 'study room'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Study Room
                        </option>

                        <option
                            value="gym"
                            <?php

                            echo $facilityType === 'gym'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Gym
                        </option>

                        <option
                            value="lecture hall"
                            <?php

                            echo $facilityType === 'lecture hall'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Lecture Hall
                        </option>

                        <option
                            value="lab"
                            <?php

                            echo $facilityType === 'lab'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Lab
                        </option>

                    </select>

                </div>

                <div class="report-filter-group">

                    <label for="usageType">
                        Usage Type
                    </label>

                    <select
                        id="usageType"
                        name="usageType"
                    >

                        <option value="">
                            All Usage
                        </option>

                        <option
                            value="student"
                            <?php

                            echo $usageType === 'student'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Student Booking
                        </option>

                        <option
                            value="event"
                            <?php

                            echo $usageType === 'event'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Event
                        </option>

                    </select>

                </div>


                <button
                    type="submit"
                    class="report-filter-button"
                >
                    Apply
                </button>

            </form>

            <!-- Summary Cards -->
            <div class="report-summary">

                <div class="summary-card">

                    <span class="summary-label">
                        Total Bookings
                    </span>

                    <span class="summary-value">
                        <?php

                        echo (int) (
                            $utilisationSummary['totalBookings']
                            ?? 0
                        );
                        ?>
                    </span>

                </div>

                <div class="summary-card">

                    <span class="summary-label">
                        Hours Used
                    </span>

                    <span class="summary-value">

                        <?php

                        echo number_format(
                            $utilisationSummary['totalHours']
                            ?? 0,
                            1
                        );
                        ?>

                        hrs

                    </span>

                </div>

                <div class="summary-card">

                    <span class="summary-label">
                        Average Utilisation
                    </span>

                    <span class="summary-value">

                        <?php

                        echo number_format(
                            $utilisationSummary['averageRate']
                            ?? 0,
                            0
                        );
                        ?>%

                    </span>

                </div>

                <div class="summary-card">

                    <span class="summary-label">
                        Most Utilised
                    </span>

                    <span class="summary-value summary-facility">

                        <?php

                        echo htmlspecialchars(
                            $utilisationSummary[
                                'mostUsedFacility'
                            ] ?? '-'
                        );
                        ?>

                    </span>

                </div>

            </div>

            <!-- Report Table -->
            <div class="utilisation-table-wrapper">

                <table class="utilisation-table">

                    <thead>

                        <tr>

                            <th>
                                Facility
                            </th>

                            <th>
                                Type
                            </th>

                            <th>
                                Bookings
                            </th>

                            <th>
                                Hours Used
                            </th>

                            <th>
                                Available Hours / Week
                            </th>

                            <th>
                                Utilisation
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php

                        if (
                            empty($utilisationReport)
                        ): ?>

                            <tr>

                                <td
                                    colspan="6"
                                    class="report-empty"
                                >
                                    No facility utilisation
                                    data available.
                                </td>

                            </tr>

                        <?php else: ?>

                            <?php

                            foreach (
                                $utilisationReport
                                as $row
                            ): ?>

                                <tr>

                                    <td>

                                        <strong>
                                            <?php

                                            echo htmlspecialchars(
                                                $row['facilityName']
                                            );
                                            ?>
                                        </strong>

                                        <span
                                            class="facility-location"
                                        >
                                            <?php

                                            echo htmlspecialchars(
                                                $row['location']
                                            );
                                            ?>,
                                            <?php

                                            echo htmlspecialchars(
                                                $row['blockFloor']
                                            );
                                            ?>
                                        </span>

                                    </td>


                                    <td>

                                        <?php

                                        echo htmlspecialchars(
                                            $row['type']
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <?php

                                        echo (int) 
                                            $row['totalBookings'];
                                        ?>

                                    </td>


                                    <td>

                                        <?php

                                        echo number_format(
                                            $row['hoursUsed'],
                                            1
                                        );
                                        ?>

                                        hrs

                                    </td>


                                    <td>

                                        <?php

                                        echo number_format(
                                            $row['availableHours'],
                                            1
                                        );
                                        ?>

                                        hrs

                                    </td>


                                    <td>

                                        <div class="utilisation-cell">

                                            <span class="utilisation-value">

                                                <?php

                                                echo number_format(
                                                    $row[
                                                        'utilisationRate'
                                                    ],
                                                    0
                                                );
                                                ?>%

                                            </span>


                                            <div class="utilisation-bar">

                                                <div
                                                    class="utilisation-fill"
                                                    style="width:
                                                        <?php

                                                        echo min(
                                                            100,
                                                            max(
                                                                0,
                                                                $row[
                                                                    'utilisationRate'
                                                                ]
                                                            )
                                                        );
                                                        ?>%;"
                                                ></div>

                                            </div>

                                        </div>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </div>

</main>

<footer>

    <div class="footer-bottom-bar">

        &copy; 2026 UniBee.
        All rights reserved.

    </div>

</footer>

<script src="../script.js"></script>

<script>

function filterFacilities()
{
    const typeSelect =
        document.getElementById(
            'facilityType'
        );

    const facilitySelect =
        document.getElementById(
            'facilityId'
        );

    const selectedType =
        typeSelect.value;

    const options =
        facilitySelect.querySelectorAll(
            'option'
        );


    options.forEach(
        function(option)
        {
            if (
                option.value === ''
            ) {
                option.hidden = false;
                return;
            }


            const facilityType =
                option.dataset.type;


            if (
                selectedType === '' ||
                facilityType === selectedType
            ) {

                option.hidden = false;

            } else {

                option.hidden = true;

                if (
                    option.selected
                ) {
                    facilitySelect.value = '';
                }
            }
        }
    );
}

</script>

</body>

</html>