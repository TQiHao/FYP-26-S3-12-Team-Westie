<?php

class UserProfiles
{
    private $id;
    private $userId;
    private $contactNumber;
    private $address;
    private $createdAt;
    private $updatedAt;

    public function __construct(
        $id = null,
        $userId = null,
        $contactNumber = null,
        $address = null,
        $createdAt = null,
        $updatedAt = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->contactNumber = $contactNumber;
        $this->address = $address;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    // Getters
    public function getId() { return $this->id; }
    public function getUserId() { return $this->userId; }
    public function getContactNumber() { return $this->contactNumber; }
    public function getAddress() { return $this->address; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getUpdatedAt() { return $this->updatedAt; }

    // Setters
    public function setContactNumber($contactNumber) { $this->contactNumber = $contactNumber; }
    public function setAddress($address) { $this->address = $address; }
}