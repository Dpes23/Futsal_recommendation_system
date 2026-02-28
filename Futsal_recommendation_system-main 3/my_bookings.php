<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'functions.php';

$userBookings = getUserBookings($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - Futsal Recommendation System</title>
    <link rel="stylesheet" href="style.css">
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
            max-width: 900px;
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
        
        .nav-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
            font-weight: 500;
            margin-left: 10px;
        }
        
        .nav-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            text-decoration: none;
            color: white;
            transform: translateY(-2px);
        }
        
        .booking-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
            border: 1px solid #f0f0f0;
            border-left: 4px solid #667eea;
        }
        
        .booking-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }
        
        .booking-card h3 {
            margin: 0 0 15px 0;
            color: #1e3c72;
            font-size: 18px;
        }
        
        .booking-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .detail-item {
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .detail-label {
            font-weight: 600;
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .detail-value {
            color: #333;
            font-size: 14px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-confirmed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .no-bookings {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .no-bookings h3 {
            margin-bottom: 15px;
            color: #1e3c72;
        }
        
        .book-now-btn {
            background: linear-gradient(135deg, #11998e, #38ef7d);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(17, 153, 142, 0.3);
            text-decoration: none;
            display: inline-block;
        }
        
        .book-now-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(17, 153, 142, 0.4);
            text-decoration: none;
            color: white;
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
                <h3>📅 My Bookings</h3>
                <p><?= htmlspecialchars($_SESSION['full_name']) ?></p>
            </div>
            <div>
                <a href="index.php" class="nav-btn">🔍 Search Futsals</a>
                <a href="logout.php" class="nav-btn">Logout</a>
            </div>
        </div>
    </div>
    
    <h2>Your Futsal Bookings</h2>
    
    <?php if (empty($userBookings)): ?>
        <div class="no-bookings">
            <h3>No bookings yet</h3>
            <p>You haven't made any futsal bookings yet. Start by searching for futsals and booking your preferred time slots!</p>
            <br>
            <a href="index.php" class="book-now-btn">🔍 Find Futsals to Book</a>
        </div>
    <?php else: ?>
        <?php foreach ($userBookings as $booking): ?>
            <div class="booking-card">
                <h3><?= htmlspecialchars($booking['futsal_name']) ?></h3>
                
                <div class="booking-details">
                    <div class="detail-item">
                        <div class="detail-label">📍 Location</div>
                        <div class="detail-value"><?= htmlspecialchars($booking['futsal_address']) ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">📅 Date</div>
                        <div class="detail-value"><?= date('M d, Y', strtotime($booking['booking_date'])) ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">⏰ Time</div>
                        <div class="detail-value"><?= date('h:i A', strtotime($booking['booking_time'])) ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">⏱️ Duration</div>
                        <div class="detail-value"><?= $booking['duration_hours'] ?> hour(s)</div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">💰 Total Price</div>
                        <div class="detail-value">Rs. <?= number_format($booking['total_price']) ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">📋 Status</div>
                        <div class="detail-value">
                            <span class="status-badge status-<?= $booking['status'] ?>">
                                <?= $booking['status'] ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div style="text-align: right; margin-top: 15px;">
                    <small style="color: #666;">
                        Booking ID: #<?= str_pad($booking['id'], 6, '0', STR_PAD_LEFT) ?> | 
                        Booked on: <?= date('M d, Y h:i A', strtotime($booking['created_at'])) ?>
                    </small>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
</html>
