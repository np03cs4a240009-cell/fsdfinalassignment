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
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $role = $_POST['role'] ?? 'user';
        
        // Validate inputs
        if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
            $error = "All fields are required.";
        } elseif (strlen($username) < 3) {
            $error = "Username must be at least 3 characters long.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        } elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters long.";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } elseif (!in_array($role, ['admin', 'user'])) {
            $error = "Invalid role selected.";
        } else {
            try {
                // Check if username already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    $error = "Username already taken. Please choose another.";
                } else {
                    // Check if email already exists
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    if ($stmt->fetch()) {
                        $error = "Email already registered. Please use another email or login.";
                    } else {
                        // Hash the password
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        
                        // Insert new user
                        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, is_active) VALUES (?, ?, ?, ?, 1)");
                        $stmt->execute([$username, $email, $hashed_password, $role]);
                        
                        $success = "Registration successful! You can now login.";
                        
                        // Optional: Auto-login after registration
                        // Uncomment the following lines to auto-login
                        /*
                        $_SESSION['user_id'] = $pdo->lastInsertId();
                        $_SESSION['username'] = $username;
                        $_SESSION['role'] = $role;
                        $_SESSION['last_activity'] = time();
                        
                        header("Location: " . ($role === 'admin' ? 'admin_dashboard.php' : 'user_dashboard.php'));
                        exit;
                        */
                    }
                }
            } catch (PDOException $e) {
                error_log("Registration error: " . $e->getMessage());
                $error = "An error occurred during registration. Please try again.";
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
    <title>Register - Event Management System</title>
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
        
        .register-wrapper {
            width: 100%;
            max-width: 480px;
        }
        
        .register-container {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        
        .register-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        
        .register-header h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .register-header p {
            font-size: 14px;
            opacity: 0.95;
        }
        
        .register-body {
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
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #2a5298;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(42, 82, 152, 0.1);
        }
        
        .role-selection {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 8px;
        }
        
        .role-option {
            position: relative;
        }
        
        .role-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }
        
        .role-option label {
            display: block;
            padding: 16px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        
        .role-option input[type="radio"]:checked + label {
            border-color: #2a5298;
            background: #e8f2ff;
            color: #1e3c72;
            font-weight: 600;
        }
        
        .role-option label:hover {
            border-color: #2a5298;
        }
        
        .role-icon {
            font-size: 24px;
            display: block;
            margin-bottom: 8px;
        }
        
        .btn-register {
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
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(42, 82, 152, 0.4);
        }
        
        .btn-register:active {
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
            color: #2d662d;
            border-left: 4px solid #3c3;
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
        
        .password-requirements {
            font-size: 12px;
            color: #666;
            margin-top: 6px;
            line-height: 1.4;
        }
        
        /* Responsive design */
        @media (max-width: 480px) {
            .register-header {
                padding: 30px 20px;
            }
            
            .register-header h1 {
                font-size: 24px;
            }
            
            .register-body {
                padding: 30px 20px;
            }
            
            .role-selection {
                grid-template-columns: 1fr;
            }
        }
        
        .loading {
            pointer-events: none;
            opacity: 0.6;
        }
    </style>
</head>
<body>
    <div class="register-wrapper">
        <div class="register-container">
            <div class="register-header">
                <h1>🎉 Create Account</h1>
                <p>Join our Event Management System</p>
            </div>
            
            <div class="register-body">
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <strong>⚠️ Error:</strong> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <strong>✓ Success:</strong> <?= htmlspecialchars($success) ?>
                        <br><br>
                        <a href="login.php" style="color: #2a5298; font-weight: 600;">Click here to login</a>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="registerForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    
                    <div class="form-group">
                        <label for="username">Username *</label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            placeholder="Choose a username"
                            value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
                            required 
                            autofocus
                            minlength="3"
                            autocomplete="username"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            placeholder="your.email@example.com"
                            value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                            required
                            autocomplete="email"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            placeholder="Create a strong password"
                            required
                            minlength="6"
                            autocomplete="new-password"
                        >
                        <div class="password-requirements">
                            Must be at least 6 characters long
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            placeholder="Re-enter your password"
                            required
                            minlength="6"
                            autocomplete="new-password"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label>Account Type *</label>
                        <div class="role-selection">
                            <div class="role-option">
                                <input 
                                    type="radio" 
                                    id="role_user" 
                                    name="role" 
                                    value="user" 
                                    <?= (!isset($_POST['role']) || $_POST['role'] === 'user') ? 'checked' : '' ?>
                                >
                                <label for="role_user">
                                    <span class="role-icon">👤</span>
                                    <div>User</div>
                                    <small style="font-size: 11px; color: #666;">Book events</small>
                                </label>
                            </div>
                            <div class="role-option">
                                <input 
                                    type="radio" 
                                    id="role_admin" 
                                    name="role" 
                                    value="admin"
                                    <?= (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'checked' : '' ?>
                                >
                                <label for="role_admin">
                                    <span class="role-icon">👨‍💼</span>
                                    <div>Admin</div>
                                    <small style="font-size: 11px; color: #666;">Manage events</small>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-register">Create Account</button>
                </form>
                
                <div class="footer-links">
                    Already have an account? <a href="login.php">Sign In</a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.classList.add('loading');
            submitBtn.textContent = 'Creating Account...';
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
        
        // Password strength indicator (optional enhancement)
        const passwordInput = document.getElementById('password');
        passwordInput.addEventListener('input', function() {
            // You can add password strength indicator here if desired
        });
    </script>
</body>
</html>