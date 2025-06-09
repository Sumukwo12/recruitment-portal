<?php
require_once __DIR__ . '/../config/database.php';

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
        $success = $stmt->execute([
            $data['title'], $data['department'], $data['location'], 
            $data['type'], $data['salary_min'], $data['salary_max'],
            $data['description'], $data['requirements'], 
            $data['responsibilities'], $data['benefits'], $data['deadline']
        ]);
        
        if ($success) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function updateJob($id, $data) {
        $query = "UPDATE " . $this->table_name . " 
                  SET title = ?, department = ?, location = ?, type = ?, 
                      salary_min = ?, salary_max = ?, description = ?, 
                      requirements = ?, responsibilities = ?, benefits = ?, 
                      deadline = ?, status = ?, updated_at = NOW()
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

    public function saveScreeningQuestions($job_id, $questions) {
        // First, delete existing questions for this job
        $deleteQuery = "DELETE FROM screening_questions WHERE job_id = ?";
        $stmt = $this->conn->prepare($deleteQuery);
        $stmt->execute([$job_id]);
        
        // Insert new questions
        if (!empty($questions)) {
            $insertQuery = "INSERT INTO screening_questions 
                           (job_id, question, type, options, required, help_text, order_index) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($insertQuery);
            
            foreach ($questions as $index => $question) {
                if (empty($question['question'])) continue;
                
                $options = null;
                if ($question['type'] === 'multiple_choice' && !empty($question['options'])) {
                    if (is_array($question['options'])) {
                        $options = json_encode($question['options']);
                    } else {
                        $optionsArray = array_filter(array_map('trim', explode("\n", $question['options'])));
                        $options = json_encode($optionsArray);
                    }
                }
                
                $stmt->execute([
                    $job_id,
                    $question['question'],
                    $question['type'],
                    $options,
                    isset($question['required']) ? 1 : 0,
                    $question['help'] ?? null,
                    $index
                ]);
            }
        }
        
        return true;
    }

    public function updateScreeningQuestions($job_id, $questions) {
        return $this->saveScreeningQuestions($job_id, $questions);
    }

    public function deleteJob($id) {
        // First delete related screening questions
        $deleteQuestionsQuery = "DELETE FROM screening_questions WHERE job_id = ?";
        $stmt = $this->conn->prepare($deleteQuestionsQuery);
        $stmt->execute([$id]);
        
        // Then delete the job
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }

    public function bulkExtendDeadlines($jobIds, $days) {
        if (empty($jobIds) || $days <= 0) {
            return false;
        }
        
        $placeholders = str_repeat('?,', count($jobIds) - 1) . '?';
        $query = "UPDATE " . $this->table_name . " 
                  SET deadline = DATE_ADD(deadline, INTERVAL ? DAY) 
                  WHERE id IN ($placeholders)";
        
        $params = array_merge([$days], $jobIds);
//$stmt = $this->conn->prepare($params);
      //  return $stmt->execute($params);
    }

    public function getRecentApplications($job_id, $limit = 5) {
        $query = "SELECT a.*, j.title as job_title 
                  FROM applications a 
                  JOIN jobs j ON a.job_id = j.id 
                  WHERE a.job_id = ? 
                  ORDER BY a.applied_at DESC 
                  LIMIT ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$job_id, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getQuestionTemplates() {
        // Return predefined question templates
        return [
            'general' => [
                [
                    'id' => 'experience',
                    'name' => 'Years of Experience',
                    'question' => 'How many years of relevant experience do you have?',
                    'type' => 'multiple_choice',
                    'options' => ['Less than 1 year', '1-2 years', '3-5 years', '5-10 years', 'More than 10 years'],
                    'required' => true
                ],
                [
                    'id' => 'availability',
                    'name' => 'Availability',
                    'question' => 'When would you be available to start?',
                    'type' => 'multiple_choice',
                    'options' => ['Immediately', 'Within 2 weeks', 'Within 1 month', 'Within 2 months', 'More than 2 months'],
                    'required' => true
                ],
                [
                    'id' => 'salary',
                    'name' => 'Salary Expectations',
                    'question' => 'What are your salary expectations for this role?',
                    'type' => 'short_answer',
                    'required' => false
                ],
                [
                    'id' => 'relocation',
                    'name' => 'Willing to Relocate',
                    'question' => 'Are you willing to relocate for this position?',
                    'type' => 'yes_no',
                    'required' => true
                ]
            ],
            'technical' => [
                [
                    'id' => 'programming',
                    'name' => 'Programming Languages',
                    'question' => 'Which programming languages are you proficient in?',
                    'type' => 'long_answer',
                    'required' => true
                ],
                [
                    'id' => 'frameworks',
                    'name' => 'Frameworks & Tools',
                    'question' => 'What frameworks and tools have you worked with?',
                    'type' => 'long_answer',
                    'required' => true
                ],
                [
                    'id' => 'portfolio',
                    'name' => 'Portfolio/GitHub',
                    'question' => 'Please provide a link to your portfolio or GitHub profile',
                    'type' => 'short_answer',
                    'required' => false
                ],
                [
                    'id' => 'certifications',
                    'name' => 'Certifications',
                    'question' => 'Do you have any relevant certifications?',
                    'type' => 'long_answer',
                    'required' => false
                ]
            ],
            'role' => [
                [
                    'id' => 'leadership',
                    'name' => 'Leadership Experience',
                    'question' => 'Do you have experience leading teams or projects?',
                    'type' => 'yes_no',
                    'required' => false
                ],
                [
                    'id' => 'remote',
                    'name' => 'Remote Work Experience',
                    'question' => 'Do you have experience working remotely?',
                    'type' => 'multiple_choice',
                    'options' => ['No experience', 'Some experience', 'Extensive experience', 'Prefer remote work'],
                    'required' => false
                ],
                [
                    'id' => 'travel',
                    'name' => 'Travel Requirements',
                    'question' => 'Are you comfortable with travel requirements (up to 25%)?',
                    'type' => 'yes_no',
                    'required' => true
                ],
                [
                    'id' => 'motivation',
                    'name' => 'Motivation',
                    'question' => 'What motivates you to apply for this position?',
                    'type' => 'long_answer',
                    'required' => true
                ]
            ]
        ];
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
