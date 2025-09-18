<?php
require_once '../auth.php';

// Check if user is staff or admin
checkRole(['staff', 'admin']);

// Set JSON response header
header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'data' => null];

try {
    // Define which actions are read-only (allow GET) vs state-changing (require POST)
    $read_only_actions = ['list', 'get'];
    $state_changing_actions = ['update_status', 'add_note', 'cancel', 'bulk_update'];
    
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
            handleListOrders();
            break;
        case 'get':
            handleGetOrder();
            break;
        case 'update_status':
            handleUpdateOrderStatus();
            break;
        case 'add_note':
            handleAddNote();
            break;
        case 'cancel':
            handleCancelOrder();
            break;
        case 'bulk_update':
            handleBulkUpdate();
            break;
        default:
            $response['message'] = 'Invalid action';
    }
} catch (Exception $e) {
    // Log detailed error for debugging (server-side only)
    error_log('Order Handler Error: ' . $e->getMessage() . ' in ' . __FILE__ . ' at line ' . __LINE__);
    
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

function handleListOrders() {
    global $pdo, $response;
    
    try {
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = max(5, min(50, intval($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        
        $search = trim($_GET['search'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $sort = $_GET['sort'] ?? 'created_at';
        $order = strtoupper($_GET['order'] ?? 'DESC');
        
        // Validate sort and order parameters
        $allowed_sorts = ['id', 'customer_name', 'total_amount', 'status', 'created_at'];
        $allowed_orders = ['ASC', 'DESC'];
        
        if (!in_array($sort, $allowed_sorts)) $sort = 'created_at';
        if (!in_array($order, $allowed_orders)) $order = 'DESC';
        
        // Build base query
        $where_conditions = [];
        $params = [];
        
        // Search filter
        if (!empty($search)) {
            $where_conditions[] = "(LOWER(o.customer_name) LIKE LOWER(?) OR LOWER(o.customer_email) LIKE LOWER(?) OR CAST(o.id AS CHAR) LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        // Status filter
        if (!empty($status)) {
            $where_conditions[] = "o.status = ?";
            $params[] = $status;
        }
        
        $where_clause = empty($where_conditions) ? "" : "WHERE " . implode(" AND ", $where_conditions);
        
        // Get total count
        $count_sql = "SELECT COUNT(*) FROM orders o $where_clause";
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute($params);
        $total_orders = $count_stmt->fetchColumn();
        
        // Get orders with item count
        $sql = "SELECT o.*, 
                       COUNT(oi.id) as item_count,
                       COALESCE(SUM(oi.quantity), 0) as total_items
                FROM orders o 
                LEFT JOIN order_items oi ON o.id = oi.order_id
                $where_clause
                GROUP BY o.id, o.user_id, o.total_amount, o.status, o.created_at, o.updated_at, 
                         o.subtotal, o.shipping, o.tax, o.discount, o.customer_name, 
                         o.customer_email, o.customer_phone, o.shipping_address, o.notes, o.staff_notes
                ORDER BY o.$sort $order 
                LIMIT ? OFFSET ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge($params, [$limit, $offset]));
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format orders data
        foreach ($orders as &$order) {
            $order['total_amount'] = floatval($order['total_amount']);
            $order['item_count'] = intval($order['item_count']);
            $order['total_items'] = intval($order['total_items']);
            $order['subtotal'] = $order['subtotal'] ? floatval($order['subtotal']) : $order['total_amount'];
            $order['shipping'] = $order['shipping'] ? floatval($order['shipping']) : 0;
            $order['tax'] = $order['tax'] ? floatval($order['tax']) : 0;
            $order['discount'] = $order['discount'] ? floatval($order['discount']) : 0;
            
            // Format dates
            $order['created_at_formatted'] = date('M d, Y H:i', strtotime($order['created_at']));
            $order['created_at_short'] = date('M d', strtotime($order['created_at']));
            
            // Status badge info
            $order['status_info'] = getStatusInfo($order['status']);
        }
        
        $total_pages = ceil($total_orders / $limit);
        
        $response['success'] = true;
        $response['data'] = [
            'orders' => $orders,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $total_pages,
                'total_orders' => intval($total_orders),
                'per_page' => $limit,
                'has_next' => $page < $total_pages,
                'has_prev' => $page > 1
            ],
            'filters' => [
                'search' => $search,
                'status' => $status,
                'sort' => $sort,
                'order' => $order
            ]
        ];
        
    } catch (Exception $e) {
        $response['message'] = 'Error fetching orders: ' . $e->getMessage();
    }
}

function handleGetOrder() {
    global $pdo, $response;
    
    try {
        $order_id = intval($_GET['id'] ?? 0);
        
        if ($order_id <= 0) {
            $response['message'] = 'Invalid order ID';
            return;
        }
        
        // Get order details
        $stmt = $pdo->prepare("SELECT o.*, u.name as user_name, u.email as user_email
                               FROM orders o 
                               LEFT JOIN users u ON o.user_id = u.id
                               WHERE o.id = ?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            $response['message'] = 'Order not found';
            return;
        }
        
        // Get order items with product details
        $stmt = $pdo->prepare("SELECT oi.*, p.name as product_name, p.description as product_description, 
                                      p.image_url as product_image, c.name as category_name
                               FROM order_items oi
                               LEFT JOIN products p ON oi.product_id = p.id
                               LEFT JOIN categories c ON p.category_id = c.id
                               WHERE oi.order_id = ?
                               ORDER BY oi.id");
        $stmt->execute([$order_id]);
        $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format data
        $order['total_amount'] = floatval($order['total_amount']);
        $order['subtotal'] = $order['subtotal'] ? floatval($order['subtotal']) : $order['total_amount'];
        $order['shipping'] = $order['shipping'] ? floatval($order['shipping']) : 0;
        $order['tax'] = $order['tax'] ? floatval($order['tax']) : 0;
        $order['discount'] = $order['discount'] ? floatval($order['discount']) : 0;
        
        $order['created_at_formatted'] = date('M d, Y H:i', strtotime($order['created_at']));
        $order['updated_at_formatted'] = date('M d, Y H:i', strtotime($order['updated_at']));
        
        $order['status_info'] = getStatusInfo($order['status']);
        
        foreach ($order_items as &$item) {
            $item['price'] = floatval($item['price']);
            $item['quantity'] = intval($item['quantity']);
            $item['total'] = $item['price'] * $item['quantity'];
            $item['product_image'] = $item['product_image'] ?: 'https://via.placeholder.com/100x100?text=' . urlencode($item['product_name']);
        }
        
        $order['items'] = $order_items;
        
        $response['success'] = true;
        $response['data'] = $order;
        
    } catch (Exception $e) {
        $response['message'] = 'Error fetching order details: ' . $e->getMessage();
    }
}

function handleUpdateOrderStatus() {
    global $pdo, $response;
    
    // Check permission
    if (!hasPermission('manage_orders')) {
        $response['message'] = 'Access denied';
        return;
    }
    
    try {
        $order_id = intval($_POST['order_id'] ?? 0);
        $new_status = trim($_POST['status'] ?? '');
        $note = trim($_POST['note'] ?? '');
        
        if ($order_id <= 0) {
            $response['message'] = 'Invalid order ID';
            return;
        }
        
        $allowed_statuses = ['pending', 'processing', 'completed', 'cancelled'];
        if (!in_array($new_status, $allowed_statuses)) {
            $response['message'] = 'Invalid status';
            return;
        }
        
        // Get current order
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            $response['message'] = 'Order not found';
            return;
        }
        
        // Update order status and add note if provided
        $update_sql = "UPDATE orders SET status = ?, updated_at = NOW()";
        $update_params = [$new_status];
        
        if (!empty($note)) {
            $existing_notes = $order['staff_notes'] ?: '';
            $new_note_entry = date('Y-m-d H:i') . ' - ' . $_SESSION['user_name'] . ': ' . $note;
            $updated_notes = empty($existing_notes) ? $new_note_entry : $existing_notes . "\n" . $new_note_entry;
            
            $update_sql .= ", staff_notes = ?";
            $update_params[] = $updated_notes;
        }
        
        $update_sql .= " WHERE id = ?";
        $update_params[] = $order_id;
        
        $stmt = $pdo->prepare($update_sql);
        $stmt->execute($update_params);
        
        $response['success'] = true;
        $response['message'] = 'Order status updated successfully';
        $response['data'] = [
            'order_id' => $order_id,
            'old_status' => $order['status'],
            'new_status' => $new_status,
            'status_info' => getStatusInfo($new_status)
        ];
        
    } catch (Exception $e) {
        $response['message'] = 'Error updating order status: ' . $e->getMessage();
    }
}

function handleAddNote() {
    global $pdo, $response;
    
    try {
        $order_id = intval($_POST['order_id'] ?? 0);
        $note = trim($_POST['note'] ?? '');
        
        if ($order_id <= 0) {
            $response['message'] = 'Invalid order ID';
            return;
        }
        
        if (empty($note)) {
            $response['message'] = 'Note cannot be empty';
            return;
        }
        
        // Get current notes
        $stmt = $pdo->prepare("SELECT staff_notes FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $existing_notes = $stmt->fetchColumn();
        
        // Add new note
        $new_note_entry = date('Y-m-d H:i') . ' - ' . $_SESSION['user_name'] . ': ' . $note;
        $updated_notes = empty($existing_notes) ? $new_note_entry : $existing_notes . "\n" . $new_note_entry;
        
        $stmt = $pdo->prepare("UPDATE orders SET staff_notes = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$updated_notes, $order_id]);
        
        $response['success'] = true;
        $response['message'] = 'Note added successfully';
        $response['data'] = [
            'order_id' => $order_id,
            'notes' => $updated_notes
        ];
        
    } catch (Exception $e) {
        $response['message'] = 'Error adding note: ' . $e->getMessage();
    }
}

function handleCancelOrder() {
    global $pdo, $response;
    
    // Check permission
    if (!hasPermission('manage_orders')) {
        $response['message'] = 'Access denied';
        return;
    }
    
    try {
        $order_id = intval($_POST['order_id'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        
        if ($order_id <= 0) {
            $response['message'] = 'Invalid order ID';
            return;
        }
        
        // Get current order
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            $response['message'] = 'Order not found';
            return;
        }
        
        if ($order['status'] === 'cancelled') {
            $response['message'] = 'Order is already cancelled';
            return;
        }
        
        if ($order['status'] === 'completed') {
            $response['message'] = 'Cannot cancel completed order';
            return;
        }
        
        // Cancel order and add note
        $existing_notes = $order['staff_notes'] ?: '';
        $cancel_note = date('Y-m-d H:i') . ' - ' . $_SESSION['user_name'] . ': Order cancelled';
        if (!empty($reason)) {
            $cancel_note .= ' - Reason: ' . $reason;
        }
        $updated_notes = empty($existing_notes) ? $cancel_note : $existing_notes . "\n" . $cancel_note;
        
        $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled', staff_notes = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$updated_notes, $order_id]);
        
        $response['success'] = true;
        $response['message'] = 'Order cancelled successfully';
        $response['data'] = [
            'order_id' => $order_id,
            'status_info' => getStatusInfo('cancelled')
        ];
        
    } catch (Exception $e) {
        $response['message'] = 'Error cancelling order: ' . $e->getMessage();
    }
}

function handleBulkUpdate() {
    global $pdo, $response;
    
    // Check permission
    if (!hasPermission('manage_orders')) {
        $response['message'] = 'Access denied';
        return;
    }
    
    try {
        $order_ids = $_POST['order_ids'] ?? [];
        $action = $_POST['bulk_action'] ?? '';
        
        if (empty($order_ids) || !is_array($order_ids)) {
            $response['message'] = 'No orders selected';
            return;
        }
        
        $order_ids = array_map('intval', $order_ids);
        $order_ids = array_filter($order_ids, function($id) { return $id > 0; });
        
        if (empty($order_ids)) {
            $response['message'] = 'Invalid order IDs';
            return;
        }
        
        $placeholders = str_repeat('?,', count($order_ids) - 1) . '?';
        $updated = 0;
        
        switch ($action) {
            case 'mark_processing':
                $stmt = $pdo->prepare("UPDATE orders SET status = 'processing', updated_at = NOW() 
                                     WHERE id IN ($placeholders) AND status = 'pending'");
                $stmt->execute($order_ids);
                $updated = $stmt->rowCount();
                break;
                
            case 'mark_completed':
                $stmt = $pdo->prepare("UPDATE orders SET status = 'completed', updated_at = NOW() 
                                     WHERE id IN ($placeholders) AND status IN ('pending', 'processing')");
                $stmt->execute($order_ids);
                $updated = $stmt->rowCount();
                break;
                
            default:
                $response['message'] = 'Invalid bulk action';
                return;
        }
        
        $response['success'] = true;
        $response['message'] = "$updated orders updated successfully";
        $response['data'] = ['updated_count' => $updated];
        
    } catch (Exception $e) {
        $response['message'] = 'Error performing bulk update: ' . $e->getMessage();
    }
}

function getStatusInfo($status) {
    $status_map = [
        'pending' => [
            'label' => 'Pending',
            'color' => 'orange',
            'class' => 'status-pending'
        ],
        'processing' => [
            'label' => 'Processing',
            'color' => 'blue', 
            'class' => 'status-processing'
        ],
        'completed' => [
            'label' => 'Completed',
            'color' => 'green',
            'class' => 'status-completed'
        ],
        'cancelled' => [
            'label' => 'Cancelled',
            'color' => 'red',
            'class' => 'status-cancelled'
        ]
    ];
    
    return $status_map[$status] ?? [
        'label' => ucfirst($status),
        'color' => 'gray',
        'class' => 'status-unknown'
    ];
}
?>