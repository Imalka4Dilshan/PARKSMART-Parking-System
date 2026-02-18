<?php
session_start();
date_default_timezone_set('Asia/Colombo');

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header('Location: dashboard.php');
    exit();
}

include __DIR__ . '/../config/db.php';

$booking_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Get booking details
$stmt = $conn->prepare("SELECT b.*, p.amount, p.payment_method, p.status as payment_status 
                        FROM bookings b 
                        LEFT JOIN payments p ON b.booking_id = p.booking_id 
                        WHERE b.id = ? AND b.user_id = ?");
if (!$stmt) {
    die("Error: " . $conn->error);
}
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed - ParkSmart</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .confirmation-card {
            background: white;
            border-radius: 20px;
            padding: 50px 40px;
            max-width: 600px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .success-icon {
            font-size: 80px;
            margin-bottom: 20px;
            animation: successPulse 1s ease-in-out;
        }
        @keyframes successPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        h1 {
            font-size: 32px;
            color: #333;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        .qr-code {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 15px;
            margin: 30px 0;
        }
        .qr-placeholder {
            width: 200px;
            height: 200px;
            margin: 0 auto;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            color: #999;
        }
        .booking-details {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            margin: 20px 0;
            text-align: left;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            color: #666;
            font-weight: 500;
        }
        .detail-value {
            color: #333;
            font-weight: 600;
        }
        .action-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 30px;
        }
        .btn {
            padding: 14px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class="confirmation-card">
        <div class="success-icon">✅</div>
        <h1>Booking Confirmed!</h1>
        <p class="subtitle">Your parking slot has been reserved successfully</p>

        <div class="qr-code">
            <p style="margin-bottom: 15px; font-weight: 600; color: #333;">Check-in QR Code</p>
            <div class="qr-placeholder">
                Booking ID: #<?= htmlspecialchars($booking['booking_id']) ?><br>
                <small>Show this at entrance</small>
            </div>
        </div>

        <div class="booking-details">
            <div class="detail-row">
                <span class="detail-label">Booking ID:</span>
                <span class="detail-value">#<?= htmlspecialchars($booking['booking_id']) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Slot Number:</span>
                <span class="detail-value"><?= htmlspecialchars($booking['slot_number']) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Date:</span>
                <span class="detail-value"><?= date('M d, Y', strtotime($booking['booking_date'])) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Start Time:</span>
                <span class="detail-value"><?= date('h:i A', strtotime($booking['start_time'])) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Duration:</span>
                <span class="detail-value"><?= $booking['duration_hours'] ?? 2 ?> Hour<?= ($booking['duration_hours'] ?? 2) > 1 ? 's' : '' ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Amount Paid:</span>
                <span class="detail-value">Rs. <?= number_format($booking['amount'] ?? 0, 2) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Payment Status:</span>
                <span class="detail-value" style="color: #4caf50;">
                    <?= ucfirst($booking['payment_status'] ?? 'pending') ?>
                </span>
            </div>
        </div>

        <div class="action-buttons">
            <a href="navigation.php" class="btn btn-primary">🗺️ Get Directions</a>
            <a href="bookings_history.php" class="btn btn-secondary">📋 My Bookings</a>
            <a href="dashboard.php" class="btn btn-secondary">🏠 Dashboard</a>
            <a href="#" onclick="window.print(); return false;" class="btn btn-secondary">🖨️ Print</a>
        </div>
    </div>
</body>
</html>
