<?php

session_start();

require_once "../database/database.php";

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
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $institutionType = trim($_POST['institutionType']);
        $country = trim($_POST['country']);
        $postalCode = trim($_POST['postalCode']);
        $password = $_POST['password'];

        if (!preg_match('/^[A-Za-z0-9\s\-]{3,10}$/', $postalCode)) {
            return "invalid_postal";
        }

        $checkSql = "SELECT COUNT(*)
                     FROM Universities
                     WHERE name = ? OR email = ?";

        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->execute([
            $name,
            $email
        ]);

        if ($checkStmt->fetchColumn() > 0) {
            return "exists";
        }

        $pendingSql = "SELECT COUNT(*)
               FROM UniversityRegistrations
               WHERE (applicantName = ? OR applicantEmail = ?)
               AND status = 'pending'";

        $pendingStmt = $this->db->prepare($pendingSql);
        $pendingStmt->execute([
            $name,
            $email
        ]);

        if ($pendingStmt->fetchColumn() > 0) {
            return "pending";
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO UniversityRegistrations
                (
                    universityId,
                    applicantName,
                    applicantEmail,
                    applicantContact,
                    status,
                    reviewedBy,
                    reviewedAt,
                    rejectionReason,
                    createdAt,
                    updatedAt
                )
                VALUES
                (
                    NULL,
                    ?,
                    ?,
                    NULL,
                    'pending',
                    NULL,
                    NULL,
                    NULL,
                    NOW(),
                    NOW()
                )";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            $name,
            $email
        ]);

        return true;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $controller = new UniversityRegistrationController();
    $result = $controller->registerUniversity();

    if ($result === true) {

        $_SESSION['registration_success'] =
            "University registration request submitted successfully. Please wait for System Admin approval.";

    } elseif ($result === "exists") {

        $_SESSION['registration_error'] =
            "This university has already been registered.";

    } elseif ($result === "pending") {

        $_SESSION['registration_error'] =
            "A registration request for this university is already pending.";

    } elseif ($result === "invalid_postal") {

        $_SESSION['registration_error'] =
            "Please enter a valid postal code (3 to 10 alphanumeric characters).";

    } else {

        $_SESSION['registration_error'] =
            "Unable to submit the registration request.";
    }

    header("Location: ../boundary/UniversityRegistrationPage.php");
    exit();
}