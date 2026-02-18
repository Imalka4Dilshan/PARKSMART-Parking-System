<?php
/**
 * Email Configuration and Sending Function for ParkSmart
 * Uses Gmail SMTP to send emails reliably
 */

function sendEmail($to, $subject, $htmlBody, $replyTo = null) {
    // Email configuration
    $from = "noreply@parksmart.com";
    $fromName = "ParkSmart System";
    
    // Create email headers
    $headers = array();
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "Content-type: text/html; charset=UTF-8";
    $headers[] = "From: {$fromName} <{$from}>";
    $headers[] = "X-Mailer: PHP/" . phpversion();
    
    if ($replyTo) {
        $headers[] = "Reply-To: {$replyTo}";
    }
    
    // Convert headers array to string
    $headersString = implode("\r\n", $headers);
    
    // Try to send email using PHP mail() function
    $sent = @mail($to, $subject, $htmlBody, $headersString);
    
    // If mail() fails, log the attempt (emails may not work on localhost XAMPP without SMTP config)
    if (!$sent) {
        // Log to file for debugging
        $logFile = __DIR__ . '/../logs/email_log.txt';
        $logDir = dirname($logFile);
        
        if (!file_exists($logDir)) {
            @mkdir($logDir, 0777, true);
        }
        
        $logMessage = date('Y-m-d H:i:s') . " - Failed to send email\n";
        $logMessage .= "To: {$to}\n";
        $logMessage .= "Subject: {$subject}\n";
        $logMessage .= "---\n";
        
        @file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
    
    return $sent;
}

/**
 * Send contact/complaint notification email
 */
function sendContactNotification($userInfo, $messageType, $subject, $message) {
    $adminEmail = "imalkadilshan1233@gmail.com";
    $emailSubject = ($messageType === 'complaint' ? '[COMPLAINT] ' : '[CONTACT] ') . $subject;
    
    $htmlBody = "<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #f5f5f5; margin: 0; padding: 0; }
        .email-container { max-width: 600px; margin: 20px auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px 20px; text-align: center; }
        .header h1 { margin: 0 0 10px 0; font-size: 28px; }
        .header h2 { margin: 0; font-size: 18px; font-weight: normal; }
        .content { padding: 30px 20px; }
        .badge { display: inline-block; padding: 10px 20px; border-radius: 5px; font-weight: bold; margin: 10px 0; font-size: 14px; }
        .complaint { background: #ff9800; color: white; }
        .contact { background: #2196f3; color: white; }
        .info-section { background: #f9f9f9; border-left: 4px solid #667eea; padding: 15px; margin: 15px 0; border-radius: 5px; }
        .info-row { margin: 10px 0; }
        .label { font-weight: bold; color: #667eea; display: inline-block; min-width: 120px; }
        .value { color: #333; }
        .message-box { background: #fff; padding: 20px; border-radius: 8px; margin: 20px 0; border: 2px solid #e0e0e0; }
        .message-box h3 { color: #667eea; margin: 0 0 15px 0; font-size: 18px; }
        .message-box p { margin: 0; white-space: pre-wrap; word-wrap: break-word; }
        .footer { background: #333; color: white; padding: 20px; text-align: center; font-size: 14px; }
        .footer p { margin: 5px 0; }
    </style>
</head>
<body>
    <div class='email-container'>
        <div class='header'>
            <h1>🅿️ ParkSmart</h1>
            <h2>New " . ($messageType === 'complaint' ? 'Complaint' : 'Contact Message') . " Received</h2>
        </div>
        <div class='content'>
            <div class='badge " . ($messageType === 'complaint' ? 'complaint' : 'contact') . "'>
                " . ($messageType === 'complaint' ? '⚠️ COMPLAINT' : '💬 CONTACT MESSAGE') . "
            </div>
            
            <div class='info-section'>
                <div class='info-row'>
                    <span class='label'>From:</span>
                    <span class='value'>" . htmlspecialchars($userInfo['username']) . "</span>
                </div>
                <div class='info-row'>
                    <span class='label'>Email:</span>
                    <span class='value'>" . htmlspecialchars($userInfo['email']) . "</span>
                </div>
                <div class='info-row'>
                    <span class='label'>User ID:</span>
                    <span class='value'>" . htmlspecialchars($userInfo['user_id']) . "</span>
                </div>
                <div class='info-row'>
                    <span class='label'>Subject:</span>
                    <span class='value'>" . htmlspecialchars($subject) . "</span>
                </div>
                <div class='info-row'>
                    <span class='label'>Date & Time:</span>
                    <span class='value'>" . date('F d, Y - h:i A') . "</span>
                </div>
            </div>
            
            <div class='message-box'>
                <h3>Message Details:</h3>
                <p>" . nl2br(htmlspecialchars($message)) . "</p>
            </div>
            
            <div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin-top: 20px;'>
                <p style='margin: 0; color: #1565c0; font-size: 14px;'>
                    <strong>📧 Reply directly to this customer:</strong> " . htmlspecialchars($userInfo['email']) . "
                </p>
            </div>
        </div>
        <div class='footer'>
            <p><strong>ParkSmart Parking Management System</strong></p>
            <p>This is an automated notification from your contact form</p>
            <p style='font-size: 12px; opacity: 0.8;'>123 Main Street, Colombo 00100, Sri Lanka</p>
        </div>
    </div>
</body>
</html>";
    
    $replyTo = $userInfo['email'];
    
    return sendEmail($adminEmail, $emailSubject, $htmlBody, $replyTo);
}

/**
 * Send welcome email to new users
 */
function sendWelcomeEmail($userInfo) {
    $userEmail = $userInfo['email'];
    $userName = $userInfo['name'];
    $userPhone = $userInfo['phone'];
    
    $subject = "Welcome to ParkSmart - Your Account is Ready! 🅿️";
    
    $htmlBody = "<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #f5f5f5; margin: 0; padding: 0; }
        .email-container { max-width: 600px; margin: 20px auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px 20px; text-align: center; }
        .header h1 { margin: 0 0 10px 0; font-size: 36px; }
        .header p { margin: 0; font-size: 18px; opacity: 0.95; }
        .content { padding: 40px 30px; }
        .welcome-box { background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); padding: 25px; border-radius: 10px; border-left: 5px solid #4caf50; margin: 20px 0; }
        .welcome-box h2 { margin: 0 0 10px 0; color: #2e7d32; font-size: 24px; }
        .welcome-box p { margin: 0; color: #1b5e20; font-size: 16px; }
        .user-info { background: #f9f9f9; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 4px solid #667eea; }
        .user-info h3 { margin: 0 0 15px 0; color: #667eea; }
        .info-row { padding: 10px 0; border-bottom: 1px solid #e0e0e0; }
        .info-row:last-child { border-bottom: none; }
        .label { font-weight: bold; color: #667eea; display: inline-block; min-width: 120px; }
        .value { color: #333; }
        .features { margin: 30px 0; }
        .features h3 { color: #667eea; margin-bottom: 15px; }
        .feature-item { display: flex; align-items: flex-start; margin: 15px 0; }
        .feature-icon { font-size: 24px; margin-right: 15px; min-width: 30px; }
        .feature-text { flex: 1; }
        .feature-text strong { color: #667eea; display: block; margin-bottom: 5px; }
        .cta-button { display: block; width: fit-content; margin: 30px auto; padding: 15px 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 10px; font-weight: bold; font-size: 16px; text-align: center; box-shadow: 0 4px 15px rgba(102,126,234,0.4); }
        .support-box { background: #e3f2fd; padding: 20px; border-radius: 10px; margin: 20px 0; text-align: center; }
        .support-box p { margin: 5px 0; color: #1565c0; }
        .footer { background: #333; color: white; padding: 25px; text-align: center; }
        .footer p { margin: 5px 0; font-size: 14px; }
        .footer a { color: #64b5f6; text-decoration: none; }
    </style>
</head>
<body>
    <div class='email-container'>
        <div class='header'>
            <h1>🅿️ Welcome to ParkSmart!</h1>
            <p>Your Smart Parking Solution</p>
        </div>
        
        <div class='content'>
            <div class='welcome-box'>
                <h2>🎉 Account Created Successfully!</h2>
                <p>Hi " . htmlspecialchars($userName) . ", thank you for joining ParkSmart. We're excited to have you on board!</p>
            </div>
            
            <p style='font-size: 16px; color: #555; margin: 20px 0;'>
                Your account has been successfully created and is ready to use. You can now enjoy hassle-free parking management with our smart system.
            </p>
            
            <div class='user-info'>
                <h3>📋 Your Account Details</h3>
                <div class='info-row'>
                    <span class='label'>Name:</span>
                    <span class='value'>" . htmlspecialchars($userName) . "</span>
                </div>
                <div class='info-row'>
                    <span class='label'>Email:</span>
                    <span class='value'>" . htmlspecialchars($userEmail) . "</span>
                </div>
                <div class='info-row'>
                    <span class='label'>Phone:</span>
                    <span class='value'>" . htmlspecialchars($userPhone) . "</span>
                </div>
                <div class='info-row'>
                    <span class='label'>Status:</span>
                    <span class='value' style='color: #4caf50; font-weight: bold;'>✓ Active</span>
                </div>
            </div>
            
            <div class='features'>
                <h3>🚀 What You Can Do Now:</h3>
                
                <div class='feature-item'>
                    <div class='feature-icon'>🅿️</div>
                    <div class='feature-text'>
                        <strong>Book Parking Slots</strong>
                        <span style='color: #666;'>Reserve your parking spot in advance with real-time slot availability</span>
                    </div>
                </div>
                
                <div class='feature-item'>
                    <div class='feature-icon'>📋</div>
                    <div class='feature-text'>
                        <strong>Track Bookings</strong>
                        <span style='color: #666;'>View and manage all your parking reservations in one place</span>
                    </div>
                </div>
                
                <div class='feature-item'>
                    <div class='feature-icon'>⭐</div>
                    <div class='feature-text'>
                        <strong>Leave Reviews</strong>
                        <span style='color: #666;'>Share your experience and help improve our service</span>
                    </div>
                </div>
                
                <div class='feature-item'>
                    <div class='feature-icon'>💳</div>
                    <div class='feature-text'>
                        <strong>Easy Payments</strong>
                        <span style='color: #666;'>Multiple payment options including card, bank transfer, and QR payment</span>
                    </div>
                </div>
                
                <div class='feature-item'>
                    <div class='feature-icon'>🗺️</div>
                    <div class='feature-text'>
                        <strong>Navigation</strong>
                        <span style='color: #666;'>Get directions to your parking location with our integrated map</span>
                    </div>
                </div>
                
                <div class='feature-item'>
                    <div class='feature-icon'>📧</div>
                    <div class='feature-text'>
                        <strong>24/7 Support</strong>
                        <span style='color: #666;'>Contact us anytime for assistance or to submit feedback</span>
                    </div>
                </div>
            </div>
            
            <a href='http://localhost/my%20First%20project/parksmart_12-29/auth/login2.php' class='cta-button'>
                🚀 Login to Your Account
            </a>
            
            <div class='support-box'>
                <p><strong>Need Help?</strong></p>
                <p>📧 Email: imalkadilshan1233@gmail.com</p>
                <p>📞 Phone: +94 11 234 5678</p>
                <p>⏰ Available: Monday - Friday, 8:00 AM - 6:00 PM</p>
            </div>
            
            <p style='font-size: 14px; color: #888; text-align: center; margin: 30px 0 10px 0;'>
                <strong>Security Tip:</strong> Never share your password with anyone. ParkSmart will never ask for your password via email.
            </p>
        </div>
        
        <div class='footer'>
            <p><strong>ParkSmart Parking Management System</strong></p>
            <p>123 Main Street, Colombo 00100, Sri Lanka</p>
            <p style='margin-top: 15px; opacity: 0.8;'>
                This email was sent because you created an account on ParkSmart.<br>
                If you didn't create this account, please contact us immediately.
            </p>
        </div>
    </div>
</body>
</html>";
    
    return sendEmail($userEmail, $subject, $htmlBody);
}

/**
 * Notify admin about new user registration
 */
function sendNewUserNotification($userInfo) {
    $adminEmail = "imalkadilshan1233@gmail.com";
    $subject = "🎉 New User Registration - ParkSmart";
    
    $htmlBody = "<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #f5f5f5; margin: 0; padding: 0; }
        .email-container { max-width: 600px; margin: 20px auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #4caf50 0%, #2e7d32 100%); color: white; padding: 30px 20px; text-align: center; }
        .header h1 { margin: 0 0 10px 0; font-size: 28px; }
        .header p { margin: 0; font-size: 16px; opacity: 0.95; }
        .content { padding: 30px 20px; }
        .badge { display: inline-block; padding: 10px 20px; border-radius: 5px; font-weight: bold; margin: 10px 0; font-size: 14px; background: #4caf50; color: white; }
        .user-info { background: #f9f9f9; border-left: 4px solid #4caf50; padding: 20px; margin: 20px 0; border-radius: 5px; }
        .info-row { padding: 10px 0; border-bottom: 1px solid #e0e0e0; }
        .info-row:last-child { border-bottom: none; }
        .label { font-weight: bold; color: #4caf50; display: inline-block; min-width: 120px; }
        .value { color: #333; }
        .stats-box { background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); padding: 20px; border-radius: 10px; margin: 20px 0; text-align: center; }
        .footer { background: #333; color: white; padding: 20px; text-align: center; font-size: 14px; }
        .footer p { margin: 5px 0; }
    </style>
</head>
<body>
    <div class='email-container'>
        <div class='header'>
            <h1>🅿️ ParkSmart Admin</h1>
            <p>New User Registration Alert</p>
        </div>
        
        <div class='content'>
            <div class='badge'>
                ✨ NEW USER JOINED
            </div>
            
            <p style='font-size: 16px; color: #555; margin: 20px 0;'>
                A new user has successfully registered on ParkSmart!
            </p>
            
            <div class='user-info'>
                <h3 style='color: #4caf50; margin: 0 0 15px 0;'>👤 User Details:</h3>
                <div class='info-row'>
                    <span class='label'>Name:</span>
                    <span class='value'>" . htmlspecialchars($userInfo['name']) . "</span>
                </div>
                <div class='info-row'>
                    <span class='label'>Email:</span>
                    <span class='value'>" . htmlspecialchars($userInfo['email']) . "</span>
                </div>
                <div class='info-row'>
                    <span class='label'>Phone:</span>
                    <span class='value'>" . htmlspecialchars($userInfo['phone']) . "</span>
                </div>
                <div class='info-row'>
                    <span class='label'>Registration Date:</span>
                    <span class='value'>" . date('F d, Y - h:i A') . "</span>
                </div>
                <div class='info-row'>
                    <span class='label'>Status:</span>
                    <span class='value' style='color: #4caf50; font-weight: bold;'>✓ Active</span>
                </div>
            </div>
            
            <div class='stats-box'>
                <p style='margin: 0; color: #1565c0; font-size: 14px;'>
                    <strong>📧 Welcome email sent to user successfully</strong>
                </p>
            </div>
            
            <p style='font-size: 14px; color: #666; text-align: center; margin: 20px 0;'>
                The user can now access all ParkSmart features including booking parking slots, making payments, and leaving reviews.
            </p>
        </div>
        
        <div class='footer'>
            <p><strong>ParkSmart Admin Notification</strong></p>
            <p>This is an automated notification from the user registration system</p>
        </div>
    </div>
</body>
</html>";
    
    return sendEmail($adminEmail, $subject, $htmlBody);
}
