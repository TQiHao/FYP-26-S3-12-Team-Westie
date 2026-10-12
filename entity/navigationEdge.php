<?php

class NavigationEdge
{
    private $id;
    private $fromNodeId;
    private $toNodeId;
    private $distanceMeters;
    private $isAccessible;
    private $fromFloorId;
    private $toFloorId;

    private $db;

    public function __construct($db = null)
    {
        $this->db = $db;
    }

    // ===== Getters =====
    public function getId()             { return $this->id; }
    public function getFromNodeId()     { return $this->fromNodeId; }
    public function getToNodeId()       { return $this->toNodeId; }
    public function getDistanceMeters() { return $this->distanceMeters; }
    public function isAccessible()      { return $this->isAccessible; }
    public function getFromFloorId()    { return $this->fromFloorId; }
    public function getToFloorId()      { return $this->toFloorId; }

    /**
     * Get all edges in a building (scoped to active floors on both ends).
     * @return NavigationEdge[]
     */
    public function findByBuilding($buildingId)
    {
        try {
            $sql = "SELECT e.id, e.fromNodeId, e.toNodeId,
                           e.distanceMeters, e.isAccessible,
                           n1.floorId AS fromFloorId,
                           n2.floorId AS toFloorId
                    FROM NavigationEdges e
                    INNER JOIN NavigationNodes n1 ON n1.id = e.fromNodeId
                    INNER JOIN NavigationNodes n2 ON n2.id = e.toNodeId
                    INNER JOIN CampusFloors f1 ON f1.id = n1.floorId
                    INNER JOIN CampusFloors f2 ON f2.id = n2.floorId
                    WHERE f1.buildingId = ?
                      AND f2.buildingId = ?
                      AND f1.status = 'active'
                      AND f2.status = 'active'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int) $buildingId, (int) $buildingId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $edges = [];
            foreach ($rows as $row) {
                $edges[] = $this->hydrate($row);
            }
            return $edges;
        } catch (PDOException $e) {
            error_log("NavigationEdge::findByBuilding error: " . $e->getMessage());
            return [];
        }
    }

    private function hydrate($row)
    {
        $e = new self($this->db);
        $e->id             = (int) $row['id'];
        $e->fromNodeId     = (int) $row['fromNodeId'];
        $e->toNodeId       = (int) $row['toNodeId'];
        $e->distanceMeters = (float) $row['distanceMeters'];
        $e->isAccessible   = ((int) $row['isAccessible']) === 1;
        $e->fromFloorId    = (int) $row['fromFloorId'];
        $e->toFloorId      = (int) $row['toFloorId'];
        return $e;
    }

    public function toArray()
    {
        return [
            'id'             => $this->id,
            'fromNodeId'     => $this->fromNodeId,
            'toNodeId'       => $this->toNodeId,
            'distanceMeters' => $this->distanceMeters,
            'accessible'     => $this->isAccessible ? 1 : 0,
            'fromFloorId'    => $this->fromFloorId,
            'toFloorId'      => $this->toFloorId,
        ];
    }

    public function findByFloor($floorId)
{
    try {
        $sql = "SELECT e.id, e.fromNodeId, e.toNodeId,
                       e.distanceMeters, e.isAccessible
                FROM NavigationEdges e
                INNER JOIN NavigationNodes n1 ON n1.id = e.fromNodeId
                INNER JOIN NavigationNodes n2 ON n2.id = e.toNodeId
                WHERE n1.floorId = ? OR n2.floorId = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([(int) $floorId, (int) $floorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("NavigationEdge::findByFloor error: " . $e->getMessage());
        return [];
    }
}

public function create($fromNodeId, $toNodeId, $distanceMeters, $isAccessible)
{
    if ((int) $fromNodeId === (int) $toNodeId) return false;

    try {
        // Prevent duplicates in either direction
        $check = "SELECT id FROM NavigationEdges
                  WHERE (fromNodeId = ? AND toNodeId = ?)
                     OR (fromNodeId = ? AND toNodeId = ?)
                  LIMIT 1";
        $stmt = $this->db->prepare($check);
        $stmt->execute([(int) $fromNodeId, (int) $toNodeId, (int) $toNodeId, (int) $fromNodeId]);
        if ($stmt->fetch()) return false;

        $sql = "INSERT INTO NavigationEdges
                (fromNodeId, toNodeId, distanceMeters, isAccessible)
                VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            (int) $fromNodeId,
            (int) $toNodeId,
            (float) $distanceMeters,
            $isAccessible ? 1 : 0
        ]);
        return (int) $this->db->lastInsertId();
    } catch (PDOException $e) {
        error_log("NavigationEdge::create error: " . $e->getMessage());
        return false;
    }
}

    public function deleteById($edgeId)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM NavigationEdges WHERE id = ?");
            $stmt->execute([(int) $edgeId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("NavigationEdge::deleteById error: " . $e->getMessage());
            return false;
        }
    }
}