<?php
require_once 'config.php';


$owner_ids = [4, ]; 
$admin_ids = [20, 7]; 


$profile_id = (int)($_GET['id'] ?? 0);
if ($profile_id <= 0) {
    die("Invalid user ID.");
}


$stmt = $db->prepare("SELECT id, username, created_at, last_login, avatar_headshot, avatar_full FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$profile_id]);
$profile = $stmt->fetch();

if (!$profile) {
    die("User not found.");
}


$badge = '';
$badge_class = '';
if (in_array($profile['id'], $owner_ids)) {
    $badge = 'Owner';
    $badge_class = 'badge-owner';
} elseif (in_array($profile['id'], $admin_ids)) {
    $badge = 'Admin';
    $badge_class = 'badge-admin';
}


$join_date = date('F j, Y', strtotime($profile['created_at']));
$last_online = $profile['last_login'] ? date('F j, Y H:i', strtotime($profile['last_login'])) : 'Never';


$stmt = $db->prepare("
    SELECT c.name, c.type, c.rarity, c.image_url
    FROM user_cosmetics uc
    JOIN cosmetics c ON uc.cosmetic_id = c.id
    WHERE uc.user_id = ?
");
$stmt->execute([$profile_id]);
$cosmetics = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($profile['username']) ?> - Avalanche</title>
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
        .profile-container {
            max-width: 980px;
            margin: 20px auto;
            background: white;
            padding: 20px;
            border: 1px solid #ccc;
        }
        .profile-header {
            border-bottom: 2px solid #ddd;
            padding-bottom: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .profile-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .headshot {
            width: 100px;
            height: 100px;
            border-radius: 8px;
            object-fit: cover;
            border: 2px solid #ddd;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
            vertical-align: middle;
        }
        .badge-owner {
            background: #ff9800;
            color: white;
        }
        .badge-admin {
            background: #2196f3;
            color: white;
        }
        .profile-info {
            margin-bottom: 20px;
        }
        .profile-info p {
            margin: 5px 0;
            color: #333;
        }
        .profile-info strong {
            color: #666;
        }
        .cosmetics-section h2 {
            margin-bottom: 15px;
            color: #333;
        }
        .avatar-display {
            margin-bottom: 30px;
        }
        .avatar-display img {
            max-width: 300px;
            border: 2px solid #ddd;
            border-radius: 8px;
        }
        .cosmetics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 15px;
        }
        .cosmetic-item {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
            border-radius: 4px;
        }
        .cosmetic-item img {
            max-width: 100%;
            height: auto;
            margin-bottom: 5px;
        }
        .cosmetic-item p {
            font-size: 12px;
            color: #666;
        }
        .rarity {
            font-weight: bold;
            font-size: 11px;
        }
        .rarity-common { color: #9e9e9e; }
        .rarity-uncommon { color: #4caf50; }
        .rarity-rare { color: #2196f3; }
        .rarity-epic { color: #9c27b0; }
        .rarity-legendary { color: #ff9800; }
        #footer {
            text-align: center;
            padding: 20px;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="site-header">
        <h1><a href="index.php">Avalanche</a></h1>
        <nav>
            <a href="index.php">Home</a>
            <a href="games.php">Games</a>
            <a href="catalog.php">Catalog</a>
            <a href="avatar.php">Avatar</a>
            <a href="people.php">People</a>
            <a href="forum.php">Forum</a>
            <a href="create.php">Create</a>
            <a href="settings.php">Settings</a>
        </nav>
        <div class="nav-right">
            <span>Welcome!</span>
            <a href="login.php">Login</a>
        </div>
    </div>
    <div class="beta-notice">
        the beta site is here! join the discord: <a href="https://discord.gg/KTHb5Ztn2n">https://discord.gg/KTHb5Ztn2n</a> →
    </div>
    
    <div class="profile-container">
        <div class="profile-header">
            
            <img src="<?= htmlspecialchars($profile['avatar_headshot'] ?? '/assets/images/default_headshot.png') ?>" 
                 alt="<?= htmlspecialchars($profile['username']) ?>'s headshot" 
                 class="headshot">
            
            <div>
                <h1>
                    <?= htmlspecialchars($profile['username']) ?>
                    <?php if ($badge): ?>
                        <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($badge) ?></span>
                    <?php endif; ?>
                </h1>
            </div>
        </div>
        
        <div class="profile-info">
            <p><strong>Join Date:</strong> <?= $join_date ?></p>
            <p><strong>Last Online:</strong> <?= $last_online ?></p>
        </div>
        
        <div class="cosmetics-section">
            <h2>Full Avatar</h2>
            <div class="avatar-display">
               
                <img src="<?= htmlspecialchars($profile['avatar_full'] ?? '/assets/images/default_full.png') ?>" 
                     alt="<?= htmlspecialchars($profile['username']) ?>'s full avatar">
            </div>
        </div>
        
        <div class="cosmetics-section">
            <h2>Rare Cosmetics</h2>
            <?php
            $rare_cosmetics = array_filter($cosmetics, function($c) {
                return in_array($c['rarity'], ['rare', 'epic', 'legendary']);
            });
            
            if (empty($rare_cosmetics)):
            ?>
                <p>No rare items yet.</p>
            <?php else: ?>
                <div class="cosmetics-grid">
                    <?php foreach ($rare_cosmetics as $cosmetic): ?>
                        <div class="cosmetic-item">
                            <img src="<?= htmlspecialchars($cosmetic['image_url']) ?>" alt="<?= htmlspecialchars($cosmetic['name']) ?>">
                            <p><?= htmlspecialchars($cosmetic['name']) ?></p>
                            <p class="rarity rarity-<?= htmlspecialchars($cosmetic['rarity']) ?>">
                                <?= ucfirst($cosmetic['rarity']) ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div id="footer">
        © <?= date("Y") ?> Avalanche. triple_t was here
    </div>
</body>
</html>