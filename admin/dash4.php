<?php 
session_start();
date_default_timezone_set('Asia/Colombo');

// Check if user is logged in as admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../auth/login2.php');
    exit();
}

// DB connection
include __DIR__ . '/../config/db.php';

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

$total_sql    = $conn->query("SELECT COUNT(*) AS total FROM parking_slots");
$total        = $total_sql->fetch_assoc()['total'] ?? 0;

$occupied_sql = $conn->query("SELECT COUNT(*) AS occupied FROM parking_slots WHERE is_occupied=1");
$occupied     = $occupied_sql->fetch_assoc()['occupied'] ?? 0;

$available    = $total - $occupied;

// Revenue calculation (today's exits)
$revenue_sql = $conn->query("SELECT SUM(fee) AS total_revenue FROM exited_vehicles WHERE DATE(exit_time) = CURDATE()");
$revenue = $revenue_sql->fetch_assoc()['total_revenue'] ?? 0;

// Count today's exits
$exits_sql = $conn->query("SELECT COUNT(*) AS total_exits FROM exited_vehicles WHERE DATE(exit_time) = CURDATE()");
$total_exits = $exits_sql->fetch_assoc()['total_exits'] ?? 0;

// Count currently parked vehicles
$parked_sql = $conn->query("SELECT COUNT(*) AS parked FROM vehicles");
$parked_vehicles = $parked_sql->fetch_assoc()['parked'] ?? 0;

// Lists
$available_slots = [];
$avail_res       = $conn->query("SELECT slot_number, vehicle_type FROM parking_slots WHERE is_occupied=0 ORDER BY slot_number");
while ($r = $avail_res->fetch_assoc()) $available_slots[] = $r;

$occupied_slots = [];
$occ_res        = $conn->query("SELECT slot_number, vehicle_type FROM parking_slots WHERE is_occupied=1 ORDER BY slot_number");
while ($r = $occ_res->fetch_assoc()) $occupied_slots[] = $r;

$recent_activity = [];
$recent_sql      = $conn->query("SELECT vehicle_number, slot_number, entry_time FROM vehicles ORDER BY entry_time DESC LIMIT 5");
while ($r = $recent_sql->fetch_assoc()) $recent_activity[] = $r;

// Recent exits
$recent_exits = [];
$exits_query = $conn->query("SELECT vehicle_number, entry_time, exit_time, fee FROM exited_vehicles ORDER BY exit_time DESC LIMIT 5");
while ($r = $exits_query->fetch_assoc()) {
    // Calculate duration
    $entry = new DateTime($r['entry_time']);
    $exit = new DateTime($r['exit_time']);
    $interval = $entry->diff($exit);
    $r['duration'] = $interval->format('%h hr %i min');
    $recent_exits[] = $r;
}

$current = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ParkSmart Dashboard</title>
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
    
    .container{max-width:1400px;margin:0 auto;padding:30px}
    
    .stats{
      display:grid;
      grid-template-columns:repeat(auto-fit,minmax(280px,1fr));
      gap:25px;
      margin:30px 0;
    }
    
    .stat{
      padding:35px 30px;
      text-align:center;
      border-radius:20px;
      cursor:pointer;
      user-select:none;
      transition:all 0.4s cubic-bezier(0.175,0.885,0.32,1.275);
      box-shadow:0 10px 40px rgba(0,0,0,0.15);
      position:relative;
      overflow:hidden;
    }
    
    .stat::before{
      content:'';
      position:absolute;
      top:0;
      left:0;
      right:0;
      bottom:0;
      background:rgba(255,255,255,0.1);
      opacity:0;
      transition:opacity 0.3s ease;
    }
    
    .stat:hover::before{opacity:1}
    
    .stat:hover{
      transform:translateY(-8px) scale(1.02);
      box-shadow:0 20px 60px rgba(0,0,0,0.25);
    }
    
    .stat > div:first-child{
      font-size:13px;
      letter-spacing:2px;
      text-transform:uppercase;
      font-weight:700;
      opacity:0.9;
      margin-bottom:12px;
    }
    
    .stat h2{
      font-size:56px;
      font-weight:800;
      margin:0;
      letter-spacing:-1px;
    }
    
    .total{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff}
    .available{background:linear-gradient(135deg,#56ab2f 0%,#a8e063 100%);color:#fff}
    .occupied{background:linear-gradient(135deg,#f093fb 0%,#f5576c 100%);color:#fff}
    
    .buttons{
      display:grid;
      grid-template-columns:repeat(auto-fit,minmax(300px,1fr));
      gap:25px;
      margin:40px 0;
    }
    
    .btn{
      padding:40px 30px;
      border:none;
      border-radius:20px;
      color:#fff;
      font-size:20px;
      font-weight:700;
      letter-spacing:1px;
      text-transform:uppercase;
      cursor:pointer;
      display:flex;
      align-items:center;
      justify-content:center;
      text-decoration:none;
      transition:all 0.4s cubic-bezier(0.175,0.885,0.32,1.275);
      box-shadow:0 10px 40px rgba(0,0,0,0.2);
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
    .btn:hover{transform:translateY(-5px);box-shadow:0 15px 50px rgba(0,0,0,0.3)}
    
    .entry{background:linear-gradient(135deg,#2196f3 0%,#1976d2 100%)}
    .exit{background:linear-gradient(135deg,#9c27b0 0%,#7b1fa2 100%)}
    
    .activity{
      background:rgba(255,255,255,0.95);
      backdrop-filter:blur(10px);
      padding:30px;
      border-radius:20px;
      margin-top:30px;
      box-shadow:0 10px 40px rgba(0,0,0,0.1);
    }
    
    .activity h2{
      color:#2c3e50;
      font-size:20px;
      letter-spacing:2px;
      text-transform:uppercase;
      margin-bottom:20px;
      font-weight:700;
      border-bottom:3px solid #667eea;
      padding-bottom:12px;
    }
    
    .row{
      display:flex;
      justify-content:space-between;
      padding:18px 15px;
      border-bottom:1px solid rgba(0,0,0,0.06);
      flex-wrap:wrap;
      gap:15px;
      transition:all 0.3s ease;
      border-radius:10px;
    }
    
    .row:hover{background:rgba(102,126,234,0.05);transform:translateX(5px)}
    
    .tag{
      padding:6px 14px;
      border-radius:20px;
      font-size:12px;
      font-weight:700;
      letter-spacing:1px;
      text-transform:uppercase;
    }
    
    .green{background:linear-gradient(135deg,#56ab2f 0%,#a8e063 100%);color:#fff}
    .orange{background:linear-gradient(135deg,#f093fb 0%,#f5576c 100%);color:#fff}
    
    .slotlist{
      margin-top:30px;
      background:rgba(255,255,255,0.95);
      backdrop-filter:blur(10px);
      padding:30px;
      border-radius:20px;
      display:none;
      box-shadow:0 10px 40px rgba(0,0,0,0.15);
      animation:slideIn 0.5s ease;
    }
    
    @keyframes slideIn{
      from{opacity:0;transform:translateY(30px)}
      to{opacity:1;transform:translateY(0)}
    }
    
    .slotlist.active{display:block}
    
    .slotlist h2{
      margin-bottom:20px;
      color:#2c3e50;
      font-size:22px;
      letter-spacing:2px;
      text-transform:uppercase;
      font-weight:700;
      border-bottom:3px solid #667eea;
      padding-bottom:12px;
    }
    
    .slotlist h3{
      margin:25px 0 15px;
      color:#2c3e50;
      font-size:16px;
      letter-spacing:2px;
      text-transform:uppercase;
      font-weight:700;
    }
    
    .back-btn{text-align:right;margin-top:-10px;margin-bottom:15px}
    .back-btn button{
      background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);
      color:#fff;
      border:none;
      padding:10px 24px;
      border-radius:10px;
      cursor:pointer;
      font-weight:700;
      letter-spacing:1px;
      text-transform:uppercase;
      font-size:13px;
      transition:all 0.3s ease;
      box-shadow:0 4px 15px rgba(102,126,234,0.3);
    }
    .back-btn button:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(102,126,234,0.4)}

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
      .container{padding:20px 15px}
      .stats{gap:15px;grid-template-columns:1fr}
      .stat{padding:25px 20px}
      .stat h2{font-size:42px}
      .buttons{gap:15px;grid-template-columns:1fr}
      .btn{padding:30px 20px;font-size:16px}
      .activity{padding:20px}
      .row{font-size:14px;padding:15px 10px}
      .slotlist{padding:20px}
    }
    
    @media (max-width: 480px) {
      .logo{font-size:12px;padding:8px 14px}
      .nav a{display:block;margin:8px 0}
      .stat h2{font-size:38px}
      .btn{font-size:14px;padding:25px 15px}
    }
  </style>
  <script>
    let currentVisible = null;
    function toggleList(id) {
      const section = document.getElementById(id);
      if (currentVisible === id) {
        section.classList.remove('active');
        currentVisible = null;
      } else {
        document.querySelectorAll('.slotlist').forEach(div => div.classList.remove('active'));
        section.classList.add('active');
        currentVisible = id;
      }
    }

    function closeAll() {
      document.querySelectorAll('.slotlist').forEach(div => div.classList.remove('active'));
      currentVisible = null;
    }
  </script>
</head>
<body>

<div class="header">
  <span class="logo">ParkSmart</span>
  <div class="nav">
    <a href="dash4.php"   class="<?= $current=='dash4.php'   ?'active':'' ?>">Dashboard</a>
    <a href="entry7.php"  class="<?= $current=='entry7.php'  ?'active':'' ?>">Entry</a>
    <a href="exit.php"    class="<?= $current=='exit.php'    ?'active':'' ?>">Exit</a>
    <a href="reviews.php" class="<?= $current=='reviews.php' ?'active':'' ?>">Reviews</a>
    <a href="admin4.php"  class="<?= $current=='admin4.php'  ?'active':'' ?>">Admin</a>
  </div>
  <div style="clear:both"></div>
</div>

<div class="container">
  <h1 style="color:#fff;font-size:38px;font-weight:800;letter-spacing:3px;text-transform:uppercase;text-align:center;margin:30px 0;text-shadow:0 4px 15px rgba(0,0,0,0.3)">■ PARKING OVERVIEW</h1>

  <?php if ($auto_processed > 0): ?>
  <div style="background:linear-gradient(135deg,#28a745 0%,#20c997 100%);color:white;padding:20px 25px;border-radius:15px;margin:25px 0;text-align:center;font-weight:700;letter-spacing:1px;text-transform:uppercase;box-shadow:0 10px 30px rgba(40,167,69,0.3)">
    ✓ Auto-processed <?= $auto_processed ?> vehicle(s) - Exceeded 12 hours parking time
  </div>
  <?php endif; ?>

  <div class="stats">
    <div class="stat total" onclick="toggleList('totalSlotsList')">
      <div>Total Spaces</div>
      <h2><?= $total ?></h2>
    </div>
    <div class="stat available" onclick="toggleList('availableSlotsList')">
      <div>Available</div>
      <h2><?= $available ?></h2>
    </div>
    <div class="stat occupied" onclick="toggleList('occupiedSlotsList')">
      <div>Occupied</div>
      <h2><?= $occupied ?></h2>
    </div>
  </div>

  <!-- Additional Stats Row -->
  <div class="stats" style="margin-top:25px">
    <div class="stat" style="background:linear-gradient(135deg,#4caf50 0%,#45a049 100%);color:#fff" onclick="toggleList('nowParkingList')">
      <div>Now Parking</div>
      <h2><?= $parked_vehicles ?></h2>
    </div>
    <div class="stat" style="background:linear-gradient(135deg,#ff9800 0%,#f57c00 100%);color:#fff" onclick="toggleList('todayExitsList')">
      <div>Today's Exits</div>
      <h2><?= $total_exits ?></h2>
    </div>
    <div class="stat" style="background:linear-gradient(135deg,#9c27b0 0%,#7b1fa2 100%);color:#fff" onclick="toggleList('todayRevenueList')">
      <div>Today's Revenue</div>
      <h2>Rs.<?= number_format($revenue, 2) ?></h2>
    </div>
  </div>

  <!-- Total Slots -->
  <div id="totalSlotsList" class="slotlist">
    <div class="back-btn"><button onclick="closeAll()">← BACK</button></div>
    <h2>■ TOTAL SLOTS (<?= $total ?>)</h2>
    <div class="row" style="font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#2c3e50;font-size:13px"><span>Slot</span><span>Type</span><span>Status</span></div>
    <?php 
    $all = $conn->query("SELECT slot_number,vehicle_type,is_occupied FROM parking_slots ORDER BY vehicle_type, slot_number");
    if ($all && $all->num_rows > 0):
      while($r=$all->fetch_assoc()): 
        $emoji = '🚗';
        if ($r['vehicle_type'] == 'car') $emoji = '🚗';
        else if ($r['vehicle_type'] == 'van') $emoji = '🚐';
        else if ($r['vehicle_type'] == 'jeep') $emoji = '🚙';
        else if ($r['vehicle_type'] == 'lorry') $emoji = '🚚';
        else if ($r['vehicle_type'] == 'threewheel') $emoji = '🛺';
        else if ($r['vehicle_type'] == 'bus') $emoji = '🚌';
        else if ($r['vehicle_type'] == 'bike') $emoji = '🏍️';
    ?>
    <div class="row">
      <span><?=htmlspecialchars($r['slot_number'])?></span>
      <span><?=$emoji?> <?=ucfirst(htmlspecialchars($r['vehicle_type']))?></span>
      <span class="tag <?= $r['is_occupied'] ? 'orange' : 'green' ?>"><?= $r['is_occupied'] ? 'Occupied' : 'Available' ?></span>
    </div>
    <?php endwhile; 
    else: ?>
    <div class="row">
      <span colspan="3" style="text-align:center;padding:20px;color:#999">
        No slots found. <a href="../database/setup_slots.php">Click here to setup slots</a>
      </span>
    </div>
    <?php endif; ?>
  </div>

  <!-- Available Slots -->
  <div id="availableSlotsList" class="slotlist">
    <div class="back-btn"><button onclick="closeAll()">← BACK</button></div>
    <h2>● AVAILABLE SLOTS (<?= $available ?>)</h2>
    <div class="row" style="font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#2c3e50;font-size:13px"><span>Slot</span><span>Type</span></div>
    <?php if (!empty($available_slots)): ?>
    <?php foreach($available_slots as $s): 
        $emoji = '🚗';
        if ($s['vehicle_type'] == 'car') $emoji = '🚗';
        else if ($s['vehicle_type'] == 'van') $emoji = '🚐';
        else if ($s['vehicle_type'] == 'jeep') $emoji = '🚙';
        else if ($s['vehicle_type'] == 'lorry') $emoji = '🚚';
        else if ($s['vehicle_type'] == 'threewheel') $emoji = '🛺';
        else if ($s['vehicle_type'] == 'bus') $emoji = '🚌';
        else if ($s['vehicle_type'] == 'bike') $emoji = '🏍️';
    ?>
    <div class="row">
      <span><?=htmlspecialchars($s['slot_number'])?></span>
      <span><?=$emoji?> <?=ucfirst(htmlspecialchars($s['vehicle_type']))?></span>
    </div>
    <?php endforeach; ?>
    <?php else: ?>
    <div class="row">
      <span colspan="2" style="text-align:center;padding:20px;color:#999">No available slots</span>
    </div>
    <?php endif; ?>
  </div>

  <!-- Occupied Slots -->
  <div id="occupiedSlotsList" class="slotlist">
    <div class="back-btn"><button onclick="closeAll()">← BACK</button></div>
    <h2>▲ OCCUPIED SLOTS (<?= $occupied ?>)</h2>
    <div class="row" style="font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#2c3e50;font-size:13px"><span>Slot</span><span>Type</span></div>
    <?php if (!empty($occupied_slots)): ?>
    <?php foreach($occupied_slots as $s): 
        $emoji = '🚗';
        if ($s['vehicle_type'] == 'car') $emoji = '🚗';
        else if ($s['vehicle_type'] == 'van') $emoji = '🚐';
        else if ($s['vehicle_type'] == 'jeep') $emoji = '🚙';
        else if ($s['vehicle_type'] == 'lorry') $emoji = '🚚';
        else if ($s['vehicle_type'] == 'threewheel') $emoji = '🛺';
        else if ($s['vehicle_type'] == 'bus') $emoji = '🚌';
        else if ($s['vehicle_type'] == 'bike') $emoji = '🏍️';
    ?>
    <div class="row">
      <span><?=htmlspecialchars($s['slot_number'])?></span>
      <span><?=$emoji?> <?=ucfirst(htmlspecialchars($s['vehicle_type']))?></span>
    </div>
    <?php endforeach; ?>
    <?php else: ?>
    <div class="row">
      <span colspan="2" style="text-align:center;padding:20px;color:#999">No occupied slots</span>
    </div>
    <?php endif; ?>
  </div>

  <!-- Now Parking Details -->
  <div id="nowParkingList" class="slotlist">
    <div class="back-btn"><button onclick="closeAll()">← BACK</button></div>
    <h2>● CURRENTLY PARKED VEHICLES (<?= $parked_vehicles ?>)</h2>
    <div class="row" style="font-weight:bold;display:grid;grid-template-columns:repeat(5,1fr);gap:10px">
      <span>Vehicle No</span><span>Slot</span><span>Type</span><span>Entry Time</span><span>Duration</span>
    </div>
    <?php
    $now_parking_sql = "SELECT vehicle_number, slot_number, vehicle_type, entry_time,
                        TIMESTAMPDIFF(HOUR, entry_time, NOW()) as hours_parked,
                        TIMESTAMPDIFF(MINUTE, entry_time, NOW()) % 60 as minutes_parked
                        FROM vehicles 
                        ORDER BY entry_time DESC";
    $now_parking_result = mysqli_query($conn, $now_parking_sql);
    if(mysqli_num_rows($now_parking_result) > 0): 
      while($vehicle = mysqli_fetch_assoc($now_parking_result)): 
        $emoji = '🚗';
        if ($vehicle['vehicle_type'] == 'car') $emoji = '🚗';
        else if ($vehicle['vehicle_type'] == 'van') $emoji = '🚐';
        else if ($vehicle['vehicle_type'] == 'jeep') $emoji = '🚙';
        else if ($vehicle['vehicle_type'] == 'lorry') $emoji = '🚚';
        else if ($vehicle['vehicle_type'] == 'threewheel') $emoji = '🛺';
        else if ($vehicle['vehicle_type'] == 'bus') $emoji = '🚌';
        else if ($vehicle['vehicle_type'] == 'bike') $emoji = '🏍️';
    ?>
    <div class="row" style="display:grid;grid-template-columns:repeat(5,1fr);gap:10px">
      <span><?= htmlspecialchars($vehicle['vehicle_number']) ?></span>
      <span><?= htmlspecialchars($vehicle['slot_number']) ?></span>
      <span><?= $emoji ?> <?= ucfirst(htmlspecialchars($vehicle['vehicle_type'])) ?></span>
      <span><?= date('d M H:i', strtotime($vehicle['entry_time'])) ?></span>
      <span><?= $vehicle['hours_parked'] ?>h <?= $vehicle['minutes_parked'] ?>m</span>
    </div>
    <?php endwhile; else: ?>
    <div class="row">
      <span style="text-align:center;padding:20px;color:#999;grid-column:1/-1">No vehicles currently parked</span>
    </div>
    <?php endif; ?>
  </div>

  <!-- Today's Exits Details -->
  <div id="todayExitsList" class="slotlist">
    <div class="back-btn"><button onclick="closeAll()">← BACK</button></div>
    <h2>▼ TODAY'S EXIT DETAILS (<?= $total_exits ?>)</h2>
    <div class="row" style="font-weight:bold;display:grid;grid-template-columns:1.2fr 0.8fr 0.8fr 0.8fr 0.8fr 1fr 0.8fr;gap:10px">
      <span>Vehicle No</span><span>Slot</span><span>Type</span><span>Entry</span><span>Exit</span><span>Duration</span><span>Fee</span>
    </div>
    <?php
    $today_exits_sql = "SELECT vehicle_number, slot_number, vehicle_type, entry_time, exit_time, fee,
                        TIMESTAMPDIFF(HOUR, entry_time, exit_time) as hours_parked,
                        TIMESTAMPDIFF(MINUTE, entry_time, exit_time) % 60 as minutes_parked
                        FROM exited_vehicles 
                        WHERE DATE(exit_time) = CURDATE()
                        ORDER BY exit_time DESC";
    $today_exits_result = mysqli_query($conn, $today_exits_sql);
    if(mysqli_num_rows($today_exits_result) > 0): 
      while($exit = mysqli_fetch_assoc($today_exits_result)): 
        $emoji = '🚗';
        if ($exit['vehicle_type'] == 'car') $emoji = '🚗';
        else if ($exit['vehicle_type'] == 'van') $emoji = '🚐';
        else if ($exit['vehicle_type'] == 'jeep') $emoji = '🚙';
        else if ($exit['vehicle_type'] == 'lorry') $emoji = '🚚';
        else if ($exit['vehicle_type'] == 'threewheel') $emoji = '🛺';
        else if ($exit['vehicle_type'] == 'bus') $emoji = '🚌';
        else if ($exit['vehicle_type'] == 'bike') $emoji = '🏍️';
    ?>
    <div class="row" style="display:grid;grid-template-columns:1.2fr 0.8fr 0.8fr 0.8fr 0.8fr 1fr 0.8fr;gap:10px">
      <span><?= htmlspecialchars($exit['vehicle_number']) ?></span>
      <span><?= htmlspecialchars($exit['slot_number']) ?></span>
      <span><?= $emoji ?> <?= ucfirst(htmlspecialchars($exit['vehicle_type'])) ?></span>
      <span><?= date('H:i', strtotime($exit['entry_time'])) ?></span>
      <span><?= date('H:i', strtotime($exit['exit_time'])) ?></span>
      <span><?= $exit['hours_parked'] ?>h <?= $exit['minutes_parked'] ?>m</span>
      <span class="tag orange">Rs.<?= number_format($exit['fee'], 2) ?></span>
    </div>
    <?php endwhile; else: ?>
    <div class="row">
      <span style="text-align:center;padding:20px;color:#999;grid-column:1/-1">No exits today</span>
    </div>
    <?php endif; ?>
  </div>

  <!-- Today's Revenue Details -->
  <div id="todayRevenueList" class="slotlist">
    <div class="back-btn"><button onclick="closeAll()">← BACK</button></div>
    <h2>■ TODAY'S REVENUE BREAKDOWN (RS.<?= number_format($revenue, 2) ?>)</h2>
    
    <!-- Summary Cards -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:15px;margin-bottom:25px">
      <div style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);padding:20px;border-radius:10px;color:white">
        <div style="font-size:12px;opacity:0.9;margin-bottom:5px">TOTAL REVENUE</div>
        <div style="font-size:28px;font-weight:700">Rs.<?= number_format($revenue, 2) ?></div>
      </div>
      <div style="background:linear-gradient(135deg,#f093fb 0%,#f5576c 100%);padding:20px;border-radius:10px;color:white">
        <div style="font-size:12px;opacity:0.9;margin-bottom:5px">AVG FEE</div>
        <div style="font-size:28px;font-weight:700">Rs.<?= $total_exits > 0 ? number_format($revenue / $total_exits, 2) : '0.00' ?></div>
      </div>
      <div style="background:linear-gradient(135deg,#4facfe 0%,#00f2fe 100%);padding:20px;border-radius:10px;color:white">
        <div style="font-size:12px;opacity:0.9;margin-bottom:5px">TOTAL EXITS</div>
        <div style="font-size:28px;font-weight:700"><?= $total_exits ?></div>
      </div>
    </div>

    <!-- Revenue by Vehicle Type -->
    <h3 style="margin:25px 0 15px;color:#2c3e50;font-size:16px;letter-spacing:2px;text-transform:uppercase;font-weight:700">● REVENUE BY VEHICLE TYPE</h3>
    <div class="row" style="font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#2c3e50;font-size:13px;display:grid;grid-template-columns:repeat(4,1fr);gap:10px">
      <span>Vehicle Type</span><span>Exits</span><span>Total Revenue</span><span>Avg Fee</span>
    </div>
    <?php
    $revenue_by_type_sql = "SELECT vehicle_type, 
                            COUNT(*) as count, 
                            SUM(fee) as total_fee,
                            AVG(fee) as avg_fee
                            FROM exited_vehicles 
                            WHERE DATE(exit_time) = CURDATE()
                            GROUP BY vehicle_type";
    $revenue_by_type_result = mysqli_query($conn, $revenue_by_type_sql);
    if(mysqli_num_rows($revenue_by_type_result) > 0): 
      while($type_rev = mysqli_fetch_assoc($revenue_by_type_result)): 
        $emoji = '🚗';
        if ($type_rev['vehicle_type'] == 'car') $emoji = '🚗';
        else if ($type_rev['vehicle_type'] == 'van') $emoji = '🚐';
        else if ($type_rev['vehicle_type'] == 'jeep') $emoji = '🚙';
        else if ($type_rev['vehicle_type'] == 'lorry') $emoji = '🚚';
        else if ($type_rev['vehicle_type'] == 'threewheel') $emoji = '🛺';
        else if ($type_rev['vehicle_type'] == 'bus') $emoji = '🚌';
        else if ($type_rev['vehicle_type'] == 'bike') $emoji = '🏍️';
    ?>
    <div class="row" style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px">
      <span><?= $emoji ?> <?= ucfirst(htmlspecialchars($type_rev['vehicle_type'])) ?></span>
      <span><?= $type_rev['count'] ?></span>
      <span class="tag green">Rs.<?= number_format($type_rev['total_fee'], 2) ?></span>
      <span>Rs.<?= number_format($type_rev['avg_fee'], 2) ?></span>
    </div>
    <?php endwhile; else: ?>
    <div class="row">
      <span style="text-align:center;padding:20px;color:#999;grid-column:1/-1">No revenue data by type</span>
    </div>
    <?php endif; ?>

    <!-- Hourly Revenue Analysis -->
    <h3 style="margin:25px 0 15px;color:#2c3e50;font-size:16px;letter-spacing:2px;text-transform:uppercase;font-weight:700">▲ HOURLY REVENUE ANALYSIS</h3>
    <div class="row" style="font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#2c3e50;font-size:13px;display:grid;grid-template-columns:repeat(3,1fr);gap:10px">
      <span>Hour</span><span>Exits</span><span>Revenue</span>
    </div>
    <?php
    $hourly_revenue_sql = "SELECT HOUR(exit_time) as hour, 
                           COUNT(*) as exits, 
                           SUM(fee) as revenue
                           FROM exited_vehicles 
                           WHERE DATE(exit_time) = CURDATE()
                           GROUP BY HOUR(exit_time)
                           ORDER BY hour";
    $hourly_revenue_result = mysqli_query($conn, $hourly_revenue_sql);
    if(mysqli_num_rows($hourly_revenue_result) > 0): 
      while($hour_rev = mysqli_fetch_assoc($hourly_revenue_result)): ?>
    <div class="row" style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px">
      <span><?= sprintf('%02d:00 - %02d:59', $hour_rev['hour'], $hour_rev['hour']) ?></span>
      <span><?= $hour_rev['exits'] ?></span>
      <span class="tag orange">Rs.<?= number_format($hour_rev['revenue'], 2) ?></span>
    </div>
    <?php endwhile; else: ?>
    <div class="row">
      <span style="text-align:center;padding:20px;color:#999;grid-column:1/-1">No hourly revenue data</span>
    </div>
    <?php endif; ?>
  </div>

  <!-- Quick Action Buttons -->
  <div class="buttons">
    <a href="entry7.php" class="btn entry">▲ VEHICLE ENTRY</a>
    <a href="exit.php" class="btn exit">▼ VEHICLE EXIT</a>
    <a href="admin4.php" class="btn" style="background:linear-gradient(135deg,#555 0%,#333 100%)">● ADMIN PANEL</a>
  </div>

  <!-- Recent Activity -->
  <div class="activity">
    <h2>▲ RECENT ENTRY ACTIVITY</h2>
    <div class="row" style="font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#2c3e50;font-size:13px">
      <span>Time</span><span>Vehicle Number</span><span>Action</span><span>Slot Number</span>
    </div>
    <?php foreach($recent_activity as $act): ?>
    <div class="row">
      <span><?=date("H:i",strtotime($act['entry_time']))?></span>
      <span style="font-weight:600"><?=htmlspecialchars($act['vehicle_number'])?></span>
      <span class="tag green">Entry</span>
      <span><?=htmlspecialchars($act['slot_number'])?></span>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Recent Exit Activity -->
  <div class="activity">
    <h2>▼ RECENT EXIT ACTIVITY</h2>
    <div class="row" style="font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#2c3e50;font-size:13px">
      <span>Time</span><span>Vehicle Number</span><span>Duration</span><span>Fee</span>
    </div>
    <?php foreach($recent_exits as $exit): ?>
    <div class="row">
      <span><?=date("H:i",strtotime($exit['exit_time']))?></span>
      <span style="font-weight:600"><?=htmlspecialchars($exit['vehicle_number'])?></span>
      <span><?=htmlspecialchars($exit['duration'])?></span>
      <span class="tag orange">Rs.<?=number_format($exit['fee'], 2)?></span>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Logout Button -->
  <div class="logout-box">
    <form action="../auth/logout.php" method="post">
      <button type="submit">■ SYSTEM LOGOUT</button>
    </form>
  </div>

</div>

</body>
</html>
