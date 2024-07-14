<?php
require 'vendor/autoload.php'; // For MongoDB PHP Library

use MongoDB\Client as MongoDBClient;

// Function to connect to MySQL
function connectMySQL() {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "adplay";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

// Function to connect to MongoDB
function connectMongoDB() {
    $mongoClient = new MongoDBClient("mongodb://localhost:27017");
    return $mongoClient->selectDatabase('adplayLav');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['jsonfile'])) {
        $fileTmpPath = $_FILES['jsonfile']['tmp_name'];
        $fileContents = file_get_contents($fileTmpPath);

        // Decode the JSON payload
        $jsonData = json_decode($fileContents, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            // Validate the structure and content of the JSON data
                $campaigns = $jsonData;
                $mongoDB = connectMongoDB();
                $collection = $mongoDB->selectCollection('campaigns');
                $result = $collection->insertMany($campaigns);
                

                $response = [
                    'status' => 'success',
                    'message' => 'Data stored successfully in MongoDB',
                    'inserted_ids' => $result->getInsertedIds()
                ];

                
                // Connect to MySQL
                $conn = connectMySQL();

                $status = $response['status'];
                $message = $response['message'];
                $inserted_ids = json_encode($response['inserted_ids']);

                $responseStmt = $conn->prepare("INSERT INTO responses (status, message, inserted_ids) VALUES (?, ?, ?)");
                $responseStmt->bind_param("sss", $status, $message, $inserted_ids);

                $responseStmt->execute();

                $campaignTypeStmt = $conn->prepare("INSERT INTO campaign_types (type_name) VALUES (?) ON DUPLICATE KEY UPDATE Id=LAST_INSERT_ID(Id)");
                $campaignTypeStmt->bind_param("s", $campaignTypeName);

                $campaignStmt = $conn->prepare("INSERT INTO campaigns (code, name, goal, starts, ends, campaign_type_id) VALUES (?, ?, ?, ?, ?, ?)");
                $campaignStmt->bind_param("ssissi", $code, $name, $goal, $starts, $ends, $campaignTypeId);
                // Insert response data into MySQL
                foreach ($campaigns as $campaign) {
                    $code = uniqid(); // Generate a unique code for the campaign
                    $name = $campaign['campaign_name'];
                    $goal = $campaign['campaign_goal'];
                    $starts = $campaign['campaign_starts'];
                    $ends = $campaign['campaign_ends'];
                    $campaignTypeName = $campaign['campaign_type'];

                    // Retrieve campaign_type_id
                    $typeQuery = "SELECT id FROM campaign_types WHERE type_name = '$campaignTypeName'";
                    $typeResult = $conn->query($typeQuery);
                    if ($typeResult->num_rows > 0) {
                        $typeRow = $typeResult->fetch_assoc();

                        $campaignTypeId = $typeRow['id'];

                        // Insert the campaign
                        $campaignStmt->execute();
                    }else{
                        
                        $campaignTypeStmt->execute();
                        $campaignTypeId = $conn->insert_id;
                        $campaignStmt->execute();
                    }
                }

                $conn->close();

                header('Content-Type: application/json');
                echo json_encode(["status" => "Success", "message" => "Data Upload Successfully"]);

        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Invalid JSON"]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "No file uploaded"]);
    }
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["status" => "error", "message" => "Only POST requests are allowed"]);
}
?>
