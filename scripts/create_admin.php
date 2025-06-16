<?php
// Script to create admin user with proper password hash
require_once '../config/config.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("Database connection failed");
}

// Delete existing admin user
$query = "DELETE FROM users WHERE username = 'admin' OR email = 'admin@recruitportal.com'";
$stmt = $db->prepare($query);
$stmt->execute();

// Create new admin user with properly hashed password
$username = 'admin';
$email = 'admin@recruitportal.com';
$password = 'admin123';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$query = "INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, 'admin')";
$stmt = $db->prepare($query);

$result = $stmt->execute([
    ':username' => $username,
    ':email' => $email,
    ':password' => $hashed_password
]);

if ($result) {
    echo "Admin user created successfully!\n";
    echo "Username: admin\n";
    echo "Email: admin@recruitportal.com\n";
    echo "Password: admin123\n";
    echo "Password hash: " . $hashed_password . "\n";
} else {
    echo "Failed to create admin user\n";
    print_r($stmt->errorInfo());
}
?>
