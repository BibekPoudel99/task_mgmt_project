<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_logged_in']) || empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../library/Database.php';

$db = new Database();
$pdo = $db->getConnection();
$userId = (int) $_SESSION['user_id'];

try {
    // First, update missed tasks
    require_once __DIR__ . '/../library/TaskUtils.php';
    $taskUtils = new TaskUtils();
    $taskUtils->updateMissedTasks();

    // Get user basic information
    $stmt = $pdo->prepare('
        SELECT id, username, created_at,
               COALESCE(is_active, 1) as is_active
        FROM users 
        WHERE id = ?
    ');
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('User not found');
    }

    // Get task statistics for this user
    $stmt = $pdo->prepare('
        SELECT 
            COUNT(*) as total_tasks,
            SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END) as completed_tasks,
            SUM(CASE WHEN completed = 0 AND is_missed = 0 THEN 1 ELSE 0 END) as active_tasks,
            SUM(CASE WHEN is_missed = 1 THEN 1 ELSE 0 END) as missed_tasks,
            SUM(CASE WHEN completed = 0 AND due_date = CURDATE() THEN 1 ELSE 0 END) as due_today
        FROM tasks t
        LEFT JOIN projects p ON p.id = t.project_id
        LEFT JOIN project_members pm ON pm.project_id = p.id
        WHERE t.owner_id = ? 
           OR t.assignee_id = ?
           OR p.owner_id = ?
           OR pm.user_id = ?
    ');
    $stmt->execute([$userId, $userId, $userId, $userId]);
    $taskStats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get project statistics for this user
    $stmt = $pdo->prepare('
        SELECT 
            COUNT(DISTINCT p.id) as total_projects,
            COUNT(DISTINCT CASE WHEN p.owner_id = ? THEN p.id END) as owned_projects,
            COUNT(DISTINCT CASE WHEN pm.user_id = ? THEN p.id END) as member_projects
        FROM projects p
        LEFT JOIN project_members pm ON pm.project_id = p.id
        WHERE p.owner_id = ? OR pm.user_id = ?
    ');
    $stmt->execute([$userId, $userId, $userId, $userId]);
    $projectStats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get recent activity (last 10 tasks created or completed)
    $stmt = $pdo->prepare('
        SELECT 
            t.id,
            t.title,
            t.completed,
            t.created_at,
            t.updated_at,
            p.name as project_name,
            CASE 
                WHEN t.completed = 1 AND t.updated_at > t.created_at THEN "completed"
                ELSE "created"
            END as activity_type
        FROM tasks t
        LEFT JOIN projects p ON p.id = t.project_id
        LEFT JOIN project_members pm ON pm.project_id = p.id
        WHERE t.owner_id = ? 
           OR t.assignee_id = ?
           OR p.owner_id = ?
           OR pm.user_id = ?
        ORDER BY 
            CASE 
                WHEN t.completed = 1 AND t.updated_at > t.created_at THEN t.updated_at
                ELSE t.created_at
            END DESC
        LIMIT 10
    ');
    $stmt->execute([$userId, $userId, $userId, $userId]);
    $recentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the response
    $response = [
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['username'] . '@taskflow.local', // Generate email since it's not stored
            'member_since' => isset($user['created_at']) ? date('M Y', strtotime($user['created_at'])) : date('M Y'),
            'status' => $user['is_active'] ? 'Active' : 'Inactive'
        ],
        'stats' => [
            'total_tasks' => (int) ($taskStats['total_tasks'] ?? 0),
            'completed_tasks' => (int) ($taskStats['completed_tasks'] ?? 0),
            'active_tasks' => (int) ($taskStats['active_tasks'] ?? 0),
            'missed_tasks' => (int) ($taskStats['missed_tasks'] ?? 0),
            'due_today' => (int) ($taskStats['due_today'] ?? 0),
            'total_projects' => (int) ($projectStats['total_projects'] ?? 0),
            'owned_projects' => (int) ($projectStats['owned_projects'] ?? 0),
            'member_projects' => (int) ($projectStats['member_projects'] ?? 0)
        ],
        'recent_activity' => array_map(function($activity) {
            return [
                'id' => $activity['id'],
                'title' => $activity['title'],
                'project_name' => $activity['project_name'],
                'activity_type' => $activity['activity_type'],
                'date' => date('M j, g:i A', strtotime($activity['activity_type'] === 'completed' ? $activity['updated_at'] : $activity['created_at']))
            ];
        }, $recentActivity)
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to fetch profile data',
        'error' => $e->getMessage()
    ]);
}
?>
