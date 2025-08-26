<?php
 session_start();
 require_once __DIR__ . '/../library/Session.php';
 require_once __DIR__ . '/../library/Token.php';
 if (empty($_SESSION['user_logged_in']) || ($_SESSION['role'] ?? '') !== 'user') {
    header('Location: user_login.php');
    exit;
}
$username = $_SESSION['username'] ?? 'User'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="TaskFlow Dashboard - Stay organized across your day, tasks, and projects. Collaborate with your team and track progress at a glance.">
    <title>TaskFlow Dashboard - Task Management System</title>
    <link rel="canonical" href="https://taskflow-dashboard.com/">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/user_style.css" rel="stylesheet">
    <!-- Admin theme styles to match admin/index.html theme -->
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="../assets/index.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body class="bg-cream">
    <!-- CSRF token for API requests -->
    <?php echo Token::input(); ?>
    <input type="hidden" id="csrfTokenInput" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
    <script>
        window.currentUser = {
            id: <?php echo json_encode($_SESSION['user_id'] ?? null); ?>,
            username: <?php echo json_encode($username); ?>
        };
    </script>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-cream border-bottom">
        <div class="container">
            <a class="navbar-brand fw-bold text-olive" href="#">
                <i class="bi bi-check-circle-fill me-2"></i>TaskFlow
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link text-olive" href="user_profile.php"><i class="bi bi-person-circle me-1"></i>Profile</a>
                <a class="nav-link text-olive" id="logoutLink" href="user_logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container py-5">
        <!-- Header Section -->
        <section class="mb-5">
            <h1 class="display-4 fw-bold text-olive mb-2">TaskFlow Dashboard</h1>
            <p class="lead text-muted">Stay organized across your day, tasks, and projects. Collaborate with your team and track progress at a glance.</p>
            
        </section>

        <!-- Tabs Navigation -->
        <ul class="nav nav-tabs mb-4" id="dashboardTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="myday-tab" data-bs-toggle="tab" data-bs-target="#myday" type="button" role="tab">
                    <i class="bi bi-sun me-2"></i>My Day
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tasks-tab" data-bs-toggle="tab" data-bs-target="#tasks" type="button" role="tab">
                    <i class="bi bi-check-square me-2"></i>Tasks
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="projects-tab" data-bs-toggle="tab" data-bs-target="#projects" type="button" role="tab">
                    <i class="bi bi-folder me-2"></i>Projects
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="team-tab" data-bs-toggle="tab" data-bs-target="#team" type="button" role="tab">
                    <i class="bi bi-people me-2"></i>Team
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="calendar-tab" data-bs-toggle="tab" data-bs-target="#calendar" type="button" role="tab">
                    <i class="bi bi-calendar me-2"></i>Calendar
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="missed-tab" data-bs-toggle="tab" data-bs-target="#missed" type="button" role="tab">
                    <i class="bi bi-exclamation-triangle me-2"></i>Missed Tasks
                    <span class="badge bg-danger ms-1" style="display: none;">0</span>
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="dashboardTabContent">
            <!-- My Day Tab -->
            <div class="tab-pane fade show active" id="myday" role="tabpanel">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0"><i class="bi bi-sun me-2"></i>Today</h5>
                    </div>
                    <div class="card-body">
                        <div id="todayTasks">
                            <p class="text-muted">No tasks due today. Add due dates in Tasks to see them here.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tasks Tab -->
            <div class="tab-pane fade" id="tasks" role="tabpanel">
                <!-- Add Task Form -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0"><i class="bi bi-plus-circle me-2"></i>Add Task</h5>
                    </div>
                    <div class="card-body">
                        <form id="addTaskForm">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="taskTitle" class="form-label">Title</label>
                                    <input type="text" class="form-control" id="taskTitle" placeholder="e.g. Prepare sprint board" required>
                                </div>
                                <div class="col-md-2">
                                    <label for="taskDueDate" class="form-label">Due date</label>
                                    <input type="date" class="form-control" id="taskDueDate" min="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="col-md-2">
                                    <label for="taskProject" class="form-label">Project</label>
                                    <select class="form-select" id="taskProject">
                                        <option value="">Optional</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="submit" class="btn btn-olive w-100">Add Task</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- All Tasks -->
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0"><i class="bi bi-list-task me-2"></i>All Tasks</h5>
                    </div>
                    <div class="card-body">
                        <div id="allTasks">
                            <p class="text-muted">No tasks yet. Add your first task above.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Projects Tab -->
            <div class="tab-pane fade" id="projects" role="tabpanel">
                <div class="row">
                    <div class="col-md-4">
                        <!-- Projects List -->
                        <div class="card shadow-sm">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0"><i class="bi bi-folder me-2"></i>Projects</h5>
                            </div>
                            <div class="card-body">
                                <form id="addProjectForm" class="mb-3">
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="projectName" placeholder="New project name" required>
                                        <button type="submit" class="btn btn-olive">Add</button>
                                    </div>
                                </form>
                                <div id="projectsList">
                                    <!-- Projects will be dynamically loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <!-- Project Details -->
                        <div class="card shadow-sm">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0"><i class="bi bi-people me-2"></i>Team & Tasks</h5>
                            </div>
                            <div class="card-body">
                                <div id="projectDetails">
                                    <p class="text-muted">Select or create a project to manage members and tasks.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Team Tab -->
            <div class="tab-pane fade" id="team" role="tabpanel">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0"><i class="bi bi-people me-2"></i>Team Overview</h5>
                    </div>
                    <div class="card-body">
                        <div id="teamOverview">
                            <p class="text-muted">No team members yet. Add some under Projects.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Calendar Tab -->
            <div class="tab-pane fade" id="calendar" role="tabpanel">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0"><i class="bi bi-calendar me-2"></i>Upcoming</h5>
                    </div>
                    <div class="card-body">
                        <div id="upcomingTasks">
                            <p class="text-muted">No due dates set yet.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="missed" role="tabpanel" aria-labelledby="missed-tab">
            <div id="missed-tasks-content">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading missed tasks...</p>
                </div>
            </div>
        </div>
        </div>
    </main>

    <!-- Toast Container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer"></div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Dashboard JS -->
    <script src="../assets/user_script.js"></script>
    <script src="../assets/missed_tasks.js"></script>
</body>
</html>