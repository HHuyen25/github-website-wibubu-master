<?php
// Kết nối DB, CSRF, trả JSON header, session
function db() {
    static $pdo;
    if (!$pdo) {
        $pdo = new PDO('mysql:host=localhost;dbname=wibubu;charset=utf8mb4', 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
    }
    return $pdo;
}
session_start();
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf_token'];
}
function check_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['csrf_token'] ?? '') !== ($_SESSION['csrf_token'] ?? '')) {
        http_response_code(403); echo json_encode(['error'=>'CSRF token sai!']); exit;
    }
}
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));