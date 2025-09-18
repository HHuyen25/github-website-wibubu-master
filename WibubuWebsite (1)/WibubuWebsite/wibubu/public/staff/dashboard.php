<?php
require_once '../auth.php';

// Check if user is staff or admin
checkRole(['staff', 'admin']);

// Get statistics relevant to staff
try {
    // Total products
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM products");
    $stmt->execute();
    $total_products = $stmt->fetchColumn();

    // Total orders today
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM orders WHERE DATE(created_at) = CURRENT_DATE");
    $stmt->execute();
    $orders_today = $stmt->fetchColumn();

    // Pending orders
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM orders WHERE status = 'pending'");
    $stmt->execute();
    $pending_orders = $stmt->fetchColumn();

    // Total reviews
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM reviews");
    $stmt->execute();
    $total_reviews = $stmt->fetchColumn();

    // Recent orders
    $stmt = $pdo->prepare("SELECT id, total, status, created_at FROM orders ORDER BY created_at DESC LIMIT 10");
    $stmt->execute();
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Low stock products (assuming stock column exists)
    $stmt = $pdo->prepare("SELECT id, name, price FROM products ORDER BY created_at DESC LIMIT 10");
    $stmt->execute();
    $recent_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $total_products = $orders_today = $pending_orders = $total_reviews = 0;
    $recent_orders = $recent_products = [];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - Wibubu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Modern Staff Dashboard Layout */
        .staff-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            min-height: 100vh;
            background: linear-gradient(135deg, 
                rgba(255, 182, 193, 0.1),
                rgba(221, 160, 221, 0.1),
                rgba(173, 216, 230, 0.1),
                rgba(255, 218, 185, 0.1)
            );
        }
        
        /* Enhanced Sidebar Design */
        .staff-sidebar {
            background: linear-gradient(145deg, 
                rgba(255, 255, 255, 0.25),
                rgba(255, 255, 255, 0.15)
            );
            backdrop-filter: blur(30px);
            border-right: 1px solid rgba(255, 255, 255, 0.3);
            padding: 2rem 1.5rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        
        .staff-sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg,
                rgba(255, 182, 193, 0.05),
                rgba(221, 160, 221, 0.05)
            );
            border-radius: 0;
        }
        
        .staff-logo {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 3rem;
            padding: 1.5rem;
            background: linear-gradient(135deg, 
                rgba(255, 255, 255, 0.2),
                rgba(255, 255, 255, 0.1)
            );
            border-radius: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
            position: relative;
            z-index: 1;
        }
        
        .staff-logo img {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .staff-logo-text {
            font-size: 1.25rem;
            font-weight: 700;
            background: linear-gradient(135deg, 
                var(--color-secondary-600),
                var(--color-accent-600)
            );
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        /* Modern Navigation */
        .staff-nav {
            list-style: none;
            position: relative;
            z-index: 1;
        }
        
        .staff-nav li {
            margin-bottom: 0.5rem;
        }
        
        .staff-nav a {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.25rem;
            border-radius: 1.25rem;
            color: var(--color-neutral-700);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .staff-nav a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg,
                rgba(59, 130, 246, 0.15),
                rgba(147, 51, 234, 0.15)
            );
            transition: left 0.4s ease;
        }
        
        .staff-nav a:hover::before,
        .staff-nav a.active::before {
            left: 0;
        }
        
        .staff-nav a:hover,
        .staff-nav a.active {
            color: var(--color-primary-600);
            transform: translateX(8px);
            box-shadow: 0 4px 20px rgba(59, 130, 246, 0.2);
        }
        
        .staff-nav a span:first-child {
            font-size: 1.2rem;
        }
        
        /* Main Content Area */
        .staff-main {
            padding: 2rem;
            overflow-y: auto;
        }
        
        /* Header Section */
        .staff-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3rem;
            background: linear-gradient(135deg, 
                rgba(255, 255, 255, 0.2),
                rgba(255, 255, 255, 0.1)
            );
            backdrop-filter: blur(20px);
            padding: 1.5rem 2rem;
            border-radius: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .staff-title {
            font-size: 2.25rem;
            font-weight: 800;
            background: linear-gradient(135deg, 
                var(--color-primary-600),
                var(--color-secondary-600)
            );
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 0;
        }
        
        .staff-user {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: linear-gradient(135deg, 
                rgba(255, 255, 255, 0.3),
                rgba(255, 255, 255, 0.2)
            );
            backdrop-filter: blur(20px);
            padding: 1rem 1.5rem;
            border-radius: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .user-avatar {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, 
                var(--color-secondary-500),
                var(--color-accent-500)
            );
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.1rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        
        /* Enhanced Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .quick-action-btn {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.5rem 2rem;
            background: linear-gradient(135deg, 
                rgba(255, 255, 255, 0.25),
                rgba(255, 255, 255, 0.15)
            );
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 1.5rem;
            color: var(--color-neutral-700);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .quick-action-btn::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, 
                rgba(59, 130, 246, 0.1) 0%,
                transparent 70%
            );
            transition: all 0.4s ease;
            transform: scale(0);
        }
        
        .quick-action-btn:hover::before {
            transform: scale(1);
        }
        
        .quick-action-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            color: var(--color-primary-700);
            border-color: rgba(59, 130, 246, 0.3);
        }
        
        .quick-action-btn span:first-child {
            font-size: 1.5rem;
        }
        
        /* Statistics Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .stat-card {
            background: linear-gradient(135deg, 
                rgba(255, 255, 255, 0.3),
                rgba(255, 255, 255, 0.2)
            );
            backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            border-radius: 2rem;
            padding: 2rem;
            text-align: center;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, 
                var(--color-primary-500),
                var(--color-secondary-500),
                var(--color-accent-500)
            );
        }
        
        .stat-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        }
        
        .stat-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.1));
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, 
                var(--color-secondary-600),
                var(--color-accent-600)
            );
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: var(--color-neutral-600);
            font-weight: 600;
            font-size: 1rem;
        }
        
        /* Dashboard Cards Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 2rem;
        }
        
        .dashboard-card {
            background: linear-gradient(135deg, 
                rgba(255, 255, 255, 0.25),
                rgba(255, 255, 255, 0.15)
            );
            backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 2rem;
            padding: 2rem;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .dashboard-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 45px rgba(0, 0, 0, 0.15);
        }
        
        .card-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .card-header span {
            font-size: 1.5rem;
        }
        
        .card-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--color-neutral-800);
            margin: 0;
        }
        
        /* Enhanced Data Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 1rem;
            overflow: hidden;
        }
        
        .data-table th,
        .data-table td {
            padding: 1rem 1.25rem;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
        }
        
        .data-table th {
            background: linear-gradient(135deg,
                rgba(59, 130, 246, 0.2),
                rgba(147, 51, 234, 0.2)
            );
            color: var(--color-primary-700);
            font-weight: 700;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .data-table tr {
            transition: all 0.3s ease;
        }
        
        .data-table tbody tr:hover {
            background: rgba(59, 130, 246, 0.05);
            transform: scale(1.01);
        }
        
        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .status-pending {
            background: linear-gradient(135deg, 
                rgba(249, 115, 22, 0.15),
                rgba(251, 146, 60, 0.15)
            );
            color: var(--color-warning-700);
            border: 1px solid rgba(249, 115, 22, 0.3);
        }
        
        .status-completed {
            background: linear-gradient(135deg, 
                rgba(34, 197, 94, 0.15),
                rgba(74, 222, 128, 0.15)
            );
            color: var(--color-success-700);
            border: 1px solid rgba(34, 197, 94, 0.3);
        }
        
        .status-cancelled {
            background: linear-gradient(135deg, 
                rgba(239, 68, 68, 0.15),
                rgba(248, 113, 113, 0.15)
            );
            color: var(--color-error-700);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        /* Logout Button */
        .logout-btn {
            background: linear-gradient(135deg, 
                var(--color-error-500),
                var(--color-error-600)
            );
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 1.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
        }
        
        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4);
        }
        
        /* Mobile Navigation Toggle */
        .mobile-nav-toggle {
            display: none;
            background: linear-gradient(135deg, 
                var(--color-primary-500),
                var(--color-secondary-500)
            );
            color: white;
            border: none;
            border-radius: 1rem;
            padding: 0.75rem;
            font-size: 1.25rem;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
            transition: all 0.3s ease;
        }
        
        .mobile-nav-toggle:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
        }
        
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }
        
        .sidebar-overlay.show {
            display: block;
        }
        
        /* Responsive Design */
        @media (max-width: 1024px) {
            .staff-layout {
                grid-template-columns: 1fr;
            }
            
            .mobile-nav-toggle {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
            }
            
            .staff-sidebar {
                position: fixed;
                top: 0;
                left: -100%;
                width: 280px;
                height: 100vh;
                z-index: 1000;
                transition: left 0.3s ease;
            }
            
            .staff-sidebar.show {
                left: 0;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .staff-main {
                padding: 1rem;
            }
            
            .staff-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="staff-layout">
        <!-- Sidebar -->
        <aside class="staff-sidebar">
            <div class="staff-logo">
                <img src="../assets/images/wibubu-logo.png" alt="Wibubu">
                <span class="staff-logo-text">Staff Panel</span>
            </div>
            
            <nav>
                <ul class="staff-nav">
                    <li><a href="dashboard.php" class="active">
                        <span>📊</span>
                        <span>Dashboard</span>
                    </a></li>
                    <li><a href="#" onclick="showSection('products')">
                        <span>📦</span>
                        <span>Quản lý sản phẩm</span>
                    </a></li>
                    <li><a href="#" onclick="showSection('orders')">
                        <span>🛒</span>
                        <span>Đơn hàng</span>
                    </a></li>
                    <li><a href="#" onclick="showSection('promotions')">
                        <span>🎁</span>
                        <span>Khuyến mãi</span>
                    </a></li>
                    <li><a href="#" onclick="showSection('customers')">
                        <span>👥</span>
                        <span>Khách hàng</span>
                    </a></li>
                    <li><a href="#" onclick="showSection('chatbox')">
                        <span>💬</span>
                        <span>Chat hỗ trợ</span>
                    </a></li>
                    <li><a href="#" onclick="showSection('reports')">
                        <span>📈</span>
                        <span>Báo cáo</span>
                    </a></li>
                    <li><a href="../index.php">
                        <span>🏠</span>
                        <span>Trang chủ</span>
                    </a></li>
                </ul>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="staff-main">
            <header class="staff-header">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <button class="mobile-nav-toggle" onclick="toggleSidebar()">
                        <span>☰</span>
                        <span>Menu</span>
                    </button>
                    <h1 class="staff-title">👨‍💼 Staff Dashboard</h1>
                </div>
                <div class="staff-user">
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
            
            <!-- Quick Actions -->
            <div class="quick-actions">
                <a href="#" onclick="showSection('products')" class="quick-action-btn">
                    <span>➕</span>
                    <span>Thêm sản phẩm mới</span>
                </a>
                <a href="#" onclick="showSection('promotions')" class="quick-action-btn">
                    <span>🎁</span>
                    <span>Tạo khuyến mãi</span>
                </a>
                <a href="#" onclick="showSection('orders')" class="quick-action-btn">
                    <span>📋</span>
                    <span>Đơn hàng chờ xử lý</span>
                </a>
                <a href="#" onclick="showSection('chatbox')" class="quick-action-btn">
                    <span>💬</span>
                    <span>Hỗ trợ khách hàng</span>
                </a>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📦</div>
                    <div class="stat-number"><?php echo number_format($total_products); ?></div>
                    <div class="stat-label">Tổng sản phẩm</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">📅</div>
                    <div class="stat-number"><?php echo number_format($orders_today); ?></div>
                    <div class="stat-label">Đơn hàng hôm nay</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-number"><?php echo number_format($pending_orders); ?></div>
                    <div class="stat-label">Đơn hàng chờ xử lý</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">⭐</div>
                    <div class="stat-number"><?php echo number_format($total_reviews); ?></div>
                    <div class="stat-label">Đánh giá sản phẩm</div>
                </div>
            </div>
            
            <!-- Dashboard Cards -->
            <div class="dashboard-grid">
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
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
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
                
                <!-- Recent Products -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <span>📦</span>
                        <h3 class="card-title">Sản phẩm mới nhất</h3>
                    </div>
                    
                    <?php if (!empty($recent_products)): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Tên sản phẩm</th>
                                    <th>Giá</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_products as $product): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td><?php echo number_format($product['price']); ?>đ</td>
                                        <td>
                                            <a href="../product-detail.php?id=<?php echo $product['id']; ?>" 
                                               style="color: var(--color-primary-600); text-decoration: none;">
                                                Xem →
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="text-align: center; color: var(--color-neutral-600); padding: var(--space-2xl);">
                            Chưa có sản phẩm nào
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Mobile Navigation Overlay -->
    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>
    
    <script>
        // Mobile sidebar toggle functionality
        function toggleSidebar() {
            const sidebar = document.querySelector('.staff-sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        }
        
        // Navigation section functionality  
        function showSection(section) {
            // Close mobile sidebar if open
            const sidebar = document.querySelector('.staff-sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            if (sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            }
            
            // Update active navigation state
            document.querySelectorAll('.staff-nav a').forEach(link => {
                link.classList.remove('active');
            });
            
            // Show different content based on section
            const mainContent = document.querySelector('.staff-main');
            
            const sectionContent = {
                'products': `
                    <!-- Product Management Header -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
                        <h2 style="font-size: 2rem; color: var(--color-primary-600); margin: 0;">📦 Quản lý sản phẩm</h2>
                        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                            <button onclick="showAddProductModal()" style="background: linear-gradient(135deg, var(--color-primary-500), var(--color-secondary-500)); color: white; padding: 1rem 1.5rem; border: none; border-radius: 1rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px rgba(59, 130, 246, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(59, 130, 246, 0.3)'">
                                <span>➕</span> Thêm sản phẩm
                            </button>
                            <button onclick="location.reload()" style="background: linear-gradient(135deg, rgba(255, 255, 255, 0.25), rgba(255, 255, 255, 0.15)); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.3); color: var(--color-neutral-700); padding: 1rem 1.5rem; border-radius: 1rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px rgba(0, 0, 0, 0.1)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                <span>🏠</span> Dashboard
                            </button>
                        </div>
                    </div>

                    <!-- Filters and Search -->
                    <div style="background: linear-gradient(135deg, rgba(255, 255, 255, 0.25), rgba(255, 255, 255, 0.15)); backdrop-filter: blur(25px); border: 1px solid rgba(255, 255, 255, 0.3); border-radius: 1.5rem; padding: 1.5rem; margin-bottom: 2rem;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
                            <div>
                                <label style="display: block; font-weight: 600; color: var(--color-neutral-700); margin-bottom: 0.5rem;">🔍 Tìm kiếm</label>
                                <input type="text" id="productSearch" placeholder="Tìm theo tên hoặc mô tả..." style="width: 100%; padding: 0.75rem 1rem; border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 0.75rem; background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(10px);">
                            </div>
                            <div>
                                <label style="display: block; font-weight: 600; color: var(--color-neutral-700); margin-bottom: 0.5rem;">📂 Danh mục</label>
                                <select id="categoryFilter" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 0.75rem; background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(10px);">
                                    <option value="">Tất cả danh mục</option>
                                </select>
                            </div>
                            <div>
                                <label style="display: block; font-weight: 600; color: var(--color-neutral-700); margin-bottom: 0.5rem;">📊 Trạng thái</label>
                                <select id="statusFilter" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 0.75rem; background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(10px);">
                                    <option value="">Tất cả trạng thái</option>
                                    <option value="active">Còn hàng</option>
                                    <option value="inactive">Hết hàng</option>
                                </select>
                            </div>
                            <div>
                                <button onclick="loadProducts()" style="width: 100%; background: linear-gradient(135deg, var(--color-secondary-500), var(--color-accent-500)); color: white; padding: 0.75rem 1rem; border: none; border-radius: 0.75rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                                    Lọc
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Products Grid/Table -->
                    <div id="productsContainer" style="background: linear-gradient(135deg, rgba(255, 255, 255, 0.25), rgba(255, 255, 255, 0.15)); backdrop-filter: blur(25px); border: 1px solid rgba(255, 255, 255, 0.3); border-radius: 2rem; padding: 2rem; margin-bottom: 2rem;">
                        <div style="text-align: center; padding: 2rem;">
                            <div style="font-size: 3rem; margin-bottom: 1rem;">⏳</div>
                            <p style="color: var(--color-neutral-600);">Đang tải dữ liệu...</p>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div id="paginationContainer" style="display: flex; justify-content: center; align-items: center; gap: 1rem; margin-bottom: 2rem;">
                    </div>

                    <!-- Add/Edit Product Modal -->
                    <div id="productModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.7); z-index: 1000; padding: 2rem; overflow-y: auto;" onclick="closeModal(event)">
                        <div style="max-width: 600px; margin: 0 auto; background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.85)); backdrop-filter: blur(30px); border: 1px solid rgba(255, 255, 255, 0.5); border-radius: 2rem; padding: 2rem; position: relative; max-height: 90vh; overflow-y: auto;" onclick="event.stopPropagation()">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 1px solid rgba(255, 255, 255, 0.3); padding-bottom: 1rem;">
                                <h3 id="modalTitle" style="margin: 0; font-size: 1.5rem; color: var(--color-primary-600);">➕ Thêm sản phẩm mới</h3>
                                <button onclick="closeProductModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--color-neutral-500); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;" onmouseover="this.style.background='rgba(239, 68, 68, 0.1)'; this.style.color='var(--color-error-600)'" onmouseout="this.style.background='none'; this.style.color='var(--color-neutral-500)'">✕</button>
                            </div>
                            
                            <form id="productForm" style="display: grid; gap: 1.5rem;">
                                <input type="hidden" id="productId" name="id">
                                
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                    <div>
                                        <label style="display: block; font-weight: 600; color: var(--color-neutral-700); margin-bottom: 0.5rem;">📝 Tên sản phẩm *</label>
                                        <input type="text" id="productName" name="name" required style="width: 100%; padding: 1rem; border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 0.75rem; background: rgba(255, 255, 255, 0.3); backdrop-filter: blur(10px);">
                                    </div>
                                    <div>
                                        <label style="display: block; font-weight: 600; color: var(--color-neutral-700); margin-bottom: 0.5rem;">📂 Danh mục *</label>
                                        <select id="productCategory" name="category_id" required style="width: 100%; padding: 1rem; border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 0.75rem; background: rgba(255, 255, 255, 0.3); backdrop-filter: blur(10px);">
                                            <option value="">Chọn danh mục</option>
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label style="display: block; font-weight: 600; color: var(--color-neutral-700); margin-bottom: 0.5rem;">📄 Mô tả sản phẩm</label>
                                    <textarea id="productDescription" name="description" rows="3" style="width: 100%; padding: 1rem; border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 0.75rem; background: rgba(255, 255, 255, 0.3); backdrop-filter: blur(10px); resize: vertical;"></textarea>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                    <div>
                                        <label style="display: block; font-weight: 600; color: var(--color-neutral-700); margin-bottom: 0.5rem;">💰 Giá (VNĐ) *</label>
                                        <input type="number" id="productPrice" name="price" min="0" step="1000" required style="width: 100%; padding: 1rem; border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 0.75rem; background: rgba(255, 255, 255, 0.3); backdrop-filter: blur(10px);">
                                    </div>
                                    <div>
                                        <label style="display: block; font-weight: 600; color: var(--color-neutral-700); margin-bottom: 0.5rem;">📦 Số lượng *</label>
                                        <input type="number" id="productStock" name="stock" min="0" required style="width: 100%; padding: 1rem; border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 0.75rem; background: rgba(255, 255, 255, 0.3); backdrop-filter: blur(10px);">
                                    </div>
                                </div>

                                <div>
                                    <label style="display: block; font-weight: 600; color: var(--color-neutral-700); margin-bottom: 0.5rem;">🖼️ Hình ảnh sản phẩm</label>
                                    <div style="display: grid; gap: 1rem;">
                                        <input type="url" id="productImageUrl" name="image_url" placeholder="URL hình ảnh hoặc upload file bên dưới" style="width: 100%; padding: 1rem; border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 0.75rem; background: rgba(255, 255, 255, 0.3); backdrop-filter: blur(10px);">
                                        <div style="display: flex; gap: 1rem; align-items: center;">
                                            <input type="file" id="productImageFile" accept="image/*" style="flex: 1;">
                                            <button type="button" onclick="uploadProductImage()" style="background: linear-gradient(135deg, var(--color-accent-500), var(--color-secondary-500)); color: white; padding: 0.75rem 1rem; border: none; border-radius: 0.5rem; cursor: pointer; font-weight: 600;">Upload</button>
                                        </div>
                                        <div id="imagePreview" style="display: none; text-align: center;">
                                            <img id="previewImg" src="" alt="Preview" style="max-width: 200px; max-height: 200px; border-radius: 0.75rem; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);">
                                        </div>
                                    </div>
                                </div>

                                <div id="modalMessage" style="display: none; padding: 1rem; border-radius: 0.75rem; font-weight: 600;"></div>

                                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1rem;">
                                    <button type="button" onclick="closeProductModal()" style="background: linear-gradient(135deg, rgba(255, 255, 255, 0.25), rgba(255, 255, 255, 0.15)); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.3); color: var(--color-neutral-700); padding: 1rem 1.5rem; border-radius: 0.75rem; cursor: pointer; font-weight: 600;">Hủy</button>
                                    <button type="submit" id="submitBtn" style="background: linear-gradient(135deg, var(--color-primary-500), var(--color-secondary-500)); color: white; padding: 1rem 1.5rem; border: none; border-radius: 0.75rem; cursor: pointer; font-weight: 600;">Lưu sản phẩm</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Delete Confirmation Modal -->
                    <div id="deleteModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.7); z-index: 1000; padding: 2rem;" onclick="closeDeleteModal(event)">
                        <div style="max-width: 400px; margin: 10% auto; background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.85)); backdrop-filter: blur(30px); border: 1px solid rgba(255, 255, 255, 0.5); border-radius: 2rem; padding: 2rem; text-align: center;" onclick="event.stopPropagation()">
                            <div style="font-size: 3rem; margin-bottom: 1rem;">⚠️</div>
                            <h3 style="color: var(--color-error-600); margin-bottom: 1rem;">Xác nhận xóa sản phẩm</h3>
                            <p id="deleteMessage" style="color: var(--color-neutral-600); margin-bottom: 2rem;"></p>
                            <div style="display: flex; gap: 1rem; justify-content: center;">
                                <button onclick="closeDeleteModal()" style="background: linear-gradient(135deg, rgba(255, 255, 255, 0.25), rgba(255, 255, 255, 0.15)); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.3); color: var(--color-neutral-700); padding: 1rem 1.5rem; border-radius: 0.75rem; cursor: pointer; font-weight: 600;">Hủy</button>
                                <button onclick="confirmDeleteProduct()" style="background: linear-gradient(135deg, var(--color-error-500), var(--color-error-600)); color: white; padding: 1rem 1.5rem; border: none; border-radius: 0.75rem; cursor: pointer; font-weight: 600;">Xóa</button>
                            </div>
                        </div>
                    </div>
                `,
                'orders': `
                    <!-- Order Management Header -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
                        <h2 style="font-size: 2rem; color: var(--color-primary-600); margin: 0;">🛒 Quản lý đơn hàng</h2>
                        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                            <button onclick="refreshOrders()" style="background: linear-gradient(135deg, var(--color-primary-500), var(--color-secondary-500)); color: white; padding: 1rem 1.5rem; border: none; border-radius: 1rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px rgba(59, 130, 246, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(59, 130, 246, 0.3)'">
                                <span>🔄</span> Refresh
                            </button>
                            <button onclick="showBulkActions()" id="bulkActionsBtn" style="background: linear-gradient(135deg, rgba(255, 255, 255, 0.25), rgba(255, 255, 255, 0.15)); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.3); color: var(--color-neutral-700); padding: 1rem 1.5rem; border-radius: 1rem; font-weight: 600; cursor: pointer; display: none; align-items: center; gap: 0.5rem; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px rgba(0, 0, 0, 0.1)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                <span>⚡</span> Bulk Actions
                            </button>
                            <button onclick="location.reload()" style="background: linear-gradient(135deg, rgba(255, 255, 255, 0.25), rgba(255, 255, 255, 0.15)); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.3); color: var(--color-neutral-700); padding: 1rem 1.5rem; border-radius: 1rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px rgba(0, 0, 0, 0.1)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                <span>🏠</span> Dashboard
                            </button>
                        </div>
                    </div>

                    <!-- Filters and Search -->
                    <div style="background: linear-gradient(135deg, rgba(255, 255, 255, 0.25), rgba(255, 255, 255, 0.15)); backdrop-filter: blur(25px); border: 1px solid rgba(255, 255, 255, 0.3); border-radius: 1.5rem; padding: 1.5rem; margin-bottom: 2rem;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; align-items: center;">
                            <input type="text" id="orderSearch" placeholder="🔍 Tìm theo mã đơn hàng hoặc khách hàng..." style="padding: 0.75rem 1rem; border: 1px solid rgba(255, 255, 255, 0.3); border-radius: 0.75rem; background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); color: var(--color-neutral-700); font-weight: 500;" />
                            
                            <select id="statusFilter" style="padding: 0.75rem 1rem; border: 1px solid rgba(255, 255, 255, 0.3); border-radius: 0.75rem; background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); color: var(--color-neutral-700); font-weight: 500;">
                                <option value="">Tất cả trạng thái</option>
                                <option value="pending">🟠 Pending</option>
                                <option value="processing">🔵 Processing</option>
                                <option value="completed">🟢 Completed</option>
                                <option value="cancelled">🔴 Cancelled</option>
                            </select>
                            
                            <select id="sortFilter" style="padding: 0.75rem 1rem; border: 1px solid rgba(255, 255, 255, 0.3); border-radius: 0.75rem; background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); color: var(--color-neutral-700); font-weight: 500;">
                                <option value="created_at_desc">🕒 Mới nhất</option>
                                <option value="created_at_asc">🕐 Cũ nhất</option>
                                <option value="total_amount_desc">💰 Giá cao nhất</option>
                                <option value="total_amount_asc">💳 Giá thấp nhất</option>
                                <option value="customer_name_asc">👤 Tên A-Z</option>
                                <option value="customer_name_desc">👤 Tên Z-A</option>
                            </select>
                        </div>
                    </div>

                    <!-- Bulk Actions Panel -->
                    <div id="bulkActionsPanel" style="display: none; background: linear-gradient(135deg, rgba(255, 255, 255, 0.25), rgba(255, 255, 255, 0.15)); backdrop-filter: blur(25px); border: 1px solid rgba(255, 255, 255, 0.3); border-radius: 1.5rem; padding: 1.5rem; margin-bottom: 2rem;">
                        <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                            <span style="color: var(--color-neutral-700); font-weight: 600;">With selected orders:</span>
                            <button onclick="bulkUpdateStatus('processing')" style="background: linear-gradient(135deg, var(--color-info-500), var(--color-info-600)); color: white; padding: 0.5rem 1rem; border: none; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 600; cursor: pointer;">Mark Processing</button>
                            <button onclick="bulkUpdateStatus('completed')" style="background: linear-gradient(135deg, var(--color-success-500), var(--color-success-600)); color: white; padding: 0.5rem 1rem; border: none; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 600; cursor: pointer;">Mark Completed</button>
                            <button onclick="exportOrders()" style="background: linear-gradient(135deg, var(--color-secondary-500), var(--color-accent-500)); color: white; padding: 0.5rem 1rem; border: none; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 600; cursor: pointer;">Export CSV</button>
                            <button onclick="hideBulkActions()" style="background: linear-gradient(135deg, rgba(255, 255, 255, 0.25), rgba(255, 255, 255, 0.15)); color: var(--color-neutral-700); padding: 0.5rem 1rem; border: 1px solid rgba(255, 255, 255, 0.3); border-radius: 0.5rem; font-size: 0.875rem; font-weight: 600; cursor: pointer;">Cancel</button>
                        </div>
                    </div>

                    <!-- Orders Container -->
                    <div id="ordersContainer" style="background: linear-gradient(135deg, rgba(255, 255, 255, 0.25), rgba(255, 255, 255, 0.15)); backdrop-filter: blur(25px); border: 1px solid rgba(255, 255, 255, 0.3); border-radius: 2rem; padding: 2rem; margin-bottom: 2rem;">
                        <div style="text-align: center; padding: 2rem; color: var(--color-neutral-600);">
                            <div style="font-size: 3rem; margin-bottom: 1rem;">📦</div>
                            <p>Loading orders...</p>
                        </div>
                    </div>

                    <!-- Pagination Container -->
                    <div id="ordersPaginationContainer" style="display: flex; justify-content: center; margin-bottom: 2rem;"></div>

                    <!-- Order Detail Modal -->
                    <div id="orderDetailModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.7); z-index: 1000; padding: 2rem; overflow-y: auto;" onclick="closeOrderModal(event)">
                        <div style="max-width: 900px; margin: 2rem auto; background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.85)); backdrop-filter: blur(30px); border: 1px solid rgba(255, 255, 255, 0.5); border-radius: 2rem; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);" onclick="event.stopPropagation()">
                            <!-- Modal Header -->
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 2rem 2rem 1rem; border-bottom: 1px solid rgba(255, 255, 255, 0.2);">
                                <h3 id="orderModalTitle" style="color: var(--color-primary-600); font-size: 1.5rem; font-weight: 700; margin: 0;">Order Details</h3>
                                <button onclick="closeOrderModal()" style="background: none; border: none; font-size: 1.5rem; color: var(--color-neutral-500); cursor: pointer; padding: 0.5rem; border-radius: 0.5rem; transition: all 0.3s ease;" onmouseover="this.style.background='rgba(255, 255, 255, 0.3)'" onmouseout="this.style.background='none'">×</button>
                            </div>
                            
                            <!-- Modal Content -->
                            <div id="orderModalContent" style="padding: 2rem;">
                                <!-- Content will be loaded dynamically -->
                            </div>
                            
                            <!-- Status Update Section -->
                            <div style="padding: 0 2rem 2rem; border-top: 1px solid rgba(255, 255, 255, 0.2);">
                                <h4 style="color: var(--color-secondary-600); margin-bottom: 1rem;">Update Order Status</h4>
                                <form id="statusUpdateForm" style="display: flex; gap: 1rem; align-items: end; flex-wrap: wrap;">
                                    <div style="flex: 1; min-width: 200px;">
                                        <label style="display: block; color: var(--color-neutral-700); font-weight: 600; margin-bottom: 0.5rem;">New Status:</label>
                                        <select id="newStatus" required style="width: 100%; padding: 0.75rem; border: 1px solid rgba(255, 255, 255, 0.3); border-radius: 0.75rem; background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); color: var(--color-neutral-700);">
                                            <option value="">Choose status...</option>
                                            <option value="pending">🟠 Pending</option>
                                            <option value="processing">🔵 Processing</option>
                                            <option value="completed">🟢 Completed</option>
                                            <option value="cancelled">🔴 Cancelled</option>
                                        </select>
                                    </div>
                                    <div style="flex: 2; min-width: 250px;">
                                        <label style="display: block; color: var(--color-neutral-700); font-weight: 600; margin-bottom: 0.5rem;">Note (optional):</label>
                                        <input type="text" id="statusNote" placeholder="Add a note about this status change..." style="width: 100%; padding: 0.75rem; border: 1px solid rgba(255, 255, 255, 0.3); border-radius: 0.75rem; background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); color: var(--color-neutral-700);" />
                                    </div>
                                    <button type="submit" style="background: linear-gradient(135deg, var(--color-primary-500), var(--color-secondary-500)); color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 0.75rem; font-weight: 600; cursor: pointer; white-space: nowrap;">Update Status</button>
                                </form>
                            </div>
                            
                            <!-- Add Note Section -->
                            <div style="padding: 0 2rem 2rem; border-top: 1px solid rgba(255, 255, 255, 0.2);">
                                <h4 style="color: var(--color-secondary-600); margin-bottom: 1rem;">Add Staff Note</h4>
                                <form id="addNoteForm" style="display: flex; gap: 1rem; align-items: end; flex-wrap: wrap;">
                                    <div style="flex: 1; min-width: 300px;">
                                        <textarea id="staffNote" placeholder="Add internal note for staff communication..." required style="width: 100%; height: 80px; padding: 0.75rem; border: 1px solid rgba(255, 255, 255, 0.3); border-radius: 0.75rem; background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); color: var(--color-neutral-700); resize: vertical;"></textarea>
                                    </div>
                                    <button type="submit" style="background: linear-gradient(135deg, var(--color-accent-500), var(--color-secondary-500)); color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 0.75rem; font-weight: 600; cursor: pointer; white-space: nowrap;">Add Note</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Cancel Order Modal -->
                    <div id="cancelOrderModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.7); z-index: 1001; padding: 2rem;" onclick="closeCancelModal(event)">
                        <div style="max-width: 500px; margin: 10% auto; background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.85)); backdrop-filter: blur(30px); border: 1px solid rgba(255, 255, 255, 0.5); border-radius: 2rem; padding: 2rem; text-align: center;" onclick="event.stopPropagation()">
                            <div style="font-size: 3rem; margin-bottom: 1rem;">⚠️</div>
                            <h3 style="color: var(--color-error-600); margin-bottom: 1rem;">Cancel Order</h3>
                            <p id="cancelMessage" style="color: var(--color-neutral-600); margin-bottom: 2rem;"></p>
                            <form id="cancelOrderForm">
                                <textarea id="cancelReason" placeholder="Reason for cancellation (optional)..." style="width: 100%; height: 80px; padding: 0.75rem; border: 1px solid rgba(255, 255, 255, 0.3); border-radius: 0.75rem; background: rgba(255, 255, 255, 0.1); margin-bottom: 1.5rem; resize: vertical;"></textarea>
                                <div style="display: flex; gap: 1rem; justify-content: center;">
                                    <button type="button" onclick="closeCancelModal()" style="background: linear-gradient(135deg, rgba(255, 255, 255, 0.25), rgba(255, 255, 255, 0.15)); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.3); color: var(--color-neutral-700); padding: 1rem 1.5rem; border-radius: 0.75rem; cursor: pointer; font-weight: 600;">Cancel</button>
                                    <button type="submit" style="background: linear-gradient(135deg, var(--color-error-500), var(--color-error-600)); color: white; padding: 1rem 1.5rem; border: none; border-radius: 0.75rem; cursor: pointer; font-weight: 600;">Confirm Cancellation</button>
                                </div>
                            </form>
                        </div>
                    </div>
                `,
                'promotions': `
                    <!-- Promotions Management Header -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
                        <h2 style="font-size: 2rem; color: var(--color-primary-600); margin: 0;">🎁 Quản lý khuyến mãi</h2>
                        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                            <button onclick="showAddPromotionModal()" style="background: linear-gradient(135deg, var(--color-primary-500), var(--color-secondary-500)); color: white; padding: 1rem 1.5rem; border: none; border-radius: 1rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px rgba(59, 130, 246, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(59, 130, 246, 0.3)'">
                                <span>🎯</span> Tạo khuyến mãi
                            </button>
                            <button onclick="showFlashSaleModal()" style="background: linear-gradient(135deg, var(--color-orange-500), var(--color-pink-500)); color: white; padding: 1rem 1.5rem; border: none; border-radius: 1rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(255, 165, 0, 0.3);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px rgba(255, 165, 0, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(255, 165, 0, 0.3)'">
                                <span>⚡</span> Flash Sale
                            </button>
                            <button onclick="showPromotionAnalytics()" style="background: linear-gradient(135deg, var(--color-green-500), var(--color-blue-500)); color: white; padding: 1rem 1.5rem; border: none; border-radius: 1rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px rgba(34, 197, 94, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(34, 197, 94, 0.3)'">
                                <span>📊</span> Thống kê
                            </button>
                            <button onclick="location.reload()" style="background: linear-gradient(135deg, rgba(255, 255, 255, 0.25), rgba(255, 255, 255, 0.15)); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.3); color: var(--color-neutral-700); padding: 1rem 1.5rem; border-radius: 1rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px rgba(0, 0, 0, 0.1)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                <span>🏠</span> Dashboard
                            </button>
                        </div>
                    </div>

                    <!-- Quick Stats Cards -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;" id="promotionsStatsContainer">
                        <!-- Stats will be loaded here -->
                    </div>

                    <!-- Filters and Search -->
                    <div style="background: linear-gradient(135deg, rgba(255, 255, 255, 0.25), rgba(255, 255, 255, 0.15)); backdrop-filter: blur(25px); border: 1px solid rgba(255, 255, 255, 0.3); border-radius: 1.5rem; padding: 1.5rem; margin-bottom: 2rem;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
                            <div>
                                <label style="display: block; font-weight: 600; color: var(--color-neutral-700); margin-bottom: 0.5rem;">🔍 Tìm kiếm</label>
                                <input type="text" id="promotionSearch" placeholder="Tìm theo tên khuyến mãi..." style="width: 100%; padding: 0.75rem 1rem; border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 0.75rem; background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(10px);">
                            </div>
                            <div>
                                <label style="display: block; font-weight: 600; color: var(--color-neutral-700); margin-bottom: 0.5rem;">🎯 Loại khuyến mãi</label>
                                <select id="promotionTypeFilter" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 0.75rem; background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(10px);">
                                    <option value="">Tất cả loại</option>
                                    <option value="percentage">Giảm theo %</option>
                                    <option value="fixed_amount">Giảm cố định</option>
                                    <option value="buy_one_get_one">Mua 1 tặng 1</option>
                                    <option value="flash_sale">Flash Sale</option>
                                    <option value="coupon_code">Mã giảm giá</option>
                                </select>
                            </div>
                            <div>
                                <label style="display: block; font-weight: 600; color: var(--color-neutral-700); margin-bottom: 0.5rem;">📊 Trạng thái</label>
                                <select id="promotionStatusFilter" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 0.75rem; background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(10px);">
                                    <option value="">Tất cả trạng thái</option>
                                    <option value="active">Đang hoạt động</option>
                                    <option value="inactive">Tạm dừng</option>
                                    <option value="scheduled">Đã lên lịch</option>
                                    <option value="expired">Đã hết hạn</option>
                                    <option value="draft">Bản nháp</option>
                                </select>
                            </div>
                            <div>
                                <button onclick="loadPromotions()" style="width: 100%; background: linear-gradient(135deg, var(--color-secondary-500), var(--color-accent-500)); color: white; padding: 0.75rem 1rem; border: none; border-radius: 0.75rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                                    Lọc
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Promotions Grid/Table -->
                    <div id="promotionsContainer" style="background: linear-gradient(135deg, rgba(255, 255, 255, 0.25), rgba(255, 255, 255, 0.15)); backdrop-filter: blur(25px); border: 1px solid rgba(255, 255, 255, 0.3); border-radius: 2rem; padding: 2rem; margin-bottom: 2rem;">
                        <div style="text-align: center; padding: 2rem;">
                            <div style="font-size: 3rem; margin-bottom: 1rem;">⏳</div>
                            <p style="color: var(--color-neutral-600);">Đang tải dữ liệu khuyến mãi...</p>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div id="promotionsPaginationContainer" style="display: flex; justify-content: center; align-items: center; gap: 1rem; margin-bottom: 2rem;">
                    </div>

                    <!-- Add/Edit Promotion Modal -->
                    <div id="promotionModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.7); z-index: 1000; padding: 2rem; overflow-y: auto;" onclick="closeModal(event)">
                        <div style="max-width: 800px; margin: 0 auto; background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.85)); backdrop-filter: blur(30px); border: 1px solid rgba(255, 255, 255, 0.5); border-radius: 2rem; padding: 2rem; position: relative; max-height: 90vh; overflow-y: auto;" onclick="event.stopPropagation()">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 1px solid rgba(255, 255, 255, 0.3); padding-bottom: 1rem;">
                                <h3 id="promotionModalTitle" style="margin: 0; font-size: 1.5rem; color: var(--color-primary-600);">🎯 Tạo khuyến mãi mới</h3>
                                <button onclick="closePromotionModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--color-neutral-500); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;" onmouseover="this.style.background='rgba(239, 68, 68, 0.1)'; this.style.color='var(--color-error-600)'" onmouseout="this.style.background='none'; this.style.color='var(--color-neutral-500)'">✕</button>
                            </div>
                            
                            <form id="promotionForm" style="display: grid; gap: 1.5rem;">
                                <input type="hidden" id="promotionId" name="id">
                                
                                <!-- Basic Information -->
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                    <div>
                                        <label style="display: block; font-weight: 600; color: var(--color-neutral-700); margin-bottom: 0.5rem;">📝 Tên khuyến mãi *</label>
                                        <input type="text" id="promotionName" name="name" required style="width: 100%; padding: 1rem; border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 0.75rem; background: rgba(255, 255, 255, 0.3); backdrop-filter: blur(10px);">
                                    </div>
                                    <div>
                                        <label style="display: block; font-weight: 600; color: var(--color-neutral-700); margin-bottom: 0.5rem;">🎯 Loại khuyến mãi *</label>
                                        <select id="promotionType" name="type" required style="width: 100%; padding: 1rem; border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 0.75rem; background: rgba(255, 255, 255, 0.3); backdrop-filter: blur(10px);" onchange="updatePromotionTypeFields()">
                                            <option value="">Chọn loại khuyến mãi</option>
                                            <option value="percentage">Giảm theo phần trăm</option>
                                            <option value="fixed_amount">Giảm số tiền cố định</option>
                                            <option value="buy_one_get_one">Mua 1 tặng 1</option>
                                            <option value="flash_sale">Flash Sale</option>
                                            <option value="coupon_code">Mã giảm giá</option>
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label style="display: block; font-weight: 600; color: var(--color-neutral-700); margin-bottom: 0.5rem;">📄 Mô tả khuyến mãi</label>
                                    <textarea id="promotionDescription" name="description" rows="3" style="width: 100%; padding: 1rem; border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 0.75rem; background: rgba(255, 255, 255, 0.3); backdrop-filter: blur(10px); resize: vertical;"></textarea>
                                </div>

                                <!-- Discount Configuration -->
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                                    <div>
                                        <label style="display: block; font-weight: 600; color: var(--color-neutral-700); margin-bottom: 0.5rem;">💰 Loại giảm giá *</label>
                                        <select id="discountType" name="discount_type" required style="width: 100%; padding: 1rem; border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 0.75rem; background: rgba(255, 255, 255, 0.3); backdrop-filter: blur(10px);">
                                            <option value="percentage">Phần trăm (%)</option>
                                            <option value="fixed">Số tiền cố định</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label style="display: block; font-weight: 600; color: var(--color-neutral-700); margin-bottom: 0.5rem;">🔢 Giá trị giảm *</label>
                                        <input type="number" id="discountValue" name="discount_value" required min="0" step="0.01" style="width: 100%; padding: 1rem; border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 0.75rem; background: rgba(255, 255, 255, 0.3); backdrop-filter: blur(10px);">
                                    </div>
                                    <div>
                                        <label style="display: block; font-weight: 600; color: var(--color-neutral-700); margin-bottom: 0.5rem;">💳 Đơn hàng tối thiểu</label>
                                        <input type="number" id="minimumOrderAmount" name="minimum_order_amount" min="0" step="0.01" style="width: 100%; padding: 1rem; border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 0.75rem; background: rgba(255, 255, 255, 0.3); backdrop-filter: blur(10px);">
                                    </div>
                                </div>

                                <!-- Date Configuration -->
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                    <div>
                                        <label style="display: block; font-weight: 600; color: var(--color-neutral-700); margin-bottom: 0.5rem;">📅 Ngày bắt đầu *</label>
                                        <input type="datetime-local" id="startDate" name="start_date" required style="width: 100%; padding: 1rem; border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 0.75rem; background: rgba(255, 255, 255, 0.3); backdrop-filter: blur(10px);">
                                    </div>
                                    <div>
                                        <label style="display: block; font-weight: 600; color: var(--color-neutral-700); margin-bottom: 0.5rem;">📅 Ngày kết thúc *</label>
                                        <input type="datetime-local" id="endDate" name="end_date" required style="width: 100%; padding: 1rem; border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 0.75rem; background: rgba(255, 255, 255, 0.3); backdrop-filter: blur(10px);">
                                    </div>
                                </div>

                                <!-- Usage Limits -->
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                                    <div>
                                        <label style="display: block; font-weight: 600; color: var(--color-neutral-700); margin-bottom: 0.5rem;">🔢 Giới hạn tổng sử dụng</label>
                                        <input type="number" id="maxUses" name="max_uses" min="1" style="width: 100%; padding: 1rem; border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 0.75rem; background: rgba(255, 255, 255, 0.3); backdrop-filter: blur(10px);" placeholder="Không giới hạn">
                                    </div>
                                    <div>
                                        <label style="display: block; font-weight: 600; color: var(--color-neutral-700); margin-bottom: 0.5rem;">👤 Giới hạn mỗi khách</label>
                                        <input type="number" id="maxUsesPerCustomer" name="max_uses_per_customer" min="1" style="width: 100%; padding: 1rem; border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 0.75rem; background: rgba(255, 255, 255, 0.3); backdrop-filter: blur(10px);" placeholder="Không giới hạn">
                                    </div>
                                    <div>
                                        <label style="display: block; font-weight: 600; color: var(--color-neutral-700); margin-bottom: 0.5rem;">⭐ Độ ưu tiên</label>
                                        <input type="number" id="priority" name="priority" min="1" max="10" value="1" style="width: 100%; padding: 1rem; border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 0.75rem; background: rgba(255, 255, 255, 0.3); backdrop-filter: blur(10px);">
                                    </div>
                                </div>

                                <!-- Product/Category Selection -->
                                <div>
                                    <label style="display: block; font-weight: 600; color: var(--color-neutral-700); margin-bottom: 0.5rem;">🎯 Áp dụng cho</label>
                                    <select id="applyTo" name="apply_to" style="width: 100%; padding: 1rem; border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 0.75rem; background: rgba(255, 255, 255, 0.3); backdrop-filter: blur(10px);" onchange="updateApplyToFields()">
                                        <option value="all_products">Tất cả sản phẩm</option>
                                        <option value="specific_products">Sản phẩm cụ thể</option>
                                        <option value="specific_categories">Danh mục cụ thể</option>
                                    </select>
                                </div>

                                <!-- Product Selection (hidden by default) -->
                                <div id="productSelectionContainer" style="display: none;">
                                    <label style="display: block; font-weight: 600; color: var(--color-neutral-700); margin-bottom: 0.5rem;">📦 Chọn sản phẩm</label>
                                    <div style="border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 0.75rem; background: rgba(255, 255, 255, 0.3); backdrop-filter: blur(10px); padding: 1rem; max-height: 200px; overflow-y: auto;">
                                        <div id="productsList">
                                            <!-- Products will be loaded here -->
                                        </div>
                                    </div>
                                </div>

                                <!-- Category Selection (hidden by default) -->
                                <div id="categorySelectionContainer" style="display: none;">
                                    <label style="display: block; font-weight: 600; color: var(--color-neutral-700); margin-bottom: 0.5rem;">📂 Chọn danh mục</label>
                                    <div style="border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 0.75rem; background: rgba(255, 255, 255, 0.3); backdrop-filter: blur(10px); padding: 1rem; max-height: 200px; overflow-y: auto;">
                                        <div id="categoriesList">
                                            <!-- Categories will be loaded here -->
                                        </div>
                                    </div>
                                </div>

                                <!-- Coupon Generation (for coupon type) -->
                                <div id="couponGenerationContainer" style="display: none; border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 0.75rem; background: rgba(255, 255, 255, 0.3); backdrop-filter: blur(10px); padding: 1rem;">
                                    <h4 style="margin: 0 0 1rem 0; color: var(--color-primary-600);">🎫 Tạo mã giảm giá</h4>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                                        <div>
                                            <label style="display: block; font-weight: 600; color: var(--color-neutral-700); margin-bottom: 0.5rem;">Tiền tố mã</label>
                                            <input type="text" id="couponPrefix" name="coupon_prefix" value="WIBUBU" style="width: 100%; padding: 0.75rem; border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 0.5rem; background: rgba(255, 255, 255, 0.3);">
                                        </div>
                                        <div>
                                            <label style="display: block; font-weight: 600; color: var(--color-neutral-700); margin-bottom: 0.5rem;">Số lượng mã</label>
                                            <input type="number" id="couponCount" name="coupon_count" value="1" min="1" max="100" style="width: 100%; padding: 0.75rem; border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 0.5rem; background: rgba(255, 255, 255, 0.3);">
                                        </div>
                                        <div>
                                            <label style="display: block; font-weight: 600; color: var(--color-neutral-700); margin-bottom: 0.5rem;">Sử dụng/mã</label>
                                            <input type="number" id="couponMaxUses" name="coupon_max_uses" value="1" min="1" style="width: 100%; padding: 0.75rem; border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 0.5rem; background: rgba(255, 255, 255, 0.3);">
                                        </div>
                                    </div>
                                    <div style="margin-top: 1rem;">
                                        <label>
                                            <input type="checkbox" id="generateCoupons" name="generate_coupons" checked style="margin-right: 0.5rem;">
                                            <span style="font-weight: 600; color: var(--color-neutral-700);">Tự động tạo mã giảm giá</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Status Selection -->
                                <div>
                                    <label style="display: block; font-weight: 600; color: var(--color-neutral-700); margin-bottom: 0.5rem;">📊 Trạng thái</label>
                                    <select id="promotionStatus" name="status" style="width: 100%; padding: 1rem; border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 0.75rem; background: rgba(255, 255, 255, 0.3); backdrop-filter: blur(10px);">
                                        <option value="draft">Bản nháp</option>
                                        <option value="active">Kích hoạt ngay</option>
                                        <option value="inactive">Tạm dừng</option>
                                    </select>
                                </div>

                                <!-- Form Actions -->
                                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1rem; border-top: 1px solid rgba(255, 255, 255, 0.3); padding-top: 1rem;">
                                    <button type="button" onclick="closePromotionModal()" style="background: linear-gradient(135deg, rgba(255, 255, 255, 0.25), rgba(255, 255, 255, 0.15)); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.3); color: var(--color-neutral-700); padding: 1rem 2rem; border-radius: 1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">
                                        Hủy
                                    </button>
                                    <button type="submit" style="background: linear-gradient(135deg, var(--color-primary-500), var(--color-secondary-500)); color: white; padding: 1rem 2rem; border: none; border-radius: 1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);">
                                        💾 Lưu khuyến mãi
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Flash Sale Quick Modal -->
                    <div id="flashSaleModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.7); z-index: 1000; padding: 2rem; overflow-y: auto;" onclick="closeModal(event)">
                        <div style="max-width: 600px; margin: 0 auto; background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.85)); backdrop-filter: blur(30px); border: 1px solid rgba(255, 255, 255, 0.5); border-radius: 2rem; padding: 2rem; position: relative;" onclick="event.stopPropagation()">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 1px solid rgba(255, 255, 255, 0.3); padding-bottom: 1rem;">
                                <h3 style="margin: 0; font-size: 1.5rem; color: var(--color-orange-600);">⚡ Tạo Flash Sale</h3>
                                <button onclick="closeFlashSaleModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--color-neutral-500); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;">✕</button>
                            </div>
                            
                            <form id="flashSaleForm" style="display: grid; gap: 1.5rem;">
                                <div>
                                    <label style="display: block; font-weight: 600; color: var(--color-neutral-700); margin-bottom: 0.5rem;">⚡ Tên Flash Sale *</label>
                                    <input type="text" id="flashSaleName" name="name" required style="width: 100%; padding: 1rem; border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 0.75rem; background: rgba(255, 255, 255, 0.3); backdrop-filter: blur(10px);">
                                </div>
                                
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                    <div>
                                        <label style="display: block; font-weight: 600; color: var(--color-neutral-700); margin-bottom: 0.5rem;">💰 Giảm giá (%)</label>
                                        <input type="number" id="flashSaleDiscount" name="discount_value" required min="5" max="90" value="20" style="width: 100%; padding: 1rem; border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 0.75rem; background: rgba(255, 255, 255, 0.3); backdrop-filter: blur(10px);">
                                    </div>
                                    <div>
                                        <label style="display: block; font-weight: 600; color: var(--color-neutral-700); margin-bottom: 0.5rem;">⏱️ Thời lượng (giờ)</label>
                                        <input type="number" id="flashSaleDuration" name="duration_hours" required min="1" max="72" value="6" style="width: 100%; padding: 1rem; border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 0.75rem; background: rgba(255, 255, 255, 0.3); backdrop-filter: blur(10px);">
                                    </div>
                                </div>

                                <div>
                                    <label style="display: block; font-weight: 600; color: var(--color-neutral-700); margin-bottom: 0.5rem;">📦 Sản phẩm áp dụng</label>
                                    <div style="border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 0.75rem; background: rgba(255, 255, 255, 0.3); backdrop-filter: blur(10px); padding: 1rem; max-height: 150px; overflow-y: auto;">
                                        <div id="flashSaleProductsList">
                                            <!-- Products will be loaded here -->
                                        </div>
                                    </div>
                                    <p style="font-size: 0.875rem; color: var(--color-neutral-600); margin-top: 0.5rem;">Để trống để áp dụng cho tất cả sản phẩm</p>
                                </div>

                                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1rem; border-top: 1px solid rgba(255, 255, 255, 0.3); padding-top: 1rem;">
                                    <button type="button" onclick="closeFlashSaleModal()" style="background: linear-gradient(135deg, rgba(255, 255, 255, 0.25), rgba(255, 255, 255, 0.15)); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.3); color: var(--color-neutral-700); padding: 1rem 2rem; border-radius: 1rem; font-weight: 600; cursor: pointer;">
                                        Hủy
                                    </button>
                                    <button type="submit" style="background: linear-gradient(135deg, var(--color-orange-500), var(--color-pink-500)); color: white; padding: 1rem 2rem; border: none; border-radius: 1rem; font-weight: 600; cursor: pointer; box-shadow: 0 4px 15px rgba(255, 165, 0, 0.3);">
                                        ⚡ Tạo Flash Sale
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Analytics Modal -->
                    <div id="analyticsModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.7); z-index: 1000; padding: 2rem; overflow-y: auto;" onclick="closeModal(event)">
                        <div style="max-width: 1000px; margin: 0 auto; background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.85)); backdrop-filter: blur(30px); border: 1px solid rgba(255, 255, 255, 0.5); border-radius: 2rem; padding: 2rem; position: relative; max-height: 90vh; overflow-y: auto;" onclick="event.stopPropagation()">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 1px solid rgba(255, 255, 255, 0.3); padding-bottom: 1rem;">
                                <h3 style="margin: 0; font-size: 1.5rem; color: var(--color-green-600);">📊 Thống kê khuyến mãi</h3>
                                <button onclick="closeAnalyticsModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--color-neutral-500); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;">✕</button>
                            </div>
                            
                            <div id="analyticsContent">
                                <!-- Analytics content will be loaded here -->
                            </div>
                        </div>
                    </div>
                `,
                'customers': `
                    <!-- Customer Management Header -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
                        <h2 style="font-size: 2rem; color: var(--color-primary-600); margin: 0;">👥 Quản lý khách hàng</h2>
                        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                            <button onclick="showCustomerAnalytics()" style="background: linear-gradient(135deg, var(--color-green-500), var(--color-blue-500)); color: white; padding: 1rem 1.5rem; border: none; border-radius: 1rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px rgba(34, 197, 94, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(34, 197, 94, 0.3)'">
                                <span>📊</span> Thống kê khách hàng
                            </button>
                            <button onclick="exportCustomers('csv')" style="background: linear-gradient(135deg, rgba(255, 255, 255, 0.25), rgba(255, 255, 255, 0.15)); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.3); color: var(--color-neutral-700); padding: 1rem 1.5rem; border-radius: 1rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px rgba(0, 0, 0, 0.1)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                <span>📥</span> Xuất dữ liệu
                            </button>
                            <button onclick="location.reload()" style="background: linear-gradient(135deg, rgba(255, 255, 255, 0.25), rgba(255, 255, 255, 0.15)); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.3); color: var(--color-neutral-700); padding: 1rem 1.5rem; border-radius: 1rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px rgba(0, 0, 0, 0.1)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                <span>🏠</span> Dashboard
                            </button>
                        </div>
                    </div>

                    <!-- Customer Analytics Overview -->
                    <div id="customerOverviewStats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                        <!-- Analytics cards will be loaded here -->
                    </div>

                    <!-- Filters and Search -->
                    <div style="background: linear-gradient(135deg, rgba(255, 255, 255, 0.25), rgba(255, 255, 255, 0.15)); backdrop-filter: blur(25px); border: 1px solid rgba(255, 255, 255, 0.3); border-radius: 2rem; padding: 2rem; margin-bottom: 2rem;">
                        <div style="display: grid; grid-template-columns: 1fr auto auto auto; gap: 1rem; align-items: end;">
                            <div>
                                <label style="display: block; font-weight: 600; color: var(--color-neutral-700); margin-bottom: 0.5rem;">🔍 Tìm kiếm khách hàng</label>
                                <input type="text" id="customerSearchInput" placeholder="Tên, email hoặc số điện thoại..." style="width: 100%; padding: 1rem; border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 1rem; background: rgba(255, 255, 255, 0.3); backdrop-filter: blur(10px); transition: all 0.3s ease;" oninput="debounceCustomerSearch()">
                            </div>
                            <div>
                                <label style="display: block; font-weight: 600; color: var(--color-neutral-700); margin-bottom: 0.5rem;">📊 Trạng thái</label>
                                <select id="customerStatusFilter" style="padding: 1rem 1.5rem; border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 1rem; background: rgba(255, 255, 255, 0.3); backdrop-filter: blur(10px); font-weight: 500;" onchange="loadCustomers()">
                                    <option value="">Tất cả trạng thái</option>
                                    <option value="active">Hoạt động</option>
                                    <option value="inactive">Không hoạt động</option>
                                    <option value="VIP">VIP</option>
                                </select>
                            </div>
                            <div>
                                <label style="display: block; font-weight: 600; color: var(--color-neutral-700); margin-bottom: 0.5rem;">💰 Mức chi tiêu</label>
                                <select id="customerSpendingFilter" style="padding: 1rem 1.5rem; border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 1rem; background: rgba(255, 255, 255, 0.3); backdrop-filter: blur(10px); font-weight: 500;" onchange="loadCustomers()">
                                    <option value="">Tất cả mức</option>
                                    <option value="high">Cao (>2M)</option>
                                    <option value="medium">Trung bình (500K-2M)</option>
                                    <option value="low">Thấp (<500K)</option>
                                </select>
                            </div>
                            <div>
                                <label style="display: block; font-weight: 600; color: var(--color-neutral-700); margin-bottom: 0.5rem;">🔃 Sắp xếp</label>
                                <select id="customerSortFilter" style="padding: 1rem 1.5rem; border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 1rem; background: rgba(255, 255, 255, 0.3); backdrop-filter: blur(10px); font-weight: 500;" onchange="loadCustomers()">
                                    <option value="created_at-DESC">Mới nhất</option>
                                    <option value="created_at-ASC">Cũ nhất</option>
                                    <option value="name-ASC">Tên A-Z</option>
                                    <option value="name-DESC">Tên Z-A</option>
                                    <option value="total_spent-DESC">Chi tiêu cao nhất</option>
                                    <option value="total_spent-ASC">Chi tiêu thấp nhất</option>
                                    <option value="last_order-DESC">Đặt hàng gần nhất</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Customer List -->
                    <div style="background: linear-gradient(135deg, rgba(255, 255, 255, 0.25), rgba(255, 255, 255, 0.15)); backdrop-filter: blur(25px); border: 1px solid rgba(255, 255, 255, 0.3); border-radius: 2rem; padding: 2rem; margin-bottom: 2rem;">
                        <!-- Loading State -->
                        <div id="customersLoadingState" style="text-align: center; padding: 3rem; display: none;">
                            <div style="display: inline-block; width: 40px; height: 40px; border: 4px solid rgba(59, 130, 246, 0.3); border-radius: 50%; border-top-color: var(--color-primary-600); animation: spin 1s ease-in-out infinite; margin-bottom: 1rem;"></div>
                            <p style="color: var(--color-neutral-600); margin: 0;">Đang tải danh sách khách hàng...</p>
                        </div>

                        <!-- Error State -->
                        <div id="customersErrorState" style="text-align: center; padding: 3rem; display: none;">
                            <div style="font-size: 3rem; margin-bottom: 1rem;">⚠️</div>
                            <h3 style="color: var(--color-error-600); margin-bottom: 1rem;">Có lỗi xảy ra</h3>
                            <p id="customersErrorMessage" style="color: var(--color-neutral-600); margin-bottom: 2rem;"></p>
                            <button onclick="loadCustomers()" style="background: var(--color-primary-500); color: white; padding: 1rem 2rem; border: none; border-radius: 1rem; cursor: pointer;">Thử lại</button>
                        </div>

                        <!-- Customer Table -->
                        <div id="customersTableContainer" style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse; background: rgba(255, 255, 255, 0.1); border-radius: 1rem; overflow: hidden;">
                                <thead>
                                    <tr style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(147, 51, 234, 0.2));">
                                        <th style="padding: 1.5rem 1rem; text-align: left; font-weight: 700; color: var(--color-primary-700); border-bottom: 1px solid rgba(255, 255, 255, 0.2);">Khách hàng</th>
                                        <th style="padding: 1.5rem 1rem; text-align: left; font-weight: 700; color: var(--color-primary-700); border-bottom: 1px solid rgba(255, 255, 255, 0.2);">Liên hệ</th>
                                        <th style="padding: 1.5rem 1rem; text-align: center; font-weight: 700; color: var(--color-primary-700); border-bottom: 1px solid rgba(255, 255, 255, 0.2);">Trạng thái</th>
                                        <th style="padding: 1.5rem 1rem; text-align: center; font-weight: 700; color: var(--color-primary-700); border-bottom: 1px solid rgba(255, 255, 255, 0.2);">Đơn hàng</th>
                                        <th style="padding: 1.5rem 1rem; text-align: right; font-weight: 700; color: var(--color-primary-700); border-bottom: 1px solid rgba(255, 255, 255, 0.2);">Tổng chi tiêu</th>
                                        <th style="padding: 1.5rem 1rem; text-align: center; font-weight: 700; color: var(--color-primary-700); border-bottom: 1px solid rgba(255, 255, 255, 0.2);">Đăng ký</th>
                                        <th style="padding: 1.5rem 1rem; text-align: center; font-weight: 700; color: var(--color-primary-700); border-bottom: 1px solid rgba(255, 255, 255, 0.2);">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody id="customersTableBody">
                                    <!-- Customer rows will be loaded here -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Empty State -->
                        <div id="customersEmptyState" style="text-align: center; padding: 3rem; display: none;">
                            <div style="font-size: 4rem; margin-bottom: 1rem;">👥</div>
                            <h3 style="color: var(--color-neutral-600); margin-bottom: 1rem;">Không tìm thấy khách hàng</h3>
                            <p style="color: var(--color-neutral-500);">Thử thay đổi bộ lọc hoặc từ khóa tìm kiếm</p>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div id="customersPaginationContainer" style="display: flex; justify-content: center; align-items: center; gap: 1rem; margin-bottom: 2rem;">
                    </div>

                    <!-- Customer Detail Modal -->
                    <div id="customerDetailModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.8); z-index: 1000; padding: 1rem; overflow-y: auto;" onclick="closeModal(event)">
                        <div style="max-width: 1200px; margin: 2rem auto; background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.85)); backdrop-filter: blur(30px); border: 1px solid rgba(255, 255, 255, 0.5); border-radius: 2rem; position: relative; max-height: 90vh; overflow-y: auto;" onclick="event.stopPropagation()">
                            <!-- Customer detail content will be loaded here -->
                        </div>
                    </div>

                    <!-- Customer Analytics Modal -->
                    <div id="customerAnalyticsModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.8); z-index: 1000; padding: 1rem; overflow-y: auto;" onclick="closeModal(event)">
                        <div style="max-width: 1200px; margin: 2rem auto; background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.85)); backdrop-filter: blur(30px); border: 1px solid rgba(255, 255, 255, 0.5); border-radius: 2rem; padding: 2rem; position: relative; max-height: 90vh; overflow-y: auto;" onclick="event.stopPropagation()">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 1px solid rgba(255, 255, 255, 0.3); padding-bottom: 1rem;">
                                <h3 style="margin: 0; font-size: 1.75rem; color: var(--color-primary-600);">📊 Thống kê và phân tích khách hàng</h3>
                                <button onclick="closeCustomerAnalyticsModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--color-neutral-500); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;" onmouseover="this.style.background='rgba(239, 68, 68, 0.1)'; this.style.color='var(--color-error-600)'" onmouseout="this.style.background='none'; this.style.color='var(--color-neutral-500)'">✕</button>
                            </div>
                            <div id="customerAnalyticsContent">
                                <!-- Analytics content will be loaded here -->
                            </div>
                        </div>
                    </div>

                    <!-- Add Note Modal -->
                    <div id="addNoteModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.7); z-index: 1000; padding: 2rem; overflow-y: auto;" onclick="closeModal(event)">
                        <div style="max-width: 600px; margin: 0 auto; background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.85)); backdrop-filter: blur(30px); border: 1px solid rgba(255, 255, 255, 0.5); border-radius: 2rem; padding: 2rem; position: relative;" onclick="event.stopPropagation()">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 1px solid rgba(255, 255, 255, 0.3); padding-bottom: 1rem;">
                                <h3 style="margin: 0; font-size: 1.5rem; color: var(--color-primary-600);">📝 Thêm ghi chú khách hàng</h3>
                                <button onclick="closeAddNoteModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--color-neutral-500); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;">✕</button>
                            </div>
                            
                            <form id="addNoteForm" style="display: grid; gap: 1.5rem;">
                                <input type="hidden" id="noteCustomerId" name="customer_id">
                                
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                    <div>
                                        <label style="display: block; font-weight: 600; color: var(--color-neutral-700); margin-bottom: 0.5rem;">🏷️ Loại ghi chú</label>
                                        <select id="noteType" name="note_type" style="width: 100%; padding: 1rem; border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 0.75rem; background: rgba(255, 255, 255, 0.3); backdrop-filter: blur(10px);">
                                            <option value="internal">Ghi chú nội bộ</option>
                                            <option value="communication">Liên hệ</option>
                                            <option value="support">Hỗ trợ</option>
                                            <option value="status_change">Thay đổi trạng thái</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label style="display: block; font-weight: 600; color: var(--color-neutral-700); margin-bottom: 0.5rem;">⚡ Độ ưu tiên</label>
                                        <select id="notePriority" name="priority" style="width: 100%; padding: 1rem; border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 0.75rem; background: rgba(255, 255, 255, 0.3); backdrop-filter: blur(10px);">
                                            <option value="low">Thấp</option>
                                            <option value="normal">Bình thường</option>
                                            <option value="high">Cao</option>
                                            <option value="urgent">Khẩn cấp</option>
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label style="display: block; font-weight: 600; color: var(--color-neutral-700); margin-bottom: 0.5rem;">📋 Tiêu đề</label>
                                    <input type="text" id="noteTitle" name="title" style="width: 100%; padding: 1rem; border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 0.75rem; background: rgba(255, 255, 255, 0.3); backdrop-filter: blur(10px);" placeholder="Tiêu đề ghi chú (tùy chọn)">
                                </div>

                                <div>
                                    <label style="display: block; font-weight: 600; color: var(--color-neutral-700); margin-bottom: 0.5rem;">📝 Nội dung *</label>
                                    <textarea id="noteContent" name="content" required rows="4" style="width: 100%; padding: 1rem; border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 0.75rem; background: rgba(255, 255, 255, 0.3); backdrop-filter: blur(10px); resize: vertical;" placeholder="Nhập nội dung ghi chú..."></textarea>
                                </div>

                                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1rem; border-top: 1px solid rgba(255, 255, 255, 0.3); padding-top: 1rem;">
                                    <button type="button" onclick="closeAddNoteModal()" style="background: linear-gradient(135deg, rgba(255, 255, 255, 0.25), rgba(255, 255, 255, 0.15)); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.3); color: var(--color-neutral-700); padding: 1rem 2rem; border-radius: 1rem; font-weight: 600; cursor: pointer;">
                                        Hủy
                                    </button>
                                    <button type="submit" style="background: linear-gradient(135deg, var(--color-primary-500), var(--color-secondary-500)); color: white; padding: 1rem 2rem; border: none; border-radius: 1rem; font-weight: 600; cursor: pointer; box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);">
                                        💾 Lưu ghi chú
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                `,
                'chatbox': `
                    <div class="chat-container">
                        <!-- Chat Header with Stats -->
                        <div class="chat-header">
                            <div class="chat-title">
                                <h2><span>💬</span> Chat Support System</h2>
                                <div class="chat-stats" id="chatStats">
                                    <div class="stat-item">
                                        <span class="stat-number" id="activeChats">0</span>
                                        <span class="stat-label">Active</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-number" id="unassignedChats">0</span>
                                        <span class="stat-label">Unassigned</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-number" id="todayMessages">0</span>
                                        <span class="stat-label">Today</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-number" id="avgResponse">0</span>
                                        <span class="stat-label">Avg Response (min)</span>
                                    </div>
                                </div>
                            </div>
                            <div class="chat-actions">
                                <button class="action-btn" onclick="refreshConversations()" title="Refresh">
                                    <span>🔄</span>
                                </button>
                                <button class="action-btn" onclick="toggleSearchBar()" title="Search">
                                    <span>🔍</span>
                                </button>
                                <button class="action-btn primary-btn" onclick="createNewConversation()" title="New Chat">
                                    <span>💬</span> New Chat
                                </button>
                            </div>
                        </div>

                        <!-- Search Bar (Hidden by default) -->
                        <div class="search-bar" id="searchBar" style="display: none;">
                            <div class="search-input-container">
                                <input type="text" id="searchInput" placeholder="Search conversations, customers, or messages...">
                                <button onclick="searchConversations()">Search</button>
                                <button onclick="clearSearch()">Clear</button>
                            </div>
                            <div id="searchResults" class="search-results"></div>
                        </div>

                        <!-- Main Chat Layout -->
                        <div class="chat-layout">
                            <!-- Conversations Sidebar -->
                            <div class="conversations-sidebar">
                                <div class="conversations-header">
                                    <h3>Conversations</h3>
                                    <div class="filter-tabs">
                                        <button class="filter-tab active" data-filter="all">All</button>
                                        <button class="filter-tab" data-filter="open">Open</button>
                                        <button class="filter-tab" data-filter="in_progress">Active</button>
                                        <button class="filter-tab" data-filter="assigned_to_me">My Chats</button>
                                    </div>
                                </div>
                                <div class="conversations-list" id="conversationsList">
                                    <div class="loading-state">Loading conversations...</div>
                                </div>
                                <div class="conversations-pagination" id="conversationsPagination"></div>
                            </div>

                            <!-- Chat Messages Area -->
                            <div class="chat-messages-area">
                                <div class="chat-welcome" id="chatWelcome">
                                    <div class="welcome-content">
                                        <div class="welcome-icon">💬</div>
                                        <h3>Welcome to Chat Support</h3>
                                        <p>Select a conversation from the sidebar to start helping customers</p>
                                        <div class="welcome-stats">
                                            <div class="welcome-stat">
                                                <span>📊</span>
                                                <div>
                                                    <strong>Professional Support</strong>
                                                    <small>Provide excellent customer service</small>
                                                </div>
                                            </div>
                                            <div class="welcome-stat">
                                                <span>⚡</span>
                                                <div>
                                                    <strong>Real-time Chat</strong>
                                                    <small>Instant messaging with customers</small>
                                                </div>
                                            </div>
                                            <div class="welcome-stat">
                                                <span>📋</span>
                                                <div>
                                                    <strong>Customer Context</strong>
                                                    <small>Full order history and profile info</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Active Chat Interface -->
                                <div class="active-chat" id="activeChat" style="display: none;">
                                    <!-- Chat Header -->
                                    <div class="chat-conversation-header" id="chatConversationHeader"></div>
                                    
                                    <!-- Messages Container -->
                                    <div class="messages-container" id="messagesContainer"></div>
                                    
                                    <!-- Message Input -->
                                    <div class="message-input-area">
                                        <div class="quick-replies" id="quickReplies"></div>
                                        <div class="message-input-container">
                                            <div class="message-tools">
                                                <button class="tool-btn" onclick="toggleQuickReplies()" title="Quick Replies">
                                                    <span>⚡</span>
                                                </button>
                                                <button class="tool-btn" onclick="attachFile()" title="Attach File">
                                                    <span>📎</span>
                                                </button>
                                                <button class="tool-btn" onclick="toggleEmoji()" title="Emoji">
                                                    <span>😊</span>
                                                </button>
                                                <input type="file" id="fileInput" style="display: none;" accept=".jpg,.jpeg,.png,.gif,.pdf,.txt">
                                            </div>
                                            <div class="input-wrapper">
                                                <textarea id="messageInput" placeholder="Type your message..." rows="1"></textarea>
                                                <div class="send-options">
                                                    <button type="button" onclick="addInternalNote()" class="internal-note-btn" title="Add Internal Note">
                                                        <span>📝</span>
                                                    </button>
                                                    <button type="button" onclick="sendMessage()" class="send-btn" id="sendBtn">
                                                        <span>📤</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Customer Info Sidebar -->
                            <div class="customer-info-sidebar">
                                <div class="customer-info" id="customerInfo">
                                    <div class="info-placeholder">
                                        <div class="placeholder-icon">👤</div>
                                        <h4>Customer Info</h4>
                                        <p>Select a conversation to view customer details</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <style>
                        .chat-container {
                            max-width: 100%;
                            margin: 0;
                        }

                        .chat-header {
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            background: linear-gradient(135deg, 
                                rgba(255, 255, 255, 0.3),
                                rgba(255, 255, 255, 0.2)
                            );
                            backdrop-filter: blur(25px);
                            border: 1px solid rgba(255, 255, 255, 0.4);
                            border-radius: 1.5rem;
                            padding: 1.5rem 2rem;
                            margin-bottom: 1.5rem;
                            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
                        }

                        .chat-title h2 {
                            margin: 0 0 1rem 0;
                            font-size: 1.75rem;
                            font-weight: 700;
                            color: var(--color-primary-700);
                            display: flex;
                            align-items: center;
                            gap: 0.75rem;
                        }

                        .chat-stats {
                            display: flex;
                            gap: 2rem;
                        }

                        .stat-item {
                            text-align: center;
                        }

                        .stat-number {
                            display: block;
                            font-size: 1.5rem;
                            font-weight: 800;
                            color: var(--color-secondary-600);
                        }

                        .stat-label {
                            font-size: 0.75rem;
                            color: var(--color-neutral-600);
                            text-transform: uppercase;
                            font-weight: 600;
                        }

                        .chat-actions {
                            display: flex;
                            gap: 1rem;
                            align-items: center;
                        }

                        .action-btn {
                            display: flex;
                            align-items: center;
                            gap: 0.5rem;
                            padding: 0.75rem 1rem;
                            background: linear-gradient(135deg, 
                                rgba(255, 255, 255, 0.25),
                                rgba(255, 255, 255, 0.15)
                            );
                            backdrop-filter: blur(20px);
                            border: 1px solid rgba(255, 255, 255, 0.3);
                            border-radius: 1rem;
                            color: var(--color-neutral-700);
                            font-weight: 600;
                            cursor: pointer;
                            transition: all 0.3s ease;
                            font-size: 0.9rem;
                        }

                        .action-btn:hover {
                            transform: translateY(-2px);
                            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
                            color: var(--color-primary-700);
                        }

                        .action-btn.primary-btn {
                            background: linear-gradient(135deg, 
                                var(--color-primary-500),
                                var(--color-secondary-500)
                            );
                            color: white;
                            border-color: transparent;
                        }

                        .search-bar {
                            background: linear-gradient(135deg, 
                                rgba(255, 255, 255, 0.25),
                                rgba(255, 255, 255, 0.15)
                            );
                            backdrop-filter: blur(20px);
                            border: 1px solid rgba(255, 255, 255, 0.3);
                            border-radius: 1.5rem;
                            padding: 1.5rem;
                            margin-bottom: 1.5rem;
                        }

                        .search-input-container {
                            display: flex;
                            gap: 1rem;
                            align-items: center;
                        }

                        .search-input-container input {
                            flex: 1;
                            padding: 0.75rem 1rem;
                            border: 1px solid rgba(255, 255, 255, 0.4);
                            border-radius: 0.75rem;
                            background: rgba(255, 255, 255, 0.3);
                            backdrop-filter: blur(10px);
                        }

                        .search-results {
                            margin-top: 1rem;
                            max-height: 300px;
                            overflow-y: auto;
                        }

                        .chat-layout {
                            display: grid;
                            grid-template-columns: 350px 1fr 320px;
                            gap: 1.5rem;
                            height: 700px;
                        }

                        .conversations-sidebar {
                            background: linear-gradient(135deg, 
                                rgba(255, 255, 255, 0.25),
                                rgba(255, 255, 255, 0.15)
                            );
                            backdrop-filter: blur(25px);
                            border: 1px solid rgba(255, 255, 255, 0.3);
                            border-radius: 1.5rem;
                            overflow: hidden;
                            display: flex;
                            flex-direction: column;
                        }

                        .conversations-header {
                            padding: 1.5rem;
                            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
                        }

                        .conversations-header h3 {
                            margin: 0 0 1rem 0;
                            color: var(--color-primary-700);
                            font-weight: 700;
                        }

                        .filter-tabs {
                            display: flex;
                            gap: 0.5rem;
                        }

                        .filter-tab {
                            padding: 0.5rem 0.75rem;
                            border: 1px solid rgba(255, 255, 255, 0.3);
                            border-radius: 0.5rem;
                            background: rgba(255, 255, 255, 0.2);
                            color: var(--color-neutral-600);
                            font-size: 0.8rem;
                            font-weight: 600;
                            cursor: pointer;
                            transition: all 0.3s ease;
                        }

                        .filter-tab.active,
                        .filter-tab:hover {
                            background: linear-gradient(135deg, 
                                var(--color-primary-500),
                                var(--color-secondary-500)
                            );
                            color: white;
                            border-color: transparent;
                        }

                        .conversations-list {
                            flex: 1;
                            overflow-y: auto;
                            padding: 1rem;
                        }

                        .conversation-item {
                            display: flex;
                            align-items: center;
                            gap: 1rem;
                            padding: 1rem;
                            margin-bottom: 0.5rem;
                            border-radius: 1rem;
                            cursor: pointer;
                            transition: all 0.3s ease;
                            background: rgba(255, 255, 255, 0.1);
                            border: 1px solid rgba(255, 255, 255, 0.2);
                        }

                        .conversation-item:hover,
                        .conversation-item.active {
                            background: linear-gradient(135deg, 
                                rgba(59, 130, 246, 0.15),
                                rgba(147, 51, 234, 0.15)
                            );
                            border-color: rgba(59, 130, 246, 0.3);
                            transform: translateX(5px);
                        }

                        .conversation-avatar {
                            width: 45px;
                            height: 45px;
                            border-radius: 50%;
                            background: linear-gradient(135deg, 
                                var(--color-secondary-500),
                                var(--color-accent-500)
                            );
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            color: white;
                            font-weight: 700;
                            font-size: 1.1rem;
                            flex-shrink: 0;
                        }

                        .conversation-info {
                            flex: 1;
                            min-width: 0;
                        }

                        .conversation-customer {
                            font-weight: 600;
                            color: var(--color-neutral-800);
                            margin-bottom: 0.25rem;
                        }

                        .conversation-last-message {
                            font-size: 0.85rem;
                            color: var(--color-neutral-600);
                            white-space: nowrap;
                            overflow: hidden;
                            text-overflow: ellipsis;
                        }

                        .conversation-meta {
                            text-align: right;
                            flex-shrink: 0;
                        }

                        .conversation-time {
                            font-size: 0.75rem;
                            color: var(--color-neutral-500);
                            margin-bottom: 0.25rem;
                        }

                        .conversation-status {
                            display: inline-block;
                            padding: 0.25rem 0.5rem;
                            border-radius: 0.5rem;
                            font-size: 0.7rem;
                            font-weight: 600;
                            text-transform: uppercase;
                        }

                        .status-open {
                            background: linear-gradient(135deg, 
                                rgba(34, 197, 94, 0.15),
                                rgba(74, 222, 128, 0.15)
                            );
                            color: var(--color-success-700);
                        }

                        .status-in_progress {
                            background: linear-gradient(135deg, 
                                rgba(249, 115, 22, 0.15),
                                rgba(251, 146, 60, 0.15)
                            );
                            color: var(--color-warning-700);
                        }

                        .unread-badge {
                            background: linear-gradient(135deg, 
                                var(--color-error-500),
                                var(--color-error-600)
                            );
                            color: white;
                            border-radius: 50%;
                            width: 20px;
                            height: 20px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            font-size: 0.7rem;
                            font-weight: 700;
                            margin-top: 0.25rem;
                        }

                        .chat-messages-area {
                            background: linear-gradient(135deg, 
                                rgba(255, 255, 255, 0.25),
                                rgba(255, 255, 255, 0.15)
                            );
                            backdrop-filter: blur(25px);
                            border: 1px solid rgba(255, 255, 255, 0.3);
                            border-radius: 1.5rem;
                            display: flex;
                            flex-direction: column;
                            overflow: hidden;
                        }

                        .chat-welcome {
                            flex: 1;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            padding: 3rem;
                        }

                        .welcome-content {
                            text-align: center;
                            max-width: 500px;
                        }

                        .welcome-icon {
                            font-size: 4rem;
                            margin-bottom: 1rem;
                        }

                        .welcome-content h3 {
                            margin: 0 0 0.5rem 0;
                            color: var(--color-primary-700);
                            font-size: 1.75rem;
                        }

                        .welcome-content p {
                            color: var(--color-neutral-600);
                            margin-bottom: 2rem;
                            line-height: 1.6;
                        }

                        .welcome-stats {
                            display: flex;
                            gap: 1rem;
                            justify-content: center;
                            flex-wrap: wrap;
                        }

                        .welcome-stat {
                            display: flex;
                            align-items: center;
                            gap: 0.75rem;
                            padding: 1rem;
                            background: rgba(255, 255, 255, 0.2);
                            border-radius: 1rem;
                            min-width: 140px;
                        }

                        .welcome-stat span {
                            font-size: 1.5rem;
                        }

                        .welcome-stat strong {
                            display: block;
                            color: var(--color-primary-700);
                            font-size: 0.9rem;
                            margin-bottom: 0.25rem;
                        }

                        .welcome-stat small {
                            color: var(--color-neutral-600);
                            font-size: 0.8rem;
                        }

                        .active-chat {
                            height: 100%;
                            display: flex;
                            flex-direction: column;
                        }

                        .chat-conversation-header {
                            padding: 1.5rem;
                            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
                            background: rgba(255, 255, 255, 0.1);
                        }

                        .messages-container {
                            flex: 1;
                            overflow-y: auto;
                            padding: 1rem;
                        }

                        .message-input-area {
                            border-top: 1px solid rgba(255, 255, 255, 0.2);
                            background: rgba(255, 255, 255, 0.1);
                        }

                        .quick-replies {
                            padding: 1rem 1rem 0;
                            display: none;
                            flex-wrap: wrap;
                            gap: 0.5rem;
                        }

                        .quick-reply-btn {
                            padding: 0.5rem 1rem;
                            background: linear-gradient(135deg, 
                                rgba(59, 130, 246, 0.15),
                                rgba(147, 51, 234, 0.15)
                            );
                            border: 1px solid rgba(59, 130, 246, 0.3);
                            border-radius: 1rem;
                            color: var(--color-primary-700);
                            font-size: 0.85rem;
                            cursor: pointer;
                            transition: all 0.3s ease;
                        }

                        .quick-reply-btn:hover {
                            background: linear-gradient(135deg, 
                                var(--color-primary-500),
                                var(--color-secondary-500)
                            );
                            color: white;
                        }

                        .message-input-container {
                            padding: 1rem;
                        }

                        .message-tools {
                            display: flex;
                            gap: 0.5rem;
                            margin-bottom: 0.75rem;
                        }

                        .tool-btn {
                            padding: 0.5rem;
                            background: rgba(255, 255, 255, 0.2);
                            border: 1px solid rgba(255, 255, 255, 0.3);
                            border-radius: 0.75rem;
                            color: var(--color-neutral-700);
                            cursor: pointer;
                            transition: all 0.3s ease;
                        }

                        .tool-btn:hover {
                            background: var(--color-primary-500);
                            color: white;
                        }

                        .input-wrapper {
                            display: flex;
                            gap: 0.75rem;
                            align-items: flex-end;
                        }

                        .input-wrapper textarea {
                            flex: 1;
                            padding: 0.75rem 1rem;
                            border: 1px solid rgba(255, 255, 255, 0.4);
                            border-radius: 1rem;
                            background: rgba(255, 255, 255, 0.3);
                            backdrop-filter: blur(10px);
                            resize: none;
                            font-family: inherit;
                            max-height: 120px;
                        }

                        .send-options {
                            display: flex;
                            gap: 0.5rem;
                        }

                        .internal-note-btn,
                        .send-btn {
                            padding: 0.75rem;
                            border-radius: 1rem;
                            border: none;
                            cursor: pointer;
                            transition: all 0.3s ease;
                        }

                        .internal-note-btn {
                            background: linear-gradient(135deg, 
                                rgba(156, 163, 175, 0.2),
                                rgba(156, 163, 175, 0.1)
                            );
                            color: var(--color-neutral-600);
                            border: 1px solid rgba(156, 163, 175, 0.3);
                        }

                        .internal-note-btn:hover {
                            background: linear-gradient(135deg, 
                                rgba(156, 163, 175, 0.3),
                                rgba(156, 163, 175, 0.2)
                            );
                        }

                        .send-btn {
                            background: linear-gradient(135deg, 
                                var(--color-primary-500),
                                var(--color-secondary-500)
                            );
                            color: white;
                            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
                        }

                        .send-btn:hover {
                            transform: translateY(-2px);
                            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
                        }

                        .customer-info-sidebar {
                            background: linear-gradient(135deg, 
                                rgba(255, 255, 255, 0.25),
                                rgba(255, 255, 255, 0.15)
                            );
                            backdrop-filter: blur(25px);
                            border: 1px solid rgba(255, 255, 255, 0.3);
                            border-radius: 1.5rem;
                            overflow: hidden;
                        }

                        .info-placeholder {
                            padding: 2rem;
                            text-align: center;
                            height: 100%;
                            display: flex;
                            flex-direction: column;
                            align-items: center;
                            justify-content: center;
                        }

                        .placeholder-icon {
                            font-size: 3rem;
                            margin-bottom: 1rem;
                            opacity: 0.6;
                        }

                        .info-placeholder h4 {
                            margin: 0 0 0.5rem 0;
                            color: var(--color-primary-700);
                            font-size: 1.25rem;
                        }

                        .info-placeholder p {
                            color: var(--color-neutral-600);
                            font-size: 0.9rem;
                            line-height: 1.5;
                        }

                        .loading-state {
                            padding: 2rem;
                            text-align: center;
                            color: var(--color-neutral-600);
                        }

                        /* Message Styles */
                        .message {
                            display: flex;
                            margin-bottom: 1rem;
                            animation: slideInUp 0.3s ease;
                        }

                        .message.staff-message {
                            justify-content: flex-end;
                        }

                        .message-bubble {
                            max-width: 70%;
                            padding: 1rem 1.25rem;
                            border-radius: 1.5rem;
                            position: relative;
                            backdrop-filter: blur(10px);
                        }

                        .customer-message .message-bubble {
                            background: linear-gradient(135deg, 
                                rgba(255, 255, 255, 0.9),
                                rgba(255, 255, 255, 0.7)
                            );
                            border: 1px solid rgba(255, 255, 255, 0.4);
                            margin-left: 1rem;
                        }

                        .staff-message .message-bubble {
                            background: linear-gradient(135deg, 
                                var(--color-primary-500),
                                var(--color-secondary-500)
                            );
                            color: white;
                            margin-right: 1rem;
                        }

                        .internal-message .message-bubble {
                            background: linear-gradient(135deg, 
                                rgba(156, 163, 175, 0.2),
                                rgba(156, 163, 175, 0.1)
                            );
                            border: 1px solid rgba(156, 163, 175, 0.3);
                            border-left: 4px solid var(--color-warning-500);
                        }

                        .message-content {
                            margin-bottom: 0.5rem;
                            line-height: 1.5;
                        }

                        .message-meta {
                            display: flex;
                            align-items: center;
                            justify-content: space-between;
                            font-size: 0.75rem;
                            opacity: 0.8;
                        }

                        .message-time {
                            color: inherit;
                        }

                        .message-status {
                            display: flex;
                            align-items: center;
                            gap: 0.25rem;
                        }

                        @keyframes slideInUp {
                            from {
                                opacity: 0;
                                transform: translateY(20px);
                            }
                            to {
                                opacity: 1;
                                transform: translateY(0);
                            }
                        }

                        /* Responsive Design */
                        @media (max-width: 1200px) {
                            .chat-layout {
                                grid-template-columns: 300px 1fr 280px;
                            }
                        }

                        @media (max-width: 1024px) {
                            .chat-layout {
                                grid-template-columns: 1fr;
                                grid-template-rows: auto 1fr auto;
                                height: auto;
                            }
                            
                            .conversations-sidebar,
                            .customer-info-sidebar {
                                height: 300px;
                            }
                            
                            .chat-messages-area {
                                height: 500px;
                            }
                        }

                        @media (max-width: 768px) {
                            .chat-header {
                                flex-direction: column;
                                gap: 1rem;
                                align-items: flex-start;
                            }
                            
                            .chat-stats {
                                gap: 1rem;
                            }
                            
                            .chat-actions {
                                align-self: stretch;
                                justify-content: space-between;
                            }
                            
                            .message-bubble {
                                max-width: 85%;
                            }
                        }
                    </style>
                `,
                'reports': `
                    <div class="reports-container">
                        <div class="reports-header">
                            <h2 style="font-size: 2.5rem; color: var(--color-primary-600); margin: 0; display: flex; align-items: center; gap: 1rem;">
                                📈 Báo cáo & Thống kê
                            </h2>
                            <div class="reports-controls">
                                <div class="date-range-selector">
                                    <label for="reportDateFrom">Từ ngày:</label>
                                    <input type="date" id="reportDateFrom" class="report-date-input">
                                    <label for="reportDateTo">Đến ngày:</label>
                                    <input type="date" id="reportDateTo" class="report-date-input">
                                    <button onclick="refreshReportsData()" class="refresh-btn">🔄 Cập nhật</button>
                                </div>
                                <div class="export-controls">
                                    <button onclick="exportReportData('csv')" class="export-btn">📊 Xuất CSV</button>
                                    <button onclick="exportReportData('pdf')" class="export-btn">📄 Xuất PDF</button>
                                </div>
                            </div>
                        </div>

                        <!-- Real-time KPI Dashboard -->
                        <div class="kpi-dashboard" id="kpiDashboard">
                            <div class="kpi-card">
                                <div class="kpi-icon">💰</div>
                                <div class="kpi-value" id="todayRevenue">0</div>
                                <div class="kpi-label">Doanh thu hôm nay</div>
                                <div class="kpi-change" id="revenueChange">+0%</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-icon">🛒</div>
                                <div class="kpi-value" id="todayOrders">0</div>
                                <div class="kpi-label">Đơn hàng hôm nay</div>
                                <div class="kpi-change" id="ordersChange">+0%</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-icon">👥</div>
                                <div class="kpi-value" id="activeCustomers">0</div>
                                <div class="kpi-label">Khách hàng hoạt động</div>
                                <div class="kpi-change positive">+12%</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-icon">⚠️</div>
                                <div class="kpi-value" id="lowStockProducts">0</div>
                                <div class="kpi-label">Sản phẩm sắp hết</div>
                                <div class="kpi-change negative">Alert</div>
                            </div>
                        </div>

                        <!-- Reports Navigation Tabs -->
                        <div class="reports-tabs">
                            <button class="report-tab active" onclick="switchReportTab('sales')">📊 Doanh thu</button>
                            <button class="report-tab" onclick="switchReportTab('performance')">🎯 Hiệu suất</button>
                            <button class="report-tab" onclick="switchReportTab('customers')">👥 Khách hàng</button>
                            <button class="report-tab" onclick="switchReportTab('inventory')">📦 Kho hàng</button>
                            <button class="report-tab" onclick="switchReportTab('marketing')">📢 Marketing</button>
                        </div>

                        <!-- Sales Dashboard Tab -->
                        <div id="salesReport" class="report-tab-content active">
                            <div class="report-section">
                                <h3>📈 Biểu đồ doanh thu theo thời gian</h3>
                                <div class="chart-controls">
                                    <button class="period-btn active" onclick="setSalesPeriod('daily')">Theo ngày</button>
                                    <button class="period-btn" onclick="setSalesPeriod('weekly')">Theo tuần</button>
                                    <button class="period-btn" onclick="setSalesPeriod('monthly')">Theo tháng</button>
                                    <button class="period-btn" onclick="setSalesPeriod('yearly')">Theo năm</button>
                                </div>
                                <div class="chart-container">
                                    <canvas id="revenueChart"></canvas>
                                </div>
                            </div>

                            <div class="reports-grid">
                                <div class="report-card">
                                    <h4>🏆 Sản phẩm bán chạy</h4>
                                    <div class="chart-container small">
                                        <canvas id="bestProductsChart"></canvas>
                                    </div>
                                    <div id="bestProductsList" class="data-list"></div>
                                </div>
                                <div class="report-card">
                                    <h4>📂 Phân tích danh mục</h4>
                                    <div class="chart-container small">
                                        <canvas id="categoryChart"></canvas>
                                    </div>
                                    <div id="categoryStats" class="stats-grid"></div>
                                </div>
                            </div>

                            <div class="report-card full-width">
                                <h4>👑 Phân khúc khách hàng theo doanh thu</h4>
                                <div class="chart-container medium">
                                    <canvas id="customerSegmentChart"></canvas>
                                </div>
                                <div id="segmentTable" class="data-table-container"></div>
                            </div>
                        </div>

                        <!-- Performance Metrics Tab -->
                        <div id="performanceReport" class="report-tab-content">
                            <div class="reports-grid">
                                <div class="report-card">
                                    <h4>🎯 Tỷ lệ chuyển đổi đơn hàng</h4>
                                    <div id="conversionMetrics" class="metrics-display">
                                        <div class="metric-item">
                                            <span class="metric-label">Tỷ lệ hoàn thành:</span>
                                            <span class="metric-value" id="completionRate">0%</span>
                                        </div>
                                        <div class="metric-item">
                                            <span class="metric-label">Tỷ lệ hủy:</span>
                                            <span class="metric-value" id="cancellationRate">0%</span>
                                        </div>
                                    </div>
                                    <div class="chart-container small">
                                        <canvas id="conversionChart"></canvas>
                                    </div>
                                </div>
                                <div class="report-card">
                                    <h4>💰 Giá trị đơn hàng trung bình</h4>
                                    <div class="chart-container small">
                                        <canvas id="aovChart"></canvas>
                                    </div>
                                    <div id="aovStats" class="stats-display"></div>
                                </div>
                            </div>

                            <div class="report-card full-width">
                                <h4>🌟 Thứ hạng hiệu suất sản phẩm</h4>
                                <div id="productPerformanceTable" class="data-table-container"></div>
                            </div>

                            <div class="report-card full-width">
                                <h4>📅 Xu hướng theo mùa</h4>
                                <div class="chart-container large">
                                    <canvas id="seasonalChart"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Customer Analytics Tab -->
                        <div id="customersReport" class="report-tab-content">
                            <div class="reports-grid">
                                <div class="report-card">
                                    <h4>💎 Giá trị trọn đời khách hàng</h4>
                                    <div class="chart-container small">
                                        <canvas id="clvChart"></canvas>
                                    </div>
                                    <div id="clvStats" class="stats-display"></div>
                                </div>
                                <div class="report-card">
                                    <h4>🎭 Phân khúc khách hàng</h4>
                                    <div class="chart-container small">
                                        <canvas id="customerSegmentationChart"></canvas>
                                    </div>
                                    <div id="segmentationStats" class="stats-display"></div>
                                </div>
                            </div>

                            <div class="report-card full-width">
                                <h4>🛒 Hành vi mua hàng</h4>
                                <div class="charts-row">
                                    <div class="chart-container medium">
                                        <h5>Thời gian mua hàng (Giờ trong ngày)</h5>
                                        <canvas id="purchaseTimeChart"></canvas>
                                    </div>
                                    <div class="chart-container medium">
                                        <h5>Thời gian mua hàng (Ngày trong tuần)</h5>
                                        <canvas id="purchaseDayChart"></canvas>
                                    </div>
                                </div>
                            </div>

                            <div class="reports-grid">
                                <div class="report-card">
                                    <h4>😊 Mức độ hài lòng</h4>
                                    <div id="satisfactionMetrics" class="metrics-display">
                                        <div class="metric-item">
                                            <span class="metric-label">Đánh giá trung bình:</span>
                                            <span class="metric-value">4.2/5</span>
                                        </div>
                                        <div class="metric-item">
                                            <span class="metric-label">Tổng đánh giá:</span>
                                            <span class="metric-value">0</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="report-card">
                                    <h4>📉 Phân tích khách hàng rời bỏ</h4>
                                    <div id="churnMetrics" class="metrics-display">
                                        <div class="metric-item">
                                            <span class="metric-label">Tỷ lệ rời bỏ:</span>
                                            <span class="metric-value" id="churnRate">0%</span>
                                        </div>
                                        <div class="metric-item">
                                            <span class="metric-label">Khách hàng có nguy cơ:</span>
                                            <span class="metric-value" id="churnedCustomers">0</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Inventory Reports Tab -->
                        <div id="inventoryReport" class="report-tab-content">
                            <div class="reports-grid">
                                <div class="report-card">
                                    <h4>📊 Trạng thái kho hàng</h4>
                                    <div class="chart-container small">
                                        <canvas id="stockStatusChart"></canvas>
                                    </div>
                                    <div id="stockSummary" class="stats-display"></div>
                                </div>
                                <div class="report-card">
                                    <h4>⚡ Tốc độ bán hàng</h4>
                                    <div id="velocityTable" class="data-table-container"></div>
                                </div>
                            </div>

                            <div class="report-card full-width">
                                <h4>⚠️ Cảnh báo tồn kho thấp</h4>
                                <div id="lowStockTable" class="data-table-container"></div>
                            </div>

                            <div class="report-card full-width">
                                <h4>📈 Hiệu suất danh mục kho hàng</h4>
                                <div class="chart-container large">
                                    <canvas id="inventoryCategoryChart"></canvas>
                                </div>
                                <div id="categoryInventoryTable" class="data-table-container"></div>
                            </div>
                        </div>

                        <!-- Marketing Analytics Tab -->
                        <div id="marketingReport" class="report-tab-content">
                            <div class="reports-grid">
                                <div class="report-card">
                                    <h4>🎁 Hiệu quả khuyến mãi</h4>
                                    <div id="promotionEffectiveness" class="data-table-container"></div>
                                </div>
                                <div class="report-card">
                                    <h4>💰 Phân tích giảm giá</h4>
                                    <div class="chart-container small">
                                        <canvas id="discountChart"></canvas>
                                    </div>
                                    <div id="discountStats" class="stats-display"></div>
                                </div>
                            </div>

                            <div class="report-card full-width">
                                <h4>📊 Hiệu suất chiến dịch</h4>
                                <div id="campaignTable" class="data-table-container"></div>
                            </div>
                        </div>
                    </div>

                    <style>
                        .reports-container {
                            padding: 0;
                        }

                        .reports-header {
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            margin-bottom: 2rem;
                            background: linear-gradient(135deg, rgba(255, 255, 255, 0.2), rgba(255, 255, 255, 0.1));
                            backdrop-filter: blur(20px);
                            padding: 1.5rem 2rem;
                            border-radius: 2rem;
                            border: 1px solid rgba(255, 255, 255, 0.3);
                            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
                        }

                        .reports-controls {
                            display: flex;
                            gap: 2rem;
                            align-items: center;
                        }

                        .date-range-selector {
                            display: flex;
                            align-items: center;
                            gap: 0.5rem;
                            background: rgba(255, 255, 255, 0.1);
                            padding: 0.75rem 1rem;
                            border-radius: 1rem;
                            border: 1px solid rgba(255, 255, 255, 0.2);
                        }

                        .report-date-input {
                            padding: 0.5rem;
                            border: 1px solid rgba(255, 255, 255, 0.3);
                            border-radius: 0.5rem;
                            background: rgba(255, 255, 255, 0.2);
                            backdrop-filter: blur(10px);
                            color: var(--color-neutral-700);
                            font-size: 0.9rem;
                        }

                        .refresh-btn, .export-btn {
                            background: linear-gradient(135deg, var(--color-primary-500), var(--color-secondary-500));
                            color: white;
                            border: none;
                            padding: 0.75rem 1.25rem;
                            border-radius: 1rem;
                            font-weight: 600;
                            cursor: pointer;
                            transition: all 0.3s ease;
                            font-size: 0.9rem;
                        }

                        .refresh-btn:hover, .export-btn:hover {
                            transform: translateY(-2px);
                            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3);
                        }

                        .export-controls {
                            display: flex;
                            gap: 0.5rem;
                        }

                        .kpi-dashboard {
                            display: grid;
                            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                            gap: 1.5rem;
                            margin-bottom: 2rem;
                        }

                        .kpi-card {
                            background: linear-gradient(135deg, rgba(255, 255, 255, 0.25), rgba(255, 255, 255, 0.15));
                            backdrop-filter: blur(25px);
                            border: 1px solid rgba(255, 255, 255, 0.3);
                            border-radius: 1.5rem;
                            padding: 1.5rem;
                            text-align: center;
                            transition: all 0.4s ease;
                            position: relative;
                            overflow: hidden;
                        }

                        .kpi-card::before {
                            content: '';
                            position: absolute;
                            top: 0;
                            left: 0;
                            right: 0;
                            height: 3px;
                            background: linear-gradient(90deg, var(--color-primary-500), var(--color-secondary-500), var(--color-accent-500));
                        }

                        .kpi-card:hover {
                            transform: translateY(-5px);
                            box-shadow: 0 15px 45px rgba(0, 0, 0, 0.2);
                        }

                        .kpi-icon {
                            font-size: 2.5rem;
                            margin-bottom: 0.5rem;
                        }

                        .kpi-value {
                            font-size: 2rem;
                            font-weight: 800;
                            background: linear-gradient(135deg, var(--color-secondary-600), var(--color-accent-600));
                            -webkit-background-clip: text;
                            background-clip: text;
                            -webkit-text-fill-color: transparent;
                            margin-bottom: 0.25rem;
                        }

                        .kpi-label {
                            color: var(--color-neutral-600);
                            font-weight: 600;
                            font-size: 0.9rem;
                            margin-bottom: 0.5rem;
                        }

                        .kpi-change {
                            font-size: 0.8rem;
                            font-weight: 600;
                            padding: 0.25rem 0.75rem;
                            border-radius: 1rem;
                            background: rgba(34, 197, 94, 0.1);
                            color: var(--color-success-700);
                            border: 1px solid rgba(34, 197, 94, 0.3);
                        }

                        .kpi-change.negative {
                            background: rgba(239, 68, 68, 0.1);
                            color: var(--color-error-700);
                            border-color: rgba(239, 68, 68, 0.3);
                        }

                        .reports-tabs {
                            display: flex;
                            gap: 0.5rem;
                            margin-bottom: 2rem;
                            background: linear-gradient(135deg, rgba(255, 255, 255, 0.2), rgba(255, 255, 255, 0.1));
                            backdrop-filter: blur(20px);
                            padding: 0.5rem;
                            border-radius: 1.5rem;
                            border: 1px solid rgba(255, 255, 255, 0.3);
                        }

                        .report-tab {
                            background: transparent;
                            border: none;
                            padding: 1rem 1.5rem;
                            border-radius: 1rem;
                            font-weight: 600;
                            color: var(--color-neutral-600);
                            cursor: pointer;
                            transition: all 0.3s ease;
                            flex: 1;
                            text-align: center;
                        }

                        .report-tab.active,
                        .report-tab:hover {
                            background: linear-gradient(135deg, var(--color-primary-500), var(--color-secondary-500));
                            color: white;
                            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
                        }

                        .report-tab-content {
                            display: none;
                        }

                        .report-tab-content.active {
                            display: block;
                        }

                        .report-section {
                            margin-bottom: 3rem;
                        }

                        .reports-grid {
                            display: grid;
                            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
                            gap: 2rem;
                            margin-bottom: 2rem;
                        }

                        .report-card {
                            background: linear-gradient(135deg, rgba(255, 255, 255, 0.25), rgba(255, 255, 255, 0.15));
                            backdrop-filter: blur(25px);
                            border: 1px solid rgba(255, 255, 255, 0.3);
                            border-radius: 2rem;
                            padding: 2rem;
                            transition: all 0.3s ease;
                        }

                        .report-card.full-width {
                            grid-column: 1 / -1;
                        }

                        .report-card:hover {
                            transform: translateY(-3px);
                            box-shadow: 0 15px 45px rgba(0, 0, 0, 0.15);
                        }

                        .report-card h4 {
                            margin: 0 0 1.5rem 0;
                            color: var(--color-primary-600);
                            font-size: 1.25rem;
                            font-weight: 700;
                        }

                        .report-card h5 {
                            margin: 0 0 1rem 0;
                            color: var(--color-neutral-700);
                            font-size: 1rem;
                            font-weight: 600;
                            text-align: center;
                        }

                        .chart-container {
                            position: relative;
                            margin-bottom: 1rem;
                        }

                        .chart-container.small {
                            height: 250px;
                        }

                        .chart-container.medium {
                            height: 350px;
                        }

                        .chart-container.large {
                            height: 450px;
                        }

                        .charts-row {
                            display: grid;
                            grid-template-columns: 1fr 1fr;
                            gap: 2rem;
                        }

                        .chart-controls {
                            display: flex;
                            gap: 0.5rem;
                            justify-content: center;
                            margin-bottom: 1.5rem;
                        }

                        .period-btn {
                            background: rgba(255, 255, 255, 0.2);
                            border: 1px solid rgba(255, 255, 255, 0.3);
                            padding: 0.5rem 1rem;
                            border-radius: 0.75rem;
                            color: var(--color-neutral-700);
                            font-weight: 500;
                            cursor: pointer;
                            transition: all 0.3s ease;
                        }

                        .period-btn.active,
                        .period-btn:hover {
                            background: linear-gradient(135deg, var(--color-primary-500), var(--color-secondary-500));
                            color: white;
                            border-color: transparent;
                        }

                        .data-table-container {
                            max-height: 400px;
                            overflow-y: auto;
                            background: rgba(255, 255, 255, 0.1);
                            border-radius: 1rem;
                            border: 1px solid rgba(255, 255, 255, 0.2);
                        }

                        .data-list {
                            max-height: 200px;
                            overflow-y: auto;
                        }

                        .data-list-item {
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            padding: 0.75rem;
                            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
                            transition: background 0.2s ease;
                        }

                        .data-list-item:hover {
                            background: rgba(255, 255, 255, 0.1);
                        }

                        .data-list-item:last-child {
                            border-bottom: none;
                        }

                        .stats-display,
                        .stats-grid {
                            display: grid;
                            gap: 1rem;
                        }

                        .stats-grid {
                            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                        }

                        .metrics-display {
                            background: rgba(255, 255, 255, 0.1);
                            padding: 1rem;
                            border-radius: 1rem;
                            border: 1px solid rgba(255, 255, 255, 0.2);
                            margin-bottom: 1rem;
                        }

                        .metric-item {
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            padding: 0.5rem 0;
                            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
                        }

                        .metric-item:last-child {
                            border-bottom: none;
                        }

                        .metric-label {
                            color: var(--color-neutral-600);
                            font-weight: 500;
                        }

                        .metric-value {
                            color: var(--color-primary-600);
                            font-weight: 700;
                            font-size: 1.1rem;
                        }

                        .loading-spinner {
                            display: flex;
                            justify-content: center;
                            align-items: center;
                            padding: 2rem;
                            color: var(--color-neutral-500);
                        }

                        .error-message {
                            background: rgba(239, 68, 68, 0.1);
                            border: 1px solid rgba(239, 68, 68, 0.3);
                            color: var(--color-error-700);
                            padding: 1rem;
                            border-radius: 1rem;
                            text-align: center;
                            margin: 1rem 0;
                        }

                        /* Responsive Design */
                        @media (max-width: 768px) {
                            .reports-header {
                                flex-direction: column;
                                gap: 1rem;
                                text-align: center;
                            }

                            .reports-controls {
                                flex-direction: column;
                                gap: 1rem;
                            }

                            .date-range-selector {
                                flex-wrap: wrap;
                                justify-content: center;
                            }

                            .reports-tabs {
                                flex-direction: column;
                            }

                            .reports-grid {
                                grid-template-columns: 1fr;
                            }

                            .charts-row {
                                grid-template-columns: 1fr;
                            }

                            .kpi-dashboard {
                                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                            }

                            .chart-controls {
                                flex-wrap: wrap;
                            }
                        }
                    </style>
                `
            };
            
            // Replace main content
            if (sectionContent[section]) {
                // Find the content area after header
                const header = mainContent.querySelector('.staff-header');
                const afterHeader = header.nextElementSibling;
                
                // Create new section content
                const newContent = document.createElement('div');
                newContent.innerHTML = sectionContent[section];
                newContent.style.marginTop = '2rem';
                
                // Replace content after header
                let currentElement = afterHeader;
                while (currentElement) {
                    const nextElement = currentElement.nextElementSibling;
                    currentElement.remove();
                    currentElement = nextElement;
                }
                
                mainContent.appendChild(newContent);
                
                // Update page title
                document.title = `${section.charAt(0).toUpperCase() + section.slice(1)} - Staff Dashboard - Wibubu`;
            }
        }
        
        // Handle responsive sidebar behavior
        function handleResize() {
            if (window.innerWidth > 1024) {
                const sidebar = document.querySelector('.staff-sidebar');
                const overlay = document.querySelector('.sidebar-overlay');
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            }
        }
        
        // Add event listeners
        window.addEventListener('resize', handleResize);
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.staff-sidebar');
            const toggle = document.querySelector('.mobile-nav-toggle');
            
            if (window.innerWidth <= 1024 && 
                sidebar.classList.contains('show') && 
                !sidebar.contains(event.target) && 
                !toggle.contains(event.target)) {
                toggleSidebar();
            }
        });

        // ===== PRODUCT MANAGEMENT JAVASCRIPT =====
        
        // Global variables for product management
        let currentPage = 1;
        let currentProductId = null;
        const csrfToken = '<?php echo $_SESSION['csrf_token']; ?>';
        
        // Load categories when needed
        function loadCategories() {
            // Categories data from PHP backend
            const categories = [
                {id: 1, name: 'Electronics'},
                {id: 2, name: 'Fashion'},
                {id: 3, name: 'Books'},
                {id: 4, name: 'Home & Garden'}
            ];
            
            // Populate category filters and form selects
            const categoryFilter = document.getElementById('categoryFilter');
            const productCategory = document.getElementById('productCategory');
            
            if (categoryFilter) {
                categoryFilter.innerHTML = '<option value="">Tất cả danh mục</option>';
                categories.forEach(category => {
                    categoryFilter.innerHTML += `<option value="${category.id}">📂 ${category.name}</option>`;
                });
            }
            
            if (productCategory) {
                productCategory.innerHTML = '<option value="">Chọn danh mục</option>';
                categories.forEach(category => {
                    productCategory.innerHTML += `<option value="${category.id}">${category.name}</option>`;
                });
            }
        }

        // Load products from server
        async function loadProducts(page = 1) {
            try {
                const search = document.getElementById('productSearch')?.value || '';
                const category = document.getElementById('categoryFilter')?.value || '';
                const status = document.getElementById('statusFilter')?.value || '';
                
                const params = new URLSearchParams({
                    action: 'list',
                    page: page,
                    limit: 10,
                    search: search,
                    category: category,
                    status: status
                });

                const response = await fetch(`product-handler.php?${params}`);
                const data = await response.json();

                if (data.success) {
                    displayProducts(data.data.products);
                    displayPagination(data.data.pagination);
                    currentPage = data.data.pagination.current_page;
                } else {
                    showError('Lỗi tải dữ liệu: ' + data.message);
                }
            } catch (error) {
                console.error('Error loading products:', error);
                showError('Không thể tải danh sách sản phẩm');
            }
        }

        // Display products in a modern grid/table format
        function displayProducts(products) {
            const container = document.getElementById('productsContainer');
            if (!container) return;

            if (!products || products.length === 0) {
                container.innerHTML = `
                    <div style="text-align: center; padding: 3rem;">
                        <div style="font-size: 4rem; margin-bottom: 1rem;">📦</div>
                        <h3 style="color: var(--color-neutral-700); margin-bottom: 1rem;">Không có sản phẩm nào</h3>
                        <p style="color: var(--color-neutral-600); margin-bottom: 2rem;">Chưa có sản phẩm nào phù hợp với bộ lọc hiện tại</p>
                        <button onclick="showAddProductModal()" style="background: linear-gradient(135deg, var(--color-primary-500), var(--color-secondary-500)); color: white; padding: 1rem 2rem; border: none; border-radius: 1rem; cursor: pointer; font-weight: 600;">
                            ➕ Thêm sản phẩm đầu tiên
                        </button>
                    </div>
                `;
                return;
            }

            let html = `
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.15), rgba(147, 51, 234, 0.15)); border-radius: 1rem 1rem 0 0;">
                                <th style="padding: 1rem; text-align: left; font-weight: 700; color: var(--color-primary-700); border-bottom: 2px solid rgba(255, 255, 255, 0.2);">Sản phẩm</th>
                                <th style="padding: 1rem; text-align: left; font-weight: 700; color: var(--color-primary-700); border-bottom: 2px solid rgba(255, 255, 255, 0.2);">Danh mục</th>
                                <th style="padding: 1rem; text-align: left; font-weight: 700; color: var(--color-primary-700); border-bottom: 2px solid rgba(255, 255, 255, 0.2);">Giá</th>
                                <th style="padding: 1rem; text-align: left; font-weight: 700; color: var(--color-primary-700); border-bottom: 2px solid rgba(255, 255, 255, 0.2);">Kho</th>
                                <th style="padding: 1rem; text-align: left; font-weight: 700; color: var(--color-primary-700); border-bottom: 2px solid rgba(255, 255, 255, 0.2);">Trạng thái</th>
                                <th style="padding: 1rem; text-align: center; font-weight: 700; color: var(--color-primary-700); border-bottom: 2px solid rgba(255, 255, 255, 0.2);">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            products.forEach((product, index) => {
                const statusBadge = product.status === 'active' 
                    ? '<span style="background: linear-gradient(135deg, rgba(34, 197, 94, 0.15), rgba(74, 222, 128, 0.15)); color: var(--color-success-700); padding: 0.5rem 1rem; border-radius: 2rem; font-size: 0.75rem; font-weight: 700; border: 1px solid rgba(34, 197, 94, 0.3);">✅ CÒN HÀNG</span>'
                    : '<span style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.15), rgba(248, 113, 113, 0.15)); color: var(--color-error-700); padding: 0.5rem 1rem; border-radius: 2rem; font-size: 0.75rem; font-weight: 700; border: 1px solid rgba(239, 68, 68, 0.3);">❌ HẾT HÀNG</span>';

                html += `
                    <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.1); transition: all 0.3s ease;" onmouseover="this.style.background='rgba(59, 130, 246, 0.05)'; this.style.transform='scale(1.01)'" onmouseout="this.style.background=''; this.style.transform='scale(1)'">
                        <td style="padding: 1rem;">
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <img src="${product.image_url}" alt="${product.name}" style="width: 60px; height: 60px; border-radius: 0.75rem; object-fit: cover; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);">
                                <div>
                                    <div style="font-weight: 700; color: var(--color-neutral-800); margin-bottom: 0.25rem;">${product.name}</div>
                                    <div style="font-size: 0.875rem; color: var(--color-neutral-600); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">${product.description || 'Không có mô tả'}</div>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 1rem;">
                            <span style="background: rgba(59, 130, 246, 0.1); color: var(--color-primary-700); padding: 0.5rem 1rem; border-radius: 1rem; font-size: 0.875rem; font-weight: 600;">📂 ${product.category_name || 'N/A'}</span>
                        </td>
                        <td style="padding: 1rem;">
                            <div style="font-size: 1.125rem; font-weight: 700; color: var(--color-secondary-700);">${formatPrice(product.price)}₫</div>
                        </td>
                        <td style="padding: 1rem;">
                            <div style="font-size: 1.25rem; font-weight: 800; color: ${product.stock > 10 ? 'var(--color-success-600)' : product.stock > 0 ? 'var(--color-warning-600)' : 'var(--color-error-600)'};">${product.stock}</div>
                        </td>
                        <td style="padding: 1rem;">${statusBadge}</td>
                        <td style="padding: 1rem; text-align: center;">
                            <div style="display: flex; gap: 0.5rem; justify-content: center;">
                                <button onclick="editProduct(${product.id})" style="background: linear-gradient(135deg, var(--color-accent-500), var(--color-secondary-500)); color: white; padding: 0.5rem 1rem; border: none; border-radius: 0.5rem; cursor: pointer; font-size: 0.875rem; font-weight: 600; transition: all 0.3s ease; display: flex; align-items: center; gap: 0.25rem;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px rgba(147, 51, 234, 0.3)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                    ✏️ Sửa
                                </button>
                                <button onclick="deleteProduct(${product.id}, '${product.name.replace(/'/g, "\\\'")}')" style="background: linear-gradient(135deg, var(--color-error-500), var(--color-error-600)); color: white; padding: 0.5rem 1rem; border: none; border-radius: 0.5rem; cursor: pointer; font-size: 0.875rem; font-weight: 600; transition: all 0.3s ease; display: flex; align-items: center; gap: 0.25rem;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px rgba(239, 68, 68, 0.3)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                    🗑️ Xóa
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });

            html += '</tbody></table></div>';
            container.innerHTML = html;
        }

        // Display pagination controls
        function displayPagination(pagination) {
            const container = document.getElementById('paginationContainer');
            if (!container) return;

            if (pagination.total_pages <= 1) {
                container.innerHTML = '';
                return;
            }

            let html = `
                <div style="display: flex; align-items: center; gap: 0.5rem; background: linear-gradient(135deg, rgba(255, 255, 255, 0.25), rgba(255, 255, 255, 0.15)); backdrop-filter: blur(25px); border: 1px solid rgba(255, 255, 255, 0.3); border-radius: 1.5rem; padding: 1rem;">
                    <span style="color: var(--color-neutral-600); font-size: 0.875rem; margin-right: 1rem;">
                        Trang ${pagination.current_page} / ${pagination.total_pages} (${pagination.total_products} sản phẩm)
                    </span>
            `;

            // Previous button
            html += `
                <button onclick="loadProducts(${pagination.current_page - 1})" 
                        ${!pagination.has_prev ? 'disabled' : ''}
                        style="background: ${pagination.has_prev ? 'linear-gradient(135deg, var(--color-primary-500), var(--color-secondary-500))' : 'rgba(255, 255, 255, 0.3)'}; 
                               color: ${pagination.has_prev ? 'white' : 'var(--color-neutral-400)'}; 
                               padding: 0.75rem 1rem; border: none; border-radius: 0.75rem; cursor: ${pagination.has_prev ? 'pointer' : 'not-allowed'}; 
                               font-weight: 600; transition: all 0.3s ease; display: flex; align-items: center; gap: 0.5rem;">
                    ← Trước
                </button>
            `;

            // Page numbers
            const startPage = Math.max(1, pagination.current_page - 2);
            const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);

            for (let i = startPage; i <= endPage; i++) {
                const isActive = i === pagination.current_page;
                html += `
                    <button onclick="loadProducts(${i})" 
                            style="background: ${isActive ? 'linear-gradient(135deg, var(--color-primary-500), var(--color-secondary-500))' : 'rgba(255, 255, 255, 0.3)'}; 
                                   color: ${isActive ? 'white' : 'var(--color-neutral-700)'}; 
                                   padding: 0.75rem 1rem; border: none; border-radius: 0.75rem; cursor: pointer; 
                                   font-weight: 600; transition: all 0.3s ease; min-width: 45px;">
                        ${i}
                    </button>
                `;
            }

            // Next button
            html += `
                <button onclick="loadProducts(${pagination.current_page + 1})" 
                        ${!pagination.has_next ? 'disabled' : ''}
                        style="background: ${pagination.has_next ? 'linear-gradient(135deg, var(--color-primary-500), var(--color-secondary-500))' : 'rgba(255, 255, 255, 0.3)'}; 
                               color: ${pagination.has_next ? 'white' : 'var(--color-neutral-400)'}; 
                               padding: 0.75rem 1rem; border: none; border-radius: 0.75rem; cursor: ${pagination.has_next ? 'pointer' : 'not-allowed'}; 
                               font-weight: 600; transition: all 0.3s ease; display: flex; align-items: center; gap: 0.5rem;">
                    Sau →
                </button>
            `;

            html += '</div>';
            container.innerHTML = html;
        }

        // Modal management functions
        function showAddProductModal() {
            loadCategories();
            document.getElementById('modalTitle').textContent = '➕ Thêm sản phẩm mới';
            document.getElementById('submitBtn').textContent = 'Thêm sản phẩm';
            document.getElementById('productForm').reset();
            document.getElementById('productId').value = '';
            hideModalMessage();
            hideImagePreview();
            currentProductId = null;
            document.getElementById('productModal').style.display = 'flex';
        }

        function closeProductModal() {
            document.getElementById('productModal').style.display = 'none';
            document.getElementById('productForm').reset();
            hideModalMessage();
            hideImagePreview();
            currentProductId = null;
        }

        function closeModal(event) {
            if (event.target.id === 'productModal') {
                closeProductModal();
            }
        }

        // Edit product function
        async function editProduct(productId) {
            try {
                const response = await fetch(`product-handler.php?action=get&id=${productId}`);
                const data = await response.json();

                if (data.success && data.data) {
                    const product = data.data;
                    loadCategories();
                    
                    document.getElementById('modalTitle').textContent = '✏️ Chỉnh sửa sản phẩm';
                    document.getElementById('submitBtn').textContent = 'Cập nhật sản phẩm';
                    
                    // Populate form with product data
                    document.getElementById('productId').value = product.id;
                    document.getElementById('productName').value = product.name;
                    document.getElementById('productDescription').value = product.description || '';
                    document.getElementById('productPrice').value = product.price;
                    document.getElementById('productStock').value = product.stock;
                    document.getElementById('productCategory').value = product.category_id;
                    document.getElementById('productImageUrl').value = product.image_url || '';
                    
                    if (product.image_url) {
                        showImagePreview(product.image_url);
                    }
                    
                    hideModalMessage();
                    currentProductId = productId;
                    document.getElementById('productModal').style.display = 'flex';
                } else {
                    showError('Không thể tải thông tin sản phẩm: ' + data.message);
                }
            } catch (error) {
                console.error('Error loading product:', error);
                showError('Lỗi khi tải thông tin sản phẩm');
            }
        }

        // Delete product functions
        function deleteProduct(productId, productName) {
            document.getElementById('deleteMessage').textContent = `Bạn có chắc chắn muốn xóa sản phẩm "${productName}"? Hành động này không thể hoàn tác.`;
            currentProductId = productId;
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function closeDeleteModal(event) {
            if (event && event.target.id !== 'deleteModal') return;
            document.getElementById('deleteModal').style.display = 'none';
            currentProductId = null;
        }

        async function confirmDeleteProduct() {
            if (!currentProductId) return;

            try {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', currentProductId);
                formData.append('csrf_token', csrfToken);

                const response = await fetch('product-handler.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showSuccess(data.message);
                    closeDeleteModal();
                    loadProducts(currentPage);
                } else {
                    showError('Lỗi xóa sản phẩm: ' + data.message);
                }
            } catch (error) {
                console.error('Error deleting product:', error);
                showError('Không thể xóa sản phẩm');
            }
        }

        // Form submission handler
        document.addEventListener('DOMContentLoaded', function() {
            const productForm = document.getElementById('productForm');
            if (productForm) {
                productForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData();
                    const isEdit = currentProductId !== null;
                    
                    formData.append('action', isEdit ? 'edit' : 'add');
                    formData.append('csrf_token', csrfToken);
                    
                    if (isEdit) {
                        formData.append('id', currentProductId);
                    }
                    
                    // Get form data
                    formData.append('name', document.getElementById('productName').value);
                    formData.append('description', document.getElementById('productDescription').value);
                    formData.append('price', document.getElementById('productPrice').value);
                    formData.append('stock', document.getElementById('productStock').value);
                    formData.append('category_id', document.getElementById('productCategory').value);
                    formData.append('image_url', document.getElementById('productImageUrl').value);
                    
                    try {
                        // Disable submit button
                        const submitBtn = document.getElementById('submitBtn');
                        submitBtn.disabled = true;
                        submitBtn.textContent = 'Đang xử lý...';
                        
                        const response = await fetch('product-handler.php', {
                            method: 'POST',
                            body: formData
                        });

                        const data = await response.json();

                        if (data.success) {
                            showModalSuccess(data.message);
                            setTimeout(() => {
                                closeProductModal();
                                loadProducts(currentPage);
                            }, 1500);
                        } else {
                            showModalError(data.message);
                        }
                    } catch (error) {
                        console.error('Error saving product:', error);
                        showModalError('Không thể lưu sản phẩm');
                    } finally {
                        // Re-enable submit button
                        const submitBtn = document.getElementById('submitBtn');
                        submitBtn.disabled = false;
                        submitBtn.textContent = currentProductId ? 'Cập nhật sản phẩm' : 'Thêm sản phẩm';
                    }
                });
            }
        });

        // Image upload functionality
        async function uploadProductImage() {
            const fileInput = document.getElementById('productImageFile');
            const file = fileInput.files[0];
            
            if (!file) {
                showModalError('Vui lòng chọn file hình ảnh');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'upload_image');
            formData.append('image', file);
            formData.append('csrf_token', csrfToken);
            
            try {
                const response = await fetch('product-handler.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('productImageUrl').value = data.data.image_url;
                    showImagePreview(data.data.image_url);
                    showModalSuccess('Upload ảnh thành công');
                } else {
                    showModalError('Lỗi upload ảnh: ' + data.message);
                }
            } catch (error) {
                console.error('Error uploading image:', error);
                showModalError('Không thể upload hình ảnh');
            }
        }

        // Image preview functions
        function showImagePreview(imageUrl) {
            const preview = document.getElementById('imagePreview');
            const img = document.getElementById('previewImg');
            
            img.src = imageUrl;
            preview.style.display = 'block';
        }

        function hideImagePreview() {
            document.getElementById('imagePreview').style.display = 'none';
        }

        // Message display functions
        function showModalSuccess(message) {
            const messageDiv = document.getElementById('modalMessage');
            messageDiv.style.display = 'block';
            messageDiv.style.background = 'linear-gradient(135deg, rgba(34, 197, 94, 0.15), rgba(74, 222, 128, 0.15))';
            messageDiv.style.color = 'var(--color-success-700)';
            messageDiv.style.border = '1px solid rgba(34, 197, 94, 0.3)';
            messageDiv.textContent = '✅ ' + message;
        }

        function showModalError(message) {
            const messageDiv = document.getElementById('modalMessage');
            messageDiv.style.display = 'block';
            messageDiv.style.background = 'linear-gradient(135deg, rgba(239, 68, 68, 0.15), rgba(248, 113, 113, 0.15))';
            messageDiv.style.color = 'var(--color-error-700)';
            messageDiv.style.border = '1px solid rgba(239, 68, 68, 0.3)';
            messageDiv.textContent = '❌ ' + message;
        }

        function hideModalMessage() {
            document.getElementById('modalMessage').style.display = 'none';
        }

        function showSuccess(message) {
            // Create and show a temporary success message
            const successDiv = document.createElement('div');
            successDiv.style.cssText = `
                position: fixed; top: 20px; right: 20px; z-index: 2000;
                background: linear-gradient(135deg, rgba(34, 197, 94, 0.95), rgba(74, 222, 128, 0.95));
                color: white; padding: 1rem 1.5rem; border-radius: 1rem; font-weight: 600;
                box-shadow: 0 8px 25px rgba(34, 197, 94, 0.3); backdrop-filter: blur(20px);
                transform: translateX(100%); transition: transform 0.3s ease;
            `;
            successDiv.textContent = '✅ ' + message;
            document.body.appendChild(successDiv);
            
            setTimeout(() => successDiv.style.transform = 'translateX(0)', 100);
            setTimeout(() => {
                successDiv.style.transform = 'translateX(100%)';
                setTimeout(() => document.body.removeChild(successDiv), 300);
            }, 3000);
        }

        function showError(message) {
            // Create and show a temporary error message
            const errorDiv = document.createElement('div');
            errorDiv.style.cssText = `
                position: fixed; top: 20px; right: 20px; z-index: 2000;
                background: linear-gradient(135deg, rgba(239, 68, 68, 0.95), rgba(248, 113, 113, 0.95));
                color: white; padding: 1rem 1.5rem; border-radius: 1rem; font-weight: 600;
                box-shadow: 0 8px 25px rgba(239, 68, 68, 0.3); backdrop-filter: blur(20px);
                transform: translateX(100%); transition: transform 0.3s ease;
            `;
            errorDiv.textContent = '❌ ' + message;
            document.body.appendChild(errorDiv);
            
            setTimeout(() => errorDiv.style.transform = 'translateX(0)', 100);
            setTimeout(() => {
                errorDiv.style.transform = 'translateX(100%)';
                setTimeout(() => document.body.removeChild(errorDiv), 300);
            }, 4000);
        }

        // Utility functions
        function formatPrice(price) {
            return new Intl.NumberFormat('vi-VN').format(price);
        }

        // Auto-load products when products section is shown
        const originalShowSection = showSection;
        showSection = function(section) {
            originalShowSection(section);
            if (section === 'products') {
                // Delay to ensure DOM is ready
                setTimeout(() => {
                    loadCategories();
                    loadProducts(1);
                }, 100);
            }
        };

        // Add search functionality
        document.addEventListener('input', function(e) {
            if (e.target.id === 'productSearch') {
                // Debounce search
                clearTimeout(e.target.searchTimeout);
                e.target.searchTimeout = setTimeout(() => {
                    loadProducts(1);
                }, 500);
            }
        });

        // ===== END PRODUCT MANAGEMENT JAVASCRIPT =====

        // ===== ORDER MANAGEMENT JAVASCRIPT =====
        
        // Global variables for order management
        let currentOrderPage = 1;
        let currentOrderId = null;
        let selectedOrders = new Set();
        
        // Load orders from server
        async function loadOrders(page = 1) {
            try {
                const search = document.getElementById('orderSearch')?.value || '';
                const status = document.getElementById('statusFilter')?.value || '';
                const sortFilter = document.getElementById('sortFilter')?.value || 'created_at_desc';
                
                const [sort, order] = sortFilter.split('_').slice(0, 2).concat(sortFilter.split('_').slice(2));
                
                const params = new URLSearchParams({
                    action: 'list',
                    page: page,
                    limit: 10,
                    search: search,
                    status: status,
                    sort: sort.replace('_desc', '').replace('_asc', ''),
                    order: order?.toUpperCase() || 'DESC'
                });

                const response = await fetch(`order-handler.php?${params}`);
                const data = await response.json();

                if (data.success) {
                    displayOrders(data.data.orders);
                    displayOrdersPagination(data.data.pagination);
                    currentOrderPage = data.data.pagination.current_page;
                    updateBulkActionsVisibility();
                } else {
                    showError('Error loading orders: ' + data.message);
                }
            } catch (error) {
                console.error('Error loading orders:', error);
                showError('Unable to load orders');
            }
        }
        
        // Display orders in a modern table format
        function displayOrders(orders) {
            const container = document.getElementById('ordersContainer');
            if (!container) return;

            if (!orders || orders.length === 0) {
                container.innerHTML = `
                    <div style="text-align: center; padding: 3rem;">
                        <div style="font-size: 4rem; margin-bottom: 1rem;">📋</div>
                        <h3 style="color: var(--color-neutral-700); margin-bottom: 1rem;">No orders found</h3>
                        <p style="color: var(--color-neutral-600);">No orders match the current filters</p>
                    </div>
                `;
                return;
            }

            let html = `
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.15), rgba(147, 51, 234, 0.15)); border-radius: 1rem 1rem 0 0;">
                                <th style="padding: 1rem; text-align: left;">
                                    <input type="checkbox" id="selectAllOrders" onchange="toggleAllOrders(this.checked)" style="margin-right: 0.5rem; transform: scale(1.2);">
                                    Order
                                </th>
                                <th style="padding: 1rem; text-align: left; font-weight: 700; color: var(--color-primary-700);">Customer</th>
                                <th style="padding: 1rem; text-align: left; font-weight: 700; color: var(--color-primary-700);">Date</th>
                                <th style="padding: 1rem; text-align: left; font-weight: 700; color: var(--color-primary-700);">Total</th>
                                <th style="padding: 1rem; text-align: left; font-weight: 700; color: var(--color-primary-700);">Items</th>
                                <th style="padding: 1rem; text-align: left; font-weight: 700; color: var(--color-primary-700);">Status</th>
                                <th style="padding: 1rem; text-align: center; font-weight: 700; color: var(--color-primary-700);">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            orders.forEach((order, index) => {
                const statusBadge = getOrderStatusBadge(order.status_info);

                html += `
                    <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.1); transition: all 0.3s ease;" onmouseover="this.style.background='rgba(59, 130, 246, 0.05)'; this.style.transform='scale(1.01)'" onmouseout="this.style.background=''; this.style.transform='scale(1)'">
                        <td style="padding: 1rem;">
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <input type="checkbox" class="order-select" value="${order.id}" onchange="toggleOrderSelection(${order.id}, this.checked)" style="transform: scale(1.2);">
                                <div>
                                    <div style="font-weight: 700; color: var(--color-primary-600); margin-bottom: 0.25rem;">#${order.id}</div>
                                    <div style="font-size: 0.875rem; color: var(--color-neutral-600);">${order.created_at_formatted}</div>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 1rem;">
                            <div>
                                <div style="font-weight: 700; color: var(--color-neutral-800); margin-bottom: 0.25rem;">${order.customer_name || 'N/A'}</div>
                                <div style="font-size: 0.875rem; color: var(--color-neutral-600);">${order.customer_email || 'N/A'}</div>
                                ${order.customer_phone ? `<div style="font-size: 0.875rem; color: var(--color-neutral-600);">📞 ${order.customer_phone}</div>` : ''}
                            </div>
                        </td>
                        <td style="padding: 1rem;">
                            <div style="color: var(--color-neutral-700); font-weight: 600;">${order.created_at_short}</div>
                        </td>
                        <td style="padding: 1rem;">
                            <div style="font-size: 1.25rem; font-weight: 700; color: var(--color-secondary-700);">${formatPrice(order.total_amount)}₫</div>
                        </td>
                        <td style="padding: 1rem;">
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <span style="background: rgba(59, 130, 246, 0.1); color: var(--color-primary-700); padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.875rem; font-weight: 600;">${order.total_items} items</span>
                            </div>
                        </td>
                        <td style="padding: 1rem;">${statusBadge}</td>
                        <td style="padding: 1rem; text-align: center;">
                            <div style="display: flex; gap: 0.5rem; justify-content: center; flex-wrap: wrap;">
                                <button onclick="viewOrderDetails(${order.id})" style="background: linear-gradient(135deg, var(--color-info-500), var(--color-info-600)); color: white; padding: 0.5rem 1rem; border: none; border-radius: 0.5rem; cursor: pointer; font-size: 0.875rem; font-weight: 600; transition: all 0.3s ease; display: flex; align-items: center; gap: 0.25rem;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px rgba(59, 130, 246, 0.3)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                    👁️ View
                                </button>
                                ${order.status !== 'cancelled' && order.status !== 'completed' ? `
                                <button onclick="showCancelOrderModal(${order.id}, '${order.customer_name?.replace(/'/g, "\\'") || 'Unknown'}')" style="background: linear-gradient(135deg, var(--color-error-500), var(--color-error-600)); color: white; padding: 0.5rem 1rem; border: none; border-radius: 0.5rem; cursor: pointer; font-size: 0.875rem; font-weight: 600; transition: all 0.3s ease; display: flex; align-items: center; gap: 0.25rem;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px rgba(239, 68, 68, 0.3)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                    ❌ Cancel
                                </button>
                                ` : ''}
                            </div>
                        </td>
                    </tr>
                `;
            });

            html += '</tbody></table></div>';
            container.innerHTML = html;
        }
        
        // Get status badge HTML
        function getOrderStatusBadge(statusInfo) {
            const classMap = {
                'pending': 'background: linear-gradient(135deg, rgba(249, 115, 22, 0.15), rgba(251, 146, 60, 0.15)); color: var(--color-warning-700); border: 1px solid rgba(249, 115, 22, 0.3);',
                'processing': 'background: linear-gradient(135deg, rgba(59, 130, 246, 0.15), rgba(147, 197, 253, 0.15)); color: var(--color-info-700); border: 1px solid rgba(59, 130, 246, 0.3);',
                'completed': 'background: linear-gradient(135deg, rgba(34, 197, 94, 0.15), rgba(74, 222, 128, 0.15)); color: var(--color-success-700); border: 1px solid rgba(34, 197, 94, 0.3);',
                'cancelled': 'background: linear-gradient(135deg, rgba(239, 68, 68, 0.15), rgba(248, 113, 113, 0.15)); color: var(--color-error-700); border: 1px solid rgba(239, 68, 68, 0.3);'
            };
            
            const statusEmojis = {
                'pending': '🟠',
                'processing': '🔵', 
                'completed': '🟢',
                'cancelled': '🔴'
            };
            
            const status = statusInfo.color;
            const style = classMap[status] || classMap['pending'];
            const emoji = statusEmojis[status] || '⚪';
            
            return `<span style="${style} padding: 0.5rem 1rem; border-radius: 2rem; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">${emoji} ${statusInfo.label}</span>`;
        }

        // Display pagination controls for orders
        function displayOrdersPagination(pagination) {
            const container = document.getElementById('ordersPaginationContainer');
            if (!container) return;

            if (pagination.total_pages <= 1) {
                container.innerHTML = '';
                return;
            }

            let html = `
                <div style="display: flex; align-items: center; gap: 0.5rem; background: linear-gradient(135deg, rgba(255, 255, 255, 0.25), rgba(255, 255, 255, 0.15)); backdrop-filter: blur(25px); border: 1px solid rgba(255, 255, 255, 0.3); border-radius: 1.5rem; padding: 1rem;">
                    <span style="color: var(--color-neutral-600); font-size: 0.875rem; margin-right: 1rem;">
                        Page ${pagination.current_page} / ${pagination.total_pages} (${pagination.total_orders} orders)
                    </span>
            `;

            // Previous button
            html += `
                <button onclick="loadOrders(${pagination.current_page - 1})" 
                        ${!pagination.has_prev ? 'disabled' : ''}
                        style="background: ${pagination.has_prev ? 'linear-gradient(135deg, var(--color-primary-500), var(--color-secondary-500))' : 'rgba(255, 255, 255, 0.3)'}; 
                               color: ${pagination.has_prev ? 'white' : 'var(--color-neutral-400)'}; 
                               padding: 0.75rem 1rem; border: none; border-radius: 0.75rem; cursor: ${pagination.has_prev ? 'pointer' : 'not-allowed'}; 
                               font-weight: 600; transition: all 0.3s ease; display: flex; align-items: center; gap: 0.5rem;">
                    ← Previous
                </button>
            `;

            // Page numbers
            const startPage = Math.max(1, pagination.current_page - 2);
            const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);

            for (let i = startPage; i <= endPage; i++) {
                const isActive = i === pagination.current_page;
                html += `
                    <button onclick="loadOrders(${i})" 
                            style="background: ${isActive ? 'linear-gradient(135deg, var(--color-primary-500), var(--color-secondary-500))' : 'rgba(255, 255, 255, 0.3)'}; 
                                   color: ${isActive ? 'white' : 'var(--color-neutral-700)'}; 
                                   padding: 0.75rem 1rem; border: none; border-radius: 0.75rem; cursor: pointer; 
                                   font-weight: 600; transition: all 0.3s ease; min-width: 45px;">
                        ${i}
                    </button>
                `;
            }

            // Next button
            html += `
                <button onclick="loadOrders(${pagination.current_page + 1})" 
                        ${!pagination.has_next ? 'disabled' : ''}
                        style="background: ${pagination.has_next ? 'linear-gradient(135deg, var(--color-primary-500), var(--color-secondary-500))' : 'rgba(255, 255, 255, 0.3)'}; 
                               color: ${pagination.has_next ? 'white' : 'var(--color-neutral-400)'}; 
                               padding: 0.75rem 1rem; border: none; border-radius: 0.75rem; cursor: ${pagination.has_next ? 'pointer' : 'not-allowed'}; 
                               font-weight: 600; transition: all 0.3s ease; display: flex; align-items: center; gap: 0.5rem;">
                    Next →
                </button>
            `;

            html += '</div>';
            container.innerHTML = html;
        }

        // Order selection management
        function toggleAllOrders(checked) {
            const checkboxes = document.querySelectorAll('.order-select');
            checkboxes.forEach(checkbox => {
                checkbox.checked = checked;
                toggleOrderSelection(parseInt(checkbox.value), checked);
            });
        }

        function toggleOrderSelection(orderId, checked) {
            if (checked) {
                selectedOrders.add(orderId);
            } else {
                selectedOrders.delete(orderId);
                document.getElementById('selectAllOrders').checked = false;
            }
            updateBulkActionsVisibility();
        }

        function updateBulkActionsVisibility() {
            const bulkBtn = document.getElementById('bulkActionsBtn');
            const bulkPanel = document.getElementById('bulkActionsPanel');
            
            if (selectedOrders.size > 0) {
                bulkBtn.style.display = 'flex';
            } else {
                bulkBtn.style.display = 'none';
                bulkPanel.style.display = 'none';
            }
        }

        // Bulk actions
        function showBulkActions() {
            const panel = document.getElementById('bulkActionsPanel');
            panel.style.display = 'block';
        }

        function hideBulkActions() {
            const panel = document.getElementById('bulkActionsPanel');
            panel.style.display = 'none';
        }

        async function bulkUpdateStatus(newStatus) {
            if (selectedOrders.size === 0) {
                showError('No orders selected');
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'bulk_update');
                formData.append('csrf_token', csrfToken);
                formData.append('bulk_action', `mark_${newStatus}`);
                
                selectedOrders.forEach(orderId => {
                    formData.append('order_ids[]', orderId);
                });

                const response = await fetch('order-handler.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showSuccess(data.message);
                    selectedOrders.clear();
                    hideBulkActions();
                    loadOrders(currentOrderPage);
                } else {
                    showError('Error updating orders: ' + data.message);
                }
            } catch (error) {
                console.error('Error in bulk update:', error);
                showError('Unable to update orders');
            }
        }

        // View order details
        async function viewOrderDetails(orderId) {
            try {
                const response = await fetch(`order-handler.php?action=get&id=${orderId}`);
                const data = await response.json();

                if (data.success && data.data) {
                    const order = data.data;
                    showOrderDetailsModal(order);
                } else {
                    showError('Unable to load order details: ' + data.message);
                }
            } catch (error) {
                console.error('Error loading order details:', error);
                showError('Error loading order details');
            }
        }

        // Show order details modal
        function showOrderDetailsModal(order) {
            currentOrderId = order.id;
            
            document.getElementById('orderModalTitle').textContent = `Order #${order.id} - ${order.customer_name || 'Unknown Customer'}`;
            
            let itemsHtml = '';
            if (order.items && order.items.length > 0) {
                itemsHtml = `
                    <div style="margin-top: 1.5rem;">
                        <h4 style="color: var(--color-secondary-600); margin-bottom: 1rem;">Order Items</h4>
                        <div style="display: grid; gap: 1rem;">
                `;
                
                order.items.forEach(item => {
                    itemsHtml += `
                        <div style="display: flex; gap: 1rem; padding: 1rem; background: rgba(255, 255, 255, 0.1); border-radius: 1rem; border: 1px solid rgba(255, 255, 255, 0.2);">
                            <img src="${item.product_image}" alt="${item.product_name}" style="width: 80px; height: 80px; object-fit: cover; border-radius: 0.75rem;" />
                            <div style="flex: 1;">
                                <div style="font-weight: 700; color: var(--color-neutral-800);">${item.product_name}</div>
                                <div style="color: var(--color-neutral-600); font-size: 0.875rem; margin: 0.25rem 0;">${item.product_description || ''}</div>
                                <div style="color: var(--color-info-600); font-size: 0.875rem;">📂 ${item.category_name || 'N/A'}</div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-weight: 700; color: var(--color-secondary-700);">${formatPrice(item.price)}₫ each</div>
                                <div style="color: var(--color-neutral-600);">Qty: ${item.quantity}</div>
                                <div style="font-weight: 700; color: var(--color-primary-600); margin-top: 0.5rem;">Total: ${formatPrice(item.total)}₫</div>
                            </div>
                        </div>
                    `;
                });
                
                itemsHtml += '</div></div>';
            }
            
            const modalContent = `
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                    <!-- Customer Info -->
                    <div style="background: rgba(255, 255, 255, 0.1); padding: 1.5rem; border-radius: 1.5rem; border: 1px solid rgba(255, 255, 255, 0.2);">
                        <h4 style="color: var(--color-secondary-600); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                            👤 Customer Information
                        </h4>
                        <div style="space-y: 0.5rem;">
                            <div><strong>Name:</strong> ${order.customer_name || 'N/A'}</div>
                            <div><strong>Email:</strong> ${order.customer_email || 'N/A'}</div>
                            ${order.customer_phone ? `<div><strong>Phone:</strong> ${order.customer_phone}</div>` : ''}
                            ${order.shipping_address ? `<div><strong>Address:</strong><br/><span style="color: var(--color-neutral-600);">${order.shipping_address}</span></div>` : ''}
                        </div>
                    </div>
                    
                    <!-- Order Summary -->
                    <div style="background: rgba(255, 255, 255, 0.1); padding: 1.5rem; border-radius: 1.5rem; border: 1px solid rgba(255, 255, 255, 0.2);">
                        <h4 style="color: var(--color-secondary-600); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                            📊 Order Summary
                        </h4>
                        <div style="space-y: 0.5rem;">
                            <div><strong>Order ID:</strong> #${order.id}</div>
                            <div><strong>Date:</strong> ${order.created_at_formatted}</div>
                            <div><strong>Status:</strong> ${getOrderStatusBadge(order.status_info)}</div>
                            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255, 255, 255, 0.2);">
                                <div style="display: flex; justify-content: space-between;">
                                    <span>Subtotal:</span>
                                    <span>${formatPrice(order.subtotal)}₫</span>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span>Shipping:</span>
                                    <span>${formatPrice(order.shipping)}₫</span>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span>Tax:</span>
                                    <span>${formatPrice(order.tax)}₫</span>
                                </div>
                                ${order.discount > 0 ? `
                                <div style="display: flex; justify-content: space-between; color: var(--color-success-600);">
                                    <span>Discount:</span>
                                    <span>-${formatPrice(order.discount)}₫</span>
                                </div>
                                ` : ''}
                                <div style="display: flex; justify-content: space-between; font-weight: 700; font-size: 1.1rem; margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid rgba(255, 255, 255, 0.2); color: var(--color-primary-600);">
                                    <span>Total:</span>
                                    <span>${formatPrice(order.total_amount)}₫</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                ${itemsHtml}
                
                ${order.notes ? `
                <div style="margin-top: 1.5rem;">
                    <h4 style="color: var(--color-secondary-600); margin-bottom: 1rem;">Customer Notes</h4>
                    <div style="background: rgba(255, 255, 255, 0.1); padding: 1rem; border-radius: 1rem; border: 1px solid rgba(255, 255, 255, 0.2);">
                        ${order.notes}
                    </div>
                </div>
                ` : ''}
                
                ${order.staff_notes ? `
                <div style="margin-top: 1.5rem;">
                    <h4 style="color: var(--color-secondary-600); margin-bottom: 1rem;">Staff Notes</h4>
                    <div style="background: rgba(255, 255, 255, 0.1); padding: 1rem; border-radius: 1rem; border: 1px solid rgba(255, 255, 255, 0.2); white-space: pre-line;">
                        ${order.staff_notes}
                    </div>
                </div>
                ` : ''}
            `;
            
            document.getElementById('orderModalContent').innerHTML = modalContent;
            document.getElementById('newStatus').value = '';
            document.getElementById('statusNote').value = '';
            document.getElementById('staffNote').value = '';
            document.getElementById('orderDetailModal').style.display = 'flex';
        }

        // Modal management
        function closeOrderModal(event) {
            if (event && event.target.id !== 'orderDetailModal') return;
            document.getElementById('orderDetailModal').style.display = 'none';
            currentOrderId = null;
        }

        // Status update form handler
        document.addEventListener('DOMContentLoaded', function() {
            const statusForm = document.getElementById('statusUpdateForm');
            if (statusForm) {
                statusForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    if (!currentOrderId) return;
                    
                    const newStatus = document.getElementById('newStatus').value;
                    const note = document.getElementById('statusNote').value;
                    
                    if (!newStatus) {
                        showError('Please select a status');
                        return;
                    }
                    
                    try {
                        const formData = new FormData();
                        formData.append('action', 'update_status');
                        formData.append('csrf_token', csrfToken);
                        formData.append('order_id', currentOrderId);
                        formData.append('status', newStatus);
                        if (note) formData.append('note', note);
                        
                        const response = await fetch('order-handler.php', {
                            method: 'POST',
                            body: formData
                        });

                        const data = await response.json();

                        if (data.success) {
                            showSuccess(data.message);
                            document.getElementById('newStatus').value = '';
                            document.getElementById('statusNote').value = '';
                            loadOrders(currentOrderPage);
                            // Refresh the modal content
                            setTimeout(() => viewOrderDetails(currentOrderId), 500);
                        } else {
                            showError('Error updating status: ' + data.message);
                        }
                    } catch (error) {
                        console.error('Error updating order status:', error);
                        showError('Unable to update order status');
                    }
                });
            }
            
            // Add note form handler
            const noteForm = document.getElementById('addNoteForm');
            if (noteForm) {
                noteForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    if (!currentOrderId) return;
                    
                    const note = document.getElementById('staffNote').value.trim();
                    
                    if (!note) {
                        showError('Please enter a note');
                        return;
                    }
                    
                    try {
                        const formData = new FormData();
                        formData.append('action', 'add_note');
                        formData.append('csrf_token', csrfToken);
                        formData.append('order_id', currentOrderId);
                        formData.append('note', note);
                        
                        const response = await fetch('order-handler.php', {
                            method: 'POST',
                            body: formData
                        });

                        const data = await response.json();

                        if (data.success) {
                            showSuccess(data.message);
                            document.getElementById('staffNote').value = '';
                            // Refresh the modal content
                            setTimeout(() => viewOrderDetails(currentOrderId), 500);
                        } else {
                            showError('Error adding note: ' + data.message);
                        }
                    } catch (error) {
                        console.error('Error adding note:', error);
                        showError('Unable to add note');
                    }
                });
            }
        });

        // Cancel order functionality
        function showCancelOrderModal(orderId, customerName) {
            currentOrderId = orderId;
            document.getElementById('cancelMessage').textContent = 
                `Are you sure you want to cancel order #${orderId} for ${customerName}? This action cannot be undone.`;
            document.getElementById('cancelOrderModal').style.display = 'flex';
        }

        function closeCancelModal(event) {
            if (event && event.target.id !== 'cancelOrderModal') return;
            document.getElementById('cancelOrderModal').style.display = 'none';
            document.getElementById('cancelReason').value = '';
            currentOrderId = null;
        }

        // Cancel order form handler
        document.addEventListener('DOMContentLoaded', function() {
            const cancelForm = document.getElementById('cancelOrderForm');
            if (cancelForm) {
                cancelForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    if (!currentOrderId) return;
                    
                    const reason = document.getElementById('cancelReason').value.trim();
                    
                    try {
                        const formData = new FormData();
                        formData.append('action', 'cancel');
                        formData.append('csrf_token', csrfToken);
                        formData.append('order_id', currentOrderId);
                        if (reason) formData.append('reason', reason);
                        
                        const response = await fetch('order-handler.php', {
                            method: 'POST',
                            body: formData
                        });

                        const data = await response.json();

                        if (data.success) {
                            showSuccess(data.message);
                            closeCancelModal();
                            loadOrders(currentOrderPage);
                        } else {
                            showError('Error cancelling order: ' + data.message);
                        }
                    } catch (error) {
                        console.error('Error cancelling order:', error);
                        showError('Unable to cancel order');
                    }
                });
            }
        });

        // Refresh orders
        function refreshOrders() {
            selectedOrders.clear();
            hideBulkActions();
            loadOrders(currentOrderPage);
        }

        // Export orders (placeholder function)
        function exportOrders() {
            if (selectedOrders.size === 0) {
                showError('No orders selected for export');
                return;
            }
            
            // Simple CSV export - in production you'd want a proper backend endpoint
            const orderIds = Array.from(selectedOrders).join(',');
            showSuccess(`Exporting orders: ${orderIds} (Feature coming soon)`);
        }

        // Auto-load orders when orders section is shown
        const originalShowSectionForOrders = showSection;
        showSection = function(section) {
            originalShowSectionForOrders(section);
            if (section === 'orders') {
                // Delay to ensure DOM is ready
                setTimeout(() => {
                    loadOrders(1);
                }, 100);
            }
        };

        // Add search functionality for orders
        document.addEventListener('input', function(e) {
            if (e.target.id === 'orderSearch') {
                // Debounce search
                clearTimeout(e.target.searchTimeout);
                e.target.searchTimeout = setTimeout(() => {
                    loadOrders(1);
                }, 500);
            }
        });

        // Add filter functionality for orders
        document.addEventListener('change', function(e) {
            if (e.target.id === 'statusFilter' || e.target.id === 'sortFilter') {
                loadOrders(1);
            }
        });

        // ===== END ORDER MANAGEMENT JAVASCRIPT =====

        // ==============================================
        // PROMOTIONS MANAGEMENT FUNCTIONS
        // ==============================================

        let currentPromotionPage = 1;
        let currentPromotionId = null;
        let promotionsData = [];

        // Load promotions with filters and pagination
        async function loadPromotions(page = 1, limit = 10) {
            currentPromotionPage = page;
            
            try {
                const search = document.getElementById('promotionSearch')?.value || '';
                const type = document.getElementById('promotionTypeFilter')?.value || '';
                const status = document.getElementById('promotionStatusFilter')?.value || '';
                
                const params = new URLSearchParams({
                    action: 'list',
                    page: page,
                    limit: limit,
                    search: search,
                    type: type,
                    status: status
                });

                const response = await fetch(`promotion-handler.php?${params}`);
                const data = await response.json();

                if (data.success) {
                    promotionsData = data.data.promotions;
                    displayPromotions(data.data.promotions);
                    displayPromotionsPagination(data.data.pagination);
                    loadPromotionsStats();
                } else {
                    showError('Error loading promotions: ' + data.message);
                }
            } catch (error) {
                console.error('Error loading promotions:', error);
                showError('Unable to load promotions');
            }
        }

        // Display promotions in grid/table format
        function displayPromotions(promotions) {
            const container = document.getElementById('promotionsContainer');
            
            if (!promotions || promotions.length === 0) {
                container.innerHTML = `
                    <div style="text-align: center; padding: 3rem;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">🎁</div>
                        <h3 style="color: var(--color-neutral-600); margin-bottom: 1rem;">Chưa có khuyến mãi nào</h3>
                        <p style="color: var(--color-neutral-500); margin-bottom: 2rem;">Tạo khuyến mãi đầu tiên để thu hút khách hàng</p>
                        <button onclick="showAddPromotionModal()" style="background: linear-gradient(135deg, var(--color-primary-500), var(--color-secondary-500)); color: white; padding: 1rem 2rem; border: none; border-radius: 1rem; font-weight: 600; cursor: pointer;">
                            🎯 Tạo khuyến mãi đầu tiên
                        </button>
                    </div>
                `;
                return;
            }

            let html = `
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 2px solid rgba(255, 255, 255, 0.3);">
                                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-neutral-700);">Khuyến mãi</th>
                                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-neutral-700);">Loại & Giảm giá</th>
                                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-neutral-700);">Thời gian</th>
                                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-neutral-700);">Sử dụng</th>
                                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-neutral-700);">Trạng thái</th>
                                <th style="padding: 1rem; text-align: center; font-weight: 600; color: var(--color-neutral-700);">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            promotions.forEach(promotion => {
                const statusBadge = getPromotionStatusBadge(promotion.computed_status);
                const typeDisplay = getPromotionTypeDisplay(promotion.type, promotion.discount_type, promotion.discount_value);
                const usageDisplay = getPromotionUsageDisplay(promotion);
                
                html += `
                    <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.1); transition: all 0.3s ease;" onmouseover="this.style.background='rgba(255, 255, 255, 0.1)'" onmouseout="this.style.background='transparent'">
                        <td style="padding: 1rem;">
                            <div>
                                <div style="font-weight: 600; color: var(--color-neutral-800); margin-bottom: 0.25rem;">${promotion.name}</div>
                                <div style="font-size: 0.875rem; color: var(--color-neutral-600);">${promotion.description || 'Không có mô tả'}</div>
                                ${promotion.type === 'coupon_code' ? `<div style="font-size: 0.75rem; color: var(--color-blue-600); margin-top: 0.25rem;">🎫 ${promotion.total_coupons} mã</div>` : ''}
                            </div>
                        </td>
                        <td style="padding: 1rem;">
                            ${typeDisplay}
                        </td>
                        <td style="padding: 1rem;">
                            <div style="font-size: 0.875rem;">
                                <div style="color: var(--color-neutral-700);">📅 ${promotion.start_date_formatted}</div>
                                <div style="color: var(--color-neutral-600);">đến ${promotion.end_date_formatted}</div>
                            </div>
                        </td>
                        <td style="padding: 1rem;">
                            ${usageDisplay}
                        </td>
                        <td style="padding: 1rem;">
                            ${statusBadge}
                        </td>
                        <td style="padding: 1rem; text-align: center;">
                            <div style="display: flex; gap: 0.5rem; justify-content: center; flex-wrap: wrap;">
                                <button onclick="editPromotion(${promotion.id})" 
                                        style="background: linear-gradient(135deg, var(--color-blue-500), var(--color-primary-500)); color: white; padding: 0.5rem 1rem; border: none; border-radius: 0.5rem; font-size: 0.875rem; cursor: pointer; display: flex; align-items: center; gap: 0.25rem;" 
                                        title="Chỉnh sửa">
                                    ✏️
                                </button>
                                <button onclick="togglePromotionStatus(${promotion.id}, '${promotion.status === 'active' ? 'inactive' : 'active'}')" 
                                        style="background: linear-gradient(135deg, var(--color-${promotion.status === 'active' ? 'orange' : 'green'}-500), var(--color-${promotion.status === 'active' ? 'red' : 'blue'}-500)); color: white; padding: 0.5rem 1rem; border: none; border-radius: 0.5rem; font-size: 0.875rem; cursor: pointer;" 
                                        title="${promotion.status === 'active' ? 'Tạm dừng' : 'Kích hoạt'}">
                                    ${promotion.status === 'active' ? '⏸️' : '▶️'}
                                </button>
                                <button onclick="viewPromotionAnalytics(${promotion.id})" 
                                        style="background: linear-gradient(135deg, var(--color-green-500), var(--color-blue-500)); color: white; padding: 0.5rem 1rem; border: none; border-radius: 0.5rem; font-size: 0.875rem; cursor: pointer;" 
                                        title="Xem thống kê">
                                    📊
                                </button>
                                <button onclick="confirmDeletePromotion(${promotion.id}, '${promotion.name}')" 
                                        style="background: linear-gradient(135deg, var(--color-red-500), var(--color-pink-500)); color: white; padding: 0.5rem 1rem; border: none; border-radius: 0.5rem; font-size: 0.875rem; cursor: pointer;" 
                                        title="Xóa">
                                    🗑️
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });

            html += `
                        </tbody>
                    </table>
                </div>
            `;

            container.innerHTML = html;
        }

        // Helper functions for promotion display
        function getPromotionStatusBadge(status) {
            const statusConfig = {
                active: { color: 'green', text: 'Đang hoạt động', icon: '✅' },
                inactive: { color: 'gray', text: 'Tạm dừng', icon: '⏸️' },
                scheduled: { color: 'blue', text: 'Đã lên lịch', icon: '⏰' },
                expired: { color: 'red', text: 'Đã hết hạn', icon: '❌' },
                draft: { color: 'orange', text: 'Bản nháp', icon: '📝' }
            };
            
            const config = statusConfig[status] || statusConfig.draft;
            return `
                <span style="display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.5rem 1rem; border-radius: 1rem; 
                             background: linear-gradient(135deg, var(--color-${config.color}-100), var(--color-${config.color}-50)); 
                             color: var(--color-${config.color}-700); font-size: 0.875rem; font-weight: 600;">
                    ${config.icon} ${config.text}
                </span>
            `;
        }

        function getPromotionTypeDisplay(type, discountType, discountValue) {
            const typeNames = {
                percentage: 'Giảm %',
                fixed_amount: 'Giảm cố định',
                buy_one_get_one: 'Mua 1 tặng 1',
                flash_sale: 'Flash Sale',
                coupon_code: 'Mã giảm giá'
            };
            
            const typeName = typeNames[type] || type;
            const valueDisplay = discountType === 'percentage' ? `${discountValue}%` : `${formatPrice(discountValue)}`;
            
            return `
                <div>
                    <div style="font-weight: 600; color: var(--color-primary-600);">${typeName}</div>
                    <div style="font-size: 0.875rem; color: var(--color-neutral-600);">Giảm ${valueDisplay}</div>
                </div>
            `;
        }

        function getPromotionUsageDisplay(promotion) {
            if (!promotion.max_uses) {
                return `
                    <div>
                        <div style="font-weight: 600; color: var(--color-neutral-700);">${promotion.current_uses}</div>
                        <div style="font-size: 0.875rem; color: var(--color-neutral-600);">lần sử dụng</div>
                    </div>
                `;
            }
            
            const percentage = promotion.usage_percentage || 0;
            return `
                <div>
                    <div style="font-weight: 600; color: var(--color-neutral-700);">${promotion.current_uses}/${promotion.max_uses}</div>
                    <div style="background: rgba(255, 255, 255, 0.3); border-radius: 1rem; height: 6px; margin: 0.25rem 0;">
                        <div style="background: linear-gradient(135deg, var(--color-primary-500), var(--color-secondary-500)); 
                                   height: 100%; border-radius: 1rem; width: ${percentage}%; transition: width 0.3s ease;"></div>
                    </div>
                    <div style="font-size: 0.75rem; color: var(--color-neutral-600);">${percentage}% đã sử dụng</div>
                </div>
            `;
        }

        // Modal management functions
        function showAddPromotionModal() {
            document.getElementById('promotionModalTitle').textContent = '🎯 Tạo khuyến mãi mới';
            document.getElementById('promotionForm').reset();
            document.getElementById('promotionId').value = '';
            currentPromotionId = null;
            
            loadProductsForModal();
            loadCategoriesForModal();
            updatePromotionTypeFields();
            updateApplyToFields();
            
            document.getElementById('promotionModal').style.display = 'flex';
        }

        function closePromotionModal() {
            document.getElementById('promotionModal').style.display = 'none';
            document.getElementById('promotionForm').reset();
            currentPromotionId = null;
        }

        function showFlashSaleModal() {
            loadProductsForFlashSale();
            document.getElementById('flashSaleModal').style.display = 'flex';
        }

        function closeFlashSaleModal() {
            document.getElementById('flashSaleModal').style.display = 'none';
            document.getElementById('flashSaleForm').reset();
        }

        function showPromotionAnalytics() {
            document.getElementById('analyticsModal').style.display = 'flex';
            loadOverallAnalytics();
        }

        function closeAnalyticsModal() {
            document.getElementById('analyticsModal').style.display = 'none';
        }

        // Form handling functions
        function updatePromotionTypeFields() {
            const type = document.getElementById('promotionType')?.value;
            const couponContainer = document.getElementById('couponGenerationContainer');
            
            if (type === 'coupon_code') {
                if (couponContainer) couponContainer.style.display = 'block';
            } else {
                if (couponContainer) couponContainer.style.display = 'none';
            }
        }

        function updateApplyToFields() {
            const applyTo = document.getElementById('applyTo')?.value;
            const productContainer = document.getElementById('productSelectionContainer');
            const categoryContainer = document.getElementById('categorySelectionContainer');
            
            if (productContainer) productContainer.style.display = applyTo === 'specific_products' ? 'block' : 'none';
            if (categoryContainer) categoryContainer.style.display = applyTo === 'specific_categories' ? 'block' : 'none';
        }

        // Load products and categories for modals
        async function loadProductsForModal() {
            try {
                const response = await fetch('promotion-handler.php?action=get_products');
                const data = await response.json();

                if (data.success) {
                    const container = document.getElementById('productsList');
                    if (!container) return;
                    
                    let html = '';
                    data.data.forEach(product => {
                        html += `
                            <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; margin-bottom: 0.25rem; 
                                         border-radius: 0.5rem; cursor: pointer; transition: background 0.3s ease;" 
                                   onmouseover="this.style.background='rgba(255, 255, 255, 0.1)'" 
                                   onmouseout="this.style.background='transparent'">
                                <input type="checkbox" name="product_ids[]" value="${product.id}" style="margin: 0;">
                                <span style="flex: 1; color: var(--color-neutral-700);">${product.name}</span>
                                <span style="color: var(--color-neutral-500); font-size: 0.875rem;">${formatPrice(product.price)}</span>
                            </label>
                        `;
                    });
                    container.innerHTML = html;
                }
            } catch (error) {
                console.error('Error loading products:', error);
            }
        }

        async function loadCategoriesForModal() {
            try {
                const response = await fetch('promotion-handler.php?action=get_categories');
                const data = await response.json();

                if (data.success) {
                    const container = document.getElementById('categoriesList');
                    if (!container) return;
                    
                    let html = '';
                    data.data.forEach(category => {
                        html += `
                            <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; margin-bottom: 0.25rem; 
                                         border-radius: 0.5rem; cursor: pointer; transition: background 0.3s ease;" 
                                   onmouseover="this.style.background='rgba(255, 255, 255, 0.1)'" 
                                   onmouseout="this.style.background='transparent'">
                                <input type="checkbox" name="category_ids[]" value="${category.id}" style="margin: 0;">
                                <span style="color: var(--color-neutral-700);">${category.name}</span>
                            </label>
                        `;
                    });
                    container.innerHTML = html;
                }
            } catch (error) {
                console.error('Error loading categories:', error);
            }
        }

        async function loadProductsForFlashSale() {
            try {
                const response = await fetch('promotion-handler.php?action=get_products&limit=50');
                const data = await response.json();

                if (data.success) {
                    const container = document.getElementById('flashSaleProductsList');
                    if (!container) return;
                    
                    let html = '';
                    data.data.forEach(product => {
                        html += `
                            <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; margin-bottom: 0.25rem; 
                                         border-radius: 0.5rem; cursor: pointer; transition: background 0.3s ease;" 
                                   onmouseover="this.style.background='rgba(255, 255, 255, 0.1)'" 
                                   onmouseout="this.style.background='transparent'">
                                <input type="checkbox" name="product_ids[]" value="${product.id}" style="margin: 0;">
                                <span style="flex: 1; color: var(--color-neutral-700);">${product.name}</span>
                                <span style="color: var(--color-neutral-500); font-size: 0.875rem;">${formatPrice(product.price)}</span>
                            </label>
                        `;
                    });
                    container.innerHTML = html || '<p style="color: var(--color-neutral-600); padding: 1rem; text-align: center;">Không có sản phẩm nào</p>';
                }
            } catch (error) {
                console.error('Error loading products for flash sale:', error);
            }
        }

        // Load and display promotions statistics
        async function loadPromotionsStats() {
            try {
                const response = await fetch('promotion-handler.php?action=usage_stats');
                const data = await response.json();

                if (data.success) {
                    displayPromotionsStats(data.data);
                }
            } catch (error) {
                console.error('Error loading promotions stats:', error);
            }
        }

        function displayPromotionsStats(stats) {
            const container = document.getElementById('promotionsStatsContainer');
            if (!container) return;
            
            const html = `
                <div style="background: linear-gradient(135deg, var(--color-primary-100), var(--color-primary-50)); 
                           border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 1.5rem; padding: 1.5rem; text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">🎯</div>
                    <div style="font-size: 1.5rem; font-weight: 700; color: var(--color-primary-600);">${stats.promotion_stats.total_promotions}</div>
                    <div style="color: var(--color-neutral-600); font-size: 0.875rem;">Tổng khuyến mãi</div>
                </div>

                <div style="background: linear-gradient(135deg, var(--color-green-100), var(--color-green-50)); 
                           border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 1.5rem; padding: 1.5rem; text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">✅</div>
                    <div style="font-size: 1.5rem; font-weight: 700; color: var(--color-green-600);">${stats.promotion_stats.active_promotions}</div>
                    <div style="color: var(--color-neutral-600); font-size: 0.875rem;">Đang hoạt động</div>
                </div>

                <div style="background: linear-gradient(135deg, var(--color-blue-100), var(--color-blue-50)); 
                           border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 1.5rem; padding: 1.5rem; text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">📊</div>
                    <div style="font-size: 1.5rem; font-weight: 700; color: var(--color-blue-600);">${stats.usage_stats.total_uses}</div>
                    <div style="color: var(--color-neutral-600); font-size: 0.875rem;">Lượt sử dụng (30 ngày)</div>
                </div>

                <div style="background: linear-gradient(135deg, var(--color-orange-100), var(--color-orange-50)); 
                           border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 1.5rem; padding: 1.5rem; text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">💰</div>
                    <div style="font-size: 1.5rem; font-weight: 700; color: var(--color-orange-600);">${formatPrice(stats.usage_stats.total_discount || 0)}</div>
                    <div style="color: var(--color-neutral-600); font-size: 0.875rem;">Tổng giảm giá</div>
                </div>
            `;

            container.innerHTML = html;
        }

        // CRUD operations
        async function editPromotion(id) {
            try {
                const response = await fetch(`promotion-handler.php?action=get&id=${id}`);
                const data = await response.json();

                if (data.success) {
                    const promotion = data.data;
                    currentPromotionId = id;
                    
                    // Fill form with promotion data
                    document.getElementById('promotionModalTitle').textContent = '✏️ Chỉnh sửa khuyến mãi';
                    document.getElementById('promotionId').value = promotion.id;
                    document.getElementById('promotionName').value = promotion.name;
                    document.getElementById('promotionDescription').value = promotion.description || '';
                    document.getElementById('promotionType').value = promotion.type;
                    document.getElementById('discountType').value = promotion.discount_type;
                    document.getElementById('discountValue').value = promotion.discount_value;
                    document.getElementById('minimumOrderAmount').value = promotion.minimum_order_amount || '';
                    document.getElementById('maxUses').value = promotion.max_uses || '';
                    document.getElementById('maxUsesPerCustomer').value = promotion.max_uses_per_customer || '';
                    document.getElementById('priority').value = promotion.priority;
                    document.getElementById('applyTo').value = promotion.apply_to;
                    document.getElementById('promotionStatus').value = promotion.status;
                    
                    // Set dates
                    const startDate = new Date(promotion.start_date);
                    const endDate = new Date(promotion.end_date);
                    document.getElementById('startDate').value = formatDateTimeLocal(startDate);
                    document.getElementById('endDate').value = formatDateTimeLocal(endDate);
                    
                    await loadProductsForModal();
                    await loadCategoriesForModal();
                    
                    // Select associated products/categories
                    if (promotion.products) {
                        promotion.products.forEach(product => {
                            const checkbox = document.querySelector(`input[name="product_ids[]"][value="${product.id}"]`);
                            if (checkbox) checkbox.checked = true;
                        });
                    }
                    
                    if (promotion.categories) {
                        promotion.categories.forEach(category => {
                            const checkbox = document.querySelector(`input[name="category_ids[]"][value="${category.id}"]`);
                            if (checkbox) checkbox.checked = true;
                        });
                    }
                    
                    updatePromotionTypeFields();
                    updateApplyToFields();
                    
                    document.getElementById('promotionModal').style.display = 'flex';
                } else {
                    showError('Error loading promotion: ' + data.message);
                }
            } catch (error) {
                console.error('Error loading promotion:', error);
                showError('Unable to load promotion');
            }
        }

        async function togglePromotionStatus(id, newStatus) {
            try {
                const formData = new FormData();
                formData.append('action', 'toggle_status');
                formData.append('csrf_token', csrfToken);
                formData.append('id', id);
                formData.append('status', newStatus);

                const response = await fetch('promotion-handler.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showSuccess(data.message);
                    loadPromotions(currentPromotionPage);
                } else {
                    showError('Error updating status: ' + data.message);
                }
            } catch (error) {
                console.error('Error updating status:', error);
                showError('Unable to update status');
            }
        }

        async function confirmDeletePromotion(id, name) {
            if (!confirm(`Bạn có chắc chắn muốn xóa khuyến mãi "${name}"?\n\nLưu ý: Chỉ có thể xóa khuyến mãi chưa được sử dụng.`)) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('csrf_token', csrfToken);
                formData.append('id', id);

                const response = await fetch('promotion-handler.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showSuccess(data.message);
                    loadPromotions(currentPromotionPage);
                } else {
                    showError('Error deleting promotion: ' + data.message);
                }
            } catch (error) {
                console.error('Error deleting promotion:', error);
                showError('Unable to delete promotion');
            }
        }

        // Analytics functions
        async function viewPromotionAnalytics(id) {
            try {
                const response = await fetch(`promotion-handler.php?action=analytics&promotion_id=${id}&period=30`);
                const data = await response.json();

                if (data.success) {
                    displayPromotionAnalytics(data.data);
                    document.getElementById('analyticsModal').style.display = 'flex';
                } else {
                    showError('Error loading analytics: ' + data.message);
                }
            } catch (error) {
                console.error('Error loading analytics:', error);
                showError('Unable to load analytics');
            }
        }

        function displayPromotionAnalytics(analyticsData) {
            const container = document.getElementById('analyticsContent');
            if (!container) return;
            
            const stats = analyticsData.stats;
            const promotion = analyticsData.promotion;
            
            let html = `
                <div style="margin-bottom: 2rem;">
                    <h4 style="color: var(--color-primary-600); margin-bottom: 1rem;">📊 ${promotion.name}</h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                        <div style="background: linear-gradient(135deg, var(--color-blue-100), var(--color-blue-50)); 
                                   border-radius: 1rem; padding: 1.5rem; text-align: center;">
                            <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">📈</div>
                            <div style="font-size: 1.25rem; font-weight: 700; color: var(--color-blue-600);">${stats.total_uses || 0}</div>
                            <div style="color: var(--color-neutral-600); font-size: 0.875rem;">Lượt sử dụng</div>
                        </div>
                        
                        <div style="background: linear-gradient(135deg, var(--color-green-100), var(--color-green-50)); 
                                   border-radius: 1rem; padding: 1.5rem; text-align: center;">
                            <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">👥</div>
                            <div style="font-size: 1.25rem; font-weight: 700; color: var(--color-green-600);">${stats.unique_users || 0}</div>
                            <div style="color: var(--color-neutral-600); font-size: 0.875rem;">Khách hàng sử dụng</div>
                        </div>
                        
                        <div style="background: linear-gradient(135deg, var(--color-orange-100), var(--color-orange-50)); 
                                   border-radius: 1rem; padding: 1.5rem; text-align: center;">
                            <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">💰</div>
                            <div style="font-size: 1.25rem; font-weight: 700; color: var(--color-orange-600);">${formatPrice(stats.total_discount || 0)}</div>
                            <div style="color: var(--color-neutral-600); font-size: 0.875rem;">Tổng giảm giá</div>
                        </div>
                        
                        <div style="background: linear-gradient(135deg, var(--color-purple-100), var(--color-purple-50)); 
                                   border-radius: 1rem; padding: 1.5rem; text-align: center;">
                            <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">🛒</div>
                            <div style="font-size: 1.25rem; font-weight: 700; color: var(--color-purple-600);">${formatPrice(stats.avg_order_value || 0)}</div>
                            <div style="color: var(--color-neutral-600); font-size: 0.875rem;">Giá trị đơn hàng TB</div>
                        </div>
                    </div>
                </div>
            `;

            container.innerHTML = html;
        }

        async function loadOverallAnalytics() {
            try {
                const response = await fetch('promotion-handler.php?action=usage_stats');
                const data = await response.json();

                if (data.success) {
                    displayOverallAnalytics(data.data);
                }
            } catch (error) {
                console.error('Error loading overall analytics:', error);
            }
        }

        function displayOverallAnalytics(data) {
            const container = document.getElementById('analyticsContent');
            if (!container) return;
            
            const html = `
                <div style="margin-bottom: 2rem;">
                    <h4 style="color: var(--color-primary-600); margin-bottom: 1rem;">📊 Tổng quan khuyến mãi</h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                        <div style="background: linear-gradient(135deg, var(--color-blue-100), var(--color-blue-50)); 
                                   border-radius: 1rem; padding: 1.5rem; text-align: center;">
                            <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">🎯</div>
                            <div style="font-size: 1.25rem; font-weight: 700; color: var(--color-blue-600);">${data.promotion_stats.total_promotions}</div>
                            <div style="color: var(--color-neutral-600); font-size: 0.875rem;">Tổng khuyến mãi</div>
                        </div>
                        
                        <div style="background: linear-gradient(135deg, var(--color-green-100), var(--color-green-50)); 
                                   border-radius: 1rem; padding: 1.5rem; text-align: center;">
                            <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">✅</div>
                            <div style="font-size: 1.25rem; font-weight: 700; color: var(--color-green-600);">${data.promotion_stats.active_promotions}</div>
                            <div style="color: var(--color-neutral-600); font-size: 0.875rem;">Đang hoạt động</div>
                        </div>
                        
                        <div style="background: linear-gradient(135deg, var(--color-orange-100), var(--color-orange-50)); 
                                   border-radius: 1rem; padding: 1.5rem; text-align: center;">
                            <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">📈</div>
                            <div style="font-size: 1.25rem; font-weight: 700; color: var(--color-orange-600);">${data.usage_stats.total_uses || 0}</div>
                            <div style="color: var(--color-neutral-600); font-size: 0.875rem;">Lượt sử dụng (30 ngày)</div>
                        </div>
                        
                        <div style="background: linear-gradient(135deg, var(--color-purple-100), var(--color-purple-50)); 
                                   border-radius: 1rem; padding: 1.5rem; text-align: center;">
                            <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">💰</div>
                            <div style="font-size: 1.25rem; font-weight: 700; color: var(--color-purple-600);">${formatPrice(data.usage_stats.total_discount || 0)}</div>
                            <div style="color: var(--color-neutral-600); font-size: 0.875rem;">Tổng giảm giá</div>
                        </div>
                    </div>
                </div>
            `;

            container.innerHTML = html;
        }

        // Utility functions
        function formatDateTimeLocal(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            
            return `${year}-${month}-${day}T${hours}:${minutes}`;
        }

        function closeModal(event) {
            if (event.target === event.currentTarget) {
                const modal = event.currentTarget;
                modal.style.display = 'none';
            }
        }

        // Form submission handlers for promotions
        document.addEventListener('DOMContentLoaded', function() {
            // Promotion form handler
            const promotionForm = document.getElementById('promotionForm');
            if (promotionForm) {
                promotionForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    try {
                        const formData = new FormData(promotionForm);
                        formData.append('action', currentPromotionId ? 'edit' : 'add');
                        formData.append('csrf_token', csrfToken);
                        
                        // Collect selected products
                        const selectedProducts = Array.from(document.querySelectorAll('input[name="product_ids[]"]:checked'))
                            .map(cb => cb.value);
                        selectedProducts.forEach(id => formData.append('product_ids[]', id));
                        
                        // Collect selected categories
                        const selectedCategories = Array.from(document.querySelectorAll('input[name="category_ids[]"]:checked'))
                            .map(cb => cb.value);
                        selectedCategories.forEach(id => formData.append('category_ids[]', id));

                        const response = await fetch('promotion-handler.php', {
                            method: 'POST',
                            body: formData
                        });

                        const data = await response.json();

                        if (data.success) {
                            showSuccess(data.message);
                            closePromotionModal();
                            loadPromotions(currentPromotionPage);
                        } else {
                            showError('Error: ' + data.message);
                        }
                    } catch (error) {
                        console.error('Error saving promotion:', error);
                        showError('Unable to save promotion');
                    }
                });
            }

            // Flash sale form handler
            const flashSaleForm = document.getElementById('flashSaleForm');
            if (flashSaleForm) {
                flashSaleForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    try {
                        const formData = new FormData(flashSaleForm);
                        formData.append('action', 'flash_sale');
                        formData.append('flash_action', 'create');
                        formData.append('csrf_token', csrfToken);
                        
                        // Collect selected products for flash sale
                        const selectedProducts = Array.from(document.querySelectorAll('#flashSaleProductsList input[name="product_ids[]"]:checked'))
                            .map(cb => cb.value);
                        selectedProducts.forEach(id => formData.append('product_ids[]', id));

                        const response = await fetch('promotion-handler.php', {
                            method: 'POST',
                            body: formData
                        });

                        const data = await response.json();

                        if (data.success) {
                            showSuccess(data.message);
                            closeFlashSaleModal();
                            loadPromotions(currentPromotionPage);
                        } else {
                            showError('Error creating flash sale: ' + data.message);
                        }
                    } catch (error) {
                        console.error('Error creating flash sale:', error);
                        showError('Unable to create flash sale');
                    }
                });
            }

            // Add search and filter functionality for promotions
            document.addEventListener('input', function(e) {
                if (e.target.id === 'promotionSearch') {
                    // Debounce search
                    clearTimeout(e.target.searchTimeout);
                    e.target.searchTimeout = setTimeout(() => {
                        loadPromotions(1);
                    }, 500);
                }
            });

            document.addEventListener('change', function(e) {
                if (e.target.id === 'promotionTypeFilter' || e.target.id === 'promotionStatusFilter') {
                    loadPromotions(1);
                }
            });
        });

        // ===== CUSTOMER MANAGEMENT JAVASCRIPT =====
        
        // Global variables for customer management
        let currentCustomerPage = 1;
        let currentCustomerId = null;
        let customerSearchTimeout = null;
        
        // Load customers from server
        async function loadCustomers(page = 1) {
            try {
                // Show loading state
                showCustomersState('loading');
                
                const search = document.getElementById('customerSearchInput')?.value || '';
                const status = document.getElementById('customerStatusFilter')?.value || '';
                const spendingLevel = document.getElementById('customerSpendingFilter')?.value || '';
                const sortFilter = document.getElementById('customerSortFilter')?.value || 'created_at-DESC';
                
                const [sort, order] = sortFilter.split('-');
                
                const params = new URLSearchParams({
                    action: 'list',
                    page: page,
                    limit: 10,
                    search: search,
                    status: status,
                    spending_level: spendingLevel,
                    sort: sort,
                    order: order
                });

                const response = await fetch(`customer-handler.php?${params}`);
                const data = await response.json();

                if (data.success) {
                    displayCustomers(data.data.customers);
                    displayCustomersPagination(data.data.pagination);
                    currentCustomerPage = data.data.pagination.current_page;
                    
                    if (data.data.customers.length === 0) {
                        showCustomersState('empty');
                    } else {
                        showCustomersState('content');
                    }
                } else {
                    showCustomersState('error', data.message);
                }
            } catch (error) {
                console.error('Error loading customers:', error);
                showCustomersState('error', 'Không thể tải danh sách khách hàng');
            }
        }
        
        // Load customer analytics overview
        async function loadCustomerOverview() {
            try {
                const response = await fetch('customer-handler.php?action=analytics');
                const data = await response.json();

                if (data.success) {
                    displayCustomerOverview(data.data.overall_stats);
                }
            } catch (error) {
                console.error('Error loading customer overview:', error);
            }
        }
        
        // Display customer overview statistics
        function displayCustomerOverview(stats) {
            const container = document.getElementById('customerOverviewStats');
            if (!container) return;
            
            const html = `
                <div style="background: linear-gradient(135deg, var(--color-primary-100), var(--color-primary-50)); 
                           border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 1.5rem; padding: 1.5rem; text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">👥</div>
                    <div style="font-size: 1.5rem; font-weight: 700; color: var(--color-primary-600);">${stats.total_customers || 0}</div>
                    <div style="color: var(--color-neutral-600); font-size: 0.875rem;">Tổng khách hàng</div>
                </div>

                <div style="background: linear-gradient(135deg, var(--color-green-100), var(--color-green-50)); 
                           border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 1.5rem; padding: 1.5rem; text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">✅</div>
                    <div style="font-size: 1.5rem; font-weight: 700; color: var(--color-green-600);">${stats.active_customers || 0}</div>
                    <div style="color: var(--color-neutral-600); font-size: 0.875rem;">Đang hoạt động</div>
                </div>

                <div style="background: linear-gradient(135deg, var(--color-orange-100), var(--color-orange-50)); 
                           border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 1.5rem; padding: 1.5rem; text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">👑</div>
                    <div style="font-size: 1.5rem; font-weight: 700; color: var(--color-orange-600);">${stats.vip_customers || 0}</div>
                    <div style="color: var(--color-neutral-600); font-size: 0.875rem;">Khách VIP</div>
                </div>

                <div style="background: linear-gradient(135deg, var(--color-blue-100), var(--color-blue-50)); 
                           border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 1.5rem; padding: 1.5rem; text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">🆕</div>
                    <div style="font-size: 1.5rem; font-weight: 700; color: var(--color-blue-600);">${stats.new_customers || 0}</div>
                    <div style="color: var(--color-neutral-600); font-size: 0.875rem;">Khách mới (30 ngày)</div>
                </div>

                <div style="background: linear-gradient(135deg, var(--color-purple-100), var(--color-purple-50)); 
                           border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 1.5rem; padding: 1.5rem; text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">💰</div>
                    <div style="font-size: 1.5rem; font-weight: 700; color: var(--color-purple-600);">${formatPrice(stats.avg_customer_value || 0)}</div>
                    <div style="color: var(--color-neutral-600); font-size: 0.875rem;">Giá trị TB/khách</div>
                </div>
            `;

            container.innerHTML = html;
        }
        
        // Display customers in table format
        function displayCustomers(customers) {
            const tableBody = document.getElementById('customersTableBody');
            if (!tableBody) return;

            if (!customers || customers.length === 0) {
                tableBody.innerHTML = '';
                return;
            }

            let html = '';
            customers.forEach(customer => {
                const statusBadge = getCustomerStatusBadge(customer.customer_status, customer.status_info);
                const segmentBadge = getCustomerSegmentBadge(customer.customer_segment, customer.segment_info);
                
                html += `
                    <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.1); transition: all 0.3s ease;" 
                        onmouseover="this.style.background='rgba(255, 255, 255, 0.05)'" 
                        onmouseout="this.style.background='transparent'">
                        <td style="padding: 1.5rem 1rem;">
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <div style="width: 50px; height: 50px; background: linear-gradient(135deg, var(--color-secondary-500), var(--color-accent-500)); 
                                           border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; 
                                           font-weight: 700; font-size: 1.25rem;">
                                    ${customer.name.charAt(0).toUpperCase()}
                                </div>
                                <div>
                                    <div style="font-weight: 600; color: var(--color-neutral-800); margin-bottom: 0.25rem;">${customer.name}</div>
                                    <div style="display: flex; gap: 0.5rem;">
                                        ${segmentBadge}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 1.5rem 1rem;">
                            <div style="color: var(--color-neutral-700); margin-bottom: 0.25rem;">📧 ${customer.email}</div>
                            <div style="color: var(--color-neutral-600); font-size: 0.875rem;">
                                📞 ${customer.phone || 'Chưa có'}
                            </div>
                        </td>
                        <td style="padding: 1.5rem 1rem; text-align: center;">
                            ${statusBadge}
                        </td>
                        <td style="padding: 1.5rem 1rem; text-align: center;">
                            <div style="font-weight: 600; color: var(--color-neutral-800); margin-bottom: 0.25rem;">
                                ${customer.total_orders}
                            </div>
                            <div style="color: var(--color-neutral-600); font-size: 0.875rem;">
                                TB: ${formatPrice(customer.avg_order_value)}
                            </div>
                        </td>
                        <td style="padding: 1.5rem 1rem; text-align: right;">
                            <div style="font-weight: 700; color: var(--color-primary-600); font-size: 1.125rem; margin-bottom: 0.25rem;">
                                ${formatPrice(customer.total_spent)}
                            </div>
                            <div style="color: var(--color-neutral-600); font-size: 0.875rem;">
                                Gần nhất: ${customer.last_order_formatted}
                            </div>
                        </td>
                        <td style="padding: 1.5rem 1rem; text-align: center;">
                            <div style="color: var(--color-neutral-700); margin-bottom: 0.25rem;">
                                ${customer.created_at_formatted}
                            </div>
                        </td>
                        <td style="padding: 1.5rem 1rem; text-align: center;">
                            <div style="display: flex; gap: 0.5rem; justify-content: center;">
                                <button onclick="viewCustomerDetails(${customer.id})" 
                                        style="background: linear-gradient(135deg, var(--color-primary-500), var(--color-secondary-500)); 
                                               color: white; border: none; padding: 0.5rem 1rem; border-radius: 0.75rem; 
                                               cursor: pointer; font-size: 0.875rem; transition: all 0.3s ease;"
                                        onmouseover="this.style.transform='translateY(-2px)'" 
                                        onmouseout="this.style.transform='translateY(0)'">
                                    👁️ Xem
                                </button>
                                <button onclick="showAddNoteModal(${customer.id})" 
                                        style="background: linear-gradient(135deg, rgba(255, 255, 255, 0.25), rgba(255, 255, 255, 0.15)); 
                                               backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.3); 
                                               color: var(--color-neutral-700); padding: 0.5rem 1rem; border-radius: 0.75rem; 
                                               cursor: pointer; font-size: 0.875rem; transition: all 0.3s ease;"
                                        onmouseover="this.style.transform='translateY(-2px)'" 
                                        onmouseout="this.style.transform='translateY(0)'">
                                    📝 Ghi chú
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });

            tableBody.innerHTML = html;
        }
        
        // Display customers pagination
        function displayCustomersPagination(pagination) {
            const container = document.getElementById('customersPaginationContainer');
            if (!container) return;

            if (pagination.total_pages <= 1) {
                container.innerHTML = '';
                return;
            }

            let html = `
                <div style="display: flex; align-items: center; gap: 1rem; background: linear-gradient(135deg, rgba(255, 255, 255, 0.25), rgba(255, 255, 255, 0.15)); 
                           backdrop-filter: blur(25px); border: 1px solid rgba(255, 255, 255, 0.3); border-radius: 2rem; padding: 1rem 2rem;">
                    <span style="color: var(--color-neutral-600); font-size: 0.875rem;">
                        Trang ${pagination.current_page} / ${pagination.total_pages} (${pagination.total_customers} khách hàng)
                    </span>
                    
                    <div style="display: flex; gap: 0.5rem;">
            `;

            // Previous button
            if (pagination.has_prev) {
                html += `
                    <button onclick="loadCustomers(${pagination.current_page - 1})" 
                            style="background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3); 
                                   color: var(--color-neutral-700); padding: 0.5rem 1rem; border-radius: 0.75rem; 
                                   cursor: pointer; transition: all 0.3s ease;"
                            onmouseover="this.style.background='rgba(255, 255, 255, 0.3)'" 
                            onmouseout="this.style.background='rgba(255, 255, 255, 0.2)'">
                        ← Trước
                    </button>
                `;
            }

            // Page numbers (show current and 2 pages around it)
            const startPage = Math.max(1, pagination.current_page - 2);
            const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);

            for (let i = startPage; i <= endPage; i++) {
                const isActive = i === pagination.current_page;
                html += `
                    <button onclick="loadCustomers(${i})" 
                            style="background: ${isActive ? 'var(--color-primary-500)' : 'rgba(255, 255, 255, 0.2)'}; 
                                   border: 1px solid rgba(255, 255, 255, 0.3); 
                                   color: ${isActive ? 'white' : 'var(--color-neutral-700)'}; 
                                   padding: 0.5rem 0.75rem; border-radius: 0.75rem; cursor: pointer; 
                                   transition: all 0.3s ease; min-width: 40px;"
                            onmouseover="if (!${isActive}) { this.style.background='rgba(255, 255, 255, 0.3)'; }" 
                            onmouseout="if (!${isActive}) { this.style.background='rgba(255, 255, 255, 0.2)'; }">
                        ${i}
                    </button>
                `;
            }

            // Next button
            if (pagination.has_next) {
                html += `
                    <button onclick="loadCustomers(${pagination.current_page + 1})" 
                            style="background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3); 
                                   color: var(--color-neutral-700); padding: 0.5rem 1rem; border-radius: 0.75rem; 
                                   cursor: pointer; transition: all 0.3s ease;"
                            onmouseover="this.style.background='rgba(255, 255, 255, 0.3)'" 
                            onmouseout="this.style.background='rgba(255, 255, 255, 0.2)'">
                        Sau →
                    </button>
                `;
            }

            html += `
                    </div>
                </div>
            `;

            container.innerHTML = html;
        }
        
        // Show different customer states (loading, error, empty, content)
        function showCustomersState(state, message = '') {
            const loadingState = document.getElementById('customersLoadingState');
            const errorState = document.getElementById('customersErrorState');
            const emptyState = document.getElementById('customersEmptyState');
            const tableContainer = document.getElementById('customersTableContainer');
            
            // Hide all states
            if (loadingState) loadingState.style.display = 'none';
            if (errorState) errorState.style.display = 'none';
            if (emptyState) emptyState.style.display = 'none';
            if (tableContainer) tableContainer.style.display = 'none';
            
            // Show appropriate state
            switch (state) {
                case 'loading':
                    if (loadingState) loadingState.style.display = 'block';
                    break;
                case 'error':
                    if (errorState) {
                        errorState.style.display = 'block';
                        const messageEl = document.getElementById('customersErrorMessage');
                        if (messageEl) messageEl.textContent = message;
                    }
                    break;
                case 'empty':
                    if (emptyState) emptyState.style.display = 'block';
                    break;
                case 'content':
                default:
                    if (tableContainer) tableContainer.style.display = 'block';
                    break;
            }
        }
        
        // Search functionality with debounce
        function debounceCustomerSearch() {
            if (customerSearchTimeout) {
                clearTimeout(customerSearchTimeout);
            }
            customerSearchTimeout = setTimeout(() => {
                loadCustomers(1); // Reset to page 1 when searching
            }, 500);
        }
        
        // Customer detail modal
        async function viewCustomerDetails(customerId) {
            try {
                currentCustomerId = customerId;
                
                const response = await fetch(`customer-handler.php?action=get&id=${customerId}`);
                const data = await response.json();

                if (data.success) {
                    displayCustomerDetailModal(data.data);
                } else {
                    showMessage('Không thể tải thông tin khách hàng: ' + data.message, 'error');
                }
            } catch (error) {
                console.error('Error loading customer details:', error);
                showMessage('Có lỗi xảy ra khi tải thông tin khách hàng', 'error');
            }
        }
        
        // Customer Analytics Modal
        function showCustomerAnalytics() {
            document.getElementById('customerAnalyticsModal').style.display = 'flex';
            loadCustomerAnalyticsData();
        }
        
        function closeCustomerAnalyticsModal() {
            document.getElementById('customerAnalyticsModal').style.display = 'none';
        }
        
        async function loadCustomerAnalyticsData() {
            try {
                const response = await fetch('customer-handler.php?action=analytics');
                const data = await response.json();

                if (data.success) {
                    displayCustomerAnalyticsModal(data.data);
                }
            } catch (error) {
                console.error('Error loading customer analytics:', error);
            }
        }
        
        function displayCustomerAnalyticsModal(data) {
            const container = document.getElementById('customerAnalyticsContent');
            if (!container) return;
            
            const content = `
                <!-- Overall Statistics -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                    <div style="background: linear-gradient(135deg, var(--color-primary-100), var(--color-primary-50)); 
                               border-radius: 1.5rem; padding: 1.5rem; text-align: center;">
                        <div style="font-size: 2rem; margin-bottom: 0.5rem;">👥</div>
                        <div style="font-size: 2rem; font-weight: 700; color: var(--color-primary-600);">${data.overall_stats.total_customers}</div>
                        <div style="color: var(--color-neutral-600);">Tổng khách hàng</div>
                    </div>
                    <div style="background: linear-gradient(135deg, var(--color-green-100), var(--color-green-50)); 
                               border-radius: 1.5rem; padding: 1.5rem; text-align: center;">
                        <div style="font-size: 2rem; margin-bottom: 0.5rem;">✅</div>
                        <div style="font-size: 2rem; font-weight: 700; color: var(--color-green-600);">${data.overall_stats.active_customers}</div>
                        <div style="color: var(--color-neutral-600);">Đang hoạt động</div>
                    </div>
                    <div style="background: linear-gradient(135deg, var(--color-orange-100), var(--color-orange-50)); 
                               border-radius: 1.5rem; padding: 1.5rem; text-align: center;">
                        <div style="font-size: 2rem; margin-bottom: 0.5rem;">👑</div>
                        <div style="font-size: 2rem; font-weight: 700; color: var(--color-orange-600);">${data.overall_stats.vip_customers}</div>
                        <div style="color: var(--color-neutral-600);">Khách VIP</div>
                    </div>
                    <div style="background: linear-gradient(135deg, var(--color-purple-100), var(--color-purple-50)); 
                               border-radius: 1.5rem; padding: 1.5rem; text-align: center;">
                        <div style="font-size: 2rem; margin-bottom: 0.5rem;">💰</div>
                        <div style="font-size: 1.5rem; font-weight: 700; color: var(--color-purple-600);">${formatPrice(data.overall_stats.avg_customer_value)}</div>
                        <div style="color: var(--color-neutral-600);">Giá trị TB/khách</div>
                    </div>
                </div>
                
                <!-- Customer Lifetime Value Distribution -->
                <div style="background: rgba(255, 255, 255, 0.1); border-radius: 1.5rem; padding: 1.5rem; margin-bottom: 2rem;">
                    <h3 style="margin: 0 0 1rem 0; color: var(--color-primary-600);">💎 Phân bố giá trị khách hàng</h3>
                    <div style="display: grid; gap: 1rem;">
                        ${data.clv_distribution.map(segment => `
                            <div style="display: flex; justify-content: space-between; align-items: center; 
                                       padding: 1rem; background: rgba(255, 255, 255, 0.1); border-radius: 1rem;">
                                <span style="font-weight: 600; color: var(--color-neutral-800);">${segment.value_segment}</span>
                                <span style="font-weight: 700; color: var(--color-primary-600);">${segment.customer_count} khách</span>
                            </div>
                        `).join('')}
                    </div>
                </div>
                
                <!-- Monthly Customer Acquisition -->
                <div style="background: rgba(255, 255, 255, 0.1); border-radius: 1.5rem; padding: 1.5rem;">
                    <h3 style="margin: 0 0 1rem 0; color: var(--color-primary-600);">📈 Khách hàng mới theo tháng (12 tháng gần đây)</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 1rem;">
                        ${data.monthly_acquisition.map(month => `
                            <div style="text-align: center; padding: 1rem; background: rgba(255, 255, 255, 0.1); border-radius: 1rem;">
                                <div style="font-weight: 700; color: var(--color-primary-600);">${month.new_customers}</div>
                                <div style="font-size: 0.875rem; color: var(--color-neutral-600);">${month.month_formatted}</div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
            
            container.innerHTML = content;
        }
        
        // Display customer detail modal
        function displayCustomerDetailModal(data) {
            const modal = document.getElementById('customerDetailModal');
            const modalContent = modal.querySelector('div');
            
            const customer = data.customer;
            const recentOrders = data.recent_orders || [];
            const favoriteProducts = data.favorite_products || [];
            const notes = data.notes || [];
            
            const statusBadge = getCustomerStatusBadge(customer.customer_status, customer.status_info);
            
            const content = `
                <div style="padding: 2rem;">
                    <!-- Header -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; 
                               border-bottom: 1px solid rgba(255, 255, 255, 0.3); padding-bottom: 1rem;">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="width: 80px; height: 80px; background: linear-gradient(135deg, var(--color-secondary-500), var(--color-accent-500)); 
                                       border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; 
                                       font-weight: 700; font-size: 2rem;">
                                ${customer.name.charAt(0).toUpperCase()}
                            </div>
                            <div>
                                <h2 style="margin: 0 0 0.5rem 0; font-size: 1.75rem; color: var(--color-primary-600);">${customer.name}</h2>
                                <div style="display: flex; gap: 1rem; align-items: center;">
                                    ${statusBadge}
                                    <span style="color: var(--color-neutral-600); font-size: 0.875rem;">
                                        Khách hàng từ ${customer.created_at_formatted}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <button onclick="closeCustomerDetailModal()" 
                                style="background: none; border: none; font-size: 1.5rem; cursor: pointer; 
                                       color: var(--color-neutral-500); width: 40px; height: 40px; border-radius: 50%; 
                                       display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;"
                                onmouseover="this.style.background='rgba(239, 68, 68, 0.1)'; this.style.color='var(--color-error-600)'" 
                                onmouseout="this.style.background='none'; this.style.color='var(--color-neutral-500)'">✕</button>
                    </div>
                    
                    <!-- Content Grid -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                        <!-- Left Column -->
                        <div>
                            <!-- Customer Info -->
                            <div style="background: rgba(255, 255, 255, 0.1); border-radius: 1.5rem; padding: 1.5rem; margin-bottom: 1.5rem;">
                                <h3 style="margin: 0 0 1rem 0; color: var(--color-primary-600); display: flex; align-items: center; gap: 0.5rem;">
                                    📋 Thông tin cá nhân
                                </h3>
                                <div style="display: grid; gap: 1rem;">
                                    <div>
                                        <label style="font-weight: 600; color: var(--color-neutral-700); display: block; margin-bottom: 0.25rem;">Email:</label>
                                        <span style="color: var(--color-neutral-800);">${customer.email}</span>
                                    </div>
                                    <div>
                                        <label style="font-weight: 600; color: var(--color-neutral-700); display: block; margin-bottom: 0.25rem;">Điện thoại:</label>
                                        <span style="color: var(--color-neutral-800);">${customer.phone || 'Chưa có thông tin'}</span>
                                    </div>
                                    <div>
                                        <label style="font-weight: 600; color: var(--color-neutral-700); display: block; margin-bottom: 0.25rem;">Ngày đăng ký:</label>
                                        <span style="color: var(--color-neutral-800);">${customer.created_at_formatted}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Customer Analytics -->
                            <div style="background: rgba(255, 255, 255, 0.1); border-radius: 1.5rem; padding: 1.5rem; margin-bottom: 1.5rem;">
                                <h3 style="margin: 0 0 1rem 0; color: var(--color-primary-600); display: flex; align-items: center; gap: 0.5rem;">
                                    📊 Thống kê mua hàng
                                </h3>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                    <div style="text-align: center; background: rgba(255, 255, 255, 0.1); border-radius: 1rem; padding: 1rem;">
                                        <div style="font-size: 1.5rem; font-weight: 700; color: var(--color-primary-600);">${customer.total_orders}</div>
                                        <div style="font-size: 0.875rem; color: var(--color-neutral-600);">Tổng đơn hàng</div>
                                    </div>
                                    <div style="text-align: center; background: rgba(255, 255, 255, 0.1); border-radius: 1rem; padding: 1rem;">
                                        <div style="font-size: 1.5rem; font-weight: 700; color: var(--color-green-600);">${formatPrice(customer.total_spent)}</div>
                                        <div style="font-size: 0.875rem; color: var(--color-neutral-600);">Tổng chi tiêu</div>
                                    </div>
                                    <div style="text-align: center; background: rgba(255, 255, 255, 0.1); border-radius: 1rem; padding: 1rem;">
                                        <div style="font-size: 1.5rem; font-weight: 700; color: var(--color-blue-600);">${formatPrice(customer.avg_order_value)}</div>
                                        <div style="font-size: 0.875rem; color: var(--color-neutral-600);">Giá trị TB/đơn</div>
                                    </div>
                                    <div style="text-align: center; background: rgba(255, 255, 255, 0.1); border-radius: 1rem; padding: 1rem;">
                                        <div style="font-size: 1.5rem; font-weight: 700; color: var(--color-orange-600);">${customer.last_order_formatted}</div>
                                        <div style="font-size: 0.875rem; color: var(--color-neutral-600);">Đơn gần nhất</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Favorite Products -->
                            <div style="background: rgba(255, 255, 255, 0.1); border-radius: 1.5rem; padding: 1.5rem;">
                                <h3 style="margin: 0 0 1rem 0; color: var(--color-primary-600); display: flex; align-items: center; gap: 0.5rem;">
                                    ⭐ Sản phẩm yêu thích
                                </h3>
                                ${favoriteProducts.length > 0 ? 
                                    favoriteProducts.map(product => `
                                        <div style="display: flex; justify-content: space-between; align-items: center; 
                                                   padding: 0.75rem; border-bottom: 1px solid rgba(255, 255, 255, 0.1); margin-bottom: 0.5rem;">
                                            <div>
                                                <div style="font-weight: 600; color: var(--color-neutral-800);">${product.name}</div>
                                                <div style="font-size: 0.875rem; color: var(--color-neutral-600);">${product.category_name || 'N/A'}</div>
                                            </div>
                                            <div style="text-align: right;">
                                                <div style="font-weight: 600; color: var(--color-primary-600);">${product.total_quantity}</div>
                                                <div style="font-size: 0.875rem; color: var(--color-neutral-600);">lần mua</div>
                                            </div>
                                        </div>
                                    `).join('') 
                                    : '<p style="color: var(--color-neutral-600); text-align: center; padding: 1rem;">Chưa có sản phẩm yêu thích</p>'
                                }
                            </div>
                        </div>
                        
                        <!-- Right Column -->
                        <div>
                            <!-- Recent Orders -->
                            <div style="background: rgba(255, 255, 255, 0.1); border-radius: 1.5rem; padding: 1.5rem; margin-bottom: 1.5rem;">
                                <h3 style="margin: 0 0 1rem 0; color: var(--color-primary-600); display: flex; align-items: center; gap: 0.5rem;">
                                    🛒 Đơn hàng gần đây
                                </h3>
                                <div style="max-height: 300px; overflow-y: auto;">
                                    ${recentOrders.length > 0 ? 
                                        recentOrders.map(order => `
                                            <div style="display: flex; justify-content: space-between; align-items: center; 
                                                       padding: 1rem; border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 1rem; 
                                                       margin-bottom: 0.75rem; background: rgba(255, 255, 255, 0.05);">
                                                <div>
                                                    <div style="font-weight: 600; color: var(--color-neutral-800);">#${order.id}</div>
                                                    <div style="font-size: 0.875rem; color: var(--color-neutral-600);">${order.created_at_formatted}</div>
                                                    <div style="font-size: 0.875rem; color: var(--color-neutral-600);">${order.item_count} sản phẩm</div>
                                                </div>
                                                <div style="text-align: right;">
                                                    <div style="font-weight: 700; color: var(--color-primary-600);">${formatPrice(order.total_amount)}</div>
                                                    <div style="font-size: 0.875rem;">
                                                        <span style="background: ${getOrderStatusColor(order.status)}; color: white; 
                                                                   padding: 0.25rem 0.5rem; border-radius: 0.5rem;">
                                                            ${order.status}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        `).join('') 
                                        : '<p style="color: var(--color-neutral-600); text-align: center; padding: 1rem;">Chưa có đơn hàng nào</p>'
                                    }
                                </div>
                            </div>
                            
                            <!-- Customer Notes -->
                            <div style="background: rgba(255, 255, 255, 0.1); border-radius: 1.5rem; padding: 1.5rem;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                    <h3 style="margin: 0; color: var(--color-primary-600); display: flex; align-items: center; gap: 0.5rem;">
                                        📝 Ghi chú nội bộ
                                    </h3>
                                    <button onclick="showAddNoteModal(${customer.id})" 
                                            style="background: var(--color-primary-500); color: white; border: none; 
                                                   padding: 0.5rem 1rem; border-radius: 0.75rem; cursor: pointer; 
                                                   font-size: 0.875rem; transition: all 0.3s ease;">
                                        + Thêm ghi chú
                                    </button>
                                </div>
                                <div style="max-height: 250px; overflow-y: auto;">
                                    ${notes.length > 0 ? 
                                        notes.map(note => `
                                            <div style="border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 1rem; 
                                                       padding: 1rem; margin-bottom: 0.75rem; background: rgba(255, 255, 255, 0.05);">
                                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                                                    <div style="font-weight: 600; color: var(--color-neutral-800);">
                                                        ${note.title || note.note_type}
                                                    </div>
                                                    <div style="font-size: 0.75rem; color: var(--color-neutral-600);">
                                                        ${note.created_at_formatted}
                                                    </div>
                                                </div>
                                                <div style="color: var(--color-neutral-700); font-size: 0.875rem; margin-bottom: 0.5rem;">
                                                    ${note.content}
                                                </div>
                                                <div style="font-size: 0.75rem; color: var(--color-neutral-600);">
                                                    Bởi: ${note.staff_name || 'Hệ thống'} • 
                                                    <span style="background: ${getNotePriorityColor(note.priority)}; color: white; 
                                                               padding: 0.125rem 0.375rem; border-radius: 0.375rem;">
                                                        ${note.priority}
                                                    </span>
                                                </div>
                                            </div>
                                        `).join('') 
                                        : '<p style="color: var(--color-neutral-600); text-align: center; padding: 1rem;">Chưa có ghi chú nào</p>'
                                    }
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 2rem; 
                               border-top: 1px solid rgba(255, 255, 255, 0.3); padding-top: 1rem;">
                        <button onclick="showAddNoteModal(${customer.id})" 
                                style="background: linear-gradient(135deg, var(--color-green-500), var(--color-blue-500)); 
                                       color: white; border: none; padding: 1rem 1.5rem; border-radius: 1rem; 
                                       cursor: pointer; font-weight: 600; transition: all 0.3s ease;">
                            📝 Thêm ghi chú
                        </button>
                        <button onclick="updateCustomerStatus(${customer.id})" 
                                style="background: linear-gradient(135deg, var(--color-orange-500), var(--color-pink-500)); 
                                       color: white; border: none; padding: 1rem 1.5rem; border-radius: 1rem; 
                                       cursor: pointer; font-weight: 600; transition: all 0.3s ease;">
                            🔄 Cập nhật trạng thái
                        </button>
                        <button onclick="closeCustomerDetailModal()" 
                                style="background: linear-gradient(135deg, rgba(255, 255, 255, 0.25), rgba(255, 255, 255, 0.15)); 
                                       backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.3); 
                                       color: var(--color-neutral-700); padding: 1rem 1.5rem; border-radius: 1rem; 
                                       cursor: pointer; font-weight: 600; transition: all 0.3s ease;">
                            Đóng
                        </button>
                    </div>
                </div>
            `;
            
            modalContent.innerHTML = content;
            modal.style.display = 'flex';
        }
        
        // Update customer status
        async function updateCustomerStatus(customerId) {
            const status = prompt('Nhập trạng thái mới (active/inactive/VIP):');
            if (!status || !['active', 'inactive', 'VIP'].includes(status)) {
                showMessage('Trạng thái không hợp lệ', 'error');
                return;
            }
            
            const note = prompt('Ghi chú (tùy chọn):') || '';
            
            try {
                const formData = new FormData();
                formData.append('action', 'update_status');
                formData.append('customer_id', customerId);
                formData.append('status', status);
                formData.append('note', note);
                formData.append('csrf_token', csrfToken);
                
                const response = await fetch('customer-handler.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showMessage('Cập nhật trạng thái thành công!', 'success');
                    loadCustomers(currentCustomerPage);
                    
                    // Reload customer details if modal is open
                    if (currentCustomerId === customerId) {
                        viewCustomerDetails(customerId);
                    }
                } else {
                    showMessage('Lỗi: ' + data.message, 'error');
                }
            } catch (error) {
                console.error('Error updating status:', error);
                showMessage('Có lỗi xảy ra khi cập nhật trạng thái', 'error');
            }
        }
        
        function getOrderStatusColor(status) {
            const statusColors = {
                'pending': 'var(--color-orange-500)',
                'confirmed': 'var(--color-blue-500)',
                'shipped': 'var(--color-purple-500)',
                'completed': 'var(--color-green-500)',
                'cancelled': 'var(--color-red-500)'
            };
            return statusColors[status] || 'var(--color-neutral-500)';
        }
        
        function getNotePriorityColor(priority) {
            const priorityColors = {
                'low': 'var(--color-green-500)',
                'normal': 'var(--color-blue-500)',
                'high': 'var(--color-orange-500)',
                'urgent': 'var(--color-red-500)'
            };
            return priorityColors[priority] || 'var(--color-neutral-500)';
        }
        
        // Note management
        function showAddNoteModal(customerId) {
            currentCustomerId = customerId;
            document.getElementById('noteCustomerId').value = customerId;
            document.getElementById('addNoteForm').reset();
            document.getElementById('noteCustomerId').value = customerId; // Reset after form reset
            document.getElementById('addNoteModal').style.display = 'flex';
        }
        
        function closeAddNoteModal() {
            document.getElementById('addNoteModal').style.display = 'none';
            document.getElementById('addNoteForm').reset();
            currentCustomerId = null;
        }
        
        function closeCustomerDetailModal() {
            document.getElementById('customerDetailModal').style.display = 'none';
            currentCustomerId = null;
        }
        
        // Export customers
        async function exportCustomers(format = 'csv') {
            try {
                const response = await fetch(`customer-handler.php?action=export&format=${format}`);
                const data = await response.json();

                if (data.success) {
                    // Create and download file
                    const blob = new Blob([data.data.content], { type: data.data.mime_type });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = data.data.filename;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                    
                    showMessage('Xuất dữ liệu thành công!', 'success');
                } else {
                    showMessage('Lỗi xuất dữ liệu: ' + data.message, 'error');
                }
            } catch (error) {
                console.error('Error exporting customers:', error);
                showMessage('Có lỗi xảy ra khi xuất dữ liệu', 'error');
            }
        }
        
        // Helper functions
        function getCustomerStatusBadge(status, statusInfo) {
            if (!statusInfo) {
                statusInfo = {
                    color: status === 'VIP' ? 'gold' : status === 'active' ? 'green' : 'gray',
                    text: status,
                    icon: status === 'VIP' ? '👑' : status === 'active' ? '✅' : '⏸️'
                };
            }
            
            const colorMap = {
                'gold': 'var(--color-orange-500)',
                'green': 'var(--color-green-500)',
                'gray': 'var(--color-neutral-500)',
                'blue': 'var(--color-blue-500)'
            };
            
            return `
                <span style="background: ${colorMap[statusInfo.color] || 'var(--color-neutral-500)'}; 
                           color: white; padding: 0.375rem 0.75rem; border-radius: 1rem; 
                           font-size: 0.875rem; font-weight: 600; display: inline-flex; 
                           align-items: center; gap: 0.25rem;">
                    ${statusInfo.icon} ${statusInfo.text}
                </span>
            `;
        }
        
        function getCustomerSegmentBadge(segment, segmentInfo) {
            if (!segmentInfo) {
                segmentInfo = {
                    color: segment === 'new' ? 'purple' : segment === 'regular' ? 'blue' : 'orange',
                    text: segment,
                    icon: segment === 'new' ? '🆕' : segment === 'regular' ? '⭐' : '📅'
                };
            }
            
            const colorMap = {
                'purple': 'var(--color-purple-500)',
                'blue': 'var(--color-blue-500)',
                'orange': 'var(--color-orange-500)',
                'gray': 'var(--color-neutral-500)'
            };
            
            return `
                <span style="background: ${colorMap[segmentInfo.color] || 'var(--color-neutral-500)'}; 
                           color: white; padding: 0.25rem 0.5rem; border-radius: 0.75rem; 
                           font-size: 0.75rem; font-weight: 600; display: inline-flex; 
                           align-items: center; gap: 0.25rem;">
                    ${segmentInfo.icon} ${segmentInfo.text}
                </span>
            `;
        }

        // Auto-load promotions when promotions section is shown
        const originalShowSectionForPromotions = showSection;
        showSection = function(section) {
            originalShowSectionForPromotions(section);
            
            if (section === 'promotions') {
                // Wait for DOM to be ready
                setTimeout(() => {
                    loadPromotions();
                }, 100);
            } else if (section === 'customers') {
                // Load customers when customers section is shown
                setTimeout(() => {
                    loadCustomers();
                    loadCustomerOverview();
                }, 100);
            }
        };
        
        // Initialize customer form handlers
        document.addEventListener('DOMContentLoaded', function() {
            // Add note form handler
            const addNoteForm = document.getElementById('addNoteForm');
            if (addNoteForm) {
                addNoteForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    try {
                        const formData = new FormData(this);
                        formData.append('csrf_token', csrfToken);
                        formData.append('action', 'add_note');
                        
                        const response = await fetch('customer-handler.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            showMessage('Ghi chú đã được thêm thành công!', 'success');
                            closeAddNoteModal();
                            
                            // Reload customer details if modal is open
                            if (currentCustomerId) {
                                viewCustomerDetails(currentCustomerId);
                            }
                        } else {
                            showMessage('Lỗi: ' + data.message, 'error');
                        }
                    } catch (error) {
                        console.error('Error adding note:', error);
                        showMessage('Có lỗi xảy ra khi thêm ghi chú', 'error');
                    }
                });
            }
        });

        // ===== CHAT SYSTEM JAVASCRIPT =====
        
        // Global chat variables
        let currentConversationId = null;
        let chatPollingInterval = null;
        let lastMessageId = 0;
        let conversationsData = [];
        let currentCustomerData = null;
        let quickRepliesCache = null;
        let chatStatsInterval = null;

        // Auto-load chat when chatbox section is shown
        const originalShowSectionForChat = showSection;
        showSection = function(section) {
            originalShowSectionForChat(section);
            
            if (section === 'chatbox') {
                // Wait for DOM to be ready
                setTimeout(() => {
                    initializeChatSystem();
                }, 100);
            } else if (section !== 'chatbox' && chatPollingInterval) {
                // Stop polling when leaving chat section
                clearInterval(chatPollingInterval);
                clearInterval(chatStatsInterval);
            }
        };

        // Initialize chat system
        async function initializeChatSystem() {
            console.log('Initializing chat system...');
            
            // Load initial data
            await Promise.all([
                loadChatStats(),
                loadConversations(),
                loadQuickReplies()
            ]);
            
            // Setup event listeners
            setupChatEventListeners();
            
            // Start polling for updates
            startChatPolling();
            
            console.log('Chat system initialized');
        }

        // Setup event listeners
        function setupChatEventListeners() {
            // Filter tabs
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    
                    const filter = this.dataset.filter;
                    loadConversations(1, filter);
                });
            });

            // Message input auto-resize
            const messageInput = document.getElementById('messageInput');
            if (messageInput) {
                messageInput.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
                });

                // Send message on Enter (but not Shift+Enter)
                messageInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        sendMessage();
                    }
                });
            }

            // File input handler
            const fileInput = document.getElementById('fileInput');
            if (fileInput) {
                fileInput.addEventListener('change', handleFileUpload);
            }
        }

        // Start polling for real-time updates
        function startChatPolling() {
            // Poll for conversation updates every 3 seconds
            chatPollingInterval = setInterval(async () => {
                if (currentConversationId) {
                    await checkForNewMessages();
                }
                await loadConversations(1, getCurrentFilter(), true); // Silent update
            }, 3000);

            // Update stats every 30 seconds
            chatStatsInterval = setInterval(loadChatStats, 30000);
        }

        // Load chat statistics
        async function loadChatStats() {
            try {
                const response = await fetch('chat-handler.php?action=get_chat_stats');
                const data = await response.json();

                if (data.success) {
                    const stats = data.data;
                    document.getElementById('activeChats').textContent = stats.active_conversations || 0;
                    document.getElementById('unassignedChats').textContent = stats.unassigned_conversations || 0;
                    document.getElementById('todayMessages').textContent = stats.messages_today || 0;
                    document.getElementById('avgResponse').textContent = stats.avg_response_time || 0;
                }
            } catch (error) {
                console.error('Error loading chat stats:', error);
            }
        }

        // Load conversations
        async function loadConversations(page = 1, filter = 'all', silent = false) {
            try {
                if (!silent) {
                    showConversationsLoading();
                }

                const params = new URLSearchParams({
                    action: 'get_conversations',
                    page: page,
                    limit: 20
                });

                if (filter === 'assigned_to_me') {
                    params.append('assigned_to_me', 'true');
                } else if (filter !== 'all') {
                    params.append('status', filter);
                }

                const response = await fetch('chat-handler.php?' + params);
                const data = await response.json();

                if (data.success) {
                    conversationsData = data.data.conversations;
                    displayConversations(data.data.conversations);
                    displayConversationsPagination(data.data.pagination);
                } else {
                    console.error('Error loading conversations:', data.message);
                    if (!silent) {
                        showConversationsError(data.message);
                    }
                }
            } catch (error) {
                console.error('Error loading conversations:', error);
                if (!silent) {
                    showConversationsError('Unable to load conversations');
                }
            }
        }

        // Display conversations in sidebar
        function displayConversations(conversations) {
            const container = document.getElementById('conversationsList');
            if (!container) return;

            if (conversations.length === 0) {
                container.innerHTML = '<div class="loading-state">No conversations found</div>';
                return;
            }

            container.innerHTML = conversations.map(conv => `
                <div class="conversation-item ${currentConversationId === conv.id ? 'active' : ''}" 
                     onclick="selectConversation(${conv.id})">
                    <div class="conversation-avatar">
                        ${conv.customer_name.charAt(0).toUpperCase()}
                    </div>
                    <div class="conversation-info">
                        <div class="conversation-customer">${conv.customer_name}</div>
                        <div class="conversation-last-message">${conv.last_message || 'No messages yet'}</div>
                    </div>
                    <div class="conversation-meta">
                        <div class="conversation-time">${conv.last_message_formatted}</div>
                        <div class="conversation-status status-${conv.status}">${conv.status}</div>
                        ${conv.unread_count > 0 ? `<div class="unread-badge">${conv.unread_count}</div>` : ''}
                    </div>
                </div>
            `).join('');
        }

        // Display conversations pagination
        function displayConversationsPagination(pagination) {
            const container = document.getElementById('conversationsPagination');
            if (!container || pagination.total_pages <= 1) {
                container.innerHTML = '';
                return;
            }

            const currentPage = pagination.current_page;
            const totalPages = pagination.total_pages;
            let paginationHTML = '';

            if (currentPage > 1) {
                paginationHTML += `<button onclick="loadConversations(${currentPage - 1}, getCurrentFilter())">‹</button>`;
            }

            // Show page numbers (simplified)
            paginationHTML += `<span>Page ${currentPage} of ${totalPages}</span>`;

            if (currentPage < totalPages) {
                paginationHTML += `<button onclick="loadConversations(${currentPage + 1}, getCurrentFilter())">›</button>`;
            }

            container.innerHTML = `<div style="display: flex; justify-content: center; align-items: center; gap: 0.5rem; padding: 1rem;">${paginationHTML}</div>`;
        }

        // Get current filter
        function getCurrentFilter() {
            const activeTab = document.querySelector('.filter-tab.active');
            return activeTab ? activeTab.dataset.filter : 'all';
        }

        // Select a conversation
        async function selectConversation(conversationId) {
            try {
                currentConversationId = conversationId;
                
                // Update UI
                document.querySelectorAll('.conversation-item').forEach(item => {
                    item.classList.remove('active');
                });
                document.querySelector(`[onclick="selectConversation(${conversationId})"]`)?.classList.add('active');

                // Show loading state
                showChatLoading();

                // Load conversation details
                const [conversationResponse, customerResponse] = await Promise.all([
                    fetch(`chat-handler.php?action=get_conversation&conversation_id=${conversationId}`),
                    loadConversationCustomerInfo(conversationId)
                ]);

                const conversationData = await conversationResponse.json();

                if (conversationData.success) {
                    displayConversation(conversationData.data);
                    // Mark messages as read
                    markMessagesAsRead(conversationId);
                    // Update last message ID for polling
                    const messages = conversationData.data.messages;
                    if (messages.length > 0) {
                        lastMessageId = Math.max(...messages.map(m => m.id));
                    }
                } else {
                    showChatError(conversationData.message);
                }
            } catch (error) {
                console.error('Error selecting conversation:', error);
                showChatError('Unable to load conversation');
            }
        }

        // Display conversation
        function displayConversation(data) {
            const conversation = data.conversation;
            const messages = data.messages;

            // Show active chat interface
            document.getElementById('chatWelcome').style.display = 'none';
            document.getElementById('activeChat').style.display = 'flex';

            // Update conversation header
            displayConversationHeader(conversation);
            
            // Display messages
            displayMessages(messages);
            
            // Scroll to bottom
            scrollToBottom();
        }

        // Display conversation header
        function displayConversationHeader(conversation) {
            const container = document.getElementById('chatConversationHeader');
            if (!container) return;

            const priorityColors = {
                'low': '#10B981',
                'normal': '#3B82F6', 
                'high': '#F59E0B',
                'urgent': '#EF4444'
            };

            const statusColors = {
                'open': '#10B981',
                'in_progress': '#F59E0B',
                'resolved': '#6B7280',
                'closed': '#6B7280'
            };

            container.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="margin: 0 0 0.5rem 0; color: var(--color-primary-700);">
                            ${conversation.subject}
                        </h3>
                        <div style="display: flex; gap: 1rem; align-items: center; color: var(--color-neutral-600); font-size: 0.9rem;">
                            <span>👤 ${conversation.customer_name}</span>
                            <span>📧 ${conversation.customer_email}</span>
                            <span style="color: ${priorityColors[conversation.priority]}">
                                🔥 ${conversation.priority.charAt(0).toUpperCase() + conversation.priority.slice(1)} Priority
                            </span>
                            <span style="color: ${statusColors[conversation.status]}">
                                ● ${conversation.status.replace('_', ' ').charAt(0).toUpperCase() + conversation.status.replace('_', ' ').slice(1)}
                            </span>
                        </div>
                    </div>
                    <div style="display: flex; gap: 0.5rem;">
                        <button class="action-btn" onclick="openConversationSettings(${conversation.id})" title="Settings">
                            <span>⚙️</span>
                        </button>
                        <button class="action-btn" onclick="closeConversation()" title="Close Chat">
                            <span>❌</span>
                        </button>
                    </div>
                </div>
            `;
        }

        // Display messages
        function displayMessages(messages) {
            const container = document.getElementById('messagesContainer');
            if (!container) return;

            container.innerHTML = messages.map(message => {
                const isStaff = message.is_staff;
                const isInternal = message.is_internal;
                const messageClass = isInternal ? 'internal-message' : (isStaff ? 'staff-message' : 'customer-message');
                
                let attachmentsHTML = '';
                if (message.attachments && message.attachments.length > 0) {
                    attachmentsHTML = message.attachments.map(attachment => `
                        <div class="attachment" style="margin-top: 0.5rem; padding: 0.5rem; background: rgba(255,255,255,0.2); border-radius: 0.5rem;">
                            <span>📎</span> ${attachment.original_filename}
                            <small style="opacity: 0.7;">(${formatFileSize(attachment.file_size)})</small>
                        </div>
                    `).join('');
                }

                return `
                    <div class="message ${messageClass}">
                        <div class="message-bubble">
                            ${isInternal ? '<div style="font-weight: 600; color: var(--color-warning-700); margin-bottom: 0.5rem;">🔒 Internal Note</div>' : ''}
                            <div class="message-content">${message.content}</div>
                            ${attachmentsHTML}
                            <div class="message-meta">
                                <span class="message-time">${message.created_at_formatted}</span>
                                <div class="message-status">
                                    <span>${message.sender_name}</span>
                                    ${message.read_at ? '✓✓' : '✓'}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        // Load customer information for conversation
        async function loadConversationCustomerInfo(conversationId) {
            try {
                // Get conversation to find customer ID
                const conv = conversationsData.find(c => c.id === conversationId);
                if (!conv) return;

                const response = await fetch(`chat-handler.php?action=get_customer_info&customer_id=${conv.customer_id}`);
                const data = await response.json();

                if (data.success) {
                    currentCustomerData = data.data;
                    displayCustomerInfo(data.data);
                } else {
                    console.error('Error loading customer info:', data.message);
                }
            } catch (error) {
                console.error('Error loading customer info:', error);
            }
        }

        // Display customer information
        function displayCustomerInfo(customerData) {
            const container = document.getElementById('customerInfo');
            if (!container) return;

            const customer = customerData.customer;
            const orderStats = customerData.order_stats;
            const recentOrders = customerData.recent_orders;
            const notes = customerData.notes;
            const conversationHistory = customerData.conversation_history;

            container.innerHTML = `
                <div style="padding: 1.5rem;">
                    <!-- Customer Profile -->
                    <div style="text-align: center; margin-bottom: 1.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.2);">
                        <div style="width: 60px; height: 60px; border-radius: 50%; background: linear-gradient(135deg, var(--color-secondary-500), var(--color-accent-500)); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem; font-weight: 700; margin: 0 auto 1rem;">
                            ${customer.name.charAt(0).toUpperCase()}
                        </div>
                        <h4 style="margin: 0 0 0.25rem 0; color: var(--color-primary-700);">${customer.name}</h4>
                        <p style="margin: 0 0 0.5rem 0; color: var(--color-neutral-600); font-size: 0.9rem;">${customer.email}</p>
                        <small style="color: var(--color-neutral-500);">Member since ${customer.member_since}</small>
                    </div>

                    <!-- Order Statistics -->
                    <div style="margin-bottom: 1.5rem;">
                        <h5 style="margin: 0 0 1rem 0; color: var(--color-primary-700); display: flex; align-items: center; gap: 0.5rem;">
                            <span>📊</span> Order Statistics
                        </h5>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; font-size: 0.85rem;">
                            <div style="text-align: center; padding: 0.75rem; background: rgba(255,255,255,0.1); border-radius: 0.75rem;">
                                <div style="font-weight: 700; color: var(--color-secondary-600);">${orderStats.total_orders}</div>
                                <div style="color: var(--color-neutral-600);">Orders</div>
                            </div>
                            <div style="text-align: center; padding: 0.75rem; background: rgba(255,255,255,0.1); border-radius: 0.75rem;">
                                <div style="font-weight: 700; color: var(--color-secondary-600);">${formatCurrency(orderStats.total_spent)}</div>
                                <div style="color: var(--color-neutral-600);">Spent</div>
                            </div>
                        </div>
                        <div style="text-align: center; margin-top: 0.75rem; font-size: 0.8rem; color: var(--color-neutral-600);">
                            Last order: ${orderStats.last_order_date}
                        </div>
                    </div>

                    <!-- Recent Orders -->
                    ${recentOrders.length > 0 ? `
                        <div style="margin-bottom: 1.5rem;">
                            <h5 style="margin: 0 0 1rem 0; color: var(--color-primary-700); display: flex; align-items: center; gap: 0.5rem;">
                                <span>🛍️</span> Recent Orders
                            </h5>
                            <div style="max-height: 150px; overflow-y: auto;">
                                ${recentOrders.slice(0, 3).map(order => `
                                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem; margin-bottom: 0.5rem; background: rgba(255,255,255,0.1); border-radius: 0.5rem; font-size: 0.8rem;">
                                        <div>
                                            <div style="font-weight: 600;">Order #${order.id}</div>
                                            <div style="color: var(--color-neutral-600);">${order.created_at_formatted}</div>
                                        </div>
                                        <div style="text-align: right;">
                                            <div style="font-weight: 600; color: var(--color-secondary-600);">${formatCurrency(order.total_amount)}</div>
                                            <div class="status-badge status-${order.status}">${order.status}</div>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    ` : ''}

                    <!-- Staff Notes -->
                    ${notes.length > 0 ? `
                        <div style="margin-bottom: 1.5rem;">
                            <h5 style="margin: 0 0 1rem 0; color: var(--color-primary-700); display: flex; align-items: center; gap: 0.5rem;">
                                <span>📝</span> Staff Notes
                            </h5>
                            <div style="max-height: 120px; overflow-y: auto; font-size: 0.8rem;">
                                ${notes.slice(0, 2).map(note => `
                                    <div style="padding: 0.5rem; margin-bottom: 0.5rem; background: rgba(255,255,255,0.1); border-radius: 0.5rem; border-left: 3px solid var(--color-primary-500);">
                                        <div style="margin-bottom: 0.25rem;">${note.content}</div>
                                        <div style="color: var(--color-neutral-500); font-size: 0.7rem;">
                                            ${note.staff_name} • ${note.created_at_formatted}
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    ` : ''}

                    <!-- Quick Actions -->
                    <div>
                        <h5 style="margin: 0 0 1rem 0; color: var(--color-primary-700); display: flex; align-items: center; gap: 0.5rem;">
                            <span>⚡</span> Quick Actions
                        </h5>
                        <div style="display: grid; gap: 0.5rem;">
                            <button onclick="viewCustomerProfile(${customer.id})" style="padding: 0.75rem; background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); border-radius: 0.75rem; color: var(--color-neutral-700); cursor: pointer; font-size: 0.85rem;">
                                👤 View Full Profile
                            </button>
                            <button onclick="createOrderForCustomer(${customer.id})" style="padding: 0.75rem; background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); border-radius: 0.75rem; color: var(--color-neutral-700); cursor: pointer; font-size: 0.85rem;">
                                🛍️ Create Order
                            </button>
                            <button onclick="addCustomerNote(${customer.id})" style="padding: 0.75rem; background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); border-radius: 0.75rem; color: var(--color-neutral-700); cursor: pointer; font-size: 0.85rem;">
                                📝 Add Note
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }

        // Load quick replies
        async function loadQuickReplies() {
            try {
                const response = await fetch('chat-handler.php?action=get_quick_replies');
                const data = await response.json();

                if (data.success) {
                    quickRepliesCache = data.data;
                }
            } catch (error) {
                console.error('Error loading quick replies:', error);
            }
        }

        // Toggle quick replies
        function toggleQuickReplies() {
            const container = document.getElementById('quickReplies');
            if (!container) return;

            if (container.style.display === 'none' || !container.style.display) {
                displayQuickReplies();
                container.style.display = 'flex';
            } else {
                container.style.display = 'none';
            }
        }

        // Display quick replies
        function displayQuickReplies() {
            const container = document.getElementById('quickReplies');
            if (!container || !quickRepliesCache) return;

            let repliesHTML = '';
            Object.keys(quickRepliesCache).forEach(category => {
                const categoryReplies = quickRepliesCache[category];
                categoryReplies.forEach(reply => {
                    repliesHTML += `
                        <button class="quick-reply-btn" onclick="useQuickReply('${reply.content}', ${reply.id})">
                            ${reply.title}
                        </button>
                    `;
                });
            });

            container.innerHTML = repliesHTML;
        }

        // Use quick reply
        function useQuickReply(content, templateId) {
            const messageInput = document.getElementById('messageInput');
            if (messageInput) {
                messageInput.value = content;
                messageInput.focus();
                // Store template ID for usage tracking
                messageInput.dataset.templateId = templateId;
                
                // Hide quick replies
                document.getElementById('quickReplies').style.display = 'none';
            }
        }

        // Send message
        async function sendMessage() {
            const messageInput = document.getElementById('messageInput');
            const sendBtn = document.getElementById('sendBtn');
            
            if (!messageInput || !currentConversationId) return;

            const content = messageInput.value.trim();
            if (!content) return;

            try {
                // Disable send button
                sendBtn.disabled = true;
                sendBtn.innerHTML = '<span>⏳</span>';

                const formData = new FormData();
                formData.append('action', 'send_message');
                formData.append('conversation_id', currentConversationId);
                formData.append('content', content);
                formData.append('csrf_token', csrfToken);

                // Check if this is from a quick reply template
                if (messageInput.dataset.templateId) {
                    formData.append('message_type', 'quick_reply');
                    formData.append('template_id', messageInput.dataset.templateId);
                    delete messageInput.dataset.templateId;
                }

                const response = await fetch('chat-handler.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    // Clear input
                    messageInput.value = '';
                    messageInput.style.height = 'auto';
                    
                    // Refresh conversation to show new message
                    await selectConversation(currentConversationId);
                    
                    // Refresh conversations list
                    await loadConversations(1, getCurrentFilter(), true);
                } else {
                    alert('Error sending message: ' + data.message);
                }
            } catch (error) {
                console.error('Error sending message:', error);
                alert('Unable to send message');
            } finally {
                // Re-enable send button
                sendBtn.disabled = false;
                sendBtn.innerHTML = '<span>📤</span>';
            }
        }

        // Add internal note
        async function addInternalNote() {
            if (!currentConversationId) return;

            const note = prompt('Enter internal note:');
            if (!note) return;

            try {
                const formData = new FormData();
                formData.append('action', 'add_internal_note');
                formData.append('conversation_id', currentConversationId);
                formData.append('content', note);
                formData.append('csrf_token', csrfToken);

                const response = await fetch('chat-handler.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    // Refresh conversation
                    await selectConversation(currentConversationId);
                } else {
                    alert('Error adding note: ' + data.message);
                }
            } catch (error) {
                console.error('Error adding note:', error);
                alert('Unable to add note');
            }
        }

        // Handle file upload
        async function handleFileUpload(event) {
            const file = event.target.files[0];
            if (!file || !currentConversationId) return;

            try {
                const formData = new FormData();
                formData.append('action', 'upload_attachment');
                formData.append('conversation_id', currentConversationId);
                formData.append('file', file);
                formData.append('csrf_token', csrfToken);

                const response = await fetch('chat-handler.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    // Refresh conversation
                    await selectConversation(currentConversationId);
                    // Clear file input
                    event.target.value = '';
                } else {
                    alert('Error uploading file: ' + data.message);
                }
            } catch (error) {
                console.error('Error uploading file:', error);
                alert('Unable to upload file');
            }
        }

        // Attach file
        function attachFile() {
            document.getElementById('fileInput').click();
        }

        // Toggle emoji (placeholder function)
        function toggleEmoji() {
            const messageInput = document.getElementById('messageInput');
            if (messageInput) {
                messageInput.focus();
                // Simple emoji insertion - in a real app you'd use an emoji picker
                const emojis = ['😊', '👍', '❤️', '😂', '😢', '😮', '😡', '🙏'];
                const randomEmoji = emojis[Math.floor(Math.random() * emojis.length)];
                messageInput.value += randomEmoji;
            }
        }

        // Mark messages as read
        async function markMessagesAsRead(conversationId) {
            try {
                const formData = new FormData();
                formData.append('action', 'mark_as_read');
                formData.append('conversation_id', conversationId);
                formData.append('csrf_token', csrfToken);

                await fetch('chat-handler.php', {
                    method: 'POST',
                    body: formData
                });
            } catch (error) {
                console.error('Error marking messages as read:', error);
            }
        }

        // Check for new messages
        async function checkForNewMessages() {
            if (!currentConversationId) return;

            try {
                const response = await fetch(`chat-handler.php?action=get_conversation&conversation_id=${currentConversationId}`);
                const data = await response.json();

                if (data.success) {
                    const messages = data.data.messages;
                    if (messages.length > 0) {
                        const latestMessageId = Math.max(...messages.map(m => m.id));
                        if (latestMessageId > lastMessageId) {
                            // New messages found, refresh display
                            displayMessages(messages);
                            scrollToBottom();
                            lastMessageId = latestMessageId;
                            
                            // Play notification sound (optional)
                            playNotificationSound();
                        }
                    }
                }
            } catch (error) {
                console.error('Error checking for new messages:', error);
            }
        }

        // Play notification sound (simple audio notification)
        function playNotificationSound() {
            try {
                // Create a simple beep sound using Web Audio API
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();
                
                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);
                
                oscillator.frequency.value = 800;
                gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);
                
                oscillator.start(audioContext.currentTime);
                oscillator.stop(audioContext.currentTime + 0.3);
            } catch (error) {
                console.log('Could not play notification sound:', error);
            }
        }

        // Search conversations
        function toggleSearchBar() {
            const searchBar = document.getElementById('searchBar');
            if (searchBar.style.display === 'none' || !searchBar.style.display) {
                searchBar.style.display = 'block';
                document.getElementById('searchInput').focus();
            } else {
                searchBar.style.display = 'none';
                clearSearch();
            }
        }

        // Search conversations
        async function searchConversations() {
            const query = document.getElementById('searchInput').value.trim();
            if (!query) return;

            try {
                const response = await fetch(`chat-handler.php?action=search_conversations&query=${encodeURIComponent(query)}&limit=10`);
                const data = await response.json();

                if (data.success) {
                    displaySearchResults(data.data);
                } else {
                    document.getElementById('searchResults').innerHTML = '<div style="padding: 1rem; color: var(--color-error-600);">Error: ' + data.message + '</div>';
                }
            } catch (error) {
                console.error('Error searching conversations:', error);
                document.getElementById('searchResults').innerHTML = '<div style="padding: 1rem; color: var(--color-error-600);">Search failed</div>';
            }
        }

        // Display search results
        function displaySearchResults(results) {
            const container = document.getElementById('searchResults');
            if (!results || results.length === 0) {
                container.innerHTML = '<div style="padding: 1rem; color: var(--color-neutral-600);">No results found</div>';
                return;
            }

            container.innerHTML = results.map(result => `
                <div style="padding: 1rem; margin-bottom: 0.5rem; background: rgba(255,255,255,0.2); border-radius: 0.75rem; cursor: pointer;" onclick="selectConversation(${result.id}); clearSearch();">
                    <div style="font-weight: 600; color: var(--color-primary-700);">${result.customer_name}</div>
                    <div style="font-size: 0.9rem; color: var(--color-neutral-600); margin: 0.25rem 0;">${result.subject}</div>
                    <div style="font-size: 0.8rem; color: var(--color-neutral-500);">${result.last_message_formatted}</div>
                    ${result.highlighted_content ? `<div style="font-size: 0.8rem; margin-top: 0.25rem; color: var(--color-secondary-600);">${result.highlighted_content}</div>` : ''}
                </div>
            `).join('');
        }

        // Clear search
        function clearSearch() {
            document.getElementById('searchInput').value = '';
            document.getElementById('searchResults').innerHTML = '';
        }

        // Refresh conversations
        function refreshConversations() {
            loadConversations(1, getCurrentFilter());
        }

        // Create new conversation
        function createNewConversation() {
            // This would typically open a modal to select customer and start new conversation
            alert('Create New Conversation feature - would open a modal to select customer and start chat');
        }

        // Close conversation
        function closeConversation() {
            currentConversationId = null;
            currentCustomerData = null;
            lastMessageId = 0;
            
            // Show welcome screen
            document.getElementById('activeChat').style.display = 'none';
            document.getElementById('chatWelcome').style.display = 'flex';
            
            // Clear customer info
            document.getElementById('customerInfo').innerHTML = `
                <div class="info-placeholder">
                    <div class="placeholder-icon">👤</div>
                    <h4>Customer Info</h4>
                    <p>Select a conversation to view customer details</p>
                </div>
            `;
            
            // Remove active state from conversation items
            document.querySelectorAll('.conversation-item').forEach(item => {
                item.classList.remove('active');
            });
        }

        // Open conversation settings
        function openConversationSettings(conversationId) {
            // This would open a modal for conversation management
            alert('Conversation Settings - would open modal for status, assignment, tags, etc.');
        }

        // Utility functions
        function scrollToBottom() {
            const container = document.getElementById('messagesContainer');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        }

        function formatCurrency(amount) {
            return new Intl.NumberFormat('vi-VN', {
                style: 'currency',
                currency: 'VND'
            }).format(amount);
        }

        function formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        }

        // Loading states
        function showConversationsLoading() {
            const container = document.getElementById('conversationsList');
            if (container) {
                container.innerHTML = '<div class="loading-state">Loading conversations...</div>';
            }
        }

        function showConversationsError(message) {
            const container = document.getElementById('conversationsList');
            if (container) {
                container.innerHTML = `<div class="loading-state" style="color: var(--color-error-600);">Error: ${message}</div>`;
            }
        }

        function showChatLoading() {
            const container = document.getElementById('messagesContainer');
            if (container) {
                container.innerHTML = '<div style="display: flex; align-items: center; justify-content: center; height: 100%; color: var(--color-neutral-600);">Loading conversation...</div>';
            }
        }

        function showChatError(message) {
            const container = document.getElementById('messagesContainer');
            if (container) {
                container.innerHTML = `<div style="display: flex; align-items: center; justify-content: center; height: 100%; color: var(--color-error-600);">Error: ${message}</div>`;
            }
        }

        // Placeholder functions for customer actions
        function viewCustomerProfile(customerId) {
            alert('Would navigate to customer profile page');
        }

        function createOrderForCustomer(customerId) {
            alert('Would open create order modal for customer');
        }

        function addCustomerNote(customerId) {
            const note = prompt('Enter note for customer:');
            if (note) {
                alert('Would add note to customer profile');
            }
        }
        
        });

        // ============ REPORTS SYSTEM FUNCTIONALITY ============

        // Chart instances
        let reportsCharts = {};
        let reportsData = {};
        let currentReportPeriod = 'monthly';
        let autoRefreshInterval = null;

        // Auto-load reports when reports section is shown
        const originalShowSectionForReports = showSection;
        showSection = function(section) {
            originalShowSectionForReports(section);
            
            if (section === 'reports') {
                // Wait for DOM to be ready
                setTimeout(() => {
                    initializeReportsSystem();
                }, 100);
            } else if (section !== 'reports' && autoRefreshInterval) {
                // Stop auto-refresh when leaving reports
                clearInterval(autoRefreshInterval);
                autoRefreshInterval = null;
            }
        };

        function initializeReportsSystem() {
            // Set default date range
            setDefaultDateRange();
            
            // Initialize real-time widgets
            loadRealTimeWidgets();
            
            // Load initial reports data
            loadSalesReports();
            
            // Start auto-refresh for real-time data
            autoRefreshInterval = setInterval(() => {
                loadRealTimeWidgets();
            }, 30000); // Refresh every 30 seconds
            
            console.log('Reports system initialized');
        }

        function setDefaultDateRange() {
            const today = new Date();
            const thirtyDaysAgo = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));
            
            document.getElementById('reportDateFrom').value = thirtyDaysAgo.toISOString().split('T')[0];
            document.getElementById('reportDateTo').value = today.toISOString().split('T')[0];
        }

        function switchReportTab(tabName) {
            // Update active tab
            document.querySelectorAll('.report-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelector(\`[onclick="switchReportTab('\${tabName}')"]\`).classList.add('active');
            
            // Update active content
            document.querySelectorAll('.report-tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.getElementById(\`\${tabName}Report\`).classList.add('active');
            
            // Load tab-specific data
            switch(tabName) {
                case 'sales':
                    loadSalesReports();
                    break;
                case 'performance':
                    loadPerformanceReports();
                    break;
                case 'customers':
                    loadCustomerReports();
                    break;
                case 'inventory':
                    loadInventoryReports();
                    break;
                case 'marketing':
                    loadMarketingReports();
                    break;
            }
        }

        function setSalesPeriod(period) {
            currentReportPeriod = period;
            
            // Update active period button
            document.querySelectorAll('.period-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            document.querySelector(\`[onclick="setSalesPeriod('\${period}')"]\`).classList.add('active');
            
            // Reload sales data with new period
            loadSalesReports();
        }

        function refreshReportsData() {
            const activeTab = document.querySelector('.report-tab.active').textContent.trim();
            
            showLoadingSpinner();
            
            // Reload current tab data
            if (activeTab.includes('Doanh thu')) {
                loadSalesReports();
            } else if (activeTab.includes('Hiệu suất')) {
                loadPerformanceReports();
            } else if (activeTab.includes('Khách hàng')) {
                loadCustomerReports();
            } else if (activeTab.includes('Kho hàng')) {
                loadInventoryReports();
            } else if (activeTab.includes('Marketing')) {
                loadMarketingReports();
            }
            
            // Always refresh real-time widgets
            loadRealTimeWidgets();
        }

        // Real-time KPI Widgets
        function loadRealTimeWidgets() {
            fetch('reports-handler.php?action=real_time_widgets')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateKPIWidgets(data.data);
                    } else {
                        console.error('Failed to load real-time widgets:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error loading real-time widgets:', error);
                });
        }

        function updateKPIWidgets(data) {
            if (data.kpis) {
                document.getElementById('todayRevenue').textContent = formatCurrency(data.kpis.today.revenue_today);
                document.getElementById('todayOrders').textContent = data.kpis.today.orders_today;
                document.getElementById('revenueChange').textContent = \`\${data.kpis.changes.revenue >= 0 ? '+' : ''}\${data.kpis.changes.revenue}%\`;
                document.getElementById('ordersChange').textContent = \`\${data.kpis.changes.orders >= 0 ? '+' : ''}\${data.kpis.changes.orders}%\`;
                
                // Update change colors
                const revenueChange = document.getElementById('revenueChange');
                const ordersChange = document.getElementById('ordersChange');
                
                revenueChange.className = 'kpi-change ' + (data.kpis.changes.revenue >= 0 ? 'positive' : 'negative');
                ordersChange.className = 'kpi-change ' + (data.kpis.changes.orders >= 0 ? 'positive' : 'negative');
            }
            
            if (data.alerts) {
                document.getElementById('lowStockProducts').textContent = data.alerts.low_stock + data.alerts.out_of_stock;
            }
        }

        // Sales Reports
        function loadSalesReports() {
            const dateFrom = document.getElementById('reportDateFrom').value;
            const dateTo = document.getElementById('reportDateTo').value;
            
            fetch(\`reports-handler.php?action=sales_dashboard&period=\${currentReportPeriod}&date_from=\${dateFrom}&date_to=\${dateTo}\`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        reportsData.sales = data.data;
                        renderSalesCharts(data.data);
                    } else {
                        showError('Error loading sales reports: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error loading sales reports:', error);
                    showError('Failed to load sales reports');
                });
        }

        function renderSalesCharts(data) {
            // Revenue trends chart
            if (data.revenue && data.revenue.trends) {
                createRevenueChart(data.revenue.trends);
            }
            
            // Best products chart
            if (data.best_products) {
                createBestProductsChart(data.best_products);
                renderBestProductsList(data.best_products);
            }
            
            // Category performance chart
            if (data.categories) {
                createCategoryChart(data.categories);
                renderCategoryStats(data.categories);
            }
            
            // Customer segments chart
            if (data.segments) {
                createCustomerSegmentChart(data.segments);
                renderSegmentTable(data.segments);
            }
        }

        function createRevenueChart(trendsData) {
            const ctx = document.getElementById('revenueChart');
            if (!ctx) return;
            
            // Destroy existing chart
            if (reportsCharts.revenue) {
                reportsCharts.revenue.destroy();
            }
            
            const labels = trendsData.map(item => {
                const date = new Date(item.period);
                return date.toLocaleDateString('vi-VN');
            });
            
            const revenues = trendsData.map(item => parseFloat(item.revenue || 0));
            const orders = trendsData.map(item => parseInt(item.orders || 0));
            
            reportsCharts.revenue = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Doanh thu (VNĐ)',
                        data: revenues,
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true,
                        yAxisID: 'y'
                    }, {
                        label: 'Số đơn hàng',
                        data: orders,
                        borderColor: 'rgb(147, 51, 234)',
                        backgroundColor: 'rgba(147, 51, 234, 0.1)',
                        tension: 0.4,
                        fill: false,
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: 'Xu hướng doanh thu và đơn hàng'
                        }
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            ticks: {
                                callback: function(value) {
                                    return formatCurrency(value);
                                }
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            grid: {
                                drawOnChartArea: false,
                            },
                        }
                    }
                }
            });
        }

        function createBestProductsChart(productsData) {
            const ctx = document.getElementById('bestProductsChart');
            if (!ctx) return;
            
            if (reportsCharts.bestProducts) {
                reportsCharts.bestProducts.destroy();
            }
            
            const topProducts = productsData.slice(0, 5);
            const labels = topProducts.map(item => item.name);
            const sales = topProducts.map(item => parseInt(item.total_sold || 0));
            
            reportsCharts.bestProducts = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: sales,
                        backgroundColor: [
                            'rgba(59, 130, 246, 0.8)',
                            'rgba(147, 51, 234, 0.8)',
                            'rgba(239, 68, 68, 0.8)',
                            'rgba(34, 197, 94, 0.8)',
                            'rgba(249, 115, 22, 0.8)'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        }
                    }
                }
            });
        }

        function renderBestProductsList(productsData) {
            const container = document.getElementById('bestProductsList');
            if (!container) return;
            
            const topProducts = productsData.slice(0, 10);
            container.innerHTML = topProducts.map((product, index) => \`
                <div class="data-list-item">
                    <div>
                        <strong>#\${index + 1} \${product.name}</strong><br>
                        <small style="color: var(--color-neutral-600);">\${product.category_name || 'N/A'}</small>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-weight: 600; color: var(--color-primary-600);">\${product.total_sold} đã bán</div>
                        <div style="font-size: 0.9rem; color: var(--color-neutral-600);">\${formatCurrency(product.total_revenue)}</div>
                    </div>
                </div>
            \`).join('');
        }

        function createCategoryChart(categoriesData) {
            const ctx = document.getElementById('categoryChart');
            if (!ctx) return;
            
            if (reportsCharts.category) {
                reportsCharts.category.destroy();
            }
            
            const categories = categoriesData.filter(cat => cat.total_revenue > 0);
            const labels = categories.map(item => item.name);
            const revenues = categories.map(item => parseFloat(item.total_revenue || 0));
            
            reportsCharts.category = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: revenues,
                        backgroundColor: [
                            'rgba(255, 182, 193, 0.8)',
                            'rgba(221, 160, 221, 0.8)',
                            'rgba(173, 216, 230, 0.8)',
                            'rgba(255, 218, 185, 0.8)',
                            'rgba(144, 238, 144, 0.8)',
                            'rgba(255, 160, 122, 0.8)'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        function renderCategoryStats(categoriesData) {
            const container = document.getElementById('categoryStats');
            if (!container) return;
            
            container.innerHTML = categoriesData.map(category => \`
                <div style="background: rgba(255, 255, 255, 0.1); padding: 1rem; border-radius: 0.75rem; text-align: center;">
                    <div style="font-weight: 600; color: var(--color-primary-600);">\${category.name}</div>
                    <div style="font-size: 0.9rem; color: var(--color-neutral-600); margin: 0.25rem 0;">\${category.product_count} sản phẩm</div>
                    <div style="font-weight: 700; color: var(--color-secondary-600);">\${formatCurrency(category.total_revenue)}</div>
                </div>
            \`).join('');
        }

        function createCustomerSegmentChart(segmentsData) {
            const ctx = document.getElementById('customerSegmentChart');
            if (!ctx) return;
            
            if (reportsCharts.customerSegment) {
                reportsCharts.customerSegment.destroy();
            }
            
            const labels = segmentsData.map(item => item.segment);
            const revenues = segmentsData.map(item => parseFloat(item.segment_revenue || 0));
            
            reportsCharts.customerSegment = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Doanh thu theo phân khúc',
                        data: revenues,
                        backgroundColor: 'rgba(59, 130, 246, 0.8)',
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return formatCurrency(value);
                                }
                            }
                        }
                    }
                }
            });
        }

        function renderSegmentTable(segmentsData) {
            const container = document.getElementById('segmentTable');
            if (!container) return;
            
            container.innerHTML = \`
                <table class="data-table" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Phân khúc</th>
                            <th>Số khách hàng</th>
                            <th>Doanh thu</th>
                            <th>Chi tiêu TB</th>
                        </tr>
                    </thead>
                    <tbody>
                        \${segmentsData.map(segment => \`
                            <tr>
                                <td style="font-weight: 600;">\${segment.segment}</td>
                                <td>\${segment.customer_count}</td>
                                <td style="color: var(--color-primary-600); font-weight: 600;">\${formatCurrency(segment.segment_revenue)}</td>
                                <td>\${formatCurrency(segment.avg_spent)}</td>
                            </tr>
                        \`).join('')}
                    </tbody>
                </table>
            \`;
        }

        // Performance Reports
        function loadPerformanceReports() {
            const dateFrom = document.getElementById('reportDateFrom').value;
            const dateTo = document.getElementById('reportDateTo').value;
            
            fetch(\`reports-handler.php?action=performance_metrics&date_from=\${dateFrom}&date_to=\${dateTo}\`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        reportsData.performance = data.data;
                        renderPerformanceCharts(data.data);
                    } else {
                        showError('Error loading performance reports: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error loading performance reports:', error);
                    showError('Failed to load performance reports');
                });
        }

        function renderPerformanceCharts(data) {
            // Conversion metrics
            if (data.conversion) {
                updateConversionMetrics(data.conversion);
                createConversionChart(data.conversion);
            }
            
            // AOV trends
            if (data.aov) {
                createAOVChart(data.aov);
                renderAOVStats(data.aov);
            }
            
            // Product performance table
            if (data.product_performance) {
                renderProductPerformanceTable(data.product_performance);
            }
            
            // Seasonal trends
            if (data.seasonal) {
                createSeasonalChart(data.seasonal);
            }
        }

        function updateConversionMetrics(conversionData) {
            document.getElementById('completionRate').textContent = conversionData.conversion_rate + '%';
            document.getElementById('cancellationRate').textContent = conversionData.cancellation_rate + '%';
        }

        function createConversionChart(conversionData) {
            const ctx = document.getElementById('conversionChart');
            if (!ctx) return;
            
            if (reportsCharts.conversion) {
                reportsCharts.conversion.destroy();
            }
            
            reportsCharts.conversion = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Hoàn thành', 'Đang xử lý', 'Đã hủy'],
                    datasets: [{
                        data: [
                            conversionData.completed_orders,
                            conversionData.pending_orders,
                            conversionData.cancelled_orders
                        ],
                        backgroundColor: [
                            'rgba(34, 197, 94, 0.8)',
                            'rgba(249, 115, 22, 0.8)',
                            'rgba(239, 68, 68, 0.8)'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        function createAOVChart(aovData) {
            const ctx = document.getElementById('aovChart');
            if (!ctx) return;
            
            if (reportsCharts.aov) {
                reportsCharts.aov.destroy();
            }
            
            const labels = aovData.map(item => new Date(item.date).toLocaleDateString('vi-VN'));
            const values = aovData.map(item => parseFloat(item.aov || 0));
            
            reportsCharts.aov = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Giá trị đơn hàng TB',
                        data: values,
                        borderColor: 'rgb(147, 51, 234)',
                        backgroundColor: 'rgba(147, 51, 234, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return formatCurrency(value);
                                }
                            }
                        }
                    }
                }
            });
        }

        function renderAOVStats(aovData) {
            const container = document.getElementById('aovStats');
            if (!container || !aovData.length) return;
            
            const totalAOV = aovData.reduce((sum, item) => sum + parseFloat(item.aov || 0), 0);
            const avgAOV = totalAOV / aovData.length;
            const maxAOV = Math.max(...aovData.map(item => parseFloat(item.aov || 0)));
            const minAOV = Math.min(...aovData.map(item => parseFloat(item.aov || 0)));
            
            container.innerHTML = \`
                <div style="background: rgba(255, 255, 255, 0.1); padding: 1rem; border-radius: 1rem;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 1rem; text-align: center;">
                        <div>
                            <div style="font-size: 0.9rem; color: var(--color-neutral-600);">Trung bình</div>
                            <div style="font-weight: 700; color: var(--color-primary-600);">\${formatCurrency(avgAOV)}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.9rem; color: var(--color-neutral-600);">Cao nhất</div>
                            <div style="font-weight: 700; color: var(--color-success-600);">\${formatCurrency(maxAOV)}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.9rem; color: var(--color-neutral-600);">Thấp nhất</div>
                            <div style="font-weight: 700; color: var(--color-error-600);">\${formatCurrency(minAOV)}</div>
                        </div>
                    </div>
                </div>
            \`;
        }

        function renderProductPerformanceTable(performanceData) {
            const container = document.getElementById('productPerformanceTable');
            if (!container) return;
            
            const topProducts = performanceData.slice(0, 20);
            
            container.innerHTML = \`
                <table class="data-table" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th>Đã bán</th>
                            <th>Doanh thu</th>
                            <th>Tồn kho</th>
                            <th>Tỷ lệ luân chuyển</th>
                        </tr>
                    </thead>
                    <tbody>
                        \${topProducts.map(product => \`
                            <tr>
                                <td style="font-weight: 600;">\${product.name}</td>
                                <td>\${product.total_sold}</td>
                                <td style="color: var(--color-primary-600); font-weight: 600;">\${formatCurrency(product.revenue)}</td>
                                <td>\${product.stock}</td>
                                <td>
                                    <span style="background: \${product.turnover_rate > 50 ? 'rgba(34, 197, 94, 0.1)' : 'rgba(249, 115, 22, 0.1)'}; 
                                                color: \${product.turnover_rate > 50 ? 'var(--color-success-700)' : 'var(--color-warning-700)'}; 
                                                padding: 0.25rem 0.5rem; border-radius: 0.5rem; font-size: 0.85rem; font-weight: 600;">
                                        \${product.turnover_rate}%
                                    </span>
                                </td>
                            </tr>
                        \`).join('')}
                    </tbody>
                </table>
            \`;
        }

        function createSeasonalChart(seasonalData) {
            const ctx = document.getElementById('seasonalChart');
            if (!ctx) return;
            
            if (reportsCharts.seasonal) {
                reportsCharts.seasonal.destroy();
            }
            
            // Group data by month across years
            const monthlyData = {};
            seasonalData.forEach(item => {
                const month = parseInt(item.month);
                if (!monthlyData[month]) {
                    monthlyData[month] = { revenues: [], orders: [] };
                }
                monthlyData[month].revenues.push(parseFloat(item.revenue || 0));
                monthlyData[month].orders.push(parseInt(item.orders || 0));
            });
            
            const months = ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6',
                           'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'];
            
            const avgRevenues = [];
            const avgOrders = [];
            
            for (let i = 1; i <= 12; i++) {
                if (monthlyData[i]) {
                    const avgRev = monthlyData[i].revenues.reduce((a, b) => a + b, 0) / monthlyData[i].revenues.length;
                    const avgOrd = monthlyData[i].orders.reduce((a, b) => a + b, 0) / monthlyData[i].orders.length;
                    avgRevenues.push(avgRev);
                    avgOrders.push(avgOrd);
                } else {
                    avgRevenues.push(0);
                    avgOrders.push(0);
                }
            }
            
            reportsCharts.seasonal = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'Doanh thu TB (VNĐ)',
                        data: avgRevenues,
                        backgroundColor: 'rgba(59, 130, 246, 0.8)',
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 1,
                        yAxisID: 'y'
                    }, {
                        label: 'Đơn hàng TB',
                        data: avgOrders,
                        backgroundColor: 'rgba(147, 51, 234, 0.8)',
                        borderColor: 'rgba(147, 51, 234, 1)',
                        borderWidth: 1,
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        title: {
                            display: true,
                            text: 'Xu hướng theo mùa (Trung bình theo tháng)'
                        }
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            ticks: {
                                callback: function(value) {
                                    return formatCurrency(value);
                                }
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            grid: {
                                drawOnChartArea: false,
                            }
                        }
                    }
                }
            });
        }

        // Customer Analytics
        function loadCustomerReports() {
            const dateFrom = document.getElementById('reportDateFrom').value;
            const dateTo = document.getElementById('reportDateTo').value;
            
            fetch(\`reports-handler.php?action=customer_analytics&date_from=\${dateFrom}&date_to=\${dateTo}\`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        reportsData.customers = data.data;
                        renderCustomerCharts(data.data);
                    } else {
                        showError('Error loading customer reports: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error loading customer reports:', error);
                    showError('Failed to load customer reports');
                });
        }

        function renderCustomerCharts(data) {
            // CLV chart and stats
            if (data.clv) {
                createCLVChart(data.clv);
                renderCLVStats(data.clv);
            }
            
            // Customer segmentation
            if (data.segmentation) {
                createCustomerSegmentationChart(data.segmentation);
                renderSegmentationStats(data.segmentation);
            }
            
            // Purchase behavior
            if (data.behavior) {
                createPurchaseBehaviorCharts(data.behavior);
            }
            
            // Churn analysis
            if (data.churn) {
                updateChurnMetrics(data.churn);
            }
        }

        function createCLVChart(clvData) {
            const ctx = document.getElementById('clvChart');
            if (!ctx) return;
            
            if (reportsCharts.clv) {
                reportsCharts.clv.destroy();
            }
            
            // Group customers by CLV ranges
            const ranges = [
                { label: '0-100K', min: 0, max: 100000, count: 0 },
                { label: '100K-500K', min: 100000, max: 500000, count: 0 },
                { label: '500K-1M', min: 500000, max: 1000000, count: 0 },
                { label: '1M-5M', min: 1000000, max: 5000000, count: 0 },
                { label: '5M+', min: 5000000, max: Infinity, count: 0 }
            ];
            
            clvData.forEach(customer => {
                const clv = parseFloat(customer.lifetime_value || 0);
                for (let range of ranges) {
                    if (clv >= range.min && clv < range.max) {
                        range.count++;
                        break;
                    }
                }
            });
            
            const labels = ranges.map(r => r.label);
            const counts = ranges.map(r => r.count);
            
            reportsCharts.clv = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Số khách hàng',
                        data: counts,
                        backgroundColor: 'rgba(147, 51, 234, 0.8)',
                        borderColor: 'rgba(147, 51, 234, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        function renderCLVStats(clvData) {
            const container = document.getElementById('clvStats');
            if (!container || !clvData.length) return;
            
            const totalCLV = clvData.reduce((sum, customer) => sum + parseFloat(customer.lifetime_value || 0), 0);
            const avgCLV = totalCLV / clvData.length;
            const maxCLV = Math.max(...clvData.map(c => parseFloat(c.lifetime_value || 0)));
            const topCustomers = clvData.slice(0, 5);
            
            container.innerHTML = \`
                <div style="background: rgba(255, 255, 255, 0.1); padding: 1rem; border-radius: 1rem; margin-bottom: 1rem;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 1rem; text-align: center;">
                        <div>
                            <div style="font-size: 0.9rem; color: var(--color-neutral-600);">CLV TB</div>
                            <div style="font-weight: 700; color: var(--color-primary-600);">\${formatCurrency(avgCLV)}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.9rem; color: var(--color-neutral-600);">CLV cao nhất</div>
                            <div style="font-weight: 700; color: var(--color-success-600);">\${formatCurrency(maxCLV)}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.9rem; color: var(--color-neutral-600);">Tổng khách hàng</div>
                            <div style="font-weight: 700; color: var(--color-secondary-600);">\${clvData.length}</div>
                        </div>
                    </div>
                </div>
                <div style="max-height: 200px; overflow-y: auto;">
                    <h6 style="margin: 0 0 0.5rem 0; color: var(--color-neutral-700);">Top 5 khách hàng VIP:</h6>
                    \${topCustomers.map((customer, index) => \`
                        <div class="data-list-item">
                            <div>
                                <strong>\${customer.name}</strong><br>
                                <small style="color: var(--color-neutral-600);">\${customer.total_orders} đơn hàng</small>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-weight: 700; color: var(--color-primary-600);">\${formatCurrency(customer.lifetime_value)}</div>
                            </div>
                        </div>
                    \`).join('')}
                </div>
            \`;
        }

        function createCustomerSegmentationChart(segmentationData) {
            const ctx = document.getElementById('customerSegmentationChart');
            if (!ctx) return;
            
            if (reportsCharts.customerSegmentation) {
                reportsCharts.customerSegmentation.destroy();
            }
            
            const labels = segmentationData.map(item => item.segment);
            const counts = segmentationData.map(item => parseInt(item.customer_count || 0));
            
            reportsCharts.customerSegmentation = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: counts,
                        backgroundColor: [
                            'rgba(255, 182, 193, 0.8)',
                            'rgba(221, 160, 221, 0.8)',
                            'rgba(173, 216, 230, 0.8)',
                            'rgba(255, 218, 185, 0.8)',
                            'rgba(144, 238, 144, 0.8)'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        function renderSegmentationStats(segmentationData) {
            const container = document.getElementById('segmentationStats');
            if (!container) return;
            
            container.innerHTML = segmentationData.map(segment => \`
                <div style="background: rgba(255, 255, 255, 0.1); padding: 1rem; border-radius: 0.75rem; text-align: center;">
                    <div style="font-weight: 600; color: var(--color-primary-600);">\${segment.segment}</div>
                    <div style="font-size: 1.5rem; font-weight: 700; color: var(--color-secondary-600); margin: 0.25rem 0;">\${segment.customer_count}</div>
                    <div style="font-size: 0.9rem; color: var(--color-neutral-600);">khách hàng</div>
                    <div style="font-size: 0.8rem; color: var(--color-neutral-600); margin-top: 0.25rem;">CLV TB: \${formatCurrency(segment.avg_lifetime_value)}</div>
                </div>
            \`).join('');
        }

        function createPurchaseBehaviorCharts(behaviorData) {
            // Group by hour and day of week
            const hourlyData = Array(24).fill(0);
            const dailyData = Array(7).fill(0);
            
            behaviorData.forEach(item => {
                const hour = parseInt(item.hour_of_day);
                const day = parseInt(item.day_of_week);
                
                if (hour >= 0 && hour < 24) {
                    hourlyData[hour] += parseInt(item.order_count || 0);
                }
                if (day >= 0 && day < 7) {
                    dailyData[day] += parseInt(item.order_count || 0);
                }
            });
            
            // Create hourly chart
            const hourCtx = document.getElementById('purchaseTimeChart');
            if (hourCtx) {
                if (reportsCharts.purchaseTime) {
                    reportsCharts.purchaseTime.destroy();
                }
                
                const hourLabels = Array.from({length: 24}, (_, i) => \`\${i}:00\`);
                
                reportsCharts.purchaseTime = new Chart(hourCtx, {
                    type: 'line',
                    data: {
                        labels: hourLabels,
                        datasets: [{
                            label: 'Số đơn hàng',
                            data: hourlyData,
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
            
            // Create daily chart
            const dayCtx = document.getElementById('purchaseDayChart');
            if (dayCtx) {
                if (reportsCharts.purchaseDay) {
                    reportsCharts.purchaseDay.destroy();
                }
                
                const dayLabels = ['Chủ nhật', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'];
                
                reportsCharts.purchaseDay = new Chart(dayCtx, {
                    type: 'bar',
                    data: {
                        labels: dayLabels,
                        datasets: [{
                            label: 'Số đơn hàng',
                            data: dailyData,
                            backgroundColor: 'rgba(147, 51, 234, 0.8)',
                            borderColor: 'rgba(147, 51, 234, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
        }

        function updateChurnMetrics(churnData) {
            document.getElementById('churnRate').textContent = churnData.churn_rate + '%';
            document.getElementById('churnedCustomers').textContent = churnData.churned_customers;
        }

        // Inventory Reports
        function loadInventoryReports() {
            fetch('reports-handler.php?action=inventory_reports')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        reportsData.inventory = data.data;
                        renderInventoryCharts(data.data);
                    } else {
                        showError('Error loading inventory reports: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error loading inventory reports:', error);
                    showError('Failed to load inventory reports');
                });
        }

        function renderInventoryCharts(data) {
            // Stock status chart
            if (data.stock_levels) {
                createStockStatusChart(data.stock_levels);
                renderStockSummary(data.stock_levels);
            }
            
            // Low stock table
            if (data.low_stock) {
                renderLowStockTable(data.low_stock);
            }
            
            // Product velocity table
            if (data.velocity) {
                renderVelocityTable(data.velocity);
            }
            
            // Category performance chart
            if (data.category_performance) {
                createInventoryCategoryChart(data.category_performance);
                renderCategoryInventoryTable(data.category_performance);
            }
        }

        function createStockStatusChart(stockData) {
            const ctx = document.getElementById('stockStatusChart');
            if (!ctx) return;
            
            if (reportsCharts.stockStatus) {
                reportsCharts.stockStatus.destroy();
            }
            
            // Count by status
            const statusCounts = {
                'Out of Stock': 0,
                'Low Stock': 0,
                'Medium Stock': 0,
                'High Stock': 0
            };
            
            stockData.forEach(product => {
                statusCounts[product.stock_status]++;
            });
            
            const labels = Object.keys(statusCounts);
            const counts = Object.values(statusCounts);
            
            reportsCharts.stockStatus = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: counts,
                        backgroundColor: [
                            'rgba(239, 68, 68, 0.8)',
                            'rgba(249, 115, 22, 0.8)',
                            'rgba(59, 130, 246, 0.8)',
                            'rgba(34, 197, 94, 0.8)'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        function renderStockSummary(stockData) {
            const container = document.getElementById('stockSummary');
            if (!container) return;
            
            const totalProducts = stockData.length;
            const outOfStock = stockData.filter(p => p.stock === 0).length;
            const lowStock = stockData.filter(p => p.stock > 0 && p.stock <= 5).length;
            const totalValue = stockData.reduce((sum, p) => sum + (p.stock * parseFloat(p.price)), 0);
            
            container.innerHTML = \`
                <div style="background: rgba(255, 255, 255, 0.1); padding: 1rem; border-radius: 1rem;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 1rem; text-align: center;">
                        <div>
                            <div style="font-size: 0.9rem; color: var(--color-neutral-600);">Tổng sản phẩm</div>
                            <div style="font-weight: 700; color: var(--color-primary-600);">\${totalProducts}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.9rem; color: var(--color-neutral-600);">Hết hàng</div>
                            <div style="font-weight: 700; color: var(--color-error-600);">\${outOfStock}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.9rem; color: var(--color-neutral-600);">Sắp hết</div>
                            <div style="font-weight: 700; color: var(--color-warning-600);">\${lowStock}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.9rem; color: var(--color-neutral-600);">Giá trị kho</div>
                            <div style="font-weight: 700; color: var(--color-success-600);">\${formatCurrency(totalValue)}</div>
                        </div>
                    </div>
                </div>
            \`;
        }

        function renderLowStockTable(lowStockData) {
            const container = document.getElementById('lowStockTable');
            if (!container) return;
            
            if (!lowStockData.length) {
                container.innerHTML = '<div class="loading-spinner">Không có sản phẩm nào sắp hết hàng 🎉</div>';
                return;
            }
            
            container.innerHTML = \`
                <table class="data-table" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th>Danh mục</th>
                            <th>Tồn kho</th>
                            <th>Giá</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        \${lowStockData.map(product => \`
                            <tr>
                                <td style="font-weight: 600;">\${product.name}</td>
                                <td>\${product.category_name || 'N/A'}</td>
                                <td>
                                    <span style="background: \${product.stock === 0 ? 'rgba(239, 68, 68, 0.1)' : 'rgba(249, 115, 22, 0.1)'}; 
                                                color: \${product.stock === 0 ? 'var(--color-error-700)' : 'var(--color-warning-700)'}; 
                                                padding: 0.25rem 0.5rem; border-radius: 0.5rem; font-weight: 600;">
                                        \${product.stock}
                                    </span>
                                </td>
                                <td>\${formatCurrency(product.price)}</td>
                                <td>
                                    <span style="background: \${product.stock === 0 ? 'rgba(239, 68, 68, 0.1)' : 'rgba(249, 115, 22, 0.1)'}; 
                                                color: \${product.stock === 0 ? 'var(--color-error-700)' : 'var(--color-warning-700)'}; 
                                                padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.85rem; font-weight: 600;">
                                        \${product.stock === 0 ? 'Hết hàng' : 'Sắp hết'}
                                    </span>
                                </td>
                            </tr>
                        \`).join('')}
                    </tbody>
                </table>
            \`;
        }

        function renderVelocityTable(velocityData) {
            const container = document.getElementById('velocityTable');
            if (!container) return;
            
            const fastMoving = velocityData.filter(p => p.days_of_stock && p.days_of_stock <= 30).slice(0, 10);
            const slowMoving = velocityData.filter(p => p.days_of_stock && p.days_of_stock > 90).slice(0, 10);
            
            container.innerHTML = \`
                <div style="margin-bottom: 1.5rem;">
                    <h6 style="color: var(--color-success-600); margin-bottom: 0.5rem;">🚀 Sản phẩm bán nhanh (≤30 ngày):</h6>
                    \${fastMoving.length ? fastMoving.map(product => \`
                        <div class="data-list-item">
                            <div>
                                <strong>\${product.name}</strong><br>
                                <small style="color: var(--color-neutral-600);">Tồn kho: \${product.stock}</small>
                            </div>
                            <div style="text-align: right;">
                                <span style="background: rgba(34, 197, 94, 0.1); color: var(--color-success-700); 
                                           padding: 0.25rem 0.5rem; border-radius: 0.5rem; font-weight: 600;">
                                    \${product.days_of_stock} ngày
                                </span>
                            </div>
                        </div>
                    \`).join('') : '<p style="color: var(--color-neutral-500); text-align: center; padding: 1rem;">Không có dữ liệu</p>'}
                </div>
                <div>
                    <h6 style="color: var(--color-warning-600); margin-bottom: 0.5rem;">🐌 Sản phẩm bán chậm (>90 ngày):</h6>
                    \${slowMoving.length ? slowMoving.map(product => \`
                        <div class="data-list-item">
                            <div>
                                <strong>\${product.name}</strong><br>
                                <small style="color: var(--color-neutral-600);">Tồn kho: \${product.stock}</small>
                            </div>
                            <div style="text-align: right;">
                                <span style="background: rgba(249, 115, 22, 0.1); color: var(--color-warning-700); 
                                           padding: 0.25rem 0.5rem; border-radius: 0.5rem; font-weight: 600;">
                                    \${product.days_of_stock} ngày
                                </span>
                            </div>
                        </div>
                    \`).join('') : '<p style="color: var(--color-neutral-500); text-align: center; padding: 1rem;">Không có dữ liệu</p>'}
                </div>
            \`;
        }

        function createInventoryCategoryChart(categoryData) {
            const ctx = document.getElementById('inventoryCategoryChart');
            if (!ctx) return;
            
            if (reportsCharts.inventoryCategory) {
                reportsCharts.inventoryCategory.destroy();
            }
            
            const labels = categoryData.map(cat => cat.name);
            const stocks = categoryData.map(cat => parseInt(cat.total_stock || 0));
            const values = categoryData.map(cat => parseFloat(cat.avg_price || 0) * parseInt(cat.total_stock || 0));
            
            reportsCharts.inventoryCategory = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Tổng tồn kho',
                        data: stocks,
                        backgroundColor: 'rgba(59, 130, 246, 0.8)',
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 1,
                        yAxisID: 'y'
                    }, {
                        label: 'Giá trị kho (VNĐ)',
                        data: values,
                        backgroundColor: 'rgba(147, 51, 234, 0.8)',
                        borderColor: 'rgba(147, 51, 234, 1)',
                        borderWidth: 1,
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            grid: {
                                drawOnChartArea: false,
                            },
                            ticks: {
                                callback: function(value) {
                                    return formatCurrency(value);
                                }
                            }
                        }
                    }
                }
            });
        }

        function renderCategoryInventoryTable(categoryData) {
            const container = document.getElementById('categoryInventoryTable');
            if (!container) return;
            
            container.innerHTML = \`
                <table class="data-table" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Danh mục</th>
                            <th>Số sản phẩm</th>
                            <th>Tổng tồn kho</th>
                            <th>Sản phẩm sắp hết</th>
                            <th>Giá TB</th>
                            <th>Giá trị kho</th>
                        </tr>
                    </thead>
                    <tbody>
                        \${categoryData.map(category => \`
                            <tr>
                                <td style="font-weight: 600;">\${category.name}</td>
                                <td>\${category.total_products}</td>
                                <td>\${category.total_stock}</td>
                                <td>
                                    <span style="background: \${category.low_stock_products > 0 ? 'rgba(249, 115, 22, 0.1)' : 'rgba(34, 197, 94, 0.1)'}; 
                                                color: \${category.low_stock_products > 0 ? 'var(--color-warning-700)' : 'var(--color-success-700)'}; 
                                                padding: 0.25rem 0.5rem; border-radius: 0.5rem; font-weight: 600;">
                                        \${category.low_stock_products}
                                    </span>
                                </td>
                                <td>\${formatCurrency(category.avg_price)}</td>
                                <td style="color: var(--color-primary-600); font-weight: 600;">
                                    \${formatCurrency(parseFloat(category.avg_price || 0) * parseInt(category.total_stock || 0))}
                                </td>
                            </tr>
                        \`).join('')}
                    </tbody>
                </table>
            \`;
        }

        // Marketing Analytics
        function loadMarketingReports() {
            const dateFrom = document.getElementById('reportDateFrom').value;
            const dateTo = document.getElementById('reportDateTo').value;
            
            fetch(\`reports-handler.php?action=marketing_analytics&date_from=\${dateFrom}&date_to=\${dateTo}\`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        reportsData.marketing = data.data;
                        renderMarketingCharts(data.data);
                    } else {
                        showError('Error loading marketing reports: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error loading marketing reports:', error);
                    showError('Failed to load marketing reports');
                });
        }

        function renderMarketingCharts(data) {
            // Promotion effectiveness
            if (data.promotions) {
                renderPromotionEffectiveness(data.promotions);
            }
            
            // Campaign performance
            if (data.campaigns) {
                renderCampaignTable(data.campaigns);
            }
            
            // Discount analysis
            if (data.discounts) {
                createDiscountChart(data.discounts);
                renderDiscountStats(data.discounts);
            }
        }

        function renderPromotionEffectiveness(promotionsData) {
            const container = document.getElementById('promotionEffectiveness');
            if (!container) return;
            
            if (!promotionsData.length) {
                container.innerHTML = '<div class="loading-spinner">Chưa có dữ liệu khuyến mãi trong khoảng thời gian này</div>';
                return;
            }
            
            container.innerHTML = \`
                <table class="data-table" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Khuyến mãi</th>
                            <th>Sản phẩm</th>
                            <th>Giảm giá</th>
                            <th>Đơn hàng</th>
                            <th>Doanh thu</th>
                            <th>Hiệu quả</th>
                        </tr>
                    </thead>
                    <tbody>
                        \${promotionsData.map(promo => \`
                            <tr>
                                <td style="font-weight: 600;">\${promo.title}</td>
                                <td>\${promo.product_name || 'N/A'}</td>
                                <td>
                                    <span style="background: rgba(239, 68, 68, 0.1); color: var(--color-error-700); 
                                               padding: 0.25rem 0.5rem; border-radius: 0.5rem; font-weight: 600;">
                                        -\${promo.discount_percent}%
                                    </span>
                                </td>
                                <td>\${promo.orders_during_promotion || 0}</td>
                                <td style="color: var(--color-primary-600); font-weight: 600;">\${formatCurrency(promo.revenue_during_promotion)}</td>
                                <td>
                                    <span style="background: \${promo.revenue_during_promotion > 0 ? 'rgba(34, 197, 94, 0.1)' : 'rgba(249, 115, 22, 0.1)'}; 
                                                color: \${promo.revenue_during_promotion > 0 ? 'var(--color-success-700)' : 'var(--color-warning-700)'}; 
                                                padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.85rem; font-weight: 600;">
                                        \${promo.revenue_during_promotion > 0 ? 'Hiệu quả' : 'Cần cải thiện'}
                                    </span>
                                </td>
                            </tr>
                        \`).join('')}
                    </tbody>
                </table>
            \`;
        }

        function renderCampaignTable(campaignData) {
            const container = document.getElementById('campaignTable');
            if (!container) return;
            
            // Using promotion data for campaigns since they're the same in our system
            renderPromotionEffectiveness.call(this, campaignData);
        }

        function createDiscountChart(discountData) {
            const ctx = document.getElementById('discountChart');
            if (!ctx) return;
            
            if (reportsCharts.discount) {
                reportsCharts.discount.destroy();
            }
            
            const labels = discountData.map(item => \`\${item.discount_percent}%\`);
            const orders = discountData.map(item => parseInt(item.total_orders || 0));
            
            reportsCharts.discount = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Đơn hàng',
                        data: orders,
                        backgroundColor: 'rgba(239, 68, 68, 0.8)',
                        borderColor: 'rgba(239, 68, 68, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        title: {
                            display: true,
                            text: 'Đơn hàng theo mức giảm giá'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        function renderDiscountStats(discountData) {
            const container = document.getElementById('discountStats');
            if (!container) return;
            
            const totalPromotions = discountData.reduce((sum, item) => sum + parseInt(item.promotion_count || 0), 0);
            const totalOrders = discountData.reduce((sum, item) => sum + parseInt(item.total_orders || 0), 0);
            const avgOrderValue = discountData.reduce((sum, item) => sum + parseFloat(item.avg_order_value || 0), 0) / (discountData.length || 1);
            const maxDiscount = Math.max(...discountData.map(item => parseFloat(item.discount_percent || 0)));
            
            container.innerHTML = \`
                <div style="background: rgba(255, 255, 255, 0.1); padding: 1rem; border-radius: 1rem;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 1rem; text-align: center;">
                        <div>
                            <div style="font-size: 0.9rem; color: var(--color-neutral-600);">Tổng KM</div>
                            <div style="font-weight: 700; color: var(--color-primary-600);">\${totalPromotions}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.9rem; color: var(--color-neutral-600);">Đơn hàng KM</div>
                            <div style="font-weight: 700; color: var(--color-success-600);">\${totalOrders}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.9rem; color: var(--color-neutral-600);">AOV TB</div>
                            <div style="font-weight: 700; color: var(--color-secondary-600);">\${formatCurrency(avgOrderValue)}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.9rem; color: var(--color-neutral-600);">Giảm giá cao nhất</div>
                            <div style="font-weight: 700; color: var(--color-error-600);">\${maxDiscount}%</div>
                        </div>
                    </div>
                </div>
            \`;
        }

        // Export functionality
        function exportReportData(format) {
            const activeTab = document.querySelector('.report-tab.active').textContent.trim();
            const dateFrom = document.getElementById('reportDateFrom').value;
            const dateTo = document.getElementById('reportDateTo').value;
            
            let reportType = 'sales';
            if (activeTab.includes('Hiệu suất')) reportType = 'performance';
            else if (activeTab.includes('Khách hàng')) reportType = 'customers';
            else if (activeTab.includes('Kho hàng')) reportType = 'inventory';
            else if (activeTab.includes('Marketing')) reportType = 'marketing';
            
            const url = \`reports-handler.php?action=export_data&type=\${format}&report=\${reportType}&date_from=\${dateFrom}&date_to=\${dateTo}\`;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (format === 'csv') {
                            downloadCSV(data.data, data.filename);
                        } else if (format === 'pdf') {
                            showSuccess('Tính năng xuất PDF sẽ được bổ sung trong phiên bản tới');
                        }
                    } else {
                        showError('Lỗi xuất dữ liệu: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Export error:', error);
                    showError('Không thể xuất dữ liệu');
                });
        }

        function downloadCSV(data, filename) {
            if (!data || !data.length) {
                showError('Không có dữ liệu để xuất');
                return;
            }
            
            // Convert data to CSV
            const headers = Object.keys(data[0]);
            const csvContent = [
                headers.join(','),
                ...data.map(row => headers.map(header => {
                    const value = row[header] || '';
                    return typeof value === 'string' && value.includes(',') ? \`"\${value}"\` : value;
                }).join(','))
            ].join('\\n');
            
            // Create and trigger download
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            showSuccess('Tải xuống thành công: ' + filename);
        }

        // Utility functions
        function formatCurrency(amount) {
            return new Intl.NumberFormat('vi-VN', {
                style: 'currency',
                currency: 'VND'
            }).format(amount || 0);
        }

        function showLoadingSpinner() {
            // Could add loading indicators to various sections
            console.log('Loading reports data...');
        }

        function showError(message) {
            // Use existing error display function from the dashboard
            showAlert(message, 'error');
        }

        function showSuccess(message) {
            // Use existing success display function from the dashboard
            showAlert(message, 'success');
        }

        });
        
    </script>
</body>
</html>