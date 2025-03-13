<?php
session_start();

// DATABASE CONNECTION
$servername = "localhost";
$username = "root";
$password = "Mudzo2608";
$dbname = "80s_video_store";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Query to fetch a random riddle (question and answer)
    $query = "SELECT question, answer FROM riddles ORDER BY RAND() LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->execute();

    $riddle = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($riddle) {
        // Get the trimmed answer to ensure consistency
        $trimmedAnswer = trim($riddle['answer']);
        
        // Store the trimmed answer in the session
        $_SESSION['correct_answer'] = $trimmedAnswer;
        
        // Return the question and length of the trimmed answer
        header('Content-Type: application/json');
        echo json_encode([
          'riddle' => $riddle['question'],
          'answerLength' => strlen($trimmedAnswer)
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'No riddles found']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>