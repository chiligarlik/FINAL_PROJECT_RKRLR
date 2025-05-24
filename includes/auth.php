<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isSupervisor() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'supervisor';
}

function isOfficer() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'officer';
}

function isJuniorOfficer() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'junior_officer';
}

function checkDepartment($department) {
    return isset($_SESSION['department']) && $_SESSION['department'] === $department;
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header('Location: /users/login.php');
        exit();
    }
}

function redirectIfNotAuthorized($requiredRole = null, $requiredDepartment = null) {
    if ($requiredRole && $_SESSION['role'] !== $requiredRole) {
        header('Location: /index.php');
        exit();
    }
    if ($requiredDepartment && $_SESSION['department'] !== $requiredDepartment) {
        header('Location: /index.php');
        exit();
    }
}
?>