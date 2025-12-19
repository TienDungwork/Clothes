const AuthManager = {
    user: null,

    init() {
        this.checkSession();
        this.bindEvents();
    },

    checkSession() {
        const userData = sessionStorage.getItem('luxe_user');
        if (userData) {
            this.user = JSON.parse(userData);
            this.updateUI();
        }
    },

    async login(email, password, remember = false) {
        try {
            const response = await API.request('auth.login', {
                method: 'POST',
                body: { email, password, remember }
            });

            if (response.success) {
                this.user = response.user;
                sessionStorage.setItem('luxe_user', JSON.stringify(this.user));
                this.updateUI();
                this.closeModal();
                Toast.success('Đăng nhập thành công!');

                // Refresh cart to get user's cart (with merged guest cart)
                if (typeof CartManager !== 'undefined') {
                    await CartManager.refresh();
                }

                // Redirect to admin page if user is admin
                if (this.user.role === 'admin') {
                    window.location.href = '/pages/admin.php';
                }
            } else {
                Toast.error(response.message);
            }
            return response;
        } catch (error) {
            Toast.error('Có lỗi xảy ra');
            return { success: false };
        }
    },

    async register(data) {
        try {
            const response = await API.request('auth.register', {
                method: 'POST',
                body: data
            });

            if (response.success) {
                Toast.success('Đăng ký thành công! Vui lòng đăng nhập.');
                this.showLogin();
            } else {
                Toast.error(response.message);
            }
            return response;
        } catch (error) {
            Toast.error('Có lỗi xảy ra');
            return { success: false };
        }
    },

    async logout() {
        try {
            await API.request('auth.logout', { method: 'POST' });
        } catch (e) { }

        this.user = null;
        sessionStorage.removeItem('luxe_user');
        this.updateUI();
        Toast.success('Đã đăng xuất');
    },

    updateUI() {
        const userIcon = document.querySelector('.header-icon[title="Tài khoản"]');
        if (!userIcon) return;

        if (this.user) {
            const initial = this.user.full_name?.charAt(0).toUpperCase() || 'U';
            userIcon.innerHTML = `<div class="user-avatar">${initial}</div>`;
            userIcon.classList.add('user-menu');

            let dropdown = userIcon.querySelector('.user-menu-dropdown');
            if (!dropdown) {
                dropdown = document.createElement('div');
                dropdown.className = 'user-menu-dropdown';
                dropdown.innerHTML = `
                    <span class="user-menu-item" style="font-weight:600;color:var(--secondary)">${this.user.full_name}</span>
                    <a href="/pages/account.html" class="user-menu-item">Tài khoản</a>
                    <a href="/pages/orders.html" class="user-menu-item">Đơn hàng</a>
                    <a href="#" class="user-menu-item" id="logoutBtn">Đăng xuất</a>
                `;
                userIcon.appendChild(dropdown);

                dropdown.querySelector('#logoutBtn').addEventListener('click', (e) => {
                    e.preventDefault();
                    this.logout();
                });
            }
        } else {
            userIcon.innerHTML = `
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            `;
            userIcon.classList.remove('user-menu');
            const dropdown = userIcon.querySelector('.user-menu-dropdown');
            if (dropdown) dropdown.remove();
        }
    },

    showModal(type = 'login') {
        let modal = document.getElementById('authModal');
        if (!modal) {
            modal = this.createModal();
            document.body.appendChild(modal);
        }

        this.switchTab(type);
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    },

    showLogin() {
        this.showModal('login');
    },

    showRegister() {
        this.showModal('register');
    },

    closeModal() {
        const modal = document.getElementById('authModal');
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    },

    switchTab(type) {
        const loginTab = document.querySelector('[data-auth-tab="login"]');
        const registerTab = document.querySelector('[data-auth-tab="register"]');
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');

        if (type === 'login') {
            loginTab?.classList.add('active');
            registerTab?.classList.remove('active');
            if (loginForm) loginForm.style.display = 'block';
            if (registerForm) registerForm.style.display = 'none';
        } else {
            loginTab?.classList.remove('active');
            registerTab?.classList.add('active');
            if (loginForm) loginForm.style.display = 'none';
            if (registerForm) registerForm.style.display = 'block';
        }
    },

    createModal() {
        const modal = document.createElement('div');
        modal.id = 'authModal';
        modal.className = 'modal-overlay';
        modal.innerHTML = `
            <div class="modal">
                <div class="modal-header">
                    <h2 class="modal-title">Tài khoản</h2>
                    <button class="modal-close" onclick="AuthManager.closeModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="auth-tabs">
                        <button class="auth-tab active" data-auth-tab="login">Đăng nhập</button>
                        <button class="auth-tab" data-auth-tab="register">Đăng ký</button>
                    </div>
                    
                    <form id="loginForm">
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-input" name="email" required placeholder="email@example.com">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Mật khẩu</label>
                            <input type="password" class="form-input" name="password" required placeholder="••••••••">
                        </div>
                        <div class="form-group">
                            <label class="form-checkbox">
                                <input type="checkbox" name="remember">
                                <span>Ghi nhớ đăng nhập</span>
                            </label>
                        </div>
                        <button type="submit" class="form-submit">Đăng nhập</button>
                    </form>
                    
                    <form id="registerForm" style="display:none">
                        <div class="form-group">
                            <label class="form-label">Họ và tên</label>
                            <input type="text" class="form-input" name="full_name" required placeholder="Nguyễn Văn A">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-input" name="email" required placeholder="email@example.com">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Số điện thoại</label>
                            <input type="tel" class="form-input" name="phone" placeholder="0909123456">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Mật khẩu</label>
                            <input type="password" class="form-input" name="password" required placeholder="Tối thiểu 6 ký tự">
                        </div>
                        <button type="submit" class="form-submit">Đăng ký</button>
                    </form>
                    
                    <div class="form-footer">
                        <span id="authFooterText">Chưa có tài khoản? <a href="#" id="switchToRegister">Đăng ký ngay</a></span>
                    </div>
                </div>
            </div>
        `;

        return modal;
    },

    bindEvents() {
        document.addEventListener('click', (e) => {
            const userIcon = e.target.closest('.header-icon[title="Tài khoản"]');

            // Nếu click vào link trong dropdown, cho phép navigate
            const menuLink = e.target.closest('.user-menu-item[href]:not(#logoutBtn)');
            if (menuLink && menuLink.getAttribute('href') !== '#') {
                return; // Cho phép link hoạt động bình thường
            }

            if (userIcon) {
                e.preventDefault();
                e.stopPropagation();

                console.log('User icon clicked, this.user:', this.user);

                if (!this.user) {
                    // Chưa đăng nhập -> hiện modal login
                    this.showLogin();
                } else {
                    // Đã đăng nhập -> toggle dropdown menu
                    const dropdown = userIcon.querySelector('.user-menu-dropdown');
                    console.log('Dropdown element:', dropdown);
                    if (dropdown) {
                        dropdown.classList.toggle('show');
                    }
                }
            }

            // Đóng dropdown khi click bên ngoài
            if (!e.target.closest('.header-icon[title="Tài khoản"]')) {
                const dropdown = document.querySelector('.user-menu-dropdown.show');
                if (dropdown) dropdown.classList.remove('show');
            }

            if (e.target.closest('.modal-overlay') === e.target) {
                this.closeModal();
            }

            const authTab = e.target.closest('[data-auth-tab]');
            if (authTab) {
                this.switchTab(authTab.dataset.authTab);
            }

            if (e.target.id === 'switchToRegister') {
                e.preventDefault();
                this.switchTab('register');
            }
        });

        document.addEventListener('submit', async (e) => {
            if (e.target.id === 'loginForm') {
                e.preventDefault();
                const form = e.target;
                const btn = form.querySelector('.form-submit');
                btn.disabled = true;
                btn.textContent = 'Đang xử lý...';

                await this.login(
                    form.email.value,
                    form.password.value,
                    form.remember.checked
                );

                btn.disabled = false;
                btn.textContent = 'Đăng nhập';
            }

            if (e.target.id === 'registerForm') {
                e.preventDefault();
                const form = e.target;
                const btn = form.querySelector('.form-submit');
                btn.disabled = true;
                btn.textContent = 'Đang xử lý...';

                await this.register({
                    full_name: form.full_name.value,
                    email: form.email.value,
                    phone: form.phone.value,
                    password: form.password.value
                });

                btn.disabled = false;
                btn.textContent = 'Đăng ký';
            }
        });
    }
};
