<?php
session_start();
require_once 'config.php';


$owner_ids = [1, 2];
$admin_ids = [3, 4, 5];

if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$user = currentUser();

/
if (!in_array($user['id'], $owner_ids) && !in_array($user['id'], $admin_ids)) {
    header("Location: index.php");
    exit;
}

$message = '';
$error = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_id = (int)($_POST['user_id'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');
    
    if ($target_id <= 0) {
        $error = "Invalid user ID.";
    } elseif ($target_id == $user['id']) {
        $error = "You cannot take action on yourself.";
    } else {

        $stmt = $db->prepare("SELECT id, username, warning_count FROM users WHERE id = ?");
        $stmt->execute([$target_id]);
        $target = $stmt->fetch();
        
        if (!$target) {
            $error = "User not found.";
        } elseif (in_array($target_id, $owner_ids) && !in_array($user['id'], $owner_ids)) {
            $error = "You cannot take action on an owner.";
        } else {
            try {

                if (isset($_POST['action_permanent'])) {
                    if (empty($reason)) {
                        $error = "Reason is required for permanent ban.";
                    } else {
                        $stmt = $db->prepare("UPDATE users SET is_banned = 1, ban_reason = ?, ban_expires = NULL, unban_reason = NULL, unbanned_by = NULL WHERE id = ?");
                        $stmt->execute([$reason, $target_id]);
                        
                        $stmt = $db->prepare("INSERT INTO bans (user_id, banned_by, reason, ban_type, expires_at) VALUES (?, ?, ?, 'permanent', NULL)");
                        $stmt->execute([$target_id, $user['id'], $reason]);
                        
                        $message = "User permanently banned!";
                    }
                } elseif (isset($_POST['action_temporary'])) {
                    $duration = (int)($_POST['duration'] ?? 7);
                    $expires_date = trim($_POST['expires_date'] ?? '');
                    
                    if (empty($reason)) {
                        $error = "Reason is required for temporary ban.";
                    } elseif (empty($expires_date)) {
                        $error = "Expiration date is required for temporary ban.";
                    } else {
                        $expires = date('Y-m-d H:i:s', strtotime($expires_date));
                        $stmt = $db->prepare("UPDATE users SET is_banned = 1, ban_reason = ?, ban_expires = ?, unban_reason = NULL, unbanned_by = NULL WHERE id = ?");
                        $stmt->execute([$reason, $expires, $target_id]);
                        
                        $stmt = $db->prepare("INSERT INTO bans (user_id, banned_by, reason, ban_type, expires_at) VALUES (?, ?, ?, 'temporary', ?)");
                        $stmt->execute([$target_id, $user['id'], $reason, $expires]);
                        
                        $message = "User temporarily banned until {$expires_date}!";
                    }
                } elseif (isset($_POST['action_warning'])) {
                    if (empty($reason)) {
                        $error = "Reason is required for warning.";
                    } else {

                        $stmt = $db->prepare("UPDATE users SET warning_count = warning_count + 1 WHERE id = ?");
                        $stmt->execute([$target_id]);
                        

                        $stmt = $db->prepare("INSERT INTO warnings (user_id, warned_by, reason) VALUES (?, ?, ?)");
                        $stmt->execute([$target_id, $user['id'], $reason]);
                        
                        
                        $stmt = $db->prepare("INSERT INTO bans (user_id, banned_by, reason, ban_type, expires_at) VALUES (?, ?, ?, 'warning', NULL)");
                        $stmt->execute([$target_id, $user['id'], $reason]);
                        
                        
                        $new_warning_count = $target['warning_count'] + 1;
                        if ($new_warning_count >= 3) {
                            $stmt = $db->prepare("UPDATE users SET is_banned = 1, ban_reason = ?, ban_expires = NULL WHERE id = ?");
                            $stmt->execute(["Auto-banned: Reached 3 warnings", $target_id]);
                            
                            $stmt = $db->prepare("INSERT INTO bans (user_id, banned_by, reason, ban_type, expires_at) VALUES (?, ?, ?, 'permanent', NULL)");
                            $stmt->execute([$target_id, $user['id'], "Auto-banned: Reached 3 warnings"]);
                            
                            $message = "Warning issued! User auto-banned (3 warnings reached).";
                        } else {
                            $message = "Warning issued! ({$new_warning_count}/3 warnings)";
                        }
                    }
                } elseif (isset($_POST['action_unban'])) {
                    $unban_reason = trim($_POST['unban_reason'] ?? '');
                    
                    $stmt = $db->prepare("UPDATE users SET is_banned = 0, ban_expires = NULL, unban_reason = ?, unbanned_by = ?, unbanned_at = NOW() WHERE id = ?");
                    $stmt->execute([$unban_reason ?: 'Manual unban', $user['id'], $target_id]);
                    
                    $stmt = $db->prepare("INSERT INTO bans (user_id, banned_by, reason, ban_type, expires_at, unban_reason) VALUES (?, ?, ?, 'unbanned', NULL, ?)");
                    $stmt->execute([$target_id, $user['id'], $unban_reason ?: 'Manual unban', $unban_reason ?: 'Manual unban']);
                    
                    $message = "User unbanned successfully!";
                }
            } catch (Exception $e) {
                $error = "Error: " . $e->getMessage();
            }
        }
    }
}


$stmt = $db->prepare("
    SELECT id, username, email, created_at, is_banned, warning_count, ban_expires, ban_reason, unban_reason, unbanned_by
    FROM users
    ORDER BY created_at DESC
");
$stmt->execute();
$all_users = $stmt->fetchAll();


$stmt = $db->prepare("
    SELECT b.*, u.username as banned_username, ub.username as banned_by_username
    FROM bans b
    JOIN users u ON b.user_id = u.id
    JOIN users ub ON b.banned_by = ub.id
    ORDER BY b.created_at DESC
    LIMIT 50
");
$stmt->execute();
$ban_log = $stmt->fetchAll();


$stmt = $db->prepare("
    SELECT w.*, u.username as warned_username, ub.username as warned_by_username
    FROM warnings w
    JOIN users u ON w.user_id = u.id
    JOIN users ub ON w.warned_by = ub.id
    ORDER BY w.created_at DESC
    LIMIT 50
");
$stmt->execute();
$warning_log = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Panel - Avalanche</title>
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
        .admin-container {
            max-width: 1200px;
            margin: 20px auto;
            background: white;
            padding: 20px;
            border: 1px solid #ccc;
        }
        .admin-header {
            border-bottom: 2px solid #ddd;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .admin-header h1 {
            color: #333;
            font-size: 28px;
        }
        .admin-badge {
            display: inline-block;
            background: #d32f2f;
            color: white;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }
        .message {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .error {
            background: #ffebee;
            color: #d32f2f;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .section {
            margin-bottom: 30px;
        }
        .section h2 {
            color: #333;
            margin-bottom: 15px;
            font-size: 20px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table th, table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        table th {
            background: #f5f5f5;
            font-weight: bold;
            color: #333;
        }
        table tr:hover {
            background: #f9f9f9;
        }
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            font-weight: bold;
            margin: 2px;
        }
        .btn-ban { background: #d32f2f; color: white; }
        .btn-ban:hover { background: #b71c1c; }
        .btn-temp { background: #ff9800; color: white; }
        .btn-temp:hover { background: #f57c00; }
        .btn-warn { background: #2196f3; color: white; }
        .btn-warn:hover { background: #1976d2; }
        .btn-unban { background: #4caf50; color: white; }
        .btn-unban:hover { background: #388e3c; }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        .modal-content {
            background: white;
            max-width: 500px;
            margin: 50px auto;
            padding: 25px;
            border-radius: 8px;
            border: 1px solid #ccc;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-content h3 {
            margin-bottom: 15px;
            color: #333;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        .modal-actions {
            text-align: right;
            margin-top: 20px;
        }
        .modal-actions button {
            margin-left: 10px;
        }
        .close {
            float: right;
            font-size: 24px;
            cursor: pointer;
            color: #999;
        }
        .close:hover { color: #333; }
        .status-banned { color: #d32f2f; font-weight: bold; }
        .status-active { color: #4caf50; font-weight: bold; }
        .warning-high { color: #d32f2f; font-weight: bold; }
        .warning-medium { color: #ff9800; font-weight: bold; }
        .warning-low { color: #4caf50; font-weight: bold; }
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
            <a href="admin.php">Admin</a>
        </nav>
        <div class="nav-right">
            <span>Welcome, <?= htmlspecialchars($user['username']) ?>!</span>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    <div class="beta-notice">
        the beta site is here! join the discord: <a href="https://discord.gg/KTHb5Ztn2n">https://discord.gg/KTHb5Ztn2n</a> →
    </div>
    
    <div class="admin-container">
        <div class="admin-header">
            <h1>
                Admin Panel
                <span class="admin-badge">
                    <?= in_array($user['id'], $owner_ids) ? 'Owner' : 'Admin' ?>
                </span>
            </h1>
        </div>
        
        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <!-- User Management Section -->
        <div class="section">
            <h2>👥 User Management</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Joined</th>
                        <th>Warnings</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_users as $u): ?>
                        <tr>
                            <td><?= $u['id'] ?></td>
                            <td><?= htmlspecialchars($u['username']) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                            <td>
                                <span class="<?= $u['warning_count'] >= 3 ? 'warning-high' : ($u['warning_count'] >= 2 ? 'warning-medium' : 'warning-low') ?>">
                                    <?= $u['warning_count'] ?> / 3
                                </span>
                            </td>
                            <td>
                                <?php if ($u['is_banned']): ?>
                                    <span class="status-banned">Banned</span>
                                    <?php if ($u['ban_expires']): ?>
                                        <br><small>Until: <?= date('M j, Y H:i', strtotime($u['ban_expires'])) ?></small>
                                    <?php endif; ?>
                                    <?php if ($u['unban_reason']): ?>
                                        <br><small style="color: #4caf50;">Unbanned</small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="status-active">Active</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($u['is_banned'] && empty($u['unbanned_by'])): ?>
                                    <button class="btn btn-unban" onclick="openUnbanModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username']) ?>')">Unban</button>
                                <?php elseif (!$u['is_banned']): ?>
                                    <button class="btn btn-ban" onclick="openModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username']) ?>', 'permanent')">Ban</button>
                                    <button class="btn btn-temp" onclick="openModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username']) ?>', 'temporary')">Temp Ban</button>
                                    <button class="btn btn-warn" onclick="openModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username']) ?>', 'warning')">Warn</button>
                                <?php else: ?>
                                    <span style="color: #666;">Already Unbanned</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Warnings Log Section -->
        <div class="section">
            <h2>⚠️ Warning Log</h2>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>User</th>
                        <th>Reason</th>
                        <th>By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($warning_log as $log): ?>
                        <tr>
                            <td><?= date('M j, Y H:i', strtotime($log['created_at'])) ?></td>
                            <td><?= htmlspecialchars($log['warned_username']) ?></td>
                            <td><?= htmlspecialchars($log['reason']) ?></td>
                            <td><?= htmlspecialchars($log['warned_by_username']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        

        <div class="section">
            <h2>📋 Ban Log</h2>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Reason</th>
                        <th>By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ban_log as $log): ?>
                        <tr>
                            <td><?= date('M j, Y H:i', strtotime($log['created_at'])) ?></td>
                            <td><?= htmlspecialchars($log['banned_username']) ?></td>
                            <td>
                                <?php
                                $badge_color = $log['ban_type'] === 'permanent' ? '#d32f2f' : 
                                              ($log['ban_type'] === 'temporary' ? '#ff9800' : 
                                              ($log['ban_type'] === 'unbanned' ? '#4caf50' : '#2196f3'));
                                ?>
                                <span style="background: <?= $badge_color ?>; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px;">
                                    <?= ucfirst($log['ban_type']) ?>
                                </span>
                                <?php if ($log['expires_at']): ?>
                                    <br><small>Until: <?= date('M j, Y H:i', strtotime($log['expires_at'])) ?></small>
                                <?php endif; ?>
                                <?php if ($log['unban_reason']): ?>
                                    <br><small style="color: #4caf50;">Unban: <?= htmlspecialchars($log['unban_reason']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($log['reason']) ?></td>
                            <td><?= htmlspecialchars($log['banned_by_username']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    

    <div id="actionModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3 id="modalTitle">Ban User</h3>
            <form method="post">
                <input type="hidden" id="modal_user_id" name="user_id" value="">
                
                <div class="form-group">
                    <label>Target User:</label>
                    <input type="text" id="modal_username" disabled>
                </div>
                
                <div class="form-group">
                    <label>Reason:</label>
                    <textarea name="reason" required placeholder="Explain why you're taking this action..."></textarea>
                </div>
                
                <div class="form-group" id="durationGroup" style="display:none;">
                    <label>Expiration Date & Time:</label>
                    <input type="datetime-local" name="expires_date" id="expires_date">
                </div>
                
                
                <input type="hidden" name="action_permanent" id="action_permanent" value="">
                <input type="hidden" name="action_temporary" id="action_temporary" value="">
                <input type="hidden" name="action_warning" id="action_warning" value="">
                
                <div class="modal-actions">
                    <button type="button" class="btn" onclick="closeModal()" style="background:#ccc;">Cancel</button>
                    <button type="submit" class="btn btn-ban" id="modalSubmit">Confirm</button>
                </div>
            </form>
        </div>
    </div>
    

    <div id="unbanModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeUnbanModal()">&times;</span>
            <h3>Unban User</h3>
            <form method="post">
                <input type="hidden" id="unban_user_id" name="user_id" value="">
                
                <div class="form-group">
                    <label>Target User:</label>
                    <input type="text" id="unban_username" disabled>
                </div>
                
                <div class="form-group">
                    <label>Unban Reason:</label>
                    <textarea name="unban_reason" placeholder="Why are you unbanning this user?"></textarea>
                </div>
                
                <input type="hidden" name="action_unban" value="1">
                
                <div class="modal-actions">
                    <button type="button" class="btn" onclick="closeUnbanModal()" style="background:#ccc;">Cancel</button>
                    <button type="submit" class="btn btn-unban">Unban User</button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="footer">
        © <?= date("Y") ?> Avalanche. triple_t was here
    </div>
    
    <script>
        function openModal(userId, username, action) {
            document.getElementById('modal_user_id').value = userId;
            document.getElementById('modal_username').value = username;
            document.getElementById('actionModal').style.display = 'block';
            

            document.getElementById('action_permanent').value = '';
            document.getElementById('action_temporary').value = '';
            document.getElementById('action_warning').value = '';
            

            const titles = {
                'permanent': 'Permanent Ban',
                'temporary': 'Temporary Ban',
                'warning': 'Issue Warning'
            };
            const colors = {
                'permanent': '#d32f2f',
                'temporary': '#ff9800',
                'warning': '#2196f3'
            };
            const actionFields = {
                'permanent': 'action_permanent',
                'temporary': 'action_temporary',
                'warning': 'action_warning'
            };
            
            document.getElementById('modalTitle').textContent = titles[action];
            document.getElementById('modalSubmit').style.background = colors[action];
            document.getElementById(actionFields[action]).value = '1';
            
            
            document.getElementById('durationGroup').style.display = action === 'temporary' ? 'block' : 'none';
            
            
            if (action === 'temporary') {
                const now = new Date();
                now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
                document.getElementById('expires_date').min = now.toISOString().slice(0, 16);
            }
        }
        
        function closeModal() {
            document.getElementById('actionModal').style.display = 'none';
        }
        
        function openUnbanModal(userId, username) {
            document.getElementById('unban_user_id').value = userId;
            document.getElementById('unban_username').value = username;
            document.getElementById('unbanModal').style.display = 'block';
        }
        
        function closeUnbanModal() {
            document.getElementById('unbanModal').style.display = 'none';
        }
        
       
        window.onclick = function(event) {
            if (event.target === document.getElementById('actionModal')) {
                closeModal();
            }
            if (event.target === document.getElementById('unbanModal')) {
                closeUnbanModal();
            }
        }
    </script>
</body>
</html>