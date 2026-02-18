<?php
session_start();
date_default_timezone_set('Asia/Colombo');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login2.php');
    exit();
}

include __DIR__ . '/../config/db.php';
$user_id = $_SESSION['user_id'];

// Fetch user's bookings
$stmt = $conn->prepare("SELECT b.*, p.payment_method, p.status as payment_status 
                        FROM bookings b 
                        LEFT JOIN payments p ON b.booking_id = p.booking_id 
                        WHERE b.user_id = ? 
                        ORDER BY b.booking_date DESC, b.start_time DESC 
                        LIMIT 20");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - ParkSmart</title>
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
            padding-bottom: 70px;
        }

        .page-header {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .page-title {
            font-size: 22px;
            font-weight: 800;
            color: #2c3e50;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .container {
            padding: 20px 16px;
            max-width: 900px;
            margin: 0 auto;
        }

        .booking-card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
            transition: all 0.3s ease;
        }

        .booking-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.2);
        }

        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 2px solid rgba(102,126,234,0.15);
        }

        .slot-number {
            font-size: 18px;
            font-weight: 700;
            color: #2c3e50;
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-active {
            background: #27ae60;
            color: white;
        }

        .status-completed {
            background: #95a5a6;
            color: white;
        }

        .status-pending {
            background: #f39c12;
            color: white;
        }

        .booking-details {
            margin-bottom: 10px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            font-size: 13px;
        }

        .detail-label {
            color: #7f8c8d;
            font-weight: 500;
        }

        .detail-value {
            color: #2c3e50;
            font-weight: 600;
        }

        .booking-actions {
            display: flex;
            gap: 8px;
        }

        .btn-small {
            flex: 1;
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            transition: all 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102,126,234,0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102,126,234,0.4);
        }

        .btn-secondary {
            background: rgba(102,126,234,0.1);
            color: #667eea;
            font-weight: 700;
        }

        .btn-secondary:hover {
            background: rgba(102,126,234,0.2);
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 50px 20px;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            margin-top: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
        }            border-radius: 6px;
            margin-top: 20px;
        }

        .empty-icon {
            font-size: 48px;
            margin-bottom: 12px;
            color: #95a5a6;
        }

        .empty-title {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 6px;
        }

        .empty-text {
            font-size: 13px;
            color: #7f8c8d;
            margin-bottom: 20px;
        }

        .btn-book-now {
            padding: 10px 24px;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            border-top: 1px solid #dfe4ea;
            padding: 8px 12px;
            display: flex;
            justify-content: space-around;
            box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.05);
        }

        .nav-item {
            text-align: center;
            cursor: pointer;
            text-decoration: none;
            color: #7f8c8d;
            flex: 1;
            padding: 4px;
            transition: all 0.3s;
        }

        .nav-item.active {
            color: #2a5298;
        }

        .nav-icon {
            font-size: 20px;
            margin-bottom: 2px;
        }

        .nav-label {
            font-size: 11px;
            font-weight: 500;
        }

        /* Edit and Delete buttons */
        .btn-edit {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(52,152,219,0.3);
        }

        .btn-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52,152,219,0.4);
        }

        .btn-delete {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(231,76,60,0.3);
        }

        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(231,76,60,0.4);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
            backdrop-filter: blur(5px);
            animation: fadeIn 0.3s ease;
        }

        .modal.active {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            max-width: 500px;
            width: 90%;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: slideIn 0.3s ease;
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 25px 30px;
            border-radius: 20px 20px 0 0;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .modal-header h2 {
            color: white;
            font-size: 24px;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .modal-body {
            padding: 30px;
        }

        .close-modal {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 32px;
            color: white;
            cursor: pointer;
            background: none;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            transition: all 0.3s;
        }

        .close-modal:hover {
            background: rgba(255,255,255,0.2);
            transform: rotate(90deg);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 600;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102,126,234,0.3);
        }

        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Confirm Dialog */
        .confirm-dialog {
            background: white;
            border-radius: 15px;
            padding: 30px;
            max-width: 400px;
            width: 90%;
            text-align: center;
            animation: slideIn 0.3s ease;
        }

        .confirm-icon {
            font-size: 60px;
            margin-bottom: 20px;
        }

        .confirm-title {
            font-size: 22px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .confirm-message {
            color: #7f8c8d;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .confirm-actions {
            display: flex;
            gap: 10px;
        }

        .btn-confirm {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s;
        }

        .btn-confirm-yes {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
        }

        .btn-confirm-yes:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(231,76,60,0.4);
        }

        .btn-confirm-no {
            background: #ecf0f1;
            color: #2c3e50;
        }

        .btn-confirm-no:hover {
            background: #bdc3c7;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Auto-update indicator */
        .update-indicator {
            position: fixed;
            top: 80px;
            right: 20px;
            background: rgba(46,204,113,0.95);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 13px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            z-index: 999;
            display: none;
            animation: slideInRight 0.3s ease;
        }

        @keyframes slideInRight {
            from { transform: translateX(100px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>
<body>
    <?php include 'user_nav.php'; ?>
    
    <div class="page-header">
        <div class="page-title">MY BOOKINGS HISTORY</div>
    </div>

    <div class="container">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($booking = $result->fetch_assoc()): ?>
                <div class="booking-card">
                    <div class="booking-header">
                        <div class="slot-number">SLOT <?php echo htmlspecialchars($booking['slot_number']); ?></div>
                        <span class="status-badge status-<?php echo $booking['status']; ?>">
                            <?php echo strtoupper($booking['status']); ?>
                        </span>
                    </div>
                    <div class="booking-details">
                        <div class="detail-row">
                            <span class="detail-label">Booking ID:</span>
                            <span class="detail-value">#<?php echo htmlspecialchars($booking['booking_id']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Date:</span>
                            <span class="detail-value"><?php echo date('d M Y', strtotime($booking['booking_date'])); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Start Time:</span>
                            <span class="detail-value"><?php echo date('h:i A', strtotime($booking['start_time'])); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Duration:</span>
                            <span class="detail-value"><?php echo $booking['duration_hours'] ?? 2; ?> Hour<?php echo ($booking['duration_hours'] ?? 2) > 1 ? 's' : ''; ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Payment:</span>
                            <span class="detail-value"><?php echo strtoupper($booking['payment_status'] ?? 'PENDING'); ?></span>
                        </div>
                    </div>
                    <div class="booking-actions">
                        <?php if ($booking['status'] === 'active'): ?>
                            <a href="navigation.php?slot=<?php echo urlencode($booking['slot_number']); ?>" class="btn-small btn-primary">
                                Directions
                            </a>
                        <?php endif; ?>
                        <?php if ($booking['status'] === 'active' || $booking['status'] === 'pending'): ?>
                            <button class="btn-small btn-edit" onclick="openEditModal('<?php echo $booking['booking_id']; ?>', '<?php echo $booking['booking_date']; ?>', '<?php echo $booking['start_time']; ?>', <?php echo $booking['duration_hours'] ?? 2; ?>, '<?php echo $booking['slot_number']; ?>')">
                                Edit
                            </button>
                            <button class="btn-small btn-delete" onclick="openDeleteModal('<?php echo $booking['booking_id']; ?>', '<?php echo $booking['slot_number']; ?>')">
                                Cancel
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">■</div>
                <div class="empty-title">No Bookings Found</div>
                <div class="empty-text">You haven't made any parking reservations yet</div>
                <a href="slot_view.php" class="btn-book-now">Book Now</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="bottom-nav">
        <a href="dashboard.php" class="nav-item">
            <div class="nav-icon">◆</div>
            <div class="nav-label">Home</div>
        </a>
        <a href="slot_view.php" class="nav-item">
            <div class="nav-icon">■</div>
            <div class="nav-label">Slots</div>
        </a>
        <a href="bookings_history.php" class="nav-item active">
            <div class="nav-icon">≡</div>
            <div class="nav-label">Bookings</div>
        </a>
        <a href="profile.php" class="nav-item">
            <div class="nav-icon">●</div>
            <div class="nav-label">Profile</div>
        </a>
    </div>

    <!-- Edit Booking Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>✏️ Edit Booking</h2>
                <button class="close-modal" onclick="closeEditModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editBookingForm" method="POST" action="edit_booking.php">
                    <input type="hidden" id="edit_booking_id" name="booking_id">
                    
                    <div class="form-group">
                        <label class="form-label">Slot Number</label>
                        <input type="text" id="edit_slot_number" class="form-control" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Booking Date</label>
                        <input type="date" id="edit_booking_date" name="booking_date" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Start Time</label>
                        <input type="time" id="edit_start_time" name="start_time" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Duration (Hours)</label>
                        <select id="edit_duration" name="duration_hours" class="form-control" required>
                            <option value="1">1 Hour</option>
                            <option value="2">2 Hours</option>
                            <option value="3">3 Hours</option>
                            <option value="4">4 Hours</option>
                            <option value="6">6 Hours</option>
                            <option value="8">8 Hours</option>
                            <option value="12">12 Hours</option>
                            <option value="24">24 Hours</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn-submit">Update Booking</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="confirm-dialog">
            <div class="confirm-icon">⚠️</div>
            <div class="confirm-title">Cancel Booking?</div>
            <div class="confirm-message">
                Are you sure you want to cancel this booking for <strong id="delete_slot_info">SLOT</strong>?<br>
                This action cannot be undone.
            </div>
            <form id="deleteBookingForm" method="POST" action="delete_booking.php">
                <input type="hidden" id="delete_booking_id" name="booking_id">
                <div class="confirm-actions">
                    <button type="button" class="btn-confirm btn-confirm-no" onclick="closeDeleteModal()">No, Keep It</button>
                    <button type="submit" class="btn-confirm btn-confirm-yes">Yes, Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Update Indicator -->
    <div id="updateIndicator" class="update-indicator">
        ✓ Bookings Updated
    </div>

    <script>
        // Edit Modal Functions
        function openEditModal(bookingId, bookingDate, startTime, duration, slotNumber) {
            console.log('Opening edit modal:', {bookingId, bookingDate, startTime, duration, slotNumber});
            
            document.getElementById('edit_booking_id').value = bookingId;
            document.getElementById('edit_booking_date').value = bookingDate;
            document.getElementById('edit_start_time').value = startTime;
            document.getElementById('edit_duration').value = duration;
            document.getElementById('edit_slot_number').value = 'SLOT ' + slotNumber;
            
            const modal = document.getElementById('editModal');
            if (modal) {
                modal.classList.add('active');
                console.log('Edit modal opened successfully');
            } else {
                console.error('Edit modal not found');
            }
        }

        function closeEditModal() {
            const modal = document.getElementById('editModal');
            if (modal) {
                modal.classList.remove('active');
            }
        }

        // Delete Modal Functions
        function openDeleteModal(bookingId, slotNumber) {
            console.log('Opening delete modal:', {bookingId, slotNumber});
            
            document.getElementById('delete_booking_id').value = bookingId;
            document.getElementById('delete_slot_info').textContent = 'SLOT ' + slotNumber;
            
            const modal = document.getElementById('deleteModal');
            if (modal) {
                modal.classList.add('active');
                console.log('Delete modal opened successfully');
            } else {
                console.error('Delete modal not found');
            }
        }

        function closeDeleteModal() {
            const modal = document.getElementById('deleteModal');
            if (modal) {
                modal.classList.remove('active');
            }
        }

        // Close modals when clicking outside
        window.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                closeEditModal();
                closeDeleteModal();
            }
        });

        // Close modals on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeEditModal();
                closeDeleteModal();
            }
        });

        // Close modals with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeEditModal();
                closeDeleteModal();
            }
        });

        // Auto-update functionality - refresh every 30 seconds
        let autoUpdateInterval;
        let lastUpdate = Date.now();

        function showUpdateIndicator() {
            const indicator = document.getElementById('updateIndicator');
            indicator.style.display = 'block';
            setTimeout(() => {
                indicator.style.display = 'none';
            }, 3000);
        }

        function checkForUpdates() {
            // Check if we need to refresh (every 30 seconds)
            const now = Date.now();
            if (now - lastUpdate >= 30000) {
                lastUpdate = now;
                // Silently reload the page to get updated booking data
                location.reload();
            }
        }

        // Start auto-update every 30 seconds
        autoUpdateInterval = setInterval(checkForUpdates, 30000);

        // Handle form submissions with AJAX for better UX
        document.getElementById('editBookingForm').addEventListener('submit', function(e) {
            e.preventDefault();
            console.log('Edit form submitted');
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            
            // Disable button and show loading
            submitBtn.disabled = true;
            submitBtn.textContent = 'Updating...';
            
            fetch('edit_booking.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Edit response received:', response);
                return response.json();
            })
            .then(data => {
                console.log('Edit response data:', data);
                if (data.success) {
                    closeEditModal();
                    showUpdateIndicator();
                    alert('✓ Booking updated successfully!');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                    alert('❌ ' + (data.message || 'Failed to update booking'));
                }
            })
            .catch(error => {
                console.error('Edit error:', error);
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
                alert('❌ An error occurred. Please check the console and try again.');
            });
        });

        document.getElementById('deleteBookingForm').addEventListener('submit', function(e) {
            e.preventDefault();
            console.log('Delete form submitted');
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            
            // Disable button and show loading
            submitBtn.disabled = true;
            submitBtn.textContent = 'Cancelling...';
            
            fetch('delete_booking.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Delete response received:', response);
                return response.json();
            })
            .then(data => {
                console.log('Delete response data:', data);
                if (data.success) {
                    closeDeleteModal();
                    showUpdateIndicator();
                    alert('✓ Booking cancelled successfully!');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                    alert('❌ ' + (data.message || 'Failed to cancel booking'));
                }
            })
            .catch(error => {
                console.error('Delete error:', error);
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
                alert('❌ An error occurred. Please check the console and try again.');
            });
        });

        // Set minimum date for edit form to today
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('edit_booking_date').setAttribute('min', today);
        });
    </script>
</body>
</html>
