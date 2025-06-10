<!-- filepath: c:\xampp\htdocs\inventory_2\inventory_system\suppliers\index.php -->
<!DOCTYPE html> 
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color:rgb(236, 239, 241);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
        }
        .container {
            max-width: 1200px;
            padding: 2rem;
        }
        .page-header {
            margin-bottom: 2rem;
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
        .table {
            margin-bottom: 0;
        }
        .table th {
            border-top: none;
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .table td {
            vertical-align: middle;
        }
        .supplier-row:hover {
            background-color: #f2f4ff;
        }
        .tag {
            display: inline-block;
            padding: 4px 8px;
            margin: 2px;
            background-color: #e8f0fe;
            color: #4a6cf7;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        .btn-primary {
            background-color: #4a6cf7;
            border-color: #4a6cf7;
        }
        .btn-primary:hover {
            background-color: #3b5ddb;
            border-color: #3b5ddb;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        .btn-outline-danger {
            color: #dc3545;
            border-color: #dc3545;
        }
        .btn-outline-danger:hover {
            color: #fff;
            background-color: #dc3545;
            border-color: #dc3545;
        }
        .action-buttons .btn {
            margin-right: 5px;
        }
        .search-box {
            position: relative;
            max-width: 300px;
        }
        .search-box i {
            position: absolute;
            left: 12px;
            top: 12px;
            color: #6c757d;
        }
        .search-input {
            padding-left: 35px;
            border-radius: 20px;
        }
        .sort-icon {
            cursor: pointer;
            margin-left: 5px;
        }
        .badge-count {
            background-color: #4a6cf7;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: normal;
        }
        .back-link {
            color: #6c757d;
            text-decoration: none;
        }
        .back-link:hover {
            color: #343a40;
        }
        .empty-message {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
        }
        .view-mode-toggle {
            margin-left: 1rem;
        }
    </style>
</head>
<body>
<?php
require_once "../includes/auth.php";
require_once "../includes/db.php";

// View mode - can be 'flat' (1NF) or 'grouped' (original)
$viewMode = isset($_GET['view']) ? $_GET['view'] : 'flat';

// Fetch all suppliers
$query = "SELECT * FROM suppliers ORDER BY Name ASC";
$suppliers = $pdo->query($query)->fetchAll();

// Fetch supplier-product relationships
$productQuery = "
    SELECT sp.SupplierID, sp.ProductID, p.Name AS ProductName, s.Name AS SupplierName, s.ContactInfo
    FROM supplier_products sp
    JOIN products p ON sp.ProductID = p.ProductID
    JOIN suppliers s ON sp.SupplierID = s.SupplierID
    ORDER BY s.Name ASC, p.Name ASC
";
$supplierProducts = $pdo->query($productQuery)->fetchAll();

// Group products by supplier for the original view
$productMap = [];
foreach ($supplierProducts as $row) {
    $productMap[$row['SupplierID']][] = $row['ProductName'];
}

// Count unique suppliers in the relationship table
$uniqueSuppliers = [];
foreach ($supplierProducts as $sp) {
    $uniqueSuppliers[$sp['SupplierID']] = true;
}
$supplierCount = count($suppliers);
$supplierProductCount = count($supplierProducts);
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center page-header">
        <div>
            <h2 class="mb-1"><i class="fas fa-building me-2"></i>Supplier Management</h2>
            <a href="../dashboard.php" class="back-link"><i class="fas fa-arrow-left me-1"></i>Back to Dashboard</a>
        </div>
        <div class="d-flex align-items-center">
            <span class="badge-count me-3">
                <i class="fas fa-users me-1"></i> <?= $supplierCount ?> suppliers
            </span>
            <a href="create.php" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> Add New Supplier
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <h5 class="mb-0">Suppliers Directory</h5>
                <div class="view-mode-toggle">
                    <a href="?view=flat" class="btn btn-sm <?= $viewMode == 'flat' ? 'btn-light' : 'btn-outline-light' ?>">
                        <i class="fas fa-list"></i> Flat View (1NF)
                    </a>
                    <a href="?view=grouped" class="btn btn-sm <?= $viewMode == 'grouped' ? 'btn-light' : 'btn-outline-light' ?>">
                        <i class="fas fa-layer-group"></i> Grouped View
                    </a>
                </div>
            </div>
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchSuppliers" class="form-control search-input" placeholder="Search suppliers...">
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <?php if ($viewMode == 'flat'): ?>
                <!-- Flat View (1NF) -->
                <table class="table table-hover mb-0" id="suppliersTable">
                    <thead>
                        <tr>
                            <th>Supplier ID <i class="fas fa-sort sort-icon" data-column="id"></i></th>
                            <th>Supplier Name <i class="fas fa-sort-up sort-icon" data-column="name"></i></th>
                            <th>Contact Info <i class="fas fa-sort sort-icon" data-column="contact"></i></th>
                            <th>Product <i class="fas fa-sort sort-icon" data-column="product"></i></th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (count($supplierProducts) > 0): ?>
                        <?php foreach ($supplierProducts as $sp): ?>
                            <tr class="supplier-row">
                                <td><?= $sp['SupplierID'] ?></td>
                                <td><strong><?= htmlspecialchars($sp['SupplierName']) ?></strong></td>
                                <td><?= htmlspecialchars($sp['ContactInfo']) ?></td>
                                <td>
                                    <span class="tag"><?= htmlspecialchars($sp['ProductName']) ?></span>
                                </td>
                                <td class="action-buttons">
                                    <a href="edit.php?id=<?= $sp['SupplierID'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="delete.php?id=<?= $sp['SupplierID'] ?>" class="btn btn-sm btn-outline-danger" 
                                       onclick="return confirm('Are you sure you want to delete this supplier?');">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="empty-message">
                                <i class="fas fa-info-circle me-2"></i>No supplier-product relationships found.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <!-- Grouped View (Original) -->
                <table class="table table-hover mb-0" id="suppliersTable">
                    <thead>
                        <tr>
                            <th>ID <i class="fas fa-sort sort-icon" data-column="id"></i></th>
                            <th>Name <i class="fas fa-sort-up sort-icon" data-column="name"></i></th>
                            <th>Contact Info <i class="fas fa-sort sort-icon" data-column="contact"></i></th>
                            <th>Supplies Products</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (count($suppliers) > 0): ?>
                        <?php foreach ($suppliers as $supplier): ?>
                            <tr class="supplier-row">
                                <td><?= $supplier['SupplierID'] ?></td>
                                <td><strong><?= htmlspecialchars($supplier['Name']) ?></strong></td>
                                <td><?= htmlspecialchars($supplier['ContactInfo']) ?></td>
                                <td>
                                    <?php
                                        $products = $productMap[$supplier['SupplierID']] ?? [];
                                        if (count($products) > 0):
                                            foreach ($products as $product):
                                    ?>
                                        <span class="tag"><?= htmlspecialchars($product) ?></span>
                                    <?php
                                            endforeach;
                                        else:
                                            echo '<span class="text-muted">—</span>';
                                        endif;
                                    ?>
                                </td>
                                <td class="action-buttons">
                                    <a href="edit.php?id=<?= $supplier['SupplierID'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="delete.php?id=<?= $supplier['SupplierID'] ?>" class="btn btn-sm btn-outline-danger" 
                                       onclick="return confirm('Are you sure you want to delete this supplier?');">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="empty-message">
                                <i class="fas fa-info-circle me-2"></i>No suppliers found. Click "Add New Supplier" to create one.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
    // Search functionality
    document.getElementById('searchSuppliers').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const tableRows = document.querySelectorAll('.supplier-row');
        
        tableRows.forEach(row => {
            let textContent = '';
            for (let i = 0; i < row.cells.length - 1; i++) {
                textContent += row.cells[i].textContent.toLowerCase() + ' ';
            }
            
            if (textContent.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // Simple table sorting
    document.querySelectorAll('.sort-icon').forEach(icon => {
        icon.addEventListener('click', function() {
            const column = this.dataset.column;
            const table = document.getElementById('suppliersTable');
            const rows = Array.from(table.querySelectorAll('tbody tr'));
            const isAscending = this.classList.contains('fa-sort-up');
            
            // Reset all sort icons
            document.querySelectorAll('.sort-icon').forEach(i => {
                i.classList.remove('fa-sort-up', 'fa-sort-down');
                i.classList.add('fa-sort');
            });
            
            // Set current sort icon
            if (isAscending) {
                this.classList.remove('fa-sort', 'fa-sort-up');
                this.classList.add('fa-sort-down');
            } else {
                this.classList.remove('fa-sort', 'fa-sort-down');
                this.classList.add('fa-sort-up');
            }
            
            // Sort rows
            let columnIndex;
            switch(column) {
                case 'id': columnIndex = 0; break;
                case 'name': columnIndex = 1; break;
                case 'contact': columnIndex = 2; break;
                case 'product': columnIndex = 3; break;
                default: columnIndex = 1;
            }
            
            rows.sort((a, b) => {
                const aValue = a.cells[columnIndex].textContent.trim();
                const bValue = b.cells[columnIndex].textContent.trim();
                
                if (columnIndex === 0) {
                    // Sort by ID numerically
                    return isAscending ? 
                        parseInt(bValue) - parseInt(aValue) : 
                        parseInt(aValue) - parseInt(bValue);
                } else {
                    // Sort alphabetically
                    return isAscending ? 
                        bValue.localeCompare(aValue) : 
                        aValue.localeCompare(bValue);
                }
            });
            
            // Remove existing rows
            rows.forEach(row => row.parentNode.removeChild(row));
            
            // Add sorted rows
            const tbody = table.querySelector('tbody');
            rows.forEach(row => tbody.appendChild(row));
        });
    });
</script>
</body>
</html>