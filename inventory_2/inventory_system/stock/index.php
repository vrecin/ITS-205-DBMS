<?php
require_once "../includes/auth.php";
require_once "../includes/db.php";

// Get product information for the dropdown
$productQuery = "SELECT ProductID, Name FROM products ORDER BY Name";
$products = $pdo->query($productQuery)->fetchAll();

// Default to first product or use selected product
$selectedProductId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : ($products[0]['ProductID'] ?? null);

// Calculate current stock level for the selected product
$currentStockQuery = "SELECT 
    COALESCE(SUM(s.QuantityAdded), 0) - COALESCE((SELECT SUM(QuantitySold) FROM sales WHERE ProductID = :productId), 0) as CurrentStock,
    p.Name as ProductName
    FROM products p
    LEFT JOIN stock s ON p.ProductID = s.ProductID
    WHERE p.ProductID = :productId
    GROUP BY p.ProductID";

$stmt = $pdo->prepare($currentStockQuery);
$stmt->execute(['productId' => $selectedProductId]);
$productInfo = $stmt->fetch();

// Get stock history for the selected product
$query = "SELECT s.StockID, s.QuantityAdded, s.DateAdded, 
         p.Name AS ProductName, sup.Name AS SupplierName
         FROM stock s
         JOIN products p ON s.ProductID = p.ProductID
         JOIN suppliers sup ON s.SupplierID = sup.SupplierID
         WHERE s.ProductID = :productId
         ORDER BY s.DateAdded DESC";

$stmt = $pdo->prepare($query);
$stmt->execute(['productId' => $selectedProductId]);
$stockEntries = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stock Records</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold">📦 Stock Records</h2>
            <div>
                <a href="../dashboard.php" class="btn btn-outline-secondary me-2">← Dashboard</a>
                <a href="create.php" class="btn btn-primary">➕ Add Stock</a>
            </div>
        </div>

        <!-- Product Selection -->
        <div class="card shadow rounded-4 mb-4">
            <div class="card-body p-4">
                <form method="get" class="row g-3 align-items-center">
                    <div class="col-md-6">
                        <label for="product_id" class="form-label">Select Product</label>
                        <select name="product_id" id="product_id" class="form-select" onchange="this.form.submit()">
                            <?php foreach ($products as $product): ?>
                                <option value="<?= $product['ProductID'] ?>" <?= $selectedProductId == $product['ProductID'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($product['Name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($productInfo['ProductName'] ?? 'No Product Selected') ?></h5>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold">Current Stock:</span>
                                    <span class="badge bg-primary rounded-pill fs-5"><?= number_format($productInfo['CurrentStock'] ?? 0) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Stock Entries Table -->
        <div class="card shadow rounded-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Stock Movement History</h5>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle text-center">
                        <thead class="table-primary">
                            <tr>
                                <th>ID</th>
                                <th>Supplier</th>
                                <th>Quantity Added</th>
                                <th>Date Added</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($stockEntries as $stock): ?>
                            <tr>
                                <td><?= $stock['StockID'] ?></td>
                                <td><?= htmlspecialchars($stock['SupplierName']) ?></td>
                                <td><?= number_format($stock['QuantityAdded']) ?></td>
                                <td><?= date("F j, Y", strtotime($stock['DateAdded'])) ?></td>
                                <td>
                                    <a href="delete.php?id=<?= $stock['StockID'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this stock entry? This will also update the current stock level.');">🗑️ Delete</a>
                                </td>
                            </tr>

                            <td>
    <a href="edit.php?id=<?= $stock['StockID'] ?>" class="btn btn-sm btn-warning">✏️ Edit</a>
    <a href="delete.php?id=<?= $stock['StockID'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this stock entry? This will also update the current stock level.');">🗑️ Delete</a>
</td>
                        <?php endforeach; ?>
                        <?php if (count($stockEntries) === 0): ?>
                            <tr>
                                <td colspan="5" class="text-muted">No stock additions found for this product.</td>
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