<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../entity/faculties.php";
require_once "../entity/programmes.php";
require_once "../entity/modules.php";
require_once "../entity/facilities.php";
require_once "../entity/users.php";
require_once "../entity/students.php";
require_once "../entity/floorPlans.php";
require_once "../entity/campusBuildings.php";
require_once "../entity/campusFloors.php";
require_once "../entity/NavigationNode.php";
require_once "../entity/NavigationEdge.php";

class ManageUniversityInformationController
{
    private $faculties;
    private $programmes;
    private $modules;
    private $facilities;
    private $users;
    private $students;
    private $floorPlans;
    private $campusBuildings;
    private $campusFloors;
    private $navigationNodes;
    private $navigationEdges;

    public function __construct()
    {
        $this->faculties        = new Faculties();
        $this->programmes       = new Programmes();
        $this->modules          = new Modules();
        $this->facilities       = new Facilities();
        $this->users            = new Users();
        $this->students         = new Students();
        $this->floorPlans       = new FloorPlans();
        $this->campusBuildings  = new CampusBuildings();
        $this->campusFloors     = new CampusFloors();

        $db = (new Database())->connect();
        $this->navigationNodes  = new NavigationNode($db);
        $this->navigationEdges  = new NavigationEdge($db);
    }

    // Validate Faculty CSV
    private function validateFacultyCsv($csvFile, $universityId)
    {
        if (!isset($csvFile) || $csvFile['error'] !== UPLOAD_ERR_OK) {
            return "Please select a CSV file.";
        }

        $extension = strtolower(pathinfo($csvFile['name'], PATHINFO_EXTENSION));

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

        $expectedHeaders = ['name', 'code', 'description'];

        if ($headers !== $expectedHeaders) {
            fclose($handle);
            return "Invalid CSV header. Expected: name, code, description.";
        }

        $rows = [];
        $codes = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if (count($row) === 1 && trim($row[0]) === '') {
                continue;
            }

            if (count($row) !== 3) {
                fclose($handle);
                return "Row {$rowNumber} must contain exactly 3 columns.";
            }

            $name = trim($row[0]);
            $code = trim($row[1]);
            $description = trim($row[2]);

            if ($name === '') {
                fclose($handle);
                return "Row {$rowNumber}: Faculty name is required.";
            }

            if ($code === '') {
                fclose($handle);
                return "Row {$rowNumber}: Faculty code is required.";
            }

            if (in_array($code, $codes)) {
                fclose($handle);
                return "Row {$rowNumber}: Duplicate faculty code '{$code}'.";
            }

            $codes[] = $code;

            if ($this->faculties->facultyExists($universityId, $name, $code)) {
                fclose($handle);
                return "Row {$rowNumber}: Faculty name or code already exists.";
            }

            $rows[] = ['name' => $name, 'code' => $code, 'description' => $description];
        }

        fclose($handle);

        if (empty($rows)) {
            return "The CSV file contains no faculty data.";
        }

        return $rows;
    }

    public function getFacultyList($universityId)
    {
        return $this->faculties->getFacultiesByUniversity($universityId);
    }

    public function uploadFacultyList($csvFile, $universityId)
    {
        $validatedRows = $this->validateFacultyCsv($csvFile, $universityId);

        if (!is_array($validatedRows)) {
            return $validatedRows;
        }

        $result = $this->faculties->uploadFacultyList($validatedRows, $universityId);

        if ($result === true) {
            return true;
        }

        return "Unable to add faculty data to the database.";
    }

    private function validateProgrammeCsv($csvFile, $facultyId)
    {
        $universityId = $_SESSION['university_id'] ?? null;

        if (!$universityId) {
            return "Unable to identify the current university.";
        }

        if (!isset($csvFile) || $csvFile['error'] !== UPLOAD_ERR_OK) {
            return "Please select a CSV file.";
        }

        $extension = strtolower(pathinfo($csvFile['name'], PATHINFO_EXTENSION));

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

        $expectedHeaders = ['name', 'code', 'durationYears', 'description', 'courseCoordinatorEmail'];

        if ($headers !== $expectedHeaders) {
            fclose($handle);
            return "Invalid CSV header. Expected: name, code, durationYears, description, courseCoordinatorEmail.";
        }

        $rows = [];
        $codes = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if (count($row) === 1 && trim($row[0]) === '') {
                continue;
            }

            if (count($row) !== 5) {
                fclose($handle);
                return "Row {$rowNumber} must contain exactly 5 columns.";
            }

            $name = trim($row[0]);
            $code = trim($row[1]);
            $durationYears = trim($row[2]);
            $description = trim($row[3]);
            $courseCoordinatorEmail = trim($row[4]);

            if ($courseCoordinatorEmail === '' || !filter_var($courseCoordinatorEmail, FILTER_VALIDATE_EMAIL)) {
                fclose($handle);
                return "Row {$rowNumber}: A valid course coordinator email is required.";
            }

            $coordinator = $this->users->getCourseCoordinatorByEmail($courseCoordinatorEmail, $universityId);

            if ($coordinator === false) {
                fclose($handle);
                return "Row {$rowNumber}: Course coordinator email '{$courseCoordinatorEmail}' does not belong to an active course coordinator in this university.";
            }

            if ($name === '') {
                fclose($handle);
                return "Row {$rowNumber}: Programme name is required.";
            }

            if ($code === '') {
                fclose($handle);
                return "Row {$rowNumber}: Programme code is required.";
            }

            if ($durationYears === '' || !is_numeric($durationYears) || (int) $durationYears <= 0) {
                fclose($handle);
                return "Row {$rowNumber}: Duration must be a positive number.";
            }

            if (in_array($code, $codes)) {
                fclose($handle);
                return "Row {$rowNumber}: Duplicate programme code '{$code}'.";
            }

            $codes[] = $code;

            if ($this->programmes->programmeExists($facultyId, $name, $code)) {
                fclose($handle);
                return "Row {$rowNumber}: Programme name or code already exists.";
            }

            $rows[] = [
                'name' => $name,
                'code' => $code,
                'durationYears' => (int) $durationYears,
                'description' => $description,
                'courseCoordinatorId' => (int) $coordinator['id']
            ];
        }

        fclose($handle);

        if (empty($rows)) {
            return "The CSV file contains no programme data.";
        }

        return $rows;
    }

    public function getFacultyById($facultyId, $universityId)
    {
        return $this->faculties->getFacultyById($facultyId, $universityId);
    }

    public function getProgrammesByFaculty($facultyId)
    {
        return $this->programmes->getProgrammesByFaculty($facultyId);
    }

    public function uploadProgrammeList($csvFile, $facultyId)
    {
        $validatedRows = $this->validateProgrammeCsv($csvFile, $facultyId);

        if (!is_array($validatedRows)) {
            return $validatedRows;
        }

        $result = $this->programmes->uploadProgrammeList($validatedRows, $facultyId);

        if ($result === true) {
            return true;
        }

        return "Unable to add programme data to the database.";
    }

    public function getProgrammeById($programmeId, $facultyId, $universityId)
    {
        return $this->programmes->getProgrammeById($programmeId, $facultyId, $universityId);
    }

    public function getModulesByProgramme($programmeId)
    {
        return $this->modules->getModulesByProgramme($programmeId);
    }

    private function validateModuleCsv($csvFile, $programmeId)
    {
        if (!isset($csvFile) || $csvFile['error'] !== UPLOAD_ERR_OK) {
            return "Please select a CSV file.";
        }

        $extension = strtolower(pathinfo($csvFile['name'], PATHINFO_EXTENSION));

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

        $expectedHeaders = ['name', 'code', 'credits', 'semester', 'description'];

        if ($headers !== $expectedHeaders) {
            fclose($handle);
            return "Invalid CSV header. Expected: name, code, credits, semester, description.";
        }

        $rows = [];
        $codes = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if (count($row) === 1 && trim($row[0]) === '') {
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

            if ($credits === '' || !is_numeric($credits) || (int) $credits <= 0) {
                fclose($handle);
                return "Row {$rowNumber}: Credits must be a positive number.";
            }

            if ($semester === '' || !is_numeric($semester) || (int) $semester <= 0) {
                fclose($handle);
                return "Row {$rowNumber}: Semester must be a positive number.";
            }

            if (in_array($code, $codes)) {
                fclose($handle);
                return "Row {$rowNumber}: Duplicate module code '{$code}'.";
            }

            $codes[] = $code;

            if ($this->modules->moduleExists($programmeId, $name, $code)) {
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
        $validatedRows = $this->validateModuleCsv($csvFile, $programmeId);

        if (!is_array($validatedRows)) {
            return $validatedRows;
        }

        $result = $this->modules->uploadModuleList($validatedRows, $programmeId);

        if ($result === true) {
            return true;
        }

        return "Unable to add module data to the database.";
    }

    public function getFacilityList($universityId)
    {
        return $this->facilities->getFacilitiesByUniversity($universityId);
    }

    private function validateFacilityCsv($csvFile, $universityId)
    {
        if (!isset($csvFile) || $csvFile['error'] !== UPLOAD_ERR_OK) {
            return "Please select a CSV file.";
        }

        $extension = strtolower(pathinfo($csvFile['name'], PATHINFO_EXTENSION));

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

        $expectedHeaders = ['name', 'type', 'description', 'location', 'blockFloor', 'capacity'];

        if ($headers !== $expectedHeaders) {
            fclose($handle);
            return "Invalid CSV header. Expected: name, type, description, location, blockFloor, capacity.";
        }

        $rows = [];
        $facilityKeys = [];
        $rowNumber = 1;

        $validTypes = ['study room', 'gym', 'lecture hall', 'lab'];

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if (count($row) === 1 && trim($row[0]) === '') {
                continue;
            }

            if (count($row) !== 6) {
                fclose($handle);
                return "Row {$rowNumber} must contain exactly 6 columns.";
            }

            $name = trim($row[0]);
            $type = strtolower(trim($row[1]));
            $description = trim($row[2]);
            $location = trim($row[3]);
            $blockFloor = trim($row[4]);
            $capacity = trim($row[5]);

            if ($name === '') {
                fclose($handle);
                return "Row {$rowNumber}: Facility name is required.";
            }

            if (!in_array($type, $validTypes)) {
                fclose($handle);
                return "Row {$rowNumber}: Invalid facility type.";
            }

            if ($capacity === '' || !is_numeric($capacity) || (int) $capacity <= 0) {
                fclose($handle);
                return "Row {$rowNumber}: Capacity must be a positive number.";
            }

            $facilityKey = strtolower($name . '|' . $location);

            if (in_array($facilityKey, $facilityKeys)) {
                fclose($handle);
                return "Row {$rowNumber}: Duplicate facility name and location.";
            }

            $facilityKeys[] = $facilityKey;

            if ($this->facilities->facilityExists($universityId, $name, $location)) {
                fclose($handle);
                return "Row {$rowNumber}: Facility already exists.";
            }

            $rows[] = [
                'name' => $name,
                'type' => $type,
                'description' => $description,
                'location' => $location,
                'blockFloor' => $blockFloor,
                'capacity' => (int) $capacity
            ];
        }

        fclose($handle);

        if (empty($rows)) {
            return "The CSV file contains no facility data.";
        }

        return $rows;
    }

    public function uploadFacilityList($csvFile, $universityId)
    {
        $validatedRows = $this->validateFacilityCsv($csvFile, $universityId);

        if (!is_array($validatedRows)) {
            return $validatedRows;
        }

        $result = $this->facilities->uploadFacilityList($validatedRows, $universityId);

        if ($result === true) {
            return true;
        }

        return "Unable to add facility data to the database.";
    }

    public function getCourseCoordinatorList($universityId)
    {
        return $this->users->getCourseCoordinatorsByUniversity($universityId);
    }

    private function validateCourseCoordinatorCsv($csvFile, $universityId)
    {
        if (!isset($csvFile) || $csvFile['error'] !== UPLOAD_ERR_OK) {
            return "Please select a CSV file.";
        }

        $extension = strtolower(pathinfo($csvFile['name'], PATHINFO_EXTENSION));

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

        $expectedHeaders = ['fullName', 'email', 'password'];

        if ($headers !== $expectedHeaders) {
            fclose($handle);
            return "Invalid CSV header. Expected: fullName, email, password.";
        }

        $rows = [];
        $emails = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if (count($row) === 1 && trim($row[0]) === '') {
                continue;
            }

            if (count($row) !== 3) {
                fclose($handle);
                return "Row {$rowNumber} must contain exactly 3 columns.";
            }

            $fullName = trim($row[0]);
            $email = trim($row[1]);
            $password = $row[2];

            if ($fullName === '') {
                fclose($handle);
                return "Row {$rowNumber}: Full name is required.";
            }

            if ($email === '') {
                fclose($handle);
                return "Row {$rowNumber}: Email is required.";
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                fclose($handle);
                return "Row {$rowNumber}: Invalid email address.";
            }

            if ($password === '') {
                fclose($handle);
                return "Row {$rowNumber}: Password is required.";
            }

            if (
                strlen($password) < 8 ||
                !preg_match('/[A-Z]/', $password) ||
                !preg_match('/[a-z]/', $password) ||
                !preg_match('/[0-9]/', $password) ||
                !preg_match('/[^A-Za-z0-9]/', $password)
            ) {
                fclose($handle);
                return "Row {$rowNumber}: Password must contain at least 8 characters, including uppercase, lowercase, number and special character.";
            }

            if (in_array($email, $emails)) {
                fclose($handle);
                return "Row {$rowNumber}: Duplicate email '{$email}'.";
            }

            $emails[] = $email;

            if ($this->users->courseCoordinatorExists($email)) {
                fclose($handle);
                return "Row {$rowNumber}: Email '{$email}' already exists.";
            }

            $rows[] = [
                'fullName' => $fullName,
                'email' => $email,
                'password' => $password
            ];
        }

        fclose($handle);

        if (empty($rows)) {
            return "The CSV file contains no course coordinator data.";
        }

        return $rows;
    }

    public function uploadCourseCoordinatorList($csvFile, $universityId)
    {
        $validatedRows = $this->validateCourseCoordinatorCsv($csvFile, $universityId);

        if (!is_array($validatedRows)) {
            return $validatedRows;
        }

        $result = $this->users->uploadCourseCoordinatorList($validatedRows, $universityId);

        if ($result === true) {
            return true;
        }

        return "Unable to add course coordinator data to the database.";
    }

    public function getLecturerList($universityId)
    {
        return $this->users->getLecturersByUniversity($universityId);
    }

    private function validateLecturerCsv($csvFile, $universityId)
    {
        if (!isset($csvFile) || $csvFile['error'] !== UPLOAD_ERR_OK) {
            return "Please select a CSV file.";
        }

        $extension = strtolower(pathinfo($csvFile['name'], PATHINFO_EXTENSION));

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

        $expectedHeaders = ['fullName', 'email', 'password'];

        if ($headers !== $expectedHeaders) {
            fclose($handle);
            return "Invalid CSV header. Expected: fullName, email, password.";
        }

        $rows = [];
        $emails = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if (count($row) === 1 && trim($row[0]) === '') {
                continue;
            }

            if (count($row) !== 3) {
                fclose($handle);
                return "Row {$rowNumber} must contain exactly 3 columns.";
            }

            $fullName = trim($row[0]);
            $email = trim($row[1]);
            $password = $row[2];

            if ($fullName === '') {
                fclose($handle);
                return "Row {$rowNumber}: Full name is required.";
            }

            if ($email === '') {
                fclose($handle);
                return "Row {$rowNumber}: Email is required.";
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                fclose($handle);
                return "Row {$rowNumber}: Invalid email address.";
            }

            if ($password === '') {
                fclose($handle);
                return "Row {$rowNumber}: Password is required.";
            }

            if (
                strlen($password) < 8 ||
                !preg_match('/[A-Z]/', $password) ||
                !preg_match('/[a-z]/', $password) ||
                !preg_match('/[0-9]/', $password) ||
                !preg_match('/[^A-Za-z0-9]/', $password)
            ) {
                fclose($handle);
                return "Row {$rowNumber}: Password must contain at least 8 characters, including uppercase, lowercase, number and special character.";
            }

            if (in_array($email, $emails)) {
                fclose($handle);
                return "Row {$rowNumber}: Duplicate email '{$email}'.";
            }

            $emails[] = $email;

            if ($this->users->lecturerExists($email)) {
                fclose($handle);
                return "Row {$rowNumber}: Email '{$email}' already exists.";
            }

            $rows[] = [
                'fullName' => $fullName,
                'email' => $email,
                'password' => $password
            ];
        }

        fclose($handle);

        if (empty($rows)) {
            return "The CSV file contains no lecturer data.";
        }

        return $rows;
    }

    public function uploadLecturerList($csvFile, $universityId)
    {
        $validatedRows = $this->validateLecturerCsv($csvFile, $universityId);

        if (!is_array($validatedRows)) {
            return $validatedRows;
        }

        $result = $this->users->uploadLecturerList($validatedRows, $universityId);

        if ($result === true) {
            return true;
        }

        return "Unable to add lecturer data to the database.";
    }

    public function getStudentList($universityId)
    {
        return $this->students->getStudentsByUniversity($universityId);
    }

    private function validateStudentCsv($csvFile, $universityId)
    {
        if (!isset($csvFile) || $csvFile['error'] !== UPLOAD_ERR_OK) {
            return "Please select a CSV file.";
        }

        $extension = strtolower(pathinfo($csvFile['name'], PATHINFO_EXTENSION));

        if ($extension !== 'csv') {
            return "Only CSV files are supported.";
        }

        $fileName = pathinfo($csvFile['name'], PATHINFO_FILENAME);

        $pattern = '/^(U\d+)_([A-Za-z0-9]+)_(\d{4}-\d{4})_(S\d+|SpecialTerm)_StudentList$/i';

        if (!preg_match($pattern, $fileName, $matches)) {
            return "Invalid file name. Use U01_BSCS_2026-2027_S1_StudentList.csv";
        }

        $universityCode = strtoupper($matches[1]);
        $programmeCode = strtoupper($matches[2]);
        $academicYear = $matches[3];
        $semester = strtolower($matches[4]) === 'specialterm' ? 'SpecialTerm' : strtoupper($matches[4]);

        $expectedUniversityCode = 'U' . str_pad((string) $universityId, 2, '0', STR_PAD_LEFT);

        if ($universityCode !== $expectedUniversityCode) {
            return "The university code in the file name does not match your university.";
        }

        $programme = $this->programmes->getProgrammeByCodeAndUniversity($programmeCode, $universityId);

        if ($programme === false) {
            return "Programme code '{$programmeCode}' does not exist for your university.";
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

        $identifierType = strtolower($headers[0] ?? '');

        if ($identifierType === 'email') {
            $expectedHeaders = ['email', 'fullName', 'password', 'semesterStart', 'semesterEnd'];
        } elseif ($identifierType === 'student_id') {
            $expectedHeaders = ['student_id', 'fullName', 'email', 'password', 'semesterStart', 'semesterEnd'];
        } else {
            fclose($handle);
            return "The first column must be 'email' or 'student_id'.";
        }

        if ($headers !== $expectedHeaders) {
            fclose($handle);
            return "Invalid CSV header. Expected: " . implode(', ', $expectedHeaders) . ".";
        }

        $rows = [];
        $emails = [];
        $studentIds = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if (count($row) === 1 && trim($row[0]) === '') {
                continue;
            }

            if (count($row) !== count($expectedHeaders)) {
                fclose($handle);
                return "Row {$rowNumber} must contain " . count($expectedHeaders) . " columns.";
            }

            if ($identifierType === 'email') {
                $email = trim($row[0]);
                $fullName = trim($row[1]);
                $password = trim($row[2]);
                $semesterStart = trim($row[3]);
                $semesterEnd = trim($row[4]);
                $studentId = null;
            } else {
                $studentId = trim($row[0]);
                $fullName = trim($row[1]);
                $email = trim($row[2]);
                $password = trim($row[3]);
                $semesterStart = trim($row[4]);
                $semesterEnd = trim($row[5]);
            }

            if ($fullName === '') {
                fclose($handle);
                return "Row {$rowNumber}: Full name is required.";
            }

            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                fclose($handle);
                return "Row {$rowNumber}: Invalid email address.";
            }

            if ($password === '') {
                fclose($handle);
                return "Row {$rowNumber}: Password is required.";
            }

            if ($semesterStart === '' || !DateTime::createFromFormat('Y-m-d', $semesterStart)) {
                fclose($handle);
                return "Row {$rowNumber}: Semester start must use YYYY-MM-DD format.";
            }

            if ($semesterEnd === '' || !DateTime::createFromFormat('Y-m-d', $semesterEnd)) {
                fclose($handle);
                return "Row {$rowNumber}: Semester end must use YYYY-MM-DD format.";
            }

            $startDate = DateTime::createFromFormat('Y-m-d', $semesterStart);
            $endDate = DateTime::createFromFormat('Y-m-d', $semesterEnd);

            if ($endDate <= $startDate) {
                fclose($handle);
                return "Row {$rowNumber}: Semester end must be after semester start.";
            }

            $emailKey = strtolower($email);

            if (in_array($emailKey, $emails)) {
                fclose($handle);
                return "Row {$rowNumber}: Duplicate email '{$email}'.";
            }

            if ($this->users->studentEmailExists($email)) {
                fclose($handle);
                return "Row {$rowNumber}: Email '{$email}' already exists.";
            }

            if ($studentId !== null && $studentId !== '') {
                if (in_array(strtoupper($studentId), $studentIds)) {
                    fclose($handle);
                    return "Row {$rowNumber}: Duplicate student ID '{$studentId}'.";
                }

                if ($this->students->studentIdExists($studentId)) {
                    fclose($handle);
                    return "Row {$rowNumber}: Student ID '{$studentId}' already exists.";
                }

                $studentIds[] = strtoupper($studentId);
            }

            $emails[] = $emailKey;

            $rows[] = [
                'studentId' => $studentId,
                'fullName' => $fullName,
                'email' => $email,
                'password' => $password,
                'semesterStart' => $semesterStart,
                'semesterEnd' => $semesterEnd
            ];
        }

        fclose($handle);

        if (empty($rows)) {
            return "The CSV file contains no student data.";
        }

        return [
            'rows' => $rows,
            'programmeId' => $programme['id'],
            'programmeCode' => $programmeCode,
            'academicYear' => $academicYear,
            'semester' => $semester
        ];
    }

    public function getLatestFloorPlan($universityId)
    {
        return $this->floorPlans->getLatestFloorPlan($universityId);
    }

    public function validateFloorPlan($file)
    {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return "Please select a floor plan.";
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        $allowedExtensions = ['png', 'jpg', 'jpeg'];

        if (!in_array($extension, $allowedExtensions)) {
            return "Invalid file format. Please upload a PNG, JPG, or JPEG file.";
        }

        $allowedMimeTypes = ['image/png', 'image/jpeg'];

        $mimeType = mime_content_type($file['tmp_name']);

        if (!in_array($mimeType, $allowedMimeTypes)) {
            return "Invalid floor plan image.";
        }

        $maxFileSize = 10 * 1024 * 1024;

        if ($file['size'] > $maxFileSize) {
            return "Floor plan file size must not exceed 10 MB.";
        }

        return true;
    }

    public function uploadFloorPlan($floorId, $fileName, $filePath, $uploadedBy)
    {
        // NEW: read natural image dimensions from the saved file
        $fullPath = __DIR__ . '/../' . $filePath;
        $imageWidth  = null;
        $imageHeight = null;

        if (file_exists($fullPath)) {
            $dims = getimagesize($fullPath);
            if ($dims !== false) {
                $imageWidth  = (int) $dims[0];
                $imageHeight = (int) $dims[1];
            }
        }

        return $this->floorPlans->uploadFloorPlan(
            $floorId,
            $fileName,
            $filePath,
            $uploadedBy,
            $imageWidth,
            $imageHeight
        );
    }

    public function uploadStudentList($csvFile, $universityId)
    {
        $validated = $this->validateStudentCsv($csvFile, $universityId);

        if (!is_array($validated)) {
            return $validated;
        }

        foreach ($validated['rows'] as $row) {
            $userId = $this->users->createStudentUser(
                $universityId,
                $row['email'],
                $row['password'],
                $row['fullName']
            );

            if ($userId === false) {
                return "Unable to create student account for '{$row['email']}'.";
            }

            $studentRecordId = $this->students->createStudentRecord(
                $userId,
                $row['studentId'],
                $validated['programmeId'],
                $validated['academicYear'],
                $validated['semester'],
                $row['semesterStart'],
                $row['semesterEnd']
            );

            if ($studentRecordId === false) {
                return "Unable to create student record for '{$row['email']}'.";
            }
        }

        return true;
    }

    public function getFloorPlanByFloorId($floorId)
    {
        $database = new Database();
        $db = $database->connect();

        try {
            $sql = "SELECT id, floorId, fileName, filePath, uploadedBy, uploadedAt, updatedAt
                    FROM FloorPlans
                    WHERE floorId = ?
                    ORDER BY id DESC LIMIT 1";

            $stmt = $db->prepare($sql);
            $stmt->execute([$floorId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function getCampusMap($universityId)
    {
        $database = new Database();
        $db = $database->connect();

        try {
            $sql = "SELECT campusMapPath FROM Universities WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$universityId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result || empty($result['campusMapPath'])) {
                return null;
            }

            return ['filePath' => $result['campusMapPath']];
        } catch (PDOException $e) {
            error_log("Get campus map error: " . $e->getMessage());
            return null;
        }
    }

    public function getCampusBuildings($universityId)
    {
        $buildings = $this->campusBuildings->getBuildingsByUniversity($universityId);

        foreach ($buildings as &$building) {
            $building['floors'] = $this->campusFloors->getFloorsByBuilding($building['id']);

            foreach ($building['floors'] as &$floor) {
                $floor['floorPlan'] = $this->floorPlans->getFloorPlanByFloorId($floor['id']);
            }
        }

        return $buildings;
    }

    public function getCampusBuildingById($buildingId)
    {
        return $this->campusBuildings->getBuildingById($buildingId);
    }

    public function createCampusBuilding($universityId, $buildingName, $buildingCode, $description)
    {
        if ($this->campusBuildings->buildingCodeExists($universityId, $buildingCode)) {
            return "Building code already exists.";
        }

        return $this->campusBuildings->createBuilding($universityId, $buildingName, $buildingCode, $description);
    }

    public function updateCampusBuilding($buildingId, $buildingName, $buildingCode, $description)
    {
        return $this->campusBuildings->updateBuilding($buildingId, $buildingName, $buildingCode, $description);
    }

    public function deactivateCampusBuilding($buildingId)
    {
        return $this->campusBuildings->deactivateBuilding($buildingId);
    }

    public function getCampusFloors($buildingId)
    {
        return $this->campusFloors->getFloorsByBuilding($buildingId);
    }

    public function getCampusFloorById($floorId)
    {
        return $this->campusFloors->getFloorById($floorId);
    }

    public function createCampusFloor($buildingId, $floorName, $floorNumber, $description)
    {
        if ($this->campusFloors->floorExists($buildingId, $floorName, $floorNumber)) {
            return "This level already exists in this building.";
        }

        return $this->campusFloors->createFloor($buildingId, $floorName, $floorNumber, $description);
    }

    public function updateCampusFloor($floorId, $floorName, $floorNumber, $description)
    {
        $floor = $this->campusFloors->getFloorById($floorId);

        if (!$floor) {
            return "Level not found.";
        }

        if ($this->campusFloors->floorExists($floor['buildingId'], $floorName, $floorNumber, $floorId)) {
            return "This level already exists in this building.";
        }

        return $this->campusFloors->updateFloor($floorId, $floorName, $floorNumber, $description);
    }

    public function deactivateCampusFloor($floorId)
    {
        return $this->campusFloors->deactivateFloor($floorId);
    }

    // ============================================================
    // NAVIGATION NODE EDITOR
    // ============================================================

    public function getNodesForFloor($floorId)
    {
        return $this->navigationNodes->findByFloor($floorId);
    }

    public function getEdgesForFloor($floorId)
    {
        return $this->navigationEdges->findByFloor($floorId);
    }

    public function createNavigationNode($floorId, $floorPlanId, $name, $type, $pixelX, $pixelY)
    {
        return $this->navigationNodes->createForFloor($floorId, $floorPlanId, $name, $type, $pixelX, $pixelY);
    }

    public function deleteNavigationNode($nodeId)
    {
        return $this->navigationNodes->deleteById($nodeId);
    }

    public function createNavigationEdge($fromId, $toId, $distance, $accessible)
    {
        return $this->navigationEdges->create($fromId, $toId, $distance, $accessible);
    }

    public function deleteNavigationEdge($edgeId)
    {
        return $this->navigationEdges->deleteById($edgeId);
    }
}

// ============================================================
// POST HANDLERS
// ============================================================

// ----- Navigation Node Editor (AJAX) -----
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {

    $ajaxActions = [
    'listNavigationNodes',
    'createNavigationNode',
    'deleteNavigationNode',
    'createNavigationEdge',
    'deleteNavigationEdge',
    'listFloorsForBuilding',      // NEW
    'listNodesForOtherFloor',     // NEW
    'createCrossFloorEdge'        // NEW
    ];

    if (in_array($_POST['action'], $ajaxActions, true)) {

        header('Content-Type: application/json');
        $controller = new ManageUniversityInformationController();

        if ($_POST['action'] === 'listNavigationNodes') {
            echo json_encode([
                'nodes' => $controller->getNodesForFloor((int) ($_POST['floorId'] ?? 0))
            ]);
            exit;
        }

        if ($_POST['action'] === 'createNavigationNode') {
            $id = $controller->createNavigationNode(
                (int) ($_POST['floorId'] ?? 0),
                (int) ($_POST['floorPlanId'] ?? 0),
                trim($_POST['name'] ?? 'Unnamed'),
                $_POST['type'] ?? 'room',
                (int) ($_POST['pixelX'] ?? 0),
                (int) ($_POST['pixelY'] ?? 0)
            );
            echo json_encode(['ok' => $id !== false, 'nodeId' => $id]);
            exit;
        }

        if ($_POST['action'] === 'deleteNavigationNode') {
            echo json_encode([
                'ok' => $controller->deleteNavigationNode((int) ($_POST['nodeId'] ?? 0))
            ]);
            exit;
        }

        if ($_POST['action'] === 'createNavigationEdge') {
            $ok = $controller->createNavigationEdge(
                (int) ($_POST['fromNodeId'] ?? 0),
                (int) ($_POST['toNodeId'] ?? 0),
                (float) ($_POST['distanceMeters'] ?? 0),
                ($_POST['isAccessible'] ?? '0') === '1'
            );
            echo json_encode(['ok' => $ok !== false]);
            exit;
        }

        if ($_POST['action'] === 'deleteNavigationEdge') {
            echo json_encode([
                'ok' => $controller->deleteNavigationEdge((int) ($_POST['edgeId'] ?? 0))
            ]);
            exit;
        }

        if ($_POST['action'] === 'listFloorsForBuilding') {
            echo json_encode([
                'floors' => $controller->getCampusFloors((int) ($_POST['buildingId'] ?? 0))
            ]);
            exit;
        }

        if ($_POST['action'] === 'listNodesForOtherFloor') {
            echo json_encode([
                'nodes' => $controller->getNodesForFloor((int) ($_POST['floorId'] ?? 0))
            ]);
            exit;
        }

        if ($_POST['action'] === 'createCrossFloorEdge') {
            $ok = $controller->createNavigationEdge(
                (int) ($_POST['fromNodeId'] ?? 0),
                (int) ($_POST['toNodeId'] ?? 0),
                (float) ($_POST['distanceMeters'] ?? 15.0),
                ($_POST['isAccessible'] ?? '0') === '1'
            );
            echo json_encode(['ok' => $ok !== false]);
            exit;
        }
    }
}

// ============================================================
// FILE UPLOAD HANDLERS (existing)
// ============================================================

// Handle Faculty upload
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['uploadType']) && $_POST['uploadType'] === "faculty") {

    $controller = new ManageUniversityInformationController();
    $csvFile = $_FILES['uploadFile'] ?? null;
    $universityId = $_SESSION['university_id'] ?? null;

    if ($universityId === null) {
        $_SESSION['upload_error'] = "Unable to identify your university.";
        header("Location: ../boundary/UploadListPage.php?type=faculty");
        exit();
    }

    $result = $controller->uploadFacultyList($csvFile, $universityId);

    if ($result === true) {
        $_SESSION['upload_success'] = "Faculty list uploaded successfully.";
        header("Location: ../boundary/UploadFacultyListPage.php");
        exit();
    } else {
        $_SESSION['upload_error'] = $result;
        header("Location: ../boundary/UploadListPage.php?type=faculty");
        exit();
    }
}

// Handle Programme upload
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['uploadType']) && $_POST['uploadType'] === "programme") {

    $controller = new ManageUniversityInformationController();
    $csvFile = $_FILES['uploadFile'] ?? null;
    $universityId = $_SESSION['university_id'] ?? null;
    $facultyId = isset($_POST['facultyId']) ? (int) $_POST['facultyId'] : null;

    if ($universityId === null) {
        $_SESSION['upload_error'] = "Unable to identify your university.";
        header("Location: ../boundary/UploadListPage.php?type=programme&facultyId=" . urlencode($facultyId));
        exit();
    }

    if ($facultyId === null || $facultyId <= 0) {
        $_SESSION['upload_error'] = "Unable to identify the selected faculty.";
        header("Location: ../boundary/UploadFacultyListPage.php");
        exit();
    }

    $faculty = $controller->getFacultyById($facultyId, $universityId);

    if ($faculty === false) {
        $_SESSION['upload_error'] = "Invalid faculty selected.";
        header("Location: ../boundary/UploadFacultyListPage.php");
        exit();
    }

    $result = $controller->uploadProgrammeList($csvFile, $facultyId);

    if ($result === true) {
        $_SESSION['upload_success'] = "Programme list uploaded successfully.";
        header("Location: ../boundary/UploadProgrammeListPage.php?facultyId=" . urlencode($facultyId));
        exit();
    } else {
        $_SESSION['upload_error'] = $result;
        header("Location: ../boundary/UploadListPage.php?type=programme&facultyId=" . urlencode($facultyId));
        exit();
    }
}

// Handle Module upload
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['uploadType']) && $_POST['uploadType'] === "module") {

    $controller = new ManageUniversityInformationController();
    $csvFile = $_FILES['uploadFile'] ?? null;
    $universityId = $_SESSION['university_id'] ?? null;
    $facultyId = isset($_POST['facultyId']) ? (int) $_POST['facultyId'] : null;
    $programmeId = isset($_POST['programmeId']) ? (int) $_POST['programmeId'] : null;

    if ($universityId === null) {
        $_SESSION['upload_error'] = "Unable to identify your university.";
        header("Location: ../boundary/UploadListPage.php?type=module&facultyId=" . urlencode($facultyId) . "&programmeId=" . urlencode($programmeId));
        exit();
    }

    if ($facultyId === null || $facultyId <= 0) {
        $_SESSION['upload_error'] = "Unable to identify the selected faculty.";
        header("Location: ../boundary/UploadFacultyListPage.php");
        exit();
    }

    if ($programmeId === null || $programmeId <= 0) {
        $_SESSION['upload_error'] = "Unable to identify the selected programme.";
        header("Location: ../boundary/UploadProgrammeListPage.php?facultyId=" . urlencode($facultyId));
        exit();
    }

    $programme = $controller->getProgrammeById($programmeId, $facultyId, $universityId);

    if ($programme === false) {
        $_SESSION['upload_error'] = "Invalid programme selected.";
        header("Location: ../boundary/UploadProgrammeListPage.php?facultyId=" . urlencode($facultyId));
        exit();
    }

    $result = $controller->uploadModuleList($csvFile, $programmeId);

    if ($result === true) {
        $_SESSION['upload_success'] = "Module list uploaded successfully.";
        header("Location: ../boundary/UploadModuleListPage.php?facultyId=" . urlencode($facultyId) . "&programmeId=" . urlencode($programmeId));
        exit();
    } else {
        $_SESSION['upload_error'] = $result;
        header("Location: ../boundary/UploadListPage.php?type=module&facultyId=" . urlencode($facultyId) . "&programmeId=" . urlencode($programmeId));
        exit();
    }
}

// Handle Facility upload
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['uploadType']) && $_POST['uploadType'] === "facility") {

    $controller = new ManageUniversityInformationController();
    $csvFile = $_FILES['uploadFile'] ?? null;
    $universityId = $_SESSION['university_id'] ?? null;

    if ($universityId === null) {
        $_SESSION['upload_error'] = "Unable to identify your university.";
        header("Location: ../boundary/UploadListPage.php?type=facility");
        exit();
    }

    $result = $controller->uploadFacilityList($csvFile, $universityId);

    if ($result === true) {
        $_SESSION['upload_success'] = "Facility list uploaded successfully.";
        header("Location: ../boundary/UploadFacilityListPage.php");
        exit();
    } else {
        $_SESSION['upload_error'] = $result;
        header("Location: ../boundary/UploadListPage.php?type=facility");
        exit();
    }
}

// Handle Course Coordinator upload
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['uploadType']) && $_POST['uploadType'] === "courseCoordinator") {

    $controller = new ManageUniversityInformationController();
    $csvFile = $_FILES['uploadFile'] ?? null;
    $universityId = $_SESSION['university_id'] ?? null;

    if ($universityId === null) {
        $_SESSION['upload_error'] = "Unable to identify your university.";
        header("Location: ../boundary/UploadListPage.php?type=courseCoordinator");
        exit();
    }

    $result = $controller->uploadCourseCoordinatorList($csvFile, $universityId);

    if ($result === true) {
        $_SESSION['upload_success'] = "Course coordinator list uploaded successfully.";
        header("Location: ../boundary/uploadCourseCoordinatorPage.php");
        exit();
    } else {
        $_SESSION['upload_error'] = $result;
        header("Location: ../boundary/UploadListPage.php?type=courseCoordinator");
        exit();
    }
}

// Handle Lecturer upload
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['uploadType']) && $_POST['uploadType'] === "lecturer") {

    $controller = new ManageUniversityInformationController();
    $csvFile = $_FILES['uploadFile'] ?? null;
    $universityId = $_SESSION['university_id'] ?? null;

    if ($universityId === null) {
        $_SESSION['upload_error'] = "Unable to identify your university.";
        header("Location: ../boundary/UploadListPage.php?type=lecturer");
        exit();
    }

    $result = $controller->uploadLecturerList($csvFile, $universityId);

    if ($result === true) {
        $_SESSION['upload_success'] = "Lecturer list uploaded successfully.";
        header("Location: ../boundary/uploadLecturerPage.php");
        exit();
    } else {
        $_SESSION['upload_error'] = $result;
        header("Location: ../boundary/UploadListPage.php?type=lecturer");
        exit();
    }
}

// Handle Student upload
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['uploadType']) && $_POST['uploadType'] === "student") {

    $controller = new ManageUniversityInformationController();
    $csvFile = $_FILES['uploadFile'] ?? null;
    $universityId = $_SESSION['university_id'] ?? null;

    if ($universityId === null) {
        $_SESSION['upload_error'] = "Unable to identify your university.";
        header("Location: ../boundary/UploadListPage.php?type=student");
        exit();
    }

    $result = $controller->uploadStudentList($csvFile, $universityId);

    if ($result === true) {
        $_SESSION['upload_success'] = "Student list uploaded successfully.";
        header("Location: ../boundary/uploadStudentListPage.php");
        exit();
    }

    $_SESSION['upload_error'] = $result;
    header("Location: ../boundary/UploadListPage.php?type=student");
    exit();
}

// Handle Overall Campus Map upload
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['uploadType']) && $_POST['uploadType'] === "campusMap") {

    $file = $_FILES['uploadFile'] ?? null;

    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['upload_error'] = "Please select a campus map.";
        header("Location: ../boundary/uploadFloorPlanPage.php");
        exit();
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['png', 'jpg', 'jpeg'];

    if (!in_array($extension, $allowedExtensions)) {
        $_SESSION['upload_error'] = "Invalid campus map format. Please upload a PNG, JPG, or JPEG file.";
        header("Location: ../boundary/uploadFloorPlanPage.php");
        exit();
    }

    $uploadDirectory = "../uploads/campusMap/";

    if (!is_dir($uploadDirectory)) {
        mkdir($uploadDirectory, 0777, true);
    }

    $fileName = uniqid('campus_map_', true) . '.' . $extension;

    if (!move_uploaded_file($file['tmp_name'], $uploadDirectory . $fileName)) {
        $_SESSION['upload_error'] = "Unable to save the campus map.";
        header("Location: ../boundary/uploadFloorPlanPage.php");
        exit();
    }

    $campusMapPath = 'uploads/campusMap/' . $fileName;

    $database = new Database();
    $db = $database->connect();

    $stmt = $db->prepare("UPDATE Universities SET campusMapPath = ? WHERE id = ?");
    $stmt->execute([$campusMapPath, $_SESSION['university_id']]);

    $_SESSION['upload_success'] = "Campus map uploaded successfully.";
    header("Location: ../boundary/uploadFloorPlanPage.php");
    exit();
}

// Handle Floor Plan upload
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['uploadType']) && $_POST['uploadType'] === "floorPlan") {

    $controller = new ManageUniversityInformationController();
    $file = $_FILES['uploadFile'] ?? null;
    $floorId = isset($_POST['floorId']) ? (int) $_POST['floorId'] : 0;
    $uploadedBy = $_SESSION['user_id'] ?? null;

    if ($floorId <= 0) {
        $_SESSION['upload_error'] = "Unable to identify the selected level.";
        header("Location: ../boundary/uploadFloorPlanPage.php");
        exit();
    }

    if ($uploadedBy === null) {
        $_SESSION['upload_error'] = "Unable to identify the current user.";
        header("Location: ../boundary/uploadFloorPlanPage.php");
        exit();
    }

    $validation = $controller->validateFloorPlan($file);

    if ($validation !== true) {
        $_SESSION['upload_error'] = $validation;
        header("Location: ../boundary/uploadFloorPlanPage.php");
        exit();
    }

    $uploadDirectory = "../uploads/floorPlans/";

    if (!is_dir($uploadDirectory)) {
        mkdir($uploadDirectory, 0777, true);
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $fileName = uniqid('floorplan_', true) . '.' . $extension;
    $filePath = "uploads/floorPlans/" . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $uploadDirectory . $fileName)) {
        $_SESSION['upload_error'] = "Unable to save the floor plan.";
        header("Location: ../boundary/uploadFloorPlanPage.php");
        exit();
    }

    $result = $controller->uploadFloorPlan($floorId, $file['name'], $filePath, $uploadedBy);

    if ($result === true) {
        $_SESSION['upload_success'] = "Floor plan uploaded successfully.";
    } else {
        $_SESSION['upload_error'] = "Unable to save the floor plan.";
    }

    header("Location: ../boundary/uploadFloorPlanPage.php");
    exit();
}

// Handle Add Campus Building
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === "createCampusBuilding") {

    $controller = new ManageUniversityInformationController();
    $universityId = $_SESSION['university_id'] ?? null;
    $buildingName = trim($_POST['buildingName'] ?? '');
    $buildingCode = trim($_POST['buildingCode'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($universityId === null) {
        $_SESSION['upload_error'] = "Unable to identify your university.";
        header("Location: ../boundary/uploadFloorPlanPage.php");
        exit();
    }

    if ($buildingName === '') {
        $_SESSION['upload_error'] = "Building name is required.";
        header("Location: ../boundary/uploadFloorPlanPage.php");
        exit();
    }

    $result = $controller->createCampusBuilding($universityId, $buildingName, $buildingCode, $description);

    if ($result === true) {
        $_SESSION['upload_success'] = "Campus building added successfully.";
    } else {
        $_SESSION['upload_error'] = $result;
    }

    header("Location: ../boundary/uploadFloorPlanPage.php");
    exit();
}

// Handle Add Campus Floor
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === "createCampusFloor") {

    $controller = new ManageUniversityInformationController();
    $buildingId = (int) ($_POST['buildingId'] ?? 0);
    $floorName = trim($_POST['floorName'] ?? '');
    $floorNumber = (int) ($_POST['floorNumber'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    if ($buildingId <= 0) {
        $_SESSION['upload_error'] = "Invalid building.";
        header("Location: ../boundary/uploadFloorPlanPage.php");
        exit();
    }

    if ($floorName === '') {
        $_SESSION['upload_error'] = "Level name is required.";
        header("Location: ../boundary/uploadFloorPlanPage.php");
        exit();
    }

    if ($floorNumber < 0) {
        $_SESSION['upload_error'] = "Level number is invalid.";
        header("Location: ../boundary/uploadFloorPlanPage.php");
        exit();
    }

    $result = $controller->createCampusFloor($buildingId, $floorName, $floorNumber, $description);

    if ($result === true) {
        $_SESSION['upload_success'] = "Level added successfully.";
    } else {
        $_SESSION['upload_error'] = $result;
    }

    header("Location: ../boundary/uploadFloorPlanPage.php");
    exit();
}

// Handle Edit Campus Floor
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === "updateCampusFloor") {

    $controller = new ManageUniversityInformationController();
    $floorId = (int) ($_POST['floorId'] ?? 0);
    $floorName = trim($_POST['floorName'] ?? '');
    $floorNumber = (int) ($_POST['floorNumber'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    if ($floorId <= 0) {
        $_SESSION['upload_error'] = "Invalid level.";
        header("Location: ../boundary/uploadFloorPlanPage.php");
        exit();
    }

    if ($floorName === '') {
        $_SESSION['upload_error'] = "Level name is required.";
        header("Location: ../boundary/uploadFloorPlanPage.php");
        exit();
    }

    if ($floorNumber < 0) {
        $_SESSION['upload_error'] = "Level number is invalid.";
        header("Location: ../boundary/uploadFloorPlanPage.php");
        exit();
    }

    $result = $controller->updateCampusFloor($floorId, $floorName, $floorNumber, $description);

    if ($result) {
        $_SESSION['upload_success'] = "Level updated successfully.";
    } else {
        $_SESSION['upload_error'] = "Unable to update level.";
    }

    header("Location: ../boundary/uploadFloorPlanPage.php");
    exit();
}

// Handle Delete Campus Floor
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === "deleteCampusFloor") {

    $controller = new ManageUniversityInformationController();
    $floorId = (int) ($_POST['floorId'] ?? 0);

    if ($floorId <= 0) {
        $_SESSION['upload_error'] = "Invalid level.";
        header("Location: ../boundary/uploadFloorPlanPage.php");
        exit();
    }

    $result = $controller->deactivateCampusFloor($floorId);

    if ($result) {
        $_SESSION['upload_success'] = "Level deleted successfully.";
    } else {
        $_SESSION['upload_error'] = "Unable to delete level.";
    }

    header("Location: ../boundary/uploadFloorPlanPage.php");
    exit();
}

// Handle Delete Campus Building
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST['action']) &&
    $_POST['action'] === "deleteBuilding"
) {
    $controller = new ManageUniversityInformationController();
    $buildingId = (int) ($_POST['id'] ?? 0);

    if ($buildingId > 0) {
        $controller->deactivateCampusBuilding($buildingId);
    }

    header("Location: ../boundary/uploadFloorPlanPage.php");
    exit();
}

// Handle Update Campus Building
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST['action']) &&
    $_POST['action'] === "updateCampusBuilding"
) {
    $controller = new ManageUniversityInformationController();

    $buildingId   = (int) ($_POST['id'] ?? 0);
    $buildingName = trim($_POST['buildingName'] ?? '');
    $buildingCode = trim($_POST['buildingCode'] ?? '');
    $description  = trim($_POST['description'] ?? '');

    if ($buildingId > 0 && $buildingName !== '') {
        $controller->updateCampusBuilding($buildingId, $buildingName, $buildingCode, $description);
    }

    header("Location: ../boundary/uploadFloorPlanPage.php");
    exit();
}