<?php
require_once __DIR__ . '/includes/game_functions.php';
require_once __DIR__ . '/includes/db_connection.php';

initSession();

// Check if we need to skip the password screen (category change)
$skipPasswordScreen = isset($_GET['keep_session']) && $_GET['keep_session'] == '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['category'])) {
        handleCategoryChange();
    } else {
        handleGuessSubmission();
    }
} else if (isset($_GET['action'])) {
    handleAction($_GET['action']);
} else {
    displayRiddlePage();
}

function handleCategoryChange() {
    $maintainSession = isset($_POST['maintain_session']) && $_POST['maintain_session'] === 'true';
    $_SESSION['current_category'] = (int)$_POST['category'];
    
    if ($maintainSession) {
        header('Location: ?keep_session=1');
    } else {
        header('Location: ?');
    }
    exit;
}

function handleAction($action) {
    switch ($action) {
        case 'new-riddle':
            fetchRandomRiddle();
            break;
        case 'get-categories':
            fetchCategories();
            break;
        case 'reset-pins':
            $_SESSION['collected_pins'] = [];
            header('Location: ?');
            exit;
            break;
        case 'mastermind':
            displayMastermindGame();
            break;
        case 'reset-game':
            resetGame();
            header('Location: ?');
            exit;
            break;
        default:
            displayRiddlePage();
    }
}

function handleGuessSubmission() {
    $data = json_decode(file_get_contents('php://input'), true);
    $guess = isset($data['guess']) ? trim($data['guess']) : '';
    $correctAnswer = isset($_SESSION['correct_answer']) ? $_SESSION['correct_answer'] : '';
    $isCorrect = strcasecmp($guess, $correctAnswer) === 0;
    
    $pinAdded = false;
    if ($isCorrect) {
        $newPin = addPin();
        $pinAdded = ($newPin !== null);
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'correct' => $isCorrect,
        'pins' => $_SESSION['collected_pins'],
        'pinAdded' => $pinAdded
    ]);
    exit;
}

function fetchCategories() {
    header('Content-Type: application/json');
    echo json_encode([
        'categories' => getCategories(),
        'currentCategory' => $_SESSION['current_category']
    ]);
    exit;
}

function fetchRandomRiddle() {
    $categoryId = (int)$_SESSION['current_category'];
    
    try {
        $db = Database::getInstance()->getConnection();
        
        // Try to get riddle from selected category
        $query = "SELECT question, answer FROM riddles WHERE category_id = :category_id ORDER BY RAND() LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':category_id', $categoryId);
        $stmt->execute();
        
        $riddle = $stmt->fetch();
        
        if (!$riddle) {
            // General fallback
            $query = "SELECT question, answer FROM riddles ORDER BY RAND() LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $riddle = $stmt->fetch();
            
            // Hardcoded fallback
            if (!$riddle) {
                $riddle = [
                    'question' => 'What has keys but no locks, space but no room, and you can enter but not go in?',
                    'answer' => 'keyboard'
                ];
            }
        }
    } catch (Exception $e) {
        // Database error - use fallback
        error_log($e->getMessage());
        $riddle = [
            'question' => 'What has keys but no locks, space but no room, and you can enter but not go in? (System Offline)',
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

function displayRiddlePage() {
    global $skipPasswordScreen;
    $categories = getCategories();
    $currentCategoryId = $_SESSION['current_category'];
    $currentCategory = isset($categories[$currentCategoryId]) ? $categories[$currentCategoryId] : $categories[CATEGORY_MOVIES];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo GAME_NAME; ?></title>
  <link rel="stylesheet" href="css/main.css">
  <script src="js/config.js"></script>
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
        <?php for($i=0; $i<MAX_PINS; $i++): ?>
            <div class="pin-placeholder" id="pin-slot-<?php echo $i; ?>"></div>
        <?php endfor; ?>
      </div>
      <p>Solve riddles to collect all <?php echo MAX_PINS; ?> access pins then place them in the correct order to unlock the next game</p>
    </div>
    
    <div class="game-controls">
      <button id="next-phase-button" onclick="proceedToMastermind()">PROCEED TO FINAL PHASE</button>
      <button onclick="loadNewRiddle()">NEW RIDDLE</button>
      <button onclick="resetPins()">RESET PINS</button>
    </div>
  </div>

  <script src="js/riddle-game.js"></script>
  <script>
    // Server-side state injection
    const SERVER_STATE = {
        collectedPins: <?php echo json_encode($_SESSION['collected_pins']); ?>,
        currentCategory: <?php echo $currentCategoryId; ?>,
        skipPassword: <?php echo $skipPasswordScreen ? 'true' : 'false'; ?>
    };
  </script>
</body>
</html>
<?php
}

function displayMastermindGame() {
    $pins = $_SESSION['collected_pins'];
    if (count($pins) < MAX_PINS) {
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
    <link rel="stylesheet" href="css/main.css">
    <script src="js/config.js"></script>
</head>
<body>
   <div class="crt-scan"></div>
   <div id="riddle-container">
    <h1>UNLOCK THE NEXT GAME</h1>
    <p>Arrage the collected pins in the correct order to break the code.</p>
   </div>
   
   <div id="guess-container">
    <div id="guess-inputs"></div>
    <button onclick="submitMastermindGuess()">SUBMIT GUESS</button>
   </div>
   
   <div id="result"></div>
   
   <div id="collected-pins-ref">
       <p>YOUR PINS: <?php echo implode(' - ', $pins); ?></p>
   </div>

   <script>
       const SECRET_PINS = <?php echo json_encode($pins); ?>;
   </script>
   <script src="js/mastermind-game.js"></script>
</body>
</html>
<?php
}
?>