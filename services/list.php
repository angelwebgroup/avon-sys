<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Create services table if not exists
$sql = "CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    duration VARCHAR(50),
    category VARCHAR(50),
    project_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id)
)";

if ($conn->query($sql) !== TRUE) {
    die("Error creating table: " . $conn->error);
}

// Get all services with project details
$sql = "SELECT s.*, p.name as project_name, c.name as client_name 
        FROM services s 
        LEFT JOIN projects p ON s.project_id = p.id 
        LEFT JOIN clients c ON p.client_id = c.id 
        ORDER BY s.created_at DESC";
$result = $conn->query($sql);

// Get project list for filter
$sql = "SELECT p.id, p.name, c.name as client_name 
        FROM projects p 
        LEFT JOIN clients c ON p.client_id = c.id 
        ORDER BY c.name, p.name";
$projects = $conn->query($sql);

// Handle project filter
$project_filter = isset($_GET['project_id']) ? $_GET['project_id'] : '';
if ($project_filter) {
    $project_filter = mysqli_real_escape_string($conn, $project_filter);
    $sql = "SELECT s.*, p.name as project_name, c.name as client_name 
            FROM services s 
            LEFT JOIN projects p ON s.project_id = p.id 
            LEFT JOIN clients c ON p.client_id = c.id 
            WHERE s.project_id = '$project_filter'
            ORDER BY s.created_at DESC";
    $result = $conn->query($sql);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services - Client Management System</title>
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
        .service-card {
            transition: transform 0.3s;
        }
        .service-card:hover {
            transform: translateY(-5px);
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
                    <h2>Services</h2>
                    <a href="add.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Service
                    </a>
                </div>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
                <?php endif; ?>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
                <?php endif; ?>

                <!-- Project Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="" class="row align-items-end">
                            <div class="col-md-4">
                                <label for="project_id" class="form-label">Filter by Project</label>
                                <select class="form-control" id="project_id" name="project_id" onchange="this.form.submit()">
                                    <option value="">All Projects</option>
                                    <?php while($project = $projects->fetch_assoc()): ?>
                                        <option value="<?php echo $project['id']; ?>" <?php echo $project_filter == $project['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($project['client_name'] . ' - ' . $project['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="row">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($service = $result->fetch_assoc()): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card service-card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($service['name']); ?></h5>
                                        <p class="card-text"><?php echo htmlspecialchars($service['description']); ?></p>
                                        <div class="mb-2">
                                            <strong>Price:</strong> $<?php echo number_format($service['price'], 2); ?>
                                        </div>
                                        <div class="mb-2">
                                            <strong>Duration:</strong>
                                            <span class="badge bg-info"><?php echo $service['duration']; ?></span>
                                        </div>
                                        <div class="mb-2">
                                            <strong>Category:</strong>
                                            <span class="badge bg-secondary"><?php echo $service['category']; ?></span>
                                        </div>
                                        <div class="mb-3">
                                            <strong>Project:</strong>
                                            <?php if ($service['project_name']): ?>
                                                <span class="badge bg-primary">
                                                    <?php echo htmlspecialchars($service['client_name'] . ' - ' . $service['project_name']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">No Project Assigned</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="btn-group">
                                            <a href="edit.php?id=<?php echo $service['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="delete.php?id=<?php echo $service['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this service?')">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-info">No services found.</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
