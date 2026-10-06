<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../entity/event.php";
require_once "../entity/facilities.php";

class ManageUniversityEventController
{
    private $event;
    private $facilities;


    public function __construct()
    {
        $this->event = new Event();
        $this->facilities = new Facilities();
    }

    // Get facilities available for events
    public function getAvailableFacilities(
        $universityId
    ) {

        $facilities =
            $this->facilities
                ->getFacilitiesByUniversity(
                    $universityId
                );

        $availableFacilities = [];

        foreach ($facilities as $facility) {

            // Only active facilities
            if (
                strtolower(
                    $facility['status'] ?? ''
                ) !== 'active'
            ) {
                continue;
            }

            $eventLocation =
                $facility['name']
                . ' — '
                . $facility['location']
                . ', '
                . $facility['blockFloor'];

            if (
                $this->event
                    ->locationExistsInActiveEvent(
                        $universityId,
                        $eventLocation
                    )
            ) {
                continue;
            }

            $facility['eventLocation'] =
                $eventLocation;

            $availableFacilities[] =
                $facility;
        }


        return $availableFacilities;
    }

    // Create University Event
    public function createUniversityEvent(
        $data,
        $universityId,
        $createdBy
    ) {

        if ($universityId === null) {
            return "Unable to identify your university.";
        }


        if ($createdBy === null) {
            return "Unable to identify the current user.";
        }

        // Form values
        $title =
            trim($data['title'] ?? '');

        $description =
            trim($data['description'] ?? '');

        $facilityId =
            (int) ($data['facilityId'] ?? 0);

        $capacity =
            trim($data['capacity'] ?? '');

        $startDate =
            trim($data['startDate'] ?? '');

        $startTime =
            trim($data['startTime'] ?? '');

        $endDate =
            trim($data['endDate'] ?? '');

        $endTime =
            trim($data['endTime'] ?? '');

        // Required fields
        if ($title === '') {
            return "Event title is required.";
        }

        if ($description === '') {
            return "Event description is required.";
        }

        if ($facilityId <= 0) {
            return "Please select a facility.";
        }

        if ($capacity === '') {
            return "Capacity is required.";
        }

        if ($startDate === '') {
            return "Start date is required.";
        }

        if ($startTime === '') {
            return "Start time is required.";
        }

        if ($endDate === '') {
            return "End date is required.";
        }

        if ($endTime === '') {
            return "End time is required.";
        }

        // Get selected facility
        $facility =
            $this->facilities
                ->getFacilityByIdAndUniversity(
                    $facilityId,
                    $universityId
                );

        if ($facility === false) {
            return "Selected facility is invalid.";
        }

        // Facility must still be active
        if (
            strtolower(
                $facility['status'] ?? ''
            ) !== 'active'
        ) {
            return
                "Selected facility is currently occupied "
                . "or unavailable.";
        }

        // Build event location
        $location =
            $facility['name']
            . ' — '
            . $facility['location']
            . ', '
            . $facility['blockFloor'];

        // Check location is not already occupied
        if (
            $this->event
                ->locationExistsInActiveEvent(
                    $universityId,
                    $location
                )
        ) {

            return
                "The selected facility is already being "
                . "used by an active event.";
        }

        // Capacity validation
        if (
            !ctype_digit((string) $capacity) ||
            (int) $capacity <= 0
        ) {
            return
                "Capacity must be a positive whole number.";
        }


        if (
            isset($facility['capacity']) &&
            (int) $capacity >
            (int) $facility['capacity']
        ) {
            return
                "Event capacity cannot exceed the "
                . "facility capacity of "
                . $facility['capacity'] . ".";
        }

        // Date/time
        $startDatetime =
            $startDate . ' ' . $startTime . ':00';

        $endDatetime =
            $endDate . ' ' . $endTime . ':00';


        $start =
            DateTime::createFromFormat(
                'Y-m-d H:i:s',
                $startDatetime
            );

        $end =
            DateTime::createFromFormat(
                'Y-m-d H:i:s',
                $endDatetime
            );

        if (
            $start === false ||
            $start->format('Y-m-d H:i:s')
            !== $startDatetime
        ) {
            return "Invalid start date or time.";
        }

        if (
            $end === false ||
            $end->format('Y-m-d H:i:s')
            !== $endDatetime
        ) {
            return "Invalid end date or time.";
        }

        if ($end <= $start) {

            return
                "End date and time must be after "
                . "the start date and time.";
        }

        // Check duplicate event
        if (
            $this->event->eventExists(
                $universityId,
                $title,
                $startDatetime,
                $endDatetime
            )
        ) {

            return
                "An event with the same name already "
                . "exists for the selected date and time.";
        }

        // Create event
        $eventId =
            $this->event
                ->createUniversityEvent(
                    $universityId,
                    $createdBy,
                    $title,
                    $description,
                    $location,
                    $startDatetime,
                    $endDatetime,
                    (int) $capacity
                );

        if ($eventId === false) {
            return
                "Unable to create the university event.";
        }

        // Mark facility as occupied
        $facilityUpdated =
            $this->facilities
                ->updateFacilityStatus(
                    $facilityId,
                    'occupied'
                );

        if ($facilityUpdated === false) {

            return
                "Event was created, but the facility "
                . "status could not be updated.";
        }

        return true;
    }

    public function getUniversityEvents(
        $universityId,
        $keyword = ''
    ) {
        // Automatically complete events that have ended
        $this->updateCompletedEvents($universityId);

        if ($keyword === '') {

            return $this->event->getAllEvents($universityId);
        }

        return $this->event->searchAllEvents($universityId, $keyword);
    }

    public function getUniversityEvent(
        $eventId,
        $universityId
    ) {
        $this->updateCompletedEvents($universityId);

        return $this->event
            ->getEventByIdAndUniversity(
                $eventId,
                $universityId
            );
    }

    public function updateUniversityEvent(
        $data,
        $universityId
    ) {

        $eventId =
            (int) ($data['eventId'] ?? 0);

        if ($eventId <= 0) {
            return "Invalid event.";
        }


        $existingEvent =
            $this->event
                ->getEventByIdAndUniversity(
                    $eventId,
                    $universityId
                );

        if ($existingEvent === false) {
            return "Event not found.";
        }


        if (
            strtolower(
                $existingEvent['status']
            ) !== 'active'
        ) {
            return
                "Only active events can be updated.";
        }

        $title = trim($data['title'] ?? '');
        $description = trim($data['description'] ?? '');
        $location = trim($data['location'] ?? '');
        $capacity = trim($data['capacity'] ?? '');
        $startDate = trim($data['startDate'] ?? '');
        $startTime = trim($data['startTime'] ?? '');
        $endDate = trim($data['endDate'] ?? '');
        $endTime = trim($data['endTime'] ?? '');


        if ($title === '') {
            return "Event title is required.";
        }

        if ($description === '') {
            return "Event description is required.";
        }

        if ($location === '') {
            return "Location is required.";
        }

        if (
            !ctype_digit((string) $capacity) ||
            (int) $capacity <= 0
        ) {
            return "Capacity must be a positive whole number.";
        }

        $startDatetime = $startDate . ' ' . $startTime . ':00';

        $endDatetime = $endDate . ' ' . $endTime . ':00';

        $start =
            DateTime::createFromFormat(
                'Y-m-d H:i:s',
                $startDatetime
            );

        $end =
            DateTime::createFromFormat(
                'Y-m-d H:i:s',
                $endDatetime
            );

        if (
            $start === false ||
            $end === false
        ) {
            return "Invalid date or time.";
        }

        if ($end <= $start) {
            return
                "End date and time must be after "
                . "the start date and time.";
        }

        $updated =
            $this->event
                ->updateUniversityEvent(
                    $eventId,
                    $universityId,
                    $title,
                    $description,
                    $location,
                    $startDatetime,
                    $endDatetime,
                    (int) $capacity
                );

        if ($updated === false) {
            return "Unable to update the event.";
        }

        return true;
    }

    public function suspendUniversityEvent(
        $eventId,
        $universityId
    ) {

        $event =
            $this->event
                ->getEventByIdAndUniversity(
                    $eventId,
                    $universityId
                );

        if ($event === false) {
            return "Event not found.";
        }


        if (
            strtolower(
                $event['status']
            ) !== 'active'
        ) {
            return
                "This event is no longer active.";
        }


        $suspended =
            $this->event
                ->suspendUniversityEvent(
                    $eventId,
                    $universityId
                );


        if ($suspended === false) {
            return "Unable to suspend the event.";
        }


        // Release the facility
        $this->facilities
            ->releaseFacilityByEventLocation(
                $universityId,
                $event['location']
            );


        return true;
    }

    public function updateCompletedEvents($universityId)
    {
        $this->event->completePastEvents($universityId);

        $this->facilities->releaseFacilitiesForCompletedEvents($universityId);
    }
}

// Handle Create University Event POST
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'createUniversityEvent'
) {

    $controller =
        new ManageUniversityEventController();


    $universityId =
        $_SESSION['university_id'] ?? null;

    $createdBy =
        $_SESSION['user_id'] ?? null;


    $result =
        $controller->createUniversityEvent(
            $_POST,
            $universityId,
            $createdBy
        );

    // Success
    if ($result === true) {

        unset($_SESSION['event_old_input']);

        $_SESSION['event_success'] =
            "Event created successfully.";

        header(
            "Location: ../boundary/CreateUniversityEventPage.php"
        );

        exit();
    }

    // Error
    $_SESSION['event_error'] = $result;

    // Keep the user's input
    $_SESSION['event_old_input'] = $_POST;

    header(
        "Location: ../boundary/CreateUniversityEventPage.php"
    );

    exit();
}

// =========================================================
// Handle Update University Event POST
// =========================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'updateUniversityEvent'
) {

    $controller = new ManageUniversityEventController();

    $universityId = $_SESSION['university_id'] ?? null;


    $result =
        $controller->updateUniversityEvent(
            $_POST,
            $universityId
        );

    // Success
    if ($result === true) {

        $_SESSION['event_update_success'] =
            "University Event has been updated successfully.";

        header(
            "Location: ../boundary/updateUniversityEventPage.php?id="
            . urlencode($_POST['eventId'])
        );

        exit();
    }

    // Error
    $_SESSION['event_update_error'] =
        $result;

    header(
        "Location: ../boundary/updateUniversityEventPage.php?id="
        . urlencode($_POST['eventId'])
    );

    exit();
}

// =========================================================
// Handle Suspend University Event POST
// =========================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'suspendUniversityEvent'
) {

    $controller =
        new ManageUniversityEventController();

    $universityId =
        $_SESSION['university_id'] ?? null;

    $eventId =
        (int) ($_POST['eventId'] ?? 0);

    $result =
        $controller->suspendUniversityEvent(
            $eventId,
            $universityId
        );

    if ($result === true) {

        $_SESSION['event_suspend_success'] =
            "University Event has been Suspended";

        header(
            "Location: ../boundary/updateUniversityEventPage.php?id="
            . urlencode($eventId)
        );

        exit();
    }

    $_SESSION['event_suspend_error'] =
        $result ?: "Unable to suspend university event.";

    header(
        "Location: ../boundary/updateUniversityEventPage.php?id="
        . urlencode($eventId)
    );

    exit();
}