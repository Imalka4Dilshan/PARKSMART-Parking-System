<?php
// Auto-process vehicles - Entry and Exit automation
date_default_timezone_set('Asia/Colombo');
include __DIR__ . '/../config/db.php';

// Auto-exit vehicles that have exceeded their parking duration
$current_time = date('Y-m-d H:i:s');

// Find vehicles that need to be auto-exited
$auto_exit_query = "
    SELECT v.*, 
           TIMESTAMPDIFF(HOUR, v.entry_time, NOW()) as hours_parked
    FROM vehicles v
    WHERE TIMESTAMPDIFF(HOUR, v.entry_time, NOW()) >= 12
";

$result = $conn->query($auto_exit_query);

if ($result && $result->num_rows > 0) {
    while ($vehicle = $result->fetch_assoc()) {
        $vehicle_number = $vehicle['vehicle_number'];
        $slot_number = $vehicle['slot_number'];
        $entry_time = $vehicle['entry_time'];
        $exit_time = date('Y-m-d H:i:s');
        
        // Calculate parking fee (Rs. 100 per hour)
        $duration = (strtotime($exit_time) - strtotime($entry_time)) / 3600;
        $fee = $duration * 100;
        
        // Move to exited_vehicles table
        $conn->query("
            INSERT INTO exited_vehicles 
                (vehicle_number, vehicle_type, slot_number, entry_time, exit_time, fee)
            VALUES 
                ('$vehicle_number', '{$vehicle['vehicle_type']}', '$slot_number', 
                 '$entry_time', '$exit_time', $fee)
        ");
        
        // Remove from vehicles table
        $conn->query("DELETE FROM vehicles WHERE vehicle_number = '$vehicle_number'");
        
        // Free up the parking slot
        $conn->query("UPDATE parking_slots SET is_occupied = 0 WHERE slot_number = '$slot_number'");
        
        echo "Auto-exited: $vehicle_number from slot $slot_number (Fee: Rs. $fee)\n";
    }
}

// Auto-expire old bookings (pending bookings older than 24 hours)
$conn->query("
    UPDATE bookings 
    SET status = 'expired' 
    WHERE status = 'pending' 
    AND TIMESTAMPDIFF(HOUR, created_at, NOW()) >= 24
");

echo "Auto-process completed at " . date('Y-m-d H:i:s') . "\n";
?>
