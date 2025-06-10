<?php
require_once "../includes/auth.php";
require_once "../includes/db.php";

$products = $pdo->query("SELECT * FROM products")->fetchAll();

// We will load suppliers dynamically based on product selection

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $productID = $_POST['product'];
    $supplierID = $_POST['supplier'];
    $quantity = $_POST['quantity'];

    $stmt = $pdo->prepare("INSERT INTO stock (ProductID, SupplierID, QuantityAdded, DateAdded)
                           VALUES (?, ?, ?, NOW())");
    $stmt->execute([$productID, $supplierID, $quantity]);

    header("Location: index.php");
    exit;
}

// Ajax handler to load suppliers for a specific product
if (isset($_GET['action']) && $_GET['action'] === 'get_suppliers' && isset($_GET['product_id'])) {
    $productID = $_GET['product_id'];
    
    $stmt = $pdo->prepare("
        SELECT s.SupplierID, s.Name 
        FROM suppliers s
        JOIN supplier_products sp ON s.SupplierID = sp.SupplierID
        WHERE sp.ProductID = ?
        ORDER BY s.Name
    ");
    $stmt->execute([$productID]);
    $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return as JSON
    header('Content-Type: application/json');
    echo json_encode($suppliers);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Stock</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        
        .card {
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        
        .form-label {
            font-weight: 500;
        }
        
        .form-select:focus, .form-control:focus {
            border-color: #198754;
            box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25);
        }
        
        .btn-success {
            background-color: #198754;
            border-color: #198754;
        }
        
        .btn-success:hover {
            background-color: #157347;
            border-color: #146c43;
        }
        
        .spinner-border {
            width: 1rem;
            height: 1rem;
            border-width: 0.2em;
        }
        
        .no-suppliers-message {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-lg rounded-4">
                    <div class="card-body p-5">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2 class="fw-bold mb-0 d-flex align-items-center">
                                <i class="fas fa-cubes text-success me-2"></i> Add Stock Entry
                            </h2>
                            <a href="index.php" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-arrow-left me-1"></i> Back to Stock
                            </a>
                        </div>

                        <form method="POST" id="stockForm">
                            <div class="mb-3">
                                <label class="form-label">Product:</label>
                                <select class="form-select" name="product" id="productSelect" required>
                                    <option disabled selected value="">-- Select a Product --</option>
                                    <?php foreach ($products as $p): ?>
                                        <option value="<?= $p['ProductID'] ?>"><?= htmlspecialchars($p['Name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Supplier:</label>
                                <select class="form-select" name="supplier" id="supplierSelect" required disabled>
                                    <option disabled selected value="">-- Select a Product First --</option>
                                </select>
                                <div id="supplierLoading" class="d-none mt-2">
                                    <div class="spinner-border text-secondary spinner-border-sm me-2" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <small class="text-secondary">Loading suppliers...</small>
                                </div>
                                <div id="noSuppliersMessage" class="no-suppliers-message d-none">
                                    <i class="fas fa-exclamation-circle me-1"></i>
                                    No suppliers available for this product. Please add a supplier first.
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Quantity:</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-hashtag text-secondary"></i>
                                    </span>
                                    <input type="number" name="quantity" class="form-control" min="1" required>
                                </div>
                                <small class="text-muted mt-1 d-block">Enter the number of units being added to inventory</small>
                            </div>

                            <div class="d-flex justify-content-between mt-4">
                                <a href="index.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-success" id="submitButton" disabled>
                                    <i class="fas fa-plus me-1"></i> Add Stock
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const productSelect = document.getElementById('productSelect');
            const supplierSelect = document.getElementById('supplierSelect');
            const supplierLoading = document.getElementById('supplierLoading');
            const noSuppliersMessage = document.getElementById('noSuppliersMessage');
            const submitButton = document.getElementById('submitButton');
            
            productSelect.addEventListener('change', function() {
                const productId = this.value;
                
                if (!productId) {
                    resetSupplierSelect();
                    return;
                }
                
                // Show loading indicator
                supplierSelect.disabled = true;
                supplierSelect.innerHTML = '<option disabled selected value="">Loading suppliers...</option>';
                supplierLoading.classList.remove('d-none');
                noSuppliersMessage.classList.add('d-none');
                submitButton.disabled = true;
                
                // Fetch suppliers for this product
                fetch(`?action=get_suppliers&product_id=${productId}`)
                    .then(response => response.json())
                    .then(suppliers => {
                        // Hide loading indicator
                        supplierLoading.classList.add('d-none');
                        
                        if (suppliers.length === 0) {
                            // No suppliers available
                            noSuppliersMessage.classList.remove('d-none');
                            supplierSelect.innerHTML = '<option disabled selected value="">No suppliers available</option>';
                            supplierSelect.disabled = true;
                            submitButton.disabled = true;
                        } else {
                            // Populate supplier dropdown
                            supplierSelect.innerHTML = '<option disabled selected value="">-- Select a Supplier --</option>';
                            suppliers.forEach(supplier => {
                                const option = document.createElement('option');
                                option.value = supplier.SupplierID;
                                option.textContent = supplier.Name;
                                supplierSelect.appendChild(option);
                            });
                            
                            supplierSelect.disabled = false;
                            submitButton.disabled = true;
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching suppliers:', error);
                        supplierLoading.classList.add('d-none');
                        supplierSelect.innerHTML = '<option disabled selected value="">Error loading suppliers</option>';
                    });
            });
            
            supplierSelect.addEventListener('change', function() {
                submitButton.disabled = !this.value;
            });
            
            function resetSupplierSelect() {
                supplierSelect.innerHTML = '<option disabled selected value="">-- Select a Product First --</option>';
                supplierSelect.disabled = true;
                noSuppliersMessage.classList.add('d-none');
                submitButton.disabled = true;
            }
            
            // Form validation
            document.getElementById('stockForm').addEventListener('submit', function(event) {
                if (!productSelect.value || !supplierSelect.value) {
                    event.preventDefault();
                    alert('Please select both a product and a supplier.');
                }
            });
        });
    </script>
</body>
</html>