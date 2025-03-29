<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handleGuessSubmission();
} else if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'new-riddle':
            fetchRandomRiddle();
            break;
        case 'get-collected-pins':
            getCollectedPins();
            break;
        case 'reset-game':
            resetGame();
            break;
    }
} else {
    displayFullPage();
}

function resetGame() {
    // Completely destroy the existing session
    session_unset();     // Remove all session variables
    session_destroy();   // Destroy the session

    // Start a new session
    session_start();

    // Send a JSON response indicating successful reset
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success', 
        'message' => 'Game has been reset successfully.'
    ]);
    exit;
}
//example of properly handling user input in php
function handleGuessSubmission() {
  //safely getting user input
    $data = json_decode(file_get_contents('php://input'), true);
    //sanitizing and validating user input to prevent sql injection
    $guess = isset($data['guess']) ? trim($data['guess']) : '';
    //using session data securely
    $correctAnswer = isset($_SESSION['correct_answer']) ? $_SESSION['correct_answer'] : '';
    //case insensitive comparison for better user experience after testing the game
    $isCorrect = strcasecmp($guess, $correctAnswer) === 0;
    
    // If guess is correct, track the pin
    if ($isCorrect) {
        $currentPin = isset($_SESSION['current_riddle_pin']) ? $_SESSION['current_riddle_pin'] : null;
        
        // Initialize collected pins array if not exists
        if (!isset($_SESSION['collected_pins'])) {
            $_SESSION['collected_pins'] = [];
        }
        
        // Add pin if not already collected
        if ($currentPin && !in_array($currentPin, $_SESSION['collected_pins'])) {
            $_SESSION['collected_pins'][] = $currentPin;
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'correct' => $isCorrect,
        'pin' => $isCorrect ? $currentPin : null
    ]);
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

        // Exclude already collected pins
        $collectedPins = isset($_SESSION['collected_pins']) ? $_SESSION['collected_pins'] : [];
        $pinCondition = $collectedPins ? "AND pin NOT IN (" . implode(',', $collectedPins) . ")" : "";

        $query = "SELECT question, answer, pin FROM riddles 
                  WHERE pin != 0 $pinCondition 
                  ORDER BY RAND() LIMIT 1";
        $stmt = $pdo->prepare($query);
        $stmt->execute();

        $riddle = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($riddle) {
            $trimmedAnswer = trim($riddle['answer']);
            $_SESSION['correct_answer'] = $trimmedAnswer;
            $_SESSION['current_riddle_pin'] = $riddle['pin'];
            
            header('Content-Type: application/json');
            echo json_encode([
                'riddle' => $riddle['question'],
                'answerLength' => strlen($trimmedAnswer),
                'pin' => $riddle['pin']
            ]);
        } else {
            // If no more unique pins are available
            http_response_code(404);
            echo json_encode(['error' => 'No more unique riddles available']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

function getCollectedPins() {
    $collectedPins = isset($_SESSION['collected_pins']) ? $_SESSION['collected_pins'] : [];
    
    header('Content-Type: application/json');
    echo json_encode(['collected_pins' => $collectedPins]);
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
      text-align: center;
    }
    #password-screen {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: #000;
      display: flex;
      justify-content: center;
      align-items: center;
      flex-direction: column;
      z-index: 10;
    }
    #password-screen input {
      background: #000;
      border: 2px solid #0f0;
      color: #0f0;
      padding: 10px;
      font-family: 'Courier New', monospace;
      font-size: 16px;
      text-align: center;
      width: 200px;
      margin: 10px;
    }
    #password-screen button {
      background: #000;
      border: 2px solid #0f0;
      color: #0f0;
      padding: 10px 20px;
      font-size: 16px;
      cursor: pointer;
    }
    #password-screen button:hover {
      background: #0f0;
      color: #000;
    }
    #password-error {
      color: red;
      margin-top: 10px;
    }
    #game-container {
      display: none;
    }
    #riddle-container {
      background-color: #001100;
      padding: 20px;
      border: 2px dashed #0f0;
      margin-bottom: 20px;
      text-shadow: 0 0 5px #0f0;
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
      text-align: center;
    }
    button {
      background: #000;
      border: 2px solid #0f0;
      color: #0f0;
      padding: 10px 20px;
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
      margin: 20px 0;
      color: #0f0;
    }
    #pin-container {
      margin-top: 20px;
      color: #0f0;
    }
  </style>
</head>
<body>

  <div id="password-screen">
    <h2>ENTER ACCESS CODE</h2>
    <input type="password" id="password-input" placeholder="ACCESS CODE">
    <button onclick="checkPasscode()">ENTER</button>
    <div id="password-error"></div>
  </div>

  <div id="game-container">
    <div id="riddle-container">
        <h2 id="riddle-text">INITIALIZING RIDDLE MODULE...</h2>
        <div class="answer-length" id="answer-length"></div>
    </div>
    
    <div id="guess-container">
        <input type="text" id="guess-input" placeholder="ENTER GUESS" />
        <button onclick="submitGuess()">EXECUTE GUESS</button>
    </div>
    
    <button onclick="loadNewRiddle()">NEW RIDDLE</button>
    <button onclick="resetGame()">RESET GAME</button>
    
    <div id="result"></div>
    
    <div id="pin-container">
        <h3>COLLECTED PINS: <span id="collected-pins">NONE</span></h3>
    </div>
  </div>

  <script>
    const unlockPasscode = "1234";
    let answerLength = 0;
    let collectedPins = [];

    function checkPasscode() {
      const userInput = document.getElementById('password-input').value;
      if (userInput === unlockPasscode) {
        document.getElementById('password-screen').style.display = "none";
        document.getElementById('game-container').style.display = "block";
        startGame();
      } else {
        document.getElementById('password-error').textContent = "!!! ACCESS DENIED !!!";
      }
    }

    function startGame() {
        fetchCollectedPins();
        fetchRiddle();
    }

    function fetchCollectedPins() {
        fetch("?action=get-collected-pins")
            .then(response => response.json())
            .then(data => {
                collectedPins = data.collected_pins;
                updateCollectedPinsDisplay();
            });
    }

    function updateCollectedPinsDisplay() {
        const pinDisplay = document.getElementById('collected-pins');
        pinDisplay.textContent = collectedPins.length > 0 ? collectedPins.join(', ') : 'NONE';
    }

    function fetchRiddle() {
        fetch("?action=new-riddle")
            .then(response => response.json())
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
                document.getElementById('riddle-text').textContent = 'SYSTEM MALFUNCTION - RETRY';
            });
    }

    function loadNewRiddle() {
        document.getElementById('riddle-text').textContent = 'ACCESSING RIDDLE DATABASE...';
        fetchRiddle();
    }

    function resetGame() {
        fetch("?action=reset-game")
            .then(response => response.json())
            .then(data => {
                // Reset UI elements
                document.getElementById('collected-pins').textContent = 'NONE';
                document.getElementById('result').textContent = 'SYSTEM RESET - REINITIALIZING...';
                document.getElementById('riddle-text').textContent = 'INITIALIZING RIDDLE MODULE...';
                document.getElementById('guess-input').value = '';
                document.getElementById('answer-length').textContent = '';

                // Reinitialize game state
                collectedPins = [];
                answerLength = 0;

                // Start game again
                startGame();
            })
            .catch(error => {
                document.getElementById('result').textContent = 'RESET FAILED - SYSTEM ERROR';
            });
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
        .then(response => response.json())
        .then(data => {
            if (data.correct) {
                document.getElementById('result').textContent = "+++ ACCESS GRANTED +++";
                fetchCollectedPins(); // Update collected pins
            } else {
                document.getElementById('result').textContent = "!!! INTRUDER ALERT !!!";
            }
        });
    }
  </script>

</body>
</html>
<?php
}
?>