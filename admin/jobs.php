<?php
require_once '../config/config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirectTo('../auth/login.php');
}

$database = new Database();
$db = $database->getConnection();

// Handle job actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'delete':
                $job_id = (int)$_POST['job_id'];
                $query = "DELETE FROM jobs WHERE id = :job_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':job_id', $job_id);
                if ($stmt->execute()) {
                    $success = "Job deleted successfully";
                } else {
                    $error = "Failed to delete job";
                }
                break;
                
            case 'toggle_status':
                $job_id = (int)$_POST['job_id'];
                $new_status = $_POST['new_status'];
                $query = "UPDATE jobs SET status = :status WHERE id = :job_id";
                $stmt = $db->prepare($query);
                $stmt->execute([':status' => $new_status, ':job_id' => $job_id]);
                $success = "Job status updated successfully";
                break;
                
            case 'extend_deadline':
                $job_id = (int)$_POST['job_id'];
                $new_deadline = $_POST['new_deadline'];
                $query = "UPDATE jobs SET deadline = :deadline, status = 'Open' WHERE id = :job_id";
                $stmt = $db->prepare($query);
                $stmt->execute([':deadline' => $new_deadline, ':job_id' => $job_id]);
                $success = "Deadline extended successfully";
                break;
        }
    }
}

// Handle success messages from redirects
$success = '';
if (isset($_GET['success'])) {
    $success = sanitizeInput($_GET['success']);
}

// Get all jobs with application counts
$query = "SELECT j.*, COUNT(a.id) as application_count 
          FROM jobs j 
          LEFT JOIN applications a ON j.id = a.job_id 
          GROUP BY j.id 
          ORDER BY j.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Jobs - Recruit Portal</title>
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
                <a href="jobs.php" class="nav-item active">Manage Jobs</a>
                <a href="applications.php" class="nav-item">Applications</a>
                <a href="../auth/logout.php" class="nav-item">Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="content-header">
                <h1>Manage Jobs</h1>
                <a href="create_job.php" class="btn btn-primary">Create New Job</a>
            </header>

            <div class="dashboard-content">
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="section">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Job Title</th>
                                    <th>Company</th>
                                    <th>Location</th>
                                    <th>Type</th>
                                    <th>Deadline</th>
                                    <th>Status</th>
                                    <th>Applications</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($jobs)): ?>
                                    <tr>
                                        <td colspan="8" class="no-data">No jobs found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($jobs as $job): ?>
                                        <?php
                                        $is_expired = strtotime($job['deadline']) < strtotime('today');
                                        $days_left = (strtotime($job['deadline']) - strtotime('today')) / (60 * 60 * 24);
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($job['title']); ?></strong>
                                                <?php if ($is_expired): ?>
                                                    <span class="status status-closed">Expired</span>
                                                <?php elseif ($days_left <= 3): ?>
                                                    <span class="days-left urgent"><?php echo (int)$days_left; ?> days left</span>
                                                <?php elseif ($days_left <= 7): ?>
                                                    <span class="days-left warning"><?php echo (int)$days_left; ?> days left</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($job['company']); ?></td>
                                            <td><?php echo htmlspecialchars($job['location']); ?></td>
                                            <td><?php echo htmlspecialchars($job['job_type']); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($job['deadline'])); ?></td>
                                            <td>
                                                <span class="status status-<?php echo strtolower($job['status']); ?>">
                                                    <?php echo $job['status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="applications.php?job_id=<?php echo $job['id']; ?>" class="btn btn-sm">
                                                    <?php echo $job['application_count']; ?> applications
                                                </a>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="edit_job.php?id=<?php echo $job['id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                                                    
                                                    <?php if ($job['status'] === 'Open'): ?>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="action" value="toggle_status">
                                                            <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                                            <input type="hidden" name="new_status" value="Closed">
                                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Close this job?')">Close</button>
                                                        </form>
                                                    <?php else: ?>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="action" value="toggle_status">
                                                            <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                                            <input type="hidden" name="new_status" value="Open">
                                                            <button type="submit" class="btn btn-sm btn-success">Reopen</button>
                                                        </form>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($is_expired || $days_left <= 7): ?>
                                                        <button class="btn btn-sm btn-primary" onclick="showExtendModal(<?php echo $job['id']; ?>, '<?php echo $job['title']; ?>')">Extend</button>
                                                    <?php endif; ?>

                                                    <?php
                                                    // Check if there are shortlisted candidates
                                                    $query_shortlisted = "SELECT COUNT(*) as count FROM applications WHERE job_id = :job_id AND status = 'Shortlisted'";
                                                    $stmt_shortlisted = $db->prepare($query_shortlisted);
                                                    $stmt_shortlisted->bindParam(':job_id', $job['id']);
                                                    $stmt_shortlisted->execute();
                                                    $shortlisted_count = $stmt_shortlisted->fetch(PDO::FETCH_ASSOC)['count'];
                                                    ?>

                                                    <?php if ($shortlisted_count > 0): ?>
                                                        <a href="email_shortlisted.php?job_id=<?php echo $job['id']; ?>" class="btn btn-sm btn-success" title="Email <?php echo $shortlisted_count; ?> shortlisted candidates">
                                                            Email (<?php echo $shortlisted_count; ?>)
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this job and all applications?')">Delete</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Extend Deadline Modal -->
    <div id="extendModal" class="modal" style="display: none;">
        <div class="modal-content">
            <h3>Extend Deadline</h3>
            <form method="POST" id="extendForm">
                <input type="hidden" name="action" value="extend_deadline">
                <input type="hidden" name="job_id" id="extendJobId">
                
                <div class="form-group">
                    <label for="new_deadline">New Deadline:</label>
                    <input type="date" name="new_deadline" id="new_deadline" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Extend Deadline</button>
                    <button type="button" class="btn btn-secondary" onclick="closeExtendModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        
        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            max-width: 400px;
            width: 90%;
        }
        
        .modal-content h3 {
            margin-bottom: 1rem;
            color: #1e293b;
        }
    </style>

    <script>
        function showExtendModal(jobId, jobTitle) {
            document.getElementById('extendJobId').value = jobId;
            document.getElementById('extendModal').style.display = 'flex';
        }
        
        function closeExtendModal() {
            document.getElementById('extendModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        document.getElementById('extendModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeExtendModal();
            }
        });
    </script>
</body>
</html>
