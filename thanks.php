<html>

<head>
    <style>
        body {
            background-color: #f2f2f2;
            font-family: Arial, sans-serif;
        }
        
        h1 {
            color: #008000;
            font-size: 2.5em;
            text-align: center;
            margin-top: 50px;
        }
        
        p {
            color: #333;
            font-size: 1.2em;
            text-align: center;
            margin-top: 20px;
        }
        
        
    </style>
</head>


<?php
   // Start the session
    session_start();

 // Retrieve the customer name from the session variable
    if (isset($_SESSION['user'])) {
        $user = $_SESSION['user'];
        $customerName = $user['name'];
    } else {
        $customerName = "Valued Customer";
    }

 // Display the thank you message
    echo "<h1>Thank You, $customerName!</h1>";
    echo 
"<p>Your order has been received and will be delivered soon.</p>";
    ?>
</html>