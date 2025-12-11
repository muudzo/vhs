<?php
// Game Constants
define('GAME_NAME', 'Retro Riddle Terminal');
define('GAME_VERSION', '1.0.0');

// Categories
define('CATEGORY_MOVIES', 1);
define('CATEGORY_SPORTS', 2);
define('CATEGORY_GAMES', 3);
define('CATEGORY_CODING', 4);

$CATEGORIES = [
    CATEGORY_MOVIES => ['id' => 1, 'name' => 'Movies', 'description' => '80s and 90s movie riddles'],
    CATEGORY_SPORTS => ['id' => 2, 'name' => 'Sports Trivia', 'description' => 'Riddles about various sports'],
    CATEGORY_GAMES => ['id' => 3, 'name' => 'Games', 'description' => 'Video game and board game riddles'],
    CATEGORY_CODING => ['id' => 4, 'name' => 'Coding Knowledge', 'description' => 'Riddles about programming, databases, and APIs']
];

// Game Settings
define('MAX_PINS', 4);
define('UNLOCK_PASSCODE', getenv('GAME_PASSCODE') ?: '1234');
