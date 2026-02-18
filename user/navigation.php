<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login2.php');
    exit();
}

$slot_number = $_GET['slot'] ?? '';
$user_name = $_SESSION['user_name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navigation - ParkSmart</title>
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

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .header h1 {
            color: #667eea;
            font-size: 32px;
            margin-bottom: 10px;
        }

        .header p {
            color: #666;
            font-size: 16px;
        }

        .map-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 20px;
        }

        .map-frame {
            width: 100%;
            height: 500px;
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .location-status {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
        }

        .location-status.loading {
            background: linear-gradient(135deg, #ffd54f 0%, #ffb300 100%);
            color: white;
        }

        .location-status.success {
            background: linear-gradient(135deg, #4caf50 0%, #45a049 100%);
            color: white;
        }

        .location-status.error {
            background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
            color: white;
        }

        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,0.3);
            border-top: 3px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .info-box {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 20px;
        }

        .info-box h2 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 24px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .info-icon {
            font-size: 24px;
        }

        .info-text {
            color: #333;
            font-size: 16px;
        }

        .button-group {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102,126,234,0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(240,147,251,0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(79,172,254,0.4);
        }

        @media (max-width: 768px) {
            .map-frame {
                height: 350px;
            }

            .header h1 {
                font-size: 24px;
            }

            .btn {
                padding: 12px 20px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <?php include 'user_nav.php'; ?>

    <div class="container">
        <div class="header">
            <h1>🗺️ Navigation & Directions</h1>
            <p>Get directions to ParkSmart Parking Facility<?php if($slot_number): ?> - Slot <?= htmlspecialchars($slot_number) ?><?php endif; ?></p>
        </div>

        <div class="location-status" id="locationStatus">
            <div class="spinner"></div>
            <span id="statusText">Getting your current location...</span>
        </div>

        <div class="map-container">
            <iframe 
                id="mapFrame"
                class="map-frame"
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d63371.80192102!2d79.8612!3d6.9271!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3ae253d10f7a7003%3A0x320b2e4d32d3838d!2sColombo%2C%20Sri%20Lanka!5e0!3m2!1sen!2s!4v1234567890"
                allowfullscreen=""
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade">
            </iframe>
        </div>

        <!-- Navigation Buttons (Right under map) -->
        <div class="button-group" style="margin-top: 20px;">
            <button onclick="getCurrentLocation()" class="btn btn-success" id="locationBtn">
                📍 Get My Current Location
            </button>
            <a href="#" id="googleMapsBtn" class="btn btn-primary" target="_blank">
                🚗 Get Directions (Google Maps)
            </a>
            <a href="#" id="wazeBtn" class="btn btn-secondary" target="_blank">
                🗺️ Get Directions (Waze)
            </a>
        </div>

        <div class="info-box">
            <h2>📍 Parking Location Details</h2>
            <div class="info-item">
                <span class="info-icon">🏢</span>
                <span class="info-text"><strong>Address:</strong> 123 Main Street, Colombo 00100, Sri Lanka</span>
            </div>
            <div class="info-item">
                <span class="info-icon">☎️</span>
                <span class="info-text"><strong>Contact:</strong> +94 11 234 5678</span>
            </div>
            <div class="info-item">
                <span class="info-icon">💬</span>
                <span class="info-text"><strong>WhatsApp:</strong> 070 430 5875</span>
            </div>
            <?php if($slot_number): ?>
            <div class="info-item">
                <span class="info-icon">🅿️</span>
                <span class="info-text"><strong>Your Slot:</strong> <?= htmlspecialchars($slot_number) ?></span>
            </div>
            <?php endif; ?>
            <div class="info-item">
                <span class="info-icon">🕐</span>
                <span class="info-text"><strong>Operating Hours:</strong> 24/7</span>
            </div>
        </div>

        <!-- Additional Actions -->
        <div class="button-group">
            <a href="https://wa.me/94704305875?text=Hello%20ParkSmart%20Support%20Team%2C%0A%0AI%20need%20directions%20to%20the%20parking%20facility<?php if($slot_number): ?>%20for%20my%20booking%20at%20Slot%20<?= urlencode($slot_number) ?><?php endif; ?>.%0A%0AThank%20you." target="_blank" class="btn btn-success">
                💬 Contact via WhatsApp
            </a>
            <a href="dashboard.php" class="btn btn-secondary">
                🏠 Back to Dashboard
            </a>
        </div>
    </div>

    <script>
        // ParkSmart Parking Location (Colombo, Sri Lanka)
        const destination = {
            lat: 6.9271,
            lng: 79.8612,
            name: 'ParkSmart Parking - 123 Main Street, Colombo'
        };

        let userLocation = null;

        // Initialize - Set default button links immediately
        document.addEventListener('DOMContentLoaded', function() {
            // Set default links (will use device's current location)
            updateNavigationButtons(null, destination);
            
            // Try to get location automatically
            getCurrentLocation();
        });

        // Function to get current location (can be called manually)
        function getCurrentLocation() {
            const statusDiv = document.getElementById('locationStatus');
            const statusText = document.getElementById('statusText');
            const locationBtn = document.getElementById('locationBtn');
            
            // Show loading status
            statusDiv.style.display = 'block';
            statusDiv.className = 'location-status loading';
            statusText.innerHTML = '<div class="spinner"></div> Getting your current location...';
            if (locationBtn) {
                locationBtn.textContent = '⏳ Detecting...';
                locationBtn.disabled = true;
            }

            if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                // Success callback
                function(position) {
                    userLocation = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };

                    // Update status
                    const statusDiv = document.getElementById('locationStatus');
                    const statusText = document.getElementById('statusText');
                    const locationBtn = document.getElementById('locationBtn');
                    
                    statusDiv.className = 'location-status success';
                    statusText.textContent = `✓ Location detected! (${userLocation.lat.toFixed(4)}, ${userLocation.lng.toFixed(4)})`;

                    // Update map with location
                    updateMapWithDirections(userLocation, destination);

                    // Update buttons with user location
                    updateNavigationButtons(userLocation, destination);

                    // Update button text
                    if (locationBtn) {
                        locationBtn.textContent = '✓ Location Updated';
                        locationBtn.disabled = false;
                    }

                    // Hide status after 3 seconds
                    setTimeout(() => {
                        statusDiv.style.display = 'none';
                        if (locationBtn) locationBtn.textContent = '📍 Refresh My Location';
                    }, 3000);
                },
                // Error callback
                function(error) {
                    const statusDiv = document.getElementById('locationStatus');
                    const statusText = document.getElementById('statusText');
                    const locationBtn = document.getElementById('locationBtn');
                    
                    statusDiv.className = 'location-status error';
                    
                    let errorMessage = '';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMessage = '⚠ Location access denied. Click "Allow" to enable location.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMessage = '⚠ Location unavailable. Please check your device settings.';
                            break;
                        case error.TIMEOUT:
                            errorMessage = '⚠ Location request timeout. Please try again.';
                            break;
                        default:
                            errorMessage = '⚠ Unable to get location. Click the button to retry.';
                    }
                    
                    statusText.textContent = errorMessage;

                    // Update buttons without user location (uses current location in Google Maps)
                    updateNavigationButtons(null, destination);

                    // Update button text
                    if (locationBtn) {
                        locationBtn.textContent = '🔄 Try Again';
                        locationBtn.disabled = false;
                    }

                    // Don't hide status on error - let user read it
                    setTimeout(() => {
                        statusDiv.style.display = 'none';
                    }, 6000);
                },
                // Options
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
            } else {
                // Geolocation not supported
                const statusDiv = document.getElementById('locationStatus');
                const statusText = document.getElementById('statusText');
                const locationBtn = document.getElementById('locationBtn');
                
                statusDiv.className = 'location-status error';
                statusText.textContent = '⚠ Geolocation not supported by your browser.';
                
                updateNavigationButtons(null, destination);
                
                if (locationBtn) {
                    locationBtn.textContent = '❌ Not Supported';
                    locationBtn.disabled = true;
                }
            }
        }

        function updateMapWithDirections(origin, dest) {
            const mapFrame = document.getElementById('mapFrame');
            
            // Calculate center point between origin and destination for better view
            const centerLat = (origin.lat + dest.lat) / 2;
            const centerLng = (origin.lng + dest.lng) / 2;
            
            // Calculate zoom level based on distance
            const latDiff = Math.abs(origin.lat - dest.lat);
            const lngDiff = Math.abs(origin.lng - dest.lng);
            const maxDiff = Math.max(latDiff, lngDiff);
            
            let zoom = 12;
            if (maxDiff > 0.5) zoom = 10;
            else if (maxDiff > 0.2) zoom = 11;
            else if (maxDiff > 0.1) zoom = 12;
            else zoom = 13;
            
            // Simple embed showing destination with marker
            const simpleUrl = `https://www.google.com/maps?q=${dest.lat},${dest.lng}&output=embed&z=${zoom}`;
            
            mapFrame.src = simpleUrl;
        }

        function updateNavigationButtons(origin, dest) {
            const googleMapsBtn = document.getElementById('googleMapsBtn');
            const wazeBtn = document.getElementById('wazeBtn');

            if (origin) {
                // With user's current location - Direct navigation from A to B
                googleMapsBtn.href = `https://www.google.com/maps/dir/${origin.lat},${origin.lng}/${dest.lat},${dest.lng}`;
                wazeBtn.href = `https://www.waze.com/ul?ll=${dest.lat},${dest.lng}&navigate=yes`;
                
                // Update button text to show ready state
                googleMapsBtn.innerHTML = '🚗 Get Directions (From Your Location)';
                wazeBtn.innerHTML = '🗺️ Navigate with Waze';
            } else {
                // Without user location - Google Maps will use device's current location
                googleMapsBtn.href = `https://www.google.com/maps/dir/?api=1&destination=${dest.lat},${dest.lng}&travelmode=driving`;
                wazeBtn.href = `https://www.waze.com/ul?ll=${dest.lat},${dest.lng}&navigate=yes`;
                
                // Update button text to show default state
                googleMapsBtn.innerHTML = '🚗 Get Directions (Google Maps)';
                wazeBtn.innerHTML = '🗺️ Get Directions (Waze)';
            }
        }
    </script>
</body>
</html>
