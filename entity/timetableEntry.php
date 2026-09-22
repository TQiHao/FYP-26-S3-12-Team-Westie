<?php

class TimetableEntry
{
    private $id;
    private $userId;
    private $title;
    private $dayOfWeek;
    private $startTime;
    private $endTime;
    private $location;
    private $createdAt;
    private $updatedAt;

    public function __construct(
        $id = null,
        $userId = null,
        $title = null,
        $dayOfWeek = null,
        $startTime = null,
        $endTime = null,
        $location = null,
        $createdAt = null,
        $updatedAt = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->title = $title;
        $this->dayOfWeek = $dayOfWeek;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->location = $location;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    // Getters
    public function getId()
    {
        return $this->id;
    }
    public function getUserId()
    {
        return $this->userId;
    }
    public function getTitle()
    {
        return $this->title;
    }
    public function getDayOfWeek()
    {
        return $this->dayOfWeek;
    }
    public function getStartTime()
    {
        return $this->startTime;
    }
    public function getEndTime()
    {
        return $this->endTime;
    }
    public function getLocation()
    {
        return $this->location;
    }
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    // Setters
    public function setTitle($title)
    {
        $this->title = $title;
    }
    public function setDayOfWeek($dayOfWeek)
    {
        $this->dayOfWeek = $dayOfWeek;
    }
    public function setStartTime($startTime)
    {
        $this->startTime = $startTime;
    }
    public function setEndTime($endTime)
    {
        $this->endTime = $endTime;
    }
    public function setLocation($location)
    {
        $this->location = $location;
    }
}
?>