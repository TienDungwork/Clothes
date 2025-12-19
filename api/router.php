<?php
/**
 * LUXE Fashion - API Router
 * 
 * Routes API requests to appropriate handlers
 */

// Define app constant
define('LUXE_APP', true);

// Headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Load configuration and dependencies
require_once __DIR__ . '/../config/config.php';
require_once INCLUDES_PATH . 'Database.php';
require_once INCLUDES_PATH . 'models/Product.php';
require_once INCLUDES_PATH . 'models/Cart.php';

// Load helpers
require_once __DIR__ . '/helpers.php';

// Start session
session_name(SESSION_NAME);
session_start();

// Get action to determine which handler to use
$action = $_GET['action'] ?? '';

try {
    // Route based on action prefix
    $handled = false;
    
    // Products routes
    if (in_array($action, ['products', 'products.featured', 'products.new', 'products.bestsellers', 
                            'products.sale', 'product', 'products.search', 'products.related', 'categories'])) {
        require_once __DIR__ . '/v1/products.php';
        $handled = true;
    }
    
    // Cart routes
    if (!$handled && in_array($action, ['cart', 'cart.add', 'cart.update', 'cart.remove', 'cart.count', 'cart.coupon'])) {
        require_once __DIR__ . '/v1/cart.php';
        $handled = true;
    }
    
    // Auth routes
    if (!$handled && (strpos($action, 'auth.') === 0 || strpos($action, 'user.') === 0)) {
        require_once __DIR__ . '/v1/auth.php';
        $handled = true;
    }
    
    // Order routes
    if (!$handled && strpos($action, 'orders.') === 0) {
        require_once __DIR__ . '/v1/orders.php';
        $handled = true;
    }
    
    // Admin routes
    if (!$handled && strpos($action, 'admin.') === 0) {
        require_once __DIR__ . '/v1/admin.php';
        $handled = true;
    }
    
    // Misc routes (newsletter, settings)
    if (!$handled && in_array($action, ['newsletter.subscribe', 'settings'])) {
        require_once __DIR__ . '/v1/misc.php';
        $handled = true;
    }
    
    // Not found
    if (!$handled) {
        errorResponse('Invalid action', 400);
    }

} catch (Exception $e) {
    errorResponse('Server error: ' . $e->getMessage(), 500);
}
