<?php
session_start();
if (empty($_SESSION['user_logged_in'])) {
    header('Location: user_login.php');
    exit;
}

require_once '../library/Database.php';
require_once '../library/TaskUtils.php';

// Add debug output here
echo "<div style='background: #f0f0f0; padding: 10px; margin: 10px; border: 1px solid #ccc;'>";
echo "<h3>DEBUG INFO:</h3>";
echo "User ID: " . $_SESSION['user_id'] . "<br>";
echo "Today's date: " . date('Y-m-d') . "<br>";

// Get all tasks for this user to see what's in the database
$db = new Database();
$pdo = $db->getConnection();

// Now get all tasks with correct column names
echo "<h4>All your tasks:</h4>";
try {
    // Use assignee_id instead of assigned_to
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE owner_id = ? OR assignee_id = ?");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $allTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($allTasks)) {
        echo "No tasks found for user ID: " . $_SESSION['user_id'] . "<br>";
    } else {
        foreach ($allTasks as $task) {
            echo "Task: {$task['title']}, Due: {$task['due_date']}, Completed: {$task['completed']}, Missed: {$task['is_missed']}<br>";
        }
    }
    
    // Now check for missed tasks
    echo "<h4>Checking for missed tasks...</h4>";
    
    // First update missed tasks
    $updateStmt = $pdo->prepare("
        UPDATE tasks 
        SET is_missed = 1 
        WHERE due_date < CURDATE() 
        AND completed = 0 
        AND is_missed = 0
    ");
    $updateStmt->execute();
    $updatedCount = $updateStmt->rowCount();
    echo "Updated {$updatedCount} tasks as missed<br>";
    
    // Get missed tasks for this user
    $missedStmt = $pdo->prepare("
        SELECT t.*, p.name as project_name 
        FROM tasks t 
        LEFT JOIN projects p ON t.project_id = p.id 
        WHERE (t.owner_id = ? OR t.assignee_id = ?) 
        AND t.is_missed = 1 
        ORDER BY t.due_date DESC
    ");
    $missedStmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $missedTasks = $missedStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($missedTasks) . " missed tasks<br>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
    $missedTasks = [];
}

echo "</div>";

$username = $_SESSION['username'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Missed Tasks - TaskFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background: #f8f9fa; }
        .main-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 2rem;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 0 16px rgba(0,0,0,0.08);
        }
        .page-title {
            color: #dc3545;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }
        .missed-badge {
            background-color: #dc3545;
        }
        .no-tasks-container {
            text-align: center;
            padding: 3rem 2rem;
        }
        .no-tasks-icon {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="page-title">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                Missed Tasks
            </h2>
            <a href="user_dashboard.php" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
            </a>
        </div>
        
        <?php if (empty($missedTasks)): ?>
            <div class="no-tasks-container">
                <div class="no-tasks-icon">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <h4 class="text-success mb-3">No Missed Tasks</h4>
                <p class="text-muted mb-4">
                    Great job! You have no missed tasks. Keep up the excellent work staying on top of your deadlines!
                </p>
                <a href="user_dashboard.php" class="btn btn-primary">
                    <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
                </a>
            </div>
        <?php else: ?>
            <div class="alert alert-warning d-flex align-items-center mb-4">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <div>
                    You have <strong><?= count($missedTasks) ?></strong> missed task(s). Consider updating their status or extending deadlines.
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Task</th>
                            <th>Project</th>
                            <th>Due Date</th>
                            <th>Days Overdue</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($missedTasks as $task): 
                            $dueDate = new DateTime($task['due_date']);
                            $today = new DateTime();
                            $daysOverdue = $today->diff($dueDate)->days;
                        ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($task['title']) ?></strong>
                            </td>
                            <td>
                                <?php if ($task['project_name']): ?>
                                    <span class="badge bg-info"><?= htmlspecialchars($task['project_name']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">No Project</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-danger">
                                <?= $dueDate->format('M j, Y') ?>
                            </td>
                            <td>
                                <span class="badge bg-danger"><?= $daysOverdue ?> days</span>
                            </td>
                            <td>
                                <span class="badge missed-badge">Missed</span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>