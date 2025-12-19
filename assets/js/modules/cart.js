/**
 * LUXE Fashion - Cart Manager
 * 
 * Handles shopping cart functionality
 */

import { Utils } from './utils.js';
import { API } from './api.js';
import { Toast } from './toast.js';

// ========================================
// CART MANAGER
// ========================================
export const CartManager = {
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

// Make CartManager available globally for onclick handlers
window.CartManager = CartManager;
