<?php
session_start();

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    $errors = [];
    
    if (empty($full_name)) {
        $errors[] = 'Full name is required';
    }
    
    if (empty($username)) {
        $errors[] = 'Username is required';
    } elseif (strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }
    
    // Check if username already exists
    $users = include 'users_data.php';
    foreach ($users as $user) {
        if ($user['username'] === $username) {
            $errors[] = 'Username already exists';
            break;
        }
    }
    
    if (empty($errors)) {
        // Add new user (in production, save to database with hashed password)
        $new_user = [
            'id' => count($users) + 1,
            'full_name' => $full_name,
            'username' => $username,
            'email' => $email,
            'password' => $password, // In production, use password_hash()
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $users[] = $new_user;
        
        // Save to file (in production, use database)
        file_put_contents('users_data.php', '<?php return ' . var_export($users, true) . ';');
        
        header('Location: login.php?registered=success');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Futsal Recommendation System</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        .register-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .register-header h1 {
            color: #1b5e20;
            margin-bottom: 10px;
        }
        
        .register-header p {
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
        
        .register-btn {
            width: 100%;
            background-color: #2e7d32;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .register-btn:hover {
            background-color: #1b5e20;
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .login-link a {
            color: #1976d2;
            text-decoration: none;
        }
        
        .login-link a:hover {
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
        
        .error-list {
            margin: 0;
            padding-left: 20px;
        }
        
        .error-list li {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>

<div class="register-container">
    <div class="register-header">
        <h1>🏐 Futsal Finder</h1>
        <p>Create your account</p>
    </div>
    
    <?php if (!empty($errors)): ?>
        <div class="error-message">
            <ul class="error-list">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label for="full_name">Full Name:</label>
            <input type="text" id="full_name" name="full_name" required 
                   value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
        </div>
        
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required 
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        </div>
        
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required 
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <div class="form-group">
            <label for="confirm_password">Confirm Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
        </div>
        
        <button type="submit" class="register-btn">Register</button>
    </form>
    
    <div class="login-link">
        <p>Already have an account? <a href="login.php">Login</a></p>
    </div>
</div>

</body>
</html>
