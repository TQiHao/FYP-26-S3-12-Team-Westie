<?php
require_once '../database/database.php';

try {
    $db = new Database();
    $pdo = $db->connect();

    // Fetch positive testimonials from DB (Rating >= 4)
    $stmt = $pdo->prepare("SELECT * FROM testimonials WHERE rating >= 4 ORDER BY id DESC LIMIT 3");
    $stmt->execute();
    $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Fallback testimonials if DB is not populated
    $testimonials = [
        ["text" => "The platform is really easy, and I love how everything is organised in one place!", "rating" => 5],
        ["text" => "It makes managing my university activities much more convenient and saves me a lot of time.", "rating" => 5],
        ["text" => "I really like the clean and colorful design. The features are useful and easy to understand.", "rating" => 5]
    ];
}

function cleanInput($data)
{
    return htmlspecialchars(stripslashes(trim($data)));
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

    <!-- Overview Hero Section -->
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

    <!-- Video Section -->
    <section class="video-section">
        <div class="video-container">
            <video controls poster="../images/landingpgCampus.avif">
                <source src="" type="video/mp4">
                Your browser does not support video playback.
            </video>
        </div>
    </section>

    <!-- View Features Section -->
    <section class="features-section">
        <div class="features-wrapper">

            <!-- Slide 1: Admin -->
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

            <!-- Slide 2: Course Coordinator -->
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

            <!-- Slide 3: Students -->
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

            <!-- Slide 4: Lecturers -->
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

    <!-- Institutional Licensing Plans -->
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

    <!-- View Testimony Section -->
    <section class="testimonials-section">
        <h2>Testimonials</h2>
        <div class="testimonials-grid">
            <?php foreach ($testimonials as $row): ?>
                <div class="testimonial-card">
                    <img src="../images/profilePic.png" alt="Profile" class="profile-icon"
                        style="margin: 0 auto; display: block;">
                    <div class="stars">
                        <?php
                        $rating = isset($row['rating']) ? (int) $row['rating'] : 5;
                        echo str_repeat('★', $rating);
                        ?>
                    </div>
                    <p><?php echo cleanInput($row['text']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-main">
            <div class="logo-container">
                <img src="../images/uniBeeLogo.png" alt="UniBee">
            </div>
            <ul class="footer-links">
                <li><a href="terms.php">Terms & Conditions</a></li>
                <li><a href="privacy.php">Privacy Policy</a></li>
                <li><a href="accessibility.php">Accessibility Statement</a></li>
            </ul>
        </div>
        <div class="footer-bottom-bar">
            &copy; 2026 UniBee. All rights reserved.
        </div>
    </footer>

    <script src="../script.js"></script>
</body>

</html>