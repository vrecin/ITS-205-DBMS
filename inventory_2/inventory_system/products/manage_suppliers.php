<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Suppliers</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            max-width: 800px;
            padding: 2rem;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: none;
        }
        .card-header {
            background-color: #4a6cf7;
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 1rem 1.5rem;
        }
        .supplier-checkbox {
            padding: 10px;
            margin: 5px 0;
            border-radius: 6px;
            transition: all 0.2s ease;
        }
        .supplier-checkbox:hover {
            background-color: #f2f4ff;
        }
        .supplier-checkbox.checked {
            background-color: #e8f0fe;
        }
        .custom-control-input:checked ~ .custom-control-label::before {
            background-color: #4a6cf7;
            border-color: #4a6cf7;
        }
        .btn-primary {
            background-color: #4a6cf7;
            border-color: #4a6cf7;
            padding: 10px 24px;
            border-radius: 6px;
            font-weight: 600;
        }
        .btn-primary:hover {
            background-color: #3b5ddb;
            border-color: #3b5ddb;
        }
        .btn-outline-secondary {
            color: #6c757d;
            border-color: #6c757d;
            padding: 10px 24px;
            border-radius: 6px;
            font-weight: 600;
        }
        .btn-outline-secondary:hover {
            background-color: #6c757d;
            color: white;
        }
        .search-box {
            margin-bottom: 20px;
            position: relative;
        }
        .search-box i {
            position: absolute;
            left: 12px;
            top: 12px;
            color: #6c757d;
        }
        .search-input {
            padding-left: 35px;
            border-radius: 6px;
        }
    </style>
</head>
<body>
    <?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    require_once "../includes/auth.php";
    require_once "../includes/db.php";

    $productId = $_GET['product_id'] ?? null;
    if (!$productId) {
        header("Location: index.php");
        exit;
    }

    // Fetch product info
    $product = $pdo->prepare("SELECT * FROM products WHERE ProductID = ?");
    $product->execute([$productId]);
    $product = $product->fetch();

    // Fetch all suppliers
    $allSuppliers = $pdo->query("SELECT * FROM suppliers")->fetchAll();

    // Fetch suppliers linked to this product
    $linkedSuppliers = $pdo->prepare("SELECT SupplierID FROM supplier_products WHERE ProductID = ?");
    $linkedSuppliers->execute([$productId]);
    $linked = $linkedSuppliers->fetchAll(PDO::FETCH_COLUMN);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Clear existing links
        $pdo->prepare("DELETE FROM supplier_products WHERE ProductID = ?")->execute([$productId]);

        // Add new links
        if (!empty($_POST['suppliers'])) {
            $stmt = $pdo->prepare("INSERT INTO supplier_products (ProductID, SupplierID) VALUES (?, ?)");
            foreach ($_POST['suppliers'] as $supplierID) {
                $stmt->execute([$productId, $supplierID]);
            }
        }

        header("Location: index.php");
        exit;
    }
    ?>

    <div class="container mt-5">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Products</a></li>
                <li class="breadcrumb-item active" aria-current="page">Manage Suppliers</li>
            </ol>
        </nav>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0"><i class="fas fa-link me-2"></i>Manage Suppliers for: <?= htmlspecialchars($product['Name']) ?></h4>
            </div>
            <div class="card-body">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchSuppliers" class="form-control search-input" placeholder="Search suppliers...">
                </div>

                <form method="post" id="suppliersForm">
                    <div class="suppliers-list">
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="d-flex justify-content-between mb-3">
                                    <span><strong>Total suppliers:</strong> <?= count($allSuppliers) ?></span>
                                    <span><strong>Selected:</strong> <span id="selectedCount"><?= count($linked) ?></span></span>
                                </div>
                            </div>
                        </div>

                        <div class="supplier-items">
                            <?php foreach ($allSuppliers as $supplier): ?>
                                <?php $isChecked = in_array($supplier['SupplierID'], $linked); ?>
                                <div class="supplier-checkbox <?= $isChecked ? 'checked' : '' ?>">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="suppliers[]" 
                                               id="supplier-<?= $supplier['SupplierID'] ?>" 
                                               value="<?= $supplier['SupplierID'] ?>" 
                                               <?= $isChecked ? 'checked' : '' ?>>
                                        <label class="form-check-label w-100" for="supplier-<?= $supplier['SupplierID'] ?>">
                                            <?= htmlspecialchars($supplier['Name']) ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Changes
                            </button>
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Products
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Search functionality
        document.getElementById('searchSuppliers').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const suppliers = document.querySelectorAll('.supplier-checkbox');
            
            suppliers.forEach(supplier => {
                const supplierName = supplier.querySelector('label').textContent.toLowerCase();
                if (supplierName.includes(searchTerm)) {
                    supplier.style.display = 'block';
                } else {
                    supplier.style.display = 'none';
                }
            });
        });

        // Update checkbox styling and counter
        document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const parent = this.closest('.supplier-checkbox');
                if (this.checked) {
                    parent.classList.add('checked');
                } else {
                    parent.classList.remove('checked');
                }
                updateSelectedCount();
            });
        });

        function updateSelectedCount() {
            const selectedCount = document.querySelectorAll('input[type="checkbox"]:checked').length;
            document.getElementById('selectedCount').textContent = selectedCount;
        }
    </script>
</body>
</html>