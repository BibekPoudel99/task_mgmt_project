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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

</head>
<body class="dashboard-body">
    <!-- CSRF token for API requests -->
    <?php echo Token::input(); ?>
    <input type="hidden" id="csrfTokenInput" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
    <script>
        window.currentUser = {
            id: <?php echo json_encode($_SESSION['user_id'] ?? null); ?>,
            username: <?php echo json_encode($username); ?>
        };
    </script>
    
    <!-- Modern Dark Navigation -->
    <nav id="mainNavbar" class="navbar navbar-expand-lg sticky-top glassmorphism navbar-large main-navbar">
        <div class="container navbar-container">
            <!-- Brand Section -->
            <a class="navbar-brand d-flex align-items-center hover-lift navbar-brand-custom" href="#">
                <div class="brand-icon pulse-glow">
                    <i class="bi bi-check-circle-fill"></i>
                    <div class="floating-orb first"></div>
                    <div class="floating-orb second"></div>
                </div>
                <div class="brand-text">
                    <div class="gradient-text brand-title">TaskFlow</div>
                </div>
            </a>

            <!-- Mobile Toggle Button -->
            <button class="navbar-toggler border-0 navbar-toggler-custom" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon navbar-toggler-icon-custom"></span>
            </button>

            <!-- Navigation Content -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- Center Navigation Items -->
                <div class="navbar-nav mx-auto d-flex align-items-center gap-2">
                    <!-- Quick Stats -->
                    <div class="nav-item d-flex align-items-center glassmorphism hover-lift nav-stats">
                        <div class="d-flex align-items-center gap-4">
                            <div class="text-center">
                                <div id="nav-total-tasks" class="nav-stat-number tasks">Loading...</div>
                                <div class="nav-stat-label">Tasks</div>
                            </div>
                            <div class="text-center">
                                <div id="nav-active-projects" class="nav-stat-number projects">Loading...</div>
                                <div class="nav-stat-label">Projects</div>
                            </div>
                            <div class="text-center">
                                <div id="nav-due-today" class="nav-stat-number due-today">Loading...</div>
                                <div class="nav-stat-label">Due Today</div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="nav-item">
                        <button class="btn d-flex align-items-center hover-lift quick-add-btn" onclick="document.getElementById('tasks-tab').click(); setTimeout(() => document.getElementById('taskTitle').focus(), 100);" 
                                onmouseover="this.style.transform='translateY(-2px) scale(1.05)'; this.style.boxShadow='0 8px 25px var(--glow-primary)'"
                                onmouseout="this.style.transform='translateY(0) scale(1)'; this.style.boxShadow='0 4px 15px var(--glow-primary)'">
                            <i class="bi bi-plus-circle me-2"></i>
                            Quick Add
                        </button>
                    </div>
                </div>

                <!-- Right Side User Section -->
                <div class="navbar-nav d-flex align-items-center gap-3">
                    <!-- User Profile Dropdown -->
                    <div class="nav-item dropdown">
                        <button class="btn dropdown-toggle d-flex align-items-center glassmorphism hover-lift user-dropdown-btn" id="userDropdown" data-bs-toggle="dropdown" 
                                onmouseover="this.style.boxShadow='0 8px 25px var(--shadow-medium)'; this.style.transform='translateY(-1px)'"
                                onmouseout="this.style.boxShadow='0 4px 15px var(--shadow-light)'; this.style.transform='translateY(0)'">
                            <!-- User Avatar -->
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($username, 0, 1)); ?>
                            </div>
                            <!-- User Info -->
                            <div class="text-start">
                                <div class="user-info-name"><?php echo htmlspecialchars($username); ?></div>
                                <div class="user-info-role">User Account</div>
                            </div>
                        </button>
                        
                        <!-- Dropdown Menu -->
                        <ul class="dropdown-menu dropdown-menu-end glassmorphism dropdown-menu-custom">
                            <!-- User Info Header -->
                            <li class="px-3 py-3 dropdown-header-custom">
                                <div class="d-flex align-items-center">
                                    <div class="dropdown-avatar">
                                        <?php echo strtoupper(substr($username, 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="dropdown-username"><?php echo htmlspecialchars($username); ?></div>
                                        <div class="dropdown-welcome">Welcome back!</div>
                                    </div>
                                </div>
                            </li>
                            
                            <!-- Menu Items -->
                            <li>
                                <a class="dropdown-item d-flex align-items-center hover-lift dropdown-item-custom" href="user_profile.php" 
                                   onmouseover="this.style.background='rgba(49,130,206,0.2)'; this.style.color='#60a5fa'"
                                   onmouseout="this.style.background='transparent'; this.style.color='white'">
                                    <i class="bi bi-person-circle me-3 dropdown-item-icon"></i>
                                    My Profile
                                </a>
                            </li>
                            
                            <li><hr class="dropdown-divider dropdown-divider-custom"></li>
                            
                            <li>
                                <a class="dropdown-item d-flex align-items-center hover-lift dropdown-item-custom logout" href="user_logout.php" id="logoutLink"
                                   onmouseover="this.style.background='rgba(239, 68, 68, 0.1)'; this.style.color='#f87171'"
                                   onmouseout="this.style.background='transparent'; this.style.color='#fca5a5'">
                                    <i class="bi bi-box-arrow-right me-3 dropdown-item-icon"></i>
                                    Sign Out
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container pb-2 main-container">        <!-- Header Section -->
        <section class="mb-5 text-center header-section">
            <!-- Animated background elements -->
            <div class="header-background-gradient"></div>
            
            <!-- Subtle decorative elements -->
            <div class="header-decoration-orb right"></div>
            <div class="header-decoration-orb left"></div>
            
            <!-- Main content -->
            <div class="header-content">
                <h1 class="fw-bold header-title">
                    TaskFlow Dashboard
                </h1>
                
                <p class="lead header-subtitle">
                    Stay organized and boost your productivity
                </p>
                
                <!-- Feature highlights -->
                <div class="header-features">
                    <div class="header-feature-item">
                        <div class="header-feature-icon">
                            <i class="bi bi-lightning-fill"></i>
                        </div>
                        <span>Fast</span>
                    </div>
                    <div class="header-feature-item">
                        <div class="header-feature-icon">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <span>Organized</span>
                    </div>
                    <div class="header-feature-item-collab">
                        <div class="header-feature-icon-collab">
                            <i class="bi bi-people-fill dashboard-icon-small"></i>
                        </div>
                        <span>Collaborative</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Tabs Navigation -->
    <ul class="nav nav-tabs mb-4 justify-content-center dashboard-tabs" id="dashboardTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active nav-tab-custom" id="myday-tab" data-bs-toggle="tab" data-bs-target="#myday" type="button" role="tab">
                    <i class="bi bi-sun me-2"></i>My Day
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link nav-tab-custom" id="tasks-tab" data-bs-toggle="tab" data-bs-target="#tasks" type="button" role="tab">
                    <i class="bi bi-check-square me-2"></i>Tasks
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link nav-tab-custom" id="projects-tab" data-bs-toggle="tab" data-bs-target="#projects" type="button" role="tab">
                    <i class="bi bi-folder me-2"></i>Projects
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link nav-tab-custom" id="team-tab" data-bs-toggle="tab" data-bs-target="#team" type="button" role="tab">
                    <i class="bi bi-people me-2"></i>Team
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link nav-tab-custom" id="calendar-tab" data-bs-toggle="tab" data-bs-target="#calendar" type="button" role="tab">
                    <i class="bi bi-calendar me-2"></i>Calendar
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link nav-tab-custom" id="missed-tab" data-bs-toggle="tab" data-bs-target="#missed" type="button" role="tab">
                    <i class="bi bi-exclamation-triangle me-2"></i>Missed Tasks
                    <span class="badge bg-danger ms-1 dashboard-badge-hidden">0</span>
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
    <div class="tab-content dashboard-tab-content" id="dashboardTabContent">
            <!-- My Day Tab -->
            <div class="tab-pane fade show active" id="myday" role="tabpanel">
                <div class="card shadow-sm dashboard-card">
                    <div class="card-header bg-light dashboard-card-header">
                        <h5 class="card-title mb-0 dashboard-card-title"><i class="bi bi-sun me-2 dashboard-icon-accent"></i>Today</h5>
                    </div>
                    <div class="card-body">
                        <div id="todayTasks">
                            <p class="text-muted dashboard-text-muted">No tasks due today. Add due dates in Tasks to see them here.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tasks Tab -->
            <div class="tab-pane fade" id="tasks" role="tabpanel">
                <!-- Add Task Form -->
                <div class="card shadow-sm mb-4 dashboard-card">
    <div class="card-header dashboard-card-header-enhanced">
        <h5 class="card-title mb-0 dashboard-card-title">
            <div class="dashboard-icon-wrapper">
                <i class="bi bi-plus-circle dashboard-icon-white"></i>
            </div>
            Create New Task
        </h5>
        <p class="mb-0 mt-2 dashboard-card-subtitle">Add a new task to your workflow and stay organized</p>
    </div>
    <div class="card-body dashboard-card-body">
        <form id="addTaskForm">
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="form-group">
                        <label for="taskTitle" class="form-label dashboard-form-label">
                            <i class="bi bi-pencil me-2 dashboard-icon-accent"></i>Task Title
                        </label>
                        <input type="text" class="form-control dashboard-form-control" id="taskTitle" 
                               placeholder="e.g., Prepare sprint board, Review documentation..." 
                               required>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="form-group">
                        <label for="taskDueDate" class="form-label dashboard-form-label">
                            <i class="bi bi-calendar3 me-2 dashboard-icon-accent"></i>Due Date
                        </label>
                        <input type="date" class="form-control dashboard-form-control" id="taskDueDate" 
                               min="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="form-group">
                        <label for="taskProject" class="form-label dashboard-form-label">
                            <i class="bi bi-folder me-2 dashboard-icon-accent"></i>Project
                        </label>
                        <select class="form-select dashboard-form-control" id="taskProject">
                            <option value="">No project (Personal task)</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="dashboard-tips">
                            <i class="bi bi-lightbulb me-2 dashboard-icon-warning"></i>
                            <span>Pro tip: Add due dates to see tasks in your calendar and daily view</span>
                        </div>
                        <button type="submit" class="btn dashboard-btn-primary">
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
                <div class="card shadow-sm dashboard-card">
                    <div class="card-header bg-light dashboard-card-header">
                        <h5 class="card-title mb-0 dashboard-card-title"><i class="bi bi-list-task me-2 dashboard-icon-accent"></i>All Tasks</h5>
                    </div>
                    <div class="card-body">
                        <div id="allTasks" class="tasks-tab">
                            <p class="text-muted dashboard-text-muted">No tasks yet. Add your first task above.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Projects Tab -->
            <div class="tab-pane fade" id="projects" role="tabpanel">
                <div class="row g-4">
                    <div class="col-md-5">
                        <!-- Projects List -->
                        <div class="card shadow-sm dashboard-card">
                            <div class="card-header bg-light dashboard-card-header-enhanced">
                                <h5 class="card-title mb-0 dashboard-card-title dashboard-card-title-large"><i class="bi bi-folder me-2 dashboard-icon-accent"></i>Projects</h5>
                            </div>
                            <div class="card-body dashboard-card-body-enhanced">
                                <form id="addProjectForm" class="mb-4">
                                    <div class="input-group dashboard-input-group">
                                        <input type="text" class="form-control dashboard-form-control-lg dashboard-form-input-project" id="projectName" placeholder="New project name" required>
                                        <button type="submit" class="btn dashboard-btn-add">
                                            <i class="bi bi-plus-circle me-2"></i>Add
                                        </button>
                                    </div>
                                </form>
                                <div id="projectsList" class="project-item dashboard-content-large">
                                    <!-- Projects will be dynamically loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <!-- Project Details -->
                        <div class="card shadow-sm dashboard-card">
                            <div class="card-header bg-light dashboard-card-header-enhanced">
                                <h5 class="card-title mb-0 dashboard-card-title dashboard-card-title-large"><i class="bi bi-people me-2 dashboard-icon-accent"></i>Team & Tasks</h5>
                            </div>
                            <div class="card-body dashboard-card-body-enhanced">
                                <div id="projectDetails" class="dashboard-content-large">
                                    <p class="text-muted dashboard-text-muted">Select or create a project to manage members and tasks.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Team Tab -->
            <div class="tab-pane fade" id="team" role="tabpanel">
                <div class="card shadow-sm dashboard-card">
                    <div class="card-header bg-light dashboard-card-header-enhanced">
                        <h5 class="card-title mb-0 dashboard-card-title dashboard-card-title-large"><i class="bi bi-people me-2 dashboard-icon-accent"></i>Team Overview</h5>
                    </div>
                    <div class="card-body dashboard-card-body-enhanced">
                        <div id="teamOverview" class="dashboard-content-large">
                            <p class="text-muted dashboard-text-muted">No team members yet. Add some under Projects.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Calendar Tab -->
            <div class="tab-pane fade" id="calendar" role="tabpanel">
                <div class="card shadow-sm dashboard-card">
                    <div class="card-header bg-light dashboard-card-header-enhanced">
                        <h5 class="card-title mb-0 dashboard-card-title dashboard-card-title-large"><i class="bi bi-calendar me-2 dashboard-icon-accent"></i>Upcoming Tasks</h5>
                    </div>
                    <div class="card-body dashboard-card-body-enhanced">
                        <div id="upcomingTasks" class="dashboard-content-large">
                             <p class="text-muted dashboard-text-muted">Loading upcoming tasks...</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="missed" role="tabpanel" aria-labelledby="missed-tab">
                <div class="card shadow-sm dashboard-card">
                    <div class="card-header bg-light dashboard-card-header-enhanced">
                        <h5 class="card-title mb-0 dashboard-card-title dashboard-card-title-large"><i class="bi bi-exclamation-triangle me-2 dashboard-icon-warning"></i>Missed Tasks</h5>
                    </div>
                    <div class="card-body dashboard-card-body-enhanced">
                        <div id="missed-tasks-content" class="dashboard-content-large">
                            <div class="text-center py-4">
                                <div class="spinner-border dashboard-icon-accent" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2 dashboard-text-muted">Loading missed tasks...</p>
                            </div>
                        </div>
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
    
    <!-- Scroll-responsive Navbar JavaScript -->
    <script>
        // Navbar scroll responsiveness
        class ScrollNavbar {
            constructor() {
                this.navbar = document.getElementById('mainNavbar');
                this.lastScrollTop = 0;
                this.scrollThreshold = 100; // Pixels to scroll before navbar changes
                this.isSmall = false;
                
                this.init();
            }
            
            init() {
                // Throttled scroll event listener for better performance
                let ticking = false;
                
                window.addEventListener('scroll', () => {
                    if (!ticking) {
                        requestAnimationFrame(() => {
                            this.handleScroll();
                            ticking = false;
                        });
                        ticking = true;
                    }
                });
                
                // Handle initial load
                this.handleScroll();
            }
            
            handleScroll() {
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                
                // Only change navbar size based on scroll position
                if (scrollTop > this.scrollThreshold && !this.isSmall) {
                    this.makeNavbarSmall();
                } else if (scrollTop <= this.scrollThreshold && this.isSmall) {
                    this.makeNavbarLarge();
                }
                
                this.lastScrollTop = scrollTop;
            }
            
            makeNavbarSmall() {
                this.isSmall = true;
                this.navbar.classList.remove('navbar-large');
                this.navbar.classList.add('navbar-small');
                
                // Add additional styling for compact mode
                this.navbar.style.backdropFilter = 'blur(25px)';
                this.navbar.style.background = 'rgba(37, 43, 50, 0.95)';
                this.navbar.style.boxShadow = '0 4px 20px var(--shadow-heavy)';
                
                // Optional: Hide some elements on mobile for even more space
                if (window.innerWidth <= 768) {
                    const navStats = this.navbar.querySelector('.nav-stats');
                    if (navStats) {
                        navStats.style.display = 'none';
                    }
                }
            }
            
            makeNavbarLarge() {
                this.isSmall = false;
                this.navbar.classList.remove('navbar-small');
                this.navbar.classList.add('navbar-large');
                
                // Restore original styling
                this.navbar.style.backdropFilter = 'blur(20px)';
                this.navbar.style.background = 'rgba(37, 43, 50, 0.8)';
                this.navbar.style.boxShadow = '0 8px 32px var(--shadow-heavy)';
                
                // Show hidden elements
                const navStats = this.navbar.querySelector('.nav-stats');
                if (navStats) {
                    navStats.style.display = 'flex';
                }
            }
        }
        
        // Initialize scroll navbar when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            new ScrollNavbar();
        });
        
        // Handle window resize
        window.addEventListener('resize', function() {
            const navbar = document.getElementById('mainNavbar');
            const navStats = navbar.querySelector('.nav-stats');
            
            // Always show nav stats on larger screens
            if (window.innerWidth > 768 && navStats) {
                navStats.style.display = 'flex';
            }
        });
    </script>
</body>
</html>