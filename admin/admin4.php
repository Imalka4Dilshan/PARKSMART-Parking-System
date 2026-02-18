<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
date_default_timezone_set('Asia/Colombo');

// Check if user is logged in as admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../auth/login2.php');
    exit();
}

$current = basename($_SERVER['PHP_SELF']);
$message = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = trim($_POST['current_password'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    
    $password_file = dirname(__DIR__) . '/auth/password.txt';
    
    try {
        // Validate inputs first
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $message = '❌ All fields are required';
        } elseif (strlen($new_password) < 3) {
            $message = '❌ Password must be at least 3 characters';
        } elseif ($new_password !== $confirm_password) {
            $message = '❌ Passwords do not match';
        } else {
            // Check if password file exists
            if (!file_exists($password_file)) {
                $message = '❌ Password file not found at: ' . $password_file;
            } else {
                $stored_password = trim(file_get_contents($password_file));
                
                if ($current_password !== $stored_password) {
                    $message = '❌ Current password is incorrect';
                } else {
                    // Try to write new password
                    if (!is_writable($password_file)) {
                        $message = '❌ Password file is not writable. Right-click password.txt → Properties → Security → Edit → Give Users Full Control';
                    } else {
                        $write_result = @file_put_contents($password_file, $new_password);
                        if ($write_result !== false) {
                            $message = '✅ Password changed successfully! New password: ' . htmlspecialchars($new_password);
                        } else {
                            $error = error_get_last();
                            $message = '❌ Failed to write to file. Error: ' . ($error ? $error['message'] : 'Unknown error');
                        }
                    }
                }
            }
        }
    } catch (Exception $e) {
        $message = '❌ Exception: ' . $e->getMessage();
    } catch (Throwable $t) {
        $message = '❌ Fatal Error: ' . $t->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin Settings - ParkSmart</title>
  <style>
    *{margin:0;padding:0;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;box-sizing:border-box}
    body{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;padding-bottom:40px}
    
    .header{
      background:rgba(255,255,255,0.95);
      backdrop-filter:blur(10px);
      padding:20px 30px;
      box-shadow:0 8px 32px rgba(0,0,0,0.1);
      overflow:hidden;
      border-bottom:3px solid rgba(102,126,234,0.3);
      position:sticky;
      top:0;
      z-index:1000;
    }
    
    .logo{
      background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);
      color:#fff;
      padding:12px 24px;
      border-radius:10px;
      display:inline-block;
      font-size:18px;
      font-weight:700;
      letter-spacing:2px;
      text-transform:uppercase;
      box-shadow:0 4px 15px rgba(102,126,234,0.4);
    }
    
    .nav{float:right;margin-top:8px}
    .nav a{
      margin-left:30px;
      text-decoration:none;
      color:#555;
      font-size:15px;
      font-weight:600;
      letter-spacing:0.5px;
      text-transform:uppercase;
      transition:all 0.3s ease;
      padding:8px 16px;
      border-radius:8px;
    }
    .nav a:hover{background:rgba(102,126,234,0.1);color:#667eea}
    .nav a.active{color:#fff;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);box-shadow:0 4px 15px rgba(102,126,234,0.3)}
    
    .container{
      max-width:700px;
      margin:40px auto;
      background:rgba(255,255,255,0.95);
      backdrop-filter:blur(10px);
      padding:50px;
      border-radius:20px;
      box-shadow:0 20px 60px rgba(0,0,0,0.2);
    }
    
    h1{
      margin-bottom:10px;
      color:#2c3e50;
      font-size:32px;
      font-weight:800;
      letter-spacing:3px;
      text-transform:uppercase;
    }
    
    h2{
      margin-bottom:25px;
      color:#2c3e50;
      font-size:18px;
      font-weight:700;
      letter-spacing:2px;
      text-transform:uppercase;
    }
    
    p{
      color:#7f8c8d;
      margin-bottom:30px;
      letter-spacing:0.5px;
    }
    
    .form-group{margin:25px 0}
    label{
      display:block;
      margin-bottom:10px;
      color:#2c3e50;
      font-weight:700;
      letter-spacing:1px;
      text-transform:uppercase;
      font-size:13px;
    }
    
    input{
      width:100%;
      padding:16px 20px;
      border:2px solid rgba(102,126,234,0.2);
      border-radius:12px;
      font-size:15px;
      transition:all 0.3s ease;
      background:rgba(255,255,255,0.9);
    }
    
    input:focus{
      outline:none;
      border-color:#667eea;
      box-shadow:0 0 0 3px rgba(102,126,234,0.1);
      transform:translateY(-2px);
    }
    
    .btn{
      background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);
      color:#fff;
      padding:18px;
      width:100%;
      border:none;
      border-radius:12px;
      font-size:16px;
      font-weight:700;
      letter-spacing:2px;
      text-transform:uppercase;
      cursor:pointer;
      margin-top:30px;
      transition:all 0.4s cubic-bezier(0.175,0.885,0.32,1.275);
      box-shadow:0 10px 30px rgba(102,126,234,0.3);
      position:relative;
      overflow:hidden;
    }
    
    .btn::before{
      content:'';
      position:absolute;
      top:0;
      left:-100%;
      width:100%;
      height:100%;
      background:rgba(255,255,255,0.2);
      transition:left 0.5s ease;
    }
    
    .btn:hover::before{left:100%}
    .btn:hover{
      transform:translateY(-3px);
      box-shadow:0 15px 40px rgba(102,126,234,0.4);
    }
    
    .message{
      margin-bottom:25px;
      padding:20px 25px;
      border-radius:15px;
      text-align:center;
      font-weight:700;
      letter-spacing:0.5px;
      box-shadow:0 8px 25px rgba(0,0,0,0.15);
    }
    
    .logout-box {
      text-align:center;
      margin: 60px 0 0 0;
    }
    .logout-box form {
      display:inline-block;
    }
    .logout-box button {
      background:linear-gradient(135deg,#e53935 0%,#c62828 100%);
      color:#fff;
      border:none;
      padding:16px 40px;
      font-size:16px;
      font-weight:700;
      letter-spacing:2px;
      text-transform:uppercase;
      border-radius:15px;
      cursor:pointer;
      transition: all 0.4s cubic-bezier(0.175,0.885,0.32,1.275);
      box-shadow:0 10px 30px rgba(229,57,53,0.3);
    }
    .logout-box button:hover {
      transform:translateY(-5px);
      box-shadow:0 15px 40px rgba(229,57,53,0.4);
    }
    
    .info-box {
      background:rgba(102,126,234,0.1);
      backdrop-filter:blur(10px);
      padding:25px;
      border-radius:15px;
      margin-bottom:35px;
      border:2px solid rgba(102,126,234,0.2);
    }
    .info-box h3 {
      color:#2c3e50;
      margin-bottom:15px;
      font-weight:700;
      letter-spacing:2px;
      text-transform:uppercase;
      font-size:15px;
    }
    .info-box p {
      margin:8px 0;
      color:#555;
      font-size:14px;
    }
    
    @media (max-width: 768px) {
      .header{padding:15px 20px}
      .logo{font-size:14px;padding:10px 18px;letter-spacing:1px}
      .nav{float:none;text-align:center;margin-top:15px}
      .nav a{margin:0 10px;font-size:12px;padding:6px 12px}
      .container{margin:20px;padding:30px}
      h1{font-size:24px;letter-spacing:2px}
      h2{font-size:16px}
    }
    
    @media (max-width: 480px) {
      .container{margin:10px;padding:20px}
      .nav a{display:block;margin:8px 0}
      h1{font-size:20px}
      .btn{font-size:14px;padding:14px}
    }
  </style>
</head>
<body>

<div class="header">
  <span class="logo">ParkSmart</span>
  <div class="nav">
    <a href="dash4.php" class="<?= $current=='dash4.php'?'active':'' ?>">Dashboard</a>
    <a href="entry7.php" class="<?= $current=='entry7.php'?'active':'' ?>">Entry</a>
    <a href="exit.php" class="<?= $current=='exit.php'?'active':'' ?>">Exit</a>
    <a href="reviews.php" class="<?= $current=='reviews.php'?'active':'' ?>">Reviews</a>
    <a href="admin4.php" class="<?= $current=='admin4.php'?'active':'' ?>">Admin</a>
  </div>
  <div style="clear:both"></div>
</div>

<div class="container">
  <h1>■ ADMIN SETTINGS</h1>
  <p>Manage your admin account settings</p>

  <?php
  // Debug information
  if (isset($_POST['change_password'])) {
      echo '<div style="background:#fff3cd;padding:15px;border-radius:12px;margin-bottom:20px;border:2px solid #ffc107;">';
      echo '<h3 style="color:#856404;margin:0 0 10px 0;">🔍 DEBUG INFO:</h3>';
      echo '<p style="color:#856404;margin:5px 0;font-size:13px;"><strong>Form Submitted:</strong> Yes</p>';
      echo '<p style="color:#856404;margin:5px 0;font-size:13px;"><strong>POST Data Received:</strong> ' . (empty($_POST) ? 'No' : 'Yes') . '</p>';
      echo '<p style="color:#856404;margin:5px 0;font-size:13px;"><strong>Password File Path:</strong> ' . htmlspecialchars(dirname(__DIR__) . '/auth/password.txt') . '</p>';
      $pf = dirname(__DIR__) . '/auth/password.txt';
      echo '<p style="color:#856404;margin:5px 0;font-size:13px;"><strong>File Exists:</strong> ' . (file_exists($pf) ? 'Yes' : 'No') . '</p>';
      echo '<p style="color:#856404;margin:5px 0;font-size:13px;"><strong>File Writable:</strong> ' . (is_writable($pf) ? 'Yes' : 'No') . '</p>';
      echo '</div>';
  }
  ?>

  <div class="info-box">
    <h3>● ACCOUNT INFORMATION</h3>
    <p><strong>Email:</strong> admin@gmail.com</p>
    <p><strong>Role:</strong> Administrator</p>
  </div>

  <?php
  // Check password file status
  $password_file = __DIR__ . '/../auth/password.txt';
  $file_status = '';
  if (!file_exists($password_file)) {
      $file_status = '<span style="color:#e74c3c;">⚠ Password file not found</span>';
  } elseif (!is_writable($password_file)) {
      $file_status = '<span style="color:#e67e22;">⚠ Password file not writable - check permissions</span> | <a href="test_password_file.php" style="color:#667eea;">Run Diagnostic Test</a>';
  } else {
      $file_status = '<span style="color:#27ae60;">✓ Password file OK</span> | Current: <strong>' . htmlspecialchars(trim(file_get_contents($password_file))) . '</strong>';
  }
  ?>
  
  <div class="info-box" style="background:rgba(52,152,219,0.1);border-color:rgba(52,152,219,0.3);">
    <h3>● SYSTEM STATUS</h3>
    <p><strong>Password File:</strong> <?= $file_status ?></p>
    <p style="font-size:12px;color:#7f8c8d;margin-top:10px;">
      <a href="test_password_file.php" style="color:#667eea;text-decoration:none;font-weight:600;">→ Run Full Diagnostic Test</a> | 
      <a href="simple_password_change.php" style="color:#667eea;text-decoration:none;font-weight:600;">→ Simple Password Changer</a>
    </p>
  </div>

  <?php if (!empty($message)): ?>
    <div class="message" style="<?= strpos($message, '✅') !== false || strpos($message, '✓') !== false ? 'background:linear-gradient(135deg,#56ab2f 0%,#a8e063 100%);color:#fff' : 'background:linear-gradient(135deg,#f093fb 0%,#f5576c 100%);color:#fff' ?>"><?php echo $message; ?></div>
  <?php endif; ?>

  <h2>▲ CHANGE PASSWORD</h2>
  
  <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
    <div class="form-group">
      <label>Current Password</label>
      <input type="password" name="current_password" placeholder="Enter current password (default: 123)" required autocomplete="current-password">
    </div>

    <div class="form-group">
      <label>New Password</label>
      <input type="password" name="new_password" placeholder="Enter new password (min 3 characters)" required autocomplete="new-password" minlength="3">
    </div>

    <div class="form-group">
      <label>Confirm New Password</label>
      <input type="password" name="confirm_password" placeholder="Confirm new password" required autocomplete="new-password" minlength="3">
    </div>

    <button class="btn" type="submit" name="change_password" value="1">● UPDATE PASSWORD</button>
  </form>

  <div class="logout-box">
    <form action="../auth/logout.php" method="post">
      <button type="submit">■ LOGOUT</button>
    </form>
  </div>
</div>

</body>
</html>