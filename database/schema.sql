-- Create database
CREATE DATABASE IF NOT EXISTS luxe_fashion CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE luxe_fashion;

-- USERS TABLE
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    avatar VARCHAR(255) DEFAULT NULL,
    role ENUM('customer', 'admin') DEFAULT 'customer',
    status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
    email_verified_at TIMESTAMP NULL,
    remember_token VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_status (status)
) ENGINE=InnoDB;

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    image VARCHAR(255),
    parent_id INT DEFAULT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_parent (parent_id)
) ENGINE=InnoDB;

-- PRODUCTS TABLE
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    gender ENUM('nam', 'nu', 'unisex', 'phu-kien') DEFAULT 'unisex',
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    short_description VARCHAR(500),
    price DECIMAL(12, 0) NOT NULL,
    sale_price DECIMAL(12, 0) DEFAULT NULL,
    sku VARCHAR(50) UNIQUE,
    stock_quantity INT DEFAULT 0,
    is_featured TINYINT(1) DEFAULT 0,
    is_new TINYINT(1) DEFAULT 0,
    is_bestseller TINYINT(1) DEFAULT 0,
    status ENUM('active', 'inactive', 'out_of_stock') DEFAULT 'active',
    views INT DEFAULT 0,
    sold_count INT DEFAULT 0,
    rating_avg DECIMAL(2, 1) DEFAULT 0,
    rating_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    INDEX idx_slug (slug),
    INDEX idx_category (category_id),
    INDEX idx_gender (gender),
    INDEX idx_price (price),
    INDEX idx_featured (is_featured),
    INDEX idx_status (status),
    FULLTEXT INDEX ft_search (name, description)
) ENGINE=InnoDB;

-- PRODUCT IMAGES TABLE
CREATE TABLE product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255),
    sort_order INT DEFAULT 0,
    is_primary TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product (product_id)
) ENGINE=InnoDB;
 
-- PRODUCT VARIANTS TABLE (Size, Color)

CREATE TABLE product_variants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    size VARCHAR(20),
    color VARCHAR(50),
    color_code VARCHAR(7),
    price_adjustment DECIMAL(12, 0) DEFAULT 0,
    stock_quantity INT DEFAULT 0,
    sku VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product (product_id)
) ENGINE=InnoDB;

-- USER ADDRESSES TABLE
CREATE TABLE user_addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    province VARCHAR(100) NOT NULL,
    district VARCHAR(100) NOT NULL,
    ward VARCHAR(100) NOT NULL,
    address_detail VARCHAR(255) NOT NULL,
    is_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id)
) ENGINE=InnoDB;

-- CARTS TABLE
CREATE TABLE carts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    session_id VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_session (session_id)
) ENGINE=InnoDB;

-- CART ITEMS TABLE
CREATE TABLE cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cart_id INT NOT NULL,
    product_id INT NOT NULL,
    variant_id INT DEFAULT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(12, 0) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL,
    INDEX idx_cart (cart_id)
) ENGINE=InnoDB;

-- ORDERS TABLE
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    order_code VARCHAR(20) NOT NULL UNIQUE,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(255) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    shipping_address TEXT NOT NULL,
    subtotal DECIMAL(12, 0) NOT NULL,
    shipping_fee DECIMAL(12, 0) DEFAULT 0,
    discount_amount DECIMAL(12, 0) DEFAULT 0,
    total_amount DECIMAL(12, 0) NOT NULL,
    coupon_code VARCHAR(50) DEFAULT NULL,
    payment_method ENUM('cod', 'bank_transfer', 'momo', 'vnpay', 'credit_card') DEFAULT 'cod',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    order_status ENUM('pending', 'confirmed', 'processing', 'shipping', 'delivered', 'cancelled', 'returned') DEFAULT 'pending',
    notes TEXT,
    shipped_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_order_code (order_code),
    INDEX idx_user (user_id),
    INDEX idx_status (order_status),
    INDEX idx_payment (payment_status)
) ENGINE=InnoDB;

-- ORDER ITEMS TABLE
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    variant_id INT DEFAULT NULL,
    product_name VARCHAR(255) NOT NULL,
    variant_info VARCHAR(100),
    quantity INT NOT NULL,
    unit_price DECIMAL(12, 0) NOT NULL,
    total_price DECIMAL(12, 0) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_order (order_id)
) ENGINE=InnoDB;

-- COUPONS TABLE
CREATE TABLE coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255),
    discount_type ENUM('percentage', 'fixed') NOT NULL,
    discount_value DECIMAL(12, 2) NOT NULL,
    min_order_amount DECIMAL(12, 0) DEFAULT 0,
    max_discount_amount DECIMAL(12, 0) DEFAULT NULL,
    usage_limit INT DEFAULT NULL,
    used_count INT DEFAULT 0,
    start_date TIMESTAMP NOT NULL,
    end_date TIMESTAMP NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_active (is_active)
) ENGINE=InnoDB;

-- PRODUCT REVIEWS TABLE
CREATE TABLE product_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    order_id INT DEFAULT NULL,
    rating TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(255),
    content TEXT,
    images JSON,
    is_verified_purchase TINYINT(1) DEFAULT 0,
    is_approved TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    INDEX idx_product (product_id),
    INDEX idx_user (user_id),
    INDEX idx_approved (is_approved)
) ENGINE=InnoDB;

-- WISHLIST TABLE
CREATE TABLE wishlists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_wishlist (user_id, product_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- NEWSLETTER SUBSCRIBERS TABLE
CREATE TABLE newsletter_subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    is_active TINYINT(1) DEFAULT 1,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    unsubscribed_at TIMESTAMP NULL,
    INDEX idx_email (email)
) ENGINE=InnoDB;

-- BANNERS TABLE
CREATE TABLE banners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    subtitle VARCHAR(255),
    image_url VARCHAR(255) NOT NULL,
    link_url VARCHAR(255),
    position ENUM('hero', 'promo', 'sidebar') DEFAULT 'hero',
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    start_date TIMESTAMP NULL,
    end_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_position (position),
    INDEX idx_active (is_active)
) ENGINE=InnoDB;

-- SETTINGS TABLE
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_group VARCHAR(50) DEFAULT 'general',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (setting_key),
    INDEX idx_group (setting_group)
) ENGINE=InnoDB;

-- INSERT SAMPLE DATA

-- Insert Categories
INSERT INTO categories (name, slug, description, image) VALUES
('Thời Trang Nam', 'thoi-trang-nam', 'Bộ sưu tập thời trang nam cao cấp', 'categories/men.jpg'),
('Thời Trang Nữ', 'thoi-trang-nu', 'Bộ sưu tập thời trang nữ thanh lịch', 'categories/women.jpg'),
('Phụ Kiện', 'phu-kien', 'Phụ kiện thời trang đa dạng', 'categories/accessories.jpg'),
('Sale', 'sale', 'Sản phẩm giảm giá đặc biệt', 'categories/sale.jpg');

-- Insert Sub-categories
INSERT INTO categories (name, slug, description, parent_id) VALUES
('Áo Sơ Mi', 'ao-so-mi', 'Áo sơ mi nam các loại', 1),
('Quần Tây', 'quan-tay', 'Quần tây công sở', 1),
('Áo Khoác', 'ao-khoac', 'Áo khoác nam', 1),
('Đầm', 'dam', 'Đầm nữ các loại', 2),
('Áo Kiểu', 'ao-kieu', 'Áo kiểu nữ thời trang', 2),
('Chân Váy', 'chan-vay', 'Chân váy nữ', 2),
('Túi Xách', 'tui-xach', 'Túi xách thời trang', 3),
('Giày Dép', 'giay-dep', 'Giày dép nam nữ', 3),
('Khăn & Phụ Kiện', 'khan-phu-kien', 'Khăn và phụ kiện khác', 3);

-- Insert Products
INSERT INTO products (category_id, name, slug, description, short_description, price, sale_price, sku, stock_quantity, is_featured, is_new, is_bestseller, rating_avg, rating_count) VALUES
(8, 'Đầm Dạ Hội Luxury', 'dam-da-hoi-luxury', 'Đầm dạ hội sang trọng với thiết kế tinh tế, phù hợp cho các buổi tiệc và sự kiện quan trọng.', 'Đầm dạ hội sang trọng', 1890000, NULL, 'DDH001', 50, 1, 1, 0, 5.0, 48),
(5, 'Áo Sơ Mi Premium', 'ao-so-mi-premium', 'Áo sơ mi nam cao cấp với chất liệu cotton 100%, thoáng mát và sang trọng.', 'Áo sơ mi nam cao cấp', 890000, NULL, 'ASM001', 100, 1, 0, 1, 5.0, 125),
(7, 'Blazer Elegance', 'blazer-elegance', 'Áo blazer nam thiết kế hiện đại, phù hợp công sở và các buổi gặp mặt quan trọng.', 'Blazer nam thanh lịch', 1990000, 1390000, 'BLZ001', 30, 1, 0, 0, 4.5, 86),
(11, 'Túi Xách Classic', 'tui-xach-classic', 'Túi xách nữ phong cách classic, chất liệu da cao cấp.', 'Túi xách da cao cấp', 2490000, NULL, 'TXC001', 25, 1, 0, 0, 5.0, 63),
(10, 'Quần Palazzo Silk', 'quan-palazzo-silk', 'Quần palazzo nữ chất liệu silk mềm mại, thoải mái.', 'Quần palazzo lụa', 1190000, NULL, 'QPS001', 45, 0, 1, 0, 4.5, 42),
(12, 'Giày Cao Gót Velvet', 'giay-cao-got-velvet', 'Giày cao gót nhung sang trọng, êm ái khi di chuyển.', 'Giày cao gót nhung', 1690000, NULL, 'GCG001', 35, 1, 0, 1, 5.0, 91),
(13, 'Khăn Lụa Họa Tiết', 'khan-lua-hoa-tiet', 'Khăn lụa cao cấp với họa tiết độc đáo.', 'Khăn lụa thời trang', 590000, 490000, 'KLH001', 80, 0, 0, 0, 4.0, 37),
(5, 'T-Shirt Minimalist', 'tshirt-minimalist', 'Áo thun nam thiết kế tối giản, chất liệu cotton organic.', 'Áo thun tối giản', 390000, NULL, 'TSM001', 150, 0, 0, 1, 5.0, 156);

-- Insert Product Images
INSERT INTO product_images (product_id, image_url, alt_text, is_primary) VALUES
(1, 'products/dam-da-hoi-1.jpg', 'Đầm Dạ Hội Luxury - Mặt trước', 1),
(1, 'products/dam-da-hoi-2.jpg', 'Đầm Dạ Hội Luxury - Mặt sau', 0),
(2, 'products/ao-so-mi-1.jpg', 'Áo Sơ Mi Premium - Trắng', 1),
(3, 'products/blazer-1.jpg', 'Blazer Elegance - Đen', 1),
(4, 'products/tui-xach-1.jpg', 'Túi Xách Classic', 1),
(5, 'products/quan-palazzo-1.jpg', 'Quần Palazzo Silk', 1),
(6, 'products/giay-cao-got-1.jpg', 'Giày Cao Gót Velvet', 1),
(7, 'products/khan-lua-1.jpg', 'Khăn Lụa Họa Tiết', 1),
(8, 'products/tshirt-1.jpg', 'T-Shirt Minimalist', 1);

-- Insert Product Variants
INSERT INTO product_variants (product_id, size, color, color_code, stock_quantity) VALUES
(1, 'S', 'Đỏ', '#e63946', 10),
(1, 'M', 'Đỏ', '#e63946', 15),
(1, 'L', 'Đỏ', '#e63946', 15),
(1, 'S', 'Đen', '#1a1a1a', 5),
(1, 'M', 'Đen', '#1a1a1a', 10),
(2, 'M', 'Trắng', '#ffffff', 30),
(2, 'L', 'Trắng', '#ffffff', 25),
(2, 'XL', 'Trắng', '#ffffff', 20),
(2, 'M', 'Xanh', '#3d5a80', 15),
(2, 'L', 'Xanh', '#3d5a80', 10);

-- Insert Coupons
INSERT INTO coupons (code, description, discount_type, discount_value, min_order_amount, max_discount_amount, usage_limit, start_date, end_date) VALUES
('WELCOME10', 'Giảm 10% cho khách hàng mới', 'percentage', 10, 500000, 200000, 1000, '2025-01-01 00:00:00', '2025-12-31 23:59:59'),
('SUMMER50', 'Giảm 50K đơn từ 1 triệu', 'fixed', 50000, 1000000, NULL, 500, '2025-06-01 00:00:00', '2025-08-31 23:59:59'),
('VIP20', 'Giảm 20% cho khách VIP', 'percentage', 20, 0, 500000, NULL, '2025-01-01 00:00:00', '2025-12-31 23:59:59');

-- Insert Settings
INSERT INTO settings (setting_key, setting_value, setting_group) VALUES
('site_name', 'LUXE Fashion', 'general'),
('site_email', 'support@luxefashion.vn', 'general'),
('site_phone', '1900 xxxx', 'general'),
('site_address', '123 Nguyễn Huệ, Q.1, TP.HCM', 'general'),
('free_shipping_threshold', '500000', 'shipping'),
('default_shipping_fee', '30000', 'shipping'),
('currency', 'VND', 'general'),
('facebook_url', 'https://facebook.com/luxefashion', 'social'),
('instagram_url', 'https://instagram.com/luxefashion', 'social');

-- Insert Admin User (password: admin123)
INSERT INTO users (email, password, full_name, phone, role) VALUES
('admin@luxefashion.vn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin LUXE', '0909123456', 'admin');
