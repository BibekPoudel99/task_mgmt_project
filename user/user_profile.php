<?php
// user_profile.php
// User profile page with dynamic data loading
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: user_login.php');
    exit();
}

// Generate CSRF token for API requests
require_once '../library/Session.php';
if (!Session::get('csrf_token')) {
    Session::put('csrf_token', md5(uniqid()));
}
$csrfToken = Session::get('csrf_token');

$username = $_SESSION['username'] ?? 'User';
$email = $_SESSION['email'] ?? 'Loading...';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - TaskFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/user_style.css">
    <style>
        .loading-shimmer {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
        }
        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
        .stat-number {
            transition: all 0.3s ease;
        }
    </style>
</head>
<body style="background-color: #f7f4ef; margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; min-height: 100vh;">
    <!-- Hidden CSRF Token for API requests -->
    <input type="hidden" id="csrfTokenInput" value="<?php echo htmlspecialchars($csrfToken); ?>">
    
    <!-- Navbar -->
    <nav style="background: linear-gradient(135deg, #f7f4ef 0%, #f2eee4 100%); border-bottom: 2px solid #e5ddc8; padding: 16px 0; position: sticky; top: 0; z-index: 1000; box-shadow: 0 2px 12px rgba(124,132,113,0.1);">
        <div style="max-width: 1200px; margin: 0 auto; padding: 0 16px; display: flex; justify-content: center; align-items: center;">
            <a href="user_dashboard.php" style="display: inline-flex; align-items: center; padding: 12px 32px; background: linear-gradient(135deg, #3182ce 0%, #4299e1 100%); color: #ffffff; text-decoration: none; border-radius: 16px; font-weight: 600; font-size: 1.1rem; transition: all 0.3s ease; box-shadow: 0 4px 16px rgba(49,130,206,0.25); border: 2px solid transparent;" onmouseover="this.style.transform='translateY(-2px) scale(1.02)'; this.style.boxShadow='0 8px 24px rgba(49,130,206,0.35)'; this.style.borderColor='rgba(255,255,255,0.2)'" onmouseout="this.style.transform='translateY(0) scale(1)'; this.style.boxShadow='0 4px 16px rgba(49,130,206,0.25)'; this.style.borderColor='transparent'">
                <i class="bi bi-house-door-fill" style="margin-right: 10px; font-size: 1.2rem;"></i>
                <span>Dashboard</span>
                <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: radial-gradient(circle at 30% 30%, rgba(255,255,255,0.2) 0%, transparent 70%); border-radius: 16px; pointer-events: none;"></div>
            </a>
        </div>
    </nav>

    <!-- Main Content -->
    <main style="max-width: 960px; margin: 24px auto; padding: 0 16px;">
        <!-- Profile Header -->
        <div style="margin-bottom: 24px;">
            <div style="background: linear-gradient(135deg, #3182ce 0%, #4299e1 100%); border-radius: 16px; padding: 32px 24px; color: #ffffff; position: relative; overflow: hidden; transition: all 0.3s ease;">
                <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: radial-gradient(circle at 20% 20%, rgba(255,255,255,0.15) 0%, transparent 50%), radial-gradient(circle at 80% 80%, rgba(255,255,255,0.1) 0%, transparent 50%); opacity: 0.7;"></div>
                <div style="position: relative; text-align: center;">
                    <!-- Profile Avatar -->
                    <div style="display: inline-block; margin-bottom: 16px; transition: transform 0.3s ease;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                        <div style="width: 80px; height: 80px; background: rgba(255,255,255,0.25); border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 3px solid rgba(255,255,255,0.4); backdrop-filter: blur(12px);">
                            <i class="bi bi-person-fill" style="font-size: 2.5rem; color: rgba(255,255,255,0.95);"></i>
                        </div>
                        <div style="position: absolute; bottom: 4px; right: 4px; width: 18px; height: 18px; background: #28a745; border-radius: 50%; border: 2px solid #ffffff;"></div>
                    </div>
                    <h3 style="margin: 0 0 8px; font-weight: 600; font-size: 1.5rem;"><?php echo htmlspecialchars($username); ?></h3>
                    <p style="margin: 0; color: rgba(255,255,255,0.85); font-size: 0.95rem;">TaskFlow Member</p>
                </div>
            </div>
        </div>

        <!-- Profile Information and Quick Stats -->
        <div style="display: flex; flex-wrap: wrap; gap: 24px; margin-bottom: 24px;">
            <!-- Profile Information -->
            <div style="flex: 1; min-width: 300px;">
                <div style="background: #ffffff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); overflow: hidden; transition: transform 0.3s ease;" onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform='translateY(0)'">
                    <div style="padding: 16px 20px; border-bottom: 1px solid #f0f0f0;">
                        <h6 style="margin: 0; color: #7c8471; font-weight: 600; font-size: 1rem;">
                            <i class="bi bi-person-lines-fill" style="margin-right: 8px;"></i>Profile Information
                        </h6>
                    </div>
                    <div style="padding: 20px; display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px;">
                        <div style="background: #f8f9fa; border-radius: 8px; padding: 12px; border-left: 4px solid #7c8471; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                            <div style="display: flex; align-items: center;">
                                <i class="bi bi-person" style="color: #7c8471; font-size: 1.1rem; margin-right: 8px;"></i>
                                <div>
                                    <small style="color: #6c757d; font-size: 0.75rem; display: block;">Username</small>
                                    <span style="font-weight: 500; font-size: 0.9rem;" id="profile-username"><?php echo htmlspecialchars($username); ?></span>
                                </div>
                            </div>
                        </div>
                        <div style="background: #f8f9fa; border-radius: 8px; padding: 12px; border-left: 4px solid #7c8471; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                            <div style="display: flex; align-items: center;">
                                <i class="bi bi-calendar-check" style="color: #7c8471; font-size: 1.1rem; margin-right: 8px;"></i>
                                <div>
                                    <small style="color: #6c757d; font-size: 0.75rem; display: block;">Member Since</small>
                                    <span style="font-weight: 500; font-size: 0.9rem;" id="profile-member-since">
                                        <span class="loading-shimmer" style="display: inline-block; width: 80px; height: 16px; border-radius: 4px;">Loading...</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div style="background: #f8f9fa; border-radius: 8px; padding: 12px; border-left: 4px solid #7c8471; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                            <div style="display: flex; align-items: center;">
                                <i class="bi bi-shield-check" style="color: #7c8471; font-size: 1.1rem; margin-right: 8px;"></i>
                                <div>
                                    <small style="color: #6c757d; font-size: 0.75rem; display: block;">Account Status</small>
                                    <span style="font-weight: 500; font-size: 0.9rem; color: #28a745;" id="profile-status">
                                        <span class="loading-shimmer" style="display: inline-block; width: 60px; height: 16px; border-radius: 4px;">Loading...</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div style="flex: 1; min-width: 300px;">
                <div style="background: #ffffff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); overflow: hidden; transition: transform 0.3s ease;" onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform='translateY(0)'">
                    <div style="padding: 16px 20px; border-bottom: 1px solid #f0f0f0;">
                        <h6 style="margin: 0; color: #7c8471; font-weight: 600; font-size: 1rem;">
                            <i class="bi bi-bar-chart-fill" style="margin-right: 8px;"></i>Quick Stats
                        </h6>
                    </div>
                    <div style="padding: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding: 12px; background: linear-gradient(135deg, #e3f2fd, #f3e5f5); border-radius: 8px;">
                            <div>
                                <div style="color: #7c8471; font-weight: 700; font-size: 1.25rem; margin-bottom: 4px;" class="stat-number" id="stat-total-tasks">
                                    <span class="loading-shimmer" style="display: inline-block; width: 30px; height: 20px; border-radius: 4px;">0</span>
                                </div>
                                <small style="color: #6c757d; font-size: 0.75rem;">Total Tasks</small>
                            </div>
                            <i class="bi bi-list-check" style="color: #7c8471; font-size: 1.5rem; opacity: 0.7;"></i>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding: 12px; background: linear-gradient(135deg, #e8f5e8, #f0f8f0); border-radius: 8px;">
                            <div>
                                <div style="color: #28a745; font-weight: 700; font-size: 1.25rem; margin-bottom: 4px;" class="stat-number" id="stat-completed-tasks">
                                    <span class="loading-shimmer" style="display: inline-block; width: 30px; height: 20px; border-radius: 4px;">0</span>
                                </div>
                                <small style="color: #6c757d; font-size: 0.75rem;">Completed</small>
                            </div>
                            <i class="bi bi-check-circle" style="color: #28a745; font-size: 1.5rem; opacity: 0.7;"></i>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding: 12px; background: linear-gradient(135deg, #fff3e0, #fef7ed); border-radius: 8px;">
                            <div>
                                <div style="color: #ffc107; font-weight: 700; font-size: 1.25rem; margin-bottom: 4px;" class="stat-number" id="stat-active-tasks">
                                    <span class="loading-shimmer" style="display: inline-block; width: 30px; height: 20px; border-radius: 4px;">0</span>
                                </div>
                                <small style="color: #6c757d; font-size: 0.75rem;">Active</small>
                            </div>
                            <i class="bi bi-clock" style="color: #ffc107; font-size: 1.5rem; opacity: 0.7;"></i>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding: 12px; background: linear-gradient(135deg, #f3e5f5, #e8eaf6); border-radius: 8px;">
                            <div>
                                <div style="color: #6f42c1; font-weight: 700; font-size: 1.25rem; margin-bottom: 4px;" class="stat-number" id="stat-total-projects">
                                    <span class="loading-shimmer" style="display: inline-block; width: 30px; height: 20px; border-radius: 4px;">0</span>
                                </div>
                                <small style="color: #6c757d; font-size: 0.75rem;">Total Projects</small>
                            </div>
                            <i class="bi bi-folder" style="color: #6f42c1; font-size: 1.5rem; opacity: 0.7;"></i>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: linear-gradient(135deg, #ffebee, #fef2f2); border-radius: 8px;">
                            <div>
                                <div style="color: #dc3545; font-weight: 700; font-size: 1.25rem; margin-bottom: 4px;" class="stat-number" id="stat-due-today">
                                    <span class="loading-shimmer" style="display: inline-block; width: 30px; height: 20px; border-radius: 4px;">0</span>
                                </div>
                                <small style="color: #6c757d; font-size: 0.75rem;">Due Today</small>
                            </div>
                            <i class="bi bi-calendar-event" style="color: #dc3545; font-size: 1.5rem; opacity: 0.7;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div style="margin-bottom: 24px;">
            <div style="background: #ffffff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); overflow: hidden; transition: transform 0.3s ease;" onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform='translateY(0)'">
                <div style="padding: 16px 20px; border-bottom: 1px solid #f0f0f0;">
                    <h6 style="margin: 0; color: #7c8471; font-weight: 600; font-size: 1rem;">
                        <i class="bi bi-clock-history" style="margin-right: 8px;"></i>Recent Activity
                    </h6>
                </div>
                <div style="padding: 20px;" id="recent-activity-container">
                    <div style="text-align: center;">
                        <div class="loading-shimmer" style="width: 60px; height: 60px; border-radius: 50%; margin: 0 auto 16px;"></div>
                        <p style="margin: 12px 0 4px; color: #6c757d; font-size: 0.95rem;">Loading recent activity...</p>
                        <small style="color: #6c757d; font-size: 0.8rem;">Please wait while we fetch your data</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
            <button onclick="alert('Profile editing feature coming soon!')" style="display: flex; align-items: center; justify-content: center; padding: 14px 24px; background: transparent; border: 2px solid #7c8471; color: #7c8471; border-radius: 10px; font-weight: 500; font-size: 0.95rem; cursor: pointer; transition: all 0.3s ease;" onmouseover="this.style.backgroundColor='#7c8471'; this.style.color='#ffffff'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(124,132,113,0.3)'" onmouseout="this.style.backgroundColor='transparent'; this.style.color='#7c8471'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                <i class="bi bi-pencil" style="margin-right: 8px; font-size: 1.1rem;"></i>Edit Profile
            </button>
            <button onclick="window.location.href='../user_api/tasks.php?export=true'" style="display: flex; align-items: center; justify-content: center; padding: 14px 24px; background: transparent; border: 2px solid #17a2b8; color: #17a2b8; border-radius: 10px; font-weight: 500; font-size: 0.95rem; cursor: pointer; transition: all 0.3s ease;" onmouseover="this.style.backgroundColor='#17a2b8'; this.style.color='#ffffff'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(23,162,184,0.3)'" onmouseout="this.style.backgroundColor='transparent'; this.style.color='#17a2b8'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                <i class="bi bi-download" style="margin-right: 8px; font-size: 1.1rem;"></i>Export Data
            </button>
            <a href="#" onclick="confirmLogout(event)" style="display: flex; align-items: center; justify-content: center; padding: 14px 24px; background: transparent; border: 2px solid #dc3545; color: #dc3545; text-decoration: none; border-radius: 10px; font-weight: 500; font-size: 0.95rem; transition: all 0.3s ease;" onmouseover="this.style.backgroundColor='#dc3545'; this.style.color='#ffffff'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(220,53,69,0.3)'" onmouseout="this.style.backgroundColor='transparent'; this.style.color='#dc3545'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                <i class="bi bi-box-arrow-right" style="margin-right: 8px; font-size: 1.1rem;"></i>Logout
            </a>
        </div>
    </main>

    <!-- Responsive Adjustments -->
    <style>
        @media (max-width: 768px) {
            body { font-size: 0.95rem; }
            main { padding: 0 12px; }
            div[style*="max-width: 960px"] { max-width: 100%; }
            div[style*="padding: 32px 24px"] { padding: 24px 16px; }
            h3[style*="font-size: 1.5rem"] { font-size: 1.3rem; }
            div[style*="width: 80px"] { width: 64px; height: 64px; }
            i[style*="font-size: 2.5rem"] { font-size: 2rem; }
            div[style*="padding: 10px 24px"] { padding: 8px 16px; font-size: 0.9rem; }
        }
        @media (max-width: 576px) {
            div[style*="padding: 16px 20px"] { padding: 12px 16px; }
            div[style*="padding: 20px"] { padding: 16px; }
            h6[style*="font-size: 1rem"] { font-size: 0.9rem; }
            div[style*="min-width: 300px"] { min-width: 100%; }
            div[style*="grid-template-columns"] { grid-template-columns: 1fr; }
        }
    </style>

    <script>
        // Profile data loader
        class ProfileLoader {
            constructor() {
                this.loadProfileData();
            }

            getCsrfToken() {
                const input = document.getElementById('csrfTokenInput');
                return input ? input.value : '';
            }

            async loadProfileData() {
                try {
                    // Show loading state
                    this.showLoadingState();

                    const response = await fetch('../user_api/profile.php', {
                        method: 'GET',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': this.getCsrfToken()
                        }
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const data = await response.json();
                    
                    if (data.success) {
                        this.updateProfileInfo(data.user);
                        this.updateStats(data.stats);
                        this.updateRecentActivity(data.recent_activity);
                        this.showSuccessState();
                    } else {
                        console.error('Failed to load profile data:', data.message);
                        this.showError('Failed to load profile data: ' + (data.message || 'Unknown error'));
                    }
                } catch (error) {
                    console.error('Error loading profile data:', error);
                    this.showError('Network error while loading profile data. Please check your connection and try again.');
                }
            }

            showLoadingState() {
                // This method can be used to show additional loading indicators if needed
                console.log('Loading profile data...');
            }

            showSuccessState() {
                // This method can be used to show success indicators if needed
                console.log('Profile data loaded successfully');
            }

            updateProfileInfo(user) {
                // Update member since
                const memberSinceEl = document.getElementById('profile-member-since');
                if (memberSinceEl) {
                    memberSinceEl.innerHTML = user.member_since;
                }

                // Update status
                const statusEl = document.getElementById('profile-status');
                if (statusEl) {
                    statusEl.innerHTML = user.status;
                    statusEl.style.color = user.status === 'Active' ? '#28a745' : '#dc3545';
                }
            }

            updateStats(stats) {
                this.animateNumber('stat-total-tasks', stats.total_tasks);
                this.animateNumber('stat-completed-tasks', stats.completed_tasks);
                this.animateNumber('stat-active-tasks', stats.active_tasks);
                this.animateNumber('stat-total-projects', stats.total_projects);
                this.animateNumber('stat-due-today', stats.due_today);
            }

            animateNumber(elementId, targetNumber) {
                const element = document.getElementById(elementId);
                if (!element) return;

                const shimmer = element.querySelector('.loading-shimmer');
                if (shimmer) {
                    // Remove shimmer effect
                    element.innerHTML = '0';
                }

                const duration = 1000;
                const startTime = performance.now();
                const startNumber = 0;

                const animate = (currentTime) => {
                    const elapsed = currentTime - startTime;
                    const progress = Math.min(elapsed / duration, 1);
                    
                    // Easing function for smooth animation
                    const easeOut = 1 - Math.pow(1 - progress, 3);
                    const currentNumber = Math.floor(startNumber + (targetNumber - startNumber) * easeOut);
                    
                    element.textContent = currentNumber;
                    
                    if (progress < 1) {
                        requestAnimationFrame(animate);
                    } else {
                        element.textContent = targetNumber;
                    }
                };

                requestAnimationFrame(animate);
            }

            updateRecentActivity(activities) {
                const container = document.getElementById('recent-activity-container');
                if (!container) return;

                if (activities.length === 0) {
                    container.innerHTML = `
                        <div style="text-align: center;">
                            <i class="bi bi-activity" style="font-size: 2.5rem; color: #6c757d; opacity: 0.3;"></i>
                            <p style="margin: 12px 0 4px; color: #6c757d; font-size: 0.95rem;">No recent activity to display</p>
                            <small style="color: #6c757d; font-size: 0.8rem;">Start creating tasks to see your activity here</small>
                        </div>
                    `;
                    return;
                }

                const activityHtml = activities.map(activity => `
                    <div style="padding: 12px; border-bottom: 1px solid #f0f0f0; display: flex; align-items: center; transition: background-color 0.3s ease;" onmouseover="this.style.backgroundColor='#f8f9fa'" onmouseout="this.style.backgroundColor='transparent'">
                        <div style="width: 40px; height: 40px; background: ${this.getActivityColor(activity.activity_type)}; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 12px; flex-shrink: 0;">
                            <i class="bi ${this.getActivityIcon(activity.activity_type)}" style="color: white; font-size: 18px;"></i>
                        </div>
                        <div style="flex-grow: 1; min-width: 0;">
                            <div style="font-weight: 500; font-size: 0.9rem; color: #333; margin-bottom: 2px; word-break: break-word;">
                                ${activity.activity_type === 'completed' ? 'Completed' : 'Created'}: ${this.escapeHtml(activity.title)}
                            </div>
                            <div style="font-size: 0.8rem; color: #6c757d; display: flex; align-items: center; gap: 8px;">
                                ${activity.project_name ? `
                                    <span style="background: #e9ecef; padding: 2px 8px; border-radius: 10px; font-size: 0.75rem;">
                                        <i class="bi bi-folder me-1"></i>${this.escapeHtml(activity.project_name)}
                                    </span>
                                ` : ''}
                                <span>${activity.date}</span>
                            </div>
                        </div>
                    </div>
                `).join('');

                container.innerHTML = activityHtml;
            }

            getActivityColor(type) {
                return type === 'completed' ? '#28a745' : '#007bff';
            }

            getActivityIcon(type) {
                return type === 'completed' ? 'bi-check-circle-fill' : 'bi-plus-circle-fill';
            }

            escapeHtml(str) {
                const div = document.createElement('div');
                div.textContent = str;
                return div.innerHTML;
            }

            showError(message) {
                // Update loading elements with error state
                const elements = document.querySelectorAll('.loading-shimmer');
                elements.forEach(el => {
                    el.textContent = 'Error';
                    el.classList.remove('loading-shimmer');
                    el.style.color = '#dc3545';
                });

                // Show error in recent activity
                const container = document.getElementById('recent-activity-container');
                if (container) {
                    container.innerHTML = `
                        <div style="text-align: center;">
                            <i class="bi bi-exclamation-triangle" style="font-size: 2.5rem; color: #dc3545; opacity: 0.7;"></i>
                            <p style="margin: 12px 0 4px; color: #dc3545; font-size: 0.95rem;">${message}</p>
                            <small style="color: #6c757d; font-size: 0.8rem;">Please refresh the page to try again</small>
                        </div>
                    `;
                }
            }
        }

        // Logout confirmation function
        function confirmLogout(event) {
            event.preventDefault();
            
            // Create a custom confirmation dialog with better styling
            const confirmDialog = document.createElement('div');
            confirmDialog.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
                backdrop-filter: blur(5px);
            `;
            
            confirmDialog.innerHTML = `
                <div style="
                    background: white;
                    border-radius: 16px;
                    padding: 32px;
                    max-width: 400px;
                    margin: 20px;
                    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                    text-align: center;
                    animation: slideIn 0.3s ease-out;
                ">
                    <div style="
                        width: 64px;
                        height: 64px;
                        background: linear-gradient(135deg, #dc3545, #e74c3c);
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        margin: 0 auto 20px;
                    ">
                        <i class="bi bi-box-arrow-right" style="color: white; font-size: 28px;"></i>
                    </div>
                    
                    <h4 style="margin: 0 0 12px; color: #333; font-weight: 600; font-size: 1.3rem;">
                        Confirm Logout
                    </h4>
                    
                    <p style="margin: 0 0 24px; color: #666; font-size: 1rem; line-height: 1.5;">
                        Are you sure you want to log out of your TaskFlow account? 
                        You'll need to log in again to access your tasks and projects.
                    </p>
                    
                    <div style="display: flex; gap: 12px; justify-content: center;">
                        <button id="cancelLogout" style="
                            padding: 12px 24px;
                            background: transparent;
                            border: 2px solid #6c757d;
                            color: #6c757d;
                            border-radius: 8px;
                            font-weight: 500;
                            cursor: pointer;
                            font-size: 0.95rem;
                            transition: all 0.3s ease;
                        " onmouseover="this.style.backgroundColor='#6c757d'; this.style.color='white'" 
                           onmouseout="this.style.backgroundColor='transparent'; this.style.color='#6c757d'">
                            Cancel
                        </button>
                        
                        <button id="confirmLogout" style="
                            padding: 12px 24px;
                            background: linear-gradient(135deg, #dc3545, #e74c3c);
                            border: none;
                            color: white;
                            border-radius: 8px;
                            font-weight: 500;
                            cursor: pointer;
                            font-size: 0.95rem;
                            transition: all 0.3s ease;
                            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
                        " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(220, 53, 69, 0.4)'" 
                           onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(220, 53, 69, 0.3)'">
                            Yes, Logout
                        </button>
                    </div>
                </div>
            `;
            
            // Add animation styles
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideIn {
                    from {
                        opacity: 0;
                        transform: translateY(-20px) scale(0.95);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0) scale(1);
                    }
                }
                
                @keyframes slideOut {
                    from {
                        opacity: 1;
                        transform: translateY(0) scale(1);
                    }
                    to {
                        opacity: 0;
                        transform: translateY(-20px) scale(0.95);
                    }
                }
            `;
            document.head.appendChild(style);
            
            document.body.appendChild(confirmDialog);
            
            // Add event listeners
            document.getElementById('cancelLogout').addEventListener('click', function() {
                const dialog = confirmDialog.querySelector('div');
                dialog.style.animation = 'slideOut 0.3s ease-in';
                setTimeout(() => {
                    document.body.removeChild(confirmDialog);
                    document.head.removeChild(style);
                }, 300);
            });
            
            document.getElementById('confirmLogout').addEventListener('click', function() {
                // Show loading state on the button
                this.innerHTML = '<i class="bi bi-arrow-repeat spin" style="margin-right: 8px;"></i>Logging out...';
                this.style.pointerEvents = 'none';
                
                // Add spinner animation
                const spinStyle = document.createElement('style');
                spinStyle.textContent = `
                    .spin { animation: spin 1s linear infinite; }
                    @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
                `;
                document.head.appendChild(spinStyle);
                
                // Redirect to logout after a brief delay for UX
                setTimeout(() => {
                    window.location.href = 'user_logout.php';
                }, 1000);
            });
            
            // Close dialog when clicking outside
            confirmDialog.addEventListener('click', function(e) {
                if (e.target === confirmDialog) {
                    document.getElementById('cancelLogout').click();
                }
            });
            
            // Close dialog with Escape key
            document.addEventListener('keydown', function escapeHandler(e) {
                if (e.key === 'Escape') {
                    document.getElementById('cancelLogout').click();
                    document.removeEventListener('keydown', escapeHandler);
                }
            });
        }

        // Initialize profile loader when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            new ProfileLoader();
        });
    </script>
</body>
</html>