<?php
// Script to test database connection and verify admin user
require_once '../config/config.php';

echo "Testing database connection...\n";

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("❌ Database connection failed!\n");
}

echo "✅ Database connection successful!\n\n";

// Check if users table exists
try {
    $query = "DESCRIBE users";
    $stmt = $db->prepare($query);
    $stmt->execute();
    echo "✅ Users table exists\n\n";
} catch (Exception $e) {
    echo "❌ Users table does not exist: " . $e->getMessage() . "\n";
    exit;
}

// Check for admin user
$query = "SELECT * FROM users WHERE username = 'admin' OR email = 'admin@recruitportal.com'";
$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($users)) {
    echo "❌ No admin user found!\n";
    echo "Run the create_admin.php script to create one.\n";
} else {
    echo "✅ Admin user(s) found:\n";
    foreach ($users as $user) {
        echo "  - ID: " . $user['id'] . "\n";
        echo "  - Username: " . $user['username'] . "\n";
        echo "  - Email: " . $user['email'] . "\n";
        echo "  - Role: " . $user['role'] . "\n";
        echo "  - Password hash: " . substr($user['password'], 0, 20) . "...\n";
        
        // Test password verification
        $test_password = 'admin123';
        $verify_result = password_verify($test_password, $user['password']);
        echo "  - Password 'admin123' verification: " . ($verify_result ? "✅ PASS" : "❌ FAIL") . "\n\n";
    }
}

// Test password hashing
echo "Testing password hashing:\n";
$test_password = 'admin123';
$hash = password_hash($test_password, PASSWORD_DEFAULT);
$verify = password_verify($test_password, $hash);
echo "  - Hash: " . $hash . "\n";
echo "  - Verification: " . ($verify ? "✅ PASS" : "❌ FAIL") . "\n";
?>
