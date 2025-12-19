/**
 * LUXE Fashion - Main JavaScript Application
 * 
 * Entry point that imports and initializes all modules
 */

// Import modules
import { CONFIG, Utils } from './modules/utils.js';
import { API } from './modules/api.js';
import { Toast } from './modules/toast.js';
import { CartManager } from './modules/cart.js';
import { ProductManager } from './modules/products.js';
import { SearchManager } from './modules/search.js';
import { NewsletterHandler, UIEnhancements, LazyLoader } from './modules/ui.js';

// Make modules available globally for backward compatibility
window.CONFIG = CONFIG;
window.Utils = Utils;
window.API = API;
window.Toast = Toast;
window.CartManager = CartManager;
window.ProductManager = ProductManager;
window.SearchManager = SearchManager;

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

// Add CSS for toast animations and quickview modal
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
