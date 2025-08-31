<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../library/Database.php';

$db = new Database();
$pdo = $db->getConnection();

try {
    $stmt = $pdo->prepare('SELECT id, username, COALESCE(is_active, 1) as is_active FROM users ORDER BY username ASC');
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert is_active to boolean for consistency
    $users = array_map(function($user) {
        return [
            'id' => (int)$user['id'],
            'username' => $user['username'],
            'is_active' => (bool)$user['is_active']
        ];
    }, $users);
    
    echo json_encode(['success' => true, 'users' => $users]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch users']);
}

