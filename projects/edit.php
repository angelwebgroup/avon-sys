<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$project_id = mysqli_real_escape_string($conn, $_GET['id']);

// Get project details
$sql = "SELECT * FROM projects WHERE id = $project_id";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    header("Location: list.php");
    exit();
}

$project = $result->fetch_assoc();

// Get all clients
$sql = "SELECT id, name FROM clients ORDER BY name";
$clients = $conn->query($sql);

// Get recurring payment details if exists
$sql = "SELECT * FROM recurring_payments WHERE project_id = $project_id LIMIT 1";
$recurring_result = $conn->query($sql);
$recurring_payment = $recurring_result->fetch_assoc();

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

    $sql = "UPDATE projects 
            SET client_id = '$client_id',
                name = '$name',
                description = '$description',
                start_date = '$start_date',
                end_date = '$end_date',
                status = '$status',
                budget = '$budget'
            WHERE id = $project_id";

    if ($conn->query($sql) === TRUE) {
        // Handle recurring payment update
        if ($is_recurring) {
            if ($recurring_payment) {
                // Update existing recurring payment
                $sql = "UPDATE recurring_payments 
                        SET amount = '$recurring_amount',
                            frequency = '$recurring_frequency'
                        WHERE project_id = $project_id";
            } else {
                // Create new recurring payment
                $sql = "INSERT INTO recurring_payments (project_id, amount, frequency, next_due_date, status) 
                        VALUES ($project_id, $recurring_amount, '$recurring_frequency', '$start_date', 'Pending')";
            }
            $conn->query($sql);
        } else if ($recurring_payment) {
            // Delete existing recurring payment if disabled
            $sql = "DELETE FROM recurring_payments WHERE project_id = $project_id";
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
    <title>Edit Project - Client Management System</title>
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
                    <h2>Edit Project</h2>
                    <a href="list.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="client_id" class="form-label">Client</label>
                                <select class="form-control" id="client_id" name="client_id" required>
                                    <?php while($client = $clients->fetch_assoc()): ?>
                                        <option value="<?php echo $client['id']; ?>" <?php echo $client['id'] == $project['client_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($client['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="name" class="form-label">Project Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($project['name']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($project['description']); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $project['start_date']; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $project['end_date']; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-control" id="status" name="status" required>
                                    <option value="Planning" <?php echo $project['status'] == 'Planning' ? 'selected' : ''; ?>>Planning</option>
                                    <option value="In Progress" <?php echo $project['status'] == 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="Completed" <?php echo $project['status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="On Hold" <?php echo $project['status'] == 'On Hold' ? 'selected' : ''; ?>>On Hold</option>
                                    <option value="Cancelled" <?php echo $project['status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="budget" class="form-label">Budget</label>
                                <input type="number" step="0.01" class="form-control" id="budget" name="budget" value="<?php echo $project['budget']; ?>" required>
                            </div>

                            <div class="card mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">Recurring Payment</h5>
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="is_recurring" name="is_recurring" <?php echo $recurring_payment ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="is_recurring">
                                                Enable Recurring Payment
                                            </label>
                                        </div>
                                    </div>
                                    <div id="recurring_options" style="display: <?php echo $recurring_payment ? 'block' : 'none'; ?>;">
                                        <div class="mb-3">
                                            <label for="recurring_amount" class="form-label">Recurring Amount</label>
                                            <input type="number" step="0.01" class="form-control" id="recurring_amount" name="recurring_amount" value="<?php echo $recurring_payment ? $recurring_payment['amount'] : ''; ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label for="recurring_frequency" class="form-label">Payment Frequency</label>
                                            <select class="form-control" id="recurring_frequency" name="recurring_frequency">
                                                <option value="Monthly" <?php echo $recurring_payment && $recurring_payment['frequency'] == 'Monthly' ? 'selected' : ''; ?>>Monthly</option>
                                                <option value="Quarterly" <?php echo $recurring_payment && $recurring_payment['frequency'] == 'Quarterly' ? 'selected' : ''; ?>>Quarterly</option>
                                                <option value="Yearly" <?php echo $recurring_payment && $recurring_payment['frequency'] == 'Yearly' ? 'selected' : ''; ?>>Yearly</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">Update Project</button>
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
