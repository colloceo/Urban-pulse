```php
<?php
session_start();

// Load environment variables
require_once 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Redirect non-logged-in users
if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}

// Redirect if cart is empty
if (empty($_SESSION["cart"])) {
    header("Location: cart.php");
    exit();
}

// Include database connection
require_once 'db_connect.php';

$error_message = '';
$cart_items = [];
$total_amount = 0;

try {
    // Fetch user's name
    $stmt = $pdo->prepare("SELECT name FROM users WHERE username = ?");
    $stmt->execute([$_SESSION["user"]]);
    $user_name = $stmt->fetchColumn() ?: $_SESSION["user"];

    // Fetch cart items
    $product_ids = array_keys($_SESSION["cart"]);
    if (!empty($product_ids)) {
        $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
        $stmt = $pdo->prepare("SELECT id, name, price, image, quantity AS stock FROM products WHERE id IN ($placeholders)");
        $stmt->execute($product_ids);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Build cart items and calculate total
        foreach ($products as $product) {
            $quantity = $_SESSION["cart"][$product['id']];
            if ($quantity > 0 && $quantity <= $product['stock']) {
                $cart_items[] = [
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'image' => $product['image'],
                    'quantity' => $quantity,
                    'subtotal' => $product['price'] * $quantity,
                ];
                $total_amount += $product['price'] * $quantity;
            } else {
                $error_message = "Some items in your cart are out of stock or invalid.";
                unset($_SESSION["cart"][$product['id']]);
            }
        }
    }

    // Redirect if no valid items
    if (empty($cart_items)) {
        header("Location: cart.php");
        exit();
    }

    // Store total amount
    $_SESSION['totalAmount'] = $total_amount;

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
        $city = filter_input(INPUT_POST, 'city', FILTER_SANITIZE_STRING);
        $state = filter_input(INPUT_POST, 'state', FILTER_SANITIZE_STRING);
        $zip = filter_input(INPUT_POST, 'zip', FILTER_SANITIZE_STRING);
        $mpesa_number = filter_input(INPUT_POST, 'mpesa_number', FILTER_SANITIZE_STRING);

        // Validate inputs
        if (!$name || !$email || !$address || !$city || !$state || !$zip || !$mpesa_number) {
            $error_message = "All fields are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Invalid email address.";
        } elseif (!preg_match("/^(?:254|\+254|0)?[17]\d{8}$/", $mpesa_number)) {
            $error_message = "Invalid M-Pesa number.";
        } else {
            // Generate order ID
            $prefix = "ORD" . date("Ymd");
            $stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_id LIKE '$prefix%'");
            $count = $stmt->fetchColumn() + 1;
            $order_id = $prefix . str_pad($count, 4, "0", STR_PAD_LEFT);

            // Insert order
            $stmt = $pdo->prepare("INSERT INTO orders (order_id, username, total, status) VALUES (?, ?, ?, 'Pending')");
            $stmt->execute([$order_id, $_SESSION["user"], $total_amount]);

            // Insert order items
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            foreach ($cart_items as $item) {
                $stmt->execute([$order_id, $item['id'], $item['quantity'], $item['price']]);
            }

            // Store session data for mpesa.php
            $_SESSION['order_id'] = $order_id;
            $_SESSION['mpesa_number'] = $mpesa_number;
            $_SESSION['cart_items'] = $cart_items;
            $_SESSION['email'] = $email;

            // Log session data for debugging
            error_log("Session data before redirect: " . print_r([
                'totalAmount' => $_SESSION['totalAmount'],
                'mpesa_number' => $_SESSION['mpesa_number'],
                'order_id' => $_SESSION['order_id'],
                'cart_items' => $_SESSION['cart_items'],
                'email' => $_SESSION['email']
            ], true));

            // Clear cart
            $_SESSION["cart"] = [];

            // Redirect to mpesa.php
            header("Location: mpesa.php");
            exit();
        }
    }

} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Complete your purchase at UrbanPulse with secure checkout.">
    <title>Checkout - UrbanPulse</title>
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
            --error: #e63946;
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

        /* Checkout Section */
        .checkout-section {
            padding: 3rem 0;
        }

        .checkout-section h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-align: center;
            color: var(--secondary);
        }

        .checkout-section .welcome {
            font-size: 1.25rem;
            text-align: center;
            margin-bottom: 2rem;
            color: var(--text);
        }

        .checkout-container {
            background-color: var(--white);
            border-radius: 10px;
            box-shadow: var(--shadow);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .checkout-container h2 {
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--secondary);
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text);
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: 5px;
            font-size: 0.95rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(255, 98, 0, 0.25);
            outline: none;
        }

        .error {
            background-color: #ffe6e6;
            color: var(--error);
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 2rem;
            text-align: center;
            font-size: 0.95rem;
        }

        .order-summary {
            background-color: var(--white);
            border-radius: 10px;
            box-shadow: var(--shadow);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .order-summary h2 {
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--secondary);
            margin-bottom: 1.5rem;
        }

        .order-summary table {
            width: 100%;
            border-collapse: collapse;
        }

        .order-summary th,
        .order-summary td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        .order-summary th {
            background-color: var(--secondary);
            color: var(--white);
            font-weight: 600;
        }

        .order-summary img {
            width: 60px;
            height: 60px;
            object-fit: contain;
            border-radius: 5px;
        }

        .order-summary .total {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary);
            text-align: right;
            padding-top: 1rem;
        }

        .checkout-button {
            background-color: var(--primary);
            border: none;
            padding: 0.75rem 1.5rem;
            font-size: 0.95rem;
            color: var(--white);
            border-radius: 5px;
            width: 100%;
            transition: background 0.3s ease;
        }

        .checkout-button:hover {
            background-color: #e55a00;
        }

        @media (max-width: 767.98px) {
            .checkout-section h1 {
                font-size: 2rem;
            }
            .checkout-container,
            .order-summary {
                padding: 1.5rem;
            }
            .order-summary th,
            .order-summary td {
                font-size: 0.9rem;
                padding: 0.75rem;
            }
            .order-summary img {
                width: 50px;
                height: 50px;
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
                            <a class="nav-link" href="shop.php"><i class="fas fa-shopping-bag me-1"></i> Shop</a>
                         </li>
                        <li class="nav-item">
                            <a href="cart.php" class="nav-link"><i class="fas fa-shopping-cart me-1"></i> Cart</a>
                        </li>
                        <li class="nav-item">
                            <a href="checkout.php" class="nav-link active"><i class="fas fa-credit-card me-1"></i> Checkout</a>
                        </li>
                        <li>
                            <a href="track.php" class="nav-link"><i class="fas fa-truck me-1"></i> Track Orders</a>
                        </li>
                    </ul>
                    <div class="d-flex gap-2">
                        <a href="logout.php" class="btn btn-outline-primary"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <!-- Checkout Section -->
    <section class="checkout-section">
        <div class="container">
            <h1>Checkout</h1>
            <p class="welcome">Welcome, <?php echo htmlspecialchars($user_name); ?>!</p>
            <?php if ($error_message): ?>
                <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php elseif (isset($_GET['error'])): ?>
                <div class="error"><?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>
            <div class="row">
                <div class="col-md-6">
                    <div class="checkout-container">
                        <h2>Billing & Payment Information</h2>
                        <form id="payment-form" action="checkout.php" method="post">
                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user_name); ?>" readonly required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label for="address">Address</label>
                                <input type="text" id="address" name="address" required placeholder="123 Main St">
                            </div>
                            <div class="form-group">
                                <label for="city">City</label>
                                <input type="text" id="city" name="city" required placeholder="Nairobi">
                            </div>
                            <div class="form-group">
                                <label for="state">State</label>
                                <input type="text" id="state" name="state" required placeholder="Nairobi">
                            </div>
                            <div class="form-group">
                                <label for="zip">Zip Code</label>
                                <input type="text" id="zip" name="zip" required placeholder="00100">
                            </div>
                            <div class="form-group">
                                <label for="mpesa-number">M-Pesa Number</label>
                                <input type="text" id="mpesa-number" name="mpesa_number" pattern="^(?:254|\+254|0)?[17]\d{8}$" required placeholder="0712345678" title="Enter a valid Kenyan phone number (e.g., 0712345678 or 254712345678)">
                            </div>
                            <button type="submit" class="checkout-button"><i class="fas fa-credit-card me-1"></i> Place Order</button>
                        </form>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="order-summary">
                        <h2>Order Summary</h2>
                        <table>
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart_items as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                                <span><?php echo htmlspecialchars($item['name']); ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td>KSH <?php echo number_format($item['subtotal'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="total">Total: KSH <?php echo number_format($total_amount, 2); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p>© <?php echo date("Y"); ?> UrbanPulse. All rights reserved.</p>
            <div class="mt-2">
                <a href="terms.php">Terms</a> | <a href="privacy.php">Privacy</a> | <a href="contact.php">Contact</a>
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
    </script>
</body>
</html>
<?php $pdo = null; ?>