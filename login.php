<?php
session_start();
require_once 'config.php';


if (isLoggedIn()) {
    if (isBanned()) {
        header("Location: banned.php");
        exit;
    }
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';
    
    if (empty($username) || empty($pass)) {
        $error = "Please enter both username and password.";
    } else {

        $stmt = $db->prepare("SELECT id, password_hash, two_factor_enabled, is_banned, ban_reason, ban_expires, unban_reason, unbanned_by FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($pass, $user['password_hash'])) {

            if (!empty($user['is_banned'])) {
               
                if ($user['ban_expires'] && strtotime($user['ban_expires']) < time()) {
                    // Ban expired - auto unban
                    $db->prepare("UPDATE users SET is_banned = 0, ban_reason = NULL, ban_expires = NULL WHERE id = ?")->execute([$user['id']]);
                } elseif (empty($user['unbanned_by'])) {
                    
                    $_SESSION['user_id'] = $user['id'];
                    header("Location: banned.php");
                    exit;
                }
            }
            
            
            if ($user) {
               
                if (!empty($user['two_factor_enabled'])) {
                    $_SESSION['2fa_user_id'] = $user['id'];
                    header("Location: verify_2fa.php");
                    exit;
                }
                
                $_SESSION['user_id'] = $user['id'];
                $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
                session_regenerate_id(true);
                header("Location: index.php");
                exit;
            }
        } else {
            $error = "Incorrect username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log In - Avalanche</title>
    <link rel="shortcut icon" type="image/x-icon" href="/assets/images/favicon.ico">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #e0e0e0; }
        .site-header {
            background: #3d5c8a;
            color: white;
            padding: 10px 20px;
        }
        .site-header h1 {
            font-size: 24px;
            font-weight: bold;
        }
        .site-header a {
            color: white;
            text-decoration: none;
        }
        .beta-notice {
            background: #6b8e3a;
            color: white;
            text-align: center;
            padding: 8px;
            font-size: 14px;
        }
        .beta-notice a {
            color: white;
            text-decoration: underline;
        }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            background: #f8f8f8;
            border: 1px solid #ccc;
            padding: 30px;
        }
        .login-container h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #000;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 3px;
            font-size: 14px;
        }
        .btn-login {
            width: 100%;
            padding: 12px;
            background: #6b8e3a;
            color: white;
            border: none;
            border-radius: 3px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
        }
        .btn-login:hover { background: #5a7a30; }
        .error {
            background: #ffebee;
            color: #d32f2f;
            padding: 10px;
            border-radius: 3px;
            margin-bottom: 15px;
            text-align: center;
        }
        .forgot-password {
            text-align: center;
            margin-top: 15px;
        }
        .forgot-password a {
            color: #0066cc;
            text-decoration: underline;
            font-size: 14px;
        }
        .signup-link {
            text-align: center;
            margin-top: 20px;
            color: #333;
            font-size: 14px;
        }
        .signup-link a {
            color: #0066cc;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="site-header">
        <h1><a href="index.php">Avalanche</a></h1>
    </div>
    <div class="beta-notice">
        the beta site is here! join the discord: <a href="https://discord.gg/KTHb5Ztn2n">https://discord.gg/KTHb5Ztn2n</a> →
    </div>
    
    <div class="login-container">
        <h2>× Log In</h2>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn-login">Log In</button>
        </form>
        
        <div class="forgot-password">
            <a href="forgot_password.php">Forgot Password?</a>
        </div>
        
        <div class="signup-link">
            Don't have an account? <a href="signup.php">Sign up</a>
        </div>
    </div>
</body>
</html>