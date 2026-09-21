<?php

class Feedback
{
    private $id;
    private $userId;
    private $universityId;
    private $category;
    private $message;
    private $status;
    private $createdAt;
    private $updatedAt;

    public function __construct(
        $id = null,
        $userId = null,
        $universityId = null,
        $category = null,
        $message = null,
        $status = null,
        $createdAt = null,
        $updatedAt = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->universityId = $universityId;
        $this->category = $category;
        $this->message = $message;
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    // Getters
    public function getId() { return $this->id; }
    public function getUserId() { return $this->userId; }
    public function getUniversityId() { return $this->universityId; }
    public function getCategory() { return $this->category; }
    public function getMessage() { return $this->message; }
    public function getStatus() { return $this->status; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getUpdatedAt() { return $this->updatedAt; }

    // Setters
    public function setCategory($category) { $this->category = $category; }
    public function setMessage($message) { $this->message = $message; }
    public function setStatus($status) { $this->status = $status; }
}
?>