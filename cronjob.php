<?php

require_once 'ZohoCRMSmsLogger.php'; // Include the class file


$clientID = '1000.64U9BS7NIDROEDTPG08JIN7JD91T0B';
$clientSecret = '5064a1347f24cd8644f41ddcc67821b23f36d46ea9';
$refreshToken = '1000.2fad8515bafac315bf03a60f04f77738.d1b4f6c733c374e538becee4714a0707'; // Obtained after initial OAuth2 authorization

$logger = new ZohoCRMSmsLogger($clientID, $clientSecret, $refreshToken);

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

// Fetch conversation IDs
$conversationsQuery = "SELECT DISTINCT conversation_id FROM messages";
$conversationsResult = $conn->query($conversationsQuery);

$conversationStats = [];

while ($conversationRow = $conversationsResult->fetch_assoc()) {
    $conversationId = $conversationRow['conversation_id'];

    // Count incoming messages
    $incomingQuery = "SELECT COUNT(*) as incoming_count FROM messages WHERE direction = 'incoming' AND conversation_id = '$conversationId'";
    $incomingResult = $conn->query($incomingQuery);
    $incomingCount = $incomingResult->fetch_assoc()['incoming_count'];

    // Count outgoing messages
    $outgoingQuery = "SELECT COUNT(*) as outgoing_count FROM messages WHERE direction = 'outgoing' AND conversation_id = '$conversationId'";
    $outgoingResult = $conn->query($outgoingQuery);
    $outgoingCount = $outgoingResult->fetch_assoc()['outgoing_count'];

    // Fetch messages for the conversation
    $messagesQuery = "SELECT createdAt, from_number, body, direction FROM messages WHERE conversation_id = '$conversationId'";
    $messagesResult = $conn->query($messagesQuery);

    $messages = [];
    $fromnumber="";
    while ($messageRow = $messagesResult->fetch_assoc()) {
        $fromnumber = $messageRow['from_number'];
        $messages[] = "[" . $messageRow['createdAt'] . "] " . $messageRow['from_number'] . ": " . $messageRow['body'];
        
    }

    $conversationStats[$conversationId] = [
        'incoming_count' => $incomingCount,
        'outgoing_count' => $outgoingCount,
        'messages' => $messages,
        'from_number'=>$fromnumber
    ];
}
$query = "DELETE FROM messages";
$conn->query($query);
// Close the database connection
$conn->close();

// Print results for each conversation
foreach ($conversationStats as $conversationId => $stats) {
    $description="";
    
    $description.= "Number of incoming messages: " .$stats['incoming_count'] . "\n";
    $description.= "Number of outgoing messages: " .$stats['outgoing_count'] . "\n";

    foreach ($stats['messages'] as $message) {
        $description.= $message . "\n";
    }
$whatid= $logger->searchContactByPhoneNumber($stats['from_number']); 
    $subject=$stats['from_number'] ." | Text Conversation";
    $logger->logTask( $subject, $description,$whatid);
   
}

?>
