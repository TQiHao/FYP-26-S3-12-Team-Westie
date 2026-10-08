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


    // =====================================================
    // GET RECOMMENDED FACILITIES
    // =====================================================

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


    // =====================================================
    // CREATE UNIVERSITY EVENT
    // =====================================================

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


        // -------------------------------------------------
        // Get form data
        // -------------------------------------------------

        $title =
            trim(
                $data['title'] ?? ''
            );


        $description =
            trim(
                $data['description'] ?? ''
            );


        $eventInfo =
            trim(
                $data['eventInfo'] ?? ''
            );


        $facilityId =
            (int) (
                $data['facilityId'] ?? 0
            );


        $capacity =
            trim(
                $data['capacity'] ?? ''
            );


        $startDate =
            trim(
                $data['startDate'] ?? ''
            );


        $startTime =
            trim(
                $data['startTime'] ?? ''
            );


        $endDate =
            trim(
                $data['endDate'] ?? ''
            );


        $endTime =
            trim(
                $data['endTime'] ?? ''
            );


        // -------------------------------------------------
        // Validate required fields
        // -------------------------------------------------

        if ($title === '') {
            return "Event title is required.";
        }


        if ($description === '') {
            return "Event description is required.";
        }


        if ($facilityId <= 0) {
            return "Please select a facility.";
        }


        if (
            !ctype_digit(
                (string) $capacity
            ) ||
            (int) $capacity <= 0
        ) {
            return
                "Capacity must be a positive whole number.";
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


        // -------------------------------------------------
        // Build datetime
        // -------------------------------------------------

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


        // -------------------------------------------------
        // Make sure the selected BookableFacilities
        // record exists and belongs to this university.
        // -------------------------------------------------

        $bookableFacility =
            $this->bookableFacilities
                ->getBookableFacilityByIdAndUniversity(
                    $facilityId,
                    $universityId
                );


        if ($bookableFacility === false) {
            return
                "Selected bookable facility is invalid.";
        }


        // Must actually be bookable
        if (
            !(
                (int) $bookableFacility['isBookable']
            )
        ) {
            return
                "The selected facility is not bookable.";
        }


        // BookableFacilities must be available
        if (
            strtolower(
                trim(
                    $bookableFacility['status'] ?? ''
                )
            ) !== 'available'
        ) {
            return
                "The selected facility is unavailable.";
        }


        // -------------------------------------------------
        // Get the physical Facility
        // -------------------------------------------------

        $physicalFacility =
            $this->facilities
                ->getFacilityByIdAndUniversity(
                    $bookableFacility['facilityId'],
                    $universityId
                );


        if ($physicalFacility === false) {
            return "Facility not found.";
        }


        // Physical facility must be active
        if (
            strtolower(
                trim(
                    $physicalFacility['status'] ?? ''
                )
            ) !== 'active'
        ) {
            return
                "The selected facility is currently occupied "
                . "or unavailable.";
        }


        // -------------------------------------------------
        // Capacity
        // -------------------------------------------------

        if (
            isset(
            $physicalFacility['capacity']
        ) &&
            (int) $capacity >
            (int) $physicalFacility['capacity']
        ) {
            return
                "Event capacity cannot exceed the "
                . "facility capacity of "
                . $physicalFacility['capacity']
                . ".";
        }


        // -------------------------------------------------
        // Final availability check
        //
        // This repeats the check at submission time
        // to prevent somebody else from taking the
        // facility between recommendation and creation.
        // -------------------------------------------------

        $recommended =
            $this->getRecommendedFacilities(
                $universityId,
                (int) $capacity,
                $startDatetime,
                $endDatetime
            );


        $facilityStillAvailable = false;


        foreach (
            $recommended as $recommendedFacility
        ) {

            if (
                (int) 
                $recommendedFacility[
                    'bookableFacilityId'
                ]
                === $facilityId
            ) {

                $facilityStillAvailable = true;

                break;
            }
        }


        if (!$facilityStillAvailable) {
            return
                "The selected facility is no longer "
                . "available for the selected time.";
        }


        // -------------------------------------------------
        // Validate poster
        // -------------------------------------------------

        $posterPath = null;


        if (
            $posterFile !== null &&
            !empty(
            $posterFile['name']
        )
        ) {

            if (
                $posterFile['error']
                !== UPLOAD_ERR_OK
            ) {
                return
                    "Unable to upload the event poster.";
            }


            $allowedExtensions = [
                'jpg',
                'jpeg',
                'png',
                'webp'
            ];


            $extension =
                strtolower(
                    pathinfo(
                        $posterFile['name'],
                        PATHINFO_EXTENSION
                    )
                );


            if (
                !in_array(
                    $extension,
                    $allowedExtensions,
                    true
                )
            ) {
                return
                    "Event poster must be JPG, JPEG, PNG "
                    . "or WEBP.";
            }


            if (
                $posterFile['size']
                > 5 * 1024 * 1024
            ) {
                return
                    "Event poster must not exceed 5 MB.";
            }
        }


        // -------------------------------------------------
        // Check duplicate event
        // -------------------------------------------------

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


        // -------------------------------------------------
        // Upload poster
        // -------------------------------------------------

        if (
            $posterFile !== null &&
            !empty(
            $posterFile['name']
        )
        ) {

            $uploadDirectory =
                "../uploads/events/posters/";


            if (
                !is_dir(
                    $uploadDirectory
                )
            ) {

                mkdir(
                    $uploadDirectory,
                    0775,
                    true
                );
            }


            $fileName =
                'event_'
                . time()
                . '_'
                . bin2hex(
                    random_bytes(4)
                )
                . '.'
                . $extension;


            $targetPath =
                $uploadDirectory
                . $fileName;


            if (
                !move_uploaded_file(
                    $posterFile['tmp_name'],
                    $targetPath
                )
            ) {
                return
                    "Unable to save the event poster.";
            }


            $posterPath =
                "uploads/events/posters/"
                . $fileName;
        }


        // -------------------------------------------------
        // Create event
        // -------------------------------------------------

        $eventId =
            $this->event
                ->createUniversityEvent(
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

            // Remove uploaded poster if event creation failed
            if (
                $posterPath !== null
            ) {

                $fullPosterPath =
                    "../"
                    . $posterPath;

                if (
                    file_exists(
                        $fullPosterPath
                    )
                ) {

                    unlink(
                        $fullPosterPath
                    );
                }
            }


            return
                "Unable to create the university event.";
        }

        // Create FacilityBooking for the event
        $facilityBookingId =
            $this->facilityBooking
                ->createEventBooking(
                    $facilityId,
                    $createdBy,
                    $startDate,
                    $startTime . ':00',
                    $endTime . ':00',
                    'Event: ' . $title
                );


        if ($facilityBookingId === false) {

            return
                "Event was created, but the "
                . "facility booking could not be created.";
        }

        // Mark physical Facility as occupied
        $facilityUpdated =
            $this->facilities
                ->updateFacilityStatus(
                    $bookableFacility[
                        'facilityId'
                    ],
                    'occupied'
                );


        if (
            $facilityUpdated === false
        ) {

            return
                "Event was created, but the facility "
                . "status could not be updated.";
        }


        return true;
    }

    // =====================================================
    // GET UNIVERSITY EVENTS
    // =====================================================

    public function getUniversityEvents(
        $universityId,
        $keyword = ''
    ) {

        // Automatically complete events whose
        // end time has passed.
        $this->updateCompletedEvents(
            $universityId
        );


        if (
            $keyword === ''
        ) {

            return $this->event
                ->getAllEvents(
                    $universityId
                );
        }


        return $this->event
            ->searchAllEvents(
                $universityId,
                $keyword
            );
    }


    // =====================================================
    // UPDATE EVENT
    // =====================================================

    public function updateUniversityEvent(
        $data,
        $universityId,
        $posterFile = null
    ) {

        $eventId =
            (int) (
                $data['eventId'] ?? 0
            );


        if ($eventId <= 0) {
            return "Invalid event.";
        }


        $existingEvent =
            $this->event
                ->getEventByIdAndUniversity(
                    $eventId,
                    $universityId
                );


        if (
            $existingEvent === false
        ) {
            return "Event not found.";
        }


        if (
            strtolower(
                $existingEvent['status']
                ?? ''
            ) !== 'active'
        ) {
            return
                "Only active events can be updated.";
        }


        // -------------------------------------------------
        // Form data
        // -------------------------------------------------

        $title =
            trim(
                $data['title'] ?? ''
            );


        $description =
            trim(
                $data['description'] ?? ''
            );


        $eventInfo =
            trim(
                $data['eventInfo'] ?? ''
            );


        $facilityId =
            (int) (
                $data['facilityId']
                ?? $existingEvent['facilityId']
            );


        $capacity =
            trim(
                $data['capacity'] ?? ''
            );


        $startDate =
            trim(
                $data['startDate'] ?? ''
            );


        $startTime =
            trim(
                $data['startTime'] ?? ''
            );


        $endDate =
            trim(
                $data['endDate'] ?? ''
            );


        $endTime =
            trim(
                $data['endTime'] ?? ''
            );


        // -------------------------------------------------
        // Required fields
        // -------------------------------------------------

        if ($title === '') {
            return "Event title is required.";
        }


        if ($description === '') {
            return "Event description is required.";
        }


        if (
            !ctype_digit(
                (string) $capacity
            ) ||
            (int) $capacity <= 0
        ) {
            return
                "Capacity must be a positive whole number.";
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


        // -------------------------------------------------
        // Build datetime
        // -------------------------------------------------

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


        // -------------------------------------------------
        // Validate selected BookableFacilities
        // -------------------------------------------------

        $bookableFacility =
            $this->bookableFacilities
                ->getBookableFacilityByIdAndUniversity(
                    $facilityId,
                    $universityId
                );


        if (
            $bookableFacility === false
        ) {
            return
                "Selected bookable facility is invalid.";
        }


        if (
            !(int) 
            $bookableFacility['isBookable']
        ) {
            return
                "The selected facility is not bookable.";
        }


        if (
            strtolower(
                trim(
                    $bookableFacility['status']
                    ?? ''
                )
            ) !== 'available'
            &&
            $facilityId !==
            (int) $existingEvent[
                'facilityId'
            ]
        ) {
            return
                "The selected facility is unavailable.";
        }


        // -------------------------------------------------
        // Physical facility
        // -------------------------------------------------

        $physicalFacility =
            $this->facilities
                ->getFacilityByIdAndUniversity(
                    $bookableFacility[
                        'facilityId'
                    ],
                    $universityId
                );


        if (
            $physicalFacility === false
        ) {
            return "Facility not found.";
        }


        if (
            $facilityId !==
            (int) $existingEvent[
                'facilityId'
            ]
        ) {

            if (
                strtolower(
                    trim(
                        $physicalFacility[
                            'status'
                        ] ?? ''
                    )
                ) !== 'active'
            ) {
                return
                    "The selected facility is currently "
                    . "occupied or unavailable.";
            }
        }


        // -------------------------------------------------
        // Capacity
        // -------------------------------------------------

        if (
            isset(
            $physicalFacility[
                'capacity'
            ]
        ) &&
            (int) $capacity >
            (int) $physicalFacility[
                'capacity'
            ]
        ) {
            return
                "Event capacity cannot exceed the "
                . "facility capacity of "
                . $physicalFacility[
                    'capacity'
                ]
                . ".";
        }


        // -------------------------------------------------
        // Final availability check
        // -------------------------------------------------

        $recommended =
            $this->getRecommendedFacilities(
                $universityId,
                (int) $capacity,
                $startDatetime,
                $endDatetime
            );


        $facilityStillAvailable = false;


        foreach (
            $recommended as $recommendedFacility
        ) {

            if (
                (int) 
                $recommendedFacility[
                    'bookableFacilityId'
                ]
                === $facilityId
            ) {

                $facilityStillAvailable =
                    true;

                break;
            }
        }


        /*
         * If the admin is keeping the same facility,
         * the current event should not reject itself.
         */
        if (
            $facilityId ===
            (int) $existingEvent[
                'facilityId'
            ]
        ) {

            $facilityStillAvailable = true;
        }


        if (
            !$facilityStillAvailable
        ) {
            return
                "The selected facility is no longer "
                . "available for the selected time.";
        }


        // -------------------------------------------------
        // Update event
        // -------------------------------------------------

        $oldFacilityId =
            (int) $existingEvent[
                'facilityId'
            ];


        $updated =
            $this->event
                ->updateUniversityEvent(
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


        if (
            $updated === false
        ) {
            return "Unable to update the event.";
        }


        // -------------------------------------------------
        // If facility changed:
        //
        // Old facility → active
        // New facility → occupied
        // -------------------------------------------------

        if (
            $facilityId !==
            $oldFacilityId
        ) {

            $oldBookableFacility =
                $this->bookableFacilities
                    ->getBookableFacilityByIdAndUniversity(
                        $oldFacilityId,
                        $universityId
                    );


            if (
                $oldBookableFacility !== false
            ) {

                $this->facilities
                    ->updateFacilityStatus(
                        $oldBookableFacility[
                            'facilityId'
                        ],
                        'active'
                    );
            }


            $this->facilities
                ->updateFacilityStatus(
                    $bookableFacility[
                        'facilityId'
                    ],
                    'occupied'
                );
        }


        // -------------------------------------------------
        // Optional new poster
        // -------------------------------------------------

        if (
            $posterFile !== null &&
            !empty(
            $posterFile['name']
        )
        ) {

            if (
                $posterFile['error']
                !== UPLOAD_ERR_OK
            ) {
                return
                    "Unable to upload the event poster.";
            }


            $allowedExtensions = [
                'jpg',
                'jpeg',
                'png',
                'webp'
            ];


            $extension =
                strtolower(
                    pathinfo(
                        $posterFile['name'],
                        PATHINFO_EXTENSION
                    )
                );


            if (
                !in_array(
                    $extension,
                    $allowedExtensions,
                    true
                )
            ) {
                return
                    "Event poster must be JPG, JPEG, PNG "
                    . "or WEBP.";
            }


            $uploadDirectory =
                "../uploads/events/posters/";


            if (
                !is_dir(
                    $uploadDirectory
                )
            ) {

                mkdir(
                    $uploadDirectory,
                    0775,
                    true
                );
            }


            $fileName =
                'event_'
                . $eventId
                . '_'
                . time()
                . '.'
                . $extension;


            $targetPath =
                $uploadDirectory
                . $fileName;


            if (
                !move_uploaded_file(
                    $posterFile['tmp_name'],
                    $targetPath
                )
            ) {
                return
                    "Unable to save the event poster.";
            }


            $posterPath =
                "uploads/events/posters/"
                . $fileName;


            $this->event
                ->updateEventPoster(
                    $eventId,
                    $universityId,
                    $posterPath
                );
        }


        return true;
    }


    // =====================================================
    // SUSPEND EVENT
    // =====================================================

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


        if (
            $event === false
        ) {
            return "Event not found.";
        }


        if (
            strtolower(
                $event['status'] ?? ''
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


        if (
            $suspended === false
        ) {
            return
                "Unable to suspend the event.";
        }


        // Events.facilityId is the
        // BookableFacilities ID
        $bookableFacility =
            $this->bookableFacilities
                ->getBookableFacilityByIdAndUniversity(
                    $event['facilityId'],
                    $universityId
                );


        if (
            $bookableFacility !== false
        ) {

            $this->facilities
                ->updateFacilityStatus(
                    $bookableFacility[
                        'facilityId'
                    ],
                    'active'
                );
        }


        return true;
    }


    // =====================================================
    // AUTOMATICALLY COMPLETE EVENTS
    // AND RELEASE THEIR FACILITIES
    // =====================================================

    public function updateCompletedEvents(
        $universityId
    ) {

        $completedEvents =
            $this->event
                ->completePastEvents(
                    $universityId
                );


        if (
            empty($completedEvents)
        ) {
            return;
        }


        foreach (
            $completedEvents as $event
        ) {

            if (
                !isset(
                $event['facilityId']
            )
            ) {
                continue;
            }


            $bookableFacility =
                $this->bookableFacilities
                    ->getBookableFacilityByIdAndUniversity(
                        $event['facilityId'],
                        $universityId
                    );


            if (
                $bookableFacility !== false
            ) {

                $this->facilities
                    ->updateFacilityStatus(
                        $bookableFacility[
                            'facilityId'
                        ],
                        'active'
                    );
            }
        }
    }
}


// =========================================================
// POST: CREATE EVENT
// =========================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    (
        $_POST['action'] ?? ''
    ) === 'createUniversityEvent'
) {

    $controller =
        new ManageUniversityEventController();


    $universityId =
        $_SESSION['university_id']
        ?? null;


    $createdBy =
        $_SESSION['user_id']
        ?? null;


    $posterFile =
        $_FILES['eventPoster']
        ?? null;


    $result =
        $controller
            ->createUniversityEvent(
                $_POST,
                $universityId,
                $createdBy,
                $posterFile
            );


    if ($result === true) {

        unset(
            $_SESSION['event_old_input']
        );


        $_SESSION['event_success'] =
            "Event created successfully.";


        header(
            "Location: ../boundary/manageUniversityEventPage.php?tab=view"
        );

        exit();
    }


    $_SESSION['event_error'] =
        $result;


    $_SESSION['event_old_input'] =
        $_POST;


    header(
        "Location: ../boundary/manageUniversityEventPage.php?tab=create"
    );

    exit();
}


// =========================================================
// POST: UPDATE EVENT
// =========================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    (
        $_POST['action'] ?? ''
    ) === 'updateUniversityEvent'
) {

    $controller =
        new ManageUniversityEventController();


    $universityId =
        $_SESSION['university_id']
        ?? null;


    $posterFile =
        $_FILES['eventPoster']
        ?? null;


    $result =
        $controller
            ->updateUniversityEvent(
                $_POST,
                $universityId,
                $posterFile
            );


    if ($result === true) {

        $_SESSION['event_success'] =
            "University Event updated successfully.";


    } else {

        $_SESSION['event_error'] =
            $result;
    }


    header(
        "Location: ../boundary/manageUniversityEventPage.php?tab=view"
    );

    exit();
}


// =========================================================
// POST: SUSPEND EVENT
// =========================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    (
        $_POST['action'] ?? ''
    ) === 'suspendUniversityEvent'
) {

    $controller =
        new ManageUniversityEventController();


    $universityId =
        $_SESSION['university_id']
        ?? null;


    $eventId =
        (int) (
            $_POST['eventId']
            ?? 0
        );


    $result =
        $controller
            ->suspendUniversityEvent(
                $eventId,
                $universityId
            );


    if ($result === true) {

        $_SESSION['event_success'] =
            "University Event has been suspended.";

    } else {

        $_SESSION['event_error'] =
            $result;
    }


    header(
        "Location: ../boundary/manageUniversityEventPage.php?tab=view"
    );

    exit();
}

?>