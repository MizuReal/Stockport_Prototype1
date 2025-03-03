<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Stockport - Inventory Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/mainindex.css">
</head>
<body>
    <div class="landing-container">
        <header class="hero">
            <nav class="navbar">
                <div class="logo">Stockport</div>
                <div class="nav-links">
                    <a href="#features">Features</a>
                    <a href="#about">About</a>
                    <a href="employee-login.php" class="nav-login">Employee Login</a>
                </div>
            </nav>
            
            <div class="hero-content">
                <h1>Streamline Your Metals Inventory Management</h1>
                <p>Efficient. Reliable. Secure.</p>
                <div class="hero-buttons">
                    <a href="employee-register.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Apply as Employee
                    </a>
                    <a href="customer-apply.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Register as Customer
                    </a>
                    <a href="employee-login.php" class="btn btn-secondary">
                        <i class="fas fa-sign-in-alt"></i> Employee Portal
                    </a>
                </div>
            </div>
        </header>

        <section id="features" class="features">
            <h2>Why Choose Stockport?</h2>
            <div class="feature-grid">
                <div class="feature-card">
                    <i class="fas fa-chart-line"></i>
                    <h3>Real-time Tracking</h3>
                    <p>Monitor your inventory levels in real-time with accurate updates.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-shield-alt"></i>
                    <h3>Secure System</h3>
                    <p>Advanced security measures to protect your valuable data.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-tasks"></i>
                    <h3>Easy Management</h3>
                    <p>Intuitive interface for effortless inventory control.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-users"></i>
                    <h3>Team Collaboration</h3>
                    <p>Work together seamlessly with role-based access.</p>
                </div>
            </div>
        </section>

        <section id="about" class="about">
            <div class="about-content">
                <h2>About Stockport</h2>
                <p>Stockport is a comprehensive inventory management system designed to help businesses maintain optimal stock levels, reduce costs, and improve efficiency. Join our team and be part of a revolutionary approach to inventory management.</p>
            </div>
        </section>

        <footer class="footer">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <a href="employee-register.php">Apply as Employee</a>
                    <a href="customer-apply.php">Register as Customer</a>
                    <a href="employee-login.php">Employee Login</a>
                </div>
                <div class="footer-section">
                    <h3>Contact</h3>
                    <p><i class="fas fa-envelope"></i> support@stockport.com</p>
                    <p><i class="fas fa-phone"></i> (555) 123-4567</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2023 Stockport. All rights reserved.</p>
            </div>
        </footer>
    </div>
</body>
</html>
