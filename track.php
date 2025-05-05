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
$orders = [];
$order_items = [];
$total_pages = 1;

try {
    // Fetch user's name
    $stmt = $pdo->prepare("SELECT name FROM users WHERE username = ?");
    $stmt->execute([$_SESSION["user"]]);
    $user_name = $stmt->fetchColumn() ?: $_SESSION["user"];

    // Handle filters and pagination
    $page = max(1, (int)($_GET['page'] ?? 1));
    $orders_per_page = 5;
    $offset = ($page - 1) * $orders_per_page;
    $sort = $_GET['sort'] ?? 'date_desc';
    $status_filter = $_GET['status'] ?? '';

    // Fetch orders
    $query = "SELECT order_id, total, status, created_at 
              FROM orders 
              WHERE username = ?";
    if ($status_filter) $query .= " AND status = ?";
    $query .= match($sort) {
        'date_asc' => " ORDER BY created_at ASC",
        'total_asc' => " ORDER BY total ASC",
        'total_desc' => " ORDER BY total DESC",
        default => " ORDER BY created_at DESC"
    } . " LIMIT ?, ?";

    $stmt = $pdo->prepare($query);
    $stmt->bindValue(1, $_SESSION["user"], PDO::PARAM_STR);
    $param_index = 2;
    if ($status_filter) {
        $stmt->bindValue($param_index++, $status_filter, PDO::PARAM_STR);
    }
    $stmt->bindValue($param_index++, $offset, PDO::PARAM_INT);
    $stmt->bindValue($param_index, $orders_per_page, PDO::PARAM_INT);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count total orders for pagination
    $count_query = "SELECT COUNT(*) FROM orders WHERE username = ?" . ($status_filter ? " AND status = ?" : "");
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->bindValue(1, $_SESSION["user"], PDO::PARAM_STR);
    if ($status_filter) {
        $count_stmt->bindValue(2, $status_filter, PDO::PARAM_STR);
    }
    $count_stmt->execute();
    $total_pages = ceil($count_stmt->fetchColumn() / $orders_per_page);

    // Fetch order items
    foreach ($orders as $order) {
        $stmt = $pdo->prepare("SELECT oi.quantity, oi.price, p.name, p.image 
                               FROM order_items oi 
                               JOIN products p ON oi.product_id = p.id 
                               WHERE oi.order_id = ?");
        $stmt->execute([$order['order_id']]);
        $order_items[$order['order_id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <meta name="description" content="Track your orders with UrbanPulse.">
    <title>Track Orders - UrbanPulse</title>
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

        /* Track Section */
        .track-section {
            padding: 3rem 0;
        }

        .track-section h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-align: center;
            color: var(--secondary);
        }

        .track-section .welcome {
            font-size: 1.25rem;
            text-align: center;
            margin-bottom: 2rem;
            color: var(--text);
        }

        .filters {
            background-color: var(--white);
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }

        .filters form {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .filters select {
            max-width: 200px;
            border-radius: 5px;
            border: 1px solid var(--border);
            padding: 0.75rem;
            font-size: 0.95rem;
        }

        .filters select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(255, 98, 0, 0.25);
        }

        .filters .btn {
            background-color: var(--primary);
            border: none;
            padding: 0.75rem 1.5rem;
            font-size: 0.95rem;
            color: var(--white);
        }

        .filters .btn:hover {
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

        .no-orders {
            text-align: center;
            font-size: 1.1rem;
            color: #666;
            margin: 2rem 0;
        }

        .orders-table {
            background-color: var(--white);
            border-radius: 10px;
            box-shadow: var(--shadow);
            overflow-x: auto;
            margin-bottom: 2rem;
        }

        .orders-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .orders-table th,
        .orders-table td {
            padding: 1rem;
            text-align: center;
            border-bottom: 1px solid var(--border);
        }

        .orders-table th {
            background-color: var(--secondary);
            color: var(--white);
            font-weight: 600;
        }

        .orders-table .btn-details {
            background-color: var(--accent);
            border: none;
            padding: 0.5rem 1rem;
            color: var(--white);
            border-radius: 5px;
            font-size: 0.95rem;
        }

        .orders-table .btn-details:hover {
            background-color: #0086b3;
        }

        .order-details {
            display: none;
            background-color: var(--white);
            border-radius: 10px;
            box-shadow: var(--shadow);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .order-details.active {
            display: block;
        }

        .order-details img {
            width: 60px;
            height: 60px;
            object-fit: contain;
            border-radius: 5px;
        }

        .pagination {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 2rem;
        }

        .pagination a {
            padding: 0.5rem 1rem;
            color: var(--secondary);
            text-decoration: none;
            border: 1px solid var(--border);
            border-radius: 5px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .pagination a:hover,
        .pagination a.active {
            background-color: var(--primary);
            color: var(--white);
            border-color: var(--primary);
        }

        @media (max-width: 767.98px) {
            .track-section h1 {
                font-size: 2rem;
            }
            .filters form {
                flex-direction: column;
                align-items: center;
            }
            .filters select {
                max-width: 100%;
            }
            .orders-table th,
            .orders-table td {
                font-size: 0.9rem;
                padding: 0.75rem;
            }
            .order-details img {
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
                            <a href="checkout.php" class="nav-link"><i class="fas fa-credit-card me-1"></i> Checkout</a>
                        </li>
                        <li>
                            <a href="track.php" class="nav-link active"><i class="fas fa-truck me-1"></i> Track Orders</a>
                        </li>
                    </ul>
                    <div class="d-flex gap-2">
                       
                        <a href="logout.php" class="btn btn-outline-primary"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <!-- Track Section -->
    <section class="track-section">
        <div class="container">
            <h1>Track Your Orders</h1>
            <p class="welcome">Welcome, <?php echo htmlspecialchars($user_name); ?>!</p>
            <?php if ($error_message): ?>
                <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <div class="filters">
                <form method="GET" action="track.php">
                    <select name="sort" aria-label="Sort orders">
                        <option value="date_desc" <?php echo $sort === 'date_desc' ? 'selected' : ''; ?>>Date: Newest</option>
                        <option value="date_asc" <?php echo $sort === 'date_asc' ? 'selected' : ''; ?>>Date: Oldest</option>
                        <option value="total_asc" <?php echo $sort === 'total_asc' ? 'selected' : ''; ?>>Total: Low-High</option>
                        <option value="total_desc" <?php echo $sort === 'total_desc' ? 'selected' : ''; ?>>Total: High-Low</option>
                    </select>
                    <select name="status" aria-label="Filter by status">
                        <option value="" <?php echo !$status_filter ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="Pending" <?php echo $status_filter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Processing" <?php echo $status_filter === 'Processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="Shipped" <?php echo $status_filter === 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                        <option value="Delivered" <?php echo $status_filter === 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                    </select>
                    <button type="submit" class="btn btn-primary" aria-label="Apply filters"><i class="fas fa-filter me-1"></i> Filter</button>
                </form>
            </div>
            <?php if (empty($orders)): ?>
                <p class="no-orders">No orders found. <a href="shop.php" class="text-decoration-underline">Continue shopping</a>.</p>
            <?php else: ?>
                <div class="orders-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                                    <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($order['created_at']))); ?></td>
                                    <td>KSH <?php echo number_format($order['total'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($order['status']); ?></td>
                                    <td>
                                        <button class="btn btn-details" onclick="toggleDetails('<?php echo $order['order_id']; ?>')" aria-label="View order details">
                                            <i class="fas fa-eye me-1"></i> View Details
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php foreach ($orders as $order): ?>
                    <div class="order-details" id="details-<?php echo $order['order_id']; ?>">
                        <h2>Order Details: <?php echo htmlspecialchars($order['order_id']); ?></h2>
                        <p><strong>Customer:</strong> <?php echo htmlspecialchars($user_name); ?></p>
                        <p><strong>Total:</strong> KSH <?php echo number_format($order['total'], 2); ?></p>
                        <p><strong>Date:</strong> <?php echo htmlspecialchars($order['created_at']); ?></p>
                        <p><strong>Status:</strong> <?php echo htmlspecialchars($order['status']); ?></p>
                        <table>
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items[$order['order_id']] as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                                <span><?php echo htmlspecialchars($item['name']); ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td>KSH <?php echo number_format($item['price'], 2); ?></td>
                                        <td>KSH <?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="track.php?page=<?php echo $i; ?>&sort=<?php echo $sort; ?>&status=<?php echo urlencode($status_filter); ?>" <?php echo $i === $page ? 'class="active"' : ''; ?>>
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
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

        // Toggle order details
        function toggleDetails(orderId) {
            const details = document.getElementById(`details-${orderId}`);
            const isActive = details.classList.contains('active');
            document.querySelectorAll('.order-details.active').forEach(el => el.classList.remove('active'));
            if (!isActive) {
                details.classList.add('active');
            }
        }
    </script>
</body>
</html>
<?php $pdo = null; ?>