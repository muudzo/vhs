<?php
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
            $category_id = isset($_GET['category']) ? intval($_GET['category']) : null;
            fetchRandomRiddle($category_id);
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
        case 'success':
            displaySuccessPage();
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

function getCategories() {
    $categories = [
        ['id' => 1, 'name' => 'General Riddles'],
        ['id' => 2, 'name' => 'Movies - Easy'],
        ['id' => 3, 'name' => 'Movies - Medium'],
        ['id' => 4, 'name' => 'Movies - Hard'],
    ];
    
    try {
        $servername = "localhost";
        $username = "root";
        $password = "Mudzo2608";
        $dbname = "80s_video_store";
        
        $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("SELECT category_id as id, category_name as name FROM categories ORDER BY category_id");
        $stmt->execute();
        
        $dbCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($dbCategories)) {
            $categories = $dbCategories;
        }
    } catch (PDOException $e) {
        // Use hardcoded categories if DB fails
    }
    
    return $categories;
}

function fetchRandomRiddle($category_id = null) {
    // Use hardcoded riddles to ensure functionality without database
    $hardcodedRiddles = [
        // General riddles (category 1)
        ['question' => 'What has keys but no locks, space but no room, and you can enter but not go in?', 'answer' => 'keyboard', 'category_id' => 1],
        ['question' => 'I speak without a mouth and hear without ears. I have no body, but I come alive with wind. What am I?', 'answer' => 'echo', 'category_id' => 1],
        ['question' => 'The more you take, the more you leave behind. What am I?', 'answer' => 'footsteps', 'category_id' => 1],
        ['question' => 'What gets wetter as it dries?', 'answer' => 'towel', 'category_id' => 1],
        ['question' => 'What has a head, a tail, but no body?', 'answer' => 'coin', 'category_id' => 1],
        ['question' => 'I\'m tall when I\'m young, and I\'m short when I\'m old. What am I?', 'answer' => 'candle', 'category_id' => 1],
        // Movie riddles - easy (category 2)
        ['question' => 'I\'m a movie about a boy who befriends an alien. What am I?', 'answer' => 'ET', 'category_id' => 2],
        ['question' => 'I\'m a box that holds movies, with a rewind button to go back. What am I?', 'answer' => 'VHS', 'category_id' => 2],
        // Movie riddles - medium (category 3)
        ['question' => 'I\'m a movie where kids fight a scary clown. What am I?', 'answer' => 'IT', 'category_id' => 3],
        ['question' => 'I\'m a machine that plays movies on your TV. What am I?', 'answer' => 'VCR', 'category_id' => 3],
        // Movie riddles - hard (category 4)
        ['question' => 'I\'m a little green guy, wise and old, in a galaxy where stories are told. Who am I?', 'answer' => 'Yoda', 'category_id' => 4],
        ['question' => 'I\'m a movie star from the 80s with a hoverboard. Who am I?', 'answer' => 'MARTY', 'category_id' => 4],
    ];
    
    // Try database if available
    $useHardcoded = true;
    try {
        $servername = "localhost";
        $username = "root";
        $password = "Mudzo2608";
        $dbname = "80s_video_store";
        
        $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Modified query to include category filter if provided
        $sql = "SELECT r.question, r.answer, r.category_id FROM riddles r";
        $params = [];
        
        if ($category_id) {
            $sql .= " WHERE r.category_id = ?";
            $params[] = $category_id;
        }
        
        $sql .= " ORDER BY RAND() LIMIT 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        $riddle = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($riddle) {
            $useHardcoded = false;
        }
    } catch (PDOException $e) {
        // Database error - we'll use hardcoded riddles
        $useHardcoded = true;
    }
    
    // If database failed or no riddles found, use hardcoded with category filter if provided
    if ($useHardcoded) {
        $filteredRiddles = $hardcodedRiddles;
        
        // Filter by category if specified
        if ($category_id) {
            $filteredRiddles = array_filter($hardcodedRiddles, function($r) use ($category_id) {
                return $r['category_id'] == $category_id;
            });
            
            // If no riddles in this category, use all riddles
            if (empty($filteredRiddles)) {
                $filteredRiddles = $hardcodedRiddles;
            }
        }
        
        $randomIndex = array_rand($filteredRiddles);
        $riddle = $filteredRiddles[$randomIndex];
    }
    
    $trimmedAnswer = trim($riddle['answer']);
    $_SESSION['correct_answer'] = $trimmedAnswer;
    
    // Store category for next riddle
    if (isset($riddle['category_id'])) {
        $_SESSION['current_category'] = $riddle['category_id'];
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'riddle' => $riddle['question'],
        'answerLength' => strlen($trimmedAnswer),
        'pins' => $_SESSION['collected_pins'],
        'category' => $_SESSION['current_category'] ?? null
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
    #category-selector {
      background-color: #000;
      color: #0f0;
      border: 2px solid #0f0;
      padding: 8px 12px;
      font-family: 'Courier New', monospace;
      font-size: 16px;
      cursor: pointer;
      outline: none;
      appearance: none;
      -webkit-appearance: none;
      -moz-appearance: none;
      position: relative;
      min-width: 200px;
    }
    
    #category-selector:focus {
      box-shadow: 0 0 8px rgba(0, 255, 0, 0.5);
    }
    
    /* Custom dropdown arrow */
    #category-selector {
      background-image: linear-gradient(45deg, transparent 50%, #0f0 50%), 
                       linear-gradient(135deg, #0f0 50%, transparent 50%);
      background-position: calc(100% - 20px) calc(1em), calc(100% - 15px) calc(1em);
      background-size: 5px 5px, 5px 5px;
      background-repeat: no-repeat;
      padding-right: 30px;
    }
    
    /* Glowing effect on hover */
    #category-selector:hover,
    #category-container button:hover {
      box-shadow: 0 0 10px rgba(0, 255, 0, 0.7);
    }
    
    /* Arcade button style for the load button */
    #category-container button {
      transition: all 0.2s ease;
      text-transform: uppercase;
      font-weight: bold;
      letter-spacing: 1px;
    }
    
    #category-container button:active {
      transform: scale(0.95);
      box-shadow: 0 0 15px rgba(0, 255, 0, 1);
    }
  </style>
</head>
<body>
  <div class="crt-scan"></div>
  <div class="pin-notification" id="pin-notification"></div>

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
    
    <!-- Category selection interface -->
    <div id="category-container" style="margin: 20px 0; text-align: center;">
      <div style="display: inline-block; padding: 10px; border: 2px dashed #0f0; background-color: #001100;">
        <h3 style="margin-top: 0; margin-bottom: 10px;">SELECT RIDDLE CATEGORY</h3>
        <div style="display: flex; align-items: center; justify-content: center;">
          <select id="category-selector" style="background: #000; color: #0f0; border: 2px solid #0f0; padding: 8px; margin-right: 10px; font-family: 'Courier New', monospace;">
            <?php foreach(getCategories() as $category): ?>
              <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
            <?php endforeach; ?>
          </select>
          <button onclick="loadRiddleWithCategory()" style="background: #000; color: #0f0; border: 2px solid #0f0; padding: 8px 15px; cursor: pointer;">LOAD CATEGORY</button>
        </div>
      </div>
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
      </div>
      <p>Solve riddles to collect all 3 access pins then place them in the correct order to unlock the next game</p>
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

    function fetchRiddle(categoryId = null) {
        let url = "?action=new-riddle";
        if (categoryId) {
            url += "&category=" + categoryId;
        }
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                document.getElementById('riddle-text').textContent = data.riddle;
                answerLength = data.answerLength;
                
                // Create the letter inputs based on answer length
                createLetterInputs(answerLength);
                
                document.getElementById('answer-length').textContent = `[REQUIRED LENGTH: ${answerLength}]`;
                document.getElementById('result').textContent = '';
                
                // Update pins from server response
                collectedPins = data.pins;
                updatePinDisplay();
                
                // Update category display if provided
                updateCategoryDisplay(data.category);
            })
            .catch(error => {
                document.getElementById('riddle-text').textContent = 'SYSTEM MALFUNCTION - RETRY';
                console.error(error);
            });
    }

    function updateCategoryDisplay(categoryId) {
        const categoryDropdown = document.getElementById('category-selector');
        if (categoryDropdown && categoryId) {
            categoryDropdown.value = categoryId;
        }
    }

    function loadRiddleWithCategory() {
        const categoryId = document.getElementById('category-selector').value;
        document.getElementById('riddle-text').textContent = 'ACCESSING RIDDLE DATABASE...';
        fetchRiddle(categoryId);
    }

    function loadNewRiddle() {
        document.getElementById('riddle-text').textContent = 'ACCESSING RIDDLE DATABASE...';
        const categoryId = document.getElementById('category-selector')?.value || null;
        fetchRiddle(categoryId);
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
    
    // Additional code to make sure category stays selected when loading new riddles
    document.addEventListener('DOMContentLoaded', function() {
      if (document.getElementById('category-selector')) {
        document.getElementById('category-selector').addEventListener('change', function() {
          // Optional: Auto-load riddle when category changes
          // loadRiddleWithCategory();
        });
      }
    });
  </script>

</body>
</html>
<?php
}

function displaySuccessPage() {
    // Check if user has completed the mastermind game (has 3 pins)
    if (!isset($_SESSION['collected_pins']) || count($_SESSION['collected_pins']) < 3) {
        header('Location: ?');
        exit;
    }
    
    // Get pins from session to display in success message
    $secretCode = implode('', $_SESSION['collected_pins']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ACCESS GRANTED</title>
  <link rel="stylesheet" href="mastermind.css">  
  <style>
    @keyframes textShadowPulse {
      0% { text-shadow: 0 0 5px #0f0, 0 0 10px #0f0; }
      50% { text-shadow: 0 0 15px #0f0, 0 0 25px #0f0, 0 0 35px #0f0; }
      100% { text-shadow: 0 0 5px #0f0, 0 0 10px #0f0; }
    }
    
    @keyframes crtFlicker {
      0% { opacity: 0.98; }
      25% { opacity: 1; }
      30% { opacity: 0.9; }
      35% { opacity: 1; }
      70% { opacity: 0.99; }
      75% { opacity: 0.9; }
      76% { opacity: 1; }
      100% { opacity: 0.98; }
    }
    
    @keyframes glitch {
      0% { transform: translate(0); }
      20% { transform: translate(-2px, 2px); }
      40% { transform: translate(-2px, -2px); }
      60% { transform: translate(2px, 2px); }
      80% { transform: translate(2px, -2px); }
      100% { transform: translate(0); }
    }
    
    @keyframes pixelate {
      0% { filter: none; }
      15% { filter: blur(1px); }
      16% { filter: none; }
      45% { filter: none; }
      46% { filter: blur(1px); }
      48% { filter: none; }
      100% { filter: none; }
    }
    
    .success-container {
      text-align: center;
      padding: 40px;
      height: 100vh;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      animation: crtFlicker 6s infinite, pixelate 8s infinite;
      overflow: hidden;
      position: relative;
    }
    
    .success-title {
      font-size: 3rem;
      margin-bottom: 30px;
      animation: textShadowPulse 2s infinite, glitch 3s infinite;
    }
    
    .reward-code {
      font-size: 2rem;
      margin: 20px 0;
      padding: 20px;
      background-color: #001100;
      display: inline-block;
      border: 3px dashed #0f0;
      animation: textShadowPulse 2s infinite;
      font-family: 'Courier New', monospace;
      font-weight: bold;
      letter-spacing: 2px;
    }
    
    .success-message {
      font-size: 1.2rem;
      margin: 20px 0;
      max-width: 600px;
      line-height: 1.6;
    }
    
    .arcade-button {
      background: #000;
      border: 3px solid #0f0;
      color: #0f0;
      padding: 15px 30px;
      font-size: 1.2rem;
      cursor: pointer;
      transition: all 0.3s ease;
      margin: 20px;
      font-family: 'Courier New', monospace;
      text-transform: uppercase;
      position: relative;
      overflow: hidden;
    }
    
    .arcade-button:hover {
      background: #0f0;
      color: #000;
      animation: textShadowPulse 1s infinite;
    }
    
    .arcade-button:before {
      content: '';
      position: absolute;
      top: -10px;
      left: -10px;
      right: -10px;
      bottom: -10px;
      border: 2px dashed #0f0;
      animation: textShadowPulse 2s infinite;
      pointer-events: none;
      opacity: 0;
      transition: opacity 0.3s ease;
    }
    
    .arcade-button:hover:before {
      opacity: 1;
    }
    
    .scanline {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(
        to bottom,
        rgba(0, 0, 0, 0),
        rgba(0, 255, 0, 0.1),
        rgba(0, 0, 0, 0)
      );
      pointer-events: none;
      opacity: 0.7;
      z-index: 9;
      animation-name: scanline;
      animation-duration: 7s;
      animation-timing-function: linear;
      animation-iteration-count: infinite;
    }
    
    @keyframes scanline {
      0% { transform: translateY(-100%); }
      100% { transform: translateY(100%); }
    }
    
    .stars {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      pointer-events: none;
      z-index: 8;
      overflow: hidden;
    }
    
    .star {
      position: absolute;
      background-color: #0f0;
      width: 2px;
      height: 2px;
      border-radius: 50%;
      opacity: 0;
      animation-name: starAnimation;
      animation-timing-function: linear;
      animation-iteration-count: infinite;
    }
    
    @keyframes starAnimation {
      0% {
        opacity: 0;
        transform: scale(0);
      }
      50% {
        opacity: 1;
        transform: scale(1.5);
      }
      100% {
        opacity: 0;
        transform: scale(0);
      }
    }
    
    /* Typography with cyberpunk vibes */
    h1, h2, h3, p {
      font-family: 'Courier New', monospace;
      text-transform: uppercase;
      letter-spacing: 2px;
    }
    
    /* Add some glitch effect to images or icons */
    .glitch-icon {
      display: inline-block;
      margin: 20px;
      animation: glitch 2s infinite;
    }
  </style>
</head>
<body>
  <div class="success-container">
    <div class="scanline"></div>
    <div class="stars" id="stars-container"></div>
    
    <h1 class="success-title">ACCESS GRANTED</h1>
    
    <div class="glitch-icon">
      <svg width="100" height="100" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
        <circle cx="50" cy="50" r="45" stroke="#0f0" stroke-width="3"/>
        <path d="M30 50 L45 65 L70 35" stroke="#0f0" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </div>
    
    <p class="success-message">YOU HAVE SUCCESSFULLY BROKEN THE CODE AND ACCESSED THE MAINFRAME.</p>
    
    <div class="reward-code">
      REWARD: BLOCKBUSTER80
    </div>
    
    <p class="success-message">YOUR ACCESS CODE WAS: <?php echo $secretCode; ?></p>
    
    <div class="button-container">
      <button class="arcade-button" onclick="window.location.href='?action=reset-game'">PLAY AGAIN</button>
      <button class="arcade-button" onclick="window.location.href='/'">MAIN MENU</button>
    </div>
  </div>
  
  <script>
    // Create star animation
    function createStars() {
      const starsContainer = document.getElementById('stars-container');
      const numberOfStars = 100;
      
      for (let i = 0; i < numberOfStars; i++) {
        const star = document.createElement('div');
        star.classList.add('star');
        
        // Random position
        const x = Math.random() * 100;
        const y = Math.random() * 100;
        
        // Random size
        const size = Math.random() * 3 + 1;
        
        // Random duration
        const duration = Math.random() * 3 + 2;
        
        // Random delay
        const delay = Math.random() * 5;
        
        star.style.left = `${x}%`;
        star.style.top = `${y}%`;
        star.style.width = `${size}px`;
        star.style.height = `${size}px`;
        star.style.animationDuration = `${duration}s`;
        star.style.animationDelay = `${delay}s`;
        
        starsContainer.appendChild(star);
      }
    }
    
    // Terminal typing effect
    function typeText(elementId, text, speed) {
      const element = document.getElementById(elementId);
      let i = 0;
      
      function type() {
        if (i < text.length) {
          element.textContent += text.charAt(i);
          i++;
          setTimeout(type, speed);
        }
      }
      
      element.textContent = "";
      type();
    }
    
    // Run animations on page load
    document.addEventListener('DOMContentLoaded', function() {
      createStars();
      
      // Optional: Add audio effect
      try {
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        
        function playSuccessSound() {
          const oscillator = audioContext.createOscillator();
          const gainNode = audioContext.createGain();
          
          oscillator.type = 'square';
          oscillator.frequency.value = 440;
          gainNode.gain.value = 0.1;
          
          oscillator.connect(gainNode);
          gainNode.connect(audioContext.destination);
          
          oscillator.start();
          
          // Frequency modulation
          oscillator.frequency.exponentialRampToValueAtTime(880, audioContext.currentTime + 0.2);
          oscillator.frequency.exponentialRampToValueAtTime(1760, audioContext.currentTime + 0.4);
          
          // End sound
          gainNode.gain.exponentialRampToValueAtTime(0.001, audioContext.currentTime + 1);
          setTimeout(() => {
            oscillator.stop();
          }, 1000);
        }
        
        playSuccessSound();
      } catch (e) {
        // Audio context not supported or some other error
        console.log("Audio not supported in this browser");
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
            
            // Add success button to proceed to success page
            const successButton = document.createElement('button');
            successButton.textContent = 'CLAIM REWARD';
            successButton.style.margin = '15px';
            successButton.style.background = '#002200';
            successButton.style.color = '#0f0';
            successButton.style.border = '2px solid #0f0';
            successButton.style.padding = '10px 20px';
            successButton.onclick = function() {
                window.location.href = '?action=success';
            };
            resultDiv.appendChild(document.createElement('br'));
            resultDiv.appendChild(successButton);
            
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
            resultDiv.appendChild(playAgainButton);
            
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
