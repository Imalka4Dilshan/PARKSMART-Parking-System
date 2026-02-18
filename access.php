<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ParkSmart - Quick Access</title>
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
            padding: 40px 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 50px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #2c3e50;
            font-size: 36px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        h2 {
            color: #2c3e50;
            font-size: 22px;
            font-weight: 700;
            margin-top: 40px;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            border-bottom: 3px solid rgba(102,126,234,0.3);
            padding-bottom: 10px;
        }
        .subtitle {
            color: #7f8c8d;
            margin-bottom: 40px;
            font-size: 16px;
            font-weight: 600;
        }
        .link-card {
            background: rgba(102,126,234,0.1);
            border: 2px solid rgba(102,126,234,0.2);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        .link-card:hover {
            background: rgba(102,126,234,0.2);
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102,126,234,0.3);
        }
        .link-card h3 {
            color: #667eea;
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .link-card p {
            color: #555;
            font-size: 14px;
            margin-bottom: 12px;
        }
        .link-card a {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
        }
        .link-card a:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102,126,234,0.4);
        }
        .url-box {
            background: white;
            padding: 12px 16px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            color: #667eea;
            margin-top: 10px;
            border: 1px solid rgba(102,126,234,0.3);
            word-break: break-all;
        }
        .credentials {
            background: rgba(22,160,133,0.1);
            border-left: 4px solid #16a085;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .credentials h4 {
            color: #16a085;
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 12px;
            text-transform: uppercase;
        }
        .credentials p {
            color: #2c3e50;
            font-size: 14px;
            margin: 6px 0;
        }
        .credentials strong {
            color: #16a085;
        }
        .warning {
            background: rgba(230,126,34,0.1);
            border-left: 4px solid #e67e22;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .warning p {
            color: #e67e22;
            font-weight: 600;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🅿 PARKSMART</h1>
        <p class="subtitle">Quick Access Guide - All System Links</p>
        
        <div class="warning">
            <p>⚠ IMPORTANT: Use the links below to avoid 404 errors caused by spaces in folder names</p>
        </div>

        <h2>● USER ACCESS</h2>
        
        <div class="link-card">
            <h3>Login / Sign In</h3>
            <p>Main login page for users and admin</p>
            <a href="auth/login2.php" target="_blank">→ Open Login Page</a>
            <div class="url-box">http://localhost/my%20First%20project/parksmart_12-29/auth/login2.php</div>
        </div>

        <div class="link-card">
            <h3>User Registration</h3>
            <p>Create new user account</p>
            <a href="user/register.php" target="_blank">→ Open Registration</a>
            <div class="url-box">http://localhost/my%20First%20project/parksmart_12-29/user/register.php</div>
        </div>

        <div class="link-card">
            <h3>User Dashboard</h3>
            <p>User account dashboard (requires login)</p>
            <a href="user/dashboard.php" target="_blank">→ Open Dashboard</a>
        </div>

        <h2>■ ADMIN ACCESS</h2>
        
        <div class="link-card">
            <h3>Admin Dashboard</h3>
            <p>Main admin control panel</p>
            <a href="admin/dash4.php" target="_blank">→ Open Admin Dashboard</a>
            <div class="url-box">http://localhost/my%20First%20project/parksmart_12-29/admin/dash4.php</div>
        </div>

        <div class="link-card">
            <h3>Admin Settings</h3>
            <p>Change admin password and settings</p>
            <a href="admin/admin4.php" target="_blank">→ Open Admin Settings</a>
            <div class="url-box">http://localhost/my%20First%20project/parksmart_12-29/admin/admin4.php</div>
        </div>

        <div class="link-card">
            <h3>Vehicle Entry</h3>
            <p>Register new vehicle entry</p>
            <a href="admin/entry7.php" target="_blank">→ Open Entry Page</a>
        </div>

        <div class="link-card">
            <h3>Vehicle Exit</h3>
            <p>Process vehicle exit and payment</p>
            <a href="admin/exit.php" target="_blank">→ Open Exit Page</a>
        </div>

        <h2>🔧 DIAGNOSTIC TOOLS</h2>
        
        <div class="link-card">
            <h3>Password File Diagnostic</h3>
            <p>Test password file permissions and functionality</p>
            <a href="admin/test_password_file.php" target="_blank">→ Run Diagnostic Test</a>
            <div class="url-box">http://localhost/my%20First%20project/parksmart_12-29/admin/test_password_file.php</div>
        </div>

        <div class="link-card">
            <h3>Simple Password Changer</h3>
            <p>Alternative password change tool (no session required) - Use if admin4.php fails</p>
            <a href="admin/simple_password_change.php" target="_blank">→ Open Password Changer</a>
            <div class="url-box">http://localhost/my%20First%20project/parksmart_12-29/admin/simple_password_change.php</div>
        </div>

        <div class="credentials">
            <h4>🔑 DEFAULT LOGIN CREDENTIALS</h4>
            
            <p><strong>ADMIN LOGIN:</strong></p>
            <p>• Email: <strong>admin@gmail.com</strong></p>
            <p>• Password: <strong>123</strong> (can be changed in Admin Settings)</p>
            <p>• Account Type: <strong>Admin</strong></p>
            
            <hr style="margin: 15px 0; border: none; border-top: 1px solid rgba(22,160,133,0.3);">
            
            <p><strong>USER LOGIN:</strong></p>
            <p>• Email: <strong>Your registered Gmail</strong></p>
            <p>• Password: <strong>Your registered password</strong></p>
            <p>• Account Type: <strong>User</strong></p>
        </div>

        <h2>📋 IMPORTANT NOTES</h2>
        
        <div style="background: rgba(52,152,219,0.1); padding: 20px; border-radius: 12px; border: 2px solid rgba(52,152,219,0.2);">
            <p style="color: #555; font-size: 14px; line-height: 1.8;">
                <strong style="color: #3498db;">URL Encoding:</strong><br>
                • Folder name has spaces: "my First project"<br>
                • Spaces in URLs must be encoded as <code>%20</code><br>
                • Use the links above or copy URLs with %20<br><br>
                
                <strong style="color: #3498db;">Email Validation:</strong><br>
                • All emails must end with <strong>@gmail.com</strong><br>
                • Admin email is <strong>admin@gmail.com</strong><br>
                • Other email providers will be rejected<br><br>
                
                <strong style="color: #3498db;">Troubleshooting:</strong><br>
                • If getting 404 errors, bookmark this page<br>
                • Make sure XAMPP Apache and MySQL are running<br>
                • Check that database "parking_db" exists<br>
                • Run diagnostic test if password change fails
            </p>
        </div>

        <div style="text-align: center; margin-top: 40px; padding: 20px; background: rgba(102,126,234,0.1); border-radius: 12px;">
            <p style="color: #667eea; font-weight: 700; font-size: 16px;">⚡ QUICK START</p>
            <p style="color: #555; margin-top: 10px;">
                <a href="auth/login2.php" style="color: #667eea; text-decoration: none; font-weight: 700;">Login to System →</a>
            </p>
        </div>
    </div>
</body>
</html>
