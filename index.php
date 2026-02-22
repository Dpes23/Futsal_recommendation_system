<?php
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
</head>
<body>

<div class="container">
    <h1>Futsal Recommendation System</h1>
    <p>Find futsals near your location or enter a location manually</p>

    <div class="location-options">
        <button type="button" id="useMyLocation" class="location-btn">
            📍 Use My Current Location
        </button>
        <span style="margin: 0 10px;">OR</span>
        <button type="button" id="manualLocation" class="location-btn">
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

    <div id="sortOptions" style="display: none; text-align: center; margin: 20px 0;">
        <label><strong>Sort by:</strong></label>
        <select id="sortBy" style="margin: 0 10px; padding: 5px;">
            <option value="distance">Distance</option>
            <option value="rating">Rating (High to Low)</option>
            <option value="price">Price (Low to High)</option>
        </select>
        <button type="button" id="applySort" class="location-btn">Apply Sort</button>
    </div>

    <?php if ($submitted): ?>
        <h2>Top 5 Nearest Futsals</h2>

        <?php if (empty($results)): ?>
            <p>No results found.</p>
        <?php else: ?>
            <?php foreach ($results as $f): ?>
                <div class="result-card">
                    <div class="card-content">
                        <h3><?= htmlspecialchars($f['name']) ?></h3>
                        <p>
                            <strong>Location:</strong> <?= htmlspecialchars($f['address']) ?><br>
                            <strong>Distance:</strong> <span class="distance"><?= $f['distance'] ?> km</span><br>
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
    <?php endif; ?>
</div>

<!-- Map Modal -->
<div id="mapModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3 id="mapTitle">Futsal Location</h3>
        <div id="map" style="height: 400px; width: 100%;"></div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
    // Futsal data with coordinates
    const futsalsData = <?= json_encode($futsals) ?>;

    let map;
    let currentMarker;

    // Modal functionality
    const modal = document.getElementById('mapModal');
    const closeBtn = document.getElementsByClassName('close')[0];

    closeBtn.onclick = function() {
        modal.style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }

    // Initialize map
    function initMap() {
        if (!map) {
            map = L.map('map').setView([27.7172, 85.3240], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: ' OpenStreetMap contributors'
            }).addTo(map);
        }
    }

    // Show futsal on map with directions
    function showFutsalOnMap(futsalName) {
        const futsal = futsalsData.find(f => f.name === futsalName);
        if (futsal) {
            initMap();
            
            // Clear previous markers and route
            if (currentMarker) {
                map.removeLayer(currentMarker);
            }
            if (window.userMarker) {
                map.removeLayer(window.userMarker);
            }
            if (window.routeControl) {
                map.removeControl(window.routeControl);
            }
            
            // Add marker for user's current location
            if (window.searchLocation === 'Your Current Location') {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const userLat = position.coords.latitude;
                        const userLng = position.coords.longitude;
                        
                        window.userMarker = L.marker([userLat, userLng], {
                            icon: L.divIcon({
                                className: 'user-location-marker',
                                html: '<div style="background: #007bff; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 12px;">📍</div>',
                                iconSize: [20, 20],
                                iconAnchor: [10, 10]
                            })
                        }).addTo(map);
                        
                        window.userMarker.bindPopup('<b>Your Location</b>').openPopup();
                        
                        // Add route from user to futsal
                        addRoute([userLat, userLng], [futsal.lat, futsal.lng], futsal);
                    },
                    function(error) {
                        // Just show futsal without user location
                        addFutsalMarker(futsal);
                    }
                );
            } else {
                // Manual location - find reference point and add as user location
                const referenceFutsal = futsalsData.find(f => 
                    f.address.toLowerCase().includes(window.searchLocation.toLowerCase()) ||
                    window.searchLocation.toLowerCase().includes(f.address.toLowerCase())
                );
                
                if (referenceFutsal) {
                    window.userMarker = L.marker([referenceFutsal.lat, referenceFutsal.lng], {
                        icon: L.divIcon({
                            className: 'user-location-marker',
                            html: '<div style="background: #007bff; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 12px;">📍</div>',
                            iconSize: [20, 20],
                            iconAnchor: [10, 10]
                        })
                    }).addTo(map);
                    
                    window.userMarker.bindPopup(`<b>${window.searchLocation}</b>`).openPopup();
                    
                    // Add route from search location to futsal
                    addRoute([referenceFutsal.lat, referenceFutsal.lng], [futsal.lat, futsal.lng], futsal);
                } else {
                    // Just show futsal without route
                    addFutsalMarker(futsal);
                }
            }
        }
    }
    
    // Add futsal marker
    function addFutsalMarker(futsal) {
        currentMarker = L.marker([futsal.lat, futsal.lng]).addTo(map);
        currentMarker.bindPopup(`<b>${futsal.name}</b><br>${futsal.address}<br>Rs. ${futsal.price}/hour<br>Rating: ${futsal.rating} ★`).openPopup();
        
        // Center map on futsal location
        map.setView([futsal.lat, futsal.lng], 15);
        
        // Update modal title
        document.getElementById('mapTitle').textContent = futsal.name;
        
        // Show modal
        modal.style.display = 'block';
        
        // Invalidate map size after modal is shown to ensure it renders correctly
        setTimeout(() => {
            map.invalidateSize();
        }, 100);
    }
    
    // Add route between two points
    function addRoute(fromCoords, toCoords, futsal) {
        // Use OpenRouteService for directions
        const url = `https://router.project-osrm.org/route/v1/driving/${fromCoords[1]},${fromCoords[0]};${toCoords[1]},${toCoords[0]}?overview=false&geometries=geojson`;
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.routes && data.routes.length > 0) {
                    const route = data.routes[0];
                    const coordinates = route.geometry.coordinates;
                    
                    // Convert coordinates for Leaflet (reverse lat/lng order)
                    const latlngs = coordinates.map(coord => [coord[1], coord[0]]);
                    
                    // Add route line
                    const routeLine = L.polyline(latlngs, {
                        color: '#007bff',
                        weight: 4,
                        opacity: 0.7
                    }).addTo(map);
                    
                    // Add futsal marker
                    addFutsalMarker(futsal);
                    
                    // Fit map to show both points and route
                    const group = new L.featureGroup([routeLine, currentMarker, window.userMarker]);
                    map.fitBounds(group.getBounds().pad(0.1));
                } else {
                    // Fallback - just show futsal marker
                    addFutsalMarker(futsal);
                }
            })
            .catch(error => {
                console.error('Error getting directions:', error);
                // Fallback - just show futsal marker
                addFutsalMarker(futsal);
            });
    }

    // Calculate distances from user's GPS coordinates
    function calculateDistancesFromCoords(userLat, userLng, futsals, returnAll = false) {
        const results = [];
        
        futsals.forEach(futsal => {
            const distance = calculateDistance(userLat, userLng, futsal.lat, futsal.lng);
            
            // Only include futsals within 2km radius
            if (distance <= 2.0) {
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
        
        // Sort: Recommended first, then by distance
        results.sort((a, b) => {
            if (a.isRecommended && !b.isRecommended) return -1;
            if (!a.isRecommended && b.isRecommended) return 1;
            return a.distance - b.distance;
        });
        
        // Return all results if requested, otherwise top 5
        return returnAll ? results : results.slice(0, 5);
    }

    // Calculate distance between two coordinates (Haversine formula)
    function calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371; // Earth radius in km
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = 
            Math.sin(dLat/2) * Math.sin(dLat/2) +
            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
            Math.sin(dLon/2) * Math.sin(dLon/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        const distance = R * c;
        return Math.round(distance * 100) / 100; // Round to 2 decimal places
    }

    // Display results on the page
    function displayResults(results, locationName) {
        const container = document.querySelector('.container');
        
        // Remove existing results if any
        const existingResults = container.querySelector('.results-section');
        if (existingResults) {
            existingResults.remove();
        }
        
        // Store current results for sorting
        window.currentResults = results;
        window.searchLocation = locationName;
        
        // Show sort options
        document.getElementById('sortOptions').style.display = 'block';
        
        // Create results section
        const resultsSection = document.createElement('div');
        resultsSection.className = 'results-section';
        resultsSection.innerHTML = `
            <h2>Top 5 Nearest Futsals from ${locationName}</h2>
            ${results.map(f => `
                <div class="result-card">
                    <div class="card-content">
                        <h3>${f.name}</h3>
                        <p>
                            <strong>Location:</strong> ${f.address}<br>
                            <strong>Distance:</strong> <span class="distance">${f.distance} km</span><br>
                            <strong>Price:</strong> Rs. ${f.price.toLocaleString()}/hour<br>
                            <strong>Rating:</strong> ${f.rating} ★
                        </p>
                    </div>
                    ${f.isRecommended ? '<span class="recommended-badge">Recommended</span>' : ''}
                </div>
            `).join('')}
        `;
        
        container.appendChild(resultsSection);
        
        // Add click events to new distance spans
        const distanceSpans = resultsSection.querySelectorAll('.distance');
        distanceSpans.forEach(span => {
            span.style.cursor = 'pointer';
            span.style.color = '#007bff';
            span.style.textDecoration = 'underline';
            span.title = 'Click to view on map';
            
            span.addEventListener('click', function() {
                const resultCard = this.closest('.result-card');
                const futsalName = resultCard.querySelector('h3').textContent;
                showFutsalOnMap(futsalName);
            });
        });
    }

    // Apply sorting functionality
    function performSort(allFutsalsWithDistance, sortBy) {
        let sortedResults;
        
        if (sortBy === 'rating') {
            sortedResults = allFutsalsWithDistance.sort((a, b) => {
                if (a.isRecommended && !b.isRecommended) return -1;
                if (!a.isRecommended && b.isRecommended) return 1;
                return b.rating - a.rating;
            });
        } else if (sortBy === 'price') {
            sortedResults = allFutsalsWithDistance.sort((a, b) => {
                if (a.isRecommended && !b.isRecommended) return -1;
                if (!a.isRecommended && b.isRecommended) return 1;
                return a.price - b.price;
            });
        } else {
            sortedResults = allFutsalsWithDistance.sort((a, b) => {
                if (a.isRecommended && !b.isRecommended) return -1;
                if (!a.isRecommended && b.isRecommended) return 1;
                return a.distance - b.distance;
            });
        }
        
        // Update display with sorted results (show top 5)
        const resultsSection = document.querySelector('.results-section');
        resultsSection.innerHTML = `
            <h2>Top 5 Futsals (Sorted by ${sortBy})</h2>
            ${sortedResults.slice(0, 5).map(f => `
                <div class="result-card">
                    <div class="card-content">
                        <h3>${f.name}</h3>
                        <p>
                            <strong>Location:</strong> ${f.address}<br>
                            <strong>Distance:</strong> <span class="distance">${f.distance} km</span><br>
                            <strong>Price:</strong> Rs. ${f.price.toLocaleString()}/hour<br>
                            <strong>Rating:</strong> ${f.rating} ★
                        </p>
                    </div>
                    ${f.isRecommended ? '<span class="recommended-badge">Recommended</span>' : ''}
                </div>
            `).join('')}
        `;
        
        // Re-add click events
        const distanceSpans = resultsSection.querySelectorAll('.distance');
        distanceSpans.forEach(span => {
            span.style.cursor = 'pointer';
            span.style.color = '#007bff';
            span.style.textDecoration = 'underline';
            span.title = 'Click to view on map';
            
            span.addEventListener('click', function() {
                const resultCard = this.closest('.result-card');
                const futsalName = resultCard.querySelector('h3').textContent;
                showFutsalOnMap(futsalName);
            });
        });
    }

    // Simple text similarity calculation for JavaScript
    function calculateTextSimilarity(str1, str2) {
        const len1 = str1.length;
        const len2 = str2.length;
        
        if (len1 === 0 || len2 === 0) return 0;
        
        let similar = 0;
        const shorter = Math.min(len1, len2);
        
        for (let i = 0; i < shorter; i++) {
            if (str1[i] === str2[i]) {
                similar++;
            }
        }
        
        return similar / Math.max(len1, len2);
    }

    // Main event listeners
    document.addEventListener('DOMContentLoaded', function() {
        // Location button functionality
        const useMyLocationBtn = document.getElementById('useMyLocation');
        const manualLocationBtn = document.getElementById('manualLocation');
        const locationForm = document.getElementById('locationForm');
        const loadingMessage = document.getElementById('loadingMessage');
        const applySortBtn = document.getElementById('applySort');

        useMyLocationBtn.addEventListener('click', function() {
            loadingMessage.style.display = 'block';
            locationForm.style.display = 'none';
            
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        // Success - got user's location
                        const userLat = position.coords.latitude;
                        const userLng = position.coords.longitude;
                        
                        // Calculate distances from user's actual location
                        const results = calculateDistancesFromCoords(userLat, userLng, futsalsData);
                        displayResults(results, 'Your Current Location');
                        
                        loadingMessage.style.display = 'none';
                    },
                    function(error) {
                        // Error - couldn't get location
                        loadingMessage.style.display = 'none';
                        alert('Could not get your location. Please enter location manually.');
                        locationForm.style.display = 'block';
                    }
                );
            } else {
                loadingMessage.style.display = 'none';
            loadingMessage.style.display = 'none';
            alert('Geolocation is not supported by your browser. Please enter location manually.');
            locationForm.style.display = 'block';
        }
    });

    manualLocationBtn.addEventListener('click', function() {
        console.log('Manual Location button clicked');
        locationForm.style.display = 'block';
        loadingMessage.style.display = 'none';
    });

    // Apply sorting functionality
    applySortBtn.addEventListener('click', function() {
        console.log('Apply Sort button clicked');
        const sortBy = document.getElementById('sortBy').value;
        console.log('Sort by:', sortBy);
        console.log('Search location:', window.searchLocation);
        
        // Calculate distances for ALL futsals from search location
        let allFutsalsWithDistance = [];
        
        if (window.searchLocation === 'Your Current Location') {
            console.log('Using GPS location for sorting');
            // Use GPS coordinates - recalculate for all futsals
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    console.log('Got GPS position for sorting:', position.coords);
                    const userLat = position.coords.latitude;
                    const userLng = position.coords.longitude;
                    allFutsalsWithDistance = calculateDistancesFromCoords(userLat, userLng, futsalsData, true);
                    performSort(allFutsalsWithDistance, sortBy);
                },
                function(error) {
                    console.error('GPS error for sorting:', error);
                    // Fallback to current results
                    performSort(window.currentResults, sortBy);
                }
            );
        } else {
            console.log('Using manual location for sorting:', window.searchLocation);
            // Manual location - find reference point and calculate all distances
            const referenceFutsal = futsalsData.find(f => 
                f.address.toLowerCase().includes(window.searchLocation.toLowerCase()) ||
                window.searchLocation.toLowerCase().includes(f.address.toLowerCase())
            );
            
            console.log('Reference futsal found:', referenceFutsal);
            
            if (referenceFutsal) {
                futsalsData.forEach(futsal => {
                    const distance = calculateDistance(
                        referenceFutsal.lat, referenceFutsal.lng,
                        futsal.lat, futsal.lng
                    );
                    
                    // Only include futsals within 2km radius
                    if (distance <= 2.0) {
                        allFutsalsWithDistance.push({
                            name: futsal.name,
                            address: futsal.address,
                            price: futsal.price,
                            rating: futsal.rating,
                            distance: distance,
                            isRecommended: futsal.rating >= 4.3 && futsal.price <= 1500
                        });
                    }
                });
                
                performSort(allFutsalsWithDistance, sortBy);
            } else {
                console.log('No reference futsal found, trying text similarity');
                // If no exact match found, try text similarity
                let bestMatch = null;
                let bestSimilarity = 0;
                
                futsalsData.forEach(futsal => {
                    const similarity = calculateTextSimilarity(window.searchLocation.toLowerCase(), futsal.address.toLowerCase());
                    if (similarity > bestSimilarity && similarity > 0.3) {
                        bestSimilarity = similarity;
                        bestMatch = futsal;
                    }
                });
                
                console.log('Best match found:', bestMatch);
                
                if (bestMatch) {
                    futsalsData.forEach(futsal => {
                        const distance = calculateDistance(
                            bestMatch.lat, bestMatch.lng,
                            futsal.lat, futsal.lng
                        );
                        
                        // Only include futsals within 2km radius
                        if (distance <= 2.0) {
                            allFutsalsWithDistance.push({
                                name: futsal.name,
                                address: futsal.address,
                                price: futsal.price,
                                rating: futsal.rating,
                                distance: distance,
                                isRecommended: futsal.rating >= 4.3 && futsal.price <= 1500
                            });
                        }
                    });
                    
                    performSort(allFutsalsWithDistance, sortBy);
                } else {
                    console.log('No good match found, using current results');
                    // Fallback to current results
                    performSort(window.currentResults, sortBy);
                }
            }
        }
    });

    // Add click events to existing distance spans (for PHP results)
    const existingDistanceSpans = document.querySelectorAll('.distance');
    console.log('Found existing distance spans:', existingDistanceSpans.length);
    existingDistanceSpans.forEach(span => {
        span.style.cursor = 'pointer';
        span.style.color = '#007bff';
        span.style.textDecoration = 'underline';
        span.title = 'Click to view on map';
        
        span.addEventListener('click', function() {
            console.log('Distance span clicked');
            alert('Distance clicked! Futsal: ' + this.closest('.result-card').querySelector('h3').textContent);
            const resultCard = this.closest('.result-card');
            const futsalName = resultCard.querySelector('h3').textContent;
            showFutsalOnMap(futsalName);
        });
    });
});
</script>

</body>
</html>