<?php
require_once '../auth.php';

// Check if user is admin
checkRole(['admin']);

// Get some statistics for dashboard
try {
    // Total users
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users");
    $stmt->execute();
    $total_users = $stmt->fetchColumn();

    // Total products
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM products");
    $stmt->execute();
    $total_products = $stmt->fetchColumn();

    // Total orders
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM orders");
    $stmt->execute();
    $total_orders = $stmt->fetchColumn();

    // Total reviews
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM reviews");
    $stmt->execute();
    $total_reviews = $stmt->fetchColumn();

    // Recent users
    $stmt = $pdo->prepare("SELECT name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent orders (assuming orders table exists)
    $stmt = $pdo->prepare("SELECT id, total, status, created_at FROM orders ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    // Handle errors gracefully
    $total_users = $total_products = $total_orders = $total_reviews = 0;
    $recent_users = $recent_orders = [];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Wibubu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        .admin-layout {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }
        
        .admin-sidebar {
            background: var(--gradient-glass);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.2);
            padding: var(--space-xl);
        }
        
        .admin-logo {
            display: flex;
            align-items: center;
            gap: var(--space-md);
            margin-bottom: var(--space-3xl);
            padding-bottom: var(--space-xl);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .admin-logo img {
            width: 40px;
            height: 40px;
        }
        
        .admin-logo-text {
            font-size: var(--font-size-xl);
            font-weight: 600;
            color: var(--color-neutral-800);
        }
        
        .admin-nav {
            list-style: none;
        }
        
        .admin-nav li {
            margin-bottom: var(--space-sm);
        }
        
        .admin-nav a {
            display: flex;
            align-items: center;
            gap: var(--space-md);
            padding: var(--space-lg);
            border-radius: var(--radius-lg);
            color: var(--color-neutral-700);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .admin-nav a:hover,
        .admin-nav a.active {
            background: rgba(139, 92, 246, 0.1);
            color: var(--color-primary-700);
            transform: translateX(5px);
        }
        
        .admin-main {
            padding: var(--space-2xl);
            background: linear-gradient(135deg, var(--color-primary-50), var(--color-secondary-50));
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--space-3xl);
        }
        
        .admin-title {
            color: var(--color-neutral-800);
            font-size: var(--font-size-4xl);
            font-weight: 700;
        }
        
        .admin-user {
            display: flex;
            align-items: center;
            gap: var(--space-md);
            background: var(--gradient-glass);
            backdrop-filter: blur(20px);
            padding: var(--space-lg) var(--space-xl);
            border-radius: var(--radius-full);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background: var(--gradient-primary);
            border-radius: var(--radius-full);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--space-2xl);
            margin-bottom: var(--space-4xl);
        }
        
        .stat-card {
            background: var(--gradient-glass);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: var(--radius-2xl);
            padding: var(--space-2xl);
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-2xl);
        }
        
        .stat-icon {
            font-size: var(--font-size-5xl);
            margin-bottom: var(--space-lg);
        }
        
        .stat-number {
            font-size: var(--font-size-4xl);
            font-weight: 700;
            color: var(--color-primary-600);
            margin-bottom: var(--space-sm);
        }
        
        .stat-label {
            color: var(--color-neutral-600);
            font-weight: 500;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--space-3xl);
        }
        
        .dashboard-card {
            background: var(--gradient-glass);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: var(--radius-2xl);
            padding: var(--space-2xl);
        }
        
        .card-header {
            display: flex;
            align-items: center;
            gap: var(--space-md);
            margin-bottom: var(--space-2xl);
        }
        
        .card-title {
            font-size: var(--font-size-2xl);
            font-weight: 600;
            color: var(--color-neutral-800);
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th,
        .data-table td {
            padding: var(--space-lg);
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .data-table th {
            background: rgba(139, 92, 246, 0.1);
            color: var(--color-primary-700);
            font-weight: 600;
        }
        
        .role-badge {
            display: inline-block;
            padding: var(--space-xs) var(--space-lg);
            border-radius: var(--radius-full);
            font-size: var(--font-size-sm);
            font-weight: 500;
        }
        
        .role-admin {
            background: rgba(239, 68, 68, 0.1);
            color: var(--color-error-700);
        }
        
        .role-staff {
            background: rgba(59, 130, 246, 0.1);
            color: var(--color-primary-700);
        }
        
        .role-customer {
            background: rgba(34, 197, 94, 0.1);
            color: var(--color-success-700);
        }
        
        .logout-btn {
            background: var(--gradient-error);
            color: white;
            padding: var(--space-md) var(--space-xl);
            border: none;
            border-radius: var(--radius-lg);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="admin-logo">
                <img src="../assets/images/wibubu-logo.png" alt="Wibubu">
                <span class="admin-logo-text">Admin Panel</span>
            </div>
            
            <nav>
                <ul class="admin-nav">
                    <li><a href="dashboard.php" class="active">📊 Dashboard</a></li>
                    <li><a href="users.php">👥 Người dùng</a></li>
                    <li><a href="products.php">📦 Sản phẩm</a></li>
                    <li><a href="orders.php">🛒 Đơn hàng</a></li>
                    <li><a href="promotions.php">🎁 Khuyến mãi</a></li>
                    <li><a href="reviews.php">⭐ Đánh giá</a></li>
                    <li><a href="analytics.php">📈 Thống kê</a></li>
                    <li><a href="settings.php">⚙️ Cài đặt</a></li>
                </ul>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="admin-main">
            <header class="admin-header">
                <h1 class="admin-title">👑 Admin Dashboard</h1>
                <div class="admin-user">
                    <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?></div>
                    <div>
                        <div style="font-weight: 600; color: var(--color-neutral-800);">
                            <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </div>
                        <div style="font-size: var(--font-size-sm); color: var(--color-neutral-600);">
                            <?php echo getRoleDisplayName($_SESSION['user_role']); ?>
                        </div>
                    </div>
                    <form method="POST" action="../auth.php" style="display: inline;">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="action" value="logout">
                        <button type="submit" class="logout-btn">Đăng xuất</button>
                    </form>
                </div>
            </header>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-number"><?php echo number_format($total_users); ?></div>
                    <div class="stat-label">Tổng người dùng</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">📦</div>
                    <div class="stat-number"><?php echo number_format($total_products); ?></div>
                    <div class="stat-label">Tổng sản phẩm</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">🛒</div>
                    <div class="stat-number"><?php echo number_format($total_orders); ?></div>
                    <div class="stat-label">Tổng đơn hàng</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">⭐</div>
                    <div class="stat-number"><?php echo number_format($total_reviews); ?></div>
                    <div class="stat-label">Tổng đánh giá</div>
                </div>
            </div>
            
            <!-- Dashboard Cards -->
            <div class="dashboard-grid">
                <!-- Recent Users -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <span>👥</span>
                        <h3 class="card-title">Người dùng mới</h3>
                    </div>
                    
                    <?php if (!empty($recent_users)): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Tên</th>
                                    <th>Email</th>
                                    <th>Vai trò</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <span class="role-badge role-<?php echo $user['role']; ?>">
                                                <?php echo getRoleDisplayName($user['role']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="text-align: center; color: var(--color-neutral-600); padding: var(--space-2xl);">
                            Chưa có người dùng nào
                        </p>
                    <?php endif; ?>
                </div>
                
                <!-- Recent Orders -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <span>🛒</span>
                        <h3 class="card-title">Đơn hàng gần đây</h3>
                    </div>
                    
                    <?php if (!empty($recent_orders)): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tổng tiền</th>
                                    <th>Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): ?>
                                    <tr>
                                        <td>#<?php echo $order['id']; ?></td>
                                        <td><?php echo number_format($order['total']); ?>đ</td>
                                        <td><?php echo htmlspecialchars($order['status']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="text-align: center; color: var(--color-neutral-600); padding: var(--space-2xl);">
                            Chưa có đơn hàng nào
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>