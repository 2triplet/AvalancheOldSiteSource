<?php
require_once 'config.php';


requireNotBanned();

$user = currentUser();
if (!$user) {
    header("Location: login.php");
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: games.php");
    exit;
}

// Fetch game details
$stmt = $db->prepare("
    SELECT g.*, u.username AS creator_username, u.id AS creator_id
    FROM games g
    LEFT JOIN users u ON g.creator_id = u.id
    WHERE g.id = ?
");
$stmt->execute([$id]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$game) {
    header("Location: games.php");
    exit;
}

// Use fallback if creator is missing
$creator_name = $game['creator_username'] ?? 'Unknown';
$creator_id = $game['creator_id'] ?? 0;

// Handle favorite/unfavorite
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['favorite_action'])) {
    $action = $_POST['favorite_action'];
    $stmt = $db->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = ? AND game_id = ?");
    $stmt->execute([$user['id'], $id]);
    $is_favorited = $stmt->fetchColumn() > 0;

    if ($action === 'favorite' && !$is_favorited) {
        $stmt = $db->prepare("INSERT INTO favorites (user_id, game_id) VALUES (?, ?)");
        $stmt->execute([$user['id'], $id]);
        $stmt = $db->prepare("UPDATE games SET favorites = favorites + 1 WHERE id = ?");
        $stmt->execute([$id]);
    } elseif ($action === 'unfavorite' && $is_favorited) {
        $stmt = $db->prepare("DELETE FROM favorites WHERE user_id = ? AND game_id = ?");
        $stmt->execute([$user['id'], $id]);
        $stmt = $db->prepare("UPDATE games SET favorites = favorites - 1 WHERE id = ?");
        $stmt->execute([$id]);
    }
    header("Location: game.php?id=$id");
    exit;
}

// Handle like/dislike
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vote_action'])) {
    $action = $_POST['vote_action'];
    $stmt = $db->prepare("SELECT type FROM game_likes WHERE user_id = ? AND game_id = ?");
    $stmt->execute([$user['id'], $id]);
    $existing_vote = $stmt->fetchColumn();

    if ($existing_vote) {
        $stmt = $db->prepare("DELETE FROM game_likes WHERE user_id = ? AND game_id = ?");
        $stmt->execute([$user['id'], $id]);
        $type = $existing_vote === 'like' ? 'likes' : 'dislikes';
        $stmt = $db->prepare("UPDATE games SET $type = $type - 1 WHERE id = ?");
        $stmt->execute([$id]);
    }

    if ($action !== 'remove') {
        $stmt = $db->prepare("INSERT INTO game_likes (user_id, game_id, type) VALUES (?, ?, ?)");
        $stmt->execute([$user['id'], $id, $action]);
        $type = $action === 'like' ? 'likes' : 'dislikes';
        $stmt = $db->prepare("UPDATE games SET $type = $type + 1 WHERE id = ?");
        $stmt->execute([$id]);
    }

    header("Location: game.php?id=$id");
    exit;
}

// Refresh game data after votes
$stmt = $db->prepare("SELECT * FROM games WHERE id = ?");
$stmt->execute([$id]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);

// Favorites check
$stmt = $db->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = ? AND game_id = ?");
$stmt->execute([$user['id'], $id]);
$is_favorited = $stmt->fetchColumn() > 0;

// Like/Dislike check
$stmt = $db->prepare("SELECT type FROM game_likes WHERE user_id = ? AND game_id = ?");
$stmt->execute([$user['id'], $id]);
$existing_vote = $stmt->fetchColumn();

// Stats
$favorites = (int)($game['favorites'] ?? 0);
$likes = (int)($game['likes'] ?? 0);
$dislikes = (int)($game['dislikes'] ?? 0);
$max_players = 20;
$current_players = (int)($game['playing'] ?? 0);
$genres = "All";
$gear_types = "None";

$created = date('n/j/Y', strtotime($game['created_at']));
$updated = $game['updated_at'] ? date('n/j/Y', strtotime($game['updated_at'])) : 'Never';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($game['title']) ?> - Avalanche</title>
    <link rel="shortcut icon" type="image/x-icon" href="/assets/images/favicon.ico">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #e0e0e0; }
        
       
        .site-header {
            background: #3d5c8a;
            color: white;
            padding: 10px 20px;
        }
        .site-header h1 {
            font-size: 24px;
            font-weight: bold;
            float: left;
        }
        .site-header h1 a {
            color: white;
            text-decoration: none;
        }
        .site-header nav {
            float: left;
            margin-left: 30px;
        }
        .site-header nav a {
            color: white;
            text-decoration: none;
            margin-right: 20px;
        }
        .site-header nav a:hover {
            text-decoration: underline;
        }
        .site-header .nav-right {
            float: right;
        }
        .site-header .nav-right a {
            color: white;
            text-decoration: none;
            margin-left: 15px;
        }
        .site-header::after {
            content: "";
            display: table;
            clear: both;
        }
        
     
        .beta-notice {
            background: #6b8e3a;
            color: white;
            text-align: center;
            padding: 8px;
            font-size: 14px;
            clear: both;
        }
        .beta-notice a {
            color: white;
            text-decoration: underline;
        }

        .game-container {
            max-width: 980px;
            margin: 20px auto;
            background: white;
            padding: 20px;
            border: 1px solid #ccc;
        }
        .game-header {
            margin-bottom: 20px;
        }
        .game-header h1 {
            font-size: 28px;
            color: #333;
        }
        .game-content {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        .game-image {
            flex: 2;
            min-width: 300px;
        }
        .game-image img {
            width: 100%;
            border: 1px solid #ccc;
        }
        .game-info {
            flex: 1;
            min-width: 250px;
            background: #f9f9f9;
            padding: 15px;
            border: 1px solid #ddd;
        }
        .game-info p {
            margin: 8px 0;
            font-size: 14px;
        }
        .game-info strong {
            color: #666;
        }
        .game-info a {
            color: #0066cc;
            text-decoration: none;
        }
        .game-info a:hover {
            text-decoration: underline;
        }
        .btn-play {
            display: block;
            width: 100%;
            padding: 12px;
            background: #6b8e3a;
            color: white;
            text-align: center;
            text-decoration: none;
            font-weight: bold;
            border-radius: 4px;
            margin-bottom: 15px;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-play:hover {
            background: #5a7a30;
        }
        .btn-fav {
            display: block;
            width: 100%;
            padding: 10px;
            background: #ff9800;
            color: white;
            text-align: center;
            text-decoration: none;
            font-weight: bold;
            border-radius: 4px;
            margin-top: 15px;
            border: none;
            cursor: pointer;
        }
        .btn-fav:hover {
            background: #f57c00;
        }
        .vote-section {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
        }
        .btn-vote {
            flex: 1;
            padding: 8px;
            background: #eee;
            border: none;
            cursor: pointer;
            font-size: 18px;
            border-radius: 4px;
            margin: 0 5px;
        }
        .btn-vote.active {
            background: #ddd;
            border: 1px solid #ccc;
        }
        .progress-bar {
            background: #eee;
            height: 10px;
            border-radius: 5px;
            margin: 10px 0;
            overflow: hidden;
        }
        .progress-fill {
            background: #6b8e3a;
            height: 100%;
        }
        .tabs {
            margin-top: 30px;
            border-bottom: 1px solid #ccc;
        }
        .tabs a {
            display: inline-block;
            padding: 10px 20px;
            background: #eee;
            color: #333;
            text-decoration: none;
            border: 1px solid #ccc;
            border-bottom: none;
            margin-right: 5px;
            border-radius: 4px 4px 0 0;
        }
        .tabs a:hover {
            background: #ddd;
        }
        .tab-content {
            padding: 20px 0;
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
    <!-- ✅ MATCHING HEADER -->
    <div class="site-header">
        <h1><a href="index.php">Avalanche</a></h1>
        <nav>
            <a href="index.php">Home</a>
            <a href="games.php">Games</a>
            <a href="catalog.php">Catalog</a>
            <a href="avatar.php">Avatar</a>
            <a href="people.php">People</a>
            <a href="forum.php">Forum</a>
            <a href="download.php">Download</a>
            <a href="settings.php">Settings</a>
        </nav>
        <div class="nav-right">
            <span>Welcome, <?= htmlspecialchars($user['username']) ?>!</span>
            <a href="logout.php" style="color:#ff6b6b;">Logout</a>
        </div>
    </div>
    
    <!-- ✅ MATCHING BETA NOTICE -->
    <div class="beta-notice">
        the beta site is here! join the dc: <a href="https://discord.gg/HRPd2Bq4tW">https://discord.gg/HRPd2Bq4tW</a>
    </div>
    
    <div class="game-container">
        <div class="game-header">
            <h1><?= htmlspecialchars($game['title']) ?></h1>
        </div>
        
        <div class="game-content">
            <div class="game-image">
                <img src="<?= htmlspecialchars($game['thumbnail']) ?>" alt="<?= htmlspecialchars($game['title']) ?>">
                <div class="tab-content">
                    <p><?= nl2br(htmlspecialchars($game['description'])) ?></p>
                </div>
                
                <div class="tabs">
                    <a href="#">Recommendations</a>
                    <a href="#">Games</a>
                    <a href="commentary.php?game_id=<?= $game['id'] ?>">Commentary</a>
                </div>
            </div>
            
            <div class="game-info">
                <p><strong>Creator:</strong> <a href="profile.php?id=<?= $creator_id ?>"><?= htmlspecialchars($creator_name) ?></a></p>
                
                <form method="post">
                    <button type="submit" class="btn-play">Play</button>
                </form>
                
                <p><strong>Created:</strong> <?= $created ?></p>
                <p><strong>Updated:</strong> <?= $updated ?></p>
                <p><strong>Visits:</strong> <?= number_format($game['visits']) ?></p>
                <p><strong>Favorited:</strong> <?= number_format($favorites) ?></p>
                <p><strong>Max Players:</strong> <?= $max_players ?></p>
                <p><strong>Current Players:</strong> <?= $current_players ?></p>
                <p><strong>Genres:</strong> <?= $genres ?></p>
                <p><strong>Allowed Gear Types:</strong> <?= $gear_types ?></p>
                
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= $current_players > 0 ? ($current_players / $max_players) * 100 : 0 ?>%;"></div>
                </div>
                
                <form method="post" class="vote-section">
                    <button type="submit" name="vote_action" value="like" class="btn-vote <?= $existing_vote === 'like' ? 'active' : '' ?>">👍 <?= $likes ?></button>
                    <button type="submit" name="vote_action" value="dislike" class="btn-vote <?= $existing_vote === 'dislike' ? 'active' : '' ?>">👎 <?= $dislikes ?></button>
                </form>
                
                <form method="post">
                    <input type="hidden" name="favorite_action" value="<?= $is_favorited ? 'unfavorite' : 'favorite' ?>">
                    <button type="submit" class="btn-fav">★ Favorite (<?= $favorites ?>)</button>
                </form>
            </div>
        </div>
    </div>
    
    <div id="footer">
        © <?= date("Y") ?> Avalanche. triple_t was here
    </div>
</body>
</html>