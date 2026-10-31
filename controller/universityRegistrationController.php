<?php

session_start();

require_once "../database/database.php";

class UniversityRegistrationController
{
    // Verification documents (proof that the institution is real)
    public const MIN_DOCUMENTS = 1;
    public const MAX_DOCUMENTS = 3;
    public const MAX_DOCUMENT_BYTES = 5242880; // 5 MB each

    public const ALLOWED_DOCUMENT_TYPES = [
        'pdf'  => 'application/pdf',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
    ];

    private $db;
    private $documentDir;
    private $savedFiles = [];

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
        $this->documentDir = dirname(__DIR__) . '/uploads/registration_documents';
    }

    /** Active licence plans for the registration form's dropdown (read from the Licenses table) */
    public function getLicensePlans()
    {
        $sql = "SELECT id, name, durationYears, description
                FROM Licenses
                WHERE status = 'active'
                ORDER BY durationYears ASC";

        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    // The biggest document this server will take
    public function getDocumentLimitText()
    {
        $limit = min(self::MAX_DOCUMENT_BYTES, $this->iniToBytes(ini_get('upload_max_filesize')) ?: PHP_INT_MAX);
        return max(1, (int) floor($limit / 1048576)) . ' MB';
    }

    public function registerUniversity()
    {
        $universityName = trim($_POST['universityName'] ?? '');
        $representativeName = trim($_POST['representativeName'] ?? '');
        $applicantContact = trim($_POST['applicantContact'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $institutionType = trim($_POST['institutionType'] ?? '');
        $country = trim($_POST['country'] ?? '');
        $postalCode = trim($_POST['postalCode'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirmPassword'] ?? '';
        $licenseId = (int) ($_POST['licenseId'] ?? 0);

        if (empty($representativeName)) {
            return "invalid_representative_name";
        }

        if (empty($applicantContact)) {
            return "invalid_contact";
        }

        if ($password === '' || $password !== $confirmPassword) {
            return "password_mismatch";
        }

        if (!preg_match('/^[A-Za-z0-9\s\-]{3,10}$/', $postalCode)) {
            return "invalid_postal";
        }

        // The chosen plan must be a real, active licence plan
        $licenseStmt = $this->db->prepare("SELECT COUNT(*) FROM Licenses WHERE id = ? AND status = 'active'");
        $licenseStmt->execute([$licenseId]);

        if ($licenseStmt->fetchColumn() == 0) {
            return "invalid_license";
        }

        // Verification documents
        $documents = $this->collectDocuments();

        if (count($documents) < self::MIN_DOCUMENTS) {
            return "document_missing";
        }

        if (count($documents) > self::MAX_DOCUMENTS) {
            return "document_count";
        }

        foreach ($documents as $document) {
            if (!$this->isValidDocument($document)) {
                return "document_invalid";
            }
        }

        $checkSql = "SELECT COUNT(*)
                     FROM Universities
                     WHERE name = ? OR email = ?";

        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->execute([
            $universityName,
            $email
        ]);

        if ($checkStmt->fetchColumn() > 0) {
            return "exists";
        }

        $pendingSql = "SELECT COUNT(*)
               FROM UniversityRegistrations
               WHERE (universityName = ? OR applicantEmail = ?)
               AND status = 'pending'";

        $pendingStmt = $this->db->prepare($pendingSql);
        $pendingStmt->execute([
            $universityName,
            $email
        ]);

        if ($pendingStmt->fetchColumn() > 0) {
            return "pending";
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO UniversityRegistrations
                (
                    universityId,
                    universityName,
                    applicantName,
                    applicantEmail,
                    passwordHash,
                    applicantContact,
                    institutionType,
                    country,
                    postalCode,
                    licenseId,
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
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    'pending',
                    NULL,
                    NULL,
                    NULL,
                    NOW(),
                    NOW()
                )";

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare($sql);

            $stmt->execute([
                $universityName,
                $representativeName,
                $email,
                $passwordHash,
                $applicantContact,
                $institutionType,
                $country,
                $postalCode,
                $licenseId
            ]);

            $registrationId = (int) $this->db->lastInsertId();

            if (!$this->saveDocuments($registrationId, $documents)) {
                throw new RuntimeException("The documents could not be saved.");
            }

            $this->db->commit();

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            foreach ($this->savedFiles as $path) {
                @unlink($path);
            }

            error_log("University registration failed: " . $e->getMessage());
            return "document_failed";
        }

        return true;
    }


    // Verification documents
    private function collectDocuments()
    {
        $files = $_FILES['documents'] ?? null;

        if (!$files || !is_array($files['name'] ?? null)) {
            return [];
        }

        $list = [];
        foreach ($files['name'] as $i => $name) {
            if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $list[] = [
                'name'     => (string) $name,
                'tmp_name' => $files['tmp_name'][$i],
                'size'     => (int) $files['size'][$i],
                'error'    => (int) $files['error'][$i],
            ];
        }

        return $list;
    }

    private function isValidDocument($document)
    {
        if ($document['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($document['tmp_name'])) {
            return false;
        }

        if ($document['size'] <= 0 || $document['size'] > self::MAX_DOCUMENT_BYTES) {
            return false;
        }

        $extension = strtolower(pathinfo($document['name'], PATHINFO_EXTENSION));
        if (!isset(self::ALLOWED_DOCUMENT_TYPES[$extension])) {
            return false;
        }

        // The file's real content must match its extension (a renamed .exe is not a PDF)
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $document['tmp_name']);
            finfo_close($finfo);

            if ($mime !== self::ALLOWED_DOCUMENT_TYPES[$extension]) {
                return false;
            }
        }

        return true;
    }

    private function saveDocuments($registrationId, $documents)
    {
        if (!$this->prepareDocumentFolder()) {
            return false;
        }

        $sql = "INSERT INTO RegistrationDocuments (registrationId, documentType, fileName, filePath)
                VALUES (?, 'Institution Verification', ?, ?)";
        $stmt = $this->db->prepare($sql);

        foreach ($documents as $document) {
            $extension = strtolower(pathinfo($document['name'], PATHINFO_EXTENSION));
            $stored    = bin2hex(random_bytes(16)) . '.' . $extension;
            $target    = $this->documentDir . '/' . $stored;

            if (!move_uploaded_file($document['tmp_name'], $target)) {
                return false;
            }
            $this->savedFiles[] = $target;

            // Keep the original name for display only (never used as a path)
            $displayName = preg_replace('/[^\w .()\-]/u', '_', basename($document['name']));

            $stmt->execute([
                $registrationId,
                substr($displayName, 0, 255),
                'uploads/registration_documents/' . $stored
            ]);
        }

        return true;
    }

    private function prepareDocumentFolder()
    {
        if (!is_dir($this->documentDir) && !@mkdir($this->documentDir, 0775, true)) {
            return false;
        }

        $guard = $this->documentDir . '/.htaccess';
        if (!is_file($guard)) {
            @file_put_contents($guard, "Options -Indexes\nRequire all denied\n");
        }

        return true;
    }

    private function iniToBytes($value)
    {
        $value = trim((string) $value);
        if ($value === '' || $value === '0' || $value === '-1') {
            return 0;
        }

        $number = (float) $value;
        switch (strtolower(substr($value, -1))) {
            case 'g':
                $number *= 1024;
            case 'm':
                $number *= 1024;
            case 'k':
                $number *= 1024;
        }

        return (int) $number;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $controller = new UniversityRegistrationController();

    if (empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        $result = "document_invalid";
    } else {
        $result = $controller->registerUniversity();
    }

    if ($result === true) {

        $_SESSION['registration_success'] =
            "University registration request submitted successfully. Please wait for Unibee approval.";

    } elseif ($result === "exists") {

        $_SESSION['registration_error'] =
            "This university has already been registered.";

    } elseif ($result === "pending") {

        $_SESSION['registration_error'] =
            "A registration request for this university is already pending.";

    } elseif ($result === "invalid_representative_name") {

        $_SESSION['registration_error'] =
            "Please enter the representative's name.";

    } elseif ($result === "invalid_contact") {

        $_SESSION['registration_error'] =
            "Please enter a contact number.";

    } elseif ($result === "password_mismatch") {

        $_SESSION['registration_error'] =
            "Passwords do not match. Please try again.";

    } elseif ($result === "invalid_license") {

        $_SESSION['registration_error'] =
            "Please choose a subscription plan.";

    } elseif ($result === "invalid_postal") {

        $_SESSION['registration_error'] =
            "Please enter a valid postal code (3 to 10 alphanumeric characters).";

    } elseif ($result === "document_missing") {

        $_SESSION['registration_error'] =
            "Please upload at least one verification document.";

    } elseif ($result === "document_count") {

        $_SESSION['registration_error'] =
            "You can upload at most " . UniversityRegistrationController::MAX_DOCUMENTS . " documents.";

    } elseif ($result === "document_invalid") {

        $_SESSION['registration_error'] =
            "Each document must be a PDF, JPG or PNG file of at most " . $controller->getDocumentLimitText() . ".";

    } elseif ($result === "document_failed") {

        $_SESSION['registration_error'] =
            "Your registration could not be saved. Please try again.";

    } else {

        $_SESSION['registration_error'] =
            "Unable to submit the registration request.";
    }

    header("Location: ../boundary/universityRegistrationPage.php");
    exit();
}