<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sign Up</title>

    <!-- CSS - External Sheet -->
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


    <!-- Registration Section -->
    <main class="registration-container">

        <!-- Registration Form -->
        <section class="registration-form">

            <h1>Get Started Now</h1>

            <form action="../controller/universityRegistrationController.php" method="POST">

                <!-- Email -->
                <div class="form-group">
                    <label for="email">Email address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                </div>


                <!-- Password -->
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Password" required>
                </div>


                <!-- University Name -->
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" placeholder="Name" required>
                </div>


                <!-- Institution Type -->
                <div class="form-group">
                    <label for="institutionType">Type of Institute</label>
                    <input type="text" id="institutionType" name="institutionType" placeholder="Type of Institute"
                        required>
                </div>


                <!-- Country -->
                <div class="form-group">
                    <label for="country">Country</label>
                    <input type="text" id="country" name="country" placeholder="Country" required>
                </div>


                <!-- Postal Code -->
                <div class="form-group">
                    <label for="postalCode">Postal Code</label>
                    <input type="text" id="postalCode" name="postalCode" placeholder="Postal Code" required>
                </div>


                <!-- Submit Button -->
                <button type="submit">
                    Sign up
                </button>

            </form>


            <!-- Login Link -->
            <p class="login-link">
                Already have an account?
                <a href="loginPage.php">Log in</a>
            </p>

        </section>


        <!-- Image -->
        <section class="registration-image">

            <img src="" alt="">

        </section>

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