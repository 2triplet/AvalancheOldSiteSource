<?php
session_start();
require_once 'config.php';

// If already logged in, redirect to index.php
if (isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$errors = [];
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';

    if (empty($username) || empty($email) || empty($pass)) {
        $errors[] = "All fields are required.";
    } else {
        if (strlen($username) < 3 || strlen($username) > 40) $errors[] = "Username must be 3–40 characters.";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email address.";
        if (strlen($pass) < 6) $errors[] = "Password must be at least 6 characters.";
        if ($pass !== $pass2) $errors[] = "Passwords do not match.";

        if (empty($errors)) {
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $errors[] = "Username or email already taken.";
            } else {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
                $stmt->execute([$username, $email, $hash]);

                $_SESSION['user_id'] = $db->lastInsertId();
                session_regenerate_id(true);
                header("Location: index.php");
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign Up - Avalanche</title>
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
        .signup-container {
            max-width: 400px;
            margin: 50px auto;
            background: #f8f8f8;
            border: 1px solid #ccc;
            padding: 30px;
        }
        .signup-container h2 {
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
        .btn-signup {
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
        .btn-signup:hover {
            background: #5a7a30;
        }
        .error {
            background: #ffebee;
            color: #d32f2f;
            padding: 10px;
            border-radius: 3px;
            margin-bottom: 15px;
        }
        .error ul {
            margin: 0;
            padding-left: 20px;
        }
        .error li {
            margin: 5px 0;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #333;
            font-size: 14px;
        }
        .login-link a {
            color: #0066cc;
            text-decoration: underline;
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
    <!-- Site Header -->
    <div class="site-header">
        <h1><a href="index.php">Avalanche</a></h1>
    </div>
    
    <!-- Beta Notice -->
    <div class="beta-notice">
        the beta site is here! join the discord: <a href="https://discord.gg/KTHb5Ztn2n">https://discord.gg/KTHb5Ztn2n</a> →
    </div>
    
    <!-- Signup Form -->
    <div class="signup-container">
        <h2>Create Account</h2>
        
        <?php if (!empty($errors)): ?>
            <div class="error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?= htmlspecialchars($username) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label for="password2">Repeat Password</label>
                <input type="password" id="password2" name="password2" required>
            </div>
            
            <button type="submit" class="btn-signup">Sign Up</button>
        </form>
        
        <div class="login-link">
            Already have an account? <a href="login.php">Log in</a>
        </div>
    </div>
    
    <div id="footer">
        © <?= date("Y") ?> Avalanche. triple_t was here
    </div>
</body>
</html>