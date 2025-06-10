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

// Get filter parameters
$filters = [
    'search' => $_GET['search'] ?? '',
    'status' => $_GET['status'] ?? '',
    'job_id' => $_GET['job_id'] ?? '',
    'department' => $_GET['department'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
    'experience' => $_GET['experience'] ?? '',
    'location' => $_GET['location'] ?? ''
];

$applications = $applicationModel->getFilteredApplications($filters);
$departments = $applicationModel->getDepartments();
$jobsList = $applicationModel->getJobsForFilter();

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

        case 'bulk_update_status':
            if (isset($_POST['application_ids']) && isset($_POST['status'])) {
                try {
                    $ids = json_decode($_POST['application_ids'], true);
                    $result = $applicationModel->bulkUpdateStatus($ids, $_POST['status']);
                    echo json_encode(['success' => $result, 'updated' => count($ids)]);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Application IDs or status not provided.']);
            }
            exit;

        case 'export_applications':
            try {
                $exportData = $applicationModel->getExportData($filters);
                echo json_encode(['success' => true, 'data' => $exportData]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
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
    <title>Admin Dashboard - Geonet Technologies Limited Careers</title>
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
                <button class="tab-button" onclick="showTab('settings')">settings</button>
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
                                <option value="Sheq">SHEQ</option>
                                <option value="Procurement">Procurement</option>
                                <option value="Design">Planing & Design</option>
                                <option value="Sales">Sales</option>
                                <option value="HR">Human Resource</option>
                                <option value="Finance">Finance</option>
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
                            <h2>Applications Management (<?php echo count($applications); ?> found)</h2>
                            <div class="header-actions">
                                <button class="btn btn-outline" onclick="toggleAdvancedFilters()">
                                    <i class="fas fa-filter"></i> Advanced Filters
                                </button>
                                <button class="btn btn-outline" onclick="exportApplications()">
                                    <i class="fas fa-download"></i> Export Data
                                </button>
                                <button class="btn btn-primary" onclick="showBulkActions()" id="bulkActionsBtn" style="display: none;">
                                    <i class="fas fa-tasks"></i> Bulk Actions
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-content">
                        <!-- Advanced Filters Panel -->
                        <div class="advanced-filters" id="advancedFilters" style="display: none;">
                            <form method="GET" class="filters-form">
                                <input type="hidden" name="tab" value="applications">
                                
                                <div class="filters-grid">
                                    <!-- Search -->
                                    <div class="filter-group">
                                        <label for="search">Search Applicants</label>
                                        <input type="text" id="search" name="search" 
                                               value="<?php echo htmlspecialchars($filters['search']); ?>"
                                               placeholder="Name, email, or keywords...">
                                    </div>

                                    <!-- Status Filter -->
                                    <div class="filter-group">
                                        <label for="status">Application Status</label>
                                        <select id="status" name="status">
                                            <option value="">All Statuses</option>
                                            <option value="new" <?php echo $filters['status'] === 'new' ? 'selected' : ''; ?>>New</option>
                                            <option value="reviewed" <?php echo $filters['status'] === 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                                            <option value="interview" <?php echo $filters['status'] === 'interview' ? 'selected' : ''; ?>>Interview</option>
                                            <option value="rejected" <?php echo $filters['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                            <option value="hired" <?php echo $filters['status'] === 'hired' ? 'selected' : ''; ?>>Hired</option>
                                        </select>
                                    </div>

                                    <!-- Job Position Filter -->
                                    <div class="filter-group">
                                        <label for="job_id">Job Position</label>
                                        <select id="job_id" name="job_id">
                                            <option value="">All Positions</option>
                                            <?php foreach ($jobsList as $job): ?>
                                                <option value="<?php echo $job['id']; ?>" 
                                                        <?php echo $filters['job_id'] == $job['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($job['title']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- Department Filter -->
                                    <div class="filter-group">
                                        <label for="department">Department</label>
                                        <select id="department" name="department">
                                            <option value="">All Departments</option>
                                            <?php foreach ($departments as $dept): ?>
                                                <option value="<?php echo $dept['department']; ?>" 
                                                        <?php echo $filters['department'] === $dept['department'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($dept['department']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- Date Range -->
                                    <div class="filter-group">
                                        <label for="date_from">Applied From</label>
                                        <input type="date" id="date_from" name="date_from" 
                                               value="<?php echo htmlspecialchars($filters['date_from']); ?>">
                                    </div>

                                    <div class="filter-group">
                                        <label for="date_to">Applied To</label>
                                        <input type="date" id="date_to" name="date_to" 
                                               value="<?php echo htmlspecialchars($filters['date_to']); ?>">
                                    </div>

                                    <!-- Experience Level -->
                                    <div class="filter-group">
                                        <label for="experience">Experience Level</label>
                                        <select id="experience" name="experience">
                                            <option value="">All Levels</option>
                                            <option value="entry" <?php echo $filters['experience'] === 'entry' ? 'selected' : ''; ?>>Entry Level (0-2 years)</option>
                                            <option value="mid" <?php echo $filters['experience'] === 'mid' ? 'selected' : ''; ?>>Mid Level (3-5 years)</option>
                                            <option value="senior" <?php echo $filters['experience'] === 'senior' ? 'selected' : ''; ?>>Senior Level (5+ years)</option>
                                        </select>
                                    </div>

                                    <!-- Location Filter -->
                                    <div class="filter-group">
                                        <label for="location">Location Preference</label>
                                        <select id="location" name="location">
                                            <option value="">All Locations</option>
                                            <option value="remote" <?php echo $filters['location'] === 'remote' ? 'selected' : ''; ?>>Remote</option>
                                            <option value="onsite" <?php echo $filters['location'] === 'onsite' ? 'selected' : ''; ?>>On-site</option>
                                            <option value="hybrid" <?php echo $filters['location'] === 'hybrid' ? 'selected' : ''; ?>>Hybrid</option>
                                            <option value="flexible" <?php echo $filters['location'] === 'flexible' ? 'selected' : ''; ?>>Flexible</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="filters-actions">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Apply Filters
                                    </button>
                                    <a href="?tab=applications" class="btn btn-outline">
                                        <i class="fas fa-times"></i> Clear Filters
                                    </a>
                                    <button type="button" class="btn btn-outline" onclick="saveFilterPreset()">
                                        <i class="fas fa-save"></i> Save Preset
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Quick Filters -->
                        <div class="quick-filters">
                            <span class="filter-label">Quick Filters:</span>
                            <button class="quick-filter-btn <?php echo $filters['status'] === 'new' ? 'active' : ''; ?>" 
                                    onclick="applyQuickFilter('status', 'new')">
                                New Applications
                            </button>
                            <button class="quick-filter-btn <?php echo $filters['status'] === 'interview' ? 'active' : ''; ?>" 
                                    onclick="applyQuickFilter('status', 'interview')">
                                Interview Stage
                            </button>
                            <button class="quick-filter-btn <?php echo $filters['date_from'] === date('Y-m-d', strtotime('-7 days')) ? 'active' : ''; ?>" 
                                    onclick="applyQuickFilter('date_from', '<?php echo date('Y-m-d', strtotime('-7 days')); ?>')">
                                Last 7 Days
                            </button>
                            <button class="quick-filter-btn <?php echo $filters['experience'] === 'senior' ? 'active' : ''; ?>" 
                                    onclick="applyQuickFilter('experience', 'senior')">
                                Senior Level
                            </button>
                        </div>

                        <!-- Bulk Actions Panel -->
                        <div class="bulk-actions-panel" id="bulkActionsPanel" style="display: none;">
                            <div class="bulk-actions-content">
                                <span class="selected-count">0 applications selected</span>
                                <div class="bulk-actions-buttons">
                                    <select id="bulkStatusSelect">
                                        <option value="">Change Status To...</option>
                                        <option value="reviewed">Reviewed</option>
                                        <option value="interview">Interview</option>
                                        <option value="rejected">Rejected</option>
                                        <option value="hired">Hired</option>
                                    </select>
                                    <button class="btn btn-primary" onclick="applyBulkAction()">Apply</button>
                                    <button class="btn btn-outline" onclick="exportSelected()">Export Selected</button>
                                    <button class="btn btn-outline" onclick="clearSelection()">Clear Selection</button>
                                </div>
                            </div>
                        </div>

                        <!-- Applications Table -->
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>
                                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                        </th>
                                        <th>
                                            <span class="sortable" onclick="sortTable('applicant')">
                                                Applicant <i class="fas fa-sort"></i>
                                            </span>
                                        </th>
                                        <th>
                                            <span class="sortable" onclick="sortTable('position')">
                                                Position <i class="fas fa-sort"></i>
                                            </span>
                                        </th>
                                        <th>
                                            <span class="sortable" onclick="sortTable('applied_date')">
                                                Applied Date <i class="fas fa-sort"></i>
                                            </span>
                                        </th>
                                        <th>Status</th>
                                        <th>Experience</th>
                                        <th>Location Pref</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="applicationsTableBody">
                                    <?php foreach ($applications as $application): ?>
                                        <tr data-application-id="<?php echo $application['id']; ?>">
                                            <td>
                                                <input type="checkbox" class="application-checkbox" 
                                                       value="<?php echo $application['id']; ?>" 
                                                       onchange="updateBulkActions()">
                                            </td>
                                            <td>
                                                <div class="applicant-info">
                                                    <div class="applicant-name">
                                                        <?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?>
                                                    </div>
                                                    <div class="applicant-email">
                                                        <?php echo htmlspecialchars($application['email']); ?>
                                                    </div>
                                                    <div class="applicant-phone">
                                                        <?php echo htmlspecialchars($application['phone']); ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="position-info">
                                                    <div class="job-title"><?php echo htmlspecialchars($application['job_title']); ?></div>
                                                    <div class="job-department"><?php echo htmlspecialchars($application['department']); ?></div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="date-info">
                                                    <div class="applied-date"><?php echo date('M j, Y', strtotime($application['applied_at'])); ?></div>
                                                    <div class="applied-time"><?php echo date('g:i A', strtotime($application['applied_at'])); ?></div>
                                                </div>
                                            </td>
                                            <td>
                                                <select class="status-select" 
                                                        onchange="updateApplicationStatus(<?php echo $application['id']; ?>, this.value)"
                                                        data-original-status="<?php echo $application['status']; ?>">
                                                    <option value="new" <?php echo $application['status'] === 'new' ? 'selected' : ''; ?>>New</option>
                                                    <option value="reviewed" <?php echo $application['status'] === 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                                                    <option value="interview" <?php echo $application['status'] === 'interview' ? 'selected' : ''; ?>>Interview</option>
                                                    <option value="rejected" <?php echo $application['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                                    <option value="hired" <?php echo $application['status'] === 'hired' ? 'selected' : ''; ?>>Hired</option>
                                                </select>
                                            </td>
                                            <td>
                                                <span class="experience-badge">
                                                    <?php 
                                                    // Extract experience from screening answers or estimate
                                                    $experience = $application['react_experience'] ?? 'N/A';
                                                    if (is_numeric($experience)) {
                                                        if ($experience <= 2) echo 'Entry';
                                                        elseif ($experience <= 5) echo 'Mid';
                                                        else echo 'Senior';
                                                    } else {
                                                        echo htmlspecialchars($experience);
                                                    }
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="location-badge">
                                                    <?php echo htmlspecialchars($application['work_arrangement'] ?? 'N/A'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="view-application.php?id=<?php echo $application['id']; ?>" 
                                                       class="btn btn-small btn-outline" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if ($application['resume_filename']): ?>
                                                        <a href="../<?php echo UPLOAD_DIR . $application['resume_filename']; ?>" 
                                                           target="_blank" class="btn btn-small btn-outline" title="Download Resume">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <button onclick="scheduleInterview(<?php echo $application['id']; ?>)" 
                                                            class="btn btn-small btn-outline" title="Schedule Interview">
                                                        <i class="fas fa-calendar"></i>
                                                    </button>
                                                    <button onclick="sendEmail('<?php echo htmlspecialchars($application['email']); ?>', '<?php echo htmlspecialchars($application['job_title']); ?>')" 
                                                            class="btn btn-small btn-outline" title="Send Email">
                                                        <i class="fas fa-envelope"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="pagination-container">
                            <div class="pagination-info">
                                Showing <?php echo count($applications); ?> of <?php echo $stats['total_applications']; ?> applications
                            </div>
                            <div class="pagination-controls">
                                <!-- Add pagination controls here if needed -->
                            </div>
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
    <script>
        // Check URL parameters to show applications tab if needed
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('tab') === 'applications') {
                showTab('applications');
            }
        });
    </script>
</body>
</html>
