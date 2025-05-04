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
$products = [];
$total_pages = 1;

try {
    // Fetch user's name
    $stmt = $pdo->prepare("SELECT name FROM users WHERE username = ?");
    $stmt->execute([$_SESSION["user"]]);
    $user_name = $stmt->fetchColumn() ?: $_SESSION["user"];

    // Handle add to cart
    if (isset($_POST["add_to_cart"])) {
        $product_id = (int)$_POST["product_id"];
        $product_quantity = (int)$_POST["product_quantity"];

        if ($product_quantity >= 1) {
            $stmt = $pdo->prepare("SELECT quantity FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $available_quantity = $stmt->fetchColumn();

            if ($product_quantity <= $available_quantity) {
                $_SESSION["cart"][$product_id] = ($_SESSION["cart"][$product_id] ?? 0) + $product_quantity;
                $stmt = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
                $stmt->execute([$product_quantity, $product_id]);
                header("Location: cart.php");
                exit();
            } else {
                $error_message = "Insufficient stock.";
            }
        } else {
            $error_message = "Invalid quantity.";
        }
    }

    // Fetch categories
    $categories = $pdo->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);

    // Handle filters and pagination
    $search = trim($_GET['search'] ?? '');
    $sort = $_GET['sort'] ?? 'name_asc';
    $category_filter = isset($_GET['category']) ? (int)$_GET['category'] : null;
    $page = max(1, (int)($_GET['page'] ?? 1));
    $products_per_page = 6;
    $offset = ($page - 1) * $products_per_page;

    // Product query
    $query = "SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.name LIKE ?";
    if ($category_filter) $query .= " AND p.category_id = ?";
    $query .= match($sort) {
        'price_asc' => " ORDER BY p.price ASC",
        'price_desc' => " ORDER BY p.price DESC",
        'name_desc' => " ORDER BY p.name DESC",
        default => " ORDER BY p.name ASC"
    } . " LIMIT ?, ?";

    $stmt = $pdo->prepare($query);
    $stmt->bindValue(1, "%$search%", PDO::PARAM_STR);
    $param_index = 2;
    if ($category_filter) {
        $stmt->bindValue($param_index++, $category_filter, PDO::PARAM_INT);
    }
    $stmt->bindValue($param_index++, $offset, PDO::PARAM_INT);
    $stmt->bindValue($param_index, $products_per_page, PDO::PARAM_INT);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count query for pagination
    $count_query = "SELECT COUNT(*) FROM products WHERE name LIKE ?" . ($category_filter ? " AND category_id = ?" : "");
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->bindValue(1, "%$search%", PDO::PARAM_STR);
    if ($category_filter) {
        $count_stmt->bindValue(2, $category_filter, PDO::PARAM_INT);
    }
    $count_stmt->execute();
    $total_pages = ceil($count_stmt->fetchColumn() / $products_per_page);

} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Shop the latest trends in fashion and accessories at UrbanPulse.">
    <title>Shop - UrbanPulse</title>
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

        /* Shop Section */
        .shop-section {
            padding: 3rem 0;
        }

        .shop-section h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-align: center;
            color: var(--secondary);
        }

        .shop-section .welcome {
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

        .filters .form-control,
        .filters .form-select {
            max-width: 200px;
            border-radius: 5px;
            border: 1px solid var(--border);
            padding: 0.75rem;
            font-size: 0.95rem;
        }

        .filters .form-control:focus,
        .filters .form-select:focus {
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

        .no-products {
            text-align: center;
            font-size: 1.1rem;
            color: #666;
            margin: 2rem 0;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .product {
            background-color: var(--white);
            border-radius: 10px;
            box-shadow: var(--shadow);
            padding: 1.5rem;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .product:hover {
            transform: translateY(-5px);
        }

        .product img {
            width: 100%;
            height: 200px;
            object-fit: contain;
            margin-bottom: 1rem;
            border-radius: 5px;
        }

        .product h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--secondary);
            margin-bottom: 0.5rem;
        }

        .product p {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.5rem;
        }

        .product .price {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .product .out-of-stock {
            font-size: 1rem;
            font-weight: 700;
            color: var(--error);
            margin-bottom: 1rem;
        }

        .product form {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
            align-items: center;
        }

        .product input[type="number"] {
            width: 70px;
            padding: 0.5rem;
            border: 1px solid var(--border);
            border-radius: 5px;
            font-size: 0.95rem;
        }

        .product button {
            background-color: var(--primary);
            border: none;
            padding: 0.5rem 1rem;
            color: var(--white);
            border-radius: 5px;
            font-size: 0.95rem;
            transition: background 0.3s ease;
        }

        .product button:hover {
            background-color: #e55a00;
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
            .shop-section h1 {
                font-size: 2rem;
            }
            .filters form {
                flex-direction: column;
                align-items: center;
            }
            .filters .form-control,
            .filters .form-select {
                max-width: 100%;
            }
            .product img {
                height: 150px;
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
                            <a class="nav-link" href="index.php"><i class="fas fa-home me-1"></i> Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="shop.php"><i class="fas fa-shopping-bag me-1"></i> Shop</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="about.php"><i class="fas fa-info-circle me-1"></i> About</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="contact.php"><i class="fas fa-envelope me-1"></i> Contact</a>
                        </li>
                    </ul>
                    <div class="d-flex gap-2">
                        <a href="cart.php" class="btn btn-outline-primary"><i class="fas fa-shopping-cart me-1"></i> Cart</a>
                        <a href="logout.php" class="btn btn-outline-primary"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <!-- Shop Section -->
    <section class="shop-section">
        <div class="container">
            <h1>Shop Our Collection</h1>
            <p class="welcome">Welcome, <?php echo htmlspecialchars($user_name); ?>!</p>
            <div class="filters">
                <form method="GET" action="shop.php">
                    <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search products..." aria-label="Search products">
                    <select name="sort" class="form-select" aria-label="Sort products">
                        <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Name: A-Z</option>
                        <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Name: Z-A</option>
                        <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price: Low-High</option>
                        <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price: High-Low</option>
                    </select>
                    <select name="category" class="form-select" aria-label="Filter by category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo $category_filter === $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary" aria-label="Apply filters"><i class="fas fa-filter me-1"></i> Filter</button>
                </form>
            </div>
            <?php if ($error_message): ?>
                <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <?php if (empty($products)): ?>
                <p class="no-products">No products found.</p>
            <?php else: ?>
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
                                    <input type="number" name="product_quantity" value="1" min="1" max="<?php echo $product['quantity']; ?>" aria-label="Quantity">
                                    <button type="submit" name="add_to_cart" aria-label="Add to cart">
                                        <i class="fas fa-cart-plus me-1"></i> Add to Cart
                                    </button>
                                </form>
                            <?php else: ?>
                                <p class="out-of-stock">Out of Stock</p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="shop.php?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>&category=<?php echo $category_filter ?? ''; ?>" 
                       <?php echo $i === $page ? 'class="active"' : ''; ?>>
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
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