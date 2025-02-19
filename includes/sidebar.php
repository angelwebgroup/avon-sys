<?php
// Get current page for active menu highlighting
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$currentDir = basename(dirname($_SERVER['PHP_SELF']));

// Special case for dashboard
$isDashboard = $currentPage === 'dashboard';

// Ensure auth is available
if (!isset($auth)) {
    require_once __DIR__ . '/../controllers/AuthController.php';
    $auth = new AuthController($conn);
}
?>
<div class="col-md-3 col-lg-2 px-0 sidebar">
    <div class="p-3">
        <h4>Avon System</h4>
        <hr>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $isDashboard ? 'active' : ''; ?>" 
                   href="/avon-sys/dashboard.php">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </a>
            </li>
            <?php if ($auth->hasPermission('view_customers')): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentDir === 'customers' ? 'active' : ''; ?>" 
                   href="/avon-sys/views/customers/index.php">
                    <i class="fas fa-users me-2"></i>Customers
                </a>
            </li>
            <?php endif; ?>
            <?php if ($auth->hasPermission('view_quotes')): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentDir === 'quotes' ? 'active' : ''; ?>" 
                   href="/avon-sys/views/quotes/index.php">
                    <i class="fas fa-file-invoice-dollar me-2"></i>Quotes
                </a>
            </li>
            <?php endif; ?>
            <?php if ($auth->hasPermission('view_purchase_orders')): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentDir === 'purchase-orders' ? 'active' : ''; ?>" 
                   href="/avon-sys/views/purchase-orders/index.php">
                    <i class="fas fa-shopping-cart me-2"></i>Purchase Orders
                </a>
            </li>
            <?php endif; ?>
            <?php if ($auth->hasPermission('view_inquiries')): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentDir === 'inquiries' ? 'active' : ''; ?>" 
                   href="/avon-sys/views/inquiries/index.php">
                    <i class="fas fa-question-circle me-2"></i>Inquiries
                </a>
            </li>
            <?php endif; ?>
            <?php if ($auth->hasRole('admin')): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentDir === 'users' ? 'active' : ''; ?>" 
                   href="/avon-sys/views/users/index.php">
                    <i class="fas fa-user-cog me-2"></i>Users
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentDir === 'profile' ? 'active' : ''; ?>" 
                   href="/avon-sys/views/profile/index.php">
                    <i class="fas fa-user me-2"></i>Profile
                </a>
            </li>
            <li class="nav-item mt-3">
                <a class="nav-link text-danger" href="/avon-sys/logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </li>
        </ul>
    </div>
</div>
