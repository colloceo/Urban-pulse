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

// Database connection
$host = "localhost";
$dbname = "ecomerce";
$username_db = "root";
$password_db = "";

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize form data
    $username = filter_input(INPUT_POST, "username", FILTER_SANITIZE_STRING);
    $password = $_POST["password"];
    $remember_me = isset($_POST["remember-me"]);

    try {
        $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username_db, $password_db);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check if user exists (no 'id' column)
        $stmt = $db->prepare("SELECT name, username, password FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Verify password
            if (password_verify($password, $user["password"])) {
                // Set session data without 'id'
                $_SESSION["user"] = [
                    "name" => $user["name"],
                    "username" => $user["username"]
                ];

                // Handle "Remember Me" (optional: set a cookie)
                if ($remember_me) {
                    $token = bin2hex(random_bytes(16));
                    setcookie("remember_me", $token, time() + (30 * 24 * 60 * 60), "/", "", true, true);
                    // Optionally store token in DB if you add a token column
                }

                header("Location: shop.php");
                exit();
            } else {
                $error_message = "Invalid password.";
            }
        } else {
            $error_message = "Username not found.";
        }
    } catch (PDOException $e) {
        $error_message = "Connection failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Login to UrbanPulse and explore the latest trends in fashion and accessories.">
    <title>Login to UrbanPulse</title>
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
        .login-container {
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
        .login-container::before {
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
        .options-row {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            margin: 0.5rem 0;
            gap: 1rem;
        }
        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: clamp(0.85rem, 1.5vw, 0.95rem);
        }
        .remember-me input[type="checkbox"] {
            accent-color: var(--accent);
            width: clamp(14px, 2vw, 16px);
            height: clamp(14px, 2vw, 16px);
        }
        .forgot-password {
            text-align: right;
        }
        .forgot-password a {
            color: var(--accent);
            text-decoration: none;
            font-size: clamp(0.8rem, 1.5vw, 0.9rem);
            font-weight: 500;
            transition: color 0.3s;
        }
        .forgot-password a:hover, p a:hover { 
            text-decoration: underline;
            color: var(--accent-hover);
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
            align-items purpsefully left blank align-items: center;
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
        .error-message {
            background: var(--error-bg);
            color: var(--error);
            padding: 0.9rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-align: left;
            animation: fadeIn 0.5s;
            border-left: 4px solid var(--error);
            font-size: clamp(0.9rem, 2vw, 1rem);
        }
        .divider {
            display: flex;
            align-items: center;
            margin: 1.5rem 0;
            color: #666;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #ddd;
        }
        .divider span {
            padding: 0 1rem;
            font-size: clamp(0.85rem, 1.8vw, 0.9rem);
        }
        .signup-link {
            margin-top: 0.5rem;
            font-size: clamp(0.9rem, 2vw, 0.95rem);
        }
        .signup-link a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        .signup-link a:hover { 
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
        @media (max-width: 768px) {
            header h1, h2 { font-size: clamp(1.5rem, 4vw, 1.75rem); }
            .login-container { 
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
                    <a href="login.php" class="active"><i class="fas fa-sign-in-alt"></i> Login</a>
                    <a href="register.php"><i class="fas fa-user-plus"></i> Sign Up</a>
                </div>
            </div>
        </div>
    </nav>
    <main>
        <div class="login-container">
            <h2>Welcome Back!</h2>
            <?php if ($error_message): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <form method="post" action="login.php">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" id="username" name="username" placeholder="Enter your username" required 
                           aria-label="Username" autocomplete="username">
                </div>

                <div class="form-group password-container">
                    <label for="password">Password:</label>
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required 
                           aria-label="Password" autocomplete="current-password">
                    <button type="button" class="toggle-password" 
                            onclick="togglePasswordVisibility('password')" 
                            aria-label="Toggle password visibility">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>

                <div class="options-row">
                    <div class="remember-me">
                        <input type="checkbox" id="remember-me" name="remember-me">
                        <label for="remember-me">Remember me</label>
                    </div>
                    <div class="forgot-password">
                        <a href="forgot-password.php">Forgot Password?</a>
                    </div>
                </div>

                <button type="submit" class="button" aria-label="Login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
            
            <div class="divider"><span>OR</span></div>
            
            <p class="signup-link">Don't have an account? <a href="register.php">Sign Up</a></p>
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
    </script>
</body>
</html>