<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$host = 'localhost';
$db = 'ecomerce'; 
$user = 'root'; 
$pass = ''; 

$conn = new mysqli($host, $user, $pass, $db);

// Check for connection errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch product details for editing
if (isset($_GET['id'])) {
    $productId = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
    } else {
        die("Product not found.");
    }
} else {
    die("No product ID provided.");
}

// Update product details
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_product'])) {
    $productId = $_POST['id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];

    // Handle image upload or URL
    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        // New image uploaded
        $imagePath = 'uploads/' . basename($_FILES["image"]["name"]);
        move_uploaded_file($_FILES["image"]["tmp_name"], $imagePath);
        
        // Update the product with a new image
        $stmt = $conn->prepare("UPDATE products SET name=?, description=?, price=?, quantity=?, image=? WHERE id=?");
        $stmt->bind_param("ssdisi", $name, $description, $price, $quantity, $imagePath, $productId);
        
    } elseif (!empty($_POST['image_url'])) {
        // If an image URL is provided
        $imagePath = $_POST['image_url'];
        
        // Update the product with the new image URL
        $stmt = $conn->prepare("UPDATE products SET name=?, description=?, price=?, quantity=?, image=? WHERE id=?");
        $stmt->bind_param("ssdisi", $name, $description, $price, $quantity, $imagePath, $productId);
        
    } else {
        // No new image uploaded; keep existing image
        if ($stmtCurrentImage = $conn->prepare("SELECT image FROM products WHERE id=?")) {
            $stmtCurrentImage->bind_param("i", $productId);
            if ($stmtCurrentImage->execute()) {
                $currentImageResult = $stmtCurrentImage->get_result();
                if ($currentImageResult->num_rows > 0) {
                    // Get current image path
                    $currentImageData = $currentImageResult->fetch_assoc();
                    // Use existing image path for update
                    if ($currentImageData) {
                        // Update without changing the image
                        $stmt = $conn->prepare("UPDATE products SET name=?, description=?, price=?, quantity=? WHERE id=?");
                        $stmt->bind_param("ssisi", $name, $description, $price, $quantity, $productId);
                    }
                }
            } else {
                echo "Error executing statement: " . $stmtCurrentImage->error;
            }
            // Close the statement for current image fetch
            if (isset($stmtCurrentImage)) {
                $stmtCurrentImage->close();
            }
        } else {
            echo "Error preparing statement: " . $conn->error;
        }
    }

    if (isset($stmt) && $stmt->execute()) {
        echo "Product updated successfully.";
        header("Location: admin.php"); // Redirect after update
        exit();
    } else {
        echo "Error: " . (isset($stmt) ? $stmt->error : "Statement not prepared.");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        header {
            background-color: #232f3e;
            color: white;
            text-align: center;
            padding: 30px 0;
            font-size: 28px;
            font-weight: bold;
        }
        nav {
            background-color: #232f3e;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        nav ul {
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            list-style: none;
        }

        nav ul li {
            margin: 0 20px;
        }

        nav ul li a {
            color: white;
            text-decoration: none;
            font-size: 18px;
            padding: 10px 20px;
            transition: background-color 0.3s ease;
        }

        nav ul li a:hover {
            background-color: #ff9900;
            border-radius: 5px;
        }
        
        form {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            margin: 50px auto;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            color: #666666;
        }
        
        input[type="text"],
        input[type="number"],
        textarea,
        input[type="file"] {
            width: calc(100% - 22px);
            padding: 10px;
            border: 1px solid #cccccc;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        
        input[type="submit"] {
            background-color: #ff9900;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease; 
         }
         
         input[type="submit"]:hover { 
             background-color:#e68a00; 
         } 
     </style>
</head>
<body>
<header>Edit Product</header>

<nav>
    <ul>
      <li><a href="admin.php">Back to Admin Dashboard</a></li>
    </ul>
</nav>

<?php if (isset($product)): ?>
<form action="" method="post" enctype="multipart/form-data">
<input type="hidden" name="id" value="<?php echo htmlspecialchars($product['id']); ?>">
    
<label for="name">Product Name:</label>
<input type="text" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>

<label for="description">Description:</label>
<textarea id="description" name="description" required><?php echo htmlspecialchars($product['description']); ?></textarea>

<label for="price">Price:</label>
<input type="text" id="price" name="price" value="<?php echo htmlspecialchars($product['price']); ?>" required>

<label for="quantity">Quantity:</label>
<input type="number" id="quantity" name="quantity" value="<?php echo htmlspecialchars($product['quantity']); ?>" required>

<label for="image">Image:</label>
<input type="file" id="image" name="image">

<label for="image_url">Or Image URL:</label>
<input type="text" id="image_url" name="image_url" placeholder="Enter image URL">

<input type="submit" name="update_product" value="Update Product">
</form>
<?php else: ?>
<p>No product found to edit.</p>
<?php endif; ?>

</body>
</html>

<?php
$conn->close();
?>
