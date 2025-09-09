<?php
require_once 'utils.php';
$db = db();

function get_cart_id() {
    if (!empty($_SESSION['user_id'])) {
        $q = db()->prepare("SELECT id FROM carts WHERE user_id=? ORDER BY id DESC LIMIT 1");
        $q->execute([$_SESSION['user_id']]);
        $cart = $q->fetch();
        if ($cart) return $cart['id'];
        db()->prepare("INSERT INTO carts (user_id) VALUES (?)")->execute([$_SESSION['user_id']]);
        return db()->lastInsertId();
    } else {
        if (empty($_SESSION['cart_session'])) $_SESSION['cart_session'] = bin2hex(random_bytes(16));
        $sid = $_SESSION['cart_session'];
        $q = db()->prepare("SELECT id FROM carts WHERE session_id=? ORDER BY id DESC LIMIT 1");
        $q->execute([$sid]);
        $cart = $q->fetch();
        if ($cart) return $cart['id'];
        db()->prepare("INSERT INTO carts (session_id) VALUES (?)")->execute([$sid]);
        return db()->lastInsertId();
    }
}

$action = $_GET['action'] ?? '';

if ($action === 'get') {
    $cid = get_cart_id();
    $q = $db->prepare("SELECT ci.*,p.name,p.price,p.image FROM cart_items ci JOIN products p ON p.id=ci.product_id WHERE cart_id=?");
    $q->execute([$cid]);
    $items = $q->fetchAll(PDO::FETCH_ASSOC);
    exit(json_encode(['items'=>$items]));
}

if ($action === 'add' && $_SERVER['REQUEST_METHOD']==='POST') {
    check_csrf();
    $cid = get_cart_id();
    $pid = intval($_POST['product_id']);
    $qty = max(1, intval($_POST['quantity']));
    $exist = $db->prepare("SELECT id FROM cart_items WHERE cart_id=? AND product_id=?");
    $exist->execute([$cid,$pid]);
    if ($exist->fetch()) {
        $db->prepare("UPDATE cart_items SET quantity=quantity+? WHERE cart_id=? AND product_id=?")
           ->execute([$qty,$cid,$pid]);
    } else {
        $db->prepare("INSERT INTO cart_items (cart_id,product_id,quantity) VALUES (?,?,?)")
           ->execute([$cid,$pid,$qty]);
    }
    exit(json_encode(['success'=>true]));
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD']==='POST') {
    check_csrf();
    $cid = get_cart_id();
    $pid = intval($_POST['product_id']);
    $qty = max(1, intval($_POST['quantity']));
    $db->prepare("UPDATE cart_items SET quantity=? WHERE cart_id=? AND product_id=?")
       ->execute([$qty,$cid,$pid]);
    exit(json_encode(['success'=>true]));
}

if ($action === 'remove' && $_SERVER['REQUEST_METHOD']==='POST') {
    check_csrf();
    $cid = get_cart_id();
    $pid = intval($_POST['product_id']);
    $db->prepare("DELETE FROM cart_items WHERE cart_id=? AND product_id=?")
       ->execute([$cid,$pid]);
    exit(json_encode(['success'=>true]));
}

exit(json_encode(['error'=>'Invalid action']));