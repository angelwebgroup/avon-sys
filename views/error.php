<?php
$code = isset($_GET['code']) ? (int)$_GET['code'] : 500;
$message = isset($_GET['message']) ? $_GET['message'] : 'An error occurred';

$titles = [
    403 => 'Access Denied',
    404 => 'Page Not Found',
    500 => 'Server Error'
];

$descriptions = [
    403 => 'You do not have permission to access this resource.',
    404 => 'The page you are looking for could not be found.',
    500 => 'An internal server error occurred.'
];

$title = $titles[$code] ?? 'Error';
$description = $descriptions[$code] ?? 'An unexpected error occurred.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> - Avon System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="/avon-sys/assets/css/style.css" rel="stylesheet">
    <style>
        .error-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
        }
        .error-code {
            font-size: 6rem;
            font-weight: bold;
            color: #dc3545;
            line-height: 1;
        }
        .error-message {
            font-size: 1.5rem;
            color: #6c757d;
            margin-bottom: 1rem;
        }
        .error-description {
            color: #6c757d;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="error-page">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 text-center">
                    <div class="error-code mb-3"><?php echo $code; ?></div>
                    <h1 class="error-message"><?php echo htmlspecialchars($title); ?></h1>
                    <p class="error-description"><?php echo htmlspecialchars($description); ?></p>
                    <?php if ($message !== $description): ?>
                        <p class="text-danger mb-4"><?php echo htmlspecialchars($message); ?></p>
                    <?php endif; ?>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="javascript:history.back()" class="btn btn-secondary">Go Back</a>
                        <a href="/avon-sys/dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
