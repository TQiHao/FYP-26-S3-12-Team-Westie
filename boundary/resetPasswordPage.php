<?php
session_start();

// Check if token is present in URL
$token = isset($_GET['token']) ? $_GET['token'] : null;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - UniBee</title>
    <!-- CSS -->
    <link rel="stylesheet" href="../style.css">
</head>

<body>

    <!-- Header -->
    <header>
        <div class="logo-container">
            <img src="../images/uniBeeLogo.png" alt="UniBee Logo">
        </div>
        <div class="page-title">Reset Password</div>
    </header>

    <!-- Reset Password Section -->
    <main class="login-container">

        <div class="login-wrapper">

            <!-- Left Column: Reset Password Form -->
            <section class="login-form">

                <?php if ($token): ?>
                    <!-- ===== SHOW SET NEW PASSWORD FORM ===== -->
                    <h1>Set New Password</h1>
                    <p>Enter your new password below</p>

                    <?php
                    if (isset($_SESSION['reset_confirm_error'])) {
                        echo '<div class="error-message">' . $_SESSION['reset_confirm_error'] . '</div>';
                        unset($_SESSION['reset_confirm_error']);
                    }
                    if (isset($_SESSION['reset_confirm_success'])) {
                        echo '<div class="success-message">' . $_SESSION['reset_confirm_success'] . '</div>';
                        unset($_SESSION['reset_confirm_success']);
                    }
                    ?>

                    <form action="../controller/resetPasswordController.php" method="POST">

                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>
                        </div>

                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                        <button type="submit" name="reset_password">Reset Password</button>

                    </form>

                <?php else: ?>
                    <!-- ===== SHOW REQUEST RESET FORM ===== -->
                    <h1>Reset Password</h1>
                    <p>Enter your login email and we will send you a link to reset your password</p>

                    <?php
                    if (isset($_SESSION['reset_error'])) {
                        echo '<div class="error-message">' . $_SESSION['reset_error'] . '</div>';
                        unset($_SESSION['reset_error']);
                    }
                    if (isset($_SESSION['reset_success'])) {
                        echo '<div class="success-message">' . $_SESSION['reset_success'] . '</div>';
                        unset($_SESSION['reset_success']);
                    }
                    ?>

                    <form action="../controller/resetPasswordController.php" method="POST">

                        <div class="form-group">
                            <label for="email">Email address</label>
                            <input type="email" id="email" name="email" placeholder="Enter your email" required>
                        </div>

                        <button type="submit" name="request_reset">Reset Password</button>

                    </form>

                    <p class="register-link">
                        <a href="loginPage.php">Back to Login</a>
                    </p>

                <?php endif; ?>

            </section>

            <!-- Right Column: Image -->
            <section class="login-image">
                <img src="../images/resetPasswordImage.png" alt="Reset Password Illustration">
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