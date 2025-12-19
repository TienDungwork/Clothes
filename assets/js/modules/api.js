/**
 * LUXE Fashion - API Service
 * 
 * Handles all API communication with backend
 */

import { CONFIG } from './utils.js';

// ========================================
// API SERVICE
// ========================================
export const API = {
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

    // ==================== PRODUCTS ====================
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

    // ==================== CART ====================
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

    // ==================== CATEGORIES ====================
    getCategories() {
        return this.request('categories');
    },

    // ==================== NEWSLETTER ====================
    subscribeNewsletter(email) {
        return this.request('newsletter.subscribe', {
            method: 'POST',
            body: { email }
        });
    }
};
