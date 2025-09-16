// Cart functionality
document.addEventListener('DOMContentLoaded', function() {
    loadCart();
});

function loadCart() {
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    const cartContainer = document.getElementById('cart-container');
    const emptyCart = document.getElementById('empty-cart');
    const cartItems = document.getElementById('cart-items');
    const cartSummary = document.getElementById('cart-summary');
    
    if (cart.length === 0) {
        emptyCart.style.display = 'block';
        cartItems.style.display = 'none';
        cartSummary.style.display = 'none';
    } else {
        emptyCart.style.display = 'none';
        cartItems.style.display = 'block';
        cartSummary.style.display = 'block';
        
        displayCartItems(cart);
        updateCartSummary(cart);
    }
}

function displayCartItems(cart) {
    const cartList = document.getElementById('cart-list');
    cartList.innerHTML = '';
    
    cart.forEach((item, index) => {
        const cartItem = document.createElement('div');
        cartItem.className = 'cart-item';
        cartItem.innerHTML = `
            <div class="item-info">
                <img src="${item.image || 'assets/images/no-image.jpg'}" alt="${item.name}">
                <div class="item-details">
                    <h3>${item.name}</h3>
                    <p>Mã SP: ${item.id}</p>
                </div>
            </div>
            <div class="item-price">${formatPrice(item.price)}đ</div>
            <div class="item-quantity">
                <button onclick="updateQuantity(${index}, -1)">-</button>
                <span>${item.quantity}</span>
                <button onclick="updateQuantity(${index}, 1)">+</button>
            </div>
            <div class="item-total">${formatPrice(item.price * item.quantity)}đ</div>
            <div class="item-actions">
                <button onclick="removeFromCart(${index})" class="remove-btn">🗑️</button>
            </div>
        `;
        cartList.appendChild(cartItem);
    });
}

function updateQuantity(index, change) {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    cart[index].quantity += change;
    
    if (cart[index].quantity <= 0) {
        cart.splice(index, 1);
    }
    
    localStorage.setItem('cart', JSON.stringify(cart));
    loadCart();
    updateCartCount();
}

function removeFromCart(index) {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    cart.splice(index, 1);
    localStorage.setItem('cart', JSON.stringify(cart));
    loadCart();
    updateCartCount();
    showNotification('Đã xóa sản phẩm khỏi giỏ hàng!');
}

function clearCart() {
    if (confirm('Bạn có chắc muốn xóa tất cả sản phẩm?')) {
        localStorage.removeItem('cart');
        loadCart();
        updateCartCount();
        showNotification('Đã xóa tất cả sản phẩm!');
    }
}

function updateCartSummary(cart) {
    const subtotal = cart.reduce((total, item) => total + (item.price * item.quantity), 0);
    
    // Shipping calculation
    const freeShippingThreshold = 500000; // 500k VND
    let shipping = 0;
    if (subtotal > 0 && subtotal < freeShippingThreshold) {
        shipping = 30000; // 30k VND shipping fee
    }
    
    // Tax calculation (VAT 10%)
    const taxRate = 0.1;
    const tax = Math.round(subtotal * taxRate);
    
    // Discount calculation
    const discount = getAppliedDiscount(subtotal);
    
    // Final total
    const total = subtotal + shipping + tax - discount;
    
    // Update display
    document.getElementById('subtotal').textContent = formatPrice(subtotal) + ' đ';
    document.getElementById('shipping-amount').textContent = shipping === 0 ? 'Miễn phí' : formatPrice(shipping) + ' đ';
    document.getElementById('tax-amount').textContent = formatPrice(tax) + ' đ';
    document.getElementById('discount-amount').textContent = discount > 0 ? '-' + formatPrice(discount) + ' đ' : '0 đ';
    document.getElementById('total').textContent = formatPrice(total) + ' đ';
    
    // Update shipping status message
    const shippingMessage = document.getElementById('shipping-message');
    if (subtotal > 0 && subtotal < freeShippingThreshold) {
        const remaining = freeShippingThreshold - subtotal;
        shippingMessage.textContent = `Mua thêm ${formatPrice(remaining)} đ để được miễn phí vận chuyển!`;
        shippingMessage.className = 'shipping-message warning';
    } else if (subtotal >= freeShippingThreshold) {
        shippingMessage.textContent = '🎉 Bạn được miễn phí vận chuyển!';
        shippingMessage.className = 'shipping-message success';
    } else {
        shippingMessage.textContent = '';
        shippingMessage.className = 'shipping-message';
    }
}

function getAppliedDiscount(subtotal) {
    // Check for applied discount codes
    const discountCode = localStorage.getItem('appliedDiscount');
    if (!discountCode) return 0;
    
    const discounts = {
        'WELCOME10': { type: 'percentage', value: 0.1, minOrder: 100000 }, // 10% off, min 100k
        'SAVE50K': { type: 'fixed', value: 50000, minOrder: 300000 }, // 50k off, min 300k
        'NEWUSER': { type: 'percentage', value: 0.15, minOrder: 200000 }, // 15% off, min 200k
    };
    
    const discount = discounts[discountCode];
    if (!discount || subtotal < discount.minOrder) return 0;
    
    if (discount.type === 'percentage') {
        return Math.round(subtotal * discount.value);
    } else {
        return discount.value;
    }
}

function applyDiscountCode() {
    const code = document.getElementById('discount-code').value.trim().toUpperCase();
    const messageEl = document.getElementById('discount-message');
    
    if (!code) {
        messageEl.textContent = 'Vui lòng nhập mã giảm giá!';
        messageEl.className = 'discount-message error';
        return;
    }
    
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    const subtotal = cart.reduce((total, item) => total + (item.price * item.quantity), 0);
    
    const discounts = {
        'WELCOME10': { type: 'percentage', value: 0.1, minOrder: 100000 },
        'SAVE50K': { type: 'fixed', value: 50000, minOrder: 300000 },
        'NEWUSER': { type: 'percentage', value: 0.15, minOrder: 200000 },
    };
    
    const discount = discounts[code];
    if (!discount) {
        messageEl.textContent = 'Mã giảm giá không hợp lệ!';
        messageEl.className = 'discount-message error';
        return;
    }
    
    if (subtotal < discount.minOrder) {
        messageEl.textContent = `Đơn hàng tối thiểu ${formatPrice(discount.minOrder)} đ để áp dụng mã này!`;
        messageEl.className = 'discount-message error';
        return;
    }
    
    localStorage.setItem('appliedDiscount', code);
    messageEl.textContent = `Đã áp dụng mã giảm giá ${code}!`;
    messageEl.className = 'discount-message success';
    
    updateCartSummary(cart);
}

function removeDiscountCode() {
    localStorage.removeItem('appliedDiscount');
    document.getElementById('discount-code').value = '';
    document.getElementById('discount-message').textContent = '';
    
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    updateCartSummary(cart);
    showNotification('Đã xóa mã giảm giá!');
}

function formatPrice(price) {
    return new Intl.NumberFormat('vi-VN').format(price);
}