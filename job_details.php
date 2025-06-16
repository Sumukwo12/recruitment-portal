<?php
require_once 'config/config.php';

$database = new Database();
$db = $database->getConnection();

// Get job ID
$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$job_id) {
    redirectTo('index.php');
}

// Get job details
$query = "SELECT * FROM jobs WHERE id = :job_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':job_id', $job_id);
$stmt->execute();

if ($stmt->rowCount() === 0) {
    redirectTo('index.php');
}

$job = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if job is still available
$is_available = $job['status'] === 'Open' && strtotime($job['deadline']) >= strtotime('today');

// Get screening questions
$query = "SELECT * FROM screening_questions WHERE job_id = :job_id ORDER BY id";
$stmt = $db->prepare($query);
$stmt->bindParam(':job_id', $job_id);
$stmt->execute();
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get application count
$query = "SELECT COUNT(*) as count FROM applications WHERE job_id = :job_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':job_id', $job_id);
$stmt->execute();
$application_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Calculate days remaining
$days_remaining = (strtotime($job['deadline']) - strtotime('today')) / (60 * 60 * 24);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($job['title']); ?> - Recruit Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <meta name="description" content="<?php echo htmlspecialchars(substr($job['description'], 0, 160)); ?>">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <h1 class="logo">Recruit Portal</h1>
                <nav class="nav">
                    <a href="index.php" class="nav-link">← Back to Jobs</a>
                    <a href="auth/login.php" class="nav-link">Admin Login</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="main">
        <div class="container">
            <div class="job-details-container">
                <!-- Job Header -->
                <div class="job-header-section">
                    <div class="job-title-area">
                        <h1 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h1>
                        <div class="job-meta">
                            <span class="company-name"><?php echo htmlspecialchars($job['company']); ?></span>
                            <span class="job-location"><?php echo htmlspecialchars($job['location']); ?></span>
                            <span class="job-type-badge"><?php echo htmlspecialchars($job['job_type']); ?></span>
                        </div>
                        
                        <?php if ($job['salary_min'] || $job['salary_max']): ?>
                            <div class="salary-info">
                                <strong><?php echo formatSalary($job['salary_min'], $job['salary_max']); ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="job-status-area">
                        <div class="status-info">
                            <span class="job-status status-<?php echo strtolower($job['status']); ?>">
                                <?php echo $job['status']; ?>
                            </span>
                            
                            <?php if ($is_available): ?>
                                <div class="deadline-info">
                                    <strong>Deadline:</strong> <?php echo date('M j, Y', strtotime($job['deadline'])); ?>
                                    <?php if ($days_remaining > 0): ?>
                                        <span class="days-remaining">(<?php echo (int)$days_remaining; ?> days left)</span>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="deadline-info expired">
                                    <strong>Deadline:</strong> <?php echo date('M j, Y', strtotime($job['deadline'])); ?>
                                    <span class="expired-text">(Expired)</span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="application-count">
                                <strong><?php echo $application_count; ?></strong> application<?php echo $application_count !== 1 ? 's' : ''; ?> received
                            </div>
                        </div>
                        
                        <div class="apply-section">
                            <?php if ($is_available): ?>
                                <a href="apply.php?job_id=<?php echo $job['id']; ?>" class="btn btn-primary btn-large">
                                    Apply Now
                                </a>
                                <p class="apply-note">Ready to join our team? Click apply to get started!</p>
                            <?php else: ?>
                                <button class="btn btn-disabled btn-large" disabled>
                                    <?php echo $job['status'] === 'Closed' ? 'Position Closed' : 'Application Deadline Passed'; ?>
                                </button>
                                <p class="apply-note">This position is no longer accepting applications.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Job Content -->
                <div class="job-content">
                    <!-- Job Description -->
                    <section class="content-section">
                        <h2>Job Description</h2>
                        <div class="content-text">
                            <?php echo nl2br(htmlspecialchars($job['description'])); ?>
                        </div>
                    </section>

                    <!-- Requirements -->
                    <section class="content-section">
                        <h2>Requirements</h2>
                        <div class="content-text">
                            <?php echo nl2br(htmlspecialchars($job['requirements'])); ?>
                        </div>
                    </section>

                    <!-- Screening Questions Preview -->
                    <?php if (!empty($questions)): ?>
                        <section class="content-section">
                            <h2>Application Questions</h2>
                            <p class="section-intro">You will be asked to answer the following questions during the application process:</p>
                            
                            <div class="questions-preview">
                                <?php foreach ($questions as $index => $question): ?>
                                    <div class="question-preview">
                                        <div class="question-number"><?php echo $index + 1; ?>.</div>
                                        <div class="question-content">
                                            <div class="question-text">
                                                <?php echo htmlspecialchars($question['question']); ?>
                                                <?php if ($question['required']): ?>
                                                    <span class="required-indicator">*</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="question-type">
                                                <?php
                                                switch ($question['question_type']) {
                                                    case 'short_answer':
                                                        echo 'Text answer required';
                                                        break;
                                                    case 'yes_no':
                                                        echo 'Yes/No answer';
                                                        break;
                                                    case 'multiple_choice':
                                                        $options = json_decode($question['options'], true);
                                                        if ($options) {
                                                            echo 'Choose from: ' . implode(', ', $options);
                                                        }
                                                        break;
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <!-- Company Information -->
                    <section class="content-section">
                        <h2>About <?php echo htmlspecialchars($job['company']); ?></h2>
                        <div class="company-info">
                            <div class="company-details">
                                <div class="detail-item">
                                    <strong>Company:</strong> <?php echo htmlspecialchars($job['company']); ?>
                                </div>
                                <div class="detail-item">
                                    <strong>Location:</strong> <?php echo htmlspecialchars($job['location']); ?>
                                </div>
                                <div class="detail-item">
                                    <strong>Job Type:</strong> <?php echo htmlspecialchars($job['job_type']); ?>
                                </div>
                                <div class="detail-item">
                                    <strong>Posted:</strong> <?php echo timeAgo($job['created_at']); ?>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Application Process -->
                    <section class="content-section">
                        <h2>Application Process</h2>
                        <div class="process-steps">
                            <div class="step">
                                <div class="step-number">1</div>
                                <div class="step-content">
                                    <h3>Submit Application</h3>
                                    <p>Fill out the application form with your personal information and upload your resume.</p>
                                </div>
                            </div>
                            <div class="step">
                                <div class="step-number">2</div>
                                <div class="step-content">
                                    <h3>Initial Review</h3>
                                    <p>Our recruitment team will review your application and qualifications.</p>
                                </div>
                            </div>
                            <div class="step">
                                <div class="step-number">3</div>
                                <div class="step-content">
                                    <h3>Interview Process</h3>
                                    <p>Qualified candidates will be contacted for interviews.</p>
                                </div>
                            </div>
                            <div class="step">
                                <div class="step-number">4</div>
                                <div class="step-content">
                                    <h3>Final Decision</h3>
                                    <p>We'll notify all candidates about the final decision.</p>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Sticky Apply Button for Mobile -->
                <div class="sticky-apply-bar">
                    <?php if ($is_available): ?>
                        <a href="apply.php?job_id=<?php echo $job['id']; ?>" class="btn btn-primary btn-full">
                            Apply for this Position
                        </a>
                    <?php else: ?>
                        <button class="btn btn-disabled btn-full" disabled>
                            Application Closed
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 Recruit Portal. All rights reserved.</p>
        </div>
    </footer>

    <style>
        /* Job Details Specific Styles */
        .job-details-container {
            max-width: 900px;
            margin: 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .job-header-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 2rem;
            align-items: start;
        }

        .job-title {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .job-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .company-name {
            font-size: 1.2rem;
            font-weight: 600;
        }

        .job-location {
            opacity: 0.9;
        }

        .job-type-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .salary-info {
            font-size: 1.3rem;
            margin-top: 0.5rem;
        }

        .job-status-area {
            text-align: right;
            min-width: 250px;
        }

        .status-info {
            margin-bottom: 1.5rem;
        }

        .job-status {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .status-open {
            background: #dcfce7;
            color: #166534;
        }

        .status-closed {
            background: #fee2e2;
            color: #dc2626;
        }

        .deadline-info {
            margin-bottom: 0.5rem;
        }

        .days-remaining {
            color: #fbbf24;
            font-weight: 600;
        }

        .expired-text {
            color: #fca5a5;
            font-weight: 600;
        }

        .application-count {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .apply-section {
            text-align: center;
        }

        .btn-large {
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .apply-note {
            font-size: 0.875rem;
            opacity: 0.8;
            margin: 0;
        }

        .job-content {
            padding: 2rem;
        }

        .content-section {
            margin-bottom: 3rem;
        }

        .content-section h2 {
            color: #1e293b;
            font-size: 1.5rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .section-intro {
            color: #64748b;
            margin-bottom: 1.5rem;
            font-style: italic;
        }

        .content-text {
            line-height: 1.8;
            color: #374151;
            font-size: 1rem;
        }

        .questions-preview {
            background: #f8fafc;
            border-radius: 8px;
            padding: 1.5rem;
        }

        .question-preview {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .question-preview:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .question-number {
            background: #3b82f6;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            font-weight: 600;
            flex-shrink: 0;
        }

        .question-text {
            font-weight: 500;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .required-indicator {
            color: #ef4444;
            font-weight: bold;
        }

        .question-type {
            font-size: 0.875rem;
            color: #64748b;
            font-style: italic;
        }

        .company-info {
            background: #f8fafc;
            border-radius: 8px;
            padding: 1.5rem;
        }

        .company-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .detail-item {
            color: #374151;
        }

        .detail-item strong {
            color: #1e293b;
        }

        .process-steps {
            display: grid;
            gap: 1.5rem;
        }

        .step {
            display: flex;
            gap: 1rem;
            align-items: flex-start;
        }

        .step-number {
            background: #3b82f6;
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            flex-shrink: 0;
        }

        .step-content h3 {
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .step-content p {
            color: #64748b;
            margin: 0;
        }

        .sticky-apply-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            padding: 1rem;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
            z-index: 100;
            display: none;
        }

        .btn-full {
            width: 100%;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .job-header-section {
                grid-template-columns: 1fr;
                gap: 1.5rem;
                text-align: center;
            }

            .job-title {
                font-size: 2rem;
            }

            .job-status-area {
                text-align: center;
                min-width: auto;
            }

            .job-content {
                padding: 1.5rem;
            }

            .company-details {
                grid-template-columns: 1fr;
            }

            .sticky-apply-bar {
                display: block;
            }

            .job-details-container {
                margin-bottom: 80px;
            }
        }

        @media (max-width: 480px) {
            .job-title {
                font-size: 1.5rem;
            }

            .job-meta {
                flex-direction: column;
                gap: 0.5rem;
            }

            .job-content {
                padding: 1rem;
            }
        }
    </style>

    <script>
        // Show/hide sticky apply bar on scroll
        window.addEventListener('scroll', function() {
            const stickyBar = document.querySelector('.sticky-apply-bar');
            const applySection = document.querySelector('.apply-section');
            
            if (stickyBar && applySection) {
                const applySectionRect = applySection.getBoundingClientRect();
                const isApplySectionVisible = applySectionRect.top < window.innerHeight && applySectionRect.bottom > 0;
                
                if (window.innerWidth <= 768) {
                    stickyBar.style.display = isApplySectionVisible ? 'none' : 'block';
                }
            }
        });

        // Share functionality
        function shareJob() {
            if (navigator.share) {
                navigator.share({
                    title: '<?php echo addslashes($job['title']); ?>',
                    text: 'Check out this job opportunity at <?php echo addslashes($job['company']); ?>',
                    url: window.location.href
                });
            } else {
                // Fallback: copy to clipboard
                navigator.clipboard.writeText(window.location.href).then(function() {
                    alert('Job link copied to clipboard!');
                });
            }
        }
    </script>
</body>
</html>
