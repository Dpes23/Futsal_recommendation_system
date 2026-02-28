<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'functions.php';
$futsals = include 'futsals_data.php';

$bookingSuccess = false;
$bookingError = '';
$selectedFutsal = null;
$prepaymentPaid = false;

// Handle prepayment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_prepayment'])) {
    $bookingId = $_POST['booking_id'] ?? '';
    
    if ($bookingId && processPrepayment($bookingId, $_SESSION['user_id'])) {
        $prepaymentPaid = true;
        $prepaymentMessage = "Prepayment of Rs. 100 paid successfully!";
    } else {
        $prepaymentError = "Failed to process prepayment. Please try again.";
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['pay_prepayment'])) {
    $futsalName = $_POST['futsal_name'] ?? '';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $duration = $_POST['duration'] ?? '';
    
    // Find the futsal to get price and address
    foreach ($futsals as $futsal) {
        if ($futsal['name'] === $futsalName) {
            $selectedFutsal = $futsal;
            break;
        }
    }
    
    if ($selectedFutsal && $date && $time && $duration) {
        // Validate date is not in the past
        $bookingDate = new DateTime($date);
        $today = new DateTime();
        $today->setTime(0, 0, 0);
        
        if ($bookingDate < $today) {
            $bookingError = 'Booking date cannot be in the past.';
        } elseif (!isTimeSlotAvailable($futsalName, $date, $time, $duration)) {
            $bookingError = 'This time slot is already booked. Please choose a different time.';
        } else {
            $totalPrice = $selectedFutsal['price'] * $duration;
            $bookingId = createBooking(
                $_SESSION['user_id'],
                $futsalName,
                $selectedFutsal['address'],
                $date,
                $time,
                $duration,
                $totalPrice
            );
            
            if ($bookingId) {
                $bookingSuccess = true;
            } else {
                $bookingError = 'Failed to create booking. Please try again.';
            }
        }
    } else {
        $bookingError = 'Please fill in all fields.';
    }
}

// Get futsal details from URL parameter if available
if (!$selectedFutsal && isset($_GET['futsal'])) {
    foreach ($futsals as $futsal) {
        if ($futsal['name'] === $_GET['futsal']) {
            $selectedFutsal = $futsal;
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Futsal - Futsal Recommendation System</title>
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
            max-width: 600px;
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
        
        .back-btn {
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
        
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            text-decoration: none;
            color: white;
            transform: translateY(-2px);
        }
        
        .futsal-info {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 25px;
        }
        
        .futsal-info h3 {
            margin: 0 0 10px 0;
            font-size: 22px;
        }
        
        .futsal-info p {
            margin: 5px 0;
            opacity: 0.9;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
            box-sizing: border-box;
        }
        
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .price-calculation {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
            border-left: 4px solid #667eea;
        }
        
        .price-calculation h4 {
            margin: 0 0 10px 0;
            color: #333;
        }
        
        .price-row {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
        }
        
        .total-price {
            font-weight: 600;
            color: #1e3c72;
            font-size: 18px;
            border-top: 2px solid #e1e5e9;
            padding-top: 10px;
            margin-top: 10px;
        }
        
        .book-btn {
            background: linear-gradient(135deg, #11998e, #38ef7d);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(17, 153, 142, 0.3);
            width: 100%;
        }
        
        .book-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(17, 153, 142, 0.4);
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
                <h3>🏐 Book a Futsal</h3>
                <p><?= htmlspecialchars($_SESSION['full_name']) ?></p>
            </div>
            <a href="index.php" class="back-btn">← Back to Search</a>
        </div>
    </div>
    
    <?php if (isset($prepaymentError)): ?>
        <div class="error-message">
            <?= htmlspecialchars($prepaymentError) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($prepaymentPaid): ?>
        <div class="success-message">
            <h4>💳 Prepayment Successful!</h4>
            <p>Your prepayment of Rs. 100 has been processed successfully.</p>
            <p>Your booking is now confirmed and ready to use!</p>
            <div style="text-align: center; margin-top: 20px;">
                <a href="my_bookings.php" class="book-btn" style="display: inline-block; width: auto;">📅 View My Bookings</a>
            </div>
        </div>
    <?php elseif ($bookingSuccess): ?>
        <div class="success-message">
            <h4>🎉 Booking Created!</h4>
            <p>Your futsal booking has been created successfully.</p>
            <p><strong>Booking ID:</strong> <?= htmlspecialchars($bookingId) ?></p>
            <p><strong>Total Amount:</strong> Rs. <?= number_format($totalPrice) ?></p>
            <p><strong>Prepayment Required:</strong> Rs. 100</p>
            <p><strong>Prepayment Status:</strong> ⏳ Pending</p>
            <p style="color: #dc3545; font-weight: 600;">⚠️ Please pay Rs. 100 prepayment to confirm your booking.</p>
            
            <?php if (isset($prepaymentMessage)): ?>
                <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-top: 10px;">
                    <?= htmlspecialchars($prepaymentMessage) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" style="margin-top: 20px;">
                <input type="hidden" name="booking_id" value="<?= htmlspecialchars($bookingId) ?>">
                <input type="hidden" name="pay_prepayment" value="1">
                <button type="submit" class="book-btn">💳 Pay Rs. 100 Prepayment</button>
            </form>
        </div>
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="index.php" class="book-btn" style="display: inline-block; width: auto;">Book Another Futsal</a>
        </div>
        
    <?php else: ?>
        
        <?php if ($bookingError): ?>
            <div class="error-message">
                <?= htmlspecialchars($bookingError) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($selectedFutsal): ?>
            <div class="futsal-info">
                <h3><?= htmlspecialchars($selectedFutsal['name']) ?></h3>
                <p><strong>📍 Location:</strong> <?= htmlspecialchars($selectedFutsal['address']) ?></p>
                <p><strong>💰 Price:</strong> Rs. <?= number_format($selectedFutsal['price']) ?>/hour</p>
                <p><strong>⭐ Rating:</strong> <?= $selectedFutsal['rating'] ?> ★</p>
            </div>
            
            <form method="POST">
                <input type="hidden" name="futsal_name" value="<?= htmlspecialchars($selectedFutsal['name']) ?>">
                
                <div class="form-group">
                    <label for="date">Booking Date:</label>
                    <input type="date" id="date" name="date" required min="<?= date('Y-m-d') ?>">
                </div>
                
                <div class="form-group">
                    <label for="time">Start Time:</label>
                    <select id="time" name="time" required>
                        <option value="">Select a time</option>
                        <?php for ($hour = 6; $hour <= 22; $hour++): ?>
                            <option value="<?= sprintf('%02d:00', $hour) ?>"><?= sprintf('%02d:00', $hour) ?></option>
                            <option value="<?= sprintf('%02d:30', $hour) ?>"><?= sprintf('%02d:30', $hour) ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="duration">Duration (hours):</label>
                    <select id="duration" name="duration" required onchange="updatePrice()">
                        <option value="">Select duration</option>
                        <option value="1">1 hour</option>
                        <option value="1.5">1.5 hours</option>
                        <option value="2">2 hours</option>
                        <option value="2.5">2.5 hours</option>
                        <option value="3">3 hours</option>
                    </select>
                </div>
                
                <div class="price-calculation">
                    <h4>Price Calculation</h4>
                    <div class="price-row">
                        <span>Rate per hour:</span>
                        <span>Rs. <?= number_format($selectedFutsal['price']) ?></span>
                    </div>
                    <div class="price-row">
                        <span>Duration:</span>
                        <span id="durationDisplay">-</span>
                    </div>
                    <div class="price-row total-price">
                        <span>Total Amount:</span>
                        <span id="totalPrice">Rs. 0</span>
                    </div>
                </div>
                
                <button type="submit" class="book-btn">Confirm Booking</button>
            </form>
            
        <?php else: ?>
            <div style="text-align: center; padding: 40px;">
                <h3>No Futsal Selected</h3>
                <p>Please go back and select a futsal to book.</p>
                <a href="index.php" class="book-btn" style="display: inline-block; width: auto; margin-top: 20px;">Search Futsals</a>
            </div>
        <?php endif; ?>
        
    <?php endif; ?>
</div>

<script>
const pricePerHour = <?= $selectedFutsal ? $selectedFutsal['price'] : 0 ?>;

function updatePrice() {
    const duration = document.getElementById('duration').value;
    const durationDisplay = document.getElementById('durationDisplay');
    const totalPriceElement = document.getElementById('totalPrice');
    
    if (duration && pricePerHour > 0) {
        const total = pricePerHour * parseFloat(duration);
        durationDisplay.textContent = duration + ' hour(s)';
        totalPriceElement.textContent = 'Rs. ' + total.toLocaleString();
    } else {
        durationDisplay.textContent = '-';
        totalPriceElement.textContent = 'Rs. 0';
    }
}

// Set minimum date to today
document.getElementById('date').min = new Date().toISOString().split('T')[0];
</script>

</body>
</html>
