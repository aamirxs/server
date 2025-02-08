<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn() && !in_array($_SERVER['REQUEST_URI'], ['/login.php', '/register.php'])) {
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Management Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php include 'includes/header.php'; ?>

    <div class="flex">
        <!-- Sidebar -->
        <div class="w-64 bg-gray-800 h-screen fixed">
            <nav class="mt-5">
                <a href="index.php" class="block py-2 px-4 text-white hover:bg-gray-700">Dashboard</a>
                <a href="modules/file-manager" class="block py-2 px-4 text-white hover:bg-gray-700">File Manager</a>
                <a href="modules/terminal" class="block py-2 px-4 text-white hover:bg-gray-700">Terminal</a>
                <a href="modules/python-deploy" class="block py-2 px-4 text-white hover:bg-gray-700">Python Deployment</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="ml-64 p-8 w-full">
            <?php
            $module = $_GET['module'] ?? 'dashboard';
            $allowed_modules = ['dashboard', 'file-manager', 'terminal', 'python-deploy'];
            
            if (in_array($module, $allowed_modules)) {
                include "modules/$module/index.php";
            } else {
                include "modules/dashboard/index.php";
            }
            ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html> 