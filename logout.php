<?php
session_start();

require_once 'config/database.php';

if (isset($_SESSION['admin_id'])) {
    $username = $_SESSION['admin_username'] ?? 'admin';
    try {
        logActivity($pdo, 'logout', "Admin logged out: " . $username, $username, 'auth', $_SESSION['admin_id']);
    } catch(Exception $e) {}
} elseif (isset($_SESSION['teacher_id'])) {
    $username = $_SESSION['teacher_username'] ?? 'teacher';
    try {
        logActivity($pdo, 'logout', "Teacher logged out: " . $username, $username, 'auth', $_SESSION['teacher_id']);
    } catch(Exception $e) {}
} elseif (isset($_SESSION['parent_id'])) {
    $username = $_SESSION['parent_username'] ?? 'parent';
    try {
        logActivity($pdo, 'logout', "Parent logged out: " . $username, $username, 'auth', $_SESSION['parent_id']);
    } catch(Exception $e) {}
} elseif (isset($_SESSION['student_id'])) {
    $username = $_SESSION['student_username'] ?? 'student';
    try {
        logActivity($pdo, 'logout', "Student logged out: " . $username, $username, 'auth', $_SESSION['student_id']);
    } catch(Exception $e) {}
}

session_destroy();
header("Location: login.php?logout=success");
exit();
