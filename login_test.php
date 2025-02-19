<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'controllers/AuthController.php';

session_start();

// Clear any existing session
session_destroy();
session_start();

$auth = new AuthController($conn);

echo "<div style='font-family: Arial; padding: 20px;'>";

echo "<h2>Login Test Page</h2>";

// Test 1: Database Connection
echo "<h3>Test 1: Database Connection</h3>";
if ($conn->ping()) {
    echo "<div style='color: green;'>Database connection successful ✓</div>";
} else {
    echo "<div style='color: red;'>Database connection failed: " . $conn->error . " ✗</div>";
}

// Test 2: Check Admin User
echo "<h3>Test 2: Check Admin User</h3>";
$stmt = $conn->prepare("SELECT * FROM users WHERE username = 'admin'");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    echo "<div style='color: green;'>Admin user found ✓</div>";
    echo "Status: " . $user['status'] . "<br>";
    echo "Role: " . $user['role'] . "<br>";
} else {
    echo "<div style='color: red;'>Admin user not found ✗</div>";
}

// Test 3: Password Verification
echo "<h3>Test 3: Password Verification</h3>";
if (isset($user)) {
    if (password_verify('admin123', $user['password'])) {
        echo "<div style='color: green;'>Password verification successful ✓</div>";
    } else {
        echo "<div style='color: red;'>Password verification failed ✗</div>";
        echo "Stored hash: " . $user['password'] . "<br>";
        echo "Test hash: " . password_hash('admin123', PASSWORD_DEFAULT) . "<br>";
    }
}

// Test 4: Login Attempt
echo "<h3>Test 4: Login Attempt</h3>";
$result = $auth->login('admin', 'admin123');
echo "<pre>";
print_r($result);
echo "</pre>";

if (isset($result['success']) && $result['success']) {
    echo "<div style='color: green;'>Login successful ✓</div>";
    echo "<h4>Session Data:</h4>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
} else {
    echo "<div style='color: red;'>Login failed ✗</div>";
    if (isset($result['error'])) {
        echo "Error: " . $result['error'] . "<br>";
    }
}

echo "<h3>Manual Login Form</h3>";
?>
<form method="POST" action="index.php" style="margin: 20px 0; padding: 20px; border: 1px solid #ccc;">
    <div style="margin-bottom: 10px;">
        <label>Username:</label><br>
        <input type="text" name="username" value="admin">
    </div>
    <div style="margin-bottom: 10px;">
        <label>Password:</label><br>
        <input type="text" name="password" value="admin123">
    </div>
    <button type="submit" style="padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer;">
        Try Login
    </button>
</form>

<h3>Useful Links</h3>
<ul>
    <li><a href="setup.php">Run Setup Script</a></li>
    <li><a href="test_login.php">Detailed Login Tests</a></li>
    <li><a href="reset_admin.php">Reset Admin Account</a></li>
    <li><a href="index.php">Main Login Page</a></li>
</ul>

<h3>Debug Information</h3>
<pre>
PHP Version: <?php echo phpversion(); ?>
Session Save Path: <?php echo session_save_path(); ?>
Session Status: <?php echo session_status(); ?>
Cookie Parameters: <?php print_r(session_get_cookie_params()); ?>
</pre>
</div><?php
echo "</div>";
?>
