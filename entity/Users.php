<?php

class Users
{
    private $id;
    private $universityId;
    private $email;
    private $passwordHash;
    private $fullName;
    private $role;
    private $status;
    private $createdAt;
    private $updatedAt;
    private $lastLogin;

    public function __construct(
        $id = null,
        $universityId = null,
        $email = null,
        $passwordHash = null,
        $fullName = null,
        $role = null,
        $status = null,
        $createdAt = null,
        $updatedAt = null,
        $lastLogin = null
    ) {
        $this->id = $id;
        $this->universityId = $universityId;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->fullName = $fullName;
        $this->role = $role;
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->lastLogin = $lastLogin;
    }

    // Getters
    public function getId()
    {
        return $this->id;
    }

    public function getUniversityId()
    {
        return $this->universityId;
    }

    public function setUniversityId($universityId)
    {
        $this->universityId = $universityId;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getFullName()
    {
        return $this->fullName;
    }

    public function getRole()
    {
        return $this->role;
    }

    public function getStatus()
    {
        return $this->status;
    }

    // Setters
    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function setFullName($fullName)
    {
        $this->fullName = $fullName;
    }

    public function setRole($role)
    {
        $this->role = $role;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }
}