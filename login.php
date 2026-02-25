<?php
session_start();

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Simple authentication (in production, use hashed passwords)
    $users = include 'users_data.php';
    
    $userFound = false;
    foreach ($users as $user) {
        if ($user['username'] === $username && $user['password'] === $password) {
            $userFound = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            break;
        }
    }
    
    if ($userFound) {
        header('Location: index.php');
        exit();
    } else {
        $error = 'Invalid username or password';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Futsal Recommendation System</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        .login-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: #497b92ff;
            margin-bottom: 12px;
        }
        
        .login-header p {
            color: #666;
            margin: 0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            border-color: #1976d2;
            outline: none;
        }
        
        .login-btn {
            width: 100%;
            background-color: #1976d2;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .login-btn:hover {
            background-color: #1565c0;
        }
        
        .register-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .register-link a {
            color: #1976d2;
            text-decoration: none;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
        
        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #c62828;
        }
        
        .success-message {
            background-color: #e8f5e8;
            color: #2e7d32;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #2e7d32;
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-header">
        <h1>🏐 Futsal Recommendation System</h1>
        <p>Login to your account</p>
    </div>
    
    <?php if (isset($error)): ?>
        <div class="error-message">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['registered']) && $_GET['registered'] == 'success'): ?>
        <div class="success-message">
            Registration successful! Please login with your credentials.
        </div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required 
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        </div>
        
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <button type="submit" class="login-btn">Login</button>
    </form>
    
    <div class="register-link">
        <p>Don't have an account? <a href="register.php">Register</a></p>
    </div>
</div>

</body>
</html>
