<?php
session_start();
require_once 'session_check.php'; // Adjust path as needed
requireActiveLogin(); // This ensures user is logged in AND has Active status
require_once '../layouts/employeeSidebar.php';
require_once '../layouts/employeeHeader.php';
require_once '../server/database.php'; // Make sure to include database connection
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/eminventory.css">
    <title>Client Order Tracker</title>
    <style>
        .order-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .order-table th, .order-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .order-table th {
            background-color: #f2f2f2;
            position: sticky;
            top: 0;
        }
        .order-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .order-table tr:hover {
            background-color: #f1f1f1;
        }
        .product-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
        }
        .status-badge {
            padding: 5px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-planned {
            background-color: #FFD700;
            color: #000;
        }
        .status-progress {
            background-color: #1E90FF;
            color: #fff;
        }
        .status-completed {
            background-color: #32CD32;
            color: #fff;
        }
        .status-cancelled {
            background-color: #FF6347;
            color: #fff;
        }
        .btn {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin: 2px;
        }
        .btn-view {
            background-color: #4CAF50;
            color: white;
        }
        .btn-edit {
            background-color: #2196F3;
            color: white;
        }
        .btn-deliver {
            background-color: #FF9800;
            color: white;
        }
        .search-container {
            margin: 20px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .search-box {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 250px;
        }
        .filter-select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .checkbox-center {
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php renderSidebar('clientOrderTracker'); // Note different active page ?>
        
        <div class="main-content">
            <?php renderHeader('Client Order Tracker'); ?>

            <div class="search-container">
                <input type="text" id="searchInput" class="search-box" placeholder="Search orders...">
                <div>
                    <select id="statusFilter" class="filter-select">
                        <option value="">All Statuses</option>
                        <option value="Planned">Planned</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                    <select id="customerFilter" class="filter-select">
                        <option value="">All Customers</option>
                        <?php
                        // Get all customers for dropdown
                        $customerQuery = "SELECT CustomerID, CustomerName FROM customers ORDER BY CustomerName";
                        $customerResult = mysqli_query($conn, $customerQuery);
                        while ($customer = mysqli_fetch_assoc($customerResult)) {
                            echo "<option value='{$customer['CustomerID']}'>{$customer['CustomerName']}</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table class="order-table" id="orderTable">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Ordered By</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                            <th>Quantity Ordered</th>
                            <th>Quantity Processed</th>
                            <th>Ready to Deliver</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Query to get all the necessary data from multiple tables
                        $query = "
                            SELECT 
                                po.OrderID, 
                                p.ProductID, 
                                p.ProductName, 
                                p.product_img, 
                                co.CustomerOrderID,
                                c.CustomerID,
                                c.CustomerName, 
                                po.StartDate, 
                                po.EndDate, 
                                po.Status, 
                                po.QuantityOrdered, 
                                po.QuantityProduced, 
                                po.Delivery_Status
                            FROM productionorders po
                            INNER JOIN products p ON po.ProductID = p.ProductID
                            INNER JOIN orderdetails od ON p.ProductID = od.ProductID
                            INNER JOIN customerorders co ON od.CustomerOrderID = co.CustomerOrderID
                            INNER JOIN customers c ON co.CustomerID = c.CustomerID
                            ORDER BY po.StartDate DESC
                        ";
                        
                        $result = mysqli_query($conn, $query);
                        
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                // Determine status class for styling
                                $statusClass = '';
                                switch ($row['Status']) {
                                    case 'Planned':
                                        $statusClass = 'status-planned';
                                        break;
                                    case 'In Progress':
                                        $statusClass = 'status-progress';
                                        break;
                                    case 'Completed':
                                        $statusClass = 'status-completed';
                                        break;
                                    case 'Cancelled':
                                        $statusClass = 'status-cancelled';
                                        break;
                                }
                                
                                // Format dates
                                $startDate = date('M d, Y', strtotime($row['StartDate']));
                                $endDate = date('M d, Y', strtotime($row['EndDate']));
                                
                                echo "<tr data-customer='{$row['CustomerID']}' data-status='{$row['Status']}'>";
                                echo "<td>
                                        <div style='display: flex; align-items: center;'>
                                            <img src='../assets/imgs/{$row['product_img']}' alt='{$row['ProductName']}' class='product-img'>
                                            <span style='margin-left: 10px;'>{$row['ProductName']}</span>
                                        </div>
                                      </td>";
                                echo "<td>{$row['CustomerName']}</td>";
                                echo "<td>{$startDate}</td>";
                                echo "<td>{$endDate}</td>";
                                echo "<td><span class='status-badge {$statusClass}'>{$row['Status']}</span></td>";
                                echo "<td>{$row['QuantityOrdered']}</td>";
                                echo "<td>{$row['QuantityProduced']}</td>";
                                echo "<td class='checkbox-center'>";
                                
                                // Checkbox for ready to deliver, disabled if not completed
                                $isReadyToDeliver = $row['Delivery_Status'] == 1;
                                $isCompleted = $row['Status'] == 'Completed';
                                $isDisabled = !$isCompleted ? 'disabled' : '';
                                $isChecked = $isReadyToDeliver ? 'checked' : '';
                                
                                echo "<input type='checkbox' class='delivery-status' 
                                      data-order-id='{$row['OrderID']}' 
                                      {$isChecked} {$isDisabled} 
                                      onchange='updateDeliveryStatus(this, {$row['OrderID']})'/>";
                                echo "</td>";
                                echo "<td>
                                        <button class='btn btn-view' onclick='viewOrder({$row['OrderID']})'>View</button>
                                        <button class='btn btn-edit' onclick='editOrder({$row['OrderID']})'>Edit</button>";
                                
                                // Only show deliver button if order is completed and ready to deliver
                                if ($isCompleted && $isReadyToDeliver) {
                                    echo "<button class='btn btn-deliver' onclick='deliverOrder({$row['OrderID']}, {$row['CustomerOrderID']})'>Deliver</button>";
                                }
                                
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='9' style='text-align: center;'>No orders found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            filterTable();
        });
        
        // Status filter
        document.getElementById('statusFilter').addEventListener('change', function() {
            filterTable();
        });
        
        // Customer filter
        document.getElementById('customerFilter').addEventListener('change', function() {
            filterTable();
        });
        
        function filterTable() {
            const searchValue = document.getElementById('searchInput').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;
            const customerFilter = document.getElementById('customerFilter').value;
            const rows = document.getElementById('orderTable').getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                const textContent = row.textContent.toLowerCase();
                const rowStatus = row.getAttribute('data-status');
                const rowCustomer = row.getAttribute('data-customer');
                
                const statusMatch = !statusFilter || rowStatus === statusFilter;
                const customerMatch = !customerFilter || rowCustomer === customerFilter;
                const textMatch = textContent.includes(searchValue);
                
                if (textMatch && statusMatch && customerMatch) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        }
        
        // Update delivery status
        function updateDeliveryStatus(checkbox, orderId) {
            const status = checkbox.checked ? 1 : 0;
            
            fetch('../api/update_delivery_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'order_id=' + orderId + '&status=' + status
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Refresh the page or update UI as needed
                    location.reload();
                } else {
                    alert('Failed to update delivery status: ' + data.message);
                    checkbox.checked = !checkbox.checked; // Revert the checkbox
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating delivery status');
                checkbox.checked = !checkbox.checked; // Revert the checkbox
            });
        }
        
        // View order details
        function viewOrder(orderId) {
            window.location.href = 'viewOrderDetails.php?id=' + orderId;
        }
        
        // Edit order
        function editOrder(orderId) {
            window.location.href = 'editOrder.php?id=' + orderId;
        }
        
        // Deliver order (update status to delivered)
        function deliverOrder(orderId, customerOrderId) {
            if (confirm('Are you sure you want to mark this order as delivered?')) {
                fetch('../api/deliver_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'order_id=' + orderId + '&customer_order_id=' + customerOrderId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Order successfully marked as delivered!');
                        location.reload();
                    } else {
                        alert('Failed to deliver order: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while processing the delivery');
                });
            }
        }
    </script>
</body>
</html>