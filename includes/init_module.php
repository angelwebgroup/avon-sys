<?php
/**
 * Module Initialization
 * 
 * This file should be included at the start of each module's pages
 * to handle module-specific initialization and error handling.
 */

require_once __DIR__ . '/ModuleManager.php';

function initModule($moduleName) {
    $moduleManager = ModuleManager::getInstance();
    $moduleManager->setCurrentModule($moduleName);
    
    // Check if module is accessible
    if (!$moduleManager->isModuleAccessible()) {
        if (isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'error' => true,
                'message' => $moduleManager->getMaintenanceMessage()
            ]);
        } else {
            include __DIR__ . '/maintenance_template.php';
        }
        exit();
    }
    
    return $moduleManager;
}

function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}
