<?php
require_once '../auth.php';

// Check if user is staff or admin
checkRole(['staff', 'admin']);

// Set JSON response header
header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'data' => null];

try {
    // Define which actions are read-only (allow GET) vs state-changing (require POST)
    $read_only_actions = ['list', 'get', 'analytics', 'usage_stats', 'validate_coupon', 'get_products', 'get_categories'];
    $state_changing_actions = ['add', 'edit', 'delete', 'toggle_status', 'generate_coupon', 'bulk_generate_coupons', 'flash_sale'];
    
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
            handleListPromotions();
            break;
        case 'add':
            handleAddPromotion();
            break;
        case 'edit':
            handleEditPromotion();
            break;
        case 'delete':
            handleDeletePromotion();
            break;
        case 'get':
            handleGetPromotion();
            break;
        case 'toggle_status':
            handleToggleStatus();
            break;
        case 'generate_coupon':
            handleGenerateCoupon();
            break;
        case 'bulk_generate_coupons':
            handleBulkGenerateCoupons();
            break;
        case 'analytics':
            handleAnalytics();
            break;
        case 'usage_stats':
            handleUsageStats();
            break;
        case 'flash_sale':
            handleFlashSale();
            break;
        case 'validate_coupon':
            handleValidateCoupon();
            break;
        case 'get_products':
            handleGetProducts();
            break;
        case 'get_categories':
            handleGetCategories();
            break;
        default:
            $response['message'] = 'Invalid action';
    }
} catch (Exception $e) {
    // Log detailed error for debugging (server-side only)
    error_log('Promotion Handler Error: ' . $e->getMessage() . ' in ' . __FILE__ . ' at line ' . __LINE__);
    
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

function handleListPromotions() {
    global $pdo, $response;
    
    try {
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = max(5, min(50, intval($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        
        $search = trim($_GET['search'] ?? '');
        $type = trim($_GET['type'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $sort = $_GET['sort'] ?? 'created_at';
        $order = strtoupper($_GET['order'] ?? 'DESC');
        
        // Validate sort and order parameters
        $allowed_sorts = ['name', 'type', 'status', 'start_date', 'end_date', 'created_at'];
        $allowed_orders = ['ASC', 'DESC'];
        
        if (!in_array($sort, $allowed_sorts)) $sort = 'created_at';
        if (!in_array($order, $allowed_orders)) $order = 'DESC';
        
        // Build base query
        $where_conditions = [];
        $params = [];
        
        // Search filter
        if (!empty($search)) {
            $where_conditions[] = "(LOWER(p.name) LIKE LOWER(?) OR LOWER(p.description) LIKE LOWER(?))";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        // Type filter
        if (!empty($type)) {
            $where_conditions[] = "p.type = ?";
            $params[] = $type;
        }
        
        // Status filter
        if (!empty($status)) {
            $where_conditions[] = "p.status = ?";
            $params[] = $status;
        }
        
        $where_clause = empty($where_conditions) ? "" : "WHERE " . implode(" AND ", $where_conditions);
        
        // Get total count
        $count_sql = "SELECT COUNT(*) FROM promotions p $where_clause";
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute($params);
        $total_promotions = $count_stmt->fetchColumn();
        
        // Get promotions with usage statistics
        $sql = "SELECT p.*, 
                       u.name as created_by_name,
                       COUNT(pu.id) as total_uses,
                       COALESCE(SUM(pu.discount_amount), 0) as total_discount_given,
                       COUNT(cc.id) as total_coupons
                FROM promotions p 
                LEFT JOIN users u ON p.created_by = u.id
                LEFT JOIN promotion_usage pu ON p.id = pu.promotion_id
                LEFT JOIN coupon_codes cc ON p.id = cc.promotion_id AND cc.is_active = true
                $where_clause
                GROUP BY p.id, u.name
                ORDER BY p.$sort $order 
                LIMIT ? OFFSET ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge($params, [$limit, $offset]));
        $promotions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process promotions data and calculate computed fields
        foreach ($promotions as &$promotion) {
            $promotion['discount_value'] = floatval($promotion['discount_value']);
            $promotion['total_uses'] = intval($promotion['total_uses']);
            $promotion['current_uses'] = intval($promotion['current_uses']);
            $promotion['max_uses'] = $promotion['max_uses'] ? intval($promotion['max_uses']) : null;
            $promotion['total_discount_given'] = floatval($promotion['total_discount_given']);
            $promotion['total_coupons'] = intval($promotion['total_coupons']);
            
            // Calculate remaining uses
            if ($promotion['max_uses']) {
                $promotion['remaining_uses'] = max(0, $promotion['max_uses'] - $promotion['current_uses']);
            } else {
                $promotion['remaining_uses'] = null; // Unlimited
            }
            
            // Calculate status badge info
            $now = new DateTime();
            $start_date = new DateTime($promotion['start_date']);
            $end_date = new DateTime($promotion['end_date']);
            
            if ($promotion['status'] === 'active') {
                if ($now < $start_date) {
                    $promotion['computed_status'] = 'scheduled';
                    $promotion['status_badge'] = 'blue';
                } elseif ($now > $end_date) {
                    $promotion['computed_status'] = 'expired';
                    $promotion['status_badge'] = 'red';
                } else {
                    $promotion['computed_status'] = 'active';
                    $promotion['status_badge'] = 'green';
                }
            } else {
                $promotion['computed_status'] = $promotion['status'];
                $promotion['status_badge'] = $promotion['status'] === 'inactive' ? 'gray' : 'blue';
            }
            
            // Format dates for display
            $promotion['start_date_formatted'] = $start_date->format('M j, Y g:i A');
            $promotion['end_date_formatted'] = $end_date->format('M j, Y g:i A');
            
            // Calculate usage percentage
            if ($promotion['max_uses']) {
                $promotion['usage_percentage'] = round(($promotion['current_uses'] / $promotion['max_uses']) * 100, 1);
            } else {
                $promotion['usage_percentage'] = null;
            }
        }
        
        $total_pages = ceil($total_promotions / $limit);
        
        $response['success'] = true;
        $response['data'] = [
            'promotions' => $promotions,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $total_pages,
                'total_promotions' => intval($total_promotions),
                'per_page' => $limit,
                'has_next' => $page < $total_pages,
                'has_prev' => $page > 1
            ],
            'filters' => [
                'search' => $search,
                'type' => $type,
                'status' => $status
            ]
        ];
        
    } catch (Exception $e) {
        $response['message'] = 'Error fetching promotions: ' . $e->getMessage();
    }
}

function handleAddPromotion() {
    global $pdo, $response;
    
    // Check permission
    if (!hasPermission('manage_promotions')) {
        $response['message'] = 'Access denied';
        return;
    }
    
    try {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $type = trim($_POST['type'] ?? '');
        $discount_type = trim($_POST['discount_type'] ?? 'percentage');
        $discount_value = floatval($_POST['discount_value'] ?? 0);
        $start_date = trim($_POST['start_date'] ?? '');
        $end_date = trim($_POST['end_date'] ?? '');
        $max_uses = !empty($_POST['max_uses']) ? intval($_POST['max_uses']) : null;
        $max_uses_per_customer = !empty($_POST['max_uses_per_customer']) ? intval($_POST['max_uses_per_customer']) : null;
        $apply_to = trim($_POST['apply_to'] ?? 'all_products');
        $minimum_order_amount = !empty($_POST['minimum_order_amount']) ? floatval($_POST['minimum_order_amount']) : null;
        $status = trim($_POST['status'] ?? 'draft');
        $priority = intval($_POST['priority'] ?? 1);
        
        // Validation
        if (empty($name)) {
            $response['message'] = 'Promotion name is required';
            return;
        }
        
        if (!in_array($type, ['percentage', 'fixed_amount', 'buy_one_get_one', 'flash_sale', 'coupon_code'])) {
            $response['message'] = 'Invalid promotion type';
            return;
        }
        
        if (!in_array($discount_type, ['percentage', 'fixed'])) {
            $response['message'] = 'Invalid discount type';
            return;
        }
        
        if ($discount_value <= 0) {
            $response['message'] = 'Discount value must be greater than 0';
            return;
        }
        
        if ($discount_type === 'percentage' && $discount_value > 100) {
            $response['message'] = 'Percentage discount cannot exceed 100%';
            return;
        }
        
        if (empty($start_date) || empty($end_date)) {
            $response['message'] = 'Start and end dates are required';
            return;
        }
        
        // Validate dates
        $start_datetime = DateTime::createFromFormat('Y-m-d\TH:i', $start_date);
        $end_datetime = DateTime::createFromFormat('Y-m-d\TH:i', $end_date);
        
        if (!$start_datetime || !$end_datetime) {
            $response['message'] = 'Invalid date format';
            return;
        }
        
        if ($end_datetime <= $start_datetime) {
            $response['message'] = 'End date must be after start date';
            return;
        }
        
        // Check for conflicting promotions (same time period and overlapping products)
        if ($apply_to !== 'all_products') {
            // Additional validation for specific products/categories will be handled later
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            // Insert promotion
            $sql = "INSERT INTO promotions (name, description, type, discount_type, discount_value, 
                                         start_date, end_date, max_uses, max_uses_per_customer, 
                                         apply_to, minimum_order_amount, status, priority, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $name, $description, $type, $discount_type, $discount_value,
                $start_datetime->format('Y-m-d H:i:s'), $end_datetime->format('Y-m-d H:i:s'),
                $max_uses, $max_uses_per_customer, $apply_to, $minimum_order_amount,
                $status, $priority, $_SESSION['user_id']
            ]);
            
            $promotion_id = $pdo->lastInsertId();
            
            // Handle specific products
            if ($apply_to === 'specific_products' && !empty($_POST['product_ids'])) {
                $product_ids = is_array($_POST['product_ids']) ? $_POST['product_ids'] : explode(',', $_POST['product_ids']);
                
                foreach ($product_ids as $product_id) {
                    $product_id = intval(trim($product_id));
                    if ($product_id > 0) {
                        $stmt = $pdo->prepare("INSERT INTO promotion_products (promotion_id, product_id) VALUES (?, ?)");
                        $stmt->execute([$promotion_id, $product_id]);
                    }
                }
            }
            
            // Handle specific categories
            if ($apply_to === 'specific_categories' && !empty($_POST['category_ids'])) {
                $category_ids = is_array($_POST['category_ids']) ? $_POST['category_ids'] : explode(',', $_POST['category_ids']);
                
                foreach ($category_ids as $category_id) {
                    $category_id = intval(trim($category_id));
                    if ($category_id > 0) {
                        $stmt = $pdo->prepare("INSERT INTO promotion_categories (promotion_id, category_id) VALUES (?, ?)");
                        $stmt->execute([$promotion_id, $category_id]);
                    }
                }
            }
            
            // Generate coupon codes if this is a coupon promotion
            if ($type === 'coupon_code' && !empty($_POST['generate_coupons'])) {
                $coupon_count = min(100, max(1, intval($_POST['coupon_count'] ?? 1)));
                $coupon_prefix = trim($_POST['coupon_prefix'] ?? 'WIBUBU');
                
                for ($i = 0; $i < $coupon_count; $i++) {
                    $coupon_code = generateUniqueCouponCode($coupon_prefix);
                    $stmt = $pdo->prepare("INSERT INTO coupon_codes (promotion_id, code, max_uses) VALUES (?, ?, ?)");
                    $stmt->execute([$promotion_id, $coupon_code, 1]);
                }
            }
            
            $pdo->commit();
            
            // Get the created promotion with relations
            $promotion = getPromotionById($promotion_id);
            
            $response['success'] = true;
            $response['message'] = 'Promotion created successfully';
            $response['data'] = $promotion;
            
        } catch (Exception $e) {
            $pdo->rollback();
            throw $e;
        }
        
    } catch (Exception $e) {
        $response['message'] = 'Error creating promotion: ' . $e->getMessage();
    }
}

function handleEditPromotion() {
    global $pdo, $response;
    
    // Check permission
    if (!hasPermission('manage_promotions')) {
        $response['message'] = 'Access denied';
        return;
    }
    
    try {
        $id = intval($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            $response['message'] = 'Invalid promotion ID';
            return;
        }
        
        // Get existing promotion
        $stmt = $pdo->prepare("SELECT * FROM promotions WHERE id = ?");
        $stmt->execute([$id]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$existing) {
            $response['message'] = 'Promotion not found';
            return;
        }
        
        // Check if promotion is active and has restrictions
        $now = new DateTime();
        $start_date = new DateTime($existing['start_date']);
        $is_active = ($existing['status'] === 'active' && $now >= $start_date);
        
        // Extract and validate input data
        $name = trim($_POST['name'] ?? $existing['name']);
        $description = trim($_POST['description'] ?? $existing['description']);
        $type = trim($_POST['type'] ?? $existing['type']);
        $discount_type = trim($_POST['discount_type'] ?? $existing['discount_type']);
        $discount_value = floatval($_POST['discount_value'] ?? $existing['discount_value']);
        $start_date_input = trim($_POST['start_date'] ?? '');
        $end_date_input = trim($_POST['end_date'] ?? '');
        $max_uses = !empty($_POST['max_uses']) ? intval($_POST['max_uses']) : null;
        $max_uses_per_customer = !empty($_POST['max_uses_per_customer']) ? intval($_POST['max_uses_per_customer']) : null;
        $apply_to = trim($_POST['apply_to'] ?? $existing['apply_to']);
        $minimum_order_amount = !empty($_POST['minimum_order_amount']) ? floatval($_POST['minimum_order_amount']) : null;
        $status = trim($_POST['status'] ?? $existing['status']);
        $priority = intval($_POST['priority'] ?? $existing['priority']);
        
        // Restrictions for active promotions
        if ($is_active) {
            // Can't change fundamental settings of active promotions
            if ($type !== $existing['type'] || $discount_type !== $existing['discount_type'] || 
                $discount_value != $existing['discount_value'] || $apply_to !== $existing['apply_to']) {
                $response['message'] = 'Cannot modify core settings of an active promotion. You can only adjust dates, usage limits, and status.';
                return;
            }
            
            // Can't move start date to future if already started
            if (!empty($start_date_input)) {
                $new_start = DateTime::createFromFormat('Y-m-d\TH:i', $start_date_input);
                if ($new_start && $new_start > $now) {
                    $response['message'] = 'Cannot move start date to future for an already active promotion';
                    return;
                }
            }
        }
        
        // Validation (similar to add but with existing values as fallbacks)
        if (empty($name)) {
            $response['message'] = 'Promotion name is required';
            return;
        }
        
        // Handle date updates
        if (!empty($start_date_input)) {
            $start_datetime = DateTime::createFromFormat('Y-m-d\TH:i', $start_date_input);
            if (!$start_datetime) {
                $response['message'] = 'Invalid start date format';
                return;
            }
        } else {
            $start_datetime = new DateTime($existing['start_date']);
        }
        
        if (!empty($end_date_input)) {
            $end_datetime = DateTime::createFromFormat('Y-m-d\TH:i', $end_date_input);
            if (!$end_datetime) {
                $response['message'] = 'Invalid end date format';
                return;
            }
        } else {
            $end_datetime = new DateTime($existing['end_date']);
        }
        
        if ($end_datetime <= $start_datetime) {
            $response['message'] = 'End date must be after start date';
            return;
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            // Update promotion
            $sql = "UPDATE promotions 
                    SET name = ?, description = ?, type = ?, discount_type = ?, discount_value = ?,
                        start_date = ?, end_date = ?, max_uses = ?, max_uses_per_customer = ?,
                        apply_to = ?, minimum_order_amount = ?, status = ?, priority = ?,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $name, $description, $type, $discount_type, $discount_value,
                $start_datetime->format('Y-m-d H:i:s'), $end_datetime->format('Y-m-d H:i:s'),
                $max_uses, $max_uses_per_customer, $apply_to, $minimum_order_amount,
                $status, $priority, $id
            ]);
            
            // Update product/category associations if allowed
            if (!$is_active || $apply_to === $existing['apply_to']) {
                // Clear existing associations
                $pdo->prepare("DELETE FROM promotion_products WHERE promotion_id = ?")->execute([$id]);
                $pdo->prepare("DELETE FROM promotion_categories WHERE promotion_id = ?")->execute([$id]);
                
                // Re-add associations
                if ($apply_to === 'specific_products' && !empty($_POST['product_ids'])) {
                    $product_ids = is_array($_POST['product_ids']) ? $_POST['product_ids'] : explode(',', $_POST['product_ids']);
                    
                    foreach ($product_ids as $product_id) {
                        $product_id = intval(trim($product_id));
                        if ($product_id > 0) {
                            $stmt = $pdo->prepare("INSERT INTO promotion_products (promotion_id, product_id) VALUES (?, ?)");
                            $stmt->execute([$id, $product_id]);
                        }
                    }
                }
                
                if ($apply_to === 'specific_categories' && !empty($_POST['category_ids'])) {
                    $category_ids = is_array($_POST['category_ids']) ? $_POST['category_ids'] : explode(',', $_POST['category_ids']);
                    
                    foreach ($category_ids as $category_id) {
                        $category_id = intval(trim($category_id));
                        if ($category_id > 0) {
                            $stmt = $pdo->prepare("INSERT INTO promotion_categories (promotion_id, category_id) VALUES (?, ?)");
                            $stmt->execute([$id, $category_id]);
                        }
                    }
                }
            }
            
            $pdo->commit();
            
            // Get the updated promotion
            $promotion = getPromotionById($id);
            
            $response['success'] = true;
            $response['message'] = 'Promotion updated successfully';
            $response['data'] = $promotion;
            
        } catch (Exception $e) {
            $pdo->rollback();
            throw $e;
        }
        
    } catch (Exception $e) {
        $response['message'] = 'Error updating promotion: ' . $e->getMessage();
    }
}

function handleDeletePromotion() {
    global $pdo, $response;
    
    // Check permission
    if (!hasPermission('manage_promotions')) {
        $response['message'] = 'Access denied';
        return;
    }
    
    try {
        $id = intval($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            $response['message'] = 'Invalid promotion ID';
            return;
        }
        
        // Check if promotion exists and if it's safe to delete
        $stmt = $pdo->prepare("SELECT * FROM promotions WHERE id = ?");
        $stmt->execute([$id]);
        $promotion = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$promotion) {
            $response['message'] = 'Promotion not found';
            return;
        }
        
        // Check if promotion has been used
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM promotion_usage WHERE promotion_id = ?");
        $stmt->execute([$id]);
        $usage_count = $stmt->fetchColumn();
        
        if ($usage_count > 0) {
            $response['message'] = 'Cannot delete promotion that has been used. You can deactivate it instead.';
            return;
        }
        
        // Check if promotion is currently active
        $now = new DateTime();
        $start_date = new DateTime($promotion['start_date']);
        $end_date = new DateTime($promotion['end_date']);
        
        if ($promotion['status'] === 'active' && $now >= $start_date && $now <= $end_date) {
            $response['message'] = 'Cannot delete an active promotion. Please deactivate it first.';
            return;
        }
        
        // Delete promotion (CASCADE will handle related records)
        $stmt = $pdo->prepare("DELETE FROM promotions WHERE id = ?");
        $stmt->execute([$id]);
        
        $response['success'] = true;
        $response['message'] = 'Promotion deleted successfully';
        
    } catch (Exception $e) {
        $response['message'] = 'Error deleting promotion: ' . $e->getMessage();
    }
}

function handleGetPromotion() {
    global $pdo, $response;
    
    try {
        $id = intval($_GET['id'] ?? 0);
        
        if ($id <= 0) {
            $response['message'] = 'Invalid promotion ID';
            return;
        }
        
        $promotion = getPromotionById($id);
        
        if (!$promotion) {
            $response['message'] = 'Promotion not found';
            return;
        }
        
        $response['success'] = true;
        $response['data'] = $promotion;
        
    } catch (Exception $e) {
        $response['message'] = 'Error fetching promotion: ' . $e->getMessage();
    }
}

function handleToggleStatus() {
    global $pdo, $response;
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $response['message'] = 'Invalid CSRF token';
        return;
    }
    
    // Check permission
    if (!hasPermission('manage_promotions')) {
        $response['message'] = 'Access denied';
        return;
    }
    
    try {
        $id = intval($_POST['id'] ?? 0);
        $new_status = trim($_POST['status'] ?? '');
        
        if ($id <= 0) {
            $response['message'] = 'Invalid promotion ID';
            return;
        }
        
        if (!in_array($new_status, ['active', 'inactive', 'draft'])) {
            $response['message'] = 'Invalid status';
            return;
        }
        
        // Update status
        $stmt = $pdo->prepare("UPDATE promotions SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$new_status, $id]);
        
        if ($stmt->rowCount() === 0) {
            $response['message'] = 'Promotion not found';
            return;
        }
        
        $response['success'] = true;
        $response['message'] = 'Promotion status updated successfully';
        $response['data'] = ['id' => $id, 'status' => $new_status];
        
    } catch (Exception $e) {
        $response['message'] = 'Error updating promotion status: ' . $e->getMessage();
    }
}

function handleGenerateCoupon() {
    global $pdo, $response;
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $response['message'] = 'Invalid CSRF token';
        return;
    }
    
    // Check permission
    if (!hasPermission('manage_promotions')) {
        $response['message'] = 'Access denied';
        return;
    }
    
    try {
        $promotion_id = intval($_POST['promotion_id'] ?? 0);
        $prefix = trim($_POST['prefix'] ?? 'WIBUBU');
        $max_uses = max(1, intval($_POST['max_uses'] ?? 1));
        $user_id = !empty($_POST['user_id']) ? intval($_POST['user_id']) : null;
        
        if ($promotion_id <= 0) {
            $response['message'] = 'Invalid promotion ID';
            return;
        }
        
        // Verify promotion exists and is coupon type
        $stmt = $pdo->prepare("SELECT type FROM promotions WHERE id = ?");
        $stmt->execute([$promotion_id]);
        $promotion = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$promotion || $promotion['type'] !== 'coupon_code') {
            $response['message'] = 'Invalid promotion or not a coupon promotion';
            return;
        }
        
        // Generate unique coupon code
        $coupon_code = generateUniqueCouponCode($prefix);
        
        // Insert coupon
        $stmt = $pdo->prepare("INSERT INTO coupon_codes (promotion_id, code, max_uses, user_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$promotion_id, $coupon_code, $max_uses, $user_id]);
        
        $response['success'] = true;
        $response['message'] = 'Coupon code generated successfully';
        $response['data'] = [
            'coupon_code' => $coupon_code,
            'max_uses' => $max_uses,
            'user_id' => $user_id
        ];
        
    } catch (Exception $e) {
        $response['message'] = 'Error generating coupon: ' . $e->getMessage();
    }
}

function handleBulkGenerateCoupons() {
    global $pdo, $response;
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $response['message'] = 'Invalid CSRF token';
        return;
    }
    
    // Check permission
    if (!hasPermission('manage_promotions')) {
        $response['message'] = 'Access denied';
        return;
    }
    
    try {
        $promotion_id = intval($_POST['promotion_id'] ?? 0);
        $count = min(1000, max(1, intval($_POST['count'] ?? 10)));
        $prefix = trim($_POST['prefix'] ?? 'WIBUBU');
        $max_uses = max(1, intval($_POST['max_uses'] ?? 1));
        
        if ($promotion_id <= 0) {
            $response['message'] = 'Invalid promotion ID';
            return;
        }
        
        // Verify promotion exists and is coupon type
        $stmt = $pdo->prepare("SELECT type FROM promotions WHERE id = ?");
        $stmt->execute([$promotion_id]);
        $promotion = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$promotion || $promotion['type'] !== 'coupon_code') {
            $response['message'] = 'Invalid promotion or not a coupon promotion';
            return;
        }
        
        $generated_codes = [];
        $stmt = $pdo->prepare("INSERT INTO coupon_codes (promotion_id, code, max_uses) VALUES (?, ?, ?)");
        
        for ($i = 0; $i < $count; $i++) {
            $coupon_code = generateUniqueCouponCode($prefix);
            $stmt->execute([$promotion_id, $coupon_code, $max_uses]);
            $generated_codes[] = $coupon_code;
        }
        
        $response['success'] = true;
        $response['message'] = "Successfully generated $count coupon codes";
        $response['data'] = [
            'generated_codes' => $generated_codes,
            'count' => $count
        ];
        
    } catch (Exception $e) {
        $response['message'] = 'Error generating coupons: ' . $e->getMessage();
    }
}

function handleAnalytics() {
    global $pdo, $response;
    
    try {
        $promotion_id = intval($_GET['promotion_id'] ?? 0);
        $period = $_GET['period'] ?? '30'; // days
        
        if ($promotion_id <= 0) {
            $response['message'] = 'Invalid promotion ID';
            return;
        }
        
        // Get promotion details
        $stmt = $pdo->prepare("SELECT * FROM promotions WHERE id = ?");
        $stmt->execute([$promotion_id]);
        $promotion = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$promotion) {
            $response['message'] = 'Promotion not found';
            return;
        }
        
        // Get usage analytics
        $period_days = intval($period);
        $start_date = date('Y-m-d H:i:s', strtotime("-$period_days days"));
        
        // Total usage stats
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_uses,
                COUNT(DISTINCT user_id) as unique_users,
                SUM(discount_amount) as total_discount,
                SUM(order_total) as total_revenue,
                AVG(order_total) as avg_order_value
            FROM promotion_usage 
            WHERE promotion_id = ? AND used_at >= ?
        ");
        $stmt->execute([$promotion_id, $start_date]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Daily usage trends
        $stmt = $pdo->prepare("
            SELECT 
                DATE(used_at) as date,
                COUNT(*) as uses,
                SUM(discount_amount) as discount,
                SUM(order_total) as revenue
            FROM promotion_usage 
            WHERE promotion_id = ? AND used_at >= ?
            GROUP BY DATE(used_at)
            ORDER BY date
        ");
        $stmt->execute([$promotion_id, $start_date]);
        $daily_trends = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Top products affected (if applicable)
        $top_products = [];
        if ($promotion['apply_to'] !== 'all_products') {
            $stmt = $pdo->prepare("
                SELECT p.name, COUNT(*) as usage_count
                FROM promotion_usage pu
                JOIN orders o ON pu.order_id = o.id
                JOIN order_items oi ON o.id = oi.order_id
                JOIN products p ON oi.product_id = p.id
                WHERE pu.promotion_id = ? AND pu.used_at >= ?
                GROUP BY p.id, p.name
                ORDER BY usage_count DESC
                LIMIT 10
            ");
            $stmt->execute([$promotion_id, $start_date]);
            $top_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        $response['success'] = true;
        $response['data'] = [
            'promotion' => $promotion,
            'stats' => $stats,
            'daily_trends' => $daily_trends,
            'top_products' => $top_products,
            'period_days' => $period_days
        ];
        
    } catch (Exception $e) {
        $response['message'] = 'Error fetching analytics: ' . $e->getMessage();
    }
}

function handleUsageStats() {
    global $pdo, $response;
    
    try {
        // Get overall promotion stats
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_promotions,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_promotions,
                COUNT(CASE WHEN status = 'scheduled' THEN 1 END) as scheduled_promotions,
                COUNT(CASE WHEN status = 'expired' THEN 1 END) as expired_promotions
            FROM promotions
        ");
        $stmt->execute();
        $promotion_stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get usage stats for last 30 days
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_uses,
                COUNT(DISTINCT promotion_id) as promotions_used,
                COUNT(DISTINCT user_id) as unique_users,
                SUM(discount_amount) as total_discount,
                SUM(order_total) as total_revenue
            FROM promotion_usage 
            WHERE used_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->execute();
        $usage_stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Top performing promotions
        $stmt = $pdo->prepare("
            SELECT 
                p.name,
                p.type,
                COUNT(pu.id) as uses,
                SUM(pu.discount_amount) as total_discount,
                SUM(pu.order_total) as total_revenue
            FROM promotions p
            LEFT JOIN promotion_usage pu ON p.id = pu.promotion_id 
                AND pu.used_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY p.id, p.name, p.type
            ORDER BY uses DESC
            LIMIT 10
        ");
        $stmt->execute();
        $top_promotions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response['success'] = true;
        $response['data'] = [
            'promotion_stats' => $promotion_stats,
            'usage_stats' => $usage_stats,
            'top_promotions' => $top_promotions
        ];
        
    } catch (Exception $e) {
        $response['message'] = 'Error fetching usage stats: ' . $e->getMessage();
    }
}

function handleFlashSale() {
    global $pdo, $response;
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $response['message'] = 'Invalid CSRF token';
        return;
    }
    
    // Check permission
    if (!hasPermission('manage_promotions')) {
        $response['message'] = 'Access denied';
        return;
    }
    
    try {
        $action = $_POST['flash_action'] ?? 'create';
        
        if ($action === 'create') {
            $name = trim($_POST['name'] ?? '');
            $discount_value = floatval($_POST['discount_value'] ?? 0);
            $duration_hours = intval($_POST['duration_hours'] ?? 1);
            $product_ids = $_POST['product_ids'] ?? [];
            
            if (empty($name)) {
                $response['message'] = 'Flash sale name is required';
                return;
            }
            
            if ($discount_value <= 0 || $discount_value > 90) {
                $response['message'] = 'Discount must be between 1% and 90%';
                return;
            }
            
            if ($duration_hours < 1 || $duration_hours > 72) {
                $response['message'] = 'Duration must be between 1 and 72 hours';
                return;
            }
            
            // Create flash sale promotion
            $start_date = new DateTime();
            $end_date = clone $start_date;
            $end_date->add(new DateInterval("PT{$duration_hours}H"));
            
            $stmt = $pdo->prepare("
                INSERT INTO promotions (name, description, type, discount_type, discount_value,
                                     start_date, end_date, apply_to, status, priority, created_by)
                VALUES (?, ?, 'flash_sale', 'percentage', ?, ?, ?, ?, 'active', 10, ?)
            ");
            
            $description = "Flash Sale - Limited time offer for $duration_hours hours!";
            $apply_to = empty($product_ids) ? 'all_products' : 'specific_products';
            
            $stmt->execute([
                $name, $description, $discount_value,
                $start_date->format('Y-m-d H:i:s'), $end_date->format('Y-m-d H:i:s'),
                $apply_to, $_SESSION['user_id']
            ]);
            
            $promotion_id = $pdo->lastInsertId();
            
            // Add specific products if provided
            if (!empty($product_ids)) {
                $stmt = $pdo->prepare("INSERT INTO promotion_products (promotion_id, product_id) VALUES (?, ?)");
                foreach ($product_ids as $product_id) {
                    $stmt->execute([$promotion_id, intval($product_id)]);
                }
            }
            
            $response['success'] = true;
            $response['message'] = 'Flash sale created successfully';
            $response['data'] = [
                'promotion_id' => $promotion_id,
                'end_time' => $end_date->format('Y-m-d H:i:s')
            ];
        }
        
    } catch (Exception $e) {
        $response['message'] = 'Error handling flash sale: ' . $e->getMessage();
    }
}

function handleValidateCoupon() {
    global $pdo, $response;
    
    try {
        $code = trim($_GET['code'] ?? '');
        $user_id = intval($_GET['user_id'] ?? 0);
        $order_total = floatval($_GET['order_total'] ?? 0);
        
        if (empty($code)) {
            $response['message'] = 'Coupon code is required';
            return;
        }
        
        // Get coupon with promotion details
        $stmt = $pdo->prepare("
            SELECT cc.*, p.name, p.type, p.discount_type, p.discount_value,
                   p.start_date, p.end_date, p.status, p.minimum_order_amount,
                   p.max_uses, p.max_uses_per_customer, p.current_uses
            FROM coupon_codes cc
            JOIN promotions p ON cc.promotion_id = p.id
            WHERE cc.code = ? AND cc.is_active = true
        ");
        $stmt->execute([$code]);
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$coupon) {
            $response['message'] = 'Invalid or expired coupon code';
            return;
        }
        
        // Check promotion status and dates
        $now = new DateTime();
        $start_date = new DateTime($coupon['start_date']);
        $end_date = new DateTime($coupon['end_date']);
        
        if ($coupon['status'] !== 'active') {
            $response['message'] = 'This promotion is not currently active';
            return;
        }
        
        if ($now < $start_date) {
            $response['message'] = 'This promotion has not started yet';
            return;
        }
        
        if ($now > $end_date) {
            $response['message'] = 'This promotion has expired';
            return;
        }
        
        // Check minimum order amount
        if ($coupon['minimum_order_amount'] && $order_total < $coupon['minimum_order_amount']) {
            $response['message'] = "Minimum order amount of $" . number_format($coupon['minimum_order_amount'], 2) . " required";
            return;
        }
        
        // Check usage limits
        if ($coupon['max_uses'] && $coupon['current_uses'] >= $coupon['max_uses']) {
            $response['message'] = 'This coupon has reached its usage limit';
            return;
        }
        
        if ($coupon['max_uses'] && $coupon['current_uses'] >= $coupon['max_uses']) {
            $response['message'] = 'This coupon has reached its usage limit';
            return;
        }
        
        // Check per-customer usage limit
        if ($user_id > 0 && $coupon['max_uses_per_customer']) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM promotion_usage WHERE promotion_id = ? AND user_id = ?");
            $stmt->execute([$coupon['promotion_id'], $user_id]);
            $user_usage = $stmt->fetchColumn();
            
            if ($user_usage >= $coupon['max_uses_per_customer']) {
                $response['message'] = 'You have reached the usage limit for this coupon';
                return;
            }
        }
        
        // Calculate discount amount
        $discount_amount = 0;
        if ($coupon['discount_type'] === 'percentage') {
            $discount_amount = ($order_total * $coupon['discount_value']) / 100;
        } else {
            $discount_amount = min($coupon['discount_value'], $order_total);
        }
        
        $response['success'] = true;
        $response['data'] = [
            'coupon' => $coupon,
            'discount_amount' => round($discount_amount, 2),
            'final_total' => round($order_total - $discount_amount, 2)
        ];
        
    } catch (Exception $e) {
        $response['message'] = 'Error validating coupon: ' . $e->getMessage();
    }
}

function handleGetProducts() {
    global $pdo, $response;
    
    try {
        $search = trim($_GET['search'] ?? '');
        $category_id = intval($_GET['category_id'] ?? 0);
        
        $where_conditions = [];
        $params = [];
        
        if (!empty($search)) {
            $where_conditions[] = "LOWER(name) LIKE LOWER(?)";
            $params[] = "%$search%";
        }
        
        if ($category_id > 0) {
            $where_conditions[] = "category_id = ?";
            $params[] = $category_id;
        }
        
        $where_clause = empty($where_conditions) ? "" : "WHERE " . implode(" AND ", $where_conditions);
        
        $stmt = $pdo->prepare("SELECT id, name, price, stock FROM products $where_clause ORDER BY name LIMIT 100");
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response['success'] = true;
        $response['data'] = $products;
        
    } catch (Exception $e) {
        $response['message'] = 'Error fetching products: ' . $e->getMessage();
    }
}

function handleGetCategories() {
    global $pdo, $response;
    
    try {
        $stmt = $pdo->prepare("SELECT id, name FROM categories ORDER BY name");
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response['success'] = true;
        $response['data'] = $categories;
        
    } catch (Exception $e) {
        $response['message'] = 'Error fetching categories: ' . $e->getMessage();
    }
}

// Helper functions

function getPromotionById($id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT p.*, u.name as created_by_name
        FROM promotions p 
        LEFT JOIN users u ON p.created_by = u.id
        WHERE p.id = ?
    ");
    $stmt->execute([$id]);
    $promotion = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$promotion) {
        return null;
    }
    
    // Get associated products
    $stmt = $pdo->prepare("
        SELECT pr.id, pr.name, pr.price 
        FROM promotion_products pp
        JOIN products pr ON pp.product_id = pr.id
        WHERE pp.promotion_id = ?
    ");
    $stmt->execute([$id]);
    $promotion['products'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get associated categories
    $stmt = $pdo->prepare("
        SELECT c.id, c.name
        FROM promotion_categories pc
        JOIN categories c ON pc.category_id = c.id
        WHERE pc.promotion_id = ?
    ");
    $stmt->execute([$id]);
    $promotion['categories'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get coupon codes
    $stmt = $pdo->prepare("
        SELECT code, max_uses, current_uses, is_active
        FROM coupon_codes
        WHERE promotion_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$id]);
    $promotion['coupon_codes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $promotion;
}

function generateUniqueCouponCode($prefix = 'WIBUBU') {
    global $pdo;
    
    do {
        $random = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8));
        $code = $prefix . $random;
        
        // Check if code already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM coupon_codes WHERE code = ?");
        $stmt->execute([$code]);
        $exists = $stmt->fetchColumn() > 0;
        
    } while ($exists);
    
    return $code;
}
?>