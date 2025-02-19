<?php
/**
 * Module Manager Class
 * 
 * Handles module-level operations, maintenance modes, and error handling
 */

class ModuleManager {
    private static $instance = null;
    private $config;
    private $currentModule;
    
    private function __construct() {
        $this->config = require_once __DIR__ . '/../config/maintenance.php';
        $this->setErrorHandling();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function setCurrentModule($module) {
        $this->currentModule = $module;
    }
    
    public function isModuleAccessible() {
        if (!isset($this->config['modules'][$this->currentModule])) {
            return true; // Module not configured, assume accessible
        }
        
        $moduleConfig = $this->config['modules'][$this->currentModule];
        
        if (!$moduleConfig['maintenance']) {
            return true;
        }
        
        // Check if current IP is allowed during maintenance
        $clientIP = $_SERVER['REMOTE_ADDR'];
        return in_array($clientIP, $moduleConfig['allowed_ips']);
    }
    
    public function getMaintenanceMessage() {
        return $this->config['modules'][$this->currentModule]['message'] ?? 'Module is under maintenance';
    }
    
    private function setErrorHandling() {
        if ($this->config['error_logging']) {
            error_reporting(E_ALL);
            ini_set('log_errors', 1);
            ini_set('error_log', __DIR__ . '/../logs/module_errors.log');
        }
        
        ini_set('display_errors', $this->config['error_display'] ? 1 : 0);
        
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
    }
    
    public function handleError($errno, $errstr, $errfile, $errline) {
        if (!(error_reporting() & $errno)) {
            return false; // Error not included in error_reporting
        }
        
        $errorType = $this->getErrorType($errno);
        $message = "$errorType: $errstr in $errfile on line $errline";
        
        // Log the error
        error_log($message);
        
        if ($this->config['error_display']) {
            echo $this->formatErrorMessage($message);
        } else {
            echo $this->formatErrorMessage('An error occurred. Please try again later.');
        }
        
        return true;
    }
    
    public function handleException($exception) {
        $message = "Exception: " . $exception->getMessage() . 
                  " in " . $exception->getFile() . 
                  " on line " . $exception->getLine();
        
        // Log the exception
        error_log($message);
        
        if ($this->config['error_display']) {
            echo $this->formatErrorMessage($message);
        } else {
            echo $this->formatErrorMessage('An error occurred. Please try again later.');
        }
    }
    
    private function getErrorType($errno) {
        switch ($errno) {
            case E_ERROR:
                return 'Fatal Error';
            case E_WARNING:
                return 'Warning';
            case E_PARSE:
                return 'Parse Error';
            case E_NOTICE:
                return 'Notice';
            default:
                return 'Unknown Error';
        }
    }
    
    private function formatErrorMessage($message) {
        if ($this->isAjaxRequest()) {
            return json_encode(['error' => true, 'message' => $message]);
        }
        
        return "<div class='alert alert-danger'>$message</div>";
    }
    
    private function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }
    
    public function logModuleError($message, $context = []) {
        $logMessage = "[" . date('Y-m-d H:i:s') . "] " . 
                     "[{$this->currentModule}] " . 
                     $message . " " . 
                     (!empty($context) ? json_encode($context) : '');
        
        error_log($logMessage . PHP_EOL, 3, __DIR__ . '/../logs/module_errors.log');
    }
}
