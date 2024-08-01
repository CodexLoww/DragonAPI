<?php
include('dbcon.php');

$config = include('config.php'); // Ensure this line correctly includes config.php

function create_payment($config, $conn) {
    $txnid = substr(md5(uniqid(rand(), true)), 0, 40);
    $url = $config['base_url'] . $txnid . '/post';
    $data = [
        'Amount' => '6666.00',
        'Currency' => 'PHP',
        'Description' => 'Test Over the Counter 2',
        'Email' => 'johnlouiecampos18@gmail.com',
        'Mode' => 2, // selected payment channel
        'ProcId' => '', 
        'Param1' => 'Test parameter 1',
        'Param2' => 'Test parameter 2',
    ];
    
    $json_payload = json_encode($data);
    $auth = base64_encode($config['test_merchant_id'] . ':' . $config['test_api_key']);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Basic " . $auth
    ]);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true); // Include the headers in the output
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For testing only, don't disable SSL verification in production

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);

    if ($response === false) {
        curl_close($ch);
        die("CURL Error: " . $curl_error);
    }

    // Split the response into headers and body
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $header_size);
    $body = substr($response, $header_size);

    curl_close($ch);

    if ($httpcode === 401) {
        die("Error: Unauthorized. Please check your API credentials.");
    }

    if ($httpcode === 400) {
        die("Error: " . $body);
    }

    // Decode JSON response
    $response_data = json_decode($body, true);
    if (isset($response_data['Url'])) {
        // Insert the transaction into the database
        $stmt = $conn->prepare("INSERT INTO transactions (txnid, refno, status, amount, ccy, procid) VALUES (?, ?, 'P', ?, ?, ?)");
        $stmt->bind_param("sssss", $txnid, $response_data['RefNo'], $data['Amount'], $data['Currency'], $data['ProcId']);
        $stmt->execute();
        $stmt->close();

        header('Location: ' . $response_data['Url']);
    } else {
        die("Error: " . $body);
    }
}

create_payment($config, $conn);
?>
