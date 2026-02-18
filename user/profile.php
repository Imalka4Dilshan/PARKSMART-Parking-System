<?php
session_start();
date_default_timezone_set('Asia/Colombo');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login2.php');
    exit();
}

include __DIR__ . '/../config/db.php';
$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    
    if (password_verify($current_password, $user['password'])) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $user_id);
        $stmt->execute();
        $success_message = "Password changed successfully!";
    } else {
        $error_message = "Current password is incorrect";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - ParkSmart</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding-bottom: 40px;
        }

        .profile-header {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            color: #2c3e50;
            padding: 35px 20px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            border-bottom: 3px solid rgba(102,126,234,0.3);
        }

        .profile-avatar {
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 44px;
            color: white;
            margin: 0 auto 18px;
            box-shadow: 0 8px 25px rgba(102,126,234,0.4);
        }

        .profile-name {
            font-size: 26px;
            font-weight: 800;
            margin-bottom: 6px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .profile-email {
            font-size: 15px;
            opacity: 0.7;
            letter-spacing: 0.5px;
        }

        .container {
            padding: 30px 20px;
            max-width: 650px;
            margin: 0 auto;
        }

        .section-card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            margin-bottom: 25px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 20px;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 14px 0;
            border-bottom: 1px solid rgba(102,126,234,0.15);
            transition: background 0.3s ease;
        }

        .info-row:hover {
            background: rgba(102,126,234,0.05);
            border-radius: 8px;
            padding-left: 12px;
            padding-right: 12px;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-size: 14px;
            color: #7f8c8d;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 13px;
        }

        .info-value {
            font-size: 15px;
            font-weight: 700;
            color: #2c3e50;
        }

        .input-group {
            margin-bottom: 20px;
        }

        .input-label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .input-field {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid rgba(102,126,234,0.2);
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: rgba(255,255,255,0.8);
        }

        .input-field:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 5px 20px rgba(102,126,234,0.3);
            transform: translateY(-2px);
        }

        .btn-primary {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 8px 25px rgba(102,126,234,0.4);
            position: relative;
            overflow: hidden;
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.2);
            transition: left 0.5s ease;
        }

        .btn-primary:hover::before {
            left: 100%;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(102,126,234,0.5);
        }

        .btn-primary:active {
            transform: translateY(-1px);
        }

        .btn-logout {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            cursor: pointer;
            text-decoration: none;
            display: block;
            text-align: center;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 8px 25px rgba(231,76,60,0.4);
            position: relative;
            overflow: hidden;
        }

        .btn-logout::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.2);
            transition: left 0.5s ease;
        }

        .btn-logout:hover::before {
            left: 100%;
        }

        .btn-logout:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(231,76,60,0.5);
        }

        .btn-logout:active {
            transform: translateY(-1px);
        }

        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .alert-success {
            background: rgba(46,213,115,0.15);
            color: #27ae60;
            border-left: 4px solid #27ae60;
        }

        .alert-error {
            background: rgba(231,76,60,0.15);
            color: #c0392b;
            border-left: 4px solid #c0392b;
        }

        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-top: 2px solid rgba(102,126,234,0.2);
            padding: 12px 20px;
            display: flex;
            justify-content: space-around;
            box-shadow: 0 -10px 30px rgba(0,0,0,0.15);
        }

        .nav-item {
            text-align: center;
            cursor: pointer;
            text-decoration: none;
            color: #7f8c8d;
            flex: 1;
            transition: all 0.3s ease;
            padding: 8px;
            border-radius: 12px;
        }

        .nav-item:hover {
            background: rgba(102,126,234,0.1);
            color: #667eea;
        }

        .nav-item.active {
            color: #667eea;
            font-weight: 700;
        }

        .nav-icon {
            font-size: 24px;
            margin-bottom: 4px;
        }

        .nav-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>
    <?php include 'user_nav.php'; ?>
    
    <div class="profile-header">
        <div class="profile-avatar">👤</div>
        <div class="profile-name"><?php echo htmlspecialchars($user['name']); ?></div>
        <div class="profile-email"><?php echo htmlspecialchars($user['email']); ?></div>
    </div>

    <div class="container">
        <div class="section-card">
            <div class="section-title">Account Information</div>
            <div class="info-row">
                <span class="info-label">Full Name</span>
                <span class="info-value"><?php echo htmlspecialchars($user['name']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Email</span>
                <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Phone</span>
                <span class="info-value"><?php echo htmlspecialchars($user['phone']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Member Since</span>
                <span class="info-value"><?php echo date('d M Y', strtotime($user['created_at'])); ?></span>
            </div>
        </div>

        <div class="section-card">
            <div class="section-title">Change Password</div>
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if (isset($error_message)): ?>
                <div class="alert alert-error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="input-group">
                    <label class="input-label">Current Password</label>
                    <input type="password" name="current_password" class="input-field" required>
                </div>
                <div class="input-group">
                    <label class="input-label">New Password</label>
                    <input type="password" name="new_password" class="input-field" required>
                </div>
                <button type="submit" name="change_password" class="btn-primary">Update Password</button>
            </form>
        </div>

        <div class="section-card">
            <a href="../auth/logout.php" class="btn-logout">Logout</a>
        </div>
    </div>

    <div class="bottom-nav">
        <a href="dashboard.php" class="nav-item">
            <div class="nav-icon">🏠</div>
            <div class="nav-label">Home</div>
        </a>
        <a href="slot_view.php" class="nav-item">
            <div class="nav-icon">🅿️</div>
            <div class="nav-label">Slots</div>
        </a>
        <a href="bookings_history.php" class="nav-item">
            <div class="nav-icon">📋</div>
            <div class="nav-label">Bookings</div>
        </a>
        <a href="profile.php" class="nav-item active">
            <div class="nav-icon">👤</div>
            <div class="nav-label">Profile</div>
        </a>
    </div>
</body>
</html>
