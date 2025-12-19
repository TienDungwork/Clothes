<?php
/**
 * LUXE Fashion - Order Model
 * 
 * Xử lý đơn hàng và thanh toán
 */

class Order
{
    private $db;
    private $table = 'orders';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Create new order
     * Supports partial checkout: if items are provided in $data, only those items are checked out
     */
    public function create(array $data): array
    {
        $cart = new Cart();
        $cartData = $cart->getCart();

        // Check if specific items were provided (partial checkout)
        $itemsToCheckout = [];
        $itemIdsToRemove = [];
        
        if (!empty($data['items']) && is_array($data['items'])) {
            // Partial checkout: only process specified items
            $requestedIds = array_column($data['items'], 'id');
            foreach ($cartData['items'] as $item) {
                if (in_array($item['id'], $requestedIds)) {
                    $itemsToCheckout[] = $item;
                    $itemIdsToRemove[] = $item['id'];
                }
            }
            
            if (empty($itemsToCheckout)) {
                return ['success' => false, 'message' => 'Không tìm thấy sản phẩm đã chọn trong giỏ hàng'];
            }
            
            // Use provided totals if available, otherwise calculate
            $subtotal = $data['subtotal'] ?? array_sum(array_column($itemsToCheckout, 'subtotal'));
            $shippingFee = $data['shipping_fee'] ?? ($subtotal >= 500000 ? 0 : 30000);
        } else {
            // Full cart checkout (backward compatible)
            if (empty($cartData['items'])) {
                return ['success' => false, 'message' => 'Giỏ hàng trống'];
            }
            $itemsToCheckout = $cartData['items'];
            $subtotal = $cartData['subtotal'];
            $shippingFee = $cartData['shipping_fee'];
        }

        $this->db->beginTransaction();

        try {
            // Generate order code
            $orderCode = $this->generateOrderCode();

            // Calculate totals
            $discountAmount = $data['discount_amount'] ?? 0;
            $totalAmount = $subtotal + $shippingFee - $discountAmount;

            // Create order
            // Determine payment status based on payment method
            // For bank_transfer, we could mark as 'paid' if you trust QR payments
            // For COD, always 'pending' until delivery
            $paymentMethod = $data['payment_method'] ?? 'cod';
            $paymentStatus = 'pending'; // Default
            
            // If bank_transfer is selected, set to 'paid' (assuming QR payment was completed)
            // Or keep 'pending' if you want admin to manually confirm
            if ($paymentMethod === 'bank_transfer') {
                $paymentStatus = 'paid'; // Auto-mark as paid for bank transfers
            }
            
            $sql = "INSERT INTO {$this->table} 
                    (user_id, order_code, customer_name, customer_email, customer_phone, 
                     shipping_address, subtotal, shipping_fee, discount_amount, total_amount,
                     coupon_code, payment_method, payment_status, notes) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $this->db->query($sql, [
                $_SESSION['user_id'] ?? null,
                $orderCode,
                $data['customer_name'],
                $data['customer_email'],
                $data['customer_phone'],
                $data['shipping_address'],
                $subtotal,
                $shippingFee,
                $discountAmount,
                $totalAmount,
                $data['coupon_code'] ?? null,
                $paymentMethod,
                $paymentStatus,
                $data['notes'] ?? null
            ]);

            $orderId = $this->db->lastInsertId();

            // Create order items
            foreach ($itemsToCheckout as $item) {
                $this->createOrderItem($orderId, $item);

                // Update product stock
                $product = new Product();
                $product->updateStock($item['product_id'], $item['quantity'], $item['variant_id'] ?? null);

                // Update sold count
                $sql = "UPDATE products SET sold_count = sold_count + ? WHERE id = ?";
                $this->db->query($sql, [$item['quantity'], $item['product_id']]);
            }

            // Update coupon usage if applicable
            if (!empty($data['coupon_code'])) {
                $sql = "UPDATE coupons SET used_count = used_count + 1 WHERE code = ?";
                $this->db->query($sql, [$data['coupon_code']]);
            }

            // Remove checked-out items from cart (partial or full)
            if (!empty($itemIdsToRemove)) {
                // Partial checkout: only remove checked-out items
                foreach ($itemIdsToRemove as $itemId) {
                    $cart->remove($itemId);
                }
            } else {
                // Full checkout: clear entire cart
                $cart->clear();
            }

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Đặt hàng thành công',
                'order_id' => $orderId,
                'order_code' => $orderCode
            ];

        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()];
        }
    }

    /**
     * Create order item
     */
    private function createOrderItem(int $orderId, array $item): void
    {
        $variantInfo = '';
        if (!empty($item['size'])) $variantInfo .= 'Size: ' . $item['size'];
        if (!empty($item['color'])) $variantInfo .= ($variantInfo ? ', ' : '') . 'Màu: ' . $item['color'];

        $sql = "INSERT INTO order_items 
                (order_id, product_id, variant_id, product_name, variant_info, quantity, unit_price, total_price) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $this->db->query($sql, [
            $orderId,
            $item['product_id'],
            $item['variant_id'] ?? null,
            $item['name'],
            $variantInfo ?: null,
            $item['quantity'],
            $item['price'],
            $item['subtotal']
        ]);
    }

    /**
     * Get order by ID
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $order = $this->db->query($sql, [$id])->fetch();

        if ($order) {
            $order['items'] = $this->getOrderItems($id);
        }

        return $order;
    }

    /**
     * Get order by code
     */
    public function getByCode(string $code): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE order_code = ?";
        $order = $this->db->query($sql, [$code])->fetch();

        if ($order) {
            $order['items'] = $this->getOrderItems($order['id']);
        }

        return $order;
    }

    /**
     * Get order items
     */
    public function getOrderItems(int $orderId): array
    {
        $sql = "SELECT oi.*, 
                       COALESCE(p.image, (SELECT image_url FROM product_images WHERE product_id = oi.product_id AND is_primary = 1 LIMIT 1)) as product_image
                FROM order_items oi 
                LEFT JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?";
        
        return $this->db->query($sql, [$orderId])->fetchAll();
    }

    /**
     * Get user orders
     */
    public function getByUser(int $userId, int $page = 1, int $perPage = ORDERS_PER_PAGE): array
    {
        $offset = ($page - 1) * $perPage;

        // Get total
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE user_id = ?";
        $total = $this->db->query($sql, [$userId])->fetchColumn();

        // Get orders
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}";
        $orders = $this->db->query($sql, [$userId])->fetchAll();

        // Get items for each order
        foreach ($orders as &$order) {
            $order['items'] = $this->getOrderItems($order['id']);
        }

        return [
            'data' => $orders,
            'total' => (int) $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Update order status
     */
    public function updateStatus(int $orderId, string $status): array
    {
        $validStatuses = ['pending', 'confirmed', 'processing', 'shipping', 'delivered', 'cancelled', 'returned'];
        
        if (!in_array($status, $validStatuses)) {
            return ['success' => false, 'message' => 'Trạng thái không hợp lệ'];
        }

        $updates = ['order_status = ?'];
        $params = [$status];

        // Set timestamps for specific statuses
        if ($status === 'shipping') {
            $updates[] = 'shipped_at = NOW()';
        } elseif ($status === 'delivered') {
            $updates[] = 'delivered_at = NOW()';
        }

        $params[] = $orderId;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $updates) . " WHERE id = ?";
        $this->db->query($sql, $params);

        return ['success' => true, 'message' => 'Cập nhật trạng thái thành công'];
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus(int $orderId, string $status): array
    {
        $validStatuses = ['pending', 'paid', 'failed', 'refunded'];
        
        if (!in_array($status, $validStatuses)) {
            return ['success' => false, 'message' => 'Trạng thái thanh toán không hợp lệ'];
        }

        $sql = "UPDATE {$this->table} SET payment_status = ? WHERE id = ?";
        $this->db->query($sql, [$status, $orderId]);

        return ['success' => true, 'message' => 'Cập nhật trạng thái thanh toán thành công'];
    }

    /**
     * Cancel order
     */
    public function cancel(int $orderId, ?int $userId = null): array
    {
        // Get order
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $order = $this->db->query($sql, [$orderId])->fetch();

        if (!$order) {
            return ['success' => false, 'message' => 'Đơn hàng không tồn tại'];
        }

        // Check ownership if user is provided
        if ($userId && $order['user_id'] != $userId) {
            return ['success' => false, 'message' => 'Không có quyền hủy đơn hàng này'];
        }

        // Check if order can be cancelled
        if (!in_array($order['order_status'], ['pending', 'confirmed'])) {
            return ['success' => false, 'message' => 'Đơn hàng không thể hủy ở trạng thái hiện tại'];
        }

        $this->db->beginTransaction();

        try {
            // Update order status
            $sql = "UPDATE {$this->table} SET order_status = 'cancelled' WHERE id = ?";
            $this->db->query($sql, [$orderId]);

            // Restore stock
            $items = $this->getOrderItems($orderId);
            foreach ($items as $item) {
                $sql = "UPDATE products SET stock_quantity = stock_quantity + ?, sold_count = sold_count - ? WHERE id = ?";
                $this->db->query($sql, [$item['quantity'], $item['quantity'], $item['product_id']]);

                if ($item['variant_id']) {
                    $sql = "UPDATE product_variants SET stock_quantity = stock_quantity + ? WHERE id = ?";
                    $this->db->query($sql, [$item['quantity'], $item['variant_id']]);
                }
            }

            $this->db->commit();

            return ['success' => true, 'message' => 'Hủy đơn hàng thành công'];

        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => 'Có lỗi xảy ra'];
        }
    }

    /**
     * Track order by code and email/phone
     */
    public function track(string $orderCode, string $contact): ?array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE order_code = ? AND (customer_email = ? OR customer_phone = ?)";
        $order = $this->db->query($sql, [$orderCode, $contact, $contact])->fetch();

        if ($order) {
            $order['items'] = $this->getOrderItems($order['id']);
        }

        return $order;
    }

    /**
     * Generate unique order code
     */
    private function generateOrderCode(): string
    {
        $prefix = 'LX';
        $date = date('ymd');
        $random = strtoupper(bin2hex(random_bytes(3)));
        
        return $prefix . $date . $random;
    }

    /**
     * Get order statistics (for admin)
     */
    public function getStatistics(): array
    {
        $stats = [];

        // Total orders today
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE DATE(created_at) = CURDATE()";
        $stats['orders_today'] = $this->db->query($sql)->fetchColumn();

        // Revenue today
        $sql = "SELECT COALESCE(SUM(total_amount), 0) FROM {$this->table} 
                WHERE DATE(created_at) = CURDATE() AND payment_status = 'paid'";
        $stats['revenue_today'] = $this->db->query($sql)->fetchColumn();

        // Pending orders
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE order_status = 'pending'";
        $stats['pending_orders'] = $this->db->query($sql)->fetchColumn();

        // Total revenue this month
        $sql = "SELECT COALESCE(SUM(total_amount), 0) FROM {$this->table} 
                WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) 
                AND payment_status = 'paid'";
        $stats['revenue_month'] = $this->db->query($sql)->fetchColumn();

        return $stats;
    }
}
