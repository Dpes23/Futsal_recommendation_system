<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if user is admin
if ($_SESSION['username'] !== 'admin') {
    header('Location: index.php');
    exit();
}

require_once 'functions.php';

// Get database statistics
require_once 'database.php';
$pdo = getDatabaseConnection();

try {
    // Get total bookings count
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM bookings");
    $totalBookings = $stmt->fetch()['total'];
    
    // Get confirmed bookings count
    $stmt = $pdo->query("SELECT COUNT(*) as confirmed FROM bookings WHERE status = 'confirmed'");
    $confirmedBookings = $stmt->fetch()['confirmed'];
    
    // Get cancelled bookings count
    $stmt = $pdo->query("SELECT COUNT(*) as cancelled FROM bookings WHERE status = 'cancelled'");
    $cancelledBookings = $stmt->fetch()['cancelled'];
    
    // Get total users count
    $stmt = $pdo->query("SELECT COUNT(*) as users FROM users");
    $totalUsers = $stmt->fetch()['users'];
    
    // Get recent bookings
    $stmt = $pdo->query("
        SELECT b.*, u.username, u.full_name 
        FROM bookings b 
        JOIN users u ON b.user_id = u.id 
        ORDER BY b.created_at DESC 
        LIMIT 10
    ");
    $recentBookings = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Handle booking cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking'])) {
    $bookingId = $_POST['booking_id'] ?? '';
    
    if ($bookingId && cancelBooking($bookingId, null)) {
        $cancelMessage = "Booking cancelled successfully! Time slot is now available.";
    } else {
        $cancelError = "Failed to cancel booking. Please try again.";
    }
}

// Handle user removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_user'])) {
    $userId = $_POST['user_id'] ?? '';
    
    if ($userId && $userId != $_SESSION['user_id']) { // Prevent admin from deleting themselves
        if (removeUser($userId)) {
            $removeMessage = "User removed successfully! All their bookings have been cancelled.";
        } else {
            $removeError = "Failed to remove user. Please try again.";
        }
    }
}

$allBookings = getAllBookings();
$allUsers = getAllUsers();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Bookings</title>
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
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .admin-header {
            background: linear-gradient(135deg, #dc3545, #c82333);
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
            border-left: 4px solid #dc3545;
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
        
        .cancel-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .remove-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .cancel-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .remove-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .remove-btn:hover {
            background: #c82333;
            transform: translateY(-1px);
        }
        
        .tab-container {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .tab-btn {
            background: none;
            border: none;
            padding: 12px 24px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .tab-btn.active {
            color: #1e3c72;
            border-bottom-color: #1e3c72;
        }
        
        .tab-btn:hover {
            color: #1e3c72;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
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
        
        h2 {
            color: #1e3c72;
            font-size: 24px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .user-info-badge {
            background: rgba(255, 255, 255, 0.3);
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            margin-left: 10px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="admin-header">
        <div class="user-info">
            <div class="user-details">
                <h3>🔧 Admin Dashboard</h3>
                <p>Database Overview & Management</p>
            </div>
            <div>
                <a href="index.php" class="nav-btn">🔍 Search Futsals</a>
                <a href="my_bookings.php" class="nav-btn">📅 My Bookings</a>
                <a href="?logout=true" class="nav-btn">Logout</a>
            </div>
        </div>
    </div>
    
    <?php if (isset($cancelMessage)): ?>
        <div class="success-message">
            <?= htmlspecialchars($cancelMessage) ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($cancelError)): ?>
        <div class="error-message">
            <?= htmlspecialchars($cancelError) ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($removeMessage)): ?>
        <div class="success-message">
            <?= htmlspecialchars($removeMessage) ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($removeError)): ?>
        <div class="error-message">
            <?= htmlspecialchars($removeError) ?>
        </div>
    <?php endif; ?>
    
    <!-- Tab Navigation -->
    <div class="tab-container">
        <button class="tab-btn active" onclick="showTab('stats')">📊 Statistics</button>
        <button class="tab-btn" onclick="showTab('bookings')">📅 Bookings</button>
        <button class="tab-btn" onclick="showTab('users')">👥 Users</button>
    </div>
    
    <!-- Statistics Tab -->
    <div id="stats-tab" class="tab-content active">
        <h2>📊 Database Statistics</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <div class="booking-card" style="text-align: center; border-left-color: #28a745;">
                <h3 style="color: #28a745; margin-bottom: 10px;">👥 Total Users</h3>
                <div style="font-size: 32px; font-weight: bold; color: #333;"><?= number_format($totalUsers) ?></div>
            </div>
            
            <div class="booking-card" style="text-align: center; border-left-color: #007bff;">
                <h3 style="color: #007bff; margin-bottom: 10px;">📋 Total Bookings</h3>
                <div style="font-size: 32px; font-weight: bold; color: #333;"><?= number_format($totalBookings) ?></div>
            </div>
            
            <div class="booking-card" style="text-align: center; border-left-color: #28a745;">
                <h3 style="color: #28a745; margin-bottom: 10px;">✅ Confirmed Bookings</h3>
                <div style="font-size: 32px; font-weight: bold; color: #333;"><?= number_format($confirmedBookings) ?></div>
            </div>
            
            <div class="booking-card" style="text-align: center; border-left-color: #dc3545;">
                <h3 style="color: #dc3545; margin-bottom: 10px;">🚫 Cancelled Bookings</h3>
                <div style="font-size: 32px; font-weight: bold; color: #333;"><?= number_format($cancelledBookings) ?></div>
            </div>
        </div>
    </div>
    
    <!-- Bookings Tab -->
    <div id="bookings-tab" class="tab-content">
        <h2>📅 Recent Bookings</h2>
    
    <?php if (empty($allBookings)): ?>
        <div class="no-bookings">
            <h3>No bookings found</h3>
            <p>There are no bookings in the system yet.</p>
        </div>
    <?php else: ?>
        <?php foreach ($allBookings as $booking): ?>
            <div class="booking-card">
                <h3><?= htmlspecialchars($booking['futsal_name']) ?> 
                    <span class="user-info-badge"><?= htmlspecialchars($booking['username']) ?></span>
                </h3>
                
                <div class="booking-details">
                    <div class="detail-item">
                        <div class="detail-label">📍 Location</div>
                        <div class="detail-value"><?= htmlspecialchars($booking['futsal_address']) ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">👤 User</div>
                        <div class="detail-value"><?= htmlspecialchars($booking['full_name']) ?> (<?= htmlspecialchars($booking['username']) ?>)</div>
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
                        <div class="detail-label">� Prepayment</div>
                        <div class="detail-value">
                            Rs. <?= number_format($booking['prepayment_amount'] ?? 100) ?> 
                            (<?= ($booking['prepayment_status'] ?? 'pending') === 'paid' ? '✅ Paid' : '⏳ Pending' ?>)
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">�📋 Status</div>
                        <div class="detail-value">
                            <span class="status-badge status-<?= $booking['status'] ?>">
                                <?= $booking['status'] ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div style="text-align: right; margin-top: 15px;">
                    <?php if ($booking['status'] === 'confirmed'): ?>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to cancel this booking? This will make the time slot available for others.');">
                            <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                            <input type="hidden" name="cancel_booking" value="1">
                            <button type="submit" class="cancel-btn">🚫 Cancel Booking</button>
                        </form>
                    <?php else: ?>
                        <small style="color: #666;">Already cancelled</small>
                    <?php endif; ?>
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

    <!-- Users Tab -->
    <div id="users-tab" class="tab-content">
        <h2>👥 Registered Users</h2>
        
        <?php if (empty($allUsers)): ?>
            <div class="no-bookings">
                <h3>No users found</h3>
                <p>There are no registered users yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($allUsers as $user): ?>
                <div class="booking-card">
                    <h3><?= htmlspecialchars($user['full_name']) ?> (@<?= htmlspecialchars($user['username']) ?>)</h3>
                    
                    <div class="booking-details">
                        <div class="detail-item">
                            <div class="detail-label">📧 Email</div>
                            <div class="detail-value"><?= htmlspecialchars($user['email']) ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">📅 Registered</div>
                            <div class="detail-value"><?= date('M d, Y', strtotime($user['created_at'])) ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">🆔 User ID</div>
                            <div class="detail-value">#<?= str_pad($user['id'], 6, '0', STR_PAD_LEFT) ?></div>
                        </div>
                    </div>
                    
                    <div style="text-align: right; margin-top: 15px;">
                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to remove this user? All their bookings will be cancelled.');">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <input type="hidden" name="remove_user" value="1">
                                <button type="submit" class="remove-btn">🗑️ Remove User</button>
                            </form>
                        <?php else: ?>
                            <small style="color: #666;">Current admin user</small>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Users Tab -->
    <div id="users-tab" class="tab-content">
        <h2>👥 Registered Users</h2>
        
        <?php if (empty($allUsers)): ?>
            <div class="no-bookings">
                <h3>No users found</h3>
                <p>There are no registered users yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($allUsers as $user): ?>
                <div class="booking-card">
                    <h3><?= htmlspecialchars($user['full_name']) ?> (@<?= htmlspecialchars($user['username']) ?>)</h3>
                    
                    <div class="booking-details">
                        <div class="detail-item">
                            <div class="detail-label">📧 Email</div>
                            <div class="detail-value"><?= htmlspecialchars($user['email']) ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">📅 Registered</div>
                            <div class="detail-value"><?= date('M d, Y', strtotime($user['created_at'])) ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">🆔 User ID</div>
                            <div class="detail-value">#<?= str_pad($user['id'], 6, '0', STR_PAD_LEFT) ?></div>
                        </div>
                    </div>
                    
                    <div style="text-align: right; margin-top: 15px;">
                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to remove this user? All their bookings will be cancelled.');">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <input type="hidden" name="remove_user" value="1">
                                <button type="submit" class="remove-btn">🗑️ Remove User</button>
                            </form>
                        <?php else: ?>
                            <small style="color: #666;">Current admin user</small>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function showTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Remove active class from all buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected tab
    document.getElementById(tabName + '-tab').classList.add('active');
    
    // Add active class to clicked button
    event.target.classList.add('active');
}
</script>
</body>
</html>
