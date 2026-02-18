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
    $user_id = $_SESSION['user_id'];

    // Validate booking ID
    if (empty($booking_id)) {
        echo json_encode(['success' => false, 'message' => 'Invalid booking ID']);
        exit();
    }

    // Verify booking belongs to user and get booking details
    $stmt = $conn->prepare("
        SELECT b.*, p.payment_id, p.payment_method, p.status as payment_status 
        FROM bookings b 
        LEFT JOIN payments p ON b.booking_id = p.booking_id 
        WHERE b.booking_id = ? AND b.user_id = ?
    ");
    $stmt->bind_param("si", $booking_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        exit();
    }

    $booking = $result->fetch_assoc();
    $booking_status = $booking['status'];
    $slot_number = $booking['slot_number'];

    // Only allow cancellation of active or pending bookings
    if ($booking_status === 'completed') {
        echo json_encode(['success' => false, 'message' => 'Cannot cancel completed bookings']);
        exit();
    }

    if ($booking_status === 'cancelled') {
        echo json_encode(['success' => false, 'message' => 'This booking is already cancelled']);
        exit();
    }

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Update booking status to cancelled
        $update_stmt = $conn->prepare("
            UPDATE bookings 
            SET status = 'cancelled', 
                updated_at = NOW() 
            WHERE booking_id = ? 
            AND user_id = ?
        ");
        $update_stmt->bind_param("si", $booking_id, $user_id);
        
        if (!$update_stmt->execute()) {
            throw new Exception('Failed to update booking status');
        }

        // Update payment status if exists
        if ($booking['payment_id']) {
            $payment_stmt = $conn->prepare("
                UPDATE payments 
                SET status = 'refunded', 
                    updated_at = NOW() 
                WHERE booking_id = ?
            ");
            $payment_stmt->bind_param("s", $booking_id);
            $payment_stmt->execute();
        }

        // Update slot availability
        $slot_stmt = $conn->prepare("
            UPDATE parking_slots 
            SET status = 'available', 
                updated_at = NOW() 
            WHERE slot_number = ?
        ");
        $slot_stmt->bind_param("s", $slot_number);
        $slot_stmt->execute();

        // Log the cancellation (optional - ignore errors)
        try {
            $log_stmt = $conn->prepare("
                INSERT INTO booking_logs (booking_id, user_id, action, action_date, details) 
                VALUES (?, ?, 'cancel', NOW(), ?)
            ");
            if ($log_stmt) {
                $details = "Booking cancelled by user. Slot $slot_number released.";
                $log_stmt->bind_param("sis", $booking_id, $user_id, $details);
                $log_stmt->execute();
            }
        } catch (Exception $e) {
            // Ignore logging errors
        }

        // Commit transaction
        $conn->commit();

        echo json_encode([
            'success' => true, 
            'message' => 'Booking cancelled successfully',
            'booking_id' => $booking_id
        ]);

    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to cancel booking: ' . $e->getMessage()
        ]);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
