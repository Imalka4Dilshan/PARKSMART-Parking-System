<?php
session_start();
date_default_timezone_set('Asia/Colombo');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login2.php');
    exit();
}

include __DIR__ . '/../config/db.php';
include __DIR__ . '/../config/email.php';
$user_id = $_SESSION['user_id'];

// Get user info
$user_query = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user_info = $user_query->get_result()->fetch_assoc();

// Create contact_messages table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    username VARCHAR(255),
    email VARCHAR(255),
    message_type VARCHAR(50) DEFAULT 'contact',
    subject VARCHAR(500),
    message TEXT,
    status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX(user_id),
    INDEX(message_type),
    INDEX(status),
    INDEX(created_at)
)");

$message = '';
$error = '';

// Handle message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_message'])) {
    $message_type = $_POST['message_type'] ?? 'contact';
    $subject = trim($_POST['subject'] ?? '');
    $user_message = trim($_POST['message'] ?? '');
    
    // Validate
    if (empty($message_type) || !in_array($message_type, ['contact', 'complaint'])) {
        $error = 'Please select a valid message type';
    } elseif (strlen($subject) < 5) {
        $error = 'Subject must be at least 5 characters';
    } elseif (strlen($user_message) < 20) {
        $error = 'Message must be at least 20 characters';
    } else {
        // Insert message
        $stmt = $conn->prepare("INSERT INTO contact_messages (user_id, username, email, message_type, subject, message) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssss", $user_id, $user_info['username'], $user_info['email'], $message_type, $subject, $user_message);
        
        if ($stmt->execute()) {
            // Send email notification to admin
            $userEmailInfo = array(
                'user_id' => $user_id,
                'username' => $user_info['username'],
                'email' => $user_info['email']
            );
            
            sendContactNotification($userEmailInfo, $message_type, $subject, $user_message);
            
            $message = ($message_type === 'complaint') 
                ? 'Your complaint has been submitted successfully. We will review it and respond soon!' 
                : 'Your message has been sent successfully. We will get back to you soon!';
        } else {
            $error = 'Failed to submit message. Please try again.';
        }
    }
}

// Get user's messages with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$total_messages_query = $conn->prepare("SELECT COUNT(*) as total FROM contact_messages WHERE user_id = ?");
$total_messages_query->bind_param("i", $user_id);
$total_messages_query->execute();
$total_messages = $total_messages_query->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_messages / $per_page);

$messages_query = $conn->prepare("SELECT * FROM contact_messages WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
$messages_query->bind_param("iii", $user_id, $per_page, $offset);
$messages_query->execute();
$messages = $messages_query->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - ParkSmart</title>
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
            padding-bottom: 50px;
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 40px;
            color: white;
            padding-top: 30px;
        }

        .page-header h1 {
            font-size: 48px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 15px;
            text-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }

        .page-header p {
            font-size: 18px;
            opacity: 0.95;
            font-weight: 500;
        }

        .alert-message {
            padding: 18px 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            font-weight: 600;
            font-size: 15px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            animation: slideDown 0.5s ease;
        }

        .alert-success {
            background: linear-gradient(135deg, #4caf50 0%, #2e7d32 100%);
            color: white;
        }

        .alert-error {
            background: linear-gradient(135deg, #f44336 0%, #c62828 100%);
            color: white;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .contact-section {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 50px rgba(0,0,0,0.25);
            margin-bottom: 40px;
        }

        .section-title {
            font-size: 28px;
            font-weight: 800;
            color: #667eea;
            margin-bottom: 30px;
            text-transform: uppercase;
            letter-spacing: 2px;
            border-bottom: 4px solid #667eea;
            padding-bottom: 15px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            font-size: 16px;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-control {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 15px;
            font-family: inherit;
            transition: all 0.3s ease;
            background: #f9f9f9;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 4px 15px rgba(102,126,234,0.2);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 150px;
        }

        .radio-group {
            display: flex;
            gap: 25px;
            margin-top: 10px;
        }

        .radio-option {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            padding: 12px 24px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            transition: all 0.3s ease;
            background: white;
        }

        .radio-option:hover {
            border-color: #667eea;
            background: rgba(102,126,234,0.05);
        }

        .radio-option input[type="radio"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .radio-option label {
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            color: #333;
        }

        .radio-option input[type="radio"]:checked + label {
            color: #667eea;
        }

        .btn-submit {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(102,126,234,0.4);
            margin-top: 10px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(102,126,234,0.5);
        }

        .messages-section {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 50px rgba(0,0,0,0.25);
        }

        .message-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            border-left: 5px solid #667eea;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .message-card:hover {
            transform: translateX(5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .message-type-badge {
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .badge-contact {
            background: linear-gradient(135deg, #2196f3 0%, #1565c0 100%);
            color: white;
        }

        .badge-complaint {
            background: linear-gradient(135deg, #ff9800 0%, #ef6c00 100%);
            color: white;
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .status-pending {
            background: linear-gradient(135deg, #ffc107 0%, #f57c00 100%);
            color: white;
        }

        .status-reviewed {
            background: linear-gradient(135deg, #4caf50 0%, #2e7d32 100%);
            color: white;
        }

        .message-subject {
            font-size: 20px;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }

        .message-text {
            color: #555;
            line-height: 1.6;
            font-size: 15px;
            margin-bottom: 10px;
        }

        .message-date {
            color: #888;
            font-size: 13px;
            font-weight: 600;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            margin-top: 30px;
        }

        .pagination a {
            padding: 12px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 700;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102,126,234,0.3);
        }

        .pagination a:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102,126,234,0.4);
        }

        .pagination span {
            color: white;
            font-weight: 700;
            font-size: 16px;
        }

        .contact-info {
            background: linear-gradient(135deg, #e8eaf6 0%, #c5cae9 100%);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            border-left: 5px solid #667eea;
        }

        .contact-info h3 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 20px;
            font-weight: 800;
        }

        .contact-info p {
            color: #333;
            line-height: 1.8;
            margin-bottom: 10px;
            font-size: 15px;
        }

        .no-messages {
            text-align: center;
            padding: 60px 20px;
            color: #999;
            font-size: 18px;
            font-weight: 600;
        }

        /* Quick Contact Buttons */
        .quick-contact-buttons {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }

        .quick-btn {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 20px 30px;
            border-radius: 15px;
            font-size: 18px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .whatsapp-btn {
            background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
            color: white;
        }

        .whatsapp-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(37,211,102,0.4);
        }

        .email-btn {
            background: linear-gradient(135deg, #EA4335 0%, #C5221F 100%);
            color: white;
        }

        .email-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(234,67,53,0.4);
        }

        .quick-btn-icon {
            font-size: 28px;
        }

        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 32px;
            }

            .contact-section, .messages-section {
                padding: 25px;
            }

            .radio-group {
                flex-direction: column;
                gap: 15px;
            }

            .message-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .quick-contact-buttons {
                flex-direction: column;
                gap: 15px;
            }

            .quick-btn {
                padding: 18px 25px;
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <?php include 'user_nav.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1>📧 Contact Us</h1>
            <p>We're here to help! Send us your questions, feedback, or complaints</p>
        </div>

        <?php if ($message): ?>
            <div class="alert-message alert-success">
                ✓ <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert-message alert-error">
                ✗ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Contact Information -->
        <div class="contact-section">
            <div class="contact-info">
                <h3>📍 ParkSmart Customer Support</h3>
                <p><strong>Email:</strong> imalkadilshan1233@gmail.com</p>
                <p><strong>Phone:</strong> +94 11 234 5678</p>
                <p><strong>WhatsApp:</strong> 070 430 5875</p>
                <p><strong>Address:</strong> 123 Main Street, Colombo 00100, Sri Lanka</p>
                <p><strong>Business Hours:</strong> Monday - Friday, 8:00 AM - 6:00 PM</p>
            </div>

            <!-- Quick Contact Buttons -->
            <div class="quick-contact-buttons">
                <a href="https://wa.me/94704305875?text=Hello%20ParkSmart%20Support%20Team%2C%0A%0AI%20am%20contacting%20you%20regarding%20your%20parking%20service%20and%20would%20appreciate%20your%20assistance%20with%20%5Bbriefly%20describe%20your%20concern%20or%20request%20here%5D.%0A%0AThank%20you%20for%20your%20support.%0ABest%20regards%2C%0A%5BYour%20Name%5D" class="quick-btn whatsapp-btn" target="_blank">
                    <span class="quick-btn-icon">💬</span>
                    <span>Chat on WhatsApp</span>
                </a>
                <a href="https://mail.google.com/mail/?view=cm&fs=1&to=imalkadilshan1233@gmail.com" class="quick-btn email-btn" target="_blank">
                    <span class="quick-btn-icon">✉️</span>
                    <span>Send Email</span>
                </a>
            </div>

            <div class="section-title">✉️ Send Us a Message</div>

            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Message Type *</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" name="message_type" value="contact" id="type_contact" required checked>
                            <label for="type_contact">💬 General Contact</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" name="message_type" value="complaint" id="type_complaint" required>
                            <label for="type_complaint">⚠️ Complaint</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="subject">Subject *</label>
                    <input type="text" id="subject" name="subject" class="form-control" 
                           placeholder="Enter subject (minimum 5 characters)" required minlength="5">
                </div>

                <div class="form-group">
                    <label class="form-label" for="message">Your Message *</label>
                    <textarea id="message" name="message" class="form-control" 
                              placeholder="Please provide details about your inquiry or complaint (minimum 20 characters)..." 
                              required minlength="20"></textarea>
                </div>

                <button type="submit" name="submit_message" class="btn-submit">
                    📤 Send Message
                </button>
            </form>
        </div>

        <!-- User's Previous Messages -->
        <div class="messages-section">
            <div class="section-title">📋 My Messages (<?php echo $total_messages; ?>)</div>

            <?php if ($messages->num_rows > 0): ?>
                <?php while ($msg = $messages->fetch_assoc()): ?>
                    <div class="message-card">
                        <div class="message-header">
                            <div>
                                <span class="message-type-badge <?php echo $msg['message_type'] === 'complaint' ? 'badge-complaint' : 'badge-contact'; ?>">
                                    <?php echo $msg['message_type'] === 'complaint' ? '⚠️ Complaint' : '💬 Contact'; ?>
                                </span>
                            </div>
                            <div>
                                <span class="status-badge <?php echo $msg['status'] === 'reviewed' ? 'status-reviewed' : 'status-pending'; ?>">
                                    <?php echo $msg['status'] === 'reviewed' ? '✓ Reviewed' : '⏳ Pending'; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="message-subject">
                            <?php echo htmlspecialchars($msg['subject']); ?>
                        </div>
                        
                        <div class="message-text">
                            <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                        </div>
                        
                        <div class="message-date">
                            Submitted on: <?php echo date('F d, Y - h:i A', strtotime($msg['created_at'])); ?>
                        </div>
                    </div>
                <?php endwhile; ?>

                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>">← Previous</a>
                        <?php endif; ?>
                        
                        <span>Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>">Next →</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="no-messages">
                    📭 You haven't sent any messages yet.<br>
                    Use the form above to contact us!
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
