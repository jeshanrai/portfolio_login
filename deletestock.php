<?php
$sid = $_GET["id"];
echo "User with ID: " . $sid . " is going to be deleted.<br>";

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "portfolio";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Use prepared statement to avoid SQL injection
$deleteSql = "DELETE FROM stocks WHERE id=?";
$stmt = $conn->prepare($deleteSql);
$stmt->bind_param("i", $sid); // "i" means the parameter is an integer

if ($stmt->execute()) {
    // Redirect to portfolio.php after successful deletion
    header('Location: portfolio.php?message=deleted');
    exit();
} else {
    echo "Error deleting data: " . $stmt->error;
}

// Close the statement and connection
$stmt->close();
$conn->close();
?>
