<?php
session_start();
require_once '../session_check.php';
requireActiveLogin();
require_once '../server/database.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get parameters
$orderId = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
$status = isset($_POST['status']) ? intval($_POST['status']) : 0;

// Validate parameters
if ($orderId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

// Check if the order exists and is in the "Completed" status
$checkQuery = "SELECT Status FROM productionorders WHERE OrderID = ?";
$stmt = mysqli_prepare($conn, $checkQuery);
mysqli_stmt_bind_param($stmt, "i", $orderId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit;
}

$row = mysqli_fetch_assoc($result);
if ($row['Status'] !== 'Completed' && $status === 1) {
    echo json_encode(['success' => false, 'message' => 'Only completed orders can be marked as ready to deliver']);
    exit;
}

// Update the delivery status
$updateQuery = "UPDATE productionorders SET Delivery_Status = ? WHERE OrderID = ?";
$stmt = mysqli_prepare($conn, $updateQuery);
mysqli_stmt_bind_param($stmt, "ii", $status, $orderId);

if (mysqli_stmt_execute($stmt)) {
    // Log the action
    $employeeId = $_SESSION['employee_id'];
    $action = $status === 1 ? "marked order #$orderId as ready to deliver" : "marked order #$orderId as not ready to deliver";
    $logQuery = "INSERT INTO activity_log (employee_id, action, timestamp) VALUES (?, ?, NOW())";
    
    // Assuming you have an activity_log table, otherwise remove this part
    if ($conn->query("SHOW TABLES LIKE 'activity_log'")->num_rows > 0) {
        $logStmt = mysqli_prepare($conn, $logQuery);
        mysqli_stmt_bind_param($logStmt, "is", $employeeId, $action);
        mysqli_stmt_execute($logStmt);
    }
    
    echo json_encode(['success' => true, 'message' => 'Delivery status updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
}

mysqli_close($conn);
?>