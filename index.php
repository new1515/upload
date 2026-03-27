<?php require_once 'config/database.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="logo">
                <i class="fas fa-graduation-cap"></i>
                School PHP AI
            </a>
            <ul class="nav-links">
                <li><a href="#home">Home</a></li>
                <li><a href="#features">Features</a></li>
                <li><a href="login.php">Admin Login</a></li>
            </ul>
        </div>
    </nav>
    
    <section class="hero" id="home">
        <div class="container">
            <div class="book-animation">
                <div class="book"></div>
            </div>
            <h1>Welcome to School Management System</h1>
            <p>A comprehensive solution for managing students, teachers, and academic records</p>
            <a href="login.php" class="btn">Get Started <i class="fas fa-arrow-right"></i></a>
        </div>
    </section>
    
    <section class="features" id="features">
        <div class="container">
            <h2>Our Features</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <i class="fas fa-user-graduate"></i>
                    <h3>Student Management</h3>
                    <p>Efficiently manage student records including enrollment, personal details, class assignments, and contact information.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <h3>Teacher Management</h3>
                    <p>Organize teacher information, subject assignments, and contact details all in one place.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-chart-line"></i>
                    <h3>Results Management</h3>
                    <p>Track and manage student academic performance with comprehensive result management system.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-book-open"></i>
                    <h3>Subject Management</h3>
                    <p>Handle all subjects across different classes with easy subject creation and assignment.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-school"></i>
                    <h3>Class Management</h3>
                    <p>Organize classes by name and section for better academic structure and management.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-lock"></i>
                    <h3>Secure System</h3>
                    <p>Protected admin access with secure login system to ensure data safety and privacy.</p>
                </div>
            </div>
        </div>
    </section>
    
    <footer class="footer">
        <div class="container">
            <p>&copy; 2026 School Management System. All rights reserved.</p>
        </div>
    </footer>
    
    <script src="assets/js/script.js"></script>
</body>
</html>
