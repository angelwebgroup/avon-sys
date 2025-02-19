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

$service_id = mysqli_real_escape_string($conn, $_GET['id']);

// Get service details
$sql = "SELECT * FROM services WHERE id = $service_id";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    header("Location: list.php");
    exit();
}

$service = $result->fetch_assoc();

// Get all projects for dropdown
$sql = "SELECT p.id, p.name, c.name as client_name 
        FROM projects p 
        LEFT JOIN clients c ON p.client_id = c.id 
        ORDER BY c.name, p.name";
$projects = $conn->query($sql);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $price = mysqli_real_escape_string($conn, $_POST['price']);
    $duration = mysqli_real_escape_string($conn, $_POST['duration']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $project_id = isset($_POST['project_id']) && !empty($_POST['project_id']) ? 
                 mysqli_real_escape_string($conn, $_POST['project_id']) : 'NULL';

    $sql = "UPDATE services 
            SET name = '$name',
                description = '$description',
                price = '$price',
                duration = '$duration',
                category = '$category',
                project_id = $project_id
            WHERE id = $service_id";

    if ($conn->query($sql) === TRUE) {
        header("Location: list.php?success=Service updated successfully");
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
    <title>Edit Service - Client Management System</title>
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
                    <h2>Edit Service</h2>
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
                                <label for="name" class="form-label">Service Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($service['name']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($service['description']); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="price" class="form-label">Price</label>
                                <input type="number" step="0.01" class="form-control" id="price" name="price" value="<?php echo $service['price']; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="duration" class="form-label">Duration</label>
                                <select class="form-control" id="duration" name="duration" required>
                                    <option value="One-time" <?php echo $service['duration'] == 'One-time' ? 'selected' : ''; ?>>One-time</option>
                                    <option value="Monthly" <?php echo $service['duration'] == 'Monthly' ? 'selected' : ''; ?>>Monthly</option>
                                    <option value="Quarterly" <?php echo $service['duration'] == 'Quarterly' ? 'selected' : ''; ?>>Quarterly</option>
                                    <option value="Yearly" <?php echo $service['duration'] == 'Yearly' ? 'selected' : ''; ?>>Yearly</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-control" id="category" name="category" required>
                                    <option value="Web Development" <?php echo $service['category'] == 'Web Development' ? 'selected' : ''; ?>>Web Development</option>
                                    <option value="Web Design" <?php echo $service['category'] == 'Web Design' ? 'selected' : ''; ?>>Web Design</option>
                                    <option value="SEO" <?php echo $service['category'] == 'SEO' ? 'selected' : ''; ?>>SEO</option>
                                    <option value="Maintenance" <?php echo $service['category'] == 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                    <option value="Hosting" <?php echo $service['category'] == 'Hosting' ? 'selected' : ''; ?>>Hosting</option>
                                    <option value="Other" <?php echo $service['category'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="project_id" class="form-label">Project (Optional)</label>
                                <select class="form-control" id="project_id" name="project_id">
                                    <option value="">-- Select Project --</option>
                                    <?php while($project = $projects->fetch_assoc()): ?>
                                        <option value="<?php echo $project['id']; ?>" <?php echo $project['id'] == $service['project_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($project['client_name'] . ' - ' . $project['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary">Update Service</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
