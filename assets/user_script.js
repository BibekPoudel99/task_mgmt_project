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
                statusBadge = '<span class="badge bg-danger">Missed</span>';
                actionButton = '<span class="text-muted small">Task overdue</span>';
            } else if (isCompleted) {
                // Task is completed
                statusBadge = '<span class="badge bg-success">Completed</span>';
                actionButton = `<button class="btn btn-sm btn-secondary me-3" onclick="app.toggleTask('${task.id}')">Reopen</button>`;
            } else {
                // Task is pending
                statusBadge = '<span class="badge bg-warning">Pending</span>';
                actionButton = `<button class="btn btn-sm btn-sage me-3" onclick="app.toggleTask('${task.id}')">Mark as completed</button>`;
            }

            return `
                <div class="task-item ${task.completed ? 'completed' : ''} ${isMissed ? 'missed' : ''} fade-in">
                    <div class="d-flex align-items-center">
                        ${actionButton}
                        <div class="flex-grow-1 d-flex align-items-center" style="gap:8px;">
                            <input class="form-control form-control-sm" value="${this.escapeHtml(task.title)}" onchange="app.updateTaskTitle('${task.id}', this.value)" ${isMissed ? 'readonly' : ''}/>
                            <div class="mt-1">
                                ${task.due_date ? `<span class="badge bg-secondary me-2">Due ${task.due_date}</span>` : ''}
                                ${project ? `<span class="badge badge-cream">${project.name}</span>` : ''}
                                ${statusBadge}
                            </div>
                        </div>
                        <button class="btn btn-sm btn-outline-danger ms-2" onclick="app.deleteTask('${task.id}')"><i class="bi bi-trash"></i></button>
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
                <div class="project-item ${this.selectedProjectId == project.id ? 'active' : ''}">
                    <div class="d-flex justify-content-between align-items-center" style="gap:8px;">
                        <span class="flex-grow-1" onclick="app.selectProject('${project.id}')">${this.escapeHtml(project.name)}</span>
                        <div class="btn-group btn-group-sm" role="group">
                            <button class="btn btn-outline-secondary" title="Rename" onclick="app.promptRenameProject('${project.id}', '${project.name.replace(/'/g, "&#39;")}')"><i class="bi bi-pencil"></i></button>
                            <button class="btn btn-outline-danger" title="Delete" onclick="app.deleteProject('${project.id}')"><i class="bi bi-trash"></i></button>
                        </div>
                        <span class="badge ${this.selectedProjectId == project.id ? '' : 'bg-secondary'}">${projectTasks.length - completedCount}</span>
                    </div>
                    <div class="progress mt-2" style="height: 6px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: ${percent}%" aria-valuenow="${percent}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="small text-muted mt-1">${percent}% completed</div>
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
            <div class="row mb-4">
                <div class="col-md-8">
                    <h6 class="text-olive">Add team member</h6>
                    <div class="mb-3">
                        <input class="form-control" id="newMemberInput" list="usersDatalist" placeholder="Type at least 3 characters to search" autocomplete="off" />
                        <datalist id="usersDatalist">${memberOptions}</datalist>
                        <div class="mt-2">
                            <button class="btn btn-olive" onclick="app.addMemberToSelected()">Add</button>
                        </div>
                    </div>
                    <div class="mb-3">
                        ${(project.members || []).length === 0 ?
                            '<span class="text-muted">No members yet.</span>' :
                            (project.members || []).map(member => `<span class="member-badge">${member}</span>`).join('')
                        }
                    </div>
                </div>
                <div class="col-md-4">
                    <h6 class="text-olive">Quick add task</h6>
                    <div class="input-group">
                        <input type="text" class="form-control" id="quickTaskInput" placeholder="Task title">
                        <button class="btn btn-olive" onclick="app.addQuickTask()">Add</button>
                    </div>
                </div>
            </div>
            <h6 class="text-olive mb-3">Project Tasks</h6>
            <div class="project-tasks">
                ${projectTasks.length === 0 ? '<p class="text-muted">No tasks for this project yet.</p>' : projectTasks.map(task => `
                    <div class="task-item ${task.completed ? 'completed' : ''} fade-in">
                        <div class="d-flex align-items-center w-100">
                            <button class="btn btn-sm ${task.completed ? 'btn-secondary' : 'btn-sage'} me-3" onclick="app.toggleTask('${task.id}')">${task.completed ? 'Reopen' : 'Mark as completed'}</button>
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center justify-content-between">
                                    <span class="task-title ${task.completed ? 'text-completed' : ''}">${task.title}</span>
                                    <div class="d-flex align-items-center ms-2" style="gap:8px;">
                                        <input type="date" class="form-control form-control-sm" style="width:auto" value="${task.due_date || ''}" onchange="app.updateDueDate('${task.id}', this.value)">
                                    </div>
                                </div>
                                ${isProjectOwner ? `
                                <div class="mt-2 d-flex align-items-center" style="gap:10px;">
                                    <label class="text-olive small mb-0">Assignee</label>
                                    <select class="form-select form-select-sm w-auto" onchange="app.assignTask('${task.id}', this.value)">
                                        <option value="">Unassigned</option>
                                        ${(project.members || []).map(member => `<option value=\"${member}\" ${task.assignee === member ? 'selected' : ''}>${member}</option>`).join('')}
                                    </select>
                                </div>` : ''}
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;

        // Typeahead behavior: show datalist suggestions only after 3 chars and filter progressively
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
        container.innerHTML = sortedTasks.map(task => `
            <div class="d-flex align-items-center mb-2 fade-in">
                <span class="badge bg-secondary me-3">${task.due_date}</span>
                <span class="${task.completed ? 'text-completed' : ''}">${this.escapeHtml(task.title)}</span>
            </div>
        `).join('');
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
