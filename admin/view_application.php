<?php
require_once '../config/config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirectTo('../auth/login.php');
}

$database = new Database();
$db = $database->getConnection();

$application_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$application_id) {
    redirectTo('applications.php');
}

// Get application details with job info
$query = "SELECT a.*, j.title as job_title, j.company, j.location, j.job_type 
          FROM applications a 
          JOIN jobs j ON a.job_id = j.id 
          WHERE a.id = :application_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':application_id', $application_id);
$stmt->execute();

if ($stmt->rowCount() === 0) {
    redirectTo('applications.php');
}

$application = $stmt->fetch(PDO::FETCH_ASSOC);

// Get screening question answers
$query = "SELECT sq.question, sq.question_type, aa.answer 
          FROM application_answers aa 
          JOIN screening_questions sq ON aa.question_id = sq.id 
          WHERE aa.application_id = :application_id 
          ORDER BY sq.id";
$stmt = $db->prepare($query);
$stmt->bindParam(':application_id', $application_id);
$stmt->execute();
$answers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $new_status = $_POST['new_status'];
        $notes = sanitizeInput($_POST['notes']);
        
        $query = "UPDATE applications SET status = :status WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([':status' => $new_status, ':id' => $application_id]);
        
        $success = "Application status updated successfully";
        $application['status'] = $new_status; // Update for display
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Application - Recruit Portal</title>
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
                <h1>Application Details</h1>
                <div class="header-actions">
                    <?php if ($application['status'] === 'Shortlisted'): ?>
                        <a href="email_shortlisted.php?job_id=<?php echo $application['job_id']; ?>" class="btn btn-success">
                            Email All Shortlisted
                        </a>
                    <?php endif; ?>
                    <a href="<?= isset($_SERVER['HTTP_REFERER']) ? htmlspecialchars($_SERVER['HTTP_REFERER']) : 'applications.php'; ?>" class="btn btn-secondary">← Back</a>
                </div>
            </header>

            <div class="dashboard-content">
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <div class="application-details">
                    <!-- Job Information -->
                    <div class="section">
                        <div class="section-header">
                            <h2>Job Information</h2>
                        </div>
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Position:</label>
                                <span><?php echo htmlspecialchars($application['job_title']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Company:</label>
                                <span><?php echo htmlspecialchars($application['company']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Location:</label>
                                <span><?php echo htmlspecialchars($application['location']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Job Type:</label>
                                <span><?php echo htmlspecialchars($application['job_type']); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Applicant Information -->
                    <div class="section">
                        <div class="section-header">
                            <h2>Applicant Information</h2>
                            <span class="status status-<?php echo strtolower($application['status']); ?>">
                                <?php echo $application['status']; ?>
                            </span>
                        </div>
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Full Name:</label>
                                <span><?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Email:</label>
                                <span><a href="mailto:<?php echo htmlspecialchars($application['email']); ?>"><?php echo htmlspecialchars($application['email']); ?></a></span>
                            </div>
                            <div class="info-item">
                                <label>Phone:</label>
                                <span><a href="tel:<?php echo htmlspecialchars($application['phone']); ?>"><?php echo htmlspecialchars($application['phone']); ?></a></span>
                            </div>
                            <div class="info-item">
                                <label>Applied Date:</label>
                                <span><?php echo date('M j, Y g:i A', strtotime($application['applied_at'])); ?></span>
                            </div>
                            <?php if ($application['address']): ?>
                                <div class="info-item full-width">
                                    <label>Address:</label>
                                    <span><?php echo htmlspecialchars($application['address']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Documents and Links -->
                    <div class="section">
                        <div class="section-header">
                            <h2>Documents & Links</h2>
                        </div>
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Resume:</label>
                                <?php if ($application['resume_path']): ?>
                                    <span><a href="../<?php echo $application['resume_path']; ?>" target="_blank" class="btn btn-sm btn-primary">View Resume</a></span>
                                <?php else: ?>
                                    <span class="text-muted">No resume uploaded</span>
                                <?php endif; ?>
                            </div>
                            <?php if ($application['portfolio_url']): ?>
                                <div class="info-item">
                                    <label>Portfolio:</label>
                                    <span><a href="<?php echo htmlspecialchars($application['portfolio_url']); ?>" target="_blank">View Portfolio</a></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($application['linkedin_url']): ?>
                                <div class="info-item">
                                    <label>LinkedIn:</label>
                                    <span><a href="<?php echo htmlspecialchars($application['linkedin_url']); ?>" target="_blank">View LinkedIn</a></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($application['cover_letter']): ?>
                            <div class="cover-letter">
                                <label>Cover Letter:</label>
                                <div class="cover-letter-content">
                                    <?php echo nl2br(htmlspecialchars($application['cover_letter'])); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Screening Questions -->
                    <?php if (!empty($answers)): ?>
                        <div class="section">
                            <div class="section-header">
                                <h2>Screening Questions</h2>
                            </div>
                            <div class="screening-answers">
                                <?php foreach ($answers as $answer): ?>
                                    <div class="answer-item">
                                        <div class="question">
                                            <strong>Q: <?php echo htmlspecialchars($answer['question']); ?></strong>
                                        </div>
                                        <div class="answer">
                                            A: <?php echo nl2br(htmlspecialchars($answer['answer'])); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Status Management -->
                    <div class="section">
                        <div class="section-header">
                            <h2>Status Management</h2>
                        </div>
                        <form method="POST" class="status-form">
                            <input type="hidden" name="action" value="update_status">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="new_status">Update Status:</label>
                                    <select id="new_status" name="new_status" required>
                                        <option value="">Select Status</option>
                                        <option value="Pending" <?php echo $application['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="Reviewed" <?php echo $application['status'] === 'Reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                                        <option value="Shortlisted" <?php echo $application['status'] === 'Shortlisted' ? 'selected' : ''; ?>>Shortlisted</option>
                                        <option value="Rejected" <?php echo $application['status'] === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="btn btn-primary">Update Status</button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="notes">Notes (optional):</label>
                                <textarea id="notes" name="notes" rows="3" placeholder="Add any notes about this application..."></textarea>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <style>
        .application-details {
            max-width: 1000px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            padding: 1.5rem;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .info-item.full-width {
            grid-column: 1 / -1;
        }
        
        .info-item label {
            font-weight: 600;
            color: #374151;
        }
        
        .info-item span {
            color: #64748b;
        }
        
        .cover-letter {
            padding: 1.5rem;
            border-top: 1px solid #e2e8f0;
        }
        
        .cover-letter label {
            font-weight: 600;
            color: #374151;
            display: block;
            margin-bottom: 1rem;
        }
        
        .cover-letter-content {
            background: #f8fafc;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid #3b82f6;
            line-height: 1.6;
        }
        
        .screening-answers {
            padding: 1.5rem;
        }
        
        .answer-item {
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 8px;
            border-left: 4px solid #10b981;
        }
        
        .answer-item .question {
            margin-bottom: 0.5rem;
            color: #374151;
        }
        
        .answer-item .answer {
            color: #64748b;
            line-height: 1.5;
        }
        
        .status-form {
            padding: 1.5rem;
        }
        
        .text-muted {
            color: #9ca3af;
            font-style: italic;
        }

        .header-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
    </style>
</body>
</html>
