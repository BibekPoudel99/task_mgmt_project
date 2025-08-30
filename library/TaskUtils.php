<?php
require_once __DIR__ . '/Database.php';

class TaskUtils {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function updateMissedTasks() {
        $pdo = $this->db->getConnection();
        
        try {
            // Mark tasks as missed if due_date has passed and not completed
            $stmt = $pdo->prepare("
                UPDATE tasks 
                SET is_missed = 1 
                WHERE due_date < CURDATE() 
                AND completed = 0 
                AND is_missed = 0
            ");
            $stmt->execute();
            $missedCount = $stmt->rowCount();
            
            // Reset missed status for tasks that now have future due dates or no due date
            $stmt2 = $pdo->prepare("
                UPDATE tasks 
                SET is_missed = 0 
                WHERE (due_date >= CURDATE() OR due_date IS NULL)
                AND completed = 0 
                AND is_missed = 1
            ");
            $stmt2->execute();
            $resetCount = $stmt2->rowCount();
            
            return $missedCount; // Return number of tasks marked as missed
        } catch (Exception $e) {
            error_log("Error updating missed tasks: " . $e->getMessage());
            return false;
        }
    }
    
    public function getMissedTasksForUser($userId) {
        $pdo = $this->db->getConnection();
        
        try {
            $stmt = $pdo->prepare("
                SELECT t.*, p.name as project_name, p.owner_id as project_owner_id
                FROM tasks t 
                LEFT JOIN projects p ON t.project_id = p.id 
                WHERE (t.owner_id = ? OR t.assignee_id = ?) 
                AND t.is_missed = 1 
                ORDER BY t.due_date DESC
            ");
            $stmt->execute([$userId, $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting missed tasks: " . $e->getMessage());
            return [];
        }
    }
}