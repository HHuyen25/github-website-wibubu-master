<?php
session_start();
require_once '../src/models/Database.php';

$database = new Database();
$pdo = $database->getConnection();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=checkout');
    exit;
}

// Get user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$order_success = false;
$error_message = '';

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Process order submission
if ($_POST && isset($_POST['place_order'])) {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = 'Token bảo mật không hợp lệ. Vui lòng thử lại!';
    } else {
        $full_name = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $payment_method = $_POST['payment_method'] ?? '';
        $cart_data = $_POST['cart_data'] ?? '';
        
        // Validate payment method
        $allowed_payment_methods = ['cod', 'bank_transfer'];
        if (!in_array($payment_method, $allowed_payment_methods)) {
            $error_message = 'Phương thức thanh toán không hợp lệ!';
        }
        
        if (!$error_message && $full_name && $phone && $address && $city && $payment_method && $cart_data) {
            try {
                $cart = json_decode($cart_data, true);
                if (!$cart || !is_array($cart) || empty($cart)) {
                    $error_message = 'Giỏ hàng trống hoặc không hợp lệ!';
                } else {
                    $pdo->beginTransaction();
                    
                    // Server-side validation: Re-calculate totals from database
                    $product_ids = array_column($cart, 'id');
                    $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
                    $stmt = $pdo->prepare("SELECT id, name, price, stock FROM products WHERE id IN ($placeholders)");
                    $stmt->execute($product_ids);
                    $db_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $db_products_by_id = [];
                    foreach ($db_products as $product) {
                        $db_products_by_id[$product['id']] = $product;
                    }
                    
                    // Validate each cart item and calculate server-side totals
                    $validated_items = [];
                    $subtotal = 0;
                    
                    foreach ($cart as $item) {
                        $product_id = intval($item['id']);
                        $quantity = intval($item['quantity']);
                        
                        if (!isset($db_products_by_id[$product_id])) {
                            throw new Exception("Sản phẩm ID {$product_id} không tồn tại!");
                        }
                        
                        $db_product = $db_products_by_id[$product_id];
                        
                        if ($quantity <= 0) {
                            throw new Exception("Số lượng sản phẩm '{$db_product['name']}' không hợp lệ!");
                        }
                        
                        if ($quantity > $db_product['stock']) {
                            throw new Exception("Sản phẩm '{$db_product['name']}' chỉ còn {$db_product['stock']} trong kho!");
                        }
                        
                        $line_total = $db_product['price'] * $quantity;
                        $subtotal += $line_total;
                        
                        $validated_items[] = [
                            'product_id' => $product_id,
                            'product_name' => $db_product['name'],
                            'price' => $db_product['price'],
                            'quantity' => $quantity,
                            'total' => $line_total
                        ];
                    }
                    
                    // Calculate shipping, tax, discount (server-side)
                    $shipping = ($subtotal > 0 && $subtotal < 500000) ? 30000 : 0;
                    $tax = round($subtotal * 0.1);
                    $discount = 0; // Discount codes disabled for security (or implement server-side validation)
                    $total_amount = $subtotal + $shipping + $tax - $discount;
                    
                    // Create order with validated totals
                    $stmt = $pdo->prepare("
                        INSERT INTO orders (user_id, full_name, phone, address, city, payment_method, 
                                          subtotal, shipping, tax, discount, total_amount, order_status, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
                    ");
                    $stmt->execute([
                        $_SESSION['user_id'], $full_name, $phone, $address, $city, $payment_method,
                        $subtotal, $shipping, $tax, $discount, $total_amount
                    ]);
                    $order_id = $pdo->lastInsertId();
                    
                    // Create validated order items
                    $stmt = $pdo->prepare("
                        INSERT INTO order_items (order_id, product_id, product_name, price, quantity, total) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    
                    foreach ($validated_items as $item) {
                        $stmt->execute([
                            $order_id, $item['product_id'], $item['product_name'],
                            $item['price'], $item['quantity'], $item['total']
                        ]);
                        
                        // Update product stock
                        $stmt_stock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                        $stmt_stock->execute([$item['quantity'], $item['product_id']]);
                    }
                    
                    $pdo->commit();
                    $order_success = true;
                    
                    // Regenerate CSRF token after successful order
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                }
                
            } catch (Exception $e) {
                $pdo->rollback();
                $error_message = 'Có lỗi xảy ra khi tạo đơn hàng: ' . $e->getMessage();
            }
        } else {
            $error_message = 'Vui lòng điền đầy đủ thông tin!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh Toán - Wibubu</title>
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
                    <a href="cart.php" class="cart-btn">🛒 <span id="cart-count">0</span></a>
                    <a href="profile.php">Xin chào, <?php echo htmlspecialchars($user['name']); ?></a>
                    <a href="logout.php">Đăng xuất</a>
                </div>
            </nav>
        </header>

        <main class="main-content">
            <?php if ($order_success): ?>
                <!-- Enhanced Order Success State -->
                <section style="text-align: center; padding: var(--space-6xl) 0; animation: scale-in 0.8s ease-out;">
                    <div style="background: var(--gradient-card); padding: var(--space-6xl); border-radius: var(--radius-2xl); border: 1px solid rgba(255,255,255,0.3); backdrop-filter: blur(15px); box-shadow: var(--shadow-2xl); max-width: 600px; margin: 0 auto; position: relative; overflow: hidden;">
                        <!-- Celebration Animation -->
                        <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(45deg, rgba(34, 197, 94, 0.1), rgba(59, 130, 246, 0.1), rgba(168, 85, 247, 0.1)); animation: celebration 3s ease-out infinite;"></div>
                        
                        <div style="position: relative; z-index: 1;">
                            <div style="background: var(--gradient-glass); width: 120px; height: 120px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto var(--space-2xl); border: 1px solid rgba(255,255,255,0.3); backdrop-filter: blur(10px); animation: bounce 2s ease-out infinite;">
                                <span style="font-size: 4rem;">🎉</span>
                            </div>
                            
                            <h1 style="font-size: var(--text-4xl); font-weight: 700; color: var(--color-neutral-900); margin-bottom: var(--space-lg);">Đặt Hàng Thành Công!</h1>
                            
                            <div style="background: var(--gradient-glass); padding: var(--space-xl); border-radius: var(--radius-lg); border: 1px solid rgba(255,255,255,0.3); backdrop-filter: blur(10px); margin-bottom: var(--space-2xl);">
                                <div style="font-size: var(--text-lg); color: var(--color-neutral-700); margin-bottom: var(--space-sm);">
                                    Mã đơn hàng: <strong style="color: var(--color-neutral-900);">#<?php echo $order_id; ?></strong>
                                </div>
                                <div style="font-size: var(--text-base); color: var(--color-neutral-600);">
                                    Cảm ơn bạn đã tin tưởng và mua hàng tại Wibubu. Đơn hàng của bạn đang được xử lý và sẽ sớm được giao đến bạn.
                                </div>
                            </div>
                            
                            <!-- Order Status Steps -->
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: var(--space-lg); margin-bottom: var(--space-4xl);">
                                <div style="background: var(--gradient-glass); padding: var(--space-lg); border-radius: var(--radius-lg); border: 1px solid rgba(34, 197, 94, 0.3); backdrop-filter: blur(10px); text-align: center;">
                                    <div style="background: var(--color-success-100); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto var(--space-sm);">
                                        <span>✅</span>
                                    </div>
                                    <div style="font-size: var(--text-sm); color: var(--color-success-700); font-weight: 600;">Đã xác nhận</div>
                                </div>
                                <div style="background: var(--gradient-glass); padding: var(--space-lg); border-radius: var(--radius-lg); border: 1px solid rgba(255,255,255,0.3); backdrop-filter: blur(10px); text-align: center;">
                                    <div style="background: var(--color-neutral-200); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto var(--space-sm);">
                                        <span>📦</span>
                                    </div>
                                    <div style="font-size: var(--text-sm); color: var(--color-neutral-600);">Đang chuẩn bị</div>
                                </div>
                                <div style="background: var(--gradient-glass); padding: var(--space-lg); border-radius: var(--radius-lg); border: 1px solid rgba(255,255,255,0.3); backdrop-filter: blur(10px); text-align: center;">
                                    <div style="background: var(--color-neutral-200); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto var(--space-sm);">
                                        <span>🚚</span>
                                    </div>
                                    <div style="font-size: var(--text-sm); color: var(--color-neutral-600);">Đang giao</div>
                                </div>
                            </div>
                            
                            <!-- Success Actions -->
                            <div style="display: flex; gap: var(--space-lg); justify-content: center; flex-wrap: wrap;">
                                <a href="index.php" class="cta-premium" style="padding: var(--space-lg) var(--space-3xl); font-size: var(--text-base);">
                                    <span>🛍️ Tiếp Tục Mua Sắm</span>
                                </a>
                                <a href="profile.php" style="padding: var(--space-lg) var(--space-3xl); background: var(--gradient-glass); color: var(--color-neutral-700); text-decoration: none; border-radius: var(--radius-xl); font-weight: 600; border: 1px solid rgba(255,255,255,0.3); backdrop-filter: blur(10px); transition: all var(--transition-fast); display: inline-flex; align-items: center; gap: var(--space-md);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='var(--shadow-lg)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                    <span>📋 Xem Đơn Hàng</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </section>
            <?php else: ?>
                <!-- Enhanced Checkout Hero Section -->
                <section class="checkout-hero">
                    <div class="checkout-hero-content">
                        <h1>🛡️ Thanh Toán Bảo Mật</h1>
                        <p>Hoàn tất đơn hàng của bạn với hệ thống thanh toán an toàn và bảo mật cao</p>
                        
                        <!-- Trust Indicators -->
                        <div class="checkout-trust-badges">
                            <div class="trust-badge">
                                <span class="trust-icon">🔒</span>
                                <span class="trust-text">Bảo mật SSL</span>
                            </div>
                            <div class="trust-badge">
                                <span class="trust-icon">🛡️</span>
                                <span class="trust-text">Dữ liệu được mã hóa</span>
                            </div>
                            <div class="trust-badge">
                                <span class="trust-icon">✅</span>
                                <span class="trust-text">Giao dịch an toàn</span>
                            </div>
                            <div class="trust-badge">
                                <span class="trust-icon">📞</span>
                                <span class="trust-text">Hỗ trợ 24/7</span>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="checkout-section">
                    <?php if ($error_message): ?>
                        <div class="enhanced-error-message">
                            <div class="error-icon">⚠️</div>
                            <div class="error-content">
                                <h4>Có lỗi xảy ra</h4>
                                <p><?php echo htmlspecialchars($error_message); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="checkout-container">
                        <div class="enhanced-checkout-form">
                            <div class="form-header">
                                <h2>📦 Thông tin giao hàng</h2>
                                <div class="security-indicator">
                                    <span class="security-icon">🔒</span>
                                    <span class="security-text">Thông tin được bảo mật</span>
                                </div>
                            </div>
                            
                            <form method="POST" id="checkout-form" class="modern-checkout-form">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <input type="hidden" id="cart-data" name="cart_data" value="">
                                
                                <div class="form-group">
                                    <label for="full_name">Họ và tên</label>
                                    <input type="text" id="full_name" name="full_name" required 
                                           value="<?php echo htmlspecialchars($user['name']); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="phone">Số điện thoại</label>
                                    <input type="tel" id="phone" name="phone" required 
                                           placeholder="0123456789">
                                </div>

                                <div class="form-group">
                                    <label for="address">Địa chỉ</label>
                                    <textarea id="address" name="address" required 
                                              placeholder="Số nhà, tên đường, phường/xã"></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="city">Tỉnh/Thành phố</label>
                                    <select id="city" name="city" required>
                                        <option value="">Chọn tỉnh/thành phố</option>
                                        <option value="Ho Chi Minh">TP. Hồ Chí Minh</option>
                                        <option value="Ha Noi">Hà Nội</option>
                                        <option value="Da Nang">Đà Nẵng</option>
                                        <option value="Can Tho">Cần Thơ</option>
                                        <option value="Hai Phong">Hải Phòng</option>
                                        <option value="An Giang">An Giang</option>
                                        <option value="Ba Ria Vung Tau">Bà Rịa - Vũng Tàu</option>
                                        <option value="Bac Lieu">Bạc Liêu</option>
                                        <option value="Bac Kan">Bắc Kạn</option>
                                        <option value="Bac Giang">Bắc Giang</option>
                                        <option value="Bac Ninh">Bắc Ninh</option>
                                        <option value="Ben Tre">Bến Tre</option>
                                        <option value="Binh Dinh">Bình Định</option>
                                        <option value="Binh Duong">Bình Dương</option>
                                        <option value="Binh Phuoc">Bình Phước</option>
                                        <option value="Binh Thuan">Bình Thuận</option>
                                        <option value="Ca Mau">Cà Mau</option>
                                        <option value="Cao Bang">Cao Bằng</option>
                                        <option value="Dak Lak">Đắk Lắk</option>
                                        <option value="Dak Nong">Đắk Nông</option>
                                        <option value="Dien Bien">Điện Biên</option>
                                        <option value="Dong Nai">Đồng Nai</option>
                                        <option value="Dong Thap">Đồng Tháp</option>
                                        <option value="Gia Lai">Gia Lai</option>
                                        <option value="Ha Giang">Hà Giang</option>
                                        <option value="Ha Nam">Hà Nam</option>
                                        <option value="Ha Tinh">Hà Tĩnh</option>
                                        <option value="Hai Duong">Hải Dương</option>
                                        <option value="Hau Giang">Hậu Giang</option>
                                        <option value="Hoa Binh">Hòa Bình</option>
                                        <option value="Hung Yen">Hưng Yên</option>
                                        <option value="Khanh Hoa">Khánh Hòa</option>
                                        <option value="Kien Giang">Kiên Giang</option>
                                        <option value="Kon Tum">Kon Tum</option>
                                        <option value="Lai Chau">Lai Châu</option>
                                        <option value="Lam Dong">Lâm Đồng</option>
                                        <option value="Lang Son">Lạng Sơn</option>
                                        <option value="Lao Cai">Lào Cai</option>
                                        <option value="Long An">Long An</option>
                                        <option value="Nam Dinh">Nam Định</option>
                                        <option value="Nghe An">Nghệ An</option>
                                        <option value="Ninh Binh">Ninh Bình</option>
                                        <option value="Ninh Thuan">Ninh Thuận</option>
                                        <option value="Phu Tho">Phú Thọ</option>
                                        <option value="Phu Yen">Phú Yên</option>
                                        <option value="Quang Binh">Quảng Bình</option>
                                        <option value="Quang Nam">Quảng Nam</option>
                                        <option value="Quang Ngai">Quảng Ngãi</option>
                                        <option value="Quang Ninh">Quảng Ninh</option>
                                        <option value="Quang Tri">Quảng Trị</option>
                                        <option value="Soc Trang">Sóc Trăng</option>
                                        <option value="Son La">Sơn La</option>
                                        <option value="Tay Ninh">Tây Ninh</option>
                                        <option value="Thai Binh">Thái Bình</option>
                                        <option value="Thai Nguyen">Thái Nguyên</option>
                                        <option value="Thanh Hoa">Thanh Hóa</option>
                                        <option value="Thua Thien Hue">Thừa Thiên Huế</option>
                                        <option value="Tien Giang">Tiền Giang</option>
                                        <option value="Tra Vinh">Trà Vinh</option>
                                        <option value="Tuyen Quang">Tuyên Quang</option>
                                        <option value="Vinh Long">Vĩnh Long</option>
                                        <option value="Vinh Phuc">Vĩnh Phúc</option>
                                        <option value="Yen Bai">Yên Bái</option>
                                    </select>
                                </div>

                                <div class="enhanced-payment-methods">
                                    <div class="payment-header">
                                        <h3>💳 Phương thức thanh toán</h3>
                                        <div class="payment-security">
                                            <span class="security-badge">🔒 Bảo mật cao</span>
                                        </div>
                                    </div>
                                    
                                    <div class="payment-options-grid">
                                        <div class="enhanced-payment-option" data-method="cod">
                                            <input type="radio" id="cod" name="payment_method" value="cod" checked>
                                            <label for="cod" class="payment-card">
                                                <div class="payment-icon">💵</div>
                                                <div class="payment-details">
                                                    <div class="payment-title">Thanh toán khi nhận hàng</div>
                                                    <div class="payment-subtitle">Tiện lợi và an toàn</div>
                                                    <div class="payment-description">Thanh toán bằng tiền mặt khi nhận được hàng</div>
                                                    <div class="payment-benefits">
                                                        <span class="benefit">✓ Không cần trả trước</span>
                                                        <span class="benefit">✓ Kiểm tra hàng trước khi thanh toán</span>
                                                    </div>
                                                </div>
                                                <div class="payment-check">
                                                    <span class="checkmark">✓</span>
                                                </div>
                                            </label>
                                        </div>
                                        
                                        <div class="enhanced-payment-option" data-method="bank_transfer">
                                            <input type="radio" id="bank_transfer" name="payment_method" value="bank_transfer">
                                            <label for="bank_transfer" class="payment-card">
                                                <div class="payment-icon">🏦</div>
                                                <div class="payment-details">
                                                    <div class="payment-title">Chuyển khoản ngân hàng</div>
                                                    <div class="payment-subtitle">Nhanh chóng và chính xác</div>
                                                    <div class="payment-description">Chuyển khoản trước, giao hàng sau khi có xác nhận</div>
                                                    <div class="payment-benefits">
                                                        <span class="benefit">✓ Xử lý đơn hàng nhanh hơn</span>
                                                        <span class="benefit">✓ Giảm 2% phí vận chuyển</span>
                                                    </div>
                                                </div>
                                                <div class="payment-check">
                                                    <span class="checkmark">✓</span>
                                                </div>
                                            </label>
                                        </div>
                                        
                                        <div class="enhanced-payment-option disabled" data-method="momo">
                                            <input type="radio" id="momo" name="payment_method" value="momo" disabled>
                                            <label for="momo" class="payment-card disabled">
                                                <div class="payment-icon">📱</div>
                                                <div class="payment-details">
                                                    <div class="payment-title">Ví MoMo</div>
                                                    <div class="payment-subtitle">Sắp ra mắt</div>
                                                    <div class="payment-description">Tính năng đang được phát triển</div>
                                                    <div class="coming-soon-badge">🚀 Sắp có</div>
                                                </div>
                                            </label>
                                        </div>
                                        
                                        <div class="enhanced-payment-option disabled" data-method="credit_card">
                                            <input type="radio" id="credit_card" name="payment_method" value="credit_card" disabled>
                                            <label for="credit_card" class="payment-card disabled">
                                                <div class="payment-icon">💳</div>
                                                <div class="payment-details">
                                                    <div class="payment-title">Thẻ tín dụng/ghi nợ</div>
                                                    <div class="payment-subtitle">Sắp ra mắt</div>
                                                    <div class="payment-description">Tính năng đang được phát triển</div>
                                                    <div class="coming-soon-badge">🚀 Sắp có</div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="checkout-footer">
                                    <div class="checkout-security-notice">
                                        <div class="security-icons">
                                            <span class="security-badge">🔒 SSL</span>
                                            <span class="security-badge">🛡️ Bảo mật</span>
                                            <span class="security-badge">✅ Xác thực</span>
                                        </div>
                                        <p class="security-text">Thông tin của bạn được bảo vệ bằng mã hóa SSL 256-bit</p>
                                    </div>
                                    
                                    <button type="submit" name="place_order" id="place-order-btn" class="enhanced-place-order-btn">
                                        <span class="btn-icon">🛡️</span>
                                        <span class="btn-text">Đặt hàng an toàn</span>
                                        <span class="btn-arrow">→</span>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="enhanced-order-summary">
                            <div class="summary-header">
                                <h2>📋 Đơn hàng của bạn</h2>
                                <div class="order-count-badge" id="order-item-count">0 sản phẩm</div>
                            </div>
                            
                            <div class="summary-content">
                                <div id="checkout-cart-items" class="cart-items-display"></div>
                                <div id="checkout-summary" class="order-totals"></div>
                                
                                <!-- Order Benefits -->
                                <div class="order-benefits">
                                    <div class="benefit-item">
                                        <span class="benefit-icon">🚚</span>
                                        <span class="benefit-text">Miễn phí giao hàng đơn từ 500k</span>
                                    </div>
                                    <div class="benefit-item">
                                        <span class="benefit-icon">↩️</span>
                                        <span class="benefit-text">Đổi trả trong 30 ngày</span>
                                    </div>
                                    <div class="benefit-item">
                                        <span class="benefit-icon">🛡️</span>
                                        <span class="benefit-text">Bảo hành chính hãng</span>
                                    </div>
                                </div>
                                
                                <!-- Support Contact -->
                                <div class="checkout-support">
                                    <div class="support-header">
                                        <span class="support-icon">📞</span>
                                        <span class="support-title">Cần hỗ trợ?</span>
                                    </div>
                                    <p class="support-text">Gọi ngay: <strong>1900-123-456</strong></p>
                                    <p class="support-time">Hỗ trợ 24/7 - Miễn phí</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            <?php endif; ?>
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
    <script src="assets/js/checkout.js"></script>
    
    <?php if ($order_success): ?>
    <script>
        // Clear cart after successful order - ensure it runs after page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Clearing cart after successful order...');
            
            // Clear cart and related data
            localStorage.removeItem('cart');
            localStorage.removeItem('appliedDiscount');
            
            // Clear cart with empty array as backup
            localStorage.setItem('cart', JSON.stringify([]));
            
            // Update cart count if function exists
            if (typeof updateCartCount === 'function') {
                updateCartCount();
            }
            
            // Update cart count badge manually
            const cartCountEl = document.getElementById('cart-count');
            if (cartCountEl) {
                cartCountEl.textContent = '0';
            }
            
            // Force update cart display if on cart page
            if (typeof loadCart === 'function') {
                loadCart();
            }
            
            console.log('Cart cleared successfully');
        });
        
        // Also clear immediately in case DOMContentLoaded already fired
        localStorage.removeItem('cart');
        localStorage.removeItem('appliedDiscount');
        localStorage.setItem('cart', JSON.stringify([]));
    </script>
    <?php endif; ?>
</body>
</html>