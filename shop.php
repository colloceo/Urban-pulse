<?php
session_start();

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}

$host = "localhost";
$dbname = "ecomerce";
$username_db = "root";
$error_message = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username_db, "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (isset($_POST["add_to_cart"])) {
        $product_id = (int)$_POST["product_id"];
        $product_quantity = (int)$_POST["product_quantity"];

        if ($product_quantity >= 1) {
            $stmt = $conn->prepare("SELECT quantity FROM products WHERE id = :id");
            $stmt->execute([':id' => $product_id]);
            $available_quantity = $stmt->fetchColumn();

            if ($product_quantity <= $available_quantity) {
                $_SESSION["cart"][$product_id] = ($_SESSION["cart"][$product_id] ?? 0) + $product_quantity;
                $stmt = $conn->prepare("UPDATE products SET quantity = quantity - :quantity WHERE id = :id");
                $stmt->execute([':quantity' => $product_quantity, ':id' => $product_id]);
                header("Location: cart.php");
                exit();
            } else {
                $error_message = "Insufficient stock.";
            }
        } else {
            $error_message = "Invalid quantity.";
        }
    }

    $categories = $conn->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
    $search = trim($_GET['search'] ?? '');
    $sort = $_GET['sort'] ?? 'name_asc';
    $category_filter = isset($_GET['category']) ? (int)$_GET['category'] : null;
    $page = max(1, (int)($_GET['page'] ?? 1));
    $products_per_page = 6;
    $offset = ($page - 1) * $products_per_page;

    $query = "SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.name LIKE :search";
    if ($category_filter) $query .= " AND p.category_id = :category_id";
    $query .= match($sort) {
        'price_asc' => " ORDER BY p.price ASC",
        'price_desc' => " ORDER BY p.price DESC",
        'name_desc' => " ORDER BY p.name DESC",
        default => " ORDER BY p.name ASC"
    } . " LIMIT :offset, :products_per_page";

    $stmt = $conn->prepare($query);
    $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
    if ($category_filter) $stmt->bindValue(':category_id', $category_filter, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':products_per_page', $products_per_page, PDO::PARAM_INT);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $count_query = "SELECT COUNT(*) FROM products WHERE name LIKE :search" . ($category_filter ? " AND category_id = :category_id" : "");
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
    if ($category_filter) $count_stmt->bindValue(':category_id', $category_filter, PDO::PARAM_INT);
    $count_stmt->execute();
    $total_pages = ceil($count_stmt->fetchColumn() / $products_per_page);

} catch (PDOException $e) {
    $error_message = "Database error occurred.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UrbanPulse - Shop</title>
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

        .filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .filters input, .filters select {
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

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .product {
            background: var(--white);
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1rem;
            text-align: center;
            transition: transform 0.2s;
        }

        .product:hover { transform: translateY(-4px); }

        .product img {
            width: 100%;
            height: 200px;
            object-fit: contain;
            margin-bottom: 1rem;
        }

        .product h3 {
            font-size: 1.25rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .product p {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.5rem;
        }

        .product .price {
            color: var(--accent);
            font-weight: 700;
            font-size: 1.1rem;
        }

        .product .out-of-stock {
            color: var(--error);
            font-weight: 700;
        }

        .product form {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            margin-top: 1rem;
        }

        .product input[type="number"] {
            width: 60px;
            padding: 0.25rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .product button {
            padding: 0.5rem 1rem;
            background: var(--accent);
            color: var(--white);
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .product button:hover { background: #e65b50; }

        .error {
            background: #ffe6e6;
            color: var(--error);
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 2rem;
            text-align: center;
        }

        .pagination {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            flex-wrap: wrap;
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
            .filters { flex-direction: column; align-items: center; }
            .filters input, .filters select { width: 100%; }
            .product img { height: 150px; }
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
            <li><a href="shop.php" class="active"><i class="fas fa-shopping-bag"></i> Products</a></li>
            <li><a href="cart.php"><i class="fas fa-shopping-cart"></i> Cart</a></li>
            <li><a href="track.php"><i class="fas fa-truck"></i> Track Orders</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>

    <main id="mainContent">
        <div class="filters">
            <form method="GET" action="shop.php">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search products...">
                <select name="sort">
                    <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Name: A-Z</option>
                    <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Name: Z-A</option>
                    <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price: Low-High</option>
                    <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price: High-Low</option>
                </select>
                <select name="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo $category_filter === $category['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Filter</button>
            </form>
        </div>

        <?php if ($error_message): ?>
            <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <div class="product-grid">
            <?php foreach ($products as $product): ?>
                <div class="product">
                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                    <p><?php echo htmlspecialchars($product['description']); ?></p>
                    <p><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></p>
                    <?php if ($product['quantity'] > 0): ?>
                        <p class="price">KSH <?php echo number_format($product['price'], 2); ?></p>
                        <form method="post" action="shop.php">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <input type="number" name="product_quantity" value="1" min="1" max="<?php echo $product['quantity']; ?>">
                            <button type="submit" name="add_to_cart">Add to Cart</button>
                        </form>
                    <?php else: ?>
                        <p class="out-of-stock">Out of Stock</p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="shop.php?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>&category=<?php echo $category_filter ?? ''; ?>" 
                   <?php echo $i === $page ? 'class="active"' : ''; ?>>
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
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