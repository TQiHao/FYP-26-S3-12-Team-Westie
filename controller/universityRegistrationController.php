<?php
session_start();

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
        $email = trim($_POST['email']);

        // Check if email already exists
        $checkSql = "SELECT COUNT(*) FROM Universities WHERE email = ?";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->execute([$email]);

        if ($checkStmt->fetchColumn() > 0) {
            return "duplicate";
        }

        // Get other data
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

        $sql = "INSERT INTO Universities
                (name, email, institutionType, country, postalCode, status,
                suspensionReason, registrationDate, updatedAt, subscriptionPlan)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            $university->getName(),
            $email,
            $university->getInstitutionType(),
            $university->getCountry(),
            $university->getPostalCode(),
            $university->getStatus(),
            $university->getSuspensionReason(),
            $university->getRegistrationDate(),
            $university->getUpdatedAt(),
            $university->getSubscriptionPlan()
        ]);

        return true;
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $controller = new UniversityRegistrationController();
    $result = $controller->registerUniversity();

    if ($result === true) {
        $_SESSION['registration_success'] =
            "University registered successfully!";
    } elseif ($result === "duplicate") {
        $_SESSION['registration_error'] =
            "This university email has already been registered.";
    } else {
        $_SESSION['registration_error'] =
            "Registration failed.";
    }

    header("Location: ../boundary/UniversityRegistrationPage.php");
    exit();
}