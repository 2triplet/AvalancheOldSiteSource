<link rel="icon" type="image/x-icon" href="/favicon.ico">
<?php
require_once 'config.php';

$user = currentUser();
$stmt = $db->prepare("SELECT * FROM bans WHERE user_id = ? AND active = 1 LIMIT 1");
$stmt->execute([$user['id']]);
$ban = $stmt->fetch();
if ($ban) {
    header("Location: ban.php");
    exit;
}

if (!isLoggedIn()) {
    header("Location: signup.php");
    exit;
}

$current_user = currentUser();
$message = '';


$stmt = $db->prepare("
    SELECT c.id, c.name, c.type, c.image_url, uc.equipped
    FROM user_cosmetics uc
    JOIN cosmetics c ON uc.cosmetic_id = c.id
    WHERE uc.user_id = ?
    ORDER BY c.type, c.name
");
$stmt->execute([$current_user['id']]);
$owned_items = $stmt->fetchAll();


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['equip_item_id'])) {
    $item_id = (int)$_POST['equip_item_id'];

    
    $stmt = $db->prepare("
        UPDATE user_cosmetics uc
        JOIN cosmetics c ON uc.cosmetic_id = c.id
        SET uc.equipped = 0
        WHERE uc.user_id = ? AND c.type = (SELECT type FROM cosmetics WHERE id = ?)
    ");
    $stmt->execute([$current_user['id'], $item_id]);

    // Equip this one
    $stmt = $db->prepare("UPDATE user_cosmetics SET equipped = 1 WHERE user_id = ? AND cosmetic_id = ?");
    $stmt->execute([$current_user['id'], $item_id]);

    $message = "Item equipped!";



}
}


$userId = $current_user['id'];   


}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <link rel="shortcut icon" type="image/x-icon" href="/assets/images/favicon.ico">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars(SITE_NAME) ?> - Avatar</title>
  <link rel="stylesheet" href="<?= htmlspecialchars(ASSETS_URL) ?>css/style.css?v=1">
  <style>
    .avatar-preview { text-align:center; margin:40px 0; }
    .full-avatar { max-width:420px; border:4px solid #6b8e23; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.2); }
    .inventory-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(180px, 1fr)); gap:25px; margin-top:40px; }
    .item-card { background:#fff; padding:15px; border:1px solid #ccc; border-radius:8px; text-align:center; }
    .item-card img { width:140px; height:140px; object-fit:contain; margin-bottom:10px; }
    .equipped { background:#e8ffe8; border-color:#4CAF50; }
    .btn-equip, .btn-unequip {
        padding:8px 20px;
        border:none;
        border-radius:4px;
        cursor:pointer;
        font-weight:bold;
        margin-top:8px;
    }
    .btn-equip   { background:#6b8e23; color:white; }
    .btn-unequip { background:#d32f2f; color:white; }
    .message { color:#006600; font-weight:bold; text-align:center; margin:20px 0; font-size:18px; }
  </style>
</head>
<body>

<div id="header">
  <div class="inner">
    <h1><a href="/" style="color:white;"><?= htmlspecialchars(SITE_NAME) ?></a></h1>
    <nav>
      <a href="/">Home</a>
      <a href="games.php">Games</a>
      <a href="catalog.php">Catalog</a>
      <a href="people.php">People</a>
      <a href="forum.php">Forum</a>
      <a href="create.php">Create</a>
      <a href="avatar.php">Avatar</a>
      <a href="#">Download</a>
      <a href="settings.php">Settings</a>
      <span style="margin-left:30px;">Welcome, <?= htmlspecialchars($current_user['username']) ?>!</span>
      <link rel="shortcut icon" type="image/png" href="/assets/images/favicon.ico">
      <a href="logout.php" style="color:#ff4444;">Logout</a>
    </nav>
  </div>
</div>

<div id="subnav">
  the beta site is here! join the dc: <a href="https://discord.gg/HRPd2Bq4tW" style="color:white; text-decoration:underline;">https://discord.gg/HRPd2Bq4tW</a>
</div>

<div id="container">
  <h2>Customize Avatar</h2>

  <?php if ($message): ?>
    <div class="message"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <div class="avatar-preview">
    <h3>Your Current Avatar</h3>
    <img src="<?= htmlspecialchars($current_user['avatar_full'] ?? 'assets/images/default_full.png') ?>" alt="Full Avatar" class="full-avatar">
  </div>

  <h3>Your Items</h3>
  <?php if (empty($owned_items)): ?>
    <p>You don't own any items yet. Visit the <a href="catalog.php">Catalog</a> to buy some!</p>
  <?php else: ?>
    <div class="inventory-grid">
      <?php foreach ($owned_items as $item): ?>
        <div class="item-card <?= $item['equipped'] ? 'equipped' : '' ?>">
          <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
          <p><?= htmlspecialchars($item['name']) ?> (<?= ucfirst($item['type']) ?>)</p>
          <form method="post">
            <?php if ($item['equipped']): ?>
              <input type="hidden" name="unequip_item_id" value="<?= $item['id'] ?>">
              <button type="submit" class="btn-unequip">Unequip</button>
            <?php else: ?>
              <input type="hidden" name="equip_item_id" value="<?= $item['id'] ?>">
              <button type="submit" class="btn-equip">Equip</button>
            <?php endif; ?>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div id="footer">
    © <?= date("Y") ?> <?= htmlspecialchars(SITE_NAME) ?>. triple_t was here
  </div>
</div>

</body>
</html>