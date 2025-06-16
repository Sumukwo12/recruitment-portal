<?php
require_once 'config/config.php';

$database = new Database();
$db = $database->getConnection();

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Build query
$query = "SELECT * FROM jobs WHERE 1=1";
$params = [];

if ($status_filter !== 'all') {
    $query .= " AND status = :status";
    $params[':status'] = $status_filter;
}

if ($search) {
    $query .= " AND (title LIKE :search OR company LIKE :search OR location LIKE :search)";
    $params[':search'] = "%$search%";
}

// Auto-close expired jobs
$update_query = "UPDATE jobs SET status = 'Closed' WHERE deadline < CURDATE() AND status = 'Open'";
$db->prepare($update_query)->execute();

$query .= " ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Geonet technologies Careers</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo-container">
                    <a href="index.php">
                    <img src="assets/images/img3.jpg" alt="Geonet Technologies Logo" class="logo-img">
                    </a>
                    <h1 class="logo">Geonet technologies Careers</h1>
                </div>
                <nav class="nav">
                    <a href="index.php" class="nav-link active">Jobs</a>
                    <a href="auth/login.php" class="nav-link">Admin Login</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="main">
        <div class="container">
            <div class="hero">
                <h2>Find Your Dream Job</h2>
                <p>Discover amazing opportunities with top companies</p>
            </div>

            <div class="filters">
                <div class="filter-group">
                    <input type="text" id="searchInput" placeholder="Search jobs..." value="<?php echo htmlspecialchars($search); ?>">
                    <select id="statusFilter">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Jobs</option>
                        <option value="Open" <?php echo $status_filter === 'Open' ? 'selected' : ''; ?>>Open Positions</option>
                        <option value="Closed" <?php echo $status_filter === 'Closed' ? 'selected' : ''; ?>>Closed Positions</option>
                    </select>
                    <button id="filterBtn" class="btn btn-secondary">Filter</button>
                </div>
            </div>

            <div class="jobs-grid">
                <?php if (empty($jobs)): ?>
                    <div class="no-jobs">
                        <h3>No jobs found</h3>
                        <p>Try adjusting your search criteria</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($jobs as $job): ?>
                        <div class="job-card <?php echo strtolower($job['status']); ?>">
                            <div class="job-header">
                                <h3 class="job-title">
                                    <a href="job_details.php?id=<?php echo $job['id']; ?>">
                                        <?php echo htmlspecialchars($job['title']); ?>
                                    </a>
                                </h3>
                                <span class="job-status status-<?php echo strtolower($job['status']); ?>">
                                    <?php echo $job['status']; ?>
                                </span>
                            </div>
                            
                            <div class="job-company"><?php echo htmlspecialchars($job['company']); ?></div>
                            <div class="job-location"><?php echo htmlspecialchars($job['location']); ?></div>
                            <div class="job-type"><?php echo htmlspecialchars($job['job_type']); ?></div>
                            
                            <?php if ($job['salary_min'] || $job['salary_max']): ?>
                                <div class="job-salary"><?php echo formatSalary($job['salary_min'], $job['salary_max']); ?></div>
                            <?php endif; ?>
                            
                            <div class="job-description">
                                <?php echo substr(htmlspecialchars($job['description']), 0, 150) . '...'; ?>
                            </div>
                            
                            <div class="job-footer">
                                <div class="job-deadline">
                                    Deadline: <?php echo date('M j, Y', strtotime($job['deadline'])); ?>
                                </div>
                                
                                <div class="job-actions">
                                    <a href="job_details.php?id=<?php echo $job['id']; ?>" class="btn btn-secondary btn-sm">
                                        View Details
                                    </a>
                                    
                                    <?php if ($job['status'] === 'Open' && strtotime($job['deadline']) >= strtotime('today')): ?>
                                        <a href="apply.php?job_id=<?php echo $job['id']; ?>" class="btn btn-primary btn-sm">Apply Now</a>
                                    <?php else: ?>
                                        <button class="btn btn-disabled btn-sm" disabled>
                                            <?php echo $job['status'] === 'Closed' ? 'Position Closed' : 'Deadline Passed'; ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                
                <p class="copyright">&copy; 2025 Recruit Portal. All rights reserved.</p>
            </div>
        </div>
    </footer>
    <script src="assets/js/main.js"></script>
</body>
</html>
