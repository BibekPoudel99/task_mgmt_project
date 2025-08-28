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
    <div class="missed-tasks-header" style="background: linear-gradient(135deg, #fef2f2, #fef7f7); border: 1px solid #fecaca; border-radius: 12px; padding: 20px; margin-bottom: 24px; box-shadow: 0 2px 8px rgba(220, 38, 38, 0.1);">
        <div class="d-flex align-items-center">
            <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #ef4444, #f87171); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-right: 16px;">
                <i class="bi bi-exclamation-triangle-fill" style="color: white; font-size: 20px;"></i>
            </div>
            <div>
                <h5 style="color: #dc2626; font-weight: 600; margin: 0; margin-bottom: 4px;">
                    ${count} Missed Task${count !== 1 ? 's' : ''} Found
                </h5>
                <p style="color: #991b1b; margin: 0; font-size: 14px;">
                    These tasks are overdue and require your immediate attention
                </p>
            </div>
        </div>
    </div>

    <div class="missed-tasks-container" style="display: grid; gap: 16px;">
`;

tasks.forEach(task => {
    const dueDate = new Date(task.due_date);
    const today = new Date();
    const timeDiff = today.getTime() - dueDate.getTime();
    const daysOverdue = Math.ceil(timeDiff / (1000 * 3600 * 24));
    
    tableHTML += `
        <div class="missed-task-card" style="background: linear-gradient(135deg, #ffffff, #fef9f9); border: 1px solid #fecaca; border-radius: 12px; padding: 20px; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(220, 38, 38, 0.08); position: relative; overflow: hidden;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 16px rgba(220, 38, 38, 0.15)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(220, 38, 38, 0.08)'">
            <!-- Urgent indicator bar -->
            <div style="position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: linear-gradient(135deg, #ef4444, #f87171);"></div>
            
            <div class="d-flex align-items-start justify-content-between">
                <div class="task-info" style="flex-grow: 1;">
                    <div class="task-header" style="margin-bottom: 12px;">
                        <h6 style="color: #1f2937; font-weight: 600; margin: 0; margin-bottom: 8px; font-size: 16px;">
                            ${escapeHtml(task.title)}
                        </h6>
                        <div class="task-meta" style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                            ${task.project_name 
                                ? `<span style="background: linear-gradient(135deg, #3b82f6, #60a5fa); color: white; padding: 4px 10px; border-radius: 16px; font-size: 12px; font-weight: 500; display: flex; align-items: center;">
                                     <i class="bi bi-folder me-1"></i>${escapeHtml(task.project_name)}
                                   </span>` 
                                : `<span style="color: #6b7280; font-size: 12px; font-style: italic; display: flex; align-items: center;">
                                     <i class="bi bi-dash-circle me-1"></i>No Project
                                   </span>`
                            }
                            <span style="background: linear-gradient(135deg, #ef4444, #f87171); color: white; padding: 4px 10px; border-radius: 16px; font-size: 12px; font-weight: 600; display: flex; align-items: center;">
                                <i class="bi bi-exclamation-triangle me-1"></i>Overdue
                            </span>
                        </div>
                    </div>
                    
                    <div class="task-details" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                        <div class="due-date-info">
                            <label style="color: #6b7280; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; display: block;">Due Date</label>
                            <div style="display: flex; align-items: center; color: #dc2626; font-weight: 600; font-size: 14px;">
                                <i class="bi bi-calendar-x me-2"></i>
                                ${dueDate.toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'})}
                            </div>
                        </div>
                        <div class="overdue-info">
                            <label style="color: #6b7280; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; display: block;">Days Overdue</label>
                            <div style="display: flex; align-items: center;">
                                <span style="background: linear-gradient(135deg, #dc2626, #ef4444); color: white; padding: 6px 12px; border-radius: 20px; font-size: 13px; font-weight: 700; display: flex; align-items: center;">
                                    <i class="bi bi-clock-history me-1"></i>${daysOverdue} day${daysOverdue !== 1 ? 's' : ''}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="task-actions" style="margin-left: 20px;">
                    <button class="btn" onclick="deleteMissedTask(${task.id})" 
                            style="background: linear-gradient(135deg, #ef4444, #f87171); color: white; border: none; border-radius: 8px; padding: 10px 16px; font-size: 13px; font-weight: 600; transition: all 0.3s ease; display: flex; align-items: center; box-shadow: 0 2px 8px rgba(239, 68, 68, 0.2);"
                            onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0 4px 16px rgba(239, 68, 68, 0.3)'"
                            onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 8px rgba(239, 68, 68, 0.2)'"
                            title="Delete this missed task">
                        <i class="bi bi-trash-fill me-2"></i>Delete
                    </button>
                </div>
            </div>
        </div>
    `;
});

tableHTML += `
    </div>
    
    <div class="missed-tasks-footer" style="background: linear-gradient(135deg, #eff6ff, #f0f9ff); border: 1px solid #bfdbfe; border-radius: 12px; padding: 20px; margin-top: 24px; box-shadow: 0 1px 3px rgba(59, 130, 246, 0.1);">
        <div class="d-flex align-items-start">
            <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #3b82f6, #60a5fa); border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-right: 16px; flex-shrink: 0;">
                <i class="bi bi-info-circle-fill" style="color: white; font-size: 16px;"></i>
            </div>
            <div>
                <h6 style="color: #1e40af; font-weight: 600; margin: 0; margin-bottom: 4px;">Important Information</h6>
                <p style="color: #1d4ed8; margin: 0; font-size: 14px; line-height: 1.5;">
                    Missed tasks cannot be marked as completed. You can delete them here or update their due dates through the main task management interface to make them active again.
                </p>
            </div>
        </div>
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