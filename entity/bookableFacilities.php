<?php

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
    public function getId() { return $this->id; }
    public function getFacilityId() { return $this->facilityId; }
    public function getIsBookable() { return $this->isBookable; }
    public function getSlotDuration() { return $this->slotDuration; }
    public function getBookingCapacity() { return $this->bookingCapacity; }
    public function getOpenTime() { return $this->openTime; }
    public function getCloseTime() { return $this->closeTime; }
    public function getStatus() { return $this->status; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getUpdatedAt() { return $this->updatedAt; }
}
?>