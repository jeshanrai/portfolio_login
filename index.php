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

$query = $conn->prepare("SELECT stock_name, stock_quantity, stock_price, buy_price FROM stocks WHERE user_id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();

$stocks = [];
$totalValue = 0;
$totalProfitLoss = 0;

while ($row = $result->fetch_assoc()) {
    $stocks[] = $row;
    $totalValue += $row['stock_quantity'] * $row['stock_price'];
}

foreach ($stocks as &$stock) {
    $stockTotal = $stock['stock_quantity'] * $stock['stock_price'];
    $stock['total'] = $stockTotal;
    $stock['profit_loss'] = ($stock['stock_price'] - $stock['buy_price']) * $stock['stock_quantity'];
    $totalProfitLoss += $stock['profit_loss'];
}
unset($stock);
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
    --primary: #00bcd4;
    --bg-light: #f0f2f5;
    --bg-dark: #1e1e2f;
    --card-light: #fff;
    --card-dark: #2c2c3e;
    --text-light: #333;
    --text-dark: #f5f5f5;
}

body {
    font-family: 'Segoe UI', sans-serif;
    background-color: var(--bg-light);
    color: var(--text-light);
    margin: 0;
    padding-top: 70px;
    transition: background 0.5s ease, color 0.5s ease;
}

/* Dark mode background */
body.dark-mode {
    background-color: var(--bg-dark);
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
    border-collapse: collapse;
    overflow-x: auto;
}

th, td {
    text-align: left;
    padding: 14px;
    border-bottom: 1px solid #e0e0e0;
    font-size: 15px;
    color: var(--text-light);
}

th {
    background-color: var(--primary);
    color: white;
}

canvas {
    max-width: 100%;
    height: auto;
    transition: all 0.4s ease;
}

.logbutton {
    margin-right: 80px;
}

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

body.dark-mode .theme-switch {
    background-color: var(--primary);
    justify-content: flex-end;
}

body.dark-mode .theme-switch::before {
    opacity: 0;
}

body.dark-mode .theme-switch::after {
    opacity: 1;
}

/* 🌙 Dark Mode Styling */
body.dark-mode {
    background-color: var(--bg-dark);
    color: var(--text-dark);
}

body.dark-mode .card,
body.dark-mode .welcome {
    background: var(--card-dark);
    color: var(--text-dark);
}

body.dark-mode th {
    background-color: #00acc1;
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


        
    </style>
</head>
<body>

<nav>
    <div>
        <a href="index.php"><i class="fas fa-home"></i> Home</a>
        <a href="addstock.php"><i class="fas fa-plus-circle"></i> Add Stock</a>
        <a href="portfolio.php"><i class="fas fa-list"></i> Portfolio</a>
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
                    <th>Purchase Price</th>
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
                    <td><?= number_format($stock['buy_price'], 2) ?></td>
                    <td><?= number_format($total, 2) ?></td>
                    <td style="color: <?= $individualProfitLoss >= 0 ? 'green' : 'red' ?>;">
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
    const ctx = document.getElementById('stockChart').getContext('2d');
    const stockLabels = <?= json_encode(array_column($stocks, 'stock_name')) ?>;
    const stockData = <?= json_encode(array_map(function($s) {
        return $s['stock_quantity'] * $s['stock_price'];
    }, $stocks)) ?>;

    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: stockLabels,
            datasets: [{
                data: stockData,
                backgroundColor: [
                    '#00bcd4', '#ff6384', '#36a2eb', '#ffcd56', '#4bc0c0',
                    '#9966ff', '#f56991', '#91f5a9', '#d091f5', '#f5e291'
                ],
                borderColor: '#fff',
                borderWidth: 2,
                hoverOffset: 20
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#333',
                        font: { size: 14 }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
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

    function toggleTheme() {
    document.body.classList.toggle('dark-mode');
    const isDark = document.body.classList.contains('dark-mode');

    // Update chart text color
    stockChart.options.plugins.legend.labels.color = isDark ? '#f5f5f5' : '#333';
    stockChart.options.plugins.tooltip.backgroundColor = isDark ? '#2c2c3e' : 'rgba(0,0,0,0.8)';
    stockChart.options.plugins.tooltip.titleColor = isDark ? '#fff' : '#fff';
    stockChart.options.plugins.tooltip.bodyColor = isDark ? '#ddd' : '#fff';
    stockChart.update();
}

// Optional: remember theme toggle state
window.addEventListener('DOMContentLoaded', () => {
    const isDark = localStorage.getItem('darkMode') === 'true';
    if (isDark) {
        document.body.classList.add('dark-mode');
        document.getElementById('themeToggle').checked = true;
    }

    document.getElementById('themeToggle').addEventListener('change', () => {
        const isDarkNow = document.body.classList.contains('dark-mode');
        localStorage.setItem('darkMode', !isDarkNow);
    });
});

</script>

</body>
</html>
