<?php
require_once '../config/config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirectTo('../auth/login.php');
}

$database = new Database();
$db = $database->getConnection();

$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$job_id) {
    redirectTo('jobs.php');
}

// Get job details
$query = "SELECT * FROM jobs WHERE id = :job_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':job_id', $job_id);
$stmt->execute();

if ($stmt->rowCount() === 0) {
    redirectTo('jobs.php');
}

$job = $stmt->fetch(PDO::FETCH_ASSOC);

// Get screening questions
$query = "SELECT * FROM screening_questions WHERE job_id = :job_id ORDER BY id";
$stmt = $db->prepare($query);
$stmt->bindParam(':job_id', $job_id);
$stmt->execute();
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Validate required fields
    $required_fields = ['title', 'company', 'location', 'job_type', 'description', 'requirements', 'deadline'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
        }
    }
    
    // Validate salary
    if (!empty($_POST['salary_min']) && !empty($_POST['salary_max'])) {
        if ((float)$_POST['salary_min'] > (float)$_POST['salary_max']) {
            $errors[] = 'Minimum salary cannot be greater than maximum salary';
        }
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // Update job
            $query = "UPDATE jobs SET title = :title, company = :company, location = :location, job_type = :job_type, 
                     salary_min = :salary_min, salary_max = :salary_max, description = :description, 
                     requirements = :requirements, deadline = :deadline, status = :status 
                     WHERE id = :job_id";
            
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':title' => sanitizeInput($_POST['title']),
                ':company' => sanitizeInput($_POST['company']),
                ':location' => sanitizeInput($_POST['location']),
                ':job_type' => $_POST['job_type'],
                ':salary_min' => !empty($_POST['salary_min']) ? (float)$_POST['salary_min'] : null,
                ':salary_max' => !empty($_POST['salary_max']) ? (float)$_POST['salary_max'] : null,
                ':description' => sanitizeInput($_POST['description']),
                ':requirements' => sanitizeInput($_POST['requirements']),
                ':deadline' => $_POST['deadline'],
                ':status' => $_POST['status'],
                ':job_id' => $job_id
            ]);
            
            // Delete existing questions
            $query = "DELETE FROM screening_questions WHERE job_id = :job_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':job_id', $job_id);
            $stmt->execute();
            
            // Insert new screening questions
            if (!empty($_POST['questions'])) {
                foreach ($_POST['questions'] as $index => $question_data) {
                    if (!empty($question_data['question'])) {
                        $options = null;
                        if ($question_data['type'] === 'multiple_choice' && !empty($question_data['options'])) {
                            $options = json_encode(array_filter(explode("\n", $question_data['options'])));
                        }
                        
                        $query = "INSERT INTO screening_questions (job_id, question, question_type, options, required) 
                                 VALUES (:job_id, :question, :question_type, :options, :required)";
                        
                        $stmt = $db->prepare($query);
                        $stmt->execute([
                            ':job_id' => $job_id,
                            ':question' => sanitizeInput($question_data['question']),
                            ':question_type' => $question_data['type'],
                            ':options' => $options,
                            ':required' => isset($question_data['required']) ? 1 : 0
                        ]);
                    }
                }
            }
            
            $db->commit();
            redirectTo('jobs.php?success=Job updated successfully');
            
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = "Failed to update job. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Job - Recruit Portal</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-layout">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Recruit Portal</h2>
                <p>Admin Panel</p>
            </div>
            
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item">Dashboard</a>
                <a href="jobs.php" class="nav-item active">Manage Jobs</a>
                <a href="applications.php" class="nav-item">Applications</a>
                <a href="../auth/logout.php" class="nav-item">Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="content-header">
                <h1>Edit Job: <?php echo htmlspecialchars($job['title']); ?></h1>
                <a href="jobs.php" class="btn btn-secondary">← Back to Jobs</a>
            </header>

            <div class="dashboard-content">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="form-container">
                    <form method="POST" id="jobForm">
                        <div class="form-section">
                            <h3>Job Information</h3>
                            
                            <div class="form-group">
                                <label for="title">Job Title *</label>
                                <input type="text" id="title" name="title" required 
                                       value="<?php echo htmlspecialchars($job['title']); ?>">
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="company">Company *</label>
                                    <input type="text" id="company" name="company" required
                                           value="<?php echo htmlspecialchars($job['company']); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="location">Location *</label>
                                    <input type="text" id="location" name="location" required
                                           value="<?php echo htmlspecialchars($job['location']); ?>">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="job_type">Job Type *</label>
                                    <select id="job_type" name="job_type" required>
                                        <option value="Full-time" <?php echo $job['job_type'] === 'Full-time' ? 'selected' : ''; ?>>Full-time</option>
                                        <option value="Part-time" <?php echo $job['job_type'] === 'Part-time' ? 'selected' : ''; ?>>Part-time</option>
                                        <option value="Contract" <?php echo $job['job_type'] === 'Contract' ? 'selected' : ''; ?>>Contract</option>
                                        <option value="Internship" <?php echo $job['job_type'] === 'Internship' ? 'selected' : ''; ?>>Internship</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="status">Status *</label>
                                    <select id="status" name="status" required>
                                        <option value="Open" <?php echo $job['status'] === 'Open' ? 'selected' : ''; ?>>Open</option>
                                        <option value="Closed" <?php echo $job['status'] === 'Closed' ? 'selected' : ''; ?>>Closed</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="salary_min">Minimum Salary (KSH)</label>
                                    <input type="number" id="salary_min" name="salary_min" min="0" step="1000"
                                           value="<?php echo $job['salary_min']; ?>"
                                           placeholder="e.g. 50000">
                                </div>
                                
                                <div class="form-group">
                                    <label for="salary_max">Maximum Salary (KSH)</label>
                                    <input type="number" id="salary_max" name="salary_max" min="0" step="1000"
                                           value="<?php echo $job['salary_max']; ?>"
                                           placeholder="e.g. 80000">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="deadline">Application Deadline *</label>
                                <input type="date" id="deadline" name="deadline" required 
                                       value="<?php echo $job['deadline']; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="description">Job Description *</label>
                                <textarea id="description" name="description" rows="6" required><?php echo htmlspecialchars($job['description']); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="requirements">Requirements *</label>
                                <textarea id="requirements" name="requirements" rows="6" required><?php echo htmlspecialchars($job['requirements']); ?></textarea>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3>Screening Questions</h3>
                            <p>Add custom questions for applicants (optional)</p>
                            
                            <div id="questionsContainer">
                                <?php foreach ($questions as $index => $question): ?>
                                    <div class="question-item" data-index="<?php echo $index; ?>">
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label>Question</label>
                                                <textarea name="questions[<?php echo $index; ?>][question]" rows="2"><?php echo htmlspecialchars($question['question']); ?></textarea>
                                            </div>
                                            <div class="form-group">
                                                <label>Type</label>
                                                <select name="questions[<?php echo $index; ?>][type]" class="question-type">
                                                    <option value="short_answer" <?php echo $question['question_type'] === 'short_answer' ? 'selected' : ''; ?>>Short Answer</option>
                                                    <option value="multiple_choice" <?php echo $question['question_type'] === 'multiple_choice' ? 'selected' : ''; ?>>Multiple Choice</option>
                                                    <option value="yes_no" <?php echo $question['question_type'] === 'yes_no' ? 'selected' : ''; ?>>Yes/No</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <?php if ($question['question_type'] === 'multiple_choice'): ?>
                                            <div class="form-group options-group">
                                                <label>Options (one per line)</label>
                                                <textarea name="questions[<?php echo $index; ?>][options]" rows="3"><?php echo implode("\n", json_decode($question['options'], true) ?: []); ?></textarea>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="question-controls">
                                            <label class="checkbox-label">
                                                <input type="checkbox" name="questions[<?php echo $index; ?>][required]" <?php echo $question['required'] ? 'checked' : ''; ?>>
                                                Required
                                            </label>
                                            <button type="button" class="btn btn-sm btn-danger remove-question">Remove</button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <button type="button" id="addQuestionBtn" class="btn btn-secondary">Add Question</button>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Update Job</button>
                            <a href="jobs.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Set initial question index for existing questions
        window.questionIndex = <?php echo count($questions); ?>;
    </script>
    <script src="../assets/js/job-form.js"></script>
</body>
</html>
