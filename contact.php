<?php
// Get the webhook JSON data
$webhookData = file_get_contents('php://input');

$webhookData = json_decode($webhookData, true);
function putdata($jsonObject,$token,$id){
    $curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://contact.openphoneapi.com/v2/contact/'.$id,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'PUT',
  CURLOPT_POSTFIELDS =>$jsonObject,
  CURLOPT_HTTPHEADER => array(
    'Content-Type: application/json',
    'Authorization: Bearer '.$token
  ),
));

$response = curl_exec($curl);

curl_close($curl);
echo $response;
}
function getdata($id,$token){
    $curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://contact.openphoneapi.com/v2/contact/'.$id,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'GET',
  CURLOPT_HTTPHEADER => array(
    'Authorization: Bearer '.$token
  ),
));

$response = curl_exec($curl);

curl_close($curl);
return $response;
}

function gettoken(){
    $curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://auth.openphoneapi.com/v2/signin/password',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS =>'{"email":"external.developer@holidaylightco.com","password":"zZE#9TmS!"}',
  CURLOPT_HTTPHEADER => array(
    'Content-Type: application/json'
  ),
));

$response = json_decode(curl_exec($curl));

curl_close($curl);
return $response->id_token;
}
function addItemToJSON($jsonObject, $value, $itemName) {
    $jsonObject = json_decode($jsonObject, true);
   
    foreach ($jsonObject['items'] as &$item) {
        
        if (isset($item['type']) && $item['type'] === $itemName) {
            $item['value'] = $value;
           
            break;
        }
        else if (isset($item['name']) && $item['name'] === $itemName) {
            $item['value'] = $value;
           
            break;
        }
    }
    
    return $jsonObject;
}

function addurlToJSON($jsonObject, $value) {
    $jsonObject = json_decode($jsonObject, true);
    $length=sizeof($jsonObject['items']);
   $flag=1;
    foreach ($jsonObject['items'] as &$item) {
        
        if (isset($item['name']) && $item['name'] === 'CRM Record Link') {
           $item['value'] = $value;
            $flag=0;
            break;
        }
    }
    
    if($flag){
        $jsonObject['items'][$length]['name']='CRM Record Link';
        $jsonObject['items'][$length]['value']=$value;
        $jsonObject['items'][$length]['type']='url';
        $jsonObject['items'][$length]["templateKey"]= "1692886660121";
    }
    print_r($jsonObject);
    return $jsonObject;
}



// Extract the ContactID from the header
$contactId = $_SERVER['HTTP_CONTACTID'];

// Extract email from the webhook JSON
$email = $webhookData['email'];

$originalPhoneNumber = $webhookData['phone'];
$phone = str_replace(array(' ', '-',), '', $originalPhoneNumber);

// Database connection parameters
$host = 'localhost';
$username = 'holiggtq_ap1solutions';
$password = '2Kjt3([$dRoo';
$dbName = 'holiggtq_ap1solutions_pwa';

// Create a database connection
$conn = new mysqli($host, $username, $password, $dbName);

// Check connection
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Prepare and execute the SQL query to search for the user by phone number
$sql = "SELECT userid FROM user_data WHERE phone = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $phone);
$stmt->execute();
$result = $stmt->get_result();

// Check if a matching user is found
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $userid=$row['userid'];
    $token=gettoken();
    
    $newjson=getdata($userid,$token);
    if($email!="")
    $newjson= json_encode(addItemToJSON($newjson, $email, "email"));
    if($phone!="")
    $newjson= json_encode(addItemToJSON($newjson, $phone, "phone-number"));
    if($webhookData['address']!="")
    $newjson= json_encode(addItemToJSON($newjson, $webhookData['address'], "address"));
    if($webhookData['firstname']!="")
    {
        $temp=json_decode($newjson,true);
        $temp['firstName']=$webhookData['firstname'];
        $newjson=json_encode($temp);
    }
    if($webhookData['lastname']!="")
    {
        $temp=json_decode($newjson,true);
        $temp['lastName']=$webhookData['lastname'];
        $newjson=json_encode($temp);
    }
    
    if($contactId!=""){
    $newjson= json_encode(addItemToJSON($newjson, $contactId, "CRM Record ID"));
    $newjson= json_encode(addurlToJSON($newjson, "https://crm.zoho.com/crm/holidaylightco/tab/Contacts/".$contactId));
    }
   file_put_contents("update.txt", $newjson."\n");
    echo "<br><br>";
    putdata($newjson,$token,$userid);
} else {
    echo 'User not found.';
}

// Close the database connection
$stmt->close();
$conn->close();


?>
