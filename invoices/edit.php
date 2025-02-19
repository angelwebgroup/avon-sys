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

$invoice_id = mysqli_real_escape_string($conn, $_GET['id']);

// Get invoice details
$sql = "SELECT i.*, c.name as client_name, p.name as project_name 
        FROM invoices i 
        LEFT JOIN clients c ON i.client_id = c.id 
        LEFT JOIN projects p ON i.project_id = p.id 
        WHERE i.id = $invoice_id";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    header("Location: list.php");
    exit();
}

$invoice = $result->fetch_assoc();

// Get all clients
$sql = "SELECT id, name FROM clients ORDER BY name";
$clients = $conn->query($sql);

// Get all projects
$sql = "SELECT id, name FROM projects WHERE client_id = {$invoice['client_id']} ORDER BY name";
$projects = $conn->query($sql);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $client_id = mysqli_real_escape_string($conn, $_POST['client_id']);
    $project_id = isset($_POST['project_id']) ? mysqli_real_escape_string($conn, $_POST['project_id']) : 'NULL';
    $amount = mysqli_real_escape_string($conn, $_POST['amount']);
    $issue_date = mysqli_real_escape_string($conn, $_POST['issue_date']);
    $due_date = mysqli_real_escape_string($conn, $_POST['due_date']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);
    
    $sql = "UPDATE invoices 
            SET client_id = '$client_id',
                project_id = $project_id,
                amount = '$amount',
                issue_date = '$issue_date',
                due_date = '$due_date',
                notes = '$notes'
            WHERE id = $invoice_id";
    
    if ($conn->query($sql) === TRUE) {
        header("Location: list.php?success=Invoice updated successfully");
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
    <title>Edit Invoice - Client Management System</title>
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
                    <h2>Edit Invoice</h2>
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
                                <label for="invoice_number" class="form-label">Invoice Number</label>
                                <input type="text" class="form-control" id="invoice_number" value="<?php echo htmlspecialchars($invoice['invoice_number']); ?>" readonly>
                            </div>

                            <div class="mb-3">
                                <label for="client_id" class="form-label">Client</label>
                                <select class="form-control" id="client_id" name="client_id" required>
                                    <?php while($client = $clients->fetch_assoc()): ?>
                                        <option value="<?php echo $client['id']; ?>" <?php echo $client['id'] == $invoice['client_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($client['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="project_id" class="form-label">Project (Optional)</label>
                                <select class="form-control" id="project_id" name="project_id">
                                    <option value="">-- Select Project --</option>
                                    <?php while($project = $projects->fetch_assoc()): ?>
                                        <option value="<?php echo $project['id']; ?>" <?php echo $project['id'] == $invoice['project_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($project['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="amount" class="form-label">Amount</label>
                                <input type="number" step="0.01" class="form-control" id="amount" name="amount" value="<?php echo $invoice['amount']; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="issue_date" class="form-label">Issue Date</label>
                                <input type="date" class="form-control" id="issue_date" name="issue_date" value="<?php echo $invoice['issue_date']; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="due_date" class="form-label">Due Date</label>
                                <input type="date" class="form-control" id="due_date" name="due_date" value="<?php echo $invoice['due_date']; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($invoice['notes']); ?></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">Update Invoice</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('client_id').addEventListener('change', function() {
            const clientId = this.value;
            const projectSelect = document.getElementById('project_id');
            
            // Clear current options
            projectSelect.innerHTML = '<option value="">-- Select Project --</option>';
            
            if (clientId) {
                fetch(`get_projects.php?client_id=${clientId}`)
                    .then(response => response.json())
                    .then(projects => {
                        projects.forEach(project => {
                            const option = document.createElement('option');
                            option.value = project.id;
                            option.textContent = project.name;
                            projectSelect.appendChild(option);
                        });
                    });
            }
        });
    </script>
</body>
</html>
