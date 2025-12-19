/**
 * LUXE Fashion - Utility Functions
 * 
 * Common utility functions and configuration
 */

// ========================================
// CONFIGURATION
// ========================================
export const CONFIG = {
    API_URL: '/api/index.php',
    CURRENCY: '₫',
    ANIMATION_DURATION: 300,
    DEBOUNCE_DELAY: 300,
    TOAST_DURATION: 3000
};

// ========================================
// UTILITY FUNCTIONS
// ========================================
export const Utils = {
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
