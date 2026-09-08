<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - UniBee</title>
    <!-- CSS -->
    <link rel="stylesheet" href="../style.css">
</head>

<body>

    <!-- Header -->
    <header>
        <div class="logo-container">
            <img src="../images/uniBeeLogo.png" alt="UniBee Logo">
        </div>
        <div class="page-title">Log in</div>
    </header>

    <!-- Login Section -->
    <main class="login-container">

        <div class="login-wrapper">

            <!-- Left Column: Login Form -->
            <section class="login-form">
                <h1>Welcome back!</h1>
                <p>Enter your Credentials to access your account</p>

                <!-- Display error/success messages -->
                <?php
                session_start();
                if (isset($_SESSION['login_error'])) {
                    echo '<div class="error-message">' . $_SESSION['login_error'] . '</div>';
                    unset($_SESSION['login_error']);
                }
                if (isset($_SESSION['login_success'])) {
                    echo '<div class="success-message">' . $_SESSION['login_success'] . '</div>';
                    unset($_SESSION['login_success']);
                }
                ?>

                <form action="../controller/loginController.php" method="POST">

                    <div class="form-group">
                        <label for="email">Email address</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Password" required>
                    </div>

                    <div class="checkbox-group">
                        <input type="checkbox" id="remember" name="remember" value="1">
                        <label for="remember">Remember for 30 days</label>
                    </div>

                    <button type="submit" name="login">Login</button>

                </form>

                <p class="register-link">
                    Don't have an account? <a href="universityRegistrationPage.php">Sign up</a>
                </p>
                <p class="reset-link">
                    <a href="resetPasswordPage.php">Forgot password?</a>
                </p>

            </section>

            <!-- Right Column: Image -->
            <section class="login-image">
                <img src="../images/loginImage.png" alt="Campus Illustration">
            </section>

        </div>

    </main>

    <!-- Footer -->
    <footer>
        <div class="footer-bottom-bar">
            &copy; 2026 UniBee. All rights reserved.
        </div>
    </footer>

    <script src="../script.js"></script>

</body>

</html>