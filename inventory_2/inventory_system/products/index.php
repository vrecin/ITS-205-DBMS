<!-- filepath: c:\xampp\htdocs\inventory_2\inventory_system\products\index.php -->
<?php
require_once "../includes/auth.php";
require_once "../includes/db.php";

// Fetch all products
$stmt = $pdo->query("SELECT * FROM products ORDER BY ProductID DESC");
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Product Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --light-bg: #f8fafc;
            --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-bg);
            color: #333;
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
        }
        
        .header-container {
            background-color: white;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn {
            border-radius: 8px;
            padding: 8px 16px;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.875rem;
        }
        
        .table {
            font-size: 0.95rem;
        }
        
        .table th {
            font-weight: 600;
            color: #555;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        .action-buttons .btn {
            margin: 0 2px;
        }
        
        .table-container {
            overflow-x: auto;
            border-radius: 12px;
        }
        
        .empty-state {
            padding: 50px 0;
            text-align: center;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: #cbd5e1;
            margin-bottom: 15px;
        }
        
        /* Price formatting */
        .price-tag {
            font-weight: 500;
            color: #1e293b;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .action-buttons {
                display: flex;
                gap: 5px;
            }
        }
    </style>
</head>
<body>

<div class="container py-4">
    <!-- Header Section -->
    <div class="header-container d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div class="mb-3 mb-md-0">
            <h2 class="h4 mb-1">Product Management</h2>
            <p class="text-muted mb-0">Manage your inventory products</p>
        </div>
        <div class="d-flex gap-2">
            <a href="../dashboard.php" class="btn btn-outline-secondary d-flex align-items-center">
                <i class="bi bi-arrow-left me-2"></i> Dashboard
            </a>
            <a href="create.php" class="btn btn-primary d-flex align-items-center">
                <i class="bi bi-plus-lg me-2"></i> Add Product
            </a>
        </div>
    </div>
    
    <!-- Products Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-container">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">#ID</th>
                            <th scope="col">Product</th>
                            <th scope="col">Category</th>
                            <th scope="col">Price (₱)</th>
                            <th scope="col" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($products): ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?= $product['ProductID'] ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-light rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="bi bi-box text-primary"></i>
                                        </div>
                                        <span class="fw-medium"><?= htmlspecialchars($product['Name']) ?></span>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($product['Category']) ?></td>
                                <td class="price-tag">₱<?= number_format($product['Price'], 2) ?></td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center action-buttons">
                                        <a href="edit.php?id=<?= $product['ProductID'] ?>" class="btn btn-sm btn-outline-primary me-1">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="delete.php?id=<?= $product['ProductID'] ?>" class="btn btn-sm btn-outline-danger me-1" onclick="return confirm('Delete this product?');">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                        <a href="manage_suppliers.php?product_id=<?= $product['ProductID'] ?>" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-link-45deg"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <i class="bi bi-inbox"></i>
                                    <h5>No Products Found</h5>
                                    <p class="text-muted">Start adding products to your inventory</p>
                                    <a href="create.php" class="btn btn-primary mt-3">Add First Product</a>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>