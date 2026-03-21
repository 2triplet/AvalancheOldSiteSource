//gng this took me 9 hours along with 2fa and shit :skull:
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
$game_id = (int)($_GET['game_id'] ?? 0);
if ($game_id <= 0) {
    header("Location: games.php");
    exit;
}

$stmt = $db->prepare("SELECT title FROM games WHERE id = ?");
$stmt->execute([$game_id]);
$game = $stmt->fetch();
$game_title = $game['title'] ?? 'Unknown Game';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_comment'])) {
    $content = trim($_POST['content'] ?? '');
    if (strlen($content) > 0 && strlen($content) <= 1000) {
        $stmt = $db->prepare("
        INSERT INTO comments (game_id, user_id, content)
        VALUES (?, ?, ?)
        ");
        $stmt->execute([$game_id, $user['id'], $content]);

        header("Location: commentary.php?game_id=$game_id");
        exit;
    } else {
        $error = "Comment must be between 1 and 1000 characters.";
    }
}

$stmt = $db->prepare("
SELECT c.content, c.created_at, u.username as commenter
FROM comments c
JOIN users u ON c.user_id = u.id
WHERE c.game_id = ?
ORDER BY c.created_at DESC
");
$stmt->execute([$game_id]);
$comments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Commentary - <?= htmlspecialchars($game_title) ?> - <?= htmlspecialchars(SITE_NAME ?? 'Avalanche') ?></title>

<!-- ✅ FAVICON LOGO HERE (Change the href to your logo path) -->
<link rel="shortcut icon" type="image/png" href="/assets/images/favicon.ico">

<link rel="stylesheet" href="<?= htmlspecialchars(ASSETS_URL ?? '/assets/') ?>css/style.css?v=1">
<style>
body { background: #e0e0e0; font-family: Arial, sans-serif; margin: 0; }
#header {
background: #3d5c8a;
color: white;
padding: 10px 20px;
overflow: hidden;
}
#header h1 { float: left; margin: 0; font-size: 28px; }
#header nav a { color: white; margin-left: 20px; text-decoration: none; }
#header .nav-right { float: right; }
.comment-page {
max-width: 980px;
margin: 20px auto;
background: white;
padding: 20px;
border: 1px solid #ccc;
box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
.back-link {
display: block;
margin-bottom: 20px;
color: #0066cc;
font-weight: bold;
text-decoration: none;
}
.back-link:hover { text-decoration: underline; }
.comment-form {
margin: 30px 0;
padding: 20px;
background: #f9f9f9;
border: 1px solid #ddd;
border-radius: 8px;
}
.comment-form textarea {
width: 100%;
padding: 12px;
border: 1px solid #ccc;
border-radius: 6px;
min-height: 120px;
margin-bottom: 10px;
box-sizing: border-box;
font-family: Arial, sans-serif;
}
.comment-btn {
background: #2196f3;
color: white;
border: none;
padding: 12px 25px;
border-radius: 6px;
cursor: pointer;
font-weight: bold;
font-size: 16px;
}
.comment-list {
margin-top: 40px;
}
.comment-item {
background: #f9f9f9;
padding: 15px;
border: 1px solid #ddd;
border-radius: 8px;
margin-bottom: 15px;
}
.comment-author {
font-weight: bold;
color: #1976d2;
margin-bottom: 5px;
}

.comment-author a {
    color: #1976d2;
    text-decoration: none;
}
.comment-author a:hover {
    text-decoration: underline;
}
.comment-date {
font-size: 12px;
color: #777;
margin-bottom: 8px;
}
.comment-text {
line-height: 1.5;
white-space: pre-wrap;
}
.error-msg {
color: #d32f2f;
margin-bottom: 10px;
font-weight: bold;
}
.no-comments {
text-align: center;
color: #777;
font-size: 18px;
margin: 40px 0;
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
<a href="#">Help</a>
<a href="#">Download</a>
<span class="nav-right">
Welcome, <?= htmlspecialchars($user['username']) ?>!
<a href="logout.php" style="color:#ff4444; margin-left:20px;">Logout</a>
</span>
</nav>
</div>
</div>
<div id="subnav">
the beta site is here! join the dc: <a href="https://discord.gg/HRPd2Bq4tW" style="color:white; text-decoration:underline;">https://discord.gg/HRPd2Bq4tW</a>
</div>
<div class="comment-page">
<a href="game.php?id=<?= $game_id ?>" class="back-link">← Back to <?= htmlspecialchars($game_title) ?></a>
<h2>Commentary for "<?= htmlspecialchars($game_title) ?>"</h2>
<?php if (isset($error)): ?>
<p class="error-msg"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>
<form method="post" class="comment-form">
<textarea name="content" placeholder="Write your comment here..." rows="5" required></textarea>
<button type="submit" name="post_comment" class="comment-btn">Post Comment</button>
</form>
<div class="comment-list">
<?php if (empty($comments)): ?>
<p class="no-comments">No comments yet. Be the first to share your thoughts!</p>
<?php else: ?>
<?php foreach ($comments as $comment): ?>
<div class="comment-item">
 
    <div class="comment-author">
        <a href="profile.php?user=<?= urlencode($comment['commenter']) ?>" title="View <?= htmlspecialchars($comment['commenter']) ?>'s profile">
            <?= htmlspecialchars($comment['commenter']) ?>
        </a>
    </div>
    <div class="comment-date"><?= date('M j, Y g:i A', strtotime($comment['created_at'])) ?></div>
    <div class="comment-text"><?= nl2br(htmlspecialchars($comment['content'])) ?></div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>
<div id="footer">
© <?= date("Y") ?> <?= htmlspecialchars(SITE_NAME ?? 'Avalanche') ?>. triple_t was here
</div>
</div>
</body>
</html>