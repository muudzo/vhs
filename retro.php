<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handleGuessSubmission();
} else if (isset($_GET['action']) && $_GET['action'] === 'new-riddle') {
    fetchRandomRiddle();
} else {
    displayFullPage();
}

function handleGuessSubmission() {
    $data = json_decode(file_get_contents('php://input'), true);
    $guess = isset($data['guess']) ? trim($data['guess']) : '';
    
    $correctAnswer = isset($_SESSION['correct_answer']) ? $_SESSION['correct_answer'] : '';
    $isCorrect = strcasecmp($guess, $correctAnswer) === 0;
    
    header('Content-Type: application/json');
    echo json_encode(['correct' => $isCorrect]);
    exit;
}

function fetchRandomRiddle() {
    $servername = "localhost";
    $username = "root";
    $password = "Mudzo2608";
    $dbname = "80s_video_store";

    try {
        $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $query = "SELECT question, answer FROM riddles ORDER BY RAND() LIMIT 1";
        $stmt = $pdo->prepare($query);
        $stmt->execute();

        $riddle = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($riddle) {
            $trimmedAnswer = trim($riddle['answer']);
            $_SESSION['correct_answer'] = $trimmedAnswer;
            
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
    exit;
}

function displayFullPage() {
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Retro Riddle Terminal</title>
  <style>
    body {
      font-family: 'Courier New', monospace;
      background-color: #000;
      color: #0f0;
      max-width: 800px;
      margin: 0 auto;
      padding: 20px;
      line-height: 1.5;
      box-shadow: inset 0 0 50px rgba(0, 255, 0, 0.1);
      position: relative;
      overflow: hidden;
    }
    #riddle-container {
      background-color: #001100;
      padding: 20px;
      border: 2px dashed #0f0;
      margin-bottom: 20px;
      text-shadow: 0 0 5px #0f0;
    }
    #guess-container {
      margin-bottom: 20px;
      position: relative;
    }
    input[type="text"] {
      background: #000;
      border: 2px solid #0f0;
      color: #0f0;
      padding: 10px;
      font-family: 'Courier New', monospace;
      font-size: 16px;
      width: 300px;
      margin: 10px 0;
    }
    input[type="text"]:focus {
      outline: none;
      box-shadow: 0 0 10px #0f0;
    }
    button {
      background: #000;
      border: 2px solid #0f0;
      color: #0f0;
      padding: 10px 20px;
      font-family: 'Courier New', monospace;
      font-size: 16px;
      cursor: pointer;
      transition: all 0.3s ease;
      margin: 5px;
    }
    button:hover {
      background: #0f0;
      color: #000;
    }
    #result {
      border-left: 3px solid #0f0;
      padding-left: 10px;
      margin: 20px 0;
      color: #0f0;
    }
    .answer-length {
      color: #0f0;
      font-size: 0.9em;
      margin-top: 5px;
    }
    @keyframes scanline {
      0% { transform: translateY(-100%); }
      100% { transform: translateY(100%); }
    }
    body::after {
      content: "";
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(
        to bottom,
        rgba(0, 0, 0, 0.1) 50%,
        rgba(0, 0, 0, 0.2) 50%
      );
      background-size: 100% 4px;
      pointer-events: none;
      animation: scanline 4s linear infinite;
    }
    body::before {
      content: "";
      position: fixed;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, transparent 60%, rgba(0, 15, 0, 0.8));
      pointer-events: none;
    }
  </style>
</head>
<body>
  <div id="riddle-container">
      <h2 id="riddle-text">INITIALIZING RIDDLE MODULE...</h2>
      <div class="answer-length" id="answer-length"></div>
  </div>
  
  <div id="guess-container">
      <input type="text" id="guess-input" placeholder="ENTER GUESS" />
      <button onclick="submitGuess()">EXECUTE GUESS</button>
  </div>
  
  <button onclick="loadNewRiddle()">NEW RIDDLE PROTOCOL</button>
  <div id="result"></div>

  <script>
    // Set the passcode that unlocks the game
    const unlockPasscode = "1234";

    // Global variable to store the required answer length
    let answerLength = 0;

    // When the page loads, prompt for the passcode
    window.onload = checkPasscode;

    function checkPasscode() {
      const userInput = prompt("Enter the passcode to unlock the game:");
      if (userInput === unlockPasscode) {
        alert("Access granted! Welcome to the game.");
        startGame();
      } else {
        alert("Incorrect passcode. Please try again.");
        checkPasscode();
      }
    }

    function startGame() {
      // Start by fetching the first riddle
      fetchRiddle();
    }

    function fetchRiddle() {
      fetch("?action=new-riddle")
        .then(response => {
          if (!response.ok) throw new Error('NETWORK FAILURE');
          return response.json();
        })
        .then(data => {
          document.getElementById('riddle-text').textContent = data.riddle;
          answerLength = data.answerLength;
          const guessInput = document.getElementById('guess-input');
          guessInput.maxLength = answerLength;
          guessInput.placeholder = `ENTER ${answerLength}-CHARACTER SEQUENCE`;
          document.getElementById('answer-length').textContent = `[REQUIRED LENGTH: ${answerLength}]`;
          document.getElementById('result').textContent = '';
          guessInput.value = '';
        })
        .catch(error => {
          console.error('ERROR:', error);
          document.getElementById('riddle-text').textContent = 'SYSTEM MALFUNCTION - RETRY';
        });
    }

    function loadNewRiddle() {
      document.getElementById('riddle-text').textContent = 'ACCESSING RIDDLE DATABASE...';
      fetchRiddle();
    }

    function submitGuess() {
      const guess = document.getElementById('guess-input').value;
      
      if (guess.trim().length !== answerLength) {
        alert(`INPUT MUST BE ${answerLength} CHARACTERS`);
        return;
      }
      
      fetch("", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({ guess: guess })
      })
      .then(response => {
        if (!response.ok) throw new Error("TRANSMISSION ERROR");
        return response.json();
      })
      .then(data => {
        document.getElementById('result').textContent = data.correct 
          ? "+++ ACCESS GRANTED +++" 
          : "!!! INTRUDER ALERT !!!";
      })
      .catch(error => {
        console.error("ERROR:", error);
        document.getElementById('result').textContent = "SYSTEM ERROR";
      });
    }
  </script>
</body>
</html>
<?php
}
?>
