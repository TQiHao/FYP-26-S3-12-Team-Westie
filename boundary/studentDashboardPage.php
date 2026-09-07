<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Student Dashboard</title>
</head>

<body>

    <!-- Header -->
    <header>
        <div class="logo">UniBee</div>

        <div class="welcome">
            <p>Welcome back,</p>
            <h2><?= htmlspecialchars($user->getFullName()) ?></h2>
        </div>

        <div class="header-icons">
            <img src="../images/notification.png" alt="Notifications">
            <img src="../images/profile.png" alt="Profile">
            <img src="../images/dropdown.png" alt="Menu">
        </div>
    </header>


    <!-- Dashboard -->
    <main>
        <!-- Hardcoded for now -->
        <section class="summary">

            <div class="summary-item">
                <p>Next class</p>
                <h2>Data Structures, 2:00pm</h2>
                <small>Room B204</small>
            </div>

            <div class="summary-item">
                <p>Notifications</p>
                <h2>3 unread</h2>
                <small>Exam schedule updated</small>
            </div>

            <div class="summary-item">
                <p>Upcoming event</p>
                <h2>Career fair, Fri 22 Aug</h2>
                <small>Main Hall</small>
            </div>

        </section>


        <!-- Quick Actions -->
        <section class="quick-actions">

            <p>Quick actions</p>

            <button>Ask AI Chatbot</button>
            <button>Book Study Room</button>
            <button>View Timetable</button>

        </section>


        <!-- Explore -->
        <section class="explore">

            <p>Explore</p>

            <div class="dashboard-card">
                <h2>Academics</h2>
                <small>5 Courses Enrolled</small>
            </div>

            <div class="dashboard-card">
                <h2>Facilities Booking</h2>
                <small>1 Active Booking</small>
            </div>

            <div class="dashboard-card">
                <h2>Study Groups</h2>
                <small>2 Groups joined</small>
            </div>

            <div class="dashboard-card">
                <h2>Campus Events</h2>
                <small>3 Upcoming</small>
            </div>

            <div class="dashboard-card">
                <h2>AI Chatbot</h2>
                <small>Ask a Question</small>
            </div>

            <div class="dashboard-card">
                <h2>Submit Feedback</h2>
                <small>Share your thoughts</small>
            </div>

        </section>

    </main>


    <!-- Footer -->
    <footer>
        © 2026 UniBee. All rights reserved.
    </footer>

</body>
</html>
