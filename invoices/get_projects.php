<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['client_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Client ID required']);
    exit();
}

$client_id = mysqli_real_escape_string($conn, $_GET['client_id']);

$sql = "SELECT id, name FROM projects WHERE client_id = $client_id ORDER BY name";
$result = $conn->query($sql);

$projects = [];
while ($row = $result->fetch_assoc()) {
    $projects[] = [
        'id' => $row['id'],
        'name' => $row['name']
    ];
}

header('Content-Type: application/json');
echo json_encode($projects);
