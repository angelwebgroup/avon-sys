<?php
require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/InquiryController.php';

$auth = new AuthController($conn);
$inquiryController = new InquiryController($conn, $auth);

if (!$auth->isAuthenticated()) {
    header("Location: /avon-sys/views/auth/login.php");
    exit();
}

$action = $_POST['action'] ?? '';
$response = ['success' => false, 'error' => 'Invalid action'];

switch ($action) {
    case 'create':
        if (!$auth->hasPermission('create_inquiries')) {
            $response = ['success' => false, 'error' => 'Permission denied'];
            break;
        }

        $inquiryData = [
            'company_name' => $_POST['company_name'] ?? '',
            'contact_person' => $_POST['contact_person'] ?? '',
            'email' => $_POST['email'] ?? '',
            'mobile_no' => $_POST['mobile_no'] ?? '',
            'subject' => $_POST['subject'] ?? '',
            'message' => $_POST['message'] ?? ''
        ];

        // Validate required fields
        $requiredFields = ['company_name', 'contact_person', 'email', 'mobile_no', 'subject', 'message'];
        $errors = [];
        
        foreach ($requiredFields as $field) {
            if (empty($inquiryData[$field])) {
                $errors[] = ucwords(str_replace('_', ' ', $field)) . ' is required';
            }
        }

        if (!empty($errors)) {
            $response = ['success' => false, 'error' => implode(', ', $errors)];
            break;
        }

        // Validate email
        if (!filter_var($inquiryData['email'], FILTER_VALIDATE_EMAIL)) {
            $response = ['success' => false, 'error' => 'Invalid email address'];
            break;
        }

        $result = $inquiryController->createInquiry($inquiryData);
        if ($result['success']) {
            header("Location: view.php?id=" . $result['inquiry_id'] . "&success=Inquiry created successfully");
            exit();
        }
        $response = $result;
        break;

    case 'update':
        if (!$auth->hasPermission('edit_inquiries')) {
            $response = ['success' => false, 'error' => 'Permission denied'];
            break;
        }

        $id = $_POST['id'] ?? 0;
        if (!$id) {
            $response = ['success' => false, 'error' => 'Invalid inquiry ID'];
            break;
        }

        $inquiryData = [
            'company_name' => $_POST['company_name'] ?? '',
            'contact_person' => $_POST['contact_person'] ?? '',
            'email' => $_POST['email'] ?? '',
            'mobile_no' => $_POST['mobile_no'] ?? '',
            'subject' => $_POST['subject'] ?? '',
            'message' => $_POST['message'] ?? '',
            'status' => $_POST['status'] ?? 'new'
        ];

        // Validate required fields
        $requiredFields = ['company_name', 'contact_person', 'email', 'mobile_no', 'subject', 'message'];
        $errors = [];
        
        foreach ($requiredFields as $field) {
            if (empty($inquiryData[$field])) {
                $errors[] = ucwords(str_replace('_', ' ', $field)) . ' is required';
            }
        }

        if (!empty($errors)) {
            $response = ['success' => false, 'error' => implode(', ', $errors)];
            break;
        }

        // Validate email
        if (!filter_var($inquiryData['email'], FILTER_VALIDATE_EMAIL)) {
            $response = ['success' => false, 'error' => 'Invalid email address'];
            break;
        }

        $result = $inquiryController->updateInquiry($id, $inquiryData);
        if ($result['success']) {
            header("Location: view.php?id=" . $id . "&success=Inquiry updated successfully");
            exit();
        }
        $response = $result;
        break;

    default:
        $response = ['success' => false, 'error' => 'Invalid action'];
}

// If we reach here, there was an error
header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php') . "?error=" . urlencode($response['error']));
exit();
