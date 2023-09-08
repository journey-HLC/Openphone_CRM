<?php

// Database configuration

$host = 'localhost';
$username = 'holiggtq_ap1solutions';
$password = '2Kjt3([$dRoo';
$dbName = 'holiggtq_ap1solutions_pwa';

// Create a database connection
$conn = new mysqli($host, $username, $password, $dbName);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$webhookData = file_get_contents('php://input');

if ($webhookData) {
$webhookData = json_decode($webhookData, true);

// Extract relevant data
$from = $webhookData['data']['object']['from'];
$to = $webhookData['data']['object']['to'];
$direction = $webhookData['data']['object']['direction'];
$body = $webhookData['data']['object']['body'];
$conversationid=$webhookData['data']['object']['conversationId'];
// SQL query to insert data
$sql = "INSERT INTO messages (from_number, to_number, direction, body,conversation_id) VALUES ('$from', '$to', '$direction', '$body','$conversationid')";

if ($conn->query($sql) === TRUE) {
    echo "Data saved successfully.";
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

// Close the database connection
$conn->close();
}
?>