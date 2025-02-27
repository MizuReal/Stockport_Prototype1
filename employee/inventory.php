<?php
require_once 'session_check.php';
requireActiveLogin();
require_once '../employee/session_check.php';
require_once '../layouts/employeeSidebar.php';
require_once '../layouts/employeeHeader.php';

// Database connection
$servername = "localhost";
$username = "root"; // Change if different
$password = ""; // Change if different
$dbname = "stockport";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get inventory counts and stats
$totalProductsQuery = "SELECT SUM(QuantityInStock) as total_stock FROM rawmaterials";
$totalProductsResult = $conn->query($totalProductsQuery);
$totalStock = 0;
if ($totalProductsResult && $row = $totalProductsResult->fetch_assoc()) {
    $totalStock = $row['total_stock'];
}

// Get low stock alerts
$lowStockQuery = "SELECT COUNT(*) as low_stock_count FROM rawmaterials WHERE QuantityInStock < MinimumStock";
$lowStockResult = $conn->query($lowStockQuery);
$lowStockCount = 0;
if ($lowStockResult && $row = $lowStockResult->fetch_assoc()) {
    $lowStockCount = $row['low_stock_count'];
}

// Get pending order counts
$processingQuery = "SELECT COUNT(*) as processing_count FROM productionorders WHERE Status = 'In Progress'";
$processingResult = $conn->query($processingQuery);
$processingCount = 0;
if ($processingResult && $row = $processingResult->fetch_assoc()) {
    $processingCount = $row['processing_count'];
}

$shippedQuery = "SELECT COUNT(*) as shipped_count FROM productionorders WHERE Status = 'Completed' AND Delivery_Status = 0";
$shippedResult = $conn->query($shippedQuery);
$shippedCount = 0;
if ($shippedResult && $row = $shippedResult->fetch_assoc()) {
    $shippedCount = $row['shipped_count'];
}

// Get low stock alerts for display
$lowStockItemsQuery = "SELECT MaterialName, QuantityInStock, MinimumStock FROM rawmaterials WHERE QuantityInStock < MinimumStock LIMIT 3";
$lowStockItemsResult = $conn->query($lowStockItemsQuery);

// Get recent orders
$recentOrdersQuery = "SELECT p.OrderID, c.CustomerName, p.Status, p.StartDate, p.ProductID 
                      FROM productionorders p 
                      JOIN customers c ON p.EmployeeID = c.CustomerID 
                      ORDER BY p.StartDate DESC LIMIT 5";
$recentOrdersResult = $conn->query($recentOrdersQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/eminventory.css">
    <title>Inventory Management</title>
</head>
<body>
    <div class="container">
        <?php renderSidebar('inventory'); ?>
        
        <div class="main-content">
            <?php renderHeader('Inventory Management'); ?>
        
            <!-- Quick Stats -->
            <div class="dashboard-grid">
                <div class="card">
                    <div class="card-header">Current Inventory Status</div>
                    <div class="stat-grid">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo number_format($totalStock); ?></div>
                            <div class="stat-label">Total Raw Material Stock</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $lowStockCount; ?></div>
                            <div class="stat-label">Low Stock Alerts</div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">Pending Orders</div>
                    <div class="stat-grid">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $processingCount; ?></div>
                            <div class="stat-label">Processing</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $shippedCount; ?></div>
                            <div class="stat-label">Ready to Ship</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Alerts Section -->
            <div class="card">
                <div class="card-header">Low Stock Alerts</div>
                <?php
                if ($lowStockItemsResult && $lowStockItemsResult->num_rows > 0) {
                    while ($row = $lowStockItemsResult->fetch_assoc()) {
                        echo '<div class="alert">';
                        echo 'Low stock alert: ' . $row['MaterialName'] . ' - Current: ' . 
                             $row['QuantityInStock'] . ', Minimum required: ' . $row['MinimumStock'];
                        echo '</div>';
                    }
                } else {
                    echo '<div class="alert">No low stock alerts at this time.</div>';
                }
                ?>
            </div>
            
            <!-- Raw Materials Inventory Table -->
            <div class="card">
                <div class="card-header">Raw Materials Inventory</div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Material ID</th>
                            <th>Material Name</th>
                            <th>Current Stock</th>
                            <th>Min Stock</th>
                            <th>Supplier</th>
                            <th>Warehouse</th>
                            <th>Last Restocked</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $materialsQuery = "SELECT r.MaterialID, r.MaterialName, r.QuantityInStock, 
                                      r.MinimumStock, s.SupplierName, r.raw_warehouse, r.LastRestockedDate 
                                      FROM rawmaterials r
                                      JOIN suppliers s ON r.SupplierID = s.SupplierID
                                      ORDER BY r.QuantityInStock < r.MinimumStock DESC, r.MaterialID ASC";
                    $materialsResult = $conn->query($materialsQuery);
                    
                    if ($materialsResult && $materialsResult->num_rows > 0) {
                        while ($row = $materialsResult->fetch_assoc()) {
                            $rowClass = $row['QuantityInStock'] < $row['MinimumStock'] ? 'class="low-stock"' : '';
                            echo "<tr $rowClass>";
                            echo "<td>" . $row['MaterialID'] . "</td>";
                            echo "<td>" . $row['MaterialName'] . "</td>";
                            echo "<td>" . $row['QuantityInStock'] . "</td>";
                            echo "<td>" . $row['MinimumStock'] . "</td>";
                            echo "<td>" . $row['SupplierName'] . "</td>";
                            echo "<td>" . $row['raw_warehouse'] . "</td>";
                            echo "<td>" . $row['LastRestockedDate'] . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7'>No materials found</td></tr>";
                    }
                    ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Products Inventory Table -->
            <div class="card">
                <div class="card-header">Products Inventory</div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product ID</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Main Material</th>
                            <th>Min Quantity</th>
                            <th>Warehouse</th>
                            <th>Price</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $productsQuery = "SELECT p.ProductID, p.ProductName, p.Category, r.MaterialName, 
                                     p.minimum_quantity, w.productWarehouse, p.SellingPrice
                                     FROM products p
                                     JOIN rawmaterials r ON p.MaterialID = r.MaterialID
                                     JOIN products_warehouse w ON p.LocationID = w.productLocationID
                                     ORDER BY p.ProductID ASC";
                    $productsResult = $conn->query($productsQuery);
                    
                    if ($productsResult && $productsResult->num_rows > 0) {
                        while ($row = $productsResult->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $row['ProductID'] . "</td>";
                            echo "<td>" . $row['ProductName'] . "</td>";
                            echo "<td>" . $row['Category'] . "</td>";
                            echo "<td>" . $row['MaterialName'] . "</td>";
                            echo "<td>" . $row['minimum_quantity'] . "</td>";
                            echo "<td>" . $row['productWarehouse'] . "</td>";
                            echo "<td>$" . number_format($row['SellingPrice'], 2) . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7'>No products found</td></tr>";
                    }
                    ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Recent Orders Table -->
            <div class="card">
                <div class="card-header">Recent Production Orders</div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Product</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Quantity</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $ordersQuery = "SELECT po.OrderID, p.ProductName, po.Status, po.StartDate, 
                                   po.QuantityOrdered, po.QuantityProduced
                                   FROM productionorders po
                                   JOIN products p ON po.ProductID = p.ProductID
                                   ORDER BY po.StartDate DESC LIMIT 5";
                    $ordersResult = $conn->query($ordersQuery);
                    
                    if ($ordersResult && $ordersResult->num_rows > 0) {
                        while ($row = $ordersResult->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>ORD-" . sprintf('%03d', $row['OrderID']) . "</td>";
                            echo "<td>" . $row['ProductName'] . "</td>";
                            echo "<td>" . $row['Status'] . "</td>";
                            echo "<td>" . date('Y-m-d', strtotime($row['StartDate'])) . "</td>";
                            echo "<td>" . $row['QuantityProduced'] . "/" . $row['QuantityOrdered'] . "</td>";
                            echo "<td><a href='view_order.php?id=" . $row['OrderID'] . "' class='btn'>View</a></td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6'>No recent orders found</td></tr>";
                    }
                    
                    // Close the database connection
                    $conn->close();
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>