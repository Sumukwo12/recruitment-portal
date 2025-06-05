<?php
require_once 'config/config.php';
require_once 'models/Job.php';

if (!isset($_GET['id'])) {
    redirect('index.php');
}

$jobModel = new Job();
$job = $jobModel->getJobById($_GET['id']);

if (!$job) {
    redirect('index.php');
}

$screeningQuestions = $jobModel->getScreeningQuestions($job['id']);
$daysLeft = ceil((strtotime($job['deadline']) - time()) / (60 * 60 * 24));
$isExpired = $daysLeft <= 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($job['title']); ?> - TechCorp Careers</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <i class="fas fa-building"></i>
                    <h1>Geonet Technologies Limited Careers</h1>
                </div>
                <nav class="nav">
                    <a href="index.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> Back to Jobs
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <div class="container job-details-container">
        <div class="job-details-layout">
            <!-- Main Content -->
            <div class="job-main-content">
                <!-- Job Header -->
                <div class="card job-header-card">
                    <div class="job-header-content">
                        <div class="job-title-section">
                            <span class="badge badge-success">Open Position</span>
                            <h1><?php echo htmlspecialchars($job['title']); ?></h1>
                            <p class="job-department"><?php echo htmlspecialchars($job['department']); ?></p>
                        </div>
                        <span class="badge badge-outline job-type"><?php echo ucfirst(str_replace('-', ' ', $job['type'])); ?></span>
                    </div>

                    <div class="job-meta">
                        <div class="job-meta-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?php echo htmlspecialchars($job['location']); ?></span>
                        </div>
                        <div class="job-meta-item">
                            <i class="fas fa-dollar-sign"></i>
                            <span><?php echo formatSalary($job['salary_min'], $job['salary_max']); ?></span>
                        </div>
                        <div class="job-meta-item">
                            <i class="fas fa-clock"></i>
                            <span><?php echo $isExpired ? 'Deadline passed' : $daysLeft . ' days left'; ?></span>
                        </div>
                        <div class="job-meta-item">
                            <i class="fas fa-users"></i>
                            <span><?php echo $job['application_count']; ?> applications</span>
                        </div>
                    </div>
                </div>

                <!-- Job Description -->
                <div class="card">
                    <div class="card-header">
                        <h2>About This Role</h2>
                    </div>
                    <div class="card-content">
                        <div class="job-description">
                            <?php echo nl2br(htmlspecialchars($job['description'])); ?>
                        </div>
                    </div>
                </div>

                <!-- Requirements -->
                <?php if ($job['requirements']): ?>
                <div class="card">
                    <div class="card-header">
                        <h2>Requirements</h2>
                    </div>
                    <div class="card-content">
                        <ul class="requirements-list">
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
                </div>
                <?php endif; ?>

                <!-- Responsibilities -->
                <?php if ($job['responsibilities']): ?>
                <div class="card">
                    <div class="card-header">
                        <h2>Key Responsibilities</h2>
                    </div>
                    <div class="card-content">
                        <ul class="responsibilities-list">
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
                </div>
                <?php endif; ?>

                <!-- Benefits -->
                <?php if ($job['benefits']): ?>
                <div class="card">
                    <div class="card-header">
                        <h2>What We Offer</h2>
                    </div>
                    <div class="card-content">
                        <ul class="benefits-list">
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
                </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="job-sidebar">
                <!-- Apply Card -->
                <div class="card apply-card">
                    <div class="card-header">
                        <h3>Ready to Apply?</h3>
                    </div>
                    <div class="card-content">
                        <?php if (!$isExpired): ?>
                            <p class="deadline-info">
                                Application deadline: <?php echo date('F j, Y', strtotime($job['deadline'])); ?>
                            </p>
                            <a href="apply.php?id=<?php echo $job['id']; ?>" class="btn btn-primary btn-large">
                                Apply for This Position
                            </a>
                            <p class="apply-note">Application takes about 10-15 minutes to complete</p>
                        <?php else: ?>
                            <div class="deadline-passed">
                                <p>This position is no longer accepting applications.</p>
                                <a href="index.php" class="btn btn-outline btn-large">View Other Positions</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Company Info -->
                <div class="card">
                    <div class="card-header">
                        <h3>About Geonet Technologies Limited</h3>
                    </div>
                    <div class="card-content">
                        <p>We're a fast-growing technology company focused on building innovative solutions that make a difference. Join our team of passionate professionals who are committed to excellence and continuous learning.</p>
                        <div class="company-stats">
                            <div class="company-stat">
                                <span class="stat-label">Company Size:</span>
                                <span class="stat-value">200-500 employees</span>
                            </div>
                            <div class="company-stat">
                                <span class="stat-label">Industry:</span>
                                <span class="stat-value">Technology</span>
                            </div>
                            <div class="company-stat">
                                <span class="stat-label">Founded:</span>
                                <span class="stat-value">2013</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Share -->
                <div class="card">
                    <div class="card-header">
                        <h3>Share This Job</h3>
                    </div>
                    <div class="card-content">
                        <div class="share-buttons">
                            <button class="btn btn-outline btn-small">LinkedIn</button>
                            <button class="btn btn-outline btn-small">Twitter</button>
                            <button class="btn btn-outline btn-small" onclick="copyToClipboard()">Copy Link</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        function copyToClipboard() {
            navigator.clipboard.writeText(window.location.href);
            alert('Job link copied to clipboard!');
        }
    </script>
</body>
</html>
