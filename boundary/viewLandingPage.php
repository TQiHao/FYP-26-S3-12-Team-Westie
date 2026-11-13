<?php
require_once '../controller/viewLandingPageController.php';

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
    <style>
        html {
            scroll-behavior: smooth;
        }

        .landing-nav {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .landing-nav a:not(.get-started-btn) {
            color: #111;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            padding: 8px 15px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .landing-nav a:not(.get-started-btn):hover {
            color: #fff;
            background-color: #e6c23a;
        }

        .landing-nav a[href="loginPage.php"] {
            margin-left: 20px;
        }

        img {
            max-width: 100%;
        }

        .nav-toggle {
            display: none;
            background: none;
            border: 0;
            font-size: 30px;
            line-height: 1;
            padding: 4px 10px;
            cursor: pointer;
            color: #111;
        }

        @media (max-width: 1024px) {
            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .pricing-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .testimonials-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 900px) {
            header {
                flex-wrap: wrap;
                justify-content: space-between;
                align-items: center;
                padding-left: 20px;
                padding-right: 20px;
            }

            .nav-toggle {
                display: block;
            }

            .landing-nav {
                display: none;
                width: 100%;
                flex-direction: column;
                align-items: stretch;
                gap: 4px;
                padding: 10px 0;
            }

            .landing-nav.open {
                display: flex;
            }

            .landing-nav a,
            .landing-nav a[href="loginPage.php"] {
                margin-left: 0;
                text-align: center;
                padding: 12px 15px;
            }

            .hero-content {
                flex-direction: column;
                text-align: center;
                gap: 20px;
                padding: 40px 20px;
            }

            .hero-left,
            .hero-right {
                width: 100%;
            }

            .hero-divider {
                display: none;
            }

            .hero-content h1 {
                font-size: 36px;
            }
        }

        @media (max-width: 600px) {

            .features-grid,
            .pricing-grid,
            .testimonials-grid {
                grid-template-columns: 1fr;
            }

            .features-card {
                position: relative;
                padding: 20px 52px;
            }

            .arrow-btn {
                position: absolute !important;
                top: 50% !important;
                transform: translateY(-50%) !important;
                width: 38px;
                height: 38px;
                font-size: 14px;
                z-index: 2;
            }

            .arrow-btn.prev-btn {
                left: 8px !important;
                right: auto !important;
            }

            .arrow-btn.next-btn {
                right: 8px !important;
                left: auto !important;
            }

            .features-title,
            .licensing-section h2,
            .testimonials-section h2 {
                font-size: 26px;
                text-align: center;
            }

            .marketing-video-section {
                padding: 30px 12px !important;
            }

            .licensing-section {
                padding-left: 15px;
                padding-right: 15px;
            }

            .testimonials-section {
                padding-left: 0;
                padding-right: 0;
            }

            .testimonials-grid {
                padding: 0;
                margin: 0;
                width: 100%;
            }

            .testimonial-card {
                width: 100%;
                max-width: 100%;
                margin: 0;
                box-sizing: border-box;
            }

            .footer-main {
                flex-direction: column;
                align-items: center;
                gap: 15px;
                text-align: center;
            }

            .footer-links {
                flex-direction: column;
                align-items: center;
                gap: 10px;
                padding: 0;
            }

            .hero-content h1 {
                font-size: 30px;
            }
        }
    </style>
</head>

<body>

    <header>
        <div class="logo-container">
            <img src="../images/uniBeeLogo.png" alt="UniBee Logo">
        </div>

        <button class="nav-toggle" id="navToggle" aria-label="Toggle menu" aria-expanded="false">&#9776;</button>

        <nav class="landing-nav" id="landingNav">
            <a href="#overview">Overview</a>
            <a href="#video">Marketing Video</a>
            <a href="#features">Features</a>
            <a href="#testimonials">Testimonials</a>
            <a href="loginPage.php">Log In</a>
            <a href="universityRegistrationPage.php" class="get-started-btn">Get started</a>
        </nav>
    </header>

    <?php
    $heroTitle = $hero ? $hero->getTitle() : 'Connect Smarter,<br>Bee Smarter.';
    $heroSubtitle = $hero ? $hero->getSubtitle() : 'Your smart campus hub for effortless learning, schedules, and collaboration.';
    $heroImage = $hero ? $hero->getImagePath() : '../images/landingpgCampus.avif';
    ?>

    <section class="hero" id="overview"
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

    <section class="marketing-video-section" id="video"
        style="padding: 50px 20px; text-align: center; background-color: #f8f9fa;">
        <div
            style="max-width: 800px; margin: 0 auto; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.15); aspect-ratio: 16/9;">
            <iframe width="100%" height="100%"
                src="https://www.youtube.com/embed/BqMPDqsBuW4?autoplay=1&mute=1&loop=1&playlist=BqMPDqsBuW4"
                title="UniBee Marketing Video" frameborder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen>
            </iframe>
        </div>
    </section>

    <section class="features-section" id="features">
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
                <h3>1 year | SGD 15,000</h3>
                <a href="universityRegistrationPage.php" class="btn-purchase">Purchase License</a>
            </div>
            <div class="price-card">
                <p>Designed for mid-sized universities seeking operational continuity. Save 10-15% on annual costs with
                    guaranteed feature updates and system maintenance.</p>
                <h3>2 years | SGD 27,000</h3>
                <a href="universityRegistrationPage.php" class="btn-purchase">Purchase License</a>
            </div>
            <div class="price-card">
                <p>Our best-value partnership plan for large institutions. Save 20-30% with multi-year budget stability,
                    priority onboarding, and custom feature development.</p>
                <h3>5 years | SGD 60,000</h3>
                <a href="universityRegistrationPage.php" class="btn-purchase">Purchase License</a>
            </div>
        </div>
    </section>

    <section class="testimonials-section" id="testimonials">
        <h2>Testimonials</h2>

        <?php if (empty($testimonials)): ?>
            <p style="text-align: center; color: #64748b; font-size: 16px;">No testimonials available at the moment.</p>
        <?php else: ?>
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
        <?php endif; ?>
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
    <script>
        (function () {
            var toggle = document.getElementById('navToggle');
            var nav = document.getElementById('landingNav');
            if (!toggle || !nav) return;

            toggle.addEventListener('click', function () {
                var open = nav.classList.toggle('open');
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            });

            var slideTimer = null;

            function startAutoSlide() {
                clearInterval(slideTimer);
                slideTimer = setInterval(function () {
                    if (typeof changeSlide === 'function') {
                        changeSlide(1);
                    }
                }, 5000);
            }

            document.querySelectorAll('.arrow-btn').forEach(function (btn) {
                btn.addEventListener('click', startAutoSlide);
            });

            startAutoSlide();

            nav.querySelectorAll('a').forEach(function (link) {
                link.addEventListener('click', function () {
                    nav.classList.remove('open');
                    toggle.setAttribute('aria-expanded', 'false');
                });
            });
        })();
    </script>
</body>

</html>