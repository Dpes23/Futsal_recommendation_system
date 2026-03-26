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
            
            // Only include futsals within 5km radius
            if ($distance <= 5.0) {
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

// Booking functions
function createBooking($userId, $futsalName, $futsalAddress, $date, $time, $duration, $totalPrice) {
    require_once __DIR__ . '/../config/database.php';
    $pdo = getDatabaseConnection();
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO bookings (user_id, futsal_name, futsal_address, booking_date, booking_time, duration_hours, total_price)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$userId, $futsalName, $futsalAddress, $date, $time, $duration, $totalPrice]);
        
        return $pdo->lastInsertId();
        
    } catch(PDOException $e) {
        error_log("Booking creation error: " . $e->getMessage());
        return false;
    }
}

function processPrepayment($bookingId, $userId) {
    require_once __DIR__ . '/../config/database.php';
    $pdo = getDatabaseConnection();
    
    try {
        // First check if the booking exists and belongs to the user
        $checkStmt = $pdo->prepare("SELECT id FROM bookings WHERE id = ? AND user_id = ?");
        $checkStmt->execute([$bookingId, $userId]);
        
        if (!$checkStmt->fetch()) {
            error_log("Booking not found for ID: $bookingId, User: $userId");
            return false;
        }
        
        // Now update the prepayment status
        $stmt = $pdo->prepare("
            UPDATE bookings 
            SET prepayment_status = 'paid' 
            WHERE id = ?
        ");
        
        $result = $stmt->execute([$bookingId]);
        
        if (!$result) {
            error_log("Prepayment update failed for booking ID: $bookingId");
            return false;
        }
        
        $rowsAffected = $stmt->rowCount();
        
        if ($rowsAffected === 0) {
            error_log("No rows updated for booking ID: $bookingId");
            return false;
        }
        
        return true;
        
    } catch(PDOException $e) {
        error_log("Prepayment processing error: " . $e->getMessage());
        return false;
    }
}

function getBookingWithPrepayment($bookingId, $userId) {
    require_once __DIR__ . '/../config/database.php';
    $pdo = getDatabaseConnection();
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM bookings 
            WHERE id = ? AND user_id = ?
        ");
        
        $stmt->execute([$bookingId, $userId]);
        return $stmt->fetch();
        
    } catch(PDOException $e) {
        error_log("Get booking error: " . $e->getMessage());
        return false;
    }
}

function getUserBookings($userId) {
    require_once __DIR__ . '/../config/database.php';
    $pdo = getDatabaseConnection();
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM bookings 
            WHERE user_id = ? 
            ORDER BY booking_date DESC, booking_time DESC
        ");
        
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
        
    } catch(PDOException $e) {
        error_log("Get user bookings error: " . $e->getMessage());
        return [];
    }
}

function isTimeSlotAvailable($futsalName, $date, $time, $duration) {
    require_once __DIR__ . '/../config/database.php';
    $pdo = getDatabaseConnection();
    
    try {
        // Calculate end time
        $bookingDateTime = new DateTime("$date $time");
        $endTime = clone $bookingDateTime;
        $endTime->modify("+$duration hours");
        $endTimeStr = $endTime->format('H:i:s');
        
        // Check for overlapping bookings
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM bookings 
            WHERE futsal_name = ? 
            AND booking_date = ? 
            AND status = 'confirmed'
            AND (
                (booking_time <= ? AND ADDTIME(booking_time, SEC_TO_TIME(duration_hours * 3600)) > ?) OR
                (booking_time < ? AND ADDTIME(booking_time, SEC_TO_TIME(duration_hours * 3600)) >= ?) OR
                (booking_time >= ? AND ADDTIME(booking_time, SEC_TO_TIME(duration_hours * 3600)) <= ?)
            )
        ");
        
        $stmt->execute([
            $futsalName, 
            $date, 
            $time, $time,  // Case 1: existing booking starts before and ends after new booking start
            $time, $endTimeStr,  // Case 2: existing booking starts before and ends after new booking end
            $time, $endTimeStr   // Case 3: existing booking is completely within new booking time
        ]);
        
        $result = $stmt->fetch();
        return $result['count'] == 0;
        
    } catch(PDOException $e) {
        error_log("Time slot availability check error: " . $e->getMessage());
        return false;
    }
}

function cancelBooking($bookingId, $userId = null) {
    require_once __DIR__ . '/../config/database.php';
    $pdo = getDatabaseConnection();
    
    try {
        if ($userId === null) {
            // Admin can cancel any booking
            $stmt = $pdo->prepare("
                UPDATE bookings 
                SET status = 'cancelled' 
                WHERE id = ?
            ");
            return $stmt->execute([$bookingId]);
        } else {
            // User can only cancel their own bookings
            $stmt = $pdo->prepare("
                UPDATE bookings 
                SET status = 'cancelled' 
                WHERE id = ? AND user_id = ?
            ");
            return $stmt->execute([$bookingId, $userId]);
        }
        
    } catch(PDOException $e) {
        error_log("Cancel booking error: " . $e->getMessage());
        return false;
    }
}

function getAllBookings() {
    require_once __DIR__ . '/../config/database.php';
    $pdo = getDatabaseConnection();
    
    try {
        $stmt = $pdo->query("
            SELECT b.*, u.full_name, u.username 
            FROM bookings b 
            JOIN users u ON b.user_id = u.id 
            ORDER BY b.created_at DESC
        ");
        
        return $stmt->fetchAll();
        
    } catch(PDOException $e) {
        error_log("Get all bookings error: " . $e->getMessage());
        return [];
    }
}

function getAllUsers() {
    require_once __DIR__ . '/../config/database.php';
    $pdo = getDatabaseConnection();
    
    try {
        $stmt = $pdo->query("
            SELECT id, username, email, full_name, created_at 
            FROM users 
            ORDER BY created_at DESC
        ");
        
        return $stmt->fetchAll();
        
    } catch(PDOException $e) {
        error_log("Get all users error: " . $e->getMessage());
        return [];
    }
}

function removeUser($userId) {
    require_once __DIR__ . '/../config/database.php';
    $pdo = getDatabaseConnection();
    
    try {
        // First cancel all bookings for this user
        $stmt = $pdo->prepare("
            UPDATE bookings 
            SET status = 'cancelled' 
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        
        // Then delete the user
        $stmt = $pdo->prepare("
            DELETE FROM users 
            WHERE id = ?
        ");
        
        return $stmt->execute([$userId]);
        
    } catch(PDOException $e) {
        error_log("Remove user error: " . $e->getMessage());
        return false;
    }
}
?>
