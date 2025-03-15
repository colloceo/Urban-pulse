<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION["user"])) {
    header("Location: shop.php");
    exit();
}

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize form data
    $name = filter_input(INPUT_POST, "name", FILTER_SANITIZE_STRING);
    $username = filter_input(INPUT_POST, "username", FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, "email", FILTER_SANITIZE_EMAIL);
    $password = $_POST["password"]; // Password needs raw value for hashing
    $confirm_password = $_POST["confirm-password"];
    $terms = isset($_POST["terms"]);

    // Validate inputs
    $errors = [];

    // Name validation
    if (empty($name)) {
        $errors[] = "Name is required.";
    } elseif (!preg_match("/^[a-zA-Z\s]+$/", $name)) {
        $errors[] = "Name can only contain letters and spaces.";
    }

    // Username validation
    if (empty($username)) {
        $errors[] = "Username is required.";
    } elseif (!preg_match("/^[a-zA-Z0-9_]+$/", $username)) {
        $errors[] = "Username can only contain letters, numbers, and underscores.";
    }

    // Email validation
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    // Password validation
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    } elseif (!preg_match("/[A-Z]/", $password) || !preg_match("/[0-9]/", $password) || !preg_match("/[^A-Za-z0-9]/", $password)) {
        $errors[] = "Password must include at least one uppercase letter, one number, and one special character.";
    }

    // Confirm password and terms
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    if (!$terms) {
        $errors[] = "You must agree to the terms and conditions.";
    }

    // Database connection
    $host = "localhost";
    $dbname = "ecomerce";
    $username_db = "root";
    $password_db = "";

    try {
        $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username_db, $password_db);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check for existing email/username
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = :email OR username = :username");
        $stmt->execute([':email' => $email, ':username' => $username]);
        
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Email or username already registered.";
        }

        // Process if no errors
        if (empty($errors)) {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $db->prepare("INSERT INTO users (name, username, email, password) VALUES (:name, :username, :email, :password)");
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
            $msg_class = "error";
        }
    } catch (PDOException $e) {
        $message = "Error: Database connection failed. " . $e->getMessage();
        $msg_class = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Register for an account on UrbanPulse and explore the latest trends in fashion and accessories.">
    <title>Register for UrbanPulse</title>
    <link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
    <link rel="manifest" href="site.webmanifest">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary: #1a2634;
            --secondary: #2e3b4e;
            --accent: #ff6f61;
            --accent-hover: #e65b50;
            --light: #f4f4f9;
            --white: #fff;
            --success: #2e7d32;
            --success-bg: #e6ffe6;
            --error: #e63946;
            --error-bg: #ffe6e6;
            --shadow: 0 4px 15px rgba(0,0,0,0.05);
            --border: #ddd;
        }
        body {
            font-family: 'Roboto', sans-serif;
            background-color: var(--light);
            color: #333;
            line-height: 1.6;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: var(--white);
            padding: clamp(1rem, 3vw, 2rem);
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        header h1 { 
            font-size: clamp(1.75rem, 5vw, 2.5rem);
            letter-spacing: 0.5px;
        }
        nav {
            background: var(--secondary);
            padding: clamp(0.5rem, 2vw, 1rem);
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }
        .dropdown {
            position: relative;
        }
        .dropdown-btn {
            color: var(--white);
            background: none;
            border: none;
            padding: clamp(0.5rem, 1.5vw, 0.75rem) clamp(1rem, 2vw, 1.5rem);
            font-size: clamp(0.9rem, 2vw, 1rem);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }
        .dropdown-btn:hover {
            background: var(--accent);
            border-radius: 5px;
        }
        .dropdown-content {
            display: none;
            position: absolute;
            background-color: var(--secondary);
            min-width: 160px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 5px;
            overflow: hidden;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
        }
        .dropdown-content a {
            color: var(--white);
            padding: clamp(0.5rem, 1vw, 0.75rem) clamp(1rem, 2vw, 1.5rem);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: clamp(0.85rem, 1.8vw, 1rem);
            transition: all 0.3s;
        }
        .dropdown-content a:hover, .dropdown-content a.active {
            background: var(--accent);
        }
        .dropdown:hover .dropdown-content {
            display: block;
        }
        main {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: clamp(2rem, 5vw, 3rem) clamp(0.5rem, 2vw, 1rem);
            background: linear-gradient(rgba(244, 244, 249, 0.8), rgba(244, 244, 249, 0.9)),
                        url('/api/placeholder/1920/1080') center/cover no-repeat fixed;
        }
        .register-container {
            background: var(--white);
            padding: clamp(1.5rem, 4vw, 2.5rem);
            border-radius: 12px;
            box-shadow: var(--shadow);
            width: 100%;
            max-width: clamp(300px, 50vw, 450px);
            text-align: center;
            animation: fadeIn 1s ease-in-out;
            position: relative;
            overflow: hidden;
        }
        .register-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: var(--accent);
        }
        h2 {
            font-size: clamp(1.5rem, 4vw, 2rem);
            color: var(--primary);
            margin-bottom: 1.5rem;
            position: relative;
            display: inline-block;
        }
        h2:after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: clamp(40px, 8vw, 50px);
            height: 3px;
            background: var(--accent);
            border-radius: 3px;
        }
        form { 
            display: flex; 
            flex-direction: column; 
            gap: 1.25rem;
            margin-top: 1.5rem;
        }
        .form-group {
            position: relative;
            text-align: left;
        }
        label {
            text-align: left;
            font-weight: 500;
            font-size: clamp(0.85rem, 1.5vw, 0.95rem);
            color: var(--secondary);
            margin-bottom: 0.5rem;
            display: block;
            transition: color 0.3s;
        }
        .form-group:focus-within label {
            color: var(--accent);
        }
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            padding: clamp(0.7rem, 1.5vw, 0.85rem) 1rem;
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: clamp(0.9rem, 2vw, 1rem);
            transition: all 0.3s;
            background-color: #f9f9f9;
            padding-left: 40px;
        }
        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: var(--accent);
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 111, 97, 0.1);
            background-color: var(--white);
        }
        .input-icon {
            position: absolute;
            left: 12px;
            top: clamp(35px, 5vw, 42px);
            color: #999;
            transition: color 0.3s;
            font-size: clamp(0.9rem, 2vw, 1rem);
        }
        .form-group:focus-within .input-icon {
            color: var(--accent);
        }
        .password-container { position: relative; }
        .toggle-password {
            position: absolute;
            right: 12px;
            top: clamp(35px, 5vw, 42px);
            cursor: pointer;
            color: #666;
            font-size: clamp(0.8rem, 1.8vw, 0.9rem);
            background: none;
            border: none;
            transition: color 0.3s;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        .toggle-password:hover {
            color: var(--accent);
        }
        .password-strength {
            height: 5px;
            background: #eee;
            border-radius: 3px;
            margin-top: 0.5rem;
            overflow: hidden;
        }
        .password-strength-meter {
            height: 100%;
            width: 0;
            transition: width 0.3s, background 0.3s;
        }
        .password-feedback {
            font-size: clamp(0.75rem, 1.5vw, 0.8rem);
            margin-top: 0.25rem;
            text-align: right;
            min-height: 1.2rem;
        }
        .button {
            padding: clamp(0.7rem, 2vw, 0.9rem);
            background: var(--accent);
            color: var(--white);
            border: none;
            border-radius: 8px;
            font-size: clamp(0.9rem, 2vw, 1rem);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 0.5rem;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 5px rgba(230, 91, 80, 0.2);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
        }
        .button:hover { 
            background: var(--accent-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(230, 91, 80, 0.3);
        }
        .button:active {
            transform: translateY(0);
        }
        .terms {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            margin: 0.5rem 0;
            text-align: left;
        }
        .terms input[type="checkbox"] {
            margin-top: 0.25rem;
            accent-color: var(--accent);
            width: clamp(14px, 2vw, 16px);
            height: clamp(14px, 2vw, 16px);
        }
        .terms label {
            font-size: clamp(0.85rem, 1.5vw, 0.9rem);
            margin: 0;
        }
        .message {
            padding: 0.9rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-align: left;
            animation: fadeIn 0.5s;
            font-size: clamp(0.9rem, 2vw, 1rem);
        }
        .message.success { 
            background: var(--success-bg); 
            color: var(--success);
            border-left: 4px solid var(--success);
        }
        .message.error { 
            background: var(--error-bg); 
            color: var(--error);
            border-left: 4px solid var(--error);
        }
        .login-link {
            margin-top: 1.5rem;
            font-size: clamp(0.9rem, 2vw, 0.95rem);
        }
        .login-link a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        .login-link a:hover { 
            text-decoration: underline;
            color: var(--accent-hover);
        }
        footer {
            background: var(--primary);
            color: var(--white);
            text-align: center;
            padding: clamp(1rem, 3vw, 1.5rem);
            width: 100%;
        }
        footer p {
            font-size: clamp(0.85rem, 2vw, 1rem);
        }
        .requirements {
            font-size: clamp(0.75rem, 1.5vw, 0.8rem);
            color: #666;
            text-align: left;
            margin-top: 0.25rem;
        }
        .requirements ul {
            margin-left: 1.25rem;
            margin-top: 0.25rem;
        }
        @media (max-width: 768px) {
            header h1, h2 { font-size: clamp(1.5rem, 4vw, 1.75rem); }
            .register-container { 
                padding: clamp(1rem, 3vw, 2rem);
                max-width: 90%;
            }
        }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    </style>
</head>
<body>
    <header>
        <h1>Welcome to UrbanPulse</h1>
    </header>
    <nav>
        <div class="nav-container">
            <div class="dropdown">
                <button class="dropdown-btn">
                    <i class="fas fa-bars"></i> Menu
                </button>
                <div class="dropdown-content">
                    <a href="index.php"><i class="fas fa-home"></i> Home</a>
                    <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                    <a href="register.php" class="active"><i class="fas fa-user-plus"></i> Sign Up</a>
                </div>
            </div>
        </div>
    </nav>
    <main>
        <div class="register-container">
            <h2>Create Your Account</h2>
            <?php if (isset($message) && $msg_class === "success"): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $message; ?>
                </div>
            <?php elseif (isset($message) && $msg_class === "error"): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            <form method="post" action="register.php" id="registrationForm">
                <div class="form-group">
                    <label for="name">Full Name:</label>
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" id="name" name="name" placeholder="Enter your full name" required aria-label="Full Name" autocomplete="name">
                </div>

                <div class="form-group">
                    <label for="username">Username:</label>
                    <i class="fas fa-at input-icon"></i>
                    <input type="text" id="username" name="username" placeholder="Choose a username" required aria-label="Username" autocomplete="username">
                </div>

                <div class="form-group">
                    <label for="email">Email Address:</label>
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" id="email" name="email" placeholder="Enter your email address" required aria-label="Email Address" autocomplete="email">
                </div>

                <div class="form-group password-container">
                    <label for="password">Password:</label>
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" id="password" name="password" placeholder="Create a strong password" required aria-label="Password" autocomplete="new-password">
                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility('password')">
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

                <div class="form-group password-container">
                    <label for="confirm-password">Confirm Password:</label>
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" id="confirm-password" name="confirm-password" placeholder="Confirm your password" required aria-label="Confirm Password" autocomplete="new-password">
                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility('confirm-password')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>

                <div class="terms">
                    <input type="checkbox" id="terms" name="terms" required>
                    <label for="terms">I agree to the <a href="terms.php" target="_blank">Terms and Conditions</a> and <a href="privacy.php" target="_blank">Privacy Policy</a></label>
                </div>

                <button type="submit" class="button" aria-label="Register">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>
            <p class="login-link">Already have an account? <a href="login.php">Login Here</a></p>
        </div>
    </main>
    <footer>
        <p>© <?php echo date("Y"); ?> UrbanPulse. All rights reserved.</p>
    </footer>

    <script>
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