<?php
require_once "../includes/auth.php";
require_once "../includes/db.php";

// Define product categories
$categories = [
    "Electronics", 
    "Clothing", 
    "Food & Beverages", 
    "Home & Kitchen", 
    "Office Supplies",
    "Health & Beauty",
    "Sports & Outdoors",
    "Toys & Games",
    "Automotive",
    "Other"
];

// Get all suppliers from the database
$suppliers = $pdo->query("SELECT SupplierID, Name FROM suppliers ORDER BY Name")->fetchAll();
$hasSuppliers = count($suppliers) > 0;

// Process form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && $hasSuppliers) {
    $name = $_POST['name'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $supplierID = $_POST['supplier'];

    // Start transaction
    $pdo->beginTransaction();
    try {
        // Insert new product
        $stmt = $pdo->prepare("INSERT INTO products (Name, Category, Price) VALUES (?, ?, ?)");
        $stmt->execute([$name, $category, $price]);
        
        // Get the newly inserted product ID
        $productID = $pdo->lastInsertId();
        
        // Link the product to the supplier
        $stmt = $pdo->prepare("INSERT INTO supplier_products (ProductID, SupplierID) VALUES (?, ?)");
        $stmt->execute([$productID, $supplierID]);
        
        // Commit transaction
        $pdo->commit();
        
        header("Location: index.php");
        exit;
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        $error = "An error occurred: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Product</title>
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
            padding: 20px;
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            padding: 10px 15px;
            border: 1px solid #e2e8f0;
            transition: all 0.2s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }
        
        .form-label {
            font-weight: 500;
            color: #4b5563;
            margin-bottom: 8px;
        }
        
        .btn {
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-outline-secondary:hover {
            background-color: #f1f5f9;
            color: #333;
        }
        
        .header-container {
            margin-bottom: 30px;
        }
        
        .currency-input {
            position: relative;
        }
        
        .currency-input::before {
            content: "₱";
            position: absolute;
            left: 15px;
            top: 10px;
            font-weight: 500;
            color: #555;
        }
        
        .currency-input input {
            padding-left: 28px;
        }
        
        .alert {
            border-radius: 8px;
            border-left: 4px solid;
            padding: 1rem;
        }
        
        .alert-warning {
            background-color: #fff8e6;
            border-color: #ffd166;
            color: #8a6400;
        }
        
        @media (max-width: 768px) {
            .button-group {
                flex-direction: column;
                gap: 10px;
            }
            
            .button-group .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <!-- Header Section -->
                <div class="header-container d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
                    <div class="mb-3 mb-md-0">
                        <h2 class="h4 mb-1 d-flex align-items-center">
                            <i class="bi bi-plus-circle-fill text-primary me-2"></i>
                            Add New Product
                        </h2>
                        <p class="text-muted mb-0">Enter the details of the new product</p>
                    </div>
                    <a href="index.php" class="btn btn-outline-secondary d-flex align-items-center">
                        <i class="bi bi-arrow-left me-2"></i> Back to Products
                    </a>
                </div>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger mb-4" role="alert">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!$hasSuppliers): ?>
                    <div class="alert alert-warning mb-4" role="alert">
                        <div class="d-flex">
                            <div class="me-3">
                                <i class="bi bi-exclamation-triangle-fill fs-4"></i>
                            </div>
                            <div>
                                <h5 class="alert-heading mb-1">No Suppliers Available</h5>
                                <p class="mb-0">You need to add at least one supplier before you can add products.</p>
                                <div class="mt-3">
                                    <a href="../suppliers/create.php" class="btn btn-sm btn-warning">
                                        <i class="bi bi-plus-lg me-1"></i> Add Supplier
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Form Card -->
                <div class="card <?= !$hasSuppliers ? 'opacity-50' : '' ?>">
                    <div class="card-body p-4">
                        <form method="POST" class="needs-validation" novalidate <?= !$hasSuppliers ? 'disabled' : '' ?>>
                            <div class="mb-4">
                                <label for="productName" class="form-label">Product Name</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white">
                                        <i class="bi bi-box-seam text-primary"></i>
                                    </span>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        id="productName" 
                                        name="name" 
                                        placeholder="Enter product name" 
                                        required
                                        <?= !$hasSuppliers ? 'disabled' : '' ?>
                                    >
                                </div>
                                <div class="invalid-feedback">
                                    Please provide a product name.
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="productCategory" class="form-label">Category</label>
                                <select class="form-select" id="productCategory" name="category" required <?= !$hasSuppliers ? 'disabled' : '' ?>>
                                    <option value="" selected disabled>Select a category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= htmlspecialchars($category) ?>"><?= htmlspecialchars($category) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">
                                    Please select a category.
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="productSupplier" class="form-label">Supplier</label>
                                <select class="form-select" id="productSupplier" name="supplier" required <?= !$hasSuppliers ? 'disabled' : '' ?>>
                                    <option value="" selected disabled>Select a supplier</option>
                                    <?php foreach ($suppliers as $supplier): ?>
                                        <option value="<?= htmlspecialchars($supplier['SupplierID']) ?>"><?= htmlspecialchars($supplier['Name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">
                                    Please select a supplier.
                                </div>
                                <?php if ($hasSuppliers): ?>
                                <small class="text-muted">Choose the supplier who will provide this product</small>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-4">
                                <label for="productPrice" class="form-label">Price</label>
                                <div class="currency-input">
                                    <input 
                                        type="number" 
                                        class="form-control" 
                                        id="productPrice" 
                                        name="price" 
                                        step="0.01" 
                                        min="0" 
                                        placeholder="0.00" 
                                        required
                                        <?= !$hasSuppliers ? 'disabled' : '' ?>
                                    >
                                </div>
                                <div class="invalid-feedback">
                                    Please provide a valid price.
                                </div>
                                <small class="text-muted">Enter amount in Philippine Peso (₱)</small>
                            </div>
                            
                            <div class="d-flex justify-content-end gap-2 button-group mt-5">
                                <a href="index.php" class="btn btn-outline-secondary px-4">
                                    Cancel
                                </a>
                                <button type="submit" class="btn btn-primary px-4" <?= !$hasSuppliers ? 'disabled' : '' ?>>
                                    <i class="bi bi-plus-lg me-1"></i> Add Product
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('.needs-validation');
            
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    
                    form.classList.add('was-validated');
                }, false);
            });
        });
    </script>
</body>
</html>