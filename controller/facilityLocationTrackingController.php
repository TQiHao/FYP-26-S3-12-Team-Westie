<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../database/database.php';
require_once __DIR__ . '/../entity/NavigationNode.php';
require_once __DIR__ . '/../entity/NavigationEdge.php';

class FacilityLocationTrackingController
{
    private $db;
    private $nodeEntity;
    private $edgeEntity;

    public function __construct()
    {
        $database = new Database();
        $this->db         = $database->connect();
        $this->nodeEntity = new NavigationNode($this->db);
        $this->edgeEntity = new NavigationEdge($this->db);
    }

    /**
     * Return the building that actually has navigation nodes.
     * (Avoids picking an unrelated building that has no map.)
     */
    public function getBuildingForUniversity($universityId)
    {
        try {
            $sql = "SELECT b.id, b.buildingName, b.buildingCode
                    FROM CampusBuildings b
                    WHERE b.universityId = ? AND b.status = 'active'
                      AND EXISTS (
                          SELECT 1 FROM CampusFloors f
                          INNER JOIN NavigationNodes n ON n.floorId = f.id
                          WHERE f.buildingId = b.id
                      )
                    ORDER BY b.id ASC
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int) $universityId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("getBuildingForUniversity error: " . $e->getMessage());
            return null;
        }
    }

    public function getFloors($buildingId)
    {
        try {
            $sql = "SELECT id, floorName, floorNumber
                    FROM CampusFloors
                    WHERE buildingId = ? AND status = 'active'
                    ORDER BY floorNumber ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int) $buildingId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("getFloors error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get nodes as plain arrays (for the boundary).
     */
    public function getNodes($buildingId)
    {
        $nodes = $this->nodeEntity->findByBuilding($buildingId);
        $result = [];
        foreach ($nodes as $n) $result[] = $n->toArray();
        return $result;
    }

    /**
     * Get edges as plain arrays (for the boundary).
     */
    public function getEdges($buildingId)
    {
        $edges = $this->edgeEntity->findByBuilding($buildingId);
        $result = [];
        foreach ($edges as $e) $result[] = $e->toArray();
        return $result;
    }

    /**
     * Dijkstra route between two nodes within one building.
     */
    public function getRoute($fromId, $toId, $buildingId, $stepFreeOnly = false)
    {
        $fromId     = (int) $fromId;
        $toId       = (int) $toId;
        $buildingId = (int) $buildingId;

        if ($fromId <= 0 || $toId <= 0 || $buildingId <= 0) return null;

        // Load nodes
        $nodeObjects = $this->nodeEntity->findByBuilding($buildingId);
        $nodes = [];
        foreach ($nodeObjects as $n) $nodes[$n->getId()] = $n->toArray();

        if (!isset($nodes[$fromId]) || !isset($nodes[$toId])) return null;

        // Load edges
        $edgeObjects = $this->edgeEntity->findByBuilding($buildingId);

        $adj = [];
        foreach ($edgeObjects as $e) {
            $accessible = $e->isAccessible();
            if ($stepFreeOnly && !$accessible) continue;

            $a = $e->getFromNodeId();
            $b = $e->getToNodeId();
            $w = $e->getDistanceMeters();

            $adj[$a][] = ['to' => $b, 'w' => $w];
            $adj[$b][] = ['to' => $a, 'w' => $w];
        }

        // Dijkstra
        $dist = [];
        $prev = [];
        $visited = [];
        foreach ($nodes as $id => $_) $dist[$id] = INF;
        $dist[$fromId] = 0;

        while (true) {
            $u = null; $best = INF;
            foreach ($dist as $id => $d) {
                if (isset($visited[$id])) continue;
                if ($d < $best) { $best = $d; $u = $id; }
            }
            if ($u === null || $best === INF) break;
            if ($u === $toId) break;

            $visited[$u] = true;
            foreach ($adj[$u] ?? [] as $edge) {
                $v = $edge['to'];
                $nd = $dist[$u] + $edge['w'];
                if ($nd < $dist[$v]) {
                    $dist[$v] = $nd;
                    $prev[$v] = $u;
                }
            }
        }

        if (!isset($prev[$toId]) && $fromId !== $toId) return null;

        // Reconstruct path
        $path = [];
        $cur = $toId;
        while ($cur !== null) {
            array_unshift($path, $cur);
            if ($cur === $fromId) break;
            $cur = $prev[$cur] ?? null;
        }

        $routeNodes = [];
        foreach ($path as $id) $routeNodes[] = $nodes[$id];

        $distance = $dist[$toId];
        $seconds  = (int) round($distance / 1.2);
        $minutes  = max(1, (int) ceil($seconds / 60));

        return [
            'nodes'         => $routeNodes,
            'distance'      => $distance,
            'walkingTime'   => $minutes,   // minutes
            'steps'         => $this->buildDirections($routeNodes),
        ];
    }

    /**
     * Generate human-readable step-by-step directions.
     */
    public function buildDirections(array $routeNodes)
    {
        $steps = [];
        $count = count($routeNodes);
        if ($count === 0) return $steps;

        $prevFloorId = null;

        for ($i = 0; $i < $count; $i++) {
            $node = $routeNodes[$i];
            $name = $node['name'];
            $type = strtolower($node['type']);
            $floorId = (int) $node['floorId'];
            $floorName = $node['floorName'] ?? 'the next level';

            // Skip junctions (not the first / last)
            if ($type === 'junction' && $i !== 0 && $i !== $count - 1) {
                $prevFloorId = $floorId;
                continue;
            }

            // First node
            if ($i === 0) {
                $steps[] = ['icon' => 'start', 'text' => "Start at $name"];
                $prevFloorId = $floorId;
                continue;
            }

            // Floor change
            if ($prevFloorId !== null && $floorId !== $prevFloorId) {
                if ($type === 'lift') {
                    $steps[] = ['icon' => 'lift', 'text' => "Take the lift to $floorName"];
                } elseif ($type === 'stairs') {
                    $steps[] = ['icon' => 'stairs', 'text' => "Take the stairs to $floorName"];
                } else {
                    $steps[] = ['icon' => 'stairs', 'text' => "Continue to $floorName"];
                }
                $prevFloorId = $floorId;
                continue;
            }

            // Last node
            if ($i === $count - 1) {
                $steps[] = ['icon' => 'arrive', 'text' => "Arrive at $name"];
                continue;
            }

            // Intermediate landmarks — avoid saying "Walk past Stairs" right before "Take the stairs"
            $nextNode = $routeNodes[$i + 1];
            $nextFloorId = (int) $nextNode['floorId'];
            $nextIsFloorChange = ($nextFloorId !== $floorId);

            if (in_array($type, ['room', 'entrance', 'exit']) && !$nextIsFloorChange) {
                $steps[] = ['icon' => 'walk', 'text' => "Walk past $name"];
            } elseif ($type === 'lift' && !$nextIsFloorChange) {
                $steps[] = ['icon' => 'walk', 'text' => "Walk past $name"];
            }

            $prevFloorId = $floorId;
        }

        return $steps;
    }
}