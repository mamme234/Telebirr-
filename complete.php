<?php
// complete.php
session_start();

// Check if user is logged in
if (!isset($_SESSION['phone']) || !isset($_SESSION['pin'])) {
    header('Location: index.html');
    exit;
}

// Check if OTP was verified (in a real app, verify OTP from database)
// For demo, we'll check if we have transaction data
if (!isset($_SESSION['transaction_data']) && !isset($_SESSION['amount'])) {
    header('Location: index.html');
    exit;
}

// Get transaction data
$transaction_id = $_SESSION['transaction_id'] ?? 'TX' . strtoupper(bin2hex(random_bytes(6)));
$amount = $_SESSION['amount'] ?? '0.00';
$recipient = $_SESSION['recipient'] ?? '';
$phone = $_SESSION['phone'] ?? '';
$remark = $_SESSION['remark'] ?? '';
$timestamp = $_SESSION['timestamp'] ?? date('Y-m-d H:i:s');

// In a real application, you would:
// 1. Update transaction status in database to 'completed'
// 2. Send confirmation SMS/email
// 3. Generate receipt
// 4. Update user balances

// For demo, we'll store in session
if (!isset($_SESSION['completed_transactions'])) {
    $_SESSION['completed_transactions'] = [];
}

$transaction_data = [
    'transaction_id' => $transaction_id,
    'from' => $phone,
    'to' => $recipient,
    'amount' => $amount,
    'remark' => $remark,
    'status' => 'completed',
    'timestamp' => $timestamp,
    'completed_at' => date('Y-m-d H:i:s')
];

// Add to completed transactions
array_push($_SESSION['completed_transactions'], $transaction_data);

// Generate a reference number
$reference_number = 'REF' . date('Ymd') . strtoupper(substr($transaction_id, -6));

// Generate a random 6-digit confirmation code
$confirmation_code = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

// Clear sensitive session data but keep for display
// Uncomment in production:
// unset($_SESSION['pin']);
// unset($_SESSION['transaction_data']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Transaction Complete - Telebirr</title>
    <link rel="stylesheet" href="css/style.css" />
    <style>
        .success-container {
            text-align: center;
        }
        .success-icon {
            font-size: 4rem;
            color: #22c55e;
            background: #dcfce7;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            animation: scaleIn 0.5s ease-out;
        }
        @keyframes scaleIn {
            0% { transform: scale(0); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        .success-title {
            color: #1e293b;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .success-subtitle {
            color: #64748b;
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
        }
        .receipt {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            text-align: left;
            border: 2px dashed #e2e8f0;
        }
        .receipt-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e2e8f0;
            margin-bottom: 1rem;
        }
        .receipt-header .receipt-title {
            font-weight: 700;
            color: #1e293b;
            font-size: 1.1rem;
        }
        .receipt-header .receipt-id {
            color: #64748b;
            font-size: 0.8rem;
            font-family: monospace;
        }
        .receipt-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            font-size: 0.9rem;
        }
        .receipt-row .label {
            color: #64748b;
        }
        .receipt-row .value {
            color: #1e293b;
            font-weight: 600;
        }
        .receipt-row .value.amount {
            color: #1a73e8;
            font-size: 1.4rem;
            font-weight: 700;
        }
        .receipt-divider {
            border-top: 1px solid #e2e8f0;
            margin: 0.5rem 0;
        }
        .confirmation-code {
            background: #f1f5f9;
            padding: 0.8rem;
            border-radius: 8px;
            text-align: center;
            margin: 1rem 0;
            font-family: monospace;
            font-size: 1.2rem;
            letter-spacing: 4px;
            color: #1a73e8;
            font-weight: 700;
        }
        .btn-success {
            width: 100%;
            padding: 0.85rem;
            background: #22c55e;
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .btn-success:hover {
            background: #16a34a;
        }
        .btn-outline {
            width: 100%;
            padding: 0.85rem;
            background: transparent;
            color: #1a73e8;
            border: 2px solid #1a73e8;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            margin-top: 0.5rem;
        }
        .btn-outline:hover {
            background: #1a73e8;
            color: #fff;
        }
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-top: 1.5rem;
        }
        .print-hidden {
            display: none;
        }
        @media print {
            .btn-success, .btn-outline, .action-buttons, .no-print {
                display: none !important;
            }
            .card {
                box-shadow: none !important;
                border: 1px solid #e2e8f0;
            }
            .success-icon {
                animation: none !important;
            }
        }
        @media (max-width: 480px) {
            .receipt {
                padding: 1rem;
            }
            .receipt-row {
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="success-container">
                <!-- Success Animation -->
                <div class="success-icon">✓</div>
                
                <h2 class="success-title">Transaction Successful! 🎉</h2>
                <p class="success-subtitle">Your payment has been processed successfully</p>

                <!-- Confirmation Code -->
                <div class="confirmation-code">
                    Confirmation: <?php echo $confirmation_code; ?>
                </div>

                <!-- Receipt -->
                <div class="receipt" id="receipt">
                    <div class="receipt-header">
                        <span class="receipt-title">📄 Receipt</span>
                        <span class="receipt-id">#<?php echo htmlspecialchars($reference_number); ?></span>
                    </div>
                    
                    <div class="receipt-row">
                        <span class="label">Transaction ID</span>
                        <span class="value"><?php echo htmlspecialchars($transaction_id); ?></span>
                    </div>
                    
                    <div class="receipt-row">
                        <span class="label">From</span>
                        <span class="value"><?php echo htmlspecialchars($phone); ?></span>
                    </div>
                    
                    <div class="receipt-row">
                        <span class="label">To</span>
                        <span class="value"><?php echo htmlspecialchars($recipient); ?></span>
                    </div>
                    
                    <div class="receipt-divider"></div>
                    
                    <div class="receipt-row">
                        <span class="label">Amount</span>
                        <span class="value amount">ETB <?php echo number_format((float)$amount, 2); ?></span>
                    </div>
                    
                    <?php if (!empty($remark)): ?>
                    <div class="receipt-row">
                        <span class="label">Remark</span>
                        <span class="value"><?php echo htmlspecialchars($remark); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="receipt-divider"></div>
                    
                    <div class="receipt-row">
                        <span class="label">Date</span>
                        <span class="value"><?php echo date('F d, Y'); ?></span>
                    </div>
                    
                    <div class="receipt-row">
                        <span class="label">Time</span>
                        <span class="value"><?php echo date('h:i A'); ?></span>
                    </div>
                    
                    <div class="receipt-row">
                        <span class="label">Status</span>
                        <span class="value" style="color: #22c55e;">✅ Completed</span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <button onclick="window.print()" class="btn-outline">
                        🖨️ Print Receipt
                    </button>
                    <a href="index.html" class="btn-success">
                        🏠 Back to Home
                    </a>
                    <a href="payment.php" class="btn-outline" style="margin-top: 0;">
                        💰 New Transaction
                    </a>
                </div>

                <!-- Additional Info -->
                <p style="margin-top: 1.5rem; font-size: 0.75rem; color: #94a3b8;">
                    A confirmation SMS has been sent to your registered phone number.<br>
                    Transaction reference: <strong><?php echo htmlspecialchars($reference_number); ?></strong>
                </p>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Confetti effect (optional)
            function createConfetti() {
                const colors = ['#22c55e', '#1a73e8', '#f59e0b', '#ef4444', '#8b5cf6'];
                const container = document.querySelector('.card');
                
                for (let i = 0; i < 30; i++) {
                    const confetti = document.createElement('div');
                    confetti.style.cssText = `
                        position: fixed;
                        width: 8px;
                        height: 8px;
                        background: ${colors[Math.floor(Math.random() * colors.length)]};
                        left: ${Math.random() * 100}%;
                        top: -10px;
                        transform: rotate(${Math.random() * 360}deg);
                        border-radius: ${Math.random() > 0.5 ? '50%' : '2px'};
                        animation: fall ${Math.random() * 2 + 2}s linear forwards;
                        animation-delay: ${Math.random() * 0.5}s;
                        z-index: 9999;
                        pointer-events: none;
                    `;
                    document.body.appendChild(confetti);
                    
                    // Remove after animation
                    setTimeout(() => {
                        confetti.remove();
                    }, 3000);
                }
            }
            
            // Add keyframes for confetti
            const style = document.createElement('style');
            style.textContent = `
                @keyframes fall {
                    0% { transform: translateY(0) rotate(0deg); opacity: 1; }
                    100% { transform: translateY(100vh) rotate(720deg); opacity: 0; }
                }
            `;
            document.head.appendChild(style);
            
            // Trigger confetti on load
            setTimeout(createConfetti, 500);
            
            // Print receipt functionality
            window.printReceipt = function() {
                window.print();
            };
            
            // Auto-redirect after 60 seconds (optional)
            // setTimeout(() => {
            //     window.location.href = 'index.html';
            // }, 60000);
        });
    </script>
</body>
</html>
