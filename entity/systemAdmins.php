<?php

require_once __DIR__ . "/../database/database.php";

/**
 * Entity for the SystemAdmins table and the profile details (SystemAdminProfiles) that go with it.
 */
class SystemAdmins
{
    private static function connect()
    {
        $database = new Database();
        return $database->connect();
    }

    /** A System Admin with their profile details, or null */
    public static function getProfile($systemAdminId)
    {
        $sql = "SELECT
                    sa.id,
                    sa.email,
                    sa.fullName,
                    sap.department,
                    sap.position,
                    sap.contactNumber
                FROM SystemAdmins sa
                LEFT JOIN SystemAdminProfiles sap ON sap.systemAdminId = sa.id
                WHERE sa.id = ?";

        $stmt = self::connect()->prepare($sql);
        $stmt->execute([$systemAdminId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);

        return $profile ?: null;
    }

    /** Save the name and profile details together (true), or nothing at all (false) */
    public static function updateProfile($systemAdminId, $fullName, $department, $position, $contactNumber)
    {
        $db = self::connect();

        try {
            $db->beginTransaction();

            $stmt = $db->prepare("UPDATE SystemAdmins SET fullName = ?, updatedAt = NOW() WHERE id = ?");
            $stmt->execute([$fullName, $systemAdminId]);

            $checkStmt = $db->prepare("SELECT COUNT(*) FROM SystemAdminProfiles WHERE systemAdminId = ?");
            $checkStmt->execute([$systemAdminId]);

            if ($checkStmt->fetchColumn() > 0) {
                $stmt = $db->prepare(
                    "UPDATE SystemAdminProfiles
                     SET department = ?, position = ?, contactNumber = ?, updatedAt = NOW()
                     WHERE systemAdminId = ?"
                );
                $stmt->execute([$department, $position, $contactNumber, $systemAdminId]);
            } else {
                $stmt = $db->prepare(
                    "INSERT INTO SystemAdminProfiles
                        (systemAdminId, department, position, contactNumber, createdAt, updatedAt)
                     VALUES (?, ?, ?, ?, NOW(), NOW())"
                );
                $stmt->execute([$systemAdminId, $department, $position, $contactNumber]);
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
}