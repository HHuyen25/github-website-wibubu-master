<?php 
session_start();
$message = '';

if ($_POST) {
    // Process contact form
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $msg = $_POST['message'] ?? '';
    
    if ($name && $email && $subject && $msg) {
        $message = "Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi trong thời gian sớm nhất.";
    } else {
        $message = "Vui lòng điền đầy đủ thông tin.";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liên Hệ - Wibubu</title>
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
                    <li><a href="about.php">Giới Thiệu</a></li>
                    <li><a href="contact.php" class="active">Liên Hệ</a></li>
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
            <section class="contact-hero">
                <h1>Liên Hệ Với Chúng Tôi</h1>
                <p>Chúng tôi luôn sẵn sàng lắng nghe và hỗ trợ bạn</p>
            </section>

            <?php if ($message): ?>
                <div class="message-alert">
                    <p><?php echo htmlspecialchars($message); ?></p>
                </div>
            <?php endif; ?>

            <section class="contact-content">
                <div class="contact-grid">
                    <div class="contact-info">
                        <h2>Thông Tin Liên Hệ</h2>
                        
                        <div class="contact-item">
                            <h3>📍 Địa Chỉ</h3>
                            <p>123 Đường ABC, Quận XYZ<br>TP. Hồ Chí Minh, Việt Nam</p>
                        </div>

                        <div class="contact-item">
                            <h3>📞 Điện Thoại</h3>
                            <p>Hotline: 1900-WIBUBU<br>Mobile: (+84) 123-456-789</p>
                        </div>

                        <div class="contact-item">
                            <h3>📧 Email</h3>
                            <p>support@wibubu.com<br>info@wibubu.com</p>
                        </div>

                        <div class="contact-item">
                            <h3>⏰ Giờ Làm Việc</h3>
                            <p>Thứ 2 - Thứ 6: 8:00 - 18:00<br>Thứ 7 - Chủ Nhật: 9:00 - 17:00</p>
                        </div>

                        <div class="contact-item">
                            <h3>💬 Chat Trực Tuyến</h3>
                            <p>Nhấn vào widget chat ở góc phải màn hình để được hỗ trợ ngay lập tức!</p>
                        </div>
                    </div>

                    <div class="contact-form-section">
                        <h2>Gửi Tin Nhắn</h2>
                        <form method="POST" class="contact-form">
                            <div class="form-group">
                                <label for="name">Họ và Tên *</label>
                                <input type="text" id="name" name="name" required>
                            </div>

                            <div class="form-group">
                                <label for="email">Email *</label>
                                <input type="email" id="email" name="email" required>
                            </div>

                            <div class="form-group">
                                <label for="subject">Chủ Đề *</label>
                                <select id="subject" name="subject" required>
                                    <option value="">Chọn chủ đề</option>
                                    <option value="general">Thông tin chung</option>
                                    <option value="order">Đơn hàng</option>
                                    <option value="product">Sản phẩm</option>
                                    <option value="technical">Hỗ trợ kỹ thuật</option>
                                    <option value="complaint">Khiếu nại</option>
                                    <option value="suggestion">Góp ý</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="message">Tin Nhắn *</label>
                                <textarea id="message" name="message" rows="6" required placeholder="Nhập nội dung tin nhắn của bạn..."></textarea>
                            </div>

                            <button type="submit" class="submit-btn">Gửi Tin Nhắn</button>
                        </form>
                    </div>
                </div>
            </section>

            <section class="faq-section">
                <h2>Câu Hỏi Thường Gặp</h2>
                <div class="faq-grid">
                    <div class="faq-item">
                        <h3>❓ Làm thế nào để đặt hàng?</h3>
                        <p>Bạn có thể đặt hàng trực tiếp trên website bằng cách thêm sản phẩm vào giỏ hàng và thanh toán.</p>
                    </div>
                    <div class="faq-item">
                        <h3>❓ Thời gian giao hàng là bao lâu?</h3>
                        <p>Chúng tôi giao hàng trong vòng 24-48h tùy theo khu vực của bạn.</p>
                    </div>
                    <div class="faq-item">
                        <h3>❓ Có thể đổi trả hàng không?</h3>
                        <p>Có, bạn có thể đổi trả trong vòng 7 ngày kể từ ngày nhận hàng.</p>
                    </div>
                    <div class="faq-item">
                        <h3>❓ Các hình thức thanh toán?</h3>
                        <p>Chúng tôi hỗ trợ thanh toán bằng thẻ tín dụng, chuyển khoản và COD.</p>
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