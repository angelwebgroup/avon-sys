<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'controllers/AuthController.php';

// Clear any existing sessions
if (isset($_GET['clear'])) {
    session_start();
    session_destroy();
    header("Location: index.php");
    exit();
}

$auth = new AuthController($conn);

if ($auth->isAuthenticated()) {
    header("Location: dashboard.php");
    exit();
}

$debug_info = [];
$debug_info['session_status'] = session_status();
$debug_info['session_save_path'] = session_save_path();
$debug_info['session_cookie_params'] = session_get_cookie_params();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Log login attempt
    error_log("Login attempt - Username: " . $username);
    $debug_info['login_attempt'] = "Username: $username";
    
    $result = $auth->login($username, $password);
    $debug_info['login_result'] = $result;
    error_log("Login result: " . print_r($result, true));
    
    if (isset($result['success']) && $result['success']) {
        error_log("Login successful - Redirecting to dashboard");
        header("Location: dashboard.php");
        exit();
    } else {
        $error = isset($result['error']) ? $result['error'] : "Invalid username or password";
        error_log("Login failed - Error: " . $error);
    }
}

// Get available users
$users_query = $conn->query("SELECT username, role, status FROM users ORDER BY username");
$available_users = $users_query->fetch_all(MYSQLI_ASSOC);
$debug_info['available_users'] = $available_users;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Management System - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-container {
            max-width: 400px;
            margin: 0 auto;
        }
        .login-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h1 {
            color: #2c3e50;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .login-header p {
            color: #7f8c8d;
            margin-bottom: 0;
        }
        .form-control {
            padding: 12px;
            margin-bottom: 20px;
        }
        .btn-login {
            background: #3498db;
            color: white;
            padding: 12px;
            width: 100%;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        .btn-login:hover {
            background: #2980b9;
            color: white;
        }
        .debug-info {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            font-size: 12px;
        }
        .debug-info pre {
            margin: 0;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <h1>Client Management System</h1>
                    <p>Enter your credentials to access the system</p>
                </div>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                               placeholder="Enter your username">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required
                               placeholder="Enter your password">
                    </div>
                    <button type="submit" class="btn btn-login">Login</button>
                </form>

                <div class="mt-3">
                    <a href="?clear=1" class="btn btn-sm btn-secondary">Clear Session</a>
                </div>

                <div class="debug-info">
                    <h6>Debug Information</h6>
                    <pre><?php 
                        echo "PHP Version: " . phpversion() . "\n";
                        echo "Session Status: " . session_status() . "\n";
                        echo "Available Users:\n";
                        foreach ($available_users as $user) {
                            echo "- {$user['username']} ({$user['role']}) - {$user['status']}\n";
                        }
                        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                            echo "\nLogin Attempt Details:\n";
                            echo "Username: " . htmlspecialchars($_POST['username']) . "\n";
                            echo "Password length: " . strlen($_POST['password']) . "\n";
                            if (isset($result)) {
                                echo "Login Result:\n";
                                print_r($result);
                            }
                        }
                    ?></pre>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
