<?php
session_start();
require_once 'session_check.php'; // Adjust path as needed
requireActiveLogin(); // This ensures user is logged in AND has Active status
require_once '../layouts/employeeSidebar.php';
require_once '../layouts/employeeHeader.php';
require_once '../server/database.php'; // Ensure this path is correct

// Get the employee ID from the session
$employeeID = $_SESSION['employeeID'];

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $customerID = $_POST['customerID'];
    $productID = $_POST['productID'];
    $quantity = $_POST['quantity'];
    $deliveryDate = $_POST['deliveryDate'];
    
    // Get current date and time
    $orderDate = date('Y-m-d H:i:s');
    
    // Get product price to calculate total amount
    $stmt = $conn->prepare("SELECT SellingPrice FROM products WHERE ProductID = ?");
    $stmt->bind_param("i", $productID);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $unitPrice = $product['SellingPrice'];
    $totalAmount = $unitPrice * $quantity;
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert into customerorders table
        $stmt = $conn->prepare("INSERT INTO customerorders (CustomerID, OrderDate, TotalAmount, Status) VALUES (?, ?, ?, 'Pending')");
        $stmt->bind_param("isd", $customerID, $orderDate, $totalAmount);
        $stmt->execute();
        
        // Get the auto-generated CustomerOrderID
        $customerOrderID = $conn->insert_id;
        
        // Insert into orderdetails table
        $stmt = $conn->prepare("INSERT INTO orderdetails (CustomerOrderID, ProductID, Quantity, UnitPrice, EmployeeID) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiidi", $customerOrderID, $productID, $quantity, $unitPrice, $employeeID);
        $stmt->execute();
        
        // Create a production order if needed
        $stmt = $conn->prepare("INSERT INTO productionorders (ProductID, EmployeeID, StartDate, EndDate, Status, QuantityOrdered, QuantityProduced, Delivery_Status, warehouseID) VALUES (?, ?, ?, ?, 'Planned', ?, 0, 0, ?)");
        
        // Get the warehouse location of the product
        $warehouseStmt = $conn->prepare("SELECT LocationID FROM products WHERE ProductID = ?");
        $warehouseStmt->bind_param("i", $productID);
        $warehouseStmt->execute();
        $warehouseResult = $warehouseStmt->get_result();
        $warehouse = $warehouseResult->fetch_assoc();
        $warehouseID = $warehouse['LocationID'];
        
        $stmt->bind_param("iissii", $productID, $employeeID, $orderDate, $deliveryDate, $quantity, $warehouseID);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        // Success message
        $_SESSION['success_message'] = "Customer order added successfully!";
        header("Location: clientOrderList.php");
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $error_message = "Error: " . $e->getMessage();
    }
}

// Fetch customers for dropdown
$customerQuery = "SELECT CustomerID, CustomerName FROM customers ORDER BY CustomerName";
$customerResult = $conn->query($customerQuery);

// Fetch products for dropdown
$productQuery = "SELECT ProductID, ProductName, SellingPrice FROM products ORDER BY ProductName";
$productResult = $conn->query($productQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/eminventory.css">
    <title>Add Client Orders</title>
    <style>
        .form-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-top: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .btn-container {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
        }
        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-primary {
            background-color: #4CAF50;
            color: white;
        }
        .btn-secondary {
            background-color: #f44336;
            color: white;
        }
        .error-message {
            color: #f44336;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php renderSidebar('clientOrderAdd'); // Note different active page ?>
        
        <div class="main-content">
            <?php renderHeader('Add Client Orders'); ?>
            
            <div class="form-container">                
                <?php if (isset($error_message)): ?>
                    <div class="error-message"><?php echo $error_message; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="customerID">Customer:</label>
                        <select name="customerID" id="customerID" class="form-control" required>
                            <option value="">-- Select Customer --</option>
                            <?php while($customer = $customerResult->fetch_assoc()): ?>
                                <option value="<?php echo $customer['CustomerID']; ?>">
                                    <?php echo htmlspecialchars($customer['CustomerName']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="productID">Product:</label>
                        <select name="productID" id="productID" class="form-control" required>
                            <option value="">-- Select Product --</option>
                            <?php while($product = $productResult->fetch_assoc()): ?>
                                <option value="<?php echo $product['ProductID']; ?>" data-price="<?php echo $product['SellingPrice']; ?>">
                                    <?php echo htmlspecialchars($product['ProductName']); ?> 
                                    (₱<?php echo number_format($product['SellingPrice'], 2); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="quantity">Quantity:</label>
                        <input type="number" name="quantity" id="quantity" class="form-control" min="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="totalPrice">Total Price:</label>
                        <input type="text" id="totalPrice" class="form-control" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="deliveryDate">Delivery Date:</label>
                        <input type="datetime-local" name="deliveryDate" id="deliveryDate" class="form-control" required>
                    </div>
                    
                    <div class="btn-container">
                        <a href="clientOrderList.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Add Order</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Calculate total price when product or quantity changes
        document.getElementById('productID').addEventListener('change', updateTotalPrice);
        document.getElementById('quantity').addEventListener('input', updateTotalPrice);
        
        function updateTotalPrice() {
            const productSelect = document.getElementById('productID');
            const quantity = document.getElementById('quantity').value;
            const totalPriceField = document.getElementById('totalPrice');
            
            if (productSelect.selectedIndex > 0 && quantity > 0) {
                const selectedOption = productSelect.options[productSelect.selectedIndex];
                const price = selectedOption.getAttribute('data-price');
                const total = price * quantity;
                totalPriceField.value = '₱' + total.toFixed(2);
            } else {
                totalPriceField.value = '';
            }
        }
        
        // Set minimum date for delivery to tomorrow
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const tomorrowStr = tomorrow.toISOString().slice(0, 16);
        document.getElementById('deliveryDate').min = tomorrowStr;
    </script>
</body>
</html>