<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "portfolio";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the stock id from URL
if (!isset($_GET['id'])) {
    header("Location: portfolio.php");
    exit;
}

$stockId = intval($_GET['id']);

// Fetch stock details
$stmt = $conn->prepare("SELECT * FROM stocks WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $stockId, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows != 1) {
    header("Location: portfolio.php");
    exit;
}

$stock = $result->fetch_assoc();
$stmt->close();

// Handle form submit for updating
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $stockName = $_POST['stockName'];
    $stockQuantity = $_POST['stockQuantity'];
    $buyPrice = $_POST['buyPrice'];

    $updateStmt = $conn->prepare("UPDATE stocks SET stock_name = ?, stock_quantity = ?, buy_price = ? WHERE id = ? AND user_id = ?");
    $updateStmt->bind_param("sdiii", $stockName, $stockQuantity, $buyPrice, $stockId, $_SESSION['user_id']);
    $updateStmt->execute();
    $updateStmt->close();

    $conn->close();
    header("Location: portfolio.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Stock</title>
    <style>
         
   :root {
  --primary: #6366f1;
  --primary-dark: #818cf8;
  --bg-light: #f9fafb;
  --bg-dark: #121212;
  --text-light: #1f2937;
  --text-dark: #ffffff; /* pure white */
  --input-light: rgba(255, 255, 255, 0.6);
  --input-dark: rgba(40, 40, 70, 0.5);
  --shadow-light: rgba(0, 0, 0, 0.1);
  --shadow-dark: rgba(255, 255, 255, 0.05);
  --blur: 20px;
}

/* ===================== Body ===================== */
body {
  font-family: 'Poppins', sans-serif;
  background: linear-gradient(135deg, #e0e7ff, #f9fafb);
  color: var(--text-light);
  margin: 0;
  padding: 70px 0 0;
  transition: background 0.4s, color 0.4s;
}

body.dark-mode {
  background: linear-gradient(135deg, #1e1e2f, #121212);
  color: var(--text-dark);
}
nav {
    background: #111827;
    color: white;
    padding: 1rem 2rem;
    position: fixed;
    width: 100%;
    top: 0;
    z-index: 999;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}
nav a {
    color: #ffffffdd;
    text-decoration: none;
    margin: 0 1rem;
    font-weight: 500;
    transition: 0.3s ease;
}

nav a:hover {
    color: #ffffff;
    background-color: #00bcd4;
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
}

/* ===================== Theme Toggle ===================== */
.ios-switch {
  position: relative;
  display: inline-block;
  width: 60px;
  height: 32px;
  margin-right: 20px;
}
.ios-switch input {
  opacity: 0;
  width: 0;
  height: 0;
}
.slider {
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 0;
  background-color: #ccc;
  border-radius: 34px;
  transition: 0.4s;
  cursor: pointer;
}
.slider::before {
  content: "";
  position: absolute;
  height: 24px;
  width: 24px;
  left: 4px;
  bottom: 4px;
  background-color: white;
  border-radius: 50%;
  transition: transform 0.4s;
}
.sun-icon, .moon-icon {
  position: absolute;
  font-size: 14px;
  top: 50%;
  transform: translateY(-50%);
  z-index: 3;
}
.sun-icon { left: 8px; color: #ffca28; }
.moon-icon { right: 8px; color: #90caf9; opacity: 0; }
input:checked + .slider {
  background-color: #4cd964;
}
input:checked + .slider::before {
  transform: translateX(26px);
}
input:checked + .slider .sun-icon {
  opacity: 0;
}
input:checked + .slider .moon-icon {
  opacity: 1;
}

/* ===================== Container ===================== */
.container {
  background: rgba(255, 255, 255, 0.6);
  backdrop-filter: blur(var(--blur));
  -webkit-backdrop-filter: blur(var(--blur));
  padding: 2.5rem;
  border-radius: 1.5rem;
  box-shadow: 0 12px 30px var(--shadow-light);
  width: 100%;
  max-width: 500px;
  margin: 150px auto 2rem;
  animation: fadeIn 1s ease forwards;
  transform: translateY(20px);
  opacity: 0;
  color: var(--text-light);
  margin-top: 80px
}

body.dark-mode .container {
  background: rgba(30, 30, 60, 0.6);
  color: var(--text-dark);
  box-shadow: 0 12px 30px var(--shadow-dark);
}

/* ===================== Form Title ===================== */
.container h2 {
  font-size: 2rem;
  font-weight: 600;
  margin-bottom: 1.8rem;
  text-align: center;
  color: inherit;
}

/* ===================== Form ===================== */
.boid-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  color: inherit;
}

.input-group {
  display: flex;
  flex-direction: column;
}

.input-group label {
  font-size: 0.95rem;
  margin-bottom: 0.4rem;
  font-weight: 500;
  color: inherit;
}

.input-group input {
  padding: 0.9rem 1.2rem;
  border-radius: 1rem;
  border: 1px solid rgba(0, 0, 0, 0.1);
  background: var(--input-light);
  font-size: 1rem;
  color: var(--text-light);
  transition: all 0.3s ease;
}

.input-group input:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.3);
  transform: scale(1.02);
}

body.dark-mode .input-group input {
  background: var(--input-dark);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: var(--text-dark);
}

body.dark-mode .input-group input:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.3);
}

/* ===================== Button ===================== */
#addStockButton {
  padding: 0.9rem;
  background-color: var(--primary);
  color: #fff;
  border: none;
  border-radius: 1rem;
  font-size: 1rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 4px 14px rgba(79, 70, 229, 0.25);
}

#addStockButton:hover {
  background-color: var(--primary-dark);
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(79, 70, 229, 0.35);
}

#addStockButton:active {
  transform: translateY(1px);
}

body.dark-mode #addStockButton {
  background-color: var(--primary);
  color: #fff;
}

/* ===================== Animation ===================== */
@keyframes fadeIn {
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

/* ===================== Responsive ===================== */
@media screen and (max-width: 480px) {
  .container {
    padding: 2rem 1.5rem;
  }

  .container h2 {
    font-size: 1.5rem;
  }
}

.logbutton {
    margin-right: 60px;}
    </style>
</head>
<body>
<nav>
    <div>
        <a href="index.php"><i class="fas fa-home"></i> Home</a>
        <a href="portfolio.php"><i class="fas fa-list"></i> Portfolio</a>
        <a href="addstock.php"><i class="fas fa-plus-circle"></i> Add Stock</a>
    </div>
    <div style="display: flex; align-items: center;">
        <a href="logout.php" class="logbutton"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</nav>

<div class="container">
  <h2>Edit Stock</h2>
  <form action="" method="POST" class="boid-form">
    <div class="input-group">
      <label for="stockName">Stock Name</label>
      <input type="text" id="stockName" name="stockName" value="<?php echo htmlspecialchars($stock['stock_name']); ?>" required>
    </div>

    <div class="input-group">
      <label for="stockQuantity">Quantity</label>
      <input type="number" id="stockQuantity" name="stockQuantity" value="<?php echo htmlspecialchars($stock['stock_quantity']); ?>" required>
    </div>

    <div class="input-group">
      <label for="buyPrice">Buy Price</label>
      <input type="number" id="buyPrice" name="buyPrice" value="<?php echo htmlspecialchars($stock['buy_price']); ?>" required>
    </div>

    <button type="submit" id="addStockButton">Update Stock</button>
  </form>
</div>

</body>
</html>
