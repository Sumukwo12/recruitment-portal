<?php
require_once 'config/config.php';

if (!isset($_GET['id'])) {
    redirect('index.php');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Submitted - TechCorp Careers</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="success-page">
        <div class="container">
            <div class="success-card">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h1>Application Submitted!</h1>
                <p>Thank you for your interest in joining our team. We'll review your application and get back to you within 5-7 business days.</p>
                <div class="success-actions">
                    <a href="index.php" class="btn btn-primary">View Other Positions</a>
                    <a href="index.php" class="btn btn-outline">Back to Home</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
