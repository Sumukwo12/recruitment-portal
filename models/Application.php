<?php


class Application {
    private $conn;
    private $table_name = "applications";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function createApplication($data) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (job_id, first_name, last_name, email, phone, address, city, state, zip_code,
                   resume_filename, cover_letter, portfolio_url, linkedin_url, 
                   referral_source, additional_info) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        $result = $stmt->execute([
            $data['job_id'], $data['first_name'], $data['last_name'], 
            $data['email'], $data['phone'], $data['address'], 
            $data['city'], $data['state'], $data['zip_code'],
            $data['resume_filename'], $data['cover_letter'], 
            $data['portfolio_url'], $data['linkedin_url'],
            $data['referral_source'], $data['additional_info']
        ]);
        
        if ($result) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function saveScreeningAnswers($application_id, $answers) {
        foreach ($answers as $question_id => $answer) {
            $query = "INSERT INTO screening_answers (application_id, question_id, answer) 
                      VALUES (?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$application_id, $question_id, $answer]);
        }
        return true;
    }

    public function getAllApplications() {
        $query = "SELECT a.*, j.title as job_title 
                  FROM " . $this->table_name . " a 
                  JOIN jobs j ON a.job_id = j.id 
                  ORDER BY a.applied_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getApplicationById($id) {
        $query = "SELECT a.*, j.title as job_title 
                  FROM " . $this->table_name . " a 
                  JOIN jobs j ON a.job_id = j.id 
                  WHERE a.id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getApplicationsByJob($job_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE job_id = ? 
                  ORDER BY applied_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $job_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateApplicationStatus($id, $status) {
        $query = "UPDATE " . $this->table_name . " SET status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$status, $id]);
    }

    public function getScreeningAnswers($application_id) {
        $query = "SELECT sa.*, sq.question, sq.type 
                  FROM screening_answers sa 
                  JOIN screening_questions sq ON sa.question_id = sq.id 
                  WHERE sa.application_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $application_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
