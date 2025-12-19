<?php
/**
 * Product-related API endpoints
 */

// Prevent direct access
if (!defined('LUXE_APP')) {
    exit('Direct access not allowed');
}

$method = getMethod();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'products':
        checkMethod('GET');
        
        $product = new Product();
        $filters = [
            'category_id' => $_GET['category_id'] ?? null,
            'category_slug' => $_GET['category'] ?? null,
            'gender' => $_GET['gender'] ?? null,
            'min_price' => $_GET['min_price'] ?? null,
            'max_price' => $_GET['max_price'] ?? null,
            'is_featured' => isset($_GET['featured']),
            'is_new' => isset($_GET['new']),
            'is_bestseller' => isset($_GET['bestseller']),
            'on_sale' => isset($_GET['sale']) || !empty($_GET['on_sale']),
            'search' => $_GET['q'] ?? null,
            'sort' => $_GET['sort'] ?? 'newest'
        ];
        
        $page = (int) ($_GET['page'] ?? 1);
        $result = $product->getAll($filters, $page);
        
        jsonResponse(['success' => true, 'data' => $result]);
        break;

    case 'products.featured':
        checkMethod('GET');
        
        $product = new Product();
        $limit = (int) ($_GET['limit'] ?? 8);
        $result = $product->getFeatured($limit);
        
        jsonResponse(['success' => true, 'data' => $result]);
        break;

    case 'products.new':
        checkMethod('GET');
        
        $product = new Product();
        $limit = (int) ($_GET['limit'] ?? 8);
        $result = $product->getNewArrivals($limit);
        
        jsonResponse(['success' => true, 'data' => $result]);
        break;

    case 'products.bestsellers':
        checkMethod('GET');
        
        $product = new Product();
        $limit = (int) ($_GET['limit'] ?? 8);
        $result = $product->getBestsellers($limit);
        
        jsonResponse(['success' => true, 'data' => $result]);
        break;

    case 'products.sale':
        checkMethod('GET');
        
        $product = new Product();
        $limit = (int) ($_GET['limit'] ?? 8);
        $result = $product->getOnSale($limit);
        
        jsonResponse(['success' => true, 'data' => $result]);
        break;

    case 'product':
        checkMethod('GET');
        
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
        checkMethod('GET');
        
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
        checkMethod('GET');
        
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

    case 'categories':
        checkMethod('GET');
        
        $db = Database::getInstance();
        $sql = "SELECT c.*, 
                       (SELECT COUNT(*) FROM products WHERE category_id = c.id AND status = 'active') as product_count
                FROM categories c 
                WHERE c.is_active = 1 
                ORDER BY c.sort_order, c.name";
        $categories = $db->query($sql)->fetchAll();
        
        jsonResponse(['success' => true, 'data' => $categories]);
        break;

    default:
        return false; // Not handled
}
