<?php
require_once "../includes/auth.php";
require_once "../includes/db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = $_POST['name'];
    $contact = $_POST['contact'];

    $stmt = $pdo->prepare("INSERT INTO suppliers (Name, ContactInfo) VALUES (?, ?)");
    $stmt->execute([$name, $contact]);

    header("Location: index.php");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Supplier</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="card shadow rounded-4">
                    <div class="card-header bg-primary text-white rounded-top-4">
                        <h4 class="mb-0 text-center">Add New Supplier</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="name" class="form-label">Supplier Name</label>
                                <input type="text" class="form-control" id="name" name="name" placeholder="Enter supplier name" required>
                            </div>
                            <div class="mb-3">
                                <label for="contact" class="form-label">Contact Info</label>
                                <input type="text" class="form-control" id="contact" name="contact" placeholder="Enter contact info">
                            </div>
                            <div class="d-flex justify-content-between">
                                <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" class="btn btn-success">Add Supplier</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
