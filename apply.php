<?php
require_once 'config/config.php';
require_once 'models/Job.php';
require_once 'models/Application.php';

if (!isset($_GET['id'])) {
    redirect('index.php');
}

$jobModel = new Job();
$job = $jobModel->getJobById($_GET['id']);

if (!$job) {
    redirect('index.php');
}

// Check if job is still open
$daysLeft = ceil((strtotime($job['deadline']) - time()) / (60 * 60 * 24));
$isExpired = $daysLeft <= 0;

if ($isExpired) {
    redirect('job-details.php?id=' . $job['id']);
}

$screeningQuestions = $jobModel->getScreeningQuestions($job['id']);
$applicationModel = new Application();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Validate required fields
    $required_fields = ['first_name', 'last_name', 'email', 'phone'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
        }
    }
    
    // Validate email
    if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    }
    
    // Handle file upload
    $resume_filename = '';
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['resume'];
        
        // Validate file type
        if ($file['type'] !== 'application/pdf') {
            $errors['resume'] = 'Only PDF files are allowed';
        }
        
        // Validate file size
        if ($file['size'] > MAX_FILE_SIZE) {
            $errors['resume'] = 'File size must be less than 10MB';
        }
        
        if (empty($errors['resume'])) {
            $resume_filename = time() . '_' . $file['name'];
            $upload_path = UPLOAD_DIR . $resume_filename;
            
            if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
                $errors['resume'] = 'Failed to upload file';
            }
        }
    } else {
        $errors['resume'] = 'Resume is required';
    }
    
    // Validate screening questions
    foreach ($screeningQuestions as $question) {
        if ($question['required'] && empty($_POST['screening_' . $question['id']])) {
            $errors['screening_' . $question['id']] = 'This field is required';
        }
    }
    
    if (empty($errors)) {
        // Create application
        $application_data = [
            'job_id' => $job['id'],
            'first_name' => sanitizeInput($_POST['first_name']),
            'last_name' => sanitizeInput($_POST['last_name']),
            'email' => sanitizeInput($_POST['email']),
            'phone' => sanitizeInput($_POST['phone']),
            'address' => sanitizeInput($_POST['address']),
            'city' => sanitizeInput($_POST['city']),
            'state' => sanitizeInput($_POST['state']),
            'zip_code' => sanitizeInput($_POST['zip_code']),
            'resume_filename' => $resume_filename,
            'cover_letter' => sanitizeInput($_POST['cover_letter']),
            'portfolio_url' => sanitizeInput($_POST['portfolio_url']),
            'linkedin_url' => sanitizeInput($_POST['linkedin_url']),
            'referral_source' => sanitizeInput($_POST['referral_source']),
            'additional_info' => sanitizeInput($_POST['additional_info'])
        ];
        
        $application_id = $applicationModel->createApplication($application_data);
        
        if ($application_id) {
            // Save screening answers
            $screening_answers = [];
            foreach ($screeningQuestions as $question) {
                if (!empty($_POST['screening_' . $question['id']])) {
                    $screening_answers[$question['id']] = sanitizeInput($_POST['screening_' . $question['id']]);
                }
            }
            
            if (!empty($screening_answers)) {
                $applicationModel->saveScreeningAnswers($application_id, $screening_answers);
            }
            
            redirect('application-success.php?id=' . $application_id);
        } else {
            $errors['general'] = 'Failed to submit application. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for <?php echo htmlspecialchars($job['title']); ?> - TechCorp Careers</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <h1>Apply for <?php echo htmlspecialchars($job['title']); ?></h1>
                </div>
                <nav class="nav">
                    <a href="job-details.php?id=<?php echo $job['id']; ?>" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> Back to Job
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <div class="container application-container">
        <div class="application-form-wrapper">
            <!-- Progress Bar -->
            <div class="progress-section">
                <div class="progress-info">
                    <span>Step <span id="currentStep">1</span> of 4</span>
                    <span><span id="progressPercent">25</span>% Complete</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
            </div>

            <?php if (isset($errors['general'])): ?>
                <div class="alert alert-error">
                    <?php echo $errors['general']; ?>
                </div>
            <?php endif; ?>

            <form id="applicationForm" method="POST" enctype="multipart/form-data">
                <!-- Step 1: Personal Information -->
                <div class="form-step active" id="step1">
                    <div class="card">
                        <div class="card-header">
                            <h2>Personal Information</h2>
                        </div>
                        <div class="card-content">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="first_name">First Name *</label>
                                    <input type="text" id="first_name" name="first_name" 
                                           value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>"
                                           class="<?php echo isset($errors['first_name']) ? 'error' : ''; ?>">
                                    <?php if (isset($errors['first_name'])): ?>
                                        <span class="error-message"><?php echo $errors['first_name']; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label for="last_name">Last Name *</label>
                                    <input type="text" id="last_name" name="last_name" 
                                           value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>"
                                           class="<?php echo isset($errors['last_name']) ? 'error' : ''; ?>">
                                    <?php if (isset($errors['last_name'])): ?>
                                        <span class="error-message"><?php echo $errors['last_name']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email" 
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                       class="<?php echo isset($errors['email']) ? 'error' : ''; ?>">
                                <?php if (isset($errors['email'])): ?>
                                    <span class="error-message"><?php echo $errors['email']; ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="phone">Phone Number *</label>
                                <input type="tel" id="phone" name="phone" 
                                       value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                                       class="<?php echo isset($errors['phone']) ? 'error' : ''; ?>">
                                <?php if (isset($errors['phone'])): ?>
                                    <span class="error-message"><?php echo $errors['phone']; ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="address">Address</label>
                                <input type="text" id="address" name="address" 
                                       value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>">
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="city">City</label>
                                    <input type="text" id="city" name="city" 
                                           value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : ''; ?>">
                                </div>
                                <div class="form-group">
                                    <label for="state">State</label>
                                    <input type="text" id="state" name="state" 
                                           value="<?php echo isset($_POST['state']) ? htmlspecialchars($_POST['state']) : ''; ?>">
                                </div>
                                <div class="form-group">
                                    <label for="zip_code">Zip Code</label>
                                    <input type="text" id="zip_code" name="zip_code" 
                                           value="<?php echo isset($_POST['zip_code']) ? htmlspecialchars($_POST['zip_code']) : ''; ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Documents -->
                <div class="form-step" id="step2">
                    <div class="card">
                        <div class="card-header">
                            <h2>Documents & Portfolio</h2>
                        </div>
                        <div class="card-content">
                            <div class="form-group">
                                <label for="resume">Resume/CV * (PDF only, max 10MB)</label>
                                <div class="file-upload-area" id="fileUploadArea">
                                    <div class="file-upload-content">
                                        <i class="fas fa-upload"></i>
                                        <p><strong>Click to upload</strong> or drag and drop</p>
                                        <p class="file-info">PDF files only (MAX. 10MB)</p>
                                    </div>
                                    <input type="file" id="resume" name="resume" accept=".pdf" 
                                           class="<?php echo isset($errors['resume']) ? 'error' : ''; ?>">
                                </div>
                                <?php if (isset($errors['resume'])): ?>
                                    <span class="error-message"><?php echo $errors['resume']; ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="cover_letter">Cover Letter (Optional)</label>
                                <textarea id="cover_letter" name="cover_letter" rows="6" 
                                          placeholder="Tell us why you're interested in this position and what makes you a great fit..."><?php echo isset($_POST['cover_letter']) ? htmlspecialchars($_POST['cover_letter']) : ''; ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="portfolio_url">Portfolio URL (Optional)</label>
                                <input type="url" id="portfolio_url" name="portfolio_url" 
                                       placeholder="https://your-portfolio.com"
                                       value="<?php echo isset($_POST['portfolio_url']) ? htmlspecialchars($_POST['portfolio_url']) : ''; ?>">
                            </div>

                            <div class="form-group">
                                <label for="linkedin_url">LinkedIn Profile (Optional)</label>
                                <input type="url" id="linkedin_url" name="linkedin_url" 
                                       placeholder="https://linkedin.com/in/yourprofile"
                                       value="<?php echo isset($_POST['linkedin_url']) ? htmlspecialchars($_POST['linkedin_url']) : ''; ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Screening Questions -->
                <div class="form-step" id="step3">
                    <div class="card">
                        <div class="card-header">
                            <h2>Screening Questions</h2>
                        </div>
                        <div class="card-content">
                            <?php foreach ($screeningQuestions as $question): ?>
                                <div class="form-group">
                                    <label><?php echo htmlspecialchars($question['question']); ?> <?php echo $question['required'] ? '*' : ''; ?></label>
                                    
                                    <?php if ($question['type'] === 'short_answer'): ?>
                                        <input type="text" name="screening_<?php echo $question['id']; ?>" 
                                               value="<?php echo isset($_POST['screening_' . $question['id']]) ? htmlspecialchars($_POST['screening_' . $question['id']]) : ''; ?>"
                                               class="<?php echo isset($errors['screening_' . $question['id']]) ? 'error' : ''; ?>">
                                    
                                    <?php elseif ($question['type'] === 'yes_no'): ?>
                                        <div class="radio-group">
                                            <label class="radio-label">
                                                <input type="radio" name="screening_<?php echo $question['id']; ?>" value="yes" 
                                                       <?php echo (isset($_POST['screening_' . $question['id']]) && $_POST['screening_' . $question['id']] === 'yes') ? 'checked' : ''; ?>>
                                                <span>Yes</span>
                                            </label>
                                            <label class="radio-label">
                                                <input type="radio" name="screening_<?php echo $question['id']; ?>" value="no"
                                                       <?php echo (isset($_POST['screening_' . $question['id']]) && $_POST['screening_' . $question['id']] === 'no') ? 'checked' : ''; ?>>
                                                <span>No</span>
                                            </label>
                                        </div>
                                    
                                    <?php elseif ($question['type'] === 'multiple_choice'): ?>
                                        <select name="screening_<?php echo $question['id']; ?>" 
                                                class="<?php echo isset($errors['screening_' . $question['id']]) ? 'error' : ''; ?>">
                                            <option value="">Select your preference</option>
                                            <?php 
                                            $options = json_decode($question['options'], true);
                                            if ($options):
                                                foreach ($options as $option): 
                                            ?>
                                                <option value="<?php echo htmlspecialchars($option); ?>"
                                                        <?php echo (isset($_POST['screening_' . $question['id']]) && $_POST['screening_' . $question['id']] === $option) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($option); ?>
                                                </option>
                                            <?php 
                                                endforeach;
                                            endif; 
                                            ?>
                                        </select>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($errors['screening_' . $question['id']])): ?>
                                        <span class="error-message"><?php echo $errors['screening_' . $question['id']]; ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Additional Information -->
                <div class="form-step" id="step4">
                    <div class="card">
                        <div class="card-header">
                            <h2>Additional Information</h2>
                        </div>
                        <div class="card-content">
                            <div class="form-group">
                                <label for="referral_source">How did you hear about this position?</label>
                                <select id="referral_source" name="referral_source">
                                    <option value="">Select an option</option>
                                    <option value="company-website" <?php echo (isset($_POST['referral_source']) && $_POST['referral_source'] === 'company-website') ? 'selected' : ''; ?>>Company Website</option>
                                    <option value="linkedin" <?php echo (isset($_POST['referral_source']) && $_POST['referral_source'] === 'linkedin') ? 'selected' : ''; ?>>LinkedIn</option>
                                    <option value="job-board" <?php echo (isset($_POST['referral_source']) && $_POST['referral_source'] === 'job-board') ? 'selected' : ''; ?>>Job Board</option>
                                    <option value="referral" <?php echo (isset($_POST['referral_source']) && $_POST['referral_source'] === 'referral') ? 'selected' : ''; ?>>Employee Referral</option>
                                    <option value="recruiter" <?php echo (isset($_POST['referral_source']) && $_POST['referral_source'] === 'recruiter') ? 'selected' : ''; ?>>Recruiter</option>
                                    <option value="other" <?php echo (isset($_POST['referral_source']) && $_POST['referral_source'] === 'other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="additional_info">Additional Information (Optional)</label>
                                <textarea id="additional_info" name="additional_info" rows="4" 
                                          placeholder="Is there anything else you'd like us to know about your application?"><?php echo isset($_POST['additional_info']) ? htmlspecialchars($_POST['additional_info']) : ''; ?></textarea>
                            </div>

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                By submitting this application, you agree to our privacy policy and terms of service. We will only use your information for recruitment purposes.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation -->
                <div class="form-navigation">
                    <button type="button" id="prevBtn" class="btn btn-outline" onclick="changeStep(-1)" style="display: none;">
                        Previous
                    </button>
                    <button type="button" id="nextBtn" class="btn btn-primary" onclick="changeStep(1)">
                        Next
                    </button>
                    <button type="submit" id="submitBtn" class="btn btn-primary" style="display: none;">
                        Submit Application
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script src="assets/js/application.js"></script>
</body>
</html>
