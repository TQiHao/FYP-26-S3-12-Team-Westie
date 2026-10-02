<?php
require_once '../database/database.php';

/* ============================================================
 * Filter lists 
 * ============================================================ */

$BAD_WORDS = [
    // Profanity
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
    'fricking',
];

$NEGATIVE_WORDS = [
    // Strong negatives
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
    // Phrasal negatives (no apostrophe — apostrophes are normalized away)
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
    'stay away',
];

$MIN_LENGTH = 20;
$MAX_LENGTH = 400;


function isCleanTestimonial($message, $badWords, $negativeWords, $minLen, $maxLen)
{
    $msg = trim($message);

    if ($msg === '')
        return false;
    if (mb_strlen($msg) < $minLen)
        return false;
    if (mb_strlen($msg) > $maxLen)
        return false;

    // Reject URLs / emails
    if (preg_match('/https?:\/\/|www\.|\.com|\.net|\.org/i', $msg))
        return false;
    if (preg_match('/\S+@\S+\.\S+/', $msg))
        return false;

    // Reject repeated characters (e.g. "aaaaaaa", "!!!!!!")
    if (preg_match('/(.)\1{5,}/u', $msg))
        return false;

    // Reject ALL CAPS
    $letters = preg_replace('/[^a-zA-Z]/', '', $msg);
    if (strlen($letters) >= 10 && strtoupper($letters) === $letters)
        return false;

    // Normalize: lowercase, convert curly apostrophes, collapse whitespace
    $norm = mb_strtolower($msg);
    $norm = str_replace(['’', '‘', '`', '´'], "'", $norm);
    $norm = preg_replace('/\s+/', ' ', $norm);

    // ===== Repeated word (3+ times in a row) — "nice nice nice nice" =====
    if (preg_match('/\b(\w+)\b(?:\s+\1\b){2,}/iu', $norm)) {
        return false;
    }

    // ===== Check word boundaries with apostrophe AND without apostrophe =====
    // e.g. "don't use" and "dont use" both map to "dont use"
    $noApostrophe = str_replace("'", '', $norm);

    $haystacks = [$norm, $noApostrophe];

    foreach (array_merge($badWords, $negativeWords) as $w) {
        $w = mb_strtolower(trim($w));
        if ($w === '')
            continue;

        // Try both with and without apostrophes
        $needle = $w;
        $needleNoApos = str_replace("'", '', $w);

        foreach ($haystacks as $hay) {
            // Word-boundary match; use \b except for phrases with spaces
            if (strpos($needle, ' ') !== false || strpos($needleNoApos, ' ') !== false) {
                if (strpos($hay, $needle) !== false || strpos($hay, $needleNoApos) !== false) {
                    return false;
                }
            } else {
                if (preg_match('/\b' . preg_quote($needle, '/') . '\b/u', $hay))
                    return false;
                if (preg_match('/\b' . preg_quote($needleNoApos, '/') . '\b/u', $hay))
                    return false;
            }
        }
    }

    return true;
}

/* ============================================================
 * Fetch + filter
 * ============================================================ */

$testimonials = [];

try {
    $db = new Database();
    $pdo = $db->connect();

    // Grab more rows than needed so we still have 3 after filtering
    $stmt = $pdo->prepare("
        SELECT 
            f.id,
            f.message,
            f.rating,
            f.category,
            f.createdAt,
            u.fullName
        FROM Feedback f
        JOIN Users u ON f.userId = u.id
        WHERE f.rating >= 4
          AND f.message IS NOT NULL
          AND TRIM(f.message) != ''
        ORDER BY f.createdAt DESC
        LIMIT 30
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        if (isCleanTestimonial($row['message'], $BAD_WORDS, $NEGATIVE_WORDS, $MIN_LENGTH, $MAX_LENGTH)) {
            $testimonials[] = $row;
            if (count($testimonials) >= 3)
                break;
        }
    }
} catch (Exception $e) {
    error_log("Landing testimonials error: " . $e->getMessage());
}

// Fallback if no qualifying feedback
if (empty($testimonials)) {
    $testimonials = [
        ["message" => "The platform is really easy, and I love how everything is organised in one place!", "rating" => 5, "fullName" => "UniBee User", "createdAt" => null],
        ["message" => "It makes managing my university activities much more convenient and saves me a lot of time.", "rating" => 5, "fullName" => "UniBee User", "createdAt" => null],
        ["message" => "I really like the clean and colorful design. The features are useful and easy to understand.", "rating" => 5, "fullName" => "UniBee User", "createdAt" => null]
    ];
}

/* ============================================================
 * Helpers
 * ============================================================ */

function cleanInput($data)
{
    return htmlspecialchars(stripslashes(trim($data)));
}

function formatTestimonialDate($datetime)
{
    if (empty($datetime))
        return '';
    $ts = strtotime($datetime);
    if (!$ts)
        return '';

    $diff = time() - $ts;
    if ($diff < 60)
        return 'Just now';
    if ($diff < 3600)
        return floor($diff / 60) . ' min ago';
    if ($diff < 86400)
        return floor($diff / 3600) . ' hour' . (floor($diff / 3600) === 1 ? '' : 's') . ' ago';
    if ($diff < 604800)
        return floor($diff / 86400) . ' day' . (floor($diff / 86400) === 1 ? '' : 's') . ' ago';

    return date('d M Y', $ts);
}

function getDisplayName($fullName)
{
    $parts = preg_split('/\s+/', trim($fullName));
    if (count($parts) === 0 || $parts[0] === '')
        return 'UniBee User';
    if (count($parts) === 1)
        return $parts[0];
    return $parts[0] . ' ' . strtoupper(substr(end($parts), 0, 1)) . '.';
}

function getInitials($fullName)
{
    $parts = preg_split('/\s+/', trim($fullName));
    if (count($parts) === 0 || $parts[0] === '')
        return '?';
    if (count($parts) === 1)
        return strtoupper(substr($parts[0], 0, 1));
    return strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniBee Platform</title>
    <link rel="stylesheet" href="../style.css">
</head>

<body>

    <header>
        <div class="logo-container">
            <img src="../images/uniBeeLogo.png" alt="UniBee Logo">
        </div>

        <nav class="landing-nav">
            <a href="loginPage.php">Log In</a>
            <a href="universityRegistrationPage.php" class="get-started-btn">Get started</a>
        </nav>
    </header>

    <section class="hero" style="background: url('../images/landingpgCampus.avif') center/cover no-repeat;">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <div class="hero-left">
                <h1>Connect Smarter,<br>Bee Smarter.</h1>
            </div>
            <div class="hero-divider"></div>
            <div class="hero-right">
                <p>Your smart campus hub for effortless learning, schedules, and collaboration.</p>
                <a href="universityRegistrationPage.php" class="btn-primary">Get Started Today</a>
            </div>
        </div>
    </section>

    <section class="video-section">
        <div class="video-container">
            <video controls poster="../images/landingpgCampus.avif">
                <source src="" type="video/mp4">
                Your browser does not support video playback.
            </video>
        </div>
    </section>

    <section class="features-section">
        <div class="features-wrapper">

            <div class="role-slide active">
                <h2 class="features-title">Features for University Admin</h2>
                <div class="features-card">
                    <button class="arrow-btn prev-btn" onclick="changeSlide(-1)">&#9664;</button>
                    <div class="features-grid">
                        <div class="feature-box">
                            <h3>Manage Facilities Booking</h3>
                            <div class="dashed-line"></div>
                            <p>Manage facilities to enable students to book campus space.</p>
                        </div>
                        <div class="feature-box">
                            <h3>Manage University Information</h3>
                            <div class="dashed-line"></div>
                            <p>Manage and update university information to keep campus resources accurate and
                                accessible.</p>
                        </div>
                    </div>
                    <button class="arrow-btn next-btn" onclick="changeSlide(1)">&#9654;</button>
                </div>
            </div>

            <div class="role-slide">
                <h2 class="features-title">Features for Course Coordinator</h2>
                <div class="features-card">
                    <button class="arrow-btn prev-btn" onclick="changeSlide(-1)">&#9664;</button>
                    <div class="features-grid">
                        <div class="feature-box">
                            <h3>Manage Classes</h3>
                            <div class="dashed-line"></div>
                            <p>Create and manage class information and schedules for students and lecturers.</p>
                        </div>
                        <div class="feature-box">
                            <h3>AI Chatbot</h3>
                            <div class="dashed-line"></div>
                            <p>Get quick answers and smart classroom suggestions for class scheduling.</p>
                        </div>
                    </div>
                    <button class="arrow-btn next-btn" onclick="changeSlide(1)">&#9654;</button>
                </div>
            </div>

            <div class="role-slide">
                <h2 class="features-title">Features for Students</h2>
                <div class="features-card">
                    <button class="arrow-btn prev-btn" onclick="changeSlide(-1)">&#9664;</button>
                    <div class="features-grid">
                        <div class="feature-box">
                            <h3>Campus Navigation</h3>
                            <div class="dashed-line"></div>
                            <p>Help navigate campus easily and find classrooms, facilities and other important
                                locations.</p>
                        </div>
                        <div class="feature-box">
                            <h3>Organise Study Groups</h3>
                            <div class="dashed-line"></div>
                            <p>Create or join study groups, find students with similar academic interests, and make
                                studying more engaging and productive.</p>
                        </div>
                    </div>
                    <button class="arrow-btn next-btn" onclick="changeSlide(1)">&#9654;</button>
                </div>
            </div>

            <div class="role-slide">
                <h2 class="features-title">Features for Lecturers</h2>
                <div class="features-card">
                    <button class="arrow-btn prev-btn" onclick="changeSlide(-1)">&#9664;</button>
                    <div class="features-grid">
                        <div class="feature-box">
                            <h3>Event Reminder</h3>
                            <div class="dashed-line"></div>
                            <p>Keep lecturers informed with timely reminders about upcoming university events.</p>
                        </div>
                        <div class="feature-box">
                            <h3>Participate in events</h3>
                            <div class="dashed-line"></div>
                            <p>Explore upcoming university events and discover new activities, experiences, and
                                opportunities to get involved.</p>
                        </div>
                    </div>
                    <button class="arrow-btn next-btn" onclick="changeSlide(1)">&#9654;</button>
                </div>
            </div>

        </div>
    </section>

    <section class="licensing-section">
        <h2><i>Flexible</i> Institutional Licensing</h2>
        <div class="pricing-grid">
            <div class="price-card">
                <p>Ideal for small institutions or trial implementations. Includes full access to all AI campus
                    features, complete setup, and standard annual support.</p>
                <h3>1 year | SGD 20,000</h3>
                <a href="universityRegistrationPage.php" class="btn-purchase">Purchase License</a>
            </div>
            <div class="price-card">
                <p>Designed for mid-sized universities seeking operational continuity. Save 10-15% on annual costs with
                    guaranteed feature updates and system maintenance.</p>
                <h3>2 years | SGD 36,000</h3>
                <a href="universityRegistrationPage.php" class="btn-purchase">Purchase License</a>
            </div>
            <div class="price-card">
                <p>Our best-value partnership plan for large institutions. Save 20-30% with multi-year budget stability,
                    priority onboarding, and custom feature development.</p>
                <h3>5 years | SGD 80,000</h3>
                <a href="universityRegistrationPage.php" class="btn-purchase">Purchase License</a>
            </div>
        </div>
    </section>

    <section class="testimonials-section">
        <h2>Testimonials</h2>
        <div class="testimonials-grid">
            <?php foreach ($testimonials as $row): ?>
                <?php
                $rating = isset($row['rating']) ? (int) $row['rating'] : 5;
                $name = $row['fullName'] ?? 'UniBee User';
                $dateStr = formatTestimonialDate($row['createdAt'] ?? null);
                ?>
                <div class="testimonial-card">
                    <div class="testimonial-avatar"><?php echo getInitials($name); ?></div>
                    <div class="stars">
                        <?php echo str_repeat('★', $rating); ?>
                    </div>
                    <p class="testimonial-text"><?php echo cleanInput($row['message'] ?? ''); ?></p>
                    <div class="testimonial-meta">
                        <span class="testimonial-name"><?php echo cleanInput(getDisplayName($name)); ?></span>
                        <?php if ($dateStr !== ''): ?>
                            <span class="testimonial-date">• <?php echo cleanInput($dateStr); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <footer>
        <div class="footer-main">
            <div class="logo-container">
                <img src="../images/uniBeeLogo.png" alt="UniBee">
            </div>
            <ul class="footer-links">
                <li><a href="termsAndConditions.php">Terms & Conditions</a></li>
                <li><a href="privacyPolicy.php">Privacy Policy</a></li>
            </ul>
        </div>
        <div class="footer-bottom-bar">
            &copy; 2026 UniBee. All rights reserved.
        </div>
    </footer>

    <script src="../script.js"></script>
</body>

</html>