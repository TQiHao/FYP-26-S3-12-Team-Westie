<?php

require_once "../database/database.php";
require_once "../entity/systemLog.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (
    !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true ||
    ($_SESSION['user_role'] ?? '') !== 'system_admin'
) {
    header("Location: ../boundary/loginPage.php");
    exit();
}

class ViewRegistrationController 
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    /** Get a single registration by id, for the review page */
    public function getRegistration($registrationId)
    {
         $sql = "SELECT r.id, r.universityName, r.applicantName, r.applicantEmail, r.applicantContact,
                       r.institutionType, r.country, r.postalCode, r.status, r.rejectionReason, r.createdAt,
                       r.licenseId, l.name AS licenseName, l.durationYears
                FROM UniversityRegistrations r
                LEFT JOIN Licenses l ON l.id = r.licenseId
                WHERE r.id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$registrationId]);
        $registration = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $registration ?: null;
    }

    //The verification documents uploaded with a registration
    public function getDocuments($registrationId)
    {
        $stmt = $this->db->prepare(
            "SELECT id, documentType, fileName, uploadedAt
            FROM RegistrationDocuments
            WHERE registrationId = ?
            ORDER BY id ASC"
        );
        $stmt->execute([$registrationId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function streamDocument($documentId)
    {
        $stmt = $this->db->prepare("SELECT registrationId, fileName, filePath FROM RegistrationDocuments WHERE id = ?");
        $stmt->execute([$documentId]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $folder = realpath(dirname(__DIR__) . '/uploads/registration_documents');
        $file   = $doc ? realpath(dirname(__DIR__) . '/' . $doc['filePath']) : false;

        // The resolved file must really be inside the documents folder (blocks ../ tricks)
        if (!$doc || !$folder || !$file || strpos($file, $folder . DIRECTORY_SEPARATOR) !== 0 || !is_file($file)) {
            http_response_code(404);
            echo "Document not found.";
            exit();
        }

        $types = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];
        $ext   = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        SystemLog::record('university', 'info', 'Registration Document Viewed',
            'Document "' . $doc['fileName'] . '" of registration REG-' . str_pad($doc['registrationId'], 4, '0', STR_PAD_LEFT) . ' was viewed.');

        header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
        header('Content-Length: ' . filesize($file));
        header('Content-Disposition: inline; filename="' . preg_replace('/[^\w.\-]/', '_', $doc['fileName']) . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($file);
        exit();
    }

    /** Approve a pending registration **/
    public function approveRegistration($registrationId, $reviewerId)
    {
        $registration = $this->getRegistration($registrationId);
 
        if (!$registration) {
            return "not_found";
        }
 
        if ($registration['status'] !== 'pending') {
            return "already_reviewed";
        }

        $fullSql = "SELECT * FROM UniversityRegistrations WHERE id = ?";
        $fullStmt = $this->db->prepare($fullSql);
        $fullStmt->execute([$registrationId]);
        $full = $fullStmt->fetch(PDO::FETCH_ASSOC);
 
        try {
            $this->db->beginTransaction();
 
            // Create the university
            $insertUniSql = "INSERT INTO Universities
                                (name, institutionType, email, country, postalCode, status, registrationDate)
                              VALUES (?, ?, ?, ?, ?, 'active', NOW())";
            $insertUniStmt = $this->db->prepare($insertUniSql);
            $insertUniStmt->execute([
                $full['universityName'],
                $full['institutionType'],
                $full['applicantEmail'],
                $full['country'],
                $full['postalCode']
            ]);
 
            $newUniversityId = $this->db->lastInsertId();

            // Create the university admin login account
            $insertUserSql = "INSERT INTO Users
                                (universityId, email, passwordHash, fullName, role, status)
                              VALUES (?, ?, ?, ?, 'university_admin', 'active')";
            $insertUserStmt = $this->db->prepare($insertUserSql);
            $insertUserStmt->execute([
                $newUniversityId,
                $full['applicantEmail'],
                $full['passwordHash'],
                $full['applicantName']
            ]);

            // Give the university the licence plan it chose when it registered
            if (!empty($full['licenseId'])) {
                $licenseStmt = $this->db->prepare("SELECT id, durationYears FROM Licenses WHERE id = ?");
                $licenseStmt->execute([$full['licenseId']]);
                $license = $licenseStmt->fetch(PDO::FETCH_ASSOC);

                if ($license) {
                    $startDate  = date('Y-m-d');
                    $expiryDate = date('Y-m-d', strtotime('+' . (int) $license['durationYears'] . ' years'));

                    $insertLicenseSql = "INSERT INTO UniversityLicenses
                                            (universityId, licenseId, startDate, expiryDate, status)
                                         VALUES (?, ?, ?, ?, 'active')";
                    $this->db->prepare($insertLicenseSql)->execute([
                        $newUniversityId,
                        $license['id'],
                        $startDate,
                        $expiryDate
                    ]);

                    $this->db->prepare("UPDATE Universities SET subscriptionPlan = ? WHERE id = ?")
                        ->execute([(int) $license['durationYears'], $newUniversityId]);
                }
            }

             // Mark the registration approved and link it to the new university
            $updateRegSql = "UPDATE UniversityRegistrations
                              SET status = 'approved',
                                  universityId = ?,
                                  reviewedBy = ?,
                                  reviewedAt = NOW()
                              WHERE id = ?";
            $updateRegStmt = $this->db->prepare($updateRegSql);
            $updateRegStmt->execute([$newUniversityId, $reviewerId, $registrationId]);
 
            $this->db->commit();
            return true;
 
        } catch (PDOException $e) {
            $this->db->rollBack();
            return "error";
        }
    }

    /** Reject a pending registration. A reason is required. */
    public function rejectRegistration($registrationId, $reviewerId, $reason)
    {
        $reason = trim($reason);
 
        if (empty($reason)) {
            return "missing_reason";
        }
 
        $registration = $this->getRegistration($registrationId);
 
        if (!$registration) {
            return "not_found";
        }
 
        if ($registration['status'] !== 'pending') {
            return "already_reviewed";
        }
 
        $sql = "UPDATE UniversityRegistrations
                SET status = 'rejected',
                    rejectionReason = ?,
                    reviewedBy = ?,
                    reviewedAt = NOW()
                WHERE id = ?";
 
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$reason, $reviewerId, $registrationId]);
    }
}

// Handle GET: open a verification document
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET['document'])) {
    $controller = new ViewRegistrationController();
    $controller->streamDocument((int) $_GET['document']);
}

// Handle POST actions (Approve / Reject)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && isset($_POST['registration_id'])) {

    $controller = new ViewRegistrationController();
    $registrationId = (int) $_POST['registration_id'];
    $reviewerId = $_SESSION['user_id'];

    if ($_POST['action'] === 'approve') {

        $result = $controller->approveRegistration($registrationId, $reviewerId);

        if ($result === true) {
            $_SESSION['registration_review_success'] = "University registration approved.";
            SystemLog::record('university', 'info', 'Registration Approved',
                'University registration REG-' . str_pad($registrationId, 4, '0', STR_PAD_LEFT) . ' was approved.');
        } else {
            $_SESSION['registration_review_error'] = "Unable to approve this registration. Please try again.";
        }

    } elseif ($_POST['action'] === 'reject') {

        $reason = $_POST['rejection_reason'] ?? '';
        $result = $controller->rejectRegistration($registrationId, $reviewerId, $reason);

        if ($result === true) {
            $_SESSION['registration_review_success'] = "University registration rejected.";
            SystemLog::record('university', 'info', 'Registration Rejected',
                'University registration REG-' . str_pad($registrationId, 4, '0', STR_PAD_LEFT) . ' was rejected.');
        } elseif ($result === "missing_reason") {
            $_SESSION['registration_review_error'] = "Please provide a reason for rejecting this registration.";
            header("Location: ../boundary/viewRegistrationPage.php?id=" . $registrationId);
            exit();
        } else {
            $_SESSION['registration_review_error'] = "Unable to reject this registration. Please try again.";
        }
    }

    header("Location: ../boundary/manageUniversityPage.php");
    exit();
}