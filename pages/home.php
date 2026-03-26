<?php
session_start();

// Home page is accessible to both logged-in and non-logged-in users
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Futsal Recommendation System - Find Your Perfect Court</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="../assets/style.css">
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

        /* Navigation Bar */
        nav {
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .nav-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #667eea;
            font-weight: 700;
            font-size: 18px;
        }

        .nav-logo span {
            font-size: 24px;
        }

        .nav-buttons {
            display: flex;
            gap: 15px;
        }

        .nav-btn {
            padding: 10px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-block;
        }

        .nav-btn.login {
            background-color: transparent;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .nav-btn.login:hover {
            background-color: #667eea;
            color: white;
        }

        .nav-btn.register {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .nav-btn.register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        /* Hero Section */
        .hero {
            padding: 80px 20px;
            text-align: center;
            color: white;
        }

        .hero-content {
            max-width: 800px;
            margin: 0 auto;
        }

        .hero h1 {
            font-size: 48px;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .hero p {
            font-size: 18px;
            margin-bottom: 30px;
            opacity: 0.95;
            line-height: 1.6;
        }

        .hero-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .hero-btn {
            padding: 14px 40px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .hero-btn.primary {
            background: white;
            color: #667eea;
        }

        .hero-btn.primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .hero-btn.secondary {
            background: transparent;
            color: white;
            border: 2px solid white;
        }

        .hero-btn.secondary:hover {
            background: white;
            color: #667eea;
        }

        /* Content Section */
        .content-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 60px 20px;
        }

        .section-title {
            font-size: 36px;
            color: white;
            text-align: center;
            margin-bottom: 50px;
            font-weight: 700;
        }

        /* Features Grid */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 80px;
        }

        .feature-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            text-align: center;
            transition: all 0.3s;
            border-left: 5px solid #667eea;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
        }

        .feature-icon {
            font-size: 48px;
            margin-bottom: 20px;
        }

        .feature-card h3 {
            font-size: 22px;
            color: #667eea;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .feature-card p {
            color: #666;
            font-size: 15px;
            line-height: 1.6;
        }

        /* Stats Section */
        .stats-section {
            background: white;
            padding: 60px 20px;
            border-radius: 15px;
            margin: 60px 0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            text-align: center;
        }

        .stat-item {
            padding: 20px;
        }

        .stat-number {
            font-size: 48px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 16px;
            color: #666;
            font-weight: 600;
        }

        /* Process Section */
        .process-section {
            margin: 80px 0;
        }

        .process-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }

        .process-step {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            text-align: center;
            border-top: 4px solid #667eea;
        }

        .step-number {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: bold;
            margin: 0 auto 20px;
        }

        .process-step h4 {
            font-size: 20px;
            color: #667eea;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .process-step p {
            color: #666;
            font-size: 15px;
            line-height: 1.6;
        }

        /* Info Boxes */
        .info-section {
            margin: 80px 0;
        }

        .info-box {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            border-left: 5px solid #667eea;
            transition: all 0.3s;
        }

        .info-box:hover {
            transform: translateX(5px);
        }

        .info-box h4 {
            color: #667eea;
            margin-bottom: 12px;
            font-size: 20px;
            font-weight: 700;
        }

        .info-box p {
            color: #666;
            font-size: 15px;
            line-height: 1.6;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
            margin: 60px 0;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 14px 40px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: white;
            color: #667eea;
            padding: 14px 40px;
            border: 2px solid #667eea;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-secondary:hover {
            background: #667eea;
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        /* Footer */
        footer {
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 30px 20px;
            text-align: center;
            border-top: 2px solid white;
            margin-top: 80px;
        }

        footer a {
            color: #667eea;
            text-decoration: none;
            transition: color 0.3s;
        }

        footer a:hover {
            color: #764ba2;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .nav-buttons {
                flex-direction: column;
                gap: 10px;
            }

            .hero h1 {
                font-size: 32px;
            }

            .hero p {
                font-size: 16px;
            }

            .hero-buttons {
                flex-direction: column;
            }

            .hero-btn {
                width: 100%;
            }

            .section-title {
                font-size: 28px;
            }

            .features-grid,
            .process-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn-primary,
            .btn-secondary {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav>
        <div class="nav-container">
            <a href="home.php" class="nav-logo">
                <span>⚽</span>
                Futsal Recommendation System
            </a>
            <div class="nav-buttons">
                <a href="/login" class="nav-btn login">Login</a>
                <a href="/register" class="nav-btn register">Register</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Find & Book Your Perfect Futsal Court</h1>
            <p>Discover the best futsal courts near you with intelligent recommendations based on location, ratings, and prices.</p>
            <div class="hero-buttons">
                <a href="/login" class="hero-btn primary">Get Started</a>
                <a href="#features" class="hero-btn secondary">Learn More</a>
            </div>
        </div>
    </section>

    <!-- Content Section -->
    <div class="content-container">
        <!-- Features Section -->
        <section id="features">
            <h2 class="section-title">Why Choose Us?</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">📍</div>
                    <h3>Smart Location Matching</h3>
                    <p>Get personalized recommendations based on your exact location using advanced GPS matching.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">⭐</div>
                    <h3>Quality Assurance</h3>
                    <p>Only high-rated futsal courts with excellent facilities and outstanding service.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">💰</div>
                    <h3>Best Prices</h3>
                    <p>Find affordable futsal courts that fit your budget without compromising quality.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🗺️</div>
                    <h3>Interactive Maps</h3>
                    <p>View court locations on an interactive map with distance and directions.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">📅</div>
                    <h3>Easy Booking</h3>
                    <p>Book your preferred court in seconds with instant confirmation and details.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🎯</div>
                    <h3>Real-Time Availability</h3>
                    <p>Check live availability and avoid double-bookings with our advanced system.</p>
                </div>
            </div>
        </section>

        <!-- Stats Section -->
        <section class="stats-section">
            <h2 class="section-title" style="color: #667eea;">Platform Statistics</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number">31+</div>
                    <div class="stat-label">Futsal Courts</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">100+</div>
                    <div class="stat-label">Active Users</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">500+</div>
                    <div class="stat-label">Bookings Made</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">4.5★</div>
                    <div class="stat-label">Average Rating</div>
                </div>
            </div>
        </section>

        <!-- How It Works Section -->
        <section>
            <h2 class="section-title">How It Works</h2>
            <div class="process-grid">
                <div class="process-step">
                    <div class="step-number">1</div>
                    <h4>Register/Login</h4>
                    <p>Create your account or login to access the system.</p>
                </div>

                <div class="process-step">
                    <div class="step-number">2</div>
                    <h4>Enter Location</h4>
                    <p>Share your location or select your preferred area.</p>
                </div>

                <div class="process-step">
                    <div class="step-number">3</div>
                    <h4>Get Recommendations</h4>
                    <p>Receive personalized recommendations for nearby courts.</p>
                </div>

                <div class="process-step">
                    <div class="step-number">4</div>
                    <h4>Book & Play</h4>
                    <p>Select your court, date, and time to complete booking.</p>
                </div>
            </div>
        </section>

        <!-- Info Section -->
        <section class="info-section">
            <div class="info-box">
                <h4>🏙️ Wide Network Coverage</h4>
                <p>We cover 31 premium futsal courts across Kathmandu Valley including Hadigaun, Patan, Boudha, Naxal, and more.</p>
            </div>

            <div class="info-box">
                <h4>💎 Premium Quality Courts</h4>
                <p>All partner courts are carefully selected and verified for quality facilities, experienced staff, and excellent service standards.</p>
            </div>

            <div class="info-box">
                <h4>🎯 Smart Algorithms</h4>
                <p>Our AI-powered recommendation engine uses advanced algorithms to match you with the perfect futsal court.</p>
            </div>
        </section>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="/login" class="btn-primary">Login to Your Account</a>
            <a href="/register" class="btn-secondary">Create New Account</a>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <p>&copy; 2026 Futsal Recommendation System. All rights reserved. | <a href="home.php">Home</a></p>
    </footer>
</body>
</html>
