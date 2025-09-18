<?php
session_start();
require_once '../src/models/Database.php';

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$error = '';
$success = '';

if ($_POST) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Token bảo mật không hợp lệ. Vui lòng thử lại!';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (!$name || !$email || !$password || !$confirm_password) {
            $error = 'Vui lòng điền đầy đủ thông tin!';
        } elseif ($password !== $confirm_password) {
            $error = 'Mật khẩu xác nhận không khớp!';
        } elseif (strlen($password) < 6) {
            $error = 'Mật khẩu phải có ít nhất 6 ký tự!';
        } else {
            $database = new Database();
            $pdo = $database->getConnection();
            
            // Check if email exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetchColumn() > 0) {
                $error = 'Email này đã được sử dụng!';
            } else {
                // Create new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'customer')");
                
                if ($stmt->execute([$name, $email, $hashed_password])) {
                    $success = 'Đăng ký thành công! Bạn có thể đăng nhập ngay bây giờ.';
                } else {
                    $error = 'Có lỗi xảy ra, vui lòng thử lại!';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Ký - Wibubu</title>
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
                    <li><a href="contact.php">Liên Hệ</a></li>
                </ul>
                <div class="nav-actions">
                    <button id="theme-toggle" class="theme-btn">🌙</button>
                    <a href="cart.php" class="cart-btn">🛒 <span id="cart-count">0</span></a>
                    <a href="login.php">Đăng nhập</a>
                </div>
            </nav>
        </header>

        <main class="main-content">
            <!-- Enhanced Auth Hero Section -->
            <section class="auth-hero">
                <div class="auth-hero-content">
                    <h1>🎆 Tạo Tài Khoản An Toàn</h1>
                    <p>Tham gia cộng đồng Wibubu để trải nghiệm mua sắm tối ưu! Hệ thống bảo mật tiên tiến bảo vệ thông tin của bạn.</p>
                    
                    <!-- Trust Indicators -->
                    <div class="auth-trust-badges">
                        <div class="trust-badge">
                            <span class="trust-icon">🔒</span>
                            <span class="trust-text">Mã hóa dữ liệu</span>
                        </div>
                        <div class="trust-badge">
                            <span class="trust-icon">🛡️</span>
                            <span class="trust-text">Bảo vệ riêng tư</span>
                        </div>
                        <div class="trust-badge">
                            <span class="trust-icon">✨</span>
                            <span class="trust-text">Đăng ký miễn phí</span>
                        </div>
                        <div class="trust-badge">
                            <span class="trust-icon">⚡</span>
                            <span class="trust-text">Kích hoạt ngay</span>
                        </div>
                    </div>
                </div>
            </section>

            <section class="auth-section">
                <div class="enhanced-auth-container">
                    <div class="auth-header">
                        <div class="auth-icon">👤</div>
                        <h1>Đăng Ký</h1>
                        <p>Tạo tài khoản mới để trải nghiệm Wibubu!</p>
                        <div class="security-indicator">
                            <span class="security-icon">🎆</span>
                            <span class="security-text">Quá trình đăng ký được bảo mật</span>
                        </div>
                    </div>

                    <?php if ($error): ?>
                        <div class="enhanced-error-message">
                            <div class="error-icon">⚠️</div>
                            <div class="error-content">
                                <h4>Đăng ký thất bại</h4>
                                <p><?php echo htmlspecialchars($error); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="enhanced-success-message">
                            <div class="success-icon">🎉</div>
                            <div class="success-content">
                                <h4>Chúc mừng! Đăng ký thành công</h4>
                                <p><?php echo htmlspecialchars($success); ?></p>
                                <p><a href="login.php" class="success-link">Đăng nhập ngay →</a></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="enhanced-auth-form" id="register-form">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <div class="enhanced-form-group">
                            <label for="name">👤 Họ và Tên</label>
                            <div class="input-wrapper">
                                <input type="text" id="name" name="name" required 
                                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                                       placeholder="Nhập họ và tên đầy đủ của bạn"
                                       class="enhanced-input">
                                <div class="input-status" id="name-status"></div>
                            </div>
                        </div>

                        <div class="enhanced-form-group">
                            <label for="email">📧 Email</label>
                            <div class="input-wrapper">
                                <input type="email" id="email" name="email" required 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                       placeholder="Nhập địa chỉ email hợp lệ"
                                       class="enhanced-input">
                                <div class="input-status" id="email-status"></div>
                            </div>
                        </div>

                        <div class="enhanced-form-group">
                            <label for="password">🔒 Mật khẩu</label>
                            <div class="input-wrapper">
                                <input type="password" id="password" name="password" required 
                                       placeholder="Tạo mật khẩu mạnh (ít nhất 6 ký tự)"
                                       class="enhanced-input">
                                <button type="button" class="password-toggle" id="password-toggle">
                                    <span class="toggle-icon">👁️</span>
                                </button>
                                <div class="input-status" id="password-status"></div>
                            </div>
                            <!-- Password Strength Indicator -->
                            <div class="password-strength" id="password-strength">
                                <div class="strength-meter">
                                    <div class="strength-bar" id="strength-bar"></div>
                                </div>
                                <div class="strength-text" id="strength-text">Mật khẩu chưa được nhập</div>
                                <div class="password-requirements">
                                    <div class="requirement" id="req-length">• Ít nhất 6 ký tự</div>
                                    <div class="requirement" id="req-uppercase">• Chứ hoa (A-Z)</div>
                                    <div class="requirement" id="req-lowercase">• Chứ thường (a-z)</div>
                                    <div class="requirement" id="req-number">• Số (0-9)</div>
                                </div>
                            </div>
                        </div>

                        <div class="enhanced-form-group">
                            <label for="confirm_password">🔐 Xác nhận mật khẩu</label>
                            <div class="input-wrapper">
                                <input type="password" id="confirm_password" name="confirm_password" required
                                       placeholder="Nhập lại mật khẩu để xác nhận"
                                       class="enhanced-input">
                                <button type="button" class="password-toggle" id="confirm-password-toggle">
                                    <span class="toggle-icon">👁️</span>
                                </button>
                                <div class="input-status" id="confirm-password-status"></div>
                            </div>
                        </div>

                        <div class="enhanced-form-options">
                            <label class="enhanced-checkbox-label">
                                <input type="checkbox" id="terms-checkbox" required class="enhanced-checkbox"> 
                                <span class="checkbox-custom"></span>
                                <span class="checkbox-text">Tôi đồng ý với <a href="terms.php" class="terms-link">Điều khoản sử dụng</a> và <a href="privacy.php" class="terms-link">Chính sách bảo mật</a></span>
                            </label>
                        </div>

                        <button type="submit" class="enhanced-auth-btn" id="register-btn">
                            <span class="btn-icon">🎆</span>
                            <span class="btn-text">Tạo Tài Khoản</span>
                            <span class="btn-arrow">→</span>
                        </button>
                    </form>

                    <div class="auth-footer">
                        <div class="auth-security-notice">
                            <div class="security-icons">
                                <span class="security-badge">🔒 Mã hóa</span>
                                <span class="security-badge">🛡️ Bảo mật</span>
                                <span class="security-badge">✨ Miễn phí</span>
                            </div>
                            <p class="security-text">Thông tin cá nhân của bạn được bảo vệ và không được chia sẻ</p>
                        </div>
                        <div class="auth-links">
                            <p>Đã có tài khoản? <a href="login.php" class="auth-link">Đăng nhập ngay</a></p>
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
</body>
</html>