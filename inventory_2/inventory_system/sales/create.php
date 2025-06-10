<?php
require_once "../includes/auth.php";
require_once "../includes/db.php";

// Fetch products
$products = $pdo->query("SELECT ProductID, Name, Price FROM products")->fetchAll();

// Get products with their current stock levels
$productsWithStock = $pdo->query("
    SELECT 
        p.ProductID,
        p.Name,
        p.Price,
        COALESCE(SUM(s.QuantityAdded), 0) - COALESCE((
            SELECT SUM(QuantitySold) 
            FROM sales 
            WHERE ProductID = p.ProductID
        ), 0) as CurrentStock
    FROM products p
    LEFT JOIN stock s ON p.ProductID = s.ProductID
    GROUP BY p.ProductID
")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = $_POST['product_id'];
    $quantitySold = $_POST['quantity_sold'];
    $saleDate = date('Y-m-d H:i:s');
    $totalAmount = $_POST['total_amount'];

    // Calculate current stock for the product
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(s.QuantityAdded), 0) as TotalAdded,
            COALESCE((
                SELECT SUM(QuantitySold) 
                FROM sales 
                WHERE ProductID = :productId
            ), 0) as TotalSold
        FROM stock s
        WHERE s.ProductID = :productId
    ");
    $stmt->execute(['productId' => $productId]);
    $stockData = $stmt->fetch();
    
    $currentStock = $stockData['TotalAdded'] - $stockData['TotalSold'];

    if ($currentStock >= $quantitySold) {
        // Insert the sale record without modifying stock records
        $stmt = $pdo->prepare("INSERT INTO sales (ProductID, QuantitySold, SaleDate, TotalAmount) VALUES (?, ?, ?, ?)");
        $stmt->execute([$productId, $quantitySold, $saleDate, $totalAmount]);

        header("Location: index.php");
        exit;
    } else {
        $errorMessage = "Not enough stock for the sale! Current available stock: " . $currentStock;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Sale</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        function calculateTotalAmount() {
            const productSelect = document.querySelector('[name="product_id"]');
            const selectedOption = productSelect.options[productSelect.selectedIndex];
            const price = parseFloat(selectedOption.getAttribute('data-price')) || 0;
            const qty = parseInt(document.querySelector('[name="quantity_sold"]').value) || 0;
            document.querySelector('[name="total_amount"]').value = (price * qty).toFixed(2);
        }
        
        function updateStockInfo() {
            const productSelect = document.querySelector('[name="product_id"]');
            const selectedOption = productSelect.options[productSelect.selectedIndex];
            const stock = selectedOption.getAttribute('data-stock') || 0;
            
            document.getElementById('current-stock').textContent = stock;
            
            // Update max quantity allowed
            document.querySelector('[name="quantity_sold"]').max = stock;
        }
    </script>
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow-lg rounded-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="fw-bold text-primary">➕ Add New Sale</h3>
                    <a href="index.php" class="btn btn-outline-secondary">← Back to Sales Records</a>
                </div>

                <?php if (isset($errorMessage)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
                <?php endif; ?>

                <form method="post" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="product_id" class="form-label">Product</label>
                        <select name="product_id" class="form-select" required onchange="calculateTotalAmount(); updateStockInfo();">
                            <option value="">-- Select a Product --</option>
                            <?php foreach ($productsWithStock as $product): ?>
                                <option value="<?= $product['ProductID'] ?>" 
                                        data-price="<?= $product['Price'] ?>"
                                        data-stock="<?= $product['CurrentStock'] ?>">
                                    <?= htmlspecialchars($product['Name']) ?> - ₱<?= number_format($product['Price'], 2) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="alert alert-info">
                        Current Available Stock: <span id="current-stock">0</span>
                    </div>

                    <div class="mb-3">
                        <label for="quantity_sold" class="form-label">Quantity Sold</label>
                        <input type="number" name="quantity_sold" class="form-control" min="1" required onchange="calculateTotalAmount()">
                    </div>

                    <div class="mb-4">
                        <label for="total_amount" class="form-label">Total Amount (₱)</label>
                        <input type="number" name="total_amount" class="form-control" readonly required step="0.01">
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">💾 Save Sale</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Initialize stock info when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            updateStockInfo();
        });
    </script>
</body>
</html>