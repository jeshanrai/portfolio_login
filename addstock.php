<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Database connection details
$servername = "localhost";
$username = "root"; // Use your database username
$password = ""; // Use your database password
$dbname = "portfolio"; // Replace with your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $stockName = $_POST['stockName'];
    $stockPrice = $_POST['stockPrice'];
    $stockQuantity = $_POST['stockQuantity'];
    $buyPrice = $_POST['buyPrice'];
    $user_id = $_SESSION['user_id']; 

    // Prepare and bind statement
    $stmt = $conn->prepare("INSERT INTO stocks (stock_name, stock_price, stock_quantity, buy_price, user_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sdidd", $stockName, $stockPrice, $stockQuantity, $buyPrice, $user_id);

    $stmt->execute();
    $stmt->close();
    $conn->close();

    header("Location: portfolio.php");
    exit;
}

$conn->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add BOID - BOID Recordbook</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* General styles */
body {
    font-family: Arial, sans-serif;
    background-color: #f9f9f9;
    margin: 0;
    padding-top: 70px; /* Prevent content from overlapping the fixed navbar */
}

.container {
    width:1500px;
    /* max-width: 800px; */
    margin: 0 auto;
    background-color: white;
    padding: 20px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    margin-top: 30px;
}

h2 {
    text-align: center;
    color: #333;
}


/* Styling for the navbar */
nav {
    background: #333;
    padding: 1rem;
    display: flex;
    align-items: center;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    position: fixed;
    top: 0;
    width: 100%;
    z-index: 1000;
}

.nav-left {
    display: flex;
    gap: 1.5rem;
}

.nav-right {
    margin-left: auto;
}

nav a {
    text-decoration: none;
    color: white;
    font-weight: bold;
    padding: 0.5rem 1rem;
    transition: background 0.3s ease, color 0.3s ease;
    border-radius: 4px;
}

nav a:hover {
    background: #00bcd4;
    color: white;
}

.input-section {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    width: 100%;
    flex-direction:column;

}

.input-section input {
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 5px;
    width: 100%;
}

.input-section button {
    padding: 10px 20px;
    background-color: #5bc0de;
    color: #fff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.input-section button:hover {
    background-color: #31b0d5;
}


        </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav>
        <div class="nav-left">
            <a href="index.php"><i class="fas fa-home"></i> HOME</a>
            <a href="addstock.php"><i class="fas fa-plus-circle"></i> Add Stock</a>
            <a href="portfolio.php"><i class="fas fa-list"></i> Portfolio</a>
        </div>
        <div class="nav-right">
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>

    <!-- Form Container -->
    <div class="container">
        <h2>Add Stock</h2>
        <form action="addstock.php" method="POST" class="boid-form">
        <div class="input-section">
     <input type="text" id="stockName" name="stockName" placeholder="Stock Name">
        <input type="number" id="stockPrice" name="stockPrice" placeholder="Stock Price">
                <input type="number" id="stockQuantity"  name="stockQuantity" placeholder="Stock Quantity">
                <input type="number" id="buyPrice" name="buyPrice"" placeholder="Buy Price">
                <button id="addStockButton">Add Stock</button>
            </div>
        </form>
    </div>\

    <div>
        
</div>
</body>
</html>
