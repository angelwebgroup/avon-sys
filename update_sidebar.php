<?php
function replaceHardcodedSidebar($filePath) {
    $content = file_get_contents($filePath);
    
    // Pattern to match the hardcoded sidebar
    $pattern = '/<div class="col-md-3 col-lg-2 px-0 sidebar">.*?<\/div>\s*<\/div>/s';
    
    // Replacement with include statement
    $replacement = '<?php include \'' . str_repeat('../', substr_count(str_replace('/Applications/XAMPP/xamppfiles/htdocs/avon-sys/', '', $filePath), '/')) . 'includes/sidebar.php\'; ?>';
    
    // Replace the hardcoded sidebar with include statement
    $newContent = preg_replace($pattern, $replacement, $content);
    
    // Save the changes
    file_put_contents($filePath, $newContent);
    echo "Updated: $filePath\n";
}

// List of files to update (excluding includes/sidebar.php)
$files = [
    '/Applications/XAMPP/xamppfiles/htdocs/avon-sys/services/edit.php',
    '/Applications/XAMPP/xamppfiles/htdocs/avon-sys/services/list.php',
    '/Applications/XAMPP/xamppfiles/htdocs/avon-sys/services/add.php',
    '/Applications/XAMPP/xamppfiles/htdocs/avon-sys/views/profile/index.php',
    '/Applications/XAMPP/xamppfiles/htdocs/avon-sys/views/users/edit.php',
    '/Applications/XAMPP/xamppfiles/htdocs/avon-sys/views/users/audit_logs.php',
    '/Applications/XAMPP/xamppfiles/htdocs/avon-sys/views/users/permission_analytics.php',
    '/Applications/XAMPP/xamppfiles/htdocs/avon-sys/views/users/permission_groups.php',
    '/Applications/XAMPP/xamppfiles/htdocs/avon-sys/views/users/create.php',
    '/Applications/XAMPP/xamppfiles/htdocs/avon-sys/views/users/manage.php',
    '/Applications/XAMPP/xamppfiles/htdocs/avon-sys/views/users/permissions.php',
    '/Applications/XAMPP/xamppfiles/htdocs/avon-sys/views/users/index.php',
    '/Applications/XAMPP/xamppfiles/htdocs/avon-sys/views/quotes/edit.php',
    '/Applications/XAMPP/xamppfiles/htdocs/avon-sys/views/quotes/view.php',
    '/Applications/XAMPP/xamppfiles/htdocs/avon-sys/views/quotes/index.php',
    '/Applications/XAMPP/xamppfiles/htdocs/avon-sys/views/quotes/create.php',
    '/Applications/XAMPP/xamppfiles/htdocs/avon-sys/views/purchase-orders/view.php',
    '/Applications/XAMPP/xamppfiles/htdocs/avon-sys/views/purchase-orders/index.php',
    '/Applications/XAMPP/xamppfiles/htdocs/avon-sys/views/customers/view.php',
    '/Applications/XAMPP/xamppfiles/htdocs/avon-sys/views/customers/create.php',
    '/Applications/XAMPP/xamppfiles/htdocs/avon-sys/views/customers/index.php',
    '/Applications/XAMPP/xamppfiles/htdocs/avon-sys/views/customers/edit.php',
    '/Applications/XAMPP/xamppfiles/htdocs/avon-sys/profile.php',
    '/Applications/XAMPP/xamppfiles/htdocs/avon-sys/reports.php',
    '/Applications/XAMPP/xamppfiles/htdocs/avon-sys/projects/edit.php',
    '/Applications/XAMPP/xamppfiles/htdocs/avon-sys/projects/list.php',
    '/Applications/XAMPP/xamppfiles/htdocs/avon-sys/projects/add.php',
    '/Applications/XAMPP/xamppfiles/htdocs/avon-sys/invoices/edit.php',
    '/Applications/XAMPP/xamppfiles/htdocs/avon-sys/settings.php',
    '/Applications/XAMPP/xamppfiles/htdocs/avon-sys/invoices/list.php',
    '/Applications/XAMPP/xamppfiles/htdocs/avon-sys/invoices/create.php',
    '/Applications/XAMPP/xamppfiles/htdocs/avon-sys/clients/edit.php',
    '/Applications/XAMPP/xamppfiles/htdocs/avon-sys/clients/list.php',
    '/Applications/XAMPP/xamppfiles/htdocs/avon-sys/clients/add.php',
    '/Applications/XAMPP/xamppfiles/htdocs/avon-sys/payments/recurring.php'
];

// Update each file
foreach ($files as $file) {
    replaceHardcodedSidebar($file);
}

echo "All files have been updated successfully!\n";
?>
