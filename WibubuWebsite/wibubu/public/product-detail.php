<?php
session_start();
require_once '../src/models/Database.php';

$database = new Database();
$pdo = $database->getConnection();

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    header('Location: products.php');
    exit();
}

// Get product details
$stmt = $pdo->prepare("SELECT p.*, c.name as category_name, c.id as category_id FROM products p 
                       LEFT JOIN categories c ON p.category_id = c.id 
                       WHERE p.id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: products.php');
    exit();
}

// Get related products from same category
$stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p 
                       LEFT JOIN categories c ON p.category_id = c.id 
                       WHERE p.category_id = ? AND p.id != ? 
                       ORDER BY p.created_at DESC LIMIT 4");
$stmt->execute([$product['category_id'], $product_id]);
$related_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Generate dynamic pricing
$sale_price = $product['price'];
$original_price = round($sale_price * 1.3);
$discount_percent = round(((($original_price - $sale_price) / $original_price) * 100));

// Product specifications (sample data)
$specifications = [
    'SKU' => 'WB' . str_pad($product['id'], 3, '0', STR_PAD_LEFT),
    'Danh mục' => $product['category_name'] ?: 'Chưa phân loại',
    'Tình trạng' => $product['stock'] > 0 ? 'Còn hàng' : 'Hết hàng',
    'Số lượng' => $product['stock'] . ' sản phẩm'
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Wibubu</title>
    <meta name="description" content="<?php echo htmlspecialchars(substr($product['description'] ?: $product['name'], 0, 160)); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($product['name']); ?> - Wibubu">
    <meta property="og:description" content="<?php echo htmlspecialchars(substr($product['description'] ?: $product['name'], 0, 160)); ?>">
    <meta property="og:image" content="<?php echo $product['image_url'] ?: 'https://via.placeholder.com/600x400?text=' . urlencode($product['name']); ?>">
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
            <!-- Enhanced Breadcrumb Navigation -->
            <div class="breadcrumb" style="padding: var(--space-xl) 0; margin-bottom: var(--space-2xl); animation: slide-in-up 0.6s ease-out;">
                <div style="background: var(--gradient-glass); padding: var(--space-lg) var(--space-xl); border-radius: var(--radius-xl); border: 1px solid rgba(255,255,255,0.3); backdrop-filter: blur(10px); display: inline-flex; align-items: center; gap: var(--space-lg); font-size: var(--text-sm);">
                    <a href="index.php" style="color: var(--color-neutral-600); text-decoration: none; display: flex; align-items: center; gap: var(--space-sm); transition: color var(--transition-fast);" onmouseover="this.style.color='var(--color-neutral-800)'" onmouseout="this.style.color='var(--color-neutral-600)'">
                        <span>🏠</span> Trang Chủ
                    </a>
                    <span style="color: var(--color-neutral-400);">›</span>
                    <a href="products.php" style="color: var(--color-neutral-600); text-decoration: none; transition: color var(--transition-fast);" onmouseover="this.style.color='var(--color-neutral-800)'" onmouseout="this.style.color='var(--color-neutral-600)'">
                        📂 Sản Phẩm
                    </a>
                    <span style="color: var(--color-neutral-400);">›</span>
                    <span style="color: var(--color-neutral-800); font-weight: 600;"><?php echo htmlspecialchars($product['name']); ?></span>
                </div>
            </div>

            <!-- Product Detail Section -->
            <section class="product-detail-container">
                <div class="product-detail-grid">
                    <!-- Product Images -->
                    <div class="product-images">
                        <div class="main-image">
                            <img id="main-product-image" 
                                 src="<?php echo $product['image_url'] ?: 'https://via.placeholder.com/600x600?text=' . urlencode($product['name']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <?php if ($discount_percent > 0): ?>
                                <div class="discount-badge">-<?php echo $discount_percent; ?>%</div>
                            <?php endif; ?>
                            <div class="exclusive-badge">Độc quyền online</div>
                        </div>
                        
                        <!-- Thumbnail Gallery (for future enhancement) -->
                        <div class="thumbnail-gallery">
                            <div class="thumbnail active">
                                <img src="<?php echo $product['image_url'] ?: 'https://via.placeholder.com/150x150?text=' . urlencode($product['name']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>">
                            </div>
                            <!-- Add more thumbnails here when multiple images are available -->
                        </div>
                    </div>

                    <!-- Product Information -->
                    <div class="product-info">
                        <div class="product-header" style="margin-bottom: var(--space-2xl); animation: slide-in-right 0.7s ease-out;">
                            <div style="margin-bottom: var(--space-lg);">
                                <div style="background: var(--gradient-glass); display: inline-flex; align-items: center; gap: var(--space-sm); padding: var(--space-sm) var(--space-lg); border-radius: var(--radius-full); border: 1px solid rgba(255,255,255,0.3); backdrop-filter: blur(10px); margin-bottom: var(--space-lg);">
                                    <span>✨</span>
                                    <span style="font-size: var(--text-sm); color: var(--color-neutral-700); font-weight: 600;">Sản Phẩm Nổi Bật</span>
                                </div>
                            </div>
                            <h1 style="font-size: var(--text-3xl); font-weight: 700; color: var(--color-neutral-900); margin-bottom: var(--space-lg); line-height: 1.2;"><?php echo htmlspecialchars($product['name']); ?></h1>
                            <div style="display: flex; align-items: center; gap: var(--space-2xl); flex-wrap: wrap; margin-bottom: var(--space-xl);">
                                <div style="background: var(--gradient-glass); padding: var(--space-sm) var(--space-lg); border-radius: var(--radius-lg); border: 1px solid rgba(255,255,255,0.3); backdrop-filter: blur(10px);">
                                    <span style="font-size: var(--text-sm); color: var(--color-neutral-600);">Mã sản phẩm:</span>
                                    <span style="font-weight: 600; color: var(--color-neutral-800); margin-left: var(--space-sm);"><?php echo $specifications['SKU']; ?></span>
                                </div>
                                <div style="background: var(--gradient-glass); padding: var(--space-sm) var(--space-lg); border-radius: var(--radius-lg); border: 1px solid rgba(255,255,255,0.3); backdrop-filter: blur(10px);">
                                    <a href="products.php?category=<?php echo $product['category_id']; ?>" style="text-decoration: none; display: flex; align-items: center; gap: var(--space-sm);">
                                        <span>📂</span>
                                        <span style="font-size: var(--text-sm); color: var(--color-neutral-700); font-weight: 600;"><?php echo htmlspecialchars($product['category_name'] ?: 'Chưa phân loại'); ?></span>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Enhanced Pricing Section -->
                        <div class="product-pricing" style="background: var(--gradient-card); padding: var(--space-2xl); border-radius: var(--radius-xl); border: 1px solid rgba(255,255,255,0.3); backdrop-filter: blur(15px); box-shadow: var(--shadow-xl); margin-bottom: var(--space-2xl); animation: scale-in 0.8s ease-out;">
                            <div style="display: flex; align-items: baseline; gap: var(--space-lg); flex-wrap: wrap; margin-bottom: var(--space-xl);">
                                <span style="font-size: var(--text-4xl); font-weight: 700; color: var(--color-neutral-900);"><?php echo number_format($sale_price, 0, ',', '.'); ?>₫</span>
                                <?php if ($discount_percent > 0): ?>
                                    <span style="font-size: var(--text-lg); color: var(--color-neutral-500); text-decoration: line-through;"><?php echo number_format($original_price, 0, ',', '.'); ?>₫</span>
                                    <span style="background: var(--color-error-500); color: var(--color-neutral-0); padding: var(--space-sm) var(--space-lg); border-radius: var(--radius-full); font-size: var(--text-sm); font-weight: 600;">-<?php echo $discount_percent; ?>%</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($discount_percent > 0): ?>
                                <div style="background: var(--gradient-glass); padding: var(--space-lg); border-radius: var(--radius-lg); border: 1px solid rgba(255,255,255,0.3); backdrop-filter: blur(10px); margin-bottom: var(--space-xl);">
                                    <span style="font-size: var(--text-sm); color: var(--color-neutral-600);">Bạn tiết kiệm được:</span>
                                    <span style="font-size: var(--text-lg); font-weight: 600; color: var(--color-success-600); margin-left: var(--space-sm);"><?php echo number_format($original_price - $sale_price, 0, ',', '.'); ?>₫</span>
                                </div>
                            <?php endif; ?>
                            
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--space-lg);">
                                <div style="background: var(--gradient-glass); padding: var(--space-lg); border-radius: var(--radius-lg); border: 1px solid rgba(255,255,255,0.3); backdrop-filter: blur(10px); text-align: center;">
                                    <div style="font-size: var(--text-lg); margin-bottom: var(--space-sm);">🚚</div>
                                    <div style="font-size: var(--text-sm); color: var(--color-neutral-700); font-weight: 600;">Miễn phí giao hàng</div>
                                    <div style="font-size: var(--text-xs); color: var(--color-neutral-500);">đơn từ 500k</div>
                                </div>
                                <div style="background: var(--gradient-glass); padding: var(--space-lg); border-radius: var(--radius-lg); border: 1px solid rgba(255,255,255,0.3); backdrop-filter: blur(10px); text-align: center;">
                                    <div style="font-size: var(--text-lg); margin-bottom: var(--space-sm);">⚡</div>
                                    <div style="font-size: var(--text-sm); color: var(--color-neutral-700); font-weight: 600;">Giao hàng nhanh</div>
                                    <div style="font-size: var(--text-xs); color: var(--color-neutral-500);">2-4 tiếng</div>
                                </div>
                                <div style="background: var(--gradient-glass); padding: var(--space-lg); border-radius: var(--radius-lg); border: 1px solid rgba(255,255,255,0.3); backdrop-filter: blur(10px); text-align: center;">
                                    <div style="font-size: var(--text-lg); margin-bottom: var(--space-sm);">💳</div>
                                    <div style="font-size: var(--text-sm); color: var(--color-neutral-700); font-weight: 600;">Trả góp 0%</div>
                                    <div style="font-size: var(--text-xs); color: var(--color-neutral-500);">3-12 tháng</div>
                                </div>
                                <div style="background: var(--gradient-glass); padding: var(--space-lg); border-radius: var(--radius-lg); border: 1px solid rgba(255,255,255,0.3); backdrop-filter: blur(10px); text-align: center;">
                                    <div style="font-size: var(--text-lg); margin-bottom: var(--space-sm);">🔄</div>
                                    <div style="font-size: var(--text-sm); color: var(--color-neutral-700); font-weight: 600;">Đổi trả dễ dàng</div>
                                    <div style="font-size: var(--text-xs); color: var(--color-neutral-500);">trong 30 ngày</div>
                                </div>
                            </div>
                        </div>

                        <!-- Enhanced Stock Status -->
                        <div style="margin-bottom: var(--space-2xl);">
                            <?php if ($product['stock'] > 0): ?>
                                <div style="background: var(--gradient-glass); border: 1px solid rgba(34, 197, 94, 0.3); padding: var(--space-lg) var(--space-xl); border-radius: var(--radius-lg); backdrop-filter: blur(10px); display: flex; align-items: center; gap: var(--space-lg);">
                                    <div style="background: var(--color-success-100); padding: var(--space-sm); border-radius: var(--radius-full); display: flex; align-items: center; justify-content: center;">
                                        <span style="font-size: var(--text-lg);">✅</span>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600; color: var(--color-success-700); margin-bottom: var(--space-xs);">Sẵn Sàng Giao Hàng</div>
                                        <div style="font-size: var(--text-sm); color: var(--color-neutral-600);">Còn lại <?php echo $product['stock']; ?> sản phẩm trong kho</div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div style="background: var(--gradient-glass); border: 1px solid rgba(239, 68, 68, 0.3); padding: var(--space-lg) var(--space-xl); border-radius: var(--radius-lg); backdrop-filter: blur(10px); display: flex; align-items: center; gap: var(--space-lg);">
                                    <div style="background: var(--color-error-100); padding: var(--space-sm); border-radius: var(--radius-full); display: flex; align-items: center; justify-content: center;">
                                        <span style="font-size: var(--text-lg);">❌</span>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600; color: var(--color-error-700); margin-bottom: var(--space-xs);">Tạm Hết Hàng</div>
                                        <div style="font-size: var(--text-sm); color: var(--color-neutral-600);">Sản phẩm sẽ sớm được bổ sung</div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Enhanced Add to Cart Section -->
                        <div style="background: var(--gradient-card); padding: var(--space-2xl); border-radius: var(--radius-xl); border: 1px solid rgba(255,255,255,0.3); backdrop-filter: blur(15px); box-shadow: var(--shadow-xl); animation: slide-in-up 0.9s ease-out;">
                            <?php if ($product['stock'] > 0): ?>
                                <div style="margin-bottom: var(--space-2xl);">
                                    <label style="display: block; font-weight: 600; color: var(--color-neutral-800); margin-bottom: var(--space-lg);">Chọn Số Lượng:</label>
                                    <div style="display: flex; align-items: center; gap: var(--space-lg); background: var(--gradient-glass); padding: var(--space-lg); border-radius: var(--radius-lg); border: 1px solid rgba(255,255,255,0.3); backdrop-filter: blur(10px); width: fit-content;">
                                        <button type="button" onclick="decreaseQuantity()" style="background: var(--gradient-primary); border: none; color: var(--color-neutral-0); width: 40px; height: 40px; border-radius: var(--radius-full); font-size: var(--text-lg); font-weight: 600; cursor: pointer; transition: all var(--transition-fast);" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                                            −
                                        </button>
                                        <input type="number" id="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" style="width: 80px; text-align: center; border: none; background: transparent; font-size: var(--text-lg); font-weight: 600; color: var(--color-neutral-800);">
                                        <button type="button" onclick="increaseQuantity()" style="background: var(--gradient-primary); border: none; color: var(--color-neutral-0); width: 40px; height: 40px; border-radius: var(--radius-full); font-size: var(--text-lg); font-weight: 600; cursor: pointer; transition: all var(--transition-fast);" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                                            +
                                        </button>
                                    </div>
                                    <div style="font-size: var(--text-sm); color: var(--color-neutral-600); margin-top: var(--space-sm);">
                                        Tối đa <?php echo $product['stock']; ?> sản phẩm
                                    </div>
                                </div>
                                
                                <div style="display: flex; gap: var(--space-lg); margin-bottom: var(--space-xl);">
                                    <button onclick="addToCartDetail()" class="cta-secondary" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: var(--space-sm);">
                                        <span>🛒</span>
                                        <span>Thêm Giỏ Hàng</span>
                                    </button>
                                    <button onclick="buyNow()" class="cta-premium" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: var(--space-sm);">
                                        <span>⚡</span>
                                        <span>Mua Ngay</span>
                                    </button>
                                </div>
                                
                                <!-- Trust Indicators -->
                                <div style="display: flex; align-items: center; justify-content: center; gap: var(--space-lg); flex-wrap: wrap; padding: var(--space-lg); background: var(--gradient-glass); border-radius: var(--radius-lg); border: 1px solid rgba(255,255,255,0.3); backdrop-filter: blur(10px);">
                                    <div style="display: flex; align-items: center; gap: var(--space-sm); font-size: var(--text-sm); color: var(--color-neutral-700);">
                                        <span>🛡️</span>
                                        <span>Bảo hành chính hãng</span>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: var(--space-sm); font-size: var(--text-sm); color: var(--color-neutral-700);">
                                        <span>🔒</span>
                                        <span>Thanh toán an toàn</span>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: var(--space-sm); font-size: var(--text-sm); color: var(--color-neutral-700);">
                                        <span>📞</span>
                                        <span>Hỗ trợ 24/7</span>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div style="text-align: center;">
                                    <button onclick="notifyWhenAvailable()" style="width: 100%; padding: var(--space-xl); background: var(--gradient-glass); color: var(--color-neutral-700); border: 1px solid rgba(255,255,255,0.3); border-radius: var(--radius-xl); font-weight: 600; cursor: pointer; transition: all var(--transition-fast); backdrop-filter: blur(10px); display: flex; align-items: center; justify-content: center; gap: var(--space-sm);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='var(--shadow-lg)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                        <span>🔔</span>
                                        <span>Báo Tôi Khi Có Hàng</span>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Product Specifications -->
                        <div class="product-specifications">
                            <h3>Thông tin sản phẩm</h3>
                            <div class="spec-grid">
                                <?php foreach ($specifications as $key => $value): ?>
                                    <div class="spec-item">
                                        <span class="spec-label"><?php echo htmlspecialchars($key); ?>:</span>
                                        <span class="spec-value"><?php echo htmlspecialchars($value); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Product Description -->
                <div class="product-description">
                    <h3>Mô tả sản phẩm</h3>
                    <div class="description-content">
                        <?php if ($product['description']): ?>
                            <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                        <?php else: ?>
                            <p>Sản phẩm chất lượng cao từ Wibubu. Được thiết kế với tình yêu và sự tỉ mỉ trong từng chi tiết. 
                            Chúng tôi cam kết mang đến cho bạn những sản phẩm tốt nhất với giá cả hợp lý nhất.</p>
                            
                            <h4>Đặc điểm nổi bật:</h4>
                            <ul>
                                <li>✨ Chất lượng cao, thiết kế hiện đại</li>
                                <li>🎯 Phù hợp với nhiều đối tượng sử dụng</li>
                                <li>💎 Giá cả cạnh tranh, uy tín đảm bảo</li>
                                <li>🚀 Giao hàng nhanh chóng toàn quốc</li>
                                <li>🛡️ Bảo hành chính hãng, hỗ trợ 24/7</li>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Enhanced Related Products -->
                <?php if (!empty($related_products)): ?>
                    <div style="margin-top: var(--space-6xl); animation: slide-in-up 1.0s ease-out;">
                        <div style="text-align: center; margin-bottom: var(--space-4xl);">
                            <h3 style="font-size: var(--text-2xl); font-weight: 700; color: var(--color-neutral-900); margin-bottom: var(--space-lg);">🔗 Sản Phẩm Liên Quan</h3>
                            <p style="color: var(--color-neutral-600); max-width: 600px; margin: 0 auto;">Khám phá thêm những sản phẩm tương tự có thể bạn sẽ thích</p>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: var(--space-2xl);">
                            <?php foreach ($related_products as $related): ?>
                                <div style="background: var(--gradient-card); border-radius: var(--radius-xl); overflow: hidden; border: 1px solid rgba(255,255,255,0.3); backdrop-filter: blur(15px); box-shadow: var(--shadow-lg); transition: all var(--transition-fast); position: relative;" onmouseover="this.style.transform='translateY(-8px)'; this.style.boxShadow='var(--shadow-2xl)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='var(--shadow-lg)'">
                                    <div style="position: relative; overflow: hidden;">
                                        <a href="product-detail.php?id=<?php echo $related['id']; ?>" style="display: block;">
                                            <img src="<?php echo $related['image_url'] ?: 'https://via.placeholder.com/300x250?text=' . urlencode($related['name']); ?>" 
                                                 alt="<?php echo htmlspecialchars($related['name']); ?>"
                                                 style="width: 100%; height: 200px; object-fit: cover; transition: transform var(--transition-fast);"
                                                 onmouseover="this.style.transform='scale(1.1)'"
                                                 onmouseout="this.style.transform='scale(1)'">
                                        </a>
                                        <div style="position: absolute; top: var(--space-lg); right: var(--space-lg); background: var(--gradient-glass); padding: var(--space-xs) var(--space-sm); border-radius: var(--radius-full); border: 1px solid rgba(255,255,255,0.3); backdrop-filter: blur(10px);">
                                            <span style="font-size: var(--text-xs); color: var(--color-neutral-700); font-weight: 600;">✨ Tương tự</span>
                                        </div>
                                    </div>
                                    
                                    <div style="padding: var(--space-xl);">
                                        <h4 style="margin-bottom: var(--space-lg);">
                                            <a href="product-detail.php?id=<?php echo $related['id']; ?>" 
                                               style="font-size: var(--text-base); font-weight: 600; color: var(--color-neutral-800); text-decoration: none; display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 2; overflow: hidden; line-height: 1.4;">
                                                <?php echo htmlspecialchars($related['name']); ?>
                                            </a>
                                        </h4>
                                        
                                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: var(--space-lg);">
                                            <div style="font-size: var(--text-lg); font-weight: 700; color: var(--color-neutral-900);">
                                                <?php echo number_format($related['price'], 0, ',', '.'); ?>₫
                                            </div>
                                            <div style="font-size: var(--text-sm); color: var(--color-neutral-600);">
                                                <?php echo htmlspecialchars($related['category_name'] ?: 'Sản phẩm'); ?>
                                            </div>
                                        </div>
                                        
                                        <a href="product-detail.php?id=<?php echo $related['id']; ?>" 
                                           style="display: block; width: 100%; padding: var(--space-lg); background: var(--gradient-glass); color: var(--color-neutral-700); text-decoration: none; text-align: center; border-radius: var(--radius-lg); font-weight: 600; border: 1px solid rgba(255,255,255,0.3); backdrop-filter: blur(10px); transition: all var(--transition-fast);" 
                                           onmouseover="this.style.background='var(--gradient-primary)'; this.style.color='var(--color-neutral-0)'" 
                                           onmouseout="this.style.background='var(--gradient-glass)'; this.style.color='var(--color-neutral-700)'">
                                            👁️ Xem Chi Tiết
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
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
    <script>
        // Product detail specific JavaScript
        const productData = {
            id: <?php echo $product['id']; ?>,
            name: <?php echo json_encode($product['name']); ?>,
            price: <?php echo $sale_price; ?>,
            image: <?php echo json_encode($product['image_url'] ?: 'https://via.placeholder.com/300x250?text=' . urlencode($product['name'])); ?>,
            stock: <?php echo $product['stock']; ?>
        };

        function increaseQuantity() {
            const quantityInput = document.getElementById('quantity');
            const currentValue = parseInt(quantityInput.value);
            if (currentValue < productData.stock) {
                quantityInput.value = currentValue + 1;
            }
        }

        function decreaseQuantity() {
            const quantityInput = document.getElementById('quantity');
            const currentValue = parseInt(quantityInput.value);
            if (currentValue > 1) {
                quantityInput.value = currentValue - 1;
            }
        }

        function addToCartDetail() {
            const quantity = parseInt(document.getElementById('quantity').value);
            
            let cart = JSON.parse(localStorage.getItem('cart')) || [];
            const existingItem = cart.find(item => item.id === productData.id);
            
            if (existingItem) {
                existingItem.quantity += quantity;
            } else {
                cart.push({ 
                    id: productData.id, 
                    quantity: quantity,
                    name: productData.name,
                    price: productData.price,
                    image: productData.image
                });
            }
            
            localStorage.setItem('cart', JSON.stringify(cart));
            updateCartCount();
            
            showNotification(`Đã thêm ${quantity} sản phẩm vào giỏ hàng!`);
        }

        function buyNow() {
            addToCartDetail();
            setTimeout(() => {
                window.location.href = 'cart.php';
            }, 1000);
        }

        function notifyWhenAvailable() {
            showNotification('Chúng tôi sẽ thông báo khi sản phẩm có hàng trở lại!');
        }

        // Thumbnail gallery functionality
        document.addEventListener('DOMContentLoaded', function() {
            const thumbnails = document.querySelectorAll('.thumbnail img');
            const mainImage = document.getElementById('main-product-image');
            
            thumbnails.forEach(thumb => {
                thumb.addEventListener('click', function() {
                    mainImage.src = this.src.replace('150x150', '600x600');
                    
                    // Update active thumbnail
                    document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
                    this.parentElement.classList.add('active');
                });
            });
        });
    </script>
</body>
</html>