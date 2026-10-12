<?php

class NavigationNode
{
    private $id;
    private $floorId;
    private $name;
    private $type;
    private $x;
    private $y;
    private $facilityId;
    private $floorName;
    private $floorNumber;

    private $db;

    public function __construct($db = null)
    {
        $this->db = $db;
    }

    // ===== Getters =====
    public function getId()          { return $this->id; }
    public function getFloorId()     { return $this->floorId; }
    public function getName()        { return $this->name; }
    public function getType()        { return $this->type; }
    public function getX()           { return $this->x; }
    public function getY()           { return $this->y; }
    public function getFacilityId()  { return $this->facilityId; }
    public function getFloorName()   { return $this->floorName; }
    public function getFloorNumber() { return $this->floorNumber; }

    /**
     * Find a single node by ID, scoped to a building.
     */
    public function findById($id, $buildingId)
    {
        try {
            $sql = "SELECT n.id, n.floorId, n.name, n.type, n.x, n.y, n.facilityId,
                           f.floorName, f.floorNumber
                    FROM NavigationNodes n
                    INNER JOIN CampusFloors f ON f.id = n.floorId
                    WHERE n.id = ?
                      AND f.buildingId = ?
                      AND f.status = 'active'
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int) $id, (int) $buildingId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) return null;
            return $this->hydrate($row);
        } catch (PDOException $e) {
            error_log("NavigationNode::findById error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all nodes in a building (scoped to active floors).
     * @return NavigationNode[]
     */
    public function findByBuilding($buildingId)
    {
        try {
            $sql = "SELECT n.id, n.floorId, n.name, n.type, n.x, n.y, n.facilityId,
                           f.floorName, f.floorNumber
                    FROM NavigationNodes n
                    INNER JOIN CampusFloors f ON f.id = n.floorId
                    WHERE f.buildingId = ?
                      AND f.status = 'active'
                    ORDER BY f.floorNumber ASC, n.id ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int) $buildingId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $nodes = [];
            foreach ($rows as $row) {
                $nodes[] = $this->hydrate($row);
            }
            return $nodes;
        } catch (PDOException $e) {
            error_log("NavigationNode::findByBuilding error: " . $e->getMessage());
            return [];
        }
    }

    private function hydrate($row)
    {
        $n = new self($this->db);
        $n->id           = (int) $row['id'];
        $n->floorId      = (int) $row['floorId'];
        $n->name         = $row['name'];
        $n->type         = $row['type'];
        $n->x            = (int) $row['x'];
        $n->y            = (int) $row['y'];
        $n->facilityId   = isset($row['facilityId']) ? (int) $row['facilityId'] : null;
        $n->floorName    = $row['floorName'] ?? '';
        $n->floorNumber  = isset($row['floorNumber']) ? (int) $row['floorNumber'] : 0;
        return $n;
    }

    public function toArray()
    {
        return [
            'id'          => $this->id,
            'floorId'     => $this->floorId,
            'name'        => $this->name,
            'type'        => $this->type,
            'x'           => $this->x,
            'y'           => $this->y,
            'facilityId'  => $this->facilityId,
            'floorName'   => $this->floorName,
            'floorNumber' => $this->floorNumber,
        ];
    }

    /**
 * Get all nodes placed on a specific floor.
 */
public function findByFloor($floorId)
{
    try {
        $sql = "SELECT id, floorId, floorPlanId, name, type,
                       x, y, pixelX, pixelY, facilityId
                FROM NavigationNodes
                WHERE floorId = ?
                ORDER BY id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([(int) $floorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("NavigationNode::findByFloor error: " . $e->getMessage());
        return [];
    }
}

/**
 * Create a node on a floor. Also writes to x/y for backward compat.
 */
public function createForFloor($floorId, $floorPlanId, $name, $type, $pixelX, $pixelY)
{
    try {
        $sql = "INSERT INTO NavigationNodes
                (floorId, floorPlanId, name, type, x, y, pixelX, pixelY)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            (int) $floorId,
            $floorPlanId ? (int) $floorPlanId : null,
            trim($name),
            $type,
            (int) $pixelX,
            (int) $pixelY,
            (int) $pixelX,
            (int) $pixelY
        ]);
        return (int) $this->db->lastInsertId();
    } catch (PDOException $e) {
        error_log("NavigationNode::createForFloor error: " . $e->getMessage());
        return false;
    }
}

    /**
     * Delete a node (its edges cascade via FK).
     */
    public function deleteById($nodeId)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM NavigationNodes WHERE id = ?");
            $stmt->execute([(int) $nodeId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("NavigationNode::deleteById error: " . $e->getMessage());
            return false;
        }
    }
}