<?php
/**
 * Authentication and user-related API endpoints
 */

// Prevent direct access
if (!defined('LUXE_APP')) {
    exit('Direct access not allowed');
}

require_once INCLUDES_PATH . 'models/User.php';

$method = getMethod();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'auth.register':
        checkMethod('POST');
        
        $input = getJsonInput();
        validateRequired($input, ['email', 'password', 'full_name']);
        
        $user = new User();
        jsonResponse($user->register($input));
        break;

    case 'auth.login':
        checkMethod('POST');
        
        $input = getJsonInput();
        if (empty($input['email']) || empty($input['password'])) {
            errorResponse('Vui lòng nhập email và mật khẩu');
        }
        
        $user = new User();
        jsonResponse($user->login($input['email'], $input['password'], $input['remember'] ?? false));
        break;

    case 'auth.logout':
        checkMethod('POST');
        
        $user = new User();
        $user->logout();
        jsonResponse(['success' => true, 'message' => 'Đăng xuất thành công']);
        break;

    case 'user.profile':
        requireAuth();
        
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
        checkMethod('POST');
        requireAuth();
        
        $input = getJsonInput();
        if (empty($input['current_password']) || empty($input['new_password'])) {
            errorResponse('Vui lòng nhập đầy đủ thông tin');
        }
        
        $user = new User();
        jsonResponse($user->changePassword($_SESSION['user_id'], $input['current_password'], $input['new_password']));
        break;

    case 'user.addresses':
        requireAuth();
        
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
        requireAuth();
        
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

    default:
        return false; // Not handled
}
