<?php
session_start();
date_default_timezone_set('Asia/Colombo');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login2.php');
    exit();
}

include __DIR__ . '/../config/db.php';

$slot = isset($_GET['slot']) ? $_GET['slot'] : 'A-12';
$booking_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$booking_time = isset($_GET['time']) ? $_GET['time'] : date('H:00');
$booking_hours = isset($_GET['hours']) ? intval($_GET['hours']) : 2;

// Get vehicle type for this slot to determine price
$slot_query = $conn->prepare("SELECT vehicle_type FROM parking_slots WHERE slot_number = ?");
$slot_query->bind_param("s", $slot);
$slot_query->execute();
$slot_result = $slot_query->get_result();
$vehicle_type = 'car'; // default
if ($slot_result && $slot_result->num_rows > 0) {
    $vehicle_type = $slot_result->fetch_assoc()['vehicle_type'];
}

// Set hourly price based on vehicle type
if ($vehicle_type === 'car') {
    $hourly_price = 300;
} else if ($vehicle_type === 'bike') {
    $hourly_price = 150;
} else if ($vehicle_type === 'van') {
    $hourly_price = 200;
} else if ($vehicle_type === 'jeep') {
    $hourly_price = 350;
} else if ($vehicle_type === 'lorry') {
    $hourly_price = 400;
} else if ($vehicle_type === 'threewheel') {
    $hourly_price = 100;
} else if ($vehicle_type === 'bus') {
    $hourly_price = 500;
} else {
    $hourly_price = 300; // default
}

// Calculate total price based on hours
$price = $hourly_price * $booking_hours;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - ParkSmart</title>
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
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .page-title {
            font-size: 24px;
            font-weight: 800;
            color: #2c3e50;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .container {
            padding: 25px 20px;
            max-width: 650px;
            margin: 0 auto;
        }

        .summary-card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            margin-bottom: 25px;
        }

        .slot-number {
            font-size: 22px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .total-price {
            font-size: 36px;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-top: 15px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        .payment-method {
            background: rgba(255,255,255,0.9);
            border-radius: 16px;
            padding: 18px;
            margin-bottom: 15px;
            cursor: pointer;
            border: 2px solid rgba(102,126,234,0.2);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .payment-method:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102,126,234,0.3);
        }

        .payment-method.selected {
            border-color: #667eea;
            background: rgba(102,126,234,0.1);
            box-shadow: 0 8px 25px rgba(102,126,234,0.3);
        }

        .payment-radio {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            border: 2px solid #bdc3c7;
            position: relative;
            flex-shrink: 0;
        }

        .payment-method.selected .payment-radio {
            border-color: #667eea;
        }

        .payment-method.selected .payment-radio::after {
            content: '';
            position: absolute;
            width: 12px;
            height: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .payment-icon {
            font-size: 32px;
        }

        .payment-info {
            flex: 1;
        }

        .payment-name {
            font-size: 16px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 4px;
        }

        .payment-desc {
            font-size: 14px;
            color: #7f8c8d;
        }

        .details-section {
            display: none;
            margin-top: 25px;
        }

        .details-section.active {
            display: block;
        }

        .upload-section {
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 25px;
            text-align: center;
        }

        .upload-box {
            border: 2px dashed rgba(102,126,234,0.3);
            border-radius: 16px;
            padding: 36px;
            cursor: pointer;
            margin-top: 15px;
            transition: all 0.3s ease;
            background: rgba(255,255,255,0.5);
        }

        .upload-box:hover {
            border-color: #667eea;
            background: rgba(102,126,234,0.05);
        }

        .upload-icon {
            font-size: 48px;
            margin-bottom: 12px;
        }

        .upload-text {
            font-size: 16px;
            color: #7f8c8d;
            font-weight: 600;
        }

        .file-info {
            margin-top: 12px;
            color: #27ae60;
            font-weight: 700;
            font-size: 14px;
        }

        .input-group {
            margin-bottom: 20px;
        }

        .input-label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .input-label .required {
            color: #e74c3c;
            margin-left: 4px;
            font-size: 14px;
        }

        .input-field.error {
            border-color: #e74c3c;
            background: rgba(231, 76, 60, 0.05);
        }

        .error-message {
            color: #e74c3c;
            font-size: 12px;
            margin-top: 6px;
            display: none;
            font-weight: 600;
        }

        .error-message.show {
            display: block;
        }

        .input-field {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid rgba(102,126,234,0.2);
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: rgba(255,255,255,0.8);
        }

        .input-field:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 5px 20px rgba(102,126,234,0.3);
            transform: translateY(-2px);
        }

        .btn-pay {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            cursor: pointer;
            margin-top: 30px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 8px 25px rgba(102,126,234,0.4);
            position: relative;
            overflow: hidden;
        }

        .btn-pay::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.2);
            transition: left 0.5s ease;
        }

        .btn-pay:hover::before {
            left: 100%;
        }

        .btn-pay:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(102,126,234,0.5);
        }

        .btn-pay:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
    </style>
</head>
<body>
    <?php include 'user_nav.php'; ?>
    
    <div class="page-header">
        <div class="page-title">💳 Payment for Slot <?php echo htmlspecialchars($slot); ?></div>
    </div>

    <div class="container">
        <div class="summary-card">
            <div class="slot-number">Slot <?php echo htmlspecialchars($slot); ?></div>
            <div style="color: #5F6368; font-size: 14px; margin-bottom: 16px;"><?php echo ucfirst($vehicle_type); ?> Parking</div>
            
            <div class="booking-details">
                <div class="booking-row">
                    <span class="booking-label">📅 Booking Date:</span>
                    <span class="booking-value"><?php echo date('M d, Y', strtotime($booking_date)); ?></span>
                </div>
                <div class="booking-row">
                    <span class="booking-label">🕐 Start Time:</span>
                    <span class="booking-value"><?php echo date('h:i A', strtotime($booking_time)); ?></span>
                </div>
                <div class="booking-row">
                    <span class="booking-label">⏱️ Duration:</span>
                    <span class="booking-value"><?php echo $booking_hours; ?> Hour<?php echo $booking_hours > 1 ? 's' : ''; ?></span>
                </div>
                <div class="booking-row">
                    <span class="booking-label">💰 Rate:</span>
                    <span class="booking-value">Rs. <?php echo number_format($hourly_price, 2); ?> / hour</span>
                </div>
            </div>
            
            <div class="total-price">Total: Rs. <?php echo number_format($price, 2); ?></div>
        </div>

        <div class="section-title">Select Payment Method</div>

        <form method="POST" action="process_payment.php" enctype="multipart/form-data" id="paymentForm">
            <input type="hidden" name="slot" value="<?php echo htmlspecialchars($slot); ?>">
            <input type="hidden" name="amount" value="<?php echo $price; ?>">
            <input type="hidden" name="booking_date" value="<?php echo htmlspecialchars($booking_date); ?>">
            <input type="hidden" name="booking_time" value="<?php echo htmlspecialchars($booking_time); ?>">
            <input type="hidden" name="booking_hours" value="<?php echo $booking_hours; ?>">
            <input type="hidden" name="payment_method" id="payment_method" value="">
            <input type="hidden" name="payment_method" id="payment_method" value="">

            <!-- Payment Method Selection -->
            <div class="payment-method" onclick="selectMethod('card')" id="method-card">
                <div class="payment-radio"></div>
                <div class="payment-icon">💳</div>
                <div class="payment-info">
                    <div class="payment-name">Card Payment</div>
                    <div class="payment-desc">Pay using Credit/Debit Card</div>
                </div>
            </div>

            <div class="payment-method" onclick="selectMethod('pdf')" id="method-pdf">
                <div class="payment-radio"></div>
                <div class="payment-icon">🏦</div>
                <div class="payment-info">
                    <div class="payment-name">Online Bank Transfer</div>
                    <div class="payment-desc">Upload bank transfer receipt in PDF format</div>
                </div>
            </div>

            <div class="payment-method" onclick="selectMethod('image')" id="method-image">
                <div class="payment-radio"></div>
                <div class="payment-icon">📱</div>
                <div class="payment-info">
                    <div class="payment-name">QR Payment</div>
                    <div class="payment-desc">Upload QR payment screenshot (JPG, PNG)</div>
                </div>
            </div>

            <!-- Card Payment Details -->
            <div class="details-section" id="card-details">
                <div class="input-group">
                    <label class="input-label">Card Number <span class="required">*</span></label>
                    <input type="text" class="input-field" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19">
                    <div class="error-message" id="card_number_error">Please enter a valid card number</div>
                </div>
                <div style="display: flex; gap: 12px;">
                    <div class="input-group" style="flex: 1;">
                        <label class="input-label">Expiry Date <span class="required">*</span></label>
                        <input type="text" class="input-field" id="card_expiry" name="card_expiry" placeholder="MM/YY" maxlength="5">
                        <div class="error-message" id="card_expiry_error">Enter MM/YY format</div>
                    </div>
                    <div class="input-group" style="flex: 1;">
                        <label class="input-label">CVV <span class="required">*</span></label>
                        <input type="text" class="input-field" id="card_cvv" name="card_cvv" placeholder="123" maxlength="3">
                        <div class="error-message" id="card_cvv_error">Enter 3-digit CVV</div>
                    </div>
                </div>
                <div class="input-group">
                    <label class="input-label">Cardholder Name <span class="required">*</span></label>
                    <input type="text" class="input-field" name="card_name" id="card_name" placeholder="John Doe">
                    <div class="error-message" id="card_name_error">Please enter cardholder name</div>
                </div>
            </div>

            <!-- PDF Upload Details -->
            <div class="details-section" id="pdf-details">
                <div class="upload-section">
                    <h3 style="color: #2c3e50; margin-bottom: 12px;">Upload Bank Transfer Receipt (PDF) <span class="required">*</span></h3>
                    <p style="color: #7f8c8d; font-size: 14px; margin-bottom: 16px;">Please upload your bank transfer receipt in PDF format</p>
                    <label for="pdf-file" class="upload-box">
                        <div class="upload-icon">🏦</div>
                        <div class="upload-text" id="pdf-text">Tap to Upload Bank Receipt (PDF)</div>
                        <div class="file-info" id="pdf-info" style="display: none;"></div>
                    </label>
                    <input type="file" id="pdf-file" name="payment_proof" accept=".pdf" style="display: none;">
                </div>
            </div>

            <!-- Image Upload Details -->
            <div class="details-section" id="image-details">
                <div class="upload-section">
                    <h3 style="color: #2c3e50; margin-bottom: 12px;">Upload QR Payment Screenshot <span class="required">*</span></h3>
                    <p style="color: #7f8c8d; font-size: 14px; margin-bottom: 16px;">Please upload your QR payment screenshot in JPG or PNG format</p>
                    <label for="image-file" class="upload-box">
                        <div class="upload-icon">📱</div>
                        <div class="upload-text" id="image-text">Tap to Upload QR Screenshot</div>
                        <div class="file-info" id="image-info" style="display: none;"></div>
                    </label>
                    <input type="file" id="image-file" name="payment_proof" accept=".jpg,.jpeg,.png" style="display: none;">
                </div>
            </div>

            <button type="submit" class="btn-pay" id="payBtn" disabled>Complete Payment</button>
        </form>
    </div>

    <script>
        let selectedMethod = null;

        function selectMethod(method) {
            selectedMethod = method;
            
            // Update radio buttons
            document.querySelectorAll('.payment-method').forEach(el => {
                el.classList.remove('selected');
            });
            document.getElementById('method-' + method).classList.add('selected');
            
            // Show/hide details
            document.querySelectorAll('.details-section').forEach(el => {
                el.classList.remove('active');
            });
            document.getElementById(method + '-details').classList.add('active');
            
            // Update hidden field
            document.getElementById('payment_method').value = method;
            
            // Enable/disable button based on method
            if (method === 'card') {
                document.getElementById('payBtn').disabled = false;
            } else {
                document.getElementById('payBtn').disabled = true;
            }
        }

        // Form validation before submission
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            let isValid = true;
            
            if (!selectedMethod) {
                e.preventDefault();
                alert('Please select a payment method');
                return;
            }
            
            if (selectedMethod === 'card') {
                // Validate card number
                const cardNumber = document.getElementById('card_number').value.replace(/\s/g, '');
                if (cardNumber.length < 13) {
                    document.getElementById('card_number_error').classList.add('show');
                    document.getElementById('card_number').classList.add('error');
                    isValid = false;
                } else {
                    document.getElementById('card_number_error').classList.remove('show');
                    document.getElementById('card_number').classList.remove('error');
                }
                
                // Validate expiry date
                const expiry = document.getElementById('card_expiry').value;
                if (expiry.length !== 5 || !expiry.includes('/')) {
                    document.getElementById('card_expiry_error').classList.add('show');
                    document.getElementById('card_expiry').classList.add('error');
                    isValid = false;
                } else {
                    document.getElementById('card_expiry_error').classList.remove('show');
                    document.getElementById('card_expiry').classList.remove('error');
                }
                
                // Validate CVV
                const cvv = document.getElementById('card_cvv').value;
                if (cvv.length !== 3) {
                    document.getElementById('card_cvv_error').classList.add('show');
                    document.getElementById('card_cvv').classList.add('error');
                    isValid = false;
                } else {
                    document.getElementById('card_cvv_error').classList.remove('show');
                    document.getElementById('card_cvv').classList.remove('error');
                }
                
                // Validate cardholder name
                const cardName = document.getElementById('card_name').value.trim();
                if (cardName.length < 3) {
                    document.getElementById('card_name_error').classList.add('show');
                    document.getElementById('card_name').classList.add('error');
                    isValid = false;
                } else {
                    document.getElementById('card_name_error').classList.remove('show');
                    document.getElementById('card_name').classList.remove('error');
                }
            } else if (selectedMethod === 'pdf') {
                // Validate PDF file
                const pdfFile = document.getElementById('pdf-file').files[0];
                if (!pdfFile) {
                    alert('Please upload a PDF file');
                    isValid = false;
                } else if (!pdfFile.name.toLowerCase().endsWith('.pdf')) {
                    alert('Please upload a valid PDF file');
                    isValid = false;
                }
            } else if (selectedMethod === 'image') {
                // Validate image file
                const imageFile = document.getElementById('image-file').files[0];
                if (!imageFile) {
                    alert('Please upload an image file');
                    isValid = false;
                } else {
                    const validExtensions = ['.jpg', '.jpeg', '.png'];
                    const fileName = imageFile.name.toLowerCase();
                    const isValidExt = validExtensions.some(ext => fileName.endsWith(ext));
                    if (!isValidExt) {
                        alert('Please upload a valid image file (JPG, PNG)');
                        isValid = false;
                    }
                }
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });

        // Card number formatting - add spaces every 4 digits
        const cardNumberInput = document.getElementById('card_number');
        if (cardNumberInput) {
            cardNumberInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\s/g, ''); // Remove all spaces
                value = value.replace(/\D/g, ''); // Remove non-digits
                
                // Add space every 4 digits
                let formattedValue = '';
                for (let i = 0; i < value.length; i++) {
                    if (i > 0 && i % 4 === 0) {
                        formattedValue += ' ';
                    }
                    formattedValue += value[i];
                }
                
                e.target.value = formattedValue;
            });
        }

        // Card expiry formatting - auto add "/" after MM
        const cardExpiryInput = document.getElementById('card_expiry');
        if (cardExpiryInput) {
            cardExpiryInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
                
                if (value.length >= 2) {
                    // Add "/" after first 2 digits (month)
                    value = value.substring(0, 2) + '/' + value.substring(2, 4);
                }
                
                e.target.value = value;
            });

            // Handle backspace to remove "/" properly
            cardExpiryInput.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace') {
                    let value = e.target.value;
                    if (value.length === 3 && value[2] === '/') {
                        e.preventDefault();
                        e.target.value = value.substring(0, 2);
                    }
                }
            });
        }

        // CVV - only allow numbers
        const cardCvvInput = document.getElementById('card_cvv');
        if (cardCvvInput) {
            cardCvvInput.addEventListener('input', function(e) {
                e.target.value = e.target.value.replace(/\D/g, ''); // Only numbers
            });
        }

        // PDF file upload handler
        document.getElementById('pdf-file').addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                const file = e.target.files[0];
                const fileName = file.name;
                const fileSize = (file.size / 1024 / 1024).toFixed(2); // MB
                
                document.getElementById('pdf-text').textContent = 'Selected File:';
                document.getElementById('pdf-info').textContent = fileName + ' (' + fileSize + ' MB)';
                document.getElementById('pdf-info').style.display = 'block';
                
                // Enable submit button
                document.getElementById('payBtn').disabled = false;
            }
        });

        // Image file upload handler
        document.getElementById('image-file').addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                const file = e.target.files[0];
                const fileName = file.name;
                const fileSize = (file.size / 1024 / 1024).toFixed(2); // MB
                
                document.getElementById('image-text').textContent = 'Selected File:';
                document.getElementById('image-info').textContent = fileName + ' (' + fileSize + ' MB)';
                document.getElementById('image-info').style.display = 'block';
                
                // Enable submit button
                document.getElementById('payBtn').disabled = false;
            }
        });
    </script>
</body>
</html>
