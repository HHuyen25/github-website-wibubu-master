<?php
require_once 'utils.php';
$db = db();

if ($_GET['action'] === 'list') {
    $q = $db->query("SELECT id,name,slug FROM categories ORDER BY name");
    exit(json_encode(['data'=>$q->fetchAll(PDO::FETCH_ASSOC)]));
}

exit(json_encode(['error'=>'Invalid action']));