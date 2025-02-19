<?php
session_start();
require_once '../config/database.php';

$message = '';
$valid_token = false;

// Add reset token columns if they don't exist
$sql = "SHOW COLUMNS FROM users LIKE 'reset_token'";
$result = $conn->query($sql);
if ($result->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD reset_token VARCHAR(64), ADD reset_token_expiry DATETIME");
}

if (isset($_GET['token'])) {
    $token = mysqli_real_escape_string($conn, $_GET['token']);
    
    // Verify token
    $sql = "SELECT * FROM users WHERE reset_token = '$token' AND reset_token_expiry > NOW()";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $valid_token = true;
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            
            if ($password === $confirm_password) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET password = '$hashed_password', reset_token = NULL, reset_token_expiry = NULL 
                        WHERE reset_token = '$token'";
                
                if ($conn->query($sql)) {
                    $message = "Password has been reset successfully. You can now <a href='../index.php'>login</a>.";
                } else {
                    $message = "Error resetting password.";
                }
            } else {
                $message = "Passwords do not match.";
            }
        }
    } else {
        $message = "Invalid or expired reset token.";
    }
} else {
    $message = "No reset token provided.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Client Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
        }
        .reset-container {
            max-width: 400px;
            margin: 100px auto;
        }
        .reset-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .reset-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .reset-header h1 {
            color: #2c3e50;
            font-size: 24px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container reset-container">
        <div class="reset-card">
            <div class="reset-header">
                <h1>Reset Password</h1>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-info"><?php echo $message; ?></div>
            <?php endif; ?>

            <?php if ($valid_token): ?>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Reset Password</button>
                </form>
            <?php endif; ?>
            
            <div class="text-center mt-3">
                <a href="../index.php">Back to Login</a>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
