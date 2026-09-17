<?php

class Notification
{
    private $id;
    private $userId;
    private $type;
    private $title;
    private $message;
    private $isRead;
    private $createdAt;

    public function __construct(
        $id = null,
        $userId = null,
        $type = null,
        $title = null,
        $message = null,
        $isRead = false,
        $createdAt = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->type = $type;
        $this->title = $title;
        $this->message = $message;
        $this->isRead = $isRead;
        $this->createdAt = $createdAt;
    }

    // Getters
    public function getId() { return $this->id; }
    public function getUserId() { return $this->userId; }
    public function getType() { return $this->type; }
    public function getTitle() { return $this->title; }
    public function getMessage() { return $this->message; }
    public function getIsRead() { return $this->isRead; }
    public function getCreatedAt() { return $this->createdAt; }

    // Setters
    public function setType($type) { $this->type = $type; }
    public function setTitle($title) { $this->title = $title; }
    public function setMessage($message) { $this->message = $message; }
    public function setIsRead($isRead) { $this->isRead = $isRead; }
}
?>