<?php session_start(); ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giới Thiệu - Wibubu</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="assets/images/wibubu-logo.png">
</head>
<body>
    <div class="container">
        <!-- Top Banner -->
        <div class="top-banner">
            <div class="policy-links">
                <a href="#shipping">🚚 Miễn phí giao hàng đơn từ 500k</a>
                <a href="#express">⚡ Giao hàng hỏa tốc 4 tiếng</a>
                <a href="#membership">👑 Chương trình thành viên</a>
                <a href="#installment">💳 Mua hàng trả góp</a>
                <a href="#stores">🏪 Hệ thống 200 cửa hàng</a>
            </div>
        </div>

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
                    <li><a href="about.php" class="active">Giới Thiệu</a></li>
                    <li><a href="contact.php">Liên Hệ</a></li>
                </ul>
                <div class="nav-actions">
                    <button id="theme-toggle" class="theme-btn">🌙</button>
                    <a href="cart.php" class="cart-btn">🛒 <span id="cart-count">0</span></a>
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
            <section class="about-hero">
                <div class="hero-content">
                    <h1>Về Wibubu</h1>
                    <p>Thương mại điện tử hiện đại với thiết kế Shy Rainbow độc đáo</p>
                </div>
            </section>

            <section class="about-content">
                <div class="content-grid">
                    <div class="content-section">
                        <h2>🌈 Câu Chuyện Của Chúng Tôi</h2>
                        <p>Wibubu được ra đời với mong muốn mang đến trải nghiệm mua sắm trực tuyến độc đáo, kết hợp giữa công nghệ hiện đại và thiết kế thẩm mỹ Shy Rainbow với gam màu pastel nhẹ nhàng, tạo cảm giác thư giãn và dễ chịu cho người dùng.</p>
                    </div>

                    <div class="content-section">
                        <h2>🎯 Sứ Mệnh</h2>
                        <p>Chúng tôi cam kết cung cấp những sản phẩm chất lượng cao với dịch vụ khách hàng tuyệt vời. Mỗi sản phẩm được tuyển chọn kỹ lưỡng để đảm bảo sự hài lòng tối đa cho khách hàng.</p>
                    </div>

                    <div class="content-section">
                        <h2>💝 Giá Trị Cốt Lõi</h2>
                        <ul>
                            <li><strong>Chất lượng:</strong> Sản phẩm được kiểm tra nghiêm ngặt</li>
                            <li><strong>Đáng tin cậy:</strong> Giao hàng nhanh chóng và chính xác</li>
                            <li><strong>Sáng tạo:</strong> Luôn đổi mới trong thiết kế và trải nghiệm</li>
                            <li><strong>Khách hàng:</strong> Đặt sự hài lòng của khách hàng lên hàng đầu</li>
                        </ul>
                    </div>

                    <div class="content-section">
                        <h2>🛍️ Tại Sao Chọn Wibubu?</h2>
                        <div class="features-grid">
                            <div class="feature">
                                <h3>🚀 Giao Hàng Nhanh</h3>
                                <p>Giao hàng trong 24h với đội ngũ vận chuyển chuyên nghiệp</p>
                            </div>
                            <div class="feature">
                                <h3>🔒 Thanh Toán An Toàn</h3>
                                <p>Hệ thống thanh toán được bảo mật tối đa</p>
                            </div>
                            <div class="feature">
                                <h3>💬 Hỗ Trợ 24/7</h3>
                                <p>Đội ngũ chăm sóc khách hàng luôn sẵn sàng hỗ trợ</p>
                            </div>
                            <div class="feature">
                                <h3>🎨 Thiết Kế Độc Đáo</h3>
                                <p>Giao diện Shy Rainbow tạo trải nghiệm mua sắm thú vị</p>
                            </div>
                        </div>
                    </div>

                    <div class="content-section">
                        <h2>📞 Liên Hệ Với Chúng Tôi</h2>
                        <p>Có thắc mắc gì? Đừng ngần ngại liên hệ với chúng tôi qua:</p>
                        <ul>
                            <li>📧 Email: support@wibubu.com</li>
                            <li>📱 Hotline: 1900-WIBUBU</li>
                            <li>💬 Chat trực tuyến trên website</li>
                        </ul>
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
</body>
</html>