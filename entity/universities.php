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
    private $db;

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
        $subscriptionPlan = null,
        $db = null
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
        $this->db = $db;
    }

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

    public function setName($name)
    {
        $this->name = $name;
    }
    public function setInstitutionType($v)
    {
        $this->institutionType = $v;
    }
    public function setCountry($v)
    {
        $this->country = $v;
    }
    public function setPostalCode($v)
    {
        $this->postalCode = $v;
    }
    public function setStatus($v)
    {
        $this->status = $v;
    }
    public function setSuspensionReason($v)
    {
        $this->suspensionReason = $v;
    }
    public function setRegistrationDate($v)
    {
        $this->registrationDate = $v;
    }
    public function setUpdatedAt($v)
    {
        $this->updatedAt = $v;
    }
    public function setSubscriptionPlan($v)
    {
        $this->subscriptionPlan = $v;
    }

    public function getCurrentLicense()
    {
        if (!$this->db || !$this->id) {
            return null;
        }

        $stmt = $this->db->prepare(
            "SELECT ul.id, ul.universityId, ul.startDate, ul.expiryDate, ul.status,
                    l.name AS licenseName, l.durationYears
             FROM UniversityLicenses ul
             JOIN Licenses l ON l.id = ul.licenseId
             WHERE ul.universityId = ?
             ORDER BY ul.expiryDate DESC
             LIMIT 1"
        );
        $stmt->execute([$this->id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function renewLicense($subscriptionPlan)
    {
        if (!$this->db || !$this->id) {
            return false;
        }

        $durationYears = (int) $subscriptionPlan;
        if (!in_array($durationYears, [1, 2, 5], true)) {
            return false;
        }

        $current = $this->getCurrentLicense();
        $renewedBy = (int) ($_SESSION['user_id'] ?? 0) ?: null;

        if ($current) {
            $baseTs = max(strtotime($current['expiryDate']), time());
            $newExpiry = date('Y-m-d', strtotime('+' . $durationYears . ' years', $baseTs));

            $stmt = $this->db->prepare(
                "UPDATE UniversityLicenses
                 SET expiryDate = ?, status = 'active', renewedBy = ?
                 WHERE id = ?"
            );
            $ok = $stmt->execute([$newExpiry, $renewedBy, $current['id']]);
        } else {
            $licenseId = $this->findOrCreateLicense($durationYears);
            if (!$licenseId) {
                return false;
            }

            $startDate = date('Y-m-d');
            $newExpiry = date('Y-m-d', strtotime('+' . $durationYears . ' years'));

            $stmt = $this->db->prepare(
                "INSERT INTO UniversityLicenses
                 (universityId, licenseId, startDate, expiryDate, status, renewedBy)
                 VALUES (?, ?, ?, ?, 'active', ?)"
            );
            $ok = $stmt->execute([$this->id, $licenseId, $startDate, $newExpiry, $renewedBy]);
        }

        if (!$ok) {
            return false;
        }

        $stmt = $this->db->prepare(
            "UPDATE Universities SET subscriptionPlan = ? WHERE id = ?"
        );
        $stmt->execute([$durationYears, $this->id]);
        $this->subscriptionPlan = $durationYears;

        return true;
    }

    private function findOrCreateLicense($durationYears)
    {
        $stmt = $this->db->prepare(
            "SELECT id FROM Licenses WHERE durationYears = ? LIMIT 1"
        );
        $stmt->execute([$durationYears]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return (int) $row['id'];
        }

        $name = $durationYears . ' Year License';
        $stmt = $this->db->prepare(
            "INSERT INTO Licenses (name, durationYears, description, status)
             VALUES (?, ?, ?, 'active')"
        );
        $stmt->execute([$name, $durationYears, $name]);
        return (int) $this->db->lastInsertId();
    }
}