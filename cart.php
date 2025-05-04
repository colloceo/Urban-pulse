<?php
session_start();

// Redirect non-logged-in users
if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}

// Include database connection
require_once 'db_connect.php';

$error_message = '';
$cart_items = [];
$total_price = 0;

try {
    // Fetch user's name
    $stmt = $pdo->prepare("SELECT name FROM users WHERE username = ?");
    $stmt->execute([$_SESSION["user"]]);
    $user_name = $stmt->fetchColumn() ?: $_SESSION["user"];

    // Handle cart actions
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        if (isset($_POST["update_cart"])) {
            $product_id = (int)$_POST["product_id"];
            $new_quantity = (int)$_POST["quantity"];

            if ($new_quantity >= 1) {
                // Check available stock
                $stmt = $pdo->prepare("SELECT quantity FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                $available_quantity = $stmt->fetchColumn();

                if ($new_quantity <= $available_quantity) {
                    $current_quantity = $_SESSION["cart"][$product_id] ?? 0;
                    $quantity_diff = $new_quantity - $current_quantity;

                    // Update cart
                    $_SESSION["cart"][$product_id] = $new_quantity;

                    // Adjust product stock
                    if ($quantity_diff != 0) {
                        $stmt = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
                        $stmt->execute([$quantity_diff, $product_id]);
                    }
                } else {
                    $error_message = "Requested quantity exceeds available stock.";
                }
            } else {
                $error_message = "Invalid quantity.";
            }
        } elseif (isset($_POST["remove_item"])) {
            $product_id = (int)$_POST["product_id"];
            $current_quantity = $_SESSION["cart"][$product_id] ?? 0;

            // Return stock to inventory
            if ($current_quantity > 0) {
                $stmt = $pdo->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?");
                $stmt->execute([$current_quantity, $product_id]);
            }

            // Remove item from cart
            unset($_SESSION["cart"][$product_id]);
        } elseif (isset($_POST["clear_cart"])) {
            // Return all stock to inventory
            foreach ($_SESSION["cart"] ?? [] as $product_id => $quantity) {
                $stmt = $pdo->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?");
                $stmt->execute([$quantity, $product_id]);
            }
            // Clear cart
            $_SESSION["cart"] = [];
        }

        // Redirect to avoid form resubmission
        header("Location: cart.php");
        exit();
    }

    // Fetch cart items
    if (!empty($_SESSION["cart"])) {
        $product_ids = array_keys($_SESSION["cart"]);
        $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
        $stmt = $pdo->prepare("SELECT p.id, p.name, p.price, p.image, p.quantity AS stock FROM products p WHERE p.id IN ($placeholders)");
        $stmt->execute($product_ids);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Build cart items with quantities
        foreach ($products as $product) {
            $cart_items[] = [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'image' => $product['image'],
                'stock' => $product['stock'],
                'quantity' => $_SESSION["cart"][$product['id']],
                'subtotal' => $product['price'] * $_SESSION["cart"][$product['id']],
            ];
            $total_price += $product['price'] * $_SESSION["cart"][$product['id']];
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
    <meta name="description" content="View and manage your shopping cart at UrbanPulse.">
    <title>Cart - UrbanPulse</title>
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

        /* Cart Section */
        .cart-section {
            padding: 3rem 0;
        }

        .cart-section h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-align: center;
            color: var(--secondary);
        }

        .cart-section .welcome {
            font-size: 1.25rem;
            text-align: center;
            margin-bottom: 2rem;
            color: var(--text);
        }

        .cart-table {
            background-color: var(--white);
            border-radius: 10px;
            box-shadow: var(--shadow);
            overflow-x: auto;
            margin-bottom: 2rem;
        }

        .cart-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .cart-table th,
        .cart-table td {
            padding: 1rem;
            text-align: center;
            border-bottom: 1px solid var(--border);
        }

        .cart-table th {
            background-color: var(--secondary);
            color: var(--white);
            font-weight: 600;
        }

        .cart-table img {
            width: 80px;
            height: 80px;
            object-fit: contain;
            border-radius: 5px;
        }

        .cart-table input[type="number"] {
            width: 70px;
            padding: 0.5rem;
            border: 1px solid var(--border);
            border-radius: 5px;
            font-size: 0.95rem;
        }

        .cart-table .btn {
            padding: 0.5rem 1rem;
            font-size: 0.95rem;
        }

        .cart-table .btn-update {
            background-color: var(--primary);
            border: none;
            color: var(--white);
        }

        .cart-table .btn-update:hover {
            background-color: #e55a00;
        }

        .cart-table .btn-remove {
            background-color: var(--error);
            border: none;
            color: var(--white);
        }

        .cart-table .btn-remove:hover {
            background-color: #c9303d;
        }

        .cart-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .cart-actions .btn {
            padding: 0.75rem 1.5rem;
            font-size: 0.95rem;
        }

        .cart-actions .btn-clear {
            background-color: var(--error);
            border: none;
            color: var(--white);
        }

        .cart-actions .btn-clear:hover {
            background-color: #c9303d;
        }

        .cart-total {
            background-color: var(--white);
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: var(--shadow);
            text-align: right;
            margin-bottom: 2rem;
        }

        .cart-total h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--secondary);
            margin-bottom: 1rem;
        }

        .cart-total .btn {
            background-color: var(--primary);
            border: none;
            padding: 0.75rem 1.5rem;
            font-size: 0.95rem;
            color: var(--white);
        }

        .cart-total .btn:hover {
            background-color: #e55a00;
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

        .empty-cart {
            text-align: center;
            font-size: 1.1rem;
            color: #666;
            margin: 2rem 0;
        }

        @media (max-width: 767.98px) {
            .cart-section h1 {
                font-size: 2rem;
            }
            .cart-table th,
            .cart-table td {
                font-size: 0.9rem;
                padding: 0.75rem;
            }
            .cart-table img {
                width: 60px;
                height: 60px;
            }
            .cart-actions {
                flex-direction: column;
                align-items: stretch;
            }
            .cart-total {
                text-align: center;
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
            height:orphous40px;
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
                            <a class="nav-link" href="shop.php"><i class="fas fa-shopping-bag me-1"></i> Shop</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="about.php"><i class="fas fa-info-circle me-1"></i> About</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="contact.php"><i class="fas fa-envelope me-1"></i> Contact</a>
                        </li>
                    </ul>
                    <div class="d-flex gap-2">
                        <a href="cart.php" class="btn btn-outline-primary active"><i class="fas fa-shopping-cart me-1"></i> Cart</a>
                        <a href="logout.php" class="btn btn-outline-primary"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <!-- Cart Section -->
    <section class="cart-section">
        <div class="container">
            <h1>Your Shopping Cart</h1>
            <p class="welcome">Welcome, <?php echo htmlspecialchars($user_name); ?>!</p>
            <?php if ($error_message): ?>
                <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <?php if (empty($cart_items)): ?>
                <p class="empty-cart">Your cart is empty. <a href="shop.php" class="text-decoration-underline">Continue shopping</a>.</p>
            <?php else: ?>
                <div class="cart-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart_items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                            <span><?php echo htmlspecialchars($item['name']); ?></span>
                                        </div>
                                    </td>
                                    <td>KSH <?php echo number_format($item['price'], 2); ?></td>
                                    <td>
                                        <form method="post" action="cart.php">
                                            <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                            <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock']; ?>" aria-label="Quantity">
                                            <button type="submit" name="update_cart" class="btn btn-update" aria-label="Update quantity">
                                                <i class="fas fa-sync-alt"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <td>KSH <?php echo number_format($item['subtotal'], 2); ?></td>
                                    <td>
                                        <form method="post" action="cart.php">
                                            <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" name="remove_item" class="btn btn-remove" aria-label="Remove item">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="cart-actions">
                    <a href="shop.php" class="btn btn-outline-primary"><i class="fas fa-shopping-bag me-1"></i> Continue Shopping</a>
                    <form method="post" action="cart.php">
                        <button type="submit" name="clear_cart" class="btn btn-clear" aria-label="Clear cart">
                            <i class="fas fa-trash-alt me-1"></i> Clear Cart
                        </button>
                    </form>
                </div>
                <div class="cart-total">
                    <h3>Total: KSH <?php echo number_format($total_price, 2); ?></h3>
                    <a href="checkout.php" class="btn btn-primary"><i class="fas fa-credit-card me-1"></i> Proceed to Checkout</a>
                </div>
            <?php endif; ?>
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