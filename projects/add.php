<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$error = '';
$success = '';

// Fetch all clients for dropdown
$clients_sql = "SELECT id, name FROM clients ORDER BY name";
$clients_result = $conn->query($clients_sql);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $client_id = mysqli_real_escape_string($conn, $_POST['client_id']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $start_date = mysqli_real_escape_string($conn, $_POST['start_date']);
    $end_date = mysqli_real_escape_string($conn, $_POST['end_date']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $budget = mysqli_real_escape_string($conn, $_POST['budget']);
    
    // Handle recurring payment
    $is_recurring = isset($_POST['is_recurring']) ? 1 : 0;
    $recurring_amount = $is_recurring ? mysqli_real_escape_string($conn, $_POST['recurring_amount']) : 0;
    $recurring_frequency = $is_recurring ? mysqli_real_escape_string($conn, $_POST['recurring_frequency']) : '';

    $sql = "INSERT INTO projects (client_id, name, description, start_date, end_date, status, budget) 
            VALUES ('$client_id', '$name', '$description', '$start_date', '$end_date', '$status', '$budget')";

    if ($conn->query($sql) === TRUE) {
        $project_id = $conn->insert_id;
        
        // Create recurring payment if enabled
        if ($is_recurring) {
            $next_due_date = $start_date; // First payment due on start date
            
            $sql = "INSERT INTO recurring_payments (project_id, amount, frequency, next_due_date, status) 
                    VALUES ($project_id, $recurring_amount, '$recurring_frequency', '$next_due_date', 'Pending')";
            $conn->query($sql);
        }
        
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
    <title>Add Project - Client Management System</title>
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
                    <h2>Add New Project</h2>
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
                                    <?php while($client = $clients_result->fetch_assoc()): ?>
                                        <option value="<?php echo $client['id']; ?>">
                                            <?php echo htmlspecialchars($client['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="name" class="form-label">Project Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="end_date" class="form-label">End Date</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-control" id="status" name="status" required>
                                    <option value="Not Started">Not Started</option>
                                    <option value="In Progress">In Progress</option>
                                    <option value="On Hold">On Hold</option>
                                    <option value="Completed">Completed</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="budget" class="form-label">Budget</label>
                                <input type="number" step="0.01" class="form-control" id="budget" name="budget" required>
                            </div>
                            
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">Recurring Payment</h5>
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="is_recurring" name="is_recurring">
                                            <label class="form-check-label" for="is_recurring">
                                                Enable Recurring Payment
                                            </label>
                                        </div>
                                    </div>
                                    <div id="recurring_options" style="display: none;">
                                        <div class="mb-3">
                                            <label for="recurring_amount" class="form-label">Recurring Amount</label>
                                            <input type="number" step="0.01" class="form-control" id="recurring_amount" name="recurring_amount">
                                        </div>
                                        <div class="mb-3">
                                            <label for="recurring_frequency" class="form-label">Payment Frequency</label>
                                            <select class="form-control" id="recurring_frequency" name="recurring_frequency">
                                                <option value="Monthly">Monthly</option>
                                                <option value="Quarterly">Quarterly</option>
                                                <option value="Yearly">Yearly</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Add Project</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('is_recurring').addEventListener('change', function() {
            document.getElementById('recurring_options').style.display = this.checked ? 'block' : 'none';
            
            const recurringInputs = document.querySelectorAll('#recurring_options input, #recurring_options select');
            recurringInputs.forEach(input => {
                input.required = this.checked;
            });
        });
    </script>
</body>
</html>
