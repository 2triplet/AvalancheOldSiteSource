<?php
session_start();
require_once 'config.php';

$error = '';
$success = '';
$token = $_GET['token'] ?? '';
$user = null;

// Validate token
if (!empty($token)) {
    $stmt = $db->prepare("SELECT id, username FROM users WHERE reset_token = ? AND reset_expires > NOW() LIMIT 1");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
} else {
    $error = "Invalid reset link.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        $stmt->execute([$password_hash, $user['id']]);
        $success = "Password reset successful! You can now log in.";
        $user = null;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password - <?= htmlspecialchars(SITE_NAME ?? 'Avalanche') ?></title>
    <link rel="shortcut icon" type="image/x-icon" href="/assets/images/favicon.ico">
    <link rel="stylesheet" href="<?= htmlspecialchars(ASSETS_URL ?? '/assets/') ?>css/style.css?v=1">
    <style>
        body { background: #e0e0e0; font-family: Arial, sans-serif; margin: 0; }
        .container { max-width: 400px; margin: 100px auto; background: white; padding: 30px; border: 1px solid #ccc; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .container h2 { margin-top: 0; text-align: center; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn-primary { width: 100%; padding: 12px; background: #0066cc; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold; }
        .btn-primary:hover { background: #0052a3; }
        .error { color: #d32f2f; background: #ffebee; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .success { color: #2e7d32; background: #e8f5e9; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .back-link { text-align: center; margin-top: 20px; }
        .back-link a { color: #0066cc; text-decoration: none; }
        #subnav { background: #0066cc; color: white; padding: 8px 20px; text-align: center; }
        #subnav a { color: white; text-decoration: underline; }
    </style>
</head>
<body>
<div id="subnav">
    the beta site is here! join the dc: <a href="https://discord.gg/HRPd2Bq4tW">https://discord.gg/HRPd2Bq4tW</a>
</div>

<div class="container">
    <h2>🔑 Reset Password</h2>
    
    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="success"><?= htmlspecialchars($success) ?></div>
        <div class="back-link"><a href="login.php">Go to Log In</a></div>
    <?php elseif ($user): ?>
        <p style="text-align:center; color:#666;">Hello, <?= htmlspecialchars($user['username']) ?>! Enter your new password below.</p>
        <form method="post">
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" required minlength="6">
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" required minlength="6">
            </div>
            <button type="submit" class="btn-primary">Reset Password</button>
        </form>
    <?php endif; ?>
    
    <?php if (!$success): ?>
        <div class="back-link"><a href="forgot_password.php">← Request New Reset Link</a></div>
    <?php endif; ?>
</div>
</body>
</html>