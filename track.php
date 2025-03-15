<?php
session_start();

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}

$conn = new PDO("mysql:host=localhost;dbname=ecomerce;charset=utf8", "root", "");
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$page = max(1, (int)($_GET['page'] ?? 1));
$orders_per_page = 5;
$offset = ($page - 1) * $orders_per_page;
$sort = $_GET['sort'] ?? 'date_desc';
$status_filter = $_GET['status'] ?? '';

$query = "SELECT o.order_id, o.total, o.status, o.created_at, u.name 
          FROM orders o 
          JOIN users u ON o.username = u.username 
          WHERE o.username = :username";
if ($status_filter) $query .= " AND o.status = :status";
$query .= match($sort) {
    'date_asc' => " ORDER BY o.created_at ASC",
    'total_asc' => " ORDER BY o.total ASC",
    'total_desc' => " ORDER BY o.total DESC",
    default => " ORDER BY o.created_at DESC"
} . " LIMIT :offset, :orders_per_page";

$stmt = $conn->prepare($query);
$stmt->bindValue(':username', $_SESSION['user']['username'], PDO::PARAM_STR);
if ($status_filter) $stmt->bindValue(':status', $status_filter, PDO::PARAM_STR);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':orders_per_page', $orders_per_page, PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$count_query = "SELECT COUNT(*) FROM orders WHERE username = :username" . ($status_filter ? " AND status = :status" : "");
$count_stmt = $conn->prepare($count_query);
$count_stmt->bindValue(':username', $_SESSION['user']['username'], PDO::PARAM_STR);
if ($status_filter) $count_stmt->bindValue(':status', $status_filter, PDO::PARAM_STR);
$count_stmt->execute();
$total_pages = ceil($count_stmt->fetchColumn() / $orders_per_page);

$order_items = [];
foreach ($orders as $order) {
    $stmt = $conn->prepare("SELECT oi.quantity, oi.price, p.name 
                            FROM order_items oi 
                            JOIN products p ON oi.product_id = p.id 
                            WHERE oi.order_id = :order_id");
    $stmt->execute([':order_id' => $order['order_id']]);
    $order_items[$order['order_id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Orders - UrbanPulse</title>
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

        .track-container {
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

        .filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .filters select {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 200px;
            max-width: 100%;
        }

        .filters button {
            padding: 0.5rem 1rem;
            background: var(--accent);
            color: var(--white);
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .filters button:hover { background: #e65b50; }

        .order {
            margin-bottom: 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .order-header {
            padding: 1rem;
            background: #f9f9f9;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            transition: background 0.2s;
        }

        .order-header:hover { background: #f0f0f0; }

        .order-details {
            padding: 1rem;
            display: none;
        }

        .order-details p {
            margin: 0.5rem 0;
            font-size: 1rem;
        }

        .order-details strong { color: var(--secondary); }

        .order-items table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .order-items th, .order-items td {
            padding: 0.75rem;
            border-bottom: 1px solid #eee;
            text-align: left;
        }

        .order-items th {
            background: var(--secondary);
            color: var(--white);
        }

        .no-orders {
            text-align: center;
            font-size: 1.1rem;
            color: #666;
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
            color: var(--primary);
            text-decoration: none;
            border: 1px solid #ddd;
            border-radius: 4px;
            transition: all 0.2s;
        }

        .pagination a:hover, .pagination a.active {
            background: var(--accent);
            color: var(--white);
            border-color: var(--accent);
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
            .track-container { padding: 1rem; }
            .filters { flex-direction: column; align-items: center; }
            .filters select { width: 100%; }
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
            <li><a href="track.php" class="active"><i class="fas fa-truck"></i> Track Orders</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>

    <main id="mainContent">
        <section class="track-container">
            <h2>Your Orders</h2>
            <div class="filters">
                <form method="GET" action="track.php">
                    <select name="sort">
                        <option value="date_desc" <?php echo $sort === 'date_desc' ? 'selected' : ''; ?>>Date: Newest</option>
                        <option value="date_asc" <?php echo $sort === 'date_asc' ? 'selected' : ''; ?>>Date: Oldest</option>
                        <option value="total_asc" <?php echo $sort === 'total_asc' ? 'selected' : ''; ?>>Total: Low-High</option>
                        <option value="total_desc" <?php echo $sort === 'total_desc' ? 'selected' : ''; ?>>Total: High-Low</option>
                    </select>
                    <select name="status">
                        <option value="" <?php echo !$status_filter ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="Pending" <?php echo $status_filter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Processing" <?php echo $status_filter === 'Processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="Shipped" <?php echo $status_filter === 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                        <option value="Delivered" <?php echo $status_filter === 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                    </select>
                    <button type="submit">Filter</button>
                </form>
            </div>
            <?php if (empty($orders)): ?>
                <p class="no-orders">No orders found.</p>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <div class="order">
                        <div class="order-header" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'block' ? 'none' : 'block'">
                            <span><strong>Order ID:</strong> <?php echo htmlspecialchars($order['order_id']); ?></span>
                            <span><strong>Status:</strong> <?php echo htmlspecialchars($order['status']); ?></span>
                        </div>
                        <div class="order-details">
                            <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['name']); ?></p>
                            <p><strong>Total:</strong> KSH <?php echo number_format($order['total'], 2); ?></p>
                            <p><strong>Date:</strong> <?php echo htmlspecialchars($order['created_at']); ?></p>
                            <div class="order-items">
                                <table>
                                    <tr>
                                        <th>Product</th>
                                        <th>Qty</th>
                                        <th>Price</th>
                                        <th>Total</th>
                                    </tr>
                                    <?php foreach ($order_items[$order['order_id']] as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td>KSH <?php echo number_format($item['price'], 2); ?></td>
                                            <td>KSH <?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="track.php?page=<?php echo $i; ?>&sort=<?php echo $sort; ?>&status=<?php echo $status_filter; ?>" <?php echo $i === $page ? 'class="active"' : ''; ?>>
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
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