<?php
require_once '../config/config.php';
require_once '../models/Job.php';

requireLogin();

$jobModel = new Job();
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    $required_fields = ['title', 'department', 'location', 'type', 'description', 'deadline'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
        }
    }
    
    // Validate salary
    if (!empty($_POST['salary_min']) && !is_numeric($_POST['salary_min'])) {
        $errors['salary_min'] = 'Minimum salary must be a number';
    }
    if (!empty($_POST['salary_max']) && !is_numeric($_POST['salary_max'])) {
        $errors['salary_max'] = 'Maximum salary must be a number';
    }
    if (!empty($_POST['salary_min']) && !empty($_POST['salary_max']) && $_POST['salary_min'] > $_POST['salary_max']) {
        $errors['salary_max'] = 'Maximum salary must be greater than minimum salary';
    }
    
    // Validate deadline
    if (!empty($_POST['deadline']) && strtotime($_POST['deadline']) < time()) {
        $errors['deadline'] = 'Deadline must be in the future';
    }
    
    if (empty($errors)) {
        $job_data = [
            'title' => sanitizeInput($_POST['title']),
            'department' => sanitizeInput($_POST['department']),
            'location' => sanitizeInput($_POST['location']),
            'type' => sanitizeInput($_POST['type']),
            'salary_min' => !empty($_POST['salary_min']) ? (float)$_POST['salary_min'] : null,
            'salary_max' => !empty($_POST['salary_max']) ? (float)$_POST['salary_max'] : null,
            'description' => sanitizeInput($_POST['description']),
            'requirements' => sanitizeInput($_POST['requirements']),
            'responsibilities' => sanitizeInput($_POST['responsibilities']),
            'benefits' => sanitizeInput($_POST['benefits']),
            'deadline' => $_POST['deadline']
        ];
        
        $job_id = $jobModel->createJob($job_data);
        if ($job_id) {
            // Handle screening questions if provided
            if (isset($_POST['screening_questions']) && !empty($_POST['screening_questions'])) {
                $jobModel->saveScreeningQuestions($job_id, $_POST['screening_questions']);
            }
            
            $success = true;
            // Clear form data
            $_POST = [];
        } else {
            $errors['general'] = 'Failed to create job. Please try again.';
        }
    }
}

// Get question templates for quick selection
$questionTemplates = $jobModel->getQuestionTemplates();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Job - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="admin-body">
    <!-- Header -->
    <header class="admin-header">
        <div class="container">
            <div class="admin-header-content">
                <div class="admin-logo">
                    <i class="fas fa-plus"></i>
                    <h1>Create New Job</h1>
                </div>
                <nav class="admin-nav">
                    <a href="dashboard.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <div class="admin-container">
        <!-- Auto-save indicator -->
        <div id="autoSaveIndicator" class="auto-save-indicator" style="display: none;">
            <i class="fas fa-save"></i> Auto-saving draft...
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                Job created successfully! <a href="dashboard.php">View all jobs</a>
            </div>
        <?php endif; ?>

        <?php if (isset($errors['general'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $errors['general']; ?>
            </div>
        <?php endif; ?>

        <div class="create-job-layout">
            <form method="POST" class="job-form" id="createJobForm">
                <!-- Basic Information -->
                <div class="card">
                    <div class="card-header">
                        <h2>Basic Information</h2>
                    </div>
                    <div class="card-content">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="title">Job Title *</label>
                                <input type="text" id="title" name="title" 
                                       value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>"
                                       class="<?php echo isset($errors['title']) ? 'error' : ''; ?>"
                                       placeholder="e.g. Accounting">
                                <?php if (isset($errors['title'])): ?>
                                    <span class="error-message"><?php echo $errors['title']; ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label for="department">Department *</label>
                                <select id="department" name="department" 
                                        class="<?php echo isset($errors['department']) ? 'error' : ''; ?>"
                                        onchange="loadDepartmentQuestions()">
                                    <option value="">Select Department</option>
                                    <option value="Sheq" <?php echo (isset($_POST['department']) && $_POST['department'] === 'Sheq') ? 'selected' : ''; ?>>SHEQ</option>
                                    <option value="Procurement" <?php echo (isset($_POST['department']) && $_POST['department'] === 'Procurement') ? 'selected' : ''; ?>>Procurement</option>
                                    <option value="Planning & Design" <?php echo (isset($_POST['department']) && $_POST['department'] === 'Planning & Design') ? 'selected' : ''; ?>>Planning & Design</option>
                                    <option value="Sales & Marketing" <?php echo (isset($_POST['department']) && $_POST['department'] === 'Sales & Marketing') ? 'selected' : ''; ?>>Sales & Marketing</option>
                                    
                                    <option value="HR" <?php echo (isset($_POST['department']) && $_POST['department'] === 'HR') ? 'selected' : ''; ?>>Human Resources</option>
                                    <option value="Finance" <?php echo (isset($_POST['department']) && $_POST['department'] === 'Finance') ? 'selected' : ''; ?>>Finance</option>
                                </select>
                                <?php if (isset($errors['department'])): ?>
                                    <span class="error-message"><?php echo $errors['department']; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="location">Location *</label>
                                <input type="text" id="location" name="location" 
                                       value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>"
                                       class="<?php echo isset($errors['location']) ? 'error' : ''; ?>"
                                       placeholder="e.g. Nairobi, Remote">
                                <?php if (isset($errors['location'])): ?>
                                    <span class="error-message"><?php echo $errors['location']; ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label for="type">Employment Type *</label>
                                <select id="type" name="type" 
                                        class="<?php echo isset($errors['type']) ? 'error' : ''; ?>"
                                        onchange="loadTypeQuestions()">
                                    <option value="">Select Type</option>
                                    <option value="full-time" <?php echo (isset($_POST['type']) && $_POST['type'] === 'full-time') ? 'selected' : ''; ?>>Full-time</option>
                                    <option value="part-time" <?php echo (isset($_POST['type']) && $_POST['type'] === 'part-time') ? 'selected' : ''; ?>>Part-time</option>
                                    <option value="contract" <?php echo (isset($_POST['type']) && $_POST['type'] === 'contract') ? 'selected' : ''; ?>>Contract</option>
                                    <option value="internship" <?php echo (isset($_POST['type']) && $_POST['type'] === 'internship') ? 'selected' : ''; ?>>Internship</option>
                                </select>
                                <?php if (isset($errors['type'])): ?>
                                    <span class="error-message"><?php echo $errors['type']; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="salary_min">Minimum Salary (Ksh)</label>
                                <input type="number" id="salary_min" name="salary_min" 
                                       value="<?php echo isset($_POST['salary_min']) ? htmlspecialchars($_POST['salary_min']) : ''; ?>"
                                       class="<?php echo isset($errors['salary_min']) ? 'error' : ''; ?>"
                                       placeholder="e.g. 25000">
                                <?php if (isset($errors['salary_min'])): ?>
                                    <span class="error-message"><?php echo $errors['salary_min']; ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label for="salary_max">Maximum Salary (Ksh)</label>
                                <input type="number" id="salary_max" name="salary_max" 
                                       value="<?php echo isset($_POST['salary_max']) ? htmlspecialchars($_POST['salary_max']) : ''; ?>"
                                       class="<?php echo isset($errors['salary_max']) ? 'error' : ''; ?>"
                                       placeholder="e.g. 120000">
                                <?php if (isset($errors['salary_max'])): ?>
                                    <span class="error-message"><?php echo $errors['salary_max']; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="deadline">Application Deadline *</label>
                            <input type="date" id="deadline" name="deadline" 
                                   value="<?php echo isset($_POST['deadline']) ? htmlspecialchars($_POST['deadline']) : ''; ?>"
                                   class="<?php echo isset($errors['deadline']) ? 'error' : ''; ?>"
                                   min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                            <?php if (isset($errors['deadline'])): ?>
                                <span class="error-message"><?php echo $errors['deadline']; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Job Description -->
                <div class="card">
                    <div class="card-header">
                        <h2>Job Description</h2>
                    </div>
                    <div class="card-content">
                        <div class="form-group">
                            <label for="description">Job Description *</label>
                            <textarea id="description" name="description" rows="8" 
                                      class="<?php echo isset($errors['description']) ? 'error' : ''; ?>"
                                      placeholder="Provide a detailed description of the role, what the candidate will be doing, and what makes this opportunity exciting..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                            <?php if (isset($errors['description'])): ?>
                                <span class="error-message"><?php echo $errors['description']; ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="requirements">Requirements</label>
                            <textarea id="requirements" name="requirements" rows="6" 
                                      placeholder="List the required qualifications, skills, and experience. Put each requirement on a new line."><?php echo isset($_POST['requirements']) ? htmlspecialchars($_POST['requirements']) : ''; ?></textarea>
                            <small class="form-help">Enter each requirement on a new line</small>
                        </div>

                        <div class="form-group">
                            <label for="responsibilities">Key Responsibilities</label>
                            <textarea id="responsibilities" name="responsibilities" rows="6" 
                                      placeholder="Describe the main responsibilities and duties for this role. Put each responsibility on a new line."><?php echo isset($_POST['responsibilities']) ? htmlspecialchars($_POST['responsibilities']) : ''; ?></textarea>
                            <small class="form-help">Enter each responsibility on a new line</small>
                        </div>

                        <div class="form-group">
                            <label for="benefits">Benefits & Perks</label>
                            <textarea id="benefits" name="benefits" rows="6" 
                                      placeholder="List the benefits, perks, and what makes your company a great place to work. Put each benefit on a new line."><?php echo isset($_POST['benefits']) ? htmlspecialchars($_POST['benefits']) : ''; ?></textarea>
                            <small class="form-help">Enter each benefit on a new line</small>
                        </div>
                    </div>
                </div>

                <!-- Screening Questions Module -->
                <div class="card">
                    <div class="card-header">
                        <h2>Screening Questions</h2>
                        <div class="card-header-actions">
                            <button type="button" class="btn btn-small btn-outline" onclick="showQuestionTemplates()">
                                <i class="fas fa-templates"></i> Templates
                            </button>
                            <button type="button" class="btn btn-small btn-primary" onclick="addScreeningQuestion()">
                                <i class="fas fa-plus"></i> Add Question
                            </button>
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="screening-questions-intro">
                            <p>Add custom screening questions to help filter candidates before they apply. These questions will appear on the application form.</p>
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
                                        <button type="button" class="template-item" onclick="addTemplateQuestion('general', 'experience')">
                                            <i class="fas fa-briefcase"></i>
                                            <span>Years of Experience</span>
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
                                        </button>
                                    </div>
                                </div>
                                <div class="template-category">
                                    <h5>Technical Questions</h5>
                                    <div class="template-items">
                                        <button type="button" class="template-item" onclick="addTemplateQuestion('technical', 'programming')">
                                            <i class="fas fa-code"></i>
                                            <span>Programming Languages</span>
                                        </button>
                                        <button type="button" class="template-item" onclick="addTemplateQuestion('technical', 'frameworks')">
                                            <i class="fas fa-layer-group"></i>
                                            <span>Frameworks & Tools</span>
                                        </button>
                                        <button type="button" class="template-item" onclick="addTemplateQuestion('technical', 'portfolio')">
                                            <i class="fas fa-folder-open"></i>
                                            <span>Portfolio/GitHub</span>
                                        </button>
                                        <button type="button" class="template-item" onclick="addTemplateQuestion('technical', 'certifications')">
                                            <i class="fas fa-certificate"></i>
                                            <span>Certifications</span>
                                        </button>
                                    </div>
                                </div>
                                <div class="template-category">
                                    <h5>Role-Specific</h5>
                                    <div class="template-items">
                                        <button type="button" class="template-item" onclick="addTemplateQuestion('role', 'leadership')">
                                            <i class="fas fa-users"></i>
                                            <span>Leadership Experience</span>
                                        </button>
                                        <button type="button" class="template-item" onclick="addTemplateQuestion('role', 'remote')">
                                            <i class="fas fa-home"></i>
                                            <span>Remote Work Experience</span>
                                        </button>
                                        <button type="button" class="template-item" onclick="addTemplateQuestion('role', 'travel')">
                                            <i class="fas fa-plane"></i>
                                            <span>Travel Requirements</span>
                                        </button>
                                        <button type="button" class="template-item" onclick="addTemplateQuestion('role', 'motivation')">
                                            <i class="fas fa-heart"></i>
                                            <span>Motivation</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Screening Questions Container -->
                        <div id="screeningQuestions">
                            <div class="no-questions">
                                <div class="no-questions-content">
                                    <i class="fas fa-question-circle"></i>
                                    <h4>No screening questions added yet</h4>
                                    <p>Add screening questions to help filter candidates and gather additional information during the application process.</p>
                                    <div class="no-questions-actions">
                                        <button type="button" class="btn btn-primary" onclick="addScreeningQuestion()">
                                            <i class="fas fa-plus"></i> Add Your First Question
                                        </button>
                                        <button type="button" class="btn btn-outline" onclick="showQuestionTemplates()">
                                            <i class="fas fa-templates"></i> Use Template
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Question Preview -->
                        <div id="questionPreview" class="question-preview" style="display: none;">
                            <div class="preview-header">
                                <h4>Preview: How questions will appear to applicants</h4>
                                <button type="button" class="btn btn-small btn-outline" onclick="hideQuestionPreview()">
                                    <i class="fas fa-times"></i> Close Preview
                                </button>
                            </div>
                            <div id="previewContent" class="preview-content">
                                <!-- Preview content will be generated here -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <div class="form-actions-left">
                        <button type="button" onclick="window.history.back()" class="btn btn-outline">
                            Cancel
                        </button>
                        <button type="button" onclick="saveDraft()" class="btn btn-outline">
                            <i class="fas fa-save"></i> Save as Draft
                        </button>
                    </div>
                    <div class="form-actions-right">
                        <button type="button" onclick="previewQuestions()" class="btn btn-outline">
                            <i class="fas fa-eye"></i> Preview Questions
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Create Job
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Question Templates Data -->
    <script>
        const questionTemplates = <?php echo json_encode($questionTemplates); ?>;
    </script>

    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/screening-questions.js"></script>
    <script>
        // Initialize screening questions module
        document.addEventListener('DOMContentLoaded', function() {
            initializeScreeningQuestions();
            setupAutoSave();
        });
    </script>
</body>
</html>
