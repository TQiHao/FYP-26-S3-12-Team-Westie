<?php

require_once __DIR__ . "/../database/database.php";

class LandingPageContent
{
    private $id;
    private $sectionKey;
    private $title;
    private $subtitle;
    private $content;
    private $imagePath;
    private $updatedAt;

    public function __construct($data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->sectionKey = $data['sectionKey'] ?? null;
        $this->title = $data['title'] ?? null;
        $this->subtitle = $data['subtitle'] ?? null;
        $this->content = $data['content'] ?? null;
        $this->imagePath = $data['imagePath'] ?? null;
        $this->updatedAt = $data['updatedAt'] ?? null;
    }

    public function getId()
    {
        return $this->id;
    }
    public function getSectionKey()
    {
        return $this->sectionKey;
    }
    public function getTitle()
    {
        return $this->title;
    }
    public function getSubtitle()
    {
        return $this->subtitle;
    }
    public function getContent()
    {
        return $this->content;
    }
    public function getImagePath()
    {
        return $this->imagePath;
    }
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function getFeatureBoxes()
    {
        if (empty($this->content)) {
            return [];
        }
        $decoded = json_decode($this->content, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'sectionKey' => $this->sectionKey,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'content' => $this->content,
            'imagePath' => $this->imagePath,
            'updatedAt' => $this->updatedAt,
        ];
    }

    
    // ------------------------------------------------------------------
    // System Admin: edit the landing page
    // ------------------------------------------------------------------

    private static function connect()
    {
        $database = new Database();
        return $database->connect();
    }

    /** One section of the landing page (hero, feature_student, ...) as a row, or false */
    public static function getSection($sectionKey)
    {
        $stmt = self::connect()->prepare("SELECT * FROM LandingPageContent WHERE sectionKey = ? LIMIT 1");
        $stmt->execute([$sectionKey]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Save several sections together (true), or none of them (false).
     * Each row is [sectionKey, title, subtitle, content]; a section that does not exist yet is created.
     */
    public static function saveSections($rows)
    {
        $db = self::connect();

        // sectionKey is UNIQUE, so an existing section is updated instead of added twice
        $sql = "INSERT INTO LandingPageContent (sectionKey, title, subtitle, content)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    title    = VALUES(title),
                    subtitle = VALUES(subtitle),
                    content  = VALUES(content)";

        try {
            $db->beginTransaction();
            $stmt = $db->prepare($sql);

            foreach ($rows as $row) {
                $stmt->execute($row);
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