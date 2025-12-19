<?php
/**
 * LUXE Fashion - Admin API
 * 
 * Admin-related API endpoints
 */

// Prevent direct access
if (!defined('LUXE_APP')) {
    exit('Direct access not allowed');
}

$method = getMethod();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'admin.products.create':
        checkMethod('POST');
        requireAdmin();
        
        $input = getJsonInput();
        if (empty($input['name']) || empty($input['price'])) {
            errorResponse('Tên và giá sản phẩm là bắt buộc');
        }
        
        $db = Database::getInstance();
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
        checkMethod('POST');
        requireAdmin();
        
        $input = getJsonInput();
        if (empty($input['id'])) {
            errorResponse('ID sản phẩm là bắt buộc');
        }
        
        $db = Database::getInstance();
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
        checkMethod('POST');
        requireAdmin();
        
        $input = getJsonInput();
        if (empty($input['id'])) {
            errorResponse('ID sản phẩm là bắt buộc');
        }
        
        $db = Database::getInstance();
        
        // Delete related records first
        $db->query("DELETE FROM product_images WHERE product_id = ?", [$input['id']]);
        $db->query("DELETE FROM product_variants WHERE product_id = ?", [$input['id']]);
        $db->query("DELETE FROM products WHERE id = ?", [$input['id']]);
        
        jsonResponse(['success' => true, 'message' => 'Xóa sản phẩm thành công']);
        break;

    default:
        return false; // Not handled
}
