// Wibubu E-commerce Components
// Reusable UI components for rendering different elements

class WibubuComponents {
    static formatPrice(price) {
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND'
        }).format(price);
    }

    static formatDate(dateString) {
        return new Date(dateString).toLocaleDateString('vi-VN');
    }

    static createProductCard(product, showActions = true) {
        const card = document.createElement('div');
        card.className = 'product-card';
        card.setAttribute('data-product-id', product.id);

        if (showActions) {
            card.onclick = () => {
                window.location.hash = `#chi-tiet?id=${product.id}`;
            };
        }

        const actionsHtml = showActions ? `
            <div class="product-actions">
                <button class="btn btn-gradient" onclick="event.stopPropagation(); app.addToCart(${product.id}, 1)">
                    Thêm vào giỏ
                </button>
            </div>
        ` : '';

        card.innerHTML = `
            <div class="product-image">
                ${product.image ? 
                    `<img src="/assets/img/${product.image}" alt="${product.name}" 
                          style="width: 100%; height: 100%; object-fit: cover; border-radius: 0.5rem 0.5rem 0 0;"
                          onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                     <span style="display: none;">Hình ảnh sản phẩm</span>` :
                    `<span>Hình ảnh sản phẩm</span>`
                }
            </div>
            <div class="product-info">
                <h3 class="product-name">${product.name}</h3>
                <p class="product-description">${product.description || ''}</p>
                <div class="product-price">${this.formatPrice(product.price)}</div>
                <div class="product-stock">
                    ${product.stock > 0 ? 
                        `<span style="color: #48bb78;">✓ Còn hàng (${product.stock})</span>` :
                        `<span style="color: #e53e3e;">✗ Hết hàng</span>`
                    }
                </div>
                ${actionsHtml}
            </div>
        `;

        return card;
    }

    static createCategoryCard(category) {
        const card = document.createElement('div');
        card.className = 'category-card';
        card.setAttribute('data-category-id', category.id);
        card.onclick = () => {
            window.location.hash = `#san-pham?cat=${category.id}`;
        };

        // Category icons mapping
        const categoryIcons = {
            'ao-thun': '👕',
            'ao-so-mi': '👔',
            'quan-jeans': '👖',
            'ao-khoac': '🧥',
            'vay-dam': '👗',
            'phu-kien': '💍',
            'giay-dep': '👟',
            'tui-xach': '👜'
        };

        const icon = categoryIcons[category.slug] || '📦';

        card.innerHTML = `
            <div class="category-icon">${icon}</div>
            <h3>${category.name}</h3>
            <p>Khám phá bộ sưu tập ${category.name.toLowerCase()}</p>
        `;

        return card;
    }

    static createPromotionCard(promotion) {
        const card = document.createElement('div');
        card.className = 'promotion-card';
        card.setAttribute('data-promotion-id', promotion.id);

        const discountText = promotion.type === 'percent' ?
            `${promotion.value}%` :
            this.formatPrice(promotion.value);

        const now = new Date();
        const endDate = new Date(promotion.ends_at);
        const isActive = now <= endDate && promotion.active;

        card.innerHTML = `
            <h3>${promotion.title}</h3>
            <div class="promotion-code" onclick="navigator.clipboard.writeText('${promotion.code}'); app.showSuccess('Đã sao chép mã ${promotion.code}')">
                ${promotion.code}
            </div>
            <p class="promotion-value">Giảm ${discountText}</p>
            <p class="promotion-condition">Đơn hàng từ: ${this.formatPrice(promotion.min_order)}</p>
            <div class="promotion-validity">
                <small>Có hiệu lực đến: ${this.formatDate(promotion.ends_at)}</small>
                <span class="promotion-status ${isActive ? 'active' : 'expired'}">
                    ${isActive ? '✓ Đang hoạt động' : '✗ Đã hết hạn'}
                </span>
            </div>
        `;

        return card;
    }

    static createCartItem(item) {
        const cartItem = document.createElement('div');
        cartItem.className = 'cart-item';
        cartItem.setAttribute('data-cart-item-id', item.product_id);

        const itemTotal = item.price * item.quantity;

        cartItem.innerHTML = `
            <div class="cart-item-image">
                ${item.image ? 
                    `<img src="/assets/img/${item.image}" alt="${item.name}" 
                          style="width: 100%; height: 100%; object-fit: cover; border-radius: 0.5rem;"
                          onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                     <span style="display: none;">Hình ảnh</span>` :
                    `<span>Hình ảnh</span>`
                }
            </div>
            <div class="cart-item-info">
                <h4>${item.name}</h4>
                <p class="item-price">${this.formatPrice(item.price)}</p>
                <p class="item-total">Thành tiền: ${this.formatPrice(itemTotal)}</p>
            </div>
            <div class="cart-item-actions">
                <div class="quantity-controls">
                    <button class="quantity-btn" onclick="app.updateCartItem(${item.product_id}, ${item.quantity - 1})" ${item.quantity <= 1 ? 'disabled' : ''}>
                        -
                    </button>
                    <span class="quantity-display">${item.quantity}</span>
                    <button class="quantity-btn" onclick="app.updateCartItem(${item.product_id}, ${item.quantity + 1})">
                        +
                    </button>
                </div>
                <button class="btn btn-remove" onclick="app.removeFromCart(${item.product_id})" title="Xóa khỏi giỏ hàng">
                    🗑️
                </button>
            </div>
        `;

        return cartItem;
    }

    static createProductDetailView(product) {
        const container = document.createElement('div');
        container.className = 'product-detail-content';

        container.innerHTML = `
            <div class="product-detail-image">
                ${product.image ? 
                    `<img src="/assets/img/${product.image}" alt="${product.name}" 
                          style="width: 100%; max-height: 500px; object-fit: cover; border-radius: 1rem;"
                          onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                     <div class="placeholder-image" style="display: none;">Hình ảnh sản phẩm</div>` :
                    `<div class="placeholder-image">Hình ảnh sản phẩm</div>`
                }
            </div>
            <div class="product-detail-info">
                <h1>${product.name}</h1>
                <p class="product-price" style="font-size: 2rem; margin: 1rem 0; color: var(--grad-start); font-weight: 700;">
                    ${this.formatPrice(product.price)}
                </p>
                <div class="product-description" style="margin: 1.5rem 0; line-height: 1.8;">
                    <p>${product.description || 'Không có mô tả chi tiết.'}</p>
                </div>
                <div class="product-stock-info" style="margin: 1rem 0;">
                    <p><strong>Tình trạng:</strong> 
                        ${product.stock > 0 ? 
                            `<span style="color: #48bb78;">Còn ${product.stock} sản phẩm</span>` :
                            `<span style="color: #e53e3e;">Hết hàng</span>`
                        }
                    </p>
                </div>
                
                <div class="product-actions" style="margin-top: 2rem;">
                    <div class="quantity-controls" style="margin-bottom: 1rem; display: flex; align-items: center; gap: 1rem;">
                        <label style="font-weight: 600;">Số lượng:</label>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <button type="button" class="quantity-btn" onclick="document.getElementById('product-quantity').stepDown()" ${product.stock === 0 ? 'disabled' : ''}>-</button>
                            <input type="number" id="product-quantity" value="1" min="1" max="${product.stock}" 
                                   style="width: 60px; text-align: center; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 0.25rem;"
                                   ${product.stock === 0 ? 'disabled' : ''}>
                            <button type="button" class="quantity-btn" onclick="document.getElementById('product-quantity').stepUp()" ${product.stock === 0 ? 'disabled' : ''}>+</button>
                        </div>
                    </div>
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                        <button class="btn btn-gradient" style="flex: 1; min-width: 200px;" 
                                onclick="app.addToCart(${product.id}, parseInt(document.getElementById('product-quantity').value))" 
                                ${product.stock === 0 ? 'disabled' : ''}>
                            ${product.stock === 0 ? 'Hết hàng' : 'Thêm vào giỏ hàng'}
                        </button>
                        <button class="btn" style="background: var(--bg-secondary); color: var(--text-primary); border: 1px solid var(--border-color);" 
                                onclick="window.location.hash='#san-pham'">
                            ← Quay lại
                        </button>
                    </div>
                </div>
            </div>
        `;

        return container;
    }

    static createLoadingSkeleton(count = 4) {
        const container = document.createElement('div');
        container.className = 'loading-skeleton';

        for (let i = 0; i < count; i++) {
            const skeleton = document.createElement('div');
            skeleton.className = 'skeleton-card';
            container.appendChild(skeleton);
        }

        return container;
    }

    static createEmptyState(title, message, actionText = null, actionCallback = null) {
        const container = document.createElement('div');
        container.className = 'empty-state';

        container.innerHTML = `
            <h3>${title}</h3>
            <p>${message}</p>
            ${actionText && actionCallback ? 
                `<button class="btn btn-gradient" onclick="${actionCallback}">${actionText}</button>` :
                ''
            }
        `;

        return container;
    }

    static createCartSummary(items, promotion = null) {
        const subtotal = items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        let discount = 0;
        let total = subtotal;

        if (promotion) {
            if (promotion.type === 'percent') {
                discount = subtotal * (promotion.value / 100);
            } else {
                discount = promotion.value;
            }
            total = Math.max(0, subtotal - discount);
        }

        const container = document.createElement('div');
        container.className = 'cart-summary card';
        container.style.marginTop = '2rem';

        container.innerHTML = `
            <h3>Tóm tắt đơn hàng</h3>
            <div class="summary-row" style="display: flex; justify-content: space-between; margin: 0.5rem 0;">
                <span>Tạm tính:</span>
                <span>${this.formatPrice(subtotal)}</span>
            </div>
            ${discount > 0 ? `
                <div class="summary-row" style="display: flex; justify-content: space-between; margin: 0.5rem 0; color: #e53e3e;">
                    <span>Giảm giá:</span>
                    <span>-${this.formatPrice(discount)}</span>
                </div>
            ` : ''}
            <hr style="margin: 1rem 0; border: none; border-top: 1px solid var(--border-color);">
            <div class="summary-row" style="display: flex; justify-content: space-between; margin: 1rem 0; font-size: 1.25rem; font-weight: 700;">
                <span>Tổng cộng:</span>
                <span style="color: var(--grad-start);">${this.formatPrice(total)}</span>
            </div>
            
            <div class="promo-code-section" style="margin: 1.5rem 0;">
                <div style="display: flex; gap: 0.5rem;">
                    <input type="text" id="promo-code" placeholder="Nhập mã khuyến mãi" 
                           style="flex: 1; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 0.5rem;">
                    <button class="btn" onclick="app.applyPromoCode()" 
                            style="background: var(--bg-secondary); color: var(--text-primary); border: 1px solid var(--border-color);">
                        Áp dụng
                    </button>
                </div>
                ${promotion ? `
                    <div style="margin-top: 0.5rem; color: #48bb78; font-size: 0.875rem;">
                        ✓ Đã áp dụng mã "${promotion.code}"
                    </div>
                ` : ''}
            </div>
            
            <button class="btn btn-gradient" style="width: 100%; margin-top: 1rem; padding: 1rem;" onclick="app.proceedToCheckout()">
                Tiến hành thanh toán
            </button>
        `;

        return container;
    }

    static createBreadcrumb(items) {
        const nav = document.createElement('nav');
        nav.className = 'breadcrumb';
        nav.style.cssText = 'margin-bottom: 1rem; font-size: 0.875rem; color: var(--text-secondary);';

        const breadcrumbItems = items.map((item, index) => {
            if (index === items.length - 1) {
                return `<span style="color: var(--text-primary); font-weight: 600;">${item.text}</span>`;
            } else {
                return `<a href="${item.href || '#'}" style="color: var(--text-secondary); text-decoration: none;">${item.text}</a>`;
            }
        }).join(' <span style="margin: 0 0.5rem;">/</span> ');

        nav.innerHTML = breadcrumbItems;
        return nav;
    }

    static createFilterSection(categories) {
        const section = document.createElement('div');
        section.className = 'products-filters';

        section.innerHTML = `
            <div class="filter-row">
                <select id="category-filter" style="min-width: 150px;">
                    <option value="">Tất cả danh mục</option>
                    ${categories.map(cat => `<option value="${cat.id}">${cat.name}</option>`).join('')}
                </select>
                
                <select id="sort-filter" style="min-width: 120px;">
                    <option value="new">Mới nhất</option>
                    <option value="price_asc">Giá tăng dần</option>
                    <option value="price_desc">Giá giảm dần</option>
                    <option value="name_asc">Tên A-Z</option>
                    <option value="name_desc">Tên Z-A</option>
                </select>
                
                <div class="price-range" style="display: flex; gap: 0.5rem; align-items: center;">
                    <input type="number" id="min-price" placeholder="Giá từ" style="width: 100px;">
                    <span>-</span>
                    <input type="number" id="max-price" placeholder="Giá đến" style="width: 100px;">
                </div>
                
                <button class="btn" onclick="app.clearFilters()" 
                        style="background: var(--bg-secondary); color: var(--text-primary); border: 1px solid var(--border-color);">
                    Xóa bộ lọc
                </button>
            </div>
        `;

        return section;
    }

    static createPagination(currentPage, totalPages, onPageChange) {
        if (totalPages <= 1) return null;

        const nav = document.createElement('nav');
        nav.className = 'pagination';
        nav.style.cssText = 'display: flex; justify-content: center; align-items: center; gap: 0.5rem; margin: 2rem 0;';

        const buttons = [];

        // Previous button
        buttons.push(`
            <button class="btn" onclick="${onPageChange}(${currentPage - 1})" 
                    ${currentPage <= 1 ? 'disabled' : ''}
                    style="background: var(--bg-secondary); color: var(--text-primary); border: 1px solid var(--border-color);">
                ‹ Trước
            </button>
        `);

        // Page numbers
        for (let i = Math.max(1, currentPage - 2); i <= Math.min(totalPages, currentPage + 2); i++) {
            buttons.push(`
                <button class="btn" onclick="${onPageChange}(${i})" 
                        style="background: ${i === currentPage ? 'var(--primary-gradient)' : 'var(--bg-secondary)'}; 
                               color: ${i === currentPage ? 'white' : 'var(--text-primary)'}; 
                               border: 1px solid var(--border-color);">
                    ${i}
                </button>
            `);
        }

        // Next button
        buttons.push(`
            <button class="btn" onclick="${onPageChange}(${currentPage + 1})" 
                    ${currentPage >= totalPages ? 'disabled' : ''}
                    style="background: var(--bg-secondary); color: var(--text-primary); border: 1px solid var(--border-color);">
                Sau ›
            </button>
        `);

        nav.innerHTML = buttons.join('');
        return nav;
    }
}