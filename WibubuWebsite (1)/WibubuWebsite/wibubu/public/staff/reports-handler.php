<?php
require_once '../auth.php';

// Check if user is staff or admin
checkRole(['staff', 'admin']);

// Set JSON response header
header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'data' => null];

try {
    // Define which actions are read-only (allow GET) vs state-changing (require POST)
    $read_only_actions = ['sales_dashboard', 'performance_metrics', 'customer_analytics', 'inventory_reports', 'marketing_analytics', 'real_time_widgets'];
    $state_changing_actions = ['export_data'];
    
    // Get the action from POST or GET
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    // Validate CSRF token for POST requests (state-changing operations)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('Invalid CSRF token');
        }
    }
    
    // Enforce POST-only for state-changing operations
    if (in_array($action, $state_changing_actions) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('This action requires POST method');
    }

    switch ($action) {
        case 'sales_dashboard':
            handleSalesDashboard();
            break;
        case 'performance_metrics':
            handlePerformanceMetrics();
            break;
        case 'customer_analytics':
            handleCustomerAnalytics();
            break;
        case 'inventory_reports':
            handleInventoryReports();
            break;
        case 'marketing_analytics':
            handleMarketingAnalytics();
            break;
        case 'real_time_widgets':
            handleRealTimeWidgets();
            break;
        case 'export_data':
            handleExportData();
            break;
        default:
            $response['message'] = 'Invalid action';
    }
} catch (Exception $e) {
    // Log detailed error for debugging (server-side only)
    error_log('Reports Handler Error: ' . $e->getMessage() . ' in ' . __FILE__ . ' at line ' . __LINE__);
    
    // Return safe error message to client
    if (strpos($e->getMessage(), 'Invalid CSRF token') !== false) {
        $response['message'] = 'Security validation failed. Please refresh the page and try again.';
    } elseif (strpos($e->getMessage(), 'requires POST method') !== false) {
        $response['message'] = 'Invalid request method.';
    } elseif (strpos($e->getMessage(), 'Access denied') !== false) {
        $response['message'] = 'Access denied.';
    } else {
        $response['message'] = 'An error occurred while processing your request. Please try again.';
    }
}

echo json_encode($response);
exit();

function handleSalesDashboard() {
    global $pdo, $response;
    
    try {
        $period = $_GET['period'] ?? 'monthly'; // daily, weekly, monthly, yearly
        $date_from = $_GET['date_from'] ?? null;
        $date_to = $_GET['date_to'] ?? null;
        
        // Set default date ranges based on period
        if (!$date_from || !$date_to) {
            switch ($period) {
                case 'daily':
                    $date_from = date('Y-m-d', strtotime('-30 days'));
                    $date_to = date('Y-m-d');
                    break;
                case 'weekly':
                    $date_from = date('Y-m-d', strtotime('-12 weeks'));
                    $date_to = date('Y-m-d');
                    break;
                case 'yearly':
                    $date_from = date('Y-m-d', strtotime('-5 years'));
                    $date_to = date('Y-m-d');
                    break;
                default: // monthly
                    $date_from = date('Y-m-d', strtotime('-12 months'));
                    $date_to = date('Y-m-d');
            }
        }
        
        $data = [];
        
        // Revenue Analytics
        $data['revenue'] = getRevenueAnalytics($period, $date_from, $date_to);
        
        // Sales Trends
        $data['trends'] = getSalesTrends($period, $date_from, $date_to);
        
        // Best Selling Products
        $data['best_products'] = getBestSellingProducts($date_from, $date_to, 10);
        
        // Category Performance
        $data['categories'] = getCategoryPerformance($date_from, $date_to);
        
        // Revenue Segments
        $data['segments'] = getRevenueSegments($date_from, $date_to);
        
        $response['success'] = true;
        $response['data'] = $data;
        
    } catch (Exception $e) {
        $response['message'] = 'Error fetching sales dashboard: ' . $e->getMessage();
    }
}

function handlePerformanceMetrics() {
    global $pdo, $response;
    
    try {
        $date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $date_to = $_GET['date_to'] ?? date('Y-m-d');
        
        $data = [];
        
        // Order Conversion Rates
        $data['conversion'] = getConversionRates($date_from, $date_to);
        
        // Average Order Value
        $data['aov'] = getAOVTrends($date_from, $date_to);
        
        // Customer Acquisition & Retention
        $data['acquisition'] = getCustomerAcquisition($date_from, $date_to);
        
        // Product Performance Rankings
        $data['product_performance'] = getProductPerformance($date_from, $date_to);
        
        // Seasonal Trends
        $data['seasonal'] = getSeasonalTrends();
        
        $response['success'] = true;
        $response['data'] = $data;
        
    } catch (Exception $e) {
        $response['message'] = 'Error fetching performance metrics: ' . $e->getMessage();
    }
}

function handleCustomerAnalytics() {
    global $pdo, $response;
    
    try {
        $date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-90 days'));
        $date_to = $_GET['date_to'] ?? date('Y-m-d');
        
        $data = [];
        
        // Customer Lifetime Value
        $data['clv'] = getCustomerLifetimeValue($date_from, $date_to);
        
        // Customer Segmentation
        $data['segmentation'] = getCustomerSegmentation($date_from, $date_to);
        
        // Purchase Behavior
        $data['behavior'] = getPurchaseBehavior($date_from, $date_to);
        
        // Customer Satisfaction from Reviews
        $data['satisfaction'] = getCustomerSatisfaction($date_from, $date_to);
        
        // Churn Analysis
        $data['churn'] = getChurnAnalysis($date_from, $date_to);
        
        $response['success'] = true;
        $response['data'] = $data;
        
    } catch (Exception $e) {
        $response['message'] = 'Error fetching customer analytics: ' . $e->getMessage();
    }
}

function handleInventoryReports() {
    global $pdo, $response;
    
    try {
        $data = [];
        
        // Stock Levels and Turnover
        $data['stock_levels'] = getStockLevels();
        
        // Low Stock Alerts
        $data['low_stock'] = getLowStockAlerts();
        
        // Product Velocity
        $data['velocity'] = getProductVelocity();
        
        // Category Performance
        $data['category_performance'] = getInventoryCategoryPerformance();
        
        $response['success'] = true;
        $response['data'] = $data;
        
    } catch (Exception $e) {
        $response['message'] = 'Error fetching inventory reports: ' . $e->getMessage();
    }
}

function handleMarketingAnalytics() {
    global $pdo, $response;
    
    try {
        $date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $date_to = $_GET['date_to'] ?? date('Y-m-d');
        
        $data = [];
        
        // Promotion Effectiveness
        $data['promotions'] = getPromotionEffectiveness($date_from, $date_to);
        
        // Campaign Performance
        $data['campaigns'] = getCampaignPerformance($date_from, $date_to);
        
        // Discount Analysis
        $data['discounts'] = getDiscountAnalysis($date_from, $date_to);
        
        $response['success'] = true;
        $response['data'] = $data;
        
    } catch (Exception $e) {
        $response['message'] = 'Error fetching marketing analytics: ' . $e->getMessage();
    }
}

function handleRealTimeWidgets() {
    global $pdo, $response;
    
    try {
        $data = [];
        
        // Real-time KPIs
        $data['kpis'] = getRealTimeKPIs();
        
        // Live sales counter
        $data['live_sales'] = getLiveSales();
        
        // Active orders
        $data['active_orders'] = getActiveOrders();
        
        // Inventory alerts
        $data['alerts'] = getInventoryAlerts();
        
        $response['success'] = true;
        $response['data'] = $data;
        
    } catch (Exception $e) {
        $response['message'] = 'Error fetching real-time widgets: ' . $e->getMessage();
    }
}

function handleExportData() {
    global $pdo, $response;
    
    try {
        $export_type = $_GET['type'] ?? 'csv'; // csv, pdf
        $report_type = $_GET['report'] ?? 'sales';
        $date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $date_to = $_GET['date_to'] ?? date('Y-m-d');
        
        $data = [];
        
        switch ($report_type) {
            case 'sales':
                $data = exportSalesData($date_from, $date_to);
                break;
            case 'customers':
                $data = exportCustomerData($date_from, $date_to);
                break;
            case 'inventory':
                $data = exportInventoryData();
                break;
            case 'performance':
                $data = exportPerformanceData($date_from, $date_to);
                break;
        }
        
        $response['success'] = true;
        $response['data'] = $data;
        $response['export_type'] = $export_type;
        $response['filename'] = "wibubu_{$report_type}_report_" . date('Y-m-d') . ".$export_type";
        
    } catch (Exception $e) {
        $response['message'] = 'Error exporting data: ' . $e->getMessage();
    }
}

// Helper Functions for Analytics Queries

function getRevenueAnalytics($period, $date_from, $date_to) {
    global $pdo;
    
    // Group by period
    $group_format = match($period) {
        'daily' => "DATE(created_at)",
        'weekly' => "DATE_FORMAT(created_at, '%Y-%m-%d') - INTERVAL (WEEKDAY(created_at)) DAY",
        'monthly' => "DATE_FORMAT(created_at, '%Y-%m-01')",
        'yearly' => "DATE_FORMAT(created_at, '%Y-01-01')",
        default => "DATE_FORMAT(created_at, '%Y-%m-01')"
    };
    
    $stmt = $pdo->prepare("
        SELECT 
            $group_format as period,
            SUM(total_amount) as revenue,
            COUNT(*) as orders,
            AVG(total_amount) as avg_order_value
        FROM orders 
        WHERE status IN ('completed', 'shipped') 
        AND created_at BETWEEN ? AND ? 
        GROUP BY $group_format 
        ORDER BY period ASC
    ");
    $stmt->execute([$date_from, $date_to]);
    $trends = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate totals
    $stmt = $pdo->prepare("
        SELECT 
            SUM(total_amount) as total_revenue,
            COUNT(*) as total_orders,
            AVG(total_amount) as avg_order_value
        FROM orders 
        WHERE status IN ('completed', 'shipped') 
        AND created_at BETWEEN ? AND ?
    ");
    $stmt->execute([$date_from, $date_to]);
    $totals = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return [
        'trends' => $trends,
        'totals' => $totals
    ];
}

function getSalesTrends($period, $date_from, $date_to) {
    global $pdo;
    
    $group_format = match($period) {
        'daily' => "DATE(created_at)",
        'weekly' => "DATE_FORMAT(created_at, '%Y-%m-%d') - INTERVAL (WEEKDAY(created_at)) DAY",
        'monthly' => "DATE_FORMAT(created_at, '%Y-%m-01')",
        'yearly' => "DATE_FORMAT(created_at, '%Y-01-01')",
        default => "DATE_FORMAT(created_at, '%Y-%m-01')"
    };
    
    $stmt = $pdo->prepare("
        SELECT 
            $group_format as period,
            SUM(total_amount) as revenue,
            COUNT(*) as orders
        FROM orders 
        WHERE created_at BETWEEN ? AND ? 
        GROUP BY $group_format 
        ORDER BY period ASC
    ");
    $stmt->execute([$date_from, $date_to]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getBestSellingProducts($date_from, $date_to, $limit = 10) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.name,
            p.price,
            p.image_url,
            c.name as category_name,
            SUM(oi.quantity) as total_sold,
            SUM(oi.quantity * oi.price) as total_revenue,
            COUNT(DISTINCT o.id) as order_count
        FROM products p
        JOIN order_items oi ON p.id = oi.product_id
        JOIN orders o ON oi.order_id = o.id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE o.status IN ('completed', 'shipped')
        AND o.created_at BETWEEN ? AND ?
        GROUP BY p.id, p.name, p.price, p.image_url, c.name
        ORDER BY total_sold DESC
        LIMIT ?
    ");
    $stmt->execute([$date_from, $date_to, $limit]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCategoryPerformance($date_from, $date_to) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            c.id,
            c.name,
            COUNT(DISTINCT p.id) as product_count,
            SUM(oi.quantity) as total_sold,
            SUM(oi.quantity * oi.price) as total_revenue,
            AVG(oi.price) as avg_price
        FROM categories c
        LEFT JOIN products p ON c.id = p.category_id
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id
        WHERE (o.status IN ('completed', 'shipped') OR o.id IS NULL)
        AND (o.created_at BETWEEN ? AND ? OR o.created_at IS NULL)
        GROUP BY c.id, c.name
        ORDER BY total_revenue DESC 
    ");
    $stmt->execute([$date_from, $date_to]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getRevenueSegments($date_from, $date_to) {
    global $pdo;
    
    // Customer value segments
    $stmt = $pdo->prepare("
        SELECT 
            CASE 
                WHEN total_spent >= 1000 THEN 'VIP'
                WHEN total_spent >= 500 THEN 'Premium'
                WHEN total_spent >= 100 THEN 'Regular'
                ELSE 'New'
            END as segment,
            COUNT(*) as customer_count,
            SUM(total_spent) as segment_revenue,
            AVG(total_spent) as avg_spent
        FROM (
            SELECT 
                u.id,
                u.name,
                COALESCE(SUM(o.total_amount), 0) as total_spent
            FROM users u
            LEFT JOIN orders o ON u.id = o.user_id
            WHERE u.role = 'customer'
            AND (o.status IN ('completed', 'shipped') OR o.id IS NULL)
            AND (o.created_at BETWEEN ? AND ? OR o.created_at IS NULL)
            GROUP BY u.id, u.name
        ) customer_totals
        GROUP BY segment
        ORDER BY segment_revenue DESC
    ");
    $stmt->execute([$date_from, $date_to]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getConversionRates($date_from, $date_to) {
    global $pdo;
    
    // Simple conversion metrics based on available data
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT CASE WHEN status = 'pending' THEN id END) as pending_orders,
            COUNT(DISTINCT CASE WHEN status IN ('completed', 'shipped') THEN id END) as completed_orders,
            COUNT(DISTINCT CASE WHEN status = 'cancelled' THEN id END) as cancelled_orders,
            COUNT(*) as total_orders
        FROM orders 
        WHERE created_at BETWEEN ? AND ?
    ");
    $stmt->execute([$date_from, $date_to]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $conversion_rate = $data['total_orders'] > 0 ? 
        round(($data['completed_orders'] / $data['total_orders']) * 100, 2) : 0;
    
    $cancellation_rate = $data['total_orders'] > 0 ? 
        round(($data['cancelled_orders'] / $data['total_orders']) * 100, 2) : 0;
    
    return [
        'conversion_rate' => $conversion_rate,
        'cancellation_rate' => $cancellation_rate,
        'pending_orders' => intval($data['pending_orders']),
        'completed_orders' => intval($data['completed_orders']),
        'cancelled_orders' => intval($data['cancelled_orders']),
        'total_orders' => intval($data['total_orders'])
    ];
}

function getAOVTrends($date_from, $date_to) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            DATE(created_at) as date,
            AVG(total_amount) as aov,
            COUNT(*) as orders
        FROM orders 
        WHERE status IN ('completed', 'shipped')
        AND created_at BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $stmt->execute([$date_from, $date_to]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCustomerAcquisition($date_from, $date_to) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as new_customers
        FROM users 
        WHERE role = 'customer'
        AND created_at BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $stmt->execute([$date_from, $date_to]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getProductPerformance($date_from, $date_to) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.name,
            p.price,
            p.stock,
            COALESCE(SUM(oi.quantity), 0) as total_sold,
            COALESCE(SUM(oi.quantity * oi.price), 0) as revenue,
            COALESCE(COUNT(DISTINCT o.id), 0) as order_count,
            ROUND(
                CASE 
                    WHEN p.stock > 0 THEN (COALESCE(SUM(oi.quantity), 0) / (p.stock + COALESCE(SUM(oi.quantity), 0))) * 100
                    ELSE 100
                END, 2
            ) as turnover_rate
        FROM products p
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id AND o.status IN ('completed', 'shipped')
        WHERE (o.created_at BETWEEN ? AND ? OR o.created_at IS NULL)
        GROUP BY p.id, p.name, p.price, p.stock
        ORDER BY revenue DESC
    ");
    $stmt->execute([$date_from, $date_to]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getSeasonalTrends() {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            EXTRACT(MONTH FROM created_at) as month,
            EXTRACT(YEAR FROM created_at) as year,
            SUM(total_amount) as revenue,
            COUNT(*) as orders
        FROM orders 
        WHERE status IN ('completed', 'shipped')
        AND created_at >= CURRENT_DATE - INTERVAL 2 YEAR
        GROUP BY EXTRACT(YEAR FROM created_at), EXTRACT(MONTH FROM created_at)
        ORDER BY year, month
    ");
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCustomerLifetimeValue($date_from, $date_to) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.name,
            u.email,
            u.created_at as registration_date,
            COUNT(o.id) as total_orders,
            COALESCE(SUM(o.total_amount), 0) as lifetime_value,
            COALESCE(AVG(o.total_amount), 0) as avg_order_value,
            MAX(o.created_at) as last_order_date
        FROM users u
        LEFT JOIN orders o ON u.id = o.user_id AND o.status IN ('completed', 'shipped')
        WHERE u.role = 'customer'
        GROUP BY u.id, u.name, u.email, u.created_at
        HAVING COUNT(o.id) > 0 OR u.created_at BETWEEN ? AND ?
        ORDER BY lifetime_value DESC
        LIMIT 100
    ");
    $stmt->execute([$date_from, $date_to]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCustomerSegmentation($date_from, $date_to) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            CASE 
                WHEN order_count = 0 THEN 'No Orders'
                WHEN order_count = 1 THEN 'One-time Buyer'
                WHEN order_count BETWEEN 2 AND 5 THEN 'Regular Customer'
                WHEN order_count > 5 THEN 'Loyal Customer'
            END as segment,
            COUNT(*) as customer_count,
            AVG(lifetime_value) as avg_lifetime_value,
            SUM(lifetime_value) as total_segment_value
        FROM (
            SELECT 
                u.id,
                COUNT(o.id) as order_count,
                COALESCE(SUM(o.total_amount), 0) as lifetime_value
            FROM users u
            LEFT JOIN orders o ON u.id = o.user_id AND o.status IN ('completed', 'shipped')
            WHERE u.role = 'customer'
            GROUP BY u.id
        ) customer_data
        GROUP BY segment
        ORDER BY total_segment_value DESC
    ");
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getPurchaseBehavior($date_from, $date_to) {
    global $pdo;
    
    // Purchase frequency analysis
    $stmt = $pdo->prepare("
        SELECT 
            EXTRACT(DOW FROM created_at) as day_of_week,
            EXTRACT(HOUR FROM created_at) as hour_of_day,
            COUNT(*) as order_count,
            SUM(total_amount) as revenue
        FROM orders 
        WHERE status IN ('completed', 'shipped')
        AND created_at BETWEEN ? AND ?
        GROUP BY EXTRACT(DOW FROM created_at), EXTRACT(HOUR FROM created_at)
        ORDER BY day_of_week, hour_of_day
    ");
    $stmt->execute([$date_from, $date_to]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCustomerSatisfaction($date_from, $date_to) {
    global $pdo;
    
    // Placeholder for reviews analysis - would need reviews table
    return [
        'avg_rating' => 4.2,
        'total_reviews' => 0,
        'rating_distribution' => [
            '5' => 0,
            '4' => 0,
            '3' => 0,
            '2' => 0,
            '1' => 0
        ]
    ];
}

function getChurnAnalysis($date_from, $date_to) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(CASE WHEN last_order_date < CURRENT_DATE - INTERVAL 90 DAY THEN 1 END) as churned_customers,
            COUNT(CASE WHEN last_order_date >= CURRENT_DATE - INTERVAL 30 DAY THEN 1 END) as active_customers,
            COUNT(*) as total_customers
        FROM (
            SELECT 
                u.id,
                MAX(o.created_at) as last_order_date
            FROM users u
            LEFT JOIN orders o ON u.id = o.user_id AND o.status IN ('completed', 'shipped')
            WHERE u.role = 'customer'
            GROUP BY u.id
            HAVING MAX(o.created_at) IS NOT NULL
        ) customer_activity
    ");
    $stmt->execute();
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $churn_rate = $data['total_customers'] > 0 ? 
        round(($data['churned_customers'] / $data['total_customers']) * 100, 2) : 0;
    
    return array_merge($data, ['churn_rate' => $churn_rate]);
}

function getStockLevels() {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.name,
            p.stock,
            p.price,
            c.name as category_name,
            CASE 
                WHEN p.stock = 0 THEN 'Out of Stock'
                WHEN p.stock <= 5 THEN 'Low Stock'
                WHEN p.stock <= 20 THEN 'Medium Stock'
                ELSE 'High Stock'
            END as stock_status
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        ORDER BY p.stock ASC
    ");
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getLowStockAlerts() {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.name,
            p.stock,
            p.price,
            c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.stock <= 5
        ORDER BY p.stock ASC
    ");
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getProductVelocity() {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.name,
            p.stock,
            COALESCE(SUM(oi.quantity), 0) as total_sold_30d,
            CASE 
                WHEN p.stock > 0 AND COALESCE(SUM(oi.quantity), 0) > 0 
                THEN ROUND(p.stock / (COALESCE(SUM(oi.quantity), 0) / 30.0), 1)
                ELSE NULL
            END as days_of_stock
        FROM products p
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id
        WHERE (o.status IN ('completed', 'shipped') OR o.id IS NULL)
        AND (o.created_at >= CURRENT_DATE - INTERVAL 30 DAY OR o.created_at IS NULL)
        GROUP BY p.id, p.name, p.stock
        ORDER BY days_of_stock ASC 
    ");
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getInventoryCategoryPerformance() {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            c.id,
            c.name,
            COUNT(p.id) as total_products,
            SUM(p.stock) as total_stock,
            COUNT(CASE WHEN p.stock <= 5 THEN 1 END) as low_stock_products,
            AVG(p.price) as avg_price
        FROM categories c
        LEFT JOIN products p ON c.id = p.category_id
        GROUP BY c.id, c.name
        ORDER BY total_stock DESC
    ");
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getPromotionEffectiveness($date_from, $date_to) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            pr.id,
            pr.title,
            pr.discount_percent,
            pr.start_date,
            pr.end_date,
            p.name as product_name,
            COUNT(o.id) as orders_during_promotion,
            SUM(o.total_amount) as revenue_during_promotion
        FROM promotions pr
        LEFT JOIN products p ON pr.product_id = p.id
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id
        WHERE (o.created_at BETWEEN pr.start_date AND pr.end_date OR o.id IS NULL)
        AND (o.status IN ('completed', 'shipped') OR o.id IS NULL)
        AND pr.start_date BETWEEN ? AND ?
        GROUP BY pr.id, pr.title, pr.discount_percent, pr.start_date, pr.end_date, p.name
        ORDER BY revenue_during_promotion DESC 
    ");
    $stmt->execute([$date_from, $date_to]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCampaignPerformance($date_from, $date_to) {
    global $pdo;
    
    // Since we don't have campaign tracking, return promotion data
    return getPromotionEffectiveness($date_from, $date_to);
}

function getDiscountAnalysis($date_from, $date_to) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            pr.discount_percent,
            COUNT(pr.id) as promotion_count,
            AVG(CASE WHEN o.id IS NOT NULL THEN o.total_amount END) as avg_order_value,
            COUNT(o.id) as total_orders
        FROM promotions pr
        LEFT JOIN products p ON pr.product_id = p.id
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id AND o.created_at BETWEEN pr.start_date AND pr.end_date
        WHERE pr.start_date BETWEEN ? AND ?
        GROUP BY pr.discount_percent
        ORDER BY pr.discount_percent
    ");
    $stmt->execute([$date_from, $date_to]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getRealTimeKPIs() {
    global $pdo;
    
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    // Today's metrics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as orders_today,
            COALESCE(SUM(total_amount), 0) as revenue_today,
            COALESCE(AVG(total_amount), 0) as aov_today
        FROM orders 
        WHERE DATE(created_at) = ? AND status IN ('completed', 'shipped')
    ");
    $stmt->execute([$today]);
    $today_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Yesterday's metrics for comparison
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as orders_yesterday,
            COALESCE(SUM(total_amount), 0) as revenue_yesterday
        FROM orders 
        WHERE DATE(created_at) = ? AND status IN ('completed', 'shipped')
    ");
    $stmt->execute([$yesterday]);
    $yesterday_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Calculate changes
    $revenue_change = $yesterday_data['revenue_yesterday'] > 0 ? 
        round((($today_data['revenue_today'] - $yesterday_data['revenue_yesterday']) / $yesterday_data['revenue_yesterday']) * 100, 1) : 0;
    
    $orders_change = $yesterday_data['orders_yesterday'] > 0 ? 
        round((($today_data['orders_today'] - $yesterday_data['orders_yesterday']) / $yesterday_data['orders_yesterday']) * 100, 1) : 0;
    
    return [
        'today' => $today_data,
        'yesterday' => $yesterday_data,
        'changes' => [
            'revenue' => $revenue_change,
            'orders' => $orders_change
        ]
    ];
}

function getLiveSales() {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            o.id,
            o.total_amount,
            o.created_at,
            u.name as customer_name
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        WHERE o.status IN ('completed', 'shipped')
        AND o.created_at >= CURRENT_DATE
        ORDER BY o.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getActiveOrders() {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
            COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as confirmed,
            COUNT(CASE WHEN status = 'shipped' THEN 1 END) as shipped,
            COUNT(*) as total
        FROM orders 
        WHERE status NOT IN ('completed', 'cancelled')
    ");
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getInventoryAlerts() {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(CASE WHEN stock = 0 THEN 1 END) as out_of_stock,
            COUNT(CASE WHEN stock <= 5 AND stock > 0 THEN 1 END) as low_stock,
            COUNT(*) as total_products
        FROM products
    ");
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Export Functions

function exportSalesData($date_from, $date_to) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            o.id as order_id,
            o.created_at,
            o.total_amount,
            o.status,
            u.name as customer_name,
            u.email as customer_email,
            p.name as product_name,
            oi.quantity,
            oi.price as unit_price,
            (oi.quantity * oi.price) as line_total
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE o.created_at BETWEEN ? AND ?
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$date_from, $date_to]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function exportCustomerData($date_from, $date_to) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.name,
            u.email,
            u.created_at as registration_date,
            COUNT(o.id) as total_orders,
            COALESCE(SUM(o.total_amount), 0) as lifetime_value,
            MAX(o.created_at) as last_order_date
        FROM users u
        LEFT JOIN orders o ON u.id = o.user_id AND o.status IN ('completed', 'shipped')
        WHERE u.role = 'customer'
        AND u.created_at BETWEEN ? AND ?
        GROUP BY u.id, u.name, u.email, u.created_at
        ORDER BY lifetime_value DESC
    ");
    $stmt->execute([$date_from, $date_to]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function exportInventoryData() {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.name,
            p.description,
            p.price,
            p.stock,
            c.name as category,
            p.created_at,
            p.updated_at
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        ORDER BY p.name
    ");
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function exportPerformanceData($date_from, $date_to) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            DATE(o.created_at) as date,
            COUNT(*) as orders,
            SUM(o.total_amount) as revenue,
            AVG(o.total_amount) as avg_order_value,
            COUNT(DISTINCT o.user_id) as unique_customers
        FROM orders o
        WHERE o.status IN ('completed', 'shipped')
        AND o.created_at BETWEEN ? AND ?
        GROUP BY DATE(o.created_at)
        ORDER BY date
    ");
    $stmt->execute([$date_from, $date_to]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>