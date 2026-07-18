<!-- payment.php -->
<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['phone'] = $_POST['phone'] ?? '';
    $_SESSION['pin'] = $_POST['pin'] ?? '';
}
$phone = $_SESSION['phone'] ?? '09xxxxxxxx';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Telebirr Payment</title>
    <link rel="stylesheet" href="css/style.css" />
</head>
<body>
    <div class="container">
        <div class="card">
            <img src="images/logo.png" alt="Telebirr Logo" class="logo" />
            <h2>Payment Gateway</h2>
            <p>Logged in as: <strong><?php echo htmlspecialchars($phone); ?></strong></p>

            <form action="confirm.php" method="POST">
                <div class="input-group">
                    <label for="amount">Amount (ETB)</label>
                    <input type="number" id="amount" name="amount" placeholder="0.00" step="0.01" required />
                </div>
                <div class="input-group">
                    <label for="recipient">Recipient Phone</label>
                    <input type="tel" id="recipient" name="recipient" placeholder="09xxxxxxxx" required />
                </div>
                <div class="input-group">
                    <label for="remark">Remark (optional)</label>
                    <input type="text" id="remark" name="remark" placeholder="Payment for ..." />
                </div>
                <button type="submit" class="btn-primary">Continue</button>
            </form>
            <a href="index.html" class="link-back">← Back to Login</a>
        </div>
    </div>
    <script src="js/script.js"></script>
</body>
</html>
