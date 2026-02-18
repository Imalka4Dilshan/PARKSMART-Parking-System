<?php
session_start();
date_default_timezone_set('Asia/Colombo');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login2.php');
    exit();
}

include __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $slot = $_POST['slot'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $payment_method = $_POST['payment_method'] ?? 'card';
    $booking_date = $_POST['booking_date'] ?? date('Y-m-d');
    $booking_time = $_POST['booking_time'] ?? date('H:i:s');
    $booking_hours = $_POST['booking_hours'] ?? 2;
    
    // Validate inputs
    if (empty($slot)) {
        die("Error: Slot number is required");
    }
    
    // Generate booking ID
    $booking_id = 'BK' . date('YmdHis') . rand(100, 999);
    
    // Handle receipt upload
    $receipt_path = null;
    if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === 0) {
        $upload_dir = __DIR__ . '/../uploads/receipts/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_ext = pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION);
        $receipt_filename = $booking_id . '_' . time() . '.' . $file_ext;
        $receipt_path = 'uploads/receipts/' . $receipt_filename;
        
        move_uploaded_file($_FILES['payment_proof']['tmp_name'], $upload_dir . $receipt_filename);
    }
    
    // Check if bookings table exists and get columns
    $check_table = $conn->query("SHOW TABLES LIKE 'bookings'");
    if ($check_table->num_rows == 0) {
        // Create bookings table if it doesn't exist
        $conn->query("CREATE TABLE IF NOT EXISTS bookings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            booking_id VARCHAR(50) UNIQUE,
            user_id INT,
            slot_number VARCHAR(10),
            booking_date DATE,
            start_time TIME,
            duration_hours INT DEFAULT 2,
            status VARCHAR(20) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    } else {
        // Check if duration_hours column exists, if not add it
        $check_column = $conn->query("SHOW COLUMNS FROM bookings LIKE 'duration_hours'");
        if ($check_column->num_rows == 0) {
            $conn->query("ALTER TABLE bookings ADD COLUMN duration_hours INT DEFAULT 2 AFTER start_time");
        }
    }
    
    // Insert booking with date, time, and hours
    $stmt = $conn->prepare("INSERT INTO bookings (booking_id, user_id, slot_number, booking_date, start_time, duration_hours, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
    if (!$stmt) {
        die("Error preparing statement: " . $conn->error);
    }
    $stmt->bind_param("sisssi", $booking_id, $user_id, $slot, $booking_date, $booking_time, $booking_hours);
    if (!$stmt->execute()) {
        die("Error executing booking: " . $stmt->error);
    }
    $booking_db_id = $conn->insert_id;
    
    // Check if payments table exists
    $check_payment_table = $conn->query("SHOW TABLES LIKE 'payments'");
    if ($check_payment_table->num_rows == 0) {
        // Create payments table if it doesn't exist
        $conn->query("CREATE TABLE IF NOT EXISTS payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            booking_id VARCHAR(50),
            amount DECIMAL(10,2),
            payment_method VARCHAR(50),
            receipt_path VARCHAR(255),
            status VARCHAR(20) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }
    
    // Insert payment
    $payment_status = ($payment_method === 'card') ? 'completed' : 'pending';
    $stmt = $conn->prepare("INSERT INTO payments (booking_id, amount, payment_method, receipt_path, status) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        die("Error preparing payment statement: " . $conn->error);
    }
    $stmt->bind_param("sdsss", $booking_id, $amount, $payment_method, $receipt_path, $payment_status);
    if (!$stmt->execute()) {
        die("Error executing payment: " . $stmt->error);
    }
    
    // Mark slot as occupied
    $conn->query("UPDATE parking_slots SET is_occupied = 1 WHERE slot_number = '$slot'");
    
    // Redirect to confirmation
    header('Location: booking_confirmation.php?id=' . $booking_db_id);
    exit();
} else {
    header('Location: slot_view.php');
    exit();
}
?>
