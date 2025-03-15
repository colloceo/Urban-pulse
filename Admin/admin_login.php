<?php
session_start();
$host = 'localhost';
$db = 'ecomerce';
$user = 'root'; // Default XAMPP username
$pass = ''; // Default XAMPP password

$conn = new mysqli($host, $user, $pass, $db);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();
        if (password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            header("Location: admin.php");
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "No such user found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <style>
        <style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f9f9f9;
        margin: 0;
        padding: 0;
        color: #333;
    }
    h1 {
        text-align: center;
        color: #232f3e;
        margin-top: 50px;
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
    input[type="password"] {
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
        background-color: #e68a00;
    }
    p.error {
        color: red; 
        text-align: center; 
    }
</style>

    </style>
</head>
<body>
    <h1>Admin Login</h1>
    <form action="" method="post">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>
        
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>

        <input type="submit" value="Login">
        
        <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
    </form>
</body>
</html>
