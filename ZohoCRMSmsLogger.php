<?php

class ZohoCRMSmsLogger {
    private $clientID;
    private $clientSecret;
    private $refreshToken;
    private $authToken;

    public function __construct($clientID, $clientSecret, $refreshToken) {
        $this->clientID = $clientID;
        $this->clientSecret = $clientSecret;
        $this->refreshToken = $refreshToken;
    }

   public function logTask( $taskSubject, $taskDescription,$whatid) {
        

        $apiUrl = "https://www.zohoapis.com/crm/v5/Tasks"; // Use "Tasks" for the module API name
        $apiHeaders = $this->getApiHeaders();

        $data = array(
            "Subject" => $taskSubject,
            "Description" => $taskDescription,
            "Status" => "Completed",
            "Who_Id" => $whatid
        );

        $dataEncoded = json_encode(array("data" => array($data)));

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataEncoded);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $apiHeaders);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'Error: ' . curl_error($ch);
            exit; // Exit the function if there's an error
        } else {
            $responseData = json_decode($response, true);

            if (isset($responseData['data']) && isset($responseData['data'][0]) && isset($responseData['data'][0]['details']['id'])) {
                $taskId = $responseData['data'][0]['details']['id'];
                if($whatid){
                echo $taskId."</br>";
               
                }
            } else {
                echo "Failed to log the task. Response: $response";
                
            }
        }

        curl_close($ch);
    }

public function formatPhoneNumber($phoneNumber) {
       
$formattedPhoneNumber = preg_replace('/(\+\d{1})(\d{3})(\d{3})(\d{4})/', '$1 $2-$3-$4', $phoneNumber);

return $formattedPhoneNumber; // Output: +1 878-999-1871
    }

public function searchContactByPhoneNumber($phoneNumber) {
    $this->refreshAuthToken();
        $phoneNumber = $this->formatPhoneNumber($phoneNumber);

        $apiUrl = "https://www.zohoapis.com/crm/v5/Contacts/search?phone=" . urlencode($phoneNumber);
        $apiHeaders = $this->getApiHeaders();

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $apiHeaders);

        $response = curl_exec($ch);

         if (curl_errno($ch)) {
            echo 'Error: ' . curl_error($ch);
            exit;
        } else {
            $responseData = json_decode($response, true);

            if (isset($responseData['data']) && !empty($responseData['data'])) {
                $lightsOwner = $responseData['data'][0]['id'];
                return $lightsOwner;
            }
        }

        curl_close($ch);
    }

    private function refreshAuthToken() {
        $tokenUrl = "https://accounts.zoho.com/oauth/v2/token";
        $tokenHeaders = array(
            "Content-Type: application/x-www-form-urlencoded",
        );

        $tokenPayload = http_build_query(array(
            "grant_type" => "refresh_token",
            "client_id" => $this->clientID,
            "client_secret" => $this->clientSecret,
            "refresh_token" => $this->refreshToken,
        ));

        $ch = curl_init($tokenUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $tokenPayload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $tokenHeaders);

        $response = curl_exec($ch);
        $authData = json_decode($response, true);

        if (isset($authData['access_token'])) {
            $this->authToken = $authData['access_token'];
        }

        curl_close($ch);
    }

    private function getApiHeaders() {
        return array(
            "Authorization: Zoho-oauthtoken " . $this->authToken,
            "Content-Type: application/x-www-form-urlencoded",
        );
    }
}

?>