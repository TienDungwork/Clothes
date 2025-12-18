<?php
/**
 * LUXE Fashion - User Model
 * 
 * Xử lý authentication và user management
 */

class User
{
    private $db;
    private $table = 'users';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Register new user
     */
    public function register(array $data): array
    {
        // Validate email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Email không hợp lệ'];
        }

        // Check if email exists
        if ($this->emailExists($data['email'])) {
            return ['success' => false, 'message' => 'Email đã được sử dụng'];
        }

        // Validate password
        if (strlen($data['password']) < 6) {
            return ['success' => false, 'message' => 'Mật khẩu phải có ít nhất 6 ký tự'];
        }

        // Hash password
        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => HASH_COST]);

        try {
            $sql = "INSERT INTO {$this->table} (email, password, full_name, phone) VALUES (?, ?, ?, ?)";
            $this->db->query($sql, [
                $data['email'],
                $hashedPassword,
                $data['full_name'],
                $data['phone'] ?? null
            ]);

            $userId = $this->db->lastInsertId();

            return [
                'success' => true,
                'message' => 'Đăng ký thành công',
                'user_id' => $userId
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Có lỗi xảy ra, vui lòng thử lại'];
        }
    }

    /**
     * Login user
     */
    public function login(string $email, string $password, bool $remember = false): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = ? AND status = 'active'";
        $user = $this->db->query($sql, [$email])->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Email hoặc mật khẩu không đúng'];
        }

        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role'];

        // Remember me
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $hashedToken = password_hash($token, PASSWORD_BCRYPT);
            
            $sql = "UPDATE {$this->table} SET remember_token = ? WHERE id = ?";
            $this->db->query($sql, [$hashedToken, $user['id']]);
            
            setcookie('remember_token', $token, time() + (86400 * 30), '/', '', true, true);
            setcookie('user_id', $user['id'], time() + (86400 * 30), '/', '', true, true);
        }

        // Merge guest cart
        $cart = new Cart();
        $cart->mergeGuestCart($user['id']);

        return [
            'success' => true,
            'message' => 'Đăng nhập thành công',
            'user' => $this->sanitizeUser($user)
        ];
    }

    /**
     * Logout user
     */
    public function logout(): void
    {
        // Clear remember token
        if (isset($_SESSION['user_id'])) {
            $sql = "UPDATE {$this->table} SET remember_token = NULL WHERE id = ?";
            $this->db->query($sql, [$_SESSION['user_id']]);
        }

        // Clear cookies
        setcookie('remember_token', '', time() - 3600, '/');
        setcookie('user_id', '', time() - 3600, '/');

        // Destroy session
        session_destroy();
    }

    /**
     * Get user by ID
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $user = $this->db->query($sql, [$id])->fetch();
        
        return $user ? $this->sanitizeUser($user) : null;
    }

    /**
     * Update user profile
     */
    public function update(int $id, array $data): array
    {
        $updates = [];
        $params = [];

        if (!empty($data['full_name'])) {
            $updates[] = 'full_name = ?';
            $params[] = $data['full_name'];
        }

        if (!empty($data['phone'])) {
            $updates[] = 'phone = ?';
            $params[] = $data['phone'];
        }

        if (!empty($data['avatar'])) {
            $updates[] = 'avatar = ?';
            $params[] = $data['avatar'];
        }

        if (empty($updates)) {
            return ['success' => false, 'message' => 'Không có dữ liệu để cập nhật'];
        }

        $params[] = $id;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $updates) . " WHERE id = ?";
        $this->db->query($sql, $params);

        return ['success' => true, 'message' => 'Cập nhật thông tin thành công'];
    }

    /**
     * Change password
     */
    public function changePassword(int $id, string $currentPassword, string $newPassword): array
    {
        $sql = "SELECT password FROM {$this->table} WHERE id = ?";
        $user = $this->db->query($sql, [$id])->fetch();

        if (!$user || !password_verify($currentPassword, $user['password'])) {
            return ['success' => false, 'message' => 'Mật khẩu hiện tại không đúng'];
        }

        if (strlen($newPassword) < 6) {
            return ['success' => false, 'message' => 'Mật khẩu mới phải có ít nhất 6 ký tự'];
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => HASH_COST]);
        $sql = "UPDATE {$this->table} SET password = ? WHERE id = ?";
        $this->db->query($sql, [$hashedPassword, $id]);

        return ['success' => true, 'message' => 'Đổi mật khẩu thành công'];
    }

    /**
     * Get user addresses
     */
    public function getAddresses(int $userId): array
    {
        $sql = "SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC";
        return $this->db->query($sql, [$userId])->fetchAll();
    }

    /**
     * Add user address
     */
    public function addAddress(int $userId, array $data): array
    {
        // If this is the first address or marked as default, update others
        if (!empty($data['is_default'])) {
            $sql = "UPDATE user_addresses SET is_default = 0 WHERE user_id = ?";
            $this->db->query($sql, [$userId]);
        }

        $sql = "INSERT INTO user_addresses (user_id, full_name, phone, province, district, ward, address_detail, is_default) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $this->db->query($sql, [
            $userId,
            $data['full_name'],
            $data['phone'],
            $data['province'],
            $data['district'],
            $data['ward'],
            $data['address_detail'],
            $data['is_default'] ?? 0
        ]);

        return ['success' => true, 'message' => 'Thêm địa chỉ thành công', 'id' => $this->db->lastInsertId()];
    }

    /**
     * Get user wishlist
     */
    public function getWishlist(int $userId): array
    {
        $sql = "SELECT p.*, 
                       (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image
                FROM wishlists w
                JOIN products p ON w.product_id = p.id
                WHERE w.user_id = ?
                ORDER BY w.created_at DESC";
        
        return $this->db->query($sql, [$userId])->fetchAll();
    }

    /**
     * Add to wishlist
     */
    public function addToWishlist(int $userId, int $productId): array
    {
        $sql = "INSERT IGNORE INTO wishlists (user_id, product_id) VALUES (?, ?)";
        $this->db->query($sql, [$userId, $productId]);

        return ['success' => true, 'message' => 'Đã thêm vào danh sách yêu thích'];
    }

    /**
     * Remove from wishlist
     */
    public function removeFromWishlist(int $userId, int $productId): array
    {
        $sql = "DELETE FROM wishlists WHERE user_id = ? AND product_id = ?";
        $this->db->query($sql, [$userId, $productId]);

        return ['success' => true, 'message' => 'Đã xóa khỏi danh sách yêu thích'];
    }

    /**
     * Check if email exists
     */
    public function emailExists(string $email): bool
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE email = ?";
        return $this->db->query($sql, [$email])->fetchColumn() > 0;
    }

    /**
     * Sanitize user data for output
     */
    private function sanitizeUser(array $user): array
    {
        unset($user['password'], $user['remember_token']);
        return $user;
    }
}
