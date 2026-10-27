<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../entity/event.php";
require_once "../entity/facilities.php";
require_once "../entity/bookableFacilities.php";
require_once "../entity/facilityBooking.php";

class ManageUniversityEventController
{
    private $event;
    private $facilities;
    private $bookableFacilities;
    private $facilityBooking;

    public function __construct()
    {
        $this->event = new Event();
        $this->facilities = new Facilities();
        $this->bookableFacilities = new BookableFacilities();
        $this->facilityBooking = new FacilityBooking();
    }

    public function getRecommendedFacilities(
        $universityId,
        $capacity,
        $startDatetime,
        $endDatetime
    ) {
        return $this->event
            ->getRecommendedFacilities(
                $universityId,
                $capacity,
                $startDatetime,
                $endDatetime
            );
    }

    public function createUniversityEvent(
        $data,
        $universityId,
        $createdBy,
        $posterFile = null
    ) {

        if ($universityId === null) {
            return "Unable to identify your university.";
        }

        if ($createdBy === null) {
            return "Unable to identify the current user.";
        }

        $title = trim($data['title'] ?? '');
        $description = trim($data['description'] ?? '');
        $eventInfo = trim($data['eventInfo'] ?? '');
        $facilityId = (int) ($data['facilityId'] ?? 0);
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

        if ($facilityId <= 0) {
            return "Please select a facility.";
        }

        if (!ctype_digit((string) $capacity) || (int) $capacity <= 0) {
            return "Capacity must be a positive whole number.";
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

        $startDatetime = $startDate . ' ' . $startTime . ':00';
        $endDatetime = $endDate . ' ' . $endTime . ':00';

        $start = DateTime::createFromFormat('Y-m-d H:i:s', $startDatetime);
        $end = DateTime::createFromFormat('Y-m-d H:i:s', $endDatetime);

        if ($start === false || $start->format('Y-m-d H:i:s') !== $startDatetime) {
            return "Invalid start date or time.";
        }

        if ($end === false || $end->format('Y-m-d H:i:s') !== $endDatetime) {
            return "Invalid end date or time.";
        }

        if ($end <= $start) {
            return "End date and time must be after the start date and time.";
        }

        $bookableFacility = $this->bookableFacilities
            ->getBookableFacilityByIdAndUniversity($facilityId, $universityId);

        if ($bookableFacility === false) {
            return "Selected bookable facility is invalid.";
        }

        $slotDuration = (int) ($bookableFacility['slotDuration'] ?? 0);

        if ($slotDuration > 0) {
            $durationMinutes = ($end->getTimestamp() - $start->getTimestamp()) / 60;

            if ($durationMinutes > $slotDuration) {
                return "Event duration cannot exceed " . $slotDuration . " minutes.";
            }
        }

        $openTime = $bookableFacility['openTime'] ?? null;
        $closeTime = $bookableFacility['closeTime'] ?? null;

        if (empty($openTime) || empty($closeTime)) {
            return "Facility opening hours are not configured.";
        }

        $eventStartTime = $start->format('H:i:s');
        $eventEndTime = $end->format('H:i:s');

        if ($start->format('Y-m-d') !== $end->format('Y-m-d')) {
            return "Events must start and end on the same day.";
        }

        if ($eventStartTime < $openTime || $eventEndTime > $closeTime) {
            return "Event must be within the facility opening hours.";
        }

        if (!((int) $bookableFacility['isBookable'])) {
            return "The selected facility is not bookable.";
        }

        if (strtolower(trim($bookableFacility['status'] ?? '')) !== 'available') {
            return "The selected facility is unavailable.";
        }

        $physicalFacility = $this->facilities
            ->getFacilityByIdAndUniversity($bookableFacility['facilityId'], $universityId);

        if ($physicalFacility === false) {
            return "Facility not found.";
        }

        if (strtolower(trim($physicalFacility['status'] ?? '')) !== 'active') {
            return "The selected facility is currently occupied or unavailable.";
        }

        if (
            isset($physicalFacility['capacity']) &&
            (int) $capacity > (int) $physicalFacility['capacity']
        ) {
            return "Event capacity cannot exceed the facility capacity of "
                . $physicalFacility['capacity'] . ".";
        }

        $recommended = $this->getRecommendedFacilities(
            $universityId,
            (int) $capacity,
            $startDatetime,
            $endDatetime
        );

        $facilityStillAvailable = false;

        foreach ($recommended as $recommendedFacility) {
            if ((int) $recommendedFacility['bookableFacilityId'] === $facilityId) {
                $facilityStillAvailable = true;
                break;
            }
        }

        if (!$facilityStillAvailable) {
            return "The selected facility is no longer available for the selected time.";
        }

        $posterPath = null;

        if ($posterFile !== null && !empty($posterFile['name'])) {

            if ($posterFile['error'] !== UPLOAD_ERR_OK) {
                return "Unable to upload the event poster.";
            }

            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            $extension = strtolower(pathinfo($posterFile['name'], PATHINFO_EXTENSION));

            if (!in_array($extension, $allowedExtensions, true)) {
                return "Event poster must be JPG, JPEG, PNG or WEBP.";
            }

            if ($posterFile['size'] > 5 * 1024 * 1024) {
                return "Event poster must not exceed 5 MB.";
            }
        }

        if ($this->event->eventExists($universityId, $title, $startDatetime, $endDatetime)) {
            return "An event with the same name already exists for the selected date and time.";
        }

        if ($posterFile !== null && !empty($posterFile['name'])) {

            $uploadDirectory = "../uploads/events/posters/";

            if (!is_dir($uploadDirectory)) {
                mkdir($uploadDirectory, 0775, true);
            }

            $fileName = 'event_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
            $targetPath = $uploadDirectory . $fileName;

            if (!move_uploaded_file($posterFile['tmp_name'], $targetPath)) {
                return "Unable to save the event poster.";
            }

            $posterPath = "uploads/events/posters/" . $fileName;
        }

        $eventId = $this->event->createUniversityEvent(
            $universityId,
            $createdBy,
            $facilityId,
            $title,
            $description,
            $startDatetime,
            $endDatetime,
            (int) $capacity,
            $posterPath,
            $eventInfo
        );

        if ($eventId === false) {

            if ($posterPath !== null) {
                $fullPosterPath = "../" . $posterPath;
                if (file_exists($fullPosterPath)) {
                    unlink($fullPosterPath);
                }
            }

            return "Unable to create the university event.";
        }

        $facilityBookingId = $this->facilityBooking->createEventBooking(
            $facilityId,
            $createdBy,
            $startDate,
            $startTime . ':00',
            $endTime . ':00',
            'Event: ' . $title
        );

        if ($facilityBookingId === false) {
            return "Event was created, but the facility booking could not be created.";
        }

        $facilityUpdated = $this->facilities->updateFacilityStatus(
            $bookableFacility['facilityId'],
            'occupied'
        );

        if ($facilityUpdated === false) {
            return "Event was created, but the facility status could not be updated.";
        }

        return true;
    }

    public function getUniversityEvents($universityId, $keyword = '')
    {
        $this->updateCompletedEvents($universityId);

        if ($keyword === '') {
            return $this->event->getAllEvents($universityId);
        }

        return $this->event->searchAllEvents($universityId, $keyword);
    }

    public function updateUniversityEvent($data, $universityId, $posterFile = null)
    {
        $eventId = (int) ($data['eventId'] ?? 0);

        if ($eventId <= 0) {
            return "Invalid event.";
        }

        $existingEvent = $this->event->getEventByIdAndUniversity($eventId, $universityId);

        if ($existingEvent === false) {
            return "Event not found.";
        }

        if (strtolower($existingEvent['status'] ?? '') !== 'active') {
            return "Only active events can be updated.";
        }

        $title = trim($data['title'] ?? '');
        $description = trim($data['description'] ?? '');
        $eventInfo = trim($data['eventInfo'] ?? '');
        $facilityId = (int) ($data['facilityId'] ?? $existingEvent['facilityId']);
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

        if (!ctype_digit((string) $capacity) || (int) $capacity <= 0) {
            return "Capacity must be a positive whole number.";
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

        $startDatetime = $startDate . ' ' . $startTime . ':00';
        $endDatetime = $endDate . ' ' . $endTime . ':00';

        $start = DateTime::createFromFormat('Y-m-d H:i:s', $startDatetime);
        $end = DateTime::createFromFormat('Y-m-d H:i:s', $endDatetime);

        if ($start === false || $end === false) {
            return "Invalid date or time.";
        }

        if ($end <= $start) {
            return "End date and time must be after the start date and time.";
        }

        $bookableFacility = $this->bookableFacilities
            ->getBookableFacilityByIdAndUniversity($facilityId, $universityId);

        if ($bookableFacility === false) {
            return "Selected bookable facility is invalid.";
        }

        $openTime = $bookableFacility['openTime'] ?? null;
        $closeTime = $bookableFacility['closeTime'] ?? null;
        $slotDuration = $bookableFacility['slotDuration'] ?? null;

        if (empty($openTime) || empty($closeTime)) {
            return "Facility opening hours are not configured.";
        }

        if ($start->format('Y-m-d') !== $end->format('Y-m-d')) {
            return "Events must start and end on the same day.";
        }

        $eventStartTime = $start->format('H:i:s');
        $eventEndTime = $end->format('H:i:s');

        if ($eventStartTime < $openTime || $eventEndTime > $closeTime) {
            return "Event must be within the facility opening hours.";
        }

        if ($slotDuration !== null && (int) $slotDuration > 0) {
            $durationMinutes = ($end->getTimestamp() - $start->getTimestamp()) / 60;

            if ($durationMinutes > (int) $slotDuration) {
                return "Event duration cannot exceed " . (int) $slotDuration . " minutes.";
            }
        }

        if (!(int) $bookableFacility['isBookable']) {
            return "The selected facility is not bookable.";
        }

        if (
            strtolower(trim($bookableFacility['status'] ?? '')) !== 'available' &&
            $facilityId !== (int) $existingEvent['facilityId']
        ) {
            return "The selected facility is unavailable.";
        }

        $physicalFacility = $this->facilities
            ->getFacilityByIdAndUniversity($bookableFacility['facilityId'], $universityId);

        if ($physicalFacility === false) {
            return "Facility not found.";
        }

        if ($facilityId !== (int) $existingEvent['facilityId']) {
            if (strtolower(trim($physicalFacility['status'] ?? '')) !== 'active') {
                return "The selected facility is currently occupied or unavailable.";
            }
        }

        if (
            isset($physicalFacility['capacity']) &&
            (int) $capacity > (int) $physicalFacility['capacity']
        ) {
            return "Event capacity cannot exceed the facility capacity of "
                . $physicalFacility['capacity'] . ".";
        }

        $recommended = $this->event->getRecommendedFacilities(
            $universityId,
            (int) $capacity,
            $startDatetime,
            $endDatetime,
            $eventId
        );

        $facilityStillAvailable = false;

        foreach ($recommended as $recommendedFacility) {
            if ((int) $recommendedFacility['bookableFacilityId'] === $facilityId) {
                $facilityStillAvailable = true;
                break;
            }
        }

        if (!$facilityStillAvailable) {
            return "The selected facility is no longer available for the selected time.";
        }

        $oldFacilityId = (int) $existingEvent['facilityId'];

        $updated = $this->event->updateUniversityEvent(
            $eventId,
            $universityId,
            $facilityId,
            $title,
            $description,
            $startDatetime,
            $endDatetime,
            (int) $capacity,
            $eventInfo
        );

        if ($updated === false) {
            return "Unable to update the event.";
        }

        if ($facilityId !== $oldFacilityId) {

            $oldBookableFacility = $this->bookableFacilities
                ->getBookableFacilityByIdAndUniversity($oldFacilityId, $universityId);

            if ($oldBookableFacility !== false) {
                $this->facilities->updateFacilityStatus(
                    $oldBookableFacility['facilityId'],
                    'active'
                );
            }

            $this->facilities->updateFacilityStatus(
                $bookableFacility['facilityId'],
                'occupied'
            );
        }

        if ($posterFile !== null && !empty($posterFile['name'])) {

            if ($posterFile['error'] !== UPLOAD_ERR_OK) {
                return "Unable to upload the event poster.";
            }

            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            $extension = strtolower(pathinfo($posterFile['name'], PATHINFO_EXTENSION));

            if (!in_array($extension, $allowedExtensions, true)) {
                return "Event poster must be JPG, JPEG, PNG or WEBP.";
            }

            $uploadDirectory = "../uploads/events/posters/";

            if (!is_dir($uploadDirectory)) {
                mkdir($uploadDirectory, 0775, true);
            }

            $fileName = 'event_' . $eventId . '_' . time() . '.' . $extension;
            $targetPath = $uploadDirectory . $fileName;

            if (!move_uploaded_file($posterFile['tmp_name'], $targetPath)) {
                return "Unable to save the event poster.";
            }

            $posterPath = "uploads/events/posters/" . $fileName;

            $this->event->updateEventPoster($eventId, $universityId, $posterPath);
        }

        return true;
    }

    private function validateFacilityBookingTiming(
        array $facility,
        DateTime $start,
        DateTime $end
    ): ?string {

        $openTime = $facility['openTime'] ?? null;
        $closeTime = $facility['closeTime'] ?? null;
        $slotDuration = $facility['slotDuration'] ?? null;

        if (empty($openTime) || empty($closeTime)) {
            return "Facility opening hours are not configured.";
        }

        if ($end <= $start) {
            return "End time must be after start time.";
        }

        if ($start->format('Y-m-d') !== $end->format('Y-m-d')) {
            return "Events must start and end on the same day.";
        }

        $startTime = $start->format('H:i:s');
        $endTime = $end->format('H:i:s');

        if ($startTime < $openTime || $endTime > $closeTime) {
            return "Event must be within the facility opening hours.";
        }

        if ($slotDuration !== null && (int) $slotDuration > 0) {
            $durationMinutes = ($end->getTimestamp() - $start->getTimestamp()) / 60;

            if ($durationMinutes > (int) $slotDuration) {
                return "Event duration cannot exceed " . (int) $slotDuration . " minutes.";
            }
        }

        return null;
    }

    public function suspendUniversityEvent($eventId, $universityId)
    {
        $event = $this->event->getEventByIdAndUniversity($eventId, $universityId);

        if ($event === false) {
            return "Event not found.";
        }

        if (strtolower($event['status'] ?? '') !== 'active') {
            return "This event is no longer active.";
        }

        $suspended = $this->event->suspendUniversityEvent($eventId, $universityId);

        if ($suspended === false) {
            return "Unable to suspend the event.";
        }

        $bookableFacility = $this->bookableFacilities
            ->getBookableFacilityByIdAndUniversity($event['facilityId'], $universityId);

        if ($bookableFacility !== false) {
            $this->facilities->updateFacilityStatus(
                $bookableFacility['facilityId'],
                'active'
            );
        }

        return true;
    }

    public function updateCompletedEvents($universityId)
    {
        $completedEvents = $this->event->completePastEvents($universityId);

        if (empty($completedEvents)) {
            return;
        }

        foreach ($completedEvents as $event) {

            if (!isset($event['facilityId'])) {
                continue;
            }

            $bookableFacility = $this->bookableFacilities
                ->getBookableFacilityByIdAndUniversity($event['facilityId'], $universityId);

            if ($bookableFacility !== false) {
                $this->facilities->updateFacilityStatus(
                    $bookableFacility['facilityId'],
                    'active'
                );
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'createUniversityEvent') {

    $controller = new ManageUniversityEventController();

    $universityId = $_SESSION['university_id'] ?? null;
    $createdBy = $_SESSION['user_id'] ?? null;
    $posterFile = $_FILES['eventPoster'] ?? null;

    $result = $controller->createUniversityEvent($_POST, $universityId, $createdBy, $posterFile);

    if ($result === true) {
        unset($_SESSION['event_old_input']);
        $_SESSION['event_success'] = "Event created successfully.";
        header("Location: ../boundary/manageUniversityEventPage.php?tab=view");
        exit();
    }

    $_SESSION['event_error'] = $result;
    $_SESSION['event_old_input'] = $_POST;

    header("Location: ../boundary/manageUniversityEventPage.php?tab=create");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'updateUniversityEvent') {

    $controller = new ManageUniversityEventController();

    $universityId = $_SESSION['university_id'] ?? null;
    $posterFile = $_FILES['eventPoster'] ?? null;

    $result = $controller->updateUniversityEvent($_POST, $universityId, $posterFile);

    if ($result === true) {
        $_SESSION['event_success'] = "University Event updated successfully.";
    } else {
        $_SESSION['event_error'] = $result;
    }

    header("Location: ../boundary/manageUniversityEventPage.php?tab=view");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'suspendUniversityEvent') {

    $controller = new ManageUniversityEventController();

    $universityId = $_SESSION['university_id'] ?? null;
    $eventId = (int) ($_POST['eventId'] ?? 0);

    $result = $controller->suspendUniversityEvent($eventId, $universityId);

    if ($result === true) {
        $_SESSION['event_success'] = "University Event has been suspended.";
    } else {
        $_SESSION['event_error'] = $result;
    }

    header("Location: ../boundary/manageUniversityEventPage.php?tab=view");
    exit();
}

?>