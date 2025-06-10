<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Supplier</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-hover: #3a56d4;
            --secondary: #6c757d;
            --success: #38b000;
            --light-bg: #f8f9fa;
            --card-shadow: 0 4px 12px rgba(0,0,0,0.08);
            --border-radius: 8px;
        }

        body {
            background-color: #f0f2f5;
            font-family: 'Inter', sans-serif;
            color: #333;
            line-height: 1.6;
        }

        .page-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem 1.5rem;
        }

        .card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            border: none;
            margin-bottom: 1.5rem;
        }

        .card-header {
            background-color: var(--primary);
            color: white;
            padding: 1.25rem 1.5rem;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            font-weight: 500;
        }

        .card-body {
            padding: 2rem;
        }

        .form-control {
            padding: 0.75rem 1rem;
            border-radius: var(--border-radius);
            border: 1px solid #dee2e6;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
        }

        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: #495057;
        }

        .input-icon {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            left: 1rem;
            color: #6c757d;
        }

        .icon-input {
            padding-left: 2.5rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            border-radius: var(--border-radius);
        }

        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
        }

        .btn-outline-secondary:hover {
            background-color: var(--secondary);
            color: white;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 12px;
        }

        .product-item {
            background-color: #f8f9fa;
            border-radius: var(--border-radius);
            padding: 12px 16px;
            display: flex;
            align-items: center;
            border: 1px solid transparent;
            cursor: pointer;
        }

        .product-item:hover {
            background-color: #eef1ff;
            border-color: #d8e0fc;
        }

        .product-item.selected {
            background-color: #e6ebff;
            border-color: #c1d0ff;
        }

        .product-item input {
            margin-right: 10px;
        }

        .back-link {
            color: #495057;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .back-link:hover {
            color: var(--primary);
        }
    </style>
</head>
<body>
<?php
require_once "../includes/auth.php";
require_once "../includes/db.php";

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM suppliers WHERE SupplierID = ?");
$stmt->execute([$id]);
$supplier = $stmt->fetch();

$products = $pdo->query("SELECT * FROM products ORDER BY Name ASC")->fetchAll();
$stmt = $pdo->prepare("SELECT ProductID FROM supplier_products WHERE SupplierID = ?");
$stmt->execute([$id]);
$linkedProducts = array_column($stmt->fetchAll(), 'ProductID');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = $_POST['name'];
    $contact = $_POST['contact'];
    $selectedProducts = $_POST['products'] ?? [];

    $stmt = $pdo->prepare("UPDATE suppliers SET Name=?, ContactInfo=? WHERE SupplierID=?");
    $stmt->execute([$name, $contact, $id]);

    $pdo->prepare("DELETE FROM supplier_products WHERE SupplierID = ?")->execute([$id]);
    foreach ($selectedProducts as $prodId) {
        $pdo->prepare("INSERT INTO supplier_products (SupplierID, ProductID) VALUES (?, ?)")->execute([$id, $prodId]);
    }

    header("Location: index.php");
}
?>

<div class="page-container">
    <a href="index.php" class="back-link">
        <i class="fas fa-arrow-left me-2"></i> Back to Suppliers
    </a>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="mb-0">Edit Supplier</h3>
            <span class="supplier-id badge bg-light text-dark">ID: <?= $supplier['SupplierID'] ?></span>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Supplier Name</label>
                        <div class="position-relative">
                            <i class="fas fa-building input-icon"></i>
                            <input type="text" class="form-control icon-input" id="name" name="name" value="<?= htmlspecialchars($supplier['Name']) ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="contact" class="form-label">Contact Information</label>
                        <div class="position-relative">
                            <i class="fas fa-address-card input-icon"></i>
                            <input type="text" class="form-control icon-input" id="contact" name="contact" value="<?= htmlspecialchars($supplier['ContactInfo']) ?>">
                        </div>
                    </div>
                </div>

                <h5>Products Supplied</h5>
                <div class="product-grid">
                    <?php foreach ($products as $product): ?>
                        <?php $isChecked = in_array($product['ProductID'], $linkedProducts); ?>
                        <div class="product-item <?= $isChecked ? 'selected' : '' ?>">
                            <input type="checkbox" class="form-check-input" name="products[]" value="<?= $product['ProductID'] ?>" <?= $isChecked ? 'checked' : '' ?> />
                            <label class="form-check-label ms-2"><?= htmlspecialchars($product['Name']) ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="d-flex justify-content-end mt-4">
                    <a href="index.php" class="btn btn-outline-secondary me-2">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('.product-item').forEach(item => {
        const checkbox = item.querySelector('input');
        item.addEventListener('click', (e) => {
            if (e.target.tagName !== 'INPUT') {
                checkbox.checked = !checkbox.checked;
                item.classList.toggle('selected', checkbox.checked);
            }
        });
        checkbox.addEventListener('change', () => {
            item.classList.toggle('selected', checkbox.checked);
        });
    });
</script>
</body>
</html>