<?php
require_once '../config/config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirectTo('../auth/login.php');
}

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Validate required fields
    $required_fields = ['title', 'company', 'location', 'job_type', 'description', 'requirements', 'deadline'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
        }
    }
    
    // Validate deadline
    if (!empty($_POST['deadline']) && strtotime($_POST['deadline']) <= strtotime('today')) {
        $errors[] = 'Deadline must be in the future';
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
            
            // Insert job
            $query = "INSERT INTO jobs (title, company, location, job_type, salary_min, salary_max, description, requirements, deadline, status) 
                     VALUES (:title, :company, :location, :job_type, :salary_min, :salary_max, :description, :requirements, :deadline, :status)";
            
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
                ':status' => $_POST['status']
            ]);
            
            $job_id = $db->lastInsertId();
            
            // Insert screening questions
            if (!empty($_POST['questions'])) {
                foreach ($_POST['questions'] as $index => $question_data) {
                    if (!empty($question_data['question'])) {
                        $options = null;
                        if ($question_data['type'] === 'multiple_choice' && !empty($question_data['options'])) {
                            $options = json_encode(array_filter(explode("\n", $question_data['options'])));
                        }
                        
                        // Determine if this is a filterable question
                        $is_filterable = isset($question_data['filterable']) ? 1 : 0;
                        $filter_type = isset($question_data['filter_type']) ? $question_data['filter_type'] : null;
                        
                        $query = "INSERT INTO screening_questions (job_id, question, question_type, options, required, is_filterable, filter_type) 
                                 VALUES (:job_id, :question, :question_type, :options, :required, :is_filterable, :filter_type)";
                        
                        $stmt = $db->prepare($query);
                        $stmt->execute([
                            ':job_id' => $job_id,
                            ':question' => sanitizeInput($question_data['question']),
                            ':question_type' => $question_data['type'],
                            ':options' => $options,
                            ':required' => isset($question_data['required']) ? 1 : 0,
                            ':is_filterable' => $is_filterable,
                            ':filter_type' => $filter_type
                        ]);
                    }
                }
            }
            
            $db->commit();
            redirectTo('jobs.php?success=Job created successfully');
            
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = "Failed to create job. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Job - Recruit Portal</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="admin-layout">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Recruitment Portal</h2>
                <p>Admin dashboard</p>
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
                <h1>Create New Job</h1>
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
                                       value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="company">Company *</label>
                                    <input type="text" id="company" name="company" required
                                           value="<?php echo isset($_POST['company']) ? htmlspecialchars($_POST['company']) : ''; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="location">Location *</label>
                                    <input type="text" id="location" name="location" required
                                           value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="job_type">Job Type *</label>
                                    <select id="job_type" name="job_type" required>
                                        <option value="">Select Job Type</option>
                                        <option value="Full-time" <?php echo (isset($_POST['job_type']) && $_POST['job_type'] === 'Full-time') ? 'selected' : ''; ?>>Full-time</option>
                                        <option value="Part-time" <?php echo (isset($_POST['job_type']) && $_POST['job_type'] === 'Part-time') ? 'selected' : ''; ?>>Part-time</option>
                                        <option value="Contract" <?php echo (isset($_POST['job_type']) && $_POST['job_type'] === 'Contract') ? 'selected' : ''; ?>>Contract</option>
                                        <option value="Internship" <?php echo (isset($_POST['job_type']) && $_POST['job_type'] === 'Internship') ? 'selected' : ''; ?>>Internship</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="status">Status *</label>
                                    <select id="status" name="status" required>
                                        <option value="Open" <?php echo (isset($_POST['status']) && $_POST['status'] === 'Open') ? 'selected' : ''; ?>>Open</option>
                                        <option value="Closed" <?php echo (isset($_POST['status']) && $_POST['status'] === 'Closed') ? 'selected' : ''; ?>>Closed</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="salary_min">Minimum Salary (KSH)</label>
                                    <input type="number" id="salary_min" name="salary_min" min="0" step="1000"
                                           value="<?php echo isset($_POST['salary_min']) ? $_POST['salary_min'] : ''; ?>"
                                           placeholder="e.g. 50000">
                                </div>
                                
                                <div class="form-group">
                                    <label for="salary_max">Maximum Salary (KSH)</label>
                                    <input type="number" id="salary_max" name="salary_max" min="0" step="1000"
                                           value="<?php echo isset($_POST['salary_max']) ? $_POST['salary_max'] : ''; ?>"
                                           placeholder="e.g. 80000">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="deadline">Application Deadline *</label>
                                <input type="date" id="deadline" name="deadline" required 
                                       min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                                       value="<?php echo isset($_POST['deadline']) ? $_POST['deadline'] : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="description">Job Description *</label>
                                <textarea id="description" name="description" rows="6" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="requirements">Requirements *</label>
                                <textarea id="requirements" name="requirements" rows="6" required><?php echo isset($_POST['requirements']) ? htmlspecialchars($_POST['requirements']) : ''; ?></textarea>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3>Screening Questions</h3>
                            <p>Add custom questions for applicants (optional)</p>
                            
                            <div class="info-box">
                                <div class="info-icon"><i class="fas fa-info-circle"></i></div>
                                <div class="info-content">
                                    <strong>Filterable Questions:</strong> Mark questions as "Filterable" to enable filtering applications by the answers to these questions. This is especially useful for experience level, skills, and location preferences.
                                </div>
                            </div>
                            
                            <div id="questionsContainer">
                                <!-- Questions will be added here dynamically -->
                            </div>
                            
                            <div class="question-actions">
                                <button type="button" id="addQuestionBtn" class="btn btn-secondary">
                                    <i class="fas fa-plus"></i> Add Question
                                </button>
                                <button type="button" class="btn btn-outline" onclick="showQuestionTemplates()">
                                    <i class="fas fa-list"></i> Use Templates
                                </button>
                            </div>
                        </div>

                        <!-- Question Templates Selector -->
                        <div id="questionTemplatesPanel" class="question-templates-panel" style="display: none;">
                            <div class="templates-header">
                                <h4>Question Templates</h4>
                                <button type="button" class="btn btn-small btn-outline" onclick="hideQuestionTemplates()">
                                    <i class="fas fa-times"></i> Close
                                </button>
                            </div>
                            <div class="templates-grid">
                                <div class="template-category">
                                    <h5>General Questions</h5>
                                    <div class="template-items">
                                        
                                        <button type="button" class="template-item" onclick="addTemplateQuestion('general', 'experience_level')">
                                            <i class="fas fa-level-up-alt"></i>
                                            <span>Experience Level</span>
                                            <span class="filterable-badge">Filterable</span>
                                        </button>
                                        <button type="button" class="template-item" onclick="addTemplateQuestion('general', 'availability')">
                                            <i class="fas fa-calendar"></i>
                                            <span>Availability</span>
                                        </button>
                                        <button type="button" class="template-item" onclick="addTemplateQuestion('general', 'salary')">
                                            <i class="fas fa-dollar-sign"></i>
                                            <span>Salary Expectations</span>
                                        </button>
                                        <button type="button" class="template-item" onclick="addTemplateQuestion('general', 'relocation')">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span>Willing to Relocate</span>
                                            <span class="filterable-badge">Filterable</span>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="template-category">
                                    <h5>Education</h5>
                                    <div class="template-items">
                                        <button type="button" class="template-item" onclick="addTemplateQuestion('education', 'education_level')">
                                            <i class="fas fa-graduation-cap"></i>
                                            <span>Education Level</span>
                                            <span class="filterable-badge">Filterable</span>
                                        </button>
                                        <button type="button" class="template-item" onclick="addTemplateQuestion('education', 'field_of_study')">
                                            <i class="fas fa-book"></i>
                                            <span>Field of Study</span>
                                        </button>
                                        <button type="button" class="template-item" onclick="addTemplateQuestion('education', 'certifications')">
                                            <i class="fas fa-certificate"></i>
                                            <span>Certifications</span>
                                        </button>
                                    </div>
                                </div>
                                
                                
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Create Job</button>
                            <a href="jobs.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <style>
        /* Screening Questions Styles */
        .question-item {
            background-color: #f9f9f9;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 1rem;
            position: relative;
        }
        
        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .question-number {
            font-weight: bold;
            color: #4a5568;
        }
        
        .question-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .question-remove {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            background: none;
            border: none;
            color: #e53e3e;
            cursor: pointer;
            font-size: 1rem;
        }
        
        .question-remove:hover {
            color: #c53030;
        }
        
        .options-container {
            margin-top: 0.5rem;
            padding: 0.5rem;
            background-color: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
        }
        
        /* Info box styles */
        .info-box {
            display: flex;
            background-color: #ebf8ff;
            border: 1px solid #bee3f8;
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            align-items: center;
        }
        
        .info-icon {
            color: #3182ce;
            font-size: 1.5rem;
            margin-right: 1rem;
        }
        
        .info-content {
            flex: 1;
            font-size: 0.9rem;
        }
        
        .filterable-badge {
            display: inline-block;
            background-color: #3b82f6;
            color: white;
            font-size: 0.75rem;
            padding: 0.125rem 0.375rem;
            border-radius: 9999px;
            margin-left: 0.5rem;
        }
    </style>

    <script src="../assets/js/job-form.js"></script>
</body>
</html>
