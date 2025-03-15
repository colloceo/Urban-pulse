<?php
$conn = new mysqli("localhost", "root", "", "ecomerce");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";
$token = isset($_GET['token']) ? $_GET['token'] : '';

if (!$token) {
    $message = "Invalid or missing reset token.";
} else {
    $stmt = $conn->prepare("SELECT email, expiry FROM reset_tokens WHERE token = ?");
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("s", $token);
    if (!$stmt->execute()) {
        die("Execute failed: " . $stmt->error);
    }
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $message = "Invalid or expired reset token.";
    } else {
        $row = $result->fetch_assoc();
        $email = $row['email'];
        $expiry = strtotime($row['expiry']);
        
        if (time() > $expiry) {
            $message = "This reset token has expired.";
        } elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
            $new_password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            
            if ($new_password !== $confirm_password) {
                $message = "Passwords do not match.";
            } elseif (strlen($new_password) < 8) {
                $message = "Password must be at least 8 characters long.";
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
                if ($stmt === false) {
                    die("Prepare failed: " . $conn->error);
                }
                
                $stmt->bind_param("ss", $hashed_password, $email);
                if (!$stmt->execute()) {
                    die("Execute failed: " . $stmt->error);
                }
                
                $stmt = $conn->prepare("DELETE FROM reset_tokens WHERE token = ?");
                $stmt->bind_param("s", $token);
                $stmt->execute();
                
                $message = "Password successfully reset. You can now log in with your new password.";
                $token = "";
            }
        }
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Reset your UrbanPulse password.">
    <title>Reset Password - UrbanPulse</title>
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
            padding: 2rem;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        nav {
            background: var(--secondary);
            padding: 1rem;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        nav ul {
            display: flex;
            justify-content: center;
            list-style: none;
        }
        nav ul li { margin: 0 1rem; }
        nav ul li a {
            color: var(--white);
            text-decoration: none;
            padding: 0.75rem 1.5rem;
            border-radius: 5px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        nav ul li a:hover, nav ul li a.active { 
            background: var(--accent);
            transform: translateY(-2px);
        }
        main {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 3rem 1rem;
            background: linear-gradient(rgba(244, 244, 249, 0.8), rgba(244, 244, 249, 0.9));
        }
        .form-container {
            background: var(--white);
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 450px;
            text-align: center;
            animation: fadeIn 1s ease-in-out;
            position: relative;
            overflow: hidden;
        }
        .form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: var(--accent);
        }
        h2 {
            font-size: 2rem;
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
            width: 50px;
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
            font-weight: 500;
            font-size: 0.95rem;
            color: var(--secondary);
            margin-bottom: 0.5rem;
            display: block;
        }
        input[type="password"] {
            padding: 0.85rem 1rem;
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
            background-color: #f9f9f9;
            padding-left: 40px;
        }
        input[type="password"]:focus {
            border-color: var(--accent);
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 111, 97, 0.1);
            background-color: var(--white);
        }
        .input-icon {
            position: absolute;
            left: 12px;
            top: 42px;
            color: #999;
        }
        .button {
            padding: 0.9rem;
            background: var(--accent);
            color: var(--white);
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 2px 5px rgba(230, 91, 80, 0.2);
        }
        .button:hover { 
            background: var(--accent-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(230, 91, 80, 0.3);
        }
        .error-message, .success-message {
            padding: 0.9rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-align: left;
            animation: fadeIn 0.5s;
        }
        .error-message {
            background: var(--error-bg);
            color: var(--error);
            border-left: 4px solid var(--error);
        }
        .success-message {
            background: var(--success-bg);
            color: var(--success);
            border-left: 4px solid var(--success);
        }
        .reset-link a {
            color: var(--accent);
            text-decoration: none;
        }
        .reset-link a:hover {
            text-decoration: underline;
            color: var(--accent-hover);
        }
        footer {
            background: var(--primary);
            color: var(--white);
            text-align: center;
            padding: 1.5rem;
        }
        @media (max-width: 768px) {
            h2 { font-size: 1.75rem; }
            .form-container { padding: 2rem 1.5rem; max-width: 90%; }
        }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    </style>
</head>
<body>
    <header>
        <h1>UrbanPulse</h1>
    </header>
    <nav>
        <ul>
            <li><a href="index.html"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
            <li><a href="register.php"><i class="fas fa-user-plus"></i> Sign Up</a></li>
        </ul>
    </nav>
    <main>
        <div class="form-container">
            <h2>Reset Password</h2>
            <?php if ($message && $token): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php elseif ($message): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($token && !$message): ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="password">New Password:</label>
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" 
                               placeholder="Enter new password" required aria-label="New Password">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password:</label>
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="confirm_password" name="confirm_password" 
                               placeholder="Confirm new password" required aria-label="Confirm Password">
                    </div>
                    <button type="submit" class="button">
                        <i class="fas fa-key"></i> Reset Password
                    </button>
                </form>
            <?php endif; ?>
            <p><a href="login.php" class="reset-link">Back to Login</a></p>
        </div>
    </main>
    <footer>
        <p>© <?php echo date("Y"); ?> UrbanPulse. All rights reserved.</p>
    </footer>
</body>
</html>
<?php $conn->close(); ?>