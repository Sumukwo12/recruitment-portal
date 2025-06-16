<?php
require_once 'config/config.php';

$database = new Database();
$db = $database->getConnection();

// Get job ID
$job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;

if (!$job_id) {
    redirectTo('index.php');
}

// Get job details
$query = "SELECT * FROM jobs WHERE id = :job_id AND status = 'Open' AND deadline >= CURDATE()";
$stmt = $db->prepare($query);
$stmt->bindParam(':job_id', $job_id);
$stmt->execute();

if ($stmt->rowCount() === 0) {
    redirectTo('index.php');
}

$job = $stmt->fetch(PDO::FETCH_ASSOC);

// Get screening questions
$query = "SELECT * FROM screening_questions WHERE job_id = :job_id ORDER BY id";
$stmt = $db->prepare($query);
$stmt->bindParam(':job_id', $job_id);
$stmt->execute();
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Validate required fields
    $required_fields = ['first_name', 'last_name', 'email', 'phone'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
        }
    }
    
    // Validate email
    if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    // Handle file upload
    $resume_path = null;
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['resume'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, ALLOWED_FILE_TYPES)) {
            $errors[] = 'Only PDF files are allowed for resume';
        }
        
        if ($file['size'] > MAX_FILE_SIZE) {
            $errors[] = 'Resume file size must be less than 10MB';
        }
        
        if (empty($errors)) {
            $filename = uniqid() . '_' . sanitizeInput($file['name']);
            $resume_path = UPLOAD_DIR . $filename;
            
            if (!move_uploaded_file($file['tmp_name'], $resume_path)) {
                $errors[] = 'Failed to upload resume';
            }
        }
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // Insert application
            $query = "INSERT INTO applications (job_id, first_name, last_name, email, phone, address, resume_path, cover_letter, portfolio_url, linkedin_url) 
                     VALUES (:job_id, :first_name, :last_name, :email, :phone, :address, :resume_path, :cover_letter, :portfolio_url, :linkedin_url)";
            
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':job_id' => $job_id,
                ':first_name' => sanitizeInput($_POST['first_name']),
                ':last_name' => sanitizeInput($_POST['last_name']),
                ':email' => sanitizeInput($_POST['email']),
                ':phone' => sanitizeInput($_POST['phone']),
                ':address' => sanitizeInput($_POST['address']),
                ':resume_path' => $resume_path,
                ':cover_letter' => sanitizeInput($_POST['cover_letter']),
                ':portfolio_url' => sanitizeInput($_POST['portfolio_url']),
                ':linkedin_url' => sanitizeInput($_POST['linkedin_url'])
            ]);
            
            $application_id = $db->lastInsertId();
            
            // Insert screening question answers
            foreach ($questions as $question) {
                $answer_key = 'question_' . $question['id'];
                if (isset($_POST[$answer_key])) {
                    $answer = sanitizeInput($_POST[$answer_key]);
                    
                    $query = "INSERT INTO application_answers (application_id, question_id, answer) VALUES (:application_id, :question_id, :answer)";
                    $stmt = $db->prepare($query);
                    $stmt->execute([
                        ':application_id' => $application_id,
                        ':question_id' => $question['id'],
                        ':answer' => $answer
                    ]);
                }
            }
            
            $db->commit();
            $success = "Application submitted successfully!";
            
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = "Failed to submit application. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for <?php echo htmlspecialchars($job['title']); ?> - Recruit Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <h1 class="logo">Recruit Portal</h1>
                <nav class="nav">
                    <a href="index.php" class="nav-link">← Back to Jobs</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="main">
        <div class="container">
            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <h3>Application Submitted!</h3>
                    <p><?php echo $success; ?></p>
                    <a href="index.php" class="btn btn-primary">View More Jobs</a>
                </div>
            <?php else: ?>
                <div class="application-form">
                    <div class="job-info">
                        <h2><?php echo htmlspecialchars($job['title']); ?></h2>
                        <p class="company"><?php echo htmlspecialchars($job['company']); ?></p>
                        <p class="location"><?php echo htmlspecialchars($job['location']); ?></p>
                        <p class="deadline">Application Deadline: <?php echo date('M j, Y', strtotime($job['deadline'])); ?></p>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-error">
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" id="applicationForm">
                        <div class="form-section">
                            <h3>Personal Information</h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="first_name">First Name *</label>
                                    <input type="text" id="first_name" name="first_name" required 
                                           value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="last_name">Last Name *</label>
                                    <input type="text" id="last_name" name="last_name" required
                                           value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="email">Email *</label>
                                    <input type="email" id="email" name="email" required
                                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="phone">Phone *</label>
                                    <input type="tel" id="phone" name="phone" required
                                           value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="address">Address</label>
                                <textarea id="address" name="address" rows="3"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3>Documents & Links</h3>
                            
                            <div class="form-group">
                                <label for="resume">Resume (PDF, max 10MB) *</label>
                                <input type="file" id="resume" name="resume" accept=".pdf" required>
                                <div class="file-info">Only PDF files are allowed. Maximum size: 10MB</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="cover_letter">Cover Letter</label>
                                <textarea id="cover_letter" name="cover_letter" rows="6" 
                                          placeholder="Tell us why you're interested in this position..."><?php echo isset($_POST['cover_letter']) ? htmlspecialchars($_POST['cover_letter']) : ''; ?></textarea>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="portfolio_url">Portfolio URL</label>
                                    <input type="url" id="portfolio_url" name="portfolio_url" 
                                           placeholder="https://yourportfolio.com"
                                           value="<?php echo isset($_POST['portfolio_url']) ? htmlspecialchars($_POST['portfolio_url']) : ''; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="linkedin_url">LinkedIn Profile</label>
                                    <input type="url" id="linkedin_url" name="linkedin_url" 
                                           placeholder="https://linkedin.com/in/yourprofile"
                                           value="<?php echo isset($_POST['linkedin_url']) ? htmlspecialchars($_POST['linkedin_url']) : ''; ?>">
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($questions)): ?>
                            <div class="form-section">
                                <h3>Screening Questions</h3>
                                
                                <?php foreach ($questions as $question): ?>
                                    <div class="form-group">
                                        <label for="question_<?php echo $question['id']; ?>">
                                            <?php echo htmlspecialchars($question['question']); ?>
                                            <?php if ($question['required']): ?> *<?php endif; ?>
                                        </label>
                                        
                                        <?php if ($question['question_type'] === 'short_answer'): ?>
                                            <textarea id="question_<?php echo $question['id']; ?>" 
                                                    name="question_<?php echo $question['id']; ?>" 
                                                    rows="3" 
                                                    <?php echo $question['required'] ? 'required' : ''; ?>><?php echo isset($_POST['question_' . $question['id']]) ? htmlspecialchars($_POST['question_' . $question['id']]) : ''; ?></textarea>
                                        
                                        <?php elseif ($question['question_type'] === 'yes_no'): ?>
                                            <div class="radio-group">
                                                <label class="radio-label">
                                                    <input type="radio" name="question_<?php echo $question['id']; ?>" value="Yes" 
                                                           <?php echo (isset($_POST['question_' . $question['id']]) && $_POST['question_' . $question['id']] === 'Yes') ? 'checked' : ''; ?>
                                                           <?php echo $question['required'] ? 'required' : ''; ?>>
                                                    Yes
                                                </label>
                                                <label class="radio-label">
                                                    <input type="radio" name="question_<?php echo $question['id']; ?>" value="No"
                                                           <?php echo (isset($_POST['question_' . $question['id']]) && $_POST['question_' . $question['id']] === 'No') ? 'checked' : ''; ?>
                                                           <?php echo $question['required'] ? 'required' : ''; ?>>
                                                    No
                                                </label>
                                            </div>
                                        
                                        <?php elseif ($question['question_type'] === 'multiple_choice'): ?>
                                            <?php $options = json_decode($question['options'], true); ?>
                                            <?php if ($options): ?>
                                                <div class="radio-group">
                                                    <?php foreach ($options as $option): ?>
                                                        <label class="radio-label">
                                                            <input type="radio" name="question_<?php echo $question['id']; ?>" 
                                                                   value="<?php echo htmlspecialchars($option); ?>"
                                                                   <?php echo (isset($_POST['question_' . $question['id']]) && $_POST['question_' . $question['id']] === $option) ? 'checked' : ''; ?>
                                                                   <?php echo $question['required'] ? 'required' : ''; ?>>
                                                            <?php echo htmlspecialchars($option); ?>
                                                        </label>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary" id="submitBtn">Submit Application</button>
                            <button type="button" class="btn btn-secondary" id="saveBtn">Save Draft</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="assets/js/application.js"></script>
</body>
</html>
