<?php

class Universities
{
    private $id;
    private $name;
    private $institutionType;
    private $country;
    private $postalCode;
    private $status;
    private $suspensionReason;
    private $registrationDate;
    private $updatedAt;
    private $subscriptionPlan;

    public function __construct(
        $id = null,
        $name = null,
        $institutionType = null,
        $country = null,
        $postalCode = null,
        $status = null,
        $suspensionReason = null,
        $registrationDate = null,
        $updatedAt = null,
        $subscriptionPlan = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->institutionType = $institutionType;
        $this->country = $country;
        $this->postalCode = $postalCode;
        $this->status = $status;
        $this->suspensionReason = $suspensionReason;
        $this->registrationDate = $registrationDate;
        $this->updatedAt = $updatedAt;
        $this->subscriptionPlan = $subscriptionPlan;
    }

    // Getters
    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getInstitutionType()
    {
        return $this->institutionType;
    }

    public function getCountry()
    {
        return $this->country;
    }

    public function getPostalCode()
    {
        return $this->postalCode;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getSuspensionReason()
    {
        return $this->suspensionReason;
    }

    public function getRegistrationDate()
    {
        return $this->registrationDate;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function getSubscriptionPlan()
    {
        return $this->subscriptionPlan;
    }

    // Setters
    public function setName($name)
    {
        $this->name = $name;
    }

    public function setInstitutionType($institutionType)
    {
        $this->institutionType = $institutionType;
    }

    public function setCountry($country)
    {
        $this->country = $country;
    }

    public function setPostalCode($postalCode)
    {
        $this->postalCode = $postalCode;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function setSuspensionReason($suspensionReason)
    {
        $this->suspensionReason = $suspensionReason;
    }

    public function setRegistrationDate($registrationDate)
    {
        $this->registrationDate = $registrationDate;
    }

    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    public function setSubscriptionPlan($subscriptionPlan)
    {
        $this->subscriptionPlan = $subscriptionPlan;
    }
}
