<?php
require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/AuditLogger.php';

$auth = new AuthController($conn);

if (!$auth->isAuthenticated() || !$auth->hasRole('admin')) {
    header("Location: /avon-sys/views/auth/login.php");
    exit();
}

$logger = new AuditLogger($conn, $_SESSION['user_id']);

// Get filters
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : null;
$format = isset($_GET['format']) ? $_GET['format'] : 'csv';

// Build query
$sql = "SELECT 
            al.created_at,
            al.action_type,
            u1.username as performed_by,
            u2.username as affected_user,
            al.old_value,
            al.new_value,
            al.ip_address
        FROM audit_logs al
        JOIN users u1 ON al.user_id = u1.id
        JOIN users u2 ON al.entity_id = u2.id
        WHERE al.entity_type = 'permission'";

$params = [];
$types = "";

if ($userId) {
    $sql .= " AND al.entity_id = ?";
    $params[] = $userId;
    $types .= "i";
}

if ($startDate) {
    $sql .= " AND al.created_at >= ?";
    $params[] = $startDate . " 00:00:00";
    $types .= "s";
}

if ($endDate) {
    $sql .= " AND al.created_at <= ?";
    $params[] = $endDate . " 23:59:59";
    $types .= "s";
}

$sql .= " ORDER BY al.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Set headers for download
$filename = "permission_audit_logs_" . date('Y-m-d_His');
if ($format === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Headers
    fputcsv($output, ['Date/Time', 'Action', 'Performed By', 'Affected User', 'Changes', 'IP Address']);
    
    // Data
    foreach ($logs as $log) {
        $changes = '';
        if ($log['action_type'] === 'update') {
            $oldValue = json_decode($log['old_value'], true);
            $newValue = json_decode($log['new_value'], true);
            
            if (isset($oldValue['permissions']) && isset($newValue['permissions'])) {
                $added = array_diff($newValue['permissions'], $oldValue['permissions']);
                $removed = array_diff($oldValue['permissions'], $newValue['permissions']);
                
                if (!empty($added)) {
                    $changes .= "Added: " . implode(', ', $added) . "\n";
                }
                if (!empty($removed)) {
                    $changes .= "Removed: " . implode(', ', $removed);
                }
            }
        } elseif ($log['action_type'] === 'create') {
            $newValue = json_decode($log['new_value'], true);
            if (isset($newValue['permissions'])) {
                $changes = "Granted: " . implode(', ', $newValue['permissions']);
            }
        } elseif ($log['action_type'] === 'delete') {
            $oldValue = json_decode($log['old_value'], true);
            if (isset($oldValue['permissions'])) {
                $changes = "Revoked: " . implode(', ', $oldValue['permissions']);
            }
        }
        
        fputcsv($output, [
            $log['created_at'],
            $log['action_type'],
            $log['performed_by'],
            $log['affected_user'],
            $changes,
            $log['ip_address']
        ]);
    }
    
    fclose($output);
} else { // Excel format
    require_once '../../vendor/autoload.php'; // You'll need PhpSpreadsheet
    
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Headers
    $sheet->setCellValue('A1', 'Date/Time');
    $sheet->setCellValue('B1', 'Action');
    $sheet->setCellValue('C1', 'Performed By');
    $sheet->setCellValue('D1', 'Affected User');
    $sheet->setCellValue('E1', 'Changes');
    $sheet->setCellValue('F1', 'IP Address');
    
    // Style headers
    $headerStyle = [
        'font' => ['bold' => true],
        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['rgb' => 'EEEEEE']]
    ];
    $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);
    
    // Data
    $row = 2;
    foreach ($logs as $log) {
        $changes = '';
        if ($log['action_type'] === 'update') {
            $oldValue = json_decode($log['old_value'], true);
            $newValue = json_decode($log['new_value'], true);
            
            if (isset($oldValue['permissions']) && isset($newValue['permissions'])) {
                $added = array_diff($newValue['permissions'], $oldValue['permissions']);
                $removed = array_diff($oldValue['permissions'], $newValue['permissions']);
                
                if (!empty($added)) {
                    $changes .= "Added: " . implode(', ', $added) . "\n";
                }
                if (!empty($removed)) {
                    $changes .= "Removed: " . implode(', ', $removed);
                }
            }
        } elseif ($log['action_type'] === 'create') {
            $newValue = json_decode($log['new_value'], true);
            if (isset($newValue['permissions'])) {
                $changes = "Granted: " . implode(', ', $newValue['permissions']);
            }
        } elseif ($log['action_type'] === 'delete') {
            $oldValue = json_decode($log['old_value'], true);
            if (isset($oldValue['permissions'])) {
                $changes = "Revoked: " . implode(', ', $oldValue['permissions']);
            }
        }
        
        $sheet->setCellValue('A' . $row, $log['created_at']);
        $sheet->setCellValue('B' . $row, $log['action_type']);
        $sheet->setCellValue('C' . $row, $log['performed_by']);
        $sheet->setCellValue('D' . $row, $log['affected_user']);
        $sheet->setCellValue('E' . $row, $changes);
        $sheet->setCellValue('F' . $row, $log['ip_address']);
        
        $row++;
    }
    
    // Auto-size columns
    foreach (range('A', 'F') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // Output file
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
    header('Cache-Control: max-age=0');
    
    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save('php://output');
}
exit();
