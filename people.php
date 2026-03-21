<link rel="icon" type="image/x-icon" href="/favicon.ico"><?php
require_once 'config.php';

if (!isLoggedIn()) {
    header("Location: signup.php");
    exit;
}

$current_user = currentUser();
$search_query = trim($_GET['q'] ?? '');
$users = [];

if ($search_query !== '') {
    $stmt = $db->prepare("
        SELECT id, username
        FROM users
        WHERE username LIKE ?
        AND id != ?
        ORDER BY username ASC
        LIMIT 50
    ");
    $stmt->execute(["%$search_query%", $current_user['id']]);
    $users = $stmt->fetchAll();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_friend_id'])) {
    $target_id = (int)$_POST['add_friend_id'];

    if ($target_id !== $current_user['id']) {
        try {
            $stmt = $db->prepare("
                INSERT IGNORE INTO friend_requests (sender_id, receiver_id, status)
                VALUES (?, ?, 'pending')
            ");
            $stmt->execute([$current_user['id'], $target_id]);
            $message = "Friend request sent!";
        } catch (Exception $e) {
            $message = "Error sending request.";
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
  <title><?= htmlspecialchars(SITE_NAME) ?> - People</title>
  <link rel="stylesheet" href="<?= htmlspecialchars(ASSETS_URL) ?>css/style.css?v=1">
  <style>
    .search-bar { margin: 20px 0; text-align: center; }
    .search-bar input { width: 400px; max-width: 90%; padding: 10px; font-size: 16px; }
    .search-bar button { padding: 10px 20px; background: #6b8e23; color: white; border: none; cursor: pointer; }
    .user-list { list-style: none; padding: 0; }
    .user-item { 
      background: #f9f9f9; 
      margin: 10px 0; 
      padding: 15px; 
      border: 1px solid #ddd; 
      border-radius: 6px; 
    }
    .user-item strong { font-size: 1.1em; }
    .btn-add { 
      background: #0066cc; 
      color: white; 
      border: none; 
      padding: 8px 14px; 
      cursor: pointer; 
      border-radius: 4px; 
      margin-left: 15px; 
    }
    .btn-add:disabled { background: #aaa; cursor: not-allowed; }
    .message { color: #006600; font-weight: bold; margin: 15px 0; text-align: center; }
  </style>
</head>
<body>

<div id="header">
  <div class="inner">
    <h1><a href="/" style="color:white;"><?= htmlspecialchars(SITE_NAME) ?></a></h1>
    <nav>
      <a href="/games.php">Games</a>
      <a href="/catalog.php">Catalog</a>
      <a href="/people.php">People</a>
      <a href="forum.php">Forum</a>
      <a href="create.php">Create</a> 
      <a href="#">Download</a>
      <a href="settings.php">Settings</a>
      <span style="margin-left:30px;">
        <a href="profile.php?id=<?= $current_user['id'] ?>" style="color:inherit;"><?= htmlspecialchars($current_user['username']) ?></a>!
      </span>
      <a href="logout.php" style="color:#ff4444;">Logout</a>
    </nav>
  </div>
</div>

<div id="subnav">the beta site is here! join the discord: https://discord.gg/AR5eseMV</div>

<div id="container">

  <?php if (isset($message)): ?>
    <div class="message"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <div class="search-bar">
    <form method="get">
      <input type="text" name="q" placeholder="Search users by username..." value="<?= htmlspecialchars($search_query) ?>" autocomplete="off">
      <button type="submit">Search</button>
    </form>
  </div>

  <?php if ($search_query !== ''): ?>
    <h2>Search results for "<?= htmlspecialchars($search_query) ?>"</h2>

    <?php if (empty($users)): ?>
      <p>No users found matching that name.</p>
    <?php else: ?>
      <ul class="user-list">
        <?php foreach ($users as $u): ?>
          <li class="user-item">
            <strong><a href="profile.php?id=<?= $u['id'] ?>"><?= htmlspecialchars($u['username']) ?></a></strong>

            <?php
            $check = $db->prepare("SELECT 1 FROM friend_requests WHERE sender_id = ? AND receiver_id = ?");
            $check->execute([$current_user['id'], $u['id']]);
            $already_sent = $check->fetch();
            ?>

            <form method="post" style="display:inline;">
              <input type="hidden" name="add_friend_id" value="<?= $u['id'] ?>">
              <button type="submit" class="btn-add" <?= $already_sent ? 'disabled' : '' ?>>
                <?= $already_sent ? 'Request Sent' : 'Send Friend Request' ?>
              </button>
            </form>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  <?php else: ?>
    <p>Enter a username above to search for other players.</p>
  <?php endif; ?>

</div>

</body>
</html>