<?php

require_once __DIR__ . "/../database/database.php";

/**
 * Entity for the UniversityRegistrations table (and the RegistrationDocuments that belong to it).
 * A university applies here first; the System Admin then approves or rejects the application.
 */
class UniversityRegistrations
{
    private static function connect()
    {
        $database = new Database();
        return $database->connect();
    }

    /** All registrations that are still waiting for a decision, oldest first */
    public static function getPending()
    {
        $sql = "SELECT id, universityName, applicantName, applicantEmail, applicantContact, status, createdAt
                FROM UniversityRegistrations
                WHERE status = 'pending'
                ORDER BY createdAt ASC";

        return self::connect()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /** One registration with the licence plan it chose, or null */
    public static function getById($registrationId)
    {
        $sql = "SELECT r.id, r.universityName, r.applicantName, r.applicantEmail, r.applicantContact,
                       r.institutionType, r.country, r.postalCode, r.status, r.rejectionReason, r.createdAt,
                       r.licenseId, l.name AS licenseName, l.durationYears
                FROM UniversityRegistrations r
                LEFT JOIN Licenses l ON l.id = r.licenseId
                WHERE r.id = ?";

        $stmt = self::connect()->prepare($sql);
        $stmt->execute([$registrationId]);
        $registration = $stmt->fetch(PDO::FETCH_ASSOC);

        return $registration ?: null;
    }

    /** The verification documents uploaded with a registration */
    public static function getDocuments($registrationId)
    {
        $stmt = self::connect()->prepare(
            "SELECT id, documentType, fileName, uploadedAt
             FROM RegistrationDocuments
             WHERE registrationId = ?
             ORDER BY id ASC"
        );
        $stmt->execute([$registrationId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** One document record (where its file is stored), or false */
    public static function getDocumentById($documentId)
    {
        $stmt = self::connect()->prepare(
            "SELECT registrationId, fileName, filePath FROM RegistrationDocuments WHERE id = ?"
        );
        $stmt->execute([$documentId]);
        $document = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        return $document;
    }

    /**
     * Approve a registration: create the university, its administrator login and its licence,
     * then mark the registration approved. Everything is saved together or not at all.
     */
    public static function approve($registrationId, $reviewerId)
    {
        $db = self::connect();

        $fullStmt = $db->prepare("SELECT * FROM UniversityRegistrations WHERE id = ?");
        $fullStmt->execute([$registrationId]);
        $full = $fullStmt->fetch(PDO::FETCH_ASSOC);
        $fullStmt->closeCursor();

        if (!$full) {
            return false;
        }

        try {
            $db->beginTransaction();

            // Create the university
            $db->prepare(
                "INSERT INTO Universities
                    (name, institutionType, email, country, postalCode, status, registrationDate)
                 VALUES (?, ?, ?, ?, ?, 'active', NOW())"
            )->execute([
                $full['universityName'],
                $full['institutionType'],
                $full['applicantEmail'],
                $full['country'],
                $full['postalCode']
            ]);

            $newUniversityId = $db->lastInsertId();

            // Create the university admin login account
            $db->prepare(
                "INSERT INTO Users
                    (universityId, email, passwordHash, fullName, role, status)
                 VALUES (?, ?, ?, ?, 'university_admin', 'active')"
            )->execute([
                $newUniversityId,
                $full['applicantEmail'],
                $full['passwordHash'],
                $full['applicantName']
            ]);

            // Give the university the licence plan it chose when it registered
            if (!empty($full['licenseId'])) {
                $licenseStmt = $db->prepare("SELECT id, durationYears FROM Licenses WHERE id = ?");
                $licenseStmt->execute([$full['licenseId']]);
                $license = $licenseStmt->fetch(PDO::FETCH_ASSOC);
                $licenseStmt->closeCursor();

                if ($license) {
                    $startDate  = date('Y-m-d');
                    $expiryDate = date('Y-m-d', strtotime('+' . (int) $license['durationYears'] . ' years'));

                    $db->prepare(
                        "INSERT INTO UniversityLicenses
                            (universityId, licenseId, startDate, expiryDate, status)
                         VALUES (?, ?, ?, ?, 'active')"
                    )->execute([
                        $newUniversityId,
                        $license['id'],
                        $startDate,
                        $expiryDate
                    ]);

                    $db->prepare("UPDATE Universities SET subscriptionPlan = ? WHERE id = ?")
                       ->execute([(int) $license['durationYears'], $newUniversityId]);
                }
            }

            // Mark the registration approved and link it to the new university
            $db->prepare(
                "UPDATE UniversityRegistrations
                 SET status = 'approved',
                     universityId = ?,
                     reviewedBy = ?,
                     reviewedAt = NOW()
                 WHERE id = ?"
            )->execute([$newUniversityId, $reviewerId, $registrationId]);

            $db->commit();
            return true;

        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return false;
        }
    }

    /** Mark a registration rejected and store the reason */
    public static function reject($registrationId, $reviewerId, $reason)
    {
        $stmt = self::connect()->prepare(
            "UPDATE UniversityRegistrations
             SET status = 'rejected',
                 rejectionReason = ?,
                 reviewedBy = ?,
                 reviewedAt = NOW()
             WHERE id = ?"
        );

        return $stmt->execute([$reason, $reviewerId, $registrationId]);
    }
}