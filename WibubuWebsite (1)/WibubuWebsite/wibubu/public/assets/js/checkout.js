// Checkout functionality
document.addEventListener('DOMContentLoaded', function() {
    loadCheckoutData();
    setupFormValidation();
});

function loadCheckoutData() {
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    
    if (cart.length === 0) {
        alert('Giỏ hàng trống! Vui lòng thêm sản phẩm trước khi thanh toán.');
        window.location.href = 'cart.php';
        return;
    }
    
    displayCheckoutItems(cart);
    displayCheckoutSummary(cart);
    
    // Set hidden form data (server will validate and recalculate totals)
    document.getElementById('cart-data').value = JSON.stringify(cart);
}

function displayCheckoutItems(cart) {
    const container = document.getElementById('checkout-cart-items');
    container.innerHTML = '';
    
    cart.forEach(item => {
        const itemEl = document.createElement('div');
        itemEl.className = 'checkout-item';
        itemEl.innerHTML = `
            <div class="checkout-item-info">
                <img src="${item.image || 'https://via.placeholder.com/60x60?text=' + encodeURIComponent(item.name)}" alt="${item.name}">
                <div class="item-details">
                    <h4>${item.name}</h4>
                    <p>Số lượng: ${item.quantity}</p>
                </div>
            </div>
            <div class="checkout-item-price">
                ${formatPrice(item.price * item.quantity)} đ
            </div>
        `;
        container.appendChild(itemEl);
    });
}

function displayCheckoutSummary(cart) {
    const subtotal = cart.reduce((total, item) => total + (item.price * item.quantity), 0);
    const freeShippingThreshold = 500000;
    const shipping = (subtotal > 0 && subtotal < freeShippingThreshold) ? 30000 : 0;
    const tax = Math.round(subtotal * 0.1);
    const discount = getAppliedDiscount(subtotal);
    const total = subtotal + shipping + tax - discount;
    
    const container = document.getElementById('checkout-summary');
    container.innerHTML = `
        <div class="checkout-summary-row">
            <span>Tạm tính:</span>
            <span>${formatPrice(subtotal)} đ</span>
        </div>
        <div class="checkout-summary-row">
            <span>Phí vận chuyển:</span>
            <span>${shipping === 0 ? 'Miễn phí' : formatPrice(shipping) + ' đ'}</span>
        </div>
        <div class="checkout-summary-row">
            <span>Thuế VAT (10%):</span>
            <span>${formatPrice(tax)} đ</span>
        </div>
        ${discount > 0 ? `
        <div class="checkout-summary-row discount">
            <span>Giảm giá:</span>
            <span>-${formatPrice(discount)} đ</span>
        </div>
        ` : ''}
        <div class="checkout-summary-row total">
            <span>Tổng cộng:</span>
            <span>${formatPrice(total)} đ</span>
        </div>
    `;
}

function getAppliedDiscount(subtotal) {
    const discountCode = localStorage.getItem('appliedDiscount');
    if (!discountCode) return 0;
    
    const discounts = {
        'WELCOME10': { type: 'percentage', value: 0.1, minOrder: 100000 },
        'SAVE50K': { type: 'fixed', value: 50000, minOrder: 300000 },
        'NEWUSER': { type: 'percentage', value: 0.15, minOrder: 200000 },
    };
    
    const discount = discounts[discountCode];
    if (!discount || subtotal < discount.minOrder) return 0;
    
    if (discount.type === 'percentage') {
        return Math.round(subtotal * discount.value);
    } else {
        return discount.value;
    }
}

function formatPrice(price) {
    return new Intl.NumberFormat('vi-VN').format(price);
}

function setupFormValidation() {
    const form = document.getElementById('checkout-form');
    const placeOrderBtn = document.getElementById('place-order-btn');
    
    form.addEventListener('submit', function(e) {
        const cart = JSON.parse(localStorage.getItem('cart')) || [];
        if (cart.length === 0) {
            e.preventDefault();
            alert('Giỏ hàng trống!');
            window.location.href = 'cart.php';
            return;
        }
        
        // Disable button to prevent double submission
        placeOrderBtn.disabled = true;
        placeOrderBtn.textContent = 'Đang xử lý...';
        
        // Re-enable button after 3 seconds in case of error
        setTimeout(() => {
            placeOrderBtn.disabled = false;
            placeOrderBtn.textContent = 'Đặt hàng';
        }, 3000);
    });
    
    // Phone number validation
    const phoneInput = document.getElementById('phone');
    phoneInput.addEventListener('input', function() {
        const phone = this.value.replace(/\D/g, '');
        if (phone.length > 0) {
            if (phone.length === 10 && phone.startsWith('0')) {
                this.setCustomValidity('');
            } else if (phone.length === 9 && !phone.startsWith('0')) {
                this.setCustomValidity('');
            } else {
                this.setCustomValidity('Số điện thoại không hợp lệ!');
            }
        }
    });
    
    // Payment method change handler
    const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
    paymentMethods.forEach(method => {
        method.addEventListener('change', function() {
            // Here you can add specific handling for different payment methods
            if (this.value === 'credit_card') {
                showNotification('Thanh toán thẻ sẽ được chuyển đến cổng thanh toán bảo mật!');
            } else if (this.value === 'momo') {
                showNotification('Bạn sẽ nhận được QR code MoMo sau khi đặt hàng!');
            } else if (this.value === 'bank_transfer') {
                showNotification('Thông tin chuyển khoản sẽ được gửi qua email sau khi đặt hàng!');
            }
        });
    });
}

// Add to main.js functions if not already there
function showNotification(message, type = 'info') {
    // Simple notification implementation
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        background: var(--color-primary-600);
        color: white;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-lg);
        z-index: 9999;
        max-width: 300px;
        word-wrap: break-word;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}