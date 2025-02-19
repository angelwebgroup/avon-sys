<?php
/**
 * Customer Module Logger
 * 
 * Handles logging for the customer module with different log levels
 * and detailed context information.
 */

class CustomerLogger {
    private $logFile;
    private $module = 'customers';
    private static $instance = null;
    
    private function __construct() {
        $config = require __DIR__ . '/../config/maintenance.php';
        $this->logFile = $config['log_path'] . 'customer_module.log';
        
        // Create logs directory if it doesn't exist
        if (!is_dir($config['log_path'])) {
            mkdir($config['log_path'], 0755, true);
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Log an error message
     */
    public function error($message, array $context = []) {
        $this->log('ERROR', $message, $context);
    }
    
    /**
     * Log a debug message
     */
    public function debug($message, array $context = []) {
        $config = require __DIR__ . '/../config/maintenance.php';
        if ($config['modules']['customers']['development']) {
            $this->log('DEBUG', $message, $context);
        }
    }
    
    /**
     * Log an info message
     */
    public function info($message, array $context = []) {
        $this->log('INFO', $message, $context);
    }
    
    /**
     * Log a database query
     */
    public function query($sql, array $params = []) {
        $config = require __DIR__ . '/../config/maintenance.php';
        if ($config['modules']['customers']['log_queries']) {
            $this->log('QUERY', $sql, ['params' => $params]);
        }
    }
    
    /**
     * Log a message with the specified level
     */
    private function log($level, $message, array $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $contextJson = !empty($context) ? json_encode($context) : '';
        
        $logMessage = sprintf(
            "[%s] [%s] [%s] %s %s\n",
            $timestamp,
            $level,
            $this->module,
            $message,
            $contextJson
        );
        
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }
    
    /**
     * Get recent log entries
     */
    public function getRecentLogs($lines = 100) {
        if (!file_exists($this->logFile)) {
            return [];
        }
        
        $logs = [];
        $file = new SplFileObject($this->logFile, 'r');
        $file->seek(PHP_INT_MAX);
        $lastLine = $file->key();
        
        $start = max(0, $lastLine - $lines);
        
        for ($i = $start; $i <= $lastLine; $i++) {
            $file->seek($i);
            $line = $file->current();
            if ($line) {
                $logs[] = $line;
            }
        }
        
        return $logs;
    }
    
    /**
     * Clear the log file
     */
    public function clearLogs() {
        if (file_exists($this->logFile)) {
            file_put_contents($this->logFile, '');
        }
    }
}
