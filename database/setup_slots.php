<?php
// Auto-setup parking slots for all vehicle types
include __DIR__ . '/../config/db.php';

echo "<h2>Setting up Parking Slots...</h2>";

// Clear existing slots
$conn->query("TRUNCATE TABLE parking_slots");
echo "<p>✓ Cleared existing slots</p>";

$total_added = 0;

// Add 50 Car slots
for ($i = 1; $i <= 50; $i++) {
    $slot_number = 'C-' . str_pad($i, 2, '0', STR_PAD_LEFT);
    $conn->query("INSERT INTO parking_slots (slot_number, vehicle_type, is_occupied) VALUES ('$slot_number', 'car', 0)");
    $total_added++;
}
echo "<p>✓ Added 50 Car slots (C-01 to C-50)</p>";

// Add 50 Van slots
for ($i = 1; $i <= 50; $i++) {
    $slot_number = 'V-' . str_pad($i, 2, '0', STR_PAD_LEFT);
    $conn->query("INSERT INTO parking_slots (slot_number, vehicle_type, is_occupied) VALUES ('$slot_number', 'van', 0)");
    $total_added++;
}
echo "<p>✓ Added 50 Van slots (V-01 to V-50)</p>";

// Add 50 Jeep slots
for ($i = 1; $i <= 50; $i++) {
    $slot_number = 'J-' . str_pad($i, 2, '0', STR_PAD_LEFT);
    $conn->query("INSERT INTO parking_slots (slot_number, vehicle_type, is_occupied) VALUES ('$slot_number', 'jeep', 0)");
    $total_added++;
}
echo "<p>✓ Added 50 Jeep slots (J-01 to J-50)</p>";

// Add 50 Lorry slots
for ($i = 1; $i <= 50; $i++) {
    $slot_number = 'L-' . str_pad($i, 2, '0', STR_PAD_LEFT);
    $conn->query("INSERT INTO parking_slots (slot_number, vehicle_type, is_occupied) VALUES ('$slot_number', 'lorry', 0)");
    $total_added++;
}
echo "<p>✓ Added 50 Lorry slots (L-01 to L-50)</p>";

// Add 50 Three Wheel slots
for ($i = 1; $i <= 50; $i++) {
    $slot_number = 'T-' . str_pad($i, 2, '0', STR_PAD_LEFT);
    $conn->query("INSERT INTO parking_slots (slot_number, vehicle_type, is_occupied) VALUES ('$slot_number', 'threewheel', 0)");
    $total_added++;
}
echo "<p>✓ Added 50 Three Wheel slots (T-01 to T-50)</p>";

// Add 50 Bus slots
for ($i = 1; $i <= 50; $i++) {
    $slot_number = 'B-' . str_pad($i, 2, '0', STR_PAD_LEFT);
    $conn->query("INSERT INTO parking_slots (slot_number, vehicle_type, is_occupied) VALUES ('$slot_number', 'bus', 0)");
    $total_added++;
}
echo "<p>✓ Added 50 Bus slots (B-01 to B-50)</p>";

// Add 50 Bike slots
for ($i = 1; $i <= 50; $i++) {
    $slot_number = 'BK-' . str_pad($i, 2, '0', STR_PAD_LEFT);
    $conn->query("INSERT INTO parking_slots (slot_number, vehicle_type, is_occupied) VALUES ('$slot_number', 'bike', 0)");
    $total_added++;
}
echo "<p>✓ Added 50 Bike slots (BK-01 to BK-50)</p>";

echo "<h3 style='color: green;'>✅ SUCCESS! Total slots created: $total_added</h3>";
echo "<p><a href='../admin/dash4.php'>Go to Dashboard</a></p>";

$conn->close();
?>
