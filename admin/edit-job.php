<?php
require_once '../config/config.php';
require_once '../models/Job.php';

requireLogin();

$jobModel = new Job();
$errors = [];
$success = false;
$job = null;

// Get job ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('dashboard.php');
}

$jobId = $_GET['id'];
$job = $jobModel->getJobById($jobId);

if (!$job) {
    redirect('dashboard.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    $required_fields = ['title', 'department', 'location', 'type', 'description', 'deadline', 'status'];
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
    if (!empty($_POST['deadline']) && strtotime($_POST['deadline']) < strtotime('-1 day')) {
        $errors['deadline'] = 'Deadline cannot be in the past';
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
            'deadline' => $_POST['deadline'],
            'status' => sanitizeInput($_POST['status'])
        ];
        
        if ($jobModel->updateJob($jobId, $job_data)) {
            $success = true;
            // Refresh job data
            $job = $jobModel->getJobById($jobId);
            
            // Handle screening questions if provided
            if (isset($_POST['screening_questions'])) {
                $jobModel->updateScreeningQuestions($jobId, $_POST['screening_questions']);
            }
        } else {
            $errors['general'] = 'Failed to update job. Please try again.';
        }
    }
}

// Get screening questions
$screeningQuestions = $jobModel->getScreeningQuestions($jobId);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Job: <?php echo htmlspecialchars($job['title']); ?> - Admin</title>
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
                    <i class="fas fa-edit"></i>
                    <h1>Edit Job</h1>
                </div>
                <nav class="admin-nav">
                    <a href="view-job.php?id=<?php echo $job['id']; ?>" class="btn btn-outline">
                        <i class="fas fa-eye"></i> View Job
                    </a>
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
            <i class="fas fa-save"></i> Auto-saving...
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                Job updated successfully! 
                <a href="view-job.php?id=<?php echo $job['id']; ?>">View updated job</a> | 
                <a href="dashboard.php">Back to dashboard</a>
            </div>
        <?php endif; ?>

        <?php if (isset($errors['general'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $errors['general']; ?>
            </div>
        <?php endif; ?>

        <div class="edit-job-layout">
            <div class="edit-job-main">
                <form method="POST" class="job-form" id="editJobForm">
                    <!-- Basic Information -->
                    <div class="card">
                        <div class="card-header">
                            <h2>Basic Information</h2>
                            <div class="job-status-indicator">
                                <span class="badge badge-<?php echo $job['status'] === 'active' ? 'success' : ($job['status'] === 'closed' ? 'secondary' : 'danger'); ?>">
                                    <?php echo ucfirst($job['status']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-content">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="title">Job Title *</label>
                                    <input type="text" id="title" name="title" 
                                           value="<?php echo htmlspecialchars($job['title']); ?>"
                                           class="<?php echo isset($errors['title']) ? 'error' : ''; ?>"
                                           placeholder="e.g. Senior Software Engineer">
                                    <?php if (isset($errors['title'])): ?>
                                        <span class="error-message"><?php echo $errors['title']; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label for="department">Department *</label>
                                    <select id="department" name="department" 
                                            class="<?php echo isset($errors['department']) ? 'error' : ''; ?>">
                                        <option value="">Select Department</option>
                                        <option value="Engineering" <?php echo $job['department'] === 'Engineering' ? 'selected' : ''; ?>>Engineering</option>
                                        <option value="Product" <?php echo $job['department'] === 'Product' ? 'selected' : ''; ?>>Product</option>
                                        <option value="Design" <?php echo $job['department'] === 'Design' ? 'selected' : ''; ?>>Design</option>
                                        <option value="Marketing" <?php echo $job['department'] === 'Marketing' ? 'selected' : ''; ?>>Marketing</option>
                                        <option value="Sales" <?php echo $job['department'] === 'Sales' ? 'selected' : ''; ?>>Sales</option>
                                        <option value="HR" <?php echo $job['department'] === 'HR' ? 'selected' : ''; ?>>Human Resources</option>
                                        <option value="Finance" <?php echo $job['department'] === 'Finance' ? 'selected' : ''; ?>>Finance</option>
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
                                           value="<?php echo htmlspecialchars($job['location']); ?>"
                                           class="<?php echo isset($errors['location']) ? 'error' : ''; ?>"
                                           placeholder="e.g. San Francisco, CA or Remote">
                                    <?php if (isset($errors['location'])): ?>
                                        <span class="error-message"><?php echo $errors['location']; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label for="type">Employment Type *</label>
                                    <select id="type" name="type" 
                                            class="<?php echo isset($errors['type']) ? 'error' : ''; ?>">
                                        <option value="">Select Type</option>
                                        <option value="full-time" <?php echo $job['type'] === 'full-time' ? 'selected' : ''; ?>>Full-time</option>
                                        <option value="part-time" <?php echo $job['type'] === 'part-time' ? 'selected' : ''; ?>>Part-time</option>
                                        <option value="contract" <?php echo $job['type'] === 'contract' ? 'selected' : ''; ?>>Contract</option>
                                        <option value="internship" <?php echo $job['type'] === 'internship' ? 'selected' : ''; ?>>Internship</option>
                                    </select>
                                    <?php if (isset($errors['type'])): ?>
                                        <span class="error-message"><?php echo $errors['type']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="salary_min">Minimum Salary (KSH)</label>
                                    <input type="number" id="salary_min" name="salary_min" 
                                           value="<?php echo $job['salary_min']; ?>"
                                           class="<?php echo isset($errors['salary_min']) ? 'error' : ''; ?>"
                                           placeholder="e.g. 80000">
                                    <?php if (isset($errors['salary_min'])): ?>
                                        <span class="error-message"><?php echo $errors['salary_min']; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label for="salary_max">Maximum Salary (KSH)</label>
                                    <input type="number" id="salary_max" name="salary_max" 
                                           value="<?php echo $job['salary_max']; ?>"
                                           class="<?php echo isset($errors['salary_max']) ? 'error' : ''; ?>"
                                           placeholder="e.g. 120000">
                                    <?php if (isset($errors['salary_max'])): ?>
                                        <span class="error-message"><?php echo $errors['salary_max']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="deadline">Application Deadline *</label>
                                    <input type="date" id="deadline" name="deadline" 
                                           value="<?php echo date('Y-m-d', strtotime($job['deadline'])); ?>"
                                           class="<?php echo isset($errors['deadline']) ? 'error' : ''; ?>"
                                           min="<?php echo date('Y-m-d'); ?>">
                                    <?php if (isset($errors['deadline'])): ?>
                                        <span class="error-message"><?php echo $errors['deadline']; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label for="status">Job Status *</label>
                                    <select id="status" name="status" 
                                            class="<?php echo isset($errors['status']) ? 'error' : ''; ?>">
                                        <option value="active" <?php echo $job['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="closed" <?php echo $job['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                        <option value="draft" <?php echo $job['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                    </select>
                                    <?php if (isset($errors['status'])): ?>
                                        <span class="error-message"><?php echo $errors['status']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Job Description -->
                    <div class="card">
                        <div class="card-header">
                            <h2>Job Description</h2>
                            <div class="card-header-actions">
                                <button type="button" class="btn btn-small btn-outline" onclick="previewDescription()">
                                    <i class="fas fa-eye"></i> Preview
                                </button>
                            </div>
                        </div>
                        <div class="card-content">
                            <div class="form-group">
                                <label for="description">Job Description *</label>
                                <textarea id="description" name="description" rows="8" 
                                          class="<?php echo isset($errors['description']) ? 'error' : ''; ?>"
                                          placeholder="Provide a detailed description of the role, what the candidate will be doing, and what makes this opportunity exciting..."><?php echo htmlspecialchars($job['description']); ?></textarea>
                                <?php if (isset($errors['description'])): ?>
                                    <span class="error-message"><?php echo $errors['description']; ?></span>
                                <?php endif; ?>
                                <div class="character-count">
                                    <span id="descriptionCount">0</span> characters
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="requirements">Requirements</label>
                                <textarea id="requirements" name="requirements" rows="6" 
                                          placeholder="List the required qualifications, skills, and experience. Put each requirement on a new line."><?php echo htmlspecialchars($job['requirements']); ?></textarea>
                                <small class="form-help">Enter each requirement on a new line</small>
                            </div>

                            <div class="form-group">
                                <label for="responsibilities">Key Responsibilities</label>
                                <textarea id="responsibilities" name="responsibilities" rows="6" 
                                          placeholder="Describe the main responsibilities and duties for this role. Put each responsibility on a new line."><?php echo htmlspecialchars($job['responsibilities']); ?></textarea>
                                <small class="form-help">Enter each responsibility on a new line</small>
                            </div>

                            <div class="form-group">
                                <label for="benefits">Benefits & Perks</label>
                                <textarea id="benefits" name="benefits" rows="6" 
                                          placeholder="List the benefits, perks, and what makes your company a great place to work. Put each benefit on a new line."><?php echo htmlspecialchars($job['benefits']); ?></textarea>
                                <small class="form-help">Enter each benefit on a new line</small>
                            </div>
                        </div>
                    </div>

                    <!-- Screening Questions -->
                    <div class="card">
                        <div class="card-header">
                            <h2>Screening Questions</h2>
                            <div class="card-header-actions">
                                <button type="button" class="btn btn-small btn-primary" onclick="addScreeningQuestion()">
                                    <i class="fas fa-plus"></i> Add Question
                                </button>
                            </div>
                        </div>
                        <div class="card-content">
                            <div id="screeningQuestions">
                                <?php if (!empty($screeningQuestions)): ?>
                                    <?php foreach ($screeningQuestions as $index => $question): ?>
                                        <div class="screening-question-item" data-index="<?php echo $index; ?>">
                                            <div class="question-header">
                                                <span class="question-number">Question <?php echo $index + 1; ?></span>
                                                <button type="button" class="btn btn-small btn-outline btn-danger" onclick="removeScreeningQuestion(<?php echo $index; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                            <div class="form-group">
                                                <label>Question Text</label>
                                                <input type="text" name="screening_questions[<?php echo $index; ?>][question]" 
                                                       value="<?php echo htmlspecialchars($question['question']); ?>"
                                                       placeholder="Enter your screening question">
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group">
                                                    <label>Question Type</label>
                                                    <select name="screening_questions[<?php echo $index; ?>][type]" onchange="toggleQuestionOptions(<?php echo $index; ?>)">
                                                        <option value="short_answer" <?php echo $question['type'] === 'short_answer' ? 'selected' : ''; ?>>Short Answer</option>
                                                        <option value="long_answer" <?php echo $question['type'] === 'long_answer' ? 'selected' : ''; ?>>Long Answer</option>
                                                        <option value="yes_no" <?php echo $question['type'] === 'yes_no' ? 'selected' : ''; ?>>Yes/No</option>
                                                        <option value="multiple_choice" <?php echo $question['type'] === 'multiple_choice' ? 'selected' : ''; ?>>Multiple Choice</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>Required</label>
                                                    <label class="toggle-switch">
                                                        <input type="checkbox" name="screening_questions[<?php echo $index; ?>][required]" 
                                                               <?php echo $question['required'] ? 'checked' : ''; ?>>
                                                        <span class="toggle-slider"></span>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="question-options" id="questionOptions<?php echo $index; ?>" 
                                                 style="display: <?php echo $question['type'] === 'multiple_choice' ? 'block' : 'none'; ?>">
                                                <label>Answer Options (one per line)</label>
                                                <textarea name="screening_questions[<?php echo $index; ?>][options]" rows="3" 
                                                          placeholder="Option 1&#10;Option 2&#10;Option 3"><?php 
                                                    if ($question['options']) {
                                                        echo implode("\n", json_decode($question['options'], true));
                                                    }
                                                ?></textarea>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="no-questions">
                                        <p>No screening questions added yet. Click "Add Question" to create your first screening question.</p>
                                    </div>
                                <?php endif; ?>
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
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Job
                            </button>
                            <button type="button" onclick="previewJob()" class="btn btn-outline">
                                <i class="fas fa-eye"></i> Preview
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Sidebar -->
            <div class="edit-job-sidebar">
                <!-- Job Statistics -->
                <div class="card">
                    <div class="card-header">
                        <h3>Job Statistics</h3>
                    </div>
                    <div class="card-content">
                        <div class="stat-item">
                            <span class="stat-label">Total Applications</span>
                            <span class="stat-value"><?php echo $job['application_count']; ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Created</span>
                            <span class="stat-value"><?php echo date('M j, Y', strtotime($job['created_at'])); ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Last Updated</span>
                            <span class="stat-value"><?php echo date('M j, Y', strtotime($job['updated_at'] ?? $job['created_at'])); ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Days Until Deadline</span>
                            <span class="stat-value">
                                <?php 
                                $daysLeft = ceil((strtotime($job['deadline']) - time()) / (60 * 60 * 24));
                                echo $daysLeft > 0 ? $daysLeft : 'Expired';
                                ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h3>Quick Actions</h3>
                    </div>
                    <div class="card-content">
                        <div class="quick-actions-list">
                            <a href="view-job.php?id=<?php echo $job['id']; ?>" class="btn btn-outline btn-large">
                                <i class="fas fa-eye"></i> View Job Page
                            </a>
                            <a href="../job-details.php?id=<?php echo $job['id']; ?>" target="_blank" class="btn btn-outline btn-large">
                                <i class="fas fa-external-link-alt"></i> View Public Page
                            </a>
                            <button onclick="duplicateJob()" class="btn btn-outline btn-large">
                                <i class="fas fa-copy"></i> Duplicate Job
                            </button>
                            <button onclick="extendDeadline()" class="btn btn-outline btn-large">
                                <i class="fas fa-calendar-plus"></i> Extend Deadline
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Recent Applications -->
                <div class="card">
                    <div class="card-header">
                        <h3>Recent Applications</h3>
                    </div>
                    <div class="card-content">
                        <?php 
                        $recentApplications = $jobModel->getRecentApplications($jobId, 5);
                        if (!empty($recentApplications)): 
                        ?>
                            <div class="recent-applications-list">
                                <?php foreach ($recentApplications as $app): ?>
                                    <div class="recent-application-item">
                                        <div class="applicant-info">
                                            <div class="applicant-name"><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></div>
                                            <div class="application-time"><?php echo timeAgo($app['applied_at']); ?></div>
                                        </div>
                                        <a href="view-application.php?id=<?php echo $app['id']; ?>" class="btn btn-small btn-outline">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <a href="dashboard.php?tab=applications&job_id=<?php echo $job['id']; ?>" class="btn btn-outline btn-small">
                                View All Applications
                            </a>
                        <?php else: ?>
                            <div class="no-applications">
                                <i class="fas fa-inbox"></i>
                                <p>No applications yet</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Modal -->
    <div id="previewModal" class="modal" style="display: none;">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h3>Job Preview</h3>
                <button class="modal-close" onclick="closePreviewModal()">&times;</button>
            </div>
            <div class="modal-body" id="previewContent">
                <!-- Preview content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closePreviewModal()">Close</button>
                <button class="btn btn-primary" onclick="openPublicPreview()">View Public Page</button>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/job-edit.js"></script>
</body>
</html>
