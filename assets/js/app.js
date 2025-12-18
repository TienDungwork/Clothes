/**
 * LUXE Fashion - Main JavaScript Application
 * 
 * Handles all frontend interactions including:
 * - Product display and filtering
 * - Shopping cart management
 * - Search functionality
 * - UI animations and interactions
 */

// ========================================
// CONFIGURATION
// ========================================
const CONFIG = {
    API_URL: './api/index.php',
    CURRENCY: '₫',
    ANIMATION_DURATION: 300,
    DEBOUNCE_DELAY: 300,
    TOAST_DURATION: 3000
};

// ========================================
// UTILITY FUNCTIONS
// ========================================
const Utils = {
    /**
     * Format price with Vietnamese currency
     */
    formatPrice(price) {
        return new Intl.NumberFormat('vi-VN').format(price) + CONFIG.CURRENCY;
    },

    /**
     * Debounce function
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    /**
     * Throttle function
     */
    throttle(func, limit) {
        let inThrottle;
        return function (...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },

    /**
     * Generate unique ID
     */
    generateId() {
        return '_' + Math.random().toString(36).substr(2, 9);
    },

    /**
     * Parse URL parameters
     */
    getUrlParams() {
        return Object.fromEntries(new URLSearchParams(window.location.search));
    },

    /**
     * Animate element
     */
    animate(element, animation, duration = CONFIG.ANIMATION_DURATION) {
        return new Promise(resolve => {
            element.style.animation = `${animation} ${duration}ms ease`;
            setTimeout(() => {
                element.style.animation = '';
                resolve();
            }, duration);
        });
    }
};

// ========================================
// API SERVICE
// ========================================
const API = {
    /**
     * Base fetch wrapper
     */
    async request(action, options = {}) {
        const url = new URL(CONFIG.API_URL, window.location.origin);
        url.searchParams.set('action', action);

        // Add query params for GET requests
        if (options.params) {
            Object.entries(options.params).forEach(([key, value]) => {
                if (value !== null && value !== undefined) {
                    url.searchParams.set(key, value);
                }
            });
        }

        const fetchOptions = {
            method: options.method || 'GET',
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            }
        };

        if (options.body && fetchOptions.method !== 'GET') {
            fetchOptions.body = JSON.stringify(options.body);
        }

        try {
            const response = await fetch(url.toString(), fetchOptions);
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Request failed');
            }

            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    },

    // Products
    getProducts(filters = {}, page = 1) {
        return this.request('products', { params: { ...filters, page } });
    },

    getFeaturedProducts(limit = 8) {
        return this.request('products.featured', { params: { limit } });
    },

    getNewProducts(limit = 8) {
        return this.request('products.new', { params: { limit } });
    },

    getBestsellers(limit = 8) {
        return this.request('products.bestsellers', { params: { limit } });
    },

    getSaleProducts(limit = 8) {
        return this.request('products.sale', { params: { limit } });
    },

    getProduct(idOrSlug) {
        const param = isNaN(idOrSlug) ? { slug: idOrSlug } : { id: idOrSlug };
        return this.request('product', { params: param });
    },

    searchProducts(query, limit = 10) {
        return this.request('products.search', { params: { q: query, limit } });
    },

    getRelatedProducts(productId, categoryId, limit = 4) {
        return this.request('products.related', {
            params: { product_id: productId, category_id: categoryId, limit }
        });
    },

    // Cart
    getCart() {
        return this.request('cart');
    },

    addToCart(productId, quantity = 1, variantId = null) {
        return this.request('cart.add', {
            method: 'POST',
            body: { product_id: productId, quantity, variant_id: variantId }
        });
    },

    updateCart(itemId, quantity) {
        return this.request('cart.update', {
            method: 'POST',
            body: { item_id: itemId, quantity }
        });
    },

    removeFromCart(itemId) {
        return this.request('cart.remove', {
            method: 'POST',
            body: { item_id: itemId }
        });
    },

    clearCart() {
        return this.request('cart', { method: 'DELETE' });
    },

    getCartCount() {
        return this.request('cart.count');
    },

    applyCoupon(code) {
        return this.request('cart.coupon', {
            method: 'POST',
            body: { code }
        });
    },

    // Categories
    getCategories() {
        return this.request('categories');
    },

    // Newsletter
    subscribeNewsletter(email) {
        return this.request('newsletter.subscribe', {
            method: 'POST',
            body: { email }
        });
    }
};

// ========================================
// TOAST NOTIFICATIONS
// ========================================
const Toast = {
    container: null,

    init() {
        this.container = document.createElement('div');
        this.container.className = 'toast-container';
        this.container.style.cssText = `
            position: fixed;
            top: 120px;
            right: 20px;
            z-index: 10000;
            display: flex;
            flex-direction: column;
            gap: 10px;
        `;
        document.body.appendChild(this.container);
    },

    show(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.style.cssText = `
            padding: 1rem 1.5rem;
            background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
            color: white;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideInRight 0.3s ease;
            min-width: 280px;
        `;

        const icon = type === 'success' ? '✓' : type === 'error' ? '✕' : 'ℹ';
        toast.innerHTML = `<span style="font-size: 1.2em;">${icon}</span><span>${message}</span>`;

        this.container.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, CONFIG.TOAST_DURATION);
    },

    success(message) { this.show(message, 'success'); },
    error(message) { this.show(message, 'error'); },
    info(message) { this.show(message, 'info'); }
};

// ========================================
// CART MANAGER
// ========================================
const CartManager = {
    cart: null,

    async init() {
        await this.refresh();
        this.bindEvents();
    },

    async refresh() {
        try {
            const response = await API.getCart();
            this.cart = response.data;
            this.updateUI();
        } catch (error) {
            console.error('Failed to load cart:', error);
        }
    },

    updateUI() {
        // Update cart count in header
        const cartCountEl = document.querySelector('.cart-count');
        if (cartCountEl) {
            cartCountEl.textContent = this.cart?.item_count || 0;
            cartCountEl.style.display = this.cart?.item_count > 0 ? 'flex' : 'none';
        }

        // Update mini cart if exists
        this.updateMiniCart();
    },

    updateMiniCart() {
        const miniCart = document.querySelector('.mini-cart');
        if (!miniCart || !this.cart) return;

        const itemsContainer = miniCart.querySelector('.mini-cart-items');
        const totalEl = miniCart.querySelector('.mini-cart-total');

        if (itemsContainer) {
            if (this.cart.items.length === 0) {
                itemsContainer.innerHTML = '<p class="empty-cart">Giỏ hàng trống</p>';
            } else {
                itemsContainer.innerHTML = this.cart.items.map(item => `
                    <div class="mini-cart-item" data-item-id="${item.id}">
                        <img src="${item.image || 'assets/images/placeholder.jpg'}" alt="${item.name}">
                        <div class="item-info">
                            <h4>${item.name}</h4>
                            ${item.size || item.color ? `<span class="variant">${item.size || ''} ${item.color || ''}</span>` : ''}
                            <span class="price">${item.quantity} x ${Utils.formatPrice(item.price)}</span>
                        </div>
                        <button class="remove-item" onclick="CartManager.remove(${item.id})">×</button>
                    </div>
                `).join('');
            }
        }

        if (totalEl) {
            totalEl.textContent = Utils.formatPrice(this.cart.total);
        }
    },

    async add(productId, quantity = 1, variantId = null) {
        try {
            const response = await API.addToCart(productId, quantity, variantId);

            if (response.success) {
                this.cart = response.cart;
                this.updateUI();
                Toast.success(response.message);
                this.animateCartIcon();
            } else {
                Toast.error(response.message);
            }
        } catch (error) {
            Toast.error('Không thể thêm vào giỏ hàng');
        }
    },

    async update(itemId, quantity) {
        try {
            const response = await API.updateCart(itemId, quantity);

            if (response.success) {
                this.cart = response.cart;
                this.updateUI();
            } else {
                Toast.error(response.message);
            }
        } catch (error) {
            Toast.error('Không thể cập nhật giỏ hàng');
        }
    },

    async remove(itemId) {
        try {
            const response = await API.removeFromCart(itemId);

            if (response.success) {
                this.cart = response.cart;
                this.updateUI();
                Toast.success(response.message);
            } else {
                Toast.error(response.message);
            }
        } catch (error) {
            Toast.error('Không thể xóa sản phẩm');
        }
    },

    async applyCoupon(code) {
        try {
            const response = await API.applyCoupon(code);

            if (response.success) {
                Toast.success(response.message);
                return response;
            } else {
                Toast.error(response.message);
                return null;
            }
        } catch (error) {
            Toast.error('Mã giảm giá không hợp lệ');
            return null;
        }
    },

    animateCartIcon() {
        const cartIcon = document.querySelector('.header-icon[title="Giỏ hàng"]');
        if (cartIcon) {
            cartIcon.style.transform = 'scale(1.2)';
            setTimeout(() => {
                cartIcon.style.transform = 'scale(1)';
            }, 200);
        }
    },

    bindEvents() {
        // Add to cart buttons
        document.addEventListener('click', async (e) => {
            const addBtn = e.target.closest('.product-add-btn');
            if (addBtn) {
                e.preventDefault();
                const productId = addBtn.dataset.productId;
                if (productId) {
                    await this.add(parseInt(productId));
                }
            }
        });
    }
};

// ========================================
// PRODUCT MANAGER
// ========================================
const ProductManager = {
    currentTab: 'all',
    products: [],

    async init() {
        await this.loadProducts();
        this.bindEvents();
    },

    async loadProducts(tab = 'all') {
        this.currentTab = tab;
        const productsGrid = document.querySelector('.products-grid');
        if (!productsGrid) return;

        // Show loading
        productsGrid.innerHTML = '<div class="loading">Đang tải...</div>';

        try {
            let response;
            switch (tab) {
                case 'new':
                    response = await API.getNewProducts(8);
                    break;
                case 'bestseller':
                    response = await API.getBestsellers(8);
                    break;
                case 'sale':
                    response = await API.getSaleProducts(8);
                    break;
                default:
                    response = await API.getFeaturedProducts(8);
            }

            this.products = response.data;
            this.renderProducts(productsGrid);
        } catch (error) {
            productsGrid.innerHTML = '<p class="error">Không thể tải sản phẩm</p>';
        }
    },

    renderProducts(container) {
        if (!this.products.length) {
            container.innerHTML = '<p class="no-products">Không có sản phẩm nào</p>';
            return;
        }

        container.innerHTML = this.products.map((product, index) => this.renderProductCard(product, index)).join('');
    },

    renderProductCard(product, index) {
        const currentPrice = product.sale_price || product.price;
        const hasDiscount = product.sale_price && product.sale_price < product.price;
        const discountPercent = hasDiscount ? Math.round((1 - product.sale_price / product.price) * 100) : 0;

        let badge = '';
        if (hasDiscount) {
            badge = `<span class="product-badge sale">-${discountPercent}%</span>`;
        } else if (product.is_new) {
            badge = '<span class="product-badge new">Mới</span>';
        } else if (product.is_bestseller) {
            badge = '<span class="product-badge hot">Hot</span>';
        }

        const bgIndex = (index % 8) + 1;

        return `
            <div class="product-card" data-product-id="${product.id}">
                <div class="product-image">
                    <div class="product-image-bg" style="background: var(--product-bg-${bgIndex}, linear-gradient(135deg, #f0e6e8 0%, #e8d5d8 100%))">
                        ${product.image ? `<img src="${product.image}" alt="${product.name}">` : this.getProductEmoji(product.category_name)}
                    </div>
                    ${badge}
                    <div class="product-actions">
                        <button class="product-action-btn wishlist-btn" title="Yêu thích">❤️</button>
                        <button class="product-action-btn quickview-btn" title="Xem nhanh" data-product-id="${product.id}">👁️</button>
                    </div>
                </div>
                <div class="product-info">
                    <span class="product-category">${product.category_name || 'Thời trang'}</span>
                    <h3 class="product-name">
                        <a href="product.php?slug=${product.slug}">${product.name}</a>
                    </h3>
                    <div class="product-price">
                        <span class="product-price-current">${Utils.formatPrice(currentPrice)}</span>
                        ${hasDiscount ? `<span class="product-price-old">${Utils.formatPrice(product.price)}</span>` : ''}
                    </div>
                    <div class="product-rating">
                        <span class="product-stars">${this.renderStars(product.rating_avg)}</span>
                        <span class="product-reviews">(${product.rating_count} đánh giá)</span>
                    </div>
                    <button class="product-add-btn" data-product-id="${product.id}">Thêm vào giỏ</button>
                </div>
            </div>
        `;
    },

    renderStars(rating) {
        const fullStars = Math.floor(rating);
        const halfStar = rating % 1 >= 0.5;
        let stars = '';

        for (let i = 0; i < fullStars; i++) {
            stars += '⭐';
        }
        if (halfStar) {
            stars += '⭐';
        }

        return stars || '⭐';
    },

    getProductEmoji(category) {
        const emojis = {
            'Đầm': '👗',
            'Áo Sơ Mi': '👔',
            'Áo Khoác': '🧥',
            'Túi Xách': '👜',
            'Quần': '👖',
            'Giày Dép': '👠',
            'Khăn & Phụ Kiện': '🧣',
            'Áo Thun': '👕',
            default: '👔'
        };

        for (const [key, emoji] of Object.entries(emojis)) {
            if (category && category.includes(key)) {
                return emoji;
            }
        }
        return emojis.default;
    },

    bindEvents() {
        // Tab switching
        const tabs = document.querySelectorAll('.product-tab');
        tabs.forEach(tab => {
            tab.addEventListener('click', async () => {
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');

                const tabName = tab.textContent.toLowerCase();
                let tabType = 'all';
                if (tabName.includes('mới')) tabType = 'new';
                else if (tabName.includes('chạy')) tabType = 'bestseller';
                else if (tabName.includes('giá') || tabName.includes('sale')) tabType = 'sale';

                await this.loadProducts(tabType);
            });
        });

        // Quick view
        document.addEventListener('click', (e) => {
            const quickviewBtn = e.target.closest('.quickview-btn');
            if (quickviewBtn) {
                const productId = quickviewBtn.dataset.productId;
                this.showQuickView(productId);
            }
        });
    },

    async showQuickView(productId) {
        try {
            const response = await API.getProduct(productId);
            const product = response.data;

            // Create and show modal
            const modal = this.createQuickViewModal(product);
            document.body.appendChild(modal);

            // Animate in
            requestAnimationFrame(() => {
                modal.classList.add('active');
            });
        } catch (error) {
            Toast.error('Không thể tải thông tin sản phẩm');
        }
    },

    createQuickViewModal(product) {
        const modal = document.createElement('div');
        modal.className = 'quickview-modal';
        modal.innerHTML = `
            <div class="quickview-overlay" onclick="this.parentElement.remove()"></div>
            <div class="quickview-content">
                <button class="quickview-close" onclick="this.closest('.quickview-modal').remove()">×</button>
                <div class="quickview-body">
                    <div class="quickview-image">
                        ${product.images?.[0] ?
                `<img src="${product.images[0].image_url}" alt="${product.name}">` :
                '<div class="placeholder-image">👔</div>'
            }
                    </div>
                    <div class="quickview-info">
                        <h2>${product.name}</h2>
                        <div class="quickview-price">
                            <span class="current-price">${Utils.formatPrice(product.sale_price || product.price)}</span>
                            ${product.sale_price ? `<span class="old-price">${Utils.formatPrice(product.price)}</span>` : ''}
                        </div>
                        <p class="quickview-desc">${product.short_description || product.description || ''}</p>
                        
                        ${product.variants?.length ? `
                            <div class="quickview-variants">
                                <div class="variant-group">
                                    <label>Kích thước:</label>
                                    <div class="variant-options">
                                        ${[...new Set(product.variants.map(v => v.size))].filter(Boolean).map(size =>
                `<button class="variant-option" data-size="${size}">${size}</button>`
            ).join('')}
                                    </div>
                                </div>
                                <div class="variant-group">
                                    <label>Màu sắc:</label>
                                    <div class="variant-options">
                                        ${[...new Set(product.variants.map(v => v.color))].filter(Boolean).map(color =>
                `<button class="variant-option color-option" data-color="${color}" style="background-color: ${product.variants.find(v => v.color === color)?.color_code || '#ccc'}"></button>`
            ).join('')}
                                    </div>
                                </div>
                            </div>
                        ` : ''}
                        
                        <div class="quickview-quantity">
                            <label>Số lượng:</label>
                            <div class="quantity-control">
                                <button class="qty-btn minus">-</button>
                                <input type="number" value="1" min="1" class="qty-input">
                                <button class="qty-btn plus">+</button>
                            </div>
                        </div>
                        
                        <button class="btn btn-accent add-to-cart-btn" data-product-id="${product.id}">
                            Thêm vào giỏ hàng
                        </button>
                    </div>
                </div>
            </div>
        `;

        // Bind quantity controls
        const qtyInput = modal.querySelector('.qty-input');
        modal.querySelector('.qty-btn.minus').addEventListener('click', () => {
            qtyInput.value = Math.max(1, parseInt(qtyInput.value) - 1);
        });
        modal.querySelector('.qty-btn.plus').addEventListener('click', () => {
            qtyInput.value = parseInt(qtyInput.value) + 1;
        });

        // Bind add to cart
        modal.querySelector('.add-to-cart-btn').addEventListener('click', async () => {
            const quantity = parseInt(qtyInput.value);
            await CartManager.add(product.id, quantity);
            modal.remove();
        });

        return modal;
    }
};

// ========================================
// SEARCH MANAGER
// ========================================
const SearchManager = {
    searchInput: null,
    resultsContainer: null,

    init() {
        this.searchInput = document.querySelector('.header-search input');
        if (!this.searchInput) return;

        this.createResultsContainer();
        this.bindEvents();
    },

    createResultsContainer() {
        this.resultsContainer = document.createElement('div');
        this.resultsContainer.className = 'search-results';
        this.resultsContainer.style.cssText = `
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            max-height: 400px;
            overflow-y: auto;
            display: none;
            z-index: 1001;
        `;
        this.searchInput.parentElement.style.position = 'relative';
        this.searchInput.parentElement.appendChild(this.resultsContainer);
    },

    bindEvents() {
        const debouncedSearch = Utils.debounce(async (query) => {
            if (query.length < 2) {
                this.hideResults();
                return;
            }

            try {
                const response = await API.searchProducts(query);
                this.showResults(response.data);
            } catch (error) {
                console.error('Search failed:', error);
            }
        }, CONFIG.DEBOUNCE_DELAY);

        this.searchInput.addEventListener('input', (e) => {
            debouncedSearch(e.target.value.trim());
        });

        this.searchInput.addEventListener('focus', () => {
            if (this.searchInput.value.length >= 2) {
                this.resultsContainer.style.display = 'block';
            }
        });

        document.addEventListener('click', (e) => {
            if (!this.searchInput.contains(e.target) && !this.resultsContainer.contains(e.target)) {
                this.hideResults();
            }
        });
    },

    showResults(products) {
        if (!products.length) {
            this.resultsContainer.innerHTML = '<p style="padding: 1rem; color: #666;">Không tìm thấy sản phẩm</p>';
        } else {
            this.resultsContainer.innerHTML = products.map(product => `
                <a href="product.php?slug=${product.slug}" class="search-result-item" style="
                    display: flex;
                    align-items: center;
                    gap: 1rem;
                    padding: 0.75rem 1rem;
                    border-bottom: 1px solid #eee;
                    transition: background 0.2s;
                " onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='transparent'">
                    <img src="${product.image || 'assets/images/placeholder.jpg'}" alt="${product.name}" style="
                        width: 50px;
                        height: 50px;
                        object-fit: cover;
                        border-radius: 4px;
                    ">
                    <div>
                        <h4 style="margin: 0; font-size: 0.9rem; color: #1a1a1a;">${product.name}</h4>
                        <span style="font-size: 0.85rem; color: #b76e79; font-weight: 600;">
                            ${Utils.formatPrice(product.sale_price || product.price)}
                        </span>
                    </div>
                </a>
            `).join('');
        }

        this.resultsContainer.style.display = 'block';
    },

    hideResults() {
        this.resultsContainer.style.display = 'none';
    }
};

// ========================================
// NEWSLETTER HANDLER
// ========================================
const NewsletterHandler = {
    init() {
        const form = document.querySelector('.newsletter-form');
        if (!form) return;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const emailInput = form.querySelector('input[type="email"]');
            const email = emailInput.value.trim();

            if (!email) {
                Toast.error('Vui lòng nhập email');
                return;
            }

            const btn = form.querySelector('button');
            const originalText = btn.textContent;
            btn.textContent = 'Đang gửi...';
            btn.disabled = true;

            try {
                const response = await API.subscribeNewsletter(email);

                if (response.success) {
                    Toast.success(response.message);
                    emailInput.value = '';
                } else {
                    Toast.error(response.message);
                }
            } catch (error) {
                Toast.error('Có lỗi xảy ra, vui lòng thử lại');
            } finally {
                btn.textContent = originalText;
                btn.disabled = false;
            }
        });
    }
};

// ========================================
// UI ENHANCEMENTS
// ========================================
const UIEnhancements = {
    init() {
        this.initHeader();
        this.initBackToTop();
        this.initScrollReveal();
        this.initCountdown();
        this.initMobileMenu();
    },

    initHeader() {
        const header = document.getElementById('header');
        if (!header) return;

        window.addEventListener('scroll', Utils.throttle(() => {
            if (window.pageYOffset > 100) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        }, 100));
    },

    initBackToTop() {
        const btn = document.getElementById('backToTop');
        if (!btn) return;

        window.addEventListener('scroll', Utils.throttle(() => {
            if (window.pageYOffset > 500) {
                btn.classList.add('visible');
            } else {
                btn.classList.remove('visible');
            }
        }, 100));

        btn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    },

    initScrollReveal() {
        const elements = document.querySelectorAll('.section');

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('reveal', 'active');
                }
            });
        }, { threshold: 0.1 });

        elements.forEach(el => observer.observe(el));
    },

    initCountdown() {
        const daysEl = document.getElementById('days');
        const hoursEl = document.getElementById('hours');
        const minutesEl = document.getElementById('minutes');
        const secondsEl = document.getElementById('seconds');

        if (!daysEl) return;

        // End date: 7 days from now
        const endDate = new Date();
        endDate.setDate(endDate.getDate() + 7);

        const updateCountdown = () => {
            const now = new Date();
            const diff = endDate - now;

            if (diff <= 0) {
                daysEl.textContent = '00';
                hoursEl.textContent = '00';
                minutesEl.textContent = '00';
                secondsEl.textContent = '00';
                return;
            }

            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);

            daysEl.textContent = String(days).padStart(2, '0');
            hoursEl.textContent = String(hours).padStart(2, '0');
            minutesEl.textContent = String(minutes).padStart(2, '0');
            secondsEl.textContent = String(seconds).padStart(2, '0');
        };

        updateCountdown();
        setInterval(updateCountdown, 1000);
    },

    initMobileMenu() {
        const toggle = document.getElementById('menuToggle');
        const navMenu = document.querySelector('.nav-menu');

        if (!toggle || !navMenu) return;

        toggle.addEventListener('click', () => {
            navMenu.classList.toggle('active');
            toggle.classList.toggle('active');
        });
    }
};

// ========================================
// LAZY LOADING IMAGES
// ========================================
const LazyLoader = {
    init() {
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                            img.removeAttribute('data-src');
                        }
                        observer.unobserve(img);
                    }
                });
            });

            document.querySelectorAll('img[data-src]').forEach(img => {
                observer.observe(img);
            });
        }
    }
};

// ========================================
// INITIALIZE APPLICATION
// ========================================
document.addEventListener('DOMContentLoaded', async () => {
    // Initialize toast notifications
    Toast.init();

    // Initialize UI enhancements
    UIEnhancements.init();

    // Initialize managers
    await CartManager.init();
    await ProductManager.init();
    SearchManager.init();
    NewsletterHandler.init();

    // Initialize auth manager
    if (typeof AuthManager !== 'undefined') {
        AuthManager.init();
    }
    LazyLoader.init();

    console.log('🛍️ LUXE Fashion initialized successfully!');
});

// Add CSS for toast animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    
    .quickview-modal {
        position: fixed;
        inset: 0;
        z-index: 10001;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .quickview-modal.active { opacity: 1; }
    .quickview-overlay {
        position: absolute;
        inset: 0;
        background: rgba(0,0,0,0.6);
        backdrop-filter: blur(4px);
    }
    .quickview-content {
        position: relative;
        background: white;
        border-radius: 16px;
        max-width: 900px;
        width: 90%;
        max-height: 90vh;
        overflow: auto;
        transform: scale(0.9);
        transition: transform 0.3s ease;
    }
    .quickview-modal.active .quickview-content { transform: scale(1); }
    .quickview-close {
        position: absolute;
        top: 1rem;
        right: 1rem;
        width: 40px;
        height: 40px;
        background: #1a1a1a;
        color: white;
        border: none;
        border-radius: 50%;
        font-size: 1.5rem;
        cursor: pointer;
        z-index: 1;
    }
    .quickview-body {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
        padding: 2rem;
    }
    .quickview-image img {
        width: 100%;
        border-radius: 8px;
    }
    .placeholder-image {
        width: 100%;
        height: 400px;
        background: linear-gradient(135deg, #f0e6e8 0%, #e8d5d8 100%);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 6rem;
    }
    .quickview-info h2 {
        font-family: 'Playfair Display', serif;
        font-size: 1.75rem;
        margin-bottom: 1rem;
    }
    .quickview-price {
        margin-bottom: 1rem;
    }
    .quickview-price .current-price {
        font-size: 1.5rem;
        font-weight: 600;
        color: #b76e79;
    }
    .quickview-price .old-price {
        font-size: 1rem;
        color: #999;
        text-decoration: line-through;
        margin-left: 0.5rem;
    }
    .quickview-desc {
        color: #666;
        line-height: 1.6;
        margin-bottom: 1.5rem;
    }
    .quickview-variants {
        margin-bottom: 1.5rem;
    }
    .variant-group {
        margin-bottom: 1rem;
    }
    .variant-group label {
        display: block;
        font-weight: 500;
        margin-bottom: 0.5rem;
    }
    .variant-options {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    .variant-option {
        padding: 0.5rem 1rem;
        border: 2px solid #ddd;
        background: white;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .variant-option:hover,
    .variant-option.selected {
        border-color: #b76e79;
    }
    .variant-option.color-option {
        width: 32px;
        height: 32px;
        padding: 0;
        border-radius: 50%;
    }
    .quickview-quantity {
        margin-bottom: 1.5rem;
    }
    .quickview-quantity label {
        display: block;
        font-weight: 500;
        margin-bottom: 0.5rem;
    }
    .quantity-control {
        display: inline-flex;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    .qty-btn {
        width: 40px;
        height: 40px;
        background: #f5f5f5;
        border: none;
        cursor: pointer;
        font-size: 1.25rem;
    }
    .qty-btn:hover { background: #eee; }
    .qty-input {
        width: 60px;
        text-align: center;
        border: none;
        border-left: 1px solid #ddd;
        border-right: 1px solid #ddd;
    }
    
    @media (max-width: 768px) {
        .quickview-body {
            grid-template-columns: 1fr;
        }
    }
`;
document.head.appendChild(style);
