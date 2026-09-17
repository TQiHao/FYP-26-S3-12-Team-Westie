<?php

require_once "../database/database.php";
require_once "../entity/universities.php";

class ManageUniversityInformationController
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    // Get university information
    public function getUniversityInformation($universityId)
    {
        $sql = "SELECT *
                FROM Universities
                WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$universityId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return new Universities(
            $row['id'],
            $row['name'],
            $row['institutionType'],
            $row['country'],
            $row['postalCode'],
            $row['status'],
            $row['suspensionReason'],
            $row['registrationDate'],
            $row['updatedAt'],
            $row['subscriptionPlan']
        );
    }

    // Handle selected management option
    public function processAction($action)
    {
        switch ($action) {

            case "faculty":
                header("Location: ../boundary/UploadFacultyPage.php");
                exit();

            case "facility":
                header("Location: ../boundary/UploadFacilityPage.php");
                exit();

            case "courseCoordinator":
                header("Location: ../boundary/UploadCourseCoordinatorPage.php");
                exit();

            case "lecturer":
                header("Location: ../boundary/UploadLecturerPage.php");
                exit();

            case "student":
                header("Location: ../boundary/UploadStudentPage.php");
                exit();

            case "floorPlan":
                header("Location: ../boundary/UploadFloorPlanPage.php");
                exit();

            default:
                return false;
        }
    }
}


// Handle selected management option
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $controller = new ManageUniversityInformationController();

    $action = $_POST['action'] ?? '';

    $result = $controller->processAction($action);

    if ($result === false) {

        $_SESSION['university_information_error'] =
            "Unable to process the selected option.";

        header(
            "Location: ../boundary/ManageUniversityInformationPage.php"
        );

        exit();
    }
}