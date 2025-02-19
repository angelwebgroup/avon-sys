<?php
function addFontAwesome($filePath) {
    $content = file_get_contents($filePath);
    
    // Check if Font Awesome is already included
    if (strpos($content, 'font-awesome') === false) {
        // Add Font Awesome CSS after Bootstrap CSS
        $content = str_replace(
            'bootstrap.min.css" rel="stylesheet">',
            'bootstrap.min.css" rel="stylesheet">' . PHP_EOL . 
            '    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">',
            $content
        );
    }
    
    // Replace Bootstrap Icons with Font Awesome
    $replacements = [
        'bi bi-plus-circle' => 'fas fa-plus-circle',
        'bi bi-search' => 'fas fa-search',
        'bi bi-person' => 'fas fa-user',
        'bi bi-envelope' => 'fas fa-envelope',
        'bi bi-telephone' => 'fas fa-phone',
        'bi bi-eye' => 'fas fa-eye',
        'bi bi-pencil' => 'fas fa-pencil-alt',
        'bi bi-upload' => 'fas fa-upload',
        'bi bi-clock-history' => 'fas fa-clock',
        'bi bi-trash' => 'fas fa-trash',
        'bi bi-download' => 'fas fa-download',
        'bi bi-gear' => 'fas fa-cog',
        'bi bi-box-arrow-right' => 'fas fa-sign-out-alt',
        'bi bi-people' => 'fas fa-users',
        'bi bi-file-text' => 'fas fa-file-alt',
        'bi bi-cart' => 'fas fa-shopping-cart',
        'bi bi-question-circle' => 'fas fa-question-circle',
        'bi bi-shield-lock' => 'fas fa-shield-alt',
        'bi bi-bell' => 'fas fa-bell',
        'bi bi-graph-up' => 'fas fa-chart-line'
    ];
    
    foreach ($replacements as $bootstrap => $fontawesome) {
        $content = str_replace($bootstrap, $fontawesome, $content);
    }
    
    // Remove Bootstrap Icons CSS if present
    $content = preg_replace('/<link[^>]*bootstrap-icons[^>]*>/', '', $content);
    
    // Save the changes
    file_put_contents($filePath, $content);
    echo "Updated: $filePath\n";
}

// Get all PHP files recursively
function getAllPhpFiles($dir) {
    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }
    return $files;
}

// Get all PHP files
$baseDir = '/Applications/XAMPP/xamppfiles/htdocs/avon-sys';
$files = getAllPhpFiles($baseDir);

// Update each file
foreach ($files as $file) {
    // Skip the script itself
    if (basename($file) !== 'update_icons.php') {
        addFontAwesome($file);
    }
}

echo "All files have been updated successfully!\n";
?>
