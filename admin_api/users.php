<?php
session_start();
header('Content-Type: application/json');
if (empty($_SESSION['user_logged_in']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../library/Database.php';
require_once __DIR__ . '/../library/Session.php';
require_once __DIR__ . '/../library/Token.php';

$db = new Database();
$pdo = $db->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $q = trim($_GET['q'] ?? '');
    $status = $_GET['status'] ?? '';
    $params = [];
    $where = [];
    if ($q !== '') {
        $where[] = '(username LIKE ? OR email LIKE ?)';
        $params[] = "%$q%";
        $params[] = "%$q%";
    }
    if ($status !== '' && ($status === '0' || $status === '1')) {
        $where[] = 'is_active = ?';
        $params[] = (int)$status;
    }
    $sql = 'SELECT id, username, email, COALESCE(is_active, 1) AS is_active FROM users';
    if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
    $sql .= ' ORDER BY id DESC LIMIT 200';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode(['success' => true, 'users' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

if ($method === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!Token::check($csrf)) {
        $new = Session::put('csrf_token', md5(uniqid()));
        http_response_code(419);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token', 'csrf_token' => $new]);
        exit;
    }
    $next = Session::put('csrf_token', md5(uniqid()));
    $action = $_POST['action'] ?? '';
    if ($action === 'set_active') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $isActive = $_POST['is_active'] === '1' ? 1 : 0;
        if (!$userId) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Invalid input', 'csrf_token' => $next]);
            exit;
        }
        try {
            $stmt = $pdo->prepare('UPDATE users SET is_active = ? WHERE id = ?');
            $stmt->execute([$isActive, $userId]);
            echo json_encode(['success' => true, 'csrf_token' => $next]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to update user', 'csrf_token' => $next]);
        }
        exit;
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unknown action', 'csrf_token' => $next]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);

