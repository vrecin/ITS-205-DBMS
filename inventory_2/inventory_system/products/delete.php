<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "../includes/auth.php";
require_once "../includes/db.php";

$id = $_GET['id'];
$stmt = $pdo->prepare("DELETE FROM products WHERE ProductID = ?");
$stmt->execute([$id]);

header("Location: index.php");
