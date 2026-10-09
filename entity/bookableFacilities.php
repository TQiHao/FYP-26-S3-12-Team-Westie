<?php

require_once "../database/database.php";

class BookableFacilities
{
    private $id;
    private $facilityId;
    private $isBookable;
    private $slotDuration;
    private $bookingCapacity;
    private $openTime;
    private $closeTime;
    private $status;
    private $createdAt;
    private $updatedAt;

    public function __construct(
        $id = null,
        $facilityId = null,
        $isBookable = false,
        $slotDuration = null,
        $bookingCapacity = null,
        $openTime = null,
        $closeTime = null,
        $status = null,
        $createdAt = null,
        $updatedAt = null
    ) {
        $this->id = $id;
        $this->facilityId = $facilityId;
        $this->isBookable = $isBookable;
        $this->slotDuration = $slotDuration;
        $this->bookingCapacity = $bookingCapacity;
        $this->openTime = $openTime;
        $this->closeTime = $closeTime;
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    // Getters
    public function getId()
    {
        return $this->id;
    }

    public function getFacilityId()
    {
        return $this->facilityId;
    }

    public function getIsBookable()
    {
        return $this->isBookable;
    }

    public function getSlotDuration()
    {
        return $this->slotDuration;
    }

    public function getBookingCapacity()
    {
        return $this->bookingCapacity;
    }

    public function getOpenTime()
    {
        return $this->openTime;
    }

    public function getCloseTime()
    {
        return $this->closeTime;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    // Get facilities that are not bookable yet
    public function getAvailableFacilities($universityId)
    {
        $database = new Database();
        $db = $database->connect();

        try {

            $sql = "SELECT
                        f.id,
                        f.universityId,
                        f.name,
                        f.type,
                        f.description,
                        f.location,
                        f.blockFloor,
                        f.capacity,
                        f.status
                    FROM Facilities f

                    LEFT JOIN BookableFacilities bf
                        ON bf.facilityId = f.id

                    WHERE f.universityId = ?
                      AND f.status = 'active'
                      AND bf.id IS NULL

                    ORDER BY f.type ASC,
                             f.name ASC";

            $stmt = $db->prepare($sql);

            $stmt->execute([
                $universityId
            ]);

            return $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );

        } catch (PDOException $e) {

            error_log(
                "Get available bookable facilities error: "
                . $e->getMessage()
            );

            return [];
        }
    }

    // Check whether facility is already bookable
    public function bookableFacilityExists($facilityId)
    {
        $database = new Database();
        $db = $database->connect();

        try {

            $sql = "SELECT id
                    FROM BookableFacilities
                    WHERE facilityId = ?
                    LIMIT 1";

            $stmt = $db->prepare($sql);

            $stmt->execute([
                $facilityId
            ]);

            return $stmt->fetch(
                PDO::FETCH_ASSOC
            ) !== false;

        } catch (PDOException $e) {

            error_log(
                "Check bookable facility error: "
                . $e->getMessage()
            );

            return false;
        }
    }

    // Get one bookable facility by ID and university
    public function getBookableFacilityByIdAndUniversity(
        $bookableFacilityId,
        $universityId
    ) {
        $database = new Database();
        $db = $database->connect();

        try {

            $sql = "SELECT
                    bf.id,
                    bf.facilityId,
                    bf.isBookable,
                    bf.slotDuration,
                    bf.bookingCapacity,
                    bf.openTime,
                    bf.closeTime,
                    bf.status,
                    bf.createdAt,
                    bf.updatedAt,

                    f.name,
                    f.roomCode,
                    f.type,
                    f.description,
                    f.location,
                    f.blockFloor,
                    f.capacity,
                    f.status AS facilityStatus

                FROM BookableFacilities bf

                INNER JOIN Facilities f
                    ON f.id = bf.facilityId

                WHERE bf.id = ?
                  AND f.universityId = ?

                LIMIT 1";

            $stmt = $db->prepare($sql);

            $stmt->execute([
                $bookableFacilityId,
                $universityId
            ]);

            $result =
                $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ?: false;

        } catch (PDOException $e) {

            error_log(
                "Get bookable facility by ID error: "
                . $e->getMessage()
            );

            return false;
        }
    }

    // Create bookable facility
    public function createBookableFacility(
        $facilityId,
        $openTime,
        $closeTime
    ) {
        $database = new Database();
        $db = $database->connect();

        try {

            $sql = "INSERT INTO BookableFacilities
                (
                    facilityId,
                    isBookable,
                    bookingCapacity,
                    openTime,
                    closeTime,
                    status
                )
                SELECT
                    id,
                    TRUE,
                    capacity,
                    ?,
                    ?,
                    'available'
                FROM Facilities
                WHERE id = ?";

            $stmt = $db->prepare($sql);

            $stmt->execute([
                $openTime,
                $closeTime,
                $facilityId
            ]);

            return $db->lastInsertId();

        } catch (PDOException $e) {

            error_log(
                "Create bookable facility error: "
                . $e->getMessage()
            );

            return false;
        }
    }

    // Get all bookable facilities
    public function getBookableFacilitiesByUniversity(
        $universityId
    ) {
        $database = new Database();
        $db = $database->connect();

        try {

            $sql = "SELECT
                        bf.id,
                        bf.facilityId,
                        bf.isBookable,
                        bf.slotDuration,
                        bf.bookingCapacity,
                        bf.openTime,
                        bf.closeTime,
                        bf.status,
                        bf.createdAt,
                        bf.updatedAt,

                        f.name,
                        f.type,
                        f.location,
                        f.blockFloor,
                        f.capacity

                    FROM BookableFacilities bf

                    INNER JOIN Facilities f
                        ON f.id = bf.facilityId

                    WHERE f.universityId = ?

                    ORDER BY f.type ASC,
                             f.name ASC";

            $stmt = $db->prepare($sql);

            $stmt->execute([
                $universityId
            ]);

            return $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );

        } catch (PDOException $e) {

            error_log(
                "Get bookable facilities error: "
                . $e->getMessage()
            );

            return [];
        }
    }

    public function getFacilityUtilisationReport(
        $universityId,
        $facilityType = '',
        $usageType = ''
    ) {
        $database = new Database();
        $db = $database->connect();

        try {

            $params = [
                $universityId
            ];

            /*
             * FacilityBookings contains both:
             * 1. Student bookings
             * 2. Event bookings
             *
             * Event bookings are identified by:
             * purpose LIKE 'Event:%'
             */

            $sql = "SELECT
                    f.id AS facilityId,
                    f.name AS facilityName,
                    f.roomCode,
                    f.type,
                    f.location,
                    f.blockFloor,
                    bf.openTime,
                    bf.closeTime,

                    /* STUDENT BOOKINGS */
                    COUNT(
                        CASE
                            WHEN fb.status IN (
                                'pending',
                                'confirmed',
                                'completed'
                            )
                            AND fb.purpose NOT LIKE 'Event:%'
                            THEN fb.id
                        END
                    ) AS studentBookings,

                    /* STUDENT HOURS */
                    COALESCE(
                        SUM(
                            CASE
                                WHEN fb.status IN (
                                    'pending',
                                    'confirmed',
                                    'completed'
                                )
                                AND fb.purpose NOT LIKE 'Event:%'
                                THEN TIMESTAMPDIFF(
                                    MINUTE,
                                    fb.startTime,
                                    fb.endTime
                                ) / 60
                                ELSE 0
                            END
                        ),
                        0
                    ) AS studentHours,

                    /* EVENT BOOKINGS */
                    COUNT(
                        CASE
                            WHEN fb.status IN (
                                'pending',
                                'confirmed',
                                'completed'
                            )
                            AND fb.purpose LIKE 'Event:%'
                            THEN fb.id
                        END
                    ) AS eventCount,

                    /* EVENT HOURS */
                    COALESCE(
                        SUM(
                            CASE
                                WHEN fb.status IN (
                                    'pending',
                                    'confirmed',
                                    'completed'
                                )
                                AND fb.purpose LIKE 'Event:%'
                                THEN TIMESTAMPDIFF(
                                    MINUTE,
                                    fb.startTime,
                                    fb.endTime
                                ) / 60
                                ELSE 0
                            END
                        ),
                        0
                    ) AS eventHours

                FROM Facilities f

                INNER JOIN BookableFacilities bf
                    ON bf.facilityId = f.id

                LEFT JOIN FacilityBookings fb
                    ON fb.facilityId = bf.id

                WHERE f.universityId = ?
                  AND bf.isBookable = TRUE";

            /*
             * Facility type filter
             */
            if ($facilityType !== '') {

                $sql .= "
                AND f.type = ?
            ";

                $params[] = $facilityType;
            }

            /*
             * Group facilities
             */
            $sql .= "
            GROUP BY
                f.id,
                f.name,
                f.roomCode,
                f.type,
                f.location,
                f.blockFloor,
                bf.openTime,
                bf.closeTime

            ORDER BY f.name ASC
        ";

            $stmt = $db->prepare($sql);

            $stmt->execute($params);

            $rows = $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );


            /*
             * Calculate final totals
             */
            foreach ($rows as &$row) {

                $studentBookings =
                    (int) $row['studentBookings'];

                $eventCount =
                    (int) $row['eventCount'];

                $studentHours =
                    (float) $row['studentHours'];

                $eventHours =
                    (float) $row['eventHours'];


                /*
                 * STUDENT ONLY
                 */
                if ($usageType === 'student') {

                    $row['totalBookings'] =
                        $studentBookings;

                    $row['hoursUsed'] =
                        $studentHours;


                    /*
                     * EVENT ONLY
                     */
                } elseif ($usageType === 'event') {

                    $row['totalBookings'] =
                        $eventCount;

                    $row['hoursUsed'] =
                        $eventHours;


                    /*
                     * ALL USAGE
                     */
                } else {

                    $row['totalBookings'] =
                        $studentBookings
                        + $eventCount;

                    $row['hoursUsed'] =
                        $studentHours
                        + $eventHours;
                }


                /*
                 * Weekly available hours.
                 *
                 * Assumes the same opening/closing
                 * time every day.
                 */
                if (
                    !empty($row['openTime']) &&
                    !empty($row['closeTime'])
                ) {

                    $open =
                        strtotime(
                            $row['openTime']
                        );

                    $close =
                        strtotime(
                            $row['closeTime']
                        );

                    if ($close > $open) {

                        $dailyHours =
                            (
                                $close - $open
                            ) / 3600;

                        $availableHours =
                            $dailyHours * 5;

                    } else {

                        $availableHours = 0;
                    }

                } else {

                    $availableHours = 0;
                }


                $row['availableHours'] =
                    $availableHours;


                /*
                 * Utilisation percentage
                 */
                if ($availableHours > 0) {

                    $row['utilisationRate'] =
                        (
                            $row['hoursUsed']
                            / $availableHours
                        ) * 100;

                } else {

                    $row['utilisationRate'] = 0;
                }
            }

            unset($row);

            return $rows;

        } catch (PDOException $e) {

            error_log(
                "Facility utilisation report error: "
                . $e->getMessage()
            );

            return [];
        }
    }

    public function getFacilityUtilisationSummary(
        $universityId,
        $facilityType = '',
        $usageType = ''
    ) {
        $report =
            $this->getFacilityUtilisationReport(
                $universityId,
                $facilityType,
                $usageType
            );

        $totalBookings = 0;
        $totalHours = 0;

        $mostUsedFacility = '-';
        $highestRate = -1;


        foreach ($report as $row) {

            $totalBookings +=
                (int) $row['totalBookings'];

            $totalHours +=
                (float) $row['hoursUsed'];

            /*
             * Only consider facilities that have
             * available operating hours.
             */
            if (
                (float) $row['availableHours'] > 0 &&
                (float) $row['utilisationRate']
                > $highestRate
            ) {

                $highestRate =
                    (float) 
                    $row['utilisationRate'];

                $mostUsedFacility =
                    $row['facilityName'];
            }
        }

        $averageRate = 0;

        if (!empty($report)) {

            $averageRate =
                array_sum(
                    array_column(
                        $report,
                        'utilisationRate'
                    )
                ) / count($report);
        }

        return [
            'totalBookings' =>
                $totalBookings,

            'totalHours' =>
                round($totalHours, 1),

            'averageRate' =>
                round($averageRate, 1),

            'mostUsedFacility' =>
                $mostUsedFacility
        ];
    }
}
?>