<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "../includes/auth.php";
require_once "../includes/db.php";

// Updated top selling products query without supplier information
$topSellingProducts = $pdo->query("
    SELECT p.Name AS ProductName, 
           SUM(s.QuantitySold) AS TotalSold, 
           SUM(s.TotalAmount) AS Revenue
    FROM sales s
    JOIN products p ON s.ProductID = p.ProductID
    GROUP BY p.ProductID
    ORDER BY Revenue DESC
    LIMIT 5
")->fetchAll();

// Updated query to show supplier contributions by product
$supplierProductDetails = $pdo->query("
    SELECT 
        sup.SupplierID,
        sup.Name AS SupplierName,
        p.Name AS ProductName,
        SUM(st.QuantityAdded) AS QuantitySupplied,
        (
            SELECT COALESCE(SUM(s.TotalAmount), 0)
            FROM sales s
            WHERE s.ProductID = p.ProductID
        ) * (
            SUM(st.QuantityAdded) / (
                SELECT SUM(st2.QuantityAdded)
                FROM stock st2
                WHERE st2.ProductID = p.ProductID
            )
        ) AS ProductRevenue
    FROM suppliers sup
    JOIN stock st ON sup.SupplierID = st.SupplierID
    JOIN products p ON st.ProductID = p.ProductID
    JOIN supplier_products sp ON (sup.SupplierID = sp.SupplierID AND p.ProductID = sp.ProductID)
    GROUP BY sup.SupplierID, p.ProductID
    ORDER BY ProductRevenue DESC
")->fetchAll();

// Get total revenue per supplier for ordering
$supplierTotalRevenue = [];
foreach ($supplierProductDetails as $detail) {
    $supplierId = $detail['SupplierID'];
    if (!isset($supplierTotalRevenue[$supplierId])) {
        $supplierTotalRevenue[$supplierId] = 0;
    }
    $supplierTotalRevenue[$supplierId] += $detail['ProductRevenue'];
}

// Group supplier product details by supplier
$groupedSupplierProducts = [];
foreach ($supplierProductDetails as $detail) {
    $supplierId = $detail['SupplierID'];
    if (!isset($groupedSupplierProducts[$supplierId])) {
        $groupedSupplierProducts[$supplierId] = [
            'SupplierName' => $detail['SupplierName'],
            'TotalRevenue' => $supplierTotalRevenue[$supplierId],
            'Products' => []
        ];
    }
    $groupedSupplierProducts[$supplierId]['Products'][] = [
        'ProductName' => $detail['ProductName'],
        'QuantitySupplied' => $detail['QuantitySupplied'],
        'ProductRevenue' => $detail['ProductRevenue']
    ];
}

// Sort suppliers by total revenue
uasort($groupedSupplierProducts, function($a, $b) {
    return $b['TotalRevenue'] <=> $a['TotalRevenue'];
});

// Prepare chart data for top selling products
$chartLabels = [];
$chartData = [];
$chartColors = [
    'rgba(67, 97, 238, 0.7)',
    'rgba(72, 149, 239, 0.7)',
    'rgba(63, 55, 201, 0.7)',
    'rgba(76, 201, 240, 0.7)',
    'rgba(58, 12, 163, 0.7)'
];
$chartBorderColors = [
    'rgba(67, 97, 238, 1)',
    'rgba(72, 149, 239, 1)',
    'rgba(63, 55, 201, 1)',
    'rgba(76, 201, 240, 1)',
    'rgba(58, 12, 163, 1)'
];

foreach ($topSellingProducts as $index => $product) {
    $chartLabels[] = $product['ProductName'];
    $chartData[] = $product['Revenue'];
}

$chartLabelsJson = json_encode($chartLabels);
$chartDataJson = json_encode($chartData);
$chartColorsJson = json_encode($chartColors);
$chartBorderColorsJson = json_encode($chartBorderColors);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Real-Time Analytics Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #4895ef;
            --secondary: #3f37c9;
            --success: #0cce6b;
            --danger: #ef476f;
            --warning: #ffd166;
            --info: #4cc9f0;
            --dark: #2b2d42;
            --light: #f8f9fa;
            --gray: #adb5bd;
            --gradient-start: #4361ee;
            --gradient-end: #3a0ca3;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f6f8fc;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            box-shadow: var(--shadow);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }
        
        h2 {
            font-weight: 600;
            font-size: 24px;
            margin-bottom: 0;
            display: flex;
            align-items: center;
        }
        
        h2 i {
            margin-right: 12px;
            font-size: 22px;
            background: rgba(255, 255, 255, 0.2);
            padding: 10px;
            border-radius: 50%;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 10px 18px;
            border-radius: 50px;
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        
        .back-btn i {
            margin-right: 8px;
        }
        
        .card {
            background-color: white;
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 25px;
            margin-bottom: 25px;
            transition: transform 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .card-header h3 {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
            margin-left: 12px;
        }
        
        .card-header i {
            color: white;
            font-size: 16px;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            padding: 10px;
            border-radius: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 10px;
        }
        
        th {
            background-color: #f6f8fc;
            color: var(--dark);
            font-weight: 600;
            text-align: left;
            padding: 14px 15px;
            font-size: 14px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        
        th:first-child {
            border-top-left-radius: 10px;
            border-bottom-left-radius: 10px;
        }
        
        th:last-child {
            border-top-right-radius: 10px;
            border-bottom-right-radius: 10px;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 15px;
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        tr:hover {
            background-color: #f9f9f9;
        }
        
        .supplier-header {
            background-color: #f6f8fc;
            font-weight: 600;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        
        .supplier-header:first-child {
            margin-top: 0;
        }
        
        .quantity-value {
            font-weight: 600;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .revenue-value {
            font-weight: 600;
            color: var(--dark);
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .chart-container {
            position: relative;
            height: 350px;
            width: 100%;
            margin: 20px 0;
        }
        
        .flex-container {
            display: flex;
            flex-wrap: wrap;
            gap: 25px;
        }
        
        .flex-item {
            flex: 1;
            min-width: 300px;
        }
        
        .chart-label {
            margin-top: 10px;
            font-weight: 500;
            text-align: center;
            color: var(--dark);
        }
        
        @media (max-width: 768px) {
            .card-grid {
                grid-template-columns: 1fr;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
            
            header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .back-btn {
                width: 100%;
                justify-content: center;
            }
            
            .flex-container {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h2><i class="fas fa-chart-line"></i> Real-Time Analytics Dashboard</h2>
            <a href="../dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </header>
        
        <div class="card">
            <div class="card-header">
                <i class="fas fa-fire"></i>
                <h3>Top-Selling Products</h3>
            </div>
            
            <div class="flex-container">
                <div class="flex-item">
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="text-center">Total Sold</th>
                                <th class="text-right">Total Revenue (₱)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topSellingProducts as $p): ?>
                            <tr>
                                <td><?= htmlspecialchars($p['ProductName']) ?></td>
                                <td class="text-center quantity-value"><?= $p['TotalSold'] ?></td>
                                <td class="text-right revenue-value">₱<?= number_format($p['Revenue'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="flex-item">
                    <div class="chart-container">
                        <canvas id="revenueChart"></canvas>
                    </div>
                    <div class="chart-label">Revenue Distribution by Product</div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <i class="fas fa-industry"></i>
                <h3>Supplier Product Contributions</h3>
            </div>
            
            <?php foreach ($groupedSupplierProducts as $supplierData): ?>
                <div class="supplier-header">
                    <?= htmlspecialchars($supplierData['SupplierName']) ?> - 
                    <span class="revenue-value">
                        Total Revenue: ₱<?= number_format($supplierData['TotalRevenue'], 2) ?>
                    </span>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th class="text-center">Quantity Supplied</th>
                            <th class="text-right">Revenue Generated (₱)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($supplierData['Products'] as $product): ?>
                        <tr>
                            <td><?= htmlspecialchars($product['ProductName']) ?></td>
                            <td class="text-center quantity-value"><?= $product['QuantitySupplied'] ?></td>
                            <td class="text-right revenue-value">₱<?= number_format($product['ProductRevenue'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>
        </div>
    </div>
    
    <script>
        // Initialize the pie chart for top selling products
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('revenueChart').getContext('2d');
            
            const revenueChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: <?= $chartLabelsJson ?>,
                    datasets: [{
                        data: <?= $chartDataJson ?>,
                        backgroundColor: <?= $chartColorsJson ?>,
                        borderColor: <?= $chartBorderColorsJson ?>,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                font: {
                                    size: 12,
                                    family: "'Segoe UI', sans-serif"
                                },
                                padding: 15
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    let value = context.raw || 0;
                                    let total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                                    let percentage = Math.round((value / total) * 100);
                                    return `${label}: ₱${value.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>