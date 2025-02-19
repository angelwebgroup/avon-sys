<?php
/**
 * Module-level Maintenance Configuration
 * 
 * This file manages maintenance modes for individual modules
 * allowing for module-specific downtime without affecting the entire application.
 */

return [
    'modules' => [
        'customers' => [
            'maintenance' => false,
            'message' => 'Customer module is under maintenance. Please try again later.',
            'allowed_ips' => ['127.0.0.1'],
            'development' => true, // Enable development mode for customers module
            'log_queries' => true, // Log database queries for debugging
            'features' => [
                'bulk_operations' => true,
                'document_upload' => true,
                'activity_logging' => true,
                'export' => true
            ]
        ],
        'inquiries' => [
            'maintenance' => false,
            'message' => 'Inquiry module is under maintenance. Please try again later.',
            'allowed_ips' => ['127.0.0.1']
        ],
        'purchase_orders' => [
            'maintenance' => false,
            'message' => 'Purchase Orders module is under maintenance. Please try again later.',
            'allowed_ips' => ['127.0.0.1']
        ],
        'quotes' => [
            'maintenance' => false,
            'message' => 'Quotes module is under maintenance. Please try again later.',
            'allowed_ips' => ['127.0.0.1']
        ]
    ],
    'error_logging' => true,
    'error_display' => true, // Show detailed errors in development
    'debug' => true, // Enable debug mode
    'log_path' => __DIR__ . '/../logs/',
    'customer_module' => [
        'pagination' => [
            'per_page_options' => [10, 25, 50, 100],
            'default_per_page' => 10
        ],
        'validations' => [
            'company_name' => ['required', 'max:100'],
            'contact_person' => ['required', 'max:100'],
            'email' => ['required', 'email', 'max:100'],
            'mobile_no' => ['required', 'max:20'],
            'telephone_no' => ['max:20'],
            'address' => ['required', 'max:500'],
            'city' => ['required', 'max:100'],
            'state_code' => ['required', 'max:2'],
            'pin_code' => ['required', 'max:10'],
            'gst_registration_no' => ['max:20'],
            'pan_no' => ['max:20'],
            'credit_limit' => ['numeric', 'min:0']
        ],
        'allowed_document_types' => [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ],
        'max_upload_size' => 5 * 1024 * 1024 // 5MB
    ]
];
