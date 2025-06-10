<?php
require_once "../includes/auth.php";
require_once "../includes/db.php";

$id = $_GET['id'];
$pdo->prepare("DELETE FROM sales WHERE SaleID = ?")->execute([$id]);

header("Location: index.php");
