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

class ManageUniversityInformationController
{
    private $faculties;
    private $programmes;
    private $modules;
    private $facilities;
    private $users;
    private $students;
    private $floorPlans;

    public function __construct()
    {
        $this->faculties = new Faculties();
        $this->programmes = new Programmes();
        $this->modules = new Modules();
        $this->facilities = new Facilities();
        $this->users = new Users();
        $this->students = new Students();
        $this->floorPlans = new FloorPlans();
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

    public function getFacilityList($universityId)
    {
        return $this->facilities->getFacilitiesByUniversity(
            $universityId
        );
    }

    // Validate Facility CSV
    private function validateFacilityCsv($csvFile, $universityId)
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
            'type',
            'description',
            'location',
            'blockFloor',
            'capacity'
        ];

        if ($headers !== $expectedHeaders) {
            fclose($handle);

            return "Invalid CSV header. Expected: name, type, description, location, blockFloor, capacity.";
        }

        $rows = [];
        $facilityKeys = [];
        $rowNumber = 1;

        $validTypes = [
            'study room',
            'gym',
            'lecture hall',
            'lab'
        ];

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

            // Check facility name
            if ($name === '') {
                fclose($handle);

                return "Row {$rowNumber}: Facility name is required.";
            }

            // Check facility type
            if (!in_array($type, $validTypes)) {
                fclose($handle);

                return "Row {$rowNumber}: Invalid facility type.";
            }

            // Check capacity
            if (
                $capacity === '' ||
                !is_numeric($capacity) ||
                (int) $capacity <= 0
            ) {
                fclose($handle);

                return "Row {$rowNumber}: Capacity must be a positive number.";
            }

            // Check duplicate inside CSV
            $facilityKey = strtolower(
                $name . '|' . $location
            );

            if (in_array($facilityKey, $facilityKeys)) {
                fclose($handle);

                return "Row {$rowNumber}: Duplicate facility name and location.";
            }

            $facilityKeys[] = $facilityKey;

            // Check duplicate in database
            if (
                $this->facilities->facilityExists(
                    $universityId,
                    $name,
                    $location
                )
            ) {
                fclose($handle);

                return "Row {$rowNumber}: Facility already exists.";
            }

            // Store verified row
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

    // insert into database
    public function uploadFacilityList($csvFile, $universityId)
    {
        // Verify CSV
        $validatedRows = $this->validateFacilityCsv(
            $csvFile,
            $universityId
        );

        // Validation failed
        if (!is_array($validatedRows)) {
            return $validatedRows;
        }

        // Insert verified data
        $result = $this->facilities->uploadFacilityList(
            $validatedRows,
            $universityId
        );

        if ($result === true) {
            return true;
        }

        return "Unable to add facility data to the database.";
    }

    public function getCourseCoordinatorList($universityId)
    {
        return $this->users->getCourseCoordinatorsByUniversity(
            $universityId
        );
    }

    private function validateCourseCoordinatorCsv(
        $csvFile,
        $universityId
    ) {
        // Check file exists
        if (
            !isset($csvFile) ||
            $csvFile['error'] !== UPLOAD_ERR_OK
        ) {

            return "Please select a CSV file.";
        }


        // Check extension
        $extension = strtolower(
            pathinfo(
                $csvFile['name'],
                PATHINFO_EXTENSION
            )
        );

        if ($extension !== 'csv') {

            return "Only CSV files are supported.";
        }


        // Open CSV
        $handle = fopen(
            $csvFile['tmp_name'],
            'r'
        );

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
        $headers = array_map(
            'trim',
            $headers
        );


        // Expected CSV headers
        $expectedHeaders = [
            'fullName',
            'email',
            'password'
        ];


        // Verify header
        if ($headers !== $expectedHeaders) {

            fclose($handle);

            return "Invalid CSV header. Expected: fullName, email, password.";
        }

        $rows = [];

        $emails = [];

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

                return
                    "Row {$rowNumber} must contain exactly 3 columns.";
            }

            $fullName = trim($row[0]);
            $email = trim($row[1]);
            $password = $row[2];

            // Check name
            if ($fullName === '') {

                fclose($handle);

                return
                    "Row {$rowNumber}: Full name is required.";
            }

            // Check email
            if ($email === '') {

                fclose($handle);

                return
                    "Row {$rowNumber}: Email is required.";
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

                fclose($handle);

                return
                    "Row {$rowNumber}: Invalid email address.";
            }

            // Check password
            if ($password === '') {

                fclose($handle);

                return
                    "Row {$rowNumber}: Password is required.";
            }

            // Password strength
            if (
                strlen($password) < 8 ||
                !preg_match('/[A-Z]/', $password) ||
                !preg_match('/[a-z]/', $password) ||
                !preg_match('/[0-9]/', $password) ||
                !preg_match('/[^A-Za-z0-9]/', $password)
            ) {

                fclose($handle);

                return
                    "Row {$rowNumber}: Password must contain at least 8 characters, including uppercase, lowercase, number and special character.";
            }

            // Duplicate email inside CSV
            if (in_array($email, $emails)) {

                fclose($handle);

                return
                    "Row {$rowNumber}: Duplicate email '{$email}'.";
            }

            $emails[] = $email;

            // Check database duplicate
            if (
                $this->users->courseCoordinatorExists(
                    $email
                )
            ) {

                fclose($handle);

                return
                    "Row {$rowNumber}: Email '{$email}' already exists.";
            }

            // Store verified row
            $rows[] = [
                'fullName' => $fullName,
                'email' => $email,
                'password' => $password
            ];
        }

        fclose($handle);

        // No data
        if (empty($rows)) {

            return
                "The CSV file contains no course coordinator data.";
        }

        return $rows;
    }

    public function uploadCourseCoordinatorList(
        $csvFile,
        $universityId
    ) {
        $validatedRows =
            $this->validateCourseCoordinatorCsv(
                $csvFile,
                $universityId
            );

        // Validation failed
        if (!is_array($validatedRows)) {

            return $validatedRows;
        }

        // Insert verified data
        $result =
            $this->users->uploadCourseCoordinatorList(
                $validatedRows,
                $universityId
            );

        if ($result === true) {

            return true;
        }

        return
            "Unable to add course coordinator data to the database.";
    }

    public function getLecturerList($universityId)
    {
        return $this->users->getLecturersByUniversity(
            $universityId
        );
    }

    private function validateLecturerCsv(
        $csvFile,
        $universityId
    ) {
        // Check file exists
        if (
            !isset($csvFile) ||
            $csvFile['error'] !== UPLOAD_ERR_OK
        ) {

            return "Please select a CSV file.";
        }


        // Check extension
        $extension = strtolower(
            pathinfo(
                $csvFile['name'],
                PATHINFO_EXTENSION
            )
        );

        if ($extension !== 'csv') {

            return "Only CSV files are supported.";
        }


        // Open CSV
        $handle = fopen(
            $csvFile['tmp_name'],
            'r'
        );

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
        $headers = array_map(
            'trim',
            $headers
        );


        // Expected headers
        $expectedHeaders = [
            'fullName',
            'email',
            'password'
        ];


        // Verify header
        if ($headers !== $expectedHeaders) {

            fclose($handle);

            return "Invalid CSV header. Expected: fullName, email, password.";
        }

        $rows = [];

        $emails = [];

        $rowNumber = 1;

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

                return
                    "Row {$rowNumber} must contain exactly 3 columns.";
            }

            $fullName = trim($row[0]);
            $email = trim($row[1]);
            $password = $row[2];

            // Check name
            if ($fullName === '') {

                fclose($handle);

                return
                    "Row {$rowNumber}: Full name is required.";
            }

            // Check email
            if ($email === '') {

                fclose($handle);

                return
                    "Row {$rowNumber}: Email is required.";
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

                fclose($handle);

                return
                    "Row {$rowNumber}: Invalid email address.";
            }

            // Check password
            if ($password === '') {

                fclose($handle);

                return
                    "Row {$rowNumber}: Password is required.";
            }

            if (
                strlen($password) < 8 ||
                !preg_match('/[A-Z]/', $password) ||
                !preg_match('/[a-z]/', $password) ||
                !preg_match('/[0-9]/', $password) ||
                !preg_match('/[^A-Za-z0-9]/', $password)
            ) {

                fclose($handle);

                return
                    "Row {$rowNumber}: Password must contain at least 8 characters, including uppercase, lowercase, number and special character.";
            }

            // Duplicate inside CSV
            if (in_array($email, $emails)) {

                fclose($handle);

                return
                    "Row {$rowNumber}: Duplicate email '{$email}'.";
            }

            $emails[] = $email;

            // Duplicate in database
            if (
                $this->users->lecturerExists(
                    $email
                )
            ) {

                fclose($handle);

                return
                    "Row {$rowNumber}: Email '{$email}' already exists.";
            }


            $rows[] = [
                'fullName' => $fullName,
                'email' => $email,
                'password' => $password
            ];
        }

        fclose($handle);

        if (empty($rows)) {

            return
                "The CSV file contains no lecturer data.";
        }

        return $rows;
    }

    public function uploadLecturerList(
        $csvFile,
        $universityId
    ) {
        $validatedRows =
            $this->validateLecturerCsv(
                $csvFile,
                $universityId
            );

        if (!is_array($validatedRows)) {

            return $validatedRows;
        }

        $result =
            $this->users->uploadLecturerList(
                $validatedRows,
                $universityId
            );

        if ($result === true) {

            return true;
        }

        return
            "Unable to add lecturer data to the database.";
    }

    public function getStudentList($universityId)
    {
        return $this->students->getStudentsByUniversity(
            $universityId
        );
    }

    private function validateStudentCsv(
        $csvFile,
        $universityId
    ) {
        // Check file exists
        if (
            !isset($csvFile) ||
            $csvFile['error'] !== UPLOAD_ERR_OK
        ) {
            return "Please select a CSV file.";
        }


        // Check extension
        $extension = strtolower(
            pathinfo(
                $csvFile['name'],
                PATHINFO_EXTENSION
            )
        );

        if ($extension !== 'csv') {
            return "Only CSV files are supported.";
        }


        // Validate filename
        $fileName = pathinfo(
            $csvFile['name'],
            PATHINFO_FILENAME
        );

        $pattern =
            '/^(U\d+)_'
            . '([A-Za-z0-9]+)_'
            . '(\d{4}-\d{4})_'
            . '(S\d+|SpecialTerm)_'
            . 'StudentList$/i';

        if (
            !preg_match(
                $pattern,
                $fileName,
                $matches
            )
        ) {
            return
                "Invalid file name. Use "
                . "U01_BSCS_2026-2027_S1_StudentList.csv";
        }


        // Extract filename information
        $universityCode = strtoupper(
            $matches[1]
        );

        $programmeCode = strtoupper(
            $matches[2]
        );

        $academicYear = $matches[3];

        $semester =
            strtolower($matches[4]) === 'specialterm'
            ? 'SpecialTerm'
            : strtoupper($matches[4]);


        // Build expected university code
        $expectedUniversityCode =
            'U' .
            str_pad(
                (string) $universityId,
                2,
                '0',
                STR_PAD_LEFT
            );


        // Check university code
        if (
            $universityCode !==
            $expectedUniversityCode
        ) {
            return
                "The university code in the file name "
                . "does not match your university.";
        }


        // Find programme
        $programme =
            $this->programmes
                ->getProgrammeByCodeAndUniversity(
                    $programmeCode,
                    $universityId
                );

        if ($programme === false) {
            return
                "Programme code '{$programmeCode}' "
                . "does not exist for your university.";
        }


        // Open CSV
        $handle = fopen(
            $csvFile['tmp_name'],
            'r'
        );

        if ($handle === false) {
            return "Unable to read the CSV file.";
        }


        // Read header
        $headers = fgetcsv($handle);

        if ($headers === false) {
            fclose($handle);

            return "The CSV file is empty.";
        }

        $headers = array_map(
            'trim',
            $headers
        );


        // Determine identifier type
        $identifierType =
            strtolower($headers[0] ?? '');


        // Email-first CSV
        if ($identifierType === 'email') {

            $expectedHeaders = [
                'email',
                'fullName',
                'password',
                'semesterStart',
                'semesterEnd'
            ];

        }

        // Student-ID-first CSV
         elseif ($identifierType === 'student_id') {

            $expectedHeaders = [
                'student_id',
                'fullName',
                'email',
                'password',
                'semesterStart',
                'semesterEnd'
            ];

        } else {

            fclose($handle);

            return
                "The first column must be "
                . "'email' or 'student_id'.";
        }


        // Check headers
        if ($headers !== $expectedHeaders) {

            fclose($handle);

            return
                "Invalid CSV header. Expected: "
                . implode(
                    ', ',
                    $expectedHeaders
                ) . ".";
        }


        $rows = [];
        $emails = [];
        $studentIds = [];

        $rowNumber = 1;


        // Read student rows
        while (
            ($row = fgetcsv($handle)) !== false
        ) {

            $rowNumber++;


            // Skip blank rows
            if (
                count($row) === 1 &&
                trim($row[0]) === ''
            ) {
                continue;
            }

            // Check column count
            if (
                count($row) !==
                count($expectedHeaders)
            ) {

                fclose($handle);

                return
                    "Row {$rowNumber} must contain "
                    . count($expectedHeaders)
                    . " columns.";
            }

            // Email-first
            if ($identifierType === 'email') {

                $email = trim($row[0]);
                $fullName = trim($row[1]);
                $password = trim($row[2]);
                $semesterStart = trim($row[3]);
                $semesterEnd = trim($row[4]);

                $studentId = null;

            }

            // Student-ID-first
            else {

                $studentId = trim($row[0]);
                $fullName = trim($row[1]);
                $email = trim($row[2]);
                $password = trim($row[3]);
                $semesterStart = trim($row[4]);
                $semesterEnd = trim($row[5]);

            }

            // Validate name
            if ($fullName === '') {

                fclose($handle);

                return
                    "Row {$rowNumber}: "
                    . "Full name is required.";
            }

            // Validate email
            if (
                $email === '' ||
                !filter_var(
                    $email,
                    FILTER_VALIDATE_EMAIL
                )
            ) {

                fclose($handle);

                return
                    "Row {$rowNumber}: "
                    . "Invalid email address.";
            }

            // Validate password
            if ($password === '') {

                fclose($handle);

                return
                    "Row {$rowNumber}: "
                    . "Password is required.";
            }

            // Validate semester start date
            if (
                $semesterStart === '' ||
                !DateTime::createFromFormat('Y-m-d', $semesterStart)
            ) {
                fclose($handle);

                return
                    "Row {$rowNumber}: "
                    . "Semester start must use YYYY-MM-DD format.";
            }

            // Validate semester end date
            if (
                $semesterEnd === '' ||
                !DateTime::createFromFormat('Y-m-d', $semesterEnd)
            ) {
                fclose($handle);

                return
                    "Row {$rowNumber}: "
                    . "Semester end must use YYYY-MM-DD format.";
            }

            // Check that end date is after start date
            $startDate = DateTime::createFromFormat(
                'Y-m-d',
                $semesterStart
            );

            $endDate = DateTime::createFromFormat(
                'Y-m-d',
                $semesterEnd
            );

            if ($endDate <= $startDate) {
                fclose($handle);

                return
                    "Row {$rowNumber}: "
                    . "Semester end must be after semester start.";
            }

            // Check duplicate email in CSV
            $emailKey = strtolower($email);

            if (
                in_array(
                    $emailKey,
                    $emails
                )
            ) {

                fclose($handle);

                return
                    "Row {$rowNumber}: Duplicate "
                    . "email '{$email}'.";
            }


            // Check existing email in database
            if (
                $this->users
                    ->studentEmailExists($email)
            ) {

                fclose($handle);

                return
                    "Row {$rowNumber}: Email "
                    . "'{$email}' already exists.";
            }

            // Validate student ID if one was supplied
            if (
                $studentId !== null &&
                $studentId !== ''
            ) {

                if (
                    in_array(
                        strtoupper($studentId),
                        $studentIds
                    )
                ) {

                    fclose($handle);

                    return
                        "Row {$rowNumber}: Duplicate "
                        . "student ID '{$studentId}'.";
                }


                if (
                    $this->students
                        ->studentIdExists(
                            $studentId
                        )
                ) {

                    fclose($handle);

                    return
                        "Row {$rowNumber}: Student ID "
                        . "'{$studentId}' already exists.";
                }


                $studentIds[] =
                    strtoupper($studentId);
            }


            $emails[] = $emailKey;


            // Store validated row
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


        // Check data exists
        if (empty($rows)) {
            return
                "The CSV file contains no student data.";
        }


        // Return everything needed for insertion
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
        return $this->floorPlans->getLatestFloorPlan(
            $universityId
        );
    }

    private function validateFloorPlan($file) 
    {
        // Check whether a file was uploaded
        if (
            !isset($file) ||
            $file['error'] !== UPLOAD_ERR_OK
        ) {
            return "Please select a floor plan.";
        }

        // Check file extension
        $extension = strtolower(
            pathinfo(
                $file['name'],
                PATHINFO_EXTENSION
            )
        );

        $allowedExtensions = [
            'png',
            'jpg',
            'jpeg'
        ];

        if (
            !in_array(
                $extension,
                $allowedExtensions
            )
        ) {
            return "Invalid file format. Please upload a PNG, JPG, or JPEG file.";
        }

        // Check MIME type
        $allowedMimeTypes = [
            'image/png',
            'image/jpeg'
        ];

        $mimeType = mime_content_type(
            $file['tmp_name']
        );

        if (
            !in_array(
                $mimeType,
                $allowedMimeTypes
            )
        ) {
            return "Invalid floor plan image.";
        }

        // Check file size
        $maxFileSize = 10 * 1024 * 1024;

        if ($file['size'] > $maxFileSize) {
            return "Floor plan file size must not exceed 10 MB.";
        }

        return true;
    }

    public function uploadFloorPlan($file, $universityId, $uploadedBy) 
    {
        // Validate file
        $validationResult =
            $this->validateFloorPlan($file);

        if ($validationResult !== true) {
            return $validationResult;
        }

        // Create upload directory
        $uploadDirectory =
            "../uploads/floorplans/";

        if (!is_dir($uploadDirectory)) {

            if (
                !mkdir(
                    $uploadDirectory,
                    0777,
                    true
                )
            ) {
                return "Unable to create the floor plan upload directory.";
            }
        }

        // Get extension
        $extension = strtolower(
            pathinfo(
                $file['name'],
                PATHINFO_EXTENSION
            )
        );

        // Generate unique file name
        $newFileName =
            "floorplan_" .
            $universityId .
            "_" .
            time() .
            "_" .
            uniqid() .
            "." .
            $extension;

        // Full server path
        $destination =
            $uploadDirectory .
            $newFileName;

        // Move uploaded file
        if (
            !move_uploaded_file(
                $file['tmp_name'],
                $destination
            )
        ) {
            return "Unable to save the floor plan file.";
        }

        // Path stored in database
        $filePath =
            "uploads/floorplans/" .
            $newFileName;

        // Original file name
        $originalFileName =
            basename($file['name']);

        // Insert database record
        $result =
            $this->floorPlans->uploadFloorPlan(
                $universityId,
                null,
                $originalFileName,
                $filePath,
                $uploadedBy
            );

        if ($result === true) {
            return true;
        }

        // Delete uploaded file if database insertion fails
        if (file_exists($destination)) {
            unlink($destination);
        }
        return "Unable to save floor plan information to the database.";
    }

    public function uploadStudentList(
        $csvFile,
        $universityId
    ) {
        // Validate everything first
        $validated =
            $this->validateStudentCsv(
                $csvFile,
                $universityId
            );

        if (!is_array($validated)) {
            return $validated;
        }


        // Create each student's account and student record
        foreach ($validated['rows'] as $row) {

            // Create account in Users
            $userId =
                $this->users
                    ->createStudentUser(
                        $universityId,
                        $row['email'],
                        $row['password'],
                        $row['fullName']
                    );


            if ($userId === false) {
                return
                    "Unable to create student account "
                    . "for '{$row['email']}'.";
            }


            // Create student-specific record
            $studentRecordId =
                $this->students
                    ->createStudentRecord(
                        $userId,
                        $row['studentId'],
                        $validated['programmeId'],
                        $validated['academicYear'],
                        $validated['semester'],
                        $row['semesterStart'],
                        $row['semesterEnd']
                    );

            if ($studentRecordId === false) {
                return
                    "Unable to create student record "
                    . "for '{$row['email']}'.";
            }
        }

        return true;
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

// Handle Facility upload
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST['uploadType']) &&
    $_POST['uploadType'] === "facility"
) {

    $controller = new ManageUniversityInformationController();

    $csvFile = $_FILES['uploadFile'] ?? null;

    $universityId = $_SESSION['university_id'] ?? null;

    // Check university
    if ($universityId === null) {

        $_SESSION['upload_error'] =
            "Unable to identify your university.";

        header(
            "Location: ../boundary/UploadListPage.php?type=facility"
        );

        exit();
    }

    // Verify CSV and upload
    $result = $controller->uploadFacilityList(
        $csvFile,
        $universityId
    );

    // Success
    if ($result === true) {

        $_SESSION['upload_success'] =
            "Facility list uploaded successfully.";

        header(
            "Location: ../boundary/UploadFacilityListPage.php"
        );

        exit();

    } else {

        $_SESSION['upload_error'] = $result;

        header(
            "Location: ../boundary/UploadListPage.php?type=facility"
        );

        exit();
    }
}

// Handle Course Coordinator upload
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST['uploadType']) &&
    $_POST['uploadType'] === "courseCoordinator"
) {

    $controller =
        new ManageUniversityInformationController();

    $csvFile =
        $_FILES['uploadFile'] ?? null;

    $universityId =
        $_SESSION['university_id'] ?? null;


    // Check university
    if ($universityId === null) {

        $_SESSION['upload_error'] =
            "Unable to identify your university.";

        header(
            "Location: ../boundary/UploadListPage.php?type=courseCoordinator"
        );

        exit();
    }


    // Verify and upload
    $result =
        $controller->uploadCourseCoordinatorList(
            $csvFile,
            $universityId
        );


    // Success
    if ($result === true) {

        $_SESSION['upload_success'] =
            "Course coordinator list uploaded successfully.";

        header(
            "Location: ../boundary/uploadCourseCoordinatorPage.php"
        );

        exit();

    } else {

        $_SESSION['upload_error'] =
            $result;

        header(
            "Location: ../boundary/UploadListPage.php?type=courseCoordinator"
        );

        exit();
    }
}

// Handle Lecturer upload
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST['uploadType']) &&
    $_POST['uploadType'] === "lecturer"
) {

    $controller =
        new ManageUniversityInformationController();

    $csvFile =
        $_FILES['uploadFile'] ?? null;

    $universityId =
        $_SESSION['university_id'] ?? null;


    // Check university
    if ($universityId === null) {

        $_SESSION['upload_error'] =
            "Unable to identify your university.";

        header(
            "Location: ../boundary/UploadListPage.php?type=lecturer"
        );

        exit();
    }


    $result =
        $controller->uploadLecturerList(
            $csvFile,
            $universityId
        );


    if ($result === true) {

        $_SESSION['upload_success'] =
            "Lecturer list uploaded successfully.";

        header(
            "Location: ../boundary/uploadLecturerPage.php"
        );

        exit();

    } else {

        $_SESSION['upload_error'] =
            $result;

        header(
            "Location: ../boundary/UploadListPage.php?type=lecturer"
        );

        exit();
    }
}

// Handle Student upload
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST['uploadType']) &&
    $_POST['uploadType'] === "student"
) {

    $controller =
        new ManageUniversityInformationController();

    $csvFile =
        $_FILES['uploadFile'] ?? null;

    $universityId =
        $_SESSION['university_id'] ?? null;


    // Check university
    if ($universityId === null) {

        $_SESSION['upload_error'] =
            "Unable to identify your university.";

        header(
            "Location: ../boundary/UploadListPage.php?type=student"
        );

        exit();
    }


    // Upload student list
    $result =
        $controller->uploadStudentList(
            $csvFile,
            $universityId
        );


    // Success
    if ($result === true) {

        $_SESSION['upload_success'] =
            "Student list uploaded successfully.";

        header(
            "Location: ../boundary/uploadStudentListPage.php"
        );

        exit();
    }


    // Failure
    $_SESSION['upload_error'] = $result;

    header(
        "Location: ../boundary/UploadListPage.php?type=student"
    );

    exit();
}

// Handle Floor Plan upload
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST['uploadType']) &&
    $_POST['uploadType'] === "floorPlan"
) {

    $controller =
        new ManageUniversityInformationController();

    $file =
        $_FILES['uploadFile'] ?? null;

    $universityId =
        $_SESSION['university_id'] ?? null;

    $uploadedBy =
        $_SESSION['user_id'] ?? null;


    // Check university
    if ($universityId === null) {

        $_SESSION['upload_error'] =
            "Unable to identify your university.";

        header(
            "Location: ../boundary/UploadListPage.php?type=floorPlan"
        );

        exit();
    }

    // Check user
    if ($uploadedBy === null) {

        $_SESSION['upload_error'] =
            "Unable to identify the current user.";

        header(
            "Location: ../boundary/UploadListPage.php?type=floorPlan"
        );

        exit();
    }

    // Upload
    $result =
        $controller->uploadFloorPlan(
            $file,
            $universityId,
            $uploadedBy
        );

    // Success
    if ($result === true) {

        $_SESSION['upload_success'] =
            "Floor plan uploaded successfully.";

        header(
            "Location: ../boundary/uploadFloorPlanPage.php"
        );

        exit();

    } else {

        $_SESSION['upload_error'] =
            $result;

        header(
            "Location: ../boundary/UploadListPage.php?type=floorPlan"
        );

        exit();
    }
}