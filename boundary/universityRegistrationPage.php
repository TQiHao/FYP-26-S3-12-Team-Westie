<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sign Up - UniBee</title>

    <link rel="stylesheet" href="../style.css">
</head>

<body>

    <!-- Header -->
    <header>
        <div class="logo-container">
            <img src="../images/uniBeeLogo.png" alt="UniBee Logo">
        </div>

        <div class="page-title">Sign up</div>
    </header>

    <main class="registration-container">

        <div class="registration-wrapper">

            <section class="registration-form">

                <h1>Get Started Now</h1>
                <p>Create your university account</p>

                <form action="../controller/universityRegistrationController.php" method="POST">

                    <div class="form-group">
                        <label for="email">Email address</label>
                        <input type="email" id="email" name="email"
                               placeholder="Enter your email" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password"
                               placeholder="Password" required>
                    </div>

                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" id="name" name="name"
                               placeholder="Name" required>
                    </div>

                    <div class="form-group">
                        <label for="institutionType">Type of Institute</label>
                        <input type="text" id="institutionType" name="institutionType"
                               placeholder="Type of Institute" required>
                    </div>

                    <div class="form-group">
                        <label for="country">Country</label>
                        <input type="text" id="country" name="country"
                               placeholder="Country" required>
                    </div>

                    <div class="form-group">
                        <label for="postalCode">Postal Code</label>
                        <input type="text" id="postalCode" name="postalCode"
                               placeholder="Postal Code" required>
                    </div>

                    <button type="submit">Sign up</button>

                </form>

                <p class="login-link">
                    Already have an account?
                    <a href="loginPage.php">Log in</a>
                </p>

            </section>

            <section class="registration-image">
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