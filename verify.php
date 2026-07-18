<!-- verify.php -->
<?php
session_start();
$phone = $_SESSION['phone'] ?? '09xxxxxxxx';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>OTP Verification</title>
    <link rel="stylesheet" href="css/style.css" />
</head>
<body>
    <div class="container">
        <div class="card">
            <img src="images/logo.png" alt="Telebirr Logo" class="logo" />
            <h2>OTP Verification</h2>
            <p>Enter the 6-digit code sent to <strong><?php echo htmlspecialchars($phone); ?></strong></p>

            <form action="complete.php" method="POST">
                <div class="input-group">
                    <label for="otp">OTP Code</label>
                    <input type="text" id="otp" name="otp" placeholder="123456" maxlength="6" required />
                </div>
                <button type="submit" class="btn-primary">Verify</button>
            </form>
            <a href="confirm.php" class="link-back">← Back</a>
        </div>
    </div>
    <script src="js/script.js"></script>
</body>
</html>
