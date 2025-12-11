<?php
require_once __DIR__ . '/../config/constants.php';

function getCategories() {
    global $CATEGORIES;
    return $CATEGORIES;
}

function initSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['collected_pins'])) {
        $_SESSION['collected_pins'] = [];
    }
    
    if (!isset($_SESSION['current_category'])) {
        $_SESSION['current_category'] = CATEGORY_MOVIES;
    }
}

function addPin() {
    if (count($_SESSION['collected_pins']) < MAX_PINS) {
        $pin = rand(0, 9);
        $_SESSION['collected_pins'][] = $pin;
        return $pin;
    }
    return null;
}

function resetGame() {
    session_destroy();
    session_start();
    $_SESSION['collected_pins'] = [];
    $_SESSION['current_category'] = CATEGORY_MOVIES;
}
