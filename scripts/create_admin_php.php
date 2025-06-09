<?php
// PHP script to create admin user programmatically
// Run this script once to create an admin user

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Admin user details
    $email = 'admin@techcorp.com';
    $password = 'admin123';
    $name = 'System Administrator';
    
    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Check if admin user already exists
    $checkQuery = "SELECT id FROM admin_users WHERE email = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->execute([$email]);
    
    if ($checkStmt->rowCount() > 0) {
        // Update existing user
        $updateQuery = "UPDATE admin_users SET password = ?, name = ? WHERE email = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $result = $updateStmt->execute([$hashedPassword, $name, $email]);
        
        if ($result) {
            echo "✅ Admin user updated successfully!\n";
        } else {
            echo "❌ Failed to update admin user.\n";
        }
    } else {
        // Create new admin user
        $insertQuery = "INSERT INTO admin_users (email, password, name, created_at) VALUES (?, ?, ?, NOW())";
        $insertStmt = $conn->prepare($insertQuery);
        $result = $insertStmt->execute([$email, $hashedPassword, $name]);
        
        if ($result) {
            echo "✅ Admin user created successfully!\n";
        } else {
            echo "❌ Failed to create admin user.\n";
        }
    }
    
    // Display login credentials
    echo "\n📋 Login Credentials:\n";
    echo "Email: " . $email . "\n";
    echo "Password: " . $password . "\n";
    echo "Admin URL: " . (isset($_SERVER['HTTP_HOST']) ? 'http://' . $_SERVER['HTTP_HOST'] : 'http://localhost') . "/admin/login.php\n";
    
    // Verify the user was created/updated
    $verifyQuery = "SELECT id, email, name, created_at FROM admin_users WHERE email = ?";
    $verifyStmt = $conn->prepare($verifyQuery);
    $verifyStmt->execute([$email]);
    $user = $verifyStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "\n✅ Verification successful:\n";
        echo "User ID: " . $user['id'] . "\n";
        echo "Email: " . $user['email'] . "\n";
        echo "Name: " . $user['name'] . "\n";
        echo "Created: " . $user['created_at'] . "\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
