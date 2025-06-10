<!-- filepath: c:\xampp\htdocs\inventory_2\inventory_system\sales\index.php -->
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once "../includes/auth.php";
require_once "../includes/db.php";

// Get sales and calculate total revenue
$query = "SELECT s.SaleID, s.QuantitySold, s.SaleDate, s.TotalAmount, p.Name AS ProductName
          FROM sales s
          JOIN products p ON s.ProductID = p.ProductID
          ORDER BY s.SaleID DESC";

$sales = $pdo->query($query)->fetchAll();

$totalRevenueQuery = "SELECT SUM(TotalAmount) AS totalRevenue FROM sales";
$totalRevenue = $pdo->query($totalRevenueQuery)->fetch()['totalRevenue'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Records</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold">📊 Sales Records</h2>
            <a href="../dashboard.php" class="btn btn-outline-primary">← Back to Dashboard</a>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="text-success">Total Revenue: ₱<?= number_format(is_null($totalRevenue) ? 0 : $totalRevenue, 2) ?></h4>
            <a href="create.php" class="btn btn-success">➕ Add New Sale</a>
        </div>

        <div class="card shadow-lg rounded-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped align-middle table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Product</th>
                                <th>Quantity Sold</th>
                        
                                <th>Date</th>
                                <th>Total Amount</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sales as $sale): ?>
                                <tr>
                                    <td><?= $sale['SaleID'] ?></td>
                                    <td><?= htmlspecialchars($sale['ProductName']) ?></td>
                                    <td><?= $sale['QuantitySold'] ?></td>
                                  
                                    <td><?= date("F j, Y", strtotime($sale['SaleDate'])) ?></td>
                                    <td>₱<?= number_format($sale['TotalAmount'], 2) ?></td>
                                    <td>
                                        <a href="delete.php?id=<?= $sale['SaleID'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this sale record?');">🗑️ Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($sales)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No sales recorded yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>