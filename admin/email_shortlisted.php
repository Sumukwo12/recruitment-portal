<?php
require_once '../config/config.php';
require '../vendor/autoload.php'; // Include PHPMailer

if (!isLoggedIn() || !isAdmin()) {
    redirectTo('../auth/login.php');
}

$database = new Database();
$db = $database->getConnection();

$job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;

if (!$job_id) {
    redirectTo('applications.php');
}

// Get job details
$query = "SELECT * FROM jobs WHERE id = :job_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':job_id', $job_id);
$stmt->execute();

if ($stmt->rowCount() === 0) {
    redirectTo('applications.php');
}

$job = $stmt->fetch(PDO::FETCH_ASSOC);

// Get shortlisted candidates
$query = "SELECT * FROM applications WHERE job_id = :job_id AND status = 'Shortlisted' ORDER BY applied_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':job_id', $job_id);
$stmt->execute();
$shortlisted = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle email sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_email'])) {
    $subject = sanitizeInput($_POST['subject']);
    $message = sanitizeInput($_POST['message']);
    $selected_candidates = isset($_POST['candidates']) ? $_POST['candidates'] : [];
    
    $errors = [];
    $success_count = 0;
    $failed_emails = [];
    
    if (empty($subject)) {
        $errors[] = "Subject is required";
    }
    
    if (empty($message)) {
        $errors[] = "Message is required";
    }
    
    if (empty($selected_candidates)) {
        $errors[] = "Please select at least one candidate";
    }
    
    if (empty($errors)) {
        // Create PHPMailer instance
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST; // Define in config.php
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME; // Define in config.php
            $mail->Password   = SMTP_PASSWORD; // Define in config.php
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT; // Define in config.php
            
            // Sender info
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addReplyTo(SMTP_REPLY_TO, SMTP_FROM_NAME);
            
        // Replace the email sending loop in your code with this improved version
foreach ($selected_candidates as $candidate_id) {
    // Get candidate details
    $query = "SELECT * FROM applications WHERE id = :id AND status = 'Shortlisted'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $candidate_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $candidate = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Prepare personalized email
        $personalized_message = str_replace(
            ['{first_name}', '{last_name}', '{job_title}', '{company}'],
            [$candidate['first_name'], $candidate['last_name'], $job['title'], $job['company']],
            $message
        );
        
        try {
            // Clear all recipients
            $mail->clearAddresses();
            $mail->clearCCs();
            $mail->clearBCCs();
            
            // Add recipient
            $mail->addAddress($candidate['email'], $candidate['first_name'] . ' ' . $candidate['last_name']);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = "
                <html>
                <body>
                    <h2>$subject</h2>
                    <p>Dear {$candidate['first_name']} {$candidate['last_name']},</p>
                    <div>" . nl2br($personalized_message) . "</div>
                    <br>
                    <p>Best regards,<br>
                    Recruitment Team<br>
                    {$job['company']}</p>
                </body>
                </html>
            ";
            
            $mail->AltBody = strip_tags($personalized_message);
            
            // Send the email
            $mail->send();
            $success_count++;
            
            // Try to log email sent, but don't fail if table doesn't exist
            try {
                $query = "INSERT INTO email_logs (application_id, subject, message, sent_by, sent_at) 
                         VALUES (:application_id, :subject, :message, :sent_by, NOW())";
                $stmt = $db->prepare($query);
                $stmt->execute([
                    ':application_id' => $candidate_id,
                    ':subject' => $subject,
                    ':message' => $personalized_message,
                    ':sent_by' => $_SESSION['user_id']
                ]);
            } catch (Exception $logError) {
                // Just log this error but don't mark the email as failed
                error_log("Failed to log email: " . $logError->getMessage());
            }
        } catch (Exception $e) {
            $failed_emails[] = $candidate['email'] . ' (' . $e->getMessage() . ')';
        }
    }
}

            
            if ($success_count > 0) {
                $success = "Successfully sent emails to $success_count candidate(s)";
            }
            
            if (!empty($failed_emails)) {
                $errors[] = "Failed to send emails to: " . implode(', ', $failed_emails);
            }
        } catch (Exception $e) {
            $errors[] = "Mailer Error: " . $e->getMessage();
        }
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Shortlisted Candidates - Recruit Portal</title>
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
                <a href="jobs.php" class="nav-item">Manage Jobs</a>
                <a href="applications.php" class="nav-item active">Applications</a>
                <a href="../auth/logout.php" class="nav-item">Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="content-header">
                <h1>Email Shortlisted Candidates</h1>
                <a href="applications.php?job_id=<?php echo $job_id; ?>" class="btn btn-secondary">← Back to Applications</a>
            </header>

            <div class="dashboard-content">
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Job Information -->
                <div class="section">
                    <div class="section-header">
                        <h2>Job: <?php echo htmlspecialchars($job['title']); ?></h2>
                    </div>
                    <div class="job-info-grid">
                        <div class="info-item">
                            <label>Company:</label>
                            <span><?php echo htmlspecialchars($job['company']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Location:</label>
                            <span><?php echo htmlspecialchars($job['location']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Shortlisted Candidates:</label>
                            <span><?php echo count($shortlisted); ?></span>
                        </div>
                    </div>
                </div>

                <?php if (empty($shortlisted)): ?>
                    <div class="section">
                        <div class="no-data">
                            <h3>No Shortlisted Candidates</h3>
                            <p>There are no shortlisted candidates for this job position yet.</p>
                            <a href="applications.php?job_id=<?php echo $job_id; ?>" class="btn btn-primary">View All Applications</a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Email Composition Form -->
                    <div class="section">
                        <div class="section-header">
                            <h2>Compose Email</h2>
                        </div>
                        
                        <form method="POST" id="emailForm">
                            <div class="email-form-container">
                                <div class="form-group">
                                    <label for="subject">Subject *</label>
                                    <input type="text" id="subject" name="subject" required 
                                           value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : 'Congratulations! You have been shortlisted for ' . htmlspecialchars($job['title']); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="message">Message *</label>
                                    <textarea id="message" name="message" rows="10" required placeholder="Write your message here..."><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : 'Dear {first_name} {last_name},

We are pleased to inform you that you have been shortlisted for the position of {job_title} at {company}.

Your application has impressed our recruitment team, and we would like to proceed to the next stage of our selection process.

We will be in touch with you shortly regarding the next steps.

Thank you for your interest in joining our team.'; ?></textarea>
                                    <div class="message-help">
                                        <strong>Available placeholders:</strong>
                                        <span class="placeholder">{first_name}</span>
                                        <span class="placeholder">{last_name}</span>
                                        <span class="placeholder">{job_title}</span>
                                        <span class="placeholder">{company}</span>
                                    </div>
                                </div>
                            </div>
                        
                            <!-- Candidate Selection -->
                            <div class="section">
                                <div class="section-header">
                                    <h3>Select Recipients</h3>
                                    <div class="selection-controls">
                                        <button type="button" id="selectAll" class="btn btn-sm btn-secondary">Select All</button>
                                        <button type="button" id="deselectAll" class="btn btn-sm btn-secondary">Deselect All</button>
                                    </div>
                                </div>
                                
                                <div class="candidates-list">
                                    <?php foreach ($shortlisted as $candidate): ?>
                                        <div class="candidate-item">
                                            <label class="candidate-checkbox">
                                                <input type="checkbox" name="candidates[]" value="<?php echo $candidate['id']; ?>" 
                                                       <?php echo (isset($_POST['candidates']) && in_array($candidate['id'], $_POST['candidates'])) ? 'checked' : 'checked'; ?>>
                                                <div class="candidate-info">
                                                    <div class="candidate-name">
                                                        <?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?>
                                                    </div>
                                                    <div class="candidate-email">
                                                        <?php echo htmlspecialchars($candidate['email']); ?>
                                                    </div>
                                                    <div class="candidate-applied">
                                                        Applied: <?php echo date('M j, Y', strtotime($candidate['applied_at'])); ?>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" name="send_email" class="btn btn-primary" id="sendEmailBtn">
                                    Send Email to Selected Candidates
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="previewEmail()">
                                    Preview Email
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Email Preview Modal -->
    <div id="previewModal" class="modal" style="display: none;">
        <div class="modal-content large">
            <div class="modal-header">
                <h3>Email Preview</h3>
                <button type="button" class="close-btn" onclick="closePreviewModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="preview-content">
                    <div class="email-preview">
                        <div class="email-header">
                            <strong>Subject:</strong> <span id="previewSubject"></span>
                        </div>
                        <div class="email-body" id="previewBody"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closePreviewModal()">Close</button>
            </div>
        </div>
    </div>

    <style>
        .job-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            padding: 1.5rem;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .info-item label {
            font-weight: 600;
            color: #374151;
        }
        
        .email-form-container {
            padding: 1.5rem;
        }
        
        .message-help {
            margin-top: 0.5rem;
            font-size: 0.875rem;
            color: #64748b;
        }
        
        .placeholder {
            background: #e2e8f0;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-family: monospace;
            margin: 0 0.25rem;
        }
        
        .selection-controls {
            display: flex;
            gap: 0.5rem;
        }
        
        .candidates-list {
            padding: 1.5rem;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .candidate-item {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 1rem;
            transition: all 0.3s;
        }
        
        .candidate-item:hover {
            border-color: #3b82f6;
            background: #f8fafc;
        }
        
        .candidate-checkbox {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            cursor: pointer;
            width: 100%;
        }
        
        .candidate-checkbox input[type="checkbox"] {
            width: 18px;
            height: 18px;
        }
        
        .candidate-info {
            flex: 1;
        }
        
        .candidate-name {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.25rem;
        }
        
        .candidate-email {
            color: #3b82f6;
            margin-bottom: 0.25rem;
        }
        
        .candidate-applied {
            font-size: 0.875rem;
            color: #64748b;
        }
        
        .modal.large .modal-content {
            max-width: 800px;
            width: 90%;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 1rem;
        }
        
        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #64748b;
        }
        
        .email-preview {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1.5rem;
            background: #fff;
        }
        
        .email-header {
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .email-body {
            line-height: 1.6;
            white-space: pre-wrap;
        }
        
        .modal-footer {
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
            margin-top: 1rem;
            text-align: right;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectAllBtn = document.getElementById('selectAll');
            const deselectAllBtn = document.getElementById('deselectAll');
            const checkboxes = document.querySelectorAll('input[name="candidates[]"]');
            const sendBtn = document.getElementById('sendEmailBtn');
            
            // Select all functionality
            selectAllBtn.addEventListener('click', function() {
                checkboxes.forEach(checkbox => checkbox.checked = true);
                updateSendButton();
            });
            
            // Deselect all functionality
            deselectAllBtn.addEventListener('click', function() {
                checkboxes.forEach(checkbox => checkbox.checked = false);
                updateSendButton();
            });
            
            // Update send button based on selection
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateSendButton);
            });
            
            function updateSendButton() {
                const selectedCount = document.querySelectorAll('input[name="candidates[]"]:checked').length;
                sendBtn.textContent = selectedCount > 0 
                    ? `Send Email to ${selectedCount} Selected Candidate${selectedCount > 1 ? 's' : ''}`
                    : 'Send Email to Selected Candidates';
                sendBtn.disabled = selectedCount === 0;
            }
            
            // Initial button update
            updateSendButton();
            
            // Form validation
            document.getElementById('emailForm').addEventListener('submit', function(e) {
                const selectedCount = document.querySelectorAll('input[name="candidates[]"]:checked').length;
                if (selectedCount === 0) {
                    e.preventDefault();
                    alert('Please select at least one candidate to send the email to.');
                    return false;
                }
                
                if (!confirm(`Are you sure you want to send this email to ${selectedCount} candidate${selectedCount > 1 ? 's' : ''}?`)) {
                    e.preventDefault();
                    return false;
                }
            });
        });
        
        function previewEmail() {
            const subject = document.getElementById('subject').value;
            const message = document.getElementById('message').value;
            
            // Use first selected candidate for preview
            const firstCandidate = document.querySelector('input[name="candidates[]"]:checked');
            if (!firstCandidate) {
                alert('Please select at least one candidate to preview the email.');
                return;
            }
            
            const candidateInfo = firstCandidate.closest('.candidate-item').querySelector('.candidate-info');
            const candidateName = candidateInfo.querySelector('.candidate-name').textContent.trim();
            const [firstName, ...lastNameParts] = candidateName.split(' ');
            const lastName = lastNameParts.join(' ');
            
            // Replace placeholders
            const previewMessage = message
                .replace(/{first_name}/g, firstName)
                .replace(/{last_name}/g, lastName)
                .replace(/{job_title}/g, '<?php echo addslashes($job['title']); ?>')
                .replace(/{company}/g, '<?php echo addslashes($job['company']); ?>');
            
            document.getElementById('previewSubject').textContent = subject;
            document.getElementById('previewBody').innerHTML = previewMessage.replace(/\n/g, '<br>');
            document.getElementById('previewModal').style.display = 'flex';
        }
        
        function closePreviewModal() {
            document.getElementById('previewModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        document.getElementById('previewModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePreviewModal();
            }
        });
    </script>
</body>
</html>
