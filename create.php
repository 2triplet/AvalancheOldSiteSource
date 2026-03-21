<link rel="icon" type="image/x-icon" href="/favicon.ico">
<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header("Location: signup.php");
    exit;
}

$user = currentUser();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_place'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (strlen($title) < 3 || strlen($title) > 100) {
        $error = "Title must be 3-100 characters.";
    } else {
        // Handle file uploads (rbxl/rbxlx, thumbnail, icon)
        $upload_dir = 'uploads/places/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        $rbxl_path = '';
        $thumbnail_path = 'assets/images/default_game.jpeg';
        $icon_path = 'assets/images/default_icon.png';

        // RBXL upload
        if (!empty($_FILES['rbxl_file']['name'])) {
            $ext = pathinfo($_FILES['rbxl_file']['name'], PATHINFO_EXTENSION);
            if (in_array(strtolower($ext), ['rbxl', 'rbxlx'])) {
                $rbxl_path = $upload_dir . uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['rbxl_file']['tmp_name'], $rbxl_path);
            } else {
                $error = "Only .rbxl or .rbxlx files allowed.";
            }
        }

        // Thumbnail upload (optional)
        if (!empty($_FILES['thumbnail']['name'])) {
            $ext = pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION);
            if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png'])) {
                $thumbnail_path = $upload_dir . uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['thumbnail']['tmp_name'], $thumbnail_path);
            }
        }

        // Icon upload (optional)
        if (!empty($_FILES['icon']['name'])) {
            $ext = pathinfo($_FILES['icon']['name'], PATHINFO_EXTENSION);
            if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png'])) {
                $icon_path = $upload_dir . uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['icon']['tmp_name'], $icon_path);
            }
        }

        if (!$error) {
            $stmt = $db->prepare("
                INSERT INTO games (title, creator_id, thumbnail, description, rbxl_path, icon_path)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$title, $user['id'], $thumbnail_path, $description, $rbxl_path, $icon_path]);

            $message = "Place created successfully! It will appear in the Games page.";
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
  <title>Create Place - <?= htmlspecialchars(SITE_NAME ?? 'Avalanche') ?></title>
  <link rel="stylesheet" href="<?= htmlspecialchars(ASSETS_URL ?? '/assets/') ?>css/style.css?v=1">
  <style>
    .form-container {
        max-width: 600px;
        margin: 40px auto;
        padding: 30px;
        background: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 8px;
    }
    .form-container h2 {
        margin-top: 0;
        text-align: center;
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: bold;
    }
    .form-group input, .form-group textarea {
        width: 100%;
        padding: 10px;
        box-sizing: border-box;
        border: 1px solid #ccc;
        border-radius: 4px;
    }
    .btn-submit {
        background: #6b8e23;
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: bold;
        width: 100%;
        font-size: 16px;
    }
    .message {
        padding: 12px;
        margin: 20px 0;
        border-radius: 6px;
        text-align: center;
    }
    .success { background: #e8ffe8; color: #006600; border: 1px solid #c8e6c9; }
    .error   { background: #ffebee; color: #d32f2f; border: 1px solid #ffcdd2; }
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
      <a href="create.php">Create</a>
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

<div id="container">
  <div class="form-container">
    <h2>Create New Place</h2>

    <?php if ($message): ?>
      <div class="message success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
      <div class="form-group">
        <label for="title">Place Title *</label>
        <input type="text" id="title" name="title" required placeholder="My Awesome Obby">
      </div>

      <div class="form-group">
        <label for="description">Description</label>
        <textarea id="description" name="description" rows="5" placeholder="Tell players what your place is about..."></textarea>
      </div>

      <div class="form-group">
        <label for="rbxl_file">Upload .rbxl / .rbxlx file *</label>
        <input type="file" id="rbxl_file" name="rbxl_file" accept=".rbxl,.rbxlx" required>
      </div>

      <div class="form-group">
        <label for="thumbnail">Upload Thumbnail (optional)</label>
        <input type="file" id="thumbnail" name="thumbnail" accept="image/*">
      </div>

      <div class="form-group">
        <label for="icon">Upload Game Icon (optional)</label>
        <input type="file" id="icon" name="icon" accept="image/*">
      </div>

      <button type="submit" name="create_place" class="btn-submit">Create Place</button>
    </form>
  </div>

  <div id="footer">
    © <?= date("Y") ?> <?= htmlspecialchars(SITE_NAME ?? 'Avalanche') ?>. triple_t was here
  </div>
</div>

</body>
</html>