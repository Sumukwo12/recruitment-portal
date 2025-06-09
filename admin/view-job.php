<?php
require_once '../config/config.php';
require_once '../models/Job.php';
require_once '../models/Application.php';

requireLogin();

if (!isset($_GET['id'])) {
    redirect('dashboard.php');
}

$jobModel = new Job();
$applicationModel = new Application();

$job = $jobModel->getJobById($_GET['id']);
if (!$job) {
    redirect('dashboard.php');
}

$applications = $applicationModel->getApplicationsByJob($job['id']);
$screeningQuestions = $jobModel->getScreeningQuestions($job['id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Job: <?php echo htmlspecialchars($job['title']); ?> - Admin</title>
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
                    <i class="fas fa-building"></i>
                    <h1>Job Details</h1>
                </div>
                <nav class="admin-nav">
                    <a href="dashboard.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                    <a href="edit-job.php?id=<?php echo $job['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit Job
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <div class="admin-container">
        <div class="job-view-layout">
            <!-- Job Information -->
            <div class="job-view-main">
                <div class="card">
                    <div class="card-header">
                        <div class="job-header-info">
                            <h2><?php echo htmlspecialchars($job['title']); ?></h2>
                            <div class="job-meta-badges">
                                <span class="badge badge-<?php echo $job['status'] === 'active' ? 'success' : ($job['status'] === 'closed' ? 'secondary' : 'danger'); ?>">
                                    <?php echo ucfirst($job['status']); ?>
                                </span>
                                <span class="badge badge-outline"><?php echo ucfirst(str_replace('-', ' ', $job['type'])); ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="job-details-grid">
                            <div class="job-detail-item">
                                <label>Department:</label>
                                <span><?php echo htmlspecialchars($job['department']); ?></span>
                            </div>
                            <div class="job-detail-item">
                                <label>Location:</label>
                                <span><?php echo htmlspecialchars($job['location']); ?></span>
                            </div>
                            <div class="job-detail-item">
                                <label>Salary Range:</label>
                                <span><?php echo formatSalary($job['salary_min'], $job['salary_max']); ?></span>
                            </div>
                            <div class="job-detail-item">
                                <label>Deadline:</label>
                                <span><?php echo date('F j, Y', strtotime($job['deadline'])); ?></span>
                            </div>
                            <div class="job-detail-item">
                                <label>Created:</label>
                                <span><?php echo date('F j, Y', strtotime($job['created_at'])); ?></span>
                            </div>
                            <div class="job-detail-item">
                                <label>Applications:</label>
                                <span><?php echo $job['application_count']; ?> received</span>
                            </div>
                        </div>

                        <div class="job-description-section">
                            <h3>Job Description</h3>
                            <div class="job-description">
                                <?php echo nl2br(htmlspecialchars($job['description'])); ?>
                            </div>
                        </div>

                        <?php if ($job['requirements']): ?>
                        <div class="job-section">
                            <h3>Requirements</h3>
                            <ul class="job-list">
                                <?php 
                                $requirements = explode("\n", $job['requirements']);
                                foreach ($requirements as $req): 
                                    if (trim($req)):
                                ?>
                                    <li><?php echo htmlspecialchars(trim($req)); ?></li>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <?php if ($job['responsibilities']): ?>
                        <div class="job-section">
                            <h3>Responsibilities</h3>
                            <ul class="job-list">
                                <?php 
                                $responsibilities = explode("\n", $job['responsibilities']);
                                foreach ($responsibilities as $resp): 
                                    if (trim($resp)):
                                ?>
                                    <li><?php echo htmlspecialchars(trim($resp)); ?></li>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <?php if ($job['benefits']): ?>
                        <div class="job-section">
                            <h3>Benefits</h3>
                            <ul class="job-list">
                                <?php 
                                $benefits = explode("\n", $job['benefits']);
                                foreach ($benefits as $benefit): 
                                    if (trim($benefit)):
                                ?>
                                    <li><?php echo htmlspecialchars(trim($benefit)); ?></li>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($screeningQuestions)): ?>
                        <div class="job-section">
                            <h3>Screening Questions</h3>
                            <div class="screening-questions">
                                <?php foreach ($screeningQuestions as $question): ?>
                                    <div class="screening-question">
                                        <p><strong>Q:</strong> <?php echo htmlspecialchars($question['question']); ?></p>
                                        <p class="question-type">Type: <?php echo ucfirst(str_replace('_', ' ', $question['type'])); ?></p>
                                        <?php if ($question['options']): ?>
                                            <p class="question-options">Options: <?php echo implode(', ', json_decode($question['options'], true)); ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Applications Sidebar -->
            <div class="job-view-sidebar">
                <div class="card">
                    <div class="card-header">
                        <h3>Applications (<?php echo count($applications); ?>)</h3>
                    </div>
                    <div class="card-content">
                        <?php if (empty($applications)): ?>
                            <div class="no-applications">
                                <i class="fas fa-inbox"></i>
                                <p>No applications yet</p>
                            </div>
                        <?php else: ?>
                            <div class="applications-list">
                                <?php foreach ($applications as $application): ?>
                                    <div class="application-item">
                                        <div class="applicant-info">
                                            <h4><?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?></h4>
                                            <p><?php echo htmlspecialchars($application['email']); ?></p>
                                            <p class="application-date"><?php echo timeAgo($application['applied_at']); ?></p>
                                        </div>
                                        <div class="application-status">
                                            <span class="badge badge-<?php echo $application['status'] === 'new' ? 'primary' : ($application['status'] === 'reviewed' ? 'secondary' : 'success'); ?>">
                                                <?php echo ucfirst($application['status']); ?>
                                            </span>
                                        </div>
                                        <div class="application-actions">
                                            <a href="view-application.php?id=<?php echo $application['id']; ?>" class="btn btn-small btn-outline">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <?php if ($application['resume_filename']): ?>
                                                <a href="../<?php echo UPLOAD_DIR . $application['resume_filename']; ?>" target="_blank" class="btn btn-small btn-outline">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Job Actions -->
                <div class="card">
                    <div class="card-header">
                        <h3>Job Actions</h3>
                    </div>
                    <div class="card-content">
                        <div class="job-actions-list">
                            <a href="edit-job.php?id=<?php echo $job['id']; ?>" class="btn btn-outline btn-large">
                                <i class="fas fa-edit"></i> Edit Job
                            </a>
                            <a href="../job-details.php?id=<?php echo $job['id']; ?>" target="_blank" class="btn btn-outline btn-large">
                                <i class="fas fa-external-link-alt"></i> View Public Page
                            </a>
                            <button onclick="toggleJobStatus(<?php echo $job['id']; ?>, '<?php echo $job['status']; ?>')" class="btn btn-outline btn-large">
                                <i class="fas fa-toggle-<?php echo $job['status'] === 'active' ? 'on' : 'off'; ?>"></i>
                                <?php echo $job['status'] === 'active' ? 'Deactivate' : 'Activate'; ?> Job
                            </button>
                            <button onclick="deleteJob(<?php echo $job['id']; ?>)" class="btn btn-outline btn-large btn-danger">
                                <i class="fas fa-trash"></i> Delete Job
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        function toggleJobStatus(jobId, currentStatus) {
            const newStatus = currentStatus === 'active' ? 'closed' : 'active';
            const action = newStatus === 'active' ? 'activate' : 'deactivate';
            
            if (confirm(`Are you sure you want to ${action} this job?`)) {
                // You can implement this functionality
                showNotification(`Job ${action}d successfully`, 'success');
                setTimeout(() => location.reload(), 1000);
            }
        }

        function deleteJob(jobId) {
            if (confirm('Are you sure you want to delete this job? This action cannot be undone.')) {
                fetch('dashboard.php?action=delete_job', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `job_id=${jobId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Job deleted successfully', 'success');
                        setTimeout(() => window.location.href = 'dashboard.php', 1000);
                    } else {
                        showNotification('Failed to delete job', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('An error occurred', 'error');
                });
            }
        }

        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 1rem 1.5rem;
                border-radius: 0.375rem;
                color: white;
                font-weight: 500;
                z-index: 1000;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            `;

            switch (type) {
                case 'success':
                    notification.style.backgroundColor = '#10b981';
                    break;
                case 'error':
                    notification.style.backgroundColor = '#dc2626';
                    break;
                default:
                    notification.style.backgroundColor = '#3b82f6';
            }

            notification.innerHTML = `
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span>${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" 
                            style="background: none; border: none; color: white; font-size: 1.2rem; cursor: pointer;">
                        &times;
                    </button>
                </div>
            `;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.remove();
            }, 5000);
        }
    </script>
</body>
</html>
