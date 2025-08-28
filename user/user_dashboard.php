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
<body style="background-color: #fcfcfa; min-height: 100vh;">
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
    <nav class="navbar navbar-expand-lg navbar-light" style="background: linear-gradient(135deg, #faf8f3, #f5f2eb); box-shadow: 0 2px 8px rgba(124,132,113,0.07);">
        <div class="container" style="max-width: 1100px;">
            <a class="navbar-brand fw-bold" style="color: #6b7260; font-family: 'Inter', 'Segoe UI', sans-serif; font-size: 2rem; letter-spacing: 1px;" href="#">
                <i class="bi bi-check-circle-fill me-2" style="color: #7c8471;"></i>TaskFlow
            </a>
            <div class="navbar-nav ms-auto d-flex align-items-center gap-2">
                <a class="nav-link" style="color: #7c8471; font-weight: 500;" href="user_profile.php"><i class="bi bi-person-circle me-1"></i>Profile</a>
                <a class="nav-link" style="color: #7c8471; font-weight: 500;" id="logoutLink" href="user_logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container py-5" style="max-width: 1200px; margin: 40px auto 0 auto; background: #fffefd; border-radius: 20px; box-shadow: 0 4px 24px rgba(124,132,113,0.10); padding: 40px 32px 32px 32px;">
        <!-- Header Section -->
        <section class="mb-5 text-center">
            <h1 class="fw-bold" style="font-family: 'Inter', 'Segoe UI', sans-serif; color: #4a5c3a; font-size: 2.5rem; margin-bottom: 0.5rem; letter-spacing: 0.5px;">TaskFlow Dashboard</h1>
            <p class="lead" style="color: #7c8471; font-family: 'Inter', 'Segoe UI', sans-serif; font-size: 1.15rem;">Stay organized across your day, tasks, and projects. Collaborate with your team and track progress at a glance.</p>
        </section>

        <!-- Tabs Navigation -->
    <ul class="nav nav-tabs mb-4 justify-content-center" id="dashboardTabs" role="tablist" style="border-bottom: 2px solid #e5e3db;">
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
    <div class="tab-content" id="dashboardTabContent" style="margin-top: 24px;">
            <!-- My Day Tab -->
            <div class="tab-pane fade show active" id="myday" role="tabpanel">
                <div class="card shadow-sm" style="border-radius: 16px; background: #fffefb;">
                    <div class="card-header bg-light" style="background: #f5f2eb !important; border-radius: 16px 16px 0 0;">
                        <h5 class="card-title mb-0" style="color: #6b7260;"><i class="bi bi-sun me-2"></i>Today</h5>
                    </div>
                    <div class="card-body">
                        <div id="todayTasks">
                            <p class="text-muted" style="color: #8b8680 !important;">No tasks due today. Add due dates in Tasks to see them here.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tasks Tab -->
            <div class="tab-pane fade" id="tasks" role="tabpanel">
                <!-- Add Task Form -->
                <div class="card shadow-sm mb-4" style="border-radius: 16px; background: linear-gradient(135deg, #ffffff, #fafbfc); border: 1px solid #e2e8f0;">
    <div class="card-header" style="background: linear-gradient(135deg, #f8fafc, #f1f5f9); border-radius: 16px 16px 0 0; border-bottom: 1px solid #e2e8f0; padding: 20px 24px;">
        <h5 class="card-title mb-0" style="color: #334155; font-weight: 600; display: flex; align-items: center;">
            <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #7c8471, #9a9e92); border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-right: 12px;">
                <i class="bi bi-plus-circle" style="color: white; font-size: 18px;"></i>
            </div>
            Create New Task
        </h5>
        <p class="mb-0 mt-2" style="color: #64748b; font-size: 14px;">Add a new task to your workflow and stay organized</p>
    </div>
    <div class="card-body" style="padding: 24px;">
        <form id="addTaskForm">
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="form-group">
                        <label for="taskTitle" class="form-label" style="color: #374151; font-weight: 600; margin-bottom: 8px; display: flex; align-items: center;">
                            <i class="bi bi-pencil me-2" style="color: #7c8471;"></i>Task Title
                        </label>
                        <input type="text" class="form-control" id="taskTitle" 
                               placeholder="e.g., Prepare sprint board, Review documentation..." 
                               required
                               style="border: 2px solid #e2e8f0; border-radius: 10px; padding: 12px 16px; font-size: 14px; transition: all 0.3s ease; background: #ffffff;"
                               onfocus="this.style.borderColor='#7c8471'; this.style.boxShadow='0 0 0 3px rgba(124,132,113,0.1)'"
                               onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'">
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="form-group">
                        <label for="taskDueDate" class="form-label" style="color: #374151; font-weight: 600; margin-bottom: 8px; display: flex; align-items: center;">
                            <i class="bi bi-calendar3 me-2" style="color: #7c8471;"></i>Due Date
                        </label>
                        <input type="date" class="form-control" id="taskDueDate" 
                               min="<?php echo date('Y-m-d'); ?>"
                               style="border: 2px solid #e2e8f0; border-radius: 10px; padding: 12px 16px; font-size: 14px; transition: all 0.3s ease; background: #ffffff;"
                               onfocus="this.style.borderColor='#7c8471'; this.style.boxShadow='0 0 0 3px rgba(124,132,113,0.1)'"
                               onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'">
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="form-group">
                        <label for="taskProject" class="form-label" style="color: #374151; font-weight: 600; margin-bottom: 8px; display: flex; align-items: center;">
                            <i class="bi bi-folder me-2" style="color: #7c8471;"></i>Project
                        </label>
                        <select class="form-select" id="taskProject"
                                style="border: 2px solid #e2e8f0; border-radius: 10px; padding: 12px 16px; font-size: 14px; transition: all 0.3s ease; background: #ffffff;"
                                onfocus="this.style.borderColor='#7c8471'; this.style.boxShadow='0 0 0 3px rgba(124,132,113,0.1)'"
                                onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'">
                            <option value="">No project (Personal task)</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="task-tips" style="display: flex; align-items: center; color: #64748b; font-size: 13px;">
                            <i class="bi bi-lightbulb me-2" style="color: #f59e0b;"></i>
                            <span>Pro tip: Add due dates to see tasks in your calendar and daily view</span>
                        </div>
                        <button type="submit" class="btn" 
                                style="background: linear-gradient(135deg, #7c8471, #9a9e92); color: white; border: none; border-radius: 12px; padding: 12px 24px; font-weight: 600; font-size: 14px; transition: all 0.3s ease; display: flex; align-items: center; box-shadow: 0 2px 8px rgba(124,132,113,0.2);"
                                onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 16px rgba(124,132,113,0.3)'"
                                onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(124,132,113,0.2)'">
                            <i class="bi bi-plus-circle-fill me-2"></i>
                            Create Task
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

                <!-- All Tasks -->
                <div class="card shadow-sm" style="border-radius: 16px; background: #fffefb;">
                    <div class="card-header bg-light" style="background: #f5f2eb !important; border-radius: 16px 16px 0 0;">
                        <h5 class="card-title mb-0" style="color: #6b7260;"><i class="bi bi-list-task me-2"></i>All Tasks</h5>
                    </div>
                    <div class="card-body">
                        <div id="allTasks" class="tasks-tab">
                            <p class="text-muted" style="color: #8b8680 !important;">No tasks yet. Add your first task above.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Projects Tab -->
            <div class="tab-pane fade" id="projects" role="tabpanel">
                <div class="row g-4">
                    <div class="col-md-5">
                        <!-- Projects List -->
                        <div class="card shadow-sm" style="border-radius: 16px; background: #fffefb;">
                            <div class="card-header bg-light" style="background: #f5f2eb !important; border-radius: 16px 16px 0 0;">
                                <h5 class="card-title mb-0" style="color: #6b7260;"><i class="bi bi-folder me-2"></i>Projects</h5>
                            </div>
                            <div class="card-body">
                                <form id="addProjectForm" class="mb-3">
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="projectName" placeholder="New project name" required>
                                        <button type="submit" class="btn btn-olive" style="border-radius: 24px; font-weight: 700;">Add</button>
                                    </div>
                                </form>
                                <div id="projectsList" class="project-item">
                                    <!-- Projects will be dynamically loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <!-- Project Details -->
                        <div class="card shadow-sm" style="border-radius: 16px; background: #fffefb;">
                            <div class="card-header bg-light" style="background: #f5f2eb !important; border-radius: 16px 16px 0 0;">
                                <h5 class="card-title mb-0" style="color: #6b7260;"><i class="bi bi-people me-2"></i>Team & Tasks</h5>
                            </div>
                            <div class="card-body">
                                <div id="projectDetails">
                                    <p class="text-muted" style="color: #fff !important;">Select or create a project to manage members and tasks.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Team Tab -->
            <div class="tab-pane fade" id="team" role="tabpanel">
                <div class="card shadow-sm" style="border-radius: 16px; background: #fffefb;">
                    <div class="card-header bg-light" style="background: #f5f2eb !important; border-radius: 16px 16px 0 0;">
                        <h5 class="card-title mb-0" style="color: #6b7260;"><i class="bi bi-people me-2"></i>Team Overview</h5>
                    </div>
                    <div class="card-body">
                        <div id="teamOverview">
                            <p class="text-muted" style="color: #8b8680 !important;">No team members yet. Add some under Projects.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Calendar Tab -->
            <div class="tab-pane fade" id="calendar" role="tabpanel">
                <div class="card shadow-sm" style="border-radius: 16px; background: #fffefb;">
                    <div class="card-header bg-light" style="background: #f5f2eb !important; border-radius: 16px 16px 0 0;">
                        <h5 class="card-title mb-0" style="color: #6b7260;"><i class="bi bi-calendar me-2"></i>Upcoming</h5>
                    </div>
                    <div class="card-body">
                        <div id="upcomingTasks">
                            <p class="text-muted" style="color: #8b8680 !important;">No due dates set yet.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="missed" role="tabpanel" aria-labelledby="missed-tab">
                <div id="missed-tasks-content">
                    <div class="text-center py-4">
                        <div class="spinner-border" style="color: #7c8471;" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2" style="color: #8b8680;">Loading missed tasks...</p>
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