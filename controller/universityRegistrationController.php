<?php

require_once "../database/database.php";
require_once "../entity/universities.php";

class UniversityRegistrationController
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    public function registerUniversity()
    {
        // Get data from registration form
        $name = $_POST['name'];
        $institutionType = $_POST['institutionType'];
        $country = $_POST['country'];
        $postalCode = $_POST['postalCode'];

        // Create University object
        $university = new Universities(
            null,
            $name,
            $institutionType,
            $country,
            $postalCode,
            "active",
            null,
            date("Y-m-d H:i:s"),
            date("Y-m-d H:i:s"),
            "basic"
        );

        // Insert into database
        $sql = "INSERT INTO Universities
                (name, institutionType, country, postalCode, status,
                 suspensionReason, registrationDate, updatedAt, subscriptionPlan)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            $university->getName(),
            $university->getInstitutionType(),
            $university->getCountry(),
            $university->getPostalCode(),
            $university->getStatus(),
            $university->getSuspensionReason(),
            $university->getRegistrationDate(),
            $university->getUpdatedAt(),
            $university->getSubscriptionPlan()
        ]);
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $controller = new UniversityRegistrationController();

    if ($controller->registerUniversity()) {
        echo "University registered successfully!";
    } else {
        echo "Registration failed.";
    }
}
