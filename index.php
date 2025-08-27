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
$name = $_SESSION['name'];

$query = $conn->prepare("SELECT stock_name, stock_quantity, buy_price FROM stocks WHERE user_id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();




// Fetch NEPSE API data
$apiUrl = "http://localhost:3000/today-price";
$response = @file_get_contents($apiUrl);
if ($response === FALSE) {
    die("⚠️ Failed to fetch NEPSE API from $apiUrl");
}

$apiData = json_decode($response, true);

// Map API data: [ 'NIMB' => 200.5, ... ]
$ltpList = [];
if (isset($apiData['data'])) {
    foreach ($apiData['data'] as $item) {
        $code = strtoupper(trim($item['company']['code']));
        $close = $item['price']['close'];
        $ltpList[$code] = $close;
    }
}

// Prepare data for display
$stocks = [];
$totalValue = 0;
$totalProfitLoss = 0;

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $nameCode = strtoupper(trim($row['stock_name']));
        $buy = $row['buy_price'];
        $qty = $row['stock_quantity'];
        $price = $ltpList[$nameCode] ?? 0;

        $stocks[] = [
            'stock_name' => $nameCode,
            'buy_price' => $buy,
            'stock_quantity' => $qty,
            'stock_price' => $price
        ];

        $totalValue += $price * $qty;
        $totalProfitLoss += ($price - $buy) * $qty;
    }
}

$conn->close();
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Portfolio Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<style>


:root {
  --primary: #6366f1;
  --primary-dark: #818cf8;
  --bg-light: #f9fafb;
  --bg-dark: #121212;
  --text-light: #1f2937;
  --text-dark: #ffffff;
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

/* ===================== Navbar ===================== */
nav {
    background-color: #0b101e;
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
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #ccc;
  border-radius: 34px;
  transition: background-color 0.4s;
  cursor: pointer;
}

.slider::before {
  content: "";
  position: absolute;
  height: 24px;
  width: 24px;
  left: 4px;
  bottom: 4px;
  background-color: #fff;
  border-radius: 50%;
  transition: transform 0.4s;
  z-index: 2;
}

.sun-icon,
.moon-icon {
  position: absolute;
  font-size: 14px;
  top: 50%;
  transform: translateY(-50%);
  z-index: 3;
  transition: opacity 0.4s ease;
}

.sun-icon {
  left: 8px;
  color: #ffca28;
  opacity: 1;
}

.moon-icon {
  right: 8px;
  color: #90caf9;
  opacity: 0;
}

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

/* ===================== Layout and Content ===================== */
.container {
  width: 90%;
  max-width: 1100px;
  margin: auto;
  margin-top: 0.5rem;
}

.welcome {
  text-align: center;
  margin-bottom: 10px;
  color: var(--text-light);
}

.welcome h1 {
  font-size: 2rem;
  margin-bottom: 0.5rem;
  color: var(--text-light);
}

.welcome p {
  font-size: 1.1rem;
  color: var(--text-light);
}

.profit-loss {
  text-align: center;
  font-size: 1.2rem;
  margin-bottom: 2rem;
  font-weight: bold;
  color: yellow;
}

.card {
  background: var(--card-light);
  padding: 2rem;
  border-radius: 16px;
  box-shadow: 0 6px 24px rgba(0, 0, 0, 0.1);
  margin-bottom: 25px;
  opacity: 0;
  transform: translateY(30px);
  animation: fadeSlideUp 0.6s forwards;
  transition: all 0.5s ease;
  color: var(--text-light);
}

.card:nth-child(1) { animation-delay: 0.2s; }
.card:nth-child(2) { animation-delay: 0.4s; }

@keyframes fadeSlideUp {
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.card h2 {
  margin-top: 0;
  margin-bottom: 1rem;
  color: var(--text-light);
  border-left: 4px solid var(--primary);
  padding-left: 12px;
}

.dashboard {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 2rem;
  flex-wrap: wrap;
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

canvas {
  max-width: 100%;
  height: auto;
  transition: all 0.4s ease;
}

.logbutton {
  margin-right: 80px;
}

/* ===================== Dark Mode Styling ===================== */
body.dark-mode {
  background-color: var(--bg-dark);
  color: var(--text-dark);
}

body.dark-mode .card,
body.dark-mode .welcome {
  background: var(--card-dark);
  color: var(--text-dark);
}



body.dark-mode .welcome h1,
body.dark-mode .welcome p,
body.dark-mode h1,
body.dark-mode h2,
body.dark-mode h3,
body.dark-mode td,
body.dark-mode th,
body.dark-mode a,
body.dark-mode .profit-loss,
body.dark-mode .card h2 {
  color: var(--text-dark) !important;
}

body.dark-mode .card,
body.dark-mode canvas {
  box-shadow: 0 0 20px rgba(255, 255, 255, 0.08),
              0 0 40px rgba(255, 255, 255, 0.05);
  transition: box-shadow 0.5s ease;
}

body.dark-mode .card:hover {
  box-shadow: 0 0 25px rgba(255, 255, 255, 0.15),
              0 0 50px rgba(255, 255, 255, 0.08);
  transform: translateY(-2px);
}

body.dark-mode .profit {
  color: #00ff99;
}

body.dark-mode .loss {
  color: #ff6b6b;
}

/* ===================== Animations ===================== */
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* ===================== Responsive ===================== */
@media screen and (max-width: 768px) {
  .dashboard {
    grid-template-columns: 1fr;
  }

  nav {
    flex-direction: column;
    align-items: flex-start;
  }

  .logbutton {
    margin-right: 0;
    margin-top: 0.5rem;
  }
}
/* Light mode */
.profit {
  color: green;
}

.loss {
  color: red;
}

body.dark-mode .profit {
  color:green !important; /* bright green */
}

body.dark-mode .loss {
  color:red !important; /* bright red */
}


body{
  overflow: hidden;
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
    <div class="welcome" style="background: linear-gradient(135deg, #00bcd4 0%, #2196f3 100%); color: white; padding: 2rem; border-radius: 16px; box-shadow: 0 8px 24px rgba(0,0,0,0.1); margin-bottom: 2rem; text-align: center; animation: fadeIn 1s ease-in-out;">
        <h1 style="font-size: 2.5rem; margin: 0;">Welcome, <span style="font-weight: 600;"><?= htmlspecialchars($name) ?></span> 👋</h1>
        <p style="font-size: 1.2rem; margin-top: 10px;">
            <i class="fas fa-chart-line"></i>
            Total Portfolio Value:
            <strong style="font-size: 1.5rem;">Rs. <?= number_format($totalValue, 2) ?></strong>
        </p>
    </div>

    <div class="dashboard">
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h2>Your Stocks</h2>
                <div style="font-weight: bold; font-size: 1rem; color: <?= $totalProfitLoss >= 0 ? 'green' : 'red' ?>">
                    <?= $totalProfitLoss >= 0 ? "Total Profit: Rs. " . number_format($totalProfitLoss, 2) : "Total Loss: Rs. " . number_format(abs($totalProfitLoss), 2) ?>
                </div>
            </div>
            <table>
                <tr>
                    <th>Stock</th>
                    <th>Quantity</th>
                    <th>Price (Rs.)</th>
               
                    <th>Total (Rs.)</th>
                    <th>Profit/Loss</th>
                </tr>
                <?php foreach ($stocks as $stock): 
                    $total = $stock['stock_quantity'] * $stock['stock_price'];
                    $individualProfitLoss = ($stock['stock_price'] - $stock['buy_price']) * $stock['stock_quantity'];
                ?>
            <tr>
    <td><?= htmlspecialchars($stock['stock_name']) ?></td>
    <td><?= $stock['stock_quantity'] ?></td>
    <td><?= number_format($stock['stock_price'], 2) ?></td>
    <td><?= number_format($total, 2) ?></td>
    <td class="<?= $individualProfitLoss >= 0 ? 'profit' : 'loss' ?>">
        <?= $individualProfitLoss >= 0 ? '+' : '-' ?>Rs. <?= number_format(abs($individualProfitLoss), 2) ?>
    </td>
</tr>

                <?php endforeach; ?>
            </table>
        </div>

        <div class="card">
            <h2>Stock Distribution</h2>
            <canvas id="stockChart" width="100" height="100"></canvas>
        </div>
    </div>
</div>

<script>
    const stockLabels = <?= json_encode(array_column($stocks, 'stock_name')) ?>;
    const stockData = <?= json_encode(array_map(function($s) {
        return $s['stock_quantity'] * $s['stock_price'];
    }, $stocks)) ?>;

    // Store chart instance globally to allow re-rendering
    let stockChart;

    function getChartColors() {
        // Dark-friendly palette with higher contrast
        return [
            '#ff6b6b', '#4ecdc4', '#ffe66d', '#1a535c', '#f25f5c',
            '#2ec4b6', '#ff9f1c', '#e71d36', '#3a86ff', '#8338ec'
        ];
    }

    function getLegendTextColor() {
        return document.body.classList.contains('dark-mode') ? '#f0f0f0' : '#333';
    }

    function renderChart() {
        const ctx = document.getElementById('stockChart').getContext('2d');

        // Destroy previous chart if it exists
        if (stockChart) stockChart.destroy();

        stockChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: stockLabels,
                datasets: [{
                    data: stockData,
                    backgroundColor: getChartColors(),
                    borderColor: '#fff',
                    borderWidth: 2,
                    hoverOffset: 15
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: getLegendTextColor(),
                            font: { size: 14 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                const label = context.label || '';
                                const value = context.raw;
                                const total = context.chart._metasets[context.datasetIndex].total;
                                const percentage = ((value / total) * 100).toFixed(2);
                                return `${label}: Rs. ${value.toFixed(2)} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    // Dark/Light Theme Toggle without reload
    function toggleTheme() {
        document.body.classList.toggle('dark-mode');

        const theme = document.body.classList.contains('dark-mode') ? 'dark' : 'light';
        localStorage.setItem('theme', theme);

        renderChart(); // Re-render to update legend color
    }

    // On load, apply saved theme and render chart
    window.addEventListener('DOMContentLoaded', () => {
        const savedTheme = localStorage.getItem('theme');
        const toggle = document.getElementById('themeToggle');

        if (savedTheme === 'dark') {
            document.body.classList.add('dark-mode');
            toggle.checked = true;
        } else {
            toggle.checked = false;
        }

        renderChart(); // Initial chart render
    });
</script>



</body>
</html>