<?php

require_once __DIR__ . '/../database/database.php';
require_once __DIR__ . '/../entity/LandingPageContent.php';

class ViewLandingPageController
{
    private $db;

    private $BAD_WORDS = [
        'fuck',
        'fucking',
        'fucked',
        'fucker',
        'shit',
        'shitty',
        'bullshit',
        'damn',
        'dammit',
        'goddamn',
        'ass',
        'asshole',
        'arse',
        'arsehole',
        'bitch',
        'bastard',
        'crap',
        'crappy',
        'dick',
        'prick',
        'cock',
        'pussy',
        'cunt',
        'slut',
        'whore',
        'hoe',
        'wtf',
        'stfu',
        'omfg',
        'idiot',
        'moron',
        'retard',
        'retarded',
        'stupid',
        'dumb',
        'dumbass',
        'dumbo',
        'piss',
        'pissed',
        'frick',
        'fricking'
    ];

    private $NEGATIVE_WORDS = [
        'terrible',
        'awful',
        'horrible',
        'horrendous',
        'atrocious',
        'dreadful',
        'appalling',
        'worst',
        'worse',
        'bad',
        'poor',
        'poorly',
        'lousy',
        'mediocre',
        'meh',
        'garbage',
        'trash',
        'rubbish',
        'useless',
        'worthless',
        'pointless',
        'broken',
        'buggy',
        'glitchy',
        'crash',
        'crashes',
        'crashed',
        'freeze',
        'freezing',
        'froze',
        'frozen',
        'laggy',
        'slow',
        'unresponsive',
        'scam',
        'fraud',
        'fake',
        'misleading',
        'deceptive',
        'disappointing',
        'disappointed',
        'disappointment',
        'unusable',
        'unreliable',
        'unstable',
        'unhelpful',
        'pathetic',
        'disgusting',
        'disgust',
        'repulsive',
        'annoying',
        'annoyed',
        'frustrating',
        'frustrated',
        'confusing',
        'confused',
        'complicated',
        'messy',
        'cluttered',
        'refund',
        'cancel',
        'uninstall',
        'unsubscribe',
        'hate',
        'hatred',
        'loathe',
        'avoid',
        'skip',
        'boycott',
        'suck',
        'sucks',
        'sucked',
        'sucky',
        'waste',
        'wasted',
        'wasteful',
        'regret',
        'rip off',
        'ripoff',
        'disgrace',
        'shameful',
        'dont use',
        'dont buy',
        'dont recommend',
        'dont like',
        'do not use',
        'do not buy',
        'do not recommend',
        'does not work',
        'doesnt work',
        'not recommended',
        'not worth',
        'not good',
        'not great',
        'not useful',
        'not helpful',
        'not working',
        'never use',
        'never again',
        'never buy',
        'no good',
        'no thanks',
        'no thank you',
        'bad to use',
        'bad app',
        'bad experience',
        'stop using',
        'stay away'
    ];

    private $MIN_LENGTH = 20;
    private $MAX_LENGTH = 400;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    public function getHeroContent()
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM LandingPageContent WHERE sectionKey = 'hero' LIMIT 1"
            );
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                return null;
            }

            return new LandingPageContent($row);

        } catch (Exception $e) {
            error_log("getHeroContent error: " . $e->getMessage());
            return null;
        }
    }

    public function getFeatureSlides()
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM LandingPageContent
                 WHERE sectionKey IN ('feature_admin','feature_cc','feature_student','feature_lecturer')
                 ORDER BY FIELD(sectionKey, 'feature_admin','feature_cc','feature_student','feature_lecturer')"
            );
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $slides = [];
            foreach ($rows as $row) {
                $slides[] = new LandingPageContent($row);
            }
            return $slides;

        } catch (Exception $e) {
            error_log("getFeatureSlides error: " . $e->getMessage());
            return [];
        }
    }

    public function getTestimonials()
    {
        $testimonials = [];

        try {
            $stmt = $this->db->prepare(
                "SELECT f.id, f.message, f.rating, f.category, f.createdAt, u.fullName
                 FROM Feedback f
                 JOIN Users u ON f.userId = u.id
                 WHERE f.rating >= 4
                   AND f.message IS NOT NULL
                   AND TRIM(f.message) != ''
                 ORDER BY f.createdAt DESC
                 LIMIT 30"
            );
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                if ($this->isCleanTestimonial($row['message'])) {
                    $testimonials[] = $row;
                    if (count($testimonials) >= 3) {
                        break;
                    }
                }
            }
        } catch (Exception $e) {
            error_log("getTestimonials error: " . $e->getMessage());
        }

        if (empty($testimonials)) {
            $testimonials = [
                ["message" => "The platform is really easy, and I love how everything is organised in one place!", "rating" => 5, "fullName" => "UniBee User", "createdAt" => null],
                ["message" => "It makes managing my university activities much more convenient and saves me a lot of time.", "rating" => 5, "fullName" => "UniBee User", "createdAt" => null],
                ["message" => "I really like the clean and colorful design. The features are useful and easy to understand.", "rating" => 5, "fullName" => "UniBee User", "createdAt" => null]
            ];
        }

        return $testimonials;
    }

    private function isCleanTestimonial($message)
    {
        $msg = trim($message);

        if ($msg === '')
            return false;
        if (mb_strlen($msg) < $this->MIN_LENGTH)
            return false;
        if (mb_strlen($msg) > $this->MAX_LENGTH)
            return false;

        if (preg_match('/https?:\/\/|www\.|\.com|\.net|\.org/i', $msg))
            return false;
        if (preg_match('/\S+@\S+\.\S+/', $msg))
            return false;
        if (preg_match('/(.)\1{5,}/u', $msg))
            return false;

        $letters = preg_replace('/[^a-zA-Z]/', '', $msg);
        if (strlen($letters) >= 10 && strtoupper($letters) === $letters)
            return false;

        $norm = mb_strtolower($msg);
        $norm = str_replace(['’', '‘', '`', '´'], "'", $norm);
        $norm = preg_replace('/\s+/', ' ', $norm);

        if (preg_match('/\b(\w+)\b(?:\s+\1\b){2,}/iu', $norm))
            return false;

        $noApostrophe = str_replace("'", '', $norm);
        $haystacks = [$norm, $noApostrophe];

        foreach (array_merge($this->BAD_WORDS, $this->NEGATIVE_WORDS) as $w) {
            $w = mb_strtolower(trim($w));
            if ($w === '')
                continue;

            $needleNoApos = str_replace("'", '', $w);

            foreach ($haystacks as $hay) {
                if (strpos($w, ' ') !== false || strpos($needleNoApos, ' ') !== false) {
                    if (strpos($hay, $w) !== false || strpos($hay, $needleNoApos) !== false) {
                        return false;
                    }
                } else {
                    if (preg_match('/\b' . preg_quote($w, '/') . '\b/u', $hay))
                        return false;
                    if (preg_match('/\b' . preg_quote($needleNoApos, '/') . '\b/u', $hay))
                        return false;
                }
            }
        }

        return true;
    }
}