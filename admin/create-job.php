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
        
        if ($jobModel->createJob($job_data)) {
            $success = true;
            // Clear form data
            $_POST = [];
        } else {
            $errors['general'] = 'Failed to create job. Please try again.';
        }
    }
}
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
            <form method="POST" class="job-form">
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
                                    <option value="Engineering" <?php echo (isset($_POST['department']) && $_POST['department'] === 'Engineering') ? 'selected' : ''; ?>>Engineering</option>
                                    <option value="Product" <?php echo (isset($_POST['department']) && $_POST['department'] === 'Product') ? 'selected' : ''; ?>>Product</option>
                                    <option value="Design" <?php echo (isset($_POST['department']) && $_POST['department'] === 'Design') ? 'selected' : ''; ?>>Design</option>
                                    <option value="Marketing" <?php echo (isset($_POST['department']) && $_POST['department'] === 'Marketing') ? 'selected' : ''; ?>>Marketing</option>
                                    <option value="Sales" <?php echo (isset($_POST['department']) && $_POST['department'] === 'Sales') ? 'selected' : ''; ?>>Sales</option>
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
                                <label for="salary_min">Minimum Salary ($)</label>
                                <input type="number" id="salary_min" name="salary_min" 
                                       value="<?php echo isset($_POST['salary_min']) ? htmlspecialchars($_POST['salary_min']) : ''; ?>"
                                       class="<?php echo isset($errors['salary_min']) ? 'error' : ''; ?>"
                                       placeholder="e.g. 80000">
                                <?php if (isset($errors['salary_min'])): ?>
                                    <span class="error-message"><?php echo $errors['salary_min']; ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label for="salary_max">Maximum Salary ($)</label>
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

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="button" onclick="window.history.back()" class="btn btn-outline">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Create Job
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        // Auto-save form data
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.job-form');
            const inputs = form.querySelectorAll('input, select, textarea');
            
            // Load saved data
            inputs.forEach(input => {
                const savedValue = localStorage.getItem(`create_job_${input.name}`);
                if (savedValue && !input.value) {
                    input.value = savedValue;
                }
            });
            
            // Save data on change
            inputs.forEach(input => {
                input.addEventListener('change', () => {
                    localStorage.setItem(`create_job_${input.name}`, input.value);
                });
            });
            
            // Clear saved data on successful submission
            <?php if ($success): ?>
                inputs.forEach(input => {
                    localStorage.removeItem(`create_job_${input.name}`);
                });
            <?php endif; ?>
        });
        
        // Form validation
        document.querySelector('.job-form').addEventListener('submit', function(e) {
            const requiredFields = ['title', 'department', 'location', 'type', 'description', 'deadline'];
            let hasErrors = false;
            
            requiredFields.forEach(fieldName => {
                const field = document.querySelector(`[name="${fieldName}"]`);
                const errorElement = field.parentNode.querySelector('.error-message');
                
                if (!field.value.trim()) {
                    field.classList.add('error');
                    if (!errorElement) {
                        const error = document.createElement('span');
                        error.className = 'error-message';
                        error.textContent = 'This field is required';
                        field.parentNode.appendChild(error);
                    }
                    hasErrors = true;
                } else {
                    field.classList.remove('error');
                    if (errorElement) {
                        errorElement.remove();
                    }
                }
            });
            
            if (hasErrors) {
                e.preventDefault();
                document.querySelector('.error').scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    </script>
</body>
</html>
