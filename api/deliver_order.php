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
$customerOrderId = isset($_POST['customer_order_id']) ? intval($_POST['customer_order_id']) : 0;

// Validate parameters
if ($orderId <= 0 || $customerOrderId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID or customer order ID']);
    exit;
}

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Check if the order is ready to deliver and is completed
    $checkQuery = "SELECT Status, Delivery_Status FROM productionorders WHERE OrderID = ?";
    $stmt = mysqli_prepare($conn, $checkQuery);
    mysqli_stmt_bind_param($stmt, "i", $orderId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 0) {
        throw new Exception('Production order not found');
    }
    
    $row = mysqli_fetch_assoc($result);
    if ($row['Status'] !== 'Completed') {
        throw new Exception('Only completed orders can be delivered');
    }
    
    if ($row['Delivery_Status'] !== 1) {
        throw new Exception('Order is not marked as ready to deliver');
    }
    
    // Update the customer order status to "Shipped"
    $updateCustomerOrderQuery = "UPDATE customerorders SET Status = 'Shipped' WHERE CustomerOrderID = ?";
    $stmt = mysqli_prepare($conn, $updateCustomerOrderQuery);
    mysqli_stmt_bind_param($stmt, "i", $customerOrderId);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to update customer order status: ' . mysqli_error($conn));
    }
    
    // Create a shipment record if the shipments table is being used
    if ($conn->query("SHOW TABLES LIKE 'shipments'")->num_rows > 0) {
        // Get the first carrier ID for demo purposes, or assign a default
        $carrierQuery = "SELECT CarrierID FROM carriers LIMIT 1";
        $carrierResult = mysqli_query($conn, $carrierQuery);
        
        if (mysqli_num_rows($carrierResult) > 0) {
            $carrierId = mysqli_fetch_assoc($carrierResult)['CarrierID'];
        } else {
            // If no carriers exist, you might want to create one or handle this differently
            $carrierId = 1; // Default carrier ID
        }
        
        // Generate a random tracking number
        $trackingNumber = 'TRK' . rand(100000, 999999);
        
        $shipmentQuery = "INSERT INTO shipments (CustomerOrderID, CarrierID, ShipmentDate, TrackingNumber, Status) 
                           VALUES (?, ?, NOW(), ?, 'In Transit')";
        $stmt = mysqli_prepare($conn, $shipmentQuery);
        mysqli_stmt_bind_param($stmt, "iis", $customerOrderId, $carrierId, $trackingNumber);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to create shipment record: ' . mysqli_error($conn));
        }
    }
    
    // Log the action
    $employeeId = $_SESSION['employee_id'];
    $action = "delivered order #$orderId to customer order #$customerOrderId";
    
    // Assuming you have an activity_log table, otherwise remove this part
    if ($conn->query("SHOW TABLES LIKE 'activity_log'")->num_rows > 0) {
        $logQuery = "INSERT INTO activity_log (employee_id, action, timestamp) VALUES (?, ?, NOW())";
        $logStmt = mysqli_prepare($conn, $logQuery);
        mysqli_stmt_bind_param($logStmt, "is", $employeeId, $action);
        mysqli_stmt_execute($logStmt);
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    echo json_encode(['success' => true, 'message' => 'Order successfully delivered']);
    
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

mysqli_close($conn);
?>