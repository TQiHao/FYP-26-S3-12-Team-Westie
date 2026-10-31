<?php

require_once "../database/database.php";

/**
 * Entity for the AIModelVersions table.
 *
 * Life of one row (the "status" column):
 *   approved  -> the file passed validation and is waiting for the System Admin to confirm
 *   deployed  -> this is the model that is live right now (only one row at a time)
 *   archived  -> it was live once and has been replaced by a newer version
 *   failed    -> installation was attempted but failed, the previous version was restored
 */
class AIModelVersions
{
    public const STATUS_APPROVED = 'approved';
    public const STATUS_DEPLOYED = 'deployed';
    public const STATUS_ARCHIVED = 'archived';
    public const STATUS_FAILED   = 'failed';

    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    /** The model that is live right now */
    public function getDeployed()
    {
        $sql = "SELECT m.*, sa.fullName AS updatedByName
                FROM AIModelVersions m
                LEFT JOIN SystemAdmins sa ON sa.id = m.updatedBy
                WHERE m.status = 'deployed'
                ORDER BY m.deployedAt DESC, m.id DESC
                LIMIT 1";

        return $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM AIModelVersions WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getHistory($limit = 10)
    {
        $sql = "SELECT m.id, m.modelName, m.version, m.status, m.createdAt, m.approvedAt, m.deployedAt,
                       sa.fullName AS updatedByName
                FROM AIModelVersions m
                LEFT JOIN SystemAdmins sa ON sa.id = m.updatedBy
                ORDER BY m.createdAt DESC, m.id DESC
                LIMIT " . (int) $limit;

        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    //True when this name + version is already registered
    public function versionExists($modelName, $version)
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM AIModelVersions
             WHERE modelName = ? AND version = ? AND status <> 'failed'"
        );
        $stmt->execute([$modelName, $version]);
        return (int) $stmt->fetchColumn() > 0;
    }

    //Saves a validated model file as "approved". Returns the new row id.
    public function createApproved($modelName, $version, $releaseNotes, $filePath, $adminId)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO AIModelVersions (modelName, version, releaseNotes, filePath, status, approvedAt, updatedBy)
             VALUES (?, ?, ?, ?, 'approved', NOW(), ?)"
        );
        $stmt->execute([$modelName, $version, $releaseNotes, $filePath, $adminId]);
        return (int) $this->db->lastInsertId();
    }

    public function replaceDeployed($currentId, $newId, $adminId)
    {
        $this->db->beginTransaction();
        try {
            if ($currentId) {
                $this->db->prepare("UPDATE AIModelVersions SET status = 'archived' WHERE id = ?")
                    ->execute([$currentId]);
            }

            $this->db->prepare(
                "UPDATE AIModelVersions SET status = 'deployed', deployedAt = NOW(), updatedBy = ? WHERE id = ?"
            )->execute([$adminId, $newId]);

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("AIModelVersions::replaceDeployed failed: " . $e->getMessage());
            return false;
        }
    }

    public function markFailed($id)
    {
        $stmt = $this->db->prepare("UPDATE AIModelVersions SET status = 'failed' WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM AIModelVersions WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function deleteUnusedApproved($adminId)
    {
        $stmt = $this->db->prepare(
            "SELECT id, filePath FROM AIModelVersions WHERE status = 'approved' AND updatedBy = ?"
        );
        $stmt->execute([$adminId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $this->delete($row['id']);
        }

        return array_column($rows, 'filePath');
    }
}