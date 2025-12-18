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


        case 'auth.register':
            if ($method !== 'POST') errorResponse('Method not allowed', 405);
            
            $input = getJsonInput();
            $required = ['email', 'password', 'full_name'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    errorResponse("Vui lòng nhập {$field}");
                }
            }
            
            require_once INCLUDES_PATH . 'models/User.php';
            $user = new User();
            jsonResponse($user->register($input));
            break;

        case 'auth.login':
            if ($method !== 'POST') errorResponse('Method not allowed', 405);
            
            $input = getJsonInput();
            if (empty($input['email']) || empty($input['password'])) {
                errorResponse('Vui lòng nhập email và mật khẩu');
            }
            
            require_once INCLUDES_PATH . 'models/User.php';
            $user = new User();
            jsonResponse($user->login($input['email'], $input['password'], $input['remember'] ?? false));
            break;

        case 'auth.logout':
            if ($method !== 'POST') errorResponse('Method not allowed', 405);
            
            require_once INCLUDES_PATH . 'models/User.php';
            $user = new User();
            $user->logout();
            jsonResponse(['success' => true, 'message' => 'Đăng xuất thành công']);
            break;

        case 'user.profile':
            if (!isset($_SESSION['user_id'])) {
                errorResponse('Chưa đăng nhập', 401);
            }
            
            require_once INCLUDES_PATH . 'models/User.php';
            $user = new User();
            
            if ($method === 'GET') {
                $profile = $user->getById($_SESSION['user_id']);
                jsonResponse(['success' => true, 'data' => $profile]);
            } elseif ($method === 'POST' || $method === 'PUT') {
                $input = getJsonInput();
                jsonResponse($user->update($_SESSION['user_id'], $input));
            } else {
                errorResponse('Method not allowed', 405);
            }
            break;

        case 'user.password':
            if ($method !== 'POST') errorResponse('Method not allowed', 405);
            if (!isset($_SESSION['user_id'])) {
                errorResponse('Chưa đăng nhập', 401);
            }
            
            $input = getJsonInput();
            if (empty($input['current_password']) || empty($input['new_password'])) {
                errorResponse('Vui lòng nhập đầy đủ thông tin');
            }
            
            require_once INCLUDES_PATH . 'models/User.php';
            $user = new User();
            jsonResponse($user->changePassword($_SESSION['user_id'], $input['current_password'], $input['new_password']));
            break;

        case 'user.addresses':
            if (!isset($_SESSION['user_id'])) {
                errorResponse('Chưa đăng nhập', 401);
            }
            
            require_once INCLUDES_PATH . 'models/User.php';
            $user = new User();
            
            if ($method === 'GET') {
                $addresses = $user->getAddresses($_SESSION['user_id']);
                jsonResponse(['success' => true, 'data' => $addresses]);
            } elseif ($method === 'POST') {
                $input = getJsonInput();
                jsonResponse($user->addAddress($_SESSION['user_id'], $input));
            } else {
                errorResponse('Method not allowed', 405);
            }
            break;

        case 'user.wishlist':
            if (!isset($_SESSION['user_id'])) {
                errorResponse('Chưa đăng nhập', 401);
            }
            
            require_once INCLUDES_PATH . 'models/User.php';
            $user = new User();
            
            if ($method === 'GET') {
                $wishlist = $user->getWishlist($_SESSION['user_id']);
                jsonResponse(['success' => true, 'data' => $wishlist]);
            } elseif ($method === 'POST') {
                $input = getJsonInput();
                if (empty($input['product_id'])) {
                    errorResponse('Product ID required');
                }
                jsonResponse($user->addToWishlist($_SESSION['user_id'], (int)$input['product_id']));
            } elseif ($method === 'DELETE') {
                $productId = (int)($_GET['product_id'] ?? 0);
                if (!$productId) {
                    errorResponse('Product ID required');
                }
                jsonResponse($user->removeFromWishlist($_SESSION['user_id'], $productId));
            } else {
                errorResponse('Method not allowed', 405);
            }
            break;

        case 'orders.create':
            if ($method !== 'POST') errorResponse('Method not allowed', 405);
            
            $input = getJsonInput();
            $required = ['customer_name', 'customer_email', 'customer_phone', 'shipping_address'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    errorResponse("Vui lòng nhập {$field}");
                }
            }
            
            require_once INCLUDES_PATH . 'models/Order.php';
            $order = new Order();
            jsonResponse($order->create($input));
            break;

        case 'orders.list':
            if ($method !== 'GET') errorResponse('Method not allowed', 405);
            if (!isset($_SESSION['user_id'])) {
                errorResponse('Chưa đăng nhập', 401);
            }
            
            require_once INCLUDES_PATH . 'models/Order.php';
            $order = new Order();
            $page = (int)($_GET['page'] ?? 1);
            jsonResponse(['success' => true, 'data' => $order->getByUser($_SESSION['user_id'], $page)]);
            break;

        case 'orders.detail':
            if ($method !== 'GET') errorResponse('Method not allowed', 405);
            
            $orderId = (int)($_GET['id'] ?? 0);
            $orderCode = $_GET['code'] ?? '';
            
            if (!$orderId && !$orderCode) {
                errorResponse('Order ID hoặc code required');
            }
            
            require_once INCLUDES_PATH . 'models/Order.php';
            $order = new Order();
            $result = $orderId ? $order->getById($orderId) : $order->getByCode($orderCode);
            
            if (!$result) {
                errorResponse('Không tìm thấy đơn hàng', 404);
            }
            
            if (isset($_SESSION['user_id']) && $result['user_id'] != $_SESSION['user_id']) {
                errorResponse('Không có quyền xem đơn hàng này', 403);
            }
            
            jsonResponse(['success' => true, 'data' => $result]);
            break;

        case 'orders.cancel':
            if ($method !== 'POST') errorResponse('Method not allowed', 405);
            if (!isset($_SESSION['user_id'])) {
                errorResponse('Chưa đăng nhập', 401);
            }
            
            $input = getJsonInput();
            $orderId = (int)($input['order_id'] ?? 0);
            
            if (!$orderId) {
                errorResponse('Order ID required');
            }
            
            require_once INCLUDES_PATH . 'models/Order.php';
            $order = new Order();
            jsonResponse($order->cancel($orderId, $_SESSION['user_id']));
            break;

        case 'orders.track':
            if ($method !== 'POST' && $method !== 'GET') errorResponse('Method not allowed', 405);
            
            $orderCode = $_GET['code'] ?? '';
            $contact = $_GET['contact'] ?? '';
            
            if ($method === 'POST') {
                $input = getJsonInput();
                $orderCode = $input['order_code'] ?? '';
                $contact = $input['contact'] ?? '';
            }
            
            if (!$orderCode || !$contact) {
                errorResponse('Vui lòng nhập mã đơn hàng và email/số điện thoại');
            }
            
            require_once INCLUDES_PATH . 'models/Order.php';
            $order = new Order();
            $result = $order->track($orderCode, $contact);
            
            if (!$result) {
                errorResponse('Không tìm thấy đơn hàng', 404);
            }
            
            jsonResponse(['success' => true, 'data' => $result]);
            break;

        // ==================== ADMIN ====================
        case 'admin.products.create':
            if ($method !== 'POST') errorResponse('Method not allowed', 405);
            if (!isset($_SESSION['user_id'])) errorResponse('Unauthorized', 401);
            
            // Check admin role
            $db = Database::getInstance();
            $user = $db->query("SELECT role FROM users WHERE id = ?", [$_SESSION['user_id']])->fetch();
            if (!$user || $user['role'] !== 'admin') errorResponse('Forbidden', 403);
            
            $input = getJsonInput();
            if (empty($input['name']) || empty($input['price'])) {
                errorResponse('Tên và giá sản phẩm là bắt buộc');
            }
            
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $input['name'])));
            $sql = "INSERT INTO products (name, slug, price, sale_price, category_id, stock_quantity, image, description, status, is_featured, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $db->query($sql, [
                $input['name'],
                $slug,
                $input['price'],
                $input['sale_price'] ?? null,
                $input['category_id'] ?? null,
                $input['stock_quantity'] ?? 0,
                $input['image'] ?? null,
                $input['description'] ?? null,
                $input['status'] ?? 'active',
                $input['is_featured'] ?? 0
            ]);
            
            jsonResponse(['success' => true, 'message' => 'Thêm sản phẩm thành công', 'id' => $db->lastInsertId()]);
            break;

        case 'admin.products.update':
            if ($method !== 'POST') errorResponse('Method not allowed', 405);
            if (!isset($_SESSION['user_id'])) errorResponse('Unauthorized', 401);
            
            $db = Database::getInstance();
            $user = $db->query("SELECT role FROM users WHERE id = ?", [$_SESSION['user_id']])->fetch();
            if (!$user || $user['role'] !== 'admin') errorResponse('Forbidden', 403);
            
            $input = getJsonInput();
            if (empty($input['id'])) errorResponse('ID sản phẩm là bắt buộc');
            
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $input['name'])));
            $sql = "UPDATE products SET name = ?, slug = ?, price = ?, sale_price = ?, category_id = ?, 
                    stock_quantity = ?, image = ?, description = ?, status = ?, is_featured = ?, updated_at = NOW() WHERE id = ?";
            $db->query($sql, [
                $input['name'],
                $slug,
                $input['price'],
                $input['sale_price'] ?? null,
                $input['category_id'] ?? null,
                $input['stock_quantity'] ?? 0,
                $input['image'] ?? null,
                $input['description'] ?? null,
                $input['status'] ?? 'active',
                $input['is_featured'] ?? 0,
                $input['id']
            ]);
            
            jsonResponse(['success' => true, 'message' => 'Cập nhật sản phẩm thành công']);
            break;

        case 'admin.products.delete':
            if ($method !== 'POST') errorResponse('Method not allowed', 405);
            if (!isset($_SESSION['user_id'])) errorResponse('Unauthorized', 401);
            
            $db = Database::getInstance();
            $user = $db->query("SELECT role FROM users WHERE id = ?", [$_SESSION['user_id']])->fetch();
            if (!$user || $user['role'] !== 'admin') errorResponse('Forbidden', 403);
            
            $input = getJsonInput();
            if (empty($input['id'])) errorResponse('ID sản phẩm là bắt buộc');
            
            // Delete related records first
            $db->query("DELETE FROM product_images WHERE product_id = ?", [$input['id']]);
            $db->query("DELETE FROM product_variants WHERE product_id = ?", [$input['id']]);
            $db->query("DELETE FROM products WHERE id = ?", [$input['id']]);
            
            jsonResponse(['success' => true, 'message' => 'Xóa sản phẩm thành công']);
            break;

        default:
            errorResponse('Invalid action', 400);
    }

} catch (Exception $e) {
    errorResponse('Server error: ' . $e->getMessage(), 500);
}
