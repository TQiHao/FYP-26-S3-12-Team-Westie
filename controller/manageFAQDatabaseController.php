<?php

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
        'weed',
        'marijuana',
        'terrorist',
        'terrorism',
        'bomb',
        'shooting',
        'murder',
        'suicide',
        'kill',
        'killing',
        'nigger',
        'faggot',
        'retard',
        'idiot',
        'stupid',
        'hate',
        'worst',
        'terrible',
        'awful',
        'useless',
        'garbage',
        'trash',
        'crap',
        'sucks',
        'scam',
        'fraud',
        'corrupt',
        'racist',
        'sexist'
    ];

    public function __construct()
    {
        $this->conn = new mysqli("127.0.0.1", "root", "", "unibee", 3307);
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
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

    public function getAllFAQs($universityId, $search = '')
    {
        $sql = "SELECT id, universityId, createdBy, question, answer, createdAt, updatedAt
                   FROM FAQs
                   WHERE universityId = ?";
        $types = "i";
        $params = [$universityId];

        if (trim($search) !== '') {
            $sql .= " AND (question LIKE ? OR answer LIKE ?)";
            $like = '%' . $search . '%';
            $types .= "ss";
            $params[] = $like;
            $params[] = $like;
        }

        $sql .= " ORDER BY createdAt DESC";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $faqs = [];
        while ($row = $result->fetch_assoc()) {
            $faqs[] = new FAQ($row);
        }
        $stmt->close();
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

        $stmt = $this->conn->prepare(
            "UPDATE FAQs SET question = ?, answer = ? WHERE id = ? AND universityId = ?"
        );
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Database error.'];
        }
        $stmt->bind_param("ssii", $question, $answer, $id, $universityId);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok
            ? ['status' => 'ok']
            : ['status' => 'error', 'message' => 'Failed to update FAQ.'];
    }

    public function deleteFAQ($id, $universityId)
    {
        $stmt = $this->conn->prepare("DELETE FROM FAQs WHERE id = ? AND universityId = ?");
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("ii", $id, $universityId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
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

            $stmt->bind_param("iiss", $universityId, $createdBy, $question, $answer);
            if ($stmt->execute()) {
                $imported++;
            } else {
                $skipped++;
                $reasons[] = "Row $rowNum skipped: database error.";
            }
        }
        $stmt->close();
        fclose($handle);

        return [
            'status' => 'ok',
            'imported' => $imported,
            'skipped' => $skipped,
            'reasons' => $reasons,
        ];
    }

    public function close()
    {
        $this->conn->close();
    }
}