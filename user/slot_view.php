<?php
session_start();
date_default_timezone_set('Asia/Colombo');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login2.php');
    exit();
}

include __DIR__ . '/../config/db.php';
$user_id = $_SESSION['user_id'];

// Get available parking slots from database
$slots_query = $conn->query("SELECT slot_number, vehicle_type, is_occupied FROM parking_slots ORDER BY slot_number");
$available_slots = [];
$occupied_slots = [];
while ($row = $slots_query->fetch_assoc()) {
    if ($row['is_occupied'] == 0) {
        $available_slots[] = $row;
    } else {
        $occupied_slots[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parking Slots - ParkSmart</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding-bottom: 40px;
        }

        .page-header {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            padding: 20px 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1200px;
            margin: 0 auto;
        }

        .back-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102,126,234,0.3);
        }

        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102,126,234,0.4);
        }

        .header-title {
            font-size: 24px;
            font-weight: 800;
            color: #2c3e50;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .refresh-btn {
            background: linear-gradient(135deg, #16a085 0%, #117a65 100%);
            border: none;
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            color: white;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(22,160,133,0.3);
        }

        .refresh-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(22,160,133,0.4);
        }

        .filter-section {
            padding: 25px;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            margin: 25px auto;
            max-width: 1200px;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        }

        .filter-label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .filter-dropdown {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid rgba(102,126,234,0.2);
            border-radius: 12px;
            background: white;
            font-size: 15px;
            font-weight: 600;
            color: #2c3e50;
            transition: all 0.3s ease;
        }

        .filter-dropdown:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 5px 20px rgba(102,126,234,0.3);
        }
            color: #2c3e50;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23667eea' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            padding-right: 40px;
        }

        .filter-dropdown:hover {
            border-color: #667eea;
            box-shadow: 0 4px 15px rgba(102,126,234,0.2);
        }

        .slot-count {
            display: inline-block;
            margin-left: 8px;
            padding: 4px 10px;
            background: rgba(102,126,234,0.15);
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
            color: #667eea;
        }

        .container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .parking-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }

        @media (max-width: 1024px) {
            .parking-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 768px) {
            .parking-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .parking-grid {
                grid-template-columns: repeat(1, 1fr);
            }
        }

        .slot-card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            overflow: hidden;
            border: 2px solid rgba(102,126,234,0.15);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .slot-card:hover {
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transform: translateY(-4px);
            border-color: #667eea;
        }

        .slot-card:active {
            transform: translateY(0);
        }

        .slot-card.hidden {
            display: none;
        }

        .slot-image {
            width: 100%;
            height: 130px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .slot-card[data-occupied="true"] .slot-image {
            background: linear-gradient(135deg, #bdc3c7 0%, #95a5a6 100%);
            opacity: 0.6;
        }

        .slot-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .vehicle-icon {
            font-size: 48px;
            color: white;
            text-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }

        .slot-status {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 6px 14px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-available {
            background: #27ae60;
            color: white;
        }

        .status-occupied {
            background: #e74c3c;
            color: white;
        }

        .slot-info {
            padding: 14px;
            background: #fafbfc;
        }

        .slot-number {
            font-size: 15px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 4px;
        }

        .slot-type {
            font-size: 12px;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-content {
            background: rgba(255,255,255,0.98);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            padding: 32px;
            box-shadow: 0 15px 50px rgba(0,0,0,0.3);
        }

        .modal-header {
            border-bottom: 2px solid rgba(102,126,234,0.2);
            padding-bottom: 16px;
            margin-bottom: 20px;
        }

        .modal-image {
            width: 100%;
            height: 180px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 6px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-image .vehicle-icon {
            font-size: 72px;
            color: white;
            text-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .modal-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 6px;
        }

        .modal-title {
            font-size: 22px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 14px;
        }

        .modal-status {
            display: inline-block;
            padding: 6px 14px;
            background: #27ae60;
            color: white;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .modal-info {
            margin: 20px 0;
            padding: 18px;
            background: rgba(102,126,234,0.05);
            border-radius: 12px;
            border-left: 4px solid #667eea;
        }

        .modal-info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .modal-info-row:last-child {
            margin-bottom: 0;
        }

        .modal-info-label {
            font-weight: 600;
            color: #7f8c8d;
        }

        .modal-info-value {
            font-weight: 700;
            color: #2c3e50;
        }

        .booking-form {
            margin: 20px 0;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-label .required {
            color: #e74c3c;
            margin-left: 4px;
        }

        .form-input {
            width: 100%;
            padding: 12px 14px;
            border: 2px solid rgba(102,126,234,0.2);
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: rgba(255,255,255,0.8);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .form-input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 4px 15px rgba(102,126,234,0.2);
        }

        .hours-total {
            margin-top: 12px;
            padding: 12px;
            background: rgba(39,174,96,0.1);
            border-radius: 8px;
            border-left: 4px solid #27ae60;
            font-size: 14px;
            font-weight: 700;
            color: #27ae60;
        }

        .btn-book {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 8px 25px rgba(102,126,234,0.4);
            position: relative;
            overflow: hidden;
        }

        .btn-book::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.2);
            transition: left 0.5s ease;
        }

        .btn-book:hover::before {
            left: 100%;
        }

        .btn-book:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(102,126,234,0.5);
        }

        .btn-close {
            width: 100%;
            padding: 16px;
            background: rgba(102,126,234,0.1);
            color: #667eea;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
        }

        .btn-close:hover {
            background: rgba(102,126,234,0.2);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <?php include 'user_nav.php'; ?>
    
    <div class="page-header">
        <div class="header">
            <button class="back-btn" onclick="history.back()">← Back</button>
            <div class="header-title">PARKING MANAGEMENT</div>
            <button class="refresh-btn" onclick="location.reload()">Refresh</button>
        </div>
    </div>

    <div class="filter-section">
        <label for="vehicleTypeFilter" class="filter-label">
            Filter by Vehicle Type
        </label>
        <select id="vehicleTypeFilter" class="filter-dropdown" onchange="filterSlots(this.value)">
            <option value="all">All Slots (<?= count($available_slots) + count($occupied_slots) ?>)</option>
            <option value="car">Cars (<?= count(array_filter(array_merge($available_slots, $occupied_slots), function($s) { return $s['vehicle_type'] == 'car'; })) ?>)</option>
            <option value="van">Vans (<?= count(array_filter(array_merge($available_slots, $occupied_slots), function($s) { return $s['vehicle_type'] == 'van'; })) ?>)</option>
            <option value="jeep">Jeeps (<?= count(array_filter(array_merge($available_slots, $occupied_slots), function($s) { return $s['vehicle_type'] == 'jeep'; })) ?>)</option>
            <option value="lorry">Lorries (<?= count(array_filter(array_merge($available_slots, $occupied_slots), function($s) { return $s['vehicle_type'] == 'lorry'; })) ?>)</option>
            <option value="threewheel">Three Wheels (<?= count(array_filter(array_merge($available_slots, $occupied_slots), function($s) { return $s['vehicle_type'] == 'threewheel'; })) ?>)</option>
            <option value="bus">Buses (<?= count(array_filter(array_merge($available_slots, $occupied_slots), function($s) { return $s['vehicle_type'] == 'bus'; })) ?>)</option>
            <option value="bike">Bikes (<?= count(array_filter(array_merge($available_slots, $occupied_slots), function($s) { return $s['vehicle_type'] == 'bike'; })) ?>)</option>
        </select>
    </div>

    <div class="container">
        <div class="parking-grid">
            <?php 
            // Show available slots first
            foreach($available_slots as $slot): 
                $icon = '🚗'; // default car
                $vehicleLabel = 'Vehicle';
                if ($slot['vehicle_type'] == 'car') { $icon = '🚗'; $vehicleLabel = 'Car'; }
                else if ($slot['vehicle_type'] == 'van') { $icon = '🚐'; $vehicleLabel = 'Van'; }
                else if ($slot['vehicle_type'] == 'jeep') { $icon = '🚙'; $vehicleLabel = 'Jeep'; }
                else if ($slot['vehicle_type'] == 'lorry') { $icon = '🚚'; $vehicleLabel = 'Lorry'; }
                else if ($slot['vehicle_type'] == 'threewheel') { $icon = '🛺'; $vehicleLabel = 'Three Wheel'; }
                else if ($slot['vehicle_type'] == 'bus') { $icon = '🚌'; $vehicleLabel = 'Bus'; }
                else if ($slot['vehicle_type'] == 'bike') { $icon = '🏍️'; $vehicleLabel = 'Bike'; }
            ?>
            <div class="slot-card" data-type="<?= htmlspecialchars($slot['vehicle_type']) ?>" data-occupied="false" onclick="openSlot('<?= htmlspecialchars($slot['slot_number']) ?>', 'available', '<?= htmlspecialchars($slot['vehicle_type']) ?>')">
                <div class="slot-image">
                    <span class="slot-status status-available">AVAILABLE</span>
                    <div class="vehicle-icon"><?= $icon ?></div>
                </div>
                <div class="slot-info">
                    <div class="slot-number"><?= htmlspecialchars($slot['slot_number']) ?></div>
                    <div class="slot-type"><?= htmlspecialchars($vehicleLabel) ?></div>
                </div>
            </div>
            <?php endforeach; ?>

            <?php 
            // Show occupied slots
            foreach($occupied_slots as $slot): 
                $icon = '🚗'; // default car
                $vehicleLabel = 'Vehicle';
                if ($slot['vehicle_type'] == 'car') { $icon = '🚗'; $vehicleLabel = 'Car'; }
                else if ($slot['vehicle_type'] == 'van') { $icon = '🚐'; $vehicleLabel = 'Van'; }
                else if ($slot['vehicle_type'] == 'jeep') { $icon = '🚙'; $vehicleLabel = 'Jeep'; }
                else if ($slot['vehicle_type'] == 'lorry') { $icon = '🚚'; $vehicleLabel = 'Lorry'; }
                else if ($slot['vehicle_type'] == 'threewheel') { $icon = '🛺'; $vehicleLabel = 'Three Wheel'; }
                else if ($slot['vehicle_type'] == 'bus') { $icon = '🚌'; $vehicleLabel = 'Bus'; }
                else if ($slot['vehicle_type'] == 'bike') { $icon = '🏍️'; $vehicleLabel = 'Bike'; }
            ?>
            <div class="slot-card" data-type="<?= htmlspecialchars($slot['vehicle_type']) ?>" data-occupied="true">
                <div class="slot-image">
                    <span class="slot-status status-occupied">OCCUPIED</span>
                    <div class="vehicle-icon" style="opacity: 0.4;"><?= $icon ?></div>
                </div>
                <div class="slot-info">
                    <div class="slot-number" style="color: #95a5a6;"><?= htmlspecialchars($slot['slot_number']) ?></div>
                    <div class="slot-type" style="color: #95a5a6;"><?= htmlspecialchars($vehicleLabel) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="modal-overlay" id="slotModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalSlotNumber">Slot Information</h2>
            </div>
            <div class="modal-image" id="modalImage"></div>
            <span class="modal-status" id="modalStatus">Available</span>
            <div class="modal-info">
                <div class="modal-info-row">
                    <span class="modal-info-label">Vehicle Type:</span>
                    <span class="modal-info-value" id="modalVehicleType"></span>
                </div>
                <div class="modal-info-row">
                    <span class="modal-info-label">Hourly Rate:</span>
                    <span class="modal-info-value" id="modalPrice">Rs. 0</span>
                </div>
            </div>

            <div class="booking-form">
                <div class="form-group">
                    <label class="form-label">Booking Date <span class="required">*</span></label>
                    <input type="date" id="bookingDate" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Start Time <span class="required">*</span></label>
                    <input type="time" id="bookingTime" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Duration (Hours) <span class="required">*</span></label>
                    <input type="number" id="bookingHours" class="form-input" min="1" max="24" value="2" required>
                    <div class="hours-total" id="totalCost">Total: Rs. 0</div>
                </div>
            </div>

            <button class="btn-book" onclick="bookSlot()">Book This Slot</button>
            <button class="btn-close" onclick="closeModal()">Close</button>
        </div>
    </div>

    <script>
        let selectedSlot = '';
        let hourlyRate = 0;
        
        // Set minimum date to today
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date();
            const tomorrow = new Date(today);
            tomorrow.setDate(tomorrow.getDate() + 1);
            
            const dateInput = document.getElementById('bookingDate');
            const minDate = today.toISOString().split('T')[0];
            dateInput.setAttribute('min', minDate);
            dateInput.value = minDate;
            
            // Set default time to current hour + 1
            const currentHour = today.getHours();
            const nextHour = (currentHour + 1) % 24;
            const timeString = String(nextHour).padStart(2, '0') + ':00';
            document.getElementById('bookingTime').value = timeString;
            
            // Calculate cost when hours change
            document.getElementById('bookingHours').addEventListener('input', updateTotalCost);
        });
        
        function updateTotalCost() {
            const hours = parseInt(document.getElementById('bookingHours').value) || 0;
            const total = hourlyRate * hours;
            document.getElementById('totalCost').textContent = 'Total: Rs. ' + total.toLocaleString();
        }
        
        function filterSlots(type) {
            // Show/hide slots based on selected type
            document.querySelectorAll('.slot-card').forEach(card => {
                if (type === 'all') {
                    card.classList.remove('hidden');
                } else {
                    if (card.getAttribute('data-type') === type) {
                        card.classList.remove('hidden');
                    } else {
                        card.classList.add('hidden');
                    }
                }
            });
        }
        
        function openSlot(slotNumber, status, vehicleType) {
            selectedSlot = slotNumber;
            let icon = '🚗';
            let vehicleLabel = 'Vehicle';
            
            // Vehicle type labels and icons
            if (vehicleType === 'car') { icon = '🚗'; vehicleLabel = 'Car'; }
            else if (vehicleType === 'van') { icon = '🚐'; vehicleLabel = 'Van'; }
            else if (vehicleType === 'jeep') { icon = '🚙'; vehicleLabel = 'Jeep'; }
            else if (vehicleType === 'lorry') { icon = '🚚'; vehicleLabel = 'Lorry'; }
            else if (vehicleType === 'threewheel') { icon = '🛺'; vehicleLabel = 'Three Wheel'; }
            else if (vehicleType === 'bus') { icon = '🚌'; vehicleLabel = 'Bus'; }
            else if (vehicleType === 'bike') { icon = '🏍️'; vehicleLabel = 'Bike'; }
            
            // Set price based on vehicle type
            let price = 0;
            if (vehicleType === 'car') {
                price = 300;
            } else if (vehicleType === 'bike') {
                price = 150;
            } else if (vehicleType === 'van') {
                price = 200;
            } else if (vehicleType === 'jeep') {
                price = 350;
            } else if (vehicleType === 'lorry') {
                price = 400;
            } else if (vehicleType === 'threewheel') {
                price = 100;
            } else if (vehicleType === 'bus') {
                price = 500;
            }
            
            document.getElementById('modalSlotNumber').textContent = slotNumber;
            document.getElementById('modalStatus').textContent = (status === 'available' ? 'Available' : 'Occupied');
            document.getElementById('modalVehicleType').textContent = vehicleLabel;
            document.getElementById('modalPrice').textContent = 'Rs. ' + price + ' / hour';
            document.getElementById('modalImage').innerHTML = '<div class="vehicle-icon">' + icon + '</div>';
            
            // Store hourly rate
            hourlyRate = price;
            updateTotalCost();
            
            if (status === 'occupied') {
                document.querySelector('.btn-book').style.display = 'none';
                document.getElementById('modalStatus').style.background = '#e74c3c';
            } else {
                document.querySelector('.btn-book').style.display = 'block';
                document.getElementById('modalStatus').style.background = '#27ae60';
            }
            
            document.getElementById('slotModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('slotModal').classList.remove('active');
            // Reset form
            document.getElementById('bookingHours').value = '2';
        }

        function bookSlot() {
            // Validate booking details
            const date = document.getElementById('bookingDate').value;
            const time = document.getElementById('bookingTime').value;
            const hours = document.getElementById('bookingHours').value;
            
            if (!date || !time || !hours) {
                alert('Please fill in all booking details (date, time, and hours)');
                return;
            }
            
            if (hours < 1 || hours > 24) {
                alert('Duration must be between 1 and 24 hours');
                return;
            }
            
            // Redirect to payment page with booking details
            const params = new URLSearchParams({
                slot: selectedSlot,
                date: date,
                time: time,
                hours: hours
            });
            window.location.href = 'payment.php?' + params.toString();
        }

        // Close modal on overlay click
        document.getElementById('slotModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>
