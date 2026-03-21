<link rel="icon" type="image/x-icon" href="/favicon.ico">
<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header("Location: signup.php");
    exit;
}

$user = currentUser();
if (!$user || !is_array($user)) {
    session_destroy();
    header("Location: signup.php");
    exit;
}


$robux = (int)($user['robux'] ?? 0);
$last_claim_str = $user['last_robux_claim'] ?? null;
$daily_message = '';


$bonus_awarded = false;
if ($last_claim_str) {
    $last_claim = new DateTime($last_claim_str);
    $now = new DateTime();
    $interval = $now->diff($last_claim);

    if ($interval->days >= 1 || ($interval->days == 0 && $interval->h >= 24)) {
        $new_robux = $robux + 50;

        $stmt = $db->prepare("UPDATE users SET robux = ?, last_robux_claim = NOW() WHERE id = ?");
        $stmt->execute([$new_robux, $user['id']]);

        $user = currentUser();
        $robux = $new_robux;
        $daily_message = "You received your daily 50 Robux bonus!";
        $bonus_awarded = true;
    }
}


$stmt = $db->prepare("
    SELECT u.id, u.username
    FROM friends f
    JOIN users u ON f.friend_id = u.id
    WHERE f.user_id = ? AND f.accepted = 1
    ORDER BY u.username ASC
    LIMIT 20
");
$stmt->execute([$user['id']]);
$friends = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars(SITE_NAME ?? 'Avalanche') ?> - Home</title>
  <link rel="stylesheet" href="<?= htmlspecialchars(ASSETS_URL ?? '/assets/') ?>css/style.css?v=1">
  <style>
    .robux-badge {
        background: #ffd700;
        color: #333;
        padding: 6px 14px;
        border-radius: 20px;
        font-weight: bold;
        font-size: 18px;
        margin-left: 15px;
        vertical-align: middle;
        display: inline-block;
        box-shadow: 0 1px 4px rgba(0,0,0,0.2);
    }
    .daily-bonus {
        background: #e8ffe8;
        color: #006600;
        font-weight: bold;
        text-align: center;
        margin: 20px 0;
        padding: 12px;
        border-radius: 6px;
        border: 1px solid #c8e6c9;
        font-size: 18px;
        display: <?= $bonus_awarded ? 'block' : 'none' ?>;
    }
    .friends-section {
        margin: 30px 0;
        padding: 15px 0;
        border-top: 1px solid #ddd;
    }
    .friends-list {
        display: flex;
        overflow-x: auto;
        gap: 20px;
        padding-bottom: 10px;
        scrollbar-width: thin;
    }
    .friend-item {
        text-align: center;
        min-width: 90px;
        flex-shrink: 0;
    }
    .friend-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #ddd;
        cursor: pointer;
        transition: transform 0.2s;
    }
    .friend-avatar:hover {
        transform: scale(1.08);
    }
    .friend-name {
        margin-top: 8px;
        font-size: 13px;
        color: #333;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .no-friends {
        text-align: center;
        color: #777;
        font-size: 16px;
        margin: 20px 0;
    }
  </style>
</head>
<body>

<div id="header">
  <div class="inner">
    <h1><a href="/" style="color:white;"><?= htmlspecialchars(SITE_NAME ?? 'Avalanche') ?></a></h1>
    <nav>
      <a href="/">Home</a>
      <a href="/games.php">Games</a>
      <a href="/catalog.php">Catalog</a>
      <a href="avatar.php">Avatar</a>
      <a href="/people.php">People</a>
      <a href="forum.php">Forum</a>
      <a href="help.php">Help</a>
      <a href="#">Download</a>
      <span style="margin-left:30px; float:right;">
        Welcome, <?= htmlspecialchars($user['username'] ?? 'User') ?>!
        <a href="logout.php" style="color:#ff4444; margin-left:20px;">Logout</a>
      </span>
    </nav>
  </div>
</div>

<div id="subnav">
  the beta site is here! join the dc: <a href="https://discord.gg/HRPd2Bq4tW" style="color:white; text-decoration:underline;">https://discord.gg/HRPd2Bq4tW</a>
</div>

<div id="container">
  <?php if ($daily_message): ?>
    <div class="daily-bonus">
      <?= htmlspecialchars($daily_message) ?>
    </div>
  <?php endif; ?>

  <h2>
    Welcome back, <?= htmlspecialchars($user['username']) ?>!
    <span class="robux-badge"><?= number_format($robux) ?> Robux</span>
  </h2>

  <p>Home page – you are logged in.</p>

  <div class="friends-section">
    <h3>Your Friends</h3>
    <?php if (empty($friends)): ?>
      <p class="no-friends">No friends yet.</p>
    <?php else: ?>
      <div class="friends-list">
        <?php foreach ($friends as $friend): ?>
          <div class="friend-item">
            <a href="profile.php?id=<?= $friend['id'] ?>">
              <img src="assets/images/default_headshot.png" alt="<?= htmlspecialchars($friend['username']) ?>" class="friend-avatar">
            </a>
            <div class="friend-name"><?= htmlspecialchars($friend['username']) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div id="footer">
    © <?= date("Y") ?> <?= htmlspecialchars(SITE_NAME ?? 'Avalanche') ?>. triple_t was here
  </div>
</div>

</body>
</html>