<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$error = '';
$success = '';

// Get all clients
$sql = "SELECT id, name FROM clients ORDER BY name";
$clients = $conn->query($sql);

// Get all projects
$sql = "SELECT id, name, client_id FROM projects ORDER BY name";
$projects = $conn->query($sql);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $client_id = mysqli_real_escape_string($conn, $_POST['client_id']);
    $project_id = isset($_POST['project_id']) ? mysqli_real_escape_string($conn, $_POST['project_id']) : null;
    $amount = mysqli_real_escape_string($conn, $_POST['amount']);
    $issue_date = mysqli_real_escape_string($conn, $_POST['issue_date']);
    $due_date = mysqli_real_escape_string($conn, $_POST['due_date']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);
    
    // Get invoice prefix from settings
    $settings_sql = "SELECT invoice_prefix FROM settings LIMIT 1";
    $settings_result = $conn->query($settings_sql);
    $settings = $settings_result->fetch_assoc();
    $invoice_prefix = $settings['invoice_prefix'] ?? 'INV-';
    
    // Generate invoice number
    $invoice_number = $invoice_prefix . date('Ymd') . '-' . sprintf('%04d', rand(1, 9999));
    
    $sql = "INSERT INTO invoices (client_id, project_id, invoice_number, issue_date, due_date, amount, status, notes) 
            VALUES ('$client_id', " . ($project_id ? "'$project_id'" : "NULL") . ", '$invoice_number', '$issue_date', '$due_date', '$amount', 'Pending', '$notes')";
    
    if ($conn->query($sql) === TRUE) {
        header("Location: list.php?success=1");
        exit();
    } else {
        $error = "Error: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Invoice - Client Management System</title>
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
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../includes/sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Create New Invoice</h2>
                    <a href="list.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="client_id" class="form-label">Client</label>
                                <select class="form-control" id="client_id" name="client_id" required>
                                    <option value="">Select Client</option>
                                    <?php while($client = $clients->fetch_assoc()): ?>
                                        <option value="<?php echo $client['id']; ?>">
                                            <?php echo htmlspecialchars($client['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="project_id" class="form-label">Project (Optional)</label>
                                <select class="form-control" id="project_id" name="project_id">
                                    <option value="">Select Project</option>
                                    <?php while($project = $projects->fetch_assoc()): ?>
                                        <option value="<?php echo $project['id']; ?>" data-client="<?php echo $project['client_id']; ?>">
                                            <?php echo htmlspecialchars($project['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="amount" class="form-label">Amount</label>
                                <input type="number" step="0.01" class="form-control" id="amount" name="amount" required>
                            </div>

                            <div class="mb-3">
                                <label for="issue_date" class="form-label">Issue Date</label>
                                <input type="date" class="form-control" id="issue_date" name="issue_date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="due_date" class="form-label">Due Date</label>
                                <input type="date" class="form-control" id="due_date" name="due_date" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">Create Invoice</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Filter projects based on selected client
        document.getElementById('client_id').addEventListener('change', function() {
            const clientId = this.value;
            const projectSelect = document.getElementById('project_id');
            const projectOptions = projectSelect.getElementsByTagName('option');
            
            for (let option of projectOptions) {
                if (option.value === '') {
                    option.style.display = 'block';
                    continue;
                }
                
                if (option.getAttribute('data-client') === clientId) {
                    option.style.display = 'block';
                } else {
                    option.style.display = 'none';
                }
            }
            
            projectSelect.value = '';
        });
    </script>
</body>
</html>
