<?php
require_once 'utils.php';

$action = $_GET['action'] ?? '';
$db = db();

if ($action === 'register' && $_SERVER['REQUEST_METHOD']==='POST') {
    check_csrf();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!$name || !$email || !$password) exit(json_encode(['error'=>'Thiếu thông tin']));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) exit(json_encode(['error'=>'Email không hợp lệ']));
    if (strlen($password) < 6) exit(json_encode(['error'=>'Mật khẩu ít nhất 6 ký tự']));
    $exists = $db->prepare("SELECT id FROM users WHERE email=?"); $exists->execute([$email]);
    if ($exists->fetch()) exit(json_encode(['error'=>'Email đã tồn tại']));
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO users (name,email,password_hash) VALUES (?,?,?)");
    $stmt->execute([$name,$email,$hash]);
    $_SESSION['user_id'] = $db->lastInsertId();
    exit(json_encode(['success'=>true]));
}

if ($action === 'login' && $_SERVER['REQUEST_METHOD']==='POST') {
    check_csrf();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $user = $db->prepare("SELECT * FROM users WHERE email=?"); $user->execute([$email]);
    $u = $user->fetch(PDO::FETCH_ASSOC);
    if (!$u || !password_verify($password, $u['password_hash'])) exit(json_encode(['error'=>'Sai thông tin đăng nhập']));
    $_SESSION['user_id'] = $u['id'];
    exit(json_encode(['success'=>true,'name'=>$u['name']]));
}

if ($action === 'logout') {
    session_destroy();
    exit(json_encode(['success'=>true]));
}

if ($action === 'me') {
    if (!empty($_SESSION['user_id'])) {
        $user = $db->prepare("SELECT name,email FROM users WHERE id=?");
        $user->execute([$_SESSION['user_id']]);
        exit(json_encode(['user'=>$user->fetch(PDO::FETCH_ASSOC)]));
    }
    exit(json_encode(['user'=>null]));
}

exit(json_encode(['error'=>'Invalid action']));