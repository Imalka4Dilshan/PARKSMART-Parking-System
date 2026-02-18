<?php
session_start();
date_default_timezone_set('Asia/Colombo');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login2.php');
    exit();
}

include __DIR__ . '/../config/db.php';
$user_id = $_SESSION['user_id'];

// Get user info
$user_stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_info = $user_stmt->get_result()->fetch_assoc();
$user_name = $user_info['name'] ?? 'User';

// Totals
$total_res = $conn->query("SELECT COUNT(*) AS total FROM parking_slots");
$total = ($total_res && $total_res->num_rows) ? (int)$total_res->fetch_assoc()['total'] : 0;

$occ_res = $conn->query("SELECT COUNT(*) AS occupied FROM parking_slots WHERE is_occupied = 1");
$occupied = ($occ_res && $occ_res->num_rows) ? (int)$occ_res->fetch_assoc()['occupied'] : 0;

$available = max(0, $total - $occupied);

// User's bookings
$my_bookings_stmt = $conn->prepare("SELECT COUNT(*) AS my_bookings FROM bookings WHERE user_id = ? AND status = 'active'");
$my_bookings_stmt->bind_param("i", $user_id);
$my_bookings_stmt->execute();
$my_bookings = (int)$my_bookings_stmt->get_result()->fetch_assoc()['my_bookings'];

// User's total bookings
$total_bookings_stmt = $conn->prepare("SELECT COUNT(*) AS total_bookings FROM bookings WHERE user_id = ?");
$total_bookings_stmt->bind_param("i", $user_id);
$total_bookings_stmt->execute();
$total_bookings = (int)$total_bookings_stmt->get_result()->fetch_assoc()['total_bookings'];

// Recent activity
$recent = [];
$recent_stmt = $conn->prepare("SELECT b.booking_id, b.slot_number, b.booking_date, b.start_time, b.status 
                                FROM bookings b 
                                WHERE b.user_id = ? 
                                ORDER BY b.booking_date DESC, b.start_time DESC 
                                LIMIT 5");
$recent_stmt->bind_param("i", $user_id);
$recent_stmt->execute();
$recent_res = $recent_stmt->get_result();
while ($r = $recent_res->fetch_assoc()) $recent[] = $r;

// Available slots
$available_slots = [];
$avail_res = $conn->query("SELECT slot_number, vehicle_type FROM parking_slots WHERE is_occupied=0 ORDER BY slot_number LIMIT 10");
while ($r = $avail_res->fetch_assoc()) $available_slots[] = $r;

$current = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ParkSmart - User Dashboard</title>
  <style>
    *{margin:0;padding:0;font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;box-sizing:border-box}
    body{background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);min-height:100vh;padding-top:0;padding-bottom:40px}
    .logo{background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);color:#fff;padding:8px;border-radius:4px;display:inline-block;font-size:14px;box-shadow: 0 4px 15px rgba(102,126,234,0.3)}
    .user-info{float:right;color:#fff;padding:8px;font-size:14px}
    .user-info strong{color:#fff;font-weight:700}
    .nav{float:right;margin-right:20px}
    .nav a{margin-left:20px;text-decoration:none;color:#fff;font-size:14px;transition:all 0.3s ease}
    .nav a:hover{color:#ffeb3b}
    .nav a.active{color:#ffeb3b;font-weight:700}
    .container{max-width:1200px;margin:0 auto;padding:20px}
    .container h1{color:#fff;text-align:center;margin-bottom:30px;font-size:32px;font-weight:800;letter-spacing:2px;text-transform:uppercase;text-shadow:2px 2px 8px rgba(0,0,0,0.2)}
    .stats{display:flex;gap:20px;margin:20px 0;flex-wrap:wrap}
    .stat{flex:1;min-width:150px;padding:30px 30px 40px 30px;text-align:center;border-radius:16px;cursor:pointer;user-select:none;transition:all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);position:relative;background:rgba(255,255,255,0.95);backdrop-filter:blur(10px);box-shadow:0 10px 30px rgba(0,0,0,0.2)}
    .stat:hover{transform:translateY(-5px);box-shadow:0 15px 40px rgba(0,0,0,0.3)}
    .stat:after{content:'■ Click for details';position:absolute;bottom:12px;left:0;right:0;font-size:11px;opacity:0.7;font-weight:600;letter-spacing:0.5px;text-transform:uppercase}
    .stat h2{font-size:48px;margin-bottom:10px;font-weight:800}
    .stat div{font-size:14px;font-weight:700;text-transform:uppercase;letter-spacing:1px}
    .total{border-left:4px solid #ff9800}
    .total div{color:#f57c00}
    .total h2{color:#ef6c00}
    .available{border-left:4px solid #4caf50}
    .available div{color:#2e7d32}
    .available h2{color:#1b5e20}
    .mybookings{border-left:4px solid #2196f3}
    .mybookings div{color:#1565c0}
    .mybookings h2{color:#0d47a1}
    .buttons{display:flex;gap:20px;margin:30px 0;flex-wrap:wrap}
    .btn{flex:1;min-width:200px;padding:50px 20px;border:none;border-radius:16px;color:#fff;font-size:18px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;cursor:pointer;display:flex;align-items:center;justify-content:center;text-decoration:none;transition:all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);box-shadow:0 10px 30px rgba(0,0,0,0.2);position:relative;overflow:hidden}
    .btn::before{content:'';position:absolute;top:0;left:-100%;width:100%;height:100%;background:rgba(255,255,255,0.2);transition:left 0.5s ease}
    .btn:hover::before{left:100%}
    .btn:hover{transform:translateY(-3px);box-shadow:0 15px 40px rgba(0,0,0,0.3)}
    .book{background:linear-gradient(135deg, #4caf50 0%, #2e7d32 100%)}
    .view{background:linear-gradient(135deg, #2196f3 0%, #1565c0 100%)}
    .navigate{background:linear-gradient(135deg, #ff9800 0%, #f57c00 100%)}
    .review{background:linear-gradient(135deg, #9c27b0 0%, #6a1b9a 100%)}
    .contact{background:linear-gradient(135deg, #00bcd4 0%, #0097a7 100%)}
    .activity{background:rgba(255,255,255,0.95);backdrop-filter:blur(10px);padding:25px;border-radius:16px;margin-top:30px;box-shadow:0 10px 30px rgba(0,0,0,0.2)}
    .activity h2{color:#2c3e50;margin-bottom:20px;font-weight:800;text-transform:uppercase;letter-spacing:2px}
    .row{display:flex;justify-content:space-between;padding:15px 0;border-bottom:1px solid rgba(102,126,234,0.15);flex-wrap:wrap;gap:10px;transition:background 0.3s ease}
    .row:hover{background:rgba(102,126,234,0.05);border-radius:8px;padding-left:10px;padding-right:10px}
    .tag{padding:5px 12px;border-radius:8px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px}
    .green{background:rgba(76,175,80,0.2);color:#2e7d32}
    .blue{background:rgba(33,150,243,0.2);color:#1565c0}
    .orange{background:rgba(255,152,0,0.2);color:#f57c00}
    .slotlist{margin-top:20px;background:rgba(255,255,255,0.95);backdrop-filter:blur(10px);padding:25px;border-radius:16px;display:none;box-shadow:0 10px 30px rgba(0,0,0,0.2)}
    .slotlist.active{display:block}
    .slotlist h2{margin-bottom:15px;color:#2c3e50;font-weight:800;text-transform:uppercase;letter-spacing:2px}
    .back-btn{text-align:right;margin-top:-10px;margin-bottom:10px}
    .back-btn button{background:linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);color:#fff;border:none;padding:10px 18px;border-radius:8px;cursor:pointer;font-weight:700;text-transform:uppercase;letter-spacing:1px;transition:all 0.3s ease;box-shadow:0 4px 15px rgba(0,0,0,0.2)}
    .back-btn button:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,0.3)}

    @media (max-width: 768px) {
      .header{padding:10px}
      .logo{font-size:12px;padding:6px}
      .user-info{float:none;display:block;text-align:center;font-size:12px}
      .nav{float:none;text-align:center;margin:10px 0}
      .nav a{margin:0 10px;font-size:13px}
      .container{padding:10px}
      .stats{gap:10px}
      .stat{padding:20px 10px;min-width:100px}
      .stat h2{font-size:32px}
      .stat p{font-size:12px}
      .buttons{gap:10px}
      .btn{min-width:100%;padding:30px 15px;font-size:16px}
      .activity{padding:15px}
      .row{font-size:14px}
      .slotlist{padding:15px}
    }
    
    @media (max-width: 480px) {
      .stat{flex:100%;min-width:100%}
      .stat h2{font-size:36px}
      .nav a{display:block;margin:5px 0}
    }

    .logout-box {
      text-align:center;
      margin: 50px 0 10px 0;
    }
    .logout-box form {
      display:inline-block;
    }
    .logout-box button {
      background:linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
      color:#fff;
      border:none;
      padding:14px 28px;
      font-size:16px;
      font-weight:700;
      text-transform:uppercase;
      letter-spacing:1.5px;
      border-radius:12px;
      cursor:pointer;
      transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      box-shadow:0 8px 25px rgba(231,76,60,0.4);
      position:relative;
      overflow:hidden;
    }
    .logout-box button::before {
      content:'';
      position:absolute;
      top:0;
      left:-100%;
      width:100%;
      height:100%;
      background:rgba(255,255,255,0.2);
      transition:left 0.5s ease;
    }
    .logout-box button:hover::before {
      left:100%;
    }
    .logout-box button:hover {
      transform:translateY(-3px);
      box-shadow:0 12px 35px rgba(231,76,60,0.5);
    }

    .profile-box {
      text-align:right;
      margin: 0 0 50px 0;
    }
    .profile-box a {
      background:linear-gradient(135deg, #16a085 0%, #117a65 100%);
      color:#fff;
      text-decoration:none;
      padding:14px 28px;
      font-size:16px;
      font-weight:700;
      text-transform:uppercase;
      letter-spacing:1.5px;
      border-radius:12px;
      transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      box-shadow:0 8px 25px rgba(22,160,133,0.4);
      position:relative;
      overflow:hidden;
      display:inline-block;
    }
    .profile-box a::before {
      content:'';
      position:absolute;
      top:0;
      left:-100%;
      width:100%;
      height:100%;
      background:rgba(255,255,255,0.2);
      transition:left 0.5s ease;
    }
    .profile-box a:hover::before {
      left:100%;
    }
    .profile-box a:hover {
      transform:translateY(-3px);
      box-shadow:0 12px 35px rgba(22,160,133,0.5);
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
  <?php include 'user_nav.php'; ?>

<div class="container">
  <h1>Parking Overview</h1>

  <div class="stats">
    <div class="stat total" onclick="toggleList('totalSlotsList')">
      <div>Total Spaces</div>
      <h2><?= $total ?></h2>
    </div>
    <div class="stat available" onclick="toggleList('availableSlotsList')">
      <div>Available Now</div>
      <h2><?= $available ?></h2>
    </div>
    <div class="stat mybookings" onclick="toggleList('myActiveBookingsList')">
      <div>My Active Bookings</div>
      <h2><?= $my_bookings ?></h2>
    </div>
  </div>

  <div class="stats" style="margin-top:20px">
    <div class="stat" style="background:#9c27b0;color:#fff" onclick="toggleList('totalBookingsList')">
      <div>Total Bookings</div>
      <h2><?= $total_bookings ?></h2>
    </div>
    <div class="stat" style="background:#ff5722;color:#fff" onclick="toggleList('occupiedSlotsList')">
      <div>Occupied Spaces</div>
      <h2><?= $occupied ?></h2>
    </div>
    <div class="stat" style="background:#607d8b;color:#fff" onclick="toggleList('occupancyRateDetails')">
      <div>Occupancy Rate</div>
      <h2><?= $total ? round(($occupied / $total) * 100) : 0 ?>%</h2>
    </div>
  </div>

  <!-- Total Slots List -->
  <div id="totalSlotsList" class="slotlist">
    <div class="back-btn"><button onclick="closeAll()">← Back</button></div>
    <h2>All Parking Spaces</h2>
    <div class="row" style="font-weight:bold"><span>Slot</span><span>Type</span><span>Status</span></div>
    <?php 
    $all = $conn->query("SELECT slot_number, vehicle_type, is_occupied FROM parking_slots ORDER BY slot_number");
    while($r = $all->fetch_assoc()): ?>
    <div class="row">
      <span><?= htmlspecialchars($r['slot_number']) ?></span>
      <span><?= htmlspecialchars($r['vehicle_type']) ?></span>
      <span class="tag <?= $r['is_occupied'] ? 'orange' : 'green' ?>">
        <?= $r['is_occupied'] ? 'Occupied' : 'Available' ?>
      </span>
    </div>
    <?php endwhile; ?>
  </div>

  <!-- Available Slots List -->
  <div id="availableSlotsList" class="slotlist">
    <div class="back-btn"><button onclick="closeAll()">← Back</button></div>
    <h2>Available Slots (First 10)</h2>
    <div class="row" style="font-weight:bold"><span>Slot</span><span>Type</span><span>Action</span></div>
    <?php foreach($available_slots as $s): ?>
    <div class="row">
      <span><?= htmlspecialchars($s['slot_number']) ?></span>
      <span><?= htmlspecialchars($s['vehicle_type']) ?></span>
      <span><a href="payment.php?slot=<?= urlencode($s['slot_number']) ?>" style="color:#007bff;text-decoration:none">Book Now →</a></span>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- My Active Bookings List -->
  <div id="myActiveBookingsList" class="slotlist">
    <div class="back-btn"><button onclick="closeAll()">← Back</button></div>
    <h2>My Active Bookings</h2>
    <div class="row" style="font-weight:bold"><span>Booking ID</span><span>Slot</span><span>Date & Time</span><span>Action</span></div>
    <?php 
    $active_bookings = $conn->prepare("SELECT booking_id, slot_number, booking_date, start_time FROM bookings WHERE user_id = ? AND status = 'active' ORDER BY booking_date DESC, start_time DESC");
    $active_bookings->bind_param("i", $user_id);
    $active_bookings->execute();
    $active_bookings_result = $active_bookings->get_result();
    if ($active_bookings_result->num_rows > 0):
      while($ab = $active_bookings_result->fetch_assoc()): ?>
    <div class="row">
      <span>#<?= htmlspecialchars($ab['booking_id']) ?></span>
      <span><?= htmlspecialchars($ab['slot_number']) ?></span>
      <span><?= date("d M Y, H:i", strtotime($ab['booking_date'] . ' ' . $ab['start_time'])) ?></span>
      <span><a href="navigation.php?slot=<?= urlencode($ab['slot_number']) ?>" style="color:#007bff;text-decoration:none">Navigate →</a></span>
    </div>
    <?php endwhile; 
    else: ?>
    <div class="row">
      <span colspan="4" style="color:#999">No active bookings at the moment.</span>
    </div>
    <?php endif; ?>
  </div>

  <!-- Total Bookings List -->
  <div id="totalBookingsList" class="slotlist">
    <div class="back-btn"><button onclick="closeAll()">← Back</button></div>
    <h2>All My Bookings</h2>
    <div class="row" style="font-weight:bold"><span>Booking ID</span><span>Slot</span><span>Date</span><span>Status</span></div>
    <?php 
    $all_bookings = $conn->prepare("SELECT booking_id, slot_number, booking_date, start_time, status FROM bookings WHERE user_id = ? ORDER BY booking_date DESC, start_time DESC LIMIT 20");
    $all_bookings->bind_param("i", $user_id);
    $all_bookings->execute();
    $all_bookings_result = $all_bookings->get_result();
    if ($all_bookings_result->num_rows > 0):
      while($b = $all_bookings_result->fetch_assoc()): ?>
    <div class="row">
      <span>#<?= htmlspecialchars($b['booking_id']) ?></span>
      <span><?= htmlspecialchars($b['slot_number']) ?></span>
      <span><?= date("d M Y, H:i", strtotime($b['booking_date'] . ' ' . $b['start_time'])) ?></span>
      <span class="tag <?= $b['status']=='active' ? 'green' : ($b['status']=='pending' ? 'orange' : 'blue') ?>">
        <?= ucfirst($b['status']) ?>
      </span>
    </div>
    <?php endwhile; 
    else: ?>
    <div class="row">
      <span colspan="4" style="color:#999">No bookings found.</span>
    </div>
    <?php endif; ?>
  </div>

  <!-- Occupied Slots List -->
  <div id="occupiedSlotsList" class="slotlist">
    <div class="back-btn"><button onclick="closeAll()">← Back</button></div>
    <h2>Currently Occupied Spaces</h2>
    <div class="row" style="font-weight:bold"><span>Slot</span><span>Vehicle Type</span><span>Status</span></div>
    <?php 
    $occupied_slots = $conn->query("SELECT slot_number, vehicle_type FROM parking_slots WHERE is_occupied = 1 ORDER BY slot_number");
    if ($occupied_slots->num_rows > 0):
      while($o = $occupied_slots->fetch_assoc()): ?>
    <div class="row">
      <span><?= htmlspecialchars($o['slot_number']) ?></span>
      <span><?= ucfirst(htmlspecialchars($o['vehicle_type'])) ?></span>
      <span class="tag orange">Occupied</span>
    </div>
    <?php endwhile;
    else: ?>
    <div class="row">
      <span colspan="3" style="color:#999">No occupied slots at the moment.</span>
    </div>
    <?php endif; ?>
  </div>

  <!-- Occupancy Rate Details -->
  <div id="occupancyRateDetails" class="slotlist">
    <div class="back-btn"><button onclick="closeAll()">← Back</button></div>
    <h2>Occupancy Rate Breakdown</h2>
    <div class="row" style="font-weight:bold;background:#f5f5f5">
      <span>METRIC</span><span>VALUE</span><span>PERCENTAGE</span>
    </div>
    <div class="row">
      <span>Total Parking Spaces</span>
      <span><?= $total ?></span>
      <span class="tag blue">100%</span>
    </div>
    <div class="row">
      <span>Occupied Spaces</span>
      <span><?= $occupied ?></span>
      <span class="tag orange"><?= $total ? round(($occupied / $total) * 100) : 0 ?>%</span>
    </div>
    <div class="row">
      <span>Available Spaces</span>
      <span><?= $available ?></span>
      <span class="tag green"><?= $total ? round(($available / $total) * 100) : 0 ?>%</span>
    </div>
    <?php
    // Get breakdown by vehicle type
    $type_stats = $conn->query("SELECT 
      vehicle_type, 
      COUNT(*) as total_count,
      SUM(CASE WHEN is_occupied = 1 THEN 1 ELSE 0 END) as occupied_count,
      SUM(CASE WHEN is_occupied = 0 THEN 1 ELSE 0 END) as available_count
      FROM parking_slots 
      GROUP BY vehicle_type 
      ORDER BY vehicle_type");
    
    if ($type_stats && $type_stats->num_rows > 0): ?>
    <div class="row" style="font-weight:bold;background:#f5f5f5;margin-top:15px">
      <span>VEHICLE TYPE</span><span>OCCUPIED / TOTAL</span><span>RATE</span>
    </div>
    <?php while($ts = $type_stats->fetch_assoc()): 
      $type_rate = $ts['total_count'] ? round(($ts['occupied_count'] / $ts['total_count']) * 100) : 0;
    ?>
    <div class="row">
      <span><?= ucfirst(htmlspecialchars($ts['vehicle_type'])) ?></span>
      <span><?= $ts['occupied_count'] ?> / <?= $ts['total_count'] ?></span>
      <span class="tag <?= $type_rate >= 80 ? 'orange' : ($type_rate >= 50 ? 'blue' : 'green') ?>"><?= $type_rate ?>%</span>
    </div>
    <?php endwhile; endif; ?>
  </div>

  <!-- Quick Action Buttons -->
  <div class="buttons">
    <a href="slot_view.php" class="btn book">🅿️ Book Parking Slot</a>
    <a href="bookings_history.php" class="btn view">📋 View My Bookings</a>
    <a href="reviews.php" class="btn review">⭐ Reviews & Ratings</a>
    <a href="contact_us.php" class="btn contact">📧 Contact & Support</a>
    <a href="navigation.php" class="btn navigate">🗺️ Get Directions</a>
  </div>

  <!-- Recent Booking Activity -->
  <div class="activity">
    <h2>My Recent Bookings</h2>
    <div class="row" style="font-weight:bold">
      <span>Booking ID</span><span>Slot</span><span>Date</span><span>Status</span>
    </div>
    <?php if (empty($recent)): ?>
    <div class="row">
      <span colspan="4" style="color:#999">No bookings yet. Book your first slot now!</span>
    </div>
    <?php else: ?>
    <?php foreach($recent as $act): ?>
    <div class="row">
      <span><?= htmlspecialchars($act['booking_id']) ?></span>
      <span><?= htmlspecialchars($act['slot_number']) ?></span>
      <span><?= date("d M Y, H:i", strtotime($act['booking_date'] . ' ' . $act['start_time'])) ?></span>
      <span class="tag <?= $act['status']=='active' ? 'green' : ($act['status']=='pending' ? 'orange' : 'blue') ?>">
        <?= ucfirst($act['status']) ?>
      </span>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Logout Button -->
  <div class="logout-box">
    <form action="../auth/logout.php" method="post">
      <button type="submit">Logout</button>
    </form>
  </div>

  <!-- Profile Button -->
  <div class="profile-box">
    <a href="profile.php">👤 My Profile</a>
  </div>

</div>

</body>
</html>
