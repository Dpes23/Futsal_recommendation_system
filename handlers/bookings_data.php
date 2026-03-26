<?php
// Bookings storage file
if (!file_exists(__DIR__ . '/bookings.json')) {
    file_put_contents(__DIR__ . '/bookings.json', json_encode([]));
}

function getAllBookings() {
    $bookingsJson = file_get_contents(__DIR__ . '/bookings.json');
    return json_decode($bookingsJson, true) ?: [];
}

function saveBooking($booking) {
    $bookings = getAllBookings();
    $booking['id'] = uniqid('booking_', true);
    $booking['created_at'] = date('Y-m-d H:i:s');
    $bookings[] = $booking;
    
    file_put_contents(__DIR__ . '/bookings.json', json_encode($bookings, JSON_PRETTY_PRINT));
    return $booking['id'];
}

function getBookingsByUser($userId) {
    $bookings = getAllBookings();
    $userBookings = [];
    
    foreach ($bookings as $booking) {
        if ($booking['user_id'] === $userId) {
            $userBookings[] = $booking;
        }
    }
    
    return $userBookings;
}
?>
