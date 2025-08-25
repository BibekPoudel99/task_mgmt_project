<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_logged_in']) || empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../library/Database.php';
require_once __DIR__ . '/../library/TaskUtils.php';

$taskUtils = new TaskUtils();
$userId = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Update missed tasks first
        $updatedCount = $taskUtils->updateMissedTasks();
        
        // Get missed tasks for user
        $missedTasks = $taskUtils->getMissedTasksForUser($userId);
        
        echo json_encode([
            'success' => true, 
            'missed_tasks' => $missedTasks,
            'count' => count($missedTasks),
            'updated_count' => $updatedCount
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}