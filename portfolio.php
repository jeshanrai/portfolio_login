<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Database connection (Procedural Style)
$conn = mysqli_connect('localhost', 'root', '', 'portfolio');
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Get the user ID from the session
$user_id = $_SESSION['user_id'];

// Fetch stocks for the logged-in user using a prepared statement
$sql = "SELECT * FROM stocks WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id); // "i" denotes integer type
$stmt->execute();
$result = $stmt->get_result();

// Initialize portfolio totals
$totalPortfolioValue = 0;
$totalWACC = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Portfolio Tracker</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
      /* Add your CSS styles here */
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
        /* Remaining CSS styles are unchanged */
          /* Add your CSS styles here */
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

body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f4;
    margin: 0;
    padding: 0;
}

.container {
    width: 80%;
    margin: 0 auto;
    padding: 20px;
    background-color: #fff;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    margin-top:80px;
}


.profit-loss {
    margin-bottom: 20px;
    font-size: 1.2em;
    padding: 10px;
    text-align: center;
}


.input-section {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.input-section input {
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 5px;
    width: 200px;
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

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

table, th, td {
    border: 1px solid #ddd;
}

th, td {
    padding: 10px;
    text-align: left;
}

th {
    background-color: #f2f2f2;
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
            background-color: #dff0d8;
            border-color: #d6e9c6;
        }

        .loss {
            color: red;
            background-color: #f2dede;
            border-color: #ebccd1;
        }
    </style>
</head>
<body>
<nav class="navbar">
    <div class="nav-left">
        <a href="index.php"><i class="fas fa-home"></i> HOME</a>
        <a href="addstock.php"><i class="fas fa-plus-circle"></i> Add Stock</a>
        <a href="portfolio.php"><i class="fas fa-list"></i> Portfolio</a>
    </div>
    <div class="nav-right">
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</nav>
<div class="container">
    <div class="profit-loss" id="profitLoss">
        Profit/Loss: Rs<span id="profitLossValue">
        <?php
        // Calculate and display profit/loss
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $stockPrice = $row['stock_price'];
                $stockQuantity = $row['stock_quantity'];
                $buyPrice = $row['buy_price'];

                $totalValue = $stockPrice * $stockQuantity;
                $WACC = $buyPrice * $stockQuantity;

                $totalPortfolioValue += $totalValue; // Accumulate total portfolio value
                $totalWACC += $WACC; // Accumulate total WACC
            }
        }

        $profitLoss = $totalPortfolioValue - $totalWACC; // Calculate profit or loss
        echo number_format($profitLoss, 2); // Display profit/loss
        ?>
        </span>
    </div>
    <main>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Stock Name</th>
                    <th>Stock Price</th>
                    <th>Stock Quantity</th>
                    <th>Buy Price</th>
                    <th>Total Value</th>
                    <th>WACC</th>
                    <th>Gain/Loss</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="stockTableBody">
            <?php
            $result->data_seek(0); // Reset the result set pointer
            if ($result->num_rows > 0) {
                $rowCount = 0;
                while ($row = $result->fetch_assoc()) {
                    $rowCount++;
                    $stockName = $row['stock_name'];
                    $stockPrice = $row['stock_price'];
                    $stockQuantity = $row['stock_quantity'];
                    $buyPrice = $row['buy_price'];

                    $totalValue = $stockPrice * $stockQuantity;
                    $WACC = $buyPrice * $stockQuantity;
                    $gainLoss = $totalValue - $WACC;
                    $gainLossPercentage = ($WACC > 0) ? round(($gainLoss / $WACC) * 100, 2) : 0;

                    echo "<tr>
                        <td>$rowCount</td>
                        <td>$stockName</td>
                        <td>Rs" . number_format($stockPrice, 2) . "</td>
                        <td>$stockQuantity</td>
                        <td>Rs" . number_format($buyPrice, 2) . "</td>
                        <td>Rs" . number_format($totalValue, 2) . "</td>
                        <td>Rs" . number_format($WACC, 2) . "</td>
                        <td class='gain-loss " . ($gainLoss >= 0 ? "green" : "red") . "'>
                            " . number_format($gainLoss, 2) . " (" . $gainLossPercentage . "%)
                        </td>
                      <td>
                                <button class='editStockButton'>Edit</button>
                                <button class='deleteStockButton'>Delete</button>
                              </td>;
            
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='9'>No stocks found</td></tr>";
            }
            ?>
            </tbody>
        </table>
        <div class="portfolio-value">
            Total Portfolio Value: Rs<span id="portfolioValue">
            <?php echo number_format($totalPortfolioValue, 2); ?>
            </span>
        </div>
    </main>
</div>



</body>
</html>
