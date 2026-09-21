<?php

class Facilities
{
    private $id;
    private $universityId;
    private $name;
    private $type;
    private $description;
    private $location;
    private $blockFloor;
    private $capacity;
    private $status;
    private $createdAt;
    private $updatedAt;

    public function __construct(
        $id = null,
        $universityId = null,
        $name = null,
        $type = null,
        $description = null,
        $location = null,
        $blockFloor = null,
        $capacity = null,
        $status = null,
        $createdAt = null,
        $updatedAt = null
    ) {
        $this->id = $id;
        $this->universityId = $universityId;
        $this->name = $name;
        $this->type = $type;
        $this->description = $description;
        $this->location = $location;
        $this->blockFloor = $blockFloor;
        $this->capacity = $capacity;
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    // Getters
    public function getId() { return $this->id; }
    public function getUniversityId() { return $this->universityId; }
    public function getName() { return $this->name; }
    public function getType() { return $this->type; }
    public function getDescription() { return $this->description; }
    public function getLocation() { return $this->location; }
    public function getBlockFloor() { return $this->blockFloor; }
    public function getCapacity() { return $this->capacity; }
    public function getStatus() { return $this->status; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getUpdatedAt() { return $this->updatedAt; }
}
?>