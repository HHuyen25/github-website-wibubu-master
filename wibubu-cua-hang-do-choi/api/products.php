<?php
require_once 'utils.php';
$db = db();

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    $where = [];
    $params = [];
    if (!empty($_GET['cat'])) {
        $ids = explode(',', $_GET['cat']);
        $where[] = 'category_id IN (' . implode(',', array_fill(0,count($ids),'?')) . ')';
        $params = array_merge($params, $ids);
    }
    if (!empty($_GET['q'])) {
        $where[] = '(name LIKE ? OR description LIKE ?)';
        $params[] = '%'.$_GET['q'].'%';
        $params[] = '%'.$_GET['q'].'%';
    }
    $order = 'created_at DESC';
    if (!empty($_GET['sort'])) {
        if ($_GET['sort'] === 'price_asc') $order = 'price ASC';
        if ($_GET['sort'] === 'price_desc') $order = 'price DESC';
    }
    $sql = "SELECT * FROM products".($where?' WHERE '.implode(' AND ', $where):'')." ORDER BY $order";
    $stm = $db->prepare($sql); $stm->execute($params);
    exit(json_encode(['data'=>$stm->fetchAll(PDO::FETCH_ASSOC)]));
}

if ($action === 'detail' && !empty($_GET['id'])) {
    $stm = $db->prepare("SELECT * FROM products WHERE id=?");
    $stm->execute([$_GET['id']]);
    exit(json_encode(['data'=>$stm->fetch(PDO::FETCH_ASSOC)]));
}

exit(json_encode(['error'=>'Invalid action']));