<?php
require_once 'functions.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';
$phone = '';

// Handle Registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $phone = trim($_POST['phone'] ?? '');
    $pin = $_POST['pin'] ?? '';
    $name = trim($_POST['name'] ?? '');
    
    if (!preg_match('/^09[0-9]{8}$/', $phone)) {
        $error = 'Please enter a valid phone number (09xxxxxxxx)';
    } elseif (strlen($pin) !== 6 || !ctype_digit($pin)) {
        $error = 'PIN must be 6 digits';
    } elseif (empty($name)) {
        $error = 'Please enter your name';
    } elseif (getUserByPhone($phone)) {
        $error = 'Phone number already registered';
    } else {
        if (registerUser($phone, $pin, $name)) {
            $success = 'Registration successful! Please login.';
        } else {
            $error = 'Registration failed. Please try again.';
        }
    }
}

// Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $phone = trim($_POST['phone'] ?? '');
    $pin = $_POST['pin'] ?? '';
    
    if (loginUser($phone, $pin)) {
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Invalid phone number or PIN';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telebirr Pro - Smart Mobile Payment</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="landing-page">
        <nav class="navbar">
            <div class="nav-container">
                <div class="nav-logo">
                    <img src="images/logo.png" alt="Telebirr" class="logo-small">
                    <span>Telebirr Pro</span>
                </div>
                <div class="nav-links">
                    <a href="#features">Features</a>
                    <a href="#bonuses">Bonuses</a>
                    <a href="#about">About</a>
                </div>
            </div>
        </nav>

        <div class="hero-section">
            <div class="hero-content">
                <h1>Smart Mobile <span>Payment</span></h1>
                <p>Send money, pay bills, and claim bonuses instantly</p>
            </div>
            <div class="auth-container">
                <div class="auth-tabs">
                    <button class="tab-btn active" onclick="showTab('login')">Login</button>
                    <button class="tab-btn" onclick="showTab('register')">Register</button>
                </div>

                <?php if ($error): ?>
                    <div class="alert error"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert success"><?php echo $success; ?></div>
                <?php endif; ?>

                <!-- Login Form -->
                <form method="POST" class="auth-form" id="loginForm">
                    <input type="hidden" name="login" value="1">
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Phone Number</label>
                        <input type="tel" name="phone" placeholder="09xxxxxxxx" 
                               pattern="09[0-9]{8}" maxlength="10" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> PIN</label>
                        <div class="pin-input">
                            <input type="password" name="pin" placeholder="Enter 6-digit PIN" 
                                   maxlength="6" pattern="[0-9]{6}" required>
                            <button type="button" onclick="togglePin(this)"><i class="fas fa-eye"></i></button>
                        </div>
                    </div>
                    <button type="submit" class="btn-primary">Login</button>
                </form>

                <!-- Register Form -->
                <form method="POST" class="auth-form" id="registerForm" style="display:none;">
                    <input type="hidden" name="register" value="1">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Full Name</label>
                        <input type="text" name="name" placeholder="Your full name" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Phone Number</label>
                        <input type="tel" name="phone" placeholder="09xxxxxxxx" 
                               pattern="09[0-9]{8}" maxlength="10" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Create PIN</label>
                        <div class="pin-input">
                            <input type="password" name="pin" placeholder="6-digit PIN" 
                                   maxlength="6" pattern="[0-9]{6}" required>
                            <button type="button" onclick="togglePin(this)"><i class="fas fa-eye"></i></button>
                        </div>
                    </div>
                    <button type="submit" class="btn-primary">Create Account</button>
                </form>
            </div>
        </div>

        <section id="features" class="features">
            <h2>Why Telebirr Pro?</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <i class="fas fa-mobile-alt"></i>
                    <h3>Easy Payments</h3>
                    <p>Send and receive money instantly</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-gift"></i>
                    <h3>Daily Bonuses</h3>
                    <p>Claim exciting rewards every day</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-shield-alt"></i>
                    <h3>Secure</h3>
                    <p>Bank-grade security with PIN</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-bolt"></i>
                    <h3>Instant</h3>
                    <p>Money moves in seconds</p>
                </div>
            </div>
        </section>

        <footer class="footer">
            <p>&copy; 2026 Telebirr Pro. All rights reserved.</p>
        </footer>
    </div>

    <script>
        function showTab(tab) {
            document.querySelectorAll('.auth-form').forEach(f => f.style.display = 'none');
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            
            if (tab === 'login') {
                document.getElementById('loginForm').style.display = 'block';
                document.querySelector('.tab-btn:first-child').classList.add('active');
            } else {
                document.getElementById('registerForm').style.display = 'block';
                document.querySelector('.tab-btn:last-child').classList.add('active');
            }
        }

        function togglePin(btn) {
            const input = btn.parentElement.querySelector('input');
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }
    </script>
</body>
</html>
