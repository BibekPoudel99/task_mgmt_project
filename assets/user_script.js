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
        try {
            await fetch('../user_api/missed_tasks.php');
            await this.refreshAll();
        } catch (error) {
            console.error('Error updating missed tasks:', error);
            await this.refreshAll();
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
            tab.addEventListener('shown.bs.tab', () => {
                this.render();
            });
        });
    }

    async refreshAll() {
        await Promise.all([
            this.fetchUsers(),
            this.fetchProjects(),
            this.fetchTasks(),
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

    render() {
        this.renderMyDay();
        this.renderTasks();
        this.renderProjects();
        this.renderTeam();
        this.renderCalendar();
    }

    // ...existing renderMyDay, renderTasks+, renderProjects, renderProjectDetails, renderTeam, renderCalendar, addMemberToSelected, updateDueDate, addQuickTask, updateTaskTitle, deleteTask, updateProjectOptions, showToast, escapeHtml methods...
    renderMyDay() {
        const today = new Date().toISOString().split('T')[0];
        const todayTasks = this.tasks.filter(t => !t.completed && t.due_date === today);
        const container = document.getElementById('todayTasks');
        if (todayTasks.length === 0) {
            container.innerHTML = '<p class="text-muted">No tasks due today. Add due dates in Tasks to see them here.</p>';
            return;
        }
        container.innerHTML = todayTasks.map(task => `
            <div class="task-item ${task.completed ? 'completed' : ''} fade-in">
                <div class="d-flex align-items-center">
                    <button class="btn btn-sm btn-sage me-3" onclick="app.toggleTask('${task.id}')">${task.completed ? 'Reopen' : 'Mark as completed'}</button>
                    <span class="task-title flex-grow-1 ${task.completed ? 'text-completed' : ''}">${this.escapeHtml(task.title)}</span>
                </div>
            </div>
        `).join('');
    }

    renderTasks() {
        const container = document.getElementById('allTasks');
        if (!this.tasks.length) {
            container.innerHTML = '<p class="text-muted">No tasks yet. Add your first task above.</p>';
            return;
        }
        container.innerHTML = this.tasks.map(task => {
            const project = this.projects.find(p => String(p.id) === String(task.project_id));
            const isCompleted = task.completed == 1;
            const isMissed = task.is_missed == 1;
            
            let statusBadge = '';
            let actionButton = '';
            
            if (isMissed) {
                // Task is missed - show missed badge, no complete button
                statusBadge = '<span class="badge bg-danger task-status">Missed</span>';
                actionButton = '<span class="text-muted small">Task overdue</span>';
            } else if (isCompleted) {
                // Task is completed
                statusBadge = '<span class="badge bg-success task-status">Completed</span>';
                actionButton = `<button class="btn btn-sm btn-secondary me-3" onclick="app.toggleTask('${task.id}')">Reopen</button>`;
            } else {
                // Task is pending
                statusBadge = '<span class="badge bg-warning task-status">Pending</span>';
                actionButton = `<button class="btn btn-sm btn-sage me-3" onclick="app.toggleTask('${task.id}')">Mark as completed</button>`;
            }

            return `
                <div class="task-item ${task.completed ? 'completed' : ''} ${isMissed ? 'missed' : ''} fade-in" style="background: linear-gradient(135deg, #ffffff, #fafbfc); border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 12px; transition: all 0.3s ease; position: relative; overflow: hidden; ${task.completed ? 'opacity: 0.8;' : ''}" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px rgba(0,0,0,0.15)'; this.style.borderColor='#7c8471'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.1)'; this.style.borderColor='#e2e8f0'">
    <!-- Status indicator bar -->
    <div style="position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: ${isMissed ? '#ef4444' : task.completed ? '#10b981' : '#f59e0b'};"></div>
    
    <div class="d-flex align-items-start gap-3">
        <!-- Action Button -->
        <div class="task-action" style="flex-shrink: 0;">
            ${isMissed ? 
                `<div class="d-flex align-items-center justify-content-center" style="width: 100px; height: 36px; background: linear-gradient(135deg, #ef4444, #f87171); color: white; border-radius: 8px; font-size: 12px; font-weight: 600;">
                    <i class="bi bi-clock-history me-1"></i>Overdue
                </div>` :
                `<button class="btn" onclick="app.toggleTask('${task.id}')" 
                        style="background: ${task.completed ? 'linear-gradient(135deg, #6b7280, #9ca3af)' : 'linear-gradient(135deg, #7c8471, #9a9e92)'}; 
                               color: white; border: none; border-radius: 8px; padding: 8px 16px; font-size: 13px; font-weight: 500; 
                               transition: all 0.3s ease; white-space: nowrap;">
                    <i class="bi ${task.completed ? 'bi-arrow-clockwise' : 'bi-check-lg'} me-1"></i>
                    ${task.completed ? 'Reopen' : 'Complete'}
                </button>`
            }
        </div>

        <!-- Task Content -->
        <div class="task-content" style="flex-grow: 1; min-width: 0;">
            <!-- Task Title Row -->
            <div class="task-title-section" style="margin-bottom: 12px;">
                <input class="form-control task-title-input" 
                       value="${this.escapeHtml(task.title)}" 
                       onchange="app.updateTaskTitle('${task.id}', this.value)" 
                       ${isMissed ? 'readonly' : ''}
                       style="border: none; background: transparent; padding: 0; font-size: 18px; font-weight: 600; 
                              color: ${task.completed ? '#6b7280' : '#1e293b'}; ${task.completed ? 'text-decoration: line-through;' : ''}
                              box-shadow: none; outline: none; resize: none;">
            </div>

            <!-- Task Meta Information -->
            <div class="task-meta" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 8px;">
                ${task.due_date ? `
                    <span class="task-due-badge" style="background: ${isMissed ? '#fecaca' : task.completed ? '#f3f4f6' : '#fef3c7'}; 
                                                        color: ${isMissed ? '#dc2626' : task.completed ? '#6b7280' : '#92400e'}; 
                                                        padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 500; 
                                                        display: flex; align-items: center;">
                        <i class="bi bi-calendar3 me-1"></i>Due ${task.due_date}
                    </span>
                ` : ''}
                
                ${project ? `
                    <span class="project-badge" style="background: linear-gradient(135deg, #7c8471, #9a9e92); color: white; 
                                                       padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 500; 
                                                       display: flex; align-items: center;">
                        <i class="bi bi-folder me-1"></i>${project.name}
                    </span>
                ` : ''}
                
                <span class="task-status-badge" style="background: ${isMissed ? '#fee2e2' : task.completed ? '#dcfce7' : '#fef3c7'}; 
                                                       color: ${isMissed ? '#dc2626' : task.completed ? '#16a34a' : '#ca8a04'}; 
                                                       padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; 
                                                       display: flex; align-items: center;">
                    <i class="bi ${isMissed ? 'bi-exclamation-triangle' : task.completed ? 'bi-check-circle' : 'bi-clock'} me-1"></i>
                    ${statusBadge.replace(/<[^>]*>/g, '')}
                </span>
            </div>
        </div>

        <!-- Delete Button -->
        <div class="task-actions" style="flex-shrink: 0;">
            <button class="btn btn-outline-danger btn-sm" 
                    onclick="app.deleteTask('${task.id}')" 
                    title="Delete task"
                    style="border: 1px solid #fecaca; color: #dc2626; padding: 6px 8px; border-radius: 6px; 
                           transition: all 0.3s ease; background: transparent;"
                    onmouseover="this.style.background='#fef2f2'; this.style.borderColor='#f87171'"
                    onmouseout="this.style.background='transparent'; this.style.borderColor='#fecaca'">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>
</div>
            `;
        }).join('');
    }

    renderProjects() {
        const projectsList = document.getElementById('projectsList');
        projectsList.innerHTML = this.projects.map(project => {
            const projectTasks = this.tasks.filter(t => String(t.project_id) === String(project.id));
            const completedCount = projectTasks.filter(t => t.completed).length;
            const total = projectTasks.length || 1;
            const percent = Math.round((completedCount / total) * 100);
            return `
                <div class="project-item ${this.selectedProjectId == project.id ? 'active' : ''}" style="border-radius: 12px; padding: 16px; margin-bottom: 12px; background: linear-gradient(135deg, #ffffff, #fafafa); border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.06); transition: all 0.3s ease; cursor: pointer;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 16px rgba(0,0,0,0.12)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.06)'">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center flex-grow-1" onclick="app.selectProject('${project.id}')">
            <div class="project-icon me-3" style="width: 40px; height: 40px; border-radius: 8px; background: linear-gradient(135deg, #7c8471, #9a9e92); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 24px;">
                ${this.escapeHtml(project.name.charAt(0).toUpperCase())}
            </div>
            <div class="flex-grow-1">
                <h6 class="project-name mb-1" style="font-weight: 600; color: #374151; font-size: 24px; margin: 0;">${this.escapeHtml(project.name)}</h6>
                <p class="text-muted small mb-0" style="font-size: 13px;">${projectTasks.length} task${projectTasks.length !== 1 ? 's' : ''} • ${completedCount} completed</p>
            </div>
        </div>
        <div class="d-flex align-items-center" style="gap: 8px;">
            <span class="badge rounded-pill ${this.selectedProjectId == project.id ? 'bg-primary' : 'bg-secondary'}" style="font-size: 11px; padding: 4px 8px;">${projectTasks.length - completedCount}</span>
            <div class="btn-group btn-group-sm" role="group">
                <button class="btn btn-outline-secondary btn-sm" title="Rename" onclick="event.stopPropagation(); app.promptRenameProject('${project.id}', '${project.name.replace(/'/g, "&#39;")}')" style="border: none; padding: 4px 8px;"><i class="bi bi-pencil"></i></button>
                <button class="btn btn-outline-danger btn-sm" title="Delete" onclick="event.stopPropagation(); app.deleteProject('${project.id}')" style="border: none; padding: 4px 8px;"><i class="bi bi-trash"></i></button>
            </div>
        </div>
    </div>
    <div class="progress mb-2" style="height: 8px; border-radius: 4px; background-color: #f3f4f6;">
        <div class="progress-bar" role="progressbar" style="width: ${percent}%; background: linear-gradient(135deg, #10b981, #34d399); border-radius: 4px; transition: width 0.5s ease;" aria-valuenow="${percent}" aria-valuemin="0" aria-valuemax="100"></div>
    </div>
    <div class="d-flex justify-content-between align-items-center">
        <span class="small text-muted" style="font-size: 12px; font-weight: 500;">${percent}% completed</span>
        <span class="small text-muted" style="font-size: 12px;">${projectTasks.length - completedCount} remaining</span>
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
        container.innerHTML = `
    <div class="project-header" style="background: linear-gradient(135deg, #ffffff, #f8fafc); border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="team-section">
                    <h5 style="color: #334155; font-weight: 600; margin-bottom: 16px; display: flex; align-items: center;">
                        <i class="bi bi-people-fill me-2" style="color: #7c8471;"></i>
                        Team Members
                    </h5>
                    <div class="member-input-group" style="margin-bottom: 16px;">
                        <div class="input-group" style="border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                            <input class="form-control" id="newMemberInput" list="usersDatalist" 
                                   placeholder="Search team members..." autocomplete="off"
                                   style="border: 1px solid #d1d5db; border-right: none; padding: 12px 16px; font-size: 14px; background: #ffffff;"/>
                            <datalist id="usersDatalist">${memberOptions}</datalist>
                            <button class="btn" onclick="app.addMemberToSelected()" 
                                    style="background: linear-gradient(135deg, #7c8471, #9a9e92); color: white; border: none; padding: 12px 20px; font-weight: 500;">
                                <i class="bi bi-plus-lg"></i> Add
                            </button>
                        </div>
                    </div>
                    <div class="members-display" style="min-height: 40px;">
                        ${(project.members || []).length === 0 
                            ? '<div style="padding: 12px; background: #f1f5f9; border-radius: 8px; text-align: center; color: #64748b; font-style: italic;">No team members added yet</div>' 
                            : `<div style="display: flex; flex-wrap: wrap; gap: 8px;">${(project.members || []).map(member => 
                                `<span style="background: linear-gradient(135deg, #7c8471, #9a9e92); color: white; padding: 6px 12px; border-radius: 20px; font-size: 13px; font-weight: 500; display: flex; align-items: center;">
                                    <i class="bi bi-person-circle me-1"></i>${member}
                                </span>`).join('')}</div>`
                        }
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="quick-task-section">
                    <h5 style="color: #334155; font-weight: 600; margin-bottom: 16px; display: flex; align-items: center;">
                        <i class="bi bi-lightning-fill me-2" style="color: #7c8471;"></i>
                        Quick Add Task
                    </h5>
                    <div class="input-group" style="border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <input type="text" class="form-control" id="quickTaskInput" 
                               placeholder="Enter task title..."
                               style="border: 1px solid #d1d5db; border-right: none; padding: 12px 16px; font-size: 14px; background: #ffffff;">
                        <button class="btn" onclick="app.addQuickTask()" 
                                style="background: linear-gradient(135deg, #059669, #10b981); color: white; border: none; padding: 12px 20px; font-weight: 500;">
                            <i class="bi bi-plus-circle-fill"></i> Add
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="tasks-section">
        <div class="tasks-header" style="display: flex; align-items: center; justify-content: between; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid #e2e8f0;">
            <h5 style="color: #334155; font-weight: 600; margin: 0; display: flex; align-items: center;">
                <i class="bi bi-check2-square me-2" style="color: #7c8471;"></i>
                Project Tasks
                <span style="background: #e2e8f0; color: #64748b; padding: 2px 8px; border-radius: 12px; font-size: 12px; font-weight: 500; margin-left: 12px;">
                    ${projectTasks.length} total
                </span>
            </h5>
        </div>
        
        <div class="tasks-container" style="display: grid; gap: 12px;">
            ${projectTasks.length === 0 
                ? `<div style="text-align: center; padding: 40px 20px; background: linear-gradient(135deg, #f8fafc, #f1f5f9); border: 2px dashed #cbd5e1; border-radius: 12px;">
                     <i class="bi bi-inbox" style="font-size: 48px; color: #94a3b8; margin-bottom: 16px;"></i>
                     <h6 style="color: #64748b; margin-bottom: 8px;">No tasks yet</h6>
                     <p style="color: #94a3b8; margin: 0; font-size: 14px;">Use the quick add above to create your first task</p>
                   </div>` 
                : projectTasks.map(task => `
                    <div class="task-card" style="background: linear-gradient(135deg, #ffffff, #fafbfc); border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; transition: all 0.3s ease; position: relative; overflow: hidden;" 
                         onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px rgba(0,0,0,0.15)'; this.style.borderColor='#7c8471'" 
                         onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.1)'; this.style.borderColor='#e2e8f0'">
                        
                        <div style="position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: ${task.completed ? '#10b981' : '#f59e0b'};"></div>
                        
                        <div class="task-content" style="display: flex; align-items: flex-start; gap: 16px;">
                            <button class="task-action-btn" onclick="app.toggleTask('${task.id}')" 
                                    style="background: ${task.completed ? 'linear-gradient(135deg, #6b7280, #9ca3af)' : 'linear-gradient(135deg, #7c8471, #9a9e92)'}; 
                                           color: white; border: none; border-radius: 8px; padding: 8px 16px; font-size: 13px; font-weight: 500; 
                                           transition: all 0.3s ease; flex-shrink: 0;">
                                <i class="bi ${task.completed ? 'bi-arrow-clockwise' : 'bi-check-lg'} me-1"></i>
                                ${task.completed ? 'Reopen' : 'Complete'}
                            </button>
                            
                            <div class="task-details" style="flex-grow: 1; min-width: 0;">
                                <div class="task-title-row" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                                    <h6 class="task-title" style="margin: 0; font-size: 24px; font-weight: 600; color: ${task.completed ? '#6b7280' : '#1e293b'}; 
                                                                      ${task.completed ? 'text-decoration: line-through;' : ''} word-break: break-word;">
                                        ${this.escapeHtml(task.title)}
                                    </h6>
                                </div>
                                
                                <div class="task-meta" style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                                    <div class="due-date-input" style="display: flex; align-items: center; gap: 8px;">
                                        <i class="bi bi-calendar3" style="color: #64748b; font-size: 14px;"></i>
                                        <input type="date" class="form-control form-control-sm" 
                                               value="${task.due_date || ''}" 
                                               onchange="app.updateDueDate('${task.id}', this.value)"
                                               style="border: 1px solid #d1d5db; border-radius: 6px; padding: 4px 8px; font-size: 13px; width: 140px; background: #ffffff;">
                                    </div>
                                    
                                    ${task.due_date ? `
                                        <span style="background: ${task.completed ? '#f3f4f6' : '#fef3c7'}; 
                                                     color: ${task.completed ? '#6b7280' : '#92400e'}; 
                                                     padding: 4px 10px; border-radius: 16px; font-size: 12px; font-weight: 500; display: flex; align-items: center;">
                                            <i class="bi bi-clock me-1"></i>Due ${task.due_date}
                                        </span>
                                    ` : ''}
                                </div>
                                
                                ${isProjectOwner ? `
                                    <div class="assignee-section" style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #f1f5f9;">
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <label style="color: #64748b; font-size: 13px; font-weight: 500; margin: 0; display: flex; align-items: center;">
                                                <i class="bi bi-person me-1"></i>Assignee:
                                            </label>
                                            <select class="form-select form-select-sm" onchange="app.assignTask('${task.id}', this.value)"
                                                    style="border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; width: auto; min-width: 120px; background: #ffffff;">
                                                <option value="">Unassigned</option>
                                                ${(project.members || []).map(member => 
                                                    `<option value="${member}" ${task.assignee === member ? 'selected' : ''}>${member}</option>`
                                                ).join('')}
                                            </select>
                                        </div>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `).join('')
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

    renderTeam() {
        const container = document.getElementById('teamOverview');
        const allMembers = new Set();
        this.projects.forEach(p => (p.members || []).forEach(m => allMembers.add(m)));
        if (allMembers.size === 0) {
            container.innerHTML = '<p class="text-muted">No team members yet. Add some under Projects.</p>';
            return;
        }
        container.innerHTML = Array.from(allMembers).map(member => `<span class="member-badge">${member}</span>`).join('');
    }

    renderCalendar() {
        const container = document.getElementById('upcomingTasks');
        const tasksWithDates = this.tasks.filter(t => t.due_date);
        if (!tasksWithDates.length) {
            container.innerHTML = '<p class="text-muted">No due dates set yet.</p>';
            return;
        }
        const sortedTasks = tasksWithDates.sort((a, b) => a.due_date.localeCompare(b.due_date));
        // ...existing code...
const today = new Date();
const tasksByDate = {};

// Group tasks by date
sortedTasks.forEach(task => {
    const taskDate = new Date(task.due_date);
    const dateKey = task.due_date;
    
    if (!tasksByDate[dateKey]) {
        tasksByDate[dateKey] = {
            date: taskDate,
            tasks: [],
            isOverdue: taskDate < today && !task.completed,
            isToday: dateKey === today.toISOString().split('T')[0],
            isTomorrow: dateKey === new Date(today.getTime() + 24 * 60 * 60 * 1000).toISOString().split('T')[0]
        };
    }
    tasksByDate[dateKey].tasks.push(task);
});

const sortedDateGroups = Object.values(tasksByDate).sort((a, b) => a.date - b.date);

container.innerHTML = `
    <div class="calendar-container" style="display: grid; gap: 20px;">
        ${sortedDateGroups.map(dateGroup => {
            const { date, tasks, isOverdue, isToday, isTomorrow } = dateGroup;
            
            // Format date label
            let dateLabel = date.toLocaleDateString('en-US', { 
                weekday: 'long', 
                month: 'short', 
                day: 'numeric' 
            });
            
            if (isToday) dateLabel = 'Today';
            else if (isTomorrow) dateLabel = 'Tomorrow';
            else if (isOverdue) dateLabel = `${dateLabel} (Overdue)`;
            
            // Determine header styling
            const headerStyle = isOverdue 
                ? 'background: linear-gradient(135deg, #fef2f2, #fef7f7); border: 1px solid #fecaca; color: #dc2626;'
                : isToday 
                ? 'background: linear-gradient(135deg, #f0fdf4, #f7fee7); border: 1px solid #bbf7d0; color: #166534;'
                : 'background: linear-gradient(135deg, #eff6ff, #f0f9ff); border: 1px solid #bfdbfe; color: #1e40af;';
            
            const iconClass = isOverdue 
                ? 'bi-exclamation-triangle-fill' 
                : isToday 
                ? 'bi-calendar-check-fill' 
                : 'bi-calendar-event-fill';
            
            return `
                <div class="date-group" style="border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 16px rgba(0,0,0,0.12)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.08)'">
                    <!-- Date Header -->
                    <div class="date-header" style="${headerStyle} padding: 16px 20px; display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center;">
                            <i class="bi ${iconClass} me-2" style="font-size: 16px;"></i>
                            <h6 style="margin: 0; font-weight: 600; font-size: 15px;">${dateLabel}</h6>
                        </div>
                        <span style="background: rgba(255,255,255,0.3); color: inherit; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                            ${tasks.length} task${tasks.length !== 1 ? 's' : ''}
                        </span>
                    </div>
                    
                    <!-- Tasks List -->
                    <div class="tasks-list" style="background: linear-gradient(135deg, #ffffff, #fafbfc); padding: 8px;">
                        ${tasks
    .filter(task => !(isOverdue && !task.completed)) // ⛔ remove overdue (not completed) tasks
    .map(task => {
        const project = this.projects.find(p => String(p.id) === String(task.project_id));
        const taskIsCompleted = task.completed;

        return `
            <div class="calendar-task-item" style="background: ${taskIsCompleted ? 'linear-gradient(135deg, #f3f4f6, #f9fafb)' : 'linear-gradient(135deg, #ffffff, #fafbfc)'}; border: 1px solid ${taskIsCompleted ? '#e5e7eb' : '#e2e8f0'}; border-radius: 8px; padding: 12px 16px; margin-bottom: 8px; transition: all 0.3s ease; position: relative; overflow: hidden;" 
                onmouseover="this.style.transform='translateX(4px)'; this.style.borderColor='#7c8471'" 
                onmouseout="this.style.transform='translateX(0)'; this.style.borderColor='${taskIsCompleted ? '#e5e7eb' : '#e2e8f0'}'">
                
                <!-- Status indicator -->
                <div style="position: absolute; left: 0; top: 0; width: 3px; height: 100%; background: ${taskIsCompleted ? '#10b981' : '#f59e0b'};"></div>

                <div style="display: flex; align-items: center; justify-content: space-between; margin-left: 8px;">
                    <div style="flex-grow: 1; min-width: 0;">
                        <div style="display: flex; align-items: center; margin-bottom: 6px;">
                            <h6 style="margin: 0; font-size: 14px; font-weight: 600; color: ${taskIsCompleted ? '#6b7280' : '#1f2937'}; ${taskIsCompleted ? 'text-decoration: line-through;' : ''} word-break: break-word;">
                                ${this.escapeHtml(task.title)}
                            </h6>
                        </div>

                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            ${project ? `
                                <span style="background: linear-gradient(135deg, #7c8471, #9a9e92); color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 500; display: flex; align-items: center;">
                                    <i class="bi bi-folder me-1" style="font-size: 10px;"></i>${project.name}
                                </span>
                            ` : ''}

                            <span style="background: ${taskIsCompleted ? '#dcfce7' : '#fef3c7'}; color: ${taskIsCompleted ? '#16a34a' : '#ca8a04'}; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; display: flex; align-items: center;">
                                <i class="bi ${taskIsCompleted ? 'bi-check-circle' : 'bi-clock'} me-1" style="font-size: 10px;"></i>
                                ${taskIsCompleted ? 'Done' : 'Pending'}
                            </span>
                        </div>
                    </div>

                    <div style="margin-left: 12px;">
                        <button onclick="app.toggleTask('${task.id}')"
                                style="background: ${taskIsCompleted ? 'linear-gradient(135deg, #6b7280, #9ca3af)' : 'linear-gradient(135deg, #7c8471, #9a9e92)'}; color: white; border: none; border-radius: 6px; padding: 6px 12px; font-size: 11px; font-weight: 500; transition: all 0.3s ease;"
                                onmouseover="this.style.transform='scale(1.05)'"
                                onmouseout="this.style.transform='scale(1)'">
                            <i class="bi ${taskIsCompleted ? 'bi-arrow-clockwise' : 'bi-check-lg'} me-1"></i>
                            ${taskIsCompleted ? 'Reopen' : 'Done'}
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
});
