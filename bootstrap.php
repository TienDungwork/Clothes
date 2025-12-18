<?php
/**
 * LUXE Fashion - Main Bootstrap File
 * 
 * This file initializes the application
 */

// Define app constant
define('LUXE_APP', true);

// Start session
session_name('luxe_session');
session_start();

// Load configuration
require_once __DIR__ . '/config/config.php';

// Autoloader for classes
spl_autoload_register(function ($class) {
    $paths = [
        INCLUDES_PATH . $class . '.php',
        INCLUDES_PATH . 'models/' . $class . '.php',
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Helper functions
function formatPrice($price) {
    return number_format($price, 0, ',', '.') . '₫';
}

function asset($path) {
    return SITE_URL . '/assets/' . ltrim($path, '/');
}

function url($path = '') {
    return SITE_URL . '/' . ltrim($path, '/');
}

function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function old($key, $default = '') {
    return $_POST[$key] ?? $default;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function currentUser() {
    if (!isLoggedIn()) return null;
    
    static $user = null;
    if ($user === null) {
        $db = Database::getInstance();
        $user = $db->query("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']])->fetch();
    }
    return $user;
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function verify_csrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
