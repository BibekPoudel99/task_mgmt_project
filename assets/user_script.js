class TaskFlowApp {
    constructor() {
        this.projects = [];
        this.tasks = [];
        this.selectedProjectId = null;
        this.users = [];
        this.init();
    }

    async init() {
        this.bindEvents();
        this.setCsrf(document.getElementById('csrfTokenInput').value);
        
        // Immediately show loading state and render initial UI
        this.renderWithLoadingStates();
        
        try {
            // Start missed tasks update in background without blocking
            fetch('../user_api/missed_tasks.php').catch(e => console.warn('Missed tasks update failed:', e));
            
            // Fetch all data and update UI
            await this.refreshAll();
            this.render(); // Final render with actual data
        } catch (error) {
            console.error('Error during initialization:', error);
            await this.refreshAll();
            this.render();
        }
    }

    bindEvents() {
        const logoutLink = document.getElementById('logoutLink');
        if (logoutLink) {
            logoutLink.addEventListener('click', (e) => {
                const ok = confirm('Are you sure you want to logout?');
                if (!ok) e.preventDefault();
            });
        }
        const addTaskForm = document.getElementById('addTaskForm');
        if (addTaskForm) {
            addTaskForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                await this.addTask();
            });
        }
        const addProjectForm = document.getElementById('addProjectForm');
        if (addProjectForm) {
            addProjectForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                await this.addProject();
            });
        }
        document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
            tab.addEventListener('shown.bs.tab', (e) => {
                const targetId = e.target.getAttribute('data-bs-target');
                // Render specific tab content instead of all tabs
                this.renderTabContent(targetId);
            });
        });
    }

    async refreshAll() {
        // Fetch tasks first since My Day depends on it most
        await this.fetchTasks();
        this.renderMyDay(); // Update My Day immediately after tasks load
        
        // Then fetch other data in parallel
        await Promise.all([
            this.fetchUsers(),
            this.fetchProjects(),
        ]);
    }

    getCsrf() {
        const input = document.getElementById('csrfTokenInput');
        return input ? input.value : '';
    }

    setCsrf(token) {
        if (!token) return;
        const input = document.getElementById('csrfTokenInput');
        if (input) input.value = token;
    }

    async fetchUsers() {
        try {
            const res = await fetch('../user_api/users.php');
            const data = await res.json();
            this.users = Array.isArray(data?.users) ? data.users : [];
        } catch (_) {
            this.users = [];
        }
    }

    async fetchProjects() {
        try {
            const res = await fetch('../user_api/projects.php');
            const data = await res.json();
            this.projects = Array.isArray(data?.projects) ? data.projects : [];
            if (!this.selectedProjectId && this.projects.length) {
                this.selectedProjectId = this.projects[0].id;
            }
        } catch (_) {
            this.projects = [];
        }
        this.updateProjectOptions();
    }

    async fetchTasks() {
        try {
            const res = await fetch('../user_api/tasks.php');
            const data = await res.json();
            this.tasks = Array.isArray(data?.tasks) ? data.tasks : [];
        } catch (_) {
            this.tasks = [];
        }
    }

    async addTask() {
        const title = document.getElementById('taskTitle').value.trim();
        const dueDate = document.getElementById('taskDueDate').value;
        const projectId = document.getElementById('taskProject').value;
        if (!title) return;
        const body = new FormData();
        body.append('action', 'create');
        body.append('title', title);
        if (dueDate) body.append('due_date', dueDate);
        if (projectId) body.append('project_id', projectId);
        body.append('csrf_token', this.getCsrf());
        try {
            const res = await fetch('../user_api/tasks.php', { method: 'POST', body });
            const data = await res.json();
            if (data.csrf_token) this.setCsrf(data.csrf_token);
            if (data.success) {
                await this.fetchTasks();
                this.render();
                this.showToast('Task added successfully!');
                document.getElementById('addTaskForm').reset();
            } else {
                this.showToast(data.message || 'Failed to add task', 'error');
            }
        } catch (e) {
            this.showToast('Network error while adding task', 'error');
        }
    }

    async toggleTask(taskId) {
        const task = this.tasks.find(t => String(t.id) === String(taskId));
        if (task && task.is_missed) {
            this.showToast('Cannot complete a missed task. The due date has passed.', 'error');
            return;
        }
        try {
            const body = new FormData();
            body.append('action', 'toggle');
            body.append('task_id', taskId);
            body.append('csrf_token', this.getCsrf());
            const res = await fetch('../user_api/tasks.php', { method: 'POST', body });
            const data = await res.json();
            if (data.csrf_token) this.setCsrf(data.csrf_token);
            if (data.success) {
                await this.fetchTasks();
                this.render();
            } else {
                this.showToast(data.message || 'Failed to update task', 'error');
            }
        } catch (_) {
            this.showToast('Network error while updating task', 'error');
        }
    }

    async addProject() {
        const name = document.getElementById('projectName').value.trim();
        if (!name) return;
        const body = new FormData();
        body.append('action', 'create');
        body.append('name', name);
        body.append('csrf_token', this.getCsrf());
        try {
            const res = await fetch('../user_api/projects.php', { method: 'POST', body });
            const data = await res.json();
            if (data.csrf_token) this.setCsrf(data.csrf_token);
            if (data.success) {
                await this.fetchProjects();
                this.render();
                this.showToast('Project created successfully!');
                document.getElementById('projectName').value = '';
            } else {
                this.showToast(data.message || 'Failed to create project', 'error');
            }
        } catch (_) {
            this.showToast('Network error while creating project', 'error');
        }
    }

    selectProject(projectId) {
        this.selectedProjectId = projectId;
        this.render();
    }

    async addMember(projectId, memberUsername) {
        if (!memberUsername) return;
        const body = new FormData();
        body.append('action', 'add_member');
        body.append('project_id', projectId);
        body.append('username', memberUsername);
        body.append('csrf_token', this.getCsrf());
        try {
            const res = await fetch('../user_api/projects.php', { method: 'POST', body });
            const data = await res.json();
            if (data.csrf_token) this.setCsrf(data.csrf_token);
            if (data.success) {
                await this.fetchProjects();
                this.render();
                this.showToast('Member added');
            } else {
                this.showToast(data.message || 'Failed to add member', 'error');
            }
        } catch (_) {
            this.showToast('Network error while adding member', 'error');
        }
    }

    async removeMember(projectId, memberUsername) {
        if (!confirm(`Remove ${memberUsername} from this project?`)) return;
        
        const body = new FormData();
        body.append('action', 'remove_member');
        body.append('project_id', projectId);
        body.append('username', memberUsername);
        body.append('csrf_token', this.getCsrf());
        
        try {
            const res = await fetch('../user_api/projects.php', { method: 'POST', body });
            const data = await res.json();
            if (data.csrf_token) this.setCsrf(data.csrf_token);
            if (data.success) {
                await this.fetchProjects();
                this.render();
                this.showToast(`${memberUsername} removed from project`);
            } else {
                this.showToast(data.message || 'Failed to remove member', 'error');
            }
        } catch (_) {
            this.showToast('Network error while removing member', 'error');
        }
    }

    render() {
        this.renderMyDay();
        this.renderTasks();
        this.renderProjects();
        this.renderTeam();
        this.renderCalendar();
    }

    renderWithLoadingStates() {
        this.renderMyDayLoading();
        this.renderTasksLoading();
        this.renderProjectsLoading();
        this.renderTeamLoading();
        this.renderCalendarLoading();
    }

    renderTabContent(targetId) {
        switch (targetId) {
            case '#myday':
                this.renderMyDay();
                break;
            case '#tasks':
                this.renderTasks();
                break;
            case '#projects':
                this.renderProjects();
                break;
            case '#team':
                this.renderTeam();
                break;
            case '#calendar':
                this.renderCalendar();
                break;
            default:
                this.render(); // fallback
        }
    }

    renderMyDayLoading() {
        const container = document.getElementById('todayTasks');
        if (!container) return;
        
        container.innerHTML = `
            <div style="text-align: center; padding: 60px 20px; background: linear-gradient(135deg, #fef3c7, #fde68a); border: 1px solid #f59e0b; border-radius: 16px; margin-bottom: 32px;">
                <div class="spinner-border text-warning" role="status" style="width: 3rem; height: 3rem; margin-bottom: 20px;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h5 style="color: #92400e; margin-bottom: 12px; font-weight: 600;">Loading My Day...</h5>
                <p style="color: #d97706; margin: 0; font-size: 16px;">Fetching your tasks for today</p>
            </div>
        `;
    }

    renderTasksLoading() {
        const container = document.getElementById('allTasks');
        if (!container) return;
        
        container.innerHTML = `
            <div style="text-align: center; padding: 40px; color: #7c8471;">
                <div class="spinner-border" role="status" style="margin-bottom: 16px;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p>Loading tasks...</p>
            </div>
        `;
    }

    renderProjectsLoading() {
        const container = document.getElementById('projectsList');
        if (!container) return;
        
        container.innerHTML = `
            <div style="text-align: center; padding: 40px; color: #7c8471;">
                <div class="spinner-border" role="status" style="margin-bottom: 16px;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p>Loading projects...</p>
            </div>
        `;
    }

    renderTeamLoading() {
        const container = document.getElementById('teamMembers');
        if (!container) return;
        
        container.innerHTML = `
            <div style="text-align: center; padding: 40px; color: #7c8471;">
                <div class="spinner-border" role="status" style="margin-bottom: 16px;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p>Loading team members...</p>
            </div>
        `;
    }

    renderCalendarLoading() {
        const container = document.getElementById('calendarTasks');
        if (!container) return;
        
        container.innerHTML = `
            <div style="text-align: center; padding: 40px; color: #7c8471;">
                <div class="spinner-border" role="status" style="margin-bottom: 16px;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p>Loading calendar...</p>
            </div>
        `;
    }

    // ...existing renderMyDay, renderTasks+, renderProjects, renderProjectDetails, renderTeam, renderCalendar, addMemberToSelected, updateDueDate, addQuickTask, updateTaskTitle, deleteTask, updateProjectOptions, showToast, escapeHtml methods...
    renderMyDay() {
        const today = new Date().toISOString().split('T')[0];
        const todayTasks = this.tasks.filter(t => !t.completed && t.due_date === today);
        const container = document.getElementById('todayTasks');
        
        if (todayTasks.length === 0) {
            container.innerHTML = `
                <div style="text-align: center; padding: 60px 20px; background: linear-gradient(135deg, #f0fdf4, #f7fee7); border: 2px dashed #bbf7d0; border-radius: 16px;">
                    <i class="bi bi-sun" style="font-size: 64px; color: #16a34a; margin-bottom: 20px;"></i>
                    <h5 style="color: #166534; margin-bottom: 12px; font-weight: 600;">All Clear for Today!</h5>
                    <p style="color: #22c55e; margin: 0; font-size: 16px;">No tasks due today. Great job staying organized!</p>
                    <div style="margin-top: 24px;">
                        <button onclick="document.querySelector('[data-bs-target=\\"#tasks\\"]').click()" 
                                style="background: linear-gradient(135deg, #16a34a, #22c55e); color: white; border: none; border-radius: 12px; padding: 12px 24px; font-weight: 600; font-size: 14px; transition: all 0.3s ease;"
                                onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 16px rgba(22, 163, 74, 0.3)'"
                                onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                            <i class="bi bi-plus-circle-fill me-2"></i>Add New Tasks
                        </button>
                    </div>
                </div>
            `;
            return;
        }

        // Separate tasks by priority/status
        const overdueTasks = this.tasks.filter(t => !t.completed && t.due_date && t.due_date < today);
        const missedTasks = todayTasks.filter(t => t.is_missed);
        const regularTasks = todayTasks.filter(t => !t.is_missed);

        container.innerHTML = `
            <!-- MyDay Header -->
            <div class="myday-header" style="background: linear-gradient(135deg, #fef3c7, #fde68a); border: 1px solid #f59e0b; border-radius: 16px; padding: 24px; margin-bottom: 32px; box-shadow: 0 4px 16px rgba(245, 158, 11, 0.15);">
                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="d-flex align-items-center">
                            <div style="width: 64px; height: 64px; background: linear-gradient(135deg, #f59e0b, #fbbf24); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin-right: 20px; box-shadow: 0 4px 16px rgba(245, 158, 11, 0.3);">
                                <i class="bi bi-calendar-day-fill" style="color: white; font-size: 28px;"></i>
                            </div>
                            <div>
                                <h4 style="color: #92400e; font-weight: 700; margin: 0; margin-bottom: 4px;">My Day</h4>
                                <p style="color: #d97706; margin: 0; font-size: 16px;">
                                    ${new Date().toLocaleDateString('en-US', { 
                                        weekday: 'long', 
                                        year: 'numeric', 
                                        month: 'long', 
                                        day: 'numeric' 
                                    })}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="myday-stats" style="display: flex; gap: 20px; justify-content: lg-end;">
                            <div style="text-align: center;">
                                <div style="font-size: 24px; font-weight: 700; color: #92400e; margin-bottom: 4px;">${todayTasks.length}</div>
                                <div style="font-size: 13px; color: #d97706; font-weight: 500;">Due Today</div>
                            </div>
                            ${overdueTasks.length > 0 ? `
                                <div style="text-align: center;">
                                    <div style="font-size: 24px; font-weight: 700; color: #dc2626; margin-bottom: 4px;">${overdueTasks.length}</div>
                                    <div style="font-size: 13px; color: #ef4444; font-weight: 500;">Overdue</div>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Overdue Tasks (if any) -->
            ${overdueTasks.length > 0 ? `
                <div class="overdue-section" style="margin-bottom: 32px;">
                    <h5 style="color: #dc2626; font-weight: 600; margin-bottom: 16px; display: flex; align-items: center; font-size: 1.4rem;">
                        <i class="bi bi-exclamation-triangle-fill me-2" style="color: #ef4444;"></i>
                        Overdue Tasks
                        <span style="background: #fee2e2; color: #dc2626; padding: 4px 12px; border-radius: 12px; font-size: 14px; font-weight: 600; margin-left: 12px;">
                            ${overdueTasks.length}
                        </span>
                    </h5>
                    <div class="overdue-tasks" style="display: grid; gap: 12px;">
                        ${overdueTasks.slice(0, 3).map(task => this.renderTaskCard(task, 'overdue')).join('')}
                        ${overdueTasks.length > 3 ? `
                            <div style="text-align: center; padding: 16px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 12px;">
                                <span style="color: #dc2626; font-size: 14px; font-weight: 500;">
                                    +${overdueTasks.length - 3} more overdue tasks. 
                                    <button onclick="document.querySelector('[data-bs-target=\\"#tasks\\"]').click()" 
                                            style="background: none; border: none; color: #dc2626; text-decoration: underline; font-weight: 600; padding: 0; font-size: 14px;">
                                        View all tasks
                                    </button>
                                </span>
                            </div>
                        ` : ''}
                    </div>
                </div>
            ` : ''}

            <!-- Today's Tasks -->
            <div class="today-section">
                <h5 style="color: #334155; font-weight: 600; margin-bottom: 20px; display: flex; align-items: center; font-size: 1.4rem;">
                    <i class="bi bi-calendar-check me-2" style="color: #7c8471;"></i>
                    Today's Focus
                    <span style="background: #e2e8f0; color: #64748b; padding: 4px 12px; border-radius: 12px; font-size: 14px; font-weight: 500; margin-left: 12px;">
                        ${todayTasks.length} task${todayTasks.length !== 1 ? 's' : ''}
                    </span>
                </h5>
                
                <div class="today-tasks" style="display: grid; gap: 16px;">
                    ${todayTasks.map(task => this.renderTaskCard(task, 'today')).join('')}
                </div>
            </div>
        `;
    }

    renderTaskCard(task, context = 'normal') {
    const project = this.projects.find(p => String(p.id) === String(task.project_id));
    const isOverdue = context === 'overdue';
    const isToday = context === 'today';
    const isMissed = task.is_missed;
    
    // Check permissions: task owner, assignee, or project owner can edit
    const currentUserId = String(window.currentUser?.id);
    const isTaskOwner = String(task.owner_id) === currentUserId;
    const isAssignee = String(task.assignee_id) === currentUserId;
    const isProjectOwner = project && String(project.owner_id) === currentUserId;
    const canEdit = isTaskOwner || isProjectOwner;
    
    // Determine card styling based on context
    const cardStyle = isOverdue 
        ? 'background: linear-gradient(135deg, #fef2f2, #fee2e2); border: 1px solid #fecaca;'
        : isToday 
        ? 'background: linear-gradient(135deg, #ffffff, #fafbfc); border: 1px solid #e2e8f0;'
        : 'background: linear-gradient(135deg, #ffffff, #f8fafc); border: 1px solid #e5e7eb;';
    
    const statusBarColor = isOverdue ? '#ef4444' : isMissed ? '#dc2626' : '#f59e0b';
    
    return `
        <div class="task-card-myday" style="${cardStyle} border-radius: 16px; padding: 20px; transition: all 0.3s ease; position: relative; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.06);" 
             onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 8px 32px rgba(0,0,0,0.12)'; this.style.borderColor='#7c8471'" 
             onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.06)'; this.style.borderColor='${isOverdue ? '#fecaca' : '#e2e8f0'}'">
            
            <!-- Priority/Status indicator bar -->
            <div style="position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: ${statusBarColor};"></div>
            
            <div class="task-content" style="display: flex; align-items: flex-start; gap: 16px;">
                <!-- Action Button -->
                <div class="task-action" style="flex-shrink: 0;">
                    ${isMissed ? `
                        <div class="missed-indicator" style="width: 120px; height: 42px; background: linear-gradient(135deg, #ef4444, #f87171); color: white; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; box-shadow: 0 3px 8px rgba(239, 68, 68, 0.3);">
                            <i class="bi bi-clock-history me-1"></i>MISSED
                        </div>
                    ` : `
                        <button class="task-complete-btn" onclick="app.toggleTask('${task.id}')" 
                                style="background: linear-gradient(135deg, #7c8471, #9a9e92); color: white; border: none; border-radius: 10px; padding: 12px 20px; font-size: 14px; font-weight: 600; transition: all 0.3s ease; box-shadow: 0 3px 8px rgba(124, 132, 113, 0.3); white-space: nowrap;"
                                onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0 4px 12px rgba(124, 132, 113, 0.4)'"
                                onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 3px 8px rgba(124, 132, 113, 0.3)'">
                            <i class="bi bi-check-lg me-1"></i>Complete
                        </button>
                    `}
                </div>

                <!-- Task Details -->
                <div class="task-details" style="flex-grow: 1; min-width: 0;">
                    <!-- Task Title with Creator Info -->
                    <div class="task-title-section" style="margin-bottom: 12px;">
                        <h6 class="task-title" style="margin: 0 0 4px 0; font-size: 1.2rem; font-weight: 700; color: ${isOverdue ? '#dc2626' : '#1e293b'}; word-break: break-word; line-height: 1.3;">
                            ${this.escapeHtml(task.title)}
                        </h6>
                        
                        <!-- Creator and Permission Info -->
                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            ${task.owner_username ? `
                                <span style="background: ${isTaskOwner ? '#dcfce7' : '#f3f4f6'}; color: ${isTaskOwner ? '#16a34a' : '#6b7280'}; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 500;">
                                    <i class="bi ${isTaskOwner ? 'bi-person-check-fill' : 'bi-person-fill'} me-1"></i>Created by ${task.owner_username}${isTaskOwner ? ' (You)' : ''}
                                </span>
                            ` : ''}
                            
                            ${!canEdit ? `
                                <span style="background: #fef3c7; color: #d97706; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 500;">
                                    <i class="bi bi-eye me-1"></i>View Only
                                </span>
                            ` : isProjectOwner && !isTaskOwner ? `
                                <span style="background: #dbeafe; color: #1e40af; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 500;">
                                    <i class="bi bi-shield-check me-1"></i>Project Owner Access
                                </span>
                            ` : ''}
                        </div>
                    </div>

                    <!-- Task Meta Information -->
                    <div class="task-meta" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 12px;">
                        <!-- Due Date Badge -->
                        <span class="due-date-badge" style="background: ${isOverdue ? '#fee2e2' : '#fef3c7'}; color: ${isOverdue ? '#dc2626' : '#92400e'}; padding: 7px 14px; border-radius: 16px; font-size: 13px; font-weight: 600; display: flex; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                            <i class="bi ${isOverdue ? 'bi-exclamation-triangle' : 'bi-calendar3'} me-2"></i>
                            ${isOverdue ? `Overdue: ${task.due_date}` : `Due: ${task.due_date}`}
                        </span>
                        
                        <!-- Project Badge -->
                        ${project ? `
                            <span class="project-badge" style="background: linear-gradient(135deg, #7c8471, #9a9e92); color: white; padding: 7px 14px; border-radius: 16px; font-size: 13px; font-weight: 600; display: flex; align-items: center; box-shadow: 0 2px 4px rgba(124, 132, 113, 0.2);">
                                <i class="bi bi-folder-fill me-2"></i>${this.escapeHtml(project.name)}
                            </span>
                        ` : `
                            <span class="no-project-badge" style="background: #f1f5f9; color: #64748b; padding: 7px 14px; border-radius: 16px; font-size: 13px; font-weight: 500; display: flex; align-items: center;">
                                <i class="bi bi-inbox me-2"></i>No Project
                            </span>
                        `}
                        
                        <!-- Assignee Badge -->
                        ${task.assignee ? `
                            <span class="assignee-badge" style="background: ${isAssignee ? '#dcfce7' : '#e0f2fe'}; color: ${isAssignee ? '#16a34a' : '#0369a1'}; padding: 7px 14px; border-radius: 16px; font-size: 13px; font-weight: 600; display: flex; align-items: center;">
                                <i class="bi bi-person-check-fill me-2"></i>Assigned to ${this.escapeHtml(task.assignee)}${isAssignee ? ' (You)' : ''}
                            </span>
                        ` : ''}
                    </div>

                    <!-- Task Status and Priority -->
                    <div class="task-status-row" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
                        <div class="status-badges" style="display: flex; align-items: center; gap: 8px;">
                            <!-- Priority Indicator -->
                            <span class="priority-badge" style="background: ${isOverdue ? '#fee2e2' : isToday ? '#fef3c7' : '#f0f9ff'}; color: ${isOverdue ? '#dc2626' : isToday ? '#ca8a04' : '#1e40af'}; padding: 5px 12px; border-radius: 12px; font-size: 12px; font-weight: 700; display: flex; align-items: center;">
                                <i class="bi ${isOverdue ? 'bi-exclamation-triangle-fill' : isToday ? 'bi-star-fill' : 'bi-clock-fill'} me-1"></i>
                                ${isOverdue ? 'URGENT' : isToday ? 'TODAY' : 'UPCOMING'}
                            </span>
                            
                            <!-- Task Type -->
                            ${isMissed ? `
                                <span style="background: #fee2e2; color: #dc2626; padding: 5px 12px; border-radius: 12px; font-size: 12px; font-weight: 700; display: flex; align-items: center;">
                                    <i class="bi bi-x-circle-fill me-1"></i>MISSED
                                </span>
                            ` : ''}
                        </div>

                        <!-- Quick Actions -->
                        <div class="quick-actions" style="display: flex; align-items: center; gap: 8px;">
                            ${project ? `
                                <button onclick="app.selectProject('${project.id}'); document.querySelector('[data-bs-target=\\"#projects\\"]').click()" 
                                        style="background: transparent; border: 1px solid #7c8471; color: #7c8471; padding: 6px 10px; border-radius: 6px; font-size: 12px; font-weight: 500; transition: all 0.3s ease;"
                                        onmouseover="this.style.background='#7c8471'; this.style.color='white'"
                                        onmouseout="this.style.background='transparent'; this.style.color='#7c8471'"
                                        title="View project">
                                    <i class="bi bi-arrow-right-circle"></i>
                                </button>
                            ` : ''}
                            
                            ${canEdit ? `
                                <button onclick="document.querySelector('[data-bs-target=\\"#tasks\\"]').click()" 
                                        style="background: transparent; border: 1px solid #64748b; color: #64748b; padding: 6px 10px; border-radius: 6px; font-size: 12px; font-weight: 500; transition: all 0.3s ease;"
                                        onmouseover="this.style.background='#64748b'; this.style.color='white'"
                                        onmouseout="this.style.background='transparent'; this.style.color='#64748b'"
                                        title="Edit task">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            ` : `
                                <span style="background: #f3f4f6; color: #6b7280; padding: 6px 10px; border-radius: 6px; font-size: 12px; font-weight: 500;" title="No edit permission">
                                    <i class="bi bi-shield-lock"></i>
                                </span>
                            `}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Progress Indicator (if project exists) -->
            ${project ? `
                <div class="project-progress" style="margin-top: 16px; padding-top: 16px; border-top: 1px solid ${isOverdue ? '#fecaca' : '#f1f5f9'};">
                    ${(() => {
                        const projectTasks = this.tasks.filter(t => String(t.project_id) === String(project.id));
                        const completedTasks = projectTasks.filter(t => t.completed);
                        const progress = projectTasks.length > 0 ? Math.round((completedTasks.length / projectTasks.length) * 100) : 0;
                        
                        return `
                            <div style="display: flex; align-items: center; justify-content: between; gap: 12px;">
                                <span style="font-size: 12px; color: #64748b; font-weight: 500;">Project Progress:</span>
                                <div style="flex-grow: 1; background: #f1f5f9; border-radius: 8px; height: 6px; overflow: hidden;">
                                    <div style="width: ${progress}%; height: 100%; background: linear-gradient(135deg, #7c8471, #9a9e92); transition: width 0.5s ease;"></div>
                                </div>
                                <span style="font-size: 12px; color: #64748b; font-weight: 600;">${progress}%</span>
                            </div>
                        `;
                    })()}
                </div>
            ` : ''}
        </div>
    `;
    }

    renderTasks() {
        const container = document.getElementById('allTasks');
        
        if (!this.tasks.length) {
            container.innerHTML = `
                <div style="text-align: center; padding: 40px 20px; background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 12px;">
                    <i class="bi bi-inbox" style="font-size: 48px; color: #94a3b8; margin-bottom: 16px;"></i>
                    <h5 style="color: #64748b; margin-bottom: 8px; font-weight: 600;">No Tasks Yet</h5>
                    <p style="color: #94a3b8; margin: 0; font-size: 16px;">Create your first task to get started</p>
                </div>
            `;
            return;
        }

        // Categorize tasks
        const completedTasks = this.tasks.filter(t => t.completed);
        const pendingTasks = this.tasks.filter(t => !t.completed && !t.is_missed);
        const missedTasks = this.tasks.filter(t => t.is_missed);
        
        // Get tasks with due dates for urgency sorting
        const today = new Date().toISOString().split('T')[0];
        const urgent = pendingTasks.filter(t => t.due_date && t.due_date <= today);
        const upcoming = pendingTasks.filter(t => t.due_date && t.due_date > today);
        const noDueDate = pendingTasks.filter(t => !t.due_date);

        container.innerHTML = `
            <!-- Simple Header -->
            <div style="background: #f0f9ff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 style="color: #1e40af; font-weight: 600; margin: 0; font-size: 20px;">All Tasks</h4>
                        <p style="color: #3b82f6; margin: 0; font-size: 16px;">Manage your complete task workflow</p>
                    </div>
                    <div style="display: flex; gap: 16px;">
                        <div style="text-align: center;">
                            <div style="font-size: 24px; font-weight: 700; color: #1e40af;">${pendingTasks.length}</div>
                            <div style="font-size: 14px; color: #3b82f6;">Active</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 24px; font-weight: 700; color: #1e40af;">${completedTasks.length}</div>
                            <div style="font-size: 14px; color: #3b82f6;">Completed</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Simple Filters -->
            <div style="margin-bottom: 24px;">
                <button onclick="app.filterTasks('all')" class="filter-btn" data-filter="all" 
                        style="background: #7c8471; color: white; border: none; border-radius: 8px; padding: 8px 16px; font-size: 14px; font-weight: 500; margin-right: 8px;">
                    All (${this.tasks.length})
                </button>
                <button onclick="app.filterTasks('pending')" class="filter-btn" data-filter="pending"
                        style="background: #f59e0b; color: white; border: none; border-radius: 8px; padding: 8px 16px; font-size: 14px; font-weight: 500; margin-right: 8px;">
                    Pending (${pendingTasks.length})
                </button>
                <button onclick="app.filterTasks('completed')" class="filter-btn" data-filter="completed"
                        style="background: #10b981; color: white; border: none; border-radius: 8px; padding: 8px 16px; font-size: 14px; font-weight: 500; margin-right: 8px;">
                    Completed (${completedTasks.length})
                </button>
                ${missedTasks.length > 0 ? `
                    <button onclick="app.filterTasks('missed')" class="filter-btn" data-filter="missed"
                            style="background: #ef4444; color: white; border: none; border-radius: 8px; padding: 8px 16px; font-size: 14px; font-weight: 500;">
                        Missed (${missedTasks.length})
                    </button>
                ` : ''}
            </div>

            <!-- Missed Tasks -->
            ${missedTasks.length > 0 ? `
                <div class="task-section" data-category="missed" style="margin-bottom: 24px;">
                    <h5 style="color: #dc2626; font-weight: 600; margin-bottom: 16px; font-size: 1.4rem;">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Missed Tasks
                    </h5>
                    <div style="display: grid; gap: 16px;">
                        ${missedTasks.map(task => this.renderSimpleTaskCard(task, 'missed')).join('')}
                    </div>
                </div>
            ` : ''}

            <!-- Urgent Tasks -->
            ${urgent.length > 0 ? `
                <div class="task-section" data-category="pending" style="margin-bottom: 24px;">
                    <h5 style="color: #f59e0b; font-weight: 600; margin-bottom: 16px; font-size: 1.4rem;">
                        <i class="bi bi-lightning-fill me-2"></i>Urgent Tasks
                    </h5>
                    <div style="display: grid; gap: 16px;">
                        ${urgent.map(task => this.renderSimpleTaskCard(task, 'urgent')).join('')}
                    </div>
                </div>
            ` : ''}

            <!-- Upcoming Tasks -->
            ${upcoming.length > 0 ? `
                <div class="task-section" data-category="pending" style="margin-bottom: 24px;">
                    <h5 style="color: #3b82f6; font-weight: 600; margin-bottom: 16px; font-size: 1.4rem;">
                        <i class="bi bi-calendar-event me-2"></i>Upcoming Tasks
                    </h5>
                    <div style="display: grid; gap: 16px;">
                        ${upcoming.map(task => this.renderSimpleTaskCard(task, 'upcoming')).join('')}
                    </div>
                </div>
            ` : ''}

            <!-- No Due Date Tasks -->
            ${noDueDate.length > 0 ? `
                <div class="task-section" data-category="pending" style="margin-bottom: 24px;">
                    <h5 style="color: #64748b; font-weight: 600; margin-bottom: 16px; font-size: 1.4rem;">
                        <i class="bi bi-clock me-2"></i>No Due Date
                    </h5>
                    <div style="display: grid; gap: 16px;">
                        ${noDueDate.map(task => this.renderSimpleTaskCard(task, 'no-date')).join('')}
                    </div>
                </div>
            ` : ''}

            <!-- Completed Tasks -->
            ${completedTasks.length > 0 ? `
                <div class="task-section" data-category="completed" style="margin-bottom: 24px;">
                    <h5 style="color: #16a34a; font-weight: 600; margin-bottom: 16px; font-size: 1.4rem;">
                        <i class="bi bi-check-circle-fill me-2"></i>Completed Tasks
                    </h5>
                    <div style="display: grid; gap: 16px;">
                        ${completedTasks.map(task => this.renderSimpleTaskCard(task, 'completed')).join('')}
                    </div>
                </div>
            ` : ''}
        `;

        this.initializeTaskFilters();
    }

    renderSimpleTaskCard(task, context = 'normal') {
        const project = this.projects.find(p => String(p.id) === String(task.project_id));
        const isCompleted = task.completed;
        const isMissed = task.is_missed;
        const isUrgent = context === 'urgent';
        
        // Check permissions: task owner, assignee, or project owner can edit
        const currentUserId = String(window.currentUser?.id);
        const isTaskOwner = String(task.owner_id) === currentUserId;
        const isAssignee = String(task.assignee_id) === currentUserId;
        const isProjectOwner = project && String(project.owner_id) === currentUserId;
        const canEdit = isTaskOwner || isProjectOwner;
        const canDelete = isTaskOwner || isProjectOwner;
        
        // Simple styling based on context
        const getCardStyle = () => {
            if (isMissed) return 'background: #fef2f2; border: 1px solid #fecaca;';
            if (isCompleted) return 'background: #f0fdf4; border: 1px solid #bbf7d0;';
            if (isUrgent) return 'background: #fef3c7; border: 1px solid #fbbf24;';
            return 'background: #ffffff; border: 1px solid #e5e7eb;';
        };

        const today = new Date().toISOString().split('T')[0];
        const daysUntilDue = task.due_date ? Math.ceil((new Date(task.due_date) - new Date(today)) / (1000 * 60 * 60 * 24)) : null;

        return `
            <div style="${getCardStyle()} border-radius: 12px; padding: 20px; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.05);" 
                 onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)'" 
                 onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.05)'">
                
                <div style="display: flex; align-items: flex-start; gap: 16px;">
                    <!-- Action Button -->
                    <div style="flex-shrink: 0;">
                        ${!isMissed ? `
                            <button onclick="app.toggleTask('${task.id}')" 
                                    style="background: ${isCompleted ? '#6b7280' : '#7c8471'}; color: white; border: none; border-radius: 8px; padding: 12px 18px; font-size: 15px; font-weight: 500;">
                                <i class="bi ${isCompleted ? 'bi-arrow-clockwise' : 'bi-check-lg'} me-1"></i>
                                ${isCompleted ? 'Reopen' : 'Complete'}
                            </button>
                        ` : `
                            <div style="background: #ef4444; color: white; border-radius: 8px; padding: 12px 18px; font-size: 15px; font-weight: 600;">
                                <i class="bi bi-clock-history me-1"></i>OVERDUE
                            </div>
                        `}
                    </div>

                    <!-- Task Details -->
                    <div style="flex-grow: 1;">
                        <!-- Task Title with Creator Info -->
                        <div style="margin-bottom: 12px;">
                            <h6 style="margin: 0 0 4px 0; font-size: 1.1rem; font-weight: 600; color: ${isCompleted ? '#6b7280' : isMissed ? '#dc2626' : '#1f2937'}; ${isCompleted ? 'text-decoration: line-through;' : ''}">
                                ${this.escapeHtml(task.title)}
                            </h6>
                            
                            <!-- Creator and Permission Info -->
                            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                ${task.owner_username ? `
                                    <span style="background: ${isTaskOwner ? '#dcfce7' : '#f3f4f6'}; color: ${isTaskOwner ? '#16a34a' : '#6b7280'}; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 500;">
                                        <i class="bi ${isTaskOwner ? 'bi-person-check-fill' : 'bi-person-fill'} me-1"></i>Created by ${task.owner_username}${isTaskOwner ? ' (You)' : ''}
                                    </span>
                                ` : ''}
                                
                                ${!canEdit ? `
                                    <span style="background: #fef3c7; color: #d97706; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 500;">
                                        <i class="bi bi-eye me-1"></i>View Only
                                    </span>
                                ` : isProjectOwner && !isTaskOwner ? `
                                    <span style="background: #dbeafe; color: #1e40af; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 500;">
                                        <i class="bi bi-shield-check me-1"></i>Project Owner Access
                                    </span>
                                ` : ''}
                            </div>
                        </div>

                        <!-- Task Info -->
                        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 16px;">
                            <!-- Due Date -->
                            ${task.due_date ? `
                                <span style="background: ${isMissed ? '#fee2e2' : '#f3f4f6'}; color: ${isMissed ? '#dc2626' : '#374151'}; padding: 5px 14px; border-radius: 6px; font-size: 15px; font-weight: 500;">
                                    <i class="bi bi-calendar3 me-1"></i>${task.due_date}
                                    ${daysUntilDue !== null ? (
                                        daysUntilDue < 0 ? ` (${Math.abs(daysUntilDue)} days overdue)` :
                                        daysUntilDue === 0 ? ' (Due today)' :
                                        ` (${daysUntilDue} days left)`
                                    ) : ''}
                                </span>
                            ` : `
                                <span style="background: #f3f4f6; color: #6b7280; padding: 5px 14px; border-radius: 6px; font-size: 15px; font-weight: 500;">
                                    <i class="bi bi-clock me-1"></i>No due date
                                </span>
                            `}
                            
                            <!-- Project -->
                            ${project ? `
                                <span style="background: #7c8471; color: white; padding: 5px 14px; border-radius: 6px; font-size: 15px; font-weight: 500;">
                                    <i class="bi bi-folder me-1"></i>${this.escapeHtml(project.name)}
                                </span>
                            ` : `
                                <span style="background: #f1f5f9; color: #64748b; padding: 5px 14px; border-radius: 6px; font-size: 15px; font-weight: 500;">
                                    <i class="bi bi-inbox me-1"></i>No Project
                                </span>
                            `}
                            
                            <!-- Assignee -->
                            ${task.assignee ? `
                                <span style="background: ${isAssignee ? '#dcfce7' : '#dbeafe'}; color: ${isAssignee ? '#16a34a' : '#1e40af'}; padding: 5px 14px; border-radius: 6px; font-size: 15px; font-weight: 500;">
                                    <i class="bi bi-person me-1"></i>${this.escapeHtml(task.assignee)}${isAssignee ? ' (You)' : ''}
                                </span>
                            ` : ''}
                        </div>

                        <!-- Edit Section - Only for those who can edit -->
                        ${!isMissed && canEdit ? `
                            <div style="display: grid; grid-template-columns: 1fr auto; gap: 12px; align-items: center;">
                                <!-- Edit Title -->
                                <input type="text" value="${this.escapeHtml(task.title)}" 
                                       onchange="app.updateTaskTitle('${task.id}', this.value)"
                                       style="border: 1px solid #d1d5db; border-radius: 6px; padding: 10px 14px; font-size: 1rem;">
                                
                                <!-- Edit Due Date -->
                                <input type="date" value="${task.due_date || ''}" 
                                       onchange="app.updateDueDate('${task.id}', this.value)"
                                       style="border: 1px solid #d1d5db; border-radius: 6px; padding: 10px 14px; font-size: 15px;">
                            </div>
                        ` : !isMissed && !canEdit ? `
                            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 14px; text-align: center;">
                                <span style="color: #64748b; font-size: 15px; font-style: italic;">
                                    <i class="bi bi-info-circle me-1"></i>Only task creator or project owner can edit this task
                                </span>
                            </div>
                        ` : ''}
                    </div>

                    <!-- Actions -->
                    <div style="flex-shrink: 0;">
                        ${project ? `
                            <button onclick="app.selectProject('${project.id}'); document.querySelector('[data-bs-target=\\"#projects\\"]').click()" 
                                    style="background: transparent; border: 1px solid #7c8471; color: #7c8471; padding: 8px 14px; border-radius: 6px; font-size: 15px; margin-right: 8px;">
                                <i class="bi bi-arrow-right"></i>
                            </button>
                        ` : ''}
                        
                        ${canDelete ? `
                            <button onclick="app.deleteTask('${task.id}')" 
                                    style="background: transparent; border: 1px solid #dc2626; color: #dc2626; padding: 8px 14px; border-radius: 6px; font-size: 15px;">
                                <i class="bi bi-trash"></i>
                            </button>
                        ` : `
                            <span style="background: #f3f4f6; color: #6b7280; padding: 8px 14px; border-radius: 6px; font-size: 13px; font-weight: 500;">
                                <i class="bi bi-shield-lock"></i>
                            </span>
                        `}
                    </div>
                </div>
            </div>
        `;
    }    filterTasks(filter) {
    // Update button styles
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.style.opacity = '0.6';
            btn.style.transform = 'scale(0.95)';
        });
        
        const activeBtn = document.querySelector(`[data-filter="${filter}"]`);
        if (activeBtn) {
            activeBtn.style.opacity = '1';
            activeBtn.style.transform = 'scale(1)';
        }

        // Show/hide task sections
        document.querySelectorAll('.task-section').forEach(section => {
            if (filter === 'all') {
                section.style.display = 'block';
            } else {
                const category = section.getAttribute('data-category');
                section.style.display = category === filter ? 'block' : 'none';
            }
        });
    }

    initializeTaskFilters() {
    // Set up filter button hover effects
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('mouseenter', () => {
                if (btn.style.opacity !== '1') {
                    btn.style.transform = 'scale(1.05)';
                }
            });
            
            btn.addEventListener('mouseleave', () => {
                if (btn.style.opacity !== '1') {
                    btn.style.transform = 'scale(0.95)';
                }
            });
        });
    }


    renderProjects() {
        const projectsList = document.getElementById('projectsList');
        projectsList.innerHTML = this.projects.map(project => {
            const projectTasks = this.tasks.filter(t => String(t.project_id) === String(project.id));
            const completedCount = projectTasks.filter(t => t.completed).length;
            const total = projectTasks.length || 1;
            const percent = Math.round((completedCount / total) * 100);
            return `
                <div class="project-item ${this.selectedProjectId == project.id ? 'active' : ''}" style="border-radius: 16px; padding: 24px; margin-bottom: 16px; background: linear-gradient(135deg, #ffffff, #fafafa); border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.06); transition: all 0.3s ease; cursor: pointer;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 16px rgba(0,0,0,0.12)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.06)'">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center flex-grow-1" onclick="app.selectProject('${project.id}')">
            <div class="project-icon me-4" style="width: 48px; height: 48px; border-radius: 12px; background: linear-gradient(135deg, #7c8471, #9a9e92); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 26px;">
                ${this.escapeHtml(project.name.charAt(0).toUpperCase())}
            </div>
            <div class="flex-grow-1">
                <h6 class="project-name mb-1" style="font-weight: 600; color: #374151; font-size: 26px; margin: 0;">${this.escapeHtml(project.name)}</h6>
                <p class="text-muted small mb-0" style="font-size: 15px;">${projectTasks.length} task${projectTasks.length !== 1 ? 's' : ''} • ${completedCount} completed</p>
            </div>
        </div>
        <div class="d-flex align-items-center" style="gap: 8px;">
            <span class="badge rounded-pill ${this.selectedProjectId == project.id ? 'bg-primary' : 'bg-secondary'}" style="font-size: 13px; padding: 6px 10px;">${projectTasks.length - completedCount}</span>
            <div class="btn-group btn-group-sm" role="group">
                <button class="btn btn-outline-secondary btn-sm" title="Rename" onclick="event.stopPropagation(); app.promptRenameProject('${project.id}', '${project.name.replace(/'/g, "&#39;")}')" style="border: none; padding: 4px 8px;"><i class="bi bi-pencil"></i></button>
                <button class="btn btn-outline-danger btn-sm" title="Delete" onclick="event.stopPropagation(); app.deleteProject('${project.id}')" style="border: none; padding: 4px 8px;"><i class="bi bi-trash"></i></button>
            </div>
        </div>
    </div>
    <div class="progress mb-3" style="height: 10px; border-radius: 6px; background-color: #f3f4f6;">
        <div class="progress-bar" role="progressbar" style="width: ${percent}%; background: linear-gradient(135deg, #10b981, #34d399); border-radius: 6px; transition: width 0.5s ease;" aria-valuenow="${percent}" aria-valuemin="0" aria-valuemax="100"></div>
    </div>
    <div class="d-flex justify-content-between align-items-center">
        <span class="small text-muted" style="font-size: 14px; font-weight: 500;">${percent}% completed</span>
        <span class="small text-muted" style="font-size: 14px;">${projectTasks.length - completedCount} remaining</span>
    </div>
</div>
            `;
        }).join('');
        this.renderProjectDetails();
    }

    

   renderProjectDetails() {
    const container = document.getElementById('projectDetails');
    if (!this.selectedProjectId) {
        container.innerHTML = '<p class="text-muted">Select or create a project to manage members and tasks.</p>';
        return;
    }
    const project = this.projects.find(p => String(p.id) === String(this.selectedProjectId));
    const projectTasks = this.tasks.filter(t => String(t.project_id) === String(this.selectedProjectId));
    const memberOptions = this.users.map(u => `<option value="${u.username}">${u.username}</option>`).join('');
    const isProjectOwner = String(project?.owner_id) === String(window.currentUser?.id);
    
    // Find owner information
    const ownerInfo = this.users.find(u => String(u.id) === String(project?.owner_id));
    const ownerName = ownerInfo ? ownerInfo.username : 'Unknown';
    const isCurrentUserOwner = String(project?.owner_id) === String(window.currentUser?.id);
    
    container.innerHTML = `
    <!-- Simple Project Owner Indicator -->
    <div style="background: linear-gradient(135deg, #ffffff, #f8fafc); border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h5 style="color: #334155; font-weight: 600; margin: 0; display: flex; align-items: center; font-size: 1.3rem;">
                <i class="bi bi-folder-fill me-2" style="color: #7c8471;"></i>
                ${this.escapeHtml(project.name)}
            </h5>
            
            <!-- Project Owner Badge -->
            <div style="display: flex; align-items: center; background: ${isCurrentUserOwner ? '#f0fdf4' : '#f8fafc'}; border: 1px solid ${isCurrentUserOwner ? '#bbf7d0' : '#e2e8f0'}; border-radius: 20px; padding: 8px 14px;">
                <div style="width: 28px; height: 28px; background: ${isCurrentUserOwner ? 'linear-gradient(135deg, #10b981, #34d399)' : 'linear-gradient(135deg, #7c8471, #9a9e92)'}; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 14px; margin-right: 10px;">
                    ${ownerName.charAt(0).toUpperCase()}
                </div>
                <span style="color: ${isCurrentUserOwner ? '#166534' : '#64748b'}; font-size: 14px; font-weight: 500;">
                    ${isCurrentUserOwner ? 'You (Owner)' : `${ownerName} (Owner)`}
                </span>
            </div>
        </div>
        
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="team-section">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 style="color: #64748b; font-weight: 500; margin: 0; display: flex; align-items: center; font-size: 1.1rem;">
                            <i class="bi bi-people me-2" style="color: #7c8471; font-size: 16px;"></i>
                            Team Members
                        </h6>
                        ${!isCurrentUserOwner ? `
                            <span style="background: #fef3c7; color: #92400e; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 500;">
                                <i class="bi bi-info-circle me-1"></i>
                                Owner only
                            </span>
                        ` : ''}
                    </div>
                    
                    ${isCurrentUserOwner ? `
                        <div class="member-input-group" style="margin-bottom: 16px;">
                            <div class="input-group" style="border-radius: 8px; overflow: hidden;">
                                <input class="form-control" id="newMemberInput" list="usersDatalist" 
                                    placeholder="Add team member..." autocomplete="off"
                                    style="border: 1px solid #d1d5db; border-right: none; padding: 12px 16px; font-size: 15px;">
                                <datalist id="usersDatalist">${memberOptions}</datalist>
                                <button class="btn" onclick="app.addMemberToSelected()" 
                                        style="background: linear-gradient(135deg, #7c8471, #9a9e92); color: white; border: none; padding: 12px 18px; font-weight: 500; font-size: 15px;">
                                    <i class="bi bi-plus"></i> Add
                                </button>
                            </div>
                        </div>
                    ` : ''}
                    
                    <!-- Team Members Display -->
                    <div class="members-display">
                        ${(project.members || []).length === 0 
                            ? `<div style="padding: 14px; background: #f8fafc; border-radius: 8px; text-align: center; color: #64748b; font-size: 15px;">
                                 <i class="bi bi-people" style="font-size: 22px; margin-bottom: 6px;"></i>
                                 <p style="margin: 0;">No team members yet</p>
                               </div>` 
                            : `<div style="display: flex; flex-wrap: wrap; gap: 10px;">
                                 ${(project.members || []).map(member => `
                                     <div style="display: flex; align-items: center; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 5px 14px 5px 5px;">
                                         <div style="width: 28px; height: 28px; background: linear-gradient(135deg, #7c8471, #9a9e92); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 13px; margin-right: 10px;">
                                             ${member.charAt(0).toUpperCase()}
                                         </div>
                                         <span style="font-size: 14px; color: #374151; margin-right: 6px;">${member}</span>
                                         ${isCurrentUserOwner ? `
                                             <button onclick="app.removeMember('${project.id}', '${member}')" 
                                                     style="background: none; border: none; color: #dc2626; padding: 3px; border-radius: 50%; font-size: 12px; display: flex; align-items: center; justify-content: center; width: 18px; height: 18px;"
                                                     title="Remove ${member}">
                                                 <i class="bi bi-x"></i>
                                             </button>
                                         ` : ''}
                                     </div>
                                 `).join('')}
                               </div>`
                        }
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="quick-task-section">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 style="color: #64748b; font-weight: 500; margin: 0; display: flex; align-items: center; font-size: 1.1rem;">
                            <i class="bi bi-lightning me-2" style="color: #7c8471; font-size: 16px;"></i>
                            Quick Add Task
                        </h6>
                    </div>
                    <div class="input-group" style="border-radius: 8px; overflow: hidden;">
                        <input type="text" class="form-control" id="quickTaskInput" 
                            placeholder="Enter task title..."
                            style="border: 1px solid #d1d5db; border-right: none; padding: 12px 16px; font-size: 15px;">
                        <button class="btn" onclick="app.addQuickTask()" 
                                style="background: linear-gradient(135deg, #059669, #10b981); color: white; border: none; padding: 12px 18px; font-weight: 500; font-size: 15px;">
                            <i class="bi bi-plus"></i> Add
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="tasks-section">
        <div class="tasks-header" style="display: flex; align-items: center; justify-content: between; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid #e2e8f0;">
            <h5 style="color: #334155; font-weight: 600; margin: 0; display: flex; align-items: center; font-size: 1.4rem;">
                <i class="bi bi-check2-square me-2" style="color: #7c8471;"></i>
                Project Tasks
                <span style="background: #e2e8f0; color: #64748b; padding: 4px 12px; border-radius: 12px; font-size: 14px; font-weight: 500; margin-left: 12px;">
                    ${projectTasks.length} total
                </span>
            </h5>
        </div>
        
        <div class="tasks-container" style="display: grid; gap: 12px;">
            ${projectTasks.length === 0 
                ? `<div style="text-align: center; padding: 40px 20px; background: linear-gradient(135deg, #f8fafc, #f1f5f9); border: 2px dashed #cbd5e1; border-radius: 12px;">
                     <i class="bi bi-inbox" style="font-size: 48px; color: #94a3b8; margin-bottom: 16px;"></i>
                     <h6 style="color: #64748b; margin-bottom: 8px; font-size: 1.1rem;">No tasks yet</h6>
                     <p style="color: #94a3b8; margin: 0; font-size: 1rem;">Use the quick add above to create your first task</p>
                   </div>` 
                : projectTasks.map(task => {
                    const isTaskOwner = String(task.owner_id) === String(window.currentUser?.id);
                    return `
                    <div class="task-card" style="background: linear-gradient(135deg, #ffffff, #fafbfc); border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; transition: all 0.3s ease; position: relative; overflow: hidden;" 
                         onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px rgba(0,0,0,0.15)'; this.style.borderColor='#7c8471'" 
                         onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.1)'; this.style.borderColor='#e2e8f0'">
                        
                        <div style="position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: ${task.completed ? '#10b981' : '#f59e0b'};"></div>
                        
                        <div class="task-content" style="display: flex; align-items: flex-start; gap: 16px;">
                            <button class="task-action-btn" onclick="app.toggleTask('${task.id}')" 
                                    style="background: ${task.completed ? 'linear-gradient(135deg, #6b7280, #9ca3af)' : 'linear-gradient(135deg, #7c8471, #9a9e92)'}; 
                                           color: white; border: none; border-radius: 8px; padding: 10px 18px; font-size: 14px; font-weight: 500; 
                                           transition: all 0.3s ease; flex-shrink: 0;">
                                <i class="bi ${task.completed ? 'bi-arrow-clockwise' : 'bi-check-lg'} me-1"></i>
                                ${task.completed ? 'Reopen' : 'Complete'}
                            </button>
                            
                            <div class="task-details" style="flex-grow: 1; min-width: 0;">
                                <div class="task-title-row" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                                    <h6 class="task-title" style="margin: 0; font-size: 1.1rem; font-weight: 600; color: ${task.completed ? '#6b7280' : '#1e293b'}; 
                                                                      ${task.completed ? 'text-decoration: line-through;' : ''} word-break: break-word;">
                                        ${this.escapeHtml(task.title)}
                                        ${!isTaskOwner ? `<span style="background: #f3f4f6; color: #6b7280; padding: 3px 8px; border-radius: 8px; font-size: 12px; font-weight: 500; margin-left: 8px;"><i class="bi bi-lock me-1"></i>Read Only</span>` : ''}
                                    </h6>
                                </div>
                                
                                <div class="task-meta" style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                                    <div class="due-date-input" style="display: flex; align-items: center; gap: 8px;">
                                        <i class="bi bi-calendar3" style="color: #64748b; font-size: 16px;"></i>
                                        ${isTaskOwner ? `
                                            <input type="date" class="form-control form-control-sm" 
                                                   value="${task.due_date || ''}" 
                                                   onchange="app.updateDueDate('${task.id}', this.value)"
                                                   style="border: 1px solid #d1d5db; border-radius: 6px; padding: 6px 10px; font-size: 14px; width: 140px;">
                                        ` : `
                                            <span style="background: #f3f4f6; color: #6b7280; padding: 6px 10px; border-radius: 6px; font-size: 14px; font-weight: 500;">
                                                ${task.due_date || 'No due date'}
                                            </span>
                                        `}
                                    </div>
                                    
                                    ${task.due_date ? `
                                        <span style="background: ${task.completed ? '#f3f4f6' : '#fef3c7'}; 
                                                     color: ${task.completed ? '#6b7280' : '#92400e'}; 
                                                     padding: 5px 12px; border-radius: 16px; font-size: 13px; font-weight: 500; display: flex; align-items: center;">
                                            <i class="bi bi-clock me-1"></i>Due ${task.due_date}
                                        </span>
                                    ` : ''}
                                </div>
                                
                                ${isProjectOwner ? `
                                    <div class="assignee-section" style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #f1f5f9;">
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <label style="color: #64748b; font-size: 14px; font-weight: 500; margin: 0; display: flex; align-items: center;">
                                                <i class="bi bi-person me-1"></i>Assignee:
                                            </label>
                                            <select class="form-select form-select-sm" onchange="app.assignTask('${task.id}', this.value)"
                                                    style="border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; width: auto; min-width: 120px;">
                                                <option value="">Unassigned</option>
                                                ${(project.members || []).map(member => 
                                                    `<option value="${member}" ${task.assignee === member ? 'selected' : ''}>${member}</option>`
                                                ).join('')}
                                            </select>
                                        </div>
                                    </div>
                                ` : task.assignee ? `
                                    <div class="assignee-display" style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #f1f5f9;">
                                        <span style="background: #e0f2fe; color: #0277bd; padding: 6px 14px; border-radius: 16px; font-size: 13px; font-weight: 500; display: flex; align-items: center; width: fit-content;">
                                            <i class="bi bi-person-check me-1"></i>Assigned to ${task.assignee}
                                        </span>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `;
                }).join('')
            }
        </div>
    </div>
`;
        const memberInput = document.getElementById('newMemberInput');
        const datalist = document.getElementById('usersDatalist');
        if (memberInput && datalist) {
            memberInput.addEventListener('input', () => {
                const query = memberInput.value.toLowerCase();
                const optionsHtml = this.users
                    .filter(u => query.length >= 3 && u.username.toLowerCase().includes(query))
                    .slice(0, 20)
                    .map(u => `<option value="${u.username}">${u.username}</option>`)
                    .join('');
                datalist.innerHTML = optionsHtml;
            });
        }
    }



    // Replace the existing renderTeam() method with this enhanced version

    renderTeam() {
        const container = document.getElementById('teamOverview');
        
        // Get all unique team members across projects
        const allMembers = new Set();
        const memberProjects = new Map(); // Track which projects each member is in
        
        this.projects.forEach(project => {
            // Add project owner
            const ownerInfo = this.users.find(u => String(u.id) === String(project.owner_id));
            if (ownerInfo) {
                allMembers.add(ownerInfo.username);
                if (!memberProjects.has(ownerInfo.username)) {
                    memberProjects.set(ownerInfo.username, []);
                }
                memberProjects.get(ownerInfo.username).push({
                    ...project,
                    role: 'owner'
                });
            }
            
            // Add project members
            (project.members || []).forEach(member => {
                allMembers.add(member);
                if (!memberProjects.has(member)) {
                    memberProjects.set(member, []);
                }
                memberProjects.get(member).push({
                    ...project,
                    role: 'member'
                });
            });
        });

        if (allMembers.size === 0) {
            container.innerHTML = `
                <div style="text-align: center; padding: 60px 20px; background: linear-gradient(135deg, #f8fafc, #f1f5f9); border: 2px dashed #cbd5e1; border-radius: 16px;">
                    <i class="bi bi-people" style="font-size: 64px; color: #94a3b8; margin-bottom: 20px;"></i>
                    <h5 style="color: #64748b; margin-bottom: 12px; font-weight: 600;">No Team Members Yet</h5>
                    <p style="color: #94a3b8; margin: 0; font-size: 16px;">Create projects and add team members to see them here</p>
                    <div style="margin-top: 24px;">
                        <button onclick="document.querySelector('[data-bs-target=\\"#projects\\"]').click()" 
                                style="background: linear-gradient(135deg, #7c8471, #9a9e92); color: white; border: none; border-radius: 12px; padding: 12px 24px; font-weight: 600; font-size: 14px; transition: all 0.3s ease;"
                                onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 16px rgba(124,132,113,0.3)'"
                                onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                            <i class="bi bi-plus-circle-fill me-2"></i>Go to Projects
                        </button>
                    </div>
                </div>
            `;
            return;
        }

        // Team overview header
        const teamStats = {
            totalMembers: allMembers.size,
            totalProjects: this.projects.length,
            activeProjects: this.projects.filter(p => {
                const projectTasks = this.tasks.filter(t => String(t.project_id) === String(p.id));
                return projectTasks.some(t => !t.completed);
            }).length
        };

        container.innerHTML = `
            <!-- Team Overview Header -->
            <div class="team-header" style="background: linear-gradient(135deg, #f0f9ff, #e0f2fe); border: 1px solid #7dd3fc; border-radius: 16px; padding: 24px; margin-bottom: 32px; box-shadow: 0 4px 16px rgba(56, 189, 248, 0.1);">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="d-flex align-items-center">
                            <div style="width: 64px; height: 64px; background: linear-gradient(135deg, #0ea5e9, #38bdf8); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin-right: 20px; box-shadow: 0 4px 16px rgba(14, 165, 233, 0.3);">
                                <i class="bi bi-people-fill" style="color: white; font-size: 28px;"></i>
                            </div>
                            <div>
                                <h4 style="color: #0c4a6e; font-weight: 700; margin: 0; margin-bottom: 4px; font-size: 1.6rem;">Team Overview</h4>
                                <p style="color: #0369a1; margin: 0; font-size: 1.1rem;">Collaborate across ${teamStats.totalProjects} project${teamStats.totalProjects !== 1 ? 's' : ''}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="team-stats" style="display: flex; gap: 20px; justify-content: lg-end;">
                            <div style="text-align: center;">
                                <div style="font-size: 28px; font-weight: 700; color: #0c4a6e; margin-bottom: 4px;">${teamStats.totalMembers}</div>
                                <div style="font-size: 15px; color: #0369a1; font-weight: 500;">Team Members</div>
                            </div>
                            <div style="text-align: center;">
                                <div style="font-size: 28px; font-weight: 700; color: #0c4a6e; margin-bottom: 4px;">${teamStats.activeProjects}</div>
                                <div style="font-size: 15px; color: #0369a1; font-weight: 500;">Active Projects</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Projects Section -->
            <div class="projects-team-view">
                <h5 style="color: #334155; font-weight: 600; margin-bottom: 24px; display: flex; align-items: center; font-size: 1.3rem;">
                    <i class="bi bi-diagram-3 me-2" style="color: #7c8471;"></i>
                    Team by Projects
                </h5>

                <div class="projects-grid" style="display: grid; gap: 24px;">
                    ${this.projects.map(project => {
                        // Get project owner info
                        const ownerInfo = this.users.find(u => String(u.id) === String(project.owner_id));
                        const ownerName = ownerInfo ? ownerInfo.username : 'Unknown';
                        const isCurrentUserOwner = String(project.owner_id) === String(window.currentUser?.id);
                        
                        // Get project tasks and statistics
                        const projectTasks = this.tasks.filter(t => String(t.project_id) === String(project.id));
                        const completedTasks = projectTasks.filter(t => t.completed);
                        const pendingTasks = projectTasks.filter(t => !t.completed);
                        
                        // Get all project members (including owner)
                        const allProjectMembers = [...new Set([ownerName, ...(project.members || [])])];
                        
                        return `
                            <div class="project-team-card" style="background: linear-gradient(135deg, #ffffff, #fafbfc); border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(0,0,0,0.06);" 
                                onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 32px rgba(0,0,0,0.12)'; this.style.borderColor='#7c8471'" 
                                onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.06)'; this.style.borderColor='#e2e8f0'">
                                
                                <!-- Project Header -->
                                <div class="project-header" style="background: linear-gradient(135deg, #f8fafc, #f1f5f9); padding: 20px 24px; border-bottom: 1px solid #e2e8f0;">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center">
                                            <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #7c8471, #9a9e92); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-right: 16px; box-shadow: 0 4px 12px rgba(124,132,113,0.3);">
                                                <span style="color: white; font-weight: 700; font-size: 20px;">${project.name.charAt(0).toUpperCase()}</span>
                                            </div>
                                            <div>
                                                <h6 style="color: #1e293b; font-weight: 700; margin: 0; font-size: 1.2rem; margin-bottom: 4px;">${this.escapeHtml(project.name)}</h6>
                                                <div style="display: flex; align-items: center; gap: 12px;">
                                                    <span style="background: #e0f2fe; color: #0369a1; padding: 4px 10px; border-radius: 12px; font-size: 13px; font-weight: 600; display: flex; align-items: center;">
                                                        <i class="bi bi-people me-1"></i>${allProjectMembers.length} member${allProjectMembers.length !== 1 ? 's' : ''}
                                                    </span>
                                                    <span style="background: #fef3c7; color: #d97706; padding: 4px 10px; border-radius: 12px; font-size: 13px; font-weight: 600; display: flex; align-items: center;">
                                                        <i class="bi bi-list-task me-1"></i>${projectTasks.length} task${projectTasks.length !== 1 ? 's' : ''}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <button onclick="app.selectProject('${project.id}'); document.querySelector('[data-bs-target=\\"#projects\\"]').click()" 
                                                style="background: linear-gradient(135deg, #7c8471, #9a9e92); color: white; border: none; border-radius: 8px; padding: 10px 18px; font-size: 14px; font-weight: 600; transition: all 0.3s ease;"
                                                onmouseover="this.style.transform='scale(1.05)'"
                                                onmouseout="this.style.transform='scale(1)'">
                                            <i class="bi bi-arrow-right-circle me-1"></i>View
                                        </button>
                                    </div>
                                </div>

                                <!-- Project Owner Section -->
                                <div class="project-owner-section" style="padding: 20px 24px; border-bottom: 1px solid #f1f5f9;">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <h6 style="color: #64748b; font-weight: 600; margin: 0; font-size: 1rem; display: flex; align-items: center;">
                                            <i class="bi bi-crown-fill me-2" style="color: #f59e0b;"></i>Project Owner
                                        </h6>
                                        ${isCurrentUserOwner ? `
                                            <span style="background: #f0fdf4; color: #166534; padding: 6px 12px; border-radius: 16px; font-size: 13px; font-weight: 700; display: flex; align-items: center;">
                                                <i class="bi bi-shield-check me-1"></i>YOU
                                            </span>
                                        ` : ''}
                                    </div>
                                    
                                    <div class="owner-info" style="margin-top: 12px; background: ${isCurrentUserOwner ? 'linear-gradient(135deg, #f0fdf4, #dcfce7)' : 'linear-gradient(135deg, #fef3c7, #fde68a)'}; border: 1px solid ${isCurrentUserOwner ? '#bbf7d0' : '#f59e0b'}; border-radius: 12px; padding: 16px; display: flex; align-items: center;">
                                        <div style="width: 44px; height: 44px; background: ${isCurrentUserOwner ? 'linear-gradient(135deg, #16a34a, #22c55e)' : 'linear-gradient(135deg, #f59e0b, #fbbf24)'}; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 16px; box-shadow: 0 4px 12px ${isCurrentUserOwner ? 'rgba(22, 163, 74, 0.3)' : 'rgba(245, 158, 11, 0.3)'};">
                                            <span style="color: white; font-weight: 700; font-size: 18px;">${ownerName.charAt(0).toUpperCase()}</span>
                                        </div>
                                        <div style="flex-grow: 1;">
                                            <div style="font-weight: 700; color: ${isCurrentUserOwner ? '#166534' : '#92400e'}; font-size: 1.1rem; margin-bottom: 2px;">
                                                ${ownerName} ${isCurrentUserOwner ? '(You)' : ''}
                                            </div>
                                            <div style="color: ${isCurrentUserOwner ? '#22c55e' : '#d97706'}; font-size: 14px; font-weight: 500; display: flex; align-items: center;">
                                                <i class="bi bi-shield-fill-check me-1"></i>Full project access & management
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Team Members Section -->
                                <div class="team-members-section" style="padding: 20px 24px;">
                                    <h6 style="color: #64748b; font-weight: 600; margin: 0; margin-bottom: 16px; font-size: 1rem; display: flex; align-items: center;">
                                        <i class="bi bi-people me-2" style="color: #7c8471;"></i>Team Members
                                        ${(project.members || []).length > 0 ? `<span style="background: #e2e8f0; color: #64748b; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; margin-left: 8px;">${(project.members || []).length}</span>` : ''}
                                    </h6>
                                    
                                    ${(project.members || []).length === 0 ? `
                                        <div style="text-align: center; padding: 24px 16px; background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 12px;">
                                            <i class="bi bi-person-plus" style="font-size: 24px; color: #94a3b8; margin-bottom: 8px;"></i>
                                            <p style="color: #64748b; margin: 0; font-size: 13px; font-style: italic;">No team members added yet</p>
                                            ${isCurrentUserOwner ? '<small style="color: #94a3b8;">You can add members in the Projects tab</small>' : ''}
                                        </div>
                                    ` : `
                                        <div class="members-grid" style="display: grid; gap: 12px;">
                                            ${(project.members || []).map(member => {
                                                // Get member's task assignments in this project
                                                const memberTasks = projectTasks.filter(t => t.assignee === member);
                                                const memberCompletedTasks = memberTasks.filter(t => t.completed);
                                                
                                                return `
                                                    <div class="member-card" style="background: linear-gradient(135deg, #ffffff, #f8fafc); border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; transition: all 0.3s ease;" 
                                                        onmouseover="this.style.borderColor='#7c8471'; this.style.transform='translateX(4px)'" 
                                                        onmouseout="this.style.borderColor='#e2e8f0'; this.style.transform='translateX(0)'">
                                                        <div class="d-flex align-items-center justify-content-between">
                                                            <div class="d-flex align-items-center">
                                                                <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #7c8471, #9a9e92); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 14px;">
                                                                    <span style="color: white; font-weight: 700; font-size: 16px;">${member.charAt(0).toUpperCase()}</span>
                                                                </div>
                                                                <div>
                                                                    <div style="font-weight: 600; color: #374151; font-size: 1.1rem; margin-bottom: 3px;">${member}</div>
                                                                    <div style="color: #64748b; font-size: 13px; display: flex; align-items: center;">
                                                                        <i class="bi bi-person-badge me-1"></i>Team Member
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            
                                                            <!-- Member Task Stats -->
                                                            <div style="text-align: right;">
                                                                ${memberTasks.length > 0 ? `
                                                                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                                                        <span style="background: #dcfce7; color: #16a34a; padding: 3px 8px; border-radius: 8px; font-size: 12px; font-weight: 600;">${memberCompletedTasks.length}</span>
                                                                        <span style="color: #94a3b8; font-size: 12px;">/</span>
                                                                        <span style="background: #f3f4f6; color: #6b7280; padding: 3px 8px; border-radius: 8px; font-size: 12px; font-weight: 600;">${memberTasks.length}</span>
                                                                    </div>
                                                                    <div style="font-size: 12px; color: #64748b; font-weight: 500;">tasks done</div>
                                                                ` : `
                                                                    <div style="background: #f1f5f9; color: #64748b; padding: 5px 10px; border-radius: 12px; font-size: 12px; font-weight: 500;">
                                                                        No tasks
                                                                    </div>
                                                                `}
                                                            </div>
                                                        </div>
                                                    </div>
                                                `;
                                            }).join('')}
                                        </div>
                                    `}
                                </div>

                                <!-- Project Stats Footer -->
                                <div class="project-stats-footer" style="background: #f8fafc; padding: 16px 24px; border-top: 1px solid #f1f5f9;">
                                    <div class="row g-3">
                                        <div class="col-4">
                                            <div style="text-align: center;">
                                                <div style="font-size: 18px; font-weight: 700; color: #16a34a; margin-bottom: 2px;">${completedTasks.length}</div>
                                                <div style="font-size: 11px; color: #64748b; font-weight: 500;">Completed</div>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div style="text-align: center;">
                                                <div style="font-size: 18px; font-weight: 700; color: #f59e0b; margin-bottom: 2px;">${pendingTasks.length}</div>
                                                <div style="font-size: 11px; color: #64748b; font-weight: 500;">Pending</div>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div style="text-align: center;">
                                                <div style="font-size: 18px; font-weight: 700; color: #64748b; margin-bottom: 2px;">
                                                    ${projectTasks.length === 0 ? '0%' : Math.round((completedTasks.length / projectTasks.length) * 100) + '%'}
                                                </div>
                                                <div style="font-size: 11px; color: #64748b; font-weight: 500;">Progress</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
            </div>
        `;
    }

    renderCalendar() {
    const container = document.getElementById('upcomingTasks');
    const tasksWithDates = this.tasks.filter(t => t.due_date);
    if (!tasksWithDates.length) {
        container.innerHTML = `
            <div style="text-align: center; padding: 40px 20px; background: linear-gradient(135deg, #f8fafc, #f1f5f9); border: 2px dashed #cbd5e1; border-radius: 12px;">
                <i class="bi bi-calendar-x" style="font-size: 48px; color: #94a3b8; margin-bottom: 16px;"></i>
                <h6 style="color: #64748b; margin-bottom: 8px; font-size: 1.1rem;">No upcoming tasks</h6>
                <p style="color: #94a3b8; margin: 0; font-size: 1rem;">Add due dates to your tasks to see them here</p>
            </div>
        `;
        return;
    }

    const today = new Date();
    const todayString = today.getFullYear() + '-' + 
                       String(today.getMonth() + 1).padStart(2, '0') + '-' + 
                       String(today.getDate()).padStart(2, '0');
    
    // Filter out overdue tasks - only show today and future tasks
    const upcomingTasks = tasksWithDates.filter(task => {
        return task.due_date >= todayString;
    });

    if (!upcomingTasks.length) {
        container.innerHTML = `
            <div style="text-align: center; padding: 40px 20px; background: linear-gradient(135deg, #f0fdf4, #f7fee7); border: 2px dashed #bbf7d0; border-radius: 12px;">
                <i class="bi bi-calendar-check" style="font-size: 48px; color: #16a34a; margin-bottom: 16px;"></i>
                <h6 style="color: #166534; margin-bottom: 8px; font-size: 1.1rem;">All caught up!</h6>
                <p style="color: #22c55e; margin: 0; font-size: 1rem;">No upcoming tasks. Great work staying on top of things!</p>
            </div>
        `;
        return;
    }

    const sortedTasks = upcomingTasks.sort((a, b) => a.due_date.localeCompare(b.due_date));
    const tasksByDate = {};

    // Group tasks by date
    sortedTasks.forEach(task => {
        const dateKey = task.due_date;
        
        if (!tasksByDate[dateKey]) {
            const taskDate = new Date(task.due_date + 'T00:00:00'); // Add time to avoid timezone issues
            const tomorrow = new Date(today);
            tomorrow.setDate(tomorrow.getDate() + 1);
            const tomorrowString = tomorrow.getFullYear() + '-' + 
                                 String(tomorrow.getMonth() + 1).padStart(2, '0') + '-' + 
                                 String(tomorrow.getDate()).padStart(2, '0');
            
            tasksByDate[dateKey] = {
                date: taskDate,
                tasks: [],
                isToday: dateKey === todayString,
                isTomorrow: dateKey === tomorrowString,
                isThisWeek: taskDate <= new Date(today.getTime() + 7 * 24 * 60 * 60 * 1000)
            };
        }
        tasksByDate[dateKey].tasks.push(task);
    });

    const sortedDateGroups = Object.values(tasksByDate).sort((a, b) => a.date - b.date);

    container.innerHTML = `
        <div class="upcoming-header" style="background: linear-gradient(135deg, #eff6ff, #f0f9ff); border: 1px solid #bfdbfe; border-radius: 12px; padding: 22px; margin-bottom: 24px; box-shadow: 0 2px 8px rgba(59, 130, 246, 0.1);">
            <div class="d-flex align-items-center">
                <div style="width: 52px; height: 52px; background: linear-gradient(135deg, #3b82f6, #60a5fa); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-right: 18px;">
                    <i class="bi bi-calendar-event-fill" style="color: white; font-size: 22px;"></i>
                </div>
                <div>
                    <h5 style="color: #1e40af; font-weight: 600; margin: 0; margin-bottom: 4px; font-size: 1.4rem;">
                        Upcoming Tasks
                    </h5>
                    <p style="color: #1d4ed8; margin: 0; font-size: 1rem;">
                        ${sortedTasks.length} task${sortedTasks.length !== 1 ? 's' : ''} scheduled ahead
                    </p>
                </div>
            </div>
        </div>

        <div class="calendar-container" style="display: grid; gap: 20px;">
            ${sortedDateGroups.map(dateGroup => {
                const { date, tasks, isToday, isTomorrow, isThisWeek } = dateGroup;
                
                // Format date label
                let dateLabel = date.toLocaleDateString('en-US', { 
                    weekday: 'long', 
                    month: 'short', 
                    day: 'numeric' 
                });
                
                if (isToday) dateLabel = 'Today';
                else if (isTomorrow) dateLabel = 'Tomorrow';
                
                // Determine header styling based on proximity
                const headerStyle = isToday 
                    ? 'background: linear-gradient(135deg, #f0fdf4, #f7fee7); border: 1px solid #bbf7d0; color: #166534;'
                    : isTomorrow 
                    ? 'background: linear-gradient(135deg, #fef3c7, #fef7c7); border: 1px solid #fbbf24; color: #d97706;'
                    : isThisWeek
                    ? 'background: linear-gradient(135deg, #eff6ff, #f0f9ff); border: 1px solid #bfdbfe; color: #1e40af;'
                    : 'background: linear-gradient(135deg, #f8fafc, #f1f5f9); border: 1px solid #e2e8f0; color: #475569;';
                
                const iconClass = isToday 
                    ? 'bi-calendar-check-fill' 
                    : isTomorrow 
                    ? 'bi-calendar-plus-fill'
                    : 'bi-calendar-event-fill';
                
                return `
                    <div class="date-group" style="border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 16px rgba(0,0,0,0.12)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.08)'">
                        <!-- Date Header -->
                        <div class="date-header" style="${headerStyle} padding: 18px 22px; display: flex; align-items: center; justify-content: space-between;">
                            <div style="display: flex; align-items: center;">
                                <i class="bi ${iconClass} me-2" style="font-size: 18px;"></i>
                                <h6 style="margin: 0; font-weight: 600; font-size: 1.1rem;">${dateLabel}</h6>
                                ${isToday ? '<span style="background: rgba(22, 101, 52, 0.1); color: #166534; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 700; margin-left: 8px;">TODAY</span>' : ''}
                                ${isTomorrow ? '<span style="background: rgba(217, 119, 6, 0.1); color: #d97706; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 700; margin-left: 8px;">TOMORROW</span>' : ''}
                            </div>
                            <span style="background: rgba(255,255,255,0.3); color: inherit; padding: 5px 12px; border-radius: 20px; font-size: 13px; font-weight: 600;">
                                ${tasks.length} task${tasks.length !== 1 ? 's' : ''}
                            </span>
                        </div>
                        
                        <!-- Tasks List -->
                        <div class="tasks-list" style="background: linear-gradient(135deg, #ffffff, #fafbfc); padding: 10px;">
                            ${tasks.map(task => {
                                const project = this.projects.find(p => String(p.id) === String(task.project_id));
                                const taskIsCompleted = task.completed;
                                
                                return `
                                    <div class="calendar-task-item" style="background: ${taskIsCompleted ? 'linear-gradient(135deg, #f3f4f6, #f9fafb)' : 'linear-gradient(135deg, #ffffff, #fafbfc)'}; border: 1px solid ${taskIsCompleted ? '#e5e7eb' : '#e2e8f0'}; border-radius: 8px; padding: 14px 18px; margin-bottom: 10px; transition: all 0.3s ease; position: relative; overflow: hidden;" 
                                        onmouseover="this.style.transform='translateX(4px)'; this.style.borderColor='#7c8471'" 
                                        onmouseout="this.style.transform='translateX(0)'; this.style.borderColor='${taskIsCompleted ? '#e5e7eb' : '#e2e8f0'}'">
                                        
                                        <!-- Status indicator -->
                                        <div style="position: absolute; left: 0; top: 0; width: 3px; height: 100%; background: ${taskIsCompleted ? '#10b981' : isToday ? '#f59e0b' : '#3b82f6'};"></div>
                                        
                                        <div style="display: flex; align-items: center; justify-content: space-between; margin-left: 8px;">
                                            <div style="flex-grow: 1; min-width: 0;">
                                                <div style="display: flex; align-items: center; margin-bottom: 8px;">
                                                    <h6 style="margin: 0; font-size: 1rem; font-weight: 600; color: ${taskIsCompleted ? '#6b7280' : '#1f2937'}; ${taskIsCompleted ? 'text-decoration: line-through;' : ''} word-break: break-word;">
                                                        ${this.escapeHtml(task.title)}
                                                    </h6>
                                                </div>
                                                
                                                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                                    ${project ? `
                                                        <span style="background: linear-gradient(135deg, #7c8471, #9a9e92); color: white; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 500; display: flex; align-items: center;">
                                                            <i class="bi bi-folder me-1" style="font-size: 11px;"></i>${project.name}
                                                        </span>
                                                    ` : ''}
                                                    
                                                    <span style="background: ${taskIsCompleted ? '#dcfce7' : isToday ? '#fef3c7' : '#dbeafe'}; color: ${taskIsCompleted ? '#16a34a' : isToday ? '#ca8a04' : '#1e40af'}; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; display: flex; align-items: center;">
                                                        <i class="bi ${taskIsCompleted ? 'bi-check-circle' : isToday ? 'bi-exclamation-circle' : 'bi-clock'} me-1" style="font-size: 11px;"></i>
                                                        ${taskIsCompleted ? 'Completed' : isToday ? 'Due Today' : 'Upcoming'}
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <div style="margin-left: 12px;">
                                                <button onclick="app.toggleTask('${task.id}')"
                                                        style="background: ${taskIsCompleted ? 'linear-gradient(135deg, #6b7280, #9ca3af)' : 'linear-gradient(135deg, #7c8471, #9a9e92)'}; color: white; border: none; border-radius: 6px; padding: 8px 14px; font-size: 12px; font-weight: 500; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"
                                                        onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.15)'"
                                                        onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.1)'">
                                                    <i class="bi ${taskIsCompleted ? 'bi-arrow-clockwise' : 'bi-check-lg'} me-1"></i>
                                                    ${taskIsCompleted ? 'Reopen' : 'Complete'}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            }).join('')}
                        </div>
                    </div>
                `;
            }).join('')}
        </div>
    `;
}

    // Helpers (UI + API)
    addMemberToSelected() {
        const input = document.getElementById('newMemberInput');
        const username = input?.value || '';
        if (username && this.selectedProjectId) {
            this.addMember(this.selectedProjectId, username);
            input.value = '';
        }
    }

    async promptRenameProject(projectId, currentName) {
        const name = prompt('New project name:', currentName || '');
        if (!name) return;
        const body = new FormData();
        body.append('action', 'update');
        body.append('project_id', projectId);
        body.append('name', name);
        body.append('csrf_token', this.getCsrf());
        try {
            const res = await fetch('../user_api/projects.php', { method: 'POST', body });
            const data = await res.json();
            if (data.csrf_token) this.setCsrf(data.csrf_token);
            if (data.success) {
                await this.fetchProjects();
                this.render();
            } else {
                this.showToast(data.message || 'Failed to update project', 'error');
            }
        } catch (_) {
            this.showToast('Network error while renaming project', 'error');
        }
    }

    async deleteProject(projectId) {
        if (!confirm('Delete this project? This will not delete tasks but will detach them from the project.')) return;
        const body = new FormData();
        body.append('action', 'delete');
        body.append('project_id', projectId);
        body.append('csrf_token', this.getCsrf());
        try {
            const res = await fetch('../user_api/projects.php', { method: 'POST', body });
            const data = await res.json();
            if (data.csrf_token) this.setCsrf(data.csrf_token);
            if (data.success) {
                if (String(this.selectedProjectId) === String(projectId)) this.selectedProjectId = null;
                await Promise.all([this.fetchProjects(), this.fetchTasks()]);
                this.render();
            } else {
                this.showToast(data.message || 'Failed to delete project', 'error');
            }
        } catch (_) {
            this.showToast('Network error while deleting project', 'error');
        }
    }

    async assignTask(taskId, username) {
        if (!this.selectedProjectId) return;
        const body = new FormData();
        body.append('action', 'assign');
        body.append('task_id', taskId);
        body.append('username', username);
        body.append('csrf_token', this.getCsrf());
        try {
            const res = await fetch('../user_api/tasks.php', { method: 'POST', body });
            const data = await res.json();
            if (data.csrf_token) this.setCsrf(data.csrf_token);
            if (data.success) {
                await this.fetchTasks();
                this.render();
            } else {
                this.showToast(data.message || 'Failed to assign task', 'error');
            }
        } catch (_) {
            this.showToast('Network error while assigning task', 'error');
        }
    }

    async updateDueDate(taskId, dueDate) {
        const body = new FormData();
        body.append('action', 'update_due');
        body.append('task_id', taskId);
        body.append('due_date', dueDate || '');
        body.append('csrf_token', this.getCsrf());
        try {
            const res = await fetch('../user_api/tasks.php', { method: 'POST', body });
            const data = await res.json();
            if (data.csrf_token) this.setCsrf(data.csrf_token);
            if (data.success) {
                await this.fetchTasks();
                this.render();
            } else {
                this.showToast(data.message || 'Failed to update due date', 'error');
            }
        } catch (_) {
            this.showToast('Network error while updating due date', 'error');
        }
    }

    async addQuickTask() {
        const input = document.getElementById('quickTaskInput');
        const title = input.value.trim();
        if (title && this.selectedProjectId) {
            const body = new FormData();
            body.append('action', 'create');
            body.append('title', title);
            body.append('project_id', this.selectedProjectId);
            body.append('csrf_token', this.getCsrf());
            try {
                const res = await fetch('../user_api/tasks.php', { method: 'POST', body });
                const data = await res.json();
                if (data.csrf_token) this.setCsrf(data.csrf_token);
                if (data.success) {
                    await this.fetchTasks();
                    this.render();
                    this.showToast('Task added to project!');
                    input.value = '';
                } else {
                    this.showToast(data.message || 'Failed to add task', 'error');
                }
            } catch (_) {
                this.showToast('Network error while adding task', 'error');
            }
        }
    }

    async updateTaskTitle(taskId, title) {
        const body = new FormData();
        body.append('action', 'update_title');
        body.append('task_id', taskId);
        body.append('title', title);
        body.append('csrf_token', this.getCsrf());
        try {
            const res = await fetch('../user_api/tasks.php', { method: 'POST', body });
            const data = await res.json();
            if (data.csrf_token) this.setCsrf(data.csrf_token);
            if (!data.success) this.showToast(data.message || 'Failed to update task title', 'error');
        } catch (_) {
            this.showToast('Network error while updating title', 'error');
        }
    }

    async deleteTask(taskId) {
        if (!confirm('Delete this task?')) return;
        const body = new FormData();
        body.append('action', 'delete');
        body.append('task_id', taskId);
        body.append('csrf_token', this.getCsrf());
        try {
            const res = await fetch('../user_api/tasks.php', { method: 'POST', body });
            const data = await res.json();
            if (data.csrf_token) this.setCsrf(data.csrf_token);
            if (data.success) {
                await this.fetchTasks();
                this.render();
            } else {
                this.showToast(data.message || 'Failed to delete task', 'error');
            }
        } catch (_) {
            this.showToast('Network error while deleting task', 'error');
        }
    }

    updateProjectOptions() {
        const select = document.getElementById('taskProject');
        if (!select) return;
        select.innerHTML = '<option value="">Optional</option>' + this.projects.map(project => `<option value="${project.id}">${project.name}</option>`).join('');
    }

    showToast(message, type = 'success') {
        const toastContainer = document.getElementById('toastContainer');
        const toastId = 'toast_' + Date.now();
        const icon = type === 'success' ? 'bi-check-circle-fill text-success' : 'bi-exclamation-triangle-fill text-danger';
        const toastHTML = `
            <div class="toast" id="${toastId}" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header">
                    <i class="bi ${icon} me-2"></i>
                    <strong class="me-auto">TaskFlow</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">${message}</div>
            </div>`;
        toastContainer.insertAdjacentHTML('beforeend', toastHTML);
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement);
        toast.show();
        toastElement.addEventListener('hidden.bs.toast', () => toastElement.remove());
    }

    escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.app = new TaskFlowApp();
    
    // Add immediate tab switching optimization
    document.addEventListener('click', (e) => {
        const tabButton = e.target.closest('[data-bs-toggle="tab"]');
        if (tabButton && window.app) {
            const targetId = tabButton.getAttribute('data-bs-target');
            // Pre-render content immediately on click, before Bootstrap animation
            setTimeout(() => window.app.renderTabContent(targetId), 0);
        }
    });
});
