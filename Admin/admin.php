<?php
session_start();

// Redirect to login if not admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

// Database connection (using PDO)
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

// Generate CSRF token if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle adding/updating a category
$success_message = $error_message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_category']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $cat_name = filter_input(INPUT_POST, 'category_name', FILTER_SANITIZE_STRING);
    $cat_desc = filter_input(INPUT_POST, 'category_desc', FILTER_SANITIZE_STRING);
    $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (:name, :description)");
    if ($stmt->execute([':name' => $cat_name, ':description' => $cat_desc])) {
        $success_message = "Category added successfully.";
    } else {
        $error_message = "Error adding category.";
    }
}

// Handle adding/updating a product
if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['add_product']) || isset($_POST['update_product'])) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : null;

    // Image handling
    $imagePath = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($ext, $allowed) && $_FILES['image']['size'] <= 2000000) {
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $imagePath = $uploadDir . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $imagePath);
        } else {
            $error_message = "Invalid image format or size (max 2MB, jpg/png/gif only).";
        }
    } elseif (!empty($_POST['image_url'])) {
        $imagePath = filter_var($_POST['image_url'], FILTER_SANITIZE_URL);
    }

    if (!$error_message) {
        if ($product_id) {
            $sql = $imagePath 
                ? "UPDATE products SET name=:name, description=:description, price=:price, quantity=:quantity, image=:image, category_id=:category_id WHERE id=:id"
                : "UPDATE products SET name=:name, description=:description, price=:price, quantity=:quantity, category_id=:category_id WHERE id=:id";
            $stmt = $conn->prepare($sql);
            $params = $imagePath 
                ? [':name' => $name, ':description' => $description, ':price' => $price, ':quantity' => $quantity, ':image' => $imagePath, ':category_id' => $category_id, ':id' => $product_id]
                : [':name' => $name, ':description' => $description, ':price' => $price, ':quantity' => $quantity, ':category_id' => $category_id, ':id' => $product_id];
        } else {
            $stmt = $conn->prepare("INSERT INTO products (name, description, price, quantity, image, category_id) VALUES (:name, :description, :price, :quantity, :image, :category_id)");
            $params = [':name' => $name, ':description' => $description, ':price' => $price, ':quantity' => $quantity, ':image' => $imagePath, ':category_id' => $category_id];
        }

        if ($stmt->execute($params)) {
            $success_message = $product_id ? "Product updated successfully." : "Product added successfully.";
        } else {
            $error_message = "Error saving product.";
        }
    }
}

// Handle deleting a product
if (isset($_GET['delete']) && isset($_GET['csrf']) && $_GET['csrf'] === $_SESSION['csrf_token']) {
    $productId = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM products WHERE id = :id");
    if ($stmt->execute([':id' => $productId])) {
        $success_message = "Product deleted successfully.";
        header("Location: admin.php");
        exit();
    } else {
        $error_message = "Error deleting product.";
    }
}

// Handle updating order status
if (isset($_GET['update_status']) && $_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $order_id = filter_input(INPUT_GET, 'update_status', FILTER_SANITIZE_STRING);
    $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
    $stmt = $conn->prepare("UPDATE orders SET status = :status WHERE order_id = :order_id");
    if ($stmt->execute([':status' => $status, ':order_id' => $order_id])) {
        $success_message = "Order status updated successfully.";
    } else {
        $error_message = "Error updating order status.";
    }
}

// Fetch product for editing
$edit_product = null;
if (isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = :id");
    $stmt->execute([':id' => (int)$_GET['edit']]);
    $edit_product = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch all products with category names
$stmt = $conn->prepare("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all categories
$categories = $conn->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all orders
$stmt = $conn->prepare("SELECT o.*, u.name AS user_name FROM orders o JOIN users u ON o.username = u.username");
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UrbanPulse Admin Dashboard</title>
    <link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
    <link rel="manifest" href="site.webmanifest">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f4f4f9;
            color: #333;
            line-height: 1.6;
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 250px;
            background: #1a2634;
            color: #fff;
            padding: 20px;
            position: fixed;
            height: 100%;
            overflow-y: auto;
        }
        .sidebar h2 { font-size: 1.5rem; margin-bottom: 20px; }
        .sidebar ul { list-style: none; }
        .sidebar ul li { margin: 15px 0; }
        .sidebar ul li a {
            color: #fff;
            text-decoration: none;
            padding: 10px;
            display: block;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .sidebar ul li a:hover, .sidebar ul li a.active { background: #ff6f61; }
        .content {
            margin-left: 250px;
            padding: 20px;
            flex: 1;
        }
        header {
            background: linear-gradient(135deg, #1a2634, #2e3b4e);
            color: #fff;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        h1 { font-size: 2rem; }
        .tab-container {
            margin-bottom: 20px;
        }
        .tab-buttons {
            display: flex;
            border-bottom: 2px solid #ddd;
        }
        .tab-button {
            padding: 10px 20px;
            background: #fff;
            border: 1px solid #ddd;
            border-bottom: none;
            cursor: pointer;
            transition: background 0.3s;
        }
        .tab-button.active {
            background: #ff6f61;
            color: #fff;
            border-color: #ff6f61;
        }
        .tab-button:hover:not(.active) {
            background: #f0f0f0;
        }
        .tab-content {
            display: none;
            background: #fff;
            padding: 20px;
            border-radius: 0 10px 10px 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .tab-content.active { display: block; }
        form { display: flex; flex-direction: column; gap: 15px; }
        label { font-weight: 700; }
        input[type="text"], input[type="number"], textarea, input[type="file"], select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        input[type="submit"] {
            padding: 10px;
            background: #ff6f61;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }
        input[type="submit"]:hover { background: #e65b50; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th { background: #2e3b4e; color: #fff; }
        tr:hover { background: #f9f9f9; }
        img { max-width: 80px; height: auto; border-radius: 5px; }
        .actions a {
            color: #ff6f61;
            text-decoration: none;
            margin-right: 10px;
        }
        .actions a:hover { text-decoration: underline; }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            position: relative;
        }
        .alert.success { background: #e6ffe6; color: #2e7d32; }
        .alert.error { background: #ffe6e6; color: #e63946; }
        .alert .close {
            position: absolute;
            right: 10px;
            top: 10px;
            cursor: pointer;
            font-size: 1.2rem;
        }
        @media (max-width: 768px) {
            .sidebar { width: 200px; }
            .content { margin-left: 200px; }
            .tab-buttons { flex-wrap: wrap; }
        }
        @media (max-width: 480px) {
            body { flex-direction: column; }
            .sidebar { width: 100%; position: static; }
            .content { margin-left: 0; }
            table { font-size: 0.9rem; }
            .tab-button { width: 100%; }
        }
    </style>
    <script>
        function openTab(tabName) {
            const tabs = document.querySelectorAll('.tab-content');
            const buttons = document.querySelectorAll('.tab-button');
            tabs.forEach(tab => tab.classList.remove('active'));
            buttons.forEach(btn => btn.classList.remove('active'));
            document.getElementById(tabName).classList.add('active');
            document.querySelector(`.tab-button[onclick="openTab('${tabName}')"]`).classList.add('active');
        }
        window.onload = () => openTab('products'); // Default tab
    </script>
</head>
<body>
    <div class="sidebar">
        <h2>UrbanPulse Admin</h2>
        <ul>
            <li><a href="admin.php" class="active">Dashboard</a></li>
            <li><a href="add_product.php">Add Product</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </div>
    <div class="content">
        <header>
            <h1>Admin Dashboard</h1>
        </header>

        <?php if ($success_message): ?>
            <div class="alert success"><?php echo htmlspecialchars($success_message); ?><span class="close" onclick="this.parentElement.style.display='none'">×</span></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert error"><?php echo htmlspecialchars($error_message); ?><span class="close" onclick="this.parentElement.style.display='none'">×</span></div>
        <?php endif; ?>

        <div class="tab-container">
            <div class="tab-buttons">
                <button class="tab-button" onclick="openTab('products')">Products</button>
                <button class="tab-button" onclick="openTab('categories')">Categories</button>
                <button class="tab-button" onclick="openTab('orders')">Orders</button>
            </div>

            <!-- Products Tab -->
            <div id="products" class="tab-content">
                <?php if (isset($_GET['add']) || $edit_product): ?>
                    <div class="form-container">
                        <h2><?php echo $edit_product ? 'Edit Product' : 'Add New Product'; ?></h2>
                        <form method="post" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <?php if ($edit_product): ?>
                                <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($edit_product['id']); ?>">
                            <?php endif; ?>
                            <label for="name">Product Name:</label>
                            <input type="text" id="name" name="name" value="<?php echo $edit_product ? htmlspecialchars($edit_product['name']) : ''; ?>" required>
                            <label for="description">Description:</label>
                            <textarea id="description" name="description" rows="4" required><?php echo $edit_product ? htmlspecialchars($edit_product['description']) : ''; ?></textarea>
                            <label for="price">Price:</label>
                            <input type="number" id="price" name="price" step="0.01" value="<?php echo $edit_product ? htmlspecialchars($edit_product['price']) : ''; ?>" required>
                            <label for="quantity">Quantity:</label>
                            <input type="number" id="quantity" name="quantity" value="<?php echo $edit_product ? htmlspecialchars($edit_product['quantity']) : ''; ?>" required>
                            <label for="category_id">Category:</label>
                            <select id="category_id" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category['id']); ?>" <?php echo $edit_product && $edit_product['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label for="image">Image (Upload or URL):</label>
                            <input type="file" id="image" name="image" accept="image/*">
                            <input type="text" name="image_url" placeholder="Or enter image URL" value="<?php echo ($edit_product && !$edit_product['image']) ? htmlspecialchars($edit_product['image']) : ''; ?>">
                            <input type="submit" name="<?php echo $edit_product ? 'update_product' : 'add_product'; ?>" value="<?php echo $edit_product ? 'Update Product' : 'Add Product'; ?>">
                        </form>
                    </div>
                <?php endif; ?>

                <h2>Products</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Category</th>
                            <th>Image</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['id']); ?></td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo htmlspecialchars($product['description']); ?></td>
                                <td>KSH <?php echo number_format($product['price'], 2); ?></td>
                                <td><?php echo htmlspecialchars($product['quantity']); ?></td>
                                <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                                <td><?php if ($product['image']): ?><img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>"><?php endif; ?></td>
                                <td class="actions">
                                    <a href="admin.php?edit=<?php echo htmlspecialchars($product['id']); ?>">Edit</a>
                                    <a href="admin.php?delete=<?php echo htmlspecialchars($product['id']); ?>&csrf=<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" onclick="return confirm('Are you sure?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Categories Tab -->
            <div id="categories" class="tab-content">
                <div class="form-container">
                    <h2>Add New Category</h2>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <label for="category_name">Category Name:</label>
                        <input type="text" id="category_name" name="category_name" required>
                        <label for="category_desc">Description:</label>
                        <textarea id="category_desc" name="category_desc" rows="4"></textarea>
                        <input type="submit" name="add_category" value="Add Category">
                    </form>
                </div>
                <h2>Categories</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($category['id']); ?></td>
                                <td><?php echo htmlspecialchars($category['name']); ?></td>
                                <td><?php echo htmlspecialchars($category['description'] ?? 'No description'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Orders Tab -->
            <div id="orders" class="tab-content">
                <h2>Orders</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                                <td><?php echo htmlspecialchars($order['user_name']); ?></td>
                                <td>KSH <?php echo number_format($order['total'], 2); ?></td>
                                <td><?php echo htmlspecialchars($order['status']); ?></td>
                                <td><?php echo htmlspecialchars($order['created_at']); ?></td>
                                <td>
                                    <form method="post" action="admin.php?update_status=<?php echo htmlspecialchars($order['order_id']); ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                        <select name="status">
                                            <option value="Pending" <?php echo $order['status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="Processing" <?php echo $order['status'] == 'Processing' ? 'selected' : ''; ?>>Processing</option>
                                            <option value="Shipped" <?php echo $order['status'] == 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                                            <option value="Delivered" <?php echo $order['status'] == 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                                        </select>
                                        <input type="submit" value="Update">
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
<?php $conn = null; ?>