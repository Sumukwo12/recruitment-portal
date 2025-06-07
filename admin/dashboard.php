<?php
require_once '../config/config.php';
require_once '../models/Job.php';
require_once '../models/Application.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

requireLogin();

$jobModel = new Job();
$applicationModel = new Application();

$stats = $jobModel->getDashboardStats();
$jobs = $jobModel->getAllJobs();
$applications = $applicationModel->getAllApplications();

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'delete_job':
            if (isset($_POST['job_id'])) {
                try {
                    $result = $jobModel->deleteJob($_POST['job_id']);
                    echo json_encode(['success' => $result]);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Job ID not provided.']);
            }
            exit;
            
        case 'update_application_status':
            if (isset($_POST['application_id']) && isset($_POST['status'])) {
                try {
                    $result = $applicationModel->updateApplicationStatus($_POST['application_id'], $_POST['status']);
                    echo json_encode(['success' => $result]);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Application ID or status not provided.']);
            }
            exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - TechCorp Careers</title>
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
                    <h1>Admin Dashboard</h1>
                </div>
                <nav class="admin-nav">
                    <a href="../index.php" class="btn btn-outline">View Public Site</a>
                    <a href="logout.php" class="btn btn-outline">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <div class="admin-container">
        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-info">
                        <p class="stat-label">Total Jobs</p>
                        <p class="stat-number"><?php echo $stats['total_jobs']; ?></p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-briefcase"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-info">
                        <p class="stat-label">Active Jobs</p>
                        <p class="stat-number"><?php echo $stats['active_jobs']; ?></p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-info">
                        <p class="stat-label">Total Applications</p>
                        <p class="stat-number"><?php echo $stats['total_applications']; ?></p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-info">
                        <p class="stat-label">New Applications</p>
                        <p class="stat-number"><?php echo $stats['new_applications']; ?></p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-trending-up"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="admin-tabs">
            <div class="tab-buttons">
                <button class="tab-button active" onclick="showTab('jobs')">Job Management</button>
                <button class="tab-button" onclick="showTab('applications')">Applications</button>
                <button class="tab-button" onclick="showTab('settings')">Settings</button>
            </div>

            <!-- Jobs Tab -->
            <div class="tab-content active" id="jobs-tab">
                <div class="card">
                    <div class="card-header">
                        <div class="card-header-content">
                            <h2>Job Postings</h2>
                            <a href="create-job.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Create New Job
                            </a>
                        </div>
                    </div>
                    <div class="card-content">
                        <!-- Filters -->
                        <div class="filters">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" id="jobSearch" placeholder="Search jobs..." onkeyup="filterJobs()">
                            </div>
                            <select id="statusFilter" onchange="filterJobs()">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="closed">Closed</option>
                                <option value="expired">Expired</option>
                            </select>
                            <select id="departmentFilter" onchange="filterJobs()">
                                <option value="">All Departments</option>
                                <option value="Engineering">Engineering</option>
                                <option value="Product">Product</option>
                                <option value="Design">Design</option>
                                <option value="Marketing">Marketing</option>
                            </select>
                        </div>

                        <!-- Jobs Table -->
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Job Title</th>
                                        <th>Department</th>
                                        <th>Status</th>
                                        <th>Applications</th>
                                        <th>Deadline</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="jobsTableBody">
                                    <?php foreach ($jobs as $job): ?>
                                        <tr data-department="<?php echo $job['department']; ?>" data-status="<?php echo $job['status']; ?>">
                                            <td class="job-title"><?php echo htmlspecialchars($job['title']); ?></td>
                                            <td><?php echo htmlspecialchars($job['department']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $job['status'] === 'active' ? 'success' : ($job['status'] === 'closed' ? 'secondary' : 'danger'); ?>">
                                                    <?php echo ucfirst($job['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $job['application_count']; ?></td>
                                            <td><?php echo date('M j, Y', strtotime($job['deadline'])); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="view-job.php?id=<?php echo $job['id']; ?>" class="btn btn-small btn-outline">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="edit-job.php?id=<?php echo $job['id']; ?>" class="btn btn-small btn-outline">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button onclick="deleteJob(<?php echo $job['id']; ?>)" class="btn btn-small btn-outline btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Applications Tab -->
            <div class="tab-content" id="applications-tab">
                <div class="card">
                    <div class="card-header">
                        <div class="card-header-content">
                            <h2>Recent Applications</h2>
                            <button class="btn btn-outline">
                                <i class="fas fa-download"></i> Export Data
                            </button>
                        </div>
                    </div>
                    <div class="card-content">
                        <!-- Search -->
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="applicationSearch" placeholder="Search applications..." onkeyup="filterApplications()">
                        </div>

                        <!-- Applications Table -->
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Applicant</th>
                                        <th>Position</th>
                                        <th>Applied Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="applicationsTableBody">
                                    <?php foreach ($applications as $application): ?>
                                        <tr>
                                            <td>
                                                <div class="applicant-info">
                                                    <div class="applicant-name"><?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?></div>
                                                    <div class="applicant-email"><?php echo htmlspecialchars($application['email']); ?></div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($application['job_title']); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($application['applied_at'])); ?></td>
                                            <td>
                                                <select class="status-select" onchange="updateApplicationStatus(<?php echo $application['id']; ?>, this.value)">
                                                    <option value="new" <?php echo $application['status'] === 'new' ? 'selected' : ''; ?>>New</option>
                                                    <option value="reviewed" <?php echo $application['status'] === 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                                                    <option value="interview" <?php echo $application['status'] === 'interview' ? 'selected' : ''; ?>>Interview</option>
                                                    <option value="rejected" <?php echo $application['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                                    <option value="hired" <?php echo $application['status'] === 'hired' ? 'selected' : ''; ?>>Hired</option>
                                                </select>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="view-application.php?id=<?php echo $application['id']; ?>" class="btn btn-small btn-outline">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if ($application['resume_filename']): ?>
                                                        <a href="../<?php echo UPLOAD_DIR . $application['resume_filename']; ?>" target="_blank" class="btn btn-small btn-outline">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settings Tab -->
            <div class="tab-content" id="settings-tab">
                <div class="card">
                    <div class="card-header">
                        <h2>System Settings</h2>
                    </div>
                    <div class="card-content">
                        <div class="settings-list">
                            <div class="setting-item">
                                <div class="setting-info">
                                    <h3>Job Board Visibility</h3>
                                    <p>Control whether the public job board is visible to applicants</p>
                                </div>
                                <button class="btn btn-outline">Toggle Visibility</button>
                            </div>

                            <div class="setting-item">
                                <div class="setting-info">
                                    <h3>Application Notifications</h3>
                                    <p>Receive email notifications for new applications</p>
                                </div>
                                <button class="btn btn-outline">Configure</button>
                            </div>

                            <div class="setting-item">
                                <div class="setting-info">
                                    <h3>CV Filtering Options</h3>
                                    <p>Configure which CV sections to display in application reviews</p>
                                </div>
                                <button class="btn btn-outline">Manage Filters</button>
                            </div>

                            <div class="setting-item">
                                <div class="setting-info">
                                    <h3>Bulk Operations</h3>
                                    <p>Export applications, extend deadlines, and manage multiple jobs</p>
                                </div>
                                <button class="btn btn-outline">Bulk Actions</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/admin.js"></script>
</body>
</html>
