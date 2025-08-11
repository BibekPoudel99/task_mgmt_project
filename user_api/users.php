<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../library/Database.php';

$db = new Database();
$pdo = $db->getConnection();

try {
    $stmt = $pdo->prepare('SELECT id, username FROM users ORDER BY username ASC');
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'users' => $users]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch users']);
}

