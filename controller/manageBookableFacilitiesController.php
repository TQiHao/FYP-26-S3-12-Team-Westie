<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../entity/facilities.php";
require_once "../entity/bookableFacilities.php";

class ManageBookableFacilitiesController
{
    private $facilities;
    private $bookableFacilities;


    public function __construct()
    {
        $this->facilities = new Facilities();

        $this->bookableFacilities =
            new BookableFacilities();
    }

    // GET AVAILABLE FACILITIES
    public function getAvailableFacilities($universityId)
    {
        return $this->bookableFacilities
            ->getAvailableFacilities(
                $universityId
            );
    }

    // CREATE BOOKABLE FACILITY
    public function createBookableFacility($facilityId, $facilityType, $openTime, $closeTime, $slotDuration, $universityId) 
    {
        $facilityId = (int) $facilityId;

        $facilityType = trim($facilityType);

        $openTime = trim($openTime);

        $closeTime = trim($closeTime);

        $slotDuration = trim((string) $slotDuration);

        if ($facilityId <= 0) {
            return "Please select a facility.";
        }


        if ($facilityType === '') {
            return "Please select a facility type.";
        }


        if ($openTime === '') {
            return "Please enter the opening time.";
        }


        if ($closeTime === '') {
            return "Please enter the closing time.";
        }


        if ($closeTime <= $openTime) {
            return "Closing time must be later than opening time.";
        }

        $slotDuration = trim((string) $slotDuration);

        if (
            $slotDuration === '' ||
            !ctype_digit($slotDuration) ||
            (int) $slotDuration <= 0
        ) {
            return "Slot duration must be a positive whole number.";
        }

        $slotDuration = (int) $slotDuration;

        // Get selected facility from Facilities
        $facility =
            $this->facilities
                ->getFacilityByIdAndUniversity(
                    $facilityId,
                    $universityId
                );

        if ($facility === false) {
            return "Selected facility is invalid.";
        }


        // Facility must be active
        if (
            strtolower(
                trim($facility['status'] ?? '')
            ) !== 'active'
        ) {
            return "Selected facility is not active.";
        }


        // Make sure selected type matches
        // the actual facility type
        if (
            strtolower(
                trim($facility['type'] ?? '')
            ) !==
            strtolower($facilityType)
        ) {
            return
                "The selected facility type does not "
                . "match the selected facility.";
        }


        // Check whether already bookable
        if (
            $this->bookableFacilities
                ->bookableFacilityExists(
                    $facilityId
                )
        ) {
            return
                "The selected facility is already "
                . "bookable.";
        }


        // Create record in BookableFacilities
        $created =
            $this->bookableFacilities
                ->createBookableFacility(
                    $facilityId,
                    $openTime,
                    $closeTime,
                    $slotDuration
                );

        if ($created === false) {
            return
                "Unable to create the bookable facility.";
        }


        return true;
    }

    // GET ALL BOOKABLE FACILITIES
    public function getBookableFacilities($universityId) 
    {
        return $this->bookableFacilities
            ->getBookableFacilitiesByUniversity(
                $universityId
            );
    }


    // GET ONE BOOKABLE FACILITY FOR EDITING
    public function getBookableFacilityById(
        $bookableFacilityId,
        $universityId
    ) {
        return $this->bookableFacilities
            ->getBookableFacilityByIdAndUniversity(
                (int) $bookableFacilityId,
                $universityId
            );
    }


    // EDIT BOOKABLE FACILITY
    public function editBookableFacility(
        $bookableFacilityId,
        $openTime,
        $closeTime,
        $slotDuration,
        $universityId
    ) {
        $bookableFacilityId = (int) $bookableFacilityId;
        $openTime = trim($openTime);
        $closeTime = trim($closeTime);
        $slotDuration = trim((string) $slotDuration);

        if ($bookableFacilityId <= 0) {
            return "Invalid bookable facility.";
        }

        if ($openTime === '' || $closeTime === '') {
            return "Please enter both opening and closing times.";
        }

        if ($closeTime <= $openTime) {
            return "Closing time must be later than opening time.";
        }

        if (
            $slotDuration === '' ||
            !ctype_digit($slotDuration) ||
            (int) $slotDuration <= 0
        ) {
            return "Slot duration must be a positive whole number.";
        }

        $facility = $this->getBookableFacilityById(
            $bookableFacilityId,
            $universityId
        );

        if ($facility === false) {
            return "Bookable facility not found.";
        }

        $updated = $this->bookableFacilities
            ->updateBookableFacility(
                $bookableFacilityId,
                $universityId,
                $openTime,
                $closeTime,
                (int) $slotDuration
            );

        return $updated
            ? true
            : "Unable to update the bookable facility.";
    }


    // SUSPEND OR REACTIVATE BOOKABLE FACILITY
    public function changeBookableFacilityStatus(
        $bookableFacilityId,
        $universityId,
        $status
    ) {
        if (!in_array($status, ['available', 'unavailable'], true)) {
            return "Invalid facility status.";
        }

        $facility = $this->getBookableFacilityById(
            $bookableFacilityId,
            $universityId
        );

        if ($facility === false) {
            return "Bookable facility not found.";
        }

        if (
            strtolower(trim($facility['facilityStatus'] ?? ''))
            !== 'active'
            && $status === 'available'
        ) {
            return "The physical facility is not active.";
        }

        $updated = $this->bookableFacilities
            ->updateBookableFacilityStatus(
                (int) $bookableFacilityId,
                $universityId,
                $status
            );

        return $updated
            ? true
            : "Unable to update the facility status.";
    }


    public function getFacilityUtilisationReport(
        $universityId,
        $facilityType = '',
        $usageType = ''
    ) {
        return $this->bookableFacilities
            ->getFacilityUtilisationReport(
                $universityId,
                $facilityType,
                $usageType
            );
    }

    public function getFacilityUtilisationSummary(
        $universityId,
        $facilityType = '',
        $usageType = ''
    ) {
        return $this->bookableFacilities
            ->getFacilityUtilisationSummary(
                $universityId,
                $facilityType,
                $usageType
            );
    }
}

// POST: CREATE BOOKABLE FACILITY
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'createBookableFacility'
) {

    $controller =
        new ManageBookableFacilitiesController();

    $universityId =
        $_SESSION['university_id'] ?? null;

    $facilityId =
        (int) ($_POST['facilityId'] ?? 0);


    $facilityType =
        trim(
            $_POST['facilityType'] ?? ''
        );

    $openTime =
        trim(
            $_POST['openTime'] ?? ''
        );

    $closeTime =
        trim(
            $_POST['closeTime'] ?? ''
        );

    $slotDuration = trim(
        $_POST['slotDuration'] ?? ''
    );

    if ($universityId === null) {

        $_SESSION['bookable_facility_error'] =
            "Unable to identify your university.";

    } else {

        $result =
            $controller->createBookableFacility(
                $facilityId,
                $facilityType,
                $openTime,
                $closeTime,
                $slotDuration,
                $universityId
            );


        if ($result === true) {

            $_SESSION['bookable_facility_success'] =
                "Bookable facility created successfully.";

        } else {

            $_SESSION['bookable_facility_error'] =
                $result;
        }
    }


    header(
        "Location: ../boundary/manageBookableFacilitiesPage.php?tab=create"
    );

    exit();
}


if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'editBookableFacility'
) {
    $controller = new ManageBookableFacilitiesController();
    $universityId = $_SESSION['university_id'] ?? null;

    if ($universityId === null) {
        $_SESSION['bookable_facility_error'] =
            "Unable to identify your university.";
    } else {
        $result = $controller->editBookableFacility(
            $_POST['bookableFacilityId'] ?? 0,
            $_POST['openTime'] ?? '',
            $_POST['closeTime'] ?? '',
            $_POST['slotDuration'] ?? '',
            $universityId
        );

        if ($result === true) {
            $_SESSION['bookable_facility_success'] =
                "Bookable facility updated successfully.";
        } else {
            $_SESSION['bookable_facility_error'] = $result;
        }
    }

    header("Location: ../boundary/manageBookableFacilitiesPage.php?tab=view");
    exit();
}


if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'changeBookableFacilityStatus'
) {
    $controller = new ManageBookableFacilitiesController();
    $universityId = $_SESSION['university_id'] ?? null;

    if ($universityId === null) {
        $_SESSION['bookable_facility_error'] =
            "Unable to identify your university.";
    } else {
        $result = $controller->changeBookableFacilityStatus(
            $_POST['bookableFacilityId'] ?? 0,
            $universityId,
            $_POST['status'] ?? ''
        );

        if ($result === true) {
            $_SESSION['bookable_facility_success'] =
                ($_POST['status'] === 'unavailable')
                ? "Bookable facility suspended successfully."
                : "Bookable facility reactivated successfully.";
        } else {
            $_SESSION['bookable_facility_error'] = $result;
        }
    }

    header("Location: ../boundary/manageBookableFacilitiesPage.php?tab=view");
    exit();
}


?>