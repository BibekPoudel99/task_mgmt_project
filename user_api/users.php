<?php
require_once __DIR__ . '/../library/ApiAuth.php';

ApiAuth::initApiResponse();
$auth = ApiAuth::requireUserAuth();
$db = ApiAuth::getDatabase();
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
    
    ApiAuth::successResponse(['users' => $users]);
} catch (Exception $e) {
    ApiAuth::errorResponse('Failed to fetch users');
}

