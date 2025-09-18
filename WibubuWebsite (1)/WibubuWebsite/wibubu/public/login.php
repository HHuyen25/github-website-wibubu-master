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
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if ($email && $password) {
            $database = new Database();
            $pdo = $database->getConnection();
            
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                
                // Redirect based on role
                switch($user['role']) {
                    case 'admin':
                        header('Location: admin/dashboard.php');
                        break;
                    case 'staff':
                        header('Location: staff/dashboard.php');
                        break;
                    case 'customer':
                    default:
                        header('Location: index.php?welcome=1');
                        break;
                }
                exit;
            } else {
                $error = 'Email hoặc mật khẩu không đúng!';
            }
        } else {
            $error = 'Vui lòng điền đầy đủ thông tin!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập - Wibubu</title>
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
                    <a href="register.php">Đăng ký</a>
                </div>
            </nav>
        </header>

        <main class="main-content">
            <!-- Enhanced Auth Hero Section -->
            <section class="auth-hero">
                <div class="auth-hero-content">
                    <h1>🔐 Đăng Nhập An Toàn</h1>
                    <p>Chào mừng bạn quay trở lại Wibubu! Hệ thống bảo mật cao cấp đảm bảo an toàn cho tài khoản của bạn.</p>
                    
                    <!-- Trust Indicators -->
                    <div class="auth-trust-badges">
                        <div class="trust-badge">
                            <span class="trust-icon">🔒</span>
                            <span class="trust-text">Mã hóa 256-bit</span>
                        </div>
                        <div class="trust-badge">
                            <span class="trust-icon">🛡️</span>
                            <span class="trust-text">Bảo mật đa lớp</span>
                        </div>
                        <div class="trust-badge">
                            <span class="trust-icon">✅</span>
                            <span class="trust-text">Xác thực an toàn</span>
                        </div>
                        <div class="trust-badge">
                            <span class="trust-icon">🚀</span>
                            <span class="trust-text">Truy cập nhanh</span>
                        </div>
                    </div>
                </div>
            </section>

            <section class="auth-section">
                <div class="enhanced-auth-container">
                    <div class="auth-header">
                        <div class="auth-icon">👤</div>
                        <h1>Đăng Nhập</h1>
                        <p>Chào mừng bạn quay trở lại Wibubu!</p>
                        <div class="security-indicator">
                            <span class="security-icon">🔐</span>
                            <span class="security-text">Phiên đăng nhập được bảo mật</span>
                        </div>
                    </div>

                    <?php if ($error): ?>
                        <div class="enhanced-error-message">
                            <div class="error-icon">⚠️</div>
                            <div class="error-content">
                                <h4>Đăng nhập thất bại</h4>
                                <p><?php echo htmlspecialchars($error); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="enhanced-success-message">
                            <div class="success-icon">✅</div>
                            <div class="success-content">
                                <h4>Thành công!</h4>
                                <p><?php echo htmlspecialchars($success); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="enhanced-auth-form" id="login-form">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <div class="enhanced-form-group">
                            <label for="email">📧 Email</label>
                            <div class="input-wrapper">
                                <input type="email" id="email" name="email" required 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                       placeholder="Nhập địa chỉ email của bạn"
                                       class="enhanced-input">
                                <div class="input-status" id="email-status"></div>
                            </div>
                        </div>

                        <div class="enhanced-form-group">
                            <label for="password">🔒 Mật khẩu</label>
                            <div class="input-wrapper">
                                <input type="password" id="password" name="password" required
                                       placeholder="Nhập mật khẩu của bạn"
                                       class="enhanced-input">
                                <button type="button" class="password-toggle" id="password-toggle">
                                    <span class="toggle-icon">👁️</span>
                                </button>
                                <div class="input-status" id="password-status"></div>
                            </div>
                        </div>

                        <div class="form-options">
                            <label class="checkbox-label">
                                <input type="checkbox" name="remember"> Ghi nhớ đăng nhập
                            </label>
                            <a href="forgot-password.php" class="forgot-link">Quên mật khẩu?</a>
                        </div>

                        <button type="submit" class="enhanced-auth-btn" id="login-btn">
                            <span class="btn-icon">🔐</span>
                            <span class="btn-text">Đăng Nhập An Toàn</span>
                            <span class="btn-arrow">→</span>
                        </button>
                    </form>

                    <div class="auth-footer">
                        <div class="auth-security-notice">
                            <div class="security-icons">
                                <span class="security-badge">🔒 SSL</span>
                                <span class="security-badge">🛡️ Bảo mật</span>
                                <span class="security-badge">✅ Tin cậy</span>
                            </div>
                            <p class="security-text">Thông tin của bạn được bảo vệ bằng mã hóa SSL 256-bit</p>
                        </div>
                        <div class="auth-links">
                            <p>Chưa có tài khoản? <a href="register.php" class="auth-link">Đăng ký ngay</a></p>
                        </div>
                    </div>

                    <div class="enhanced-demo-accounts">
                        <div class="demo-header">
                            <span class="demo-icon">🎯</span>
                            <h3>Tài khoản demo</h3>
                        </div>
                        <div class="demo-grid">
                            <div class="demo-account-card">
                                <div class="demo-badge admin-badge">👑 Quản trị viên</div>
                                <div class="demo-credentials">
                                    <div class="credential-item">
                                        <span class="credential-label">📧 Email:</span>
                                        <span class="credential-value">admin@wibubu.com</span>
                                    </div>
                                    <div class="credential-item">
                                        <span class="credential-label">🔒 Mật khẩu:</span>
                                        <span class="credential-value">password</span>
                                    </div>
                                </div>
                                <button class="quick-login-btn admin-btn" onclick="quickLogin('admin@wibubu.com', 'password')">
                                    ⚡ Đăng nhập Admin
                                </button>
                            </div>
                            
                            <div class="demo-account-card">
                                <div class="demo-badge staff-badge">👨‍💼 Nhân viên</div>
                                <div class="demo-credentials">
                                    <div class="credential-item">
                                        <span class="credential-label">📧 Email:</span>
                                        <span class="credential-value">staff@wibubu.com</span>
                                    </div>
                                    <div class="credential-item">
                                        <span class="credential-label">🔒 Mật khẩu:</span>
                                        <span class="credential-value">password</span>
                                    </div>
                                </div>
                                <button class="quick-login-btn staff-btn" onclick="quickLogin('staff@wibubu.com', 'password')">
                                    ⚡ Đăng nhập Staff
                                </button>
                            </div>
                            
                            <div class="demo-account-card">
                                <div class="demo-badge customer-badge">🛒 Khách hàng</div>
                                <div class="demo-credentials">
                                    <div class="credential-item">
                                        <span class="credential-label">📧 Email:</span>
                                        <span class="credential-value">customer@wibubu.com</span>
                                    </div>
                                    <div class="credential-item">
                                        <span class="credential-label">🔒 Mật khẩu:</span>
                                        <span class="credential-value">password</span>
                                    </div>
                                </div>
                                <button class="quick-login-btn customer-btn" onclick="quickLogin('customer@wibubu.com', 'password')">
                                    ⚡ Đăng nhập Customer
                                </button>
                            </div>
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