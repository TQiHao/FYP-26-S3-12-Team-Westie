<?php

class ChatSession
{
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db;
    }

    public function startSession($userId)
    {
        try {
            $sql = "INSERT INTO AIChatbotSession (userId, startedAt, messageCount, createdAt, updatedAt)
                    VALUES (?, NOW(), 0, NOW(), NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("ChatSession startSession error: " . $e->getMessage());
            return false;
        }
    }

    public function incrementCount($sessionId)
    {
        try {
            $sql = "UPDATE AIChatbotSession SET messageCount = messageCount + 1, updatedAt = NOW()
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$sessionId]);
        } catch (PDOException $e) {
            error_log("ChatSession incrementCount error: " . $e->getMessage());
            return false;
        }
    }

    public function clearSession($sessionId)
    {
        try {
            $sql = "UPDATE AIChatbotSession SET clearedAt = NOW(), updatedAt = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$sessionId]);
        } catch (PDOException $e) {
            error_log("ChatSession clearSession error: " . $e->getMessage());
            return false;
        }
    }
}