// Wibubu E-commerce App - Main JavaScript
// Single Page Application with Hash-based Routing

class WibubuApp {
    constructor() {
        this.currentUser = null;
        this.cart = [];
        this.products = [];
        this.categories = [];
        this.promotions = [];
        this.searchTimeout = null;

        this.init();
    }

    async init() {
        // Initialize theme and brightness
        this.initTheme();
        this.initBrightness();

        // Set up event listeners
        this.setupEventListeners();

        // Load initial data
        await this.loadInitialData();

        // Initialize routing
        this.initRouter();

        // Check authentication status
        await this.checkAuth();

        // Load cart
        await this.loadCart();
    }

    initTheme() {
        const savedTheme = localStorage.getItem('theme') || 'light';
        const html = document.documentElement;

        if (savedTheme === 'dark') {
            html.classList.add('dark');
            document.getElementById('theme-toggle').textContent = '🌙';
        } else {
            html.classList.remove('dark');
            document.getElementById('theme-toggle').textContent = '🌞';
        }
    }

    initBrightness() {
        const savedBrightness = localStorage.getItem('brightness') || '1';
        const brightnessSlider = document.getElementById('brightness-slider');
        const mainContent = document.getElementById('main-content');

        brightnessSlider.value = savedBrightness;
        mainContent.style.filter = `brightness(${savedBrightness})`;
    }

    setupEventListeners() {
        // Theme toggle
        document.getElementById('theme-toggle').addEventListener('click', () => {
            this.toggleTheme();
        });

        // Brightness control
        document.getElementById('brightness-slider').addEventListener('input', (e) => {
            this.adjustBrightness(e.target.value);
        });

        // Search functionality
        const searchInput = document.getElementById('search-input');
        const searchBtn = document.getElementById('search-btn');

        searchInput.addEventListener('input', (e) => {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.performSearch(e.target.value);
            }, 300);
        });

        searchBtn.addEventListener('click', () => {
            this.performSearch(searchInput.value);
        });

        // Mobile menu toggle
        document.getElementById('mobile-menu-toggle').addEventListener('click', () => {
            document.getElementById('main-nav').classList.toggle('active');
        });

        // Form submissions
        document.getElementById('login-form-element').addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleLogin();
        });

        document.getElementById('register-form-element').addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleRegister();
        });

        // Product filters
        const categoryFilter = document.getElementById('category-filter');
        const sortFilter = document.getElementById('sort-filter');
        const minPrice = document.getElementById('min-price');
        const maxPrice = document.getElementById('max-price');

        if (categoryFilter) {
            categoryFilter.addEventListener('change', () => this.filterProducts());
            sortFilter.addEventListener('change', () => this.filterProducts());
            minPrice.addEventListener('input', () => {
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => this.filterProducts(), 300);
            });
            maxPrice.addEventListener('input', () => {
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => this.filterProducts(), 300);
            });
        }

        // Hash change for routing
        window.addEventListener('hashchange', () => {
            this.handleRoute();
        });

        window.addEventListener('DOMContentLoaded', () => {
            this.handleRoute();
        });
    }

    initRouter() {
        this.handleRoute();
    }

    async handleRoute() {
        const hash = window.location.hash.substring(1) || 'home';
        const [route, params] = hash.split('?');

        // Hide all sections
        document.querySelectorAll('[data-route]').forEach(section => {
            section.classList.add('hidden');
        });

        // Show current section
        const currentSection = document.querySelector(`[data-route="${route}"]`);
        if (currentSection) {
            currentSection.classList.remove('hidden');
            this.updatePageTitle(route);

            // Handle specific routes
            switch (route) {
                case 'home':
                    this.loadFeaturedProducts();
                    break;
                case 'san-pham':
                    this.loadProducts();
                    break;
                case 'danh-muc':
                    this.loadCategories();
                    break;
                case 'khuyen-mai':
                    this.loadPromotions();
                    break;
                case 'chi-tiet':
                    this.loadProductDetail(params);
                    break;
                case 'gio-hang':
                    this.loadCartPage();
                    break;
                case 'thanh-toan':
                    await this.loadCheckoutPage();
                    break;
                case 'don-hang':
                    await this.loadOrderConfirmationPage();
                    break;
                case 'dang-nhap':
                case 'dang-ky':
                    // Already handled by HTML
                    break;
            }
        }
    }

    updatePageTitle(route) {
        const titles = {
            'home': 'Wibubu - Trang chủ',
            'gioi-thieu': 'Wibubu - Giới thiệu',
            'san-pham': 'Wibubu - Sản phẩm',
            'danh-muc': 'Wibubu - Danh mục',
            'khuyen-mai': 'Wibubu - Khuyến mãi',
            'chi-tiet': 'Wibubu - Chi tiết sản phẩm',
            'gio-hang': 'Wibubu - Giỏ hàng',
            'thanh-toan': 'Wibubu - Thanh toán',
            'don-hang': 'Wibubu - Xác nhận đơn hàng',
            'dang-nhap': 'Wibubu - Đăng nhập',
            'dang-ky': 'Wibubu - Đăng ký'
        };

        document.getElementById('page-title').textContent = titles[route] || 'Wibubu';
    }

    toggleTheme() {
        const html = document.documentElement;
        const themeToggle = document.getElementById('theme-toggle');

        if (html.classList.contains('dark')) {
            html.classList.remove('dark');
            themeToggle.textContent = '🌞';
            localStorage.setItem('theme', 'light');
        } else {
            html.classList.add('dark');
            themeToggle.textContent = '🌙';
            localStorage.setItem('theme', 'dark');
        }
    }

    adjustBrightness(value) {
        const mainContent = document.getElementById('main-content');
        mainContent.style.filter = `brightness(${value})`;
        localStorage.setItem('brightness', value);
    }

    async apiCall(endpoint, options = {}) {
        try {
            const response = await fetch(`/api/${endpoint}`, {
                headers: {
                    'Content-Type': 'application/json',
                    ...options.headers
                },
                ...options
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'API call failed');
            }

            return data;
        } catch (error) {
            console.error('API Error:', error);
            this.showError(error.message);
            throw error;
        }
    }

    async loadInitialData() {
        try {
            // Load categories for filter dropdown
            const categoriesData = await this.apiCall('categories.php?action=list');
            this.categories = categoriesData.categories || [];
            this.populateCategoryFilter();
        } catch (error) {
            console.error('Failed to load initial data:', error);
        }
    }

    populateCategoryFilter() {
        const categoryFilter = document.getElementById('category-filter');
        if (categoryFilter && this.categories.length > 0) {
            // Clear existing options except "Tất cả danh mục"
            categoryFilter.innerHTML = '<option value="">Tất cả danh mục</option>';

            this.categories.forEach(category => {
                const option = document.createElement('option');
                option.value = category.id;
                option.textContent = category.name;
                categoryFilter.appendChild(option);
            });
        }
    }

    async checkAuth() {
        try {
            const response = await this.apiCall('auth.php?action=me');
            if (response.user) {
                this.currentUser = response.user;
                this.updateAuthUI();
            }
        } catch (error) {
            // User not logged in
            this.currentUser = null;
            this.updateAuthUI();
        }
    }

    updateAuthUI() {
        const authLink = document.getElementById('auth-link');

        if (this.currentUser) {
            authLink.textContent = `Xin chào, ${this.currentUser.name}`;
            authLink.href = '#logout';
            authLink.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleLogout();
            });
        } else {
            authLink.textContent = 'Đăng nhập';
            authLink.href = '#dang-nhap';
        }
    }

    async handleLogin() {
        const email = document.getElementById('login-email').value;
        const password = document.getElementById('login-password').value;

        try {
            const response = await this.apiCall('auth.php?action=login', {
                method: 'POST',
                body: JSON.stringify({
                    email,
                    password
                })
            });

            this.currentUser = response.user;
            this.updateAuthUI();
            this.showSuccess('Đăng nhập thành công!');
            window.location.hash = '#home';
        } catch (error) {
            this.showError('Đăng nhập thất bại. Vui lòng kiểm tra lại thông tin.');
        }
    }

    async handleRegister() {
        const name = document.getElementById('register-name').value;
        const email = document.getElementById('register-email').value;
        const password = document.getElementById('register-password').value;
        const passwordConfirm = document.getElementById('register-password-confirm').value;

        if (password !== passwordConfirm) {
            this.showError('Mật khẩu xác nhận không khớp!');
            return;
        }

        try {
            const response = await this.apiCall('auth.php?action=register', {
                method: 'POST',
                body: JSON.stringify({
                    name,
                    email,
                    password
                })
            });

            this.showSuccess('Đăng ký thành công! Vui lòng đăng nhập.');
            window.location.hash = '#dang-nhap';
        } catch (error) {
            this.showError('Đăng ký thất bại. Email có thể đã được sử dụng.');
        }
    }

    async handleLogout() {
        try {
            await this.apiCall('auth.php?action=logout', {
                method: 'POST'
            });
            this.currentUser = null;
            this.cart = [];
            this.updateAuthUI();
            this.updateCartUI();
            this.showSuccess('Đăng xuất thành công!');
            window.location.hash = '#home';
        } catch (error) {
            this.showError('Đăng xuất thất bại.');
        }
    }

    async loadCart() {
        try {
            const response = await this.apiCall('cart.php?action=get');
            this.cart = response.items || [];
            this.updateCartUI();
        } catch (error) {
            console.error('Failed to load cart:', error);
        }
    }

    updateCartUI() {
        const cartCount = document.getElementById('cart-count');
        const totalItems = this.cart.reduce((sum, item) => sum + item.quantity, 0);
        cartCount.textContent = totalItems;
    }

    async performSearch(query) {
        if (query.length > 0) {
            window.location.hash = `#san-pham?q=${encodeURIComponent(query)}`;
        }
    }

    async loadFeaturedProducts() {
        try {
            const response = await this.apiCall('products.php?action=list&limit=8');
            const products = response.products || [];
            this.renderFeaturedProducts(products);
        } catch (error) {
            console.error('Failed to load featured products:', error);
        }
    }

    renderFeaturedProducts(products) {
        const container = document.getElementById('featured-products-grid');
        container.innerHTML = '';

        products.forEach(product => {
            const productCard = this.createProductCard(product);
            container.appendChild(productCard);
        });
    }

    async loadProducts() {
        const hash = window.location.hash;
        const urlParams = new URLSearchParams(hash.includes('?') ? hash.split('?')[1] : '');

        const params = {
            action: 'list',
            q: urlParams.get('q') || '',
            cat: document.getElementById('category-filter') ? .value || '',
            sort: document.getElementById('sort-filter') ? .value || 'new',
            min_price: document.getElementById('min-price') ? .value || '',
            max_price: document.getElementById('max-price') ? .value || ''
        };

        try {
            this.showProductsLoading(true);
            const queryString = new URLSearchParams(params).toString();
            const response = await this.apiCall(`products.php?${queryString}`);
            const products = response.products || [];
            this.renderProducts(products);
        } catch (error) {
            console.error('Failed to load products:', error);
        } finally {
            this.showProductsLoading(false);
        }
    }

    showProductsLoading(show) {
        const loading = document.getElementById('products-loading');
        const grid = document.getElementById('products-grid');

        if (show) {
            loading.classList.remove('hidden');
            grid.classList.add('hidden');
        } else {
            loading.classList.add('hidden');
            grid.classList.remove('hidden');
        }
    }

    renderProducts(products) {
        const container = document.getElementById('products-grid');
        container.innerHTML = '';

        if (products.length === 0) {
            container.innerHTML = `
                <div class="empty-state" style="grid-column: 1 / -1;">
                    <h3>Không tìm thấy sản phẩm</h3>
                    <p>Vui lòng thử tìm kiếm với từ khóa khác</p>
                </div>
            `;
            return;
        }

        products.forEach(product => {
            const productCard = this.createProductCard(product);
            container.appendChild(productCard);
        });
    }

    createProductCard(product) {
        const card = document.createElement('div');
        card.className = 'product-card';
        card.onclick = () => {
            window.location.hash = `#chi-tiet?id=${product.id}`;
        };

        card.innerHTML = `
            <div class="product-image">
                ${product.image ? `<img src="/assets/img/${product.image}" alt="${product.name}" style="width: 100%; height: 100%; object-fit: cover;">` : `<span>Hình ảnh sản phẩm</span>`}
            </div>
            <div class="product-info">
                <h3 class="product-name">${product.name}</h3>
                <p class="product-description">${product.description || ''}</p>
                <div class="product-price">${this.formatPrice(product.price)}</div>
                <div class="product-actions">
                    <button class="btn btn-gradient" onclick="event.stopPropagation(); app.addToCart(${product.id}, 1)">
                        Thêm vào giỏ
                    </button>
                </div>
            </div>
        `;

        return card;
    }

    async loadCategories() {
        try {
            const response = await this.apiCall('categories.php?action=list');
            const categories = response.categories || [];
            this.renderCategories(categories);
        } catch (error) {
            console.error('Failed to load categories:', error);
        }
    }

    renderCategories(categories) {
        const container = document.getElementById('categories-grid');
        container.innerHTML = '';

        categories.forEach(category => {
            const categoryCard = this.createCategoryCard(category);
            container.appendChild(categoryCard);
        });
    }

    createCategoryCard(category) {
        const card = document.createElement('div');
        card.className = 'category-card';
        card.onclick = () => {
            window.location.hash = `#san-pham?cat=${category.id}`;
        };

        card.innerHTML = `
            <div class="category-icon">📦</div>
            <h3>${category.name}</h3>
            <p>Khám phá bộ sưu tập ${category.name.toLowerCase()}</p>
        `;

        return card;
    }

    async loadPromotions() {
        try {
            const response = await this.apiCall('promotions.php?action=list');
            const promotions = response.promotions || [];
            this.renderPromotions(promotions);
        } catch (error) {
            console.error('Failed to load promotions:', error);
        }
    }

    renderPromotions(promotions) {
        const container = document.getElementById('promotions-grid');
        container.innerHTML = '';

        promotions.forEach(promotion => {
            const promotionCard = this.createPromotionCard(promotion);
            container.appendChild(promotionCard);
        });
    }

    createPromotionCard(promotion) {
        const card = document.createElement('div');
        card.className = 'promotion-card';

        card.innerHTML = `
            <h3>${promotion.title}</h3>
            <div class="promotion-code">${promotion.code}</div>
            <p>Giảm ${promotion.type === 'percent' ? promotion.value + '%' : this.formatPrice(promotion.value)}</p>
            <p>Đơn hàng từ: ${this.formatPrice(promotion.min_order)}</p>
            <small>Có hiệu lực đến: ${new Date(promotion.ends_at).toLocaleDateString('vi-VN')}</small>
        `;

        return card;
    }

    async loadProductDetail(params) {
        const urlParams = new URLSearchParams(params || '');
        const productId = urlParams.get('id');

        if (!productId) {
            document.getElementById('product-detail').innerHTML = '<p>Không tìm thấy sản phẩm</p>';
            return;
        }

        try {
            const response = await this.apiCall(`products.php?action=detail&id=${productId}`);
            const product = response.product;
            this.renderProductDetail(product);
        } catch (error) {
            document.getElementById('product-detail').innerHTML = '<p>Không thể tải thông tin sản phẩm</p>';
        }
    }

    renderProductDetail(product) {
        const container = document.getElementById('product-detail');
        container.innerHTML = `
            <div class="product-detail-content">
                <div class="product-detail-image">
                    ${product.image ? `<img src="/assets/img/${product.image}" alt="${product.name}" style="width: 100%; border-radius: 1rem;">` : `<div class="placeholder-image">Hình ảnh sản phẩm</div>`}
                </div>
                <div class="product-detail-info">
                    <h1>${product.name}</h1>
                    <p class="product-price" style="font-size: 2rem; margin: 1rem 0;">${this.formatPrice(product.price)}</p>
                    <p>${product.description || ''}</p>
                    <p><strong>Tồn kho:</strong> ${product.stock} sản phẩm</p>
                    
                    <div class="product-actions" style="margin-top: 2rem;">
                        <div class="quantity-controls" style="margin-bottom: 1rem;">
                            <label>Số lượng:</label>
                            <button type="button" class="quantity-btn" onclick="this.nextElementSibling.stepDown()">-</button>
                            <input type="number" id="product-quantity" value="1" min="1" max="${product.stock}" style="width: 60px; text-align: center; margin: 0 0.5rem;">
                            <button type="button" class="quantity-btn" onclick="this.previousElementSibling.stepUp()">+</button>
                        </div>
                        <button class="btn btn-gradient" onclick="app.addToCart(${product.id}, document.getElementById('product-quantity').value)" ${product.stock === 0 ? 'disabled' : ''}>
                            ${product.stock === 0 ? 'Hết hàng' : 'Thêm vào giỏ hàng'}
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    async addToCart(productId, quantity = 1) {
        try {
            await this.apiCall('cart.php?action=add', {
                method: 'POST',
                body: JSON.stringify({
                    product_id: productId,
                    quantity: parseInt(quantity)
                })
            });

            await this.loadCart();
            this.showSuccess('Đã thêm sản phẩm vào giỏ hàng!');
        } catch (error) {
            this.showError('Không thể thêm sản phẩm vào giỏ hàng');
        }
    }

    async loadCartPage() {
        try {
            const response = await this.apiCall('cart.php?action=get');
            const cart = response.cart || {};
            const items = response.items || [];
            this.renderCartPage(cart, items);
        } catch (error) {
            console.error('Failed to load cart page:', error);
        }
    }

    renderCartPage(cart, items) {
        const container = document.getElementById('cart-content');

        if (items.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <h3>Giỏ hàng trống</h3>
                    <p>Bạn chưa có sản phẩm nào trong giỏ hàng</p>
                    <button class="btn btn-gradient" onclick="location.hash='#san-pham'">Mua sắm ngay</button>
                </div>
            `;
            return;
        }

        let total = 0;
        const itemsHtml = items.map(item => {
            const itemTotal = item.price * item.quantity;
            total += itemTotal;

            return `
                <div class="cart-item">
                    <div class="cart-item-image">
                        ${item.image ? `<img src="/assets/img/${item.image}" alt="${item.name}">` : 'Hình ảnh'}
                    </div>
                    <div class="cart-item-info">
                        <h4>${item.name}</h4>
                        <p>${this.formatPrice(item.price)}</p>
                    </div>
                    <div class="cart-item-actions">
                        <div class="quantity-controls">
                            <button class="quantity-btn" onclick="app.updateCartItem(${item.product_id}, ${item.quantity - 1})">-</button>
                            <span>${item.quantity}</span>
                            <button class="quantity-btn" onclick="app.updateCartItem(${item.product_id}, ${item.quantity + 1})">+</button>
                        </div>
                        <div>${this.formatPrice(itemTotal)}</div>
                        <button class="btn" style="background: #e53e3e; color: white;" onclick="app.removeFromCart(${item.product_id})">Xóa</button>
                    </div>
                </div>
            `;
        }).join('');

        container.innerHTML = `
            <div class="cart-items">
                ${itemsHtml}
            </div>
            <div class="cart-summary card" style="margin-top: 2rem;">
                <h3>Tổng cộng: ${this.formatPrice(total)}</h3>
                <div style="margin-top: 1rem;">
                    <input type="text" id="promo-code" placeholder="Mã khuyến mãi" style="margin-right: 0.5rem;">
                    <button class="btn" onclick="app.applyPromoCode()">Áp dụng</button>
                </div>
                <button class="btn btn-gradient" style="width: 100%; margin-top: 1rem;" onclick="app.navigateToCheckout()">Thanh toán</button>
            </div>
        `;
    }

    async updateCartItem(productId, quantity) {
        if (quantity <= 0) {
            await this.removeFromCart(productId);
            return;
        }

        try {
            await this.apiCall('cart.php?action=update', {
                method: 'POST',
                body: JSON.stringify({
                    product_id: productId,
                    quantity
                })
            });

            await this.loadCart();
            await this.loadCartPage();
        } catch (error) {
            this.showError('Không thể cập nhật giỏ hàng');
        }
    }

    async removeFromCart(productId) {
        try {
            await this.apiCall('cart.php?action=remove', {
                method: 'POST',
                body: JSON.stringify({
                    product_id: productId
                })
            });

            await this.loadCart();
            await this.loadCartPage();
            this.showSuccess('Đã xóa sản phẩm khỏi giỏ hàng');
        } catch (error) {
            this.showError('Không thể xóa sản phẩm');
        }
    }

    async applyPromoCode() {
        const code = document.getElementById('promo-code').value;
        if (!code) return;

        try {
            const response = await this.apiCall('promotions.php?action=validate-code', {
                method: 'POST',
                body: JSON.stringify({
                    code
                })
            });

            this.showSuccess(`Đã áp dụng mã khuyến mãi: ${response.promotion.title}`);
            // TODO: Recalculate cart total with promotion
        } catch (error) {
            this.showError('Mã khuyến mãi không hợp lệ');
        }
    }

    async filterProducts() {
        await this.loadProducts();
    }

    async loadOrderConfirmationPage() {
        const urlParams = new URLSearchParams(window.location.hash.split('?')[1]);
        const orderId = urlParams.get('id');

        if (!orderId) {
            this.showError('Không tìm thấy thông tin đơn hàng');
            window.location.hash = '#';
            return;
        }

        const container = document.getElementById('order-confirmation-content');

        try {
            // Show loading
            container.innerHTML = '<div class="loading">Đang tải thông tin đơn hàng...</div>';

            const response = await this.apiCall(`orders.php?action=detail&id=${orderId}`);

            if (response.success) {
                const {
                    order,
                    items
                } = response;

                const confirmationHtml = `
                    <div class="order-confirmation">
                        <div class="success-header">
                            <div class="success-icon">✅</div>
                            <h2>Đặt hàng thành công!</h2>
                            <p>Cảm ơn bạn đã đặt hàng. Đơn hàng #${order.id} đã được xác nhận.</p>
                        </div>
                        
                        <div class="order-details">
                            <div class="order-info">
                                <h3>Thông tin đơn hàng</h3>
                                <div class="info-row">
                                    <span>Mã đơn hàng:</span>
                                    <span>#${order.id}</span>
                                </div>
                                <div class="info-row">
                                    <span>Ngày đặt:</span>
                                    <span>${new Date(order.created_at).toLocaleDateString('vi-VN')}</span>
                                </div>
                                <div class="info-row">
                                    <span>Trạng thái:</span>
                                    <span class="status-badge">${order.status}</span>
                                </div>
                                <div class="info-row">
                                    <span>Tổng tiền:</span>
                                    <span class="total-amount">${this.formatPrice(order.total_amount)}</span>
                                </div>
                                ${order.discount_amount > 0 ? `
                                <div class="info-row">
                                    <span>Giảm giá:</span>
                                    <span class="discount">-${this.formatPrice(order.discount_amount)}</span>
                                </div>
                                ` : ''}
                            </div>
                            
                            <div class="shipping-info">
                                <h3>Thông tin giao hàng</h3>
                                <div class="info-row">
                                    <span>Người nhận:</span>
                                    <span>${order.shipping_name}</span>
                                </div>
                                <div class="info-row">
                                    <span>Email:</span>
                                    <span>${order.shipping_email}</span>
                                </div>
                                <div class="info-row">
                                    <span>Điện thoại:</span>
                                    <span>${order.shipping_phone}</span>
                                </div>
                                <div class="info-row">
                                    <span>Địa chỉ:</span>
                                    <span>${order.shipping_address}</span>
                                </div>
                                ${order.notes ? `
                                <div class="info-row">
                                    <span>Ghi chú:</span>
                                    <span>${order.notes}</span>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                        
                        <div class="order-items">
                            <h3>Chi tiết sản phẩm</h3>
                            <div class="items-list">
                                ${items.map(item => `
                                    <div class="order-item">
                                        <div class="item-info">
                                            <span class="item-name">${item.product_name}</span>
                                            <span class="item-quantity">x${item.quantity}</span>
                                        </div>
                                        <div class="item-price">${this.formatPrice(item.item_total)}</div>
                                    </div>
                                `).join('')}
                            </div>
                            
                            <div class="order-summary">
                                <div class="summary-row">
                                    <span>Tạm tính:</span>
                                    <span>${this.formatPrice(order.total_amount + order.discount_amount)}</span>
                                </div>
                                ${order.discount_amount > 0 ? `
                                <div class="summary-row discount">
                                    <span>Giảm giá:</span>
                                    <span>-${this.formatPrice(order.discount_amount)}</span>
                                </div>
                                ` : ''}
                                <div class="summary-row total">
                                    <span>Tổng cộng:</span>
                                    <span>${this.formatPrice(order.total_amount)}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="order-actions">
                            <button class="btn btn-primary" onclick="window.location.hash='#'">
                                Tiếp tục mua sắm
                            </button>
                            ${this.currentUser ? `
                            <button class="btn btn-secondary" onclick="window.location.hash='#tai-khoan'">
                                Xem đơn hàng của tôi
                            </button>
                            ` : ''}
                        </div>
                    </div>
                `;

                container.innerHTML = confirmationHtml;
            } else {
                throw new Error(response.message || 'Không thể tải thông tin đơn hàng');
            }
        } catch (error) {
            container.innerHTML = `
                <div class="error-message">
                    <h3>Không thể tải thông tin đơn hàng</h3>
                    <p>${error.message}</p>
                    <button class="btn btn-primary" onclick="window.location.hash='#'">
                        Về trang chủ
                    </button>
                </div>
            `;
        }
    }

    formatPrice(price) {
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND'
        }).format(price);
    }

    showSuccess(message) {
        this.showNotification(message, 'success');
    }

    showError(message) {
        this.showNotification(message, 'error');
    }

    showNotification(message, type) {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 100px;
            right: 20px;
            background: ${type === 'success' ? '#48bb78' : '#e53e3e'};
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            z-index: 9999;
            max-width: 300px;
        `;
        notification.textContent = message;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    navigateToCheckout() {
        if (this.cart.length === 0) {
            this.showError('Giỏ hàng trống');
            return;
        }

        // Check if user is logged in - if not, suggest login or guest checkout
        if (!this.currentUser) {
            if (confirm('Bạn muốn đăng nhập để thanh toán dễ dàng hơn? (Có thể thanh toán không cần tài khoản)')) {
                window.location.hash = '#dang-nhap?redirect=thanh-toan';
                return;
            }
        }

        window.location.hash = '#thanh-toan';
    }

    async loadCheckoutPage() {
        if (this.cart.length === 0) {
            window.location.hash = '#gio-hang';
            return;
        }

        const container = document.getElementById('checkout-content');

        let total = 0;
        this.cart.forEach(item => {
            total += item.price * item.quantity;
        });

        const checkoutHtml = `
            <div class="checkout-container">
                <h2>Thanh toán</h2>
                
                <div class="checkout-grid">
                    <div class="checkout-form">
                        <div class="card">
                            <h3>Thông tin giao hàng</h3>
                            <form id="checkout-form">
                                <div class="form-group">
                                    <label for="shipping-name">Họ và tên *</label>
                                    <input type="text" id="shipping-name" name="shipping_name" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="shipping-email">Email *</label>
                                    <input type="email" id="shipping-email" name="shipping_email" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="shipping-phone">Số điện thoại *</label>
                                    <input type="tel" id="shipping-phone" name="shipping_phone" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="shipping-address">Địa chỉ giao hàng *</label>
                                    <textarea id="shipping-address" name="shipping_address" rows="3" required placeholder="Số nhà, tên đường, phường/xã, quận/huyện, tỉnh/thành phố"></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" id="billing-same" checked onchange="app.toggleBillingAddress()">
                                        Địa chỉ thanh toán giống địa chỉ giao hàng
                                    </label>
                                </div>
                                
                                <div id="billing-address" style="display: none;">
                                    <h4>Thông tin thanh toán</h4>
                                    <div class="form-group">
                                        <label for="billing-name">Họ và tên</label>
                                        <input type="text" id="billing-name" name="billing_name">
                                    </div>
                                    <div class="form-group">
                                        <label for="billing-address-text">Địa chỉ thanh toán</label>
                                        <textarea id="billing-address-text" name="billing_address" rows="3"></textarea>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="promo-code-checkout">Mã khuyến mãi</label>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <input type="text" id="promo-code-checkout" name="promotion_code" placeholder="Nhập mã khuyến mãi">
                                        <button type="button" class="btn" onclick="app.validatePromoCode()">Áp dụng</button>
                                    </div>
                                    <div id="promo-result"></div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="order-notes">Ghi chú đơn hàng</label>
                                    <textarea id="order-notes" name="notes" rows="2" placeholder="Ghi chú về đơn hàng (tùy chọn)"></textarea>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="order-summary">
                        <div class="card">
                            <h3>Đơn hàng của bạn</h3>
                            <div class="order-items">
                                ${this.cart.map(item => `
                                    <div class="order-item">
                                        <span class="item-name">${item.name} × ${item.quantity}</span>
                                        <span class="item-price">${this.formatPrice(item.price * item.quantity)}</span>
                                    </div>
                                `).join('')}
                            </div>
                            
                            <div class="order-totals">
                                <div class="subtotal">
                                    <span>Tạm tính:</span>
                                    <span id="subtotal">${this.formatPrice(total)}</span>
                                </div>
                                
                                <div id="discount-line" style="display: none;">
                                    <span>Giảm giá:</span>
                                    <span id="discount-amount">-₫0</span>
                                </div>
                                
                                <div class="total">
                                    <strong>
                                        <span>Tổng cộng:</span>
                                        <span id="final-total">${this.formatPrice(total)}</span>
                                    </strong>
                                </div>
                            </div>
                            
                            <button class="btn btn-gradient btn-full" onclick="app.submitOrder()" style="margin-top: 1rem;">
                                Đặt hàng
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        container.innerHTML = checkoutHtml;

        // Safely set user data after DOM insertion to prevent XSS
        if (this.currentUser) {
            document.getElementById('shipping-name').value = this.currentUser.name || '';
            document.getElementById('shipping-email').value = this.currentUser.email || '';
        }
    }

    toggleBillingAddress() {
        const checkbox = document.getElementById('billing-same');
        const billingDiv = document.getElementById('billing-address');

        billingDiv.style.display = checkbox.checked ? 'none' : 'block';
    }

    async validatePromoCode() {
        const code = document.getElementById('promo-code-checkout').value;
        if (!code) return;

        let total = 0;
        this.cart.forEach(item => {
            total += item.price * item.quantity;
        });

        try {
            const response = await this.apiCall('promotions.php?action=validate-code', {
                method: 'POST',
                body: JSON.stringify({
                    code,
                    cart_total: total
                })
            });

            if (response.success) {
                document.getElementById('promo-result').innerHTML = `
                    <div style="color: #48bb78; margin-top: 0.5rem;">
                        ✓ ${response.promotion.title} - Tiết kiệm ${this.formatPrice(response.promotion.discount_amount)}
                    </div>
                `;

                document.getElementById('discount-line').style.display = 'flex';
                document.getElementById('discount-amount').textContent = `-${this.formatPrice(response.promotion.discount_amount)}`;
                document.getElementById('final-total').textContent = this.formatPrice(response.promotion.final_total);

                this.currentPromotion = response.promotion;
            }
        } catch (error) {
            document.getElementById('promo-result').innerHTML = `
                <div style="color: #e53e3e; margin-top: 0.5rem;">
                    ✗ ${error.message || 'Mã khuyến mãi không hợp lệ'}
                </div>
            `;
        }
    }

    async submitOrder() {
        const form = document.getElementById('checkout-form');
        const formData = new FormData(form);

        // Validate required fields
        const requiredFields = ['shipping_name', 'shipping_email', 'shipping_phone', 'shipping_address'];
        for (const field of requiredFields) {
            if (!formData.get(field)) {
                this.showError('Vui lòng điền đầy đủ thông tin bắt buộc');
                return;
            }
        }

        const orderData = {};
        for (let [key, value] of formData.entries()) {
            orderData[key] = value;
        }

        // Add billing same as shipping if checked
        orderData.billing_same_as_shipping = document.getElementById('billing-same').checked;

        try {
            this.showNotification('Đang xử lý đơn hàng...', 'info');

            const response = await this.apiCall('orders.php?action=create', {
                method: 'POST',
                body: JSON.stringify(orderData)
            });

            if (response.success) {
                this.showSuccess('Đặt hàng thành công!');

                // Clear cart and update UI, then redirect
                this.cart = [];
                this.updateCartUI();
                window.location.hash = `#don-hang?id=${response.order_id}`;
            }
        } catch (error) {
            this.showError(error.message || 'Không thể đặt hàng. Vui lòng thử lại');
        }
    }
}

// Initialize the app
const app = new WibubuApp();