// Theme toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const themeToggle = document.getElementById('theme-toggle');
    const chatToggle = document.getElementById('chat-toggle');
    const chatBody = document.querySelector('.chat-body');
    const chatInput = document.querySelector('.chat-input');
    
    // Check for saved theme preference or default to light mode
    const currentTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', currentTheme);
    
    // Update theme toggle button
    themeToggle.textContent = currentTheme === 'dark' ? '☀️' : '🌙';
    
    // Theme toggle event
    themeToggle.addEventListener('click', function() {
        const theme = document.documentElement.getAttribute('data-theme');
        const newTheme = theme === 'dark' ? 'light' : 'dark';
        
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        themeToggle.textContent = newTheme === 'dark' ? '☀️' : '🌙';
    });
    
    // Chat toggle functionality
    if (chatToggle) {
        chatToggle.addEventListener('click', function() {
            const isVisible = chatBody.style.display === 'block';
            chatBody.style.display = isVisible ? 'none' : 'block';
            chatInput.style.display = isVisible ? 'none' : 'flex';
        });
    }
    
    // Send message functionality
    const sendButton = document.getElementById('send-message');
    const messageInput = document.getElementById('chat-message');
    
    if (sendButton && messageInput) {
        sendButton.addEventListener('click', sendMessage);
        messageInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
    }
    
    function sendMessage() {
        const message = messageInput.value.trim();
        if (message) {
            // Add message to chat
            const messageElement = document.createElement('div');
            messageElement.textContent = `Bạn: ${message}`;
            messageElement.style.marginBottom = '10px';
            chatBody.appendChild(messageElement);
            
            // Clear input
            messageInput.value = '';
            
            // Scroll to bottom
            chatBody.scrollTop = chatBody.scrollHeight;
            
            // Here you would typically send the message to the server
            // For now, we'll just add a simple response
            setTimeout(() => {
                const responseElement = document.createElement('div');
                responseElement.textContent = 'Nhân viên: Cảm ơn bạn đã liên hệ! Chúng tôi sẽ hỗ trợ bạn ngay.';
                responseElement.style.marginBottom = '10px';
                responseElement.style.color = '#666';
                chatBody.appendChild(responseElement);
                chatBody.scrollTop = chatBody.scrollHeight;
            }, 1000);
        }
    }
    
    // Enhanced Auth Functionality
    initAuthEnhancements();
    
    // Add fade-in animation to elements
    const elements = document.querySelectorAll('.hero-banner, .categories, .featured-products, .promotions');
    elements.forEach((el, index) => {
        setTimeout(() => {
            el.classList.add('fade-in');
        }, index * 200);
    });
    
    // Cart functionality placeholder
    updateCartCount();
    
    // Add event listeners for add to cart buttons
    initCartButtons();
});

function updateCartCount() {
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    const cartCount = document.getElementById('cart-count');
    if (cartCount) {
        cartCount.textContent = cart.length;
    }
}

function initCartButtons() {
    // Add event listeners for add to cart buttons in product listing
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('add-to-cart-mykingdom')) {
            const button = e.target;
            const productId = button.getAttribute('data-product-id');
            const productName = button.getAttribute('data-product-name');
            const productPrice = parseInt(button.getAttribute('data-product-price'));
            const productImage = button.getAttribute('data-product-image');
            
            // Add to cart with the data from button attributes
            addToCartWithData(productId, productName, productPrice, productImage, 1);
        }
    });
}

function addToCartWithData(productId, productName, productPrice, productImage, quantity = 1) {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    const existingItem = cart.find(item => item.id === productId);
    
    if (existingItem) {
        existingItem.quantity += quantity;
    } else {
        cart.push({ 
            id: productId, 
            quantity: quantity,
            name: productName,
            price: productPrice,
            image: productImage
        });
    }
    
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartCount();
    
    // Show notification
    showNotification('Đã thêm sản phẩm vào giỏ hàng!');
}

// Add to cart function (to be used by product pages)
function addToCart(productId, quantity = 1) {
    // Get product info from the page or fetch from server
    const productCard = event.target.closest('.product-card');
    let productName = 'Sản phẩm';
    let productPrice = 0;
    let productImage = 'assets/images/no-image.jpg';
    
    if (productCard) {
        productName = productCard.querySelector('h3').textContent;
        const priceText = productCard.querySelector('.price').textContent.replace(/[đ.,]/g, '');
        productPrice = parseInt(priceText);
        const imgElement = productCard.querySelector('img');
        if (imgElement) {
            productImage = imgElement.src;
        }
    }
    
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    const existingItem = cart.find(item => item.id === productId);
    
    if (existingItem) {
        existingItem.quantity += quantity;
    } else {
        cart.push({ 
            id: productId, 
            quantity: quantity,
            name: productName,
            price: productPrice,
            image: productImage
        });
    }
    
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartCount();
    
    // Show notification
    showNotification('Đã thêm sản phẩm vào giỏ hàng!');
}

function showNotification(message) {
    const notification = document.createElement('div');
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(45deg, #FFB3E6, #E6B3FF);
        color: white;
        padding: 1rem 2rem;
        border-radius: 10px;
        z-index: 10000;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Enhanced Auth Functionality
function initAuthEnhancements() {
    // Password toggle functionality
    initPasswordToggle();
    
    // Password strength checking
    initPasswordStrength();
    
    // Quick login functionality
    initQuickLogin();
    
    // Enhanced form validation
    initFormValidation();
    
    // Input status feedback
    initInputFeedback();
}

// Password Toggle Functionality
function initPasswordToggle() {
    const passwordToggles = document.querySelectorAll('.password-toggle');
    
    passwordToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const input = this.previousElementSibling;
            const icon = this.querySelector('.toggle-icon');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.textContent = '🙈';
            } else {
                input.type = 'password';
                icon.textContent = '👁️';
            }
        });
    });
}

// Password Strength Checking
function initPasswordStrength() {
    const passwordInput = document.getElementById('password');
    const strengthBar = document.getElementById('strength-bar');
    const strengthText = document.getElementById('strength-text');
    const requirements = {
        length: document.getElementById('req-length'),
        uppercase: document.getElementById('req-uppercase'),
        lowercase: document.getElementById('req-lowercase'),
        number: document.getElementById('req-number')
    };
    
    if (passwordInput && strengthBar) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            const strength = calculatePasswordStrength(password);
            updatePasswordStrength(strength, strengthBar, strengthText, requirements, password);
        });
    }
}

function calculatePasswordStrength(password) {
    let score = 0;
    const checks = {
        length: password.length >= 6,
        uppercase: /[A-Z]/.test(password),
        lowercase: /[a-z]/.test(password),
        number: /\d/.test(password)
    };
    
    Object.values(checks).forEach(check => {
        if (check) score++;
    });
    
    return { score, checks };
}

function updatePasswordStrength(strength, strengthBar, strengthText, requirements, password) {
    // Clear previous classes
    strengthBar.className = 'strength-bar';
    strengthText.className = 'strength-text';
    
    // Update strength indicator
    if (password.length === 0) {
        strengthText.textContent = 'Mật khẩu chưa được nhập';
    } else if (strength.score <= 1) {
        strengthBar.classList.add('weak');
        strengthText.classList.add('weak');
        strengthText.textContent = 'Mật khẩu yếu';
    } else if (strength.score === 2) {
        strengthBar.classList.add('fair');
        strengthText.classList.add('fair');
        strengthText.textContent = 'Mật khẩu trung bình';
    } else if (strength.score === 3) {
        strengthBar.classList.add('good');
        strengthText.classList.add('good');
        strengthText.textContent = 'Mật khẩu tốt';
    } else {
        strengthBar.classList.add('strong');
        strengthText.classList.add('strong');
        strengthText.textContent = 'Mật khẩu mạnh';
    }
    
    // Update requirements
    if (requirements.length) requirements.length.classList.toggle('met', strength.checks.length);
    if (requirements.uppercase) requirements.uppercase.classList.toggle('met', strength.checks.uppercase);
    if (requirements.lowercase) requirements.lowercase.classList.toggle('met', strength.checks.lowercase);
    if (requirements.number) requirements.number.classList.toggle('met', strength.checks.number);
}

// Quick Login Functionality
function initQuickLogin() {
    window.quickLogin = function(email, password) {
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const loginForm = document.getElementById('login-form');
        
        if (emailInput && passwordInput && loginForm) {
            emailInput.value = email;
            passwordInput.value = password;
            
            // Add visual feedback
            emailInput.style.background = 'linear-gradient(135deg, rgba(34, 197, 94, 0.1), rgba(34, 197, 94, 0.05))';
            passwordInput.style.background = 'linear-gradient(135deg, rgba(34, 197, 94, 0.1), rgba(34, 197, 94, 0.05))';
            
            // Reset styles after a moment
            setTimeout(() => {
                emailInput.style.background = '';
                passwordInput.style.background = '';
            }, 2000);
            
            // Focus on login button
            const loginBtn = document.getElementById('login-btn');
            if (loginBtn) {
                loginBtn.focus();
                loginBtn.style.animation = 'pulse 1s ease-in-out';
                setTimeout(() => {
                    loginBtn.style.animation = '';
                }, 1000);
            }
        }
    };
}

// Enhanced Form Validation
function initFormValidation() {
    const forms = document.querySelectorAll('.enhanced-auth-form');
    
    forms.forEach(form => {
        // Real-time validation
        const inputs = form.querySelectorAll('.enhanced-input');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateInput(this);
            });
            
            input.addEventListener('input', function() {
                if (this.classList.contains('error')) {
                    validateInput(this);
                }
            });
        });
        
        // Form submission
        form.addEventListener('submit', function(e) {
            let isValid = true;
            inputs.forEach(input => {
                if (!validateInput(input)) {
                    isValid = false;
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showFormError('Vui lòng kiểm tra và sửa các lỗi trên form');
            }
        });
    });
}

function validateInput(input) {
    const status = input.parentNode.querySelector('.input-status');
    const type = input.type;
    const value = input.value.trim();
    
    // Remove previous state
    input.classList.remove('error', 'success');
    if (status) status.textContent = '';
    
    // Validation rules
    let isValid = true;
    let message = '';
    
    if (input.required && !value) {
        isValid = false;
        message = 'Trường này là bắt buộc';
    } else if (value) {
        switch (type) {
            case 'email':
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value)) {
                    isValid = false;
                    message = 'Email không hợp lệ';
                } else {
                    message = '✓ Email hợp lệ';
                }
                break;
                
            case 'password':
                if (input.id === 'confirm_password') {
                    const password = document.getElementById('password');
                    if (password && value !== password.value) {
                        isValid = false;
                        message = 'Mật khẩu không khớp';
                    } else if (value === password.value) {
                        message = '✓ Mật khẩu khớp';
                    }
                } else if (value.length < 6) {
                    isValid = false;
                    message = 'Mật khẩu phải có ít nhất 6 ký tự';
                }
                break;
                
            case 'text':
                if (input.id === 'name' && value.length < 2) {
                    isValid = false;
                    message = 'Tên phải có ít nhất 2 ký tự';
                } else if (input.id === 'name') {
                    message = '✓ Tên hợp lệ';
                }
                break;
        }
    }
    
    // Apply validation state
    if (isValid) {
        input.classList.add('success');
    } else {
        input.classList.add('error');
    }
    
    if (status && message) {
        status.textContent = message;
        status.style.color = isValid ? 'var(--color-success-600)' : 'var(--color-error-600)';
    }
    
    return isValid;
}

// Input Status Feedback
function initInputFeedback() {
    const inputs = document.querySelectorAll('.enhanced-input');
    
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentNode.style.transform = 'scale(1.02)';
        });
        
        input.addEventListener('blur', function() {
            this.parentNode.style.transform = 'scale(1)';
        });
    });
}

function showFormError(message) {
    // Create or update error message
    let errorDiv = document.querySelector('.form-error-message');
    if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.className = 'enhanced-error-message form-error-message';
        errorDiv.innerHTML = `
            <div class="error-icon">⚠️</div>
            <div class="error-content">
                <h4>Lỗi form</h4>
                <p>${message}</p>
            </div>
        `;
        
        const form = document.querySelector('.enhanced-auth-form');
        if (form) {
            form.insertBefore(errorDiv, form.firstChild);
        }
    } else {
        errorDiv.querySelector('.error-content p').textContent = message;
    }
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        if (errorDiv && errorDiv.parentNode) {
            errorDiv.remove();
        }
    }, 5000);
}

// CSS for notification animation
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
`;
document.head.appendChild(style);