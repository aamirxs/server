<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Management Panel</title>
    
    <!-- Tailwind CSS -->
    <link href="https://cdn.tailwindcss.com" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom Styles -->
    <style>
        .sidebar-item:hover .sidebar-icon {
            transform: translateX(5px);
            transition: transform 0.2s;
        }
        
        .loading-spinner {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-gray-900 text-white flex flex-col">
            <!-- Logo -->
            <div class="p-4 border-b border-gray-800">
                <h1 class="text-xl font-bold">Server Panel</h1>
                <p class="text-sm text-gray-400">Management Console</p>
            </div>
            
            <!-- Navigation -->
            <nav class="flex-1 p-4">
                <div class="space-y-2">
                    <a href="index.php" class="sidebar-item flex items-center p-3 text-gray-300 rounded hover:bg-gray-800 hover:text-white">
                        <i class="fas fa-tachometer-alt w-6 sidebar-icon"></i>
                        <span>Dashboard</span>
                    </a>
                    
                    <a href="modules/file-manager" class="sidebar-item flex items-center p-3 text-gray-300 rounded hover:bg-gray-800 hover:text-white">
                        <i class="fas fa-folder w-6 sidebar-icon"></i>
                        <span>File Manager</span>
                    </a>
                    
                    <a href="modules/terminal" class="sidebar-item flex items-center p-3 text-gray-300 rounded hover:bg-gray-800 hover:text-white">
                        <i class="fas fa-terminal w-6 sidebar-icon"></i>
                        <span>Terminal</span>
                    </a>
                    
                    <a href="modules/python-deploy" class="sidebar-item flex items-center p-3 text-gray-300 rounded hover:bg-gray-800 hover:text-white">
                        <i class="fab fa-python w-6 sidebar-icon"></i>
                        <span>Python Deploy</span>
                    </a>
                    
                    <a href="modules/monitoring" class="sidebar-item flex items-center p-3 text-gray-300 rounded hover:bg-gray-800 hover:text-white">
                        <i class="fas fa-chart-line w-6 sidebar-icon"></i>
                        <span>Monitoring</span>
                    </a>
                    
                    <a href="modules/backup" class="sidebar-item flex items-center p-3 text-gray-300 rounded hover:bg-gray-800 hover:text-white">
                        <i class="fas fa-database w-6 sidebar-icon"></i>
                        <span>Backup</span>
                    </a>
                </div>
            </nav>
            
            <!-- User Profile -->
            <div class="p-4 border-t border-gray-800">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center">
                        <i class="fas fa-user text-gray-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium"><?php echo $_SESSION['username'] ?? 'Admin'; ?></p>
                        <a href="logout.php" class="text-xs text-gray-400 hover:text-white">Logout</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Bar -->
            <header class="bg-white shadow-sm">
                <div class="flex items-center justify-between p-4">
                    <div class="flex items-center space-x-4">
                        <button id="menu-toggle" class="text-gray-500 hover:text-gray-700">
                            <i class="fas fa-bars"></i>
                        </button>
                        <h2 class="text-xl font-semibold"><?php echo $page_title ?? 'Dashboard'; ?></h2>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="flex items-center space-x-4">
                        <button class="bg-gray-100 p-2 rounded-full hover:bg-gray-200" title="Notifications">
                            <i class="fas fa-bell text-gray-600"></i>
                        </button>
                        <button class="bg-gray-100 p-2 rounded-full hover:bg-gray-200" title="Settings">
                            <i class="fas fa-cog text-gray-600"></i>
                        </button>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto bg-gray-50 p-6"> 