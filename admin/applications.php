<?php
require_once '../config/config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirectTo('../auth/login.php');
}

$database = new Database();
$db = $database->getConnection();

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $application_id = (int)$_POST['application_id'];
        $new_status = $_POST['new_status'];
        
        $query = "UPDATE applications SET status = :status WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([':status' => $new_status, ':id' => $application_id]);
        
        $success = "Application status updated successfully";
    }
}

// Get filter parameters
$job_filter = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$experience_filter = isset($_GET['experience']) ? $_GET['experience'] : '';
$education_filter = isset($_GET['education']) ? $_GET['education'] : '';
$location_filter = isset($_GET['location']) ? sanitizeInput($_GET['location']) : '';

// Build base query
$query = "SELECT DISTINCT a.*, j.title as job_title, j.company 
          FROM applications a 
          JOIN jobs j ON a.job_id = j.id";

// Add join for filtering by answers
if ($experience_filter || $education_filter || $location_filter) {
    $query .= " LEFT JOIN application_answers aa ON a.id = aa.application_id
                LEFT JOIN screening_questions sq ON aa.question_id = sq.id";
}

$query .= " WHERE 1=1";
$params = [];

if ($job_filter) {
    $query .= " AND a.job_id = :job_id";
    $params[':job_id'] = $job_filter;
}

if ($status_filter !== 'all') {
    $query .= " AND a.status = :status";
    $params[':status'] = $status_filter;
}

if ($search) {
    $query .= " AND (a.first_name LIKE :search OR a.last_name LIKE :search OR a.email LIKE :search OR a.phone LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($date_from) {
    $query .= " AND DATE(a.applied_at) >= :date_from";
    $params[':date_from'] = $date_from;
}

if ($date_to) {
    $query .= " AND DATE(a.applied_at) <= :date_to";
    $params[':date_to'] = $date_to;
}

// Experience filter logic
if ($experience_filter) {
    $query .= " AND sq.is_filterable = 1 AND sq.filter_type = 'experience'";
    
    if ($experience_filter === 'entry') {
        $query .= " AND (aa.answer LIKE '%Entry Level%' OR aa.answer LIKE '%0 year%' OR aa.answer LIKE '%1 year%' OR aa.answer LIKE '%2 year%' OR aa.answer LIKE '%less than 3%')";
    } elseif ($experience_filter === 'mid') {
        $query .= " AND (aa.answer LIKE '%Mid Level%' OR aa.answer LIKE '%3 year%' OR aa.answer LIKE '%4 year%' OR aa.answer LIKE '%5 year%')";
    } elseif ($experience_filter === 'senior') {
        $query .= " AND (aa.answer LIKE '%Senior Level%' OR aa.answer LIKE '%more than 5%' OR aa.answer LIKE '%5+ year%' OR aa.answer LIKE '%6 year%' OR aa.answer LIKE '%7 year%' OR aa.answer LIKE '%8 year%' OR aa.answer LIKE '%9 year%' OR aa.answer LIKE '%10 year%' OR aa.answer REGEXP '[0-9]{2,} year')";
    }
}

// Education filter logic
if ($education_filter) {
    $query .= " AND sq.is_filterable = 1 AND sq.filter_type = 'education'";
    
    switch ($education_filter) {
        case 'high_school':
            $query .= " AND aa.answer LIKE '%High School%'";
            break;
        case 'certificate':
            $query .= " AND aa.answer LIKE '%Certificate%'";
            break;
        case 'diploma':
            $query .= " AND aa.answer LIKE '%Diploma%'";
            break;
        case 'associate':
            $query .= " AND aa.answer LIKE '%Associate%'";
            break;
        case 'bachelor':
            $query .= " AND aa.answer LIKE '%Bachelor%'";
            break;
        case 'master':
            $query .= " AND aa.answer LIKE '%Master%'";
            break;
        case 'doctorate':
            $query .= " AND (aa.answer LIKE '%PhD%' OR aa.answer LIKE '%Doctorate%')";
            break;
    }
}

// Location filter logic
if ($location_filter) {
    $query .= " AND sq.is_filterable = 1 AND sq.filter_type = 'location' AND aa.answer LIKE :location";
    $params[':location'] = "%$location_filter%";
}

$query .= " ORDER BY a.applied_at DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all jobs for filter dropdown
$query = "SELECT id, title, company FROM jobs ORDER BY title";
$stmt = $db->prepare($query);
$stmt->execute();
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get available locations for filter dropdown
$query = "SELECT DISTINCT aa.answer 
          FROM application_answers aa 
          JOIN screening_questions sq ON aa.question_id = sq.id 
          WHERE sq.is_filterable = 1 AND sq.filter_type = 'location'
          LIMIT 20";
$stmt = $db->prepare($query);
$stmt->execute();
$locations = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applications - Recruit Portal</title>
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
                <h1>Applications Management</h1>
                <div class="header-actions">
                    <?php if ($job_filter): ?>
                        <?php
                        // Check if there are shortlisted candidates for this job
                        $query = "SELECT COUNT(*) as count FROM applications WHERE job_id = :job_id AND status = 'Shortlisted'";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':job_id', $job_filter);
                        $stmt->execute();
                        $shortlisted_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                        ?>
                        <?php if ($shortlisted_count > 0): ?>
                            <a href="email_shortlisted.php?job_id=<?php echo $job_filter; ?>" class="btn btn-success">
                                Email Shortlisted (<?php echo $shortlisted_count; ?>)
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                    <button onclick="exportApplications()" class="btn btn-secondary">Export CSV</button>
                </div>
            </header>

            <div class="dashboard-content">
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <!-- Filters -->
                <div class="section">
                    <div class="section-header">
                        <h2>Filters</h2>
                    </div>
                    
                    <form method="GET" class="filters-form">
                        <div class="filter-row">
                            <div class="form-group">
                                <label for="job_id">Job Position</label>
                                <select id="job_id" name="job_id">
                                    <option value="">All Jobs</option>
                                    <?php foreach ($jobs as $job): ?>
                                        <option value="<?php echo $job['id']; ?>" <?php echo $job_filter == $job['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($job['title'] . ' - ' . $job['company']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status" name="status">
                                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                                    <option value="Pending" <?php echo $status_filter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="Reviewed" <?php echo $status_filter === 'Reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                                    <option value="Shortlisted" <?php echo $status_filter === 'Shortlisted' ? 'selected' : ''; ?>>Shortlisted</option>
                                    <option value="Rejected" <?php echo $status_filter === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="search">Search</label>
                                <input type="text" id="search" name="search" placeholder="Name, email, or phone" 
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        
                        <div class="filter-row">
                            <div class="form-group">
                                <label for="experience">Experience Level</label>
                                <select id="experience" name="experience">
                                    <option value="">All Levels</option>
                                    <option value="entry" <?php echo $experience_filter === 'entry' ? 'selected' : ''; ?>>Entry Level (0-2 years)</option>
                                    <option value="mid" <?php echo $experience_filter === 'mid' ? 'selected' : ''; ?>>Mid Level (3-5 years)</option>
                                    <option value="senior" <?php echo $experience_filter === 'senior' ? 'selected' : ''; ?>>Senior Level (5+ years)</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="education">Education Level</label>
                                <select id="education" name="education">
                                    <option value="">All Education Levels</option>
                                    <option value="high_school" <?php echo $education_filter === 'high_school' ? 'selected' : ''; ?>>High School</option>
                                    <option value="certificate" <?php echo $education_filter === 'certificate' ? 'selected' : ''; ?>>Certificate</option>
                                    <option value="diploma" <?php echo $education_filter === 'diploma' ? 'selected' : ''; ?>>Diploma</option>
                                    <option value="bachelor" <?php echo $education_filter === 'bachelor' ? 'selected' : ''; ?>>Bachelor's Degree</option>
                                    <option value="master" <?php echo $education_filter === 'master' ? 'selected' : ''; ?>>Master's Degree</option>
                                    <option value="doctorate" <?php echo $education_filter === 'doctorate' ? 'selected' : ''; ?>>PhD/Doctorate</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="location">Location Preference</label>
                                <input type="text" id="location" name="location" placeholder="e.g. Remote, Hybrid" 
                                       value="<?php echo htmlspecialchars($location_filter); ?>" list="locations-list">
                                <datalist id="locations-list">
                                    <?php foreach ($locations as $location): ?>
                                        <option value="<?php echo htmlspecialchars($location); ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                        </div>
                        
                        <div class="filter-row">
                            <div class="form-group">
                                <label for="date_from">Date From</label>
                                <input type="date" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="date_to">Date To</label>
                                <input type="date" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div class="filter-buttons">
                                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                                    <a href="applications.php" class="btn btn-secondary">Clear</a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Applications Table -->
                <div class="section">
                    <div class="section-header">
                        <h2>Applications (<?php echo count($applications); ?>)</h2>
                    </div>
                    
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Applicant</th>
                                    <th>Job Position</th>
                                    <th>Company</th>
                                    <th>Applied Date</th>
                                    <th>Status</th>
                                    <th>Resume</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($applications)): ?>
                                    <tr>
                                        <td colspan="7" class="no-data">No applications found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($applications as $app): ?>
                                        <tr>
                                            <td>
                                                <div class="applicant-info">
                                                    <strong><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></strong>
                                                    <br>
                                                    <small><?php echo htmlspecialchars($app['email']); ?></small>
                                                    <br>
                                                    <small><?php echo htmlspecialchars($app['phone']); ?></small>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($app['job_title']); ?></td>
                                            <td><?php echo htmlspecialchars($app['company']); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($app['applied_at'])); ?></td>
                                            <td>
                                                <span class="status status-<?php echo strtolower($app['status']); ?>">
                                                    <?php echo $app['status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($app['resume_path']): ?>
                                                    <a href="../<?php echo $app['resume_path']; ?>" target="_blank" class="btn btn-sm">View Resume</a>
                                                <?php else: ?>
                                                    <span class="text-muted">No resume</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="view_application.php?id=<?php echo $app['id']; ?>" class="btn btn-sm btn-primary">View Details</a>
                                                    
                                                    <select onchange="updateStatus(<?php echo $app['id']; ?>, this.value)" class="status-select">
                                                        <option value="">Change Status</option>
                                                        <option value="Pending" <?php echo $app['status'] === 'Pending' ? 'disabled' : ''; ?>>Pending</option>
                                                        <option value="Reviewed" <?php echo $app['status'] === 'Reviewed' ? 'disabled' : ''; ?>>Reviewed</option>
                                                        <option value="Shortlisted" <?php echo $app['status'] === 'Shortlisted' ? 'disabled' : ''; ?>>Shortlisted</option>
                                                        <option value="Rejected" <?php echo $app['status'] === 'Rejected' ? 'disabled' : ''; ?>>Rejected</option>
                                                    </select>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Hidden form for status updates -->
    <form id="statusForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="application_id" id="statusApplicationId">
        <input type="hidden" name="new_status" id="statusNewStatus">
    </form>

    <style>
        .filters-form {
            padding: 1.5rem;
        }
        
        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .filter-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .applicant-info {
            min-width: 200px;
        }
        
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            min-width: 150px;
        }
        
        .status-select {
            padding: 0.25rem;
            font-size: 0.875rem;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
        }
        
        .header-actions {
            display: flex;
            gap: 1rem;
        }
    </style>

    <script>
        function updateStatus(applicationId, newStatus) {
            if (newStatus && confirm('Are you sure you want to change the status?')) {
                document.getElementById('statusApplicationId').value = applicationId;
                document.getElementById('statusNewStatus').value = newStatus;
                document.getElementById('statusForm').submit();
            }
        }
        
        function exportApplications() {
            const params = new URLSearchParams(window.location.search);
            params.append('export', 'csv');
            window.location.href = 'export_applications.php?' + params.toString();
        }
    </script>
</body>
</html>
