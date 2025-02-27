<?php
session_start();
include('server/database.php');

// Redirect if already logged in
if (isset($_SESSION['employeeID']) && !empty($_SESSION['employeeID']) && $_SESSION['employee_role'] === 'Admin') {
    header('Location: admin/dashboard.php');
    exit();
}

// Define regex for email validation
$emailRegex = "/^[a-zA-Z0-9._%-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/";
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['email']) && isset($_POST['password'])) {
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        
        // Validate email format
        if (!preg_match($emailRegex, $email)) {
            $error = "Invalid email format";
        } else {
            // Query the database for admin employees
            $stmt = $conn->prepare("
                SELECT EmployeeID, FirstName, LastName, Role, employeePassword, Status, employeeEmail
                FROM employees
                WHERE employeeEmail = ? AND Role = 'Admin'
            ");
            
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $admin = $result->fetch_assoc();
                
                // Check if account is active
                if ($admin['Status'] === 'Inactive') {
                    $error = "Your account is currently inactive. Please contact your administrator.";
                }
                // Verify password
                elseif (password_verify($password, $admin['employeePassword'])) {
                    // Set session variables
                    $_SESSION['employeeID'] = $admin['EmployeeID'];
                    $_SESSION['employee_name'] = $admin['FirstName'] . ' ' . $admin['LastName'];
                    $_SESSION['employee_email'] = $admin['employeeEmail'];
                    $_SESSION['employee_role'] = $admin['Role'];
                    $_SESSION['employee_status'] = $admin['Status'];
                    
                    // Redirect to dashboard or stored URL
                    $redirect = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : 'admin/dashboard.php';
                    unset($_SESSION['redirect_after_login']); // Clear the stored URL
                    
                    header("Location: $redirect");
                    exit();
                } else {
                    $error = "Incorrect password";
                }
            } else {
                $error = "No admin account found with that email";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stockport - Admin Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .error-message {
            color: #dc3545;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- Split Layout Form -->
    <div class="container">
        <div class="split-form">
            <div class="image-side">
                <h2>Admin Login Page</h2>
                <p>Please enter your credentials.</p>
            </div>
            <div class="form-side">
                <h2>Sign In</h2>
                <?php if(!empty($error)): ?>
                    <div class="error-message">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                <!-- Fixed form action to explicitly post to this page -->
                <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                    <input type="email" name="email" placeholder="Email" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <!-- Fixed button to be a proper submit button without JavaScript -->
                    <button type="submit" class="btn mt-3">Login</button>
                </form>
                <div class="form-links">
                    <a href="forgot-password.php">Forgot Password?</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>