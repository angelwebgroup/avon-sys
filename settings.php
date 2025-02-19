<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Create settings table if not exists
$sql = "CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(100),
    company_email VARCHAR(100),
    company_phone VARCHAR(20),
    company_address TEXT,
    invoice_prefix VARCHAR(10),
    tax_rate DECIMAL(5,2),
    currency VARCHAR(10),
    date_format VARCHAR(20),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) !== TRUE) {
    die("Error creating table: " . $conn->error);
}

// Check if settings exist, if not insert default
$sql = "SELECT * FROM settings LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    $sql = "INSERT INTO settings (company_name, company_email, company_phone, company_address, invoice_prefix, tax_rate, currency, date_format) 
            VALUES ('Your Company', 'company@example.com', '', '', 'INV-', 10.00, 'USD', 'Y-m-d')";
    $conn->query($sql);
    $result = $conn->query("SELECT * FROM settings LIMIT 1");
}

$settings = $result->fetch_assoc();

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $company_name = mysqli_real_escape_string($conn, $_POST['company_name']);
    $company_email = mysqli_real_escape_string($conn, $_POST['company_email']);
    $company_phone = mysqli_real_escape_string($conn, $_POST['company_phone']);
    $company_address = mysqli_real_escape_string($conn, $_POST['company_address']);
    $invoice_prefix = mysqli_real_escape_string($conn, $_POST['invoice_prefix']);
    $tax_rate = mysqli_real_escape_string($conn, $_POST['tax_rate']);
    $currency = mysqli_real_escape_string($conn, $_POST['currency']);
    $date_format = mysqli_real_escape_string($conn, $_POST['date_format']);

    $sql = "UPDATE settings SET 
            company_name = '$company_name',
            company_email = '$company_email',
            company_phone = '$company_phone',
            company_address = '$company_address',
            invoice_prefix = '$invoice_prefix',
            tax_rate = '$tax_rate',
            currency = '$currency',
            date_format = '$date_format'";

    if ($conn->query($sql) === TRUE) {
        $success_message = "Settings updated successfully!";
        $settings = $conn->query("SELECT * FROM settings LIMIT 1")->fetch_assoc();
    } else {
        $error_message = "Error updating settings: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Client Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: #2c3e50;
            color: white;
        }
        .sidebar-link {
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            display: block;
            transition: 0.3s;
        }
        .sidebar-link:hover {
            background: #34495e;
            color: white;
        }
        .sidebar-link i {
            margin-right: 10px;
        }
        .content {
            padding: 20px;
        }
        .settings-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 content">
                <h2 class="mb-4">System Settings</h2>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <div class="card settings-card">
                    <div class="card-body">
                        <form method="POST" action="">
                            <h5 class="mb-4">Company Information</h5>
                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <label for="company_name" class="form-label">Company Name</label>
                                    <input type="text" class="form-control" id="company_name" name="company_name" 
                                           value="<?php echo htmlspecialchars($settings['company_name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="company_email" class="form-label">Company Email</label>
                                    <input type="email" class="form-control" id="company_email" name="company_email" 
                                           value="<?php echo htmlspecialchars($settings['company_email']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="company_phone" class="form-label">Company Phone</label>
                                    <input type="text" class="form-control" id="company_phone" name="company_phone" 
                                           value="<?php echo htmlspecialchars($settings['company_phone']); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="company_address" class="form-label">Company Address</label>
                                    <textarea class="form-control" id="company_address" name="company_address" rows="3"><?php echo htmlspecialchars($settings['company_address']); ?></textarea>
                                </div>
                            </div>

                            <h5 class="mb-4">Invoice Settings</h5>
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label for="invoice_prefix" class="form-label">Invoice Prefix</label>
                                    <input type="text" class="form-control" id="invoice_prefix" name="invoice_prefix" 
                                           value="<?php echo htmlspecialchars($settings['invoice_prefix']); ?>" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="tax_rate" class="form-label">Tax Rate (%)</label>
                                    <input type="number" step="0.01" class="form-control" id="tax_rate" name="tax_rate" 
                                           value="<?php echo htmlspecialchars($settings['tax_rate']); ?>" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="currency" class="form-label">Currency</label>
                                    <select class="form-control" id="currency" name="currency">
                                        <option value="USD" <?php echo $settings['currency'] == 'USD' ? 'selected' : ''; ?>>USD</option>
                                        <option value="EUR" <?php echo $settings['currency'] == 'EUR' ? 'selected' : ''; ?>>EUR</option>
                                        <option value="GBP" <?php echo $settings['currency'] == 'GBP' ? 'selected' : ''; ?>>GBP</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="date_format" class="form-label">Date Format</label>
                                    <select class="form-control" id="date_format" name="date_format">
                                        <option value="Y-m-d" <?php echo $settings['date_format'] == 'Y-m-d' ? 'selected' : ''; ?>>YYYY-MM-DD</option>
                                        <option value="d/m/Y" <?php echo $settings['date_format'] == 'd/m/Y' ? 'selected' : ''; ?>>DD/MM/YYYY</option>
                                        <option value="m/d/Y" <?php echo $settings['date_format'] == 'm/d/Y' ? 'selected' : ''; ?>>MM/DD/YYYY</option>
                                    </select>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary mt-3">Save Settings</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
