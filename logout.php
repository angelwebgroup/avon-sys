<?php
require_once 'controllers/AuthController.php';
require_once 'config/database.php';

$auth = new AuthController($conn);
$auth->logout();
