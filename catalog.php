<link rel="icon" type="image/x-icon" href="/favicon.ico">
<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header("Location: signup.php");
    exit;
}

$current_user = currentUser();
$message = '';


$stmt = $db->prepare("SELECT * FROM cosmetics ORDER BY rarity DESC, name ASC");
$stmt->execute();
$items = $stmt->fetchAll();


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy_item_id'])) {
    $item_id = (int)$_POST['buy_item_id'];


    $check = $db->prepare("SELECT id FROM user_cosmetics WHERE user_id = ? AND cosmetic_id = ?");
    $check->execute([$current_user['id'], $item_id]);
    if ($check->fetch()) {
        $message = "You already own this item!";
    } else {

        $stmt = $db->prepare("INSERT INTO user_cosmetics (user_id, cosmetic_id) VALUES (?, ?)");
        $stmt->execute([$current_user['id'], $item_id]);

        $message = "Item purchased! Redirecting to customize avatar...";

        // Redirect after 2 seconds
        header("Refresh: 2; URL=avatar.php");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="shortcut icon" type="image/x-icon" href="/assets/images/favicon.ico">
  <title><?= htmlspecialchars(SITE_NAME) ?> - Catalog</title>
  <link rel="stylesheet" href="<?= htmlspecialchars(ASSETS_URL) ?>css/style.css?v=1">
  <style>
    .catalog-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 25px;
        margin-top: 30px;
    }
    .item-card {
        background: #ffffff;
        padding: 20px;
        border: 1px solid #ccc;
        border-radius: 8px;
        text-align: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .item-card img {
        width: 160px;
        height: 160px;
        object-fit: contain;
        margin-bottom: 15px;
    }
    .item-card h3 {
        margin: 10px 0;
        font-size: 18px;
    }
    .rarity-common  { color: #777; }
    .rarity-uncommon{ color: #4CAF50; }
    .rarity-rare    { color: #2196F3; font-weight: bold; }
    .rarity-legendary{ color: #FF9800; font-weight: bold; }
    .btn-buy {
        background: #6b8e23;
        color: white;
        border: none;
        padding: 10px 24px;
        border-radius: 4px;
        cursor: pointer;
        font-weight: bold;
        margin-top: 10px;
    }
    .message {
        color: #006600;
        font-weight: bold;
        text-align: center;
        margin: 20px 0;
        font-size: 18px;
    }
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
      <a href="logout.php" style="color:#ff4444;">Logout</a>
    </nav>
  </div>
</div>

<div id="subnav">
  the beta site is here! join the dc: <a href="https://discord.gg/HRPd2Bq4tW" style="color:white; text-decoration:underline;">https://discord.gg/HRPd2Bq4tW</a>
</div>

<div id="container">
  <h2>Catalog</h2>

  <?php if ($message): ?>
    <div class="message"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <div class="catalog-grid">
    <?php foreach ($items as $item): ?>
      <div class="item-card">
        <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
        <h3><?= htmlspecialchars($item['name']) ?></h3>
        <p>Type: <?= ucfirst($item['type']) ?></p>
        <p class="rarity-<?= $item['rarity'] ?>">Rarity: <?= ucfirst($item['rarity']) ?></p>
        <form method="post">
          <input type="hidden" name="buy_item_id" value="<?= $item['id'] ?>">
          <button type="submit" class="btn-buy">Buy</button>
        </form>
      </div>
    <?php endforeach; ?>
  </div>

  <div id="footer">
    © <?= date("Y") ?> <?= htmlspecialchars(SITE_NAME) ?>. triple_t was here
  </div>
</div>

</body>
</html>