<?php

class Users
{
    private $id;
    private $universityId;
    private $email;
    private $passwordHash;
    private $fullName;
    private $role;
    private $status;
    private $createdAt;
    private $updatedAt;
    private $lastLogin;

    public function __construct(
        $id = null,
        $universityId = null,
        $email = null,
        $passwordHash = null,
        $fullName = null,
        $role = null,
        $status = null,
        $createdAt = null,
        $updatedAt = null,
        $lastLogin = null
    ) {
        $this->id = $id;
        $this->universityId = $universityId;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->fullName = $fullName;
        $this->role = $role;
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->lastLogin = $lastLogin;
    }

    // Getters
    public function getId()
    {
        return $this->id;
    }

    public function getUniversityId()
    {
        return $this->universityId;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getPasswordHash()
    {
        return $this->passwordHash;
    }

    public function getFullName()
    {
        return $this->fullName;
    }

    public function getRole()
    {
        return $this->role;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function getLastLogin()
    {
        return $this->lastLogin;
    }

    // Setters
    public function setId($id)
    {
        $this->id = $id;
    }

    public function setUniversityId($universityId)
    {
        $this->universityId = $universityId;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function setPasswordHash($passwordHash)
    {
        $this->passwordHash = $passwordHash;
    }

    public function setFullName($fullName)
    {
        $this->fullName = $fullName;
    }

    public function setRole($role)
    {
        $this->role = $role;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    public function setLastLogin($lastLogin)
    {
        $this->lastLogin = $lastLogin;
    }

    public function getCourseCoordinatorsByUniversity($universityId)
    {
        $database = new Database();
        $db = $database->connect();

        try {

            $sql = "SELECT
                    id,
                    universityId,
                    email,
                    fullName,
                    role,
                    status,
                    createdAt,
                    updatedAt
                FROM Users
                WHERE universityId = ?
                  AND role = 'course_coordinator'
                ORDER BY id ASC";

            $stmt = $db->prepare($sql);

            $stmt->execute([
                $universityId
            ]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {

            return [];

        }
    }

    public function courseCoordinatorExists($email)
    {
        $database = new Database();
        $db = $database->connect();

        try {

            $sql = "SELECT id
                FROM Users
                WHERE email = ?
                LIMIT 1";

            $stmt = $db->prepare($sql);

            $stmt->execute([
                $email
            ]);

            return $stmt->fetch(PDO::FETCH_ASSOC) !== false;

        } catch (PDOException $e) {

            return false;

        }
    }

    public function uploadCourseCoordinatorList($rows, $universityId)
    {
        $database = new Database();
        $db = $database->connect();

        try {

            $db->beginTransaction();

            $sql = "INSERT INTO Users
                (
                    universityId,
                    email,
                    passwordHash,
                    fullName,
                    role,
                    status,
                    createdAt,
                    updatedAt
                )
                VALUES
                (
                    ?, ?, ?, ?, 'course_coordinator',
                    'active', NOW(), NOW()
                )";

            $stmt = $db->prepare($sql);

            foreach ($rows as $row) {

                $passwordHash = password_hash(
                    $row['password'],
                    PASSWORD_DEFAULT
                );

                $stmt->execute([
                    $universityId,
                    $row['email'],
                    $passwordHash,
                    $row['fullName']
                ]);
            }

            $db->commit();

            return true;

        } catch (PDOException $e) {

            if ($db->inTransaction()) {
                $db->rollBack();
            }

            return false;

        }
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

    public function getLecturersByUniversity($universityId)
    {
        $database = new Database();
        $db = $database->connect();

        try {

            $sql = "SELECT
                    id,
                    universityId,
                    email,
                    fullName,
                    role,
                    status,
                    createdAt,
                    updatedAt
                FROM Users
                WHERE universityId = ?
                  AND role = 'lecturer'
                ORDER BY id ASC";

            $stmt = $db->prepare($sql);

            $stmt->execute([
                $universityId
            ]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {

            return [];

        }
    }

    public function lecturerExists($email)
    {
        $database = new Database();
        $db = $database->connect();

        try {

            $sql = "SELECT id
                FROM Users
                WHERE email = ?
                LIMIT 1";

            $stmt = $db->prepare($sql);

            $stmt->execute([
                $email
            ]);

            return $stmt->fetch(PDO::FETCH_ASSOC) !== false;

        } catch (PDOException $e) {

            return false;

        }
    }

    public function uploadLecturerList($rows, $universityId)
    {
        $database = new Database();
        $db = $database->connect();

        try {

            $db->beginTransaction();

            $sql = "INSERT INTO Users
                (
                    universityId,
                    email,
                    passwordHash,
                    fullName,
                    role,
                    status,
                    createdAt,
                    updatedAt
                )
                VALUES
                (
                    ?, ?, ?, ?, 'lecturer',
                    'active', NOW(), NOW()
                )";

            $stmt = $db->prepare($sql);

            foreach ($rows as $row) {

                $passwordHash = password_hash(
                    $row['password'],
                    PASSWORD_DEFAULT
                );

                $stmt->execute([
                    $universityId,
                    $row['email'],
                    $passwordHash,
                    $row['fullName']
                ]);
            }

            $db->commit();

            return true;

        } catch (PDOException $e) {

            if ($db->inTransaction()) {
                $db->rollBack();
            }

            return false;
        }
    }

    // Check whether an email already exists.
    public function studentEmailExists($email)
    {
        $database = new Database();
        $db = $database->connect();

        try {

            $sql = "SELECT id
                    FROM Users
                    WHERE email = ?
                    LIMIT 1";

            $stmt = $db->prepare($sql);

            $stmt->execute([
                $email
            ]);

            return $stmt->fetch(PDO::FETCH_ASSOC) !== false;

        } catch (PDOException $e) {

            return false;
        }
    }


    // Create a student account in Users.
    public function createStudentUser(
        $universityId,
        $email,
        $password,
        $fullName
    ) {
        $database = new Database();
        $db = $database->connect();

        try {

            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $sql = "INSERT INTO Users
                    (
                        universityId,
                        email,
                        passwordHash,
                        fullName,
                        role,
                        status,
                        createdAt,
                        updatedAt
                    )
                    VALUES
                    (
                        ?, ?, ?, ?, 'student',
                        'active', NOW(), NOW()
                    )";

            $stmt = $db->prepare($sql);

            $stmt->execute([
                $universityId,
                $email,
                $passwordHash,
                $fullName
            ]);

            return $db->lastInsertId();

        } catch (PDOException $e) {

            return false;
        }
    }
}
