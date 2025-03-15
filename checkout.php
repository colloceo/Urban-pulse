<?php
session_start();

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['totalAmount']) || empty($_SESSION["cart"])) {
    header("Location: cart.php");
    exit();
}

$host = "localhost";
$dbname = "ecomerce";
$username_db = "root";
$password_db = "";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username_db, $password_db);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

function generateOrderId($conn) {
    $prefix = "ORD" . date("Ymd");
    $stmt = $conn->query("SELECT COUNT(*) FROM orders WHERE order_id LIKE '$prefix%'");
    $count = $stmt->fetchColumn() + 1;
    return $prefix . str_pad($count, 4, "0", STR_PAD_LEFT);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
    $city = filter_input(INPUT_POST, 'city', FILTER_SANITIZE_STRING);
    $state = filter_input(INPUT_POST, 'state', FILTER_SANITIZE_STRING);
    $zip = filter_input(INPUT_POST, 'zip', FILTER_SANITIZE_STRING);
    $mpesa_number = filter_input(INPUT_POST, 'mpesa_number', FILTER_SANITIZE_STRING);
    $total = (float)$_SESSION['totalAmount'];
    $username = $_SESSION['user']['username'];
    $cart = $_SESSION['cart'];

    $order_id = generateOrderId($conn);

    $product_ids = array_keys($cart);
    if (empty($product_ids)) {
        die("Cart is empty or invalid.");
    }
    $stmt = $conn->prepare("SELECT id, name, price FROM products WHERE id IN (" . implode(',', array_fill(0, count($product_ids), '?')) . ")");
    $stmt->execute($product_ids);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $cart_items = [];
    foreach ($products as $product) {
        $quantity = $cart[$product['id']];
        if ($quantity > 0) {
            $cart_items[$product['id']] = [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $quantity
            ];
        }
    }

    if (empty($cart_items)) {
        die("No valid items found in cart.");
    }

    $stmt = $conn->prepare("INSERT INTO orders (order_id, username, total, status) VALUES (:order_id, :username, :total, 'Pending')");
    $stmt->execute([':order_id' => $order_id, ':username' => $username, ':total' => $total]);

    $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (:order_id, :product_id, :quantity, :price)");
    foreach ($cart_items as $item) {
        $stmt->execute([
            ':order_id' => $order_id,
            ':product_id' => $item['id'],
            ':quantity' => $item['quantity'],
            ':price' => $item['price']
        ]);
    }

    $_SESSION['order_id'] = $order_id;
    $_SESSION['mpesa_number'] = $mpesa_number;
    $_SESSION['totalAmount'] = $total;
    $_SESSION['cart_items'] = $cart_items;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - UrbanPulse</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        :root {
            --primary: #1a2634;
            --secondary: #2e3b4e;
            --accent: #ff6f61;
            --light: #f4f4f9;
            --white: #ffffff;
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
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1rem;
            transition: margin-left 0.3s ease;
        }

        main.shifted { margin-left: 260px; }

        .checkout-container {
            background: var(--white);
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 2rem;
        }

        h2 {
            font-size: 1.75rem;
            color: var(--primary);
            text-align: center;
            margin-bottom: 2rem;
        }

        label {
            display: block;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        input[type="text"],
        input[type="email"] {
            width: 100%;
            padding: 0.75rem;
            margin-bottom: 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus,
        input[type="email"]:focus {
            border-color: var(--accent);
            outline: none;
        }

        .payment-section {
            margin-top: 1.5rem;
        }

        .button {
            padding: 0.75rem 1.5rem;
            background: var(--accent);
            color: var(--white);
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            width: 100%;
            transition: background 0.3s;
        }

        .button:hover { background: #e65b50; }

        .total-summary {
            text-align: right;
            font-size: 1.25rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
        }

        .total-summary span { color: var(--accent); font-weight: 700; }

        footer {
            background: var(--primary);
            color: var(--white);
            text-align: center;
            padding: 1.5rem;
        }

        @media (max-width: 768px) {
            .sidebar { width: 200px; left: -200px; }
            main.shifted { margin-left: 220px; }
            .checkout-container { padding: 1rem; }
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
            <li><a href="cart.php"><i class="fas fa-shopping-cart"></i> Cart</a></li>
            <li><a href="track.php"><i class="fas fa-truck"></i> Track Orders</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>

    <main id="mainContent">
        <section class="checkout-container">
            <h2>Checkout</h2>
            <form id="payment-form" action="mpesa.php" method="post">
                <h3>Billing Information</h3>
                <label for="name">Full Name:</label>
                <input type="text" id="name" name="name" required placeholder="collins ceo">

                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required placeholder="collinsceo@example.com">

                <label for="address">Address:</label>
                <input type="text" id="address" name="address" required placeholder="123 Main St">

                <label for="city">City:</label>
                <input type="text" id="city" name="city" required placeholder="Nairobi">

                <label for="state">State:</label>
                <input type="text" id="state" name="state" required placeholder="Nairobi">

                <label for="zip">Zip Code:</label>
                <input type="text" id="zip" name="zip" required placeholder="00100">

                <div class="payment-section">
                    <h3>Payment Information (M-Pesa)</h3>
                    <label for="mpesa-number">M-Pesa Number:</label>
                    <input type="text" id="mpesa-number" name="mpesa_number" pattern="^(?:254|\+254|0)?[17]\d{8}$" placeholder="0712345678" title="Enter a valid Kenyan phone number (e.g., 0712345678 or 254712345678)" required>
                </div>

                <div class="total-summary">
                    Total: <span>KSH <?php echo number_format($_SESSION['totalAmount'], 2); ?></span>
                </div>

                <input type="hidden" id="totalAmount" name="totalAmount" value="<?php echo htmlspecialchars($_SESSION['totalAmount']); ?>">
                <input type="submit" value="Place Order" class="button">
            </form>
        </section>
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
<?php $conn = null; ?>