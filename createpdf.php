<?php
require 'libs/dompdf/autoload.inc.php';

use Dompdf\Dompdf;

session_start();
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access");
}

$conn = mysqli_connect('localhost', 'root', '', 'portfolio');
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['name']; // assuming you've set 'name' in session

// Fetch NEPSE API Data
$apiUrl = "http://localhost:3000/today-price";
$response = @file_get_contents($apiUrl);
if ($response === FALSE) {
    die("Failed to fetch stock data");
}

$apiData = json_decode($response, true);
$ltpList = [];

if (isset($apiData['data'])) {
    foreach ($apiData['data'] as $item) {
        $code = strtoupper(trim($item['company']['code']));
        $close = $item['price']['close'];
        $ltpList[$code] = $close;
    }
}

// Fetch user's stocks
$sql = "SELECT * FROM stocks WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$totalPortfolioValue = 0;
$totalWACC = 0;

$rows = [];

while ($row = $result->fetch_assoc()) {
    $stockName = strtoupper(trim($row['stock_name']));
    $quantity = $row['stock_quantity'];
    $buyPrice = $row['buy_price'];
    $buyDate = date('Y-m-d', strtotime($row['created_at']));
    $ltp = $ltpList[$stockName] ?? 0;

    $totalValue = $ltp * $quantity;
    $wacc = $buyPrice * $quantity;
    $gainLoss = $totalValue - $wacc;
    $gainLossPercent = ($wacc > 0) ? round(($gainLoss / $wacc) * 100, 2) : 0;

    $totalPortfolioValue += $totalValue;
    $totalWACC += $wacc;

    $rows[] = [
        'stockName' => $stockName,
        'ltp' => $ltp,
        'quantity' => $quantity,
        'buyPrice' => $buyPrice,
        'totalValue' => $totalValue,
        'wacc' => $wacc,
        'gainLoss' => $gainLoss,
        'gainLossPercent' => $gainLossPercent,
        'buyDate' => $buyDate
    ];
}

$totalProfitLoss = $totalPortfolioValue - $totalWACC;
$profitLossColor = $totalProfitLoss >= 0 ? 'green' : 'red';

// Start HTML
$html = '
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h2 { text-align: center; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: center; }
        th { background-color: #f0f0f0; }
        .header-row td { border: none; font-weight: bold; padding: 5px 10px; }
    </style>
    <h2>Portfolio Summary</h2>
    <table>
        <tr class="header-row">
            <td style="width: 33%; text-align: left;">Username: ' . htmlspecialchars($username) . '</td>
            <td style="width: 34%; text-align: center; color:' . $profitLossColor . ';">
                Total Profit/Loss: Rs ' . number_format($totalProfitLoss, 2) . '
            </td>
            <td style="width: 33%; text-align: right;">Generated on: ' . date('Y-m-d H:i:s') . '</td>
        </tr>
    </table>

    <table>
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
            </tr>
        </thead>
        <tbody>';

$count = 1;
foreach ($rows as $r) {
    $gainLossClass = $r['gainLoss'] >= 0 ? 'color:green;' : 'color:red;';
    $html .= '<tr>
        <td>' . $count++ . '</td>
        <td>' . htmlspecialchars($r['stockName']) . '</td>
        <td>Rs ' . number_format($r['ltp'], 2) . '</td>
        <td>' . $r['quantity'] . '</td>
        <td>Rs ' . number_format($r['buyPrice'], 2) . '</td>
        <td>Rs ' . number_format($r['totalValue'], 2) . '</td>
        <td>Rs ' . number_format($r['wacc'], 2) . '</td>
        <td style="' . $gainLossClass . '">
            ' . number_format($r['gainLoss'], 2) . ' (' . $r['gainLossPercent'] . '%)
        </td>
        <td>' . $r['buyDate'] . '</td>
    </tr>';
}

$html .= '</tbody></table>';

// Generate PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream("portfolio_summary.pdf", array("Attachment" => false)); // view in browser
exit;
