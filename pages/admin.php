<?php
define('LUXE_APP', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';

session_name(SESSION_NAME);
session_start();

// Check if user is logged in and is admin
$isAdmin = false;
$user = null;
if (isset($_SESSION['user_id'])) {
    $db = Database::getInstance();
    $user = $db->query("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']])->fetch();
    $isAdmin = $user && $user['role'] === 'admin';
}

// Get stats
$db = Database::getInstance();
$stats = [
    'products' => $db->query("SELECT COUNT(*) as count FROM products")->fetch()['count'],
    'orders' => $db->query("SELECT COUNT(*) as count FROM orders")->fetch()['count'],
    'users' => $db->query("SELECT COUNT(*) as count FROM users")->fetch()['count'],
    'revenue' => $db->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE order_status != 'cancelled'")->fetch()['total']
];

// Get products
$page = (int)($_GET['page'] ?? 1);
$limit = 10;
$offset = ($page - 1) * $limit;
$products = $db->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC LIMIT $limit OFFSET $offset")->fetchAll();
$totalProducts = $db->query("SELECT COUNT(*) as count FROM products")->fetch()['count'];
$totalPages = ceil($totalProducts / $limit);

// Get categories for form
$categories = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel | LUXE Fashion</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1a1a1a;
            --secondary: #b76e79;
            --accent: #d4a373;
            --bg-light: #faf8f5;
            --bg-cream: #f8f4f0;
            --text-primary: #1a1a1a;
            --text-secondary: #666;
            --white: #ffffff;
            --border: #e8e3dd;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 10px 40px rgba(0, 0, 0, 0.12);
            --radius: 12px;
            --radius-lg: 20px;
            --transition: 0.3s ease;
            --gradient-primary: linear-gradient(135deg, #b76e79 0%, #d4a373 100%);
            --gradient-sidebar: linear-gradient(180deg, #1a1a1a 0%, #2d2d2d 100%);
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Inter', sans-serif; 
            background: var(--bg-light); 
            color: var(--text-primary);
            line-height: 1.6;
        }
        
        .admin-container { display: flex; min-height: 100vh; }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: var(--gradient-sidebar);
            color: var(--white);
            padding: 30px 20px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 40px;
            padding-bottom: 25px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar-logo img {
            width: 45px;
            height: 45px;
            border-radius: 12px;
        }
        .sidebar-menu { list-style: none; }
        .sidebar-menu li { margin-bottom: 8px; }
        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 18px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            border-radius: var(--radius);
            transition: all var(--transition);
            font-weight: 500;
        }
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: rgba(183, 110, 121, 0.3);
            color: #fff;
        }
        .sidebar-menu a.active {
            background: var(--gradient-primary);
        }
        .menu-icon {
            width: 22px;
            height: 22px;
            opacity: 0.9;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 35px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 35px;
        }
        .header h1 { 
            font-family: 'Playfair Display', serif;
            font-size: 2rem; 
            font-weight: 600;
        }
        .header-user {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 20px;
            background: var(--white);
            border-radius: 50px;
            box-shadow: var(--shadow);
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            background: var(--gradient-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        
        /* Stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
            margin-bottom: 35px;
        }
        .stat-card {
            background: var(--white);
            padding: 28px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all var(--transition);
            border: 1px solid var(--border);
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        .stat-icon {
            width: 65px;
            height: 65px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .stat-icon img {
            width: 32px;
            height: 32px;
        }
        .stat-card:nth-child(1) .stat-icon { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-card:nth-child(2) .stat-icon { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-card:nth-child(3) .stat-icon { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .stat-card:nth-child(4) .stat-icon { background: var(--gradient-primary); }
        .stat-info { flex: 1; }
        .stat-value { font-size: 1.8rem; font-weight: 700; color: var(--primary); margin-bottom: 5px; }
        .stat-label { color: var(--text-secondary); font-size: 0.9rem; }
        
        /* Buttons */
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: var(--radius);
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary { 
            background: var(--gradient-primary); 
            color: var(--white); 
            box-shadow: 0 4px 15px rgba(183, 110, 121, 0.3);
        }
        .btn-primary:hover { 
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(183, 110, 121, 0.4);
        }
        .btn-secondary { background: var(--bg-cream); color: var(--primary); }
        .btn-danger { background: #fee2e2; color: #dc2626; }
        .btn-danger:hover { background: #fecaca; }
        .btn-sm { padding: 8px 14px; font-size: 0.8rem; }
        
        /* Card */
        .card {
            background: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            overflow: hidden;
            border: 1px solid var(--border);
        }
        .card-header {
            padding: 25px 30px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--bg-cream);
        }
        .card-header h2 { 
            font-family: 'Playfair Display', serif;
            font-size: 1.3rem; 
            font-weight: 600;
        }
        .card-body { padding: 0; }
        
        /* Table */
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 18px 25px; text-align: left; }
        th { 
            background: var(--bg-light); 
            font-weight: 600; 
            font-size: 0.8rem; 
            text-transform: uppercase; 
            color: var(--text-secondary);
            letter-spacing: 0.5px;
        }
        td { border-bottom: 1px solid var(--border); }
        tr:hover td { background: var(--bg-cream); }
        
        .product-img {
            width: 55px;
            height: 55px;
            object-fit: cover;
            border-radius: var(--radius);
            background: var(--bg-cream);
        }
        .product-name { font-weight: 600; color: var(--primary); }
        
        .badge {
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-success { background: #dcfce7; color: #16a34a; }
        .badge-warning { background: #fef3c7; color: #d97706; }
        .badge-danger { background: #fee2e2; color: #dc2626; }
        
        .actions { display: flex; gap: 8px; }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            padding: 25px;
        }
        .pagination a {
            padding: 10px 16px;
            border: 2px solid var(--border);
            border-radius: var(--radius);
            text-decoration: none;
            color: var(--text-secondary);
            font-weight: 500;
            transition: all var(--transition);
        }
        .pagination a:hover, .pagination a.active {
            background: var(--gradient-primary);
            color: var(--white);
            border-color: transparent;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: var(--white);
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 550px;
            max-height: 90vh;
            overflow-y: auto;
            animation: modalIn 0.3s ease;
        }
        @keyframes modalIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
        .modal-header {
            padding: 25px 30px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h3 { 
            font-family: 'Playfair Display', serif;
            font-size: 1.3rem;
        }
        .modal-close { 
            font-size: 1.8rem; 
            cursor: pointer; 
            color: var(--text-secondary);
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all var(--transition);
        }
        .modal-close:hover { background: var(--bg-cream); }
        .modal-body { padding: 30px; }
        .modal-footer { 
            padding: 20px 30px; 
            border-top: 1px solid var(--border); 
            display: flex; 
            gap: 12px; 
            justify-content: flex-end; 
            background: var(--bg-cream);
        }
        
        /* Forms */
        .form-group { margin-bottom: 22px; }
        .form-label { 
            display: block; 
            margin-bottom: 10px; 
            font-weight: 600; 
            font-size: 0.9rem;
        }
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid var(--border);
            border-radius: var(--radius);
            font-size: 1rem;
            transition: all var(--transition);
            background: var(--white);
        }
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 4px rgba(183, 110, 121, 0.1);
        }
        .form-textarea { min-height: 100px; resize: vertical; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        
        /* Login */
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--bg-light) 0%, var(--bg-cream) 100%);
        }
        .login-box {
            background: var(--white);
            padding: 50px;
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 420px;
            text-align: center;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border);
        }
        .login-logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 25px;
            border-radius: 20px;
        }
        .login-box h1 { 
            font-family: 'Playfair Display', serif;
            margin-bottom: 10px; 
            font-size: 1.8rem;
        }
        .login-subtitle {
            color: var(--text-secondary);
            margin-bottom: 35px;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: var(--radius);
            margin-bottom: 25px;
            font-weight: 500;
        }
        .alert-error { background: #fee2e2; color: #dc2626; }
        
        @media (max-width: 1200px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 992px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>
<?php if (!$isAdmin): ?>
    <div class="login-container">
        <div class="login-box">
            <img src="../assets/icons/logo.jpg" class="login-logo" alt="LUXE">
            <h1>LUXE Admin</h1>
            <p class="login-subtitle">Đăng nhập để quản lý cửa hàng</p>
            <div id="loginError" class="alert alert-error" style="display:none;"></div>
            <form id="adminLoginForm">
                <div class="form-group">
                    <label class="form-label" style="text-align:left;">Email</label>
                    <input type="email" class="form-input" name="email" required placeholder="admin@luxefashion.vn">
                </div>
                <div class="form-group">
                    <label class="form-label" style="text-align:left;">Mật khẩu</label>
                    <input type="password" class="form-input" name="password" required placeholder="••••••••">
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%; padding: 16px;">Đăng nhập</button>
            </form>
            <p style="margin-top:25px;color:var(--text-secondary);">
                <a href="index.html" style="color:var(--secondary);">← Về trang chủ</a>
            </p>
        </div>
    </div>
    <script>
        document.getElementById('adminLoginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;
            const errorDiv = document.getElementById('loginError');
            const btn = form.querySelector('button');
            btn.disabled = true;
            btn.textContent = 'Đang xử lý...';
            
            try {
                const res = await fetch('/api/index.php?action=auth.login', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        email: form.email.value,
                        password: form.password.value
                    })
                });
                const data = await res.json();
                
                if (data.success) {
                    if (data.user.role === 'admin') {
                        location.reload();
                    } else {
                        errorDiv.textContent = 'Bạn không có quyền admin!';
                        errorDiv.style.display = 'block';
                    }
                } else {
                    errorDiv.textContent = data.message;
                    errorDiv.style.display = 'block';
                }
            } catch (err) {
                errorDiv.textContent = 'Có lỗi xảy ra!';
                errorDiv.style.display = 'block';
            }
            btn.disabled = false;
            btn.textContent = 'Đăng nhập';
        });
    </script>
<?php else: ?>
    <div class="admin-container">
        <aside class="sidebar">
            <div class="sidebar-logo">
                <img src="../assets/icons/logo.jpg" alt="LUXE">
                <span>LUXE Admin</span>
            </div>
            <ul class="sidebar-menu">
                <li><a href="admin.php" class="active">
                    <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    Dashboard
                </a></li>
                <li><a href="admin.php#products">
                    <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
                    Sản phẩm
                </a></li>
                <li><a href="#">
                    <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                    Đơn hàng
                </a></li>
                <li><a href="#">
                    <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    Khách hàng
                </a></li>
                <li><a href="#">
                    <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                    Danh mục
                </a></li>
                <li><a href="#">
                    <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                    Cài đặt
                </a></li>
                <li style="margin-top:30px;padding-top:20px;border-top:1px solid rgba(255,255,255,0.1);">
                    <a href="index.html">
                        <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                        Về trang chủ
                    </a>
                </li>
                <li><a href="#" onclick="logout()">
                    <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                    Đăng xuất
                </a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="header">
                <h1>Dashboard</h1>
                <div class="header-user">
                    <div class="user-avatar"><?= strtoupper(substr($user['full_name'], 0, 1)) ?></div>
                    <span><?= htmlspecialchars($user['full_name']) ?></span>
                </div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?= number_format($stats['products']) ?></div>
                        <div class="stat-label">Sản phẩm</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?= number_format($stats['orders']) ?></div>
                        <div class="stat-label">Đơn hàng</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?= number_format($stats['users']) ?></div>
                        <div class="stat-label">Khách hàng</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?= number_format($stats['revenue'], 0, ',', '.') ?>₫</div>
                        <div class="stat-label">Doanh thu</div>
                    </div>
                </div>
            </div>
            
            <div class="card" id="products">
                <div class="card-header">
                    <h2>Quản lý sản phẩm</h2>
                    <button class="btn btn-primary" onclick="openModal('add')">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Thêm sản phẩm
                    </button>
                </div>
                <div class="card-body">
                    <table>
                        <thead>
                            <tr>
                                <th>Sản phẩm</th>
                                <th>Danh mục</th>
                                <th>Giá</th>
                                <th>Kho</th>
                                <th>Trạng thái</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $p): ?>
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:center;gap:15px;">
                                        <?php if ($p['image']): ?>
                                            <img src="<?= $p['image'] ?>" class="product-img">
                                        <?php else: ?>
                                            <div class="product-img" style="display:flex;align-items:center;justify-content:center;font-size:1.5rem;">📷</div>
                                        <?php endif; ?>
                                        <span class="product-name"><?= htmlspecialchars($p['name']) ?></span>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($p['category_name'] ?? 'Chưa phân loại') ?></td>
                                <td>
                                    <?php if ($p['sale_price']): ?>
                                        <span style="text-decoration:line-through;color:#999;font-size:0.85rem;"><?= number_format($p['price'], 0, ',', '.') ?>₫</span><br>
                                        <strong style="color:var(--secondary);"><?= number_format($p['sale_price'], 0, ',', '.') ?>₫</strong>
                                    <?php else: ?>
                                        <strong><?= number_format($p['price'], 0, ',', '.') ?>₫</strong>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $p['stock_quantity'] > 10 ? 'badge-success' : ($p['stock_quantity'] > 0 ? 'badge-warning' : 'badge-danger') ?>">
                                        <?= $p['stock_quantity'] ?> sp
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= $p['status'] === 'active' ? 'badge-success' : 'badge-danger' ?>">
                                        <?= $p['status'] === 'active' ? 'Hiện' : 'Ẩn' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="actions">
                                        <button class="btn btn-secondary btn-sm" onclick='editProduct(<?= json_encode($p) ?>)'>
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                        </button>
                                        <button class="btn btn-danger btn-sm" onclick="deleteProduct(<?= $p['id'] ?>)">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?page=<?= $i ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Product Modal -->
    <div class="modal" id="productModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Thêm sản phẩm</h3>
                <span class="modal-close" onclick="closeModal()">&times;</span>
            </div>
            <form id="productForm">
                <div class="modal-body">
                    <input type="hidden" name="id" id="productId">
                    <div class="form-group">
                        <label class="form-label">Tên sản phẩm *</label>
                        <input type="text" class="form-input" name="name" id="productName" required placeholder="Nhập tên sản phẩm">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Giá gốc *</label>
                            <input type="number" class="form-input" name="price" id="productPrice" required placeholder="0">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Giá sale</label>
                            <input type="number" class="form-input" name="sale_price" id="productSalePrice" placeholder="0">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Danh mục</label>
                            <select class="form-select" name="category_id" id="productCategory">
                                <option value="">-- Chọn danh mục --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Số lượng kho</label>
                            <input type="number" class="form-input" name="stock_quantity" id="productStock" value="0">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">URL hình ảnh</label>
                        <input type="text" class="form-input" name="image" id="productImage" placeholder="products/image.jpg">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Mô tả</label>
                        <textarea class="form-textarea" name="description" id="productDescription" placeholder="Mô tả sản phẩm..."></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Trạng thái</label>
                        <select class="form-select" name="status" id="productStatus">
                            <option value="active">Hiển thị</option>
                            <option value="inactive">Ẩn</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                            <input type="checkbox" name="is_featured" id="productFeatured" style="width:20px;height:20px;accent-color:var(--secondary);">
                            <span class="form-label" style="margin:0;">Sản phẩm nổi bật (hiện trang chủ)</span>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Hủy</button>
                    <button type="submit" class="btn btn-primary">Lưu sản phẩm</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        const API = '/api/index.php';
        
        function openModal(mode) {
            document.getElementById('productModal').classList.add('active');
            document.getElementById('modalTitle').textContent = mode === 'add' ? 'Thêm sản phẩm' : 'Sửa sản phẩm';
            if (mode === 'add') {
                document.getElementById('productForm').reset();
                document.getElementById('productId').value = '';
            }
        }
        
        function closeModal() {
            document.getElementById('productModal').classList.remove('active');
        }
        
        function editProduct(product) {
            document.getElementById('productId').value = product.id;
            document.getElementById('productName').value = product.name;
            document.getElementById('productPrice').value = product.price;
            document.getElementById('productSalePrice').value = product.sale_price || '';
            document.getElementById('productCategory').value = product.category_id || '';
            document.getElementById('productStock').value = product.stock_quantity;
            document.getElementById('productImage').value = product.image || '';
            document.getElementById('productDescription').value = product.description || '';
            document.getElementById('productStatus').value = product.status;
            document.getElementById('productFeatured').checked = product.is_featured == 1;
            openModal('edit');
        }
        
        async function deleteProduct(id) {
            if (!confirm('Bạn có chắc muốn xóa sản phẩm này?')) return;
            
            try {
                const res = await fetch(`${API}?action=admin.products.delete`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ id })
                });
                const data = await res.json();
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message);
                }
            } catch (err) {
                alert('Có lỗi xảy ra!');
            }
        }
        
        document.getElementById('productForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;
            const id = form.id.value;
            const action = id ? 'admin.products.update' : 'admin.products.create';
            
            const data = {
                id: id || undefined,
                name: form.name.value,
                price: parseFloat(form.price.value),
                sale_price: form.sale_price.value ? parseFloat(form.sale_price.value) : null,
                category_id: form.category_id.value || null,
                stock_quantity: parseInt(form.stock_quantity.value) || 0,
                image: form.image.value || null,
                description: form.description.value || null,
                status: form.status.value,
                is_featured: form.is_featured.checked ? 1 : 0
            };
            
            try {
                const res = await fetch(`${API}?action=${action}`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                const result = await res.json();
                if (result.success) {
                    location.reload();
                } else {
                    alert(result.message);
                }
            } catch (err) {
                alert('Có lỗi xảy ra!');
            }
        });
        
        async function logout() {
            await fetch(`${API}?action=auth.logout`, { method: 'POST' });
            sessionStorage.removeItem('luxe_user');
            window.location.href = '/pages/index.html';
        }
        
        // Close modal on outside click
        document.getElementById('productModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
    </script>
<?php endif; ?>
</body>
</html>
