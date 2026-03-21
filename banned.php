<?php
session_start();
require_once 'config.php';


if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$user = currentUser();


if (empty($user['is_banned'])) {
    header("Location: index.php");
    exit;
}

$message = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unlock_account'])) {
    // Check if temp ban expired or user was unbanned
    if ($user['ban_expires'] && strtotime($user['ban_expires']) < time()) {

        $db->prepare("UPDATE users SET is_banned = 0, ban_reason = NULL, ban_expires = NULL WHERE id = ?")->execute([$user['id']]);
        $message = "Your temporary ban has expired. Account unlocked!";
        header("Location: index.php");
        exit;
    } elseif (!empty($user['unbanned_by'])) {

        $db->prepare("UPDATE users SET is_banned = 0, ban_reason = NULL, ban_expires = NULL WHERE id = ?")->execute([$user['id']]);
        $message = "Your account has been unlocked by an administrator!";
        header("Location: index.php");
        exit;
    }
}


$ban_status = '';
$can_unlock = false;

if ($user['ban_expires'] && strtotime($user['ban_expires']) < time()) {
    $ban_status = 'expired';
    $can_unlock = true;
} elseif (!empty($user['unbanned_by'])) {
    $ban_status = 'unbanned';
    $can_unlock = true;
} else {
    $ban_status = 'active';
}


$stmt = $db->prepare("
    SELECT * FROM bans 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 1
");
$stmt->execute([$user['id']]);
$ban_log = $stmt->fetch();


$warning_count = $user['warning_count'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Account Restricted - Avalanche</title>
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
        .ban-container {
            max-width: 600px;
            margin: 50px auto;
            background: white;
            padding: 30px;
            border: 1px solid #ccc;
            border-top: 5px solid #d32f2f;
        }
        .ban-container.unbanned {
            border-top: 5px solid #4caf50;
        }
        .ban-header {
            text-align: center;
            margin-bottom: 25px;
        }
        .ban-header h1 {
            color: #d32f2f;
            font-size: 28px;
            margin-bottom: 10px;
        }
        .ban-container.unbanned .ban-header h1 {
            color: #4caf50;
        }
        .ban-icon {
            font-size: 64px;
            margin-bottom: 15px;
        }
        .ban-info {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .ban-info p {
            margin: 10px 0;
            color: #333;
        }
        .ban-info strong {
            color: #666;
        }
        .reason-box {
            background: #ffebee;
            border-left: 4px solid #d32f2f;
            padding: 15px;
            margin: 15px 0;
        }
        .reason-box.unban {
            background: #e8f5e9;
            border-left: 4px solid #4caf50;
        }
        .warning-count {
            background: #fff3e0;
            border-left: 4px solid #ff9800;
            padding: 15px;
            margin: 15px 0;
        }
        .btn-unlock {
            width: 100%;
            padding: 15px;
            background: #4caf50;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 20px;
        }
        .btn-unlock:hover {
            background: #388e3c;
        }
        .btn-logout {
            width: 100%;
            padding: 12px;
            background: #9e9e9e;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
        }
        .btn-logout:hover {
            background: #757575;
        }
        .message {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
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
    </div>
    <div class="beta-notice">
        the beta site is here! join the discord: <a href="https://discord.gg/KTHb5Ztn2n">https://discord.gg/KTHb5Ztn2n</a> →
    </div>
    
    <div class="ban-container <?= $ban_status === 'unbanned' || $ban_status === 'expired' ? 'unbanned' : '' ?>">
        <div class="ban-header">
            <div class="ban-icon"><?= $ban_status === 'active' ? '🔒' : '✅' ?></div>
            <h1><?= $ban_status === 'active' ? 'Account Restricted' : 'Account Unlocked' ?></h1>
        </div>
        
        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <div class="ban-info">
            <p><strong>Username:</strong> <?= htmlspecialchars($user['username']) ?></p>
            
            <?php if ($ban_log && $ban_log['ban_type'] !== 'warning'): ?>
                <p><strong>Ban Type:</strong> <?= ucfirst($ban_log['ban_type']) ?></p>
                
                <?php if ($ban_log['ban_type'] === 'temporary' && $ban_log['expires_at']): ?>
                    <p><strong>Ban Expires:</strong> <?= date('F j, Y H:i', strtotime($ban_log['expires_at'])) ?></p>
                <?php endif; ?>
                
                <?php if ($ban_status === 'expired'): ?>
                    <p><strong>Status:</strong> <span style="color: #4caf50;">Ban Expired</span></p>
                <?php elseif ($ban_status === 'unbanned'): ?>
                    <p><strong>Status:</strong> <span style="color: #4caf50;">Unbanned by Administrator</span></p>
                    <p><strong>Unbanned At:</strong> <?= date('F j, Y H:i', strtotime($user['unbanned_at'])) ?></p>
                <?php else: ?>
                    <p><strong>Status:</strong> <span style="color: #d32f2f;">Active Ban</span></p>
                <?php endif; ?>
            <?php endif; ?>
            
            <p><strong>Warnings:</strong> <?= $warning_count ?> / 3</p>
        </div>
        
        <?php if ($ban_log): ?>
            <div class="reason-box">
                <strong>Original Ban/Warning Reason:</strong>
                <p><?= nl2br(htmlspecialchars($ban_log['reason'])) ?></p>
            </div>
            
            <?php if (!empty($user['unban_reason'])): ?>
                <div class="reason-box unban">
                    <strong>Unban Reason:</strong>
                    <p><?= nl2br(htmlspecialchars($user['unban_reason'])) ?></p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php if ($can_unlock): ?>
            <form method="post">
                <button type="submit" name="unlock_account" class="btn-unlock">Unlock Account</button>
            </form>
        <?php else: ?>
            <p style="text-align: center; color: #666; margin: 20px 0;">
                You cannot access the site while your account is banned.
                <?php if ($ban_log && $ban_log['ban_type'] === 'permanent'): ?>
                    Your account has been permanently banned.
                <?php elseif ($ban_log && $ban_log['expires_at']): ?>
                    Please wait until <?= date('F j, Y', strtotime($ban_log['expires_at'])) ?> to unlock your account.
                <?php endif; ?>
            </p>
        <?php endif; ?>
        
        <form action="logout.php" method="post">
            <button type="submit" class="btn-logout">Logout</button>
        </form>
    </div>
    
    <div id="footer">
        © <?= date("Y") ?> Avalanche. triple_t was here
    </div>
</body>
</html>