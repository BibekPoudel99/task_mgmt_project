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
    
    <!-- Modern Blue Theme CSS Variables -->
    <style>
        :root {
            --bg-primary: #0f1419;
            --bg-secondary: #1a202c;
            --bg-tertiary: #2d3748;
            --bg-card: #f7fafc;
            --bg-card-hover: #edf2f7;
            --text-primary: #2d3748;
            --text-secondary: #4a5568;
            --text-muted: #718096;
            --accent-primary: #3182ce;
            --accent-secondary: #4299e1;
            --accent-success: #38a169;
            --accent-warning: #ed8936;
            --accent-danger: #e53e3e;
            --border-primary: #e2e8f0;
            --border-secondary: #cbd5e0;
            --shadow-light: rgba(0, 0, 0, 0.1);
            --shadow-medium: rgba(0, 0, 0, 0.15);
            --shadow-heavy: rgba(0, 0, 0, 0.25);
            --glow-primary: rgba(49, 130, 206, 0.3);
            --glow-secondary: rgba(66, 153, 225, 0.3);
        }
        
        * {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', roboto, sans-serif;
        }
        
        .glassmorphism {
            background: rgba(45, 55, 72, 0.9);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }
        
        .hover-lift:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px var(--shadow-medium);
        }
        
        .gradient-text {
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .pulse-glow {
            animation: pulseGlow 3s ease-in-out infinite;
        }
        
        @keyframes pulseGlow {
            0%, 100% { box-shadow: 0 0 20px var(--glow-primary); }
            50% { box-shadow: 0 0 30px var(--glow-secondary), 0 0 40px var(--glow-primary); }
        }
        
        .floating-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(1px);
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            33% { transform: translateY(-10px) rotate(120deg); }
            66% { transform: translateY(5px) rotate(240deg); }
        }
        
        /* Navbar scroll responsive styles */
        .navbar-large {
            padding: 16px 0;
        }
        
        .navbar-small {
            padding: 8px 0;
        }
        
        .navbar-small .brand-icon {
            width: 40px !important;
            height: 40px !important;
            border-radius: 12px !important;
        }
        
        .navbar-small .brand-icon i {
            font-size: 20px !important;
        }
        
        .navbar-small .brand-title {
            font-size: 1.5rem !important;
        }
        
        .navbar-small .nav-stats {
            transform: scale(0.9);
        }
        
        .navbar-small .user-avatar {
            width: 32px !important;
            height: 32px !important;
        }
        
        .navbar-small .quick-add-btn {
            padding: 8px 14px !important;
            font-size: 0.85rem !important;
        }
        
        .navbar-small .nav-item .glassmorphism {
            padding: 8px 16px !important;
        }
        
        /* Smooth transitions for all navbar elements */
        .navbar * {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }
    </style>
</head>
<body style="background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 100%); min-height: 100vh; color: var(--text-primary);">
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
    <nav id="mainNavbar" class="navbar navbar-expand-lg sticky-top glassmorphism navbar-large" style="box-shadow: 0 8px 32px var(--shadow-heavy); border-bottom: 1px solid rgba(255,255,255,0.1); z-index: 1020; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);">
        <div class="container" style="max-width: 1400px;">
            <!-- Brand Section -->
            <a class="navbar-brand d-flex align-items-center hover-lift" style="color: white; font-family: 'Inter', sans-serif; text-decoration: none;" href="#">
                <div class="brand-icon pulse-glow" style="width: 52px; height: 52px; background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary)); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin-right: 16px; position: relative; overflow: hidden; transition: all 0.3s ease;">
                    <i class="bi bi-check-circle-fill" style="color: white; font-size: 26px; z-index: 2; transition: all 0.3s ease;"></i>
                    <div class="floating-orb" style="width: 20px; height: 20px; background: rgba(255,255,255,0.2); top: 10%; left: 20%; animation-delay: 0s;"></div>
                    <div class="floating-orb" style="width: 15px; height: 15px; background: rgba(255,255,255,0.1); bottom: 15%; right: 15%; animation-delay: 2s;"></div>
                </div>
                <div class="brand-text">
                    <div class="gradient-text brand-title" style="font-size: 1.9rem; font-weight: 800; letter-spacing: -0.5px; line-height: 1; margin-bottom: 2px; transition: all 0.3s ease;">TaskFlow</div>
                </div>
            </a>

            <!-- Mobile Toggle Button -->
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" style="padding: 10px; border-radius: 12px; background: var(--bg-card);">
                <span class="navbar-toggler-icon" style="filter: invert(1);"></span>
            </button>

            <!-- Navigation Content -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- Center Navigation Items -->
                <div class="navbar-nav mx-auto d-flex align-items-center gap-2">
                    <!-- Quick Stats -->
                    <div class="nav-item d-flex align-items-center glassmorphism hover-lift nav-stats" style="border-radius: 16px; padding: 12px 20px; margin: 0 12px; transition: all 0.3s ease;">
                        <div class="d-flex align-items-center gap-4">
                            <div class="text-center">
                                <div id="nav-total-tasks" style="font-size: 1.3rem; font-weight: 800; color: #60a5fa; line-height: 1; transition: all 0.3s ease;">Loading...</div>
                                <div style="font-size: 0.7rem; color: #cbd5e0; font-weight: 600; text-transform: uppercase; letter-spacing: 0.8px;">Tasks</div>
                            </div>
                            <div class="text-center">
                                <div id="nav-active-projects" style="font-size: 1.3rem; font-weight: 800; color: #93c5fd; line-height: 1; transition: all 0.3s ease;">Loading...</div>
                                <div style="font-size: 0.7rem; color: #cbd5e0; font-weight: 600; text-transform: uppercase; letter-spacing: 0.8px;">Projects</div>
                            </div>
                            <div class="text-center">
                                <div id="nav-due-today" style="font-size: 1.3rem; font-weight: 800; color: #fbbf24; line-height: 1; transition: all 0.3s ease;">Loading...</div>
                                <div style="font-size: 0.7rem; color: #cbd5e0; font-weight: 600; text-transform: uppercase; letter-spacing: 0.8px;">Due Today</div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="nav-item">
                        <button class="btn d-flex align-items-center hover-lift quick-add-btn" onclick="document.getElementById('tasks-tab').click(); setTimeout(() => document.getElementById('taskTitle').focus(), 100);" 
                                style="background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary)); color: white; border: none; border-radius: 12px; padding: 10px 18px; font-size: 0.9rem; font-weight: 600; box-shadow: 0 4px 15px var(--glow-primary); transition: all 0.3s ease;"
                                onmouseover="this.style.transform='translateY(-2px) scale(1.05)'; this.style.boxShadow='0 8px 25px var(--glow-primary)'"
                                onmouseout="this.style.transform='translateY(0) scale(1)'; this.style.boxShadow='0 4px 15px var(--glow-primary)'">
                            <i class="bi bi-plus-circle me-2" style="font-size: 1rem;"></i>
                            Quick Add
                        </button>
                    </div>
                </div>

                <!-- Right Side User Section -->
                <div class="navbar-nav d-flex align-items-center gap-3">
                    <!-- User Profile Dropdown -->
                    <div class="nav-item dropdown">
                        <button class="btn dropdown-toggle d-flex align-items-center glassmorphism hover-lift" id="userDropdown" data-bs-toggle="dropdown" 
                                style="border-radius: 16px; padding: 10px 16px; color: white; font-weight: 600; border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 4px 15px var(--shadow-light);"
                                onmouseover="this.style.boxShadow='0 8px 25px var(--shadow-medium)'; this.style.transform='translateY(-1px)'"
                                onmouseout="this.style.boxShadow='0 4px 15px var(--shadow-light)'; this.style.transform='translateY(0)'">
                            <!-- User Avatar -->
                            <div class="user-avatar" style="width: 36px; height: 36px; background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary)); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-right: 12px; color: white; font-weight: 700; font-size: 1rem; box-shadow: 0 4px 15px var(--glow-primary); transition: all 0.3s ease;">
                                <?php echo strtoupper(substr($username, 0, 1)); ?>
                            </div>
                            <!-- User Info -->
                            <div class="text-start">
                                <div style="font-size: 0.95rem; font-weight: 700; line-height: 1.1; color: white;"><?php echo htmlspecialchars($username); ?></div>
                                <div style="font-size: 0.75rem; color: #cbd5e0; font-weight: 500;">User Account</div>
                            </div>
                        </button>
                        
                        <!-- Dropdown Menu -->
                        <ul class="dropdown-menu dropdown-menu-end glassmorphism" style="border-radius: 16px; border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 20px 40px var(--shadow-heavy); min-width: 250px; padding: 12px; margin-top: 8px;">
                            <!-- User Info Header -->
                            <li class="px-3 py-3" style="border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 12px; border-radius: 12px; background: rgba(45,55,72,0.5);">
                                <div class="d-flex align-items-center">
                                    <div style="width: 48px; height: 48px; background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary)); border-radius: 14px; display: flex; align-items: center; justify-content: center; margin-right: 14px; color: white; font-weight: 700; font-size: 1.2rem; box-shadow: 0 4px 15px var(--glow-primary);">
                                        <?php echo strtoupper(substr($username, 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div style="font-weight: 700; color: white; font-size: 1rem;"><?php echo htmlspecialchars($username); ?></div>
                                        <div style="color: #cbd5e0; font-size: 0.85rem;">Welcome back!</div>
                                    </div>
                                </div>
                            </li>
                            
                            <!-- Menu Items -->
                            <li>
                                <a class="dropdown-item d-flex align-items-center hover-lift" href="user_profile.php" 
                                   style="border-radius: 12px; padding: 12px 16px; color: white; font-weight: 500; background: transparent; margin-bottom: 4px;"
                                   onmouseover="this.style.background='rgba(49,130,206,0.2)'; this.style.color='#60a5fa'"
                                   onmouseout="this.style.background='transparent'; this.style.color='white'">
                                    <i class="bi bi-person-circle me-3" style="font-size: 1.1rem; color: var(--accent-primary);"></i>
                                    My Profile
                                </a>
                            </li>
                            
                            <li><hr class="dropdown-divider" style="margin: 12px 0; border-color: rgba(255,255,255,0.1);"></li>
                            
                            <li>
                                <a class="dropdown-item d-flex align-items-center hover-lift" href="user_logout.php" id="logoutLink"
                                   style="border-radius: 12px; padding: 12px 16px; color: #fca5a5; font-weight: 500; background: transparent;"
                                   onmouseover="this.style.background='rgba(239, 68, 68, 0.1)'; this.style.color='#f87171'"
                                   onmouseout="this.style.background='transparent'; this.style.color='#fca5a5'">
                                    <i class="bi bi-box-arrow-right me-3" style="font-size: 1.1rem;"></i>
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
    <main class="container pb-2" style="max-width: 1400px; margin: 10px auto 0 auto; background: #f8f6f0; border-radius: 24px; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08); padding: 48px 40px 40px 40px;">        <!-- Header Section -->
        <section class="mb-5 text-center" style="position: relative; padding: 32px 24px; background: linear-gradient(135deg, var(--accent-primary) 0%, var(--accent-secondary) 50%, #4338ca 100%); border-radius: 16px; box-shadow: 0 12px 24px var(--glow-primary), 0 4px 8px rgba(0,0,0,0.3); margin-bottom: 32px; overflow: hidden;">
            <!-- Animated background elements -->
            <div style="position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: radial-gradient(circle at 25% 25%, rgba(255,255,255,0.08) 0%, transparent 50%), radial-gradient(circle at 75% 75%, rgba(255,255,255,0.05) 0%, transparent 50%); animation: float 12s ease-in-out infinite; pointer-events: none;"></div>
            
            <!-- Subtle decorative elements -->
            <div style="position: absolute; top: 15%; right: 12%; width: 40px; height: 40px; background: radial-gradient(circle, rgba(255,255,255,0.15), transparent); border-radius: 50%; animation: pulse 4s ease-in-out infinite;"></div>
            <div style="position: absolute; bottom: 20%; left: 15%; width: 30px; height: 30px; background: radial-gradient(circle, rgba(255,255,255,0.1), transparent); border-radius: 50%; animation: pulse 5s ease-in-out infinite 1s;"></div>
            
            <!-- Main content -->
            <div style="position: relative; z-index: 2;">
                <h1 class="fw-bold" style="font-family: 'Inter', 'Segoe UI', sans-serif; background: linear-gradient(135deg, #ffffff 0%, #fdfdfb 50%, #f8f6f3 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; font-size: 2.6rem; margin-bottom: 12px; letter-spacing: -0.5px; line-height: 1.1; font-weight: 800; text-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    TaskFlow Dashboard
                </h1>
                
                <p class="lead" style="color: rgba(255,255,255,0.95); font-family: 'Inter', 'Segoe UI', sans-serif; font-size: 1.1rem; margin: 0; font-weight: 500; line-height: 1.4; text-shadow: 0 1px 2px rgba(0,0,0,0.2);">
                    Stay organized and boost your productivity
                </p>
                
                <!-- Feature highlights -->
                <div style="display: flex; justify-content: center; gap: 24px; margin-top: 24px; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 6px; color: rgba(255,255,255,0.9); font-size: 0.85rem; font-weight: 600;">
                        <div style="width: 28px; height: 28px; background: rgba(255,255,255,0.15); border-radius: 50%; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
                            <i class="bi bi-lightning-fill" style="font-size: 12px;"></i>
                        </div>
                        <span>Fast</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 6px; color: rgba(255,255,255,0.9); font-size: 0.85rem; font-weight: 600;">
                        <div style="width: 28px; height: 28px; background: rgba(255,255,255,0.15); border-radius: 50%; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
                            <i class="bi bi-check-circle-fill" style="font-size: 12px;"></i>
                        </div>
                        <span>Organized</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 6px; color: rgba(255,255,255,0.9); font-size: 0.85rem; font-weight: 600;">
                        <div style="width: 28px; height: 28px; background: rgba(255,255,255,0.15); border-radius: 50%; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
                            <i class="bi bi-people-fill" style="font-size: 12px;"></i>
                        </div>
                        <span>Collaborative</span>
                    </div>
                </div>
            </div>
            
            <!-- CSS Animations -->
            <style>
                @keyframes float {
                    0%, 100% { transform: translateY(0px) rotate(0deg); }
                    50% { transform: translateY(-8px) rotate(0.5deg); }
                }
                
                @keyframes pulse {
                    0%, 100% { opacity: 0.2; transform: scale(1); }
                    50% { opacity: 0.4; transform: scale(1.05); }
                }
                
                /* Responsive adjustments */
                @media (max-width: 768px) {
                    h1 { font-size: 2.2rem !important; }
                    .lead { font-size: 1rem !important; }
                }
                
                @media (max-width: 576px) {
                    h1 { font-size: 1.8rem !important; }
                    .lead { font-size: 0.95rem !important; }
                }
            </style>
        </section>

        <!-- Tabs Navigation -->
    <style>
        .nav-tab-custom {
            border-radius: 12px 12px 0 0 !important;
            margin: 0 4px;
            padding: 12px 20px !important;
            font-weight: 500;
            transition: all 0.2s ease !important;
            border: 1px solid transparent !important;
            background: transparent !important;
            color: var(--text-secondary) !important;
        }
        .nav-tab-custom:not(.active):hover {
            background: rgba(49, 130, 206, 0.1) !important;
            border-color: var(--accent-primary) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 12px var(--glow-primary) !important;
            color: var(--accent-primary) !important;
        }
        .nav-tab-custom.active {
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary)) !important;
            border-color: var(--accent-primary) !important;
            color: white !important;
            box-shadow: 0 4px 12px var(--glow-primary) !important;
        }
    </style>
    <ul class="nav nav-tabs mb-4 justify-content-center" id="dashboardTabs" role="tablist" style="border-bottom: 2px solid var(--border-primary); gap: 8px;">
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
                    <span class="badge bg-danger ms-1" style="display: none;">0</span>
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
    <div class="tab-content" id="dashboardTabContent" style="margin-top: 24px;">
            <!-- My Day Tab -->
            <div class="tab-pane fade show active" id="myday" role="tabpanel">
                <div class="card shadow-sm" style="border-radius: 16px; background: white; border: 1px solid var(--border-primary);">
                    <div class="card-header bg-light" style="background: linear-gradient(135deg, #f8fafc, #e2e8f0) !important; border-radius: 16px 16px 0 0; border-bottom: 1px solid var(--border-primary);">
                        <h5 class="card-title mb-0" style="color: var(--text-primary);"><i class="bi bi-sun me-2" style="color: var(--accent-primary);"></i>Today</h5>
                    </div>
                    <div class="card-body">
                        <div id="todayTasks">
                            <p class="text-muted" style="color: var(--text-muted) !important;">No tasks due today. Add due dates in Tasks to see them here.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tasks Tab -->
            <div class="tab-pane fade" id="tasks" role="tabpanel">
                <!-- Add Task Form -->
                <div class="card shadow-sm mb-4" style="border-radius: 16px; background: white; border: 1px solid var(--border-primary);">
    <div class="card-header" style="background: linear-gradient(135deg, #f8fafc, #e2e8f0); border-radius: 16px 16px 0 0; border-bottom: 1px solid var(--border-primary); padding: 20px 24px;">
        <h5 class="card-title mb-0" style="color: var(--text-primary); font-weight: 600; display: flex; align-items: center;">
            <div style="width: 40px; height: 40px; background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary)); border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-right: 12px;">
                <i class="bi bi-plus-circle" style="color: white; font-size: 18px;"></i>
            </div>
            Create New Task
        </h5>
        <p class="mb-0 mt-2" style="color: var(--text-secondary); font-size: 14px;">Add a new task to your workflow and stay organized</p>
    </div>
    <div class="card-body" style="padding: 24px;">
        <form id="addTaskForm">
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="form-group">
                        <label for="taskTitle" class="form-label" style="color: var(--text-primary); font-weight: 600; margin-bottom: 8px; display: flex; align-items: center;">
                            <i class="bi bi-pencil me-2" style="color: var(--accent-primary);"></i>Task Title
                        </label>
                        <input type="text" class="form-control" id="taskTitle" 
                               placeholder="e.g., Prepare sprint board, Review documentation..." 
                               required
                               style="border: 2px solid var(--border-primary); border-radius: 10px; padding: 12px 16px; font-size: 14px; transition: all 0.3s ease; background: white; color: var(--text-primary);"
                               onfocus="this.style.borderColor='var(--accent-primary)'; this.style.boxShadow='0 0 0 3px var(--glow-primary)'"
                               onblur="this.style.borderColor='var(--border-primary)'; this.style.boxShadow='none'">
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="form-group">
                        <label for="taskDueDate" class="form-label" style="color: var(--text-primary); font-weight: 600; margin-bottom: 8px; display: flex; align-items: center;">
                            <i class="bi bi-calendar3 me-2" style="color: var(--accent-primary);"></i>Due Date
                        </label>
                        <input type="date" class="form-control" id="taskDueDate" 
                               min="<?php echo date('Y-m-d'); ?>"
                               style="border: 2px solid var(--border-primary); border-radius: 10px; padding: 12px 16px; font-size: 14px; transition: all 0.3s ease; background: white; color: var(--text-primary);"
                               onfocus="this.style.borderColor='var(--accent-primary)'; this.style.boxShadow='0 0 0 3px var(--glow-primary)'"
                               onblur="this.style.borderColor='var(--border-primary)'; this.style.boxShadow='none'">
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="form-group">
                        <label for="taskProject" class="form-label" style="color: var(--text-primary); font-weight: 600; margin-bottom: 8px; display: flex; align-items: center;">
                            <i class="bi bi-folder me-2" style="color: var(--accent-primary);"></i>Project
                        </label>
                        <select class="form-select" id="taskProject"
                                style="border: 2px solid var(--border-primary); border-radius: 10px; padding: 12px 16px; font-size: 14px; transition: all 0.3s ease; background: white; color: var(--text-primary);"
                                onfocus="this.style.borderColor='var(--accent-primary)'; this.style.boxShadow='0 0 0 3px var(--glow-primary)'"
                                onblur="this.style.borderColor='var(--border-primary)'; this.style.boxShadow='none'">
                            <option value="">No project (Personal task)</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="task-tips" style="display: flex; align-items: center; color: var(--text-secondary); font-size: 13px;">
                            <i class="bi bi-lightbulb me-2" style="color: var(--accent-warning);"></i>
                            <span>Pro tip: Add due dates to see tasks in your calendar and daily view</span>
                        </div>
                        <button type="submit" class="btn" 
                                style="background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary)); color: white; border: none; border-radius: 12px; padding: 12px 24px; font-weight: 600; font-size: 14px; transition: all 0.3s ease; display: flex; align-items: center; box-shadow: 0 2px 8px var(--glow-primary);"
                                onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 16px var(--glow-primary)'"
                                onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px var(--glow-primary)'">
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
                <div class="card shadow-sm" style="border-radius: 16px; background: white; border: 1px solid var(--border-primary);">
                    <div class="card-header bg-light" style="background: linear-gradient(135deg, #f8fafc, #e2e8f0) !important; border-radius: 16px 16px 0 0; border-bottom: 1px solid var(--border-primary);">
                        <h5 class="card-title mb-0" style="color: var(--text-primary);"><i class="bi bi-list-task me-2" style="color: var(--accent-primary);"></i>All Tasks</h5>
                    </div>
                    <div class="card-body">
                        <div id="allTasks" class="tasks-tab">
                            <p class="text-muted" style="color: var(--text-muted) !important;">No tasks yet. Add your first task above.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Projects Tab -->
            <div class="tab-pane fade" id="projects" role="tabpanel">
                <div class="row g-4">
                    <div class="col-md-5">
                        <!-- Projects List -->
                        <div class="card shadow-sm" style="border-radius: 16px; background: white; border: 1px solid var(--border-primary);">
                            <div class="card-header bg-light" style="background: linear-gradient(135deg, #f8fafc, #e2e8f0) !important; border-radius: 16px 16px 0 0; padding: 20px 24px; border-bottom: 1px solid var(--border-primary);">
                                <h5 class="card-title mb-0" style="color: var(--text-primary); font-size: 1.4rem;"><i class="bi bi-folder me-2" style="color: var(--accent-primary);"></i>Projects</h5>
                            </div>
                            <div class="card-body" style="padding: 28px 24px; min-height: 500px;">
                                <form id="addProjectForm" class="mb-4">
                                    <div class="input-group" style="height: 48px;">
                                        <input type="text" class="form-control" id="projectName" placeholder="New project name" required 
                                               style="font-size: 1.1rem; padding: 12px 16px; border: 2px solid var(--border-primary); background: white; color: var(--text-primary); border-radius: 12px 0 0 12px;"
                                               onfocus="this.style.borderColor='var(--accent-primary)'; this.style.boxShadow='0 0 0 3px var(--glow-primary)'"
                                               onblur="this.style.borderColor='var(--border-primary)'; this.style.boxShadow='none'">
                                        <button type="submit" class="btn" style="background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary)); color: white; border: none; border-radius: 0 12px 12px 0; font-weight: 700; font-size: 1rem; padding: 12px 20px; transition: all 0.3s ease; box-shadow: 0 2px 8px var(--glow-primary);"
                                                onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 16px var(--glow-primary)'"
                                                onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px var(--glow-primary)'">
                                            <i class="bi bi-plus-circle me-2"></i>Add
                                        </button>
                                    </div>
                                </form>
                                <div id="projectsList" class="project-item" style="font-size: 1.1rem;">
                                    <!-- Projects will be dynamically loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <!-- Project Details -->
                        <div class="card shadow-sm" style="border-radius: 16px; background: white; border: 1px solid var(--border-primary);">
                            <div class="card-header bg-light" style="background: linear-gradient(135deg, #f8fafc, #e2e8f0) !important; border-radius: 16px 16px 0 0; padding: 20px 24px; border-bottom: 1px solid var(--border-primary);">
                                <h5 class="card-title mb-0" style="color: var(--text-primary); font-size: 1.4rem;"><i class="bi bi-people me-2" style="color: var(--accent-primary);"></i>Team & Tasks</h5>
                            </div>
                            <div class="card-body" style="padding: 28px 24px; min-height: 500px;">
                                <div id="projectDetails" style="font-size: 1.1rem;">
                                    <p class="text-muted" style="color: var(--text-muted) !important; font-size: 1.1rem;">Select or create a project to manage members and tasks.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Team Tab -->
            <div class="tab-pane fade" id="team" role="tabpanel">
                <div class="card shadow-sm" style="border-radius: 16px; background: white; border: 1px solid var(--border-primary);">
                    <div class="card-header bg-light" style="background: linear-gradient(135deg, #f8fafc, #e2e8f0) !important; border-radius: 16px 16px 0 0; padding: 20px 24px; border-bottom: 1px solid var(--border-primary);">
                        <h5 class="card-title mb-0" style="color: var(--text-primary); font-size: 1.4rem;"><i class="bi bi-people me-2" style="color: var(--accent-primary);"></i>Team Overview</h5>
                    </div>
                    <div class="card-body" style="padding: 28px 24px; min-height: 500px;">
                        <div id="teamOverview" style="font-size: 1.1rem;">
                            <p class="text-muted" style="color: var(--text-muted) !important; font-size: 1.1rem;">No team members yet. Add some under Projects.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Calendar Tab -->
            <div class="tab-pane fade" id="calendar" role="tabpanel">
                <div class="card shadow-sm" style="border-radius: 16px; background: white; border: 1px solid var(--border-primary);">
                    <div class="card-header bg-light" style="background: linear-gradient(135deg, #f8fafc, #e2e8f0) !important; border-radius: 16px 16px 0 0; padding: 20px 24px; border-bottom: 1px solid var(--border-primary);">
                        <h5 class="card-title mb-0" style="color: var(--text-primary); font-size: 1.4rem;"><i class="bi bi-calendar me-2" style="color: var(--accent-primary);"></i>Upcoming Tasks</h5>
                    </div>
                    <div class="card-body" style="padding: 28px 24px; min-height: 500px;">
                        <div id="upcomingTasks" style="font-size: 1.1rem;">
                             <p class="text-muted" style="color: var(--text-muted) !important; font-size: 1.1rem;">Loading upcoming tasks...</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="missed" role="tabpanel" aria-labelledby="missed-tab">
                <div class="card shadow-sm" style="border-radius: 16px; background: white; border: 1px solid var(--border-primary);">
                    <div class="card-header bg-light" style="background: linear-gradient(135deg, #f8fafc, #e2e8f0) !important; border-radius: 16px 16px 0 0; padding: 20px 24px; border-bottom: 1px solid var(--border-primary);">
                        <h5 class="card-title mb-0" style="color: var(--text-primary); font-size: 1.4rem;"><i class="bi bi-exclamation-triangle me-2" style="color: var(--accent-warning);"></i>Missed Tasks</h5>
                    </div>
                    <div class="card-body" style="padding: 28px 24px; min-height: 500px;">
                        <div id="missed-tasks-content" style="font-size: 1.1rem;">
                            <div class="text-center py-4">
                                <div class="spinner-border" style="color: var(--accent-primary);" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2" style="color: var(--text-muted); font-size: 1.1rem;">Loading missed tasks...</p>
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