<div class="arcade-instructions" style="margin-top: 30px; text-align: center;">
          <div style="display: inline-block; padding: 10px 20px; background-color: #001100; border: 2px dashed #0f0;">
            <h3 style="margin-top: 5px;">HOW TO PLAY</h3>
            <ul style="text-align: left; list-style-type: none; padding-left: 10px;">
              <li style="margin: 5px 0;">🎮 <span style="color: #0f0;">GREEN</span> = Correct pin in correct position</li>
              <li style="margin: 5px 0;">🎮 <span style="color: #ff0;">YELLOW</span> = Correct pin in wrong position</li>
              <li style="margin: 5px 0;">🎮 <span style="color: #666;">GRAY</span> = Pin not in the code</li>
            </ul>
          </div>
        </div><?php
session_start();

// Initialize pins array if it doesn't exist
if (!isset($_SESSION['collected_pins'])) {
    $_SESSION['collected_pins'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle the riddle guess submission
    handleGuessSubmission();
} else if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'new-riddle':
            fetchRandomRiddle();
            break;
        case 'reset-pins':
            // Reset pins when requested
            $_SESSION['collected_pins'] = [];
            // Redirect back to the main page instead of JSON response
            header('Location: ?');
            exit;
            break;
        case 'mastermind':
            displayMastermindGame();
            break;
        default:
            displayRiddlePage();
    }
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
    // Database connection details
    $servername = "localhost";
    $username = "root";
    $password = "Mudzo2608";
    $dbname = "80s_video_store";
    
    // Hardcoded riddles as fallback
    $hardcodedRiddles = [
        ['question' => 'What has keys but no locks, space but no room, and you can enter but not go in?', 'answer' => 'keyboard'],
        ['question' => 'I speak without a mouth and hear without ears. I have no body, but I come alive with wind. What am I?', 'answer' => 'echo'],
        ['question' => 'The more you take, the more you leave behind. What am I?', 'answer' => 'footsteps'],
        ['question' => 'What gets wetter as it dries?', 'answer' => 'towel'],
        ['question' => 'What has a head, a tail, but no body?', 'answer' => 'coin'],
        ['question' => 'I\'m tall when I\'m young, and I\'m short when I\'m old. What am I?', 'answer' => 'candle'],
        ['question' => 'What can you break, even if you never pick it up or touch it?', 'answer' => 'promise'],
        ['question' => 'What has many keys but can\'t open a single lock?', 'answer' => 'piano'],
        ['question' => 'What can you hold in your right hand, but never in your left hand?', 'answer' => 'left hand'],
        ['question' => 'What goes up but never comes down?', 'answer' => 'age']
    ];
    
    // First try to get a riddle from the database
    try {
        // Set a short timeout for the database connection
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 3, // 3 seconds timeout
        ];
        
        $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password, $options);
        
        $query = "SELECT question, answer FROM riddles ORDER BY RAND() LIMIT 1";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        
        $riddle = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($riddle) {
            // Successfully got a riddle from the database
            $trimmedAnswer = trim($riddle['answer']);
            $_SESSION['correct_answer'] = $trimmedAnswer;
            $_SESSION['riddle_source'] = 'database';
            
            header('Content-Type: application/json');
            echo json_encode([
                'riddle' => $riddle['question'],
                'answerLength' => strlen($trimmedAnswer),
                'pins' => $_SESSION['collected_pins'],
                'source' => 'database'
            ]);
            exit;
        }
    } catch (PDOException $e) {
        // Database error - log it but continue to fallback
        error_log("Database error: " . $e->getMessage());
        // We'll fall through to use hardcoded riddles
    }
    
    // If we get here, either the database connection failed or no riddles were found
    // Use a hardcoded riddle instead
    $randomIndex = array_rand($hardcodedRiddles);
    $riddle = $hardcodedRiddles[$randomIndex];
    
    $trimmedAnswer = trim($riddle['answer']);
    $_SESSION['correct_answer'] = $trimmedAnswer;
    $_SESSION['riddle_source'] = 'hardcoded';
    
    header('Content-Type: application/json');
    echo json_encode([
        'riddle' => $riddle['question'],
        'answerLength' => strlen($trimmedAnswer),
        'pins' => $_SESSION['collected_pins'],
        'source' => 'fallback'
    ]);
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
    .game-controls {
      margin-top: 20px;
      display: flex;
      justify-content: center;
      gap: 10px;
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
    
    <div id="result"></div>
    
    <div id="collected-pins">
      <h3>COLLECTED ACCESS PINS</h3>
      <div id="pin-display">
        <div class="pin" id="pin-slot-0"></div>
        <div class="pin" id="pin-slot-1"></div>
        <div class="pin" id="pin-slot-2"></div>
      </div>
      <p>Solve riddles to collect all 3 access pins</p>
      <p><em>Remember: the order of collection matters!</em></p>
    </div>
    
    <div class="game-controls">
      <button id="next-phase-button" onclick="proceedToMastermind()">PROCEED TO FINAL PHASE</button>
      <button onclick="loadNewRiddle()">NEW RIDDLE</button>
      <button onclick="resetPins()">RESET PINS</button>
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
                guessInput.focus();
                
                // Update pins from server response
                collectedPins = data.pins;
                updatePinDisplay();
            })
            .catch(error => {
                document.getElementById('riddle-text').textContent = 'SYSTEM MALFUNCTION - RETRY';
                console.error(error);
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
                    
                    // Show a quick flash of the pin value that was just collected
                    const pinValue = data.pins[data.pins.length - 1];
                    const flashMessage = document.createElement('div');
                    flashMessage.textContent = `PIN COLLECTED: ${pinValue}`;
                    flashMessage.style.position = 'fixed';
                    flashMessage.style.top = '50%';
                    flashMessage.style.left = '50%';
                    flashMessage.style.transform = 'translate(-50%, -50%)';
                    flashMessage.style.backgroundColor = '#0f0';
                    flashMessage.style.color = '#000';
                    flashMessage.style.padding = '20px';
                    flashMessage.style.borderRadius = '5px';
                    flashMessage.style.fontWeight = 'bold';
                    flashMessage.style.fontSize = '24px';
                    flashMessage.style.zIndex = '1000';
                    document.body.appendChild(flashMessage);
                    
                    // Remove the flash after a brief moment
                    setTimeout(() => {
                        document.body.removeChild(flashMessage);
                        // Load new riddle after showing the flash
                        loadNewRiddle();
                    }, 1000);
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
        // Update pin display without revealing their order - just show which pins were collected
        for (let i = 0; i < 3; i++) {
            const slot = document.getElementById(`pin-slot-${i}`);
            if (i < collectedPins.length) {
                // Show that a pin is collected, but don't reveal the value
                slot.className = "pin";
                // Show a ? or a * instead of the actual pin value
                slot.textContent = "*";
                slot.style.backgroundColor = "#0f0";
            } else {
                // Show placeholder for uncollected pins
                slot.className = "pin";
                slot.textContent = "?";
                slot.style.backgroundColor = "#444";
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
        if (confirm("Are you sure you want to reset your collected pins?")) {
            window.location.href = "?action=reset-pins";
        }
    }
    
    // Event listener for Enter key in input field
    document.getElementById('guess-input').addEventListener('keydown', function(event) {
        if (event.key === 'Enter') {
            submitGuess();
        }
    });
    
    // Event listener for Enter key in password field
    document.getElementById('password-input').addEventListener('keydown', function(event) {
        if (event.key === 'Enter') {
            checkPasscode();
        }
    });
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
    <title>Final Phase - Code Cracker</title>
    <link rel="stylesheet" href="mastermind.css">   
</head>
<body>
   <div id="riddle-container">
    <h1>UNLOCK THE FINAL CODE</h1>
    <div class="crt-scan"></div>
    <p>Enter the pins in the correct sequence or be locked in forever</p>
    <p>Your access pins have been collected. Now you must remember their order!</p>
    <p>Available pins to use: 
        <?php 
        // Sort pins to avoid giving away the order
        $displayPins = $pins;
        sort($displayPins);
        foreach($displayPins as $pin): 
        ?>
            <span style="display: inline-block; padding: 5px 10px; background: #0f0; color: #000; margin: 0 5px; border-radius: 50%;"><?php echo $pin; ?></span>
        <?php endforeach; ?>
    </p>
    <p>Arrange these pins in the correct sequence to unlock the system.</p>
   </div>
   
   <style>
     /* Add arcade machine styling */
     .crt-scan {
       position: absolute;
       top: 0;
       left: 0;
       width: 100%;
       height: 10px;
       background: rgba(0, 255, 0, 0.1);
       z-index: 999;
       pointer-events: none;
       animation: crt-scan 8s linear infinite;
     }
     
     @keyframes crt-scan {
       0% { transform: translateY(-100%); }
       100% { transform: translateY(1000%); }
     }
     
     @keyframes flicker {
       0% { opacity: 0.97; }
       5% { opacity: 0.9; }
       10% { opacity: 0.97; }
       15% { opacity: 1; }
       50% { opacity: 0.98; }
       95% { opacity: 0.9; }
       100% { opacity: 0.98; }
     }
     
     body {
       animation: flicker 5s infinite;
     }
     
     input, button {
       position: relative;
       z-index: 1;
     }
     
     /* Make inputs appear more like an arcade */
     #guess-inputs {
       display: flex;
       justify-content: center;
       margin: 20px 0;
     }
   </style>
   
   <div id="guess-container">
     <div id="guess-inputs"></div>
     <button id="submit-button" onclick="submitGuess()">SUBMIT GUESS</button>
   </div>
   
   <div id="result"></div>
   
   <div id="attempts-history" style="margin-top: 20px; border-top: 2px dashed #0f0; padding-top: 15px; display: none;">
     <h3>PREVIOUS ATTEMPTS</h3>
     <div id="history-container"></div>
   </div>
   
   <div style="margin-top: 20px;">
     <button onclick="window.location.href='?'" style="background: #111; color: #0f0; border: 1px solid #0f0; padding: 5px 10px;">RETURN TO RIDDLES</button>
   </div>
   
   <script>
    const secretCode = "<?php echo $secretCode; ?>";
    const availableDigits = [<?php echo implode(',', $pins); ?>];
    let attempts = 0;
    let maxAttempts = 5;
    let attemptHistory = [];
    const guessInputContainer = document.getElementById('guess-inputs');
    const resultDiv = document.getElementById('result');
    const historyContainer = document.getElementById('history-container');
    const attemptsHistory = document.getElementById('attempts-history');

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
                } else if (event.key === 'Enter') {
                    submitGuess();
                }
            });
        });
        
        // Focus on first input when game starts
        if (inputs.length > 0) {
            inputs[0].focus();
        }
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
        
        // Add to history
        attemptHistory.push({ guess, feedback });
        updateHistory();
        
        if (isCorrect) {
            resultDiv.innerHTML = '<span style="color: #0f0; font-size: 1.2em; font-weight: bold;">ACCESS GRANTED! CODE UNLOCKED!</span>';
            document.getElementById('submit-button').disabled = true;
            
            // You mentioned you'll create the success page separately
            setTimeout(() => {
                window.location.href = 'success.php'; // Point this to your success page
            }, 2000);
        } else if (attempts >= maxAttempts) {
            resultDiv.innerHTML = `<span style="color: #f00;">GAME OVER! THE CODE WAS: ${secretCode}</span><br>
            <button onclick="window.location.href='?'" style="margin-top:15px; background: #300; color: #f88; border: 1px solid #f00;">RESTART</button>`;
            disableAllInputs();
        } else {
            resultDiv.textContent = `ATTEMPT ${attempts} of ${maxAttempts}`;
            clearInputs();
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
        });
        return allCorrect;
    }
    
    function clearInputs() {
        for (let i = 0; i < secretCode.length; i++) {
            const input = document.getElementById(`digit${i}`);
            input.value = '';
            input.classList.remove('green', 'yellow', 'gray');
        }
        document.getElementById(`digit0`).focus();
    }
    
    function disableAllInputs() {
        const inputs = document.querySelectorAll('.digit-input');
        inputs.forEach(input => {
            input.disabled = true;
        });
        document.getElementById('submit-button').disabled = true;
    }
    
    function updateHistory() {
        if (attemptHistory.length > 0) {
            attemptsHistory.style.display = 'block';
            
            historyContainer.innerHTML = '';
            attemptHistory.forEach((attempt, attemptIndex) => {
                const historyRow = document.createElement('div');
                historyRow.style.margin = '5px 0';
                historyRow.style.display = 'flex';
                historyRow.style.alignItems = 'center';
                historyRow.style.justifyContent = 'center';
                
                // Add attempt number
                const attemptNumber = document.createElement('div');
                attemptNumber.textContent = `#${attemptIndex + 1}:`;
                attemptNumber.style.marginRight = '10px';
                historyRow.appendChild(attemptNumber);
                
                // Add digits with feedback colors
                attempt.guess.split('').forEach((digit, i) => {
                    const digitSpan = document.createElement('span');
                    digitSpan.textContent = digit;
                    digitSpan.style.display = 'inline-block';
                    digitSpan.style.width = '30px';
                    digitSpan.style.height = '30px';
                    digitSpan.style.lineHeight = '30px';
                    digitSpan.style.textAlign = 'center';
                    digitSpan.style.margin = '0 5px';
                    digitSpan.style.borderRadius = '4px';
                    
                    // Apply color based on feedback
                    switch (attempt.feedback[i]) {
                        case 'green':
                            digitSpan.style.backgroundColor = '#0f0';
                            digitSpan.style.color = '#000';
                            break;
                        case 'yellow':
                            digitSpan.style.backgroundColor = '#ff0';
                            digitSpan.style.color = '#000';
                            break;
                        case 'gray':
                            digitSpan.style.backgroundColor = '#333';
                            digitSpan.style.color = '#0f0';
                            break;
                    }
                    
                    historyRow.appendChild(digitSpan);
                });
                
                historyContainer.appendChild(historyRow);
            });
        }
    }

    generateInputBoxes(secretCode.length);
   </script>
</body>
</html>
<?php
}
?>