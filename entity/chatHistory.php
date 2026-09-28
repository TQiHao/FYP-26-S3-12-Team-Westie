<?php

class ChatHistory
{
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db;
    }

    public function save($userId, $sessionId, $question, $answer, $source = 'faq')
    {
        try {
            $sql = "INSERT INTO ChatHistory (userId, sessionId, question, answer, source, createdAt)
                    VALUES (?, ?, ?, ?, ?, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $sessionId, $question, $answer, $source]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("ChatHistory save error: " . $e->getMessage());
            return false;
        }
    }

    public function getByUserId($userId, $limit = 20)
    {
        try {
            $sql = "SELECT id, userId, sessionId, question, answer, source, createdAt
                    FROM ChatHistory
                    WHERE userId = ?
                    ORDER BY createdAt DESC
                    LIMIT " . (int)$limit;
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ChatHistory getByUserId error: " . $e->getMessage());
            return false;
        }
    }

    public function getById($id, $userId)
    {
        try {
            $sql = "SELECT id, userId, sessionId, question, answer, source, createdAt
                    FROM ChatHistory
                    WHERE id = ? AND userId = ?
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id, $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ChatHistory getById error: " . $e->getMessage());
            return false;
        }
    }

    public function clearByUserId($userId)
    {
        try {
            $sql = "DELETE FROM ChatHistory WHERE userId = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("ChatHistory clearByUserId error: " . $e->getMessage());
            return false;
        }
    }

    public function countByUserId($userId)
    {
        try {
            $sql = "SELECT COUNT(*) FROM ChatHistory WHERE userId = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("ChatHistory countByUserId error: " . $e->getMessage());
            return 0;
        }
    }
}