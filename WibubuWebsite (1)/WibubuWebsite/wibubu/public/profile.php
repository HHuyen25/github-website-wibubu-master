<?php
session_start();

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../src/models/Database.php';

$database = new Database();
$pdo = $database->getConnection();

// Get user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: logout.php');
    exit;
}

// Get current section from URL parameter, default to 'personal-info'
$current_section = $_GET['section'] ?? 'personal-info';

// Handle form submissions
$message = '';
if ($_POST) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $message = 'Token bảo mật không hợp lệ. Vui lòng thử lại!';
    } elseif (isset($_POST['update_profile'])) {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        if ($name && $email) {
            // Check if email exists for other users
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $_SESSION['user_id']]);
            
            if ($stmt->fetchColumn() > 0) {
                $message = 'Email này đã được sử dụng bởi tài khoản khác!';
            } else {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
                if ($stmt->execute([$name, $email, $_SESSION['user_id']])) {
                    $_SESSION['username'] = $name;
                    $user['name'] = $name;
                    $user['email'] = $email;
                    $message = 'Cập nhật thông tin thành công!';
                } else {
                    $message = 'Có lỗi xảy ra, vui lòng thử lại!';
                }
            }
        } else {
            $message = 'Vui lòng điền đầy đủ thông tin!';
        }
    } elseif (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if ($current_password && $new_password && $confirm_password) {
            if ($new_password !== $confirm_password) {
                $message = 'Mật khẩu xác nhận không khớp!';
            } elseif (strlen($new_password) < 6) {
                $message = 'Mật khẩu mới phải có ít nhất 6 ký tự!';
            } elseif (password_verify($current_password, $user['password'])) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                if ($stmt->execute([$hashed_password, $_SESSION['user_id']])) {
                    $message = 'Đổi mật khẩu thành công!';
                } else {
                    $message = 'Có lỗi xảy ra, vui lòng thử lại!';
                }
            } else {
                $message = 'Mật khẩu hiện tại không đúng!';
            }
        } else {
            $message = 'Vui lòng điền đầy đủ thông tin!';
        }
    }
}

// Get user's orders for order history
$orders = [];
if ($current_section === 'orders') {
    $stmt = $pdo->prepare("
        SELECT o.*, 
               GROUP_CONCAT(CONCAT(p.name, ' (x', oi.quantity, ')') SEPARATOR ', ') as items
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id  
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE o.user_id = ?
        GROUP BY o.id, o.user_id, o.total_amount, o.status, o.created_at, o.updated_at
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tài Khoản Của Tôi - Wibubu</title>
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
                    <a href="profile.php" class="active">Xin chào, <?php echo htmlspecialchars($user['name']); ?></a>
                    <a href="logout.php">Đăng xuất</a>
                </div>
            </nav>
        </header>

        <main class="main-content">
            <section class="mykingdom-hero">
                <h1>👤 Tài Khoản Của Tôi</h1>
                <p>Quản lý thông tin tài khoản và đơn hàng của bạn</p>
            </section>

            <div class="account-dashboard">
                <!-- Sidebar Navigation -->
                <div class="account-sidebar">
                    <div class="user-welcome">
                        <div class="user-avatar">
                            <span><?php echo strtoupper(substr($user['name'], 0, 1)); ?></span>
                        </div>
                        <div class="user-info">
                            <h3><?php echo htmlspecialchars($user['name']); ?></h3>
                            <p class="user-role">
                                <?php 
                                switch($user['role']) {
                                    case 'admin': echo '👑 Quản trị viên'; break;
                                    case 'staff': echo '👨‍💼 Nhân viên'; break;
                                    default: echo '👤 Khách hàng'; break;
                                }
                                ?>
                            </p>
                        </div>
                    </div>

                    <nav class="account-nav">
                        <a href="?section=personal-info" class="nav-item <?php echo $current_section === 'personal-info' ? 'active' : ''; ?>">
                            <span class="nav-icon">👤</span>
                            <span class="nav-text">Thông tin cá nhân</span>
                        </a>
                        <a href="?section=orders" class="nav-item <?php echo $current_section === 'orders' ? 'active' : ''; ?>">
                            <span class="nav-icon">📦</span>
                            <span class="nav-text">Đơn hàng của tôi</span>
                        </a>
                        <a href="?section=addresses" class="nav-item <?php echo $current_section === 'addresses' ? 'active' : ''; ?>">
                            <span class="nav-icon">📍</span>
                            <span class="nav-text">Địa chỉ giao hàng</span>
                        </a>
                        <a href="?section=password" class="nav-item <?php echo $current_section === 'password' ? 'active' : ''; ?>">
                            <span class="nav-icon">🔐</span>
                            <span class="nav-text">Đổi mật khẩu</span>
                        </a>
                    </nav>

                    <div class="sidebar-footer">
                        <a href="index.php" class="back-home">
                            <span>←</span> Về trang chủ
                        </a>
                        <a href="logout.php" class="logout-btn">
                            <span>🚪</span> Đăng xuất
                        </a>
                    </div>
                </div>

                <!-- Main Content Area -->
                <div class="account-content">
                    <?php if ($message): ?>
                        <div class="alert-message <?php echo strpos($message, 'thành công') !== false ? 'success' : 'error'; ?>">
                            <p><?php echo htmlspecialchars($message); ?></p>
                        </div>
                    <?php endif; ?>

                    <!-- Personal Information Section -->
                    <?php if ($current_section === 'personal-info'): ?>
                        <div class="content-section">
                            <div class="section-header">
                                <h2>👤 Thông Tin Cá Nhân</h2>
                                <p>Cập nhật thông tin tài khoản của bạn</p>
                            </div>

                            <div class="content-card">
                                <form method="POST" class="account-form">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="name">Họ và Tên</label>
                                            <input type="text" id="name" name="name" required 
                                                   value="<?php echo htmlspecialchars($user['name']); ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="email">Email</label>
                                            <input type="email" id="email" name="email" required 
                                                   value="<?php echo htmlspecialchars($user['email']); ?>">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label>Ngày tham gia</label>
                                        <div class="info-display">
                                            <span class="info-icon">📅</span>
                                            <span><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></span>
                                        </div>
                                    </div>

                                    <div class="form-actions">
                                        <button type="submit" name="update_profile" class="btn-primary">
                                            💾 Lưu thay đổi
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <?php if ($user['role'] === 'admin' || $user['role'] === 'staff'): ?>
                                <div class="content-card admin-panel">
                                    <h3>
                                        <?php echo $user['role'] === 'admin' ? '🔧 Panel Quản trị' : '👨‍💼 Panel Nhân viên'; ?>
                                    </h3>
                                    <p>Các chức năng quản lý sẽ được phát triển trong phiên bản tiếp theo.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    
                    <!-- Order History Section -->
                    <?php elseif ($current_section === 'orders'): ?>
                        <div class="content-section">
                            <div class="section-header">
                                <h2>📦 Đơn Hàng Của Tôi</h2>
                                <p>Theo dõi tình trạng các đơn hàng của bạn</p>
                            </div>

                            <div class="orders-container">
                                <?php if (empty($orders)): ?>
                                    <div class="empty-state">
                                        <div class="empty-icon">📦</div>
                                        <h3>Chưa có đơn hàng nào</h3>
                                        <p>Bạn chưa thực hiện đơn hàng nào. Hãy khám phá các sản phẩm tuyệt vời của chúng tôi!</p>
                                        <a href="products.php" class="btn-primary">🛍️ Mua sắm ngay</a>
                                    </div>
                                <?php else: ?>
                                    <div class="orders-list">
                                        <?php foreach ($orders as $order): ?>
                                            <div class="order-card">
                                                <div class="order-header">
                                                    <div class="order-id">
                                                        <h4>Đơn hàng #<?php echo $order['id']; ?></h4>
                                                        <span class="order-date"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></span>
                                                    </div>
                                                    <div class="order-status status-<?php echo $order['status']; ?>">
                                                        <?php 
                                                        switch($order['status']) {
                                                            case 'pending': echo '⏳ Chờ xử lý'; break;
                                                            case 'confirmed': echo '✅ Đã xác nhận'; break;
                                                            case 'shipped': echo '🚚 Đang giao hàng'; break;
                                                            case 'completed': echo '🎉 Hoàn thành'; break;
                                                            case 'cancelled': echo '❌ Đã hủy'; break;
                                                            default: echo $order['status'];
                                                        }
                                                        ?>
                                                    </div>
                                                </div>
                                                <div class="order-details">
                                                    <div class="order-items">
                                                        <strong>Sản phẩm:</strong>
                                                        <p><?php echo $order['items'] ?: 'Không có thông tin sản phẩm'; ?></p>
                                                    </div>
                                                    <div class="order-total">
                                                        <span class="total-label">Tổng tiền:</span>
                                                        <span class="total-amount"><?php echo number_format($order['total_amount'], 0, ',', '.'); ?>đ</span>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    <!-- Addresses Section -->
                    <?php elseif ($current_section === 'addresses'): ?>
                        <div class="content-section">
                            <div class="section-header">
                                <h2>📍 Địa Chỉ Giao Hàng</h2>
                                <p>Quản lý địa chỉ giao hàng của bạn</p>
                            </div>

                            <div class="content-card">
                                <div class="empty-state">
                                    <div class="empty-icon">📍</div>
                                    <h3>Quản lý địa chỉ</h3>
                                    <p>Chức năng quản lý địa chỉ giao hàng sẽ được phát triển trong phiên bản tiếp theo.</p>
                                </div>
                            </div>
                        </div>

                    <!-- Change Password Section -->
                    <?php elseif ($current_section === 'password'): ?>
                        <div class="content-section">
                            <div class="section-header">
                                <h2>🔐 Đổi Mật Khẩu</h2>
                                <p>Cập nhật mật khẩu để bảo mật tài khoản</p>
                            </div>

                            <div class="content-card">
                                <form method="POST" class="account-form">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                    <div class="form-group">
                                        <label for="current_password">Mật khẩu hiện tại</label>
                                        <input type="password" id="current_password" name="current_password" required>
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="new_password">Mật khẩu mới</label>
                                            <input type="password" id="new_password" name="new_password" required minlength="6">
                                        </div>
                                        <div class="form-group">
                                            <label for="confirm_password">Xác nhận mật khẩu mới</label>
                                            <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                                        </div>
                                    </div>

                                    <div class="password-requirements">
                                        <h4>Yêu cầu mật khẩu:</h4>
                                        <ul>
                                            <li>Ít nhất 6 ký tự</li>
                                            <li>Nên bao gồm chữ hoa, chữ thường và số</li>
                                            <li>Không sử dụng thông tin cá nhân dễ đoán</li>
                                        </ul>
                                    </div>

                                    <div class="form-actions">
                                        <button type="submit" name="change_password" class="btn-primary">
                                            🔐 Đổi mật khẩu
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
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