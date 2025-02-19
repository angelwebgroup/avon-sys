<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

// Delete existing admin users
$conn->query("DELETE FROM users WHERE role = 'admin'");

// Create new admin user
$username = 'admin';
$password = password_hash('admin123', PASSWORD_DEFAULT);
$email = 'admin@example.com';

$stmt = $conn->prepare("INSERT INTO users (username, password, email, role, status) VALUES (?, ?, ?, 'admin', 'approved')");
$stmt->bind_param("sss", $username, $password, $email);

if ($stmt->execute()) {
    echo "<div style='color: green; font-family: Arial; padding: 20px;'>";
    echo "<h2>Admin Account Reset Successfully</h2>";
    echo "New admin credentials:<br>";
    echo "Username: admin<br>";
    echo "Password: admin123<br>";
    echo "<br>Please try logging in with these credentials at: <a href='index.php'>Login Page</a>";
    echo "</div>";
} else {
    echo "<div style='color: red; font-family: Arial; padding: 20px;'>";
    echo "Error creating admin user: " . $stmt->error;
    echo "</div>";
}
