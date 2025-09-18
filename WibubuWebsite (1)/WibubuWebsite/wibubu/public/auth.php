<?php
session_start();

// Initialize database connection
try {
    $pdo = new PDO(
        'pgsql:host=' . ($_ENV['PGHOST'] ?? getenv('PGHOST')) . 
        ';port=' . ($_ENV['PGPORT'] ?? getenv('PGPORT')) . 
        ';dbname=' . ($_ENV['PGDATABASE'] ?? getenv('PGDATABASE')),
        $_ENV['PGUSER'] ?? getenv('PGUSER'),
        $_ENV['PGPASSWORD'] ?? getenv('PGPASSWORD'),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// CSRF Token generation
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
$message_type = '';

// Handle login
if (isset($_POST['action']) && $_POST['action'] === 'login') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = 'Phiên làm việc không hợp lệ. Vui lòng thử lại!';
        $message_type = 'error';
    } else {
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        
        if (!empty($email) && !empty($password)) {
            try {
                $stmt = $pdo->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user && password_verify($password, $user['password'])) {
                    // Regenerate session ID to prevent session fixation
                    session_regenerate_id(true);
                    
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];
                    
                    // Redirect based on role
                    switch($user['role']) {
                        case 'admin':
                            header("Location: admin/dashboard.php");
                            break;
                        case 'staff':
                            header("Location: staff/dashboard.php");
                            break;
                        case 'customer':
                        default:
                            header("Location: index.php");
                            break;
                    }
                    exit();
                } else {
                    $message = 'Email hoặc mật khẩu không đúng!';
                    $message_type = 'error';
                }
            } catch (Exception $e) {
                $message = 'Có lỗi xảy ra. Vui lòng thử lại!';
                $message_type = 'error';
            }
        } else {
            $message = 'Vui lòng điền đầy đủ thông tin!';
            $message_type = 'error';
        }
    }
}

// Handle logout
if (isset($_POST['action']) && $_POST['action'] === 'logout') {
    // Validate CSRF token for logout
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = 'Phiên làm việc không hợp lệ. Vui lòng thử lại!';
        $message_type = 'error';
    } else {
        session_destroy();
        header("Location: login.php?logout=1");
        exit();
    }
}

// Role-based access control function
function checkRole($allowedRoles = []) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
    
    if (!empty($allowedRoles) && !in_array($_SESSION['user_role'], $allowedRoles)) {
        header("Location: unauthorized.php");
        exit();
    }
}

// Get user role display name
function getRoleDisplayName($role) {
    switch($role) {
        case 'admin':
            return 'Quản trị viên';
        case 'staff':
            return 'Nhân viên bán hàng';
        case 'customer':
            return 'Khách hàng';
        default:
            return 'Người dùng';
    }
}

// Check if user has permission for specific action
function hasPermission($action) {
    if (!isset($_SESSION['user_role'])) {
        return false;
    }
    
    $role = $_SESSION['user_role'];
    
    $permissions = [
        'admin' => ['*'], // Admin có tất cả quyền
        'staff' => [
            'manage_products', 'manage_promotions', 'view_orders', 'manage_orders',
            'manage_chatbox', 'view_customers', 'view_reports'
        ],
        'customer' => [
            'view_products', 'manage_cart', 'place_orders', 
            'view_profile', 'use_chatbox', 'write_reviews'
        ]
    ];
    
    return in_array('*', $permissions[$role] ?? []) || 
           in_array($action, $permissions[$role] ?? []);
}
?>