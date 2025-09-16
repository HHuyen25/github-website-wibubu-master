<?php session_start(); ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ Hàng - Wibubu</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="assets/images/wibubu-logo.png">
</head>
<body>
    <div class="container">

        <header class="header">
            <nav class="navbar">
                <div class="nav-brand">
                    <a href="index.php" class="brand-logo">
                        <img src="assets/images/wibubu-logo.png" alt="Wibubu Logo">
                        <span class="logo-text">Wibubu</span>
                    </a>
                </div>
                <ul class="nav-menu">
                    <li><a href="index.php">Trang Chủ</a></li>
                    <li><a href="products.php">Sản Phẩm</a></li>
                    <li><a href="about.php">Giới Thiệu</a></li>
                    <li><a href="contact.php">Liên Hệ</a></li>
                </ul>
                <div class="nav-actions">
                    <button id="theme-toggle" class="theme-btn">🌙</button>
                    <a href="cart.php" class="cart-btn active">🛒 <span id="cart-count">0</span></a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="profile.php">Xin chào, <?php echo htmlspecialchars($_SESSION['username']); ?></a>
                        <a href="logout.php">Đăng xuất</a>
                    <?php else: ?>
                        <a href="login.php">Đăng nhập</a>
                        <a href="register.php">Đăng ký</a>
                    <?php endif; ?>
                </div>
            </nav>
        </header>

        <main class="main-content">
            <!-- Enhanced Cart Hero Section -->
            <section style="text-align: center; padding: var(--space-4xl) 0; animation: slide-in-up 0.6s ease-out;">
                <h1 style="font-size: var(--text-4xl); font-weight: 700; color: var(--color-neutral-900); margin-bottom: var(--space-lg); display: flex; align-items: center; justify-content: center; gap: var(--space-lg);">
                    <span>🛒</span>
                    <span>Giỏ Hàng Của Bạn</span>
                </h1>
                <p style="font-size: var(--text-lg); color: var(--color-neutral-600); max-width: 600px; margin: 0 auto;">
                    Xem lại các sản phẩm bạn đã chọn và tiến hành thanh toán để hoàn tất đơn hàng
                </p>
            </section>

            <section class="cart-section">
                <div id="cart-container">
                    <!-- Enhanced Empty Cart State -->
                    <div id="empty-cart" style="text-align: center; padding: var(--space-6xl); background: var(--gradient-card); border-radius: var(--radius-2xl); border: 1px solid rgba(255,255,255,0.3); backdrop-filter: blur(15px); box-shadow: var(--shadow-xl); animation: scale-in 0.8s ease-out;">
                        <div style="font-size: var(--text-6xl); margin-bottom: var(--space-2xl); opacity: 0.8;">🛒</div>
                        <h2 style="font-size: var(--text-2xl); font-weight: 700; color: var(--color-neutral-800); margin-bottom: var(--space-lg);">Giỏ Hàng Đang Trống</h2>
                        <p style="font-size: var(--text-base); color: var(--color-neutral-600); margin-bottom: var(--space-4xl); max-width: 400px; margin-left: auto; margin-right: auto; margin-bottom: var(--space-4xl);">
                            Hãy khám phá những sản phẩm tuyệt vời của chúng tôi và thêm vào giỏ hàng để bắt đầu mua sắm!
                        </p>
                        
                        <!-- Trust Indicators for Empty Cart -->
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: var(--space-lg); margin-bottom: var(--space-4xl); max-width: 500px; margin-left: auto; margin-right: auto;">
                            <div style="background: var(--gradient-glass); padding: var(--space-lg); border-radius: var(--radius-lg); border: 1px solid rgba(255,255,255,0.3); backdrop-filter: blur(10px); text-align: center;">
                                <div style="font-size: var(--text-lg); margin-bottom: var(--space-sm);">🚚</div>
                                <div style="font-size: var(--text-xs); color: var(--color-neutral-600);">Miễn phí ship</div>
                            </div>
                            <div style="background: var(--gradient-glass); padding: var(--space-lg); border-radius: var(--radius-lg); border: 1px solid rgba(255,255,255,0.3); backdrop-filter: blur(10px); text-align: center;">
                                <div style="font-size: var(--text-lg); margin-bottom: var(--space-sm);">🔄</div>
                                <div style="font-size: var(--text-xs); color: var(--color-neutral-600);">Đổi trả dễ dàng</div>
                            </div>
                            <div style="background: var(--gradient-glass); padding: var(--space-lg); border-radius: var(--radius-lg); border: 1px solid rgba(255,255,255,0.3); backdrop-filter: blur(10px); text-align: center;">
                                <div style="font-size: var(--text-lg); margin-bottom: var(--space-sm);">🛡️</div>
                                <div style="font-size: var(--text-xs); color: var(--color-neutral-600);">Bảo hành chất lượng</div>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: var(--space-lg); justify-content: center; flex-wrap: wrap;">
                            <a href="products.php" class="cta-premium" style="padding: var(--space-lg) var(--space-3xl); font-size: var(--text-base);">
                                <span>🛍️ Khám Phá Sản Phẩm</span>
                            </a>
                            <a href="index.php" style="padding: var(--space-lg) var(--space-3xl); background: var(--gradient-glass); color: var(--color-neutral-700); text-decoration: none; border-radius: var(--radius-xl); font-weight: 600; border: 1px solid rgba(255,255,255,0.3); backdrop-filter: blur(10px); transition: all var(--transition-fast); display: inline-flex; align-items: center; gap: var(--space-md);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='var(--shadow-lg)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                <span>🏠 Về Trang Chủ</span>
                            </a>
                        </div>
                    </div>

                    <div id="cart-items" class="cart-items" style="display: none;">
                        <div class="cart-header">
                            <span>Sản phẩm</span>
                            <span>Giá</span>
                            <span>Số lượng</span>
                            <span>Tổng</span>
                            <span>Thao tác</span>
                        </div>
                        <div id="cart-list">
                            <!-- Cart items will be loaded here by JavaScript -->
                        </div>
                    </div>

                    <!-- Enhanced Cart Summary -->
                    <div id="cart-summary" style="display: none; animation: slide-in-up 0.8s ease-out;">
                        <div style="background: var(--gradient-card); padding: var(--space-2xl); border-radius: var(--radius-xl); border: 1px solid rgba(255,255,255,0.3); backdrop-filter: blur(15px); box-shadow: var(--shadow-xl); margin-bottom: var(--space-2xl);">
                            <!-- Shipping Benefits -->
                            <div id="shipping-message" style="padding: var(--space-lg); background: var(--gradient-glass); border-radius: var(--radius-lg); border: 1px solid rgba(34, 197, 94, 0.3); backdrop-filter: blur(10px); margin-bottom: var(--space-2xl);">
                                <div style="display: flex; align-items: center; gap: var(--space-lg);">
                                    <div style="background: var(--color-success-100); padding: var(--space-sm); border-radius: var(--radius-full);">
                                        <span style="font-size: var(--text-lg);">🚚</span>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600; color: var(--color-success-700); margin-bottom: var(--space-xs);">Miễn Phí Giao Hàng!</div>
                                        <div style="font-size: var(--text-sm); color: var(--color-neutral-600);">Đơn hàng từ 500k được giao hàng miễn phí</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Enhanced Discount Section -->
                            <div style="margin-bottom: var(--space-2xl);">
                                <h3 style="font-size: var(--text-lg); font-weight: 600; color: var(--color-neutral-800); margin-bottom: var(--space-lg); display: flex; align-items: center; gap: var(--space-sm);">
                                    <span>🎟️</span>
                                    <span>Mã Giảm Giá</span>
                                </h3>
                                <div style="display: flex; gap: var(--space-sm); margin-bottom: var(--space-lg);">
                                    <input type="text" id="discount-code" placeholder="Nhập mã giảm giá..." maxlength="20" style="flex: 1; padding: var(--space-lg); border: 1px solid rgba(255,255,255,0.3); border-radius: var(--radius-lg); background: var(--gradient-glass); backdrop-filter: blur(10px); font-size: var(--text-sm);">
                                    <button onclick="applyDiscountCode()" style="padding: var(--space-lg) var(--space-xl); background: var(--gradient-primary); color: var(--color-neutral-0); border: none; border-radius: var(--radius-lg); font-weight: 600; cursor: pointer; transition: all var(--transition-fast);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='var(--shadow-lg)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                        Áp dụng
                                    </button>
                                </div>
                                <div id="discount-message" style="font-size: var(--text-sm); padding: var(--space-sm);"></div>
                            </div>
                            
                            <!-- Enhanced Summary Details -->
                            <div style="margin-bottom: var(--space-2xl);">
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: var(--space-lg) 0; border-bottom: 1px solid rgba(255,255,255,0.2);">
                                    <span style="color: var(--color-neutral-600);">Tạm tính:</span>
                                    <span id="subtotal" style="font-weight: 600; color: var(--color-neutral-800);">0₫</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: var(--space-lg) 0; border-bottom: 1px solid rgba(255,255,255,0.2);">
                                    <span style="color: var(--color-neutral-600);">Phí vận chuyển:</span>
                                    <span id="shipping-amount" style="font-weight: 600; color: var(--color-success-600);">Miễn phí</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: var(--space-lg) 0; border-bottom: 1px solid rgba(255,255,255,0.2);">
                                    <span style="color: var(--color-neutral-600);">Thuế VAT (10%):</span>
                                    <span id="tax-amount" style="font-weight: 600; color: var(--color-neutral-800);">0₫</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: var(--space-lg) 0; border-bottom: 1px solid rgba(255,255,255,0.2);" id="discount-row">
                                    <span style="color: var(--color-neutral-600);">Giảm giá:</span>
                                    <span id="discount-amount" style="font-weight: 600; color: var(--color-error-600);">0₫</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: var(--space-xl) 0; background: var(--gradient-glass); margin: var(--space-lg) -var(--space-lg) 0 -var(--space-lg); padding-left: var(--space-lg); padding-right: var(--space-lg); border-radius: var(--radius-lg);">
                                    <span style="font-size: var(--text-lg); font-weight: 600; color: var(--color-neutral-800);">Tổng cộng:</span>
                                    <span id="total" style="font-size: var(--text-2xl); font-weight: 700; color: var(--color-neutral-900);">0₫</span>
                                </div>
                            </div>
                            
                            <!-- Enhanced Cart Actions -->
                            <div style="display: flex; gap: var(--space-lg); margin-bottom: var(--space-xl);">
                                <button onclick="clearCart()" style="flex: 1; padding: var(--space-lg); background: var(--gradient-glass); color: var(--color-neutral-700); border: 1px solid rgba(255,255,255,0.3); border-radius: var(--radius-lg); font-weight: 600; cursor: pointer; transition: all var(--transition-fast); backdrop-filter: blur(10px);" onmouseover="this.style.background='var(--color-error-500)'; this.style.color='var(--color-neutral-0)'" onmouseout="this.style.background='var(--gradient-glass)'; this.style.color='var(--color-neutral-700)'">
                                    🗑️ Xóa Tất Cả
                                </button>
                                <a href="checkout.php" class="cta-premium" style="flex: 2; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: var(--space-sm); padding: var(--space-lg);">
                                    <span>💳</span>
                                    <span>Thanh Toán</span>
                                </a>
                            </div>
                            
                            <!-- Security Badges -->
                            <div style="display: flex; align-items: center; justify-content: center; gap: var(--space-lg); flex-wrap: wrap; padding: var(--space-lg); background: var(--gradient-glass); border-radius: var(--radius-lg); border: 1px solid rgba(255,255,255,0.3); backdrop-filter: blur(10px);">
                                <div style="display: flex; align-items: center; gap: var(--space-sm); font-size: var(--text-sm); color: var(--color-neutral-700);">
                                    <span>🔒</span>
                                    <span>Thanh toán an toàn</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: var(--space-sm); font-size: var(--text-sm); color: var(--color-neutral-700);">
                                    <span>🛡️</span>
                                    <span>Bảo mật SSL</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: var(--space-sm); font-size: var(--text-sm); color: var(--color-neutral-700);">
                                    <span>📞</span>
                                    <span>Hỗ trợ 24/7</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Continue Shopping Section -->
                <div style="text-align: center; margin-top: var(--space-4xl); animation: slide-in-up 1.0s ease-out;">
                    <div style="background: var(--gradient-card); padding: var(--space-2xl); border-radius: var(--radius-xl); border: 1px solid rgba(255,255,255,0.3); backdrop-filter: blur(15px); box-shadow: var(--shadow-lg); display: inline-block;">
                        <div style="display: flex; align-items: center; gap: var(--space-lg); flex-wrap: wrap; justify-content: center;">
                            <a href="products.php" style="padding: var(--space-lg) var(--space-2xl); background: var(--gradient-glass); color: var(--color-neutral-700); text-decoration: none; border-radius: var(--radius-xl); font-weight: 600; border: 1px solid rgba(255,255,255,0.3); backdrop-filter: blur(10px); transition: all var(--transition-fast); display: flex; align-items: center; gap: var(--space-sm);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='var(--shadow-lg)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                <span>←</span>
                                <span>Tiếp Tục Mua Sắm</span>
                            </a>
                            <a href="index.php" style="padding: var(--space-lg) var(--space-2xl); background: var(--gradient-glass); color: var(--color-neutral-700); text-decoration: none; border-radius: var(--radius-xl); font-weight: 600; border: 1px solid rgba(255,255,255,0.3); backdrop-filter: blur(10px); transition: all var(--transition-fast); display: flex; align-items: center; gap: var(--space-sm);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='var(--shadow-lg)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                <span>🏠</span>
                                <span>Trang Chủ</span>
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <footer class="footer">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Wibubu</h3>
                    <p>Thương mại điện tử hiện đại với thiết kế Shy Rainbow</p>
                </div>
                <div class="footer-section">
                    <h3>Liên Kết</h3>
                    <ul>
                        <li><a href="about.php">Giới Thiệu</a></li>
                        <li><a href="contact.php">Liên Hệ</a></li>
                        <li><a href="terms.php">Điều Khoản</a></li>
                    </ul>
                </div>
            </div>
        </footer>
    </div>

    <script src="assets/js/main.js"></script>
    <script src="assets/js/cart.js"></script>
</body>
</html>