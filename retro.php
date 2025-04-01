<?php
session_start();
// Initialize pins array if it doesn't exist
if (!isset($_SESSION['collected_pins'])) {
    $_SESSION['collected_pins'] = [];
}

// Initialize current category if it doesn't exist
if (!isset($_SESSION['current_category'])) {
    $_SESSION['current_category'] = 1; // Default to Movies (ID: 1)
}

// Check if we need to skip the password screen (category change)
$skipPasswordScreen = false;
if (isset($_GET['keep_session']) && $_GET['keep_session'] == '1') {
    $skipPasswordScreen = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['category'])) {
        // Save the current game state
        $maintainSession = isset($_POST['maintain_session']) && $_POST['maintain_session'] === 'true';
        
        // Handle category selection via POST
        $_SESSION['current_category'] = (int)$_POST['category'];
        
        // Only redirect if this is a maintenance of existing session
        if ($maintainSession) {
            header('Location: ?keep_session=1');
            exit;
        } else {
            header('Location: ?');
            exit;
        }
    } else {
        // Handle the riddle guess submission
        handleGuessSubmission();
    }
} else if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'new-riddle':
            fetchRandomRiddle();
            break;
        case 'get-categories':
            fetchCategories();
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
        case 'reset-game':
            // Destroy the session to completely reset the game
            session_destroy();
            // Start a new session
            session_start();
            $_SESSION['collected_pins'] = [];
            // Redirect back to the main page
            header('Location: ?');
            exit;
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
        if (count($_SESSION['collected_pins']) < 4) {
            $_SESSION['collected_pins'][] = $pin;
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'correct' => $isCorrect,
        'pins' => $_SESSION['collected_pins'],
        'pinAdded' => $isCorrect && count($_SESSION['collected_pins']) <= 4
    ]);
    exit;
}

function fetchCategories() {
    // Hardcoded categories based on the image provided
    $categories = [
        1 => 'Movies',
        2 => 'Sports Trivia',
        3 => 'Games',
        4 => 'Coding Knowledge'
    ];
    
    header('Content-Type: application/json');
    echo json_encode([
        'categories' => $categories,
        'currentCategory' => $_SESSION['current_category']
    ]);
    exit;
}

function fetchRandomRiddle() {
    $categoryId = (int)$_SESSION['current_category'];
    
    try {
        $dbConfig = getDbConfig();
        $pdo = new PDO("mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset=utf8", 
                       $dbConfig['username'], 
                       $dbConfig['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Try to get riddle from selected category
        $query = "SELECT question, answer FROM riddles WHERE category_id = :category_id ORDER BY RAND() LIMIT 1";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':category_id', $categoryId);
        $stmt->execute();
        
        $riddle = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$riddle) {
            // If no riddle found in selected category, try general fallback
            $query = "SELECT question, answer FROM riddles ORDER BY RAND() LIMIT 1";
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            $riddle = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // If still no riddle found, use hardcoded fallback
            if (!$riddle) {
                $riddle = [
                    'question' => 'What has keys but no locks, space but no room, and you can enter but not go in?',
                    'answer' => 'keyboard'
                ];
            }
        }
    } catch (PDOException $e) {
        // Database error - use hardcoded fallback
        $riddle = [
            'question' => 'What has keys but no locks, space but no room, and you can enter but not go in?',
            'answer' => 'keyboard'
        ];
    }
    
    $trimmedAnswer = trim($riddle['answer']);
    $_SESSION['correct_answer'] = $trimmedAnswer;
    
    header('Content-Type: application/json');
    echo json_encode([
        'riddle' => $riddle['question'],
        'answerLength' => strlen($trimmedAnswer),
        'pins' => $_SESSION['collected_pins'],
        'category' => $categoryId
    ]);
    exit;
}

// Database configuration function to centralize credentials
function getDbConfig() {
    return [
        'host' => 'localhost',
        'dbname' => '80s_video_store',
        'username' => 'root',
        'password' => 'Mudzo2608'
    ];
}

function displayRiddlePage() {
    global $skipPasswordScreen; // Access the global variable
    
    // Hardcoded category names based on the database structure
    $categories = [
        1 => ['id' => 1, 'name' => 'Movies', 'description' => '80s and 90s movie riddles'],
        2 => ['id' => 2, 'name' => 'Sports Trivia', 'description' => 'Riddles about various sports'],
        3 => ['id' => 3, 'name' => 'Games', 'description' => 'Video game and board game riddles'],
        4 => ['id' => 4, 'name' => 'Coding Knowledge', 'description' => 'Riddles about programming, databases, and APIs']
    ];
    
    $currentCategoryId = isset($_SESSION['current_category']) ? $_SESSION['current_category'] : 1;
    $currentCategory = isset($categories[$currentCategoryId]) ? $categories[$currentCategoryId] : $categories[1];
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
    
    /* Styling for letter inputs similar to mastermind */
    #answer-inputs {
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
      margin: 15px 0;
      gap: 8px;
    }
    
    .letter-input {
      background: #000;
      border: 2px solid #0f0;
      color: #0f0;
      padding: 8px;
      font-family: 'Courier New', monospace;
      font-size: 18px;
      width: 30px;
      height: 30px;
      text-align: center;
      border-radius: 4px;
      text-transform: lowercase;
      transition: all 0.3s ease;
    }
    
    .letter-input:focus {
      outline: none;
      border-color: #0f0;
      box-shadow: 0 0 8px rgba(0, 255, 0, 0.5);
      transform: scale(1.05);
    }
    
    /* CRT scan effect */
    @keyframes crt-scan {
      0% { transform: translateY(-100%); }
      100% { transform: translateY(1000%); }
    }
    
    .crt-scan {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 5px;
      background: rgba(0, 255, 0, 0.1);
      z-index: 999;
      pointer-events: none;
      animation: crt-scan 8s linear infinite;
    }
    
    /* Flash effect for acquired pin */
    @keyframes flash-notification {
      0% { opacity: 0; transform: scale(0.8); }
      10% { opacity: 1; transform: scale(1.1); }
      90% { opacity: 1; transform: scale(1.1); }
      100% { opacity: 0; transform: scale(0.8); }
    }
    
    .pin-notification {
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%) scale(1);
      background: #001800;
      border: 3px solid #0f0;
      color: #0f0;
      padding: 20px;
      font-size: 24px;
      text-align: center;
      border-radius: 10px;
      z-index: 1000;
      animation: flash-notification 2s forwards;
      display: none;
    }
    
    /* Category selector styling */
    .category-selector {
      display: flex;
      justify-content: center;
      gap: 10px;
      margin-bottom: 20px;
    }
    
    .category-option {
      background: #000;
      color: #0f0;
      border: 2px solid #0f0;
      padding: 8px 12px;
      font-family: 'Courier New', monospace;
      font-size: 16px;
      cursor: pointer;
      transition: all 0.2s;
    }
    
    .category-option:hover {
      background: #001800;
    }
    
    .category-option.active {
      background: #0f0;
      color: #000;
      font-weight: bold;
    }
    
    .category-info {
      text-align: center;
      margin-bottom: 20px;
      color: #0f0;
    }
    
    .category-description {
      font-style: italic;
      font-size: 14px;
      opacity: 0.8;
    }
  </style>
</head>
<body>
  <div class="crt-scan"></div>
  <div class="pin-notification" id="pin-notification"></div>

  <div id="password-screen" style="<?php echo $skipPasswordScreen ? 'display:none;' : ''; ?>">
    <h2>ENTER ACCESS CODE</h2>
    <input type="password" id="password-input" placeholder="ACCESS CODE">
    <button onclick="checkPasscode()">ENTER</button>
    <div id="password-error"></div>
  </div>

  <div id="game-container" style="<?php echo $skipPasswordScreen ? 'display:block;' : 'display:none;'; ?>">
    <div class="category-selector">
      <?php foreach ($categories as $category): ?>
        <button 
          class="category-option <?php echo ($category['id'] == $currentCategoryId) ? 'active' : ''; ?>" 
          onclick="changeCategory(<?php echo $category['id']; ?>)">
          <?php echo $category['id'] . '. ' . $category['name']; ?>
        </button>
      <?php endforeach; ?>
    </div>
    
    <div class="category-info">
      <div class="category-description">
        <?php echo $currentCategory['description']; ?>
      </div>
    </div>
    
    <div id="riddle-container">
      <h2 id="riddle-text">INITIALIZING RIDDLE MODULE...</h2>
      <div class="answer-length" id="answer-length"></div>
    </div>
    
    <div id="guess-container">
      <div id="answer-inputs"></div>
      <button onclick="submitGuess()">EXECUTE GUESS</button>
    </div>
    
    <div id="result"></div>
    
    <div id="collected-pins">
      <h3>COLLECTED ACCESS PINS</h3>
      <div id="pin-display">
        <div class="pin-placeholder" id="pin-slot-0"></div>
        <div class="pin-placeholder" id="pin-slot-1"></div>
        <div class="pin-placeholder" id="pin-slot-2"></div>
        <div class="pin-placeholder" id="pin-slot-3"></div>
      </div>
      <p>Solve riddles to collect all 4 access pins then place them in the correct order to unlock the next game</p>
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
    let currentCategory = <?php echo $currentCategoryId; ?>;
    
    // Hardcoded category details (matching PHP)
    const categories = {
      1: { id: 1, name: 'Movies', description: '80s and 90s movie riddles' },
      2: { id: 2, name: 'Sports Trivia', description: 'Riddles about various sports' },
      3: { id: 3, name: 'Games', description: 'Video game and board game riddles' },
      4: { id: 4, name: 'Coding Knowledge', description: 'Riddles about programming, databases, and APIs' }
    };

    // Initialize the game if we're skipping the password screen
    document.addEventListener('DOMContentLoaded', function() {
      if (<?php echo $skipPasswordScreen ? 'true' : 'false'; ?>) {
        startGame();
      }
    });

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
    
    function changeCategory(categoryId) {
      // First check if we're already on this category
      if (currentCategory === categoryId) {
        return; // Don't do anything if we're already on this category
      }
      
      // Create and submit a form to change category without affecting the session
      const form = document.createElement('form');
      form.method = 'POST';
      form.style.display = 'none';
      
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'category';
      input.value = categoryId;
      
      // Add a flag to indicate this is just a category change, not a full session reset
      const flagInput = document.createElement('input');
      flagInput.type = 'hidden';
      flagInput.name = 'maintain_session';
      flagInput.value = 'true';
      
      form.appendChild(input);
      form.appendChild(flagInput);
      document.body.appendChild(form);
      form.submit();
    }
    
    // Create letter input boxes for the riddle answer
    function createLetterInputs(length) {
      const inputContainer = document.getElementById('answer-inputs');
      inputContainer.innerHTML = '';
      
      for (let i = 0; i < length; i++) {
        const input = document.createElement('input');
        input.type = 'text';
        input.maxLength = 1;
        input.className = 'letter-input';
        input.id = `letter-${i}`;
        input.dataset.index = i;
        inputContainer.appendChild(input);
        
        // Add event listeners for keyboard navigation
        input.addEventListener('input', function(e) {
          // Move to next input when a character is entered
          if (input.value.length === 1 && i < length - 1) {
            document.getElementById(`letter-${i + 1}`).focus();
          }
        });
        
        input.addEventListener('keydown', function(e) {
          // Handle backspace (move to previous input)
          if (e.key === 'Backspace' && input.value === '' && i > 0) {
            document.getElementById(`letter-${i - 1}`).focus();
          }
          // Handle Enter key (submit guess)
          else if (e.key === 'Enter') {
            submitGuess();
          }
          // Handle arrow keys
          else if (e.key === 'ArrowRight' && i < length - 1) {
            document.getElementById(`letter-${i + 1}`).focus();
          }
          else if (e.key === 'ArrowLeft' && i > 0) {
            document.getElementById(`letter-${i - 1}`).focus();
          }
        });
      }
      
      // Focus the first input
      if (length > 0) {
        document.getElementById('letter-0').focus();
      }
    }

    function fetchRiddle() {
      fetch("?action=new-riddle")
        .then(response => response.json())
        .then(data => {
          document.getElementById('riddle-text').textContent = data.riddle;
          answerLength = data.answerLength;
          currentCategory = data.category;
          
          // Update active category in UI
          document.querySelectorAll('.category-option').forEach(btn => {
            if (parseInt(btn.getAttribute('onclick').match(/\d+/)[0]) === currentCategory) {
              btn.classList.add('active');
            } else {
              btn.classList.remove('active');
            }
          });
          
          // Create the letter inputs based on answer length
          createLetterInputs(answerLength);
          
          document.getElementById('answer-length').textContent = `[REQUIRED LENGTH: ${answerLength}]`;
          document.getElementById('result').textContent = '';
          
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
      // Collect letters from all inputs
      let guess = '';
      for (let i = 0; i < answerLength; i++) {
        const input = document.getElementById(`letter-${i}`);
        guess += input.value || '';
      }
      
      // Check if all fields are filled
      if (guess.length !== answerLength) {
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
          // Show correct answer in green
          for (let i = 0; i < answerLength; i++) {
            const input = document.getElementById(`letter-${i}`);
            input.style.backgroundColor = "#0f0";
            input.style.color = "#000";
            input.disabled = true;
          }
          
          if (data.pinAdded) {
            document.getElementById('result').textContent += " | NEW ACCESS PIN ACQUIRED!";
            
            // Flash notification with pin
            const pinValue = data.pins[data.pins.length - 1];
            const notification = document.getElementById('pin-notification');
            notification.textContent = `ACCESS PIN ACQUIRED: ${pinValue}`;
            notification.style.display = 'block';
            
            // Hide notification and load new riddle after delay
            setTimeout(() => {
              notification.style.display = 'none';
              loadNewRiddle();
            }, 2000);
          } else {
            document.getElementById('result').textContent += " | ALL ACCESS PINS COLLECTED!";
          }
          
          // Update pins display
          collectedPins = data.pins;
          updatePinDisplay();
          // Check if all pins are collected and triggers pop up to give user game flow direction 
          if(collectedPins.length>=4{
            const notification = document.getElementById('pin-notification');
            notification.textContent = `ALL PINS COLLECTED! PROCEED TO FINAL PHASE TIME IS RUNNING OUT !`;
            notification.style.display = 'block';
            // Hide notification and redirect to mastermind after delay
            setTimeout(() => {
             window.location.ref = "?action=mastermind";
            }, 3000);
          })
        } else {
          // Shake inputs to indicate incorrect answer
          const inputs = document.querySelectorAll('.letter-input');
          inputs.forEach(input => {
            input.style.backgroundColor = "#300";
            setTimeout(() => {
              input.style.backgroundColor = "#000";
            }, 500);
          });
        }
      });
    }
    
    function updatePinDisplay() {
      // Reset all pin slots to placeholders
      for (let i = 0; i < 4; i++) {
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
        collectedPins.length >= 4 ? 'block' : 'none';
    }
    
    function proceedToMastermind() {
      window.location.href = "?action=mastermind";
    }
    
    function resetPins() {
      if (confirm("Are you sure you want to reset your collected pins?")) {
        window.location.href = "?action=reset-pins";
      }
    }
    
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
    if (count($pins) < 4) {
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
      
      /* Arcade machine styling for digit inputs */
      .digit-input {
        background: #000;
        border: 3px solid #0f0;
        color: #0f0;
        padding: 12px;
        font-family: 'Courier New', monospace;
        font-size: 20px;
        width: 45px;
        height: 45px;
        margin: 0 8px;
        text-align: center;
        text-shadow: 0 0 5px #0f0;
        caret-color: #0f0;
        box-shadow: 0 0 10px rgba(0, 255, 0, 0.2);
        transition: all 0.3s ease;
      }
      
      .digit-input:focus {
        outline: none;
        box-shadow: 0 0 15px rgba(0, 255, 0, 0.6);
        transform: scale(1.1);
      }
    </style>
</head>
<body>
   <div class="crt-scan"></div>
   
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
   
   <div id="guess-container">
     <div id="guess-inputs"></div>
     <button id="submit-button" onclick="submitGuess()">SUBMIT GUESS</button>
   </div>
   
   <div id="result"></div>
   
   <div id="attempts-history" style="margin-top: 20px; border-top: 2px dashed #0f0; padding-top: 15px; display: none;">
     <h3>PREVIOUS ATTEMPTS</h3>
     <div id="history-container"></div>
   </div>
   
   <div class="arcade-instructions" style="margin-top: 30px; text-align: center;">
     <div style="display: inline-block; padding: 10px 20px; background-color: #001100; border: 2px dashed #0f0;">
       <h3 style="margin-top: 5px;">HOW TO PLAY</h3>
       <ul style="text-align: left; list-style-type: none; padding-left: 10px;">
         <li style="margin: 5px 0;">🎮 <span style="color: #0f0;">GREEN</span> = Correct pin in correct position</li>
         <li style="margin: 5px 0;">🎮 <span style="color: #ff0;">YELLOW</span> = Correct pin in wrong position</li>
         <li style="margin: 5px 0;">🎮 <span style="color: #666;">GRAY</span> = Pin not in the code</li>
       </ul>
     </div>
   </div>
   
   <div style="margin-top: 20px;">
     <button onclick="window.location.href='?action=reset-game'" style="background: #111; color: #0f0; border: 1px solid #0f0; padding: 5px 10px;">RESET GAME</button>
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
                } else if (event.key === 'ArrowRight' && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                } else if (event.key === 'ArrowLeft' && index > 0) {
                    inputs[index - 1].focus();
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
        
        // Show success screen instead of json response
        if (isCorrect) {
            resultDiv.innerHTML = '<span style="color: #0f0; font-size: 1.2em; font-weight: bold;">ACCESS GRANTED! CODE UNLOCKED!</span>';
            document.getElementById('submit-button').disabled = true;
            
            // Add play again button that resets session
            const playAgainButton = document.createElement('button');
            playAgainButton.textContent = 'PLAY AGAIN';
            playAgainButton.style.margin = '15px';
            playAgainButton.style.background = '#002200';
            playAgainButton.style.color = '#0f0';
            playAgainButton.style.border = '2px solid #0f0';
            playAgainButton.style.padding = '10px 20px';
            playAgainButton.onclick = function() {
                window.location.href = '?action=reset-game';
            };
            resultDiv.appendChild(document.createElement('br'));
            resultDiv.appendChild(playAgainButton);
            
            // No redirect - let the player choose to play again
        } else if (attempts >= maxAttempts) {
            resultDiv.innerHTML = `<span style="color: #f00;">GAME OVER! THE CODE WAS: ${secretCode}</span><br>
            <button onclick="window.location.href='?action=reset-game'" style="margin-top:15px; background: #300; color: #f88; border: 1px solid #f00;">PLAY AGAIN</button>`;
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

// Add a function for success.php that includes a reset button
function createSuccessPage() {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Granted</title>
    <style>
        body {
            background-color: #000;
            color: #0f0;
            font-family: 'Courier New', monospace;
            text-align: center;
            padding: 50px;
            overflow: hidden;
        }
        
        h1 {
            font-size: 3em;
            margin-bottom: 30px;
            text-shadow: 0 0 10px #0f0;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 30px;
            border: 2px solid #0f0;
            box-shadow: 0 0 20px rgba(0, 255, 0, 0.3);
        }
        
        .success-message {
            font-size: 1.5em;
            margin: 30px 0;
        }
        
        .play-again {
            display: inline-block;
            margin-top: 40px;
            padding: 15px 30px;
            background-color: #001100;
            color: #0f0;
            text-decoration: none;
            border: 2px solid #0f0;
            font-size: 1.2em;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .play-again:hover {
            background-color: #0f0;
            color: #000;
        }
        
        /* CRT effect */
        .crt-lines {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: linear-gradient(transparent 50%, rgba(0, 0, 0, 0.05) 50%);
            background-size: 100% 4px;
            pointer-events: none;
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
    </style>
</head>
<body>
    <div class="crt-lines"></div>
    
    <div class="container">
        <h1>ACCESS GRANTED</h1>
        
        <div class="success-message">
            <p>You've successfully completed all challenges!</p>
            <p>The retro terminal system has been unlocked.</p>
        </div>
        
        <a href="?action=reset-game" class="play-again">PLAY AGAIN</a>
    </div>
</body>
</html>
<?php
}