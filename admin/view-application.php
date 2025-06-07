<?php
require_once '../config/config.php';
require_once '../models/Application.php';
require_once '../models/Job.php';

requireLogin();

if (!isset($_GET['id'])) {
    redirect('dashboard.php');
}

$applicationModel = new Application();
$jobModel = new Job();

$application = $applicationModel->getApplicationById($_GET['id']);
if (!$application) {
    redirect('dashboard.php');
}

$screeningAnswers = $applicationModel->getScreeningAnswers($application['id']);

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus = sanitizeInput($_POST['status']);
    if ($applicationModel->updateApplicationStatus($application['id'], $newStatus)) {
        $application['status'] = $newStatus;
        $success_message = 'Application status updated successfully';
    } else {
        $error_message = 'Failed to update application status';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application: <?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?> - Admin</title>
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
                    <i class="fas fa-user"></i>
                    <h1>Application Details</h1>
                </div>
                <nav class="admin-nav">
                    <a href="dashboard.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                    <a href="view-job.php?id=<?php echo $application['job_id']; ?>" class="btn btn-outline">
                        <i class="fas fa-briefcase"></i> View Job
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <div class="admin-container">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="application-view-layout">
            <!-- Main Application Details -->
            <div class="application-view-main">
                <!-- Applicant Information -->
                <div class="card">
                    <div class="card-header">
                        <div class="applicant-header">
                            <div class="applicant-info">
                                <h2><?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?></h2>
                                <p class="job-title">Applied for: <?php echo htmlspecialchars($application['job_title']); ?></p>
                                <p class="application-date">Applied: <?php echo date('F j, Y \a\t g:i A', strtotime($application['applied_at'])); ?></p>
                            </div>
                            <div class="application-status-badge">
                                <span class="badge badge-<?php echo $application['status'] === 'new' ? 'primary' : ($application['status'] === 'reviewed' ? 'secondary' : ($application['status'] === 'interview' ? 'warning' : ($application['status'] === 'hired' ? 'success' : 'danger'))); ?>">
                                    <?php echo ucfirst($application['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="contact-info-grid">
                            <div class="contact-item">
                                <label><i class="fas fa-envelope"></i> Email:</label>
                                <span><a href="mailto:<?php echo htmlspecialchars($application['email']); ?>"><?php echo htmlspecialchars($application['email']); ?></a></span>
                            </div>
                            <div class="contact-item">
                                <label><i class="fas fa-phone"></i> Phone:</label>
                                <span><a href="tel:<?php echo htmlspecialchars($application['phone']); ?>"><?php echo htmlspecialchars($application['phone']); ?></a></span>
                            </div>
                            <?php if ($application['address']): ?>
                            <div class="contact-item">
                                <label><i class="fas fa-map-marker-alt"></i> Address:</label>
                                <span>
                                    <?php echo htmlspecialchars($application['address']); ?>
                                    <?php if ($application['city'] || $application['state'] || $application['zip_code']): ?>
                                        <br><?php echo htmlspecialchars($application['city'] . ', ' . $application['state'] . ' ' . $application['zip_code']); ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <?php endif; ?>
                            <?php if ($application['portfolio_url']): ?>
                            <div class="contact-item">
                                <label><i class="fas fa-globe"></i> Portfolio:</label>
                                <span><a href="<?php echo htmlspecialchars($application['portfolio_url']); ?>" target="_blank"><?php echo htmlspecialchars($application['portfolio_url']); ?></a></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($application['linkedin_url']): ?>
                            <div class="contact-item">
                                <label><i class="fab fa-linkedin"></i> LinkedIn:</label>
                                <span><a href="<?php echo htmlspecialchars($application['linkedin_url']); ?>" target="_blank"><?php echo htmlspecialchars($application['linkedin_url']); ?></a></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Cover Letter -->
                <?php if ($application['cover_letter']): ?>
                <div class="card">
                    <div class="card-header">
                        <h3>Cover Letter</h3>
                    </div>
                    <div class="card-content">
                        <div class="cover-letter">
                            <?php echo nl2br(htmlspecialchars($application['cover_letter'])); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Screening Questions -->
                <?php if (!empty($screeningAnswers)): ?>
                <div class="card">
                    <div class="card-header">
                        <h3>Screening Questions</h3>
                    </div>
                    <div class="card-content">
                        <div class="screening-answers">
                            <?php foreach ($screeningAnswers as $answer): ?>
                                <div class="screening-answer">
                                    <div class="question">
                                        <strong>Q:</strong> <?php echo htmlspecialchars($answer['question']); ?>
                                    </div>
                                    <div class="answer">
                                        <strong>A:</strong> <?php echo htmlspecialchars($answer['answer']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Additional Information -->
                <?php if ($application['referral_source'] || $application['additional_info']): ?>
                <div class="card">
                    <div class="card-header">
                        <h3>Additional Information</h3>
                    </div>
                    <div class="card-content">
                        <?php if ($application['referral_source']): ?>
                            <div class="additional-info-item">
                                <label>How they heard about us:</label>
                                <span><?php echo ucfirst(str_replace('-', ' ', htmlspecialchars($application['referral_source']))); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($application['additional_info']): ?>
                            <div class="additional-info-item">
                                <label>Additional Comments:</label>
                                <div class="additional-comments">
                                    <?php echo nl2br(htmlspecialchars($application['additional_info'])); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="application-view-sidebar">
                <!-- Status Management -->
                <div class="card">
                    <div class="card-header">
                        <h3>Application Status</h3>
                    </div>
                    <div class="card-content">
                        <form method="POST" class="status-form">
                            <div class="form-group">
                                <label for="status">Update Status:</label>
                                <select id="status" name="status" class="status-select">
                                    <option value="new" <?php echo $application['status'] === 'new' ? 'selected' : ''; ?>>New</option>
                                    <option value="reviewed" <?php echo $application['status'] === 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                                    <option value="interview" <?php echo $application['status'] === 'interview' ? 'selected' : ''; ?>>Interview</option>
                                    <option value="rejected" <?php echo $application['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                    <option value="hired" <?php echo $application['status'] === 'hired' ? 'selected' : ''; ?>>Hired</option>
                                </select>
                            </div>
                            <button type="submit" name="update_status" class="btn btn-primary btn-large">
                                Update Status
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Resume Download -->
                <?php if ($application['resume_filename']): ?>
                <div class="card">
                    <div class="card-header">
                        <h3>Resume/CV</h3>
                    </div>
                    <div class="card-content">
                        <div class="resume-info">
                            <div class="file-icon">
                                <i class="fas fa-file-pdf"></i>
                            </div>
                            <div class="file-details">
                                <p class="file-name"><?php echo htmlspecialchars($application['resume_filename']); ?></p>
                                <p class="file-type">PDF Document</p>
                            </div>
                        </div>
                        <a href="../<?php echo UPLOAD_DIR . $application['resume_filename']; ?>" target="_blank" class="btn btn-outline btn-large">
                            <i class="fas fa-download"></i> Download Resume
                        </a>
                        <a href="../<?php echo UPLOAD_DIR . $application['resume_filename']; ?>" target="_blank" class="btn btn-outline btn-large">
                            <i class="fas fa-eye"></i> View Resume
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h3>Quick Actions</h3>
                    </div>
                    <div class="card-content">
                        <div class="quick-actions">
                            <a href="mailto:<?php echo htmlspecialchars($application['email']); ?>?subject=Re: Your application for <?php echo urlencode($application['job_title']); ?>" class="btn btn-outline btn-large">
                                <i class="fas fa-envelope"></i> Send Email
                            </a>
                            <button onclick="scheduleInterview()" class="btn btn-outline btn-large">
                                <i class="fas fa-calendar"></i> Schedule Interview
                            </button>
                            <button onclick="addNotes()" class="btn btn-outline btn-large">
                                <i class="fas fa-sticky-note"></i> Add Notes
                            </button>
                            <button onclick="printApplication()" class="btn btn-outline btn-large">
                                <i class="fas fa-print"></i> Print Application
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Application Timeline -->
                <div class="card">
                    <div class="card-header">
                        <h3>Timeline</h3>
                    </div>
                    <div class="card-content">
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-icon">
                                    <i class="fas fa-paper-plane"></i>
                                </div>
                                <div class="timeline-content">
                                    <h4>Application Submitted</h4>
                                    <p><?php echo date('F j, Y \a\t g:i A', strtotime($application['applied_at'])); ?></p>
                                </div>
                            </div>
                            <?php if ($application['status'] !== 'new'): ?>
                            <div class="timeline-item">
                                <div class="timeline-icon">
                                    <i class="fas fa-eye"></i>
                                </div>
                                <div class="timeline-content">
                                    <h4>Status: <?php echo ucfirst($application['status']); ?></h4>
                                    <p>Updated by admin</p>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        function scheduleInterview() {
            alert('Interview scheduling feature coming soon!');
        }

        function addNotes() {
            alert('Notes feature coming soon!');
        }

        function printApplication() {
            window.print();
        }
    </script>
</body>
</html>
