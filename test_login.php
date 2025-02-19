<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<h2>Database Connection Test</h2>";
if ($conn->ping()) {
    echo "<div style='color: green;'>Database connection successful!</div>";
} else {
    echo "<div style='color: red;'>Database connection failed: " . $conn->error . "</div>";
}

echo "<h2>Users Table Test</h2>";
$result = $conn->query("SELECT id, username, email, role, status, LEFT(password, 20) as password_preview FROM users");
if ($result) {
    echo "<pre>";
    while ($row = $result->fetch_assoc()) {
        print_r($row);
    }
    echo "</pre>";
} else {
    echo "<div style='color: red;'>Error querying users table: " . $conn->error . "</div>";
}

echo "<h2>Password Hash Test</h2>";
$test_password = 'password';
$hash = password_hash($test_password, PASSWORD_DEFAULT);
echo "Test password: " . $test_password . "<br>";
echo "Generated hash: " . $hash . "<br>";
echo "Hash verification test: " . (password_verify($test_password, $hash) ? 'SUCCESS' : 'FAILED') . "<br>";

if (isset($_POST['test_login'])) {
    echo "<h2>Login Test Results</h2>";
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    echo "Testing login for username: " . htmlspecialchars($username) . "<br>";
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo "<div style='color: red;'>User not found</div>";
    } else {
        $user = $result->fetch_assoc();
        echo "User found:<br>";
        echo "Status: " . $user['status'] . "<br>";
        echo "Role: " . $user['role'] . "<br>";
        echo "Password verification: " . (password_verify($password, $user['password']) ? 'SUCCESS' : 'FAILED') . "<br>";
        
        if (password_verify($password, $user['password'])) {
            echo "<div style='color: green;'>Login would be successful!</div>";
        } else {
            echo "<div style='color: red;'>Password verification failed</div>";
            echo "Stored hash: " . $user['password'] . "<br>";
            echo "Test hash for 'password': " . password_hash('password', PASSWORD_DEFAULT) . "<br>";
        }
    }
}

// Create new admin user with test password
if (isset($_POST['create_test_user'])) {
    $username = 'test_admin';
    $password = password_hash('test123', PASSWORD_DEFAULT);
    $email = 'test_admin@example.com';
    
    $stmt = $conn->prepare("INSERT INTO users (username, password, email, role, status) VALUES (?, ?, ?, 'admin', 'approved')");
    $stmt->bind_param("sss", $username, $password, $email);
    
    if ($stmt->execute()) {
        echo "<div style='color: green;'>Test admin user created successfully!<br>";
        echo "Username: test_admin<br>";
        echo "Password: test123</div>";
    } else {
        echo "<div style='color: red;'>Error creating test user: " . $stmt->error . "</div>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login System Test</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .test-form { margin: 20px 0; padding: 20px; border: 1px solid #ccc; }
        .btn { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <h2>Test Login Form</h2>
    <div class="test-form">
        <form method="POST">
            <div>
                <label>Username:</label><br>
                <input type="text" name="username" value="admin">
            </div>
            <div style="margin-top: 10px;">
                <label>Password:</label><br>
                <input type="text" name="password" value="password">
            </div>
            <div style="margin-top: 10px;">
                <button type="submit" name="test_login" class="btn">Test Login</button>
            </div>
        </form>
    </div>

    <h2>Create Test Admin User</h2>
    <div class="test-form">
        <form method="POST">
            <button type="submit" name="create_test_user" class="btn">Create Test Admin User</button>
        </form>
    </div>

    <h2>Session Information</h2>
    <pre>
    <?php print_r($_SESSION); ?>
    </pre>

    <h2>Server Information</h2>
    <pre>
    PHP Version: <?php echo phpversion(); ?>
    Document Root: <?php echo $_SERVER['DOCUMENT_ROOT']; ?>
    Script Path: <?php echo __FILE__; ?>
    </pre>
</body>
</html>
