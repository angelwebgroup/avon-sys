<?php
/**
 * Customer Management Interface
 */

require_once '../../includes/init_module.php';
require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/CustomerController.php';

// Initialize module
$moduleManager = initModule('customers');

try {
    $auth = new AuthController($conn);
    $customerController = new CustomerController($conn, $auth);

    if (!$auth->isAuthenticated()) {
        header("Location: /avon-sys/views/auth/login.php");
        exit();
    }

    // Get pagination and filter parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
    $filters = [
        'search' => isset($_GET['search']) ? $_GET['search'] : '',
        'state' => isset($_GET['state']) ? $_GET['state'] : '',
        'approval_status' => isset($_GET['approval_status']) ? $_GET['approval_status'] : ''
    ];

    // Get customers with pagination and filters
    $result = $customerController->getCustomers($page, $perPage, $filters);
    $customers = $result['data'];
    $totalPages = $result['totalPages'];
    
} catch (Exception $e) {
    $moduleManager->logModuleError('Error in customers/index.php: ' . $e->getMessage());
    $error_message = 'An error occurred while loading the customer list. Please try again later.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers - Avon System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="/avon-sys/assets/css/style.css" rel="stylesheet">
    <style>
        .table th { 
            background-color: #f8f9fa;
        }
        .status-badge {
            font-size: 0.85rem;
            padding: 0.35em 0.65em;
        }
        .action-buttons .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../../includes/sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 content">
                <div class="p-4">
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($error_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php else: ?>
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2>Customers</h2>
                            <a href="create.php" class="btn btn-primary">
                                <i class="fas fa-plus-circle"></i> Add New Customer
                            </a>
                        </div>

                        <?php if (isset($_SESSION['success_message'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php 
                                    echo htmlspecialchars($_SESSION['success_message']);
                                    unset($_SESSION['success_message']);
                                ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['error_message'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php 
                                    echo htmlspecialchars($_SESSION['error_message']);
                                    unset($_SESSION['error_message']);
                                ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Search and Filters -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <form method="GET" class="row g-3">
                                    <div class="col-md-4">
                                        <input type="text" class="form-control" name="search" 
                                               value="<?php echo htmlspecialchars($filters['search']); ?>" 
                                               placeholder="Search customers...">
                                    </div>
                                    <div class="col-md-3">
                                        <select class="form-select" name="state">
                                            <option value="">All States</option>
                                            <!-- Add state options -->
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <select class="form-select" name="approval_status">
                                            <option value="">All Status</option>
                                            <option value="Y" <?php echo $filters['approval_status'] === 'Y' ? 'selected' : ''; ?>>Approved</option>
                                            <option value="N" <?php echo $filters['approval_status'] === 'N' ? 'selected' : ''; ?>>Pending</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-search"></i> Search
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Customers Table -->
                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Company Name</th>
                                                <th>Contact Person</th>
                                                <th>Email</th>
                                                <th>Phone</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($customers as $customer): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($customer['company_name']); ?></td>
                                                <td><?php echo htmlspecialchars($customer['contact_person']); ?></td>
                                                <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                                <td><?php echo htmlspecialchars($customer['mobile_no']); ?></td>
                                                <td>
                                                    <?php if ($customer['customer_approved'] === 'Y'): ?>
                                                        <span class="badge bg-success status-badge">
                                                            Approved
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning status-badge">
                                                            Pending
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="action-buttons">
                                                    <a href="view.php?id=<?php echo $customer['id']; ?>" class="btn btn-info btn-sm">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="edit.php?id=<?php echo $customer['id']; ?>" class="btn btn-primary btn-sm">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button class="btn btn-danger btn-sm delete-customer" data-id="<?php echo $customer['id']; ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Pagination -->
                                <?php if ($totalPages > 1): ?>
                                    <nav aria-label="Page navigation" class="mt-4">
                                        <ul class="pagination justify-content-center">
                                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                                <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                                                    <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                                        <?php echo $i; ?>
                                                    </a>
                                                </li>
                                            <?php endfor; ?>
                                        </ul>
                                    </nav>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Delete customer
            document.querySelectorAll('.delete-customer').forEach(button => {
                button.addEventListener('click', function() {
                    if (confirm('Are you sure you want to delete this customer?')) {
                        const customerId = this.getAttribute('data-id');
                        fetch('process.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                action: 'delete',
                                id: customerId
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Customer deleted successfully');
                                window.location.reload();
                            } else {
                                alert('Error: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred while deleting the customer');
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
