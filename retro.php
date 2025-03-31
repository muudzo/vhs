<?php
session_start();

// Initialize pins array if it doesn't exist
if (!isset($_SESSION['collected_pins'])) {
    $_SESSION['collected_pins'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_GET['action']) && $_GET['action'] === 'reset-pins') {
        // Reset pins when requested
        $_SESSION['collected_pins'] = [];
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Pins reset']);
        exit;
    } else {
        handleGuessSubmission();
    }
} else if (isset($_GET['action']) && $_GET['action'] === 'new-riddle') {
    fetchRandomRiddle();
} else if (isset($_GET['action']) && $_GET['action'] === 'get-pins') {
    // Return the collected pins
    header('Content-Type: application/json');
    echo json_encode(['pins' => $_SESSION['collected_pins']]);
    exit;
} else if (isset($_GET['action']) && $_GET['action'] === 'mastermind') {
    displayMastermindGame();
} else {
    displayRiddlePage();
}

function handleGuessSubmission() {
    $data = json_decode(file_get_contents('php://input'), true);
    $guess = isset($data['guess']) ? trim($data['guess']) : '';
    
    $correctAnswer = isset($_SESSION['correct_answer']) ? $_SESSION['correct_answer'] : '';
    $isCorrect = strcasecmp($guess, $correctAnswer) === 0;
    
    if ($isCorrect) {
        // Generate a random pin between 0-9 when a riddle is solved correctly
        $pin = rand(0, 9);
        // Add to the collection (if not already maxed out)
        if (count($_SESSION['collected_pins']) < 3) {
            $_SESSION['collected_pins'][] = $pin;
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'correct' => $isCorrect, 
        'pins' => $_SESSION['collected_pins'],
        'pinAdded' => $isCorrect && count($_SESSION['collected_pins']) <= 3
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
                'answerLength' => strlen($trimmedAnswer),
                'pins' => $_SESSION['collected_pins']
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

function displayRiddlePage() {
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Retro Riddle Terminal</title>
  <link rel="stylesheet" href="mastermind.css">
  <style>
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
    #collected-pins {
      margin-top: 20px;
      padding: 10px;
      border: 2px dashed #0f0;
      background-color: #001100;
    }
    #pin-display {
      display: flex;
      justify-content: center;
      gap: 10px;
      margin-top: 10px;
    }
    .pin {
      width: 40px;
      height: 40px;
      background-color: #0f0;
      color: #000;
      display: flex;
      justify-content: center;
      align-items: center;
      font-size: 20px;
      font-weight: bold;
      border-radius: 50%;
    }
    .pin-placeholder {
      width: 40px;
      height: 40px;
      background-color: #333;
      border: 2px dashed #0f0;
      border-radius: 50%;
    }
    #next-phase-button {
      margin-top: 20px;
      background-color: #000;
      color: #0f0;
      border: 2px solid #0f0;
      padding: 10px 20px;
      font-size: 18px;
      cursor: pointer;
      transition: all 0.3s;
      display: none;
    }
    #next-phase-button:hover {
      background-color: #0f0;
      color: #000;
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
    <div id="result"></div>
    
    <div id="collected-pins">
      <h3>COLLECTED ACCESS PINS</h3>
      <div id="pin-display">
        <div class="pin-placeholder" id="pin-slot-0"></div>
        <div class="pin-placeholder" id="pin-slot-1"></div>
        <div class="pin-placeholder" id="pin-slot-2"></div>
      </div>
      <p>Solve riddles to collect all 3 access pins</p>
    </div>
    
    <button id="next-phase-button" onclick="proceedToMastermind()">PROCEED TO FINAL PHASE</button>
    <button onclick="resetPins()" style="margin-top: 10px;">RESET PINS</button>
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
        fetchRiddle();
        updatePinDisplay();
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
                
                // Update pins from server response
                collectedPins = data.pins;
                updatePinDisplay();
            })
            .catch(error => {
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
        .then(response => response.json())
        .then(data => {
            document.getElementById('result').textContent = data.correct ? "+++ ACCESS GRANTED +++" : "!!! INTRUDER ALERT !!!";
            
            if (data.correct) {
                if (data.pinAdded) {
                    document.getElementById('result').textContent += " | NEW ACCESS PIN ACQUIRED!";
                } else {
                    document.getElementById('result').textContent += " | ALL ACCESS PINS COLLECTED!";
                }
                
                // Update pins display
                collectedPins = data.pins;
                updatePinDisplay();
            }
        });
    }
    
    function updatePinDisplay() {
        // Reset all pin slots to placeholders
        for (let i = 0; i < 3; i++) {
            const slot = document.getElementById(`pin-slot-${i}`);
            if (i < collectedPins.length) {
                // Display collected pin
                slot.className = "pin";
                slot.textContent = collectedPins[i];
            } else {
                // Display placeholder
                slot.className = "pin-placeholder";
                slot.textContent = "";
            }
        }
        
        // Show next phase button if all pins are collected
        document.getElementById('next-phase-button').style.display = 
            collectedPins.length >= 3 ? 'block' : 'none';
    }
    
    function proceedToMastermind() {
        window.location.href = "?action=mastermind";
    }
    
    function resetPins() {
        fetch("?action=reset-pins", {
            method: "POST"
        })
        .then(response => response.json())
        .then(data => {
            collectedPins = [];
            updatePinDisplay();
            document.getElementById('result').textContent = "ACCESS PINS RESET";
        });
    }
  </script>

</body>
</html>
<?php
}

function displayMastermindGame() {
    // Get pins from session
    $pins = isset($_SESSION['collected_pins']) ? $_SESSION['collected_pins'] : [];
    $secretCode = implode('', $pins);
    
    // If no pins collected, redirect back to riddle page
    if (count($pins) < 3) {
        header('Location: ?');
        exit;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>final-phase</title>
    <link rel="stylesheet" href="mastermind.css">   
</head>
<body>
   <div id="riddle-container">
    <h1>UNLOCK THE FINAL CODE</h1>
    <p>Enter the pins in the correct sequence or be locked in forever</p>
    <p>Your collected pins: 
        <?php foreach($pins as $pin): ?>
            <span style="display: inline-block; padding: 5px 10px; background: #0f0; color: #000; margin: 0 5px; border-radius: 50%;"><?php echo $pin; ?></span>
        <?php endforeach; ?>
    </p>
    <p>You must determine the correct order!</p>
   </div>
   
   <div id="guess-container">
     <div id="guess-inputs"></div>
     <button onclick="submitGuess()">SUBMIT GUESS</button>
   </div>
   
   <div id="result"></div>
   
   <script>
    const secretCode = "<?php echo $secretCode; ?>";
    const availableDigits = [<?php echo implode(',', $pins); ?>];
    let attempts = 0;
    let maxAttempts = 5;
    const guessInputContainer = document.getElementById('guess-inputs');
    const resultDiv = document.getElementById('result');

    function generateInputBoxes(length) {
        guessInputContainer.innerHTML = '';
        for (let i = 0; i < length; i++) {
            let input = document.createElement('input');
            input.type = 'text';
            input.maxLength = 1;
            input.id = `digit${i}`;
            input.classList.add('digit-input');
            input.inputMode = "numeric";
            guessInputContainer.appendChild(input);
        }
        setupInputListeners();
    }

    function setupInputListeners() {
        const inputs = document.querySelectorAll('.digit-input');
        inputs.forEach((input, index) => {
            input.addEventListener('input', () => {
                input.value = input.value.replace(/\D/g, '');
                // Validate that only collected pins can be used
                if (input.value && !availableDigits.includes(parseInt(input.value))) {
                    input.value = '';
                    return;
                }
                if (input.value.length === 1 && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
            });
            input.addEventListener('keydown', (event) => {
                if (event.key === 'Backspace' && input.value === '' && index > 0) {
                    inputs[index - 1].focus();
                }
            });
        });
    }

    function submitGuess() {
        let guess = "";
        for (let i = 0; i < secretCode.length; i++) {
            guess += document.getElementById(`digit${i}`).value || '';
        }
        if (guess.length !== secretCode.length) {
            resultDiv.textContent = `ENTER A ${secretCode.length}-DIGIT CODE`;
            return;
        }
        
        const feedback = checkGuess(guess, secretCode);
        const isCorrect = updateInputFeedback(feedback, guess);
        attempts++;
        
        if (isCorrect) {
            resultDiv.innerHTML = 'ACCESS GRANTED! CODE UNLOCKED!<br><button onclick="window.location.href=\'game-success.php\'" style="margin-top:15px;">CONTINUE</button>';
        } else if (attempts >= maxAttempts) {
            resultDiv.innerHTML = `GAME OVER! THE CODE WAS: ${secretCode}<br><button onclick="window.location.href=\'?\'" style="margin-top:15px;">RESTART</button>`;
        } else {
            resultDiv.textContent = `ATTEMPT ${attempts} of ${maxAttempts}`;
        }
    }

    function checkGuess(guess, code) {
        return Array.from(guess).map((digit, index) => digit === code[index] ? 'green' : code.includes(digit) ? 'yellow' : 'gray');
    }

    function updateInputFeedback(feedback, guess) {
        const allCorrect = feedback.every(f => f === 'green');
        feedback.forEach((status, index) => {
            const input = document.getElementById(`digit${index}`);
            input.classList.remove('green', 'yellow', 'gray');
            input.classList.add(status);
            input.value = guess[index];
            input.disabled = allCorrect;
        });
        return allCorrect;
    }

    generateInputBoxes(secretCode.length);
   </script>
</body>
</html>
<?php
}
?>