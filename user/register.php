<?php
session_start();
date_default_timezone_set('Asia/Colombo');

$error = '';
$success = '';
include __DIR__ . '/../config/db.php';
include __DIR__ . '/../config/email.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        $error = 'All fields are required';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Email already registered';
        } else {
            try {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                // Use email as username (or extract part before @ if preferred)
                $username = $email;
                $stmt = $conn->prepare("INSERT INTO users (username, name, email, phone, password) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $username, $name, $email, $phone, $hashed_password);
                
                if ($stmt->execute()) {
                    // Send welcome email to the new user
                    $welcomeInfo = array(
                        'name' => $name,
                        'email' => $email,
                        'phone' => $phone
                    );
                    sendWelcomeEmail($welcomeInfo);
                    
                    // Notify admin about new user registration
                    sendNewUserNotification($welcomeInfo);
                    
                    $success = 'Account created successfully! A welcome email has been sent to your email address.';
                    header('Refresh: 3; URL=../auth/login2.php');
                } else {
                    $error = 'Registration failed. Please try again';
                }
            } catch (mysqli_sql_exception $e) {
                // Handle duplicate username error or other database errors
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    $error = 'This email is already registered. Please use a different email.';
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - ParkSmart</title>
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
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .register-container {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 440px;
            width: 100%;
            padding: 50px 40px;
            border: none;
        }

        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 35px;
        }

        .logo-icon {
            width: 52px;
            height: 52px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
            font-weight: 800;
            box-shadow: 0 8px 25px rgba(102,126,234,0.4);
        }

        .logo-text {
            font-size: 26px;
            font-weight: 700;
            color: #2c3e50;
            letter-spacing: 1px;
        }

        h1 {
            font-size: 28px;
            font-weight: 800;
            color: #2c3e50;
            text-align: center;
            margin-bottom: 10px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .subtitle {
            text-align: center;
            font-size: 14px;
            color: #7f8c8d;
            margin-bottom: 35px;
            letter-spacing: 0.5px;
        }

        .form-group {
            margin-bottom: 22px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 10px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #667eea;
            font-size: 18px;
        }

        input {
            width: 100%;
            padding: 16px 20px 16px 50px;
            border: 2px solid rgba(102,126,234,0.2);
            border-radius: 12px;
            font-size: 15px;
            color: #2c3e50;
            transition: all 0.3s ease;
            background: rgba(255,255,255,0.9);
        }

        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
            transform: translateY(-2px);
        }

        input::placeholder {
            color: #9AA0A6;
        }

        .btn-register {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            cursor: pointer;
            margin-top: 30px;
            transition: all 0.4s cubic-bezier(0.175,0.885,0.32,1.275);
            box-shadow: 0 10px 30px rgba(102,126,234,0.3);
            position: relative;
            overflow: hidden;
        }

        .btn-register::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.2);
            transition: left 0.5s ease;
        }

        .btn-register:hover::before {
            left: 100%;
        }

        .btn-register:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(102,126,234,0.4);
        }

        .btn-register:active {
            transform: translateY(-1px);
        }

        .login-link {
            text-align: center;
            margin-top: 28px;
            font-size: 14px;
            color: #7f8c8d;
        }

        .login-link a {
            color: #667eea;
            font-weight: 600;
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .error-message {
            background: #FCE8E6;
            color: #C5221F;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
            border-left: 3px solid #C5221F;
        }

        .success-message {
            background: #E6F4EA;
            color: #137333;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
            border-left: 3px solid #137333;
        }

        @media (max-width: 480px) {
            .register-container {
                padding: 32px 24px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="logo">
            <div class="logo-icon">P</div>
            <div class="logo-text">ParkSmart</div>
        </div>

        <h1>Create Account</h1>
        <p class="subtitle">Join ParkSmart for easy parking</p>

        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="name">Full Name</label>
                <div class="input-wrapper">
                    <span class="input-icon">👤</span>
                    <input type="text" id="name" name="name" placeholder="Enter your full name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <div class="input-wrapper">
                    <span class="input-icon">📧</span>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <div class="input-wrapper">
                    <span class="input-icon">📱</span>
                    <input type="tel" id="phone" name="phone" placeholder="Enter your phone" required value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <span class="input-icon">🔒</span>
                    <input type="password" id="password" name="password" placeholder="Create a password" required>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="input-wrapper">
                    <span class="input-icon">🔒</span>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required>
                </div>
            </div>

            <button type="submit" class="btn-register">Create Account</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="../auth/login2.php">Login</a>
        </div>
    </div>
</body>
</html>
