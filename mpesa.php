<?php
session_start();

// Load environment variables
require_once 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$shortcode = $_ENV['MPESA_SHORTCODE'];
$passkey = $_ENV['MPESA_PASSKEY'];
$consumer_key = $_ENV['MPESA_CONSUMER_KEY'];
$consumer_secret = $_ENV['MPESA_CONSUMER_SECRET'];
$callback_url = $_ENV['MPESA_CALLBACK_URL'];

// Africa's Talking credentials
$at_api_key = $_ENV['AT_API_KEY'];
$at_username = $_ENV['AT_USERNAME'];

// Mailtrap SMTP credentials
$smtp_host = $_ENV['SMTP_HOST'];
$smtp_port = $_ENV['SMTP_PORT'];
$smtp_username = $_ENV['SMTP_USERNAME'];
$smtp_password = $_ENV['SMTP_PASSWORD'];

// Generate access token for M-Pesa
function generateAccessToken($consumer_key, $consumer_secret) {
    $url = "https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials";
    $credentials = base64_encode($consumer_key . ':' . $consumer_secret);
    $headers = ["Authorization: Basic " . $credentials];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        error_log("Access token generation failed: " . $curl_error);
        header("Location: error.php?message=" . urlencode("Failed to generate access token. Please try again."));
        exit();
    }

    $response_data = json_decode($response, true);
    return $response_data['access_token'];
}

// Format phone number function
function formatPhoneNumber($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (substr($phone, 0, 1) === '0') {
        $phone = '254' . substr($phone, 1);
    } elseif (substr($phone, 0, 4) === '+254') {
        $phone = substr($phone, 1);
    }
    if (strlen($phone) !== 12 || substr($phone, 0, 3) !== '254') {
        error_log("Invalid phone number: $phone");
        header("Location: checkout.php?error=" . urlencode("Invalid phone number format. Use 2547xxxxxxxxx (e.g., 254712345678)."));
        exit();
    }
    return $phone;
}

// Function to send email notification (manual SMTP with Mailtrap)
function sendOrderEmail($email, $order_id, $cart_items, $total) {
    global $smtp_host, $smtp_port, $smtp_username, $smtp_password;

    $from = "no-reply@urbanpulse.com";
    $subject = "Order Confirmation - Order #$order_id";
    $message = "Thank you for your order!\n\nOrder ID: $order_id\nItems:\n";
    foreach ($cart_items as $item) {
        $message .= "- {$item['name']} (KSH " . number_format($item['price'], 2) . ") x {$item['quantity']}\n";
    }
    $message .= "\nTotal: KSH " . number_format($total, 2) . "\n\nView your orders at: http://localhost/track.php";

    $smtp = fsockopen($smtp_host, $smtp_port, $errno, $errstr, 30);
    if (!$smtp) {
        error_log("SMTP connection failed: $errstr ($errno)");
        return;
    }

    fputs($smtp, "HELO localhost\r\n");
    fputs($smtp, "AUTH LOGIN\r\n");
    fputs($smtp, base64_encode($smtp_username) . "\r\n");
    fputs($smtp, base64_encode($smtp_password) . "\r\n");
    fputs($smtp, "MAIL FROM: <$from>\r\n");
    fputs($smtp, "RCPT TO: <$email>\r\n");
    fputs($smtp, "DATA\r\n");
    fputs($smtp, "Subject: $subject\r\n");
    fputs($smtp, "From: $from\r\n");
    fputs($smtp, "To: $email\r\n");
    fputs($smtp, "\r\n$message\r\n");
    fputs($smtp, ".\r\n");
    fputs($smtp, "QUIT\r\n");

    fclose($smtp);
    error_log("Email sent successfully to $email");
}

// Function to send SMS notification (Africa's Talking HTTP API)
function sendOrderSMS($phone, $order_id, $total) {
    global $at_api_key, $at_username;

    $url = "https://api.sandbox.africastalking.com/version1/message";
    $message = "Order #$order_id confirmed! Total: KSH " . number_format($total, 2) . ". View your orders at: http://localhost/track.php";
    $data = http_build_query([
        'username' => $at_username,
        'to' => $phone,
        'message' => $message
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "ApiKey: $at_api_key",
        "Content-Type: application/x-www-form-urlencoded",
        "Accept: application/json"
    ]);
    $response = curl_exec($ch);
    if ($response === false) {
        error_log("SMS failed: " . curl_error($ch));
    } else {
        error_log("SMS sent successfully: " . $response);
    }
    curl_close($ch);
}

// Log session data for debugging
error_log("Session ID: " . session_id());
error_log("Session data on mpesa.php load: " . print_r($_SESSION, true));

// Validate session data
$totalAmount = $_SESSION['totalAmount'] ?? null;
$mpesa_number = $_SESSION['mpesa_number'] ?? null;
$email = $_SESSION['email'] ?? null;

if (!isset($totalAmount, $mpesa_number, $_SESSION['order_id'], $_SESSION['cart_items'], $email)) {
    error_log("Missing session data: " . print_r($_SESSION, true));
    header("Location: checkout.php?error=" . urlencode("Required session data missing. Please complete the checkout process."));
    exit();
}

$mpesa_number = formatPhoneNumber($mpesa_number);
$access_token = generateAccessToken($consumer_key, $consumer_secret);

$timestamp = date('YmdHis');
$password_string = $shortcode . $passkey . $timestamp;
$encoded_password = base64_encode($password_string);

$url = "https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest";
$headers = [
    "Content-Type:application/json",
    "Authorization:Bearer " . $access_token,
];

$request_data = [
    'BusinessShortCode' => $shortcode,
    'Password' => $encoded_password,
    'Timestamp' => $timestamp,
    'TransactionType' => 'CustomerPayBillOnline',
    'Amount' => $totalAmount,
    'PartyA' => $mpesa_number,
    'PartyB' => $shortcode,
    'PhoneNumber' => $mpesa_number,
    'CallBackURL' => $callback_url,
    'AccountReference' => 'UrbanPulse',
    'TransactionDesc' => 'Payment for order',
];

$data = json_encode($request_data);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($ch);
$curl_error = curl_error($ch);
curl_close($ch);

if ($response === false) {
    error_log("M-Pesa request failed: " . $curl_error);
    header("Location: error.php?message=" . urlencode("Error occurred while processing payment. Please try again."));
    exit();
}

$response_data = json_decode($response, true);
error_log("M-Pesa Response: " . json_encode($response_data));

if (isset($response_data['errorCode'])) {
    error_log("M-Pesa API Error: " . $response_data['errorMessage']);
    header("Location: error.php?message=" . urlencode($response_data['errorMessage']));
    exit();
}

if (isset($response_data['ResponseCode']) && $response_data['ResponseCode'] == "0") {
    $_SESSION['checkout_request_id'] = $response_data['CheckoutRequestID'];

    // Send notifications
    $order_id = $_SESSION['order_id'];
    $cart_items = $_SESSION['cart_items'];
    $total = $_SESSION['totalAmount'];

    sendOrderEmail($email, $order_id, $cart_items, $total);
    sendOrderSMS('+' . $mpesa_number, $order_id, $total);

    // Clear session data
    unset($_SESSION['cart']);
    unset($_SESSION['totalAmount']);
    unset($_SESSION['order_id']);
    unset($_SESSION['mpesa_number']);
    unset($_SESSION['cart_items']);
    unset($_SESSION['email']);

    // Success page
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="refresh" content="5;url=track.php">
        <title>Payment Success</title>
        <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
        <style>
            body {
                font-family: "Roboto", sans-serif;
                background: #f4f4f9;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
            }
            .success-box {
                background: #fff;
                padding: 2rem;
                border-radius: 12px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.05);
                text-align: center;
                max-width: 500px;
            }
            .success-box i {
                color: #2e7d32;
                font-size: 3rem;
                margin-bottom: 1rem;
            }
            .success-box h2 {
                color: #1a2634;
                margin-bottom: 1rem;
            }
            .success-box p {
                color: #333;
                line-height: 1.6;
            }
            .success-box a {
                color: #ff6f61;
                text-decoration: none;
            }
            .success-box a:hover {
                text-decoration: underline;
                color: #e65b50;
            }
        </style>
    </head>
    <body>
        <div class="success-box">
            <i class="fas fa-check-circle"></i>
            <h2>Payment Initiated Successfully!</h2>
            <p>Please check your phone to complete the M-Pesa STK Push payment.<br>
            You will be redirected to your <a href="track.php">order tracking page</a> in 5 seconds.</p>
        </div>
    </body>
    </html>';
    exit();
} else {
    error_log("M-Pesa Payment Failed: " . $response_data['ResponseDescription']);
    header("Location: error.php?message=" . urlencode($response_data['ResponseDescription']));
    exit();
}
?>