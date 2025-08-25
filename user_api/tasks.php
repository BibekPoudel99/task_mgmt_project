<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'C:/xampp/htdocs/task_mgmt/php_errors.log');
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_logged_in']) || empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../library/Database.php';
require_once __DIR__ . '/../library/Session.php';
require_once __DIR__ . '/../library/Token.php';

$db = new Database();
$pdo = $db->getConnection();
$userId = (int) $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Update missed tasks first
    require_once __DIR__ . '/../library/TaskUtils.php';
    $taskUtils = new TaskUtils();
    $taskUtils->updateMissedTasks();
    
    // List tasks owned by user or assigned to user
    try {
        $sql = "
            SELECT t.id, t.title, t.project_id, t.due_date, t.completed, t.owner_id, t.assignee_id, t.is_missed,
                   au.username AS assignee
            FROM tasks t
            LEFT JOIN users au ON au.id = t.assignee_id
            WHERE t.owner_id = :uid OR t.assignee_id = :uid
            ORDER BY t.id DESC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        $tasks = array_map(function($t) {
            return [
                'id' => (int)$t['id'],
                'title' => $t['title'],
                'project_id' => $t['project_id'] ? (int)$t['project_id'] : null,
                'due_date' => $t['due_date'],
                'completed' => (bool)$t['completed'],
                'owner_id' => (int)$t['owner_id'],
                'assignee_id' => $t['assignee_id'] ? (int)$t['assignee_id'] : null,
                'assignee' => $t['assignee'] ?? null,
                'is_missed' => (bool)$t['is_missed'], // Add this line
            ];
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
        echo json_encode(['success' => true, 'tasks' => $tasks]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to fetch tasks']);
    }
    exit;
}

if ($method === 'POST') {
    // CSRF validate (single-use token)
    $csrf = $_POST['csrf_token'] ?? '';
    if (!Token::check($csrf)) {
        $newToken = Session::put('csrf_token', md5(uniqid()));
        http_response_code(419);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token', 'csrf_token' => $newToken]);
        exit;
    }
    // Next token for following request
    $nextToken = Session::put('csrf_token', md5(uniqid()));

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $projectId = (int)($_POST['project_id'] ?? 0);
        $dueDate = $_POST['due_date'] ?? null;

        if ($title === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Title is required']);
            exit;
        }

        // If project specified, ensure user is owner or member
        if ($projectId) {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM projects p LEFT JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = ? WHERE p.id = ? AND (p.owner_id = ? OR pm.user_id = ?)');
            $stmt->execute([$userId, $projectId, $userId, $userId]);
            $allowed = (int)$stmt->fetchColumn() > 0;
            if (!$allowed) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Not a member of this project']);
                exit;
            }
        }

        try {
            $stmt = $pdo->prepare('INSERT INTO tasks (title, owner_id, project_id, due_date, completed, created_at) VALUES (?, ?, ?, ?, 0, NOW())');
            $stmt->execute([$title, $userId, $projectId ?: null, $dueDate ?: null]);
            echo json_encode(['success' => true, 'id' => (int)$pdo->lastInsertId(), 'csrf_token' => $nextToken]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to create task', 'csrf_token' => $nextToken]);
        }
        exit;
    }

    if ($action === 'assign') {
        $taskId = (int)($_POST['task_id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        if (!$taskId || $username === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Invalid input']);
            exit;
        }
        try {
            // Load task
            $stmt = $pdo->prepare('SELECT id, owner_id, project_id FROM tasks WHERE id = ?');
            $stmt->execute([$taskId]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$task) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Task not found']);
                exit;
            }
            $projectId = (int)($task['project_id'] ?? 0);
            // Only project owner may assign tasks within the project
            $allowed = false;
            if ($projectId) {
                $ownStmt = $pdo->prepare('SELECT owner_id FROM projects WHERE id = ?');
                $ownStmt->execute([$projectId]);
                $projOwnerId = (int)($ownStmt->fetchColumn() ?: 0);
                if ($projOwnerId === $userId) $allowed = true;
            }
            if (!$allowed) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Not allowed']);
                exit;
            }
            if (!$projectId) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Task is not part of a project']);
                exit;
            }
            // Find user and ensure member of project
            $uStmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
            $uStmt->execute([$username]);
            $assigneeId = (int)($uStmt->fetchColumn() ?: 0);
            if (!$assigneeId) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'User not found']);
                exit;
            }
            $mStmt = $pdo->prepare('SELECT COUNT(*) FROM project_members WHERE project_id = ? AND user_id = ?');
            $mStmt->execute([$projectId, $assigneeId]);
            if ((int)$mStmt->fetchColumn() === 0) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'User is not a member of this project']);
                exit;
            }
            // Update assignee
            $upd = $pdo->prepare('UPDATE tasks SET assignee_id = ? WHERE id = ?');
            $upd->execute([$assigneeId, $taskId]);
            echo json_encode(['success' => true, 'csrf_token' => $nextToken]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to assign task', 'csrf_token' => $nextToken]);
        }
        exit;
    }

    if ($action === 'update_due') {
        $taskId = (int)($_POST['task_id'] ?? 0);
        $dueDate = trim($_POST['due_date'] ?? '');
        if (!$taskId) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Invalid task']);
            exit;
        }
        try {
            // Only task owner, assignee, or project owner may update due date
            $stmt = $pdo->prepare('SELECT t.owner_id, t.assignee_id, t.project_id, p.owner_id AS project_owner FROM tasks t LEFT JOIN projects p ON p.id = t.project_id WHERE t.id = ?');
            $stmt->execute([$taskId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Task not found']);
                exit;
            }
            $allowed = ($row['owner_id'] == $userId) || ($row['assignee_id'] == $userId) || ($row['project_owner'] == $userId);
            if (!$allowed) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Not allowed']);
                exit;
            }
            $upd = $pdo->prepare('UPDATE tasks SET due_date = ? WHERE id = ?');
            $upd->execute([$dueDate ?: null, $taskId]);
            echo json_encode(['success' => true, 'csrf_token' => $nextToken]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to update due date', 'csrf_token' => $nextToken]);
        }
        exit;
    }

    if ($action === 'toggle') {
        $taskId = (int)($_POST['task_id'] ?? 0);
        if (!$taskId) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Invalid task']);
            exit;
        }
        try {
            // Only owner or assignee can toggle
            $stmt = $pdo->prepare('SELECT completed FROM tasks WHERE id = ? AND (owner_id = ? OR assignee_id = ?)');
            $stmt->execute([$taskId, $userId, $userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Not allowed']);
                exit;
            }
            $new = ((int)$row['completed']) ? 0 : 1;
            $upd = $pdo->prepare('UPDATE tasks SET completed = ? WHERE id = ?');
            $upd->execute([$new, $taskId]);
            echo json_encode(['success' => true, 'csrf_token' => $nextToken]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to update task', 'csrf_token' => $nextToken]);
        }
        exit;
    }

    if ($action === 'update_title') {
        $taskId = (int)($_POST['task_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        if (!$taskId || $title === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Invalid input']);
            exit;
        }
        try {
            // Only owner or assignee can rename
            $stmt = $pdo->prepare('SELECT owner_id, assignee_id FROM tasks WHERE id = ?');
            $stmt->execute([$taskId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row || ($row['owner_id'] != $userId && $row['assignee_id'] != $userId)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Not allowed']);
                exit;
            }
            $upd = $pdo->prepare('UPDATE tasks SET title = ? WHERE id = ?');
            $upd->execute([$title, $taskId]);
            echo json_encode(['success' => true, 'csrf_token' => $nextToken]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to update task title', 'csrf_token' => $nextToken]);
        }
        exit;
    }

    if ($action === 'unassign') {
        $taskId = (int)($_POST['task_id'] ?? 0);
        if (!$taskId) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Invalid input']);
            exit;
        }
        try {
            // Only owner or project owner can unassign
            $stmt = $pdo->prepare('SELECT owner_id, project_id FROM tasks WHERE id = ?');
            $stmt->execute([$taskId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Task not found']);
                exit;
            }
            $allowed = ($row['owner_id'] == $userId);
            if (!$allowed && $row['project_id']) {
                $ownStmt = $pdo->prepare('SELECT owner_id FROM projects WHERE id = ?');
                $ownStmt->execute([$row['project_id']]);
                $projOwnerId = (int)($ownStmt->fetchColumn() ?: 0);
                $allowed = $projOwnerId === $userId;
            }
            if (!$allowed) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Not allowed']);
                exit;
            }
            $upd = $pdo->prepare('UPDATE tasks SET assignee_id = NULL WHERE id = ?');
            $upd->execute([$taskId]);
            echo json_encode(['success' => true, 'csrf_token' => $nextToken]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to unassign task', 'csrf_token' => $nextToken]);
        }
        exit;
    }

    if ($action === 'delete') {
        $taskId = (int)($_POST['task_id'] ?? 0);
        if (!$taskId) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Invalid input']);
            exit;
        }
        try {
            // Only owner can delete
            $stmt = $pdo->prepare('DELETE FROM tasks WHERE id = ? AND owner_id = ?');
            $stmt->execute([$taskId, $userId]);
            if ($stmt->rowCount() === 0) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Not allowed']);
                exit;
            }
            echo json_encode(['success' => true, 'csrf_token' => $nextToken]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to delete task', 'csrf_token' => $nextToken]);
        }
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unknown action', 'csrf_token' => $nextToken]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);

