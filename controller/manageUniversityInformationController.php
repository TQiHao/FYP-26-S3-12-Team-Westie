<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../entity/faculties.php";
require_once "../entity/programmes.php";
require_once "../entity/modules.php";

class ManageUniversityInformationController
{
    private $faculties;
    private $programmes;
    private $modules;

    public function __construct()
    {
        $this->faculties = new Faculties();
        $this->programmes = new Programmes();
        $this->modules = new Modules();
    }

    // Validate Faculty CSV
    private function validateFacultyCsv($csvFile, $universityId)
    {
        // Check file exists
        if (!isset($csvFile) || $csvFile['error'] !== UPLOAD_ERR_OK) {
            return "Please select a CSV file.";
        }

        // Check extension
        $extension = strtolower(
            pathinfo($csvFile['name'], PATHINFO_EXTENSION)
        );

        if ($extension !== 'csv') {
            return "Only CSV files are supported.";
        }

        // Open CSV
        $handle = fopen($csvFile['tmp_name'], 'r');

        if ($handle === false) {
            return "Unable to read the CSV file.";
        }

        // Read header
        $headers = fgetcsv($handle);

        if ($headers === false) {
            fclose($handle);
            return "The CSV file is empty.";
        }

        // Remove spaces
        $headers = array_map('trim', $headers);

        // Check if there is header
        $expectedHeaders = [
            'name',
            'code',
            'description'
        ];

        // Verify header
        if ($headers !== $expectedHeaders) {
            fclose($handle);

            return "Invalid CSV header. Expected: name, code, description.";
        }

        $rows = [];
        $codes = [];
        $rowNumber = 1;

        // Read each row
        while (($row = fgetcsv($handle)) !== false) {

            $rowNumber++;

            // Skip empty rows
            if (
                count($row) === 1 &&
                trim($row[0]) === ''
            ) {
                continue;
            }

            // Check column count
            if (count($row) !== 3) {
                fclose($handle);

                return "Row {$rowNumber} must contain exactly 3 columns.";
            }

            $name = trim($row[0]);
            $code = trim($row[1]);
            $description = trim($row[2]);

            // Check required fields
            if ($name === '') {
                fclose($handle);

                return "Row {$rowNumber}: Faculty name is required.";
            }

            if ($code === '') {
                fclose($handle);

                return "Row {$rowNumber}: Faculty code is required.";
            }

            // Check duplicate code inside CSV
            if (in_array($code, $codes)) {
                fclose($handle);

                return "Row {$rowNumber}: Duplicate faculty code '{$code}'.";
            }

            $codes[] = $code;

            // Check if Faculty already exists in database
            if (
                $this->faculties->facultyExists(
                    $universityId,
                    $name,
                    $code
                )
            ) {
                fclose($handle);

                return "Row {$rowNumber}: Faculty name or code already exists.";
            }

            // Store verified row
            $rows[] = [
                'name' => $name,
                'code' => $code,
                'description' => $description
            ];
        }

        fclose($handle);

        // Check if there is data
        if (empty($rows)) {
            return "The CSV file contains no faculty data.";
        }

        // Return verified rows
        return $rows;
    }

    public function getFacultyList($universityId)
    {
        return $this->faculties->getFacultiesByUniversity($universityId);
    }

    // Verify CSV first, then insert into database
    public function uploadFacultyList($csvFile, $universityId)
    {
        // Verify CSV
        $validatedRows = $this->validateFacultyCsv(
            $csvFile,
            $universityId
        );

        // Validation failed
        if (!is_array($validatedRows)) {
            return $validatedRows;
        }

        // Insert verified data
        $result = $this->faculties->uploadFacultyList(
            $validatedRows,
            $universityId
        );

        if ($result === true) {
            return true;
        }

        return "Unable to add faculty data to the database.";
    }

    // Validate programme CSV
    private function validateProgrammeCsv($csvFile, $facultyId)
    {
        // Check file exists
        if (!isset($csvFile) || $csvFile['error'] !== UPLOAD_ERR_OK) {
            return "Please select a CSV file.";
        }

        // Check extension
        $extension = strtolower(
            pathinfo($csvFile['name'], PATHINFO_EXTENSION)
        );

        if ($extension !== 'csv') {
            return "Only CSV files are supported.";
        }

        // Open CSV
        $handle = fopen($csvFile['tmp_name'], 'r');

        if ($handle === false) {
            return "Unable to read the CSV file.";
        }

        // Read header
        $headers = fgetcsv($handle);

        if ($headers === false) {
            fclose($handle);
            return "The CSV file is empty.";
        }

        // Remove spaces
        $headers = array_map('trim', $headers);

        // Expected programme CSV columns
        $expectedHeaders = [
            'name',
            'code',
            'durationYears',
            'description'
        ];

        // Verify header
        if ($headers !== $expectedHeaders) {
            fclose($handle);

            return "Invalid CSV header. Expected: name, code, durationYears, description.";
        }

        $rows = [];
        $codes = [];
        $rowNumber = 1;

        // Read each row
        while (($row = fgetcsv($handle)) !== false) {

            $rowNumber++;

            // Skip empty rows
            if (
                count($row) === 1 &&
                trim($row[0]) === ''
            ) {
                continue;
            }

            // Check column count
            if (count($row) !== 4) {
                fclose($handle);

                return "Row {$rowNumber} must contain exactly 4 columns.";
            }

            $name = trim($row[0]);
            $code = trim($row[1]);
            $durationYears = trim($row[2]);
            $description = trim($row[3]);

            // Check required fields
            if ($name === '') {
                fclose($handle);

                return "Row {$rowNumber}: Programme name is required.";
            }

            if ($code === '') {
                fclose($handle);

                return "Row {$rowNumber}: Programme code is required.";
            }

            // Check duration
            if (
                $durationYears === '' ||
                !is_numeric($durationYears) ||
                (int) $durationYears <= 0
            ) {
                fclose($handle);

                return "Row {$rowNumber}: Duration must be a positive number.";
            }

            // Check duplicate code inside CSV
            if (in_array($code, $codes)) {
                fclose($handle);

                return "Row {$rowNumber}: Duplicate programme code '{$code}'.";
            }

            $codes[] = $code;

            // Check if programme already exists in database
            if (
                $this->programmes->programmeExists(
                    $facultyId,
                    $name,
                    $code
                )
            ) {
                fclose($handle);

                return "Row {$rowNumber}: Programme name or code already exists.";
            }

            // Store verified row
            $rows[] = [
                'name' => $name,
                'code' => $code,
                'durationYears' => (int) $durationYears,
                'description' => $description
            ];
        }

        fclose($handle);

        // Check if there is data
        if (empty($rows)) {
            return "The CSV file contains no programme data.";
        }

        // Return verified rows
        return $rows;
    }

    public function getFacultyById($facultyId, $universityId)
    {
        return $this->faculties->getFacultyById(
            $facultyId,
            $universityId
        );
    }

    public function getProgrammesByFaculty($facultyId)
    {
        return $this->programmes->getProgrammesByFaculty($facultyId);
    }

    // Verify Programme CSV first, then insert into database
    public function uploadProgrammeList($csvFile, $facultyId)
    {
        // Verify CSV
        $validatedRows = $this->validateProgrammeCsv(
            $csvFile,
            $facultyId
        );

        // Validation failed
        if (!is_array($validatedRows)) {
            return $validatedRows;
        }

        // Insert verified data
        $result = $this->programmes->uploadProgrammeList(
            $validatedRows,
            $facultyId
        );

        if ($result === true) {
            return true;
        }

        return "Unable to add programme data to the database.";
    }

    public function getProgrammeById($programmeId, $facultyId, $universityId)
    {
        return $this->programmes->getProgrammeById(
            $programmeId,
            $facultyId,
            $universityId
        );
    }

    public function getModulesByProgramme($programmeId)
    {
        return $this->modules->getModulesByProgramme($programmeId);
    }

    // Validate Module CSV
    private function validateModuleCsv($csvFile, $programmeId)
    {
        if (!isset($csvFile) || $csvFile['error'] !== UPLOAD_ERR_OK) {
            return "Please select a CSV file.";
        }

        $extension = strtolower(
            pathinfo($csvFile['name'], PATHINFO_EXTENSION)
        );

        if ($extension !== 'csv') {
            return "Only CSV files are supported.";
        }

        $handle = fopen($csvFile['tmp_name'], 'r');

        if ($handle === false) {
            return "Unable to read the CSV file.";
        }

        $headers = fgetcsv($handle);

        if ($headers === false) {
            fclose($handle);
            return "The CSV file is empty.";
        }

        $headers = array_map('trim', $headers);

        $expectedHeaders = [
            'name',
            'code',
            'credits',
            'semester',
            'description'
        ];

        if ($headers !== $expectedHeaders) {
            fclose($handle);

            return "Invalid CSV header. Expected: name, code, credits, semester, description.";
        }

        $rows = [];
        $codes = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {

            $rowNumber++;

            if (
                count($row) === 1 &&
                trim($row[0]) === ''
            ) {
                continue;
            }

            if (count($row) !== 5) {
                fclose($handle);

                return "Row {$rowNumber} must contain exactly 5 columns.";
            }

            $name = trim($row[0]);
            $code = trim($row[1]);
            $credits = trim($row[2]);
            $semester = trim($row[3]);
            $description = trim($row[4]);

            if ($name === '') {
                fclose($handle);

                return "Row {$rowNumber}: Module name is required.";
            }

            if ($code === '') {
                fclose($handle);

                return "Row {$rowNumber}: Module code is required.";
            }

            if (
                $credits === '' ||
                !is_numeric($credits) ||
                (int) $credits <= 0
            ) {
                fclose($handle);

                return "Row {$rowNumber}: Credits must be a positive number.";
            }

            if (
                $semester === '' ||
                !is_numeric($semester) ||
                (int) $semester <= 0
            ) {
                fclose($handle);

                return "Row {$rowNumber}: Semester must be a positive number.";
            }

            if (in_array($code, $codes)) {
                fclose($handle);

                return "Row {$rowNumber}: Duplicate module code '{$code}'.";
            }

            $codes[] = $code;

            // Check duplicate module in database
            if (
                $this->modules->moduleExists(
                    $programmeId,
                    $name,
                    $code
                )
            ) {
                fclose($handle);

                return "Row {$rowNumber}: Module name or code already exists.";
            }

            $rows[] = [
                'name' => $name,
                'code' => $code,
                'credits' => (int) $credits,
                'semester' => (int) $semester,
                'description' => $description
            ];
        }

        fclose($handle);

        if (empty($rows)) {
            return "The CSV file contains no module data.";
        }

        return $rows;
    }

    public function uploadModuleList($csvFile, $programmeId)
    {
        $validatedRows = $this->validateModuleCsv(
            $csvFile,
            $programmeId
        );

        if (!is_array($validatedRows)) {
            return $validatedRows;
        }

        $result = $this->modules->uploadModuleList(
            $validatedRows,
            $programmeId
        );

        if ($result === true) {
            return true;
        }

        return "Unable to add module data to the database.";
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

    // Check university
    if ($universityId === null) {

        $_SESSION['upload_error'] =
            "Unable to identify your university.";

        header(
            "Location: ../boundary/UploadListPage.php?type=faculty"
        );

        exit();
    }

    // Verify and upload
    $result = $controller->uploadFacultyList(
        $csvFile,
        $universityId
    );

    if ($result === true) {

        $_SESSION['upload_success'] =
            "Faculty list uploaded successfully.";

        header(
            "Location: ../boundary/UploadFacultyListPage.php"
        );

        exit();

    } else {

        $_SESSION['upload_error'] = $result;

        header(
            "Location: ../boundary/UploadListPage.php?type=faculty"
        );

        exit();
    }
}

// Handle Programme upload
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST['uploadType']) &&
    $_POST['uploadType'] === "programme"
) {

    $controller = new ManageUniversityInformationController();

    $csvFile = $_FILES['uploadFile'] ?? null;

    $universityId = $_SESSION['university_id'] ?? null;

    $facultyId = isset($_POST['facultyId'])
        ? (int) $_POST['facultyId']
        : null;


    // Check university
    if ($universityId === null) {

        $_SESSION['upload_error'] =
            "Unable to identify your university.";

        header(
            "Location: ../boundary/UploadListPage.php?type=programme&facultyId="
            . urlencode($facultyId)
        );

        exit();
    }


    // Check faculty ID
    if ($facultyId === null || $facultyId <= 0) {

        $_SESSION['upload_error'] =
            "Unable to identify the selected faculty.";

        header(
            "Location: ../boundary/UploadFacultyListPage.php"
        );

        exit();
    }


    // Verify faculty belongs to current university
    $faculty = $controller->getFacultyById(
        $facultyId,
        $universityId
    );

    if ($faculty === false) {

        $_SESSION['upload_error'] =
            "Invalid faculty selected.";

        header(
            "Location: ../boundary/UploadFacultyListPage.php"
        );

        exit();
    }


    // Verify CSV and upload
    $result = $controller->uploadProgrammeList(
        $csvFile,
        $facultyId
    );


    // Success
    if ($result === true) {

        $_SESSION['upload_success'] =
            "Programme list uploaded successfully.";

        header(
            "Location: ../boundary/UploadProgrammeListPage.php?facultyId="
            . urlencode($facultyId)
        );

        exit();

    } else {

        $_SESSION['upload_error'] = $result;

        header(
            "Location: ../boundary/UploadListPage.php?type=programme&facultyId="
            . urlencode($facultyId)
        );

        exit();
    }
}

// Handle Module upload
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST['uploadType']) &&
    $_POST['uploadType'] === "module"
) {

    $controller = new ManageUniversityInformationController();

    $csvFile = $_FILES['uploadFile'] ?? null;

    $universityId = $_SESSION['university_id'] ?? null;

    $facultyId = isset($_POST['facultyId'])
        ? (int) $_POST['facultyId']
        : null;

    $programmeId = isset($_POST['programmeId'])
        ? (int) $_POST['programmeId']
        : null;


    // Check university
    if ($universityId === null) {

        $_SESSION['upload_error'] =
            "Unable to identify your university.";

        header(
            "Location: ../boundary/UploadListPage.php?type=module&facultyId="
            . urlencode($facultyId)
            . "&programmeId="
            . urlencode($programmeId)
        );

        exit();
    }


    // Check Faculty ID
    if ($facultyId === null || $facultyId <= 0) {

        $_SESSION['upload_error'] =
            "Unable to identify the selected faculty.";

        header(
            "Location: ../boundary/UploadFacultyListPage.php"
        );

        exit();
    }


    // Check Programme ID
    if ($programmeId === null || $programmeId <= 0) {

        $_SESSION['upload_error'] =
            "Unable to identify the selected programme.";

        header(
            "Location: ../boundary/UploadProgrammeListPage.php?facultyId="
            . urlencode($facultyId)
        );

        exit();
    }


    // Verify programme belongs to selected Faculty and University
    $programme = $controller->getProgrammeById(
        $programmeId,
        $facultyId,
        $universityId
    );

    if ($programme === false) {

        $_SESSION['upload_error'] =
            "Invalid programme selected.";

        header(
            "Location: ../boundary/UploadProgrammeListPage.php?facultyId="
            . urlencode($facultyId)
        );

        exit();
    }


    // Verify CSV and upload
    $result = $controller->uploadModuleList(
        $csvFile,
        $programmeId
    );


    // Success
    if ($result === true) {

        $_SESSION['upload_success'] =
            "Module list uploaded successfully.";

        header(
            "Location: ../boundary/UploadModuleListPage.php?facultyId="
            . urlencode($facultyId)
            . "&programmeId="
            . urlencode($programmeId)
        );

        exit();

    } else {

        $_SESSION['upload_error'] = $result;

        header(
            "Location: ../boundary/UploadListPage.php?type=module&facultyId="
            . urlencode($facultyId)
            . "&programmeId="
            . urlencode($programmeId)
        );

        exit();
    }
}