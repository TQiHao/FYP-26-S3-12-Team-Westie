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
        $closeTime,
        $slotDuration
    ) {
        $database = new Database();
        $db = $database->connect();

        try {
            $sql = "INSERT INTO BookableFacilities
                (
                    facilityId,
                    isBookable,
                    slotDuration,
                    bookingCapacity,
                    openTime,
                    closeTime,
                    status
                )
                SELECT
                    id,
                    TRUE,
                    ?,
                    capacity,
                    ?,
                    ?,
                    'available'
                FROM Facilities
                WHERE id = ?";

            $stmt = $db->prepare($sql);

            $stmt->execute([
                $slotDuration,
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
            // Current Monday-Friday reporting week
            $today = new DateTimeImmutable('today');
            $weekStart = $today->modify('monday this week');
            $weekEnd = $weekStart->modify('+4 days');

            $startDate = $weekStart->format('Y-m-d');
            $endDate = $weekEnd->format('Y-m-d');

            $params = [
                $startDate,
                $endDate,
                $universityId
            ];

            $sql = "SELECT
                    f.id AS facilityId,
                    f.name AS facilityName,
                    f.roomCode,
                    f.type,
                    f.location,
                    f.blockFloor,
                    bf.openTime,
                    bf.closeTime,

                    COUNT(
                        CASE
                            WHEN fb.status IN (
                                'pending',
                                'confirmed',
                                'completed'
                            )
                            AND COALESCE(fb.purpose, '')
                                NOT LIKE 'Event:%'
                            THEN fb.id
                        END
                    ) AS studentBookings,

                    COALESCE(SUM(
                        CASE
                            WHEN fb.status IN (
                                'pending',
                                'confirmed',
                                'completed'
                            )
                            AND COALESCE(fb.purpose, '')
                                NOT LIKE 'Event:%'
                            THEN GREATEST(
                                0,
                                TIMESTAMPDIFF(
                                    MINUTE,
                                    fb.startTime,
                                    fb.endTime
                                )
                            ) / 60
                            ELSE 0
                        END
                    ), 0) AS studentHours,

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

                    COALESCE(SUM(
                        CASE
                            WHEN fb.status IN (
                                'pending',
                                'confirmed',
                                'completed'
                            )
                            AND fb.purpose LIKE 'Event:%'
                            THEN GREATEST(
                                0,
                                TIMESTAMPDIFF(
                                    MINUTE,
                                    fb.startTime,
                                    fb.endTime
                                )
                            ) / 60
                            ELSE 0
                        END
                    ), 0) AS eventHours

                FROM Facilities f

                INNER JOIN BookableFacilities bf
                    ON bf.facilityId = f.id

                LEFT JOIN FacilityBookings fb
                    ON fb.facilityId = bf.id
                    AND fb.bookingDate BETWEEN ? AND ?

                WHERE f.universityId = ?
                  AND bf.isBookable = TRUE";

            if ($facilityType !== '') {
                $sql .= " AND f.type = ?";
                $params[] = $facilityType;
            }

            $sql .= " GROUP BY
                    f.id,
                    f.name,
                    f.roomCode,
                    f.type,
                    f.location,
                    f.blockFloor,
                    bf.openTime,
                    bf.closeTime

                  ORDER BY f.name ASC";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as &$row) {
                $studentBookings = (int) $row['studentBookings'];
                $eventCount = (int) $row['eventCount'];
                $studentHours = (float) $row['studentHours'];
                $eventHours = (float) $row['eventHours'];

                if ($usageType === 'student') {
                    $row['totalBookings'] = $studentBookings;
                    $row['hoursUsed'] = $studentHours;
                } elseif ($usageType === 'event') {
                    $row['totalBookings'] = $eventCount;
                    $row['hoursUsed'] = $eventHours;
                } else {
                    $row['totalBookings'] =
                        $studentBookings + $eventCount;

                    $row['hoursUsed'] =
                        $studentHours + $eventHours;
                }

                // Assumption: same opening hours on all five weekdays
                $open = strtotime($row['openTime'] ?? '');
                $close = strtotime($row['closeTime'] ?? '');

                if (
                    !empty($row['openTime']) &&
                    !empty($row['closeTime']) &&
                    $close > $open
                ) {
                    $dailyHours = ($close - $open) / 3600;
                    $row['availableHours'] = $dailyHours * 5;
                } else {
                    $row['availableHours'] = 0;
                }

                $row['utilisationRate'] =
                    $row['availableHours'] > 0
                    ? (
                        $row['hoursUsed']
                        / $row['availableHours']
                    ) * 100
                    : 0;

                $row['reportStartDate'] = $startDate;
                $row['reportEndDate'] = $endDate;
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
        $report = $this->getFacilityUtilisationReport(
            $universityId,
            $facilityType,
            $usageType
        );

        $totalBookings = 0;
        $totalHours = 0;
        $totalAvailableHours = 0;

        $mostUsedFacility = '-';
        $highestRate = -1;

        foreach ($report as $row) {
            $totalBookings += (int) $row['totalBookings'];
            $totalHours += (float) $row['hoursUsed'];

            $availableHours = (float) $row['availableHours'];
            $utilisationRate = (float) $row['utilisationRate'];

            if ($availableHours > 0) {
                $totalAvailableHours += $availableHours;

                if ($utilisationRate > $highestRate) {
                    $highestRate = $utilisationRate;
                    $mostUsedFacility = $row['facilityName'];
                }
            }
        }

        $averageRate = $totalAvailableHours > 0
            ? ($totalHours / $totalAvailableHours) * 100
            : 0;

        return [
            'totalBookings' => $totalBookings,
            'totalHours' => round($totalHours, 1),
            'averageRate' => round($averageRate, 1),
            'mostUsedFacility' => $mostUsedFacility
        ];
    }


    // Update bookable facility settings
    public function updateBookableFacility(
        $bookableFacilityId,
        $universityId,
        $openTime,
        $closeTime,
        $slotDuration
    ) {
        $database = new Database();
        $db = $database->connect();

        try {
            $sql = "UPDATE BookableFacilities bf
                    INNER JOIN Facilities f
                        ON f.id = bf.facilityId
                    SET bf.openTime = ?,
                        bf.closeTime = ?,
                        bf.slotDuration = ?,
                        bf.updatedAt = CURRENT_TIMESTAMP
                    WHERE bf.id = ?
                      AND f.universityId = ?";

            $stmt = $db->prepare($sql);

            $stmt->execute([
                $openTime,
                $closeTime,
                $slotDuration,
                $bookableFacilityId,
                $universityId
            ]);

            return $stmt->rowCount() > 0;

        } catch (PDOException $e) {
            error_log(
                "Update bookable facility error: "
                . $e->getMessage()
            );

            return false;
        }
    }

    // Suspend or reactivate a bookable facility
    public function updateBookableFacilityStatus(
        $bookableFacilityId,
        $universityId,
        $status
    ) {
        $database = new Database();
        $db = $database->connect();

        try {
            $sql = "UPDATE BookableFacilities bf
                    INNER JOIN Facilities f
                        ON f.id = bf.facilityId
                    SET bf.status = ?,
                        bf.updatedAt = CURRENT_TIMESTAMP
                    WHERE bf.id = ?
                      AND f.universityId = ?";

            $stmt = $db->prepare($sql);

            $stmt->execute([
                $status,
                $bookableFacilityId,
                $universityId
            ]);

            return $stmt->rowCount() > 0;

        } catch (PDOException $e) {
            error_log(
                "Update bookable facility status error: "
                . $e->getMessage()
            );

            return false;
        }
    }

}
?>