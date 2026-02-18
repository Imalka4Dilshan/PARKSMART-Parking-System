<?php
session_start();
date_default_timezone_set('Asia/Colombo');

// Check if user is logged in as admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../auth/login2.php');
    exit();
}

include __DIR__ . '/../config/db.php';

$current = basename($_SERVER['PHP_SELF']);

$message = "";
$vehicle = null;
$duration = "";
$fee = 0;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search'])) {
    $vehicle_number = $conn->real_escape_string($_POST["vehicle_number"]);

    $sql = "SELECT * FROM vehicles WHERE vehicle_number='$vehicle_number'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $vehicle = $result->fetch_assoc();
        $entry_time = new DateTime($vehicle['entry_time']);
        $exit_time = new DateTime();

        $interval = $entry_time->diff($exit_time);
        $duration = $interval->format('%h hours %i minutes');

        $vehicle_type = strtolower($vehicle['vehicle_type']);
        $base_rates = ['bike' => 100, 'van' => 150, 'car' => 200];
        $base_fee = $base_rates[$vehicle_type] ?? 100;

        $hours = $interval->h + ($interval->days * 24) + ($interval->i > 0 ? 1 : 0);
        if ($hours <= 0) $hours = 1; // ✅ Force minimum 1 hour fee

        $fee = $base_fee + max(0, ($hours - 1)) * 50;
    } else {
        $message = "❌ Vehicle not found.";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_exit'])) {
    $vehicle_number = $conn->real_escape_string($_POST["vehicle_number"]);

    $sql = "SELECT * FROM vehicles WHERE vehicle_number='$vehicle_number'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $vehicle = $result->fetch_assoc();
        $slot_number = $vehicle['slot_number'];
        $entry_time = new DateTime($vehicle['entry_time']);
        $exit_time = new DateTime();

        $interval = $entry_time->diff($exit_time);
        $hours = $interval->h + ($interval->days * 24) + ($interval->i > 0 ? 1 : 0);
        if ($hours <= 0) $hours = 1; // ✅ Force minimum 1 hour fee

        $vehicle_type = strtolower($vehicle['vehicle_type']);
        $base_rates = ['bike' => 100, 'van' => 150, 'car' => 200];
        $base_fee = $base_rates[$vehicle_type] ?? 100;
        $fee = $base_fee + max(0, ($hours - 1)) * 50;

        $exit_time_str = $exit_time->format("Y-m-d H:i:s");

        $conn->query("UPDATE parking_slots SET is_occupied=0 WHERE slot_number='$slot_number'");
        $conn->query("INSERT INTO exited_vehicles (vehicle_number, vehicle_type, slot_number, entry_time, exit_time, fee) VALUES ('{$vehicle['vehicle_number']}', '{$vehicle['vehicle_type']}', '$slot_number', '{$vehicle['entry_time']}', '$exit_time_str', $fee)");
        $conn->query("DELETE FROM vehicles WHERE vehicle_number='$vehicle_number'");

        $vehicle_type = ucfirst($vehicle['vehicle_type']);
        $message = "✅ <strong>$vehicle_type</strong> with plate <strong>$vehicle_number</strong> exited successfully. Slot <strong>$slot_number</strong> is now free.";
        $vehicle = null;
    } else {
        $message = "❌ Vehicle not found.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Vehicle Exit</title>
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
      max-width:600px;
      margin:40px auto;
      background:rgba(255,255,255,0.95);
      backdrop-filter:blur(10px);
      padding:50px;
      border-radius:20px;
      text-align:center;
      box-shadow:0 20px 60px rgba(0,0,0,0.2);
    }
    
    h1{
      margin-bottom:30px;
      color:#2c3e50;
      font-size:32px;
      font-weight:800;
      letter-spacing:3px;
      text-transform:uppercase;
    }
    
    .form-group{margin:25px 0;text-align:left}
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
    
    .details{
      margin:35px 0;
      text-align:left;
      background:rgba(102,126,234,0.05);
      padding:25px;
      border-radius:15px;
      border:2px solid rgba(102,126,234,0.1);
    }
    
    .row{
      display:flex;
      justify-content:space-between;
      padding:15px 0;
      border-bottom:2px solid rgba(102,126,234,0.1);
      flex-wrap:wrap;
      transition:all 0.3s ease;
    }
    
    .row:hover{
      background:rgba(102,126,234,0.05);
      padding-left:10px;
      border-radius:8px;
    }
    
    .row:last-child{
      border-bottom:none;
      font-weight:700;
      color:#667eea;
      font-size:18px;
      margin-top:10px;
      padding-top:20px;
      border-top:3px solid #667eea;
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
      margin:25px 0;
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
    
    .note{
      color:#7f8c8d;
      font-size:12px;
      letter-spacing:0.5px;
      margin-top:15px;
    }
    
    .message{
      margin:25px 0;
      padding:20px 25px;
      background:linear-gradient(135deg,#56ab2f 0%,#a8e063 100%);
      color:#fff;
      font-weight:700;
      font-size:15px;
      border-radius:15px;
      text-align:center;
      box-shadow:0 10px 30px rgba(86,171,47,0.3);
      letter-spacing:0.5px;
    }

    /* Logout button */
    .logout-box {
      text-align:center;
      margin: 60px 0 20px 0;
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

    
    @media (max-width: 768px) {
      .header{padding:15px 20px}
      .logo{font-size:14px;padding:10px 18px;letter-spacing:1px}
      .nav{float:none;text-align:center;margin-top:15px}
      .nav a{margin:0 10px;font-size:12px;padding:6px 12px}
      .container{margin:20px;padding:30px}
      h1{font-size:24px;letter-spacing:2px}
      .details{margin:25px 0;padding:20px}
      .row{gap:5px;font-size:14px;padding:12px 0}
    }
    
    @media (max-width: 480px) {
      .container{margin:10px;padding:20px}
      .nav a{display:block;margin:8px 0}
      .btn{padding:14px;font-size:14px}
      h1{font-size:20px}
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
  <h1>▼ VEHICLE EXIT</h1>

  <?php if (!empty($message)): ?>
    <div class="message"><?php echo $message; ?></div>
  <?php endif; ?>

  <form method="POST" action="">
    <div class="form-group">
      <label>License Plate Number</label>
      <input type="text" name="vehicle_number" placeholder="Enter or scan license plate"
             value="<?php echo htmlspecialchars($_POST['vehicle_number'] ?? ''); ?>" required>
    </div>

    <?php if ($vehicle): ?>
      <div class="details">
        <div class="row">
          <span>Entry Time</span>
          <span><?php echo date("h:i A", strtotime($vehicle['entry_time'])); ?></span>
        </div>
        <div class="row">
          <span>Exit Time</span>
          <span><?php echo date("h:i A"); ?></span>
        </div>
        <div class="row">
          <span>Duration</span>
          <span><?php echo $duration; ?></span>
        </div>
        <div class="row">
          <span>Parking Fee</span>
          <span>Rs.<?php echo $fee; ?></span>
        </div>
      </div>
      <button class="btn" type="submit" name="confirm_exit">Confirm Exit</button>
    <?php else: ?>
      <button class="btn" type="submit" name="search">Find Vehicle</button>
    <?php endif; ?>

    <p class="note">Please ensure all details are correct before confirming</p>
  </form>

      <!-- Logout Button -->
  <div class="logout-box">
    <form action="../auth/logout.php" method="post">
      <button type="submit">■ SYSTEM LOGOUT</button>
    </form>
  </div>


</div>

</body>
</html>
