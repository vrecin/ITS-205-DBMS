<!-- filepath: c:\xampp\htdocs\inventory_2\inventory_system\delete_all_login_history.php -->
<!-- filepath: c:\xampp\htdocs\inventory_2\inventory_system\delete_all_login_history.php -->
<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "includes/auth.php";
require_once "includes/db.php";

if (!$pdo) {
    die("Database connection failed.");
}
try {
    // Delete all login history records
    $stmt = $pdo->prepare("DELETE FROM user_login_history");
    $stmt->execute();

    // Redirect back to the user management page with a success message
    header("Location: user_management.php?message=Login history deleted successfully");
    exit();
} catch (PDOException $e) {
    // Redirect back with an error message
    header("Location: user_management.php?error=Failed to delete login history: " . $e->getMessage());
    exit();
}
?>