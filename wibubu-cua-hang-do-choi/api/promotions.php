<?php
require_once 'utils.php';
$db = db();

if ($_GET['action'] === 'list') {
    $now = date('Y-m-d H:i:s');
    $q = $db->prepare("SELECT * FROM promotions WHERE active=1 AND starts_at<=? AND ends_at>=?");
    $q->execute([$now, $now]);
    exit(json_encode(['data'=>$q->fetchAll(PDO::FETCH_ASSOC)]));
}

if ($_GET['action'] === 'validate-code' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['code']??''));
    $q = $db->prepare("SELECT * FROM promotions WHERE code=? AND active=1 AND starts_at<=? AND ends_at>=?");
    $q->execute([$code, date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]);
    $promo = $q->fetch(PDO::FETCH_ASSOC);
    if ($promo) exit(json_encode(['valid'=>true,'promo'=>$promo]));
    exit(json_encode(['valid'=>false]));
}

exit(json_encode(['error'=>'Invalid action']));