<?php
// entry.php
session_start();
date_default_timezone_set('Asia/Colombo');

// Check if user is logged in as admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../auth/login2.php');
    exit();
}

include __DIR__ . '/../config/db.php';
$current = basename($_SERVER['PHP_SELF']); // Detect active page

$message = "";
$suggestions = [];

// Auto-process: Remove vehicles that exceeded parking time (12+ hours)
$auto_exit_query = "
    SELECT v.*, TIMESTAMPDIFF(HOUR, v.entry_time, NOW()) as hours_parked
    FROM vehicles v
    WHERE TIMESTAMPDIFF(HOUR, v.entry_time, NOW()) >= 12
";
$auto_exit_result = $conn->query($auto_exit_query);
$auto_processed = 0;
if ($auto_exit_result && $auto_exit_result->num_rows > 0) {
    while ($v = $auto_exit_result->fetch_assoc()) {
        $duration = (time() - strtotime($v['entry_time'])) / 3600;
        $fee = $duration * 100;
        $exit_time = date('Y-m-d H:i:s');
        
        $conn->query("INSERT INTO exited_vehicles (vehicle_number, vehicle_type, slot_number, entry_time, exit_time, fee)
                      VALUES ('{$v['vehicle_number']}', '{$v['vehicle_type']}', '{$v['slot_number']}', 
                              '{$v['entry_time']}', '$exit_time', $fee)");
        $conn->query("DELETE FROM vehicles WHERE vehicle_number = '{$v['vehicle_number']}'");
        $conn->query("UPDATE parking_slots SET is_occupied = 0 WHERE slot_number = '{$v['slot_number']}'");
        $auto_processed++;
    }
}

// Get recent vehicles for auto-complete
$recent_query = $conn->query("
    SELECT DISTINCT vehicle_number, vehicle_type 
    FROM vehicles 
    ORDER BY entry_time DESC 
    LIMIT 10
");
while ($r = $recent_query->fetch_assoc()) {
    $suggestions[] = $r;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $vehicle_number = $conn->real_escape_string($_POST["vehicle_number"]);
    $vehicle_type   = $conn->real_escape_string($_POST["vehicle_type"]);

    // Find available slot
    $res = $conn->query("
        SELECT * 
          FROM parking_slots 
         WHERE vehicle_type='$vehicle_type' 
           AND is_occupied = 0 
         LIMIT 1
    ");

    if ($res && $res->num_rows > 0) {
        $slot        = $res->fetch_assoc();
        $slot_number = $slot['slot_number'];
        $entry_time  = date("Y-m-d H:i:s");

        $conn->query("
            INSERT INTO vehicles 
                (vehicle_number, vehicle_type, slot_number, entry_time)
            VALUES
                ('$vehicle_number','$vehicle_type','$slot_number','$entry_time')
        ");

        $conn->query("
            UPDATE parking_slots 
               SET is_occupied = 1 
             WHERE slot_number = '$slot_number'
        ");

        $message = "✅ <strong>" . strtoupper($vehicle_number) . 
                   "</strong> assigned to slot <strong>$slot_number</strong> at <strong>" 
                   . date("h:i A") . "</strong>.";
    } else {
        $message = "❌ No available slots for <strong>$vehicle_type</strong>.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Vehicle Entry</title>
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
      margin-bottom:10px;
      color:#2c3e50;
      font-size:32px;
      font-weight:800;
      letter-spacing:3px;
      text-transform:uppercase;
    }
    
    p{
      color:#7f8c8d;
      margin-bottom:30px;
      letter-spacing:1px;
      text-transform:uppercase;
      font-size:13px;
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
    
    input,select{
      width:100%;
      padding:16px 20px;
      border:2px solid rgba(102,126,234,0.2);
      border-radius:12px;
      font-size:15px;
      transition:all 0.3s ease;
      background:rgba(255,255,255,0.9);
    }
    
    input:focus,select:focus{
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
      margin-top:25px;
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
      margin-top:30px;
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

    
    .quick-entry {
      background:rgba(102,126,234,0.1);
      backdrop-filter:blur(10px);
      padding:25px;
      border-radius:15px;
      margin-bottom:30px;
      border:2px solid rgba(102,126,234,0.2);
    }
    
    .quick-entry h3 {
      margin-bottom:18px;
      color:#2c3e50;
      font-size:15px;
      font-weight:700;
      letter-spacing:2px;
      text-transform:uppercase;
    }
    
    .recent-vehicles {
      display:flex;
      flex-wrap:wrap;
      gap:12px;
    }
    
    .vehicle-chip {
      padding:10px 20px;
      background:white;
      border:2px solid #667eea;
      border-radius:25px;
      cursor:pointer;
      font-size:14px;
      color:#667eea;
      font-weight:600;
      transition:all 0.3s cubic-bezier(0.175,0.885,0.32,1.275);
      box-shadow:0 4px 15px rgba(102,126,234,0.2);
    }
    
    .vehicle-chip:hover {
      background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);
      color:white;
      transform:translateY(-3px);
      box-shadow:0 8px 25px rgba(102,126,234,0.4);
    }
    
    @media (max-width: 768px) {
      .header{padding:15px 20px}
      .logo{font-size:14px;padding:10px 18px;letter-spacing:1px}
      .nav{float:none;text-align:center;margin-top:15px}
      .nav a{margin:0 10px;font-size:12px;padding:6px 12px}
      .container{margin:20px;padding:30px}
      h1{font-size:24px;letter-spacing:2px}
      p{font-size:12px}
      .quick-entry{padding:18px}
      .vehicle-chip{padding:8px 14px;font-size:12px}
    }
    
    @media (max-width: 480px) {
      .container{margin:10px;padding:20px}
      .nav a{display:block;margin:8px 0}
      h1{font-size:20px}
      .btn{font-size:14px;padding:14px}
    }
    
    .auto-detect {
      background:linear-gradient(135deg,#56ab2f 0%,#a8e063 100%);
      color:white;
      padding:15px 20px;
      border-radius:12px;
      margin-bottom:25px;
      text-align:center;
      font-weight:700;
      letter-spacing:1px;
      text-transform:uppercase;
      font-size:13px;
      box-shadow:0 6px 20px rgba(86,171,47,0.3);
    }
  </style>
  <script>
    function quickEntry(vehicleNumber, vehicleType) {
      document.querySelector('input[name="vehicle_number"]').value = vehicleNumber;
      document.querySelector('select[name="vehicle_type"]').value = vehicleType;
      // Auto-submit the form
      document.querySelector('form').submit();
    }
    
    function detectVehicleType() {
      const vehicleNumber = document.querySelector('input[name="vehicle_number"]').value.toUpperCase();
      const vehicleTypeSelect = document.querySelector('select[name="vehicle_type"]');
      
      // Auto-detect based on common patterns
      if (vehicleNumber.includes('BIKE') || vehicleNumber.length <= 6) {
        vehicleTypeSelect.value = 'bike';
      } else if (vehicleNumber.includes('VAN') || vehicleNumber.includes('BUS')) {
        vehicleTypeSelect.value = 'van';
      } else {
        vehicleTypeSelect.value = 'car';
      }
    }
  </script>
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
  <h1>▲ VEHICLE ENTRY</h1>
  <p>● Enter vehicle details below</p>

  <?php if ($auto_processed > 0): ?>
  <div style="background:linear-gradient(135deg,#56ab2f 0%,#a8e063 100%);color:white;padding:18px 25px;border-radius:15px;margin-bottom:25px;text-align:center;font-weight:700;letter-spacing:1px;text-transform:uppercase;font-size:13px;box-shadow:0 8px 25px rgba(86,171,47,0.3)">
    ✓ Auto-removed <?= $auto_processed ?> vehicle(s) - Exceeded 12 hours
  </div>
  <?php endif; ?>

  <?php if (!empty($suggestions)): ?>
  <div class="quick-entry">
    <h3>■ QUICK ENTRY - RECENT VEHICLES</h3>
    <div class="recent-vehicles">
      <?php foreach($suggestions as $s): ?>
        <div class="vehicle-chip" onclick="quickEntry('<?= htmlspecialchars($s['vehicle_number']) ?>', '<?= htmlspecialchars($s['vehicle_type']) ?>')">
          <?= $s['vehicle_type'] == 'car' ? '🚗' : ($s['vehicle_type'] == 'bike' ? '🏍️' : '🚐') ?> 
          <?= htmlspecialchars($s['vehicle_number']) ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <div class="auto-detect">
    ● Vehicle type auto-detects based on license plate
  </div>

  <?php if (!empty($message)): ?>
    <div class="message"><?php echo $message; ?></div>
  <?php endif; ?>

  <form method="POST" action="">
    <div class="form-group">
      <label>License Plate Number *</label>
      <input type="text" name="vehicle_number" placeholder="Enter license plate number" required onblur="detectVehicleType()" style="text-transform:uppercase">
    </div>

    <div class="form-group">
      <label>Vehicle Type * <small>(Auto-detected)</small></label>
      <select name="vehicle_type" required>
        <option value="">-- Auto-detecting... --</option>
        <option value="car">Car</option>
        <option value="van">Van</option>
        <option value="jeep">Jeep</option>
        <option value="lorry">Lorry</option>
        <option value="threewheel">Three Wheel</option>
        <option value="bus">Bus</option>
        <option value="bike">Bike</option>
      </select>
    </div>

    <button class="btn" type="submit">Confirm Entry</button>
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
