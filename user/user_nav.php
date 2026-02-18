<?php
// User Navigation Component
// Include this at the top of each user page after session check
if (!isset($_SESSION['user_id'])) {
    exit;
}

$current_page = basename($_SERVER['PHP_SELF']);
$user_name = $_SESSION['user_name'] ?? 'User';
?>
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    .user-nav {
        background: white;
        padding: 0;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
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
        color: #2c3e50;
        font-size: 26px;
        font-weight: 800;
        display: flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
        letter-spacing: 1px;
        text-transform: uppercase;
    }

    .nav-logo-icon {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 8px 12px;
        border-radius: 10px;
        font-size: 24px;
        font-weight: 800;
        box-shadow: 0 4px 15px rgba(102,126,234,0.3);
    }

    .nav-links {
        display: flex;
        gap: 8px;
        list-style: none;
    }

    .nav-links a {
        color: #7f8c8d;
        text-decoration: none;
        padding: 10px 20px;
        border-radius: 10px;
        transition: all 0.3s ease;
        font-weight: 600;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .nav-links a:hover {
        background: rgba(102,126,234,0.1);
        color: #667eea;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102,126,234,0.2);
    }

    .nav-links a.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        font-weight: 700;
        box-shadow: 0 4px 15px rgba(102,126,234,0.3);
    }

    .nav-icon {
        font-size: 16px;
    }

    .nav-user {
        display: flex;
        align-items: center;
        gap: 16px;
        color: #2c3e50;
    }

    .nav-user-info {
        font-size: 14px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .nav-user-info .user-icon {
        background: rgba(102,126,234,0.15);
        color: #667eea;
        padding: 6px 10px;
        border-radius: 8px;
        font-size: 16px;
    }

    .logout-btn {
        background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
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
        box-shadow: 0 4px 15px rgba(231,76,60,0.3);
    }

    .logout-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(231,76,60,0.4);
    }

    @media (max-width: 968px) {
        .nav-user-info span {
            display: none;
        }
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
            gap: 6px;
        }

        .nav-links a {
            padding: 8px 16px;
            font-size: 13px;
        }

        .nav-logo {
            font-size: 22px;
        }
    }

    @media (max-width: 480px) {
        .nav-links a span.nav-label {
            display: none;
        }

        .nav-links a {
            padding: 8px 12px;
        }
    }
</style>

<nav class="user-nav">
    <div class="nav-container">
        <a href="dashboard.php" class="nav-logo">
            <span class="nav-logo-icon">P</span>
            <span>ParkSmart</span>
        </a>
        <ul class="nav-links">
            <li><a href="dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
                <span class="nav-icon">■</span>
                <span class="nav-label">Dashboard</span>
            </a></li>
            <li><a href="slot_view.php" class="<?= $current_page == 'slot_view.php' ? 'active' : '' ?>">
                <span class="nav-icon">P</span>
                <span class="nav-label">Slots</span>
            </a></li>
            <li><a href="bookings_history.php" class="<?= $current_page == 'bookings_history.php' ? 'active' : '' ?>">
                <span class="nav-icon">≡</span>
                <span class="nav-label">Bookings</span>
            </a></li>
            <li><a href="reviews.php" class="<?= $current_page == 'reviews.php' ? 'active' : '' ?>">
                <span class="nav-icon">⭐</span>
                <span class="nav-label">Reviews</span>
            </a></li>
            <li><a href="contact_us.php" class="<?= $current_page == 'contact_us.php' ? 'active' : '' ?>">
                <span class="nav-icon">📧</span>
                <span class="nav-label">Contact</span>
            </a></li>
            <li><a href="navigation.php" class="<?= $current_page == 'navigation.php' ? 'active' : '' ?>">
                <span class="nav-icon">▲</span>
                <span class="nav-label">Map</span>
            </a></li>
            <li><a href="weather.php" class="<?= $current_page == 'weather.php' ? 'active' : '' ?>">
                <span class="nav-icon">🌤️</span>
                <span class="nav-label">Weather</span>
            </a></li>
            <li><a href="profile.php" class="<?= $current_page == 'profile.php' ? 'active' : '' ?>">
                <span class="nav-icon">●</span>
                <span class="nav-label">Profile</span>
            </a></li>
        </ul>
        <div class="nav-user">
            <div class="nav-user-info">
                <span class="user-icon">●</span>
                <span><?= htmlspecialchars($user_name) ?></span>
            </div>
            <a href="../auth/logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
</nav>
