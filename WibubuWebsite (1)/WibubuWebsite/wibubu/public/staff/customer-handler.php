<?php
require_once '../auth.php';

// Check if user is staff or admin
checkRole(['staff', 'admin']);

// Set JSON response header
header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'data' => null];

try {
    // Define which actions are read-only (allow GET) vs state-changing (require POST)
    $read_only_actions = ['list', 'get', 'analytics', 'get_notes', 'segment'];
    $state_changing_actions = ['add_note', 'update_status', 'export'];
    
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
        case 'list':
            handleListCustomers();
            break;
        case 'get':
            handleGetCustomer();
            break;
        case 'analytics':
            handleCustomerAnalytics();
            break;
        case 'add_note':
            handleAddNote();
            break;
        case 'get_notes':
            handleGetNotes();
            break;
        case 'update_status':
            handleUpdateStatus();
            break;
        case 'export':
            handleExportCustomers();
            break;
        case 'segment':
            handleCustomerSegmentation();
            break;
        default:
            $response['message'] = 'Invalid action';
    }
} catch (Exception $e) {
    // Log detailed error for debugging (server-side only)
    error_log('Customer Handler Error: ' . $e->getMessage() . ' in ' . __FILE__ . ' at line ' . __LINE__);
    
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

function handleListCustomers() {
    global $pdo, $response;
    
    try {
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = max(5, min(50, intval($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        
        $search = trim($_GET['search'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $sort = $_GET['sort'] ?? 'created_at';
        $order = strtoupper($_GET['order'] ?? 'DESC');
        $spending_level = trim($_GET['spending_level'] ?? '');
        
        // Validate sort and order parameters
        $allowed_sorts = ['name', 'email', 'created_at', 'total_spent', 'total_orders', 'last_order'];
        $allowed_orders = ['ASC', 'DESC'];
        
        if (!in_array($sort, $allowed_sorts)) $sort = 'created_at';
        if (!in_array($order, $allowed_orders)) $order = 'DESC';
        
        // Build base query with customer analytics
        $where_conditions = ['u.role = ?'];
        $params = ['customer'];
        
        // Search filter
        if (!empty($search)) {
            $where_conditions[] = "(LOWER(u.name) LIKE LOWER(?) OR LOWER(u.email) LIKE LOWER(?))";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        // Build customer analytics subquery
        $customer_analytics_sql = "
            SELECT 
                u.id,
                u.name,
                u.email,
                u.created_at,
                u.updated_at,
                COALESCE(customer_stats.total_orders, 0) as total_orders,
                COALESCE(customer_stats.total_spent, 0) as total_spent,
                COALESCE(customer_stats.avg_order_value, 0) as avg_order_value,
                customer_stats.last_order_date,
                customer_stats.first_order_date,
                CASE 
                    WHEN customer_stats.total_spent >= 5000000 THEN 'VIP'
                    WHEN customer_stats.last_order_date < NOW() - INTERVAL 90 DAY OR customer_stats.last_order_date IS NULL THEN 'inactive'
                    ELSE 'active'
                END as customer_status,
                CASE
                    WHEN customer_stats.first_order_date >= NOW() - INTERVAL 30 DAY THEN 'new'
                    WHEN customer_stats.total_orders >= 10 THEN 'regular'
                    ELSE 'occasional'
                END as customer_segment
            FROM users u
            LEFT JOIN (
                SELECT 
                    o.user_id,
                    COUNT(o.id) as total_orders,
                    SUM(o.total_amount) as total_spent,
                    AVG(o.total_amount) as avg_order_value,
                    MAX(o.created_at) as last_order_date,
                    MIN(o.created_at) as first_order_date
                FROM orders o
                WHERE o.status NOT IN ('cancelled')
                GROUP BY o.user_id
            ) customer_stats ON u.id = customer_stats.user_id
        ";
        
        $where_clause = "WHERE " . implode(" AND ", $where_conditions);
        
        // Status filter
        if (!empty($status)) {
            if ($status === 'VIP') {
                $where_conditions[] = "customer_stats.total_spent >= 5000000";
            } elseif ($status === 'inactive') {
                $where_conditions[] = "(customer_stats.last_order_date < NOW() - INTERVAL 90 DAY OR customer_stats.last_order_date IS NULL)";
            } elseif ($status === 'active') {
                $where_conditions[] = "customer_stats.last_order_date >= NOW() - INTERVAL 90 DAY";
            }
            $where_clause = "WHERE " . implode(" AND ", $where_conditions);
        }
        
        // Spending level filter
        if (!empty($spending_level)) {
            switch ($spending_level) {
                case 'high':
                    $where_conditions[] = "customer_stats.total_spent >= 2000000";
                    break;
                case 'medium':
                    $where_conditions[] = "customer_stats.total_spent >= 500000 AND customer_stats.total_spent < 2000000";
                    break;
                case 'low':
                    $where_conditions[] = "customer_stats.total_spent < 500000";
                    break;
            }
            $where_clause = "WHERE " . implode(" AND ", $where_conditions);
        }
        
        // Get total count
        $count_sql = "SELECT COUNT(*) FROM ($customer_analytics_sql $where_clause) as customers";
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute($params);
        $total_customers = $count_stmt->fetchColumn();
        
        // Map sort columns for the analytics query
        $sort_mapping = [
            'name' => 'u.name',
            'email' => 'u.email', 
            'created_at' => 'u.created_at',
            'total_spent' => 'total_spent',
            'total_orders' => 'total_orders',
            'last_order' => 'last_order_date'
        ];
        $actual_sort = $sort_mapping[$sort] ?? 'u.created_at';
        
        // Get customers
        $sql = "SELECT * FROM ($customer_analytics_sql $where_clause) as customers 
                ORDER BY $actual_sort $order 
                LIMIT ? OFFSET ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge($params, [$limit, $offset]));
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format customers data
        foreach ($customers as &$customer) {
            $customer['total_spent'] = floatval($customer['total_spent']);
            $customer['total_orders'] = intval($customer['total_orders']);
            $customer['avg_order_value'] = floatval($customer['avg_order_value']);
            
            // Format dates
            $customer['created_at_formatted'] = date('M d, Y', strtotime($customer['created_at']));
            $customer['last_order_formatted'] = $customer['last_order_date'] ? date('M d, Y', strtotime($customer['last_order_date'])) : 'Never';
            
            // Status badge info
            $customer['status_info'] = getCustomerStatusInfo($customer['customer_status']);
            $customer['segment_info'] = getCustomerSegmentInfo($customer['customer_segment']);
            
            // Privacy-compliant phone display (if exists in orders)
            $customer['phone'] = getCustomerPhone($customer['id']);
        }
        
        $total_pages = ceil($total_customers / $limit);
        
        $response['success'] = true;
        $response['data'] = [
            'customers' => $customers,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $total_pages,
                'total_customers' => intval($total_customers),
                'per_page' => $limit,
                'has_next' => $page < $total_pages,
                'has_prev' => $page > 1
            ],
            'filters' => [
                'search' => $search,
                'status' => $status,
                'spending_level' => $spending_level,
                'sort' => $sort,
                'order' => $order
            ]
        ];
        
    } catch (Exception $e) {
        $response['message'] = 'Error fetching customers: ' . $e->getMessage();
    }
}

function handleGetCustomer() {
    global $pdo, $response;
    
    try {
        $customer_id = intval($_GET['id'] ?? 0);
        
        if ($customer_id <= 0) {
            $response['message'] = 'Invalid customer ID';
            return;
        }
        
        // Get customer details with analytics
        $stmt = $pdo->prepare("
            SELECT 
                u.*,
                COALESCE(cs.total_orders, 0) as total_orders,
                COALESCE(cs.total_spent, 0) as total_spent,
                COALESCE(cs.avg_order_value, 0) as avg_order_value,
                cs.last_order_date,
                cs.first_order_date,
                CASE 
                    WHEN cs.total_spent >= 5000000 THEN 'VIP'
                    WHEN cs.last_order_date < NOW() - INTERVAL 90 DAY OR cs.last_order_date IS NULL THEN 'inactive'
                    ELSE 'active'
                END as customer_status
            FROM users u
            LEFT JOIN (
                SELECT 
                    o.user_id,
                    COUNT(o.id) as total_orders,
                    SUM(o.total_amount) as total_spent,
                    AVG(o.total_amount) as avg_order_value,
                    MAX(o.created_at) as last_order_date,
                    MIN(o.created_at) as first_order_date
                FROM orders o
                WHERE o.status NOT IN ('cancelled')
                GROUP BY o.user_id
            ) cs ON u.id = cs.user_id
            WHERE u.id = ? AND u.role = 'customer'
        ");
        $stmt->execute([$customer_id]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$customer) {
            $response['message'] = 'Customer not found';
            return;
        }
        
        // Get recent orders
        $stmt = $pdo->prepare("
            SELECT o.*, COUNT(oi.id) as item_count
            FROM orders o 
            LEFT JOIN order_items oi ON o.id = oi.order_id
            WHERE o.user_id = ?
            GROUP BY o.id
            ORDER BY o.created_at DESC 
            LIMIT 10
        ");
        $stmt->execute([$customer_id]);
        $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get favorite products/categories
        $stmt = $pdo->prepare("
            SELECT 
                p.id,
                p.name,
                c.name as category_name,
                SUM(oi.quantity) as total_quantity,
                COUNT(DISTINCT o.id) as order_count,
                MAX(o.created_at) as last_purchased
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            JOIN products p ON oi.product_id = p.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE o.user_id = ? AND o.status NOT IN ('cancelled')
            GROUP BY p.id, p.name, c.name
            ORDER BY total_quantity DESC
            LIMIT 5
        ");
        $stmt->execute([$customer_id]);
        $favorite_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get customer notes
        $stmt = $pdo->prepare("
            SELECT cn.*, u.name as staff_name
            FROM customer_notes cn
            LEFT JOIN users u ON cn.staff_id = u.id
            WHERE cn.customer_id = ?
            ORDER BY cn.created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$customer_id]);
        $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format data
        $customer['total_spent'] = floatval($customer['total_spent']);
        $customer['total_orders'] = intval($customer['total_orders']);
        $customer['avg_order_value'] = floatval($customer['avg_order_value']);
        
        foreach ($recent_orders as &$order) {
            $order['total_amount'] = floatval($order['total_amount']);
            $order['item_count'] = intval($order['item_count']);
            $order['created_at_formatted'] = date('M d, Y H:i', strtotime($order['created_at']));
        }
        
        foreach ($favorite_products as &$product) {
            $product['total_quantity'] = intval($product['total_quantity']);
            $product['order_count'] = intval($product['order_count']);
            $product['last_purchased_formatted'] = date('M d, Y', strtotime($product['last_purchased']));
        }
        
        foreach ($notes as &$note) {
            $note['created_at_formatted'] = date('M d, Y H:i', strtotime($note['created_at']));
        }
        
        $response['success'] = true;
        $response['data'] = [
            'customer' => $customer,
            'recent_orders' => $recent_orders,
            'favorite_products' => $favorite_products,
            'notes' => $notes
        ];
        
    } catch (Exception $e) {
        $response['message'] = 'Error fetching customer details: ' . $e->getMessage();
    }
}

function handleCustomerAnalytics() {
    global $pdo, $response;
    
    try {
        // Overall customer statistics
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT u.id) as total_customers,
                COUNT(DISTINCT CASE WHEN cs.total_spent >= 5000000 THEN u.id END) as vip_customers,
                COUNT(DISTINCT CASE WHEN cs.last_order_date >= NOW() - INTERVAL 90 DAY THEN u.id END) as active_customers,
                COUNT(DISTINCT CASE WHEN cs.last_order_date < NOW() - INTERVAL 90 DAY OR cs.last_order_date IS NULL THEN u.id END) as inactive_customers,
                COUNT(DISTINCT CASE WHEN cs.first_order_date >= NOW() - INTERVAL 30 DAY THEN u.id END) as new_customers,
                COALESCE(AVG(cs.total_spent), 0) as avg_customer_value,
                COALESCE(AVG(cs.total_orders), 0) as avg_orders_per_customer
            FROM users u
            LEFT JOIN (
                SELECT 
                    o.user_id,
                    COUNT(o.id) as total_orders,
                    SUM(o.total_amount) as total_spent,
                    MAX(o.created_at) as last_order_date,
                    MIN(o.created_at) as first_order_date
                FROM orders o
                WHERE o.status NOT IN ('cancelled')
                GROUP BY o.user_id
            ) cs ON u.id = cs.user_id
            WHERE u.role = 'customer'
        ");
        $stmt->execute();
        $overall_stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Monthly customer acquisition
        $stmt = $pdo->prepare("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m-01') as month,
                COUNT(*) as new_customers
            FROM users 
            WHERE role = 'customer' AND created_at >= NOW() - INTERVAL 12 MONTH
            GROUP BY DATE_FORMAT(created_at, '%Y-%m-01')
            ORDER BY month
        ");
        $stmt->execute();
        $monthly_acquisition = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Customer lifetime value distribution
        $stmt = $pdo->prepare("
            SELECT 
                CASE 
                    WHEN total_spent >= 5000000 THEN 'Very High (5M+)'
                    WHEN total_spent >= 2000000 THEN 'High (2M-5M)'
                    WHEN total_spent >= 500000 THEN 'Medium (500K-2M)'
                    WHEN total_spent > 0 THEN 'Low (<500K)'
                    ELSE 'No Orders'
                END as value_segment,
                COUNT(*) as customer_count
            FROM (
                SELECT 
                    u.id,
                    COALESCE(SUM(o.total_amount), 0) as total_spent
                FROM users u
                LEFT JOIN orders o ON u.id = o.user_id AND o.status NOT IN ('cancelled')
                WHERE u.role = 'customer'
                GROUP BY u.id
            ) customer_values
            GROUP BY value_segment
            ORDER BY 
                CASE value_segment
                    WHEN 'Very High (5M+)' THEN 1
                    WHEN 'High (2M-5M)' THEN 2
                    WHEN 'Medium (500K-2M)' THEN 3
                    WHEN 'Low (<500K)' THEN 4
                    ELSE 5
                END
        ");
        $stmt->execute();
        $clv_distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format data
        foreach ($overall_stats as $key => $value) {
            if (is_numeric($value)) {
                $overall_stats[$key] = is_float($value + 0) ? floatval($value) : intval($value);
            }
        }
        
        foreach ($monthly_acquisition as &$month_data) {
            $month_data['new_customers'] = intval($month_data['new_customers']);
            $month_data['month_formatted'] = date('M Y', strtotime($month_data['month']));
        }
        
        foreach ($clv_distribution as &$segment) {
            $segment['customer_count'] = intval($segment['customer_count']);
        }
        
        $response['success'] = true;
        $response['data'] = [
            'overall_stats' => $overall_stats,
            'monthly_acquisition' => $monthly_acquisition,
            'clv_distribution' => $clv_distribution
        ];
        
    } catch (Exception $e) {
        $response['message'] = 'Error fetching analytics: ' . $e->getMessage();
    }
}

function handleAddNote() {
    global $pdo, $response;
    
    try {
        $customer_id = intval($_POST['customer_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $note_type = trim($_POST['note_type'] ?? 'internal');
        $priority = trim($_POST['priority'] ?? 'normal');
        
        // Validation
        if ($customer_id <= 0) {
            $response['message'] = 'Invalid customer ID';
            return;
        }
        
        if (empty($content)) {
            $response['message'] = 'Note content is required';
            return;
        }
        
        if (!in_array($note_type, ['internal', 'communication', 'status_change', 'support'])) {
            $note_type = 'internal';
        }
        
        if (!in_array($priority, ['low', 'normal', 'high', 'urgent'])) {
            $priority = 'normal';
        }
        
        // Check if customer exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'customer'");
        $stmt->execute([$customer_id]);
        if (!$stmt->fetch()) {
            $response['message'] = 'Customer not found';
            return;
        }
        
        // Insert note
        $stmt = $pdo->prepare("
            INSERT INTO customer_notes (customer_id, staff_id, note_type, title, content, priority, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$customer_id, $_SESSION['user_id'], $note_type, $title, $content, $priority]);
        
        $note_id = $pdo->lastInsertId();
        
        // Get the created note
        $stmt = $pdo->prepare("
            SELECT cn.*, u.name as staff_name
            FROM customer_notes cn
            LEFT JOIN users u ON cn.staff_id = u.id
            WHERE cn.id = ?
        ");
        $stmt->execute([$note_id]);
        $note = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($note) {
            $note['created_at_formatted'] = date('M d, Y H:i', strtotime($note['created_at']));
        }
        
        $response['success'] = true;
        $response['message'] = 'Note added successfully';
        $response['data'] = $note;
        
    } catch (Exception $e) {
        $response['message'] = 'Error adding note: ' . $e->getMessage();
    }
}

function handleGetNotes() {
    global $pdo, $response;
    
    try {
        $customer_id = intval($_GET['customer_id'] ?? 0);
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = max(5, min(20, intval($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        
        if ($customer_id <= 0) {
            $response['message'] = 'Invalid customer ID';
            return;
        }
        
        // Get total count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM customer_notes WHERE customer_id = ?");
        $stmt->execute([$customer_id]);
        $total_notes = $stmt->fetchColumn();
        
        // Get notes
        $stmt = $pdo->prepare("
            SELECT cn.*, u.name as staff_name
            FROM customer_notes cn
            LEFT JOIN users u ON cn.staff_id = u.id
            WHERE cn.customer_id = ?
            ORDER BY cn.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$customer_id, $limit, $offset]);
        $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format notes
        foreach ($notes as &$note) {
            $note['created_at_formatted'] = date('M d, Y H:i', strtotime($note['created_at']));
        }
        
        $total_pages = ceil($total_notes / $limit);
        
        $response['success'] = true;
        $response['data'] = [
            'notes' => $notes,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $total_pages,
                'total_notes' => intval($total_notes),
                'per_page' => $limit
            ]
        ];
        
    } catch (Exception $e) {
        $response['message'] = 'Error fetching notes: ' . $e->getMessage();
    }
}

function handleUpdateStatus() {
    global $pdo, $response;
    
    try {
        $customer_id = intval($_POST['customer_id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        $note = trim($_POST['note'] ?? '');
        
        if ($customer_id <= 0) {
            $response['message'] = 'Invalid customer ID';
            return;
        }
        
        if (!in_array($status, ['active', 'inactive', 'VIP'])) {
            $response['message'] = 'Invalid status';
            return;
        }
        
        // Check if customer exists
        $stmt = $pdo->prepare("SELECT id, name FROM users WHERE id = ? AND role = 'customer'");
        $stmt->execute([$customer_id]);
        $customer = $stmt->fetch();
        if (!$customer) {
            $response['message'] = 'Customer not found';
            return;
        }
        
        // Add status change note
        $note_content = "Customer status changed to: " . ucfirst($status);
        if (!empty($note)) {
            $note_content .= "\nNote: " . $note;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO customer_notes (customer_id, staff_id, note_type, title, content, priority, created_at, updated_at) 
            VALUES (?, ?, 'status_change', 'Status Updated', ?, 'normal', NOW(), NOW())
        ");
        $stmt->execute([$customer_id, $_SESSION['user_id'], $note_content]);
        
        $response['success'] = true;
        $response['message'] = 'Customer status updated successfully';
        
    } catch (Exception $e) {
        $response['message'] = 'Error updating status: ' . $e->getMessage();
    }
}

function handleExportCustomers() {
    global $pdo, $response;
    
    // Check permission
    if (!hasPermission('view_customers')) {
        $response['message'] = 'Access denied';
        return;
    }
    
    try {
        $format = $_GET['format'] ?? 'csv';
        
        // Get customer data for export
        $stmt = $pdo->prepare("
            SELECT 
                u.name,
                u.email,
                u.created_at,
                COALESCE(cs.total_orders, 0) as total_orders,
                COALESCE(cs.total_spent, 0) as total_spent,
                COALESCE(cs.avg_order_value, 0) as avg_order_value,
                cs.last_order_date,
                CASE 
                    WHEN cs.total_spent >= 5000000 THEN 'VIP'
                    WHEN cs.last_order_date < NOW() - INTERVAL 90 DAY OR cs.last_order_date IS NULL THEN 'inactive'
                    ELSE 'active'
                END as customer_status
            FROM users u
            LEFT JOIN (
                SELECT 
                    o.user_id,
                    COUNT(o.id) as total_orders,
                    SUM(o.total_amount) as total_spent,
                    AVG(o.total_amount) as avg_order_value,
                    MAX(o.created_at) as last_order_date
                FROM orders o
                WHERE o.status NOT IN ('cancelled')
                GROUP BY o.user_id
            ) cs ON u.id = cs.user_id
            WHERE u.role = 'customer'
            ORDER BY u.created_at DESC
        ");
        $stmt->execute();
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($format === 'csv') {
            // Generate CSV content
            $csv_content = "Name,Email,Registration Date,Total Orders,Total Spent,Average Order Value,Last Order Date,Status\n";
            
            foreach ($customers as $customer) {
                $csv_content .= sprintf(
                    '"%s","%s","%s","%d","%s","%s","%s","%s"' . "\n",
                    $customer['name'],
                    $customer['email'],
                    date('Y-m-d', strtotime($customer['created_at'])),
                    $customer['total_orders'],
                    number_format($customer['total_spent'], 0),
                    number_format($customer['avg_order_value'], 0),
                    $customer['last_order_date'] ? date('Y-m-d', strtotime($customer['last_order_date'])) : '',
                    $customer['customer_status']
                );
            }
            
            $response['success'] = true;
            $response['data'] = [
                'filename' => 'customers_export_' . date('Y-m-d') . '.csv',
                'content' => $csv_content,
                'mime_type' => 'text/csv'
            ];
        }
        
    } catch (Exception $e) {
        $response['message'] = 'Error exporting customers: ' . $e->getMessage();
    }
}

function handleCustomerSegmentation() {
    global $pdo, $response;
    
    try {
        // Get customer segmentation data
        $stmt = $pdo->prepare("
            SELECT 
                CASE
                    WHEN cs.first_order_date >= NOW() - INTERVAL 30 DAY THEN 'new'
                    WHEN cs.total_orders >= 10 AND cs.total_spent >= 2000000 THEN 'vip_regular'
                    WHEN cs.total_orders >= 10 THEN 'regular'
                    WHEN cs.last_order_date < NOW() - INTERVAL 90 DAY OR cs.last_order_date IS NULL THEN 'inactive'
                    WHEN cs.total_spent >= 5000000 THEN 'high_value'
                    ELSE 'occasional'
                END as segment,
                COUNT(*) as customer_count,
                AVG(cs.total_spent) as avg_spent,
                AVG(cs.total_orders) as avg_orders
            FROM users u
            LEFT JOIN (
                SELECT 
                    o.user_id,
                    COUNT(o.id) as total_orders,
                    SUM(o.total_amount) as total_spent,
                    MAX(o.created_at) as last_order_date,
                    MIN(o.created_at) as first_order_date
                FROM orders o
                WHERE o.status NOT IN ('cancelled')
                GROUP BY o.user_id
            ) cs ON u.id = cs.user_id
            WHERE u.role = 'customer'
            GROUP BY segment
            ORDER BY customer_count DESC
        ");
        $stmt->execute();
        $segments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format data
        foreach ($segments as &$segment) {
            $segment['customer_count'] = intval($segment['customer_count']);
            $segment['avg_spent'] = floatval($segment['avg_spent'] ?? 0);
            $segment['avg_orders'] = floatval($segment['avg_orders'] ?? 0);
        }
        
        $response['success'] = true;
        $response['data'] = $segments;
        
    } catch (Exception $e) {
        $response['message'] = 'Error fetching segmentation: ' . $e->getMessage();
    }
}

// Helper functions
function getCustomerStatusInfo($status) {
    switch ($status) {
        case 'VIP':
            return ['color' => 'gold', 'text' => 'VIP', 'icon' => '👑'];
        case 'active':
            return ['color' => 'green', 'text' => 'Active', 'icon' => '✅'];
        case 'inactive':
            return ['color' => 'gray', 'text' => 'Inactive', 'icon' => '⏸️'];
        default:
            return ['color' => 'blue', 'text' => 'Customer', 'icon' => '👤'];
    }
}

function getCustomerSegmentInfo($segment) {
    switch ($segment) {
        case 'new':
            return ['color' => 'purple', 'text' => 'New', 'icon' => '🆕'];
        case 'regular':
            return ['color' => 'blue', 'text' => 'Regular', 'icon' => '⭐'];
        case 'occasional':
            return ['color' => 'orange', 'text' => 'Occasional', 'icon' => '📅'];
        default:
            return ['color' => 'gray', 'text' => 'Unknown', 'icon' => '❓'];
    }
}

function getCustomerPhone($customer_id) {
    global $pdo;
    
    try {
        // Get the most recent phone from orders (privacy-compliant)
        $stmt = $pdo->prepare("SELECT customer_phone FROM orders WHERE user_id = ? AND customer_phone IS NOT NULL ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$customer_id]);
        $phone = $stmt->fetchColumn();
        
        // Mask phone number for privacy (show only last 4 digits)
        if ($phone) {
            return '***-***-' . substr($phone, -4);
        }
        
        return null;
    } catch (Exception $e) {
        return null;
    }
}
?>