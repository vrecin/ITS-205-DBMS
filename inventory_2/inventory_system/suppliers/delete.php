<?php
require_once "../includes/auth.php";
require_once "../includes/db.php";

$id = $_GET['id'];

// Delete linked products first (foreign key constraint)
$pdo->prepare("DELETE FROM supplier_products WHERE SupplierID = ?")->execute([$id]);

// Delete supplier
$pdo->prepare("DELETE FROM suppliers WHERE SupplierID = ?")->execute([$id]);

header("Location: index.php");
