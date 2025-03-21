<?php
session_start();

// Master PIN users need to guess - define this at the top
$masterPin = "3845";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handleGuessSubmission();
} else if (isset($_GET['action']) && $_GET['action'] === 'new-riddle') {
    fetchRandomRiddle();
} else if (isset($_GET['action']) && $_GET['action'] === 'check-pin-status') {
    checkPinStatus();
} else if (isset($_GET['action']) && $_GET['action'] === 'verify-pin') {
    verifyPinGuess();
} else if (isset($_GET['action']) && $_GET['action'] === 'reset-game') {
    resetGame();
} else {
    displayFullPage();
}

function handleGuessSubmission() {
    global $masterPin;
    
    $data = json_decode(file_get_contents('php://input'), true);
    $guess = isset($data['guess']) ? trim($data['guess']) : '';
    
    $correctAnswer = isset($_SESSION['correct_answer']) ? $_SESSION['correct_answer'] : '';
    $isCorrect = strcasecmp($guess, $correctAnswer) === 0;
    
    if ($isCorrect) {
        // Initialize solved riddles array if not exists
        if (!isset($_SESSION['solved_riddles'])) {
            $_SESSION['solved_riddles'] = [];
        }
        
        $riddleId = $_SESSION['current_riddle_id'];
        $_SESSION['solved_riddles'][] = $riddleId;
        
        // Get the PIN digit for this riddle
        $pinDigit = getPinDigitForRiddle($riddleId);
        
        // Initialize PIN collection array if not exists
        if (!isset($_SESSION['collected_pin_digits'])) {
            $_SESSION['collected_pin_digits'] = [];
        }
        
        // Add this digit to collected digits if not already there
        if (!in_array($pinDigit, $_SESSION['collected_pin_digits'])) {
            $_SESSION['collected_pin_digits'][] = $pinDigit;
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode(['correct' => $isCorrect]);
    exit;
}

function getPinDigitForRiddle($riddleId) {
    $servername = "localhost";
    $username = "root";
    $password = "Mudzo2608";
    $dbname = "80s_video_store";
    
    try {
        $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $query = "SELECT pin_digit FROM riddles WHERE id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$riddleId]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['pin_digit'] : null;
    } catch (PDOException $e) {
        return null;
    }
}

function fetchRandomRiddle() {
    $servername = "localhost";
    $username = "root";
    $password = "Mudzo2608";
    $dbname = "80s_video_store";

    try {
        $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Get a riddle the user hasn't solved yet if possible
        $solvedRiddleIds = isset($_SESSION['solved_riddles']) ? $_SESSION['solved_riddles'] : [];
        
        if (!empty($solvedRiddleIds)) {
            $placeholders = implode(',', array_fill(0, count($solvedRiddleIds), '?'));
            $query = "SELECT id, question, answer, pin_digit FROM riddles WHERE id NOT IN ($placeholders) ORDER BY RAND() LIMIT 1";
            $stmt = $pdo->prepare($query);
            $stmt->execute($solvedRiddleIds);
        } else {
            $query = "SELECT id, question, answer, pin_digit FROM riddles ORDER BY RAND() LIMIT 1";
            $stmt = $pdo->prepare($query);
            $stmt->execute();
        }

        $riddle = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($riddle) {
            $trimmedAnswer = trim($riddle['answer']);
            $_SESSION['correct_answer'] = $trimmedAnswer;
            $_SESSION['current_riddle_id'] = $riddle['id'];
            
            header('Content-Type: application/json');
            echo json_encode([
                'riddle' => $riddle['question'],
                'answerLength' => strlen($trimmedAnswer)
            ]);
        } else {
            // If all riddles are solved, get a random one
            $query = "SELECT id, question, answer, pin_digit FROM riddles ORDER BY RAND() LIMIT 1";
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            
            $riddle = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($riddle) {
                $trimmedAnswer = trim($riddle['answer']);
                $_SESSION['correct_answer'] = $trimmedAnswer;
                $_SESSION['current_riddle_id'] = $riddle['id'];
                
                header('Content-Type: application/json');
                echo json_encode([
                    'riddle' => $riddle['question'],
                    'answerLength' => strlen($trimmedAnswer),
                    'allSolved' => true
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'No riddles found']);
            }
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

function checkPinStatus() {
    $progress = checkPinProgress();
    
    header('Content-Type: application/json');
    echo json_encode($progress);
    exit;
}

function checkPinProgress() {
    global $masterPin;
    
    $collectedDigits = isset($_SESSION['collected_pin_digits']) ? $_SESSION['collected_pin_digits'] : [];
    $totalRiddles = getTotalRiddleCount();
    $solvedRiddles = isset($_SESSION['solved_riddles']) ? count(array_unique($_SESSION['solved_riddles'])) : 0;
    
    $allRiddlesSolved = ($solvedRiddles >= $totalRiddles);
    $hasAllPinDigits = count(array_unique($collectedDigits)) >= strlen($masterPin);
    
    return [
        'allRiddlesSolved' => $allRiddlesSolved,
        'hasAllPinDigits' => $hasAllPinDigits,
        'collectedDigits' => $collectedDigits,
        'solvedCount' => $solvedRiddles,
        'totalRiddles' => $totalRiddles
    ];
}

function getTotalRiddleCount() {
    $servername = "localhost";
    $username = "root";
    $password = "Mudzo2608";
    $dbname = "80s_video_store";
    
    try {
        $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $query = "SELECT COUNT(*) as count FROM riddles";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? intval($result['count']) : 0;
    } catch (PDOException $e) {
        return 0;
    }
}

function verifyPinGuess() {
    global $masterPin;
    
    $data = json_decode(file_get_contents('php://input'), true);
    $guess = isset($data['pin']) ? $data['pin'] : '';
    
    if (strlen($guess) !== strlen($masterPin)) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid PIN length']);
        exit;
    }
    
    $feedback = [];
    $usedMasterPositions = [];
    
    // First pass: check for exact matches (green)
    for ($i = 0; $i < strlen($masterPin); $i++) {
        if ($guess[$i] === $masterPin[$i]) {
            $feedback[$i] = 'green';
            $usedMasterPositions[] = $i;
        }
    }
    
    // Second pass: check for correct digits in wrong positions (yellow)
    for ($i = 0; $i < strlen($guess); $i++) {
        if (isset($feedback[$i])) continue; // Skip already matched positions
        
        $found = false;
        for ($j = 0; $j < strlen($masterPin); $j++) {
            if (in_array($j, $usedMasterPositions)) continue; // Skip already used positions
            
            if ($guess[$i] === $masterPin[$j]) {
                $feedback[$i] = 'yellow';
                $usedMasterPositions[] = $j;
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $feedback[$i] = 'red'; // Wrong digit
        }
    }
    
    $isCorrect = ($guess === $masterPin);
    
    // If correct, store in session
    if ($isCorrect) {
        $_SESSION['pin_solved'] = true;
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'feedback' => $feedback,
        'correct' => $isCorrect
    ]);
    exit;
}

function resetGame() {
    session_destroy();
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
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
    
    /* Mastermind Styles */
    #mastermind-container {
      margin-top: 30px;
      border: 2px solid #0f0;
      padding: 20px;
      background-color: #001100;
      display: none;
    }
    #collected-digits {
      font-size: 24px;
      margin: 15px 0;
      letter-spacing: 10px;
    }
    #previous-guesses {
      margin-top: 20px;
    }
    .guess-row {
      display: flex;
      justify-content: center;
      margin: 10px 0;
    }
    .digit {
      width: 40px;
      height: 40px;
      border: 1px solid #0f0;
      margin: 0 5px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
    }
    .feedback-green {
      background-color: #00FF00;
      color: #000;
    }
    .feedback-yellow {
      background-color: #FFFF00;
      color: #000;
    }
    .feedback-red {
      background-color: #FF0000;
      color: #000;
    }
    #pin-result {
      margin-top: 20px;
      font-size: 18px;
      min-height: 30px;
    }
    #progress-display {
      margin-top: 10px;
      font-size: 14px;
      color: #0f0;
    }
    #victory-screen {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: #000;
      z-index: 20;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      text-align: center;
    }
    .blinking {
      animation: blink 1s infinite;
    }
    @keyframes blink {
      0% { opacity: 1; }
      50% { opacity: 0; }
      100% { opacity: 1; }
    }
    #secret-message {
      font-size: 24px;
      margin: 30px 0;
      color: #f00;
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
    <h1>ENIGMA TERMINAL 3.0</h1>
    <div id="progress-display"></div>
    
    <div id="riddle-container">
        <h2 id="riddle-text">INITIALIZING RIDDLE MODULE...</h2>
        <div class="answer-length" id="answer-length"></div>
    </div>
    
    <div id="guess-container">
        <input type="text" id="guess-input" placeholder="ENTER GUESS" />
        <button onclick="submitGuess()">EXECUTE GUESS</button>
    </div>
    
    <button onclick="loadNewRiddle()">NEW RIDDLE</button>
    <div id="result"></div>
    
    <div id="mastermind-container">
      <h2>FINAL SECURITY SYSTEM</h2>
      <p>ENTER THE MASTER PIN USING COLLECTED DIGITS</p>
      <div id="collected-digits"></div>
      
      <div id="pin-input-container">
        <input type="text" id="pin-input" placeholder="ENTER 4-DIGIT PIN" maxlength="4" pattern="[0-9]*" inputmode="numeric" />
        <button onclick="submitPinGuess()">VERIFY</button>
      </div>
      
      <div id="previous-guesses"></div>
      <div id="pin-result"></div>
    </div>
  </div>
  
  <div id="victory-screen">
    <h1 class="blinking">SYSTEM ACCESSED</h1>
    <p>CONGRATULATIONS, HACKER</p>
    <div id="secret-message">TOP SECRET INFORMATION REVEALED</div>
    <button onclick="resetGame()">RESET SYSTEM</button>
  </div>

  <script>
    const unlockPasscode = "1234";
    let answerLength = 0;
    let attempts = 0;

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
      fetchRiddle().then(() => {
        checkPinStatus();
      });
    }

    async function fetchRiddle() {
      try {
        const response = await fetch("?action=new-riddle");
        const data = await response.json();
        
        document.getElementById('riddle-text').textContent = data.riddle;
        answerLength = data.answerLength;
        const guessInput = document.getElementById('guess-input');
        guessInput.maxLength = answerLength;
        guessInput.placeholder = `ENTER ${answerLength}-CHARACTER SEQUENCE`;
        document.getElementById('answer-length').textContent = `[REQUIRED LENGTH: ${answerLength}]`;
        document.getElementById('result').textContent = '';
        guessInput.value = '';
        
        return data;
      } catch (error) {
        document.getElementById('riddle-text').textContent = 'SYSTEM MALFUNCTION - RETRY';
        return null;
      }
    }

    function loadNewRiddle() {
      document.getElementById('riddle-text').textContent = 'ACCESSING RIDDLE DATABASE...';
      fetchRiddle().then(() => {
        checkPinStatus();
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
        document.getElementById('result').textContent = data.correct ? "+++ ACCESS GRANTED +++" : "!!! INTRUDER ALERT !!!";
        
        if (data.correct) {
          checkPinStatus();
        }
      });
    }
    
    function checkPinStatus() {
      fetch("?action=check-pin-status")
        .then(response => response.json())
        .then(data => {
          // Update progress display
          document.getElementById('progress-display').textContent = 
            `RIDDLES SOLVED: ${data.solvedCount}/${data.totalRiddles} | DIGITS COLLECTED: ${data.collectedDigits.length}`;
          
          // Show mastermind game if we have enough digits or all riddles are solved
          if (data.hasAllPinDigits || data.allRiddlesSolved) {
            document.getElementById('mastermind-container').style.display = "block";
            updateCollectedDigits(data.collectedDigits);
          }
        });
    }

    function updateCollectedDigits(digits) {
      const container = document.getElementById('collected-digits');
      container.textContent = "AVAILABLE DIGITS: " + digits.join(' ');
    }

    function submitPinGuess() {
      const pinGuess = document.getElementById('pin-input').value;
      
      if (pinGuess.length !== 4) {
        alert("PIN MUST BE 4 DIGITS");
        return;
      }
      
      attempts++;
      
      fetch("?action=verify-pin", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({ pin: pinGuess })
      })
      .then(response => response.json())
      .then(data => {
        displayPinFeedback(pinGuess, data.feedback);
        
        if (data.correct) {
          document.getElementById('pin-result').textContent = "!!! SECURITY SYSTEM DISABLED !!!";
          setTimeout(displayVictoryScreen, 1500);
        } else {
          document.getElementById('pin-result').textContent = `ATTEMPT ${attempts} FAILED. TRY AGAIN.`;
        }
      });
    }

    function displayPinFeedback(guess, feedback) {
      const container = document.getElementById('previous-guesses');
      const guessRow = document.createElement('div');
      guessRow.className = 'guess-row';
      
      // Add attempt number
      const attemptLabel = document.createElement('div');
      attemptLabel.textContent = `#${attempts}:`;
      attemptLabel.style.marginRight = '10px';
      attemptLabel.style.width = '30px';
      attemptLabel.style.textAlign = 'right';
      guessRow.appendChild(attemptLabel);
      
      for (let i = 0; i < guess.length; i++) {
        const digitDiv = document.createElement('div');
        digitDiv.className = `digit feedback-${feedback[i]}`;
        digitDiv.textContent = guess[i];
        guessRow.appendChild(digitDiv);
      }
      
      // Add explanation of colors
      if (attempts === 1) {
        const legend = document.createElement('div');
        legend.style.marginTop = '10px';
        legend.style.fontSize = '12px';
        legend.innerHTML = 
          'GREEN = CORRECT DIGIT IN CORRECT POSITION<br>' +
          'YELLOW = CORRECT DIGIT IN WRONG POSITION<br>' +
          'RED = INCORRECT DIGIT';
        container.appendChild(legend);
      }
      
      container.insertBefore(guessRow, container.firstChild);
      document.getElementById('pin-input').value = '';
    }
    
    function displayVictoryScreen() {
      document.getElementById('victory-screen').style.display = "flex";
    }
    
    function resetGame() {
      fetch("?action=reset-game")
        .then(() => {
          location.reload();
        });
    }
    
    // Add event listener to allow submitting with Enter key
    document.getElementById('password-input').addEventListener('keyup', function(event) {
      if (event.key === 'Enter') {
        checkPasscode();
      }
    });
    
    document.getElementById('guess-input').addEventListener('keyup', function(event) {
      if (event.key === 'Enter') {
        submitGuess();
      }
    });
    
    document.getElementById('pin-input').addEventListener('keyup', function(event) {
      if (event.key === 'Enter') {
        submitPinGuess();
      }
    });
  </script>

</body>
</html>
<?php
}
?>