<?php
require_once "../includes/auth.php";
require_once "../includes/db.php";

// Get the stock entry to edit
$stockId = isset($_GET['id']) ? (int)$_GET['id'] : null;
if (!$stockId) {
    header("Location: index.php");
    exit();
}

$query = "SELECT s.StockID, s.QuantityAdded, s.DateAdded, s.SupplierID, p.Name AS ProductName
          FROM stock s
          JOIN products p ON s.ProductID = p.ProductID
          WHERE s.StockID = :stockId";
$stmt = $pdo->prepare($query);
$stmt->execute(['stockId' => $stockId]);
$stock = $stmt->fetch();

if (!$stock) {
    header("Location: index.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantityAdded = (int)$_POST['quantity'];
    $supplierId = (int)$_POST['supplier_id'];
    $dateAdded = $_POST['date_added'];

    $updateQuery = "UPDATE stock 
                    SET QuantityAdded = :quantityAdded, SupplierID = :supplierId, DateAdded = :dateAdded 
                    WHERE StockID = :stockId";
    $stmt = $pdo->prepare($updateQuery);
    $stmt->execute([
        'quantityAdded' => $quantityAdded,
        'supplierId' => $supplierId,
        'dateAdded' => $dateAdded,
        'stockId' => $stockId
    ]);

    header("Location: index.php");
    exit();
}

// Get all suppliers for the dropdown
$suppliersQuery = "SELECT SupplierID, Name FROM suppliers ORDER BY Name";
$suppliers = $pdo->query($suppliersQuery)->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Stock Entry</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow rounded-4">
            <div class="card-header bg-warning text-white">
                <h5 class="mb-0">✏️ Edit Stock Entry</h5>
            </div>
            <div class="card-body p-4">
                <form method="post">
                    <div class="mb-3">
                        <label for="product_name" class="form-label">Product</label>
                        <input type="text" id="product_name" class="form-control" value="<?= htmlspecialchars($stock['ProductName']) ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label for="supplier_id" class="form-label">Supplier</label>
                        <select name="supplier_id" id="supplier_id" class="form-select" required>
                            <?php foreach ($suppliers as $supplier): ?>
                                <option value="<?= $supplier['SupplierID'] ?>" <?= $supplier['SupplierID'] == $stock['SupplierID'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($supplier['Name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Quantity Added</label>
                        <input type="number" name="quantity" id="quantity" class="form-control" value="<?= $stock['QuantityAdded'] ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="date_added" class="form-label">Date Added</label>
                        <input type="date" name="date_added" id="date_added" class="form-control" value="<?= date('Y-m-d', strtotime($stock['DateAdded'])) ?>" required>
                    </div>
                    <div class="d-flex justify-content-between">
                        <a href="index.php" class="btn btn-outline-secondary">← Back</a>
                        <button type="submit" class="btn btn-warning">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>