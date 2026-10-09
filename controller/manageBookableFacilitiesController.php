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
public function createBookableFacility(
    $facilityId,
    $facilityType,
    $openTime,
    $closeTime,
    $universityId
) {
    $facilityId =
        (int) $facilityId;

    $facilityType =
        trim($facilityType);

    $openTime =
        trim($openTime);

    $closeTime =
        trim($closeTime);


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
                $closeTime
            );


    if ($created === false) {
        return
            "Unable to create the bookable facility.";
    }


    return true;
}

    // GET ALL BOOKABLE FACILITIES
    public function getBookableFacilities(
        $universityId
    ) {
        return $this->bookableFacilities
            ->getBookableFacilitiesByUniversity(
                $universityId
            );
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
        $universityId
    ) {
        return $this->bookableFacilities
            ->getFacilityUtilisationSummary(
                $universityId
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

?>