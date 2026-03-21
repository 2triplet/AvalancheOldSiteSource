<?php
session_start();
require_once 'config.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$user = currentUser();
$error = '';
$success = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $email = trim($_POST['email'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    
    if (empty($email)) {
        $error = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $user_data = $stmt->fetch();
        
        if (password_verify($current_password, $user_data['password_hash'])) {
            $stmt = $db->prepare("UPDATE users SET email = ? WHERE id = ?");
            $stmt->execute([$email, $user['id']]);
            $success = "Profile updated successfully!";
            $user = currentUser();
        } else {
            $error = "Current password is incorrect.";
        }
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } else {
        $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $user_data = $stmt->fetch();
        
        if (password_verify($current_password, $user_data['password_hash'])) {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$password_hash, $user['id']]);
            $success = "Password changed successfully!";
        } else {
            $error = "Current password is incorrect.";
        }
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enable_2fa'])) {
    $code = trim($_POST['2fa_code'] ?? '');
    $secret = $_SESSION['2fa_secret'] ?? '';
    
    if (empty($code) || empty($secret)) {
        $error = "Invalid 2FA setup.";
    } else {
        // ✅ Increased window to 2 for time tolerance
        if (verifyTOTP($secret, $code, 2)) {
            $stmt = $db->prepare("UPDATE users SET two_factor_secret = ?, two_factor_enabled = 1 WHERE id = ?");
            $stmt->execute([$secret, $user['id']]);
            unset($_SESSION['2fa_secret']);
            $success = "2FA enabled successfully!";
            $user = currentUser();
        } else {
            $error = "Invalid 2FA code. Please try again.";
        }
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['disable_2fa'])) {
    $code = trim($_POST['2fa_code'] ?? '');
    
    if (!empty($user['two_factor_secret']) && verifyTOTP($user['two_factor_secret'], $code, 2)) {
        $stmt = $db->prepare("UPDATE users SET two_factor_secret = NULL, two_factor_enabled = 0 WHERE id = ?");
        $stmt->execute([$user['id']]);
        $success = "2FA disabled successfully!";
        $user = currentUser();
    } else {
        $error = "Invalid 2FA code.";
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup_2fa'])) {
    $secret = generateTOTPSecret();
    $_SESSION['2fa_secret'] = $secret;
}


function generateTOTPSecret() {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $secret = '';
    for ($i = 0; $i < 32; $i++) {
        $secret .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $secret;
}

function base32_decode($secret) {
    $charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $secret = strtoupper(str_replace('=', '', $secret));
    $binary = '';
    
    for ($i = 0; $i < strlen($secret); $i++) {
        $pos = strpos($charset, $secret[$i]);
        if ($pos === false) return false;
        $binary .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
    }
    
    $result = '';
    for ($i = 0; $i + 7 < strlen($binary); $i += 8) {
        $result .= chr(bindec(substr($binary, $i, 8)));
    }
    return $result;
}

function verifyTOTP($secret, $code, $window = 1) {
    if (strlen($code) !== 6 || !ctype_digit($code)) {
        return false;
    }
    
    $secret_binary = base32_decode($secret);
    if ($secret_binary === false || empty($secret_binary)) {
        return false;
    }
    
    $time_step = 30;
    $current_time = floor(time() / $time_step);
    
    for ($i = -$window; $i <= $window; $i++) {
        $time = $current_time + $i;
        $hmac = hash_hmac('sha1', pack('N*', 0, $time), $secret_binary, true);
        $offset = ord(substr($hmac, -1)) & 0x0F;
        $hashpart = substr($hmac, $offset, 4);
        $value = unpack('N', $hashpart)[1] & 0x7FFFFFFF;
        $generated_code = str_pad($value % 1000000, 6, '0', STR_PAD_LEFT);
        
        if (hash_equals($generated_code, $code)) {
            return true;
        }
    }
    
    return false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Settings - Avalanche</title>
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
            float: left;
        }
        .site-header h1 a {
            color: white;
            text-decoration: none;
        }
        .site-header nav {
            float: left;
            margin-left: 30px;
        }
        .site-header nav a {
            color: white;
            text-decoration: none;
            margin-right: 20px;
        }
        .site-header nav a:hover {
            text-decoration: underline;
        }
        .site-header .nav-right {
            float: right;
        }
        .site-header .nav-right a {
            color: white;
            text-decoration: none;
            margin-left: 15px;
        }
        .site-header::after {
            content: "";
            display: table;
            clear: both;
        }
        .beta-notice {
            background: #6b8e3a;
            color: white;
            text-align: center;
            padding: 8px;
            font-size: 14px;
            clear: both;
        }
        .beta-notice a {
            color: white;
            text-decoration: underline;
        }
        .settings-container {
            max-width: 800px;
            margin: 30px auto;
            background: #f8f8f8;
            border: 1px solid #ccc;
        }
        .settings-section {
            padding: 25px;
            border-bottom: 1px solid #ddd;
        }
        .settings-section:last-child {
            border-bottom: none;
        }
        .settings-section h2 {
            margin-bottom: 15px;
            color: #333;
            font-size: 20px;
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
        .form-group input:disabled {
            background: #f0f0f0;
            color: #666;
        }
        .btn-primary {
            padding: 12px 25px;
            background: #6b8e3a;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
        }
        .btn-primary:hover {
            background: #5a7a30;
        }
        .btn-danger {
            padding: 12px 25px;
            background: #d32f2f;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
        }
        .btn-danger:hover {
            background: #b71c1c;
        }
        .error {
            background: #ffebee;
            color: #d32f2f;
            padding: 10px;
            border-radius: 3px;
            margin-bottom: 15px;
        }
        .success {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 10px;
            border-radius: 3px;
            margin-bottom: 15px;
        }
        .two-factor-status {
            padding: 15px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 3px;
            margin-bottom: 15px;
        }
        .two-factor-status.enabled {
            border-left: 4px solid #4caf50;
        }
        .two-factor-status.disabled {
            border-left: 4px solid #f44336;
        }
        .qr-code {
            text-align: center;
            margin: 20px 0;
        }
        .qr-code img {
            border: 1px solid #ddd;
            padding: 10px;
            background: white;
        }
        .secret-key {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 3px;
            font-family: monospace;
            text-align: center;
            margin: 15px 0;
            word-break: break-all;
        }
        #footer {
            text-align: center;
            padding: 20px;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="site-header">
        <h1><a href="index.php">Avalanche</a></h1>
        <nav>
            <a href="index.php">Home</a>
            <a href="games.php">Games</a>
            <a href="catalog.php">Catalog</a>
            <a href="avatar.php">Avatar</a>
            <a href="people.php">People</a>
            <a href="forum.php">Forum</a>
            <a href="create.php">Create</a>
            <a href="settings.php">Settings</a>
        </nav>
        <div class="nav-right">
            <span>Welcome, <?= htmlspecialchars($user['username']) ?>!</span>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="beta-notice">
        the beta site is here! join the discord: <a href="https://discord.gg/KTHb5Ztn2n">https://discord.gg/KTHb5Ztn2n</a> →
    </div>
    
    <div class="settings-container">
        <div class="settings-section">
            <h2>Profile Settings</h2>
            <?php if ($error && isset($_POST['update_profile'])): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success && isset($_POST['update_profile'])): ?>
                <div class="success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" value="<?= htmlspecialchars($user['username']) ?>" disabled>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" required>
                </div>
                <button type="submit" name="update_profile" class="btn-primary">Update Profile</button>
            </form>
        </div>
        
        <div class="settings-section">
            <h2>Change Password</h2>
            <?php if ($error && isset($_POST['change_password'])): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success && isset($_POST['change_password'])): ?>
                <div class="success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" required minlength="6">
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" required minlength="6">
                </div>
                <button type="submit" name="change_password" class="btn-primary">Change Password</button>
            </form>
        </div>
        
        <div class="settings-section">
            <h2>Two-Factor Authentication</h2>
            <?php if ($error && (isset($_POST['enable_2fa']) || isset($_POST['disable_2fa']))): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success && (isset($_POST['enable_2fa']) || isset($_POST['disable_2fa']))): ?>
                <div class="success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <?php if (!empty($user['two_factor_enabled'])): ?>
                <div class="two-factor-status enabled">
                    <strong>2FA is Enabled</strong>
                    <p style="margin-top: 10px; color: #666;">Your account is protected with two-factor authentication.</p>
                </div>
                <form method="post">
                    <div class="form-group">
                        <label>Enter 2FA Code to Disable</label>
                        <input type="text" name="2fa_code" placeholder="123456" maxlength="6" required>
                    </div>
                    <button type="submit" name="disable_2fa" class="btn-danger">Disable 2FA</button>
                </form>
            <?php else: ?>
                <div class="two-factor-status disabled">
                    <strong>2FA is Disabled</strong>
                    <p style="margin-top: 10px; color: #666;">Enable 2FA to add an extra layer of security to your account.</p>
                </div>
                
                <?php if (!empty($_SESSION['2fa_secret'])): ?>
                    <div class="qr-code">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= urlencode('otpauth://totp/Avalanche:' . $user['username'] . '?secret=' . $_SESSION['2fa_secret'] . '&issuer=Avalanche') ?>" alt="2FA QR Code">
                    </div>
                    <div class="secret-key">
                        <strong>Secret Key:</strong><br>
                        <?= htmlspecialchars($_SESSION['2fa_secret']) ?>
                    </div>
                    <p style="text-align: center; color: #666; margin-bottom: 15px;">
                        Scan this QR code with Google Authenticator, Authy, or similar app.
                    </p>
                    <form method="post">
                        <div class="form-group">
                            <label>Enter 2FA Code from App</label>
                            <input type="text" name="2fa_code" placeholder="123456" maxlength="6" required>
                        </div>
                        <button type="submit" name="enable_2fa" class="btn-primary">Enable 2FA</button>
                    </form>
                <?php else: ?>
                    <form method="post">
                        <button type="submit" name="setup_2fa" class="btn-primary">Setup 2FA</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <div id="footer">
        © <?= date("Y") ?> Avalanche. triple_t was here
    </div>
</body>
</html>