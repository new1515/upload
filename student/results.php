<?php require_once '../config/database.php'; if (!isset($_SESSION['student_id'])) { redirect('../login.php'); } $studentId = $_SESSION['student_id']; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Results - Student Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .portal-header { background: linear-gradient(135deg, #e74c3c, #c0392b); color: white; padding: 25px 30px; border-radius: 15px; margin-bottom: 25px; }
        .result-card { background: white; border-radius: 15px; padding: 20px; margin-bottom: 20px; box-shadow: 0 3px 15px rgba(0,0,0,0.08); }
    </style>
</head>
<body>
    <button class="mobile-menu-toggle">
        <i class="fas fa-bars"></i>
    </button>
    <div class="sidebar-overlay"></div>
    <nav class="sidebar">
        <div class="sidebar-brand"><i class="fas fa-user-graduate"></i> Student Portal</div>
        <ul class="sidebar-menu">
            <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a></li>
            <li><a href="results.php" class="active"><i class="fas fa-chart-line"></i> My Results</a></li>
            <li><a href="reportcard.php"><i class="fas fa-file-alt"></i> Report Card</a></li>
            <li><a href="lessons.php"><i class="fas fa-book-open"></i> Lessons</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>
    <main class="main-content">
        <div class="header"><h1><i class="fas fa-chart-line"></i> My Results</h1></div>
        <div class="school-header">
            <div class="school-logo"><i class="fas fa-graduation-cap"></i></div>
            <div class="school-info"><h1>School Name</h1></div>
        </div>
        <div class="content">
            <div class="portal-header" style="display: block; text-align: center;">
                <h2 style="margin: 0;"><i class="fas fa-chart-line"></i> My Academic Results</h2>
            </div>
            <div class="result-card" style="text-align: center;">
                <p style="color: #888;">No results available yet. Results will appear here once entered by teachers.</p>
            </div>
        </div>
    </main>
</body>
</html>
