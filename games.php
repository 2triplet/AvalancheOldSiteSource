<link rel="icon" type="image/x-icon" href="/favicon.ico"> <?php
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

// Pagination
$games_per_page = 20;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $games_per_page;

// Fetch total games count
$stmt = $db->prepare("SELECT COUNT(*) as total FROM games");
$stmt->execute();
$total_games = $stmt->fetch()['total'];
$total_pages = ceil($total_games / $games_per_page);

// Fetch games for current page
$stmt = $db->prepare("
    SELECT g.id, g.title, g.thumbnail, g.playing, g.visits, u.username as creator
    FROM games g
    JOIN users u ON g.creator_id = u.id
    ORDER BY g.created_at DESC
    LIMIT ?, ?
");
$stmt->execute([$offset, $games_per_page]);
$games = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="shortcut icon" type="image/x-icon" href="/assets/images/favicon.ico">
  <title><?= htmlspecialchars(SITE_NAME ?? 'Avalanche') ?> - Games</title>
  <link rel="stylesheet" href="<?= htmlspecialchars(ASSETS_URL ?? '/assets/') ?>css/style.css?v=1">
  <style>
    .games-container {
        max-width: 1400px;
        margin: 40px auto;
        padding: 0 20px;
    }
    .games-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 35px;
        margin-top: 40px;
    }
    .game-card {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0,0,0,0.12);
        border: 1px solid #e0e0e0;
        cursor: pointer;
        text-decoration: none;
        color: inherit;
        height: 420px;
        display: flex;
        flex-direction: column;
        transition: box-shadow 0.2s;
    }
    .game-card:hover {
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    }
    .thumbnail-container {
        width: 100%;
        height: 180px;
        background: #111;
        overflow: hidden;
    }
    .thumbnail {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .game-info {
        padding: 20px;
        flex-grow: 1;
    }
    .game-title {
        margin: 0 0 12px;
        font-size: 20px;
        font-weight: bold;
        color: #222;
    }
    .game-stats {
        font-size: 15px;
        color: #555;
        margin-bottom: 8px;
    }
    .game-creator {
        font-size: 14px;
        color: #777;
    }
    .pagination {
        text-align: center;
        margin: 50px 0;
        font-size: 17px;
    }
    .pagination a {
        margin: 0 12px;
        padding: 10px 20px;
        background: #6b8e23;
        color: white;
        text-decoration: none;
        border-radius: 8px;
    }
    .pagination a.disabled {
        background: #ccc;
        cursor: not-allowed;
    }
    .no-games {
        text-align: center;
        color: #777;
        font-size: 22px;
        margin: 100px 0;
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
      <a href="#">Download</a>
      <a href="settings.php">Settings</a>
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

<div id="container" class="games-container">
  <h2>Games</h2>
  <p>Discover and play community-made places!</p>

  <div class="games-grid">
    <?php if (empty($games)): ?>
      <div class="no-games">
        No games have been created yet.
      </div>
    <?php else: ?>
      <?php foreach ($games as $game): ?>
        <a href="game.php?id=<?= $game['id'] ?>" class="game-card">
          <div class="thumbnail-container">
            <img src="<?= htmlspecialchars($game['thumbnail']) ?>" alt="<?= htmlspecialchars($game['title']) ?>" class="thumbnail">
          </div>
          <div class="game-info">
            <h3 class="game-title"><?= htmlspecialchars($game['title']) ?></h3>
            <div class="game-stats">
              <?= number_format($game['playing']) ?> playing • <?= number_format($game['visits']) ?> visits
            </div>
            <div class="game-creator">
              Created by <?= htmlspecialchars($game['creator']) ?>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Pagination -->
  <?php if ($total_pages > 1): ?>
    <div class="pagination">
      <?php if ($page > 1): ?>
        <a href="?page=<?= $page - 1 ?>">Previous</a>
      <?php else: ?>
        <span class="disabled">Previous</span>
      <?php endif; ?>

      <span>Page <?= $page ?> of <?= $total_pages ?></span>

      <?php if ($page < $total_pages): ?>
        <a href="?page=<?= $page + 1 ?>">Next</a>
      <?php else: ?>
        <span class="disabled">Next</span>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <div id="footer">
    © <?= date("Y") ?> <?= htmlspecialchars(SITE_NAME ?? 'Avalanche') ?>. triple_t was here
  </div>
</div>

</body>
</html>