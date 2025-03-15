<?php
$response = file_get_contents('php://input');
$response_data = json_decode($response, true);

if ($response_data['Body']['stkCallback']['ResultCode'] == 0) {
    // Payment was successful
    file_put_contents('success.log', print_r($response_data, true));
} else {
    // Payment failed
    file_put_contents('error.log', print_r($response_data, true));
}
?>