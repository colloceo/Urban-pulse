<?php
session_start();

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "ecomerce");
if ($conn->connect_error) {
    exit("Database connection failed.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_id = (int)$_POST["product_id"];
    if (isset($_POST["update_cart"])) {
        $new_quantity = (int)$_POST["quantity"];
        $stmt = $conn->prepare("SELECT quantity FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $stmt->bind_result($stock);
        $stmt->fetch();
        $stmt->close();

        if ($new_quantity > $stock) {
            $error_message = "Quantity exceeds stock.";
        } elseif ($new_quantity > 0) {
            $_SESSION["cart"][$product_id] = $new_quantity;
        } else {
            unset($_SESSION["cart"][$product_id]);
        }
    } elseif (isset($_POST["remove_item"])) {
        unset($_SESSION["cart"][$product_id]);
    }
}

$total = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart - UrbanPulse</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
    <style>
        :root {
            --primary: #1a2634;
            --secondary: #2e3b4e;
            --accent: #ff6f61;
            --light: #f4f4f9;
            --white: #ffffff;
            --error: #e63946;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Roboto', sans-serif;
            background: var(--light);
            color: #333;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        header {
            background: var(--primary);
            color: var(--white);
            padding: 1.5rem;
            text-align: center;
            position: relative;
        }

        header h1 {
            font-size: 1.75rem;
            font-weight: 700;
        }

        .menu-toggle {
            position: absolute;
            left: 1.5rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--white);
            font-size: 1.5rem;
            cursor: pointer;
        }

        .sidebar {
            width: 240px;
            height: 100vh;
            background: var(--secondary);
            position: fixed;
            top: 0;
            left: -240px;
            transition: left 0.3s ease;
            z-index: 1000;
        }

        .sidebar.open { left: 0; }

        .sidebar ul {
            list-style: none;
            padding: 5rem 1rem 1rem;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--white);
            text-decoration: none;
            padding: 0.75rem 1rem;
            margin: 0.5rem 0;
            border-radius: 4px;
            transition: background 0.2s;
        }

        .sidebar a:hover, .sidebar a.active {
            background: var(--accent);
        }

        main {
            flex: 1;
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
            transition: margin-left 0.3s ease;
        }

        main.shifted { margin-left: 260px; }

        h2 {
            font-size: 1.75rem;
            color: var(--primary);
            text-align: center;
            margin-bottom: 2rem;
        }

        .cart-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--white);
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .cart-table th, .cart-table td {
            padding: 1rem;
            text-align: center;
            border-bottom: 1px solid #eee;
        }

        .cart-table th {
            background: var(--secondary);
            color: var(--white);
            font-weight: 700;
        }

        .cart-table tr:hover { background: #f9f9f9; }

        .quantity-input {
            width: 60px;
            padding: 0.25rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
        }

        .button {
            padding: 0.5rem 1rem;
            background: var(--accent);
            color: var(--white);
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .button:hover { background: #e65b50; }

        .empty-cart {
            text-align: center;
            font-size: 1.1rem;
            color: #666;
            margin: 2rem 0;
        }

        .empty-cart a {
            color: var(--accent);
            text-decoration: none;
        }

        .empty-cart a:hover { text-decoration: underline; }

        .cart-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .error {
            background: #ffe6e6;
            color: var(--error);
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 2rem;
            text-align: center;
        }

        footer {
            background: var(--primary);
            color: var(--white);
            text-align: center;
            padding: 1rem;
        }

        @media (max-width: 768px) {
            .sidebar { width: 200px; left: -200px; }
            main.shifted { margin-left: 220px; }
            .cart-table { font-size: 0.9rem; }
            .cart-table th, .cart-table td { padding: 0.75rem; }
            .cart-actions { flex-direction: column; }
        }
    </style>
</head>
<body>
    <header>
        <button class="menu-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION["user"]["name"]); ?></h1>
    </header>

    <nav class="sidebar" id="sidebar">
        <ul>
            <li><a href="shop.php"><i class="fas fa-shopping-bag"></i> Products</a></li>
            <li><a href="cart.php" class="active"><i class="fas fa-shopping-cart"></i> Cart</a></li>
            <li><a href="track.php"><i class="fas fa-truck"></i> Track Orders</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>

    <main id="mainContent">
        <h2>Your Cart</h2>

        <?php if (isset($error_message)): ?>
            <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if (empty($_SESSION["cart"])): ?>
            <div class="empty-cart">Your cart is empty. <a href="shop.php">Continue shopping</a>.</div>
        <?php else: ?>
            <table class="cart-table">
                <tr>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Total</th>
                    <th>Action</th>
                </tr>
                <?php
                foreach ($_SESSION['cart'] as $product_id => $quantity) {
                    $stmt = $conn->prepare("SELECT name, price, quantity AS stock FROM products WHERE id = ?");
                    $stmt->bind_param("i", $product_id);
                    $stmt->execute();
                    $row = $stmt->get_result()->fetch_assoc();
                    if ($row) {
                        $item_total = $quantity * $row['price'];
                        $total += $item_total;
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td>
                            <form method="post">
                                <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                <input type="number" name="quantity" value="<?php echo $quantity; ?>" min="1" max="<?php echo $row['stock']; ?>" class="quantity-input">
                                <button type="submit" name="update_cart" class="button">Update</button>
                            </form>
                        </td>
                        <td>KSH <?php echo number_format($row['price'], 2); ?></td>
                        <td>KSH <?php echo number_format($item_total, 2); ?></td>
                        <td>
                            <form method="post">
                                <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                <button type="submit" name="remove_item" class="button">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php
                    }
                    $stmt->close();
                }
                $_SESSION['totalAmount'] = $total;
                ?>
                <tr>
                    <td colspan="3"><strong>Total:</strong></td>
                    <td><strong>KSH <?php echo number_format($total, 2); ?></strong></td>
                    <td></td>
                </tr>
            </table>
            <div class="cart-actions">
                <a href="shop.php" class="button">Continue Shopping</a>
                <form action="checkout.php" method="post">
                    <input type="submit" value="Checkout" class="button">
                </form>
            </div>
        <?php endif; ?>
    </main>

    <footer>
        <p>© <?php echo date("Y"); ?> UrbanPulse</p>
    </footer>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('mainContent').classList.toggle('shifted');
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>