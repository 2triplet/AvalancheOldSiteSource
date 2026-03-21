<?php
session_start();
require_once 'config.php';

$error = '';
$success = '';
$reset_link_display = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    if (empty($username) || empty($email)) {
        $error = "Please enter both username and email.";
    } else {

        $stmt = $db->prepare("SELECT id, email FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && $user['email'] === $email) {
            
            $reset_token = bin2hex(random_bytes(32));
            $reset_expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $stmt = $db->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
            $stmt->execute([$reset_token, $reset_expires, $user['id']]);
            
            
            $reset_link = "https://alanbloxxr.xyz/reset_password.php?token=" . $reset_token;
            
            
            $subject = "Password Reset - Avalanche";
            $message = "Hello " . $username . ",\n\n";
            $message .= "Click the following link to reset your password:\n";
            $message .= $reset_link . "\n\n";
            $message .= "This link will expire in 1 hour.\n\n";
            $message .= "If you didn't request this, please ignore this email.";
            $headers = "From: emailhere@gmail.com\r\n";
            $headers .= "Reply-To: emailhere@gmail.com\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();

            if (mail($email, $subject, $message, $headers)) {
                $success = "Password reset link sent to your email!";
            } else {
                
                $success = "Email failed to send. Here's your reset link:";
                $reset_link_display = $reset_link;
            }
        } else {
            
            $success = "If that username and email match our records, you'll receive a password reset link.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/x-icon" href="/assets/images/favicon.ico">
    <title>Forgot Password - Avalanche</title>
    <link rel="shortcut icon" type="image/x-icon" href="/assets/images/favicon.ico">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            background: #e0e0e0;
        }
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
        .site-header a:hover {
            text-decoration: underline;
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
            max-width: 500px;
            margin: 50px auto;
            background: #f8f8f8;
            border: 1px solid #ccc;
            padding: 30px;
        }
        .container h2 {
            text-align: center;
            margin-bottom: 10px;
            color: #000;
        }
        .container > p {
            text-align: center;
            color: #666;
            margin-bottom: 20px;
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
        .btn-primary {
            width: 100%;
            padding: 12px;
            background: #6b8e3a;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            margin-top: 10px;
        }
        .btn-primary:hover {
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
        .success {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 10px;
            border-radius: 3px;
            margin-bottom: 15px;
            text-align: center;
        }
        .reset-link {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 3px;
            word-break: break-all;
            font-size: 12px;
            margin-top: 10px;
            border: 1px solid #ddd;
        }
        .reset-link a {
            color: #0066cc;
            text-decoration: underline;
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
        <h2>Forgot Password?</h2>
        <p>Enter your username and email to reset your password.</p>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
            <?php if ($reset_link_display): ?>
                <div class="reset-link">
                    <strong>Reset Link:</strong><br>
                    <a href="<?= htmlspecialchars($reset_link_display) ?>"><?= htmlspecialchars($reset_link_display) ?></a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <button type="submit" class="btn-primary">Send Reset Link</button>
        </form>
        
        <div class="back-link">
            <a href="login.php">← Back to Log In</a>
        </div>
    </div>
</body>
</html>