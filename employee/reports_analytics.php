<?php
require_once 'session_check.php'; // Adjust path as needed
requireActiveLogin(); // This ensures user is logged in AND has Active status
include '../server/database.php';
require_once '../layouts/employeeSidebar.php';
require_once '../layouts/employeeHeader.php';

// Fetch raw materials analytics
$rawMaterialsQuery = "SELECT 
    MaterialName,
    QuantityInStock,
    MinimumStock,
    UnitCost,
    (QuantityInStock * UnitCost) as TotalValue,
    LastRestockedDate,
    raw_warehouse
    FROM rawmaterials";
$rawMaterialsResult = $conn->query($rawMaterialsQuery);

// Fetch production orders analytics with correct join
$productionQuery = "SELECT 
    p.ProductName,
    po.Status,
    COUNT(*) as OrderCount,
    SUM(po.QuantityOrdered) as TotalOrdered,
    SUM(po.QuantityProduced) as TotalProduced
    FROM productionorders po
    JOIN products p ON po.ProductID = p.ProductID
    GROUP BY p.ProductName, po.Status";
$productionResult = $conn->query($productionQuery);

// Fetch customer orders analytics - Note: No customer orders in current DB
$orderQuery = "SELECT 
    Status,
    COUNT(*) as OrderCount,
    SUM(TotalAmount) as TotalRevenue
    FROM customerorders
    GROUP BY Status";
$orderResult = $conn->query($orderQuery);

// Fetch product inventory analytics - Using actual minimum_quantity field
$inventoryQuery = "SELECT 
    p.ProductID,
    p.ProductName,
    p.Category,
    p.minimum_quantity as Quantity,
    p.Weight,
    p.weight_unit,
    pw.productWarehouse,
    pw.Section,
    (p.minimum_quantity * p.ProductionCost) as InventoryValue
    FROM products p
    JOIN products_warehouse pw ON p.LocationID = pw.productLocationID
    ORDER BY p.Category, p.ProductName";
$inventoryResult = $conn->query($inventoryQuery);

// Calculate active orders count
$activeOrdersQuery = "SELECT COUNT(*) as activeOrders FROM productionorders WHERE Status = 'In Progress' OR Status = 'Planned'";
$activeOrdersResult = $conn->query($activeOrdersQuery);
$activeOrdersData = $activeOrdersResult->fetch_assoc();
$activeOrders = $activeOrdersData['activeOrders'];

// Get total product inventory value
$productValueQuery = "SELECT SUM(minimum_quantity * ProductionCost) as TotalValue FROM products";
$productValueResult = $conn->query($productValueQuery);
$productValueData = $productValueResult->fetch_assoc();
$productInventoryValue = $productValueData['TotalValue'] ?: 0;

// Check low stock products - using product-specific thresholds
$lowStockProductsQuery = "SELECT COUNT(*) as lowStockCount FROM products WHERE minimum_quantity <= 100";
$lowStockProductsResult = $conn->query($lowStockProductsQuery);
$lowStockProductsData = $lowStockProductsResult->fetch_assoc();
$lowStockProducts = $lowStockProductsData['lowStockCount'];

// Get products by category for category distribution chart
$categoryQuery = "SELECT Category, COUNT(*) as ProductCount FROM products GROUP BY Category ORDER BY ProductCount DESC";
$categoryResult = $conn->query($categoryQuery);

// Update warehouse distribution query to use current usage and capacity
$warehouseQuery = "SELECT 
    pw.productWarehouse,
    pw.Capacity,
    pw.current_usage,
    pw.warehouse_weight_unit,
    (pw.current_usage / pw.Capacity * 100) as usage_percentage
    FROM products_warehouse pw
    ORDER BY pw.productWarehouse";
$warehouseResult = $conn->query($warehouseQuery);

// Update production orders status query to get actual counts
$productionStatusQuery = "SELECT 
    Status,
    COUNT(*) as StatusCount,
    SUM(QuantityOrdered) as TotalQuantity
    FROM productionorders 
    GROUP BY Status";
$productionStatusResult = $conn->query($productionStatusQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/eminventory.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .analytics-card {
            transition: transform 0.2s;
            margin-bottom: 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .analytics-card:hover {
            transform: translateY(-5px);
        }
        .metric-value {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }
        .metric-label {
            color: #7f8c8d;
            font-size: 14px;
        }
        .chart-container {
            position: relative;
            margin: auto;
            height: 300px;
            width: 100%;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
        }
        .table-responsive {
            margin-top: 20px;
        }
        .container{
            padding: 0; 
            max-width: 100%;  
        }
        .low-stock {
            background-color: #ffeeee;
        }
        .critical-stock {
            background-color: #ffdddd;
        }
        .card-title {
            margin-bottom: 15px;
            font-weight: 600;
            color: #2c3e50;
        }
    </style>
</head>
<body class="bg-light">
<div class="container">
        <?php renderSidebar('reports_analytics'); // Note different active page ?>
        
        <div class="main-content">
            <?php renderHeader('Reports Analytics'); ?>

            <div class="container-fluid py-4">
                <h2 class="mb-4">Reports & Analytics Dashboard</h2>

                <!-- Quick Stats Row -->
                <div class="row mb-4">
                    <?php
                    // Raw Materials Stats
                    $totalMaterials = $rawMaterialsResult->num_rows;
                    $totalValue = 0;
                    $lowStock = 0;
                    
                    // Reset pointer to beginning of result
                    $rawMaterialsResult->data_seek(0);
                    
                    while($row = $rawMaterialsResult->fetch_assoc()) {
                        $totalValue += $row['TotalValue'];
                        if($row['QuantityInStock'] <= $row['MinimumStock']) {
                            $lowStock++;
                        }
                    }
                    
                    // Add product inventory value to total
                    $totalValue += $productInventoryValue;

                    // Get product count
                    $inventoryResult->data_seek(0);
                    $productCount = $inventoryResult->num_rows;
                    ?>
                    <div class="col-md-3">
                        <div class="card analytics-card">
                            <div class="card-body">
                                <h6 class="metric-label">Total Materials & Products</h6>
                                <div class="metric-value"><?php echo ($totalMaterials + $productCount); ?></div>
                                <div class="small text-muted mt-2">
                                    <?php echo $totalMaterials; ?> Materials, <?php echo $productCount; ?> Products
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card analytics-card">
                            <div class="card-body">
                                <h6 class="metric-label">Total Inventory Value</h6>
                                <div class="metric-value">₱<?php echo number_format($totalValue, 2); ?></div>
                                <div class="small text-muted mt-2">
                                    Raw materials and finished products
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card analytics-card">
                            <div class="card-body">
                                <h6 class="metric-label">Low Stock Items</h6>
                                <div class="metric-value"><?php echo ($lowStock + $lowStockProducts); ?></div>
                                <div class="small text-muted mt-2">
                                    Items below recommended levels
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card analytics-card">
                            <div class="card-body">
                                <h6 class="metric-label">Active Orders</h6>
                                <div class="metric-value"><?php echo $activeOrders; ?></div>
                                <div class="small text-muted mt-2">
                                    In Progress or Planned
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row mb-4">
                    <!-- Raw Materials Chart -->
                    <div class="col-md-6">
                        <div class="card analytics-card">
                            <div class="card-body">
                                <h5 class="card-title">Raw Materials Stock Level</h5>
                                <div class="chart-container">
                                    <canvas id="rawMaterialsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Product Category Distribution -->
                    <div class="col-md-6">
                        <div class="card analytics-card">
                            <div class="card-body">
                                <h5 class="card-title">Product Categories</h5>
                                <div class="chart-container">
                                    <canvas id="categoryChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Charts Row -->
                <div class="row mb-4">
                    <!-- Warehouse Distribution -->
                    <div class="col-md-6">
                        <div class="card analytics-card">
                            <div class="card-body">
                                <h5 class="card-title">Product Distribution by Warehouse</h5>
                                <div class="chart-container">
                                    <canvas id="warehouseChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Production Status Chart -->
                    <div class="col-md-6">
                        <div class="card analytics-card">
                            <div class="card-body">
                                <h5 class="card-title">Production Order Status</h5>
                                <div class="chart-container">
                                    <canvas id="productionStatusChart"></canvas>
                                </div>
                                <div class="text-center mt-3">
                                    <span class="badge bg-info">Current: 1 Order for 40,000 Food Cans (In Progress)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Raw Materials Table -->
                <div class="card analytics-card">
                    <div class="card-body">
                        <h5 class="card-title">Raw Materials Inventory</h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Material</th>
                                        <th>Warehouse</th>
                                        <th>Quantity</th>
                                        <th>Minimum Stock</th>
                                        <th>Status</th>
                                        <th>Last Restocked</th>
                                        <th>Unit Cost</th>
                                        <th>Total Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $rawMaterialsResult->data_seek(0);
                                    while($row = $rawMaterialsResult->fetch_assoc()) {
                                        $stockRatio = $row['QuantityInStock'] / $row['MinimumStock'];
                                        $rowClass = '';
                                        
                                        if ($stockRatio <= 0.75) {
                                            $rowClass = 'critical-stock';
                                            $status = '<span class="badge bg-danger">Critical Stock</span>';
                                        } else if ($stockRatio <= 1) {
                                            $rowClass = 'low-stock';
                                            $status = '<span class="badge bg-warning text-dark">Low Stock</span>';
                                        } else {
                                            $status = '<span class="badge bg-success">Adequate</span>';
                                        }
                                        
                                        echo "<tr class='{$rowClass}'>
                                            <td>{$row['MaterialName']}</td>
                                            <td>{$row['raw_warehouse']}</td>
                                            <td>{$row['QuantityInStock']}</td>
                                            <td>{$row['MinimumStock']}</td>
                                            <td>{$status}</td>
                                            <td>{$row['LastRestockedDate']}</td>
                                            <td>₱" . number_format($row['UnitCost'], 2) . "</td>
                                            <td>₱" . number_format($row['TotalValue'], 2) . "</td>
                                        </tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Products Inventory Table - New table based on products -->
                <div class="card analytics-card mt-4">
                    <div class="card-body">
                        <h5 class="card-title">Products Inventory</h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Product</th>
                                        <th>Category</th>
                                        <th>Warehouse</th>
                                        <th>Section</th>
                                        <th>Quantity</th>
                                        <th>Weight</th>
                                        <th>Production Cost</th>
                                        <th>Selling Price</th>
                                        <th>Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $inventoryResult->data_seek(0);
                                    
                                    // Group products by category for better readability
                                    $currentCategory = '';
                                    
                                    while($row = $inventoryResult->fetch_assoc()) {
                                        // Get production cost from products table
                                        $costQuery = "SELECT ProductionCost, SellingPrice FROM products WHERE ProductID = {$row['ProductID']}";
                                        $costResult = $conn->query($costQuery);
                                        $costData = $costResult->fetch_assoc();
                                        
                                        // Apply row background for categories
                                        $categoryClass = '';
                                        if ($currentCategory != $row['Category']) {
                                            $currentCategory = $row['Category'];
                                            $categoryClass = 'table-secondary';
                                        }
                                        
                                        // Highlight low stock items
                                        $stockClass = '';
                                        if ($row['Quantity'] <= 100) {
                                            $stockClass = 'low-stock';
                                        }
                                        if ($row['Quantity'] <= 50) {
                                            $stockClass = 'critical-stock';
                                        }
                                        
                                        $rowClass = $stockClass ? $stockClass : $categoryClass;
                                        
                                        echo "<tr class='{$rowClass}'>
                                            <td>{$row['ProductName']}</td>
                                            <td>{$row['Category']}</td>
                                            <td>{$row['productWarehouse']}</td>
                                            <td>{$row['Section']}</td>
                                            <td>{$row['Quantity']}</td>
                                            <td>{$row['Weight']} {$row['weight_unit']}</td>
                                            <td>₱" . number_format($costData['ProductionCost'], 2) . "</td>
                                            <td>₱" . number_format($costData['SellingPrice'], 2) . "</td>
                                            <td>₱" . number_format($row['InventoryValue'], 2) . "</td>
                                        </tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bootstrap JS and dependencies -->
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
            
            <script>
                // Raw Materials Chart
                const rawMaterialsCtx = document.getElementById('rawMaterialsChart').getContext('2d');
                const rawMaterialsData = {
                    labels: <?php 
                        $rawMaterialsResult->data_seek(0);
                        $labels = [];
                        $data = [];
                        $minStock = [];
                        while($row = $rawMaterialsResult->fetch_assoc()) {
                            $labels[] = $row['MaterialName'];
                            $data[] = $row['QuantityInStock'];
                            $minStock[] = $row['MinimumStock'];
                        }
                        echo json_encode($labels);
                    ?>,
                    datasets: [{
                        label: 'Current Stock',
                        data: <?php echo json_encode($data); ?>,
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Minimum Stock',
                        data: <?php echo json_encode($minStock); ?>,
                        backgroundColor: 'rgba(255, 99, 132, 0.5)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }]
                };

                new Chart(rawMaterialsCtx, {
                    type: 'bar',
                    data: rawMaterialsData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Quantity'
                                }
                            }
                        },
                        plugins: {
                            title: {
                                display: true,
                                text: 'Raw Materials Inventory Levels'
                            }
                        }
                    }
                });

                // Category Distribution Chart
                const categoryCtx = document.getElementById('categoryChart').getContext('2d');
                const categoryData = {
                    labels: <?php 
                        $categoryResult->data_seek(0);
                        $categoryLabels = [];
                        $categoryCount = [];
                        while($row = $categoryResult->fetch_assoc()) {
                            $categoryLabels[] = $row['Category'];
                            $categoryCount[] = $row['ProductCount'];
                        }
                        echo json_encode($categoryLabels);
                    ?>,
                    datasets: [{
                        data: <?php echo json_encode($categoryCount); ?>,
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.7)',
                            'rgba(54, 162, 235, 0.7)',
                            'rgba(255, 206, 86, 0.7)',
                            'rgba(75, 192, 192, 0.7)',
                            'rgba(153, 102, 255, 0.7)',
                            'rgba(255, 159, 64, 0.7)',
                            'rgba(199, 199, 199, 0.7)'
                        ],
                        borderColor: [
                            'rgba(255, 99, 132, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 206, 86, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(153, 102, 255, 1)',
                            'rgba(255, 159, 64, 1)',
                            'rgba(199, 199, 199, 1)'
                        ],
                        borderWidth: 1
                    }]
                };

                new Chart(categoryCtx, {
                    type: 'pie',
                    data: categoryData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right'
                            },
                            title: {
                                display: true,
                                text: 'Products by Category'
                            }
                        }
                    }
                });

                // Warehouse Distribution Chart
                const warehouseCtx = document.getElementById('warehouseChart').getContext('2d');
                const warehouseData = {
                    labels: <?php 
                        $warehouseResult->data_seek(0);
                        $warehouseLabels = [];
                        $warehouseUsage = [];
                        $warehouseCapacity = [];
                        while($row = $warehouseResult->fetch_assoc()) {
                            $warehouseLabels[] = $row['productWarehouse'];
                            $warehouseUsage[] = round($row['current_usage'], 2);
                            $warehouseCapacity[] = round($row['Capacity'], 2);
                        }
                        echo json_encode($warehouseLabels);
                    ?>,
                    datasets: [{
                        label: 'Current Usage (' + <?php 
                            $warehouseResult->data_seek(0);
                            $row = $warehouseResult->fetch_assoc();
                            echo json_encode($row['warehouse_weight_unit']); 
                        ?> + ')',
                        data: <?php echo json_encode($warehouseUsage); ?>,
                        backgroundColor: 'rgba(54, 162, 235, 0.7)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Total Capacity',
                        data: <?php echo json_encode($warehouseCapacity); ?>,
                        backgroundColor: 'rgba(255, 206, 86, 0.4)',
                        borderColor: 'rgba(255, 206, 86, 1)',
                        borderWidth: 1,
                        type: 'line',
                        fill: false
                    }]
                };

                new Chart(warehouseCtx, {
                    type: 'bar',
                    data: warehouseData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Weight'
                                }
                            }
                        },
                        plugins: {
                            title: {
                                display: true,
                                text: 'Warehouse Capacity and Usage'
                            }
                        }
                    }
                });

                // Production Status Chart
                const productionStatusCtx = document.getElementById('productionStatusChart').getContext('2d');
                const productionStatusData = {
                    labels: <?php 
                        $productionStatusResult->data_seek(0);
                        $statusLabels = [];
                        $statusCounts = [];
                        $totalQuantities = [];
                        while($row = $productionStatusResult->fetch_assoc()) {
                            $statusLabels[] = $row['Status'];
                            $statusCounts[] = $row['StatusCount'];
                            $totalQuantities[] = $row['TotalQuantity'];
                        }
                        echo json_encode($statusLabels);
                    ?>,
                    datasets: [{
                        data: <?php echo json_encode($statusCounts); ?>,
                        backgroundColor: [
                            'rgba(54, 162, 235, 0.7)',  // In Progress
                            'rgba(255, 206, 86, 0.7)',  // Planned
                            'rgba(75, 192, 192, 0.7)',  // Completed
                            'rgba(255, 99, 132, 0.7)'   // Cancelled
                        ],
                        borderColor: [
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 206, 86, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(255, 99, 132, 1)'
                        ],
                        borderWidth: 1
                    }]
                };

                new Chart(productionStatusCtx, {
                    type: 'doughnut',
                    data: productionStatusData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right'
                            },
                            title: {
                                display: true,
                                text: 'Production Orders by Status'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.raw || 0;
                                        const total = <?php echo json_encode($totalQuantities); ?>[context.dataIndex];
                                        return `${label}: ${value} orders (${total} units)`;
                                    }
                                }
                            }
                        }
                    }
                });
            </script>
        </div>
    </div>
</body>
</html>