<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        }

        .admin-nav {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 24px;
        }

        .nav-logo {
            color: white;
            font-size: 26px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 12px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .nav-logo-icon {
            background: rgba(255,255,255,0.2);
            padding: 8px 12px;
            border-radius: 10px;
            font-size: 24px;
            font-weight: 800;
            backdrop-filter: blur(10px);
        }

        .nav-links {
            display: flex;
            gap: 8px;
            list-style: none;
        }

        .nav-links a {
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .nav-links a:hover {
            background: rgba(255,255,255,0.2);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .nav-links a.active {
            background: rgba(255,255,255,0.25);
            color: white;
            font-weight: 700;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .nav-user {
            display: flex;
            align-items: center;
            gap: 16px;
            color: white;
        }

        .nav-user-icon {
            background: rgba(255,255,255,0.2);
            padding: 6px 10px;
            border-radius: 8px;
            font-size: 16px;
        }

        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 700;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 13px;
        }

        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        @media (max-width: 768px) {
            .nav-container {
                flex-direction: column;
                gap: 16px;
                padding: 12px 16px;
            }
            
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }

            .nav-logo {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>
    <nav class="admin-nav">
        <div class="nav-container">
            <div class="nav-logo">
                <span class="nav-logo-icon">P</span>
                <span>ParkSmart Admin</span>
            </div>
            <ul class="nav-links">
                <li><a href="dash4.php" class="<?php echo ($current == 'dash4.php') ? 'active' : ''; ?>">Dashboard</a></li>
                <li><a href="entry7.php" class="<?php echo ($current == 'entry7.php') ? 'active' : ''; ?>">Entry</a></li>
                <li><a href="exit.php" class="<?php echo ($current == 'exit.php') ? 'active' : ''; ?>">Exit</a></li>
                <li><a href="reviews.php" class="<?php echo ($current == 'reviews.php') ? 'active' : ''; ?>">Reviews</a></li>
                <li><a href="admin4.php" class="<?php echo ($current == 'admin4.php') ? 'active' : ''; ?>">Settings</a></li>
            </ul>
            <div class="nav-user">
                <span class="nav-user-icon">●</span>
                <span>Admin</span>
                <a href="../auth/logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </nav>
