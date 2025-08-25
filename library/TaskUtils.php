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
            
            return $stmt->rowCount(); // Return number of tasks marked as missed
        } catch (Exception $e) {
            error_log("Error updating missed tasks: " . $e->getMessage());
            return false;
        }
    }
    
    public function getMissedTasksForUser($userId) {
        $pdo = $this->db->getConnection();
        
        try {
            $stmt = $pdo->prepare("
                SELECT t.*, p.name as project_name 
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