<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$conn = mysqli_connect('localhost', 'root', '', 'portfolio');
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$user_id = $_SESSION['user_id'];


// Fetch NEPSE API data
$apiUrl = "http://localhost:3000/today-price";
$response = @file_get_contents($apiUrl);
if ($response === FALSE) {
    die("⚠️ Failed to fetch NEPSE API from $apiUrl");
}

$apiData = json_decode($response, true);

// Map API stock prices
$ltpList = [];
if (isset($apiData['data'])) {
    foreach ($apiData['data'] as $item) {
        $code = strtoupper(trim($item['company']['code']));
        $close = $item['price']['close'];
        $ltpList[$code] = $close;
    }
}

// Fetch user stocks
$sql = "SELECT * FROM stocks WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$totalPortfolioValue = 0;
$totalWACC = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stock Portfolio Tracker</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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

table {
  width: 100%;
  margin-top: 1rem;
  border-collapse: collapse;
  overflow-x: auto;
}

th, td {
  text-align: center;
  padding: 0.5rem;
  border-bottom: 1px solid #e0e0e0;
  font-size: 15px;
  color: var(--text-light);
}

th {
    background-color: #00acc1;

  color: black;
}

body.dark-mode td:not(.text-profit):not(.text-loss),
body.dark-mode th,
body.dark-mode a,
body.dark-mode .profit-loss,
body.dark-mode .card h2 {
  color: var(--text-dark) !important;
}

td .deleteStockButton, td .editStockButton {
 padding: 5px 10px;
 background-color: #d9534f;
 color: #fff;
 border: none;
 border-radius: 5px;
 cursor: pointer;
}

td .editStockButton {
 background-color: #5bc0de;
 margin-right: 5px;
}

td .deleteStockButton:hover {
 background-color: #c9302c;
}

td .editStockButton:hover {
 background-color: #31b0d5;
}

.portfolio-value {
 margin-top: 20px;
 font-size: 1.2em;
}
.profit-loss {
 margin-bottom: 20px;

 padding: 10px;
 text-align: center;
 font-size: 1.2em;
 border: 2px solid #ccc;
 border-radius: 5px;
 transition: background-color 0.3s ease;

}


.profit {
 color: green;
 background-color: #dff0d8;
 border-color: #d6e9c6;
}

.loss {
 color: red;
 background-color: #f2dede;
 border-color: #ebccd1;
}

td.gain-loss {
 text-align: right;
 padding-right: 10px;
}

td.gain-loss span {
 font-size: 0.8em;
 color: #666;
}
.profit-loss {
 margin-bottom: 20px;
 padding: 10px;
 text-align: center;
 font-size: 1.2em;
 border: 2px solid #ccc;
 border-radius: 5px;
 transition: background-color 0.3s ease;
}



td.gain-loss {
 text-align: right;
 padding-right: 10px;
 font-weight: bold;
}

td.gain-loss.green {
 color: green;
}

td.gain-loss.red {
 color: red;
}
     .profit-loss {
         margin-bottom: 20px;
         padding: 10px;
         text-align: center;
         font-size: 1.2em;
         border: 2px solid #ccc;
         border-radius: 5px;
         transition: background-color 0.3s ease;
     }

     .profit {
         color: green;
        
     }

     .loss {
         color: red;
      
     }
    
.text-profit {
    color: green !important;
}

.text-loss {
    color: red !important;
}
.download-btn {
    padding: 10px 20px;
    background-color: #007bff; /* Bootstrap primary color */
    color: #fff;
    text-decoration: none;
    font-weight: 600;
    border-radius: 8px;
    transition: background-color 0.3s ease, transform 0.2s ease;
}

.download-btn:hover {
    background-color: #0056b3;
    transform: scale(1.05);
    text-decoration: none;
}

.deleteStockButton {
    background-color: #dc3545;
    color: white;
    border: none;
    padding: 8px 14px;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.deleteStockButton:hover {
    background-color: #c82333;
}
body.dark-mode .profit-loss {
    background-color: #343a40;
    color: #f8f9fa;
}

body.dark-mode .profit-loss #profitLossValue {
    color: inherit;
}

body.dark-mode .download-btn {
    background-color: #28a745;
}

body:not(.dark-mode) .download-btn {
    background-color: #007bff;
}

.download-btn:hover {
    background-color: #0056b3;
}
.logbutton{
    margin-right: 80px;
}
         
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
    <label class="ios-switch">
        <input type="checkbox" id="themeToggle" onchange="toggleTheme()">
        <span class="slider">
            <i class="fas fa-sun sun-icon"></i>
            <i class="fas fa-moon moon-icon"></i>
        </span>
    </label>
    <a href="logout.php" class="logbutton"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>



</nav>
<div class="container">
<div class="profit-loss" id="profitLoss">
   
    <?php
    if ($result->num_rows > 0) {
        $result->data_seek(0);
        while ($row = $result->fetch_assoc()) {
            $stockName = strtoupper(trim($row['stock_name']));
            $buyPrice = $row['buy_price'];
            $stockQuantity = $row['stock_quantity'];
            $stockPrice = $ltpList[$stockName] ?? 0;

            $totalValue = $stockPrice * $stockQuantity;
            $WACC = $buyPrice * $stockQuantity;

            $totalPortfolioValue += $totalValue;
            $totalWACC += $WACC;
        }
    }
    $profitLoss = $totalPortfolioValue - $totalWACC;
$plClass = $profitLoss >= 0 ? 'text-profit' : 'text-loss';
?>
<div class="profit-loss" id="profitLoss" style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px; padding: 10px 20px; background-color: #f8f9fa; border-radius: 10px;">
    <div>
        Profit/Loss: Rs 
        <span id="profitLossValue" class="<?= $plClass ?>">
            <?= number_format($profitLoss, 2) ?>
        </span>
    </div>
    <a href="createpdf.php" target="_blank" download="portfolio.pdf" id="downloadPdf" class="btn btn-primary download-btn">
        Download PDF
    </a>
</div>


        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Stock Name</th>
                    <th>Stock Price</th>
                    <th>Quantity</th>
                    <th>Buy Price</th>
                    <th>Total Value</th>
                    <th>WACC</th>
                    <th>Gain/Loss</th>
                    <th>Buy Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="stockTableBody">
                <?php
                $stmt->execute();
                  // Rerun to reset pointer
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $rowCount = 0;
                    while ($row = $result->fetch_assoc()) {
                        $rowCount++;
                        $stockId = $row['id'];
                        $stockName = strtoupper(trim($row['stock_name']));
                        $stockQuantity = $row['stock_quantity'];
                        $buyPrice = $row['buy_price'];
                        $createdAt = date('Y-m-d', strtotime($row['created_at']));
                        $stockPrice = $ltpList[$stockName] ?? 0;

                        $totalValue = $stockPrice * $stockQuantity;
                        $WACC = $buyPrice * $stockQuantity;
                        $gainLoss = $totalValue - $WACC;
                        $gainLossPercentage = ($WACC > 0) ? round(($gainLoss / $WACC) * 100, 2) : 0;
                        $gainLossClass = $gainLoss >= 0 ? 'text-profit' : 'text-loss';

                        echo "<tr>
                        <td>$rowCount</td>
                        <td>$stockName</td>
                        <td>Rs " . number_format($stockPrice, 2) . "</td>
                        <td>$stockQuantity</td>
                        <td>Rs " . number_format($buyPrice, 2) . "</td>
                        <td>Rs " . number_format($totalValue, 2) . "</td>
                        <td>Rs " . number_format($WACC, 2) . "</td>
                        <td class='$gainLossClass'>" . number_format($gainLoss, 2) . " ({$gainLossPercentage}%)</td>
                        <td>$createdAt</td>
                        <td>
                            <a href='editstock.php?id=$stockId' class='editStockButton'>Edit</a>
                            <a href='deletestock.php?id=$stockId' class='deleteStockButton'>Delete</a>
                        </td>
                      </tr>";
                
                    }
                } else {
                    echo "<tr><td colspan='10'>No stocks found</td></tr>";
                }
                ?>
            </tbody>
        </table>
        <div class="portfolio-value">
            Total Portfolio Value: Rs 
            <span id="portfolioValue"><?php echo number_format($totalPortfolioValue, 2); ?></span>
        </div>
    
</div>

<script>
  // Toggle theme function
function toggleTheme() {
    document.body.classList.toggle('dark-mode');
    const theme = document.body.classList.contains('dark-mode') ? 'dark' : 'light';
    localStorage.setItem('theme', theme);
}

// Load stored theme on page load
window.onload = function() {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
    }
};

</script>
</body>
</html>
