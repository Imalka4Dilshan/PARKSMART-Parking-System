<?php
session_start();
header('Content-Type: application/json');
date_default_timezone_set('Asia/Colombo');

// Enable error logging for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

include __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = $_POST['booking_id'] ?? '';
    $booking_date = $_POST['booking_date'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $duration_hours = intval($_POST['duration_hours'] ?? 2);
    $user_id = $_SESSION['user_id'];

    // Validate inputs
    if (empty($booking_id) || empty($booking_date) || empty($start_time) || $duration_hours <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid input data']);
        exit();
    }

    // Validate date is not in the past
    $today = date('Y-m-d');
    if ($booking_date < $today) {
        echo json_encode(['success' => false, 'message' => 'Cannot book in the past']);
        exit();
    }

    // Verify booking belongs to user
    $stmt = $conn->prepare("SELECT slot_number, status FROM bookings WHERE booking_id = ? AND user_id = ?");
    $stmt->bind_param("si", $booking_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        exit();
    }

    $booking = $result->fetch_assoc();
    $slot_number = $booking['slot_number'];
    $booking_status = $booking['status'];

    // Only allow editing of active or pending bookings
    if ($booking_status !== 'active' && $booking_status !== 'pending') {
        echo json_encode(['success' => false, 'message' => 'Cannot edit completed or cancelled bookings']);
        exit();
    }

    // Check if the new time slot is available (excluding current booking)
    $check_stmt = $conn->prepare("
        SELECT booking_id 
        FROM bookings 
        WHERE slot_number = ? 
        AND booking_date = ? 
        AND status IN ('active', 'pending')
        AND booking_id != ?
        AND (
            (start_time <= ? AND DATE_ADD(CONCAT(booking_date, ' ', start_time), INTERVAL COALESCE(duration_hours, 2) HOUR) > ?)
            OR
            (start_time < DATE_ADD(?, INTERVAL ? HOUR) AND start_time >= ?)
        )
    ");
    
    $check_stmt->bind_param("ssssssis", 
        $slot_number, 
        $booking_date, 
        $booking_id,
        $start_time,
        $start_time,
        $start_time,
        $duration_hours,
        $start_time
    );
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'This time slot is already booked. Please choose a different time.']);
        exit();
    }

    // Calculate end time
    $end_time_calculation = date('H:i:s', strtotime("$start_time + $duration_hours hours"));

    // Update the booking
    $update_stmt = $conn->prepare("
        UPDATE bookings 
        SET booking_date = ?, 
            start_time = ?, 
            duration_hours = ?
        WHERE booking_id = ? 
        AND user_id = ?
    ");
    
    $update_stmt->bind_param("ssisi", $booking_date, $start_time, $duration_hours, $booking_id, $user_id);

    if ($update_stmt->execute()) {
        // Log the update (optional - ignore errors)
        try {
            $log_stmt = $conn->prepare("
                INSERT INTO booking_logs (booking_id, user_id, action, action_date, details) 
                VALUES (?, ?, 'update', NOW(), ?)
            ");
            if ($log_stmt) {
                $details = "Updated: Date=$booking_date, Time=$start_time, Duration=$duration_hours hours";
                $log_stmt->bind_param("sis", $booking_id, $user_id, $details);
                $log_stmt->execute();
            }
        } catch (Exception $e) {
            // Ignore logging errors - the main update was successful
        }

        echo json_encode([
            'success' => true, 
            'message' => 'Booking updated successfully',
            'booking_id' => $booking_id
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to update booking: ' . $conn->error
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
