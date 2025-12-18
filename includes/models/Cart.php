<?php
/**
 * LUXE Fashion - Cart Model
 * 
 * Xử lý giỏ hàng với session và database
 */

class Cart
{
    private $db;
    private $cartId;
    private $userId;
    private $sessionId;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->sessionId = session_id();
        $this->userId = $_SESSION['user_id'] ?? null;
        $this->initCart();
    }

    /**
     * Initialize or get existing cart
     */
    private function initCart(): void
    {
        // Try to find existing cart
        if ($this->userId) {
            $sql = "SELECT id FROM carts WHERE user_id = ?";
            $cart = $this->db->query($sql, [$this->userId])->fetch();
        } else {
            $sql = "SELECT id FROM carts WHERE session_id = ? AND user_id IS NULL";
            $cart = $this->db->query($sql, [$this->sessionId])->fetch();
        }

        if ($cart) {
            $this->cartId = $cart['id'];
        } else {
            // Create new cart
            $sql = "INSERT INTO carts (user_id, session_id) VALUES (?, ?)";
            $this->db->query($sql, [$this->userId, $this->sessionId]);
            $this->cartId = $this->db->lastInsertId();
        }
    }

    /**
     * Add item to cart
     */
    public function add(int $productId, int $quantity = 1, ?int $variantId = null): array
    {
        // Validate product
        $product = $this->getProductInfo($productId);
        if (!$product) {
            return ['success' => false, 'message' => 'Sản phẩm không tồn tại'];
        }

        // Check stock
        $stockCheck = $this->checkStock($productId, $variantId, $quantity);
        if (!$stockCheck['success']) {
            return $stockCheck;
        }

        // Get price
        $price = $product['sale_price'] ?? $product['price'];

        // Check if item already exists in cart
        $existingItem = $this->findItem($productId, $variantId);

        if ($existingItem) {
            // Update quantity
            $newQuantity = $existingItem['quantity'] + $quantity;
            
            // Check stock for new quantity
            $stockCheck = $this->checkStock($productId, $variantId, $newQuantity);
            if (!$stockCheck['success']) {
                return $stockCheck;
            }

            $sql = "UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE id = ?";
            $this->db->query($sql, [$newQuantity, $existingItem['id']]);
        } else {
            // Add new item
            $sql = "INSERT INTO cart_items (cart_id, product_id, variant_id, quantity, price) VALUES (?, ?, ?, ?, ?)";
            $this->db->query($sql, [$this->cartId, $productId, $variantId, $quantity, $price]);
        }

        return [
            'success' => true,
            'message' => 'Đã thêm vào giỏ hàng',
            'cart' => $this->getCart()
        ];
    }

    /**
     * Update item quantity
     */
    public function update(int $itemId, int $quantity): array
    {
        $item = $this->getItemById($itemId);
        if (!$item) {
            return ['success' => false, 'message' => 'Sản phẩm không tồn tại trong giỏ'];
        }

        if ($quantity <= 0) {
            return $this->remove($itemId);
        }

        // Check stock
        $stockCheck = $this->checkStock($item['product_id'], $item['variant_id'], $quantity);
        if (!$stockCheck['success']) {
            return $stockCheck;
        }

        $sql = "UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE id = ? AND cart_id = ?";
        $this->db->query($sql, [$quantity, $itemId, $this->cartId]);

        return [
            'success' => true,
            'message' => 'Đã cập nhật giỏ hàng',
            'cart' => $this->getCart()
        ];
    }

    /**
     * Remove item from cart
     */
    public function remove(int $itemId): array
    {
        $sql = "DELETE FROM cart_items WHERE id = ? AND cart_id = ?";
        $this->db->query($sql, [$itemId, $this->cartId]);

        return [
            'success' => true,
            'message' => 'Đã xóa sản phẩm khỏi giỏ',
            'cart' => $this->getCart()
        ];
    }

    /**
     * Clear cart
     */
    public function clear(): array
    {
        $sql = "DELETE FROM cart_items WHERE cart_id = ?";
        $this->db->query($sql, [$this->cartId]);

        return [
            'success' => true,
            'message' => 'Đã xóa toàn bộ giỏ hàng',
            'cart' => $this->getCart()
        ];
    }

    /**
     * Get cart with items
     */
    public function getCart(): array
    {
        $sql = "SELECT ci.*, p.name, p.slug, p.price as original_price, p.sale_price,
                       pv.size, pv.color, pv.color_code,
                       COALESCE(p.image, (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1)) as image
                FROM cart_items ci
                JOIN products p ON ci.product_id = p.id
                LEFT JOIN product_variants pv ON ci.variant_id = pv.id
                WHERE ci.cart_id = ?
                ORDER BY ci.created_at DESC";

        $items = $this->db->query($sql, [$this->cartId])->fetchAll();

        // Calculate totals
        $subtotal = 0;
        $itemCount = 0;

        foreach ($items as &$item) {
            $item['subtotal'] = $item['price'] * $item['quantity'];
            $subtotal += $item['subtotal'];
            $itemCount += $item['quantity'];
        }

        // Calculate shipping
        $shippingFee = $subtotal >= FREE_SHIPPING_THRESHOLD ? 0 : DEFAULT_SHIPPING_FEE;

        return [
            'items' => $items,
            'item_count' => $itemCount,
            'subtotal' => $subtotal,
            'shipping_fee' => $shippingFee,
            'total' => $subtotal + $shippingFee
        ];
    }

    /**
     * Get cart item count
     */
    public function getCount(): int
    {
        $sql = "SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE cart_id = ?";
        return (int) $this->db->query($sql, [$this->cartId])->fetchColumn();
    }

    /**
     * Find item in cart
     */
    private function findItem(int $productId, ?int $variantId = null): ?array
    {
        if ($variantId) {
            $sql = "SELECT * FROM cart_items WHERE cart_id = ? AND product_id = ? AND variant_id = ?";
            return $this->db->query($sql, [$this->cartId, $productId, $variantId])->fetch();
        } else {
            $sql = "SELECT * FROM cart_items WHERE cart_id = ? AND product_id = ? AND variant_id IS NULL";
            return $this->db->query($sql, [$this->cartId, $productId])->fetch();
        }
    }

    /**
     * Get item by ID
     */
    private function getItemById(int $itemId): ?array
    {
        $sql = "SELECT * FROM cart_items WHERE id = ? AND cart_id = ?";
        return $this->db->query($sql, [$itemId, $this->cartId])->fetch();
    }

    /**
     * Get product info
     */
    private function getProductInfo(int $productId): ?array
    {
        $sql = "SELECT id, name, price, sale_price, stock_quantity, status FROM products WHERE id = ? AND status = 'active'";
        return $this->db->query($sql, [$productId])->fetch();
    }

    /**
     * Check stock availability
     */
    private function checkStock(int $productId, ?int $variantId, int $quantity): array
    {
        if ($variantId) {
            $sql = "SELECT stock_quantity FROM product_variants WHERE id = ?";
            $stock = $this->db->query($sql, [$variantId])->fetchColumn();
        } else {
            $sql = "SELECT stock_quantity FROM products WHERE id = ?";
            $stock = $this->db->query($sql, [$productId])->fetchColumn();
        }

        if ($stock < $quantity) {
            return [
                'success' => false,
                'message' => $stock > 0 ? "Chỉ còn {$stock} sản phẩm trong kho" : 'Sản phẩm đã hết hàng'
            ];
        }

        return ['success' => true];
    }

    /**
     * Merge guest cart to user cart after login
     */
    public function mergeGuestCart(int $userId): void
    {
        // Find guest cart
        $sql = "SELECT id FROM carts WHERE session_id = ? AND user_id IS NULL";
        $guestCart = $this->db->query($sql, [$this->sessionId])->fetch();

        if (!$guestCart) {
            return;
        }

        // Find or create user cart
        $sql = "SELECT id FROM carts WHERE user_id = ?";
        $userCart = $this->db->query($sql, [$userId])->fetch();

        if (!$userCart) {
            // Update guest cart to user cart
            $sql = "UPDATE carts SET user_id = ? WHERE id = ?";
            $this->db->query($sql, [$userId, $guestCart['id']]);
            $this->cartId = $guestCart['id'];
        } else {
            // Merge items from guest cart to user cart
            $sql = "UPDATE cart_items SET cart_id = ? WHERE cart_id = ?";
            $this->db->query($sql, [$userCart['id'], $guestCart['id']]);

            // Delete guest cart
            $sql = "DELETE FROM carts WHERE id = ?";
            $this->db->query($sql, [$guestCart['id']]);

            $this->cartId = $userCart['id'];
        }

        $this->userId = $userId;
    }

    /**
     * Apply coupon code
     */
    public function applyCoupon(string $code): array
    {
        $sql = "SELECT * FROM coupons WHERE code = ? AND is_active = 1 AND start_date <= NOW() AND end_date >= NOW()";
        $coupon = $this->db->query($sql, [$code])->fetch();

        if (!$coupon) {
            return ['success' => false, 'message' => 'Mã giảm giá không hợp lệ hoặc đã hết hạn'];
        }

        // Check usage limit
        if ($coupon['usage_limit'] && $coupon['used_count'] >= $coupon['usage_limit']) {
            return ['success' => false, 'message' => 'Mã giảm giá đã hết lượt sử dụng'];
        }

        $cart = $this->getCart();

        // Check minimum order amount
        if ($cart['subtotal'] < $coupon['min_order_amount']) {
            return [
                'success' => false,
                'message' => 'Đơn hàng tối thiểu ' . number_format($coupon['min_order_amount']) . '₫ để áp dụng mã này'
            ];
        }

        // Calculate discount
        if ($coupon['discount_type'] === 'percentage') {
            $discount = $cart['subtotal'] * ($coupon['discount_value'] / 100);
            if ($coupon['max_discount_amount'] && $discount > $coupon['max_discount_amount']) {
                $discount = $coupon['max_discount_amount'];
            }
        } else {
            $discount = $coupon['discount_value'];
        }

        return [
            'success' => true,
            'message' => 'Áp dụng mã giảm giá thành công',
            'coupon' => [
                'code' => $coupon['code'],
                'discount' => $discount,
                'description' => $coupon['description']
            ],
            'new_total' => $cart['total'] - $discount
        ];
    }
}
