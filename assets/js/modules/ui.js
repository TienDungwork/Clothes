/**
 * LUXE Fashion - UI Enhancements
 * 
 * Handles UI features: header, back to top, scroll reveal, countdown, mobile menu
 */

import { Utils } from './utils.js';
import { API } from './api.js';
import { Toast } from './toast.js';

// ========================================
// NEWSLETTER HANDLER
// ========================================
export const NewsletterHandler = {
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
export const UIEnhancements = {
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
export const LazyLoader = {
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
