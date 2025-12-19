<?php
/**
 * Order-related API endpoints
 */

// Prevent direct access
if (!defined('LUXE_APP')) {
    exit('Direct access not allowed');
}

require_once INCLUDES_PATH . 'models/Order.php';

$method = getMethod();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'orders.create':
        checkMethod('POST');
        
        $input = getJsonInput();
        validateRequired($input, ['customer_name', 'customer_email', 'customer_phone', 'shipping_address']);
        
        $order = new Order();
        jsonResponse($order->create($input));
        break;

    case 'orders.list':
        checkMethod('GET');
        requireAuth();
        
        $order = new Order();
        $page = (int)($_GET['page'] ?? 1);
        jsonResponse(['success' => true, 'data' => $order->getByUser($_SESSION['user_id'], $page)]);
        break;

    case 'orders.detail':
        checkMethod('GET');
        
        $orderId = (int)($_GET['id'] ?? 0);
        $orderCode = $_GET['code'] ?? '';
        
        if (!$orderId && !$orderCode) {
            errorResponse('Order ID hoặc code required');
        }
        
        $order = new Order();
        $result = $orderId ? $order->getById($orderId) : $order->getByCode($orderCode);
        
        if (!$result) {
            errorResponse('Không tìm thấy đơn hàng', 404);
        }
        
        // Check permission
        if (isset($_SESSION['user_id']) && $result['user_id'] != $_SESSION['user_id']) {
            errorResponse('Không có quyền xem đơn hàng này', 403);
        }
        
        jsonResponse(['success' => true, 'data' => $result]);
        break;

    case 'orders.cancel':
        checkMethod('POST');
        requireAuth();
        
        $input = getJsonInput();
        $orderId = (int)($input['order_id'] ?? 0);
        
        if (!$orderId) {
            errorResponse('Order ID required');
        }
        
        $order = new Order();
        jsonResponse($order->cancel($orderId, $_SESSION['user_id']));
        break;

    case 'orders.track':
        if ($method !== 'POST' && $method !== 'GET') {
            errorResponse('Method not allowed', 405);
        }
        
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
        
        $order = new Order();
        $result = $order->track($orderCode, $contact);
        
        if (!$result) {
            errorResponse('Không tìm thấy đơn hàng', 404);
        }
        
        jsonResponse(['success' => true, 'data' => $result]);
        break;

    default:
        return false; // Not handled
}
