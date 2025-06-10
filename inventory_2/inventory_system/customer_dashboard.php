<?php
session_start();
require_once "includes/db.php";

// Check if user is logged in and is a customer
if (!isset($_SESSION['user']) || $_SESSION['user']['RoleID'] != 3) {
    header("Location: login.php");
    exit();
}

// Get user information
$user = $_SESSION['user'];
$username = $user['Username'];
$userID = $user['UserID'];

// Handle product search and filtering
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';

// Get all categories for filter dropdown
$categoryQuery = "SELECT DISTINCT Category FROM products ORDER BY Category";
$categoryStmt = $pdo->query($categoryQuery);
$categories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);

// Build product query with filters
$productQuery = "SELECT p.ProductID, p.Name, p.Category, p.Price, 
                COALESCE(SUM(s.QuantityAdded), 0) - COALESCE((SELECT SUM(QuantitySold) FROM sales WHERE ProductID = p.ProductID), 0) as CurrentStock
                FROM products p
                LEFT JOIN stock s ON p.ProductID = s.ProductID";

$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "p.Name LIKE :search";
    $params['search'] = "%$search%";
}

if (!empty($category)) {
    $whereConditions[] = "p.Category = :category";
    $params['category'] = $category;
}

if (!empty($whereConditions)) {
    $productQuery .= " WHERE " . implode(" AND ", $whereConditions);
}

$productQuery .= " GROUP BY p.ProductID HAVING CurrentStock > 0 ORDER BY p.Name";

$productStmt = $pdo->prepare($productQuery);
$productStmt->execute($params);
$products = $productStmt->fetchAll();

// Handle product purchase
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['purchase'])) {
    $productID = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    
    // Check if product exists and has enough stock
    $stockCheckQuery = "SELECT p.ProductID, p.Name, p.Price, 
                        COALESCE(SUM(s.QuantityAdded), 0) - COALESCE((SELECT SUM(QuantitySold) FROM sales WHERE ProductID = p.ProductID), 0) as CurrentStock
                        FROM products p
                        LEFT JOIN stock s ON p.ProductID = s.ProductID
                        WHERE p.ProductID = :productID
                        GROUP BY p.ProductID";
    
    $stockStmt = $pdo->prepare($stockCheckQuery);
    $stockStmt->execute(['productID' => $productID]);
    $product = $stockStmt->fetch();
    
    if ($product && $product['CurrentStock'] >= $quantity && $quantity > 0) {
        // Calculate total amount
        $totalAmount = $product['Price'] * $quantity;
        
        // Insert sale record
        $saleQuery = "INSERT INTO sales (ProductID, QuantitySold, SaleDate, TotalAmount) 
                      VALUES (:productID, :quantity, NOW(), :totalAmount)";
        
        $saleStmt = $pdo->prepare($saleQuery);
        $saleResult = $saleStmt->execute([
            'productID' => $productID,
            'quantity' => $quantity,
            'totalAmount' => $totalAmount
        ]);
        
        if ($saleResult) {
            $purchaseSuccess = "Successfully purchased {$quantity} {$product['Name']} for ₱" . number_format($totalAmount, 2);
        } else {
            $purchaseError = "Error processing your purchase. Please try again.";
        }
    } else {
        $purchaseError = "Invalid product or insufficient stock.";
    }
    
    // Refresh product list after purchase
    $productStmt->execute($params);
    $products = $productStmt->fetchAll();
}

// Get purchase history for this user
$historyQuery = "SELECT s.SaleID, p.Name as ProductName, s.QuantitySold, s.SaleDate, s.TotalAmount  
                FROM sales s
                JOIN products p ON s.ProductID = p.ProductID
                ORDER BY s.SaleID DESC
                LIMIT 20";

$historyStmt = $pdo->query($historyQuery);
$recentSales = $historyStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShopHub - Customer Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #7E57C2;
            --primary-dark: #5E35B1;
            --secondary: #FF7043;
            --text: #263238;
            --text-light: #607D8B;
            --bg-light: #F9F9F9;
            --bg-dark: #37474F;
            --success: #4CAF50;
            --error: #F44336;
            --warning: #FFC107;
            --card: #FFFFFF;
            --border: #E0E0E0;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: var(--bg-light);
            color: var(--text);
            min-height: 100vh;
            display: grid;
            grid-template-columns: 250px 1fr;
            grid-template-rows: auto 1fr;
            grid-template-areas:
                "sidebar header"
                "sidebar main";
        }
        
        /* SIDEBAR */
        .sidebar {
            grid-area: sidebar;
            background: var(--primary);
            color: white;
            height: 100vh;
            position: fixed;
            width: 250px;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            z-index: 10;
            transition: all 0.3s ease;
        }
        
        .sidebar-header {
            padding: 2rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .user-section {
            padding: 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
        }
        
        .user-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .user-role {
            font-size: 0.85rem;
            opacity: 0.8;
        }
        
        .nav-menu {
            padding: 1.5rem 0;
        }
        
        .nav-item {
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            transition: all 0.2s;
            color: rgba(255,255,255,0.8);
        }
        
        .nav-item:hover, .nav-item.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .nav-item i {
            width: 20px;
            text-align: center;
        }
        
        .logout-section {
            padding: 1.5rem;
            position: absolute;
            bottom: 0;
            width: 100%;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.1);
            color: white;
            border: none;
            padding: 0.75rem;
            border-radius: 8px;
            width: 100%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.2s;
            font-weight: 500;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.2);
        }
        
        /* HEADER */
        .header {
            grid-area: header;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 5;
        }
        
        .page-title {
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary);
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .header-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg-light);
            color: var(--primary);
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
        }
        
        .header-icon:hover {
            background: var(--primary);
            color: white;
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--secondary);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .toggle-sidebar {
            display: none;
        }
        
        /* MAIN CONTENT */
        .main-content {
            grid-area: main;
            padding: 2rem;
            overflow-y: auto;
        }
        
        /* ALERTS */
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .alert-success {
            background: rgba(76, 175, 80, 0.1);
            border-left: 4px solid var(--success);
            color: var(--success);
        }
        
        .alert-error {
            background: rgba(244, 67, 54, 0.1);
            border-left: 4px solid var(--error);
            color: var(--error);
        }
        
        /* DASHBOARD SECTIONS - VERTICAL LAYOUT */
        .dashboard-sections {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        /* SEARCH BAR */
        .search-section {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }
        
        .filter-form {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .search-container {
            flex: 1;
            min-width: 200px;
            position: relative;
        }
        
        .search-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 3rem;
            border: 1px solid var(--border);
            border-radius: 30px;
            font-size: 0.95rem;
            transition: all 0.2s;
        }
        
        .search-input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(126, 87, 194, 0.1);
        }
        
        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
        }
        
        .category-select {
            padding: 0.75rem 1.5rem;
            border: 1px solid var(--border);
            border-radius: 30px;
            background: white;
            min-width: 150px;
            transition: all 0.2s;
            cursor: pointer;
        }
        
        .category-select:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(126, 87, 194, 0.1);
        }
        
        .filter-btn {
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 30px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .filter-btn:hover {
            background: var(--primary-dark);
        }
        
        /* PRODUCTS SECTION */
        .products-section {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        
        .section-header {
            padding: 1.5rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .section-title {
            font-weight: 600;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .sort-options {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255,255,255,0.1);
            padding: 0.25rem;
            border-radius: 8px;
        }
        
        .sort-option {
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .sort-option.active {
            background: white;
            color: var(--primary);
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            padding: 1.5rem;
        }
        
        .product-card {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            height: 100%;
            border: 1px solid var(--border);
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .product-image {
            height: 150px;
            background: linear-gradient(135deg, #f5f7fa, #c3cfe2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: var(--primary);
            position: relative;
        }
        
        .product-category-tag {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255,255,255,0.8);
            color: var(--primary);
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
            border-radius: 30px;
            font-weight: 600;
        }
        
        .product-info {
            padding: 1rem;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }
        
        .product-name {
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 1rem;
            color: var(--text);
        }
        
        .product-price {
            font-weight: 700;
            color: var(--secondary);
            font-size: 1.25rem;
            margin-bottom: 0.75rem;
        }
        
        .stock-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            color: var(--text-light);
            margin-bottom: 1rem;
        }
        
        .stock-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--success);
        }
        
        .product-form {
            margin-top: auto;
            display: flex;
            gap: 0.5rem;
        }
        
        .qty-input {
            flex: 1;
            padding: 0.5rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            text-align: center;
            width: 60px;
        }
        
        .buy-btn {
            flex: 3;
            background: var(--secondary);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.5rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .buy-btn:hover {
            background: #E64A19;
        }
        
        /* HISTORY SECTION */
        .history-section {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        
        .history-header {
            padding: 1.5rem;
            background: linear-gradient(135deg, #5C6BC0, #3949AB);
            color: white;
        }
        
        .history-title {
            font-weight: 600;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        /* HISTORY TABLE STYLES */
        .history-table-container {
            padding: 1rem;
            overflow-x: auto;
        }
        
        .history-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }
        
        .history-table th,
        .history-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        
        .history-table th {
            font-weight: 600;
            color: var(--text);
            background-color: var(--bg-light);
        }
        
        .history-table tr:last-child td {
            border-bottom: none;
        }
        
        .history-table tr:hover {
            background-color: rgba(0,0,0,0.02);
        }
        
        .order-id {
            font-weight: 500;
            color: var(--primary);
        }
        
        .product-cell {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .product-icon {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            background: rgba(92, 107, 192, 0.1);
            color: #5C6BC0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
        }
        
        .amount-cell {
            font-weight: 600;
            color: var(--secondary);
        }
        
        .empty-history {
            text-align: center;
            padding: 2rem;
            color: var(--text-light);
        }
        
        .empty-history i {
            font-size: 3rem;
            color: #e0e0e0;
            margin-bottom: 1rem;
        }
        
        /* RESPONSIVE */
        @media (max-width: 768px) {
            body {
                grid-template-columns: 1fr;
                grid-template-areas:
                    "header"
                    "main";
            }
            
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .toggle-sidebar {
                display: flex;
                margin-right: 1rem;
            }
            
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
            
            .history-table th,
            .history-table td {
                padding: 0.5rem 0.25rem;
                font-size: 0.8rem;
            }
            
            .product-cell {
                gap: 0.25rem;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="fas fa-shopping-bag"></i>
                <span>ShopHub</span>
            </div>
        </div>
        
        <div class="user-section">
            <div class="user-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="user-name"><?= htmlspecialchars($username) ?></div>
            <div class="user-role">Customer</div>
        </div>
        
        <nav class="nav-menu">
            <div class="nav-item active">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </div>
        </nav>
        
        <div class="logout-section">
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Log Out</span>
            </a>
        </div>
    </aside>
    
    <!-- Header -->
    <header class="header">
        <div class="toggle-sidebar header-icon">
            <i class="fas fa-bars"></i>
        </div>
        
        <h1 class="page-title">
            <i class="fas fa-store"></i>
            <span>Marketplace</span>
        </h1>
        
        <div class="header-actions">
            <div class="header-icon">
                <i class="fas fa-bell"></i>
                <div class="notification-badge">3</div>
            </div>
            <div class="header-icon">
                <i class="fas fa-shopping-cart"></i>
                <div class="notification-badge">2</div>
            </div>
        </div>
    </header>
    
    <!-- Main Content -->
    <main class="main-content">
        <?php if (isset($purchaseSuccess)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <div><?= $purchaseSuccess ?></div>
            </div>
        <?php endif; ?>
        
        <?php if (isset($purchaseError)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <div><?= $purchaseError ?></div>
            </div>
        <?php endif; ?>
        
        <!-- Search Bar -->
        <section class="search-section">
            <form method="get" action="" class="filter-form">
                <div class="search-container">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" name="search" class="search-input" placeholder="Search for products..." value="<?= htmlspecialchars($search) ?>">
                </div>
                
                <select name="category" class="category-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <button type="submit" class="filter-btn">
                    <i class="fas fa-filter"></i>
                    <span>Filter</span>
                </button>
            </form>
        </section>
        
        <!-- Dashboard Content - Vertical Layout -->
        <div class="dashboard-sections">
            <!-- Products Section -->
            <section class="products-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-box"></i>
                        <span>Available Products</span>
                    </h2>
                    <div class="sort-options">
                        <div class="sort-option active">All</div>
                    </div>
                </div>
                
                <div class="products-grid">
                    <?php if (count($products) > 0): ?>
                        <?php foreach ($products as $product): ?>
                            <div class="product-card">
                                <div class="product-image">
                                    <?php
                                    // Display different icons based on category
                                    $category = strtolower($product['Category']);
                                    $icon = 'box';
                                    
                                    if (strpos($category, 'electronics') !== false) {
                                        $icon = 'laptop';
                                    } elseif (strpos($category, 'clothing') !== false) {
                                        $icon = 'tshirt';
                                    } elseif (strpos($category, 'food') !== false) {
                                        $icon = 'utensils';
                                    } elseif (strpos($category, 'book') !== false) {
                                        $icon = 'book';
                                    } elseif (strpos($category, 'furniture') !== false) {
                                        $icon = 'chair';
                                    }
                                    ?>
                                    <i class="fas fa-<?= $icon ?>"></i>
                                    <div class="product-category-tag"><?= htmlspecialchars($product['Category']) ?></div>
                                </div>
                                <div class="product-info">
                                    <div class="product-name"><?= htmlspecialchars($product['Name']) ?></div>
                                    <div class="product-price">₱<?= number_format($product['Price'], 2) ?></div>
                                    <div class="stock-info">
                                        <div class="stock-dot"></div>
                                        <span><?= $product['CurrentStock'] ?> in stock</span>
                                    </div>
                                    <form method="post" class="product-form">
                                        <input type="hidden" name="product_id" value="<?= $product['ProductID'] ?>">
                                        <input type="number" name="quantity" class="qty-input" min="1" max="<?= $product['CurrentStock'] ?>" value="1" required>
                                        <button type="submit" name="purchase" class="buy-btn">
                                            <i class="fas fa-shopping-cart"></i>
                                            <span>Buy</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-history">
                            <i class="fas fa-box-open"></i>
                            <p>No products available at the moment.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Recent Purchases Section -->
            <section class="history-section">
                <div class="history-header">
                    <h2 class="history-title">
                        <i class="fas fa-receipt"></i>
                        <span>Recent Purchases</span>
                    </h2>
                </div>
                <div class="history-table-container">
                    <?php if (count($recentSales) > 0): ?>
                        <table class="history-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Product</th>
                                    <th>Date</th>
                                    <th>Quantity</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentSales as $sale): ?>
                                    <tr>
                                        <td class="order-id">#<?= sprintf('%06d', $sale['SaleID']) ?></td>
                                        <td><?= htmlspecialchars($sale['ProductName']) ?></td>
                                        <td><?= date('M d, Y', strtotime($sale['SaleDate'])) ?></td>
                                        <td><?= number_format($sale['QuantitySold']) ?></td>
                                        <td class="amount-cell">₱<?= number_format($sale['TotalAmount'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-history">
                            <i class="fas fa-shopping-cart"></i>
                            <p>No recent purchases found.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>

    <script>
        // Mobile sidebar toggle
        document.querySelector('.toggle-sidebar').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });

        // Simulate nav item clicks (for demo)
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', function() {
                document.querySelectorAll('.nav-item').forEach(i => {
                    i.classList.remove('active');
                });
                this.classList.add('active');
            });
        });
    </script>
</body>
</html>