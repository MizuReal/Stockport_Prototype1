<?php
// Start session if one doesn't exist already
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get current page name
$current_page = basename($_SERVER['PHP_SELF']);

include '../server/database.php';
require_once '../admin/session_check_admin.php';
requireAdminAccess();

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize inputs to prevent XSS
    function sanitize_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = sanitize_input($_POST['name']);
                $phone = sanitize_input($_POST['phone']);
                $email = sanitize_input($_POST['email']);
                $address = sanitize_input($_POST['address']);

                // Improved validation
                $errors = [];
                if (empty($name)) $errors[] = "Customer name is required.";
                if (empty($phone)) $errors[] = "Phone number is required.";
                if (empty($email)) $errors[] = "Email is required.";
                elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format.";
                if (empty($address)) $errors[] = "Address is required.";

                if (!empty($errors)) {
                    echo "<script>alert('" . implode("\\n", $errors) . "');</script>";
                    break;
                }

                $sql = "INSERT INTO customers (CustomerName, Phone, Email, Address) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);

                if ($stmt === false) {
                    error_log("Error preparing statement (add): " . $conn->error);
                    echo "<script>alert('Error preparing statement: " . htmlspecialchars($conn->error) . "');</script>";
                    break;
                }

                $stmt->bind_param("ssss", $name, $phone, $email, $address);

                if ($stmt->execute()) {
                    echo "<script>alert('New customer added successfully'); window.location.href = 'customers.php';</script>";
                } else {
                    error_log("Error executing statement (add): " . $stmt->error);
                    echo "<script>alert('Error adding customer: " . htmlspecialchars($stmt->error) . "');</script>";
                }

                $stmt->close();
                break;

            case 'edit':
                $id = filter_var($_POST['customerid'], FILTER_VALIDATE_INT);
                $name = sanitize_input($_POST['name']);
                $phone = sanitize_input($_POST['phone']);
                $email = sanitize_input($_POST['email']);
                $address = sanitize_input($_POST['address']);

                // Improved validation
                $errors = [];
                if (!$id) $errors[] = "Invalid customer ID.";
                if (empty($name)) $errors[] = "Customer name is required.";
                if (empty($phone)) $errors[] = "Phone number is required.";
                if (empty($email)) $errors[] = "Email is required.";
                elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format.";
                if (empty($address)) $errors[] = "Address is required.";

                if (!empty($errors)) {
                    echo "<script>alert('" . implode("\\n", $errors) . "');</script>";
                    break;
                }

                $sql = "UPDATE customers SET CustomerName=?, Phone=?, Email=?, Address=? WHERE CustomerID=?";
                $stmt = $conn->prepare($sql);

                if ($stmt === false) {
                    error_log("Error preparing statement (edit): " . $conn->error);
                    echo "<script>alert('Error preparing statement: " . htmlspecialchars($conn->error) . "');</script>";
                    break;
                }

                $stmt->bind_param("ssssi", $name, $phone, $email, $address, $id);

                if ($stmt->execute()) {
                    echo "<script>alert('Customer updated successfully'); window.location.href = 'customers.php';</script>";
                } else {
                    error_log("Error executing statement (edit): " . $stmt->error);
                    echo "<script>alert('Error updating customer: " . htmlspecialchars($stmt->error) . "');</script>";
                }

                $stmt->close();
                break;

            case 'delete':
                $id = filter_var($_POST['customerid'], FILTER_VALIDATE_INT);
                
                if (!$id) {
                    echo "<script>alert('Invalid customer ID for deletion.');</script>";
                    break;
                }

                $sql = "DELETE FROM customers WHERE CustomerID=?";
                $stmt = $conn->prepare($sql);

                if ($stmt === false) {
                    error_log("Error preparing statement (delete): " . $conn->error);
                    echo "<script>alert('Error preparing statement: " . htmlspecialchars($conn->error) . "');</script>";
                    break;
                }

                $stmt->bind_param("i", $id);

                if ($stmt->execute()) {
                    echo "<script>alert('Customer deleted successfully'); window.location.href = 'customers.php';</script>";
                } else {
                    error_log("Error executing statement (delete): " . $stmt->error);
                    echo "<script>alert('Error deleting customer: " . htmlspecialchars($stmt->error) . "');</script>";
                }

                $stmt->close();
                break;
        }
    }
}

$sql = "SELECT * FROM customers ORDER BY CustomerID DESC";
$result = $conn->query($sql);
$customers = [];

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }
}

// Close connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Management - Warehouse System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        header h1 {
            color: white;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../layouts/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <header>
                <h1><i class="fas fa-users"></i> Customer Management</h1>
            </header>

            <div class="content">
                <button id="btnAddCustomer" class="btn-add-customer">
                    <i class="fas fa-plus"></i> Add New Customer
                </button>

                <div class="customer-table-container">
                    <table class="customer-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer Name</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Address</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($customers)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center;">No customers found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($customers as $customer): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($customer['CustomerID']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['CustomerName']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['Phone']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['Email']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['Address']); ?></td>
                                    <td>
                                        <button class="btn-edit" data-customerid="<?php echo htmlspecialchars($customer['CustomerID']); ?>">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn-delete" data-customerid="<?php echo htmlspecialchars($customer['CustomerID']); ?>">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="customerModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 id="modalTitle">Add New Customer</h2>
            <form id="customerForm" class="customer-form" method="post" action="customers.php">
                <input type="hidden" id="customerId" name="customerid" value="">
                <input type="hidden" id="formAction" name="action" value="add">

                <div class="form-group">
                    <label for="name">Customer Name</label>
                    <input type="text" id="name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="text" id="phone" name="phone" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" rows="3" required></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-submit">Submit</button>
                    <button type="button" class="btn-cancel">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Confirm Deletion</h2>
            <p>Are you sure you want to delete this customer? This action cannot be undone.</p>
            <form method="post" action="customers.php" id="deleteForm">
                <input type="hidden" id="deleteId" name="customerid" value="">
                <input type="hidden" name="action" value="delete">
                <div class="form-actions">
                    <button type="submit" class="btn-delete-confirm">Delete</button>
                    <button type="button" class="btn-cancel">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get DOM elements
            const deleteModal = document.getElementById('deleteModal');
            const customerModal = document.getElementById('customerModal');
            const deleteButtons = document.querySelectorAll('.btn-delete');
            const editButtons = document.querySelectorAll('.btn-edit');
            const btnAddCustomer = document.getElementById('btnAddCustomer');
            const modalCloseBtns = document.querySelectorAll('.close, .btn-cancel');
            const customerForm = document.getElementById('customerForm');
            
            // Add New Customer button
            btnAddCustomer.addEventListener('click', function() {
                document.getElementById('modalTitle').textContent = 'Add New Customer';
                document.getElementById('formAction').value = 'add';
                document.getElementById('customerId').value = '';
                customerForm.reset();
                customerModal.style.display = 'block';
            });

            // Delete buttons
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(event) {
                    event.preventDefault();
                    const customerId = this.dataset.customerid;
                    document.getElementById('deleteId').value = customerId;
                    deleteModal.style.display = 'block';
                });
            });

            // Edit buttons
            editButtons.forEach(button => {
                button.addEventListener('click', function(event) {
                    event.preventDefault();
                    document.getElementById('modalTitle').textContent = 'Edit Customer';
                    document.getElementById('formAction').value = 'edit';

                    const customerId = this.dataset.customerid;
                    document.getElementById('customerId').value = customerId;

                    // Find customer data from the array
                    const customer = <?php echo json_encode($customers); ?>.find(c => c.CustomerID == customerId);
                    
                    if (customer) {
                        document.getElementById('name').value = customer.CustomerName;
                        document.getElementById('phone').value = customer.Phone;
                        document.getElementById('email').value = customer.Email;
                        document.getElementById('address').value = customer.Address;
                        customerModal.style.display = 'block';
                    } else {
                        alert('Customer data not found');
                    }
                });
            });

            // Close modal buttons
            modalCloseBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    deleteModal.style.display = 'none';
                    customerModal.style.display = 'none';
                });
            });

            // Close modals when clicking outside
            window.addEventListener('click', function(event) {
                if (event.target === deleteModal) {
                    deleteModal.style.display = 'none';
                }
                if (event.target === customerModal) {
                    customerModal.style.display = 'none';
                }
            });

            // Form validation
            customerForm.addEventListener('submit', function(event) {
                const name = document.getElementById('name').value.trim();
                const phone = document.getElementById('phone').value.trim();
                const email = document.getElementById('email').value.trim();
                const address = document.getElementById('address').value.trim();
                
                let hasError = false;
                let errorMsg = '';
                
                if (!name) {
                    errorMsg += 'Customer name is required\n';
                    hasError = true;
                }
                
                if (!phone) {
                    errorMsg += 'Phone number is required\n';
                    hasError = true;
                }
                
                if (!email) {
                    errorMsg += 'Email is required\n';
                    hasError = true;
                } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    errorMsg += 'Invalid email format\n';
                    hasError = true;
                }
                
                if (!address) {
                    errorMsg += 'Address is required\n';
                    hasError = true;
                }
                
                if (hasError) {
                    event.preventDefault();
                    alert(errorMsg);
                }
            });
        });
    </script>
</body>
</html>