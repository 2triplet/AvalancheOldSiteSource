<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['2fa_user_id'])) {
    header("Location: login.php");
    exit;
}

$error = '';
$user_id = $_SESSION['2fa_user_id'];

$stmt = $db->prepare("SELECT username, two_factor_secret FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user || !$user['two_factor_secret']) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['2fa_code'] ?? '');
    
    // ✅ Increased window to 2 for time tolerance
    if (verifyTOTP($user['two_factor_secret'], $code, 2)) {
        $_SESSION['user_id'] = $user_id;
        unset($_SESSION['2fa_user_id']);
        $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user_id]);
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid 2FA code. Please try again.";
    }
}

// ✅ FIXED TOTP Functions (same as settings.php)
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
    <link rel="shortcut icon" type="image/x-icon" href="/assets/images/favicon.ico">
    <title>2FA Verification - Avalanche</title>
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
        .container {
            max-width: 400px;
            margin: 100px auto;
            background: #f8f8f8;
            border: 1px solid #ccc;
            padding: 30px;
        }
        .container h2 {
            text-align: center;
            margin-bottom: 10px;
        }
        .container > p {
            text-align: center;
            color: #666;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 3px;
            font-size: 14px;
            text-align: center;
            letter-spacing: 5px;
        }
        .btn-verify {
            width: 100%;
            padding: 12px;
            background: #6b8e3a;
            color: white;
            border: none;
            border-radius: 3px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }
        .btn-verify:hover {
            background: #5a7a30;
        }
        .error {
            background: #ffebee;
            color: #d32f2f;
            padding: 10px;
            border-radius: 3px;
            margin-bottom: 15px;
            text-align: center;
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
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
    
    <div class="container">
        <h2>🔐 Two-Factor Authentication</h2>
        <p>Enter the 6-digit code from your authenticator app.</p>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <input type="text" name="2fa_code" placeholder="000000" maxlength="6" required autofocus>
            </div>
            <button type="submit" class="btn-verify">Verify</button>
        </form>
        
        <div class="back-link">
            <a href="login.php">← Back to Log In</a>
        </div>
    </div>
</body>
</html>