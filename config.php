<?php
// config.php - Database and Telegram Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'telebirr_pro');

// Telegram Bot Configuration (Get from @BotFather)
define('BOT_TOKEN', 'YOUR_BOT_TOKEN_HERE'); // Replace with your bot token
define('CHAT_ID', 'YOUR_CHAT_ID_HERE'); // Replace with your chat ID

define('SITE_NAME', 'Telebirr Pro');
define('SITE_URL', 'http://localhost/telebirr-pro/');
define('SESSION_TIMEOUT', 3600);

if (session_status() === PHP_SESSION_NONE) session_start();
date_default_timezone_set('Africa/Addis_Ababa');
?>
