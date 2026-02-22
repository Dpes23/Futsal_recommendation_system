<?php

// Algorithm: Haversine formula to calculate distance in kilometers
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $R = 6371; // Earth radius in km

    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon/2) * sin($dLon/2);

    $c = 2 * atan2(sqrt($a), sqrt(1-$a));

    $distance = $R * $c;

    return round($distance, 2);
}

// Recommendation function: sort by distance
function getRecommendedFutsals($userLat, $userLng, $futsals) {
    $results = [];

    foreach ($futsals as $futsal) {
        $dist = calculateDistance(
            $userLat,
            $userLng,
            $futsal['lat'],
            $futsal['lng']
        );

        $results[] = [
            'name'     => $futsal['name'],
            'address'  => $futsal['address'],
            'price'    => $futsal['price'],
            'rating'   => $futsal['rating'],
            'distance' => $dist
        ];
    }

    // Sort by distance (smallest → largest)
    usort($results, function($a, $b) {
        return $a['distance'] <=> $b['distance'];
    });

    // Return top 5
    return array_slice($results, 0, 5);
}

// Location-based recommendation function with real distance calculation
function getRecommendedFutsalsByLocation($userLocation, $futsals) {
    $results = [];
    $userLocationLower = strtolower(trim($userLocation));
    
    // Find matching futsal to get its coordinates as user's location
    $userLat = null;
    $userLng = null;
    
    foreach ($futsals as $futsal) {
        $futsalAddressLower = strtolower($futsal['address']);
        
        // Check if user's location matches or is part of futsal address
        if (strpos($futsalAddressLower, $userLocationLower) !== false || 
            strpos($userLocationLower, $futsalAddressLower) !== false) {
            
            // Use this futsal's coordinates as user's location
            $userLat = $futsal['lat'];
            $userLng = $futsal['lng'];
            break;
        }
    }
    
    // If no exact match found, use text similarity to find closest match
    if ($userLat === null) {
        $bestMatch = null;
        $bestSimilarity = 0;
        
        foreach ($futsals as $futsal) {
            $futsalAddressLower = strtolower($futsal['address']);
            $similarity = calculateTextSimilarity($userLocationLower, $futsalAddressLower);
            
            if ($similarity > $bestSimilarity && $similarity > 0.3) {
                $bestSimilarity = $similarity;
                $bestMatch = $futsal;
            }
        }
        
        if ($bestMatch) {
            $userLat = $bestMatch['lat'];
            $userLng = $bestMatch['lng'];
        }
    }
    
    // If we have user coordinates, calculate real distances
    if ($userLat !== null && $userLng !== null) {
        foreach ($futsals as $futsal) {
            $distance = calculateDistance($userLat, $userLng, $futsal['lat'], $futsal['lng']);
            
            // Only include futsals within 2km radius
            if ($distance <= 2.0) {
                $isRecommended = $futsal['rating'] >= 4.3 && $futsal['price'] <= 1500;
                
                $results[] = [
                    'name'     => $futsal['name'],
                    'address'  => $futsal['address'],
                    'price'    => $futsal['price'],
                    'rating'   => $futsal['rating'],
                    'distance' => $distance,
                    'isRecommended' => $isRecommended
                ];
            }
        }
        
        // Sort: Recommended first, then by real distance
        usort($results, function($a, $b) {
            if ($a['isRecommended'] && !$b['isRecommended']) return -1;
            if (!$a['isRecommended'] && $b['isRecommended']) return 1;
            return $a['distance'] <=> $b['distance'];
        });
    }
    
    // Return top 5
    return array_slice($results, 0, 5);
}

// Simple text similarity calculation
function calculateTextSimilarity($str1, $str2) {
    $len1 = strlen($str1);
    $len2 = strlen($str2);
    
    if ($len1 == 0 || $len2 == 0) return 0;
    
    $similar = 0;
    $shorter = min($len1, $len2);
    
    for ($i = 0; $i < $shorter; $i++) {
        if ($str1[$i] == $str2[$i]) {
            $similar++;
        }
    }
    
    return $similar / max($len1, $len2);
}