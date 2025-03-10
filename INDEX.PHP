<?php

// DATABASE CONNECTION
$servername = "localhost";
$dbname = "80s_video_store";
$username = "root";
$password = "Mudzo2608";

try {
    // Connect to the database
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Query to fetch a random riddle
    $query = "SELECT question, answer FROM riddles ORDER BY RAND() LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->execute(); // <-- Added execution step
    $riddle = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($riddle) {
        // Return the riddle as JSON
        header('Content-Type: application/json');
        echo json_encode($riddle);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'No riddles found']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>