<?php
session_start();
require_once '../src/models/Database.php';

$database = new Database();
$pdo = $database->getConnection();

// Get categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get products
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

$sql = "SELECT p.*, c.name as category_name FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE 1=1";
$params = [];

if ($category_filter) {
    $sql .= " AND p.category_id = ?";
    $params[] = $category_filter;
}

if ($search) {
    $sql .= " AND (LOWER(p.name) LIKE LOWER(?) OR LOWER(p.description) LIKE LOWER(?))";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY p.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sản Phẩm - Wibubu</title>
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
                    <li><a href="products.php" class="active">Sản Phẩm</a></li>
                    <li><a href="about.php">Giới Thiệu</a></li>
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
            <!-- Enhanced Products Hero -->
            <section class="mykingdom-hero">
                <h1>🌈 Khám Phá Sản Phẩm</h1>
                <p>Tìm kiếm và mua sắm hàng ngàn sản phẩm chất lượng với giá tốt nhất. Bộ sưu tập đa dạng với thiết kế Shy Rainbow độc đáo.</p>
            </section>

            <!-- Enhanced Filters Section -->
            <section class="filters" style="animation: slide-in-up 0.6s ease-out;">
                <div style="text-align: center; margin-bottom: var(--space-2xl);">
                    <h3 style="color: var(--color-neutral-800); margin-bottom: var(--space-lg);">Tìm Kiếm & Lọc Sản Phẩm</h3>
                    <?php if ($search || $category_filter): ?>
                        <p style="color: var(--color-neutral-600); margin-bottom: var(--space-xl);">
                            Hiển thị <?php echo count($products); ?> sản phẩm
                            <?php if ($search): ?>
                                cho "<strong><?php echo htmlspecialchars($search); ?></strong>"
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>
                
                <form method="GET" class="filter-form">
                    <div class="search-box" style="position: relative;">
                        <input type="text" name="search" placeholder="🔍 Tìm kiếm sản phẩm, thương hiệu..." value="<?php echo htmlspecialchars($search); ?>" style="padding-right: var(--space-6xl);">
                        <button type="submit" style="position: absolute; right: 0; top: 0; bottom: 0; padding: 0 var(--space-2xl); background: var(--gradient-primary); border: none; color: var(--color-neutral-0); font-weight: 600; border-radius: 0 var(--radius-xl) var(--radius-xl) 0;">
                            ✨ Tìm Kiếm
                        </button>
                    </div>
                    
                    <div class="category-filter" style="position: relative;">
                        <select name="category" onchange="this.form.submit()" style="appearance: none; background-image: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTIiIGhlaWdodD0iOCIgdmlld0JveD0iMCAwIDEyIDgiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxwYXRoIGQ9Ik0xIDFMNiA2TDExIDEiIHN0cm9rZT0iIzZCNzI4MCIgc3Ryb2tlLXdpZHRoPSIyIiBzdHJva2UtbGluZWNhcD0icm91bmQiIHN0cm9rZS1saW5lam9pbj0icm91bmQiLz4KPC9zdmc+'); background-repeat: no-repeat; background-position: right var(--space-lg) center;">
                            <option value="">🏪 Tất cả danh mục</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                    📂 <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <?php if ($search || $category_filter): ?>
                        <a href="products.php" style="padding: var(--space-xl); background: var(--gradient-glass); color: var(--color-neutral-700); text-decoration: none; border-radius: var(--radius-xl); font-weight: 600; border: 1px solid rgba(255,255,255,0.3); backdrop-filter: blur(10px); transition: all var(--transition-fast);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='var(--shadow-lg)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                            🗑️ Xóa bộ lọc
                        </a>
                    <?php endif; ?>
                </form>
            </section>

            <!-- Enhanced Products Section -->
            <section class="mykingdom-products">
                <?php if (empty($products)): ?>
                    <div style="text-align: center; padding: var(--space-6xl); background: var(--gradient-card); border-radius: var(--radius-2xl); border: 1px solid rgba(255,255,255,0.3); backdrop-filter: blur(15px); box-shadow: var(--shadow-xl); animation: scale-in 0.6s ease-out;">
                        <div style="font-size: var(--text-4xl); margin-bottom: var(--space-2xl);">🔍</div>
                        <h3 style="color: var(--color-neutral-800); margin-bottom: var(--space-lg);">Không Tìm Thấy Sản Phẩm</h3>
                        <p style="color: var(--color-neutral-600); margin-bottom: var(--space-4xl); max-width: 400px; margin-left: auto; margin-right: auto;">Rất tiếc, chúng tôi không tìm thấy sản phẩm nào phù hợp. Hãy thử tìm kiếm với từ khóa khác hoặc duyệt tất cả sản phẩm.</p>
                        <div style="display: flex; gap: var(--space-lg); justify-content: center; flex-wrap: wrap;">
                            <a href="products.php" class="cta-premium" style="padding: var(--space-lg) var(--space-3xl); font-size: var(--text-base);">
                                <span>📂 Xem Tất Cả</span>
                            </a>
                            <a href="index.php" style="padding: var(--space-lg) var(--space-3xl); background: var(--gradient-glass); color: var(--color-neutral-700); text-decoration: none; border-radius: var(--radius-xl); font-weight: 600; border: 1px solid rgba(255,255,255,0.3); backdrop-filter: blur(10px); transition: all var(--transition-fast); display: inline-flex; align-items: center; gap: var(--space-md);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='var(--shadow-lg)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                <span>🏠 Về Trang Chủ</span>
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; margin-bottom: var(--space-6xl);">
                        <h2 style="font-size: var(--text-2xl); color: var(--color-neutral-800); margin-bottom: var(--space-lg);">🛒 Danh Sách Sản Phẩm</h2>
                        <p style="font-size: var(--text-base); color: var(--color-neutral-600);">Hiển thị <strong><?php echo count($products); ?> sản phẩm</strong> phù hợp với lựa chọn của bạn</p>
                    </div>
                    
                    <div class="mykingdom-grid">
                        <?php foreach ($products as $product): ?>
                            <div class="mykingdom-card" style="animation: slide-in-up 0.6s ease-out;">
                                <div class="mykingdom-image">
                                    <a href="product-detail.php?id=<?php echo $product['id']; ?>" class="product-image-link">
                                        <img src="<?php echo $product['image_url'] ?: 'https://via.placeholder.com/300x250?text=' . urlencode($product['name']); ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    </a>
                                    <div class="exclusive-badge">✨ Nổi Bật</div>
                                    <?php if ($product['stock'] < 10 && $product['stock'] > 0): ?>
                                        <div class="promotion-tag">⚡ Sắp hết hàng</div>
                                    <?php elseif (rand(1, 4) == 1): ?>
                                        <div class="promotion-tag">🎁 Ưu đãi hot</div>
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
                                                👁️ Chi Tiết
                                            </a>
                                            <?php if ($product['stock'] > 0): ?>
                                                <button class="add-to-cart-mykingdom" 
                                                        data-product-id="<?php echo $product['id']; ?>"
                                                        data-product-name="<?php echo htmlspecialchars($product['name']); ?>"
                                                        data-product-price="<?php echo $sale_price; ?>"
                                                        data-product-image="<?php echo htmlspecialchars($product['image_url'] ?: 'https://via.placeholder.com/300x250?text=' . urlencode($product['name'])); ?>"
                                                        style="flex: 1;">
                                                    🛒 Thêm giỏ
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
                <?php endif; ?>
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