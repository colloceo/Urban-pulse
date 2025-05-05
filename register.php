<?php
session_start();

// Redirect logged-in users
if (isset($_SESSION["user"])) {
    header("Location: shop.php");
    exit();
}

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

// Include database connection
require_once 'db_connect.php'; // Replace with your actual database connection file

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = filter_input(INPUT_POST, "name", FILTER_SANITIZE_STRING);
    $username = filter_input(INPUT_POST, "username", FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, "email", FILTER_SANITIZE_EMAIL);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm-password"];
    $terms = isset($_POST["terms"]);

    $errors = [];

    if (empty($name) || !preg_match("/^[a-zA-Z\s]+$/", $name)) {
        $errors[] = "Valid name is required.";
    }

    if (empty($username) || !preg_match("/^[a-zA-Z0-9_]+$/", $username)) {
        $errors[] = "Valid username is required.";
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required.";
    }

    if (empty($password) || strlen($password) < 8 || !preg_match("/[A-Z]/", $password) || !preg_match("/[0-9]/", $password) || !preg_match("/[^A-Za-z0-9]/", $password)) {
        $errors[] = "Password must be 8+ characters with uppercase, number, and special character.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    if (!$terms) {
        $errors[] = "You must agree to the terms.";
    }

    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email OR username = :username");
        $stmt->execute([':email' => $email, ':username' => $username]);
        
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Email or username already registered.";
        }

        if (empty($errors)) {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (name, username, email, password) VALUES (:name, :username, :email, :password)");
            $stmt->execute([
                ':name' => $name,
                ':username' => $username,
                ':email' => $email,
                ':password' => $hashed_password
            ]);

            $message = "Registration successful! <a href='login.php'>Login here</a>.";
            $msg_class = "success";
        } else {
            $message = implode("<br>", $errors);
            $msg_class = "danger";
        }
    } catch (PDOException $e) {
        $message = "Database error: " . $e->getMessage();
        $msg_class = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Register for an account on UrbanPulse and explore the latest trends in fashion and accessories.">
    <title>Register - UrbanPulse</title>
    <link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
    <link rel="manifest" href="site.webmanifest">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #ff6200;
            --secondary: #1a252f;
            --accent: #00a8e8;
            --light: #f8f9fa;
            --white: #ffffff;
            --text: #212529;
            --border: #dee2e6;
            --shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light);
            color: var(--text);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Header */
        header {
            background-color: var(--secondary);
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: var(--shadow);
        }

        .navbar {
            padding: 0.5rem 1rem;
        }

        .navbar-brand img {
            height: 35px;
        }

        .navbar-nav .nav-link {
            color: var(--white);
            font-size: 0.95rem;
            padding: 0.5rem 1rem;
            transition: color 0.3s ease;
        }

        .navbar-nav .nav-link:hover,
        .navbar-nav .nav-link.active {
            color: var(--primary);
        }

        .navbar .btn {
            font-size: 0.9rem;
            padding: 0.4rem 1rem;
            color: var(--white);
            border-color: var(--primary);
        }

        .navbar .btn:hover {
            background-color: var(--primary);
            color: var(--white);
        }

        /* Navbar Toggler */
        .navbar-toggler {
            border: 1px solid var(--primary);
            padding: 0.25rem 0.5rem;
            transition: all 0.2s ease;
        }

        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='%23ff6200' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        .navbar-collapse {
            background-color: var(--secondary);
            padding: 1rem;
            transition: height 0.3s ease;
        }

        @media (max-width: 767.98px) {
            .navbar-collapse {
                border-top: 1px solid var(--border);
            }
            .navbar-nav .nav-link {
                padding: 0.75rem 1rem;
                font-size: 1rem;
            }
            .navbar .btn {
                margin: 0.5rem 1rem;
                width: calc(100% - 2rem);
                text-align: center;
            }
        }

        /* Register Section */
        .register-section {
            padding: 3rem 0;
        }

        .register-section h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-align: center;
            color: var(--secondary);
        }

        .register-form-container {
            background-color: var(--white);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: var(--shadow);
            max-width: 600px;
            margin: 0 auto;
        }

        .register-form-container .form-label {
            font-weight: 500;
            color: var(--text);
        }

        .register-form-container .form-control {
            border-radius: 5px;
            border: 1px solid var(--border);
            padding: 0.75rem;
            font-size: 1rem;
        }

        .register-form-container .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(255, 98, 0, 0.25);
        }

        .register-form-container .btn-primary {
            background-color: var(--primary);
            border: none;
            padding: 0.75rem;
            font-size: 1rem;
            font-weight: 500;
            width: 100%;
        }

        .register-form-container .btn-primary:hover {
            background-color: #e55a00;
        }

        .register-form-container .alert {
            font-size: 0.95rem;
            padding: 0.75rem;
            border-radius: 5px;
        }

        .register-form-container .alert i {
            font-size: 1.2rem;
        }

        .register-form-container .form-check-label a {
            color: var(--primary);
            text-decoration: none;
        }

        .register-form-container .form-check-label a:hover {
            text-decoration: underline;
        }

        .register-form-container .login-link {
            text-align: center;
            margin-top: 1rem;
        }

        .register-form-container .login-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }

        .register-form-container .login-link a:hover {
            text-decoration: underline;
        }

        /* Password Strength Meter */
        .password-strength {
            height: 5px;
            margin-top: 0.5rem;
            background: var(--border);
            border-radius: 3px;
            overflow: hidden;
        }

        .password-strength-meter {
            height: 100%;
            width: 0;
            transition: width 0.3s ease, background 0.3s ease;
        }

        .password-feedback {
            font-size: 0.85rem;
            margin-top: 0.25rem;
            min-height: 1.2rem;
        }

        .requirements {
            font-size: 0.85rem;
            color: var(--text);
            margin-top: 0.5rem;
        }

        .requirements ul {
            list-style: none;
            padding: 0;
        }

        .requirements li {
            margin-bottom: 0.25rem;
        }

        /* Toggle Password */
        .password-group {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text);
        }

        .toggle-password i {
            font-size: 1rem;
        }

        @media (max-width: 767.98px) {
            .register-section h1 {
                font-size: 2rem;
            }
            .register-form-container {
                padding: 1.5rem;
            }
        }

        /* Footer */
        footer {
            background-color: var(--secondary);
            color: var(--white);
            padding: 2rem 0;
            text-align: center;
            margin-top: auto;
        }

        footer a {
            color: var(--primary);
            text-decoration: none;
            margin: 0 0.5rem;
        }

        footer a:hover {
            text-decoration: underline;
        }

        footer .social-icons a {
            font-size: 1.2rem;
            margin: 0 0.75rem;
            color: var(--white);
            transition: color 0.3s ease;
        }

        footer .social-icons a:hover {
            color: var(--primary);
        }

        /* Back to Top Button */
        #back-to-top {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: var(--primary);
            color: var(--white);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: none;
            z-index: 1000;
            box-shadow: var(--shadow);
            border: none;
            cursor: pointer;
        }

        #back-to-top:hover {
            background: #e55a00;
        }

        /* Loader */
        .loader {
            border: 4px solid rgba(255,255,255,0.2);
            border-top: 4px solid var(--primary);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1001;
        }

        @keyframes spin {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="loader" id="loader"></div>

    <!-- Header -->
    <header>
        <nav class="navbar navbar-expand-md">
            <div class="container">
                <a class="navbar-brand" href="index.php">
                    <img src="/api/placeholder/120/35" alt="UrbanPulse Logo">
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav mx-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php"><i class="fas fa-home me-1"></i> Home</a>
                        </li>
                       
                        <li class="nav-item">
                            <a class="nav-link" href="about.php"><i class="fas fa-info-circle me-1"></i> About</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="contact.php"><i class="fas fa-envelope me-1"></i> Contact</a>
                        </li>
                    </ul>
                    <div class="d-flex gap-2">
                        <a href="login.php" class="btn btn-outline-primary"><i class="fas fa-sign-in-alt me-1"></i> Login</a>
                        <a href="register.php" class="btn btn-outline-primary active"><i class="fas fa-user-plus me-1"></i> Sign Up</a>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <!-- Register Section -->
    <section class="register-section">
        <div class="container">
            <h1>Create Your Account</h1>
            <div class="register-form-container">
                <?php if (isset($message)): ?>
                    <div class="alert alert-<?php echo $msg_class; ?> d-flex align-items-center" role="alert">
                        <i class="fas fa-<?php echo $msg_class === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                <form method="post" action="register.php" id="registrationForm">
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" placeholder="Enter your full name" required aria-label="Full Name" autocomplete="name">
                    </div>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" placeholder="Choose a username" required aria-label="Username" autocomplete="username">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email address" required aria-label="Email Address" autocomplete="email">
                    </div>
                    <div class="mb-3 password-group">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Create a strong password" required aria-label="Password" autocomplete="new-password">
                        <button type="button" class="toggle-password" onclick="togglePasswordVisibility('password')" aria-label="Toggle password visibility">
                            <i class="fas fa-eye"></i>
                        </button>
                        <div class="password-strength">
                            <div class="password-strength-meter" id="passwordStrengthMeter"></div>
                        </div>
                        <div class="password-feedback" id="passwordFeedback"></div>
                        <div class="requirements">
                            Password requirements:
                            <ul>
                                <li>At least 8 characters</li>
                                <li>At least one uppercase letter</li>
                                <li>At least one number</li>
                                <li>At least one special character</li>
                            </ul>
                        </div>
                    </div>
                    <div class="mb-3 password-group">
                        <label for="confirm-password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm-password" name="confirm-password" placeholder="Confirm your password" required aria-label="Confirm Password" autocomplete="new-password">
                        <button type="button" class="toggle-password" onclick="togglePasswordVisibility('confirm-password')" aria-label="Toggle password visibility">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                        <label class="form-check-label" for="terms">
                            I agree to the <a href="terms.php" target="_blank">Terms and Conditions</a> and <a href="privacy.php" target="_blank">Privacy Policy</a>
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary" aria-label="Register">
                        <i class="fas fa-user-plus me-2"></i> Create Account
                    </button>
                </form>
                <div class="login-link">
                    <p>Already have an account? <a href="login.php">Login Here</a></p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p>© <?php echo date("Y"); ?> UrbanPulse. All rights reserved.</p>
            <div class="mt-2">
                <a href="terms.php">Terms</a> |
                <a href="privacy.php">Privacy</a> |
                <a href="contact.php">Contact</a>
            </div>
            <div class="social-icons mt-3">
                <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <button id="back-to-top" aria-label="Back to top">
        <i class="fas fa-chevron-up"></i>
    </button>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        // Loader management
        const loader = document.getElementById("loader");
        window.addEventListener("load", () => {
            loader.style.display = "none";
        });
        setTimeout(() => {
            loader.style.display = "none";
        }, 5000);

        // Back to top button
        const backToTopButton = document.getElementById("back-to-top");
        window.addEventListener("scroll", () => {
            backToTopButton.style.display = window.scrollY > 300 ? "block" : "none";
        });
        backToTopButton.addEventListener("click", () => {
            window.scrollTo({ top: 0, behavior: "smooth" });
        });

        // Toggle password visibility
        function togglePasswordVisibility(inputId) {
            const input = document.getElementById(inputId);
            const toggle = input.nextElementSibling;
            const icon = toggle.querySelector('i');
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = "password";
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Password strength meter
        const passwordInput = document.getElementById('password');
        const meter = document.getElementById('passwordStrengthMeter');
        const feedback = document.getElementById('passwordFeedback');

        passwordInput.addEventListener('input', updatePasswordStrength);

        function updatePasswordStrength() {
            const password = passwordInput.value;
            let strength = 0;
            let message = '';
            
            if (password.length >= 8) strength += 25;
            if (password.match(/[A-Z]/)) strength += 25;
            if (password.match(/[0-9]/)) strength += 25;
            if (password.match(/[^A-Za-z0-9]/)) strength += 25;
            
            meter.style.width = strength + '%';
            
            if (strength <= 25) {
                meter.style.background = '#e63946';
                message = 'Weak password';
            } else if (strength <= 50) {
                meter.style.background = '#f4a261';
                message = 'Fair password';
            } else if (strength <= 75) {
                meter.style.background = '#90be6d';
                message = 'Good password';
            } else {
                meter.style.background = '#2e7d32';
                message = 'Strong password';
            }
            
            feedback.textContent = message;
        }

        // Form validation
        const confirmPassword = document.getElementById('confirm-password');
        const registrationForm = document.getElementById('registrationForm');
        
        registrationForm.addEventListener('submit', function(event) {
            if (passwordInput.value !== confirmPassword.value) {
                event.preventDefault();
                feedback.textContent = 'Passwords do not match!';
                feedback.style.color = '#e63946';
            }
        });
    </script>
</body>
</html>