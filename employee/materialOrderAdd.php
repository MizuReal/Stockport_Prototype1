<?php
require_once 'session_check.php'; // Adjust path as needed
requireActiveLogin(); // This ensures user is logged in AND has Active status
require_once '../layouts/employeeSidebar.php';
require_once '../layouts/employeeHeader.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database connection
$host = 'localhost';
$dbname = 'stockport';
$username = 'root';
$password = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Fetch warehouses for dropdown selection
$warehouseStmt = $pdo->query("SELECT productLocationID, productWarehouse, Section, Capacity, warehouse_weight_unit FROM products_warehouse");
$warehouses = $warehouseStmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_order'])) {
    // Create a new raw material order
    $productID = $_POST['ProductID'];
    $employeeID = $_SESSION['employeeID'];
    $startDate = date('Y-m-d');
    $endDate = $_POST['EndDate'];
    $status = 'In Progress';
    $sheetCount = max(1, (int)$_POST['SheetCount']);
    $warehouseID = $_POST['WarehouseID']; // Warehouse selection field
    
    // Get the product ratio, material information, and weight
    $productStmt = $pdo->prepare("SELECT p.minimum_quantity, p.MaterialID, p.Weight, p.weight_unit FROM products p WHERE p.ProductID = :ProductID");
    $productStmt->execute([':ProductID' => $productID]);
    $productInfo = $productStmt->fetch(PDO::FETCH_ASSOC);
    $ratio = isset($productInfo['minimum_quantity']) ? $productInfo['minimum_quantity'] : 0;
    $materialID = $productInfo['MaterialID'];
    $productWeight = floatval($productInfo['Weight']);
    $productWeightUnit = $productInfo['weight_unit'];
    
    // Check current material stock
    $stockStmt = $pdo->prepare("SELECT QuantityInStock FROM rawmaterials WHERE MaterialID = :MaterialID");
    $stockStmt->execute([':MaterialID' => $materialID]);
    $currentStock = $stockStmt->fetchColumn();
    
    // Check warehouse capacity by weight
    $warehouseStmt = $pdo->prepare("
        SELECT pw.Capacity, pw.warehouse_weight_unit
        FROM products_warehouse pw
        WHERE pw.productLocationID = :WarehouseID
    ");
    $warehouseStmt->execute([':WarehouseID' => $warehouseID]);
    $warehouseInfo = $warehouseStmt->fetch(PDO::FETCH_ASSOC);
    $warehouseCapacity = $warehouseInfo['Capacity'];
    $warehouseWeightUnit = $warehouseInfo['warehouse_weight_unit'];
    
    $quantityOrdered = $ratio * $sheetCount; // Add this line to fix the null QuantityOrdered

    // Get current warehouse usage (convert all weights to kg for consistency)
    $usageStmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(
                CASE 
                    WHEN p.weight_unit = 'g' THEN (p.Weight * (po.QuantityOrdered - po.QuantityProduced)) / 1000
                    WHEN p.weight_unit = 'kg' THEN (p.Weight * (po.QuantityOrdered - po.QuantityProduced))
                END
            ), 0) as TotalWeight
        FROM productionorders po
        JOIN products p ON po.ProductID = p.ProductID
        WHERE po.warehouseID = :WarehouseID AND po.Status IN ('Planned', 'In Progress')
    ");
    $usageStmt->execute([':WarehouseID' => $warehouseID]);
    $currentUsage = floatval($usageStmt->fetchColumn());

    // Convert all measurements to kg for comparison
    $warehouseCapacity = $warehouseWeightUnit == 'g' ? $warehouseCapacity / 1000 : $warehouseCapacity;
    $totalProductWeight = $productWeight * $quantityOrdered;
    $totalProductWeight = $productWeightUnit == 'g' ? $totalProductWeight / 1000 : $totalProductWeight;
    
    $remainingCapacity = $warehouseCapacity - $currentUsage;

    // Add debug output
    error_log("Debug - Warehouse Capacity (kg): $warehouseCapacity");
    error_log("Debug - Current Usage (kg): $currentUsage");
    error_log("Debug - Total Product Weight (kg): $totalProductWeight");
    error_log("Debug - Remaining Capacity (kg): $remainingCapacity");

    if ($ratio <= 0) {
        $_SESSION['order_message'] = [
            'type' => 'error',
            'text' => 'Error: No production ratio defined for this product. Please contact administrator.'
        ];
    } elseif ($currentStock < $sheetCount) {
        $_SESSION['order_message'] = [
            'type' => 'error',
            'text' => "Error: Insufficient material in stock. Available: $currentStock sheets. Required: $sheetCount sheets."
        ];
    } elseif ($totalProductWeight > $remainingCapacity) {
        $_SESSION['order_message'] = [
            'type' => 'error',
            'text' => "Error: Insufficient warehouse capacity. Available: " . number_format($remainingCapacity, 2) . " kg. Required: " . number_format($totalProductWeight, 2) . " kg."
        ];
    } else {
        $quantityProduced = 0;

        // Begin transaction to ensure database consistency
        $pdo->beginTransaction();
        
        try {
            // Insert the new order with warehouse ID
            $stmt = $pdo->prepare("INSERT INTO productionOrders (ProductID, EmployeeID, StartDate, EndDate, Status, QuantityOrdered, QuantityProduced, warehouseID)
                            VALUES (:ProductID, :EmployeeID, :StartDate, :EndDate, :Status, :QuantityOrdered, :QuantityProduced, :WarehouseID)");
            $stmt->execute([
                ':ProductID' => $productID,
                ':EmployeeID' => $employeeID,
                ':StartDate' => $startDate,
                ':EndDate' => $endDate,
                ':Status' => $status,
                ':QuantityOrdered' => $quantityOrdered,
                ':QuantityProduced' => $quantityProduced,
                ':WarehouseID' => $warehouseID
            ]);
            
            // Update the material stock
            $updateStmt = $pdo->prepare("UPDATE rawmaterials SET 
                                        QuantityInStock = QuantityInStock - :SheetCount 
                                        WHERE MaterialID = :MaterialID");
            $updateStmt->execute([
                ':SheetCount' => $sheetCount,
                ':MaterialID' => $materialID
            ]);

            // Update warehouse current usage (store everything in kg)
            $updateWarehouseStmt = $pdo->prepare("
                UPDATE products_warehouse 
                SET current_usage = GREATEST(0, current_usage + :newUsage)
                WHERE productLocationID = :warehouseID");
            $updateWarehouseStmt->execute([
                ':newUsage' => $totalProductWeight, // Already in kg from earlier conversion
                ':warehouseID' => $warehouseID
            ]);
            
            // Commit the transaction
            $pdo->commit();
            
            $_SESSION['order_message'] = [
                'type' => 'success',
                'text' => "Order created successfully! Ordered " . $quantityOrdered . " units using " . $sheetCount . " material sheets. Total weight: " . number_format($totalProductWeight, 2) . " kg"
            ];
        } catch (Exception $e) {
            // Roll back the transaction if something failed
            $pdo->rollBack();
            $_SESSION['order_message'] = [
                'type' => 'error',
                'text' => "Error: " . $e->getMessage()
            ];
        }
    }
    
    // Redirect to the same page to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Check for flash messages
$orderCreationMessage = "";
if (isset($_SESSION['order_message'])) {
    $messageType = $_SESSION['order_message']['type'] == 'error' ? 'alert-error' : 'alert';
    $orderCreationMessage = "<div class='{$messageType}'>{$_SESSION['order_message']['text']}</div>";
    // Clear the message to prevent it from showing on subsequent page loads
    unset($_SESSION['order_message']);
}

// Fetch all products for the dropdown, including MaterialName, MaterialID, current stock, minimum_quantity (ratio), weight, and weight_unit
$productStmt = $pdo->query("
    SELECT p.ProductID, p.ProductName, p.minimum_quantity, p.Weight, p.weight_unit, rm.MaterialID, rm.MaterialName, rm.QuantityInStock 
    FROM products p
    JOIN rawmaterials rm ON p.MaterialID = rm.MaterialID
");
$products = $productStmt->fetchAll(PDO::FETCH_ASSOC);

// Create product data mappings for JavaScript
$productData = [];
foreach ($products as $product) {
    $productData[$product['ProductID']] = [
        'ratio' => $product['minimum_quantity'],
        'stock' => $product['QuantityInStock'],
        'weight' => $product['Weight'],
        'weightUnit' => $product['weight_unit']
    ];
}

// Fetch current warehouse capacities and usages with weight calculations
$warehouseUsageStmt = $pdo->query("
    SELECT 
        pw.productLocationID,
        pw.productWarehouse,
        pw.Section,
        pw.Capacity,
        pw.current_usage,
        pw.warehouse_weight_unit
    FROM 
        products_warehouse pw
");
$warehouseUsages = $warehouseUsageStmt->fetchAll(PDO::FETCH_ASSOC);

// Warehouse data for JavaScript
$warehouseData = [];
foreach ($warehouseUsages as $warehouse) {
    $currentUsage = floatval($warehouse['current_usage']);
    $capacity = floatval($warehouse['Capacity']);
    $remainingCapacity = max(0, $capacity - $currentUsage);

    $warehouseData[$warehouse['productLocationID']] = [
        'capacity' => $capacity,
        'currentUsage' => $currentUsage,
        'remainingCapacity' => $remainingCapacity,
        'weightUnit' => $warehouse['warehouse_weight_unit']
    ];
}

// Fetch all orders with additional details including weights
$orderStmt = $pdo->query("
    SELECT 
        po.OrderID, 
        p.ProductID, 
        p.ProductName, 
        p.minimum_quantity, 
        p.Weight,
        p.weight_unit,
        rm.MaterialName, 
        rm.raw_material_img, 
        e.FirstName, 
        e.LastName, 
        po.StartDate, 
        po.EndDate, 
        po.Status, 
        po.QuantityOrdered, 
        po.QuantityProduced,
        pw.productWarehouse as Warehouse,
        pw.Section as WarehouseSection,
        pw.warehouse_weight_unit
    FROM 
        productionOrders po
    JOIN 
        products p ON po.ProductID = p.ProductID
    JOIN 
        rawmaterials rm ON p.MaterialID = rm.MaterialID
    JOIN 
        employees e ON po.EmployeeID = e.EmployeeID
    LEFT JOIN
        products_warehouse pw ON po.warehouseID = pw.productLocationID
    ORDER BY 
        po.StartDate DESC
");
$orders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);

// JSON encode data for JavaScript
$jsProductData = json_encode($productData);
$jsWarehouseData = json_encode($warehouseData);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raw Material Order Processing</title>
    <link rel="stylesheet" href="../assets/css/eminventory.css">
    <script>
        // Store product and warehouse data for JavaScript use
        const productData = <?= $jsProductData ?>;
        const warehouseData = <?= $jsWarehouseData ?>;
        
        // JavaScript function to filter table rows based on search input
        function filterTable() {
            const input = document.getElementById('search-bar').value.toLowerCase();
            const tableBody = document.getElementById('order-table-body');
            const rows = tableBody.getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                let matchFound = false;

                for (let j = 0; j < cells.length; j++) {
                    if (cells[j].textContent.toLowerCase().includes(input)) {
                        matchFound = true;
                        break;
                    }
                }

                rows[i].style.display = matchFound ? '' : 'none';
            }
        }
        
        // Convert weight units
        function convertWeight(weight, fromUnit, toUnit) {
            weight = parseFloat(weight);
            if (isNaN(weight) || weight < 0) return 0;
            // Always convert to kg for storage and comparison
            if (fromUnit === toUnit) return weight;
            if (fromUnit === 'g' && toUnit === 'kg') return Number((weight / 1000).toFixed(3));
            if (fromUnit === 'kg' && toUnit === 'g') return Number((weight * 1000).toFixed(1));
            return weight;
        }
        
        // Update quantity and warehouse capacity information
        function updateOrderInfo() {
            const productSelect = document.getElementById('ProductID');
            const warehouseSelect = document.getElementById('WarehouseID');
            const productID = productSelect.value;
            const warehouseID = warehouseSelect.value;
            
            if (!productID) {
                document.getElementById('stock-info').textContent = '';
                document.getElementById('material-info').textContent = '';
                document.getElementById('weight-info').textContent = '';
                document.getElementById('QuantityDisplay').value = '';
                return;
            }
            
            const productName = productSelect.options[productSelect.selectedIndex].text.split(' (')[0].trim();
            const sheetCount = parseInt(document.getElementById('SheetCount').value) || 1;
            
            // Get product data
            const product = productData[productID];
            
            if (!product) {
                console.error('Product data not found for ID:', productID);
                return;
            }
            
            // Calculate total quantity
            const totalQuantity = product.ratio * sheetCount;
            
            // Update the readonly quantity display
            document.getElementById('QuantityDisplay').value = totalQuantity;
            document.getElementById('QuantityOrdered').value = totalQuantity;
            
            // Update the material info display
            if (product.ratio > 0) {
                document.getElementById('material-info').textContent = 
                    `Each sheet produces ${product.ratio} units of ${productName}. Total: ${totalQuantity} units.`;
                document.getElementById('material-info').style.color = '#008800';
            } else {
                document.getElementById('material-info').textContent = 
                    `Unknown product ratio. Please contact administrator.`;
                document.getElementById('material-info').style.color = '#FF0000';
            }
            
            // Display current stock information
            const stockInfo = document.getElementById('stock-info');
            
            if (productID) {
                const stockAmount = product.stock;
                stockInfo.textContent = `Available stock: ${stockAmount} sheets`;
                
                // Change color based on if there's enough stock
                if (stockAmount < sheetCount) {
                    stockInfo.style.color = '#FF0000';
                } else {
                    stockInfo.style.color = '#008800';
                }
            } else {
                stockInfo.textContent = '';
            }
            
            // Calculate and display weight information
            const weightInfo = document.getElementById('weight-info');
            if (productID) {
                const totalWeight = product.weight * totalQuantity;
                weightInfo.textContent = `Total product weight: ${totalWeight} ${product.weightUnit}`;
                
                // If warehouse is selected, calculate remaining capacity
                if (warehouseID && warehouseData[warehouseID]) {
                    const warehouse = warehouseData[warehouseID];
                    const convertedWeight = convertWeight(totalWeight, product.weightUnit, warehouse.weightUnit);
                    const remainingCapacity = Math.max(0, warehouse.capacity - warehouse.currentUsage);
                    
                    weightInfo.textContent += ` (${convertedWeight} ${warehouse.weightUnit})`;
                    weightInfo.textContent += `\nWarehouse remaining capacity: ${remainingCapacity.toFixed(2)} ${warehouse.weightUnit}`;
                    
                    // Color based on if the warehouse has enough capacity
                    if (convertedWeight > remainingCapacity) {
                        weightInfo.style.color = '#FF0000';
                    } else {
                        weightInfo.style.color = '#008800';
                    }
                }
            } else {
                weightInfo.textContent = '';
            }
        }
        
        // Initialize when page loads
        window.onload = function() {
            // Only run update if a product is selected (this prevents calculations when page first loads)
            const productSelect = document.getElementById('ProductID');
            if (productSelect.value) {
                updateOrderInfo();
            }
        };
    </script>
</head>
<body>
    <!-- Container -->
    <div class="container">
        <?php renderSidebar('rawMaterialOrder'); // Note different active page ?>

        <div class="main-content">
            <?php renderHeader('Raw Material Order Processing'); ?>
            
            <!-- Display order creation message if any -->
            <?php if (!empty($orderCreationMessage)): ?>
                <?= $orderCreationMessage ?>
            <?php endif; ?>
            
            <!-- Search Bar -->
            <input type="text" id="search-bar" class="search-bar" placeholder="Search orders..." onkeyup="filterTable()">
            
            <!-- Product ratios reference table - Now dynamically generated -->
            <section class="card">
                <h2 class="card-header">Material to Product Ratios</h2>
                <div style="padding: 15px;">
                    <?php
                    // Group products by material type
                    $groupedProducts = [];
                    foreach ($products as $product) {
                        $materialName = $product['MaterialName'];
                        if (!isset($groupedProducts[$materialName])) {
                            $groupedProducts[$materialName] = [];
                        }
                        $groupedProducts[$materialName][] = [
                            'name' => $product['ProductName'],
                            'ratio' => $product['minimum_quantity'],
                            'weight' => $product['Weight'],
                            'unit' => $product['weight_unit']
                        ];
                    }
                    
                    // Display grouped products
                    foreach ($groupedProducts as $materialName => $materialProducts) {
                        echo "<p><strong>{$materialName} Products:</strong> ";
                        $productList = [];
                        foreach ($materialProducts as $prod) {
                            $productList[] = "{$prod['name']} ({$prod['ratio']} units per sheet, {$prod['weight']} {$prod['unit']} per unit)";
                        }
                        echo implode(', ', $productList);
                        echo "</p>";
                    }
                    ?>
                </div>
            </section>
            
            <!-- Materials Inventory Status -->
            <section class="card">
                <h2 class="card-header">Current Raw Material Inventory</h2>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Material</th>
                            <th>Quantity in Stock (Sheets)</th>
                            <th>Minimum Stock Level</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $materialStmt = $pdo->query("SELECT MaterialName, QuantityInStock, MinimumStock FROM rawmaterials");
                        $materials = $materialStmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        foreach ($materials as $material):
                            $stockStatus = $material['QuantityInStock'] <= $material['MinimumStock'] ? 'Low' : 'Good';
                            $statusColor = $stockStatus == 'Low' ? '#FF0000' : '#008800';
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($material['MaterialName']) ?></td>
                            <td><?= htmlspecialchars($material['QuantityInStock']) ?></td>
                            <td><?= htmlspecialchars($material['MinimumStock']) ?></td>
                            <td style="color: <?= $statusColor ?>;"><?= $stockStatus ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
            
            <!-- Warehouse Capacity Status -->
            <section class="card">
                <h2 class="card-header">Warehouse Capacity Status</h2>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Warehouse</th>
                            <th>Section</th>
                            <th>Total Capacity</th>
                            <th>Current Usage</th>
                            <th>Available Capacity</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($warehouseUsages as $warehouse): 
                            $currentUsage = floatval($warehouse['current_usage']);
                            $capacity = floatval($warehouse['Capacity']);
                            $availableCapacity = max(0, $capacity - $currentUsage);
                            $capacityPercent = $capacity > 0 ? min(100, ($currentUsage / $capacity) * 100) : 0;
                            
                            // Determine status color
                            if ($capacityPercent > 90) {
                                $statusText = 'Critical';
                                $statusColor = '#FF0000';
                            } elseif ($capacityPercent > 75) {
                                $statusText = 'High';
                                $statusColor = '#FFA500';
                            } else {
                                $statusText = 'Good';
                                $statusColor = '#008800';
                            }
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($warehouse['productWarehouse']) ?></td>
                            <td><?= htmlspecialchars($warehouse['Section']) ?></td>
                            <td><?= number_format($capacity, 2) ?> <?= htmlspecialchars($warehouse['warehouse_weight_unit']) ?></td>
                            <td><?= number_format($currentUsage, 2) ?> <?= htmlspecialchars($warehouse['warehouse_weight_unit']) ?></td>
                            <td><?= number_format($availableCapacity, 2) ?> <?= htmlspecialchars($warehouse['warehouse_weight_unit']) ?></td>
                            <td style="color: <?= $statusColor ?>;"><?= $statusText ?> (<?= round($capacityPercent) ?>%)</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
            
            <!-- Form to create a new order -->
            <section class="card">
                <h2 class="card-header">Create New Order</h2>
                <form method="POST" action="">
                    <label for="ProductID">Select Product:</label>
                    <select id="ProductID" name="ProductID" required class="search-bar" onchange="updateOrderInfo()">
                        <option value="">-- Select a Product --</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?= htmlspecialchars($product['ProductID']) ?>">
                                <?= htmlspecialchars($product['ProductName']) ?> (Material: <?= htmlspecialchars($product['MaterialName']) ?>, 
                                Weight: <?= htmlspecialchars($product['Weight']) ?> <?= htmlspecialchars($product['weight_unit']) ?>/unit)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p id="stock-info" style="margin-top: 5px;"></p>
                    
                    <label for="SheetCount">Number of Material Sheets:</label>
                    <input type="number" id="SheetCount" name="SheetCount" min="1" value="1" required class="search-bar" onchange="updateOrderInfo()">
                    
                    <label for="QuantityDisplay">Quantity to be Ordered:</label>
                    <input type="text" id="QuantityDisplay" readonly class="search-bar">
                    <input type="hidden" id="QuantityOrdered" name="QuantityOrdered">
                    <p id="material-info" style="font-style: italic; margin-top: 5px;"></p>
                    
                    <!-- Warehouse selection dropdown with weight capacity information -->
                    <label for="WarehouseID">Storage Warehouse:</label>
                    <select id="WarehouseID" name="WarehouseID" required class="search-bar" onchange="updateOrderInfo()">
                        <option value="">-- Select Destination Warehouse --</option>
                        <?php foreach ($warehouseUsages as $warehouse): 
                            $currentUsage = floatval($warehouse['current_usage']);
                            $availableCapacity = max(0, floatval($warehouse['Capacity']) - $currentUsage);
                        ?>
                            <option value="<?= htmlspecialchars($warehouse['productLocationID']) ?>">
                                <?= htmlspecialchars($warehouse['productWarehouse']) ?> - 
                                <?= htmlspecialchars($warehouse['Section']) ?> 
                                (Available: <?= number_format($availableCapacity, 2) ?> <?= htmlspecialchars($warehouse['warehouse_weight_unit']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p id="weight-info" style="font-style: italic; margin-top: 5px;"></p>
                    
                    <label for="EndDate">Expected End Date:</label>
                    <input type="date" id="EndDate" name="EndDate" required class="search-bar">
                    
                    <button type="submit" name="create_order" class="btn">Create Order</button>
                </form>
            </section>
            
            <!-- Display all orders -->
            <section class="card">
                <h2 class="card-header">Current Orders</h2>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Product to be Produced</th>
                            <th>Ordered Material</th>
                            <th>Ordered By</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Warehouse</th>
                            <th>Status</th>
                            <th>Quantity Ordered</th>
                            <th>Quantity Produced</th>
                            <th>Total Weight</th>
                        </tr>
                    </thead>
                    <tbody id="order-table-body">
                        <?php foreach ($orders as $order): 
                            // Calculate total weight for this order
                            $totalWeight = $order['Weight'] * $order['QuantityOrdered'];
                            $warehouseUnit = $order['warehouse_weight_unit'] ?? $order['weight_unit'];
                            
                            // Convert weight if necessary
                            if ($order['weight_unit'] != $warehouseUnit) {
                                if ($order['weight_unit'] == 'g' && $warehouseUnit == 'kg') {
                                    $totalWeight = $totalWeight / 1000;
                                } else if ($order['weight_unit'] == 'kg' && $warehouseUnit == 'g') {
                                    $totalWeight = $totalWeight * 1000;
                                }
                            }
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($order['OrderID']) ?></td>
                                <td><?= htmlspecialchars($order['ProductName']) ?></td>
                                <td>
                                    <div style="display: flex; align-items: center;">
                                        <img src="../assets/imgs/<?= htmlspecialchars($order['raw_material_img']) ?>" alt="Material Image" style="width: 50px; height: 50px; margin-right: 10px;">
                                        <span><?= htmlspecialchars($order['MaterialName']) ?></span>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($order['FirstName'] . ' ' . $order['LastName']) ?></td>
                                <td><?= htmlspecialchars($order['StartDate']) ?></td>
                                <td><?= htmlspecialchars($order['EndDate']) ?></td>
                                <td>
                                    <?= $order['Warehouse'] ? htmlspecialchars($order['Warehouse'] . ' - ' . $order['WarehouseSection']) : 'Not specified' ?>
                                </td>
                                <td><?= htmlspecialchars($order['Status']) ?></td>
                                <td>
                                    <?= htmlspecialchars($order['QuantityOrdered']) ?>
                                    <?php
                                    // Calculate how many sheets this represents using minimum_quantity
                                    $ratio = $order['minimum_quantity'];
                                    if ($ratio > 0) {
                                        $sheets = $order['QuantityOrdered'] / $ratio;
                                        echo " <span style='color:#666;'>($sheets sheets)</span>";
                                    }
                                    ?>
                                </td>
                                <td><?= htmlspecialchars($order['QuantityProduced']) ?></td>
                                <td><?= number_format($totalWeight, 2) ?> <?= htmlspecialchars($warehouseUnit) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        </div>
    </div>
</body>
</html>