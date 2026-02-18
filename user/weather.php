<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$_SESSION['user_name'] = $_SESSION['user_name'] ?? 'User';

// Get user info
$userQuery = "SELECT username, email FROM users WHERE id = ?";
$stmt = $conn->prepare($userQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$userResult = $stmt->get_result();
$user = $userResult->fetch_assoc();

// Function to get weather data from OpenWeatherMap API (using default coordinates for parking area)
// You can replace with your actual parking location coordinates
$parkingLat = 6.9271; // Sri Lanka default
$parkingLon = 80.7789;
$apiKey = "1e5ae0b4e2d8c10cdbce7e2cb4efd743"; // Free OpenWeatherMap API key

function getWeatherData($lat, $lon, $apiKey) {
    try {
        $url = "https://api.openweathermap.org/data/2.5/weather?lat=$lat&lon=$lon&appid=$apiKey&units=metric";
        $response = @file_get_contents($url);
        
        if ($response === false) {
            return null;
        }
        
        $data = json_decode($response, true);
        return $data;
    } catch (Exception $e) {
        return null;
    }
}

$weatherData = getWeatherData($parkingLat, $parkingLon, $apiKey);

// Log user access to weather panel
$logQuery = "INSERT INTO user_access_logs (user_id, action, details, ip_address, timestamp) VALUES (?, ?, ?, ?, NOW())";
$stmt = $conn->prepare($logQuery);
$ipAddress = $_SERVER['REMOTE_ADDR'];
$action = "view_weather_panel";
$details = "User accessed weather information panel";
$stmt->bind_param("isss", $userId, $action, $details, $ipAddress);
$stmt->execute();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weather - ParkSmart</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            padding-top: 80px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        h2 {
            color: #667eea;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 30px;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .weather-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .weather-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 30px;
            color: white;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transition: transform 0.3s ease;
        }

        .weather-card:hover {
            transform: translateY(-5px);
        }

        .weather-icon {
            font-size: 60px;
            margin-bottom: 15px;
        }

        .weather-label {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .weather-value {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .weather-description {
            font-size: 14px;
            opacity: 0.85;
            text-transform: capitalize;
        }

        .info-section {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            border-left: 5px solid #667eea;
        }

        .info-section h3 {
            color: #2c3e50;
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-content {
            color: #555;
            font-size: 14px;
            line-height: 1.8;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .detail-box {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border-top: 3px solid #667eea;
        }

        .detail-label {
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .detail-value {
            color: #2c3e50;
            font-size: 20px;
            font-weight: 700;
        }

        .error-message {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #667eea;
        }

        .spinner {
            border: 4px solid #f0f0f0;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .footer {
            text-align: center;
            color: #999;
            font-size: 12px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        @media (max-width: 768px) {
            body {
                padding-top: 100px;
            }
            
            .container {
                padding: 20px;
            }

            .weather-grid {
                grid-template-columns: 1fr;
            }

            h2 {
                font-size: 22px;
            }

            .weather-value {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <?php include 'user_nav.php'; ?>

    <!-- Main Content -->
    <div class="container">
        <h2>Parking Area Weather Information</h2>

        <?php if ($weatherData && isset($weatherData['main'])): ?>
            <!-- Current Weather Cards -->
            <div class="weather-grid">
                <!-- Temperature Card -->
                <div class="weather-card">
                    <div class="weather-icon">🌡️</div>
                    <div class="weather-label">Temperature</div>
                    <div class="weather-value"><?php echo round($weatherData['main']['temp']); ?>°C</div>
                    <div class="weather-description">Feels like <?php echo round($weatherData['main']['feels_like']); ?>°C</div>
                </div>

                <!-- Weather Condition Card -->
                <div class="weather-card">
                    <div class="weather-icon">
                        <?php
                        $weatherMain = strtolower($weatherData['weather'][0]['main']);
                        $icon = '☀️';
                        if (strpos($weatherMain, 'cloud') !== false) $icon = '☁️';
                        if (strpos($weatherMain, 'rain') !== false) $icon = '🌧️';
                        if (strpos($weatherMain, 'snow') !== false) $icon = '❄️';
                        if (strpos($weatherMain, 'thunder') !== false) $icon = '⛈️';
                        if (strpos($weatherMain, 'fog') !== false) $icon = '🌫️';
                        echo $icon;
                        ?>
                    </div>
                    <div class="weather-label">Condition</div>
                    <div class="weather-value" style="font-size: 24px;"><?php echo ucfirst($weatherData['weather'][0]['main']); ?></div>
                    <div class="weather-description"><?php echo ucfirst($weatherData['weather'][0]['description']); ?></div>
                </div>

                <!-- Rain Card -->
                <div class="weather-card">
                    <div class="weather-icon">🌧️</div>
                    <div class="weather-label">Rainfall</div>
                    <div class="weather-value"><?php echo isset($weatherData['rain']['1h']) ? $weatherData['rain']['1h'] : '0'; ?> mm</div>
                    <div class="weather-description">Last 1 hour</div>
                </div>

                <!-- Wind Card -->
                <div class="weather-card">
                    <div class="weather-icon">💨</div>
                    <div class="weather-label">Wind Speed</div>
                    <div class="weather-value"><?php echo round($weatherData['wind']['speed']); ?> m/s</div>
                    <div class="weather-description">
                        <?php 
                        $windDegree = $weatherData['wind']['deg'] ?? 0;
                        $windDir = '';
                        if ($windDegree >= 337.5 || $windDegree < 22.5) $windDir = 'N';
                        elseif ($windDegree >= 22.5 && $windDegree < 67.5) $windDir = 'NE';
                        elseif ($windDegree >= 67.5 && $windDegree < 112.5) $windDir = 'E';
                        elseif ($windDegree >= 112.5 && $windDegree < 157.5) $windDir = 'SE';
                        elseif ($windDegree >= 157.5 && $windDegree < 202.5) $windDir = 'S';
                        elseif ($windDegree >= 202.5 && $windDegree < 247.5) $windDir = 'SW';
                        elseif ($windDegree >= 247.5 && $windDegree < 292.5) $windDir = 'W';
                        elseif ($windDegree >= 292.5 && $windDegree < 337.5) $windDir = 'NW';
                        echo "Direction: $windDir";
                        ?>
                    </div>
                </div>

                <!-- Humidity Card -->
                <div class="weather-card">
                    <div class="weather-icon">💧</div>
                    <div class="weather-label">Humidity</div>
                    <div class="weather-value"><?php echo $weatherData['main']['humidity']; ?>%</div>
                    <div class="weather-description">Moisture level</div>
                </div>

                <!-- Pressure Card -->
                <div class="weather-card">
                    <div class="weather-icon">🔘</div>
                    <div class="weather-label">Pressure</div>
                    <div class="weather-value"><?php echo $weatherData['main']['pressure']; ?> hPa</div>
                    <div class="weather-description">Atmospheric</div>
                </div>
            </div>

            <!-- Detailed Weather Information -->
            <div class="info-section">
                <h3>📍 Detailed Weather Information</h3>
                <div class="details-grid">
                    <div class="detail-box">
                        <div class="detail-label">Current Temperature</div>
                        <div class="detail-value"><?php echo round($weatherData['main']['temp']); ?>°C</div>
                    </div>
                    <div class="detail-box">
                        <div class="detail-label">Feels Like</div>
                        <div class="detail-value"><?php echo round($weatherData['main']['feels_like']); ?>°C</div>
                    </div>
                    <div class="detail-box">
                        <div class="detail-label">Min Temperature</div>
                        <div class="detail-value"><?php echo round($weatherData['main']['temp_min']); ?>°C</div>
                    </div>
                    <div class="detail-box">
                        <div class="detail-label">Max Temperature</div>
                        <div class="detail-value"><?php echo round($weatherData['main']['temp_max']); ?>°C</div>
                    </div>
                    <div class="detail-box">
                        <div class="detail-label">Wind Speed</div>
                        <div class="detail-value"><?php echo round($weatherData['wind']['speed']); ?> m/s</div>
                    </div>
                    <div class="detail-box">
                        <div class="detail-label">Wind Gust</div>
                        <div class="detail-value"><?php echo isset($weatherData['wind']['gust']) ? round($weatherData['wind']['gust']) : 'N/A'; ?> m/s</div>
                    </div>
                    <div class="detail-box">
                        <div class="detail-label">Rainfall (1h)</div>
                        <div class="detail-value"><?php echo isset($weatherData['rain']['1h']) ? $weatherData['rain']['1h'] : '0'; ?> mm</div>
                    </div>
                    <div class="detail-box">
                        <div class="detail-label">Cloud Coverage</div>
                        <div class="detail-value"><?php echo $weatherData['clouds']['all']; ?>%</div>
                    </div>
                </div>
            </div>

            <!-- Recommendations -->
            <div class="info-section">
                <h3>⚠️ Parking Recommendations</h3>
                <div class="info-content">
                    <?php
                    $recommendations = [];
                    
                    if ($weatherData['main']['temp'] > 35) {
                        $recommendations[] = "🌡️ <strong>High Heat:</strong> Consider covered parking or covered vehicle parking slots to protect your vehicle.";
                    }
                    if ($weatherData['main']['temp'] < 5) {
                        $recommendations[] = "❄️ <strong>Low Temperature:</strong> Ensure your vehicle is properly parked in a protected area.";
                    }
                    if (isset($weatherData['rain']['1h']) && $weatherData['rain']['1h'] > 0) {
                        $recommendations[] = "🌧️ <strong>Rainfall Detected:</strong> Seek covered parking slots to protect your vehicle from rain.";
                    }
                    if ($weatherData['wind']['speed'] > 15) {
                        $recommendations[] = "💨 <strong>Strong Winds:</strong> Park away from open areas and ensure vehicle is secure.";
                    }
                    if ($weatherData['main']['humidity'] > 80) {
                        $recommendations[] = "💧 <strong>High Humidity:</strong> Moisture could affect your vehicle - consider covered parking.";
                    }
                    
                    if (empty($recommendations)) {
                        echo "✅ <strong>Good Conditions:</strong> Weather conditions are favorable for parking. Normal parking without special precautions is recommended.";
                    } else {
                        foreach ($recommendations as $rec) {
                            echo "<p>• $rec</p>";
                        }
                    }
                    ?>
                </div>
            </div>

        <?php else: ?>
            <div class="error-message">
                ⚠️ Weather data is currently unavailable. Please refresh the page to try again.
            </div>
        <?php endif; ?>

        <div class="footer">
            <p>Weather data updated at: <strong><?php echo isset($weatherData['dt']) ? date('Y-m-d H:i:s', $weatherData['dt']) : 'N/A'; ?></strong></p>
            <p>Parking Area Location: Central Parking Zone | Data from OpenWeatherMap</p>
        </div>
    </div>
</body>
</html>
