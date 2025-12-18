<?php
/**
 * LUXE Fashion - API Handler
 * 
 * RESTful API endpoints cho frontend
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

// Start session
session_name(SESSION_NAME);
session_start();

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Response helper
function jsonResponse($data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Error handler
function errorResponse(string $message, int $statusCode = 400): void
{
    jsonResponse(['success' => false, 'message' => $message], $statusCode);
}

// Get JSON input
function getJsonInput(): array
{
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?? [];
}

try {
    // Route requests
    switch ($action) {
        // ==================== PRODUCTS ====================
        case 'products':
            if ($method !== 'GET') errorResponse('Method not allowed', 405);
            
            $product = new Product();
            $filters = [
                'category_id' => $_GET['category_id'] ?? null,
                'category_slug' => $_GET['category'] ?? null,
                'min_price' => $_GET['min_price'] ?? null,
                'max_price' => $_GET['max_price'] ?? null,
                'is_featured' => isset($_GET['featured']),
                'is_new' => isset($_GET['new']),
                'is_bestseller' => isset($_GET['bestseller']),
                'on_sale' => isset($_GET['sale']),
                'search' => $_GET['q'] ?? null,
                'sort' => $_GET['sort'] ?? 'newest'
            ];
            
            $page = (int) ($_GET['page'] ?? 1);
            $result = $product->getAll($filters, $page);
            
            jsonResponse(['success' => true, 'data' => $result]);
            break;

        case 'products.featured':
            if ($method !== 'GET') errorResponse('Method not allowed', 405);
            
            $product = new Product();
            $limit = (int) ($_GET['limit'] ?? 8);
            $result = $product->getFeatured($limit);
            
            jsonResponse(['success' => true, 'data' => $result]);
            break;

        case 'products.new':
            if ($method !== 'GET') errorResponse('Method not allowed', 405);
            
            $product = new Product();
            $limit = (int) ($_GET['limit'] ?? 8);
            $result = $product->getNewArrivals($limit);
            
            jsonResponse(['success' => true, 'data' => $result]);
            break;

        case 'products.bestsellers':
            if ($method !== 'GET') errorResponse('Method not allowed', 405);
            
            $product = new Product();
            $limit = (int) ($_GET['limit'] ?? 8);
            $result = $product->getBestsellers($limit);
            
            jsonResponse(['success' => true, 'data' => $result]);
            break;

        case 'products.sale':
            if ($method !== 'GET') errorResponse('Method not allowed', 405);
            
            $product = new Product();
            $limit = (int) ($_GET['limit'] ?? 8);
            $result = $product->getOnSale($limit);
            
            jsonResponse(['success' => true, 'data' => $result]);
            break;

        case 'product':
            if ($method !== 'GET') errorResponse('Method not allowed', 405);
            
            $id = $_GET['id'] ?? null;
            $slug = $_GET['slug'] ?? null;
            
            if (!$id && !$slug) {
                errorResponse('Product ID or slug required');
            }
            
            $product = new Product();
            $result = $id ? $product->getById((int) $id) : $product->getBySlug($slug);
            
            if (!$result) {
                errorResponse('Product not found', 404);
            }
            
            jsonResponse(['success' => true, 'data' => $result]);
            break;

        case 'products.search':
            if ($method !== 'GET') errorResponse('Method not allowed', 405);
            
            $keyword = $_GET['q'] ?? '';
            if (strlen($keyword) < 2) {
                errorResponse('Search keyword must be at least 2 characters');
            }
            
            $product = new Product();
            $limit = (int) ($_GET['limit'] ?? 10);
            $result = $product->search($keyword, $limit);
            
            jsonResponse(['success' => true, 'data' => $result]);
            break;

        case 'products.related':
            if ($method !== 'GET') errorResponse('Method not allowed', 405);
            
            $productId = (int) ($_GET['product_id'] ?? 0);
            $categoryId = (int) ($_GET['category_id'] ?? 0);
            
            if (!$productId || !$categoryId) {
                errorResponse('Product ID and Category ID required');
            }
            
            $product = new Product();
            $limit = (int) ($_GET['limit'] ?? 4);
            $result = $product->getRelated($productId, $categoryId, $limit);
            
            jsonResponse(['success' => true, 'data' => $result]);
            break;

        // ==================== CART ====================
        case 'cart':
            $cart = new Cart();
            
            if ($method === 'GET') {
                jsonResponse(['success' => true, 'data' => $cart->getCart()]);
            } elseif ($method === 'DELETE') {
                jsonResponse($cart->clear());
            } else {
                errorResponse('Method not allowed', 405);
            }
            break;

        case 'cart.add':
            if ($method !== 'POST') errorResponse('Method not allowed', 405);
            
            $input = getJsonInput();
            $productId = (int) ($input['product_id'] ?? 0);
            $quantity = (int) ($input['quantity'] ?? 1);
            $variantId = isset($input['variant_id']) ? (int) $input['variant_id'] : null;
            
            if (!$productId) {
                errorResponse('Product ID required');
            }
            
            $cart = new Cart();
            jsonResponse($cart->add($productId, $quantity, $variantId));
            break;

        case 'cart.update':
            if ($method !== 'POST' && $method !== 'PUT') errorResponse('Method not allowed', 405);
            
            $input = getJsonInput();
            $itemId = (int) ($input['item_id'] ?? 0);
            $quantity = (int) ($input['quantity'] ?? 0);
            
            if (!$itemId) {
                errorResponse('Item ID required');
            }
            
            $cart = new Cart();
            jsonResponse($cart->update($itemId, $quantity));
            break;

        case 'cart.remove':
            if ($method !== 'POST' && $method !== 'DELETE') errorResponse('Method not allowed', 405);
            
            $input = getJsonInput();
            $itemId = (int) ($input['item_id'] ?? $_GET['item_id'] ?? 0);
            
            if (!$itemId) {
                errorResponse('Item ID required');
            }
            
            $cart = new Cart();
            jsonResponse($cart->remove($itemId));
            break;

        case 'cart.count':
            if ($method !== 'GET') errorResponse('Method not allowed', 405);
            
            $cart = new Cart();
            jsonResponse(['success' => true, 'count' => $cart->getCount()]);
            break;

        case 'cart.coupon':
            if ($method !== 'POST') errorResponse('Method not allowed', 405);
            
            $input = getJsonInput();
            $code = $input['code'] ?? '';
            
            if (!$code) {
                errorResponse('Coupon code required');
            }
            
            $cart = new Cart();
            jsonResponse($cart->applyCoupon($code));
            break;

        // ==================== CATEGORIES ====================
        case 'categories':
            if ($method !== 'GET') errorResponse('Method not allowed', 405);
            
            $db = Database::getInstance();
            $sql = "SELECT c.*, 
                           (SELECT COUNT(*) FROM products WHERE category_id = c.id AND status = 'active') as product_count
                    FROM categories c 
                    WHERE c.is_active = 1 
                    ORDER BY c.sort_order, c.name";
            $categories = $db->query($sql)->fetchAll();
            
            jsonResponse(['success' => true, 'data' => $categories]);
            break;

        // ==================== NEWSLETTER ====================
        case 'newsletter.subscribe':
            if ($method !== 'POST') errorResponse('Method not allowed', 405);
            
            $input = getJsonInput();
            $email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);
            
            if (!$email) {
                errorResponse('Email không hợp lệ');
            }
            
            $db = Database::getInstance();
            
            // Check if already subscribed
            $sql = "SELECT id, is_active FROM newsletter_subscribers WHERE email = ?";
            $existing = $db->query($sql, [$email])->fetch();
            
            if ($existing) {
                if ($existing['is_active']) {
                    jsonResponse(['success' => true, 'message' => 'Email này đã đăng ký nhận tin']);
                } else {
                    // Reactivate subscription
                    $sql = "UPDATE newsletter_subscribers SET is_active = 1, subscribed_at = NOW() WHERE id = ?";
                    $db->query($sql, [$existing['id']]);
                    jsonResponse(['success' => true, 'message' => 'Đã kích hoạt lại đăng ký']);
                }
            } else {
                // New subscription
                $sql = "INSERT INTO newsletter_subscribers (email) VALUES (?)";
                $db->query($sql, [$email]);
                jsonResponse(['success' => true, 'message' => 'Đăng ký nhận tin thành công! 💌']);
            }
            break;

        // ==================== SETTINGS ====================
        case 'settings':
            if ($method !== 'GET') errorResponse('Method not allowed', 405);
            
            $db = Database::getInstance();
            $sql = "SELECT setting_key, setting_value FROM settings";
            $rows = $db->query($sql)->fetchAll();
            
            $settings = [];
            foreach ($rows as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            
            jsonResponse(['success' => true, 'data' => $settings]);
            break;

        default:
            errorResponse('Invalid action', 400);
    }

} catch (Exception $e) {
    errorResponse('Server error: ' . $e->getMessage(), 500);
}
