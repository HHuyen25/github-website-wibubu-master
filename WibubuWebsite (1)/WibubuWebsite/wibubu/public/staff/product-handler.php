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
    $state_changing_actions = ['add', 'edit', 'delete', 'upload_image'];
    
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
            handleListProducts();
            break;
        case 'add':
            handleAddProduct();
            break;
        case 'edit':
            handleEditProduct();
            break;
        case 'delete':
            handleDeleteProduct();
            break;
        case 'get':
            handleGetProduct();
            break;
        case 'upload_image':
            handleImageUpload();
            break;
        default:
            $response['message'] = 'Invalid action';
    }
} catch (Exception $e) {
    // Log detailed error for debugging (server-side only)
    error_log('Product Handler Error: ' . $e->getMessage() . ' in ' . __FILE__ . ' at line ' . __LINE__);
    
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

function handleListProducts() {
    global $pdo, $response;
    
    try {
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = max(5, min(50, intval($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        
        $search = trim($_GET['search'] ?? '');
        $category = trim($_GET['category'] ?? '');
        $status = trim($_GET['status'] ?? ''); // active/inactive based on stock
        
        // Build base query
        $where_conditions = [];
        $params = [];
        
        // Search filter
        if (!empty($search)) {
            $where_conditions[] = "(LOWER(p.name) LIKE LOWER(?) OR LOWER(p.description) LIKE LOWER(?))";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        // Category filter
        if (!empty($category)) {
            $where_conditions[] = "p.category_id = ?";
            $params[] = $category;
        }
        
        // Status filter (based on stock)
        if ($status === 'active') {
            $where_conditions[] = "p.stock > 0";
        } elseif ($status === 'inactive') {
            $where_conditions[] = "p.stock = 0";
        }
        
        $where_clause = empty($where_conditions) ? "" : "WHERE " . implode(" AND ", $where_conditions);
        
        // Get total count
        $count_sql = "SELECT COUNT(*) FROM products p 
                      LEFT JOIN categories c ON p.category_id = c.id 
                      $where_clause";
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute($params);
        $total_products = $count_stmt->fetchColumn();
        
        // Get products
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                $where_clause
                ORDER BY p.created_at DESC 
                LIMIT ? OFFSET ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge($params, [$limit, $offset]));
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format products data
        foreach ($products as &$product) {
            $product['price'] = floatval($product['price']);
            $product['stock'] = intval($product['stock']);
            $product['status'] = $product['stock'] > 0 ? 'active' : 'inactive';
            $product['image_url'] = $product['image_url'] ?: 'https://via.placeholder.com/300x200?text=' . urlencode($product['name']);
        }
        
        $total_pages = ceil($total_products / $limit);
        
        $response['success'] = true;
        $response['data'] = [
            'products' => $products,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $total_pages,
                'total_products' => intval($total_products),
                'per_page' => $limit,
                'has_next' => $page < $total_pages,
                'has_prev' => $page > 1
            ],
            'filters' => [
                'search' => $search,
                'category' => $category,
                'status' => $status
            ]
        ];
        
    } catch (Exception $e) {
        $response['message'] = 'Error fetching products: ' . $e->getMessage();
    }
}

function handleAddProduct() {
    global $pdo, $response;
    
    // Check permission
    if (!hasPermission('manage_products')) {
        $response['message'] = 'Access denied';
        return;
    }
    
    try {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $stock = intval($_POST['stock'] ?? 0);
        $category_id = intval($_POST['category_id'] ?? 0);
        $image_url = trim($_POST['image_url'] ?? '');
        
        // Validation
        if (empty($name)) {
            $response['message'] = 'Product name is required';
            return;
        }
        
        if ($price <= 0) {
            $response['message'] = 'Price must be greater than 0';
            return;
        }
        
        if ($stock < 0) {
            $response['message'] = 'Stock cannot be negative';
            return;
        }
        
        if ($category_id <= 0) {
            $response['message'] = 'Please select a valid category';
            return;
        }
        
        // Check if category exists
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE id = ?");
        $stmt->execute([$category_id]);
        if (!$stmt->fetch()) {
            $response['message'] = 'Invalid category selected';
            return;
        }
        
        // Insert product
        $sql = "INSERT INTO products (name, description, price, stock, category_id, image_url, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $description, $price, $stock, $category_id, $image_url]);
        
        $product_id = $pdo->lastInsertId();
        
        // Get the created product
        $stmt = $pdo->prepare("SELECT p.*, c.name as category_name 
                               FROM products p 
                               LEFT JOIN categories c ON p.category_id = c.id 
                               WHERE p.id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            $product['price'] = floatval($product['price']);
            $product['stock'] = intval($product['stock']);
            $product['status'] = $product['stock'] > 0 ? 'active' : 'inactive';
        }
        
        $response['success'] = true;
        $response['message'] = 'Product added successfully';
        $response['data'] = $product;
        
    } catch (Exception $e) {
        $response['message'] = 'Error adding product: ' . $e->getMessage();
    }
}

function handleEditProduct() {
    global $pdo, $response;
    
    // Check permission
    if (!hasPermission('manage_products')) {
        $response['message'] = 'Access denied';
        return;
    }
    
    try {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $stock = intval($_POST['stock'] ?? 0);
        $category_id = intval($_POST['category_id'] ?? 0);
        $image_url = trim($_POST['image_url'] ?? '');
        
        // Validation
        if ($id <= 0) {
            $response['message'] = 'Invalid product ID';
            return;
        }
        
        if (empty($name)) {
            $response['message'] = 'Product name is required';
            return;
        }
        
        if ($price <= 0) {
            $response['message'] = 'Price must be greater than 0';
            return;
        }
        
        if ($stock < 0) {
            $response['message'] = 'Stock cannot be negative';
            return;
        }
        
        if ($category_id <= 0) {
            $response['message'] = 'Please select a valid category';
            return;
        }
        
        // Check if product exists
        $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            $response['message'] = 'Product not found';
            return;
        }
        
        // Check if category exists
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE id = ?");
        $stmt->execute([$category_id]);
        if (!$stmt->fetch()) {
            $response['message'] = 'Invalid category selected';
            return;
        }
        
        // Update product
        $sql = "UPDATE products 
                SET name = ?, description = ?, price = ?, stock = ?, category_id = ?, image_url = ?, updated_at = NOW()
                WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $description, $price, $stock, $category_id, $image_url, $id]);
        
        // Get the updated product
        $stmt = $pdo->prepare("SELECT p.*, c.name as category_name 
                               FROM products p 
                               LEFT JOIN categories c ON p.category_id = c.id 
                               WHERE p.id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            $product['price'] = floatval($product['price']);
            $product['stock'] = intval($product['stock']);
            $product['status'] = $product['stock'] > 0 ? 'active' : 'inactive';
        }
        
        $response['success'] = true;
        $response['message'] = 'Product updated successfully';
        $response['data'] = $product;
        
    } catch (Exception $e) {
        $response['message'] = 'Error updating product: ' . $e->getMessage();
    }
}

function handleDeleteProduct() {
    global $pdo, $response;
    
    // Check permission
    if (!hasPermission('manage_products')) {
        $response['message'] = 'Access denied';
        return;
    }
    
    try {
        $id = intval($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            $response['message'] = 'Invalid product ID';
            return;
        }
        
        // Check if product exists
        $stmt = $pdo->prepare("SELECT id, name FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            $response['message'] = 'Product not found';
            return;
        }
        
        // Check if product is referenced in orders
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE product_id = ?");
        $stmt->execute([$id]);
        $order_count = $stmt->fetchColumn();
        
        if ($order_count > 0) {
            $response['message'] = 'Cannot delete product: it is referenced in ' . $order_count . ' order(s)';
            return;
        }
        
        // Delete product
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
        
        $response['success'] = true;
        $response['message'] = 'Product "' . $product['name'] . '" deleted successfully';
        
    } catch (Exception $e) {
        $response['message'] = 'Error deleting product: ' . $e->getMessage();
    }
}

function handleGetProduct() {
    global $pdo, $response;
    
    try {
        $id = intval($_GET['id'] ?? 0);
        
        if ($id <= 0) {
            $response['message'] = 'Invalid product ID';
            return;
        }
        
        $stmt = $pdo->prepare("SELECT p.*, c.name as category_name 
                               FROM products p 
                               LEFT JOIN categories c ON p.category_id = c.id 
                               WHERE p.id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            $response['message'] = 'Product not found';
            return;
        }
        
        $product['price'] = floatval($product['price']);
        $product['stock'] = intval($product['stock']);
        $product['status'] = $product['stock'] > 0 ? 'active' : 'inactive';
        
        $response['success'] = true;
        $response['data'] = $product;
        
    } catch (Exception $e) {
        $response['message'] = 'Error fetching product: ' . $e->getMessage();
    }
}

function handleImageUpload() {
    global $response;
    
    // Check permission
    if (!hasPermission('manage_products')) {
        $response['message'] = 'Access denied';
        return;
    }
    
    try {
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $response['message'] = 'No image uploaded or upload error';
            return;
        }
        
        $file = $_FILES['image'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        // Enhanced MIME type validation using finfo (more secure than $_FILES['type'])
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $detected_type = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($detected_type, $allowed_types)) {
                $response['message'] = 'Invalid file type detected. Only image files are allowed';
                return;
            }
        } else {
            // Fallback to $_FILES type check
            if (!in_array($file['type'], $allowed_types)) {
                $response['message'] = 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed';
                return;
            }
        }
        
        // Validate file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            $response['message'] = 'File too large. Maximum size is 5MB';
            return;
        }
        
        // Create upload directory if it doesn't exist
        $upload_dir = '../assets/images/products/';
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                $response['message'] = 'Failed to create upload directory';
                return;
            }
        }
        
        // Validate filename and prevent directory traversal
        $original_name = basename($file['name']);
        if (strpos($original_name, '..') !== false || strpos($original_name, '/') !== false || strpos($original_name, '\\') !== false) {
            $response['message'] = 'Invalid filename';
            return;
        }
        
        // Validate file extension and get secure extension
        $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($extension, $allowed_extensions)) {
            $response['message'] = 'Invalid file extension. Only JPG, PNG, GIF, and WebP are allowed';
            return;
        }
        
        // Validate image dimensions and content
        $image_info = getimagesize($file['tmp_name']);
        if ($image_info === false) {
            $response['message'] = 'File is not a valid image';
            return;
        }
        
        // Generate secure filename (completely randomized, no user input)
        $filename = 'product_' . date('Y-m-d_H-i-s') . '_' . uniqid() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $filepath = $upload_dir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $image_url = 'assets/images/products/' . $filename;
            
            $response['success'] = true;
            $response['message'] = 'Image uploaded successfully';
            $response['data'] = ['image_url' => $image_url];
        } else {
            $response['message'] = 'Failed to move uploaded file';
        }
        
    } catch (Exception $e) {
        $response['message'] = 'Error uploading image: ' . $e->getMessage();
    }
}
?>