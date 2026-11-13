<?php

require_once "../controller/authGuard.php";
requireRole(['university_admin']);

require_once "../database/database.php";

class FAQ
{
    private $id;
    private $universityId;
    private $createdBy;
    private $question;
    private $answer;
    private $createdAt;
    private $updatedAt;

    public function __construct($data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->universityId = $data['universityId'] ?? null;
        $this->createdBy = $data['createdBy'] ?? null;
        $this->question = $data['question'] ?? '';
        $this->answer = $data['answer'] ?? '';
        $this->createdAt = $data['createdAt'] ?? null;
        $this->updatedAt = $data['updatedAt'] ?? null;
    }

    public function getId()
    {
        return $this->id;
    }
    public function getUniversityId()
    {
        return $this->universityId;
    }
    public function getCreatedBy()
    {
        return $this->createdBy;
    }
    public function getQuestion()
    {
        return $this->question;
    }
    public function getAnswer()
    {
        return $this->answer;
    }
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'universityId' => $this->universityId,
            'createdBy' => $this->createdBy,
            'question' => $this->question,
            'answer' => $this->answer,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }
}

class ManageFAQDatabaseController
{
    private $conn;

    private $blockedWords = [
        'fuck',
        'fucking',
        'fucked',
        'shit',
        'bullshit',
        'damn',
        'bitch',
        'bastard',
        'asshole',
        'dick',
        'pussy',
        'whore',
        'slut',
        'cunt',
        'cock',
        'piss',
        'porn',
        'porno',
        'rape',
        'raped',
        'molest',
        'cocaine',
        'heroin',
        'meth',
        'terrorist',
        'terrorism',
        'bomb',
        'murder',
        'suicide',
        'nigger',
        'faggot',
        'retard'
    ];

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    private function isBlocked($text)
    {
        $lower = strtolower((string) $text);
        foreach ($this->blockedWords as $word) {
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/i', $lower)) {
                return true;
            }
        }
        return false;
    }

    private function questionExists($universityId, $question)
    {
        $stmt = $this->conn->prepare(
            "SELECT id FROM FAQs WHERE universityId = ? AND LOWER(TRIM(question)) = LOWER(TRIM(?)) LIMIT 1"
        );
        $stmt->execute([$universityId, $question]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    public function getAllFAQs($universityId, $search = '')
    {
        $sql = "SELECT id, universityId, createdBy, question, answer, createdAt, updatedAt
                FROM FAQs
                WHERE universityId = ?";
        $params = [$universityId];

        if (trim($search) !== '') {
            $sql .= " AND (question LIKE ? OR answer LIKE ?)";
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $sql .= " ORDER BY createdAt DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        $faqs = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $faqs[] = new FAQ($row);
        }
        return $faqs;
    }

    public function updateFAQ($id, $universityId, $question, $answer)
    {
        $question = trim($question);
        $answer = trim($answer);

        if ($question === '' || $answer === '') {
            return ['status' => 'error', 'message' => 'Question and answer cannot be empty.'];
        }
        if ($this->isBlocked($question) || $this->isBlocked($answer)) {
            return ['status' => 'error', 'message' => 'Content contains inappropriate language and cannot be saved.'];
        }

        $dupStmt = $this->conn->prepare(
            "SELECT id FROM FAQs WHERE universityId = ? AND LOWER(TRIM(question)) = LOWER(TRIM(?)) AND id <> ? LIMIT 1"
        );
        $dupStmt->execute([$universityId, $question, $id]);
        if ($dupStmt->fetch(PDO::FETCH_ASSOC)) {
            return ['status' => 'error', 'message' => 'An FAQ with the same question already exists.'];
        }

        $stmt = $this->conn->prepare(
            "UPDATE FAQs SET question = ?, answer = ? WHERE id = ? AND universityId = ?"
        );
        $ok = $stmt->execute([$question, $answer, $id, $universityId]);

        return $ok
            ? ['status' => 'ok']
            : ['status' => 'error', 'message' => 'Failed to update FAQ.'];
    }

    public function deleteFAQ($id, $universityId)
    {
        $stmt = $this->conn->prepare("DELETE FROM FAQs WHERE id = ? AND universityId = ?");
        return $stmt->execute([$id, $universityId]);
    }

    public function importFromCSV($universityId, $userId, $filePath)
    {
        $empty = ['imported' => 0, 'skipped' => 0, 'reasons' => []];

        if (!is_uploaded_file($filePath) && !file_exists($filePath)) {
            return array_merge(['status' => 'error', 'message' => 'File not received.'], $empty);
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return array_merge(['status' => 'error', 'message' => 'Cannot open file.'], $empty);
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return array_merge(['status' => 'error', 'message' => 'The CSV file is empty.'], $empty);
        }

        $headerLower = array_map(function ($h) {
            return strtolower(trim($h));
        }, $header);

        $qIdx = array_search('question', $headerLower);
        $aIdx = array_search('answer', $headerLower);

        if ($qIdx === false || $aIdx === false) {
            fclose($handle);
            return array_merge(
                ['status' => 'error', 'message' => 'CSV must contain "question" and "answer" columns in the header row.'],
                $empty
            );
        }

        $imported = 0;
        $skipped = 0;
        $reasons = [];
        $seenInFile = [];

        $stmt = $this->conn->prepare(
            "INSERT INTO FAQs (universityId, createdBy, question, answer) VALUES (?, ?, ?, ?)"
        );
        $createdBy = $userId > 0 ? $userId : null;

        $rowNum = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;

            if (count($row) <= max($qIdx, $aIdx)) {
                $skipped++;
                $reasons[] = "Row $rowNum skipped: missing column.";
                continue;
            }

            $question = trim($row[$qIdx] ?? '');
            $answer = trim($row[$aIdx] ?? '');

            if ($question === '' || $answer === '') {
                $skipped++;
                $reasons[] = "Row $rowNum skipped: empty question or answer.";
                continue;
            }

            if ($this->isBlocked($question) || $this->isBlocked($answer)) {
                $skipped++;
                $reasons[] = "Row $rowNum skipped: inappropriate or negative content.";
                continue;
            }

            $key = strtolower($question);
            if (isset($seenInFile[$key])) {
                $skipped++;
                $reasons[] = "Row $rowNum skipped: duplicate question within the CSV file.";
                continue;
            }
            $seenInFile[$key] = true;

            if ($this->questionExists($universityId, $question)) {
                $skipped++;
                $reasons[] = "Row $rowNum skipped: this FAQ already exists in the database.";
                continue;
            }

            if ($stmt->execute([$universityId, $createdBy, $question, $answer])) {
                $imported++;
            } else {
                $skipped++;
                $reasons[] = "Row $rowNum skipped: database error.";
            }
        }
        fclose($handle);

        return [
            'status' => 'ok',
            'imported' => $imported,
            'skipped' => $skipped,
            'reasons' => $reasons,
        ];
    }
}
?>