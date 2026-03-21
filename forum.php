<link rel="icon" type="image/x-icon" href="/favicon.ico">
<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header("Location: signup.php");
    exit;
}

$current_user = currentUser();
$message = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_thread'])) {
    $cat_id = (int)($_POST['category_id'] ?? 0);
    $title  = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if ($cat_id > 0 && strlen($title) >= 3 && strlen($content) >= 5) {
        try {
            $stmt = $db->prepare("INSERT INTO forum_threads (category_id, user_id, title) VALUES (?, ?, ?)");
            $stmt->execute([$cat_id, $current_user['id'], $title]);
            $thread_id = $db->lastInsertId();

            $stmt = $db->prepare("INSERT INTO forum_posts (thread_id, user_id, content) VALUES (?, ?, ?)");
            $stmt->execute([$thread_id, $current_user['id'], $content]);

            $message = "Thread created!";
        } catch (Exception $e) {
            $message = "Error creating thread.";
        }
    } else {
        $message = "Please fill title and message properly.";
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_to_thread'])) {
    $thread_id = (int)($_POST['thread_id'] ?? 0);
    $content = trim($_POST['reply_content'] ?? '');

    if ($thread_id > 0 && strlen($content) >= 5) {
        try {
            $stmt = $db->prepare("INSERT INTO forum_posts (thread_id, user_id, content) VALUES (?, ?, ?)");
            $stmt->execute([$thread_id, $current_user['id'], $content]);
            $message = "Reply posted!";
        } catch (Exception $e) {
            $message = "Error posting reply.";
        }
    } else {
        $message = "Reply too short.";
    }
}


$categories = $db->query("SELECT * FROM forum_categories ORDER BY name")->fetchAll();


$thread = null;
$posts = [];
$thread_id = (int)($_GET['thread'] ?? 0);

if ($thread_id > 0) {
    $stmt = $db->prepare("
        SELECT t.*, u.username 
        FROM forum_threads t 
        JOIN users u ON t.user_id = u.id 
        WHERE t.id = ?
    ");
    $stmt->execute([$thread_id]);
    $thread = $stmt->fetch();

    if ($thread) {
        $stmt = $db->prepare("
            SELECT p.*, u.username 
            FROM forum_posts p 
            JOIN users u ON p.user_id = u.id 
            WHERE p.thread_id = ? 
            ORDER BY p.created_at ASC
        ");
        $stmt->execute([$thread_id]);
        $posts = $stmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="shortcut icon" type="image/x-icon" href="/assets/images/favicon.ico">
  <title><?= htmlspecialchars(SITE_NAME) ?> - Forum</title>
  <link rel="stylesheet" href="<?= htmlspecialchars(ASSETS_URL) ?>css/style.css?v=1">
  <style>
    .category { background:#f8f8f8; padding:15px; margin:15px 0; border:1px solid #ccc; border-radius:6px; }
    .thread-list { list-style:none; padding:0; margin:10px 0; }
    .thread-item { padding:10px; border-bottom:1px solid #eee; }
    .thread-item a { font-weight:bold; color:#0066cc; }
    .post { background:#fff; margin:15px 0; padding:15px; border:1px solid #ccc; border-radius:6px; }
    .post-header { font-weight:bold; color:#444; margin-bottom:8px; }
    .new-form { margin:40px 0; padding:20px; background:#f9f9f9; border:1px solid #ddd; border-radius:6px; }
    textarea { width:100%; min-height:120px; padding:10px; margin:10px 0; box-sizing:border-box; }
    button { padding:10px 20px; background:#6b8e23; color:white; border:none; cursor:pointer; }
    .message { color:#006600; font-weight:bold; margin:15px 0; text-align:center; }
  </style>
</head>
<body>

<div id="header">
  <div class="inner">
    <h1><a href="/" style="color:white;"><?= htmlspecialchars(SITE_NAME) ?></a></h1>
    <nav>
      <a href="/">Home</a>
      <a href="people.php">People</a>
      <a href="forum.php">Forum</a>
      <a href="create.php">Create</a>
      <a href="settings.php">Settings</a>
      <span style="margin-left:30px;">Hi, <?= htmlspecialchars($current_user['username']) ?></span>
      <a href="logout.php" style="color:#ff4444;">Logout</a>
    </nav>
  </div>
</div>

<div id="subnav">Community Forum</div>

<div id="container">

  <?php if ($message): ?>
    <div class="message"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <?php if ($thread): ?>

    <h2><?= htmlspecialchars($thread['title']) ?></h2>
    <p>Started by <?= htmlspecialchars($thread['username']) ?> on <?= date('M d, Y H:i', strtotime($thread['created_at'])) ?></p>

    <?php foreach ($posts as $post): ?>
      <div class="post">
        <div class="post-header">
          <?= htmlspecialchars($post['username']) ?> • <?= date('M d, Y H:i', strtotime($post['created_at'])) ?>
        </div>
        <div><?= nl2br(htmlspecialchars($post['content'])) ?></div>
      </div>
    <?php endforeach; ?>

    <form method="post" class="new-form">
      <h3>Post a Reply</h3>
      <textarea name="reply_content" placeholder="Your reply..." required></textarea>
      <input type="hidden" name="thread_id" value="<?= $thread['id'] ?>">
      <button type="submit" name="reply_to_thread">Submit Reply</button>
    </form>

    <p><a href="forum.php">← Back to Forum</a></p>

  <?php else: ?>

    <h2>Forum Categories</h2>

    <?php foreach ($categories as $cat): ?>
      <div class="category">
        <h3><?= htmlspecialchars($cat['name']) ?></h3>
        <p><?= htmlspecialchars($cat['description'] ?? 'No description') ?></p>

        <?php
        $stmt = $db->prepare("SELECT t.id, t.title, t.created_at, u.username FROM forum_threads t JOIN users u ON t.user_id = u.id WHERE category_id = ? ORDER BY t.created_at DESC LIMIT 10");
        $stmt->execute([$cat['id']]);
        $threads = $stmt->fetchAll();
        ?>

        <?php if ($threads): ?>
          <ul class="thread-list">
            <?php foreach ($threads as $t): ?>
              <li class="thread-item">
                <a href="?thread=<?= $t['id'] ?>"><?= htmlspecialchars($t['title']) ?></a>
                <br><small>by <?= htmlspecialchars($t['username']) ?> • <?= date('M d, Y', strtotime($t['created_at'])) ?></small>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <p>No threads yet in this category.</p>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>

    <div class="new-form">
      <h3>Create New Thread</h3>
      <form method="post">
        <select name="category_id" required style="width:100%; padding:8px; margin:10px 0;">
          <option value="">Select Category</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
          <?php endforeach; ?>
        </select>

        <input type="text" name="title" placeholder="Thread Title" required style="width:100%; padding:10px; margin:10px 0;">
        <textarea name="content" placeholder="Your message..." required></textarea>
        <br><br>
        <button type="submit" name="new_thread">Create Thread</button>
      </form>
    </div>

  <?php endif; ?>

</div>

</body>
</html>