<?php

require_once 'ZohoCRMCallLogger.php'; // Include the class file


$clientID = '1000.64U9BS7NIDROEDTPG08JIN7JD91T0B';
$clientSecret = '5064a1347f24cd8644f41ddcc67821b23f36d46ea9';
$refreshToken = '1000.2fad8515bafac315bf03a60f04f77738.d1b4f6c733c374e538becee4714a0707'; // Obtained after initial OAuth2 authorization

$logger = new ZohoCRMCallLogger($clientID, $clientSecret, $refreshToken);

$webhookData = file_get_contents('php://input');

if ($webhookData) {
    $data = json_decode($webhookData, true);

    if ($data && isset($data['data']['object'])) {
        $direction = ($data['data']['object']['direction'] == "outgoing") ? "Outgoing Call" : "Incoming Call";
       $phonenumber = ($direction == "outgoing")
            ? $data['data']['object']['to']
            : $data['data']['object']['from'];
          $whoId= $logger->searchContactByPhoneNumber($phonenumber); 
        $mediaUrl = $data['data']['object']['media'][0]['url'];
        $answeredAt = strtotime($data['data']['object']['answeredAt']);
        $completedAt = strtotime($data['data']['object']['completedAt']);
        $durationInSeconds = $completedAt - $answeredAt;
        $subject=$phonenumber ." | ".$direction;
        if($direction!="outgoing")
        $subject=$subject." | ".ucfirst($data['data']['object']['status']);
        
        $calltype = ($data['data']['object']['direction'] == "outgoing") ? "Outbound" : "Inbound";
$hours = floor($durationInSeconds / 3600);
$minutes = floor(($durationInSeconds % 3600) / 60);
$formattedDuration = sprintf("%02d:%02d", $hours, $minutes);

        $formattedAnsweredAt = date("Y-m-d\TH:i:s", $answeredAt);

        $logger->logCall($subject,$mediaUrl, $formattedAnsweredAt, $formattedDuration, $whoId,$calltype);
    }
}
?>
