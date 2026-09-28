<?php

class FAQ
{
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db;
    }

    /**
     * Layer 1: exact match (case-insensitive).
     */
    public function findExactMatch($universityId, $question)
    {
        try {
            $sql = "SELECT id, question, answer
                    FROM FAQs
                    WHERE universityId = ?
                      AND LOWER(TRIM(question)) = LOWER(TRIM(?))
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$universityId, $question]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("findExactMatch error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all FAQs for a university (used by TF-IDF).
     */
    public function getFaqsByUniversity($universityId)
    {
        try {
            $sql = "SELECT id, question, answer
                    FROM FAQs
                    WHERE universityId = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$universityId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("getFaqsByUniversity error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Layer 2: TF-IDF cosine similarity.
     */
    public function findSimilarMatch($universityId, $question, $threshold = 0.25)
    {
        $faqs = $this->getFaqsByUniversity($universityId);
        if (!$faqs) return null;

        $corpus = [];
        foreach ($faqs as $faq) {
            $corpus[] = $faq['question'];
        }
        $corpus[] = $question;

        $tokenize = function ($text) {
            $text = strtolower($text);
            $text = preg_replace('/[^a-z0-9 ]+/', ' ', $text);
            return array_filter(explode(' ', $text));
        };

        $docs = array_map($tokenize, $corpus);
        $docCount = count($docs);
        $userIdx = $docCount - 1;

        $df = [];
        foreach ($docs as $tokens) {
            foreach (array_unique($tokens) as $t) {
                $df[$t] = ($df[$t] ?? 0) + 1;
            }
        }

        $idf = [];
        foreach ($df as $t => $c) {
            $idf[$t] = log($docCount / (1 + $c));
        }

        $vectors = [];
        foreach ($docs as $tokens) {
            $tf = array_count_values($tokens);
            $vec = [];
            foreach ($tf as $t => $c) {
                $vec[$t] = ($c / max(1, count($tokens))) * ($idf[$t] ?? 0);
            }
            $vectors[] = $vec;
        }

        $cosine = function ($a, $b) {
            $dot = 0; $na = 0; $nb = 0;
            foreach ($a as $k => $v) { $dot += $v * ($b[$k] ?? 0); $na += $v * $v; }
            foreach ($b as $v) { $nb += $v * $v; }
            if ($na == 0 || $nb == 0) return 0;
            return $dot / (sqrt($na) * sqrt($nb));
        };

        $bestScore = 0;
        $bestIdx = -1;
        for ($i = 0; $i < $userIdx; $i++) {
            $score = $cosine($vectors[$i], $vectors[$userIdx]);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestIdx = $i;
            }
        }

        if ($bestIdx >= 0 && $bestScore >= $threshold) {
            $match = $faqs[$bestIdx];
            $match['score'] = $bestScore;
            return $match;
        }
        return null;
    }

    public function getFaqById($id)
    {
        try {
            $sql = "SELECT id, question, answer FROM FAQs WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("getFaqById error: " . $e->getMessage());
            return false;
        }
    }
}
?>