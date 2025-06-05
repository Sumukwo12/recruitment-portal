<?php


class Job {
    private $conn;
    private $table_name = "jobs";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getActiveJobs() {
        $query = "SELECT j.*, 
                  (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) as application_count
                  FROM " . $this->table_name . " j 
                  WHERE j.status = 'active' 
                  AND j.deadline >= CURDATE() 
                  AND j.is_visible = 1
                  ORDER BY j.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllJobs() {
        $query = "SELECT j.*, 
                  (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) as application_count
                  FROM " . $this->table_name . " j 
                  ORDER BY j.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getJobById($id) {
        $query = "SELECT j.*, 
                  (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) as application_count
                  FROM " . $this->table_name . " j 
                  WHERE j.id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getScreeningQuestions($job_id) {
        $query = "SELECT * FROM screening_questions 
                  WHERE job_id = ? 
                  ORDER BY order_index ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $job_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createJob($data) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (title, department, location, type, salary_min, salary_max, 
                   description, requirements, responsibilities, benefits, deadline) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $data['title'], $data['department'], $data['location'], 
            $data['type'], $data['salary_min'], $data['salary_max'],
            $data['description'], $data['requirements'], 
            $data['responsibilities'], $data['benefits'], $data['deadline']
        ]);
    }

    public function updateJob($id, $data) {
        $query = "UPDATE " . $this->table_name . " 
                  SET title = ?, department = ?, location = ?, type = ?, 
                      salary_min = ?, salary_max = ?, description = ?, 
                      requirements = ?, responsibilities = ?, benefits = ?, 
                      deadline = ?, status = ?
                  WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $data['title'], $data['department'], $data['location'], 
            $data['type'], $data['salary_min'], $data['salary_max'],
            $data['description'], $data['requirements'], 
            $data['responsibilities'], $data['benefits'], 
            $data['deadline'], $data['status'], $id
        ]);
    }

    public function deleteJob($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }

    public function getDashboardStats() {
        $stats = [];
        
        // Total jobs
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['total_jobs'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Active jobs
        $query = "SELECT COUNT(*) as active FROM " . $this->table_name . " 
                  WHERE status = 'active' AND deadline >= CURDATE()";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['active_jobs'] = $stmt->fetch(PDO::FETCH_ASSOC)['active'];
        
        // Total applications
        $query = "SELECT COUNT(*) as total FROM applications";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['total_applications'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // New applications (last 7 days)
        $query = "SELECT COUNT(*) as new_apps FROM applications 
                  WHERE applied_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['new_applications'] = $stmt->fetch(PDO::FETCH_ASSOC)['new_apps'];
        
        return $stats;
    }
}
?>
