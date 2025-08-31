<?php
require_once __DIR__ . '/../library/ApiAuth.php';

ApiAuth::initApiResponse();
$auth = ApiAuth::requireUserAuth();
$userId = $auth['user_id'];

$db = ApiAuth::getDatabase();
$pdo = $db->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // List projects visible to the user: owned or where user is a member
    try {
        $sql = "
            SELECT p.id, p.name, p.owner_id
            FROM projects p
            LEFT JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = :uid
            WHERE p.owner_id = :uid OR pm.user_id = :uid
            GROUP BY p.id
            ORDER BY p.id DESC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch members per project with their status
        $projectIds = array_map(fn($p) => (int)$p['id'], $projects);
        $membersByProject = [];
        if ($projectIds) {
            $in = implode(',', array_fill(0, count($projectIds), '?'));
            $stmtM = $pdo->prepare("SELECT pm.project_id, u.username, COALESCE(u.is_active, 1) as is_active FROM project_members pm JOIN users u ON u.id = pm.user_id WHERE pm.project_id IN ($in)");
            $stmtM->execute($projectIds);
            while ($row = $stmtM->fetch(PDO::FETCH_ASSOC)) {
                $pid = (int)$row['project_id'];
                if (!isset($membersByProject[$pid])) $membersByProject[$pid] = [];
                $membersByProject[$pid][] = [
                    'username' => $row['username'],
                    'is_active' => (bool)$row['is_active']
                ];
            }
        }

        $projects = array_map(function($p) use ($membersByProject) {
            $pid = (int)$p['id'];
            return [
                'id' => $pid,
                'name' => $p['name'],
                'owner_id' => (int)$p['owner_id'],
                'members' => $membersByProject[$pid] ?? []
            ];
        }, $projects);

        echo json_encode(['success' => true, 'projects' => $projects]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to fetch projects']);
    }
    exit;
}

if ($method === 'POST') {
    // CSRF
    $csrf = $_POST['csrf_token'] ?? '';
    $nextToken = ApiAuth::validateCsrfToken($csrf);

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Name is required']);
            exit;
        }
        try {
            $stmt = $pdo->prepare('INSERT INTO projects (name, owner_id, created_at) VALUES (?, ?, NOW())');
            $stmt->execute([$name, $userId]);
            $projectId = (int)$pdo->lastInsertId();
            // Owner is implicitly a member as well
            $pdo->prepare('INSERT IGNORE INTO project_members (project_id, user_id) VALUES (?, ?)')->execute([$projectId, $userId]);
            echo json_encode(['success' => true, 'id' => $projectId, 'csrf_token' => $nextToken]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to create project', 'csrf_token' => $nextToken]);
        }
        exit;
    }

    if ($action === 'update') {
        $projectId = (int)($_POST['project_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        if (!$projectId || $name === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Invalid input']);
            exit;
        }
        try {
            $ownerCheck = $pdo->prepare('SELECT owner_id FROM projects WHERE id = ?');
            $ownerCheck->execute([$projectId]);
            $ownerId = (int)($ownerCheck->fetchColumn() ?: 0);
            if ($ownerId !== $userId) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Only project owner can update project']);
                exit;
            }
            $stmt = $pdo->prepare('UPDATE projects SET name = ? WHERE id = ?');
            $stmt->execute([$name, $projectId]);
            echo json_encode(['success' => true, 'csrf_token' => $nextToken]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to update project', 'csrf_token' => $nextToken]);
        }
        exit;
    }

    if ($action === 'delete') {
        $projectId = (int)($_POST['project_id'] ?? 0);
        if (!$projectId) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Invalid input']);
            exit;
        }
        try {
            $ownerCheck = $pdo->prepare('SELECT owner_id FROM projects WHERE id = ?');
            $ownerCheck->execute([$projectId]);
            $ownerId = (int)($ownerCheck->fetchColumn() ?: 0);
            if ($ownerId !== $userId) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Only project owner can delete project']);
                exit;
            }
            // Null out project_id in tasks (fk set null should handle, but ensure order)
            $pdo->prepare('UPDATE tasks SET project_id = NULL WHERE project_id = ?')->execute([$projectId]);
            // Delete project (members cascade via FK)
            $pdo->prepare('DELETE FROM projects WHERE id = ?')->execute([$projectId]);
            echo json_encode(['success' => true, 'csrf_token' => $nextToken]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to delete project', 'csrf_token' => $nextToken]);
        }
        exit;
    }

    if ($action === 'remove_member') {
        $projectId = (int)($_POST['project_id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        if (!$projectId || $username === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Invalid input']);
            exit;
        }
        try {
            $ownerCheck = $pdo->prepare('SELECT owner_id FROM projects WHERE id = ?');
            $ownerCheck->execute([$projectId]);
            $ownerId = (int)($ownerCheck->fetchColumn() ?: 0);
            if ($ownerId !== $userId) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Only project owner can remove member']);
                exit;
            }
            $stmtU = $pdo->prepare('SELECT id FROM users WHERE username = ?');
            $stmtU->execute([$username]);
            $memberId = (int)($stmtU->fetchColumn() ?: 0);
            if (!$memberId) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'User not found']);
                exit;
            }
            
            // Transfer ownership of all tasks created by this user in this project to the project owner
            $pdo->prepare('UPDATE tasks SET owner_id = ? WHERE project_id = ? AND owner_id = ?')->execute([$ownerId, $projectId, $memberId]);
            
            // Unassign all tasks assigned to this user in this project
            $pdo->prepare('UPDATE tasks SET assignee_id = NULL WHERE project_id = ? AND assignee_id = ?')->execute([$projectId, $memberId]);
            
            // Remove user from project members
            $pdo->prepare('DELETE FROM project_members WHERE project_id = ? AND user_id = ?')->execute([$projectId, $memberId]);
            echo json_encode(['success' => true, 'csrf_token' => $nextToken]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to remove member', 'csrf_token' => $nextToken]);
        }
        exit;
    }

    if ($action === 'add_member') {
        $projectId = (int)($_POST['project_id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        if (!$projectId || $username === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Invalid input']);
            exit;
        }
        try {
            // Only owner can add members
            $ownerCheck = $pdo->prepare('SELECT owner_id FROM projects WHERE id = ?');
            $ownerCheck->execute([$projectId]);
            $ownerId = (int)($ownerCheck->fetchColumn() ?: 0);
            if ($ownerId !== $userId) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Only project owner can add members']);
                exit;
            }
            // Find user by username
            $stmtU = $pdo->prepare('SELECT id FROM users WHERE username = ?');
            $stmtU->execute([$username]);
            $memberId = (int)($stmtU->fetchColumn() ?: 0);
            if (!$memberId) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'User not found']);
                exit;
            }
            // Insert membership
            $stmt = $pdo->prepare('INSERT IGNORE INTO project_members (project_id, user_id) VALUES (?, ?)');
            $stmt->execute([$projectId, $memberId]);
            echo json_encode(['success' => true, 'csrf_token' => $nextToken]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to add member', 'csrf_token' => $nextToken]);
        }
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unknown action', 'csrf_token' => $nextToken]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);

