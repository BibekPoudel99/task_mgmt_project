class TaskFlowApp {
    constructor() {
        this.projects = [];
        this.tasks = [];
        this.selectedProjectId = null;
        this.storageKey = 'taskflow-dashboard';
        
        this.init();
    }
    
    init() {
        this.loadData();
        this.bindEvents();
        this.render();
        this.updateAssigneeOptions();
        this.updateProjectOptions();
    }
    
    // Data Management
    loadData() {
        const projectsData = localStorage.getItem(`${this.storageKey}:projects`);
        const tasksData = localStorage.getItem(`${this.storageKey}:tasks`);
        
        this.projects = projectsData ? JSON.parse(projectsData) : [
            { id: 'p-1', name: 'Website Revamp', members: ['Alex', 'Jordan'] }
        ];
        
        this.tasks = tasksData ? JSON.parse(tasksData) : [
            { 
                id: 't-1', 
                title: 'Draft homepage copy', 
                completed: false, 
                dueDate: new Date().toISOString().split('T')[0], 
                assignee: 'Alex', 
                projectId: 'p-1' 
            },
            { 
                id: 't-2', 
                title: 'Design wireframes', 
                completed: false, 
                dueDate: '', 
                assignee: 'Jordan', 
                projectId: 'p-1' 
            }
        ];
        
        this.selectedProjectId = this.projects.length > 0 ? this.projects[0].id : null;
    }
    
    saveData() {
        localStorage.setItem(`${this.storageKey}:projects`, JSON.stringify(this.projects));
        localStorage.setItem(`${this.storageKey}:tasks`, JSON.stringify(this.tasks));
    }
    
    // Event Binding
    bindEvents() {
        // Add Task Form
        document.getElementById('addTaskForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.addTask();
        });
        
        // Add Project Form
        document.getElementById('addProjectForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.addProject();
        });
        
        // Tab switching
        document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
            tab.addEventListener('shown.bs.tab', () => {
                this.render();
            });
        });
    }
    
    // Task Management
    addTask() {
        const title = document.getElementById('taskTitle').value.trim();
        const dueDate = document.getElementById('taskDueDate').value;
        const assignee = document.getElementById('taskAssignee').value;
        const projectId = document.getElementById('taskProject').value;
        
        if (!title) return;
        
        const task = {
            id: this.generateId(),
            title,
            completed: false,
            dueDate: dueDate || '',
            assignee: assignee || '',
            projectId: projectId || ''
        };
        
        this.tasks.unshift(task);
        this.saveData();
        this.render();
        this.showToast('Task added successfully!');
        
        // Reset form
        document.getElementById('addTaskForm').reset();
    }
    
    toggleTask(taskId) {
        const task = this.tasks.find(t => t.id === taskId);
        if (task) {
            task.completed = !task.completed;
            this.saveData();
            this.render();
            this.showToast(`Task ${task.completed ? 'completed' : 'reopened'}!`);
        }
    }
    
    updateTaskAssignee(taskId, assignee) {
        const task = this.tasks.find(t => t.id === taskId);
        if (task) {
            task.assignee = assignee;
            this.saveData();
            this.render();
            this.showToast('Task assignee updated!');
        }
    }
    
    // Project Management
    addProject() {
        const name = document.getElementById('projectName').value.trim();
        if (!name) return;
        
        const project = {
            id: this.generateId(),
            name,
            members: []
        };
        
        this.projects.unshift(project);
        this.selectedProjectId = project.id;
        this.saveData();
        this.render();
        this.updateProjectOptions();
        this.showToast('Project created successfully!');
        
        // Reset form
        document.getElementById('projectName').value = '';
    }
    
    selectProject(projectId) {
        this.selectedProjectId = projectId;
        this.render();
    }
    
    addMember(projectId, memberName) {
        const project = this.projects.find(p => p.id === projectId);
        if (project && memberName.trim()) {
            if (!project.members.includes(memberName.trim())) {
                project.members.push(memberName.trim());
                this.saveData();
                this.render();
                this.updateAssigneeOptions();
                this.showToast('Team member added!');
            }
        }
    }
    
    // Rendering Methods
    render() {
        this.renderMyDay();
        this.renderTasks();
        this.renderProjects();
        this.renderTeam();
        this.renderCalendar();
    }
    
    renderMyDay() {
        const today = new Date().toISOString().split('T')[0];
        const todayTasks = this.tasks.filter(t => !t.completed && t.dueDate === today);
        
        const container = document.getElementById('todayTasks');
        
        if (todayTasks.length === 0) {
            container.innerHTML = '<p class="text-muted">No tasks due today. Add due dates in Tasks to see them here.</p>';
            return;
        }
        
        container.innerHTML = todayTasks.map(task => `
            <div class="task-item ${task.completed ? 'completed' : ''} fade-in">
                <div class="d-flex align-items-center">
                    <input type="checkbox" class="form-check-input me-3" 
                           ${task.completed ? 'checked' : ''} 
                           onchange="app.toggleTask('${task.id}')">
                    <span class="task-title flex-grow-1 ${task.completed ? 'text-completed' : ''}">${task.title}</span>
                    ${task.assignee ? `<span class="badge badge-sage ms-auto">${task.assignee}</span>` : ''}
                </div>
            </div>
        `).join('');
    }
    
    renderTasks() {
        const container = document.getElementById('allTasks');
        
        if (this.tasks.length === 0) {
            container.innerHTML = '<p class="text-muted">No tasks yet. Add your first task above.</p>';
            return;
        }
        
        container.innerHTML = this.tasks.map(task => {
            const project = this.projects.find(p => p.id === task.projectId);
            return `
                <div class="task-item ${task.completed ? 'completed' : ''} fade-in">
                    <div class="d-flex align-items-center">
                        <input type="checkbox" class="form-check-input me-3" 
                               ${task.completed ? 'checked' : ''} 
                               onchange="app.toggleTask('${task.id}')">
                        <div class="flex-grow-1">
                            <span class="task-title ${task.completed ? 'text-completed' : ''}">${task.title}</span>
                            <div class="mt-1">
                                ${task.dueDate ? `<span class="badge bg-secondary me-2">Due ${task.dueDate}</span>` : ''}
                                ${task.assignee ? `<span class="badge badge-sage me-2">${task.assignee}</span>` : ''}
                                ${project ? `<span class="badge badge-cream">${project.name}</span>` : ''}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }
    
    renderProjects() {
        const projectsList = document.getElementById('projectsList');
        
        projectsList.innerHTML = this.projects.map(project => {
            const incompleteTasks = this.tasks.filter(t => t.projectId === project.id && !t.completed).length;
            return `
                <div class="project-item ${this.selectedProjectId === project.id ? 'active' : ''}" 
                     onclick="app.selectProject('${project.id}')">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>${project.name}</span>
                        <span class="badge ${this.selectedProjectId === project.id ? '' : 'bg-secondary'}">${incompleteTasks}</span>
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
        
        const project = this.projects.find(p => p.id === this.selectedProjectId);
        const projectTasks = this.tasks.filter(t => t.projectId === this.selectedProjectId);
        
        container.innerHTML = `
            <div class="row mb-4">
                <div class="col-md-8">
                    <h6 class="text-olive">Add team member</h6>
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="newMemberInput" placeholder="Name or email">
                        <button class="btn btn-olive" onclick="app.addMemberToSelected()">Add</button>
                    </div>
                    <div class="mb-3">
                        ${project.members.length === 0 ? 
                            '<span class="text-muted">No members yet.</span>' : 
                            project.members.map(member => `<span class="member-badge">${member}</span>`).join('')
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
                ${projectTasks.length === 0 ? 
                    '<p class="text-muted">No tasks for this project yet.</p>' :
                    projectTasks.map(task => `
                        <div class="task-item ${task.completed ? 'completed' : ''} fade-in">
                            <div class="d-flex align-items-center">
                                <input type="checkbox" class="form-check-input me-3" 
                                       ${task.completed ? 'checked' : ''} 
                                       onchange="app.toggleTask('${task.id}')">
                                <span class="task-title flex-grow-1 ${task.completed ? 'text-completed' : ''}">${task.title}</span>
                                <select class="form-select w-auto ms-auto" onchange="app.updateTaskAssignee('${task.id}', this.value)">
                                    <option value="">Assign to...</option>
                                    ${project.members.map(member => 
                                        `<option value="${member}" ${task.assignee === member ? 'selected' : ''}>${member}</option>`
                                    ).join('')}
                                </select>
                            </div>
                        </div>
                    `).join('')
                }
            </div>
        `;
    }
    
    renderTeam() {
        const container = document.getElementById('teamOverview');
        const allMembers = [...new Set(this.projects.flatMap(p => p.members))];
        
        if (allMembers.length === 0) {
            container.innerHTML = '<p class="text-muted">No team members yet. Add some under Projects.</p>';
            return;
        }
        
        container.innerHTML = allMembers.map(member => 
            `<span class="member-badge">${member}</span>`
        ).join('');
    }
    
    renderCalendar() {
        const container = document.getElementById('upcomingTasks');
        const tasksWithDates = this.tasks.filter(t => t.dueDate);
        
        if (tasksWithDates.length === 0) {
            container.innerHTML = '<p class="text-muted">No due dates set yet.</p>';
            return;
        }
        
        const sortedTasks = tasksWithDates.sort((a, b) => a.dueDate.localeCompare(b.dueDate));
        
        container.innerHTML = sortedTasks.map(task => `
            <div class="d-flex align-items-center mb-2 fade-in">
                <span class="badge bg-secondary me-3">${task.dueDate}</span>
                <span class="${task.completed ? 'text-completed' : ''}">${task.title}</span>
                ${task.assignee ? `<span class="badge badge-sage ms-auto">${task.assignee}</span>` : ''}
            </div>
        `).join('');
    }
    
    // Helper Methods
    addMemberToSelected() {
        const input = document.getElementById('newMemberInput');
        const memberName = input.value.trim();
        
        if (memberName && this.selectedProjectId) {
            this.addMember(this.selectedProjectId, memberName);
            input.value = '';
        }
    }
    
    addQuickTask() {
        const input = document.getElementById('quickTaskInput');
        const title = input.value.trim();
        
        if (title && this.selectedProjectId) {
            const task = {
                id: this.generateId(),
                title,
                completed: false,
                dueDate: '',
                assignee: '',
                projectId: this.selectedProjectId
            };
            
            this.tasks.unshift(task);
            this.saveData();
            this.render();
            this.showToast('Task added to project!');
            input.value = '';
        }
    }
    
    updateAssigneeOptions() {
        const select = document.getElementById('taskAssignee');
        const allMembers = [...new Set(this.projects.flatMap(p => p.members))];
        
        select.innerHTML = '<option value="">Select assignee</option>' +
            allMembers.map(member => `<option value="${member}">${member}</option>`).join('');
    }
    
    updateProjectOptions() {
        const select = document.getElementById('taskProject');
        
        select.innerHTML = '<option value="">Optional</option>' +
            this.projects.map(project => `<option value="${project.id}">${project.name}</option>`).join('');
    }
    
    generateId() {
        return 'id_' + Math.random().toString(36).substr(2, 9);
    }
    
    showToast(message, type = 'success') {
        const toastContainer = document.getElementById('toastContainer');
        const toastId = 'toast_' + Date.now();
        
        const toastHTML = `
            <div class="toast" id="${toastId}" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header">
                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                    <strong class="me-auto">TaskFlow</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            </div>
        `;
        
        toastContainer.insertAdjacentHTML('beforeend', toastHTML);
        
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement);
        toast.show();
        
        // Remove toast element after it's hidden
        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });
    }
}

// Initialize the app when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.app = new TaskFlowApp();
});