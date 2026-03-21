<?php
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>404 - Page Not Found | <?= htmlspecialchars(SITE_NAME ?? 'Avalanche') ?></title>
  <link rel="icon" type="image/x-icon" href="/favicon.ico">
  <link rel="stylesheet" href="<?= htmlspecialchars(ASSETS_URL ?? '/assets/') ?>css/style.css?v=1">
  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: Arial, sans-serif;
      background: #0d1117;
      color: #e6edf3;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
    #header {
      background: #161b22;
      color: white;
      padding: 15px 20px;
      border-bottom: 1px solid #30363d;
    }
    #header .inner {
      max-width: 1200px;
      margin: 0 auto;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    h1 a {
      color: #58a6ff;
      text-decoration: none;
      font-size: 24px;
    }
    .container {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      padding: 20px;
    }
    .error-box {
      max-width: 600px;
      background: #161b22;
      border: 1px solid #30363d;
      border-radius: 12px;
      padding: 40px 30px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.4);
    }
    .error-title {
      font-size: 120px;
      margin: 0;
      color: #f85149;
      line-height: 1;
      font-weight: bold;
    }
    .error-subtitle {
      font-size: 28px;
      margin: 20px 0 30px;
      color: #8b949e;
    }
    .error-message {
      font-size: 18px;
      margin-bottom: 40px;
      color: #c9d1d9;
    }
    .btn-home {
      display: inline-block;
      background: #238636;
      color: white;
      padding: 14px 32px;
      border-radius: 6px;
      text-decoration: none;
      font-size: 18px;
      font-weight: bold;
      transition: background 0.2s;
    }
    .btn-home:hover {
      background: #2ea043;
    }
    #footer {
      background: #161b22;
      color: #8b949e;
      text-align: center;
      padding: 20px;
      border-top: 1px solid #30363d;
      font-size: 14px;
    }
  </style>
</head>
<body>

<div id="header">
  <div class="inner">
    <h1><a href="/"><?= htmlspecialchars(SITE_NAME ?? 'Avalanche') ?></a></h1>
  </div>
</div>

<div class="container">
  <div class="error-box">
    <div class="error-title">404</div>
    <div class="error-subtitle">Page Not Found</div>
    <p class="error-message">
      The page you are looking for might have been removed, had its name changed,<br>
      or is temporarily unavailable. idfk what to do just go to the homepage son
    </p>
    <a href="/" class="btn-home">Return to Home</a>
  </div>
</div>

<div id="footer">
  © <?= date("Y") ?> <?= htmlspecialchars(SITE_NAME ?? 'Avalanche') ?>. triple_t was here
</div>

</body>
</html>