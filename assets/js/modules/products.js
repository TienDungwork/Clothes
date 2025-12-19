/**
 * LUXE Fashion - Product Manager
 * 
 * Handles product display, filtering, and quick view
 */

import { Utils } from './utils.js';
import { API } from './api.js';
import { Toast } from './toast.js';
import { CartManager } from './cart.js';

// ========================================
// PRODUCT MANAGER
// ========================================
export const ProductManager = {
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
