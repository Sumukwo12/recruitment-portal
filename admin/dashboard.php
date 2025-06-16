<?php
require_once '../config/config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirectTo('../auth/login.php');
}

$database = new Database();
$db = $database->getConnection();

// Get statistics
$stats = [];

// Total jobs
$query = "SELECT COUNT(*) as total FROM jobs";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_jobs'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Open jobs
$query = "SELECT COUNT(*) as total FROM jobs WHERE status = 'Open'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['open_jobs'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total applications
$query = "SELECT COUNT(*) as total FROM applications";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_applications'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Pending applications
$query = "SELECT COUNT(*) as total FROM applications WHERE status = 'Pending'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['pending_applications'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Expiring jobs (within 7 days)
$query = "SELECT COUNT(*) as total FROM jobs WHERE status = 'Open' AND deadline BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['expiring_jobs'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Recent applications
$query = "SELECT a.*, j.title as job_title, j.company 
          FROM applications a 
          JOIN jobs j ON a.job_id = j.id 
          ORDER BY a.applied_at DESC 
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Jobs expiring soon
$query = "SELECT * FROM jobs 
          WHERE status = 'Open' AND deadline BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
          ORDER BY deadline ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$expiring_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Geonet Technologies portal</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-layout">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Geonet Technologies</h2>
                <p>Admin Panel</p>
            </div>
            
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item active">Dashboard</a>
                <a href="jobs.php" class="nav-item">Manage Jobs</a>
                <a href="applications.php" class="nav-item">Applications</a>
                <a href="../auth/logout.php" class="nav-item">Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="content-header">
                <h1>Dashboard</h1>
                <div class="user-info">
                    Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>
                </div>
            </header>

            <div class="dashboard-content">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">📋</div>
                        <div class="stat-info">
                            <h3><?php echo $stats['total_jobs']; ?></h3>
                            <p>Total Jobs</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">🟢</div>
                        <div class="stat-info">
                            <h3><?php echo $stats['open_jobs']; ?></h3>
                            <p>Open Positions</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">📄</div>
                        <div class="stat-info">
                            <h3><?php echo $stats['total_applications']; ?></h3>
                            <p>Total Applications</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">⏳</div>
                        <div class="stat-info">
                            <h3><?php echo $stats['pending_applications']; ?></h3>
                            <p>Pending Review</p>
                        </div>
                    </div>
                    
                    <div class="stat-card warning">
                        <div class="stat-icon">⚠️</div>
                        <div class="stat-info">
                            <h3><?php echo $stats['expiring_jobs']; ?></h3>
                            <p>Expiring Soon</p>
                        </div>
                    </div>
                </div>

                <div class="dashboard-sections">
                    <div class="section">
                        <div class="section-header">
                            <h2>Recent Applications</h2>
                            <a href="applications.php" class="btn btn-secondary">View All</a>
                        </div>
                        
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Applicant</th>
                                        <th>Job</th>
                                        <th>Company</th>
                                        <th>Applied</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recent_applications)): ?>
                                        <tr>
                                            <td colspan="6" class="no-data">No applications yet</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recent_applications as $app): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($app['job_title']); ?></td>
                                                <td><?php echo htmlspecialchars($app['company']); ?></td>
                                                <td><?php echo timeAgo($app['applied_at']); ?></td>
                                                <td>
                                                    <span class="status status-<?php echo strtolower($app['status']); ?>">
                                                        <?php echo $app['status']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="view_application.php?id=<?php echo $app['id']; ?>" class="btn btn-sm">View</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <?php if (!empty($expiring_jobs)): ?>
                        <div class="section">
                            <div class="section-header">
                                <h2>Jobs Expiring Soon</h2>
                                <a href="jobs.php" class="btn btn-secondary">Manage Jobs</a>
                            </div>
                            
                            <div class="table-container">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Job Title</th>
                                            <th>Company</th>
                                            <th>Deadline</th>
                                            <th>Days Left</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($expiring_jobs as $job): ?>
                                            <?php
                                            $days_left = (strtotime($job['deadline']) - strtotime('today')) / (60 * 60 * 24);
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($job['title']); ?></td>
                                                <td><?php echo htmlspecialchars($job['company']); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($job['deadline'])); ?></td>
                                                <td>
                                                    <span class="days-left <?php echo $days_left <= 3 ? 'urgent' : 'warning'; ?>">
                                                        <?php echo (int)$days_left; ?> days
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="edit_job.php?id=<?php echo $job['id']; ?>" class="btn btn-sm">Extend</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
