<?php
require_once '../controller/ViewLandingPageController.php';

$controller = new ViewLandingPageController();

$hero = $controller->getHeroContent();
$featureSlides = $controller->getFeatureSlides();
$testimonials = $controller->getTestimonials();

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

    <?php
    $heroTitle = $hero ? $hero->getTitle() : 'Connect Smarter,<br>Bee Smarter.';
    $heroSubtitle = $hero ? $hero->getSubtitle() : 'Your smart campus hub for effortless learning, schedules, and collaboration.';
    $heroImage = $hero ? $hero->getImagePath() : '../images/landingpgCampus.avif';
    ?>

    <section class="hero"
        style="background: url('<?php echo htmlspecialchars($heroImage); ?>') center/cover no-repeat;">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <div class="hero-left">
                <h1><?php echo $heroTitle; ?></h1>
            </div>
            <div class="hero-divider"></div>
            <div class="hero-right">
                <p><?php echo htmlspecialchars($heroSubtitle); ?></p>
                <a href="universityRegistrationPage.php" class="btn-primary">Get Started Today</a>
            </div>
        </div>
    </section>

    <section class="video-section">
        <div class="video-container">
            <video controls poster="<?php echo htmlspecialchars($heroImage); ?>">
                <source src="" type="video/mp4">
                Your browser does not support video playback.
            </video>
        </div>
    </section>

    <section class="features-section">
        <div class="features-wrapper">

            <?php foreach ($featureSlides as $i => $slide): ?>
                <?php $boxes = $slide->getFeatureBoxes(); ?>
                <div class="role-slide <?php echo $i === 0 ? 'active' : ''; ?>">
                    <h2 class="features-title"><?php echo htmlspecialchars($slide->getTitle()); ?></h2>
                    <div class="features-card">
                        <button class="arrow-btn prev-btn" onclick="changeSlide(-1)">&#9664;</button>
                        <div class="features-grid">
                            <?php foreach ($boxes as $box): ?>
                                <div class="feature-box">
                                    <h3><?php echo htmlspecialchars($box['title'] ?? ''); ?></h3>
                                    <div class="dashed-line"></div>
                                    <p><?php echo htmlspecialchars($box['desc'] ?? ''); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button class="arrow-btn next-btn" onclick="changeSlide(1)">&#9654;</button>
                    </div>
                </div>
            <?php endforeach; ?>

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
                <li><a href="termsAndConditionsPage.php">Terms & Conditions</a></li>
                <li><a href="privacyPolicyPage.php">Privacy Policy</a></li>
            </ul>
        </div>
        <div class="footer-bottom-bar">
            &copy; 2026 UniBee. All rights reserved.
        </div>
    </footer>

    <script src="../script.js"></script>
</body>

</html>