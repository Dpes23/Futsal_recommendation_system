<?php
session_start();

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: /index');
    exit();
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $login_type = $_POST['login_type'] ?? 'user'; // Determine if it's admin or user login
    
    // Use MySQL database for authentication
    require_once __DIR__ . '/../config/database.php';
    $pdo = getDatabaseConnection();
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['is_admin'] = $user['is_admin'] ?? 0;
            
            // Redirect based on login type
            if ($login_type === 'admin') {
                // For admin login, redirect to admin panel
                header('Location: /admin_bookings');
            } else {
                // For user login, redirect to dashboard
                header('Location: /index');
            }
            exit();
        } else {
            $error = 'Invalid username or password';
        }
    } catch(PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Futsal Recommendation System</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
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
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .auth-container {
            width: 100%;
            max-width: 500px;
            padding: 20px;
        }

        .auth-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .auth-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 30px;
            text-align: center;
            color: white;
        }

        .auth-header .logo {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .auth-header h1 {
            font-size: 28px;
            margin-bottom: 8px;
            font-weight: 700;
        }

        .auth-header p {
            font-size: 14px;
            opacity: 0.9;
        }

        .auth-form {
            padding: 40px 30px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
            background-color: #f8f9fa;
        }

        .form-group input:focus {
            border-color: #667eea;
            background-color: white;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group input::placeholder {
            color: #999;
        }

        .login-btn {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .error-message {
            background-color: #fee;
            color: #c33;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #c33;
            font-size: 14px;
        }

        .success-message {
            background-color: #efe;
            color: #3c3;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #3c3;
            font-size: 14px;
        }

        .auth-links {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }

        .auth-link {
            text-decoration: none;
            color: #667eea;
            font-size: 14px;
            font-weight: 600;
            transition: color 0.3s;
        }

        .auth-link:hover {
            color: #764ba2;
        }

        .login-tabs {
            display: flex;
            gap: 0;
            margin-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }

        .login-tab {
            flex: 1;
            padding: 12px;
            text-align: center;
            cursor: pointer;
            font-weight: 600;
            color: #999;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            background-color: #f8f9fa;
        }

        .login-tab.active {
            color: #667eea;
            border-bottom-color: #667eea;
            background-color: white;
        }

        .login-tab:hover {
            color: #667eea;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .admin-hint {
            background-color: #fff3cd;
            color: #856404;
            padding: 10px 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 13px;
            border-left: 4px solid #ffc107;
        }

        @media (max-width: 600px) {
            .auth-header {
                padding: 30px 20px;
            }

            .auth-header h1 {
                font-size: 24px;
            }

            .auth-form {
                padding: 30px 20px;
            }

            .auth-container {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="logo">⚽</div>
                <h1>Futsal Recommendation System</h1>
                <p>Login to your account</p>
            </div>

            <div class="login-tabs">
                <div class="login-tab active" onclick="switchTab('user')">👤 User Login</div>
                <div class="login-tab" onclick="switchTab('admin')">🔐 Admin Login</div>
            </div>

            <!-- User Login Tab -->
            <div id="user-tab" class="tab-content active">
                <form class="auth-form" method="POST" action="">
                    <input type="hidden" name="login_type" value="user">
                    
                    <?php if (isset($error)): ?>
                        <div class="error-message">
                            <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="username">Username</label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            placeholder="Enter your username"
                            value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            placeholder="Enter your password"
                            required
                        >
                    </div>

                    <button type="submit" class="login-btn">Login</button>
                </form>
            </div>

            <!-- Admin Login Tab -->
            <div id="admin-tab" class="tab-content">
                <div class="auth-form">
                    <div class="admin-hint">
                        <strong>🔐 Admin Access Only</strong><br>
                        Use admin credentials to access the admin panel
                    </div>

                    <form method="POST" action="">
                        <input type="hidden" name="login_type" value="admin">
                        
                        <?php if (isset($error) && isset($_POST['login_type']) && $_POST['login_type'] === 'admin'): ?>
                            <div class="error-message">
                                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="admin-username">Admin Username</label>
                            <input 
                                type="text" 
                                id="admin-username" 
                                name="username" 
                                placeholder="admin"
                                value="<?php echo isset($_POST['username']) && isset($_POST['login_type']) && $_POST['login_type'] === 'admin' ? htmlspecialchars($_POST['username']) : ''; ?>"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="admin-password">Admin Password</label>
                            <input 
                                type="password" 
                                id="admin-password" 
                                name="password" 
                                placeholder="Enter admin password"
                                required
                            >
                        </div>

                        <button type="submit" class="login-btn">Admin Login</button>
                    </form>
                </div>
            </div>

            <div class="auth-links" style="padding: 0 30px 30px 30px;">
                <a href="/register" class="auth-link">Create Account</a>
                <a href="/" class="auth-link">Back to Home</a>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            // Hide all tabs
            document.getElementById('user-tab').classList.remove('active');
            document.getElementById('admin-tab').classList.remove('active');
            
            // Remove active class from all tab buttons
            document.querySelectorAll('.login-tab').forEach(t => t.classList.remove('active'));
            
            // Show selected tab
            document.getElementById(tab + '-tab').classList.add('active');
            
            // Add active class to clicked tab button
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
