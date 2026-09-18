<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../entity/faculties.php";

class ManageUniversityInformationController
{
    private $faculties;

    public function __construct()
    {
        $this->faculties = new Faculties();
    }

    // Upload Faculty CSV
    public function uploadFacultyList($csvFile, $universityId)
    {
        return $this->faculties->uploadFacultyList(
            $csvFile,
            $universityId
        );
    }
}


// Handle Faculty upload
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST['uploadType']) &&
    $_POST['uploadType'] === "faculty"
) {

    $controller = new ManageUniversityInformationController();

    $csvFile = $_FILES['uploadFile'] ?? null;
    $universityId = $_SESSION['university_id'] ?? null;

    if ($universityId === null) {
        $_SESSION['upload_error'] =
            "Unable to identify your university.";

        header(
            "Location: ../boundary/UploadListPage.php?type=faculty"
        );
        exit();
    }

    if ($csvFile === null) {
        $_SESSION['upload_error'] =
            "Please select a CSV file.";

        header(
            "Location: ../boundary/UploadListPage.php?type=faculty"
        );
        exit();
    }

    $result = $controller->uploadFacultyList(
        $csvFile,
        $universityId
    );

    if ($result === true) {
        $_SESSION['upload_success'] =
            "Faculty list uploaded successfully.";
    } else {
        $_SESSION['upload_error'] =
            "Invalid Faculty CSV file. Please check the file format.";
    }

    header(
        "Location: ../boundary/UploadListPage.php?type=faculty"
    );
    exit();
}