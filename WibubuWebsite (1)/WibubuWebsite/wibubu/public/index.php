<?php
session_start();
require_once '../src/models/Database.php';

$database = new Database();
$pdo = $database->getConnection();

// Get featured products
$stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p 
                       LEFT JOIN categories c ON p.category_id = c.id 
                       ORDER BY p.created_at DESC LIMIT 6");
$stmt->execute();
$featured_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wibubu - Thương Mại Điện Tử</title>
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
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if ($_SESSION['user_role'] === 'admin'): ?>
                            <a href="admin/dashboard.php" class="dashboard-link">👑 Admin Panel</a>
                        <?php elseif ($_SESSION['user_role'] === 'staff'): ?>
                            <a href="staff/dashboard.php" class="dashboard-link">👨‍💼 Staff Panel</a>
                        <?php endif; ?>
                        <span class="user-greeting">Xin chào, <?php echo htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['username'] ?? 'User'); ?></span>
                        <form method="POST" action="auth.php" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : ''; ?>">
                            <input type="hidden" name="action" value="logout">
                            <button type="submit" class="logout-link" style="background: none; border: none; color: inherit; cursor: pointer; font: inherit;">Đăng xuất</button>
                        </form>
                    <?php else: ?>
                        <a href="login.php">Đăng nhập</a>
                        <a href="register.php">Đăng ký</a>
                    <?php endif; ?>
                </div>
            </nav>
        </header>

        <main class="main-content">
            <!-- Enhanced Premium Hero Section -->
            <section class="mykingdom-hero">
                <h1>🌈 Chào Mừng Đến Với Wibubu</h1>
                <p>Khám phá thế giới sản phẩm độc quyền với thiết kế Shy Rainbow đầy màu sắc. Trải nghiệm mua sắm trực tuyến tuyệt vời với hàng ngàn sản phẩm chất lượng cao.</p>
                <div style="margin-top: var(--space-4xl);">
                    <a href="products.php" class="cta-premium">
                        <span>Khám Phá Ngay</span>
                        <span>✨</span>
                    </a>
                </div>
            </section>

            <!-- Trust Indicators -->
            <section class="trust-indicators">
                <div class="trust-item">
                    <span class="trust-icon">🛡️</span>
                    <span>Bảo Hành Chính Hãng</span>
                </div>
                <div class="trust-item">
                    <span class="trust-icon">🚚</span>
                    <span>Giao Hàng Miễn Phí</span>
                </div>
                <div class="trust-item">
                    <span class="trust-icon">⭐</span>
                    <span>98% Khách Hài Lòng</span>
                </div>
                <div class="trust-item">
                    <span class="trust-icon">💳</span>
                    <span>Thanh Toán Bảo Mật</span>
                </div>
            </section>

            <!-- Featured Products Section -->
            <section class="mykingdom-products">
                <div style="text-align: center; margin-bottom: var(--space-6xl);">
                    <h2 style="font-size: var(--text-3xl); color: var(--color-neutral-800); margin-bottom: var(--space-lg);">✨ Sản Phẩm Nổi Bật</h2>
                    <p style="font-size: var(--text-lg); color: var(--color-neutral-600); max-width: 600px; margin: 0 auto;">Những sản phẩm được yêu thích nhất với thiết kế độc đáo và chất lượng vượt trội</p>
                </div>
                <div class="mykingdom-grid">
                    <?php foreach ($featured_products as $product): ?>
                        <div class="mykingdom-card" style="animation: slide-in-up 0.6s ease-out;">
                            <div class="mykingdom-image">
                                <a href="product-detail.php?id=<?php echo $product['id']; ?>" class="product-image-link">
                                    <img src="<?php echo $product['image_url'] ?: 'https://via.placeholder.com/300x250?text=' . urlencode($product['name']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                                </a>
                                <div class="exclusive-badge">✨ Độc quyền</div>
                                <?php if ($product['stock'] < 10 && $product['stock'] > 0): ?>
                                    <div class="promotion-tag">⚡ Sắp hết hàng</div>
                                <?php elseif (rand(1, 3) == 1): ?>
                                    <div class="promotion-tag">🎁 Ưu đãi đặc biệt</div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mykingdom-info">
                                <h3 class="product-title">
                                    <a href="product-detail.php?id=<?php echo $product['id']; ?>" class="product-title-link">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </a>
                                </h3>
                                
                                <div class="supplier-info">
                                    <div>
                                        <span class="supplier-label">Nhà cung cấp:</span><br>
                                        <strong><?php echo htmlspecialchars($product['category_name'] ?: 'WIBUBU'); ?></strong>
                                    </div>
                                    <div>
                                        <span class="supplier-label">SKU:</span><br>
                                        <span class="sku-info">WB<?php echo str_pad($product['id'], 3, '0', STR_PAD_LEFT); ?></span>
                                    </div>
                                </div>

                                <div class="mykingdom-pricing">
                                    <?php 
                                    $sale_price = $product['price'];
                                    $original_price = round($sale_price * 1.3);
                                    $discount_percent = round(((($original_price - $sale_price) / $original_price) * 100));
                                    ?>
                                    <div class="price-row" style="justify-content: center; align-items: baseline; gap: var(--space-lg);">
                                        <span class="sale-price" style="font-size: var(--text-xl);"><?php echo number_format($sale_price, 0, ',', '.'); ?>₫</span>
                                        <?php if ($discount_percent > 0): ?>
                                            <span class="original-price" style="font-size: var(--text-sm);"><?php echo number_format($original_price, 0, ',', '.'); ?>₫</span>
                                            <span style="background: var(--color-error-500); color: var(--color-neutral-0); padding: var(--space-xs) var(--space-sm); border-radius: var(--radius-full); font-size: var(--text-xs); font-weight: 600;">-<?php echo $discount_percent; ?>%</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="mykingdom-actions">
                                    <div style="display: flex; gap: var(--space-lg); margin-bottom: var(--space-lg);">
                                        <a href="product-detail.php?id=<?php echo $product['id']; ?>" 
                                           style="flex: 1; padding: var(--space-lg); background: var(--gradient-glass); color: var(--color-neutral-700); text-decoration: none; text-align: center; border-radius: var(--radius-lg); font-weight: 600; transition: all var(--transition-fast); border: 1px solid rgba(255,255,255,0.3); backdrop-filter: blur(10px);" 
                                           onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='var(--shadow-lg)'" 
                                           onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                            👁️ Xem Chi Tiết
                                        </a>
                                        <?php if ($product['stock'] > 0): ?>
                                            <button class="add-to-cart-mykingdom" 
                                                    data-product-id="<?php echo $product['id']; ?>"
                                                    data-product-name="<?php echo htmlspecialchars($product['name']); ?>"
                                                    data-product-price="<?php echo $sale_price; ?>"
                                                    data-product-image="<?php echo htmlspecialchars($product['image_url'] ?: 'https://via.placeholder.com/300x250?text=' . urlencode($product['name'])); ?>"
                                                    style="flex: 1;">
                                                🛒 Thêm vào giỏ
                                            </button>
                                        <?php else: ?>
                                            <button class="sold-out-btn" disabled style="flex: 1;">
                                                😔 Hết hàng
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($product['stock'] > 0): ?>
                                        <div class="stock-status in-stock">✅ Còn <?php echo $product['stock']; ?> sản phẩm</div>
                                    <?php else: ?>
                                        <div class="stock-status out-of-stock">❌ Hết hàng</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (empty($featured_products)): ?>
                    <div style="text-align: center; padding: 3rem; background: white; border-radius: 15px; margin-top: 2rem;">
                        <h3>Chưa có sản phẩm nào</h3>
                        <p>Vui lòng thêm sản phẩm để hiển thị tại đây.</p>
                        <a href="products.php" style="display: inline-block; margin-top: 1rem; padding: 1rem 2rem; background: linear-gradient(45deg, var(--primary-green), var(--primary-blue)); color: white; text-decoration: none; border-radius: 10px;">Xem tất cả sản phẩm</a>
                    </div>
                <?php endif; ?>
            </section>
        </main>

        <footer class="footer">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Wibubu</h3>
                    <p>Thương mại điện tử hiện đại với thiết kế Shy Rainbow độc đáo. Mang đến trải nghiệm mua sắm an toàn, tiện lợi và đầy màu sắc cho khách hàng Việt Nam.</p>
                    <div class="footer-trust-indicators">
                        <div class="footer-trust-badge">🔒 Bảo mật SSL</div>
                        <div class="footer-trust-badge">💳 Thanh toán an toàn</div>
                        <div class="footer-trust-badge">🚚 Giao hàng nhanh</div>
                        <div class="footer-trust-badge">⭐ Đánh giá 4.8/5</div>
                    </div>
                    <div class="footer-social-links">
                        <a href="#" class="footer-social-link" title="Facebook">📘</a>
                        <a href="#" class="footer-social-link" title="Instagram">📷</a>
                        <a href="#" class="footer-social-link" title="Twitter">🐦</a>
                        <a href="#" class="footer-social-link" title="YouTube">📺</a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h3>🛍️ Danh Mục</h3>
                    <ul>
                        <li><a href="products.php">Tất cả sản phẩm</a></li>
                        <li><a href="products.php?category=electronics">Điện tử</a></li>
                        <li><a href="products.php?category=fashion">Thời trang</a></li>
                        <li><a href="products.php?category=home">Gia dụng</a></li>
                        <li><a href="products.php?category=beauty">Làm đẹp</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>🔗 Liên Kết</h3>
                    <ul>
                        <li><a href="about.php">Giới thiệu</a></li>
                        <li><a href="contact.php">Liên hệ</a></li>
                        <li><a href="terms.php">Điều khoản sử dụng</a></li>
                        <li><a href="privacy.php">Chính sách bảo mật</a></li>
                        <li><a href="shipping.php">Chính sách giao hàng</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>📞 Hỗ Trợ</h3>
                    <ul>
                        <li><a href="contact.php">Trung tâm hỗ trợ</a></li>
                        <li><a href="faq.php">Câu hỏi thường gặp</a></li>
                        <li><a href="return.php">Chính sách đổi trả</a></li>
                        <li><a href="warranty.php">Bảo hành</a></li>
                        <li><a href="feedback.php">Góp ý</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-copyright">
                <p>&copy; 2025 Wibubu. Thiết kế Shy Rainbow độc quyền. Tất cả quyền được bảo lưu.</p>
                <p>Được phát triển với ❤️ từ Việt Nam | Phiên bản 2.0</p>
            </div>
        </footer>
    </div>

    <!-- Chat Widget -->
    <div id="chat-widget" class="chat-widget">
        <div class="chat-header">
            <h4>Hỗ Trợ Khách Hàng</h4>
            <button id="chat-toggle">💬</button>
        </div>
        <div class="chat-body" id="chat-body">
            <!-- Chat messages will appear here -->
        </div>
        <div class="chat-input">
            <input type="text" id="chat-message" placeholder="Nhập tin nhắn...">
            <button id="send-message">Gửi</button>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>