<?php
require_once __DIR__ . '/../library/ApiAuth.php';

ApiAuth::initApiResponse();
$auth = ApiAuth::requireUserAuth();
$userId = $auth['user_id'];

require_once __DIR__ . '/../library/TaskUtils.php';

$taskUtils = new TaskUtils();

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