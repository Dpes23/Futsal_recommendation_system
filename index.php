<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit();
}

require_once 'functions.php';
$futsals = include 'futsals_data.php';

// Default location
$userLocation = '';
$results = [];
$submitted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userLocation = $_POST['location'] ?? '';
    $results = getRecommendedFutsalsByLocation($userLocation, $futsals);
    $submitted = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Futsal Recommendation System</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        body {
            background-image: url('https://images.unsplash.com/photo-1541252260730-0412e8e2108e?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1950&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-repeat: no-repeat;
            min-height: 100vh;
            margin: 0;
            padding: 20px 0;
        }
        
        .container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            padding: 30px;
            max-width: 700px;
            margin: 0 auto;
        }
        
        .user-header {
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            color: white;
            padding: 20px;
            margin: -30px -30px 20px -30px;
            border-radius: 20px 20px 0 0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .user-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .user-details h3 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }
        
        .user-details p {
            margin: 5px 0 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        
        .logout-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            text-decoration: none;
            color: white;
            transform: translateY(-2px);
        }
        
        .welcome-message {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
        
        .welcome-message p {
            font-size: 16px;
            margin: 0;
            opacity: 0.8;
        }
        
        .location-options {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 25px;
            gap: 15px;
        }
        
        .location-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .location-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        .form-row {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            gap: 10px;
        }
        
        .form-row label {
            font-weight: 500;
            color: #333;
            min-width: 80px;
        }
        
        .form-row input {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-row input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        button[type="submit"] {
            background: linear-gradient(135deg, #11998e, #38ef7d);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(17, 153, 142, 0.3);
        }
        
        button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(17, 153, 142, 0.4);
        }
        
        #loadingMessage {
            background: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 15px;
            margin: 20px 0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .result-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
            border: 1px solid #f0f0f0;
        }
        
        .result-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }
        
        .result-card h3 {
            margin: 0 0 10px 0;
            color: #1e3c72;
            font-size: 18px;
        }
        
        .result-card p {
            margin: 0;
            color: #666;
            line-height: 1.6;
        }
        
        .distance {
            color: #667eea;
            font-weight: 600;
            cursor: pointer;
            text-decoration: underline;
        }
        
        .distance:hover {
            color: #764ba2;
        }
        
        .recommended-badge {
            background: linear-gradient(135deg, #f093fb, #f5576c);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            position: absolute;
            top: 15px;
            right: 15px;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
            backdrop-filter: blur(5px);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 20px;
            border-radius: 20px;
            width: 80%;
            max-width: 600px;
            overflow: hidden;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: black;
        }
        
        h2 {
            color: #1e3c72;
            font-size: 24px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="user-header">
        <div class="user-info">
            <div class="user-details">
                <h3>👋 Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?>!</h3>
                <p>@<?= htmlspecialchars($_SESSION['username']) ?></p>
            </div>
            <a href="?logout=true" class="logout-btn">Logout</a>
        </div>
    </div>
    
    <div class="welcome-message">
        <p style="text-align: center;">Find futsals near your location or enter a location manually</p>
    </div>

    <div class="location-options">
        <button type="button" class="location-btn" onclick="showGPS()">
            📍 Use My Current Location
        </button>
        <span style="margin: 0 10px;">OR</span>
        <button type="button" class="location-btn" onclick="showManual()">
            📍 Enter Location Manually
        </button>
    </div>

    <form method="POST" id="locationForm" style="display: none;">
        <div class="form-row">
            <label>Location:</label>
            <input type="text" name="location" placeholder="e.g., Baneshwor" value="<?= htmlspecialchars($userLocation) ?>" required>
        </div>

        <button type="submit">Find Nearest Futsals</button>
    </form>

    <div id="loadingMessage" style="display: none; text-align: center; padding: 20px;">
        <p>📍 Getting your location...</p>
    </div>

    <?php if ($submitted): ?>
        <div class="results-section">
            <h2>Top 5 Nearest Futsals</h2>

            <?php if (empty($results)): ?>
                <p>No results found.</p>
            <?php else: ?>
                <?php foreach ($results as $index => $f): ?>
                    <div class="result-card">
                        <div class="card-content">
                            <h3><?= htmlspecialchars($f['name']) ?></h3>
                            <p>
                                <strong>Location:</strong> <?= htmlspecialchars($f['address']) ?><br>
                                <strong>Distance:</strong> <span class="distance" data-futsal-name="<?= htmlspecialchars($f['name']) ?>"><?= number_format($f['distance'], 1) ?> km</span><br>
                                <strong>Price:</strong> Rs. <?= number_format($f['price']) ?>/hour<br>
                                <strong>Rating:</strong> <?= $f['rating'] ?> ★
                            </p>
                        </div>
                        <?php if (isset($f['isRecommended']) && $f['isRecommended']): ?>
                            <span class="recommended-badge">Recommended</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Map Modal -->
<div id="mapModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3>Futsal Location</h3>
        <div id="map" style="height: 400px; width: 100%;"></div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
// Futsal data
const futsalsData = <?= json_encode($futsals) ?>;

let map;

// Simple working functions
function showGPS() {
    console.log('GPS button clicked!');
    document.getElementById('loadingMessage').style.display = 'block';
    document.getElementById('locationForm').style.display = 'none';
    
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                console.log('Got GPS position:', position.coords);
                const userLat = position.coords.latitude;
                const userLng = position.coords.longitude;
                
                // Calculate distances and show results
                const results = calculateDistances(userLat, userLng);
                displayResults(results, 'Your Current Location');
                
                document.getElementById('loadingMessage').style.display = 'none';
            },
            function(error) {
                console.error('GPS error:', error);
                document.getElementById('loadingMessage').style.display = 'none';
                alert('Could not get your location. Please enter location manually.');
                document.getElementById('locationForm').style.display = 'block';
            }
        );
    } else {
        document.getElementById('loadingMessage').style.display = 'none';
        document.getElementById('locationForm').style.display = 'block';
    }
}

function showManual() {
    console.log('Manual button clicked!');
    document.getElementById('locationForm').style.display = 'block';
    document.getElementById('loadingMessage').style.display = 'none';
}

function calculateDistances(userLat, userLng) {
    const results = [];
    
    futsalsData.forEach(futsal => {
        const distance = calculateDistance(userLat, userLng, futsal.lat, futsal.lng);
        
        if (distance <= 5.0) {
            results.push({
                name: futsal.name,
                address: futsal.address,
                price: futsal.price,
                rating: futsal.rating,
                distance: distance,
                isRecommended: futsal.rating >= 4.3 && futsal.price <= 1500
            });
        }
    });
    
    results.sort((a, b) => {
        if (a.isRecommended && !b.isRecommended) return -1;
        if (!a.isRecommended && b.isRecommended) return 1;
        return a.distance - b.distance;
    });
    
    return results.slice(0, 5);
}

function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = 
        Math.sin(dLat/2) * Math.sin(dLat/2) +
        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
        Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    const distance = R * c;
    return Math.round(distance * 100) / 100;
}

function displayResults(results, locationName) {
    const container = document.querySelector('.container');
    
    // Remove existing results
    const existingResults = container.querySelector('.results-section');
    if (existingResults) {
        existingResults.remove();
    }
    
    // Create results section
    const resultsSection = document.createElement('div');
    resultsSection.className = 'results-section';
    resultsSection.innerHTML = `
        <h2>Top 5 Nearest Futsals from ${locationName}</h2>
        ${results.map((f, index) => `
            <div class="result-card">
                <div class="card-content">
                    <h3>${f.name}</h3>
                    <p>
                        <strong>Location:</strong> ${f.address}<br>
                        <strong>Distance:</strong> <span class="distance" data-futsal-index="${index}">${f.distance.toFixed(1)} km</span><br>
                        <strong>Price:</strong> Rs. ${f.price.toLocaleString()}/hour<br>
                        <strong>Rating:</strong> ${f.rating} ★
                    </p>
                </div>
                ${f.isRecommended ? '<span class="recommended-badge">Recommended</span>' : ''}
            </div>
        `).join('')}
    `;
    
    container.appendChild(resultsSection);
    
    // Add event listeners using event delegation
    resultsSection.addEventListener('click', function(e) {
        if (e.target.classList.contains('distance')) {
            const futsalIndex = e.target.getAttribute('data-futsal-index');
            const selectedFutsal = results[futsalIndex];
            showMap(selectedFutsal.name);
        }
    });
}

function showMap(futsalName) {
    console.log('Show map for:', futsalName);
    const futsal = futsalsData.find(f => f.name === futsalName);
    
    if (futsal) {
        const modal = document.getElementById('mapModal');
        modal.style.display = 'block';
        
        if (!map) {
            map = L.map('map').setView([27.7172, 85.3240], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: ' OpenStreetMap contributors'
            }).addTo(map);
        }
        
        // Clear previous markers
        map.eachLayer(function(layer) {
            if (layer instanceof L.Marker) {
                map.removeLayer(layer);
            }
        });
        
        // Add futsal marker
        const marker = L.marker([futsal.lat, futsal.lng]).addTo(map);
        marker.bindPopup(`<b>${futsal.name}</b><br>${futsal.address}<br>Rs. ${futsal.price}/hour<br>Rating: ${futsal.rating} ★`).openPopup();
        
        map.setView([futsal.lat, futsal.lng], 15);
        
        setTimeout(() => {
            map.invalidateSize();
        }, 100);
    }
}

// Modal close functionality
document.querySelector('.close').onclick = function() {
    document.getElementById('mapModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('mapModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}

// Global event listener for all distance clicks (PHP and JS rendered)
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('distance')) {
        let futsalName = null;
        
        // Try to get futsal name from data attribute (PHP rendered)
        if (e.target.getAttribute('data-futsal-name')) {
            futsalName = e.target.getAttribute('data-futsal-name');
        }
        // Try to get from results array (JS rendered)
        else if (e.target.getAttribute('data-futsal-index')) {
            // This will be handled by the existing displayResults listener
            return;
        }
        
        if (futsalName) {
            showMap(futsalName);
        }
    }
});
</script>

</body>
</html>
