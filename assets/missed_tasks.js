// Missed Tasks functionality
function loadMissedTasks() {
    fetch('../user_api/missed_tasks.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayMissedTasks(data.missed_tasks, data.count);
                updateMissedBadge(data.count);
            } else {
                document.getElementById('missed-tasks-content').innerHTML = 
                    '<div class="alert alert-danger">Error loading missed tasks</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('missed-tasks-content').innerHTML = 
                '<div class="alert alert-danger">Error loading missed tasks</div>';
        });
}

function displayMissedTasks(tasks, count) {
    const content = document.getElementById('missed-tasks-content');
    
    if (tasks.length === 0) {
        content.innerHTML = `
            <div class="text-center py-5">
                <div class="mb-3">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                </div>
                <h4 class="text-success mb-3">No Missed Tasks</h4>
                <p class="text-muted">Great job! You have no missed tasks. Keep up the excellent work staying on top of your deadlines!</p>
            </div>
        `;
        return;
    }

    let tableHTML = `
        <div class="alert alert-warning d-flex align-items-center mb-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <div>
                You have <strong>${count}</strong> missed task(s). These tasks are now overdue and cannot be marked as completed.
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
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
    `;

    tasks.forEach(task => {
        const dueDate = new Date(task.due_date);
        const today = new Date();
        const timeDiff = today.getTime() - dueDate.getTime();
        const daysOverdue = Math.ceil(timeDiff / (1000 * 3600 * 24));
        
        tableHTML += `
            <tr class="table-warning">
                <td><strong>${escapeHtml(task.title)}</strong></td>
                <td>
                    ${task.project_name 
                        ? `<span class="badge bg-info">${escapeHtml(task.project_name)}</span>` 
                        : '<span class="text-muted">No Project</span>'
                    }
                </td>
                <td class="text-danger">${dueDate.toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'})}</td>
                <td><span class="badge bg-danger">${daysOverdue} days</span></td>
                <td><span class="badge bg-danger">Missed</span></td>
                <td>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteMissedTask(${task.id})">
                        <i class="bi bi-trash me-1"></i>Delete
                    </button>
                </td>
            </tr>
        `;
    });

    tableHTML += `
                </tbody>
            </table>
        </div>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            <strong>Note:</strong> Missed tasks cannot be marked as completed. You can only delete them or extend their due dates through task management.
        </div>
    `;

    content.innerHTML = tableHTML;
}

// Add function to delete missed tasks
function deleteMissedTask(taskId) {
    if (confirm('Are you sure you want to delete this missed task?')) {
        // Call your delete task API
        fetch('../user_api/tasks.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                id: taskId,
                csrf_token: document.getElementById('csrfTokenInput').value
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload missed tasks
                loadMissedTasks();
                // Show success message
                showToast('Task deleted successfully', 'success');
            } else {
                showToast('Error deleting task: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error deleting task', 'error');
        });
    }
}

function updateMissedBadge(count) {
    const badge = document.querySelector('#missed-tab .badge');
    if (badge) {
        if (count > 0) {
            badge.textContent = count;
            badge.style.display = 'inline';
        } else {
            badge.style.display = 'none';
        }
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Initialize missed tasks functionality when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Load missed tasks when the tab is clicked
    const missedTab = document.getElementById('missed-tab');
    if (missedTab) {
        missedTab.addEventListener('click', function() {
            loadMissedTasks();
        });
    }

    // Load missed tasks count for badge on page load
    fetch('../user_api/missed_tasks.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateMissedBadge(data.count);
            }
        })
        .catch(error => console.error('Error loading missed tasks count:', error));
});