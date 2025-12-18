<?php
define('LUXE_APP', true);
require_once 'config/config.php';
require_once 'includes/Database.php';
require_once 'includes/models/Product.php';

$slug = $_GET['slug'] ?? '';
$id = (int)($_GET['id'] ?? 0);

$productModel = new Product();
$product = $slug ? $productModel->getBySlug($slug) : ($id ? $productModel->getById($id) : null);

if (!$product) {
    header('Location: products.html');
    exit;
}

$relatedProducts = $productModel->getRelated($product['id'], $product['category_id'], 4);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> | LUXE Fashion</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/products.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <link rel="stylesheet" href="assets/css/modal.css">
    <style>
        .product-page { padding: 160px 0 60px; }
        .product-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: start;
        }
        
        .product-gallery {}
        .gallery-main {
            aspect-ratio: 1;
            background: linear-gradient(135deg, #f0e6e8 0%, #e8d5d8 100%);
            border-radius: var(--radius-lg);
            overflow: hidden;
            margin-bottom: 15px;
        }
        .gallery-main img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .gallery-thumbs {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }
        .gallery-thumb {
            aspect-ratio: 1;
            background: var(--bg-light);
            border-radius: var(--radius);
            overflow: hidden;
            border: 2px solid transparent;
            cursor: pointer;
            transition: all var(--transition);
        }
        .gallery-thumb:hover, .gallery-thumb.active {
            border-color: var(--secondary);
        }
        .gallery-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .product-details {}
        .product-breadcrumb {
            display: flex;
            gap: 8px;
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-bottom: 15px;
        }
        .product-breadcrumb a:hover { color: var(--secondary); }
        
        .product-title {
            font-family: var(--font-serif);
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .product-rating {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        .product-rating-stars { color: #fbbf24; }
        .product-rating-count {
            font-size: 0.9rem;
            color: var(--text-secondary);
        }
        
        .product-price-section {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
        }
        .price-current {
            font-size: 2rem;
            font-weight: 700;
            color: var(--secondary);
        }
        .price-old {
            font-size: 1.25rem;
            color: var(--text-muted);
            text-decoration: line-through;
        }
        .price-discount {
            padding: 5px 12px;
            background: #fef2f2;
            color: #ef4444;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .product-desc {
            color: var(--text-secondary);
            line-height: 1.7;
            margin-bottom: 25px;
            padding-bottom: 25px;
            border-bottom: 1px solid var(--border);
        }
        
        .option-group { margin-bottom: 20px; }
        .option-label {
            font-weight: 500;
            margin-bottom: 10px;
        }
        .option-values {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .option-btn {
            padding: 10px 20px;
            border: 2px solid var(--border);
            border-radius: var(--radius);
            font-weight: 500;
            transition: all var(--transition);
        }
        .option-btn:hover, .option-btn.active {
            border-color: var(--secondary);
            background: rgba(183, 110, 121, 0.1);
        }
        .color-btn {
            width: 36px;
            height: 36px;
            padding: 0;
            border-radius: 50%;
        }
        
        .quantity-section {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
        }
        .quantity-control {
            display: flex;
            border: 1px solid var(--border);
            border-radius: var(--radius);
        }
        .qty-btn {
            width: 45px;
            height: 45px;
            background: var(--bg-light);
            font-size: 1.25rem;
        }
        .qty-btn:hover { background: var(--border); }
        .qty-input {
            width: 60px;
            text-align: center;
            border: none;
            border-left: 1px solid var(--border);
            border-right: 1px solid var(--border);
            font-size: 1rem;
        }
        .stock-info {
            font-size: 0.9rem;
            color: var(--text-secondary);
        }
        .stock-info.in-stock { color: #22c55e; }
        .stock-info.low-stock { color: #f59e0b; }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
        }
        .add-to-cart-btn {
            flex: 1;
            padding: 15px 30px;
            background: var(--gradient-primary);
            color: var(--white);
            border-radius: var(--radius);
            font-size: 1rem;
            font-weight: 600;
            transition: all var(--transition);
        }
        .add-to-cart-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(183, 110, 121, 0.3);
        }
        .buy-now-btn {
            flex: 1;
            padding: 15px 30px;
            background: var(--primary);
            color: var(--white);
            border-radius: var(--radius);
            font-size: 1rem;
            font-weight: 600;
        }
        .wishlist-btn {
            width: 50px;
            height: 50px;
            border: 2px solid var(--border);
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }
        .wishlist-btn:hover { border-color: var(--secondary); }
        
        .product-features {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            padding: 20px;
            background: var(--bg-light);
            border-radius: var(--radius);
        }
        .feature-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
        }
        .feature-icon { font-size: 1.25rem; }
        
        .related-section {
            padding: 60px 0;
            background: var(--bg-light);
        }
        .related-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
        }
        
        @media (max-width: 992px) {
            .product-container { grid-template-columns: 1fr; }
            .related-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 576px) {
            .action-buttons { flex-direction: column; }
            .related-grid { grid-template-columns: 1fr; }
            .product-features { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <header class="header" id="header">
        <div class="header-main">
            <div class="container">
                <a href="index.html" class="logo">
                    <div class="logo-icon"><img src="assets/icons/logo.jpg" alt="logo-icon"></div>
                    LUXE
                </a>
                <nav class="nav">
                    <ul class="nav-menu">
                        <li><a href="index.html" class="nav-link">Trang Chủ</a></li>
                        <li><a href="products.html?category=nam" class="nav-link">Thời Trang Nam</a></li>
                        <li><a href="products.html?category=nu" class="nav-link">Thời Trang Nữ</a></li>
                        <li><a href="products.html?category=phu-kien" class="nav-link">Phụ Kiện</a></li>
                    </ul>
                </nav>
                <div class="header-actions">
                    <div class="header-search">
                        <input type="text" placeholder="Tìm kiếm sản phẩm..." id="searchInput">
                        <button type="button">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                            </svg>
                        </button>
                    </div>
                    <button class="header-icon" title="Tài khoản" onclick="AuthManager.showLogin()">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </button>
                    <a href="cart.html" class="header-icon" title="Giỏ hàng">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                        </svg>
                        <span class="cart-count">0</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <section class="product-page">
        <div class="container">
            <div class="product-container">
                <div class="product-gallery">
                    <div class="gallery-main" id="galleryMain">
                        <?php if (!empty($product['images'])): ?>
                            <img src="<?= $product['images'][0]['image_url'] ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                        <?php else: ?>
                            <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:8rem">👗</div>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($product['images']) && count($product['images']) > 1): ?>
                    <div class="gallery-thumbs">
                        <?php foreach ($product['images'] as $i => $img): ?>
                        <button class="gallery-thumb <?= $i === 0 ? 'active' : '' ?>" onclick="changeImage('<?= $img['image_url'] ?>', this)">
                            <img src="<?= $img['image_url'] ?>" alt="">
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="product-details">
                    <div class="product-breadcrumb">
                        <a href="index.html">Trang chủ</a>
                        <span>/</span>
                        <a href="products.html">Sản phẩm</a>
                        <span>/</span>
                        <span><?= htmlspecialchars($product['name']) ?></span>
                    </div>

                    <h1 class="product-title"><?= htmlspecialchars($product['name']) ?></h1>
                    
                    <div class="product-rating">
                        <span class="product-rating-stars">★★★★★</span>
                        <span class="product-rating-count">(<?= $product['review_count'] ?? 0 ?> đánh giá)</span>
                    </div>

                    <div class="product-price-section">
                        <span class="price-current"><?= number_format($product['sale_price'] ?? $product['price'], 0, ',', '.') ?>₫</span>
                        <?php if ($product['sale_price']): ?>
                        <span class="price-old"><?= number_format($product['price'], 0, ',', '.') ?>₫</span>
                        <span class="price-discount">-<?= round(100 - ($product['sale_price'] / $product['price'] * 100)) ?>%</span>
                        <?php endif; ?>
                    </div>

                    <p class="product-desc"><?= nl2br(htmlspecialchars($product['description'] ?? '')) ?></p>

                    <?php if (!empty($product['variants'])): ?>
                    <?php
                    $sizes = [];
                    foreach ($product['variants'] as $v) {
                        if ($v['size']) $sizes[$v['size']] = $v['size'];
                    }
                    ?>
                    
                    <?php if ($sizes): ?>
                    <div class="option-group">
                        <div class="option-label">Kích thước:</div>
                        <div class="option-values" id="sizeOptions">
                            <?php foreach ($sizes as $size): ?>
                            <button class="option-btn" data-size="<?= $size ?>"><?= $size ?></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>

                    <div class="quantity-section">
                        <div class="quantity-control">
                            <button class="qty-btn" onclick="changeQty(-1)">−</button>
                            <input type="number" class="qty-input" id="quantity" value="1" min="1">
                            <button class="qty-btn" onclick="changeQty(1)">+</button>
                        </div>
                        <span class="stock-info <?= $product['stock_quantity'] > 10 ? 'in-stock' : 'low-stock' ?>">
                            <?= $product['stock_quantity'] > 10 ? '✓ Còn hàng' : "Chỉ còn {$product['stock_quantity']} sản phẩm" ?>
                        </span>
                    </div>

                    <div class="action-buttons">
                        <button class="add-to-cart-btn" onclick="addToCart()">🛒 Thêm vào giỏ</button>
                        <button class="buy-now-btn" onclick="buyNow()">Mua ngay</button>
                        <button class="wishlist-btn">❤️</button>
                    </div>

                    <div class="product-features">
                        <div class="feature-item">
                            <img class="feature-icon" src="assets/icons/delivery-truck.png" alt="" style="width:24px;height:24px">
                            <span>Miễn phí vận chuyển</span>
                        </div>
                        <div class="feature-item">
                            <img class="feature-icon" src="assets/icons/return.png" alt="" style="width:24px;height:24px">
                            <span>Đổi trả 30 ngày</span>
                        </div>
                        <div class="feature-item">
                            <img class="feature-icon" src="assets/icons/product.png" alt="" style="width:24px;height:24px">
                            <span>Hàng chính hãng</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php if ($relatedProducts): ?>
    <section class="related-section">
        <div class="container">
            <h2 class="section-title">Sản Phẩm Liên Quan</h2>
            <div class="related-grid">
                <?php foreach ($relatedProducts as $item): ?>
                <div class="product-card">
                    <div class="product-image">
                        <div class="product-image-bg">
                            <?= $item['image'] ? "<img src=\"{$item['image']}\" alt=\"\">" : '👗' ?>
                        </div>
                    </div>
                    <div class="product-info">
                        <h3 class="product-name">
                            <a href="product.php?slug=<?= $item['slug'] ?>"><?= htmlspecialchars($item['name']) ?></a>
                        </h3>
                        <div class="product-price">
                            <span class="product-price-current"><?= number_format($item['sale_price'] ?? $item['price'], 0, ',', '.') ?>₫</span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <footer class="footer">
        <div class="container">
            <div class="footer-bottom" style="border-top:none; padding-top:0;">
                <p class="footer-copyright">© 2025 LUXE Fashion</p>
            </div>
        </div>
    </footer>

    <script src="assets/js/app.js"></script>
    <script>
        const productId = <?= $product['id'] ?>;
        let selectedVariant = null;
        
        document.addEventListener('DOMContentLoaded', async () => {
            Toast.init();
            await CartManager.init();
            
            document.querySelectorAll('.option-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    this.parentElement.querySelectorAll('.option-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                });
            });
        });
        
        function changeImage(src, thumb) {
            document.querySelector('.gallery-main').innerHTML = `<img src="${src}" alt="">`;
            document.querySelectorAll('.gallery-thumb').forEach(t => t.classList.remove('active'));
            thumb.classList.add('active');
        }
        
        function changeQty(delta) {
            const input = document.getElementById('quantity');
            let val = parseInt(input.value) + delta;
            if (val < 1) val = 1;
            input.value = val;
        }
        
        async function addToCart() {
            const qty = parseInt(document.getElementById('quantity').value);
            await CartManager.add(productId, qty, selectedVariant);
        }
        
        async function buyNow() {
            const qty = parseInt(document.getElementById('quantity').value);
            await CartManager.add(productId, qty, selectedVariant);
            window.location.href = 'cart.html';
        }
    </script>
</body>
</html>
