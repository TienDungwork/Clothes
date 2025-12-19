/**
 * LUXE Fashion - Search Manager
 * 
 * Handles product search functionality
 */

import { CONFIG, Utils } from './utils.js';
import { API } from './api.js';

// ========================================
// SEARCH MANAGER
// ========================================
export const SearchManager = {
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
                    text-decoration: none;
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
