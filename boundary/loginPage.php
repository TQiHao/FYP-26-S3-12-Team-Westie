<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - UniBee</title>
    <!-- CSS will be added later -->
    <link rel="stylesheet" href="../style.css">
</head>
<body>

    <!-- Header -->
    <header>
        <div class="logo">UniBee</div>
        <div class="page-title">Log in</div>
    </header>

    <!-- Login Section -->
    <main class="login-container">

        <!-- Login Form -->
        <section class="login-form">

            <h1>Welcome back!</h1>
            <p>Enter your Credentials to access your account</p>

            <!-- Display error messages if any -->
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

                <!-- Email -->
                <div class="form-group">
                    <label for="email">Email address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Enter your email"
                        required
                    >
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Password"
                        required
                    >
                </div>

                <!-- Remember Me -->
                <div class="form-group">
                    <input
                        type="checkbox"
                        id="remember"
                        name="remember"
                        value="1"
                    >
                    <label for="remember">Remember for 30 days</label>
                </div>

                <!-- Submit Button -->
                <button type="submit" name="login">
                    Login
                </button>

            </form>

            <!-- Links -->
            <p class="register-link">
                Don't have an account?
                <a href="universityRegistrationPage.php">Sign up</a>
            </p>
            <p class="reset-link">
                <a href="resetPasswordPage.php">Forgot password?</a>
            </p>

        </section>

        <!-- Image Section (Optional) -->
        <section class="login-image">
            <img src="" alt="">
        </section>

    </main>

</body>
</html>
