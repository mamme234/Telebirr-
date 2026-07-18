<?php
// confirm.php
session_start();

// Check if user is logged in
if (!isset($_SESSION['phone']) || !isset($_SESSION['pin'])) {
    header('Location: index.html');
    exit;
}

// Process POST data from payment.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate amount
    if (empty($_POST['amount']) || $_POST['amount'] <= 0) {
        $_SESSION['error'] = 'Please enter a valid amount.';
        header('Location: payment.php');
        exit;
    }
    
    // Validate recipient
    if (empty($_POST['recipient']) || !preg_match('/^09[0-9]{8}$/', $_POST['recipient'])) {
        $_SESSION['error'] = 'Please enter a valid recipient phone number (09xxxxxxxx).';
        header('Location: payment.php');
        exit;
    }
    
    // Store payment data in session
    $_SESSION['amount'] = number_format((float)$_POST['amount'], 2, '.', '');
    $_SESSION['recipient'] = $_POST['recipient'];
    $_SESSION['remark'] = trim($_POST['remark'] ?? '');
    $_SESSION['transaction_id'] = 'TX' . strtoupper(bin2hex(random_bytes(6)));
    $_SESSION['timestamp'] = date('Y-m-d H:i:s');
    
    // In a real application, you would:
    // 1. Check if sender has sufficient balance
    // 2. Check if recipient exists
    // 3. Deduct amount from sender
    // 4. Add amount to recipient
    // 5. Store transaction in database
    
    // For demo, we'll store in session
    $_SESSION['transaction_data'] = [
        'transaction_id' => $_SESSION['transaction_id'],
        'from' => $_SESSION['phone'],
        'to' => $_SESSION['recipient'],
        'amount' => $_SESSION['amount'],
        'remark' => $_SESSION['remark'],
        'status' => 'pending',
        'timestamp' => $_SESSION['timestamp']
    ];
} else {
    // If accessed directly without POST, redirect to payment
    header('Location: payment.php');
    exit;
}

// Get data for display
$amount = $_SESSION['amount'] ?? '0.00';
$recipient = $_SESSION['recipient'] ?? '';
$remark = $_SESSION['remark'] ?? '';
$phone = $_SESSION['phone'] ?? '';
$transaction_id = $_SESSION['transaction_id'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Confirm Transaction - Telebirr</title>
    <link rel="stylesheet" href="css/style.css" />
    <style>
        .transaction-details {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            text-align: left;
        }
        .transaction-details .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.6rem 0;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.95rem;
        }
        .transaction-details .detail-row:last-child {
            border-bottom: none;
        }
        .transaction-details .label {
            color: #64748b;
            font-weight: 500;
        }
        .transaction-details .value {
            color: #1e293b;
            font-weight: 600;
            text-align: right;
            word-break: break-all;
        }
        .transaction-details .value.amount {
            color: #1a73e8;
            font-size: 1.2rem;
        }
        .btn-group {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        .btn-group .btn-primary {
            flex: 1;
        }
        .btn-secondary {
            flex: 1;
            padding: 0.85rem;
            background: #e2e8f0;
            color: #334155;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            text-decoration: none;
            text-align: center;
        }
        .btn-secondary:hover {
            background: #cbd5e1;
        }
        .warning-text {
            color: #ef4444;
            font-size: 0.8rem;
            margin-top: 0.5rem;
            padding: 0.5rem;
            background: #fee2e2;
            border-radius: 8px;
        }
        .security-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: #dcfce7;
            color: #16a34a;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }
        @media (max-width: 480px) {
            .btn-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <img src="images/logo.png" alt="Telebirr Logo" class="logo" />
            <h2>🔒 Confirm Transaction</h2>
            <p style="color: #64748b; font-size: 0.9rem;">Please verify the details before confirming</p>
            
            <div class="security-badge">
                <span>🔐</span> Secure Transaction
            </div>

            <div class="transaction-details">
                <div class="detail-row">
                    <span class="label">Transaction ID</span>
                    <span class="value"><?php echo htmlspecialchars($transaction_id); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">From</span>
                    <span class="value"><?php echo htmlspecialchars($phone); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">To</span>
                    <span class="value"><?php echo htmlspecialchars($recipient); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Amount</span>
                    <span class="value amount">ETB <?php echo number_format((float)$amount, 2); ?></span>
                </div>
                <?php if (!empty($remark)): ?>
                <div class="detail-row">
                    <span class="label">Remark</span>
                    <span class="value"><?php echo htmlspecialchars($remark); ?></span>
                </div>
                <?php endif; ?>
                <div class="detail-row">
                    <span class="label">Time</span>
                    <span class="value"><?php echo date('h:i A'); ?></span>
                </div>
            </div>

            <div class="warning-text">
                ⚠️ Please verify the recipient and amount carefully. Transactions cannot be reversed.
            </div>

            <form action="verify.php" method="POST" id="confirmForm">
                <input type="hidden" name="confirm" value="1" />
                <div class="btn-group">
                    <a href="payment.php" class="btn-secondary">← Edit</a>
                    <button type="submit" class="btn-primary" id="confirmBtn">Confirm &amp; Send OTP</button>
                </div>
            </form>
            
            <p style="margin-top: 1rem; font-size: 0.75rem; color: #94a3b8;">
                By confirming, you agree to the Telebirr Terms &amp; Conditions
            </p>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const confirmBtn = document.getElementById('confirmBtn');
            const form = document.getElementById('confirmForm');
            
            form.addEventListener('submit', function(e) {
                confirmBtn.textContent = 'Processing...';
                confirmBtn.disabled = true;
            });
            
            // Prevent double submission
            let submitted = false;
            form.addEventListener('submit', function(e) {
                if (submitted) {
                    e.preventDefault();
                    return;
                }
                submitted = true;
            });
        });
    </script>
</body>
</html>
