<!-- admin/dashboard.php -->
<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
// In a real app, fetch transactions from database
$transactions = [
    ['id' => 'TX001', 'from' => '0912345678', 'to' => '0987654321', 'amount' => '150.00', 'status' => 'Completed', 'date' => '2026-07-18 10:30'],
    ['id' => 'TX002', 'from' => '0922334455', 'to' => '0911223344', 'amount' => '75.50', 'status' => 'Pending', 'date' => '2026-07-18 09:15'],
    ['id' => 'TX003', 'from' => '0933445566', 'to' => '0955667788', 'amount' => '200.00', 'status' => 'Completed', 'date' => '2026-07-17 22:00'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../css/style.css" />
</head>
<body>
    <div class="container">
        <div class="card">
            <h2>📊 Dashboard</h2>
            <p>Welcome, Admin!</p>
            <a href="login.php?logout=1" class="link-back" style="float:right;">Logout</a>

            <table class="transaction-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $tx): ?>
                    <tr>
                        <td><?php echo $tx['id']; ?></td>
                        <td><?php echo $tx['from']; ?></td>
                        <td><?php echo $tx['to']; ?></td>
                        <td>ETB <?php echo $tx['amount']; ?></td>
                        <td><span class="status <?php echo strtolower($tx['status']); ?>"><?php echo $tx['status']; ?></span></td>
                        <td><?php echo $tx['date']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
