<?php


session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: " . ($_SESSION['role'] === 'admin' ? 'admin_dashboard.php' : 'user_dashboard.php'));
    exit;
}

require '../config/db.php';

$error = '';
$success = '';

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid security token. Please try again.";
    } else {
        // Input validation
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error = "Please enter both username and password.";
        } else {
            try {
                // Fetch user from database
                $stmt = $pdo->prepare("SELECT id, username, password, role, is_active FROM users WHERE username = ? LIMIT 1");
                $stmt->execute([$username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    // Check if password is hashed (bcrypt starts with $2y$ or $2a$)
                    $password_valid = false;
                    
                    if (substr($user['password'], 0, 4) === '$2y$' || substr($user['password'], 0, 4) === '$2a$') {
                        // Password is hashed, use password_verify
                        $password_valid = password_verify($password, $user['password']);
                    } else {
                        // Legacy plain text password (for backward compatibility)
                        $password_valid = ($password === $user['password']);
                        
                        // Automatically upgrade to hashed password on successful login
                        if ($password_valid) {
                            try {
                                $hashed = password_hash($password, PASSWORD_DEFAULT);
                                $update_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                                $update_stmt->execute([$hashed, $user['id']]);
                            } catch (PDOException $e) {
                                // Log but don't fail login if hash update fails
                                error_log("Password hash upgrade failed: " . $e->getMessage());
                            }
                        }
                    }
                    
                    if ($password_valid) {
                    // Check if account is active
                    if (isset($user['is_active']) && $user['is_active'] == 0) {
                        $error = "Your account has been deactivated. Please contact support.";
                    } else {
                        // Regenerate session ID to prevent session fixation
                        session_regenerate_id(true);
                        
                        // Set session variables
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['last_activity'] = time();
                        
                        // Log successful login (optional - add to your database)
                        // $stmt = $pdo->prepare("INSERT INTO login_logs (user_id, ip_address, login_time) VALUES (?, ?, NOW())");
                        // $stmt->execute([$user['id'], $_SERVER['REMOTE_ADDR']]);
                        
                        // Redirect based on role
                        $redirect = ($user['role'] === 'admin') ? 'admin_dashboard.php' : 'user_dashboard.php';
                        header("Location: " . $redirect);
                        exit;
                    }
                    } else {
                        // Generic error message to prevent user enumeration
                        $error = "Invalid username or password.";
                        
                        // Optional: Log failed login attempt
                        // error_log("Failed login attempt for username: " . $username . " from IP: " . $_SERVER['REMOTE_ADDR']);
                    }
                } else {
                    // Generic error message to prevent user enumeration
                    $error = "Invalid username or password.";
                }
            } catch (PDOException $e) {
                error_log("Login error: " . $e->getMessage());
                $error = "An error occurred. Please try again later.".$e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Event Management System</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-wrapper {
            width: 100%;
            max-width: 440px;
        }
        
        .login-container {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        
        .login-header h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .login-header p {
            font-size: 14px;
            opacity: 0.95;
        }
        
        .login-body {
            padding: 40px 30px;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #2a5298;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(42, 82, 152, 0.1);
        }
        
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(42, 82, 152, 0.4);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .alert {
            padding: 14px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .alert-error {
            background: #fee;
            color: #c33;
            border-left: 4px solid #c33;
        }
        
        .alert-success {
            background: #efe;
            color: #3c3;
            border-left: 4px solid #3c3;
        }
        
        .footer-links {
            text-align: center;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #e1e8ed;
        }
        
        .footer-links {
            text-align: center;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #e1e8ed;
            font-size: 14px;
            color: #666;
        }
        
        .footer-links a {
            color: #2a5298;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        
        .footer-links a:hover {
            color: #1e3c72;
            text-decoration: underline;
        }
        
        /* Responsive design */
        @media (max-width: 480px) {
            .login-header {
                padding: 30px 20px;
            }
            
            .login-header h1 {
                font-size: 24px;
            }
            
            .login-body {
                padding: 30px 20px;
            }
        }
        
        /* Password toggle icon placeholder */
        .password-wrapper {
            position: relative;
        }
        
        .loading {
            pointer-events: none;
            opacity: 0.6;
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-container">
            <div class="login-header">
                <h1>🎉 Event Management</h1>
                <p>Sign in to manage your events</p>
            </div>
            
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <strong>⚠️ Error:</strong> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <strong>✓ Success:</strong> <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="loginForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            placeholder="Enter your username"
                            value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
                            required 
                            autofocus
                            autocomplete="username"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-wrapper">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                placeholder="Enter your password"
                                required
                                autocomplete="current-password"
                            >
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-login">Sign In</button>
                </form>
                
                <div class="footer-links">
                    <a href="forgot_password.php">Forgot Password?</a>
                    <span style="margin: 0 8px;">|</span>
                    Don't have an account? <a href="register.php">Sign Up</a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Optional: Form validation and UX improvements
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.classList.add('loading');
            submitBtn.textContent = 'Signing in...';
        });
        
        // Clear error messages on input
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                const alert = document.querySelector('.alert-error');
                if (alert) {
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                }
            });
        });
    </script>
</body>
</html>
