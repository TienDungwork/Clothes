<?php
/**
 * Cart-related API endpoints
 */

// Prevent direct access
if (!defined('LUXE_APP')) {
    exit('Direct access not allowed');
}

$method = getMethod();
$action = $_GET['action'] ?? '';

switch ($action) {
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
        checkMethod('POST');
        
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
        if ($method !== 'POST' && $method !== 'PUT') {
            errorResponse('Method not allowed', 405);
        }
        
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
        if ($method !== 'POST' && $method !== 'DELETE') {
            errorResponse('Method not allowed', 405);
        }
        
        $input = getJsonInput();
        $itemId = (int) ($input['item_id'] ?? $_GET['item_id'] ?? 0);
        
        if (!$itemId) {
            errorResponse('Item ID required');
        }
        
        $cart = new Cart();
        jsonResponse($cart->remove($itemId));
        break;

    case 'cart.count':
        checkMethod('GET');
        
        $cart = new Cart();
        jsonResponse(['success' => true, 'count' => $cart->getCount()]);
        break;

    case 'cart.coupon':
        checkMethod('POST');
        
        $input = getJsonInput();
        $code = $input['code'] ?? '';
        
        if (!$code) {
            errorResponse('Coupon code required');
        }
        
        $cart = new Cart();
        jsonResponse($cart->applyCoupon($code));
        break;

    default:
        return false; // Not handled
}
