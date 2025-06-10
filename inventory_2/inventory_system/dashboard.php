<?php
    require_once "includes/auth.php";
    require_once "includes/db.php"; // Added database connection
    $user = $_SESSION['user'];
    
    // Get products with low stock
    $productsStock = $pdo->query("
    SELECT 
        p.Name, 
        p.ProductID, 
        p.Category, 
        p.Price,
        COALESCE(SUM(s.QuantityAdded), 0) - COALESCE((SELECT SUM(QuantitySold) FROM sales WHERE ProductID = p.ProductID), 0) AS CurrentStock
    FROM 
        products p
    LEFT JOIN 
        stock s ON s.ProductID = p.ProductID
    GROUP BY 
        p.ProductID, p.Name, p.Category, p.Price
    ORDER BY 
        CurrentStock ASC
    LIMIT 6
    ")->fetchAll();
    
    // Fetch system settings
    $settingsStmt = $pdo->query("SELECT * FROM system_settings LIMIT 1");
    $settings = $settingsStmt->fetch(PDO::FETCH_ASSOC);

    // Use the settings values or fallback to defaults if not found
    $criticalStockThreshold = isset($settings['critical_stock_threshold']) ? intval($settings['critical_stock_threshold']) : 3;
    $lowStockThreshold = isset($settings['low_stock_threshold']) ? intval($settings['low_stock_threshold']) : 5;
    
    // Get total number of products
    $totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    
    // Get total number of suppliers
    $totalSuppliers = $pdo->query("SELECT COUNT(*) FROM suppliers")->fetchColumn();
    
    // Get sales made today
    $todaySales = $pdo->query("SELECT COUNT(*) FROM sales WHERE DATE(SaleDate) = CURDATE()")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StockFlow - Inventory Management</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #4F46E5;
            --primary-dark: #4338CA;
            --primary-light: #E0E7FF;
            --secondary: #10B981;
            --secondary-light: #D1FAE5;
            --warning: #F59E0B;
            --warning-light: #FEF3C7;
            --danger: #EF4444;
            --danger-light: #FEE2E2;
            --dark: #111827;
            --gray: #6B7280;
            --light-gray: #F3F4F6;
            --white: #FFFFFF;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --border-radius: 0.375rem;
            --border-radius-lg: 0.75rem;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background-color: #F9FAFB;
            color: var(--dark);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        /* Header styles */
        header {
            background-color: var(--white);
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 40;
        }
        
        .header-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 2rem;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }
        
        .brand {
            display: flex;
            align-items: center;
        }
        
        .logo {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: var(--white);
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            margin-right: 0.75rem;
        }
        
        .brand-name {
            font-weight: 700;
            font-size: 1.25rem;
            color: var(--primary);
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .search-bar {
            position: relative;
            width: 300px;
        }
        
        .search-input {
            background-color: var(--light-gray);
            border: none;
            border-radius: 20px;
            padding: 0.5rem 1rem 0.5rem 2.5rem;
            width: 100%;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        
        .search-input:focus {
            background-color: var(--white);
            box-shadow: var(--shadow-sm);
            outline: none;
        }
        
        .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            font-size: 0.875rem;
        }
        
        .notification-bell {
            position: relative;
            color: var(--gray);
            font-size: 1.25rem;
            cursor: pointer;
            transition: color 0.2s;
        }
        
        .notification-bell:hover {
            color: var(--primary);
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--danger);
            color: var(--white);
            font-size: 0.625rem;
            font-weight: 600;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            cursor: pointer;
            gap: 0.75rem;
            padding: 0.5rem;
            border-radius: var(--border-radius);
            transition: background-color 0.2s;
        }
        
        .user-menu:hover {
            background-color: var(--light-gray);
        }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1rem;
        }
        
        .user-info {
            display: none;
        }
        
        @media (min-width: 768px) {
            .user-info {
                display: block;
            }
        }
        
        .user-name {
            font-weight: 500;
            font-size: 0.875rem;
        }
        
        .user-role {
            font-size: 0.75rem;
            color: var(--gray);
        }
        
        /* Navigation styles */
        nav {
            background-color: var(--white);
            padding: 0.75rem 2rem;
            box-shadow: 0 1px 0 0 rgba(0, 0, 0, 0.05);
        }
        
        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .nav-tabs {
            display: flex;
            gap: 0.25rem;
            overflow-x: auto;
            scrollbar-width: none;
        }
        
        .nav-tabs::-webkit-scrollbar {
            display: none;
        }
        
        .nav-tab {
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--gray);
            text-decoration: none;
            white-space: nowrap;
            transition: all 0.2s;
        }
        
        .nav-tab:hover {
            color: var(--primary);
            background-color: var(--primary-light);
        }
        
        .nav-tab.active {
            color: var(--primary);
            background-color: var(--primary-light);
            font-weight: 600;
        }
        
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--gray);
            font-size: 1.25rem;
            cursor: pointer;
        }
        
        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: block;
            }
            
            .nav-tabs {
                display: none;
                flex-direction: column;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background-color: var(--white);
                box-shadow: var(--shadow-md);
                padding: 1rem;
                z-index: 30;
            }
            
            .nav-tabs.open {
                display: flex;
            }
        }
        
        .action-buttons {
            display: flex;
            gap: 0.75rem;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: var(--white);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
        }
        
        .btn-outline {
            border: 1px solid var(--gray);
            color: var(--gray);
        }
        
        .btn-outline:hover {
            border-color: var(--primary);
            color: var(--primary);
            background-color: var(--primary-light);
        }
        
        /* Main content styles */
        main {
            flex-grow: 1;
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }
        
        .page-heading {
            margin-bottom: 2rem;
        }
        
        .greeting {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .subheading {
            color: var(--gray);
            font-size: 1rem;
        }
        
        /* Dashboard grid layout */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 1.5rem;
        }
        
        .quick-stats {
            grid-column: span 12;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        @media (min-width: 1024px) {
            .quick-stats {
                grid-column: span 8;
            }
        }
        
        .stat-card {
            background-color: var(--white);
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        
        .stat-title {
            font-weight: 500;
            font-size: 1rem;
            color: var(--gray);
        }
        
        .stat-icon {
            width: 42px;
            height: 42px;
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }
        
        .icon-products {
            background-color: var(--primary-light);
            color: var(--primary);
        }
        
        .icon-suppliers {
            background-color: var(--secondary-light);
            color: var(--secondary);
        }
        
        .icon-stock {
            background-color: var(--warning-light);
            color: var(--warning);
        }
        
        .icon-sales {
            background-color: var(--danger-light);
            color: var(--danger);
        }
        
        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stat-description {
            color: var(--gray);
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }
        
        .stat-action {
            margin-top: auto;
        }
        
        .stat-link {
            display: inline-flex;
            align-items: center;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--primary);
            text-decoration: none;
            gap: 0.25rem;
            transition: all 0.2s;
        }
        
        .stat-link:hover {
            color: var(--primary-dark);
        }
        
        .stat-link .icon {
            font-size: 0.75rem;
            transition: transform 0.2s;
        }
        
        .stat-link:hover .icon {
            transform: translateX(3px);
        }
        
        .inventory-overview {
            grid-column: span 12;
            background-color: var(--white);
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
        }
        
        @media (min-width: 1024px) {
            .inventory-overview {
                grid-column: span 4;
            }
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .section-title {
            font-size: 1.125rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .section-title i {
            color: var(--primary);
        }
        
        .section-action {
            font-size: 0.875rem;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .stock-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .stock-item {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            border-radius: var(--border-radius);
            position: relative;
            transition: all 0.2s;
            box-shadow: var(--shadow-sm);
        }
        
        .stock-item:hover {
            transform: translateX(5px);
        }
        
        .stock-item::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            border-radius: 4px 0 0 4px;
        }
        
        .stock-item.critical::before {
            background-color: var(--danger);
        }
        
        .stock-item.warning::before {
            background-color: var(--warning);
        }
        
        .stock-item.normal::before {
            background-color: var(--secondary);
        }
        
        .stock-indicator {
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            border-radius: 50%;
            margin-right: 1rem;
            font-weight: 600;
        }
        
        .indicator-critical {
            background-color: var(--danger-light);
            color: var(--danger);
        }
        
        .indicator-warning {
            background-color: var(--warning-light);
            color: var(--warning);
        }
        
        .indicator-normal {
            background-color: var(--secondary-light);
            color: var(--secondary);
        }
        
        .stock-details {
            flex-grow: 1;
        }
        
        .stock-name {
            font-weight: 500;
            font-size: 0.938rem;
            margin-bottom: 0.25rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 200px;
        }
        
        .stock-category {
            font-size: 0.75rem;
            color: var(--gray);
        }
        
        .dashboard-modules {
            grid-column: span 12;
            margin-top: 1.5rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        
        .module-card {
            background-color: var(--white);
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .module-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }
        
        .module-card::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s ease;
        }
        
        .module-card:hover::after {
            transform: scaleX(1);
        }
        
        .module-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .module-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-right: 1rem;
        }
        
        .module-title {
            font-size: 1.125rem;
            font-weight: 600;
        }
        
        .module-description {
            color: var(--gray);
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
            flex-grow: 1;
        }
        
        .module-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            background-color: var(--light-gray);
            color: var(--dark);
            text-decoration: none;
            border-radius: var(--border-radius);
            font-weight: 500;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        
        .module-action:hover {
            background-color: var(--primary);
            color: var(--white);
        }
        
        /* Footer styles */
        footer {
            background-color: var(--white);
            padding: 1.5rem 2rem;
            text-align: center;
            color: var(--gray);
            font-size: 0.875rem;
            margin-top: auto;
            box-shadow: 0 -1px 0 0 rgba(0, 0, 0, 0.05);
        }
        
        /* Responsive adjustments */
        @media (max-width: 640px) {
            .header-container, .nav-container {
                padding: 1rem;
            }
            
            main {
                padding: 1.5rem 1rem;
            }
            
            .search-bar {
                display: none;
            }
            
            .quick-stats {
                grid-template-columns: 1fr;
            }
            
            .dashboard-modules {
                grid-template-columns: 1fr;
            }
        }
        
        /* Loading overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(5px);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 50;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        
        .loading-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid var(--primary-light);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spinner 1s linear infinite;
        }
        
        .loading-message {
            margin-top: 1rem;
            font-size: 1rem;
            font-weight: 500;
            color: var(--primary);
        }
        
        @keyframes spinner {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="header-container">
            <div class="brand">
                <div class="logo">
                    <i class="fas fa-box"></i>
                </div>
                <div class="brand-name">Inventory System</div>
            </div>
            
            <div class="header-actions">
                <div class="search-bar">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input" placeholder="Search products, suppliers...">
                </div>
                
                <div class="notification-bell">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">3</span>
                </div>
                
                <div class="user-menu">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($user['Username'], 0, 1)); ?>
                    </div>
                    <div class="user-info">
                        <div class="user-name"><?= htmlspecialchars($user['Username']) ?></div>
                        <div class="user-role"><?= $user['RoleName'] ?></div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation -->
    <nav>
        <div class="nav-container">
            <button class="mobile-menu-toggle" id="mobileMenuToggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="nav-tabs" id="navTabs">
                <a href="dashboard.php" class="nav-tab active">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                
                <?php if ($user['RoleName'] == 'Admin'): ?>
                <a href="products/index.php" class="nav-tab">
                    <i class="fas fa-box"></i> Products
                </a>
                <a href="suppliers/index.php" class="nav-tab">
                    <i class="fas fa-truck"></i> Suppliers
                </a>
                <?php endif; ?>
                
                <a href="stock/index.php" class="nav-tab">
                    <i class="fas fa-warehouse"></i> Stock
                </a>
                <a href="sales/index.php" class="nav-tab">
                    <i class="fas fa-shopping-cart"></i> Sales
                </a>
                <a href="reports/dashboard.php" class="nav-tab">
                    <i class="fas fa-chart-line"></i> Reports
                </a>
                
                <?php if ($user['RoleName'] == 'Admin'): ?>
                <a href="user_management.php" class="nav-tab">
                    <i class="fas fa-users-cog"></i> Users
                </a>
                <a href="settings.php" class="nav-tab">
                    <i class="fas fa-cog"></i> Settings
                </a>
                <?php endif; ?>
            </div>
            
            <div class="action-buttons">
                <a href="sales/add.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> New Sale
                </a>
                <a href="logout.php" class="btn btn-outline" id="logoutButton">
    <i class="fas fa-sign-out-alt"></i> Logout
</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main>
        <div class="page-heading">
            <h1 class="greeting">Welcome back, <?= htmlspecialchars($user['Username']) ?>!</h1>
            <p class="subheading">Here's what's happening with your inventory today.</p>
        </div>

        <div class="dashboard-grid">
            <!-- Quick Stats Cards -->
            <div class="quick-stats">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Products</div>
                        <div class="stat-icon icon-products">
                            <i class="fas fa-box"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= $totalProducts ?></div>
                    <div class="stat-description">Total products in your inventory</div>
                    <div class="stat-action">
                        <a href="products/index.php" class="stat-link">
                            Manage Products <i class="fas fa-arrow-right icon"></i>
                        </a>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Suppliers</div>
                        <div class="stat-icon icon-suppliers">
                            <i class="fas fa-truck"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= $totalSuppliers ?></div>
                    <div class="stat-description">Active suppliers in your network</div>
                    <div class="stat-action">
                        <a href="suppliers/index.php" class="stat-link">
                            Manage Suppliers <i class="fas fa-arrow-right icon"></i>
                        </a>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Sales Today</div>
                        <div class="stat-icon icon-sales">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= $todaySales ?></div>
                    <div class="stat-description">Transactions processed today</div>
                    <div class="stat-action">
                        <a href="sales/index.php" class="stat-link">
                            View Sales <i class="fas fa-arrow-right icon"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Inventory Overview -->
            <div class="inventory-overview">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-boxes"></i>
                        Stock Status
                        <span style="font-size: 0.7rem; margin-left: 8px; color: var(--gray);">
                            (Critical: ≤<?= $criticalStockThreshold ?>, Low: ≤<?= $lowStockThreshold ?>)
                        </span>
                    </h2>
                    <a href="products/index.php" class="section-action">
                        View All <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

                <?php if (!empty($productsStock)): ?>
                <div class="stock-list">
                    <?php foreach ($productsStock as $product): 
                        $stockClass = '';
                        if ($product['CurrentStock'] <= $criticalStockThreshold) {
                            $stockClass = 'critical';
                        } elseif ($product['CurrentStock'] <= $lowStockThreshold) {
                            $stockClass = 'warning';
                        } else {
                            $stockClass = 'normal';
                        }
                    ?>
                    <div class="stock-item <?= $stockClass ?>">
                        <div class="stock-indicator indicator-<?= $stockClass ?>">
                            <?= $product['CurrentStock'] ?>
                        </div>
                        <div class="stock-details">
                            <div class="stock-name"><?= htmlspecialchars($product['Name']) ?></div>
                            <div class="stock-category"><?= htmlspecialchars($product['Category']) ?></div>
                        </div>
                        <div class="stock-action">
                            <a href="products/view.php?id=<?= $product['ProductID'] ?>" class="section-action">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p>No products found in stock.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; <?= date('Y') ?> Inventory System</p>
    </footer>
</body>
</html>