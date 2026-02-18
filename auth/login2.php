<?php
session_start();
date_default_timezone_set('Asia/Colombo');

$login_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $user_type = $_POST['user_type'] ?? 'user';

    if ($user_type === 'admin') {
        // Admin login - validate Gmail format
        if (!preg_match('/^[a-zA-Z0-9._%+\-]+@gmail\.com$/', $email)) {
            $login_error = 'Admin email must be a valid Gmail address (@gmail.com)';
        } else {
            $valid_email = 'admin@gmail.com';
            $valid_password = trim(file_get_contents(__DIR__ . '/password.txt'));

            if ($email === $valid_email && $password === $valid_password) {
                $_SESSION['user'] = $email;
                $_SESSION['user_type'] = 'admin';
                header('Location: ../admin/dash4.php');
                exit();
            } else {
                $login_error = 'Invalid admin credentials';
            }
        }
    } else {
        // User login
        include __DIR__ . '/../config/db.php';
        
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_type'] = 'customer';
                header('Location: ../user/dashboard.php');
                exit();
            }
        }
        $login_error = 'Invalid user credentials';
    }
}
?>
<!DOCTYPE html> 
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>ParkSmart - Smart Parking Management Solution</title>
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
            display: flex;
            flex-direction: column;
        }
        .header {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255,255,255,0.2);
            padding: 0 20px;
        }
        .nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            height: 70px;
        }
        .logo {
            display: flex;
            align-items: center;
            font-size: 28px;
            font-weight: 800;
            color: white;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .logo-icon {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 10px 14px;
            border-radius: 12px;
            display: inline-block;
            margin-right: 12px;
            font-size: 24px;
            font-weight: 800;
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .beta-tag {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            margin-left: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .nav-links {
            display: flex;
            list-style: none;
            gap: 30px;
        }
        .nav-links a {
            text-decoration: none;
            color: rgba(255,255,255,0.9);
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .nav-links a:hover {
            color: white;
            transform: translateY(-2px);
        }
        .nav-links a {
            cursor: pointer;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.7);
            backdrop-filter: blur(5px);
            animation: fadeIn 0.3s ease;
        }
        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: white;
            margin: auto;
            padding: 0;
            border-radius: 20px;
            max-width: 700px;
            width: 90%;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
            animation: slideIn 0.4s ease;
            position: relative;
        }
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 40px;
            border-radius: 20px 20px 0 0;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .modal-header h2 {
            font-size: 32px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin: 0;
        }
        .modal-body {
            padding: 40px;
            line-height: 1.8;
            color: #333;
        }
        .modal-body h3 {
            color: #667eea;
            font-size: 24px;
            font-weight: 700;
            margin-top: 25px;
            margin-bottom: 15px;
        }
        .modal-body p {
            margin-bottom: 15px;
            font-size: 16px;
        }
        .modal-body ul {
            margin-left: 20px;
            margin-bottom: 20px;
        }
        .modal-body li {
            margin-bottom: 10px;
            font-size: 16px;
        }
        .close-modal {
            position: absolute;
            top: 25px;
            right: 35px;
            color: white;
            font-size: 40px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            line-height: 1;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        .close-modal:hover {
            background: rgba(255,255,255,0.2);
            transform: rotate(90deg);
        }
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .feature-item {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 25px;
            border-radius: 15px;
            border-left: 4px solid #667eea;
            transition: all 0.3s ease;
        }
        .feature-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(102,126,234,0.3);
        }
        .feature-item h4 {
            color: #667eea;
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .feature-icon {
            font-size: 36px;
            margin-bottom: 15px;
        }
        .contact-info {
            background: linear-gradient(135deg, #e8eaf6 0%, #c5cae9 100%);
            padding: 25px;
            border-radius: 15px;
            margin: 20px 0;
            border-left: 5px solid #667eea;
        }
        .contact-info p {
            margin: 10px 0;
            font-size: 16px;
        }
        .contact-info strong {
            color: #667eea;
            display: inline-block;
            min-width: 120px;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 60px 20px;
            display: grid;
            grid-template-columns: 1fr 450px;
            gap: 80px;
            align-items: center;
            flex: 1;
        }
        .welcome-section h1 {
            font-size: 48px;
            font-weight: 800;
            margin-bottom: 20px;
            color: white;
            text-transform: uppercase;
            letter-spacing: 2px;
            text-shadow: 2px 2px 10px rgba(0,0,0,0.3);
            animation: fadeInUp 0.8s ease;
        }
        .welcome-section p {
            font-size: 20px;
            color: rgba(255,255,255,0.9);
            margin-bottom: 40px;
            font-weight: 600;
            letter-spacing: 1px;
            animation: fadeInUp 1s ease;
        }
        .parking-image {
            width: 100%;
            height: 350px;
            background: url('car.jpg') center/cover no-repeat;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
            animation: fadeInUp 1.2s ease;
            border: 3px solid rgba(255,255,255,0.2);
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .signin-card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 50px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: fadeInUp 1s ease;
        }
        .signin-card h2 {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 10px;
            color: #2c3e50;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .signin-card .subtitle {
            color: #7f8c8d;
            font-size: 15px;
            margin-bottom: 40px;
            font-weight: 600;
        }
        .form-group {
            margin-bottom: 28px;
        }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .input-container {
            position: relative;
        }
        .input-container input {
            width: 100%;
            padding: 16px 20px;
            padding-left: 50px;
            border: 2px solid rgba(102,126,234,0.2);
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: rgba(255,255,255,0.8);
            font-weight: 500;
        }
        .input-container input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 8px 25px rgba(102,126,234,0.3);
            transform: translateY(-2px);
        }
        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #667eea;
            font-size: 18px;
            font-weight: 700;
        }
        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #667eea;
            cursor: pointer;
            font-size: 18px;
            font-weight: 700;
            transition: all 0.3s ease;
        }
        .password-toggle:hover {
            color: #764ba2;
            transform: translateY(-50%) scale(1.1);
        }
        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
        }
        .remember-me {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: #7f8c8d;
            font-weight: 600;
        }
        .remember-me input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        .signin-btn {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 18px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            margin-bottom: 28px;
            box-shadow: 0 8px 25px rgba(102,126,234,0.4);
            position: relative;
            overflow: hidden;
        }
        .signin-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.2);
            transition: left 0.5s ease;
        }
        .signin-btn:hover::before {
            left: 100%;
        }
        .signin-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(102,126,234,0.5);
        }
        .security-notice {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            color: #7f8c8d;
            margin-top: 20px;
            font-weight: 600;
        }
        .security-icon {
            color: #16a085;
            font-size: 16px;
        }
        .quick-contact-buttons {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }
        .quick-btn {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 16px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .whatsapp-btn {
            background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
            color: white;
        }
        .whatsapp-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 211, 102, 0.4);
        }
        .email-btn {
            background: linear-gradient(135deg, #EA4335 0%, #C5221F 100%);
            color: white;
        }
        .email-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(234, 67, 53, 0.4);
        }
        .quick-btn-icon {
            font-size: 18px;
        }
        .footer {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-top: 1px solid rgba(255,255,255,0.2);
            padding: 25px 20px;
            margin-top: auto;
        }
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .footer-left {
            color: rgba(255,255,255,0.9);
            font-size: 13px;
            font-weight: 600;
        }
        .footer-links {
            display: flex;
            gap: 25px;
            list-style: none;
        }
        .footer-links a {
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .footer-links a:hover {
            color: white;
        }
        .status-indicator {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            color: rgba(255,255,255,0.9);
            font-weight: 600;
        }
        .status-dot {
            width: 10px;
            height: 10px;
            background: #16a085;
            border-radius: 50%;
            box-shadow: 0 0 10px #16a085;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        @media (max-width: 968px) {
            .main-container {
                grid-template-columns: 1fr;
                gap: 40px;
                padding: 40px 20px;
            }
            .welcome-section h1 {
                font-size: 36px;
            }
            .parking-image {
                height: 250px;
            }
        }
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }
            .signin-card {
                padding: 32px;
            }
            .footer-content {
                flex-direction: column;
                gap: 16px;
                text-align: center;
            }
            .logo {
                font-size: 22px;
            }
        }
        .error-message {
            background: rgba(231,76,60,0.1);
            color: #c0392b;
            padding: 14px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-weight: 700;
            text-align: center;
            border-left: 4px solid #e74c3c;
        }
        .user-type-selector {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 10px;
        }
        .user-type-option {
            position: relative;
            cursor: pointer;
        }
        .user-type-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }
        .user-type-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            padding: 20px;
            border: 2px solid rgba(102,126,234,0.2);
            border-radius: 12px;
            transition: all 0.3s ease;
            background: rgba(255,255,255,0.5);
        }
        .user-type-option input[type="radio"]:checked + .user-type-label {
            border-color: #667eea;
            background: rgba(102,126,234,0.15);
            box-shadow: 0 8px 25px rgba(102,126,234,0.3);
            transform: translateY(-2px);
        }
        .user-type-icon {
            font-size: 36px;
        }
        .user-type-label span:last-child {
            font-size: 14px;
            font-weight: 700;
            color: #2c3e50;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .user-type-option:hover .user-type-label {
            border-color: #667eea;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <header class="header">
        <nav class="nav">
            <div class="logo">
                <div class="logo-icon">P</div>
                ParkSmart
            </div>
            <ul class="nav-links">
                <li><a href="#about">About</a></li>
                <li><a href="#features">Features</a></li>
                <li><a href="#contact">Contact</a></li>
                <li><a href="#help">Help</a></li>
            </ul>
        </nav>
    </header>

    <main class="main-container">
        <div class="welcome-section">
            <h1>Welcome to ParkSmart</h1>
            <p>Smart Parking Management Solution</p>
            <div class="parking-image"></div>
        </div>

        <div class="signin-card">
            <h2>Sign In</h2>
            <p class="subtitle">Access your ParkSmart account</p>

            <?php if (!empty($login_error)) : ?>
                <div class="error-message"><?= htmlspecialchars($login_error) ?></div>
            <?php endif; ?>

            <form method="post" action="">
                <div class="form-group">
                    <label for="user_type">Select Account Type</label>
                    <div class="user-type-selector">
                        <label class="user-type-option">
                            <input type="radio" name="user_type" value="user" checked>
                            <span class="user-type-label">
                                <span class="user-type-icon">●</span>
                                <span>User</span>
                            </span>
                        </label>
                        <label class="user-type-option">
                            <input type="radio" name="user_type" value="admin">
                            <span class="user-type-label">
                                <span class="user-type-icon">■</span>
                                <span>Admin</span>
                            </span>
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-container">
                        <span class="input-icon">@</span>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="Enter your Gmail (e.g., name@gmail.com)"
                            pattern="[a-zA-Z0-9._%+\-]+@gmail\.com$"
                            title="Please enter a valid Gmail address (must end with @gmail.com)"
                            required
                            value="<?= isset($email) ? htmlspecialchars($email) : '' ?>"
                        />
                    </div>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-container">
                        <span class="input-icon">●</span>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            required
                        />
                        <span class="password-toggle" id="toggle-password">●</span>
                    </div>
                </div>
                <div class="remember-forgot">
                    <label class="remember-me">
                        <input type="checkbox" id="remember" name="remember" />
                        Remember me
                    </label>
                </div>
                <button type="submit" class="signin-btn">Sign In</button>
                
                <div style="text-align: center; margin-top: 16px; font-size: 14px; color: #7f8c8d; font-weight: 600;">
                    Don't have an account? <a href="../user/register.php" style="color: #667eea; text-decoration: none; font-weight: 700; transition: all 0.3s ease;">Create Account</a>
                </div>
            </form>

            <div class="security-notice">
                <span class="security-icon">●</span>Your data is securely encrypted
            </div>
        </div>
    </main>

    <!-- <footer class="footer">
        <div class="footer-content">
            <div class="footer-left">© 2024 ParkSmart. All rights reserved.</div>
            <ul class="footer-links">
                <li><a href="#">Privacy Policy</a></li>
                <li><a href="#">Terms of Service</a></li>
                <li><a href="#">Support</a></li>
            </ul>
            <div class="status-indicator">
                <div class="status-dot"></div>
                All systems operational
            </div>
        </div>
    </footer> -->

    <script>
        // Password toggle eye icon
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const passwordIcon = document.getElementById('toggle-password');
            const emailInput = document.getElementById('email');
            const signinForm = emailInput.closest('form');

            // Password toggle functionality
            passwordIcon.style.cursor = 'pointer';
            passwordIcon.addEventListener('click', function () {
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    passwordIcon.textContent = '○';
                } else {
                    passwordInput.type = 'password';
                    passwordIcon.textContent = '●';
                }
            });

            // Email validation for @gmail.com
            emailInput.addEventListener('input', function() {
                const emailValue = this.value;
                if (emailValue && !emailValue.endsWith('@gmail.com')) {
                    this.setCustomValidity('Email must be a Gmail address (@gmail.com)');
                } else {
                    this.setCustomValidity('');
                }
            });

            // Form submission validation
            signinForm.addEventListener('submit', function(e) {
                const emailValue = emailInput.value;
                if (!emailValue.match(/^[a-zA-Z0-9._%+\-]+@gmail\.com$/)) {
                    e.preventDefault();
                    alert('Please enter a valid Gmail address. Email must end with @gmail.com');
                    emailInput.focus();
                    return false;
                }
            });
        });
    </script>

    <!-- About Modal -->
    <div id="aboutModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>📖 About ParkSmart</h2>
                <span class="close-modal" data-modal="aboutModal">&times;</span>
            </div>
            <div class="modal-body">
                <p>Welcome to <strong>ParkSmart</strong> - Your Smart Parking Management Solution designed to make parking hassle-free and efficient!</p>
                
                <h3>🎯 Our Mission</h3>
                <p>To revolutionize urban parking by providing a smart, user-friendly platform that connects drivers with available parking spaces in real-time, reducing search time and traffic congestion.</p>
                
                <h3>💡 What We Do</h3>
                <p>ParkSmart is a Smart Parking Management Solution that allows users to:</p>
                <ul>
                    <li>Find and reserve parking spots in advance</li>
                    <li>Make secure online payments</li>
                    <li>Track booking history and manage reservations</li>
                    <li>Get real-time parking availability updates</li>
                    <li>Navigate to parking locations with ease</li>
                </ul>
                
                <h3>🌟 Why Choose ParkSmart?</h3>
                <ul>
                    <li><strong>Save Time:</strong> No more circling around looking for parking</li>
                    <li><strong>Guaranteed Spot:</strong> Reserve your space before you arrive</li>
                    <li><strong>24/7 Access:</strong> Book anytime, anywhere</li>
                    <li><strong>Secure Payments:</strong> Multiple payment options with encryption</li>
                    <li><strong>Real-time Updates:</strong> Live parking availability</li>
                    <li><strong>User Reviews:</strong> Check ratings before booking</li>
                </ul>
                
                <h3>🏆 Our Vision</h3>
                <p>To be the leading Smart Parking Management Solution in Sri Lanka, making city parking stress-free for everyone while contributing to reduced traffic congestion and environmental sustainability.</p>
                
                <div class="contact-info">
                    <p><strong>Version:</strong> Beta 1.0</p>
                    <p><strong>Location:</strong> Colombo, Sri Lanka</p>
                    <p><strong>Established:</strong> 2026</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Modal -->
    <div id="featuresModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>🚀 Features</h2>
                <span class="close-modal" data-modal="featuresModal">&times;</span>
            </div>
            <div class="modal-body">
                <p>ParkSmart offers a comprehensive set of features designed to enhance your parking experience:</p>
                
                <div class="feature-grid">
                    <div class="feature-item">
                        <div class="feature-icon">🅿️</div>
                        <h4>Smart Booking</h4>
                        <p>Reserve parking slots in advance with real-time availability updates</p>
                    </div>
                    
                    <div class="feature-item">
                        <div class="feature-icon">💳</div>
                        <h4>Multiple Payments</h4>
                        <p>Card payments, bank transfers, and QR code payments supported</p>
                    </div>
                    
                    <div class="feature-item">
                        <div class="feature-icon">📋</div>
                        <h4>Booking History</h4>
                        <p>Track all your past and current reservations in one place</p>
                    </div>
                    
                    <div class="feature-item">
                        <div class="feature-icon">⭐</div>
                        <h4>Reviews & Ratings</h4>
                        <p>Share your experience and read reviews from other users</p>
                    </div>
                    
                    <div class="feature-item">
                        <div class="feature-icon">🗺️</div>
                        <h4>Navigation</h4>
                        <p>Integrated maps to help you reach your parking spot easily</p>
                    </div>
                    
                    <div class="feature-item">
                        <div class="feature-icon">📧</div>
                        <h4>Contact & Support</h4>
                        <p>24/7 customer support for inquiries and complaints</p>
                    </div>
                    
                    <div class="feature-item">
                        <div class="feature-icon">🔐</div>
                        <h4>Secure Login</h4>
                        <p>Gmail authentication for enhanced security</p>
                    </div>
                    
                    <div class="feature-item">
                        <div class="feature-icon">📊</div>
                        <h4>Dashboard</h4>
                        <p>View parking statistics and occupancy rates at a glance</p>
                    </div>
                    
                    <div class="feature-item">
                        <div class="feature-icon">⏰</div>
                        <h4>Flexible Timing</h4>
                        <p>Choose your parking date, time, and duration</p>
                    </div>
                    
                    <div class="feature-item">
                        <div class="feature-icon">📱</div>
                        <h4>Mobile Friendly</h4>
                        <p>Responsive design works on all devices</p>
                    </div>
                    
                    <div class="feature-item">
                        <div class="feature-icon">🎨</div>
                        <h4>Modern UI</h4>
                        <p>Beautiful purple gradient theme throughout</p>
                    </div>
                    
                    <div class="feature-item">
                        <div class="feature-icon">✉️</div>
                        <h4>Email Notifications</h4>
                        <p>Receive welcome emails and booking confirmations</p>
                    </div>
                </div>
                
                <h3>🔜 Coming Soon</h3>
                <ul>
                    <li>Mobile app for iOS and Android</li>
                    <li>Loyalty rewards program</li>
                    <li>Parking spot recommendations based on your preferences</li>
                    <li>Integration with navigation apps</li>
                    <li>Monthly parking subscriptions</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Contact Modal -->
    <div id="contactModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>📞 Contact Us</h2>
                <span class="close-modal" data-modal="contactModal">&times;</span>
            </div>
            <div class="modal-body">
                <p>We're here to help! Get in touch with us through any of the following channels:</p>
                
                <div class="contact-info">
                    <h3 style="margin-top: 0; color: #667eea;">📍 ParkSmart Customer Support</h3>
                    <p><strong>Email:</strong> imalkadilshan1233@gmail.com</p>
                    <p><strong>Phone:</strong> +94 11 234 5678</p>
                    <p><strong>WhatsApp:</strong> 070 430 5875</p>
                    <p><strong>Address:</strong> 123 Main Street, Colombo 00100, Sri Lanka</p>
                    <p><strong>Business Hours:</strong> Monday - Friday, 8:00 AM - 6:00 PM</p>
                </div>
                
                <!-- Quick Contact Buttons -->
                <div class="quick-contact-buttons" style="margin: 20px 0;">
                    <a href="https://wa.me/94704305875?text=Hello%20ParkSmart%20Support%20Team%2C%0A%0AI%20am%20contacting%20you%20regarding%20your%20parking%20service%20and%20would%20appreciate%20your%20assistance%20with%20%5Bbriefly%20describe%20your%20concern%20or%20request%20here%5D.%0A%0AThank%20you%20for%20your%20support.%0ABest%20regards%2C%0A%5BYour%20Name%5D" class="quick-btn whatsapp-btn" target="_blank">
                        <span class="quick-btn-icon">💬</span>
                        <span>Chat on WhatsApp</span>
                    </a>
                    <a href="https://mail.google.com/mail/?view=cm&fs=1&to=imalkadilshan1233@gmail.com" class="quick-btn email-btn" target="_blank">
                        <span class="quick-btn-icon">✉️</span>
                        <span>Send Email</span>
                    </a>
                </div>
                
                <h3>📧 For Registered Users</h3>
                <p>If you already have an account, you can use our Contact & Support feature from your dashboard to:</p>
                <ul>
                    <li>Submit general inquiries</li>
                    <li>File complaints</li>
                    <li>Track your message history</li>
                    <li>Get personalized support</li>
                </ul>
                
                <h3>🆘 Emergency Support</h3>
                <p>For urgent parking issues or emergencies, please call our 24/7 hotline:</p>
                <div class="contact-info">
                    <p><strong>Emergency Hotline:</strong> +94 77 123 4567</p>
                    <p><strong>Available:</strong> 24 hours, 7 days a week</p>
                </div>
                
                <h3>💬 Social Media</h3>
                <p>Follow us on social media for updates, tips, and parking news:</p>
                <ul>
                    <li>Facebook: @ParkSmartLK</li>
                    <li>Twitter: @ParkSmartLK</li>
                    <li>Instagram: @parksmartlk</li>
                </ul>
                
                <h3>📮 Mailing Address</h3>
                <div class="contact-info">
                    <p>ParkSmart Parking Systems (Pvt) Ltd<br>
                    123 Main Street<br>
                    Colombo 00100<br>
                    Sri Lanka</p>
                </div>
                
                <p style="margin-top: 30px; padding: 20px; background: #e3f2fd; border-radius: 10px; border-left: 4px solid #2196f3;">
                    <strong>Note:</strong> For the best support experience, please log in to your account and use the Contact & Support feature. This allows us to better assist you with your specific account and booking details.
                </p>
            </div>
        </div>
    </div>

    <!-- Help Modal -->
    <div id="helpModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>❓ Help & FAQ</h2>
                <span class="close-modal" data-modal="helpModal">&times;</span>
            </div>
            <div class="modal-body">
                <h3>🚀 Getting Started</h3>
                
                <h4 style="color: #764ba2; margin-top: 20px;">How do I create an account?</h4>
                <p>Click on "Create Account" below the login form. Fill in your name, email (must be Gmail), phone number, and password. You'll receive a welcome email once registered.</p>
                
                <h4 style="color: #764ba2; margin-top: 20px;">What email can I use to register?</h4>
                <p>ParkSmart requires a Gmail address (@gmail.com) for registration and login. This ensures better security and email delivery.</p>
                
                <h3 style="margin-top: 30px;">🅿️ Booking & Parking</h3>
                
                <h4 style="color: #764ba2; margin-top: 20px;">How do I book a parking slot?</h4>
                <ol>
                    <li>Log in to your account</li>
                    <li>Navigate to "Slots" or click "Book Parking Slot"</li>
                    <li>Choose your preferred slot</li>
                    <li>Select date, time, and duration</li>
                    <li>Proceed to payment</li>
                    <li>Receive confirmation</li>
                </ol>
                
                <h4 style="color: #764ba2; margin-top: 20px;">Can I cancel my booking?</h4>
                <p>Yes, you can view and manage your bookings from the "Bookings" section in your dashboard. Cancellation policies may apply based on timing.</p>
                
                <h4 style="color: #764ba2; margin-top: 20px;">How do I know if a slot is available?</h4>
                <p>The dashboard shows real-time availability. Available slots are highlighted in green, while occupied slots are marked in orange/red.</p>
                
                <h3 style="margin-top: 30px;">💳 Payment</h3>
                
                <h4 style="color: #764ba2; margin-top: 20px;">What payment methods do you accept?</h4>
                <ul>
                    <li><strong>Card Payment:</strong> Credit/Debit cards</li>
                    <li><strong>Bank Transfer:</strong> Upload PDF proof</li>
                    <li><strong>QR Payment:</strong> Upload screenshot</li>
                </ul>
                
                <h4 style="color: #764ba2; margin-top: 20px;">Is my payment information secure?</h4>
                <p>Yes! All payment information is processed securely. We use industry-standard encryption to protect your financial data.</p>
                
                <h3 style="margin-top: 30px;">👤 Account Management</h3>
                
                <h4 style="color: #764ba2; margin-top: 20px;">How do I reset my password?</h4>
                <p>Currently, please contact support to reset your password. A self-service password reset feature is coming soon!</p>
                
                <h4 style="color: #764ba2; margin-top: 20px;">Can I update my profile information?</h4>
                <p>Yes! Go to your Profile section from the navigation menu to update your name, phone number, and other details.</p>
                
                <h3 style="margin-top: 30px;">⭐ Reviews & Ratings</h3>
                
                <h4 style="color: #764ba2; margin-top: 20px;">How do I leave a review?</h4>
                <p>Navigate to the "Reviews" section and rate the parking facility on slot condition, safety, cleanliness, and staff service. You can also write a detailed review.</p>
                
                <h4 style="color: #764ba2; margin-top: 20px;">Can I edit my review?</h4>
                <p>Yes! You can update or delete your review anytime from the Reviews page.</p>
                
                <h3 style="margin-top: 30px;">📧 Contact & Support</h3>
                
                <h4 style="color: #764ba2; margin-top: 20px;">How do I get help?</h4>
                <p>You can:</p>
                <ul>
                    <li>Use the Contact & Support feature in your dashboard</li>
                    <li>Email us at imalkadilshan1233@gmail.com</li>
                    <li>Call +94 11 234 5678 during business hours</li>
                    <li>Use the emergency hotline for urgent issues</li>
                </ul>
                
                <h4 style="color: #764ba2; margin-top: 20px;">How long does it take to get a response?</h4>
                <p>We aim to respond to all inquiries within 24 hours during business days. Emergency issues are handled immediately.</p>
                
                <h3 style="margin-top: 30px;">🔐 Security & Privacy</h3>
                
                <h4 style="color: #764ba2; margin-top: 20px;">Is my personal information safe?</h4>
                <p>Absolutely! We use secure encryption and follow best practices to protect your personal and payment information. We never share your data with third parties.</p>
                
                <h4 style="color: #764ba2; margin-top: 20px;">Why do you require Gmail?</h4>
                <p>Gmail authentication helps us ensure account security and reliable email delivery for booking confirmations and important notifications.</p>
                
                <div style="margin-top: 40px; padding: 25px; background: linear-gradient(135deg, #fff3cd 0%, #ffe9a6 100%); border-radius: 15px; border-left: 5px solid #ffc107;">
                    <h4 style="margin: 0 0 10px 0; color: #856404;">💡 Still Need Help?</h4>
                    <p style="margin: 0; color: #856404;">If you can't find the answer you're looking for, please don't hesitate to contact our support team. We're here to help make your parking experience smooth and hassle-free!</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Modal functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Get all nav links
            const aboutLink = document.querySelector('a[href="#about"]');
            const featuresLink = document.querySelector('a[href="#features"]');
            const contactLink = document.querySelector('a[href="#contact"]');
            const helpLink = document.querySelector('a[href="#help"]');
            
            // Get all modals
            const aboutModal = document.getElementById('aboutModal');
            const featuresModal = document.getElementById('featuresModal');
            const contactModal = document.getElementById('contactModal');
            const helpModal = document.getElementById('helpModal');
            
            // Get all close buttons
            const closeBtns = document.querySelectorAll('.close-modal');
            
            // Open modals
            aboutLink.addEventListener('click', function(e) {
                e.preventDefault();
                aboutModal.classList.add('active');
            });
            
            featuresLink.addEventListener('click', function(e) {
                e.preventDefault();
                featuresModal.classList.add('active');
            });
            
            contactLink.addEventListener('click', function(e) {
                e.preventDefault();
                contactModal.classList.add('active');
            });
            
            helpLink.addEventListener('click', function(e) {
                e.preventDefault();
                helpModal.classList.add('active');
            });
            
            // Close modals
            closeBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const modalId = this.getAttribute('data-modal');
                    document.getElementById(modalId).classList.remove('active');
                });
            });
            
            // Close modal when clicking outside
            window.addEventListener('click', function(e) {
                if (e.target.classList.contains('modal')) {
                    e.target.classList.remove('active');
                }
            });
            
            // Close modal with Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    document.querySelectorAll('.modal.active').forEach(modal => {
                        modal.classList.remove('active');
                    });
                }
            });
        });
    </script>
</body>
</html>
