<?php

if (http_response_code() === 404 || $_SERVER['REDIRECT_STATUS'] === '404') {
    include '404.php';
    exit;
}
    



if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


define('SITE_NAME', 'Avalanche');
define('ASSETS_URL', '/assets/');


$db_host = 'host_here';
$db_name = 'name_here';
$db_user = 'user_here';
$db_pass = 'password_here';


try {
    $db = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}


function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}


function currentUser() {
    global $db;
    
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        return null;
    }
    
    try {
        $stmt = $db->prepare("
            SELECT id, username, email, robux, two_factor_secret, two_factor_enabled, 
                   is_banned, ban_reason, ban_expires, warning_count, 
                   unban_reason, unbanned_by, unbanned_at
            FROM users
            WHERE id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

//unban poopoo
function isBanned() {
    if (!isLoggedIn()) return false;
    
    $user = currentUser();
    if (!$user) return false;
    
   
    if (empty($user['is_banned'])) return false;
    
    
    if ($user['ban_expires'] && strtotime($user['ban_expires']) < time()) {
        return false; // ban expired 
    }
    
  
    if (!empty($user['unbanned_by'])) {
        return false; // was unbanned
    }
    
    return true; 
}

r
function isAdmin() {
    if (!isLoggedIn()) return false;
    
    $owner_ids = [1, 2];
    $admin_ids = [3, 4, 5];
    
    return in_array($_SESSION['user_id'], $owner_ids) || in_array($_SESSION['user_id'], $admin_ids);
}


function requireNotBanned() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
    
    if (isBanned()) {
        header("Location: banned.php");
        exit;
    }
}


function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

n
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}
?>
