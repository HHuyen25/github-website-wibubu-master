<?php
require_once '../auth.php';

// Check if user is staff or admin
checkRole(['staff', 'admin']);

// Set JSON response header
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$response = ['success' => false, 'message' => '', 'data' => null];

try {
    // Validate CSRF token for POST requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('Invalid CSRF token');
        }
    }

    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    switch ($action) {
        case 'get_conversations':
            handleGetConversations();
            break;
        case 'get_conversation':
            handleGetConversation();
            break;
        case 'send_message':
            handleSendMessage();
            break;
        case 'mark_as_read':
            handleMarkAsRead();
            break;
        case 'update_conversation_status':
            handleUpdateConversationStatus();
            break;
        case 'assign_staff':
            handleAssignStaff();
            break;
        case 'add_tags':
            handleAddTags();
            break;
        case 'get_quick_replies':
            handleGetQuickReplies();
            break;
        case 'search_conversations':
            handleSearchConversations();
            break;
        case 'get_customer_info':
            handleGetCustomerInfo();
            break;
        case 'add_internal_note':
            handleAddInternalNote();
            break;
        case 'upload_attachment':
            handleUploadAttachment();
            break;
        case 'get_staff_list':
            handleGetStaffList();
            break;
        case 'get_conversation_tags':
            handleGetConversationTags();
            break;
        case 'create_conversation':
            handleCreateConversation();
            break;
        case 'get_chat_stats':
            handleGetChatStats();
            break;
        case 'transfer_conversation':
            handleTransferConversation();
            break;
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log('Chat Handler Error: ' . $e->getMessage());
}

echo json_encode($response);
exit();

function handleGetConversations() {
    global $pdo, $response;
    
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = max(5, min(50, intval($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    
    $status = trim($_GET['status'] ?? '');
    $assigned_to_me = $_GET['assigned_to_me'] ?? false;
    $priority = trim($_GET['priority'] ?? '');
    
    $where_conditions = ['1=1'];
    $params = [];
    
    // Filter by status
    if (!empty($status) && in_array($status, ['open', 'in_progress', 'resolved', 'closed'])) {
        $where_conditions[] = 'cc.status = ?';
        $params[] = $status;
    }
    
    // Filter conversations assigned to current user
    if ($assigned_to_me) {
        $where_conditions[] = 'cc.assigned_staff_id = ?';
        $params[] = $_SESSION['user_id'];
    }
    
    // Filter by priority
    if (!empty($priority) && in_array($priority, ['low', 'normal', 'high', 'urgent'])) {
        $where_conditions[] = 'cc.priority = ?';
        $params[] = $priority;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Get total count
    $count_sql = "
        SELECT COUNT(*) 
        FROM chat_conversations cc
        WHERE $where_clause
    ";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total = $stmt->fetchColumn();
    
    // Get conversations with customer and staff info
    $sql = "
        SELECT 
            cc.id,
            cc.subject,
            cc.status,
            cc.priority,
            cc.last_message_at,
            cc.created_at,
            cc.tags,
            u_customer.name as customer_name,
            u_customer.email as customer_email,
            u_staff.name as assigned_staff_name,
            (SELECT content 
             FROM chat_messages cm 
             WHERE cm.conversation_id = cc.id 
             ORDER BY cm.created_at DESC 
             LIMIT 1) as last_message,
            (SELECT COUNT(*) 
             FROM chat_messages cm 
             WHERE cm.conversation_id = cc.id 
             AND cm.read_at IS NULL 
             AND cm.sender_id != ?) as unread_count
        FROM chat_conversations cc
        JOIN users u_customer ON cc.customer_id = u_customer.id
        LEFT JOIN users u_staff ON cc.assigned_staff_id = u_staff.id
        WHERE $where_clause
        ORDER BY 
            CASE cc.priority 
                WHEN 'urgent' THEN 1
                WHEN 'high' THEN 2
                WHEN 'normal' THEN 3
                WHEN 'low' THEN 4
            END,
            cc.last_message_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge([$_SESSION['user_id']], $params, [$limit, $offset]));
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format conversations
    foreach ($conversations as &$conversation) {
        $conversation['last_message_formatted'] = date('M d, H:i', strtotime($conversation['last_message_at']));
        $conversation['created_at_formatted'] = date('M d, Y H:i', strtotime($conversation['created_at']));
        $conversation['unread_count'] = intval($conversation['unread_count']);
        $conversation['tags'] = $conversation['tags'] ? json_decode($conversation['tags']) : [];
        
        // Truncate last message
        if (strlen($conversation['last_message']) > 100) {
            $conversation['last_message'] = substr($conversation['last_message'], 0, 100) . '...';
        }
    }
    
    $response['success'] = true;
    $response['data'] = [
        'conversations' => $conversations,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($total / $limit),
            'total_items' => $total,
            'per_page' => $limit
        ]
    ];
}

function handleGetConversation() {
    global $pdo, $response;
    
    $conversation_id = intval($_GET['conversation_id'] ?? 0);
    if (!$conversation_id) {
        throw new Exception('Conversation ID is required');
    }
    
    // Get conversation details
    $sql = "
        SELECT 
            cc.*,
            u_customer.name as customer_name,
            u_customer.email as customer_email,
            u_staff.name as assigned_staff_name,
            u_staff.email as assigned_staff_email
        FROM chat_conversations cc
        JOIN users u_customer ON cc.customer_id = u_customer.id
        LEFT JOIN users u_staff ON cc.assigned_staff_id = u_staff.id
        WHERE cc.id = ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$conversation_id]);
    $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$conversation) {
        throw new Exception('Conversation not found');
    }
    
    // Get messages
    $sql = "
        SELECT 
            cm.*,
            u.name as sender_name,
            u.email as sender_email,
            u.role as sender_role,
            (SELECT json_agg(json_build_object(
                'id', ca.id,
                'filename', ca.filename,
                'original_filename', ca.original_filename,
                'file_size', ca.file_size,
                'mime_type', ca.mime_type
            )) FROM chat_attachments ca WHERE ca.message_id = cm.id) as attachments
        FROM chat_messages cm
        JOIN users u ON cm.sender_id = u.id
        WHERE cm.conversation_id = ?
        ORDER BY cm.created_at ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$conversation_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format messages
    foreach ($messages as &$message) {
        $message['created_at_formatted'] = date('M d, Y H:i', strtotime($message['created_at']));
        $message['attachments'] = $message['attachments'] ? json_decode($message['attachments'], true) : [];
        $message['is_staff'] = in_array($message['sender_role'], ['staff', 'admin']);
    }
    
    $conversation['tags'] = $conversation['tags'] ? json_decode($conversation['tags']) : [];
    
    $response['success'] = true;
    $response['data'] = [
        'conversation' => $conversation,
        'messages' => $messages
    ];
}

function handleSendMessage() {
    global $pdo, $response;
    
    $conversation_id = intval($_POST['conversation_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');
    $is_internal = filter_var($_POST['is_internal'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $message_type = $_POST['message_type'] ?? 'text';
    
    if (!$conversation_id || empty($content)) {
        throw new Exception('Conversation ID and content are required');
    }
    
    // Validate message type
    $allowed_types = ['text', 'file', 'image', 'system', 'quick_reply'];
    if (!in_array($message_type, $allowed_types)) {
        $message_type = 'text';
    }
    
    $pdo->beginTransaction();
    
    try {
        // Insert message
        $sql = "
            INSERT INTO chat_messages (
                conversation_id, sender_id, message_type, content, is_internal, created_at
            ) VALUES (?, ?, ?, ?, ?, NOW())
            RETURNING id, created_at
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$conversation_id, $_SESSION['user_id'], $message_type, $content, $is_internal]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Update conversation last_message_at
        $sql = "
            UPDATE chat_conversations 
            SET last_message_at = NOW(), updated_at = NOW()
            WHERE id = ?
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$conversation_id]);
        
        // If this is a quick reply, increment usage count
        if ($message_type === 'quick_reply' && isset($_POST['template_id'])) {
            $sql = "
                UPDATE quick_reply_templates 
                SET usage_count = usage_count + 1, updated_at = NOW()
                WHERE id = ?
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([intval($_POST['template_id'])]);
        }
        
        $pdo->commit();
        
        $response['success'] = true;
        $response['message'] = 'Message sent successfully';
        $response['data'] = [
            'message_id' => $result['id'],
            'created_at' => $result['created_at']
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function handleMarkAsRead() {
    global $pdo, $response;
    
    $conversation_id = intval($_POST['conversation_id'] ?? 0);
    if (!$conversation_id) {
        throw new Exception('Conversation ID is required');
    }
    
    // Mark all messages in conversation as read (except staff messages)
    $sql = "
        UPDATE chat_messages 
        SET read_at = NOW()
        WHERE conversation_id = ? 
        AND read_at IS NULL 
        AND sender_id != ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$conversation_id, $_SESSION['user_id']]);
    
    $response['success'] = true;
    $response['message'] = 'Messages marked as read';
}

function handleUpdateConversationStatus() {
    global $pdo, $response;
    
    $conversation_id = intval($_POST['conversation_id'] ?? 0);
    $status = trim($_POST['status'] ?? '');
    
    if (!$conversation_id || empty($status)) {
        throw new Exception('Conversation ID and status are required');
    }
    
    $allowed_statuses = ['open', 'in_progress', 'resolved', 'closed'];
    if (!in_array($status, $allowed_statuses)) {
        throw new Exception('Invalid status');
    }
    
    $update_fields = ['status = ?', 'updated_at = NOW()'];
    $params = [$status];
    
    // Set resolved_at or closed_at timestamps
    if ($status === 'resolved') {
        $update_fields[] = 'resolved_at = NOW()';
    } elseif ($status === 'closed') {
        $update_fields[] = 'closed_at = NOW()';
    }
    
    $sql = "UPDATE chat_conversations SET " . implode(', ', $update_fields) . " WHERE id = ?";
    $params[] = $conversation_id;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    // Add system message about status change
    $system_message = "Conversation status changed to: " . ucfirst($status);
    $sql = "
        INSERT INTO chat_messages (conversation_id, sender_id, message_type, content, is_internal)
        VALUES (?, ?, 'system', ?, true)
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$conversation_id, $_SESSION['user_id'], $system_message]);
    
    $response['success'] = true;
    $response['message'] = 'Status updated successfully';
}

function handleAssignStaff() {
    global $pdo, $response;
    
    $conversation_id = intval($_POST['conversation_id'] ?? 0);
    $staff_id = intval($_POST['staff_id'] ?? 0);
    
    if (!$conversation_id) {
        throw new Exception('Conversation ID is required');
    }
    
    // Verify staff member exists and has proper role
    if ($staff_id > 0) {
        $sql = "SELECT name FROM users WHERE id = ? AND role IN ('staff', 'admin')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$staff_id]);
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$staff) {
            throw new Exception('Invalid staff member');
        }
        $staff_name = $staff['name'];
    } else {
        $staff_id = null;
        $staff_name = 'unassigned';
    }
    
    // Update conversation
    $sql = "UPDATE chat_conversations SET assigned_staff_id = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$staff_id, $conversation_id]);
    
    // Add system message
    $system_message = $staff_id ? "Conversation assigned to: " . $staff_name : "Conversation unassigned";
    $sql = "
        INSERT INTO chat_messages (conversation_id, sender_id, message_type, content, is_internal)
        VALUES (?, ?, 'system', ?, true)
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$conversation_id, $_SESSION['user_id'], $system_message]);
    
    $response['success'] = true;
    $response['message'] = 'Staff assignment updated';
}

function handleAddTags() {
    global $pdo, $response;
    
    $conversation_id = intval($_POST['conversation_id'] ?? 0);
    $tags = $_POST['tags'] ?? [];
    
    if (!$conversation_id || !is_array($tags)) {
        throw new Exception('Conversation ID and tags are required');
    }
    
    // Validate tags exist
    if (!empty($tags)) {
        $placeholders = str_repeat('?,', count($tags) - 1) . '?';
        $sql = "SELECT name FROM conversation_tags WHERE name IN ($placeholders)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($tags);
        $valid_tags = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Only use valid tags
        $tags = array_intersect($tags, $valid_tags);
    }
    
    $tags_json = json_encode($tags);
    
    $sql = "UPDATE chat_conversations SET tags = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$tags_json, $conversation_id]);
    
    $response['success'] = true;
    $response['message'] = 'Tags updated successfully';
    $response['data'] = ['tags' => $tags];
}

function handleGetQuickReplies() {
    global $pdo, $response;
    
    $category = trim($_GET['category'] ?? '');
    
    $where_conditions = ['is_active = true'];
    $params = [];
    
    if (!empty($category)) {
        $where_conditions[] = 'category = ?';
        $params[] = $category;
    }
    
    // Get global templates and user's personal templates
    $where_conditions[] = '(staff_id IS NULL OR staff_id = ?)';
    $params[] = $_SESSION['user_id'];
    
    $where_clause = implode(' AND ', $where_conditions);
    
    $sql = "
        SELECT id, title, content, category, staff_id, usage_count
        FROM quick_reply_templates
        WHERE $where_clause
        ORDER BY usage_count DESC, title ASC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group by category
    $grouped = [];
    foreach ($templates as $template) {
        $cat = $template['category'] ?: 'general';
        if (!isset($grouped[$cat])) {
            $grouped[$cat] = [];
        }
        $grouped[$cat][] = $template;
    }
    
    $response['success'] = true;
    $response['data'] = $grouped;
}

function handleSearchConversations() {
    global $pdo, $response;
    
    $query = trim($_GET['query'] ?? '');
    $limit = min(20, intval($_GET['limit'] ?? 10));
    
    if (empty($query)) {
        throw new Exception('Search query is required');
    }
    
    $sql = "
        SELECT DISTINCT
            cc.id,
            cc.subject,
            cc.status,
            cc.priority,
            cc.last_message_at,
            u_customer.name as customer_name,
            u_customer.email as customer_email,
            ts_headline('english', cm.content, to_tsquery('english', ?)) as highlighted_content
        FROM chat_conversations cc
        JOIN users u_customer ON cc.customer_id = u_customer.id
        JOIN chat_messages cm ON cm.conversation_id = cc.id
        WHERE to_tsvector('english', cm.content) @@ to_tsquery('english', ?)
           OR LOWER(cc.subject) LIKE LOWER(?)
           OR LOWER(u_customer.name) LIKE LOWER(?)
           OR LOWER(u_customer.email) LIKE LOWER(?)
        ORDER BY cc.last_message_at DESC
        LIMIT ?
    ";
    
    $search_query = implode(' | ', explode(' ', $query)); // OR search for multiple words
    $like_query = "%$query%";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$search_query, $search_query, $like_query, $like_query, $like_query, $limit]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($results as &$result) {
        $result['last_message_formatted'] = date('M d, H:i', strtotime($result['last_message_at']));
    }
    
    $response['success'] = true;
    $response['data'] = $results;
}

function handleGetCustomerInfo() {
    global $pdo, $response;
    
    $customer_id = intval($_GET['customer_id'] ?? 0);
    if (!$customer_id) {
        throw new Exception('Customer ID is required');
    }
    
    // Get customer basic info
    $sql = "SELECT id, name, email, created_at FROM users WHERE id = ? AND role = 'customer'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$customer_id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$customer) {
        throw new Exception('Customer not found');
    }
    
    // Get order statistics
    $sql = "
        SELECT 
            COUNT(*) as total_orders,
            SUM(total_amount) as total_spent,
            AVG(total_amount) as avg_order_value,
            MAX(created_at) as last_order_date
        FROM orders 
        WHERE user_id = ? AND status != 'cancelled'
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$customer_id]);
    $order_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get recent orders
    $sql = "
        SELECT id, total_amount, status, created_at
        FROM orders 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$customer_id]);
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get customer notes
    $sql = "
        SELECT content, created_at, staff_name
        FROM customer_notes 
        WHERE customer_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$customer_id]);
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get conversation history
    $sql = "
        SELECT id, subject, status, created_at, resolved_at
        FROM chat_conversations 
        WHERE customer_id = ? 
        ORDER BY created_at DESC 
        LIMIT 10
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$customer_id]);
    $conversation_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format data
    $customer['member_since'] = date('M Y', strtotime($customer['created_at']));
    
    foreach ($recent_orders as &$order) {
        $order['created_at_formatted'] = date('M d, Y', strtotime($order['created_at']));
        $order['total_amount'] = floatval($order['total_amount']);
    }
    
    foreach ($notes as &$note) {
        $note['created_at_formatted'] = date('M d, Y H:i', strtotime($note['created_at']));
    }
    
    foreach ($conversation_history as &$conv) {
        $conv['created_at_formatted'] = date('M d, Y', strtotime($conv['created_at']));
        $conv['resolved_at_formatted'] = $conv['resolved_at'] ? date('M d, Y', strtotime($conv['resolved_at'])) : null;
    }
    
    $response['success'] = true;
    $response['data'] = [
        'customer' => $customer,
        'order_stats' => [
            'total_orders' => intval($order_stats['total_orders']),
            'total_spent' => floatval($order_stats['total_spent'] ?: 0),
            'avg_order_value' => floatval($order_stats['avg_order_value'] ?: 0),
            'last_order_date' => $order_stats['last_order_date'] ? date('M d, Y', strtotime($order_stats['last_order_date'])) : 'Never'
        ],
        'recent_orders' => $recent_orders,
        'notes' => $notes,
        'conversation_history' => $conversation_history
    ];
}

function handleAddInternalNote() {
    global $pdo, $response;
    
    $conversation_id = intval($_POST['conversation_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');
    
    if (!$conversation_id || empty($content)) {
        throw new Exception('Conversation ID and content are required');
    }
    
    // Add internal note as a message
    $sql = "
        INSERT INTO chat_messages (conversation_id, sender_id, message_type, content, is_internal)
        VALUES (?, ?, 'system', ?, true)
        RETURNING id, created_at
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$conversation_id, $_SESSION['user_id'], "Internal Note: " . $content]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $response['success'] = true;
    $response['message'] = 'Internal note added';
    $response['data'] = $result;
}

function handleUploadAttachment() {
    global $pdo, $response;
    
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload error');
    }
    
    $conversation_id = intval($_POST['conversation_id'] ?? 0);
    if (!$conversation_id) {
        throw new Exception('Conversation ID is required');
    }
    
    $file = $_FILES['file'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'text/plain'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if ($file['size'] > $max_size) {
        throw new Exception('File too large. Maximum size is 5MB');
    }
    
    if (!in_array($file['type'], $allowed_types)) {
        throw new Exception('Invalid file type');
    }
    
    // Create uploads directory if it doesn't exist
    $upload_dir = '../uploads/chat/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $file_path = $upload_dir . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        throw new Exception('Failed to save file');
    }
    
    $pdo->beginTransaction();
    
    try {
        // Create message for the attachment
        $message_type = strpos($file['type'], 'image/') === 0 ? 'image' : 'file';
        $content = "Shared " . ($message_type === 'image' ? 'an image' : 'a file') . ": " . $file['name'];
        
        $sql = "
            INSERT INTO chat_messages (conversation_id, sender_id, message_type, content)
            VALUES (?, ?, ?, ?)
            RETURNING id
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$conversation_id, $_SESSION['user_id'], $message_type, $content]);
        $message_id = $stmt->fetchColumn();
        
        // Add attachment record
        $sql = "
            INSERT INTO chat_attachments (
                message_id, filename, original_filename, file_path, file_size, mime_type
            ) VALUES (?, ?, ?, ?, ?, ?)
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $message_id,
            $filename,
            $file['name'],
            $file_path,
            $file['size'],
            $file['type']
        ]);
        
        // Update conversation last message time
        $sql = "UPDATE chat_conversations SET last_message_at = NOW() WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$conversation_id]);
        
        $pdo->commit();
        
        $response['success'] = true;
        $response['message'] = 'File uploaded successfully';
        $response['data'] = [
            'message_id' => $message_id,
            'filename' => $filename,
            'original_filename' => $file['name']
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        unlink($file_path); // Clean up uploaded file
        throw $e;
    }
}

function handleGetStaffList() {
    global $pdo, $response;
    
    $sql = "
        SELECT id, name, email
        FROM users 
        WHERE role IN ('staff', 'admin') 
        ORDER BY name ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response['success'] = true;
    $response['data'] = $staff;
}

function handleGetConversationTags() {
    global $pdo, $response;
    
    $sql = "
        SELECT id, name, color, description
        FROM conversation_tags
        ORDER BY name ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response['success'] = true;
    $response['data'] = $tags;
}

function handleCreateConversation() {
    global $pdo, $response;
    
    $customer_id = intval($_POST['customer_id'] ?? 0);
    $subject = trim($_POST['subject'] ?? 'Support Request');
    $priority = trim($_POST['priority'] ?? 'normal');
    $initial_message = trim($_POST['message'] ?? '');
    
    if (!$customer_id) {
        throw new Exception('Customer ID is required');
    }
    
    // Verify customer exists
    $sql = "SELECT id FROM users WHERE id = ? AND role = 'customer'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$customer_id]);
    if (!$stmt->fetchColumn()) {
        throw new Exception('Customer not found');
    }
    
    $pdo->beginTransaction();
    
    try {
        // Create conversation
        $sql = "
            INSERT INTO chat_conversations (
                customer_id, assigned_staff_id, subject, priority, created_at, last_message_at
            ) VALUES (?, ?, ?, ?, NOW(), NOW())
            RETURNING id
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$customer_id, $_SESSION['user_id'], $subject, $priority]);
        $conversation_id = $stmt->fetchColumn();
        
        // Add initial message if provided
        if (!empty($initial_message)) {
            $sql = "
                INSERT INTO chat_messages (conversation_id, sender_id, content)
                VALUES (?, ?, ?)
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$conversation_id, $_SESSION['user_id'], $initial_message]);
        }
        
        $pdo->commit();
        
        $response['success'] = true;
        $response['message'] = 'Conversation created successfully';
        $response['data'] = ['conversation_id' => $conversation_id];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function handleGetChatStats() {
    global $pdo, $response;
    
    // Get various statistics
    $stats = [];
    
    // Active conversations
    $sql = "SELECT COUNT(*) FROM chat_conversations WHERE status IN ('open', 'in_progress')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $stats['active_conversations'] = $stmt->fetchColumn();
    
    // Unassigned conversations
    $sql = "SELECT COUNT(*) FROM chat_conversations WHERE assigned_staff_id IS NULL AND status IN ('open', 'in_progress')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $stats['unassigned_conversations'] = $stmt->fetchColumn();
    
    // Today's messages
    $sql = "SELECT COUNT(*) FROM chat_messages WHERE DATE(created_at) = CURRENT_DATE";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $stats['messages_today'] = $stmt->fetchColumn();
    
    // Average response time (in minutes)
    $sql = "
        SELECT AVG(
            EXTRACT(EPOCH FROM (
                SELECT MIN(cm2.created_at) 
                FROM chat_messages cm2 
                WHERE cm2.conversation_id = cm1.conversation_id 
                AND cm2.created_at > cm1.created_at
                AND cm2.sender_id IN (SELECT id FROM users WHERE role IN ('staff', 'admin'))
            ) - cm1.created_at) / 60
        )
        FROM chat_messages cm1
        WHERE cm1.sender_id IN (SELECT id FROM users WHERE role = 'customer')
        AND DATE(cm1.created_at) = CURRENT_DATE
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $avg_response_time = $stmt->fetchColumn();
    $stats['avg_response_time'] = round($avg_response_time ?: 0, 1);
    
    $response['success'] = true;
    $response['data'] = $stats;
}

function handleTransferConversation() {
    global $pdo, $response;
    
    $conversation_id = intval($_POST['conversation_id'] ?? 0);
    $new_staff_id = intval($_POST['new_staff_id'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');
    
    if (!$conversation_id || !$new_staff_id) {
        throw new Exception('Conversation ID and new staff ID are required');
    }
    
    // Verify new staff member
    $sql = "SELECT name FROM users WHERE id = ? AND role IN ('staff', 'admin')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$new_staff_id]);
    $new_staff = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$new_staff) {
        throw new Exception('Invalid staff member');
    }
    
    // Update conversation
    $sql = "UPDATE chat_conversations SET assigned_staff_id = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$new_staff_id, $conversation_id]);
    
    // Add transfer message
    $message = "Conversation transferred to: " . $new_staff['name'];
    if ($reason) {
        $message .= " - Reason: " . $reason;
    }
    
    $sql = "
        INSERT INTO chat_messages (conversation_id, sender_id, message_type, content, is_internal)
        VALUES (?, ?, 'system', ?, true)
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$conversation_id, $_SESSION['user_id'], $message]);
    
    $response['success'] = true;
    $response['message'] = 'Conversation transferred successfully';
}
?>