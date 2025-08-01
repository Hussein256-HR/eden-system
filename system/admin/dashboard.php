<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Require login
require_login();

// Get dashboard statistics
$stats = get_dashboard_stats();
$recent_activities = get_recent_activities(4);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eden Miracle Church Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --sidebar-width: 280px;
            --sidebar-bg: #1a1a2e;
            --sidebar-active: #16213e;
            --primary-color: #0f3460;
            --secondary-color: #e94560;
            --accent-color: #f8a44c;
        }
        
        [data-theme="dark"] {
            --sidebar-bg: #0d1117;
            --sidebar-active: #21262d;
            --primary-color: #238636;
            --secondary-color: #f85149;
            --accent-color: #ffa657;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            transition: background-color 0.3s, color 0.3s;
        }
        
        [data-theme="dark"] body {
            background-color: #0d1117;
            color: #c9d1d9;
        }
        
        [data-theme="dark"] .card {
            background-color: #161b22;
            border-color: #30363d;
            color: #c9d1d9;
        }
        
        [data-theme="dark"] .navbar {
            background-color: #161b22 !important;
            border-bottom: 1px solid #30363d;
        }
        
        [data-theme="dark"] .form-control,
        [data-theme="dark"] .form-select {
            background-color: #21262d;
            border-color: #30363d;
            color: #c9d1d9;
        }
        
        [data-theme="dark"] .form-control:focus,
        [data-theme="dark"] .form-select:focus {
            background-color: #21262d;
            border-color: #58a6ff;
            color: #c9d1d9;
        }
        
        [data-theme="dark"] .table {
            color: #c9d1d9;
        }
        
        [data-theme="dark"] .table-striped > tbody > tr:nth-of-type(odd) > td {
            background-color: rgba(255, 255, 255, 0.05);
        }
        
        [data-theme="dark"] .dropdown-menu {
            background-color: #21262d;
            border-color: #30363d;
        }
        
        [data-theme="dark"] .dropdown-item {
            color: #c9d1d9;
        }
        
        [data-theme="dark"] .dropdown-item:hover {
            background-color: #30363d;
            color: #c9d1d9;
        }
        
        .sidebar {
            background-color: var(--sidebar-bg);
            color: white;
            height: 100vh;
            position: fixed;
            width: var(--sidebar-width);
            transition: all 0.3s;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            overflow-y: auto;
            transform: translateX(0);
        }
        
        .sidebar-header {
            padding: 20px;
            background-color: rgba(0, 0, 0, 0.2);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            border-radius: 5px;
            margin: 5px 10px;
            padding: 10px 15px;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .sidebar .nav-link:hover, 
        .sidebar .nav-link.active {
            background-color: var(--sidebar-active);
            color: white;
        }
        
        .sidebar .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            transition: all 0.3s;
            min-height: 100vh;
        }
        
        .navbar {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 15px 20px;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 20px;
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .stats-card {
            transition: transform 0.2s;
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
        }
        
        .form-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        [data-theme="dark"] .form-section {
            background: #161b22;
        }
        
        .error-message {
            display: none;
            color: #dc3545;
            font-size: 0.875em;
            margin-top: 5px;
        }
        
        .success-message {
            display: none;
            color: #28a745;
            font-size: 0.875em;
            margin-top: 5px;
        }
        
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
        
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .content-section {
            display: none;
        }
        
        .content-section.active {
            display: block;
            animation: fadeIn 0.3s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .nav-link.dropdown-toggle {
            position: relative;
            cursor: pointer;
        }
        
        .nav-link.dropdown-toggle::after {
            content: '\f282';
            font-family: 'bootstrap-icons';
            float: right;
            margin-top: 2px;
            transition: transform 0.3s ease;
        }
        
        .nav-link.dropdown-toggle[aria-expanded="true"]::after {
            transform: rotate(90deg);
        }
        
        .collapse .nav-link {
            padding: 8px 15px;
            font-size: 0.9em;
            color: rgba(255, 255, 255, 0.7);
            border-left: 2px solid rgba(255, 255, 255, 0.1);
            margin-left: 10px;
        }
        
        .collapse .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.05);
            color: white;
            border-left-color: var(--secondary-color);
        }
        
        .collapse .nav-link i {
            margin-right: 8px;
            width: 16px;
            text-align: center;
        }
        
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .dashboard-stats {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-stats {
                grid-template-columns: 1fr;
            }
            
            .table-responsive {
                font-size: 0.875rem;
            }
        }
        
        #sidebarToggle {
            display: none;
            background: var(--primary-color);
            border: none;
            color: white;
            border-radius: 5px;
            padding: 8px 12px;
        }
        
        @media (max-width: 992px) {
            #sidebarToggle {
                display: block;
            }
        }
        
        .sidebar::-webkit-scrollbar {
            width: 5px;
        }
        
        .sidebar::-webkit-scrollbar-thumb {
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
        }
        
        .brand-link {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: white;
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .brand-link:hover {
            color: white;
        }
        
        .brand-link i {
            margin-right: 10px;
            font-size: 1.5rem;
        }
        
        .recent-activities {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .activity-item {
            border-left: 3px solid var(--primary-color);
            padding-left: 15px;
            margin-bottom: 15px;
        }
        
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }
        
        @media (max-width: 992px) {
            .overlay.active {
                display: block;
            }
        }
        
        .theme-toggle {
            background: none;
            border: none;
            color: inherit;
            font-size: 1.2rem;
            cursor: pointer;
            transition: transform 0.3s;
        }
        
        .theme-toggle:hover {
            transform: scale(1.1);
        }
    </style>
</head>
<body>
    <div class="overlay" id="overlay"></div>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="#" class="brand-link">
                    <i class="bi bi-building"></i>
                    <span>Eden Church Admin</span>
                </a>
            </div>
            
            <div class="px-3 pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="#" data-page="dashboard">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    
                    <!-- Users Management Dropdown -->
                    <li class="nav-item">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="collapse" data-bs-target="#usersDropdown" aria-expanded="false">
                            <i class="bi bi-people"></i> Users
                        </a>
                        <div class="collapse" id="usersDropdown">
                            <ul class="nav flex-column ms-3">
                                <li class="nav-item">
                                    <a class="nav-link" href="#" data-page="add-user">
                                        <i class="bi bi-person-plus"></i> Add User
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#" data-page="view-users">
                                        <i class="bi bi-people-fill"></i> View Users
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    
                    <!-- Ministers -->
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-page="ministers">
                            <i class="bi bi-person-badge"></i> Ministers
                        </a>
                    </li>
                    
                    <!-- Departments Dropdown -->
                    <li class="nav-item">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="collapse" data-bs-target="#departmentsDropdown" aria-expanded="false">
                            <i class="bi bi-diagram-3"></i> Departments
                        </a>
                        <div class="collapse" id="departmentsDropdown">
                            <ul class="nav flex-column ms-3">
                                <li class="nav-item">
                                    <a class="nav-link" href="#" data-page="service-coordination">
                                        <i class="bi bi-gear"></i> Service Coordination
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#" data-page="children">
                                        <i class="bi bi-heart"></i> Children
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#" data-page="choir">
                                        <i class="bi bi-music-note"></i> Choir
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#" data-page="ushering">
                                        <i class="bi bi-hand-thumbs-up"></i> Ushering
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#" data-page="security">
                                        <i class="bi bi-shield"></i> Security
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    
                    <!-- Generations -->
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-page="generations">
                            <i class="bi bi-people-fill"></i> Generations
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-page="report">
                            <i class="bi bi-file-text"></i> Reports
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content w-100">
            <nav class="navbar navbar-expand-lg navbar-light">
                <div class="container-fluid">
                    <button id="sidebarToggle" class="btn me-3">
                        <i class="bi bi-list"></i>
                    </button>
                    <a class="navbar-brand fw-bold text-dark" href="#" id="currentPageTitle">Church Dashboard</a>
                    
                    <div class="d-flex align-items-center ms-auto">
                        <button class="theme-toggle me-3" id="themeToggle" title="Toggle dark mode">
                            <i class="bi bi-moon-fill" id="themeIcon"></i>
                        </button>
                        <div class="me-3">
                            <span class="me-2">
                                <i class="bi bi-calendar me-1"></i> 
                                <span id="currentDateTime"></span>
                            </span>
                        </div>
                        <div class="dropdown">
                            <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" 
                               id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle fs-4 me-2"></i>
                                <span class="d-none d-md-inline"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownUser">
                                <li><a class="dropdown-item" href="#" data-page="profile"><i class="bi bi-person me-2"></i> Profile</a></li>
                                <li><a class="dropdown-item" href="#" data-page="settings"><i class="bi bi-gear me-2"></i> Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="../logout.php"><i class="bi bi-box-arrow-right me-2"></i> Sign out</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>
            
            <div class="container-fluid p-4">
                <!-- Dashboard Content -->
                <div class="content-section active" id="dashboard-content">
                    <div class="dashboard-stats">
                        <div class="card stats-card">
                            <div class="card-body bg-danger">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="card-title text-white">Total Members</h3>
                                        <h2 class="text-white" id="totalMembers"><?php echo $stats['total_members']; ?></h2>
                                    </div>
                                    <i class="bi bi-people fs-1 text-white"></i>
                                </div>
                            </div>
                        </div>
                        <div class="card stats-card">
                            <div class="card-body bg-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="card-title text-success">Total Staff</h3>
                                        <h2 class="text-success" id="totalStaff"><?php echo $stats['total_staff']; ?></h2>
                                    </div>
                                    <i class="bi bi-briefcase fs-1 text-success"></i>
                                </div>
                            </div>
                        </div>
                        <div class="card stats-card bg-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="card-title text-black">Ministers</h3>
                                        <h2 class="text-black" id="totalMinisters"><?php echo $stats['total_ministers']; ?></h2>
                                    </div>
                                    <i class="bi bi-person-badge fs-1 text-black"></i>
                                </div>
                            </div>
                        </div>
                        <div class="card stats-card">
                            <div class="card-body bg-primary">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="card-title text-white">Departments</h3>
                                        <h2 class="text-white" id="totalDepartments"><?php echo $stats['total_departments']; ?></h2>
                                    </div>
                                    <i class="bi bi-diagram-3 fs-1 text-white"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h3 class="card-title">Recent Activities</h3>
                                </div>
                                <div class="card-body recent-activities" id="recentActivities">
                                    <?php foreach ($recent_activities as $activity): ?>
                                    <div class="activity-item">
                                        <h5><?php echo htmlspecialchars($activity['description']); ?></h5>
                                        <p class="text-muted">By: <?php echo htmlspecialchars($activity['user_name'] ?? 'System'); ?></p>
                                        <small><?php echo format_datetime($activity['created_at']); ?></small>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h3 class="card-title">Quick Stats</h3>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <small class="text-muted">Total Children</small>
                                        <h4 id="totalChildren"><?php echo $stats['total_children']; ?></h4>
                                    </div>
                                    <div class="mb-3">
                                        <small class="text-muted">Total Generations</small>
                                        <h4 id="totalGenerations"><?php echo $stats['total_generations']; ?></h4>
                                    </div>
                                    <div class="mb-3">
                                        <small class="text-muted">This Week's Reports</small>
                                        <h4 id="weeklyReports"><?php echo $stats['weekly_reports']; ?></h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Other content sections will be loaded dynamically -->
                <div class="content-section" id="add-user-content">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h3 class="card-title">Add New User</h3>
                        </div>
                        <div class="card-body">
                            <form id="addUserForm">
                                <div class="mb-3">
                                    <label class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" name="fullName" placeholder="Enter full name" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Username *</label>
                                    <input type="text" class="form-control" name="username" placeholder="Enter username" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" placeholder="Enter email">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Password *</label>
                                    <input type="password" class="form-control" name="password" placeholder="Enter password" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Role *</label>
                                    <select class="form-select" name="role" required>
                                        <option value="">Select Role</option>
                                        <option value="viewer">Viewer</option>
                                        <option value="staff">Staff</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary">Add User</button>
                                <button type="reset" class="btn btn-secondary ms-2">Reset Form</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- View Users Content -->
                <div class="content-section" id="view-users-content">
                    <div class="card">
                        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                            <h3 class="card-title mb-0">View All Users</h3>
                            <button class="btn btn-sm btn-light" onclick="exportUsers()">
                                <i class="bi bi-download"></i> Export
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <input type="text" id="userSearchInput" class="form-control" placeholder="Search users...">
                                </div>
                                <div class="col-md-6 text-end">
                                    <span class="badge bg-primary fs-6">Total Users: <span id="userCount">0</span></span>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped" id="usersTable">
                                    <thead>
                                        <tr>
                                            <th>Full Name</th>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Date Added</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="usersTableBody">
                                        <!-- Dynamic content will be populated here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Other sections will be added here -->
                <div class="content-section" id="ministers-content">
                    <div class="text-center">
                        <h3>Ministers Registration</h3>
                        <p>Ministers registration form will be loaded here.</p>
                    </div>
                </div>
                
                <div class="content-section" id="generations-content">
                    <div class="text-center">
                        <h3>Generations Management</h3>
                        <p>Generations management interface will be loaded here.</p>
                    </div>
                </div>
                
                <div class="content-section" id="service-coordination-content">
                    <div class="text-center">
                        <h3>Service Coordination</h3>
                        <p>Service coordination reports will be loaded here.</p>
                    </div>
                </div>
                
                <div class="content-section" id="children-content">
                    <div class="text-center">
                        <h3>Children Department</h3>
                        <p>Children department management will be loaded here.</p>
                    </div>
                </div>
                
                <div class="content-section" id="choir-content">
                    <div class="text-center">
                        <h3>Choir Department</h3>
                        <p>Choir department management will be loaded here.</p>
                    </div>
                </div>
                
                <div class="content-section" id="ushering-content">
                    <div class="text-center">
                        <h3>Ushering Department</h3>
                        <p>Ushering department management will be loaded here.</p>
                    </div>
                </div>
                
                <div class="content-section" id="security-content">
                    <div class="text-center">
                        <h3>Security Department</h3>
                        <p>Security department management will be loaded here.</p>
                    </div>
                </div>
                
                <div class="content-section" id="report-content">
                    <div class="text-center">
                        <h3>Reports</h3>
                        <p>Reports system will be loaded here.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
    
    <script>
        // Dashboard JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            // Set up navigation
            const navLinks = document.querySelectorAll('[data-page]');
            const contentSections = document.querySelectorAll('.content-section');
            const currentPageTitle = document.getElementById('currentPageTitle');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const themeToggle = document.getElementById('themeToggle');
            const themeIcon = document.getElementById('themeIcon');
            
            // Set current date/time
            function updateDateTime() {
                const now = new Date();
                const options = { 
                    weekday: 'long', 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                };
                document.getElementById('currentDateTime').textContent = now.toLocaleDateString('en-US', options);
            }
            
            updateDateTime();
            setInterval(updateDateTime, 60000); // Update every minute
            
            // Navigation function
            function navigateToPage(pageId) {
                // Hide all content sections
                contentSections.forEach(section => {
                    section.classList.remove('active');
                });
                
                // Show the selected content section
                const targetSection = document.getElementById(`${pageId}-content`);
                if (targetSection) {
                    targetSection.classList.add('active');
                    
                    // Update page title
                    const pageName = pageId.replace(/-/g, ' ');
                    currentPageTitle.textContent = pageName.charAt(0).toUpperCase() + pageName.slice(1);
                    
                    // Close sidebar on mobile after navigation
                    if (window.innerWidth < 992) {
                        sidebar.classList.remove('active');
                        overlay.classList.remove('active');
                    }
                }
                
                // Load page content dynamically
                if (pageId === 'view-users') {
                    loadUsers();
                }
            }
            
            // Add event listeners to navigation links
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const pageId = this.getAttribute('data-page');
                    navigateToPage(pageId);
                    
                    // Update active link in sidebar
                    navLinks.forEach(navLink => {
                        navLink.classList.remove('active');
                    });
                    this.classList.add('active');
                });
            });
            
            // Set dashboard as active initially
            navigateToPage('dashboard');
            
            // Mobile sidebar toggle
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
            });
            
            // Close sidebar when clicking overlay
            overlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            });
            
            // Theme toggle
            themeToggle.addEventListener('click', function() {
                const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
                if (isDark) {
                    document.documentElement.removeAttribute('data-theme');
                    themeIcon.className = 'bi bi-moon-fill';
                } else {
                    document.documentElement.setAttribute('data-theme', 'dark');
                    themeIcon.className = 'bi bi-sun-fill';
                }
            });
            
            // User management functions
            function loadUsers() {
                fetch('../api/users.php?action=list')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            displayUsers(data.data);
                            document.getElementById('userCount').textContent = data.total;
                        }
                    })
                    .catch(error => console.error('Error loading users:', error));
            }
            
            function displayUsers(users) {
                const tbody = document.getElementById('usersTableBody');
                tbody.innerHTML = '';
                
                users.forEach(user => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${user.full_name}</td>
                        <td>${user.username}</td>
                        <td>${user.email || 'N/A'}</td>
                        <td><span class="badge bg-${getRoleBadgeColor(user.role)}">${user.role}</span></td>
                        <td>${formatDate(user.created_at)}</td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="editUser(${user.id})">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteUser(${user.id})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    `;
                    tbody.appendChild(row);
                });
            }
            
            function getRoleBadgeColor(role) {
                switch(role) {
                    case 'admin': return 'danger';
                    case 'staff': return 'warning';
                    case 'viewer': return 'info';
                    default: return 'secondary';
                }
            }
            
            function formatDate(dateString) {
                const date = new Date(dateString);
                return date.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                });
            }
            
            // Add user form submission
            document.getElementById('addUserForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const userData = Object.fromEntries(formData);
                
                fetch('../api/users.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(userData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('User added successfully!');
                        this.reset();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while adding the user.');
                });
            });
            
            // Search functionality
            document.getElementById('userSearchInput').addEventListener('input', function() {
                const searchTerm = this.value;
                if (searchTerm.length > 2 || searchTerm.length === 0) {
                    fetch(`../api/users.php?action=list&search=${encodeURIComponent(searchTerm)}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                displayUsers(data.data);
                                document.getElementById('userCount').textContent = data.total;
                            }
                        })
                        .catch(error => console.error('Error searching users:', error));
                }
            });
        });
        
        // Global functions
        function editUser(userId) {
            // Implementation for editing user
            console.log('Edit user:', userId);
        }
        
        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user?')) {
                fetch(`../api/users.php?id=${userId}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('User deleted successfully!');
                        loadUsers(); // Reload the users list
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the user.');
                });
            }
        }
        
        function exportUsers() {
            // Implementation for exporting users
            console.log('Export users');
        }
    </script>
</body>
</html>