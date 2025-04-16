<?php
session_start();
include('config.php');
$conn = new mysqli('localhost', 'root', '', 'portfolio');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['id'])) {
    $stock_id = $_GET['id'];
    $user_id = $_SESSION['user_id']; // User ID stored in session

    // Delete the BOID
    $sql = "DELETE FROM portfolio WHERE id = '$stock_id' AND user_id = '$user_id'";
    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('BOID deleted successfully'); window.location.href = 'portfolio.php';</script>";
    } else {
        echo "<script>alert('Error deleting BOID'); window.location.href = 'portfolio.php';</script>";
    }
}
?>
