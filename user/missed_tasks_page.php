<?php
session_start();
if (empty($_SESSION['user_logged_in'])) {
    header('Location: user_login.php');
    exit;
}

require_once '../library/TaskUtils.php';

$taskUtils = new TaskUtils();

try {
    // Update missed tasks first
    $updatedCount = $taskUtils->updateMissedTasks();
    
    // Get missed tasks for this user
    $missedTasks = $taskUtils->getMissedTasksForUser($_SESSION['user_id']);
    
} catch (Exception $e) {
    error_log("Error loading missed tasks: " . $e->getMessage());
    $missedTasks = [];
}

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
    <link rel="stylesheet" href="../assets/user_style.css">
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