<?php
require_once 'config/config.php';
require_once 'models/Job.php';

$jobModel = new Job();
$jobs = $jobModel->getActiveJobs();
$stats = $jobModel->getDashboardStats();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Geonet Technologies Limited Careers - Join Our Amazing Team</title>
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
                    <a href="admin/login.php" class="btn btn-outline"></a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h2>Join Our Amazing Team</h2>
                <p>Discover exciting career opportunities and be part of something extraordinary</p>
                <div class="stats">
                    <div class="stat">
                        <div class="stat-number"><?php echo $stats['active_jobs']; ?></div>
                        <div class="stat-label">Open Positions</div>
                    </div>
                    <div class="stat">
                        <div class="stat-number"><?php echo $stats['total_applications']; ?></div>
                        <div class="stat-label">Applications</div>
                    </div>
                    <div class="stat">
                        <div class="stat-number">7</div>
                        <div class="stat-label">Departments</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Search and Filter Section -->
    <section class="search-section">
        <div class="container">
            <div class="search-filters">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search jobs by title, department, or location...">
                </div>
                <select id="departmentFilter" class="filter-select">
                    <option value="">All Departments</option>
                    <option value="NOC">NOC</option>
                    <option value="Finance">Finance</option>
                    <option value="Planning">Planning</option>
                    <option value="Sales">Sales</option>
                </select>
                <select id="locationFilter" class="filter-select">
                    <option value="">All Locations</option>
                    <option value="">/option>
                    <option value=" "></option>
                    <option value=" "></option>
                    <option value=""></option>
                </select>
            </div>
        </div>
    </section>

    <!-- Jobs Section -->
    <section class="jobs-section">
        <div class="container">
            <div class="section-header">
                <h3>Open Positions</h3>
                <div class="job-count"><?php echo count($jobs); ?> positions available</div>
            </div>

            <div class="jobs-grid" id="jobsGrid">
                <?php if (empty($jobs)): ?>
                    <div class="no-jobs">
                        <i class="fas fa-users"></i>
                        <h3>No Vacancies Available</h3>
                        <p>We don't have any open positions at the moment. Please check back later!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($jobs as $job): ?>
                        <div class="job-card" data-department="<?php echo $job['department']; ?>" data-location="<?php echo $job['location']; ?>">
                            <div class="job-header">
                                <div class="job-badges">
                                    <span class="badge badge-success">Open</span>
                                    <span class="badge badge-outline"><?php echo ucfirst(str_replace('-', ' ', $job['type'])); ?></span>
                                </div>
                                <h4><?php echo htmlspecialchars($job['title']); ?></h4>
                                <p class="job-department"><?php echo htmlspecialchars($job['department']); ?></p>
                            </div>

                            <div class="job-details">
                                <div class="job-detail">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo htmlspecialchars($job['location']); ?></span>
                                </div>
                                <div class="job-detail">
                                    <i class="fas fa-dollar-sign"></i>
                                    <span><?php echo formatSalary($job['salary_min'], $job['salary_max']); ?></span>
                                </div>
                                <div class="job-detail">
                                    <i class="fas fa-clock"></i>
                                    <span><?php 
                                        $days = (strtotime($job['deadline']) - time()) / (60 * 60 * 24);
                                        echo ceil($days) . ' days left';
                                    ?></span>
                                </div>
                                <div class="job-detail">
                                    <i class="fas fa-users"></i>
                                    <span><?php echo $job['application_count']; ?> applications</span>
                                </div>
                            </div>

                            <div class="job-description">
                                <p><?php echo substr(htmlspecialchars($job['description']), 0, 150) . '...'; ?></p>
                            </div>

                            <div class="job-actions">
                                <a href="job-details.php?id=<?php echo $job['id']; ?>" class="btn btn-outline">View Details</a>
                                <a href="apply.php?id=<?php echo $job['id']; ?>" class="btn btn-primary">Apply Now</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="footer-logo">
                        <i class="fas fa-building"></i>
                        <span>Geonet Technologies Limited Careers</span>
                    </div>
                    <p>Building the future of technology, one hire at a time.</p>
                </div>
                <div class="footer-section">
                    <h4>Company</h4>
                    <ul>
                        <li><a href="https://geonet-tech.co.ke/about-us/">About Us</a></li>
                        
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="#">Help Center</a></li>
                        <li><a href="#">Contact</a></li>
                        <li><a href="#">Privacy</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Connect</h4>
                    <ul>
                        <li><a href="#">LinkedIn</a></li>
                        <li><a href="#">Twitter</a></li>
                        <li><a href="#"></a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 Geonet Technologies Limited Careers. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>
