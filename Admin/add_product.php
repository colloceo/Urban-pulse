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

// Fetch all categories for the dropdown
$categories = $conn->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);

// Handle adding a new product
$success_message = $error_message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product']) && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);

    // Image handling
    $imagePath = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($ext, $allowed) && $_FILES['image']['size'] <= 2000000) { // 2MB limit
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $imagePath = $uploadDir . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $imagePath);
        } else {
            $error_message = "Invalid image format or size (max 2MB, jpg/png/gif only).";
        }
    } elseif (!empty($_POST['image_url'])) {
        $imagePath = filter_var($_POST['image_url'], FILTER_SANITIZE_URL);
        if (!filter_var($imagePath, FILTER_VALIDATE_URL)) {
            $error_message = "Invalid image URL.";
        }
    }

    if (!$error_message && $name && $description && $price !== false && $quantity !== false && $category_id !== false) {
        $stmt = $conn->prepare("INSERT INTO products (name, description, price, quantity, image, category_id) VALUES (:name, :description, :price, :quantity, :image, :category_id)");
        $params = [
            ':name' => $name,
            ':description' => $description,
            ':price' => $price,
            ':quantity' => $quantity,
            ':image' => $imagePath,
            ':category_id' => $category_id
        ];

        if ($stmt->execute($params)) {
            $success_message = "Product added successfully. <a href='admin.php'>Back to Dashboard</a>";
        } else {
            $error_message = "Error adding product.";
        }
    } elseif (!$error_message) {
        $error_message = "Please fill all required fields with valid data.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Add a new product to UrbanPulse's inventory.">
    <title>Add Product - UrbanPulse Admin</title>
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
        }
        h1 { font-size: 2rem; }
        .form-container {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            max-width: 600px;
            margin: 20px auto;
        }
        h2 {
            font-size: 1.75rem;
            color: #1a2634;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        form { display: flex; flex-direction: column; gap: 15px; }
        label { font-weight: 700; }
        input[type="text"],
        input[type="number"],
        textarea,
        input[type="file"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        textarea { resize: vertical; min-height: 100px; }
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
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            position: relative;
            text-align: center;
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
            h1, h2 { font-size: 1.5rem; }
        }
        @media (max-width: 480px) {
            body { flex-direction: column; }
            .sidebar { width: 100%; position: static; }
            .content { margin-left: 0; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>UrbanPulse Admin</h2>
        <ul>
            <li><a href="admin.php">Dashboard</a></li>
            <li><a href="add_product.php" class="active">Add Product</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </div>
    <div class="content">
        <header>
            <h1>Add New Product</h1>
        </header>

        <?php if ($success_message): ?>
            <div class="alert success"><?php echo $success_message; ?><span class="close" onclick="this.parentElement.style.display='none'">×</span></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert error"><?php echo htmlspecialchars($error_message); ?><span class="close" onclick="this.parentElement.style.display='none'">×</span></div>
        <?php endif; ?>

        <div class="form-container">
            <h2>Product Details</h2>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <label for="name">Product Name:</label>
                <input type="text" id="name" name="name" required aria-label="Product Name">

                <label for="description">Description:</label>
                <textarea id="description" name="description" required aria-label="Description"></textarea>

                <label for="price">Price:</label>
                <input type="number" id="price" name="price" step="0.01" min="0" required aria-label="Price">

                <label for="quantity">Quantity:</label>
                <input type="number" id="quantity" name="quantity" min="0" required aria-label="Quantity">

                <label for="category_id">Category:</label>
                <select id="category_id" name="category_id" required aria-label="Category">
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category['id']); ?>">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="image">Image Upload:</label>
                <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/gif" aria-label="Image Upload">

                <label for="image_url">Or Image URL:</label>
                <input type="text" id="image_url" name="image_url" placeholder="Enter image URL" aria-label="Image URL">

                <input type="submit" name="add_product" value="Add Product" aria-label="Add Product">
            </form>
        </div>
    </div>
</body>
</html>
<?php $conn = null; ?>