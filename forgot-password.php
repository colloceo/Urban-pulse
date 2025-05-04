<?php
$conn = new mysqli("localhost", "root", "", "ecomerce");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";
$email = "";
$reset_link = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);
    
    $stmt = $conn->prepare("SELECT username FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $token = bin2hex(random_bytes(32));
        $expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));
        
        $stmt = $conn->prepare("INSERT INTO reset_tokens (email, token, expiry) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE token = ?, expiry = ?");
        $stmt->bind_param("sssss", $email, $token, $expiry, $token, $expiry);
        $stmt->execute();
        
        $reset_link = "http://localhost/e-commerce/reset-password.php?token=" . $token;
        $message = "Password reset link sent!";
    } else {
        $message = "No account found with that email.";
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
    <title>Forgot Password - UrbanPulse</title>
    <link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
    <link rel="manifest" href="site.webmanifest">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="loader" id="loader"></div>

    <!-- Header -->
    <header class="py-4 text-center">
        <h1 class="display-4 fw-bold">Welcome to UrbanPulse</h1>
    </header>

    <!-- Desktop Navigation -->
    <nav class="desktop-nav navbar navbar-expand-md bg-dark navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="#">UrbanPulse</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#desktopNavbar" aria-controls="desktopNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="desktopNavbar">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="fas fa-home me-1"></i>Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt me-1"></i>Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php"><i class="fas fa-user-plus me-1"></i>Sign Up</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Mobile Bottom Navigation -->
    <nav class="mobile-nav d-md-none">
        <div class="container d-flex justify-content-around">
            <a href="index.php" class="nav-link">
                <i class="fas fa-home fa-lg"></i><br>Home
            </a>
            <a href="login.php" class="nav-link">
                <i class="fas fa-sign-in-alt fa-lg"></i><br>Login
            </a>
            <a href="register.php" class="nav-link">
                <i class="fas fa-user-plus fa-lg"></i><br>Sign Up
            </a>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="d-flex justify-content-center align-items-center py-5 flex-grow-1 form-page">
        <div class="register-container p-4 w-100">
            <h2 class="text-center mb-4">Forgot Password</h2>
            <?php if ($message && !$reset_link): ?>
                <div class="alert alert-danger d-flex align-items-center" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php elseif ($message && $reset_link): ?>
                <div class="alert alert-success d-flex align-items-center" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                    <div class="reset-link mt-2">
                        <a href="<?php echo $reset_link; ?>" style="color: var(--accent);"><?php echo $reset_link; ?></a>
                        <p class="mt-1">(Valid for 1 hour)</p>
                    </div>
                </div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="mb-3 form-group position-relative">
                    <label for="email" class="form-label">Email Address:</label>
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="Enter email" required aria-label="Email">
                </div>
                <button type="submit" class="btn btn-primary w-100" aria-label="Generate Reset Link">
                    <i class="fas fa-envelope me-2"></i>Generate Reset Link
                </button>
            </form>
            <p class="text-center mt-4"><a href="login.php" style="color: var(--accent);" class="text-decoration-none">Back to Login</a></p>
        </div>
    </main>

    <!-- Footer -->
    <footer class="py-4 text-center">
        <p>© <?php echo date("Y"); ?> UrbanPulse. All rights reserved. <a href="terms.php">Terms</a></p>
    </footer>

    <!-- Back to Top Button -->
    <button id="back-to-top" class="btn btn-primary" aria-label="Back to top">
        <i class="fas fa-chevron-up"></i>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        // Loader
        const loader = document.getElementById("loader");
        window.addEventListener("load", () => loader.style.display = "none");
        setTimeout(() => loader.style.display = "none", 5000);

        // Back to Top
        const backToTopButton = document.getElementById("back-to-top");
        window.addEventListener("scroll", () => {
            backToTopButton.style.display = window.scrollY > 300 ? "block" : "none";
        });
        backToTopButton.addEventListener("click", () => {
            window.scrollTo({ top: 0, behavior: "smooth" });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>