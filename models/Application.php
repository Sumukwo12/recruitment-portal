<?php
require_once __DIR__ . '/../config/database.php';

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
        $query = "SELECT a.*, j.title as job_title, j.department,
                         GROUP_CONCAT(
                             CASE 
                                 WHEN sq.question LIKE '%React%' OR sq.question LIKE '%experience%' 
                                 THEN sa.answer 
                             END
                         ) as react_experience,
                         GROUP_CONCAT(
                             CASE 
                                 WHEN sq.question LIKE '%work arrangement%' OR sq.question LIKE '%preference%' 
                                 THEN sa.answer 
                             END
                         ) as work_arrangement
                  FROM " . $this->table_name . " a 
                  JOIN jobs j ON a.job_id = j.id 
                  LEFT JOIN screening_answers sa ON a.id = sa.application_id
                  LEFT JOIN screening_questions sq ON sa.question_id = sq.id
                  GROUP BY a.id
                  ORDER BY a.applied_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFilteredApplications($filters) {
        $query = "SELECT a.*, j.title as job_title, j.department,
                         GROUP_CONCAT(
                             CASE 
                                 WHEN sq.question LIKE '%React%' OR sq.question LIKE '%experience%' 
                                 THEN sa.answer 
                             END
                         ) as react_experience,
                         GROUP_CONCAT(
                             CASE 
                                 WHEN sq.question LIKE '%work arrangement%' OR sq.question LIKE '%preference%' 
                                 THEN sa.answer 
                             END
                         ) as work_arrangement
                  FROM " . $this->table_name . " a 
                  JOIN jobs j ON a.job_id = j.id 
                  LEFT JOIN screening_answers sa ON a.id = sa.application_id
                  LEFT JOIN screening_questions sq ON sa.question_id = sq.id
                  WHERE 1=1";
        
        $params = [];
        
        // Search filter
        if (!empty($filters['search'])) {
            $query .= " AND (a.first_name LIKE ? OR a.last_name LIKE ? OR a.email LIKE ? OR j.title LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // Status filter
        if (!empty($filters['status'])) {
            $query .= " AND a.status = ?";
            $params[] = $filters['status'];
        }
        
        // Job filter
        if (!empty($filters['job_id'])) {
            $query .= " AND a.job_id = ?";
            $params[] = $filters['job_id'];
        }
        
        // Department filter
        if (!empty($filters['department'])) {
            $query .= " AND j.department = ?";
            $params[] = $filters['department'];
        }
        
        // Date range filters
        if (!empty($filters['date_from'])) {
            $query .= " AND DATE(a.applied_at) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $query .= " AND DATE(a.applied_at) <= ?";
            $params[] = $filters['date_to'];
        }
        
        $query .= " GROUP BY a.id";
        
        // Experience filter (applied after grouping)
        if (!empty($filters['experience'])) {
            $query .= " HAVING ";
            switch ($filters['experience']) {
                case 'entry':
                    $query .= "CAST(react_experience AS UNSIGNED) <= 2";
                    break;
                case 'mid':
                    $query .= "CAST(react_experience AS UNSIGNED) BETWEEN 3 AND 5";
                    break;
                case 'senior':
                    $query .= "CAST(react_experience AS UNSIGNED) > 5";
                    break;
            }
        }
        
        // Location filter (applied after grouping)
        if (!empty($filters['location'])) {
            if (!empty($filters['experience'])) {
                $query .= " AND ";
            } else {
                $query .= " HAVING ";
            }
            $query .= "work_arrangement LIKE ?";
            $params[] = '%' . $filters['location'] . '%';
        }
        
        $query .= " ORDER BY a.applied_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDepartments() {
        $query = "SELECT DISTINCT j.department FROM jobs j 
                  JOIN applications a ON j.id = a.job_id 
                  ORDER BY j.department";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getJobsForFilter() {
        $query = "SELECT DISTINCT j.id, j.title FROM jobs j 
                  JOIN applications a ON j.id = a.job_id 
                  ORDER BY j.title";
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

    public function bulkUpdateStatus($ids, $status) {
        if (empty($ids)) return false;
        
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $query = "UPDATE " . $this->table_name . " SET status = ? WHERE id IN ($placeholders)";
        
        $params = array_merge([$status], $ids);
        $stmt = $this->conn->prepare($query);
        return $stmt->execute($params);
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

    public function getExportData($filters) {
        $applications = $this->getFilteredApplications($filters);
        $exportData = [];
        
        foreach ($applications as $app) {
            $exportData[] = [
                'Name' => $app['first_name'] . ' ' . $app['last_name'],
                'Email' => $app['email'],
                'Phone' => $app['phone'],
                'Position' => $app['job_title'],
                'Department' => $app['department'],
                'Applied Date' => $app['applied_at'],
                'Status' => ucfirst($app['status']),
                'Experience' => $app['react_experience'] ?? 'N/A',
                'Work Preference' => $app['work_arrangement'] ?? 'N/A',
                'Location' => $app['city'] . ', ' . $app['state'],
                'Portfolio' => $app['portfolio_url'] ?? '',
                'LinkedIn' => $app['linkedin_url'] ?? '',
                'Referral Source' => $app['referral_source'] ?? ''
            ];
        }
        
        return $exportData;
    }
}
?>
