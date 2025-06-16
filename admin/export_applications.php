<?php
require_once '../config/config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirectTo('../auth/login.php');
}

$database = new Database();
$db = $database->getConnection();

// Get filter parameters (same as applications.php)
$job_filter = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query
$query = "SELECT a.*, j.title as job_title, j.company 
          FROM applications a 
          JOIN jobs j ON a.job_id = j.id 
          WHERE 1=1";
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

$query .= " ORDER BY a.applied_at DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="applications_' . date('Y-m-d') . '.csv"');

// Create file pointer
$output = fopen('php://output', 'w');

// Add CSV headers
fputcsv($output, [
    'Application ID',
    'First Name',
    'Last Name',
    'Email',
    'Phone',
    'Address',
    'Job Title',
    'Company',
    'Status',
    'Applied Date',
    'Portfolio URL',
    'LinkedIn URL',
    'Has Resume'
]);

// Add data rows
foreach ($applications as $app) {
    fputcsv($output, [
        $app['id'],
        $app['first_name'],
        $app['last_name'],
        $app['email'],
        $app['phone'],
        $app['address'],
        $app['job_title'],
        $app['company'],
        $app['status'],
        date('Y-m-d H:i:s', strtotime($app['applied_at'])),
        $app['portfolio_url'],
        $app['linkedin_url'],
        $app['resume_path'] ? 'Yes' : 'No'
    ]);
}

fclose($output);
exit();
?>
