<?php
/**
 * LUXE Fashion - Database Configuration
 * 
 * Cấu hình kết nối MySQL database
 */

// Prevent direct access
if (!defined('LUXE_APP')) {
    exit('Direct access not allowed');
}

// Database Configuration
define('DB_HOST', '127.0.0.1:3307');
define('DB_NAME', 'luxe_fashion');
define('DB_USER', 'root');
define('DB_PASS', 'luxe123');
define('DB_CHARSET', 'utf8mb4');

// Site Configuration
define('SITE_URL', 'http://localhost/ban_quan_ao');
define('SITE_NAME', 'LUXE Fashion');
define('SITE_EMAIL', 'support@luxefashion.vn');

// Paths
define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('INCLUDES_PATH', ROOT_PATH . 'includes' . DIRECTORY_SEPARATOR);
define('UPLOADS_PATH', ROOT_PATH . 'uploads' . DIRECTORY_SEPARATOR);

// Session Configuration
define('SESSION_NAME', 'luxe_session');
define('SESSION_LIFETIME', 86400); // 24 hours

// Security
define('HASH_COST', 12);
define('CSRF_TOKEN_NAME', 'csrf_token');

// Pagination
define('PRODUCTS_PER_PAGE', 12);
define('ORDERS_PER_PAGE', 10);
define('REVIEWS_PER_PAGE', 5);

// Shipping
define('FREE_SHIPPING_THRESHOLD', 500000);
define('DEFAULT_SHIPPING_FEE', 30000);

// Currency
define('CURRENCY_SYMBOL', '₫');
define('CURRENCY_CODE', 'VND');

// Error Reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');
