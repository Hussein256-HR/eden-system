<?php
// Enable detailed error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';
require_once 'includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to dashboard if already logged in
if (is_logged_in()) {
    header('Location: admin/dashboard.php');
    exit();
}

$error_message = '';
$username = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Only trim the username (no HTML encoding)
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    // Validate CSRF token
    if (!verify_csrf_token($csrf_token)) {
        $error_message = 'Invalid security token. Please refresh the page and try again.';
    } 
    // Check for empty fields
    elseif (empty($username) || empty($password)) {
        $error_message = 'Please enter both username and password.';
    } 
    // Try to authenticate user
    else {
        try {
            // Get database connection
            $db = (new Database())->getConnection();
            if (!$db) {
                throw new Exception("Database connection failed.");
            }

            // Find active user by username
            $stmt = $db->prepare("SELECT id, username, password, full_name, email, role 
                                  FROM users 
                                  WHERE username = ? AND is_active = 1 
                                  LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            // Check if user exists and password matches
            if ($user && password_verify($password, $user['password'])) {
                // Security: Regenerate session ID
                session_regenerate_id(true);
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['login_time'] = time();

                // Log this login activity
                log_activity('user_login', 'User logged in: ' . $user['username'], $user['id']);

                // Redirect to dashboard
                header('Location: admin/dashboard.php');
                exit();
            } else {
                $error_message = 'Invalid username or password.';
            }
        } catch (Exception $e) {
            // Log detailed error
            error_log("Login error: " . $e->getMessage());
            $error_message = 'An internal error occurred. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Eden Miracle Church Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0f3460;
            --secondary-color: #e94560;
            --accent-color: #f8a44c;
        }

        body {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
        }

        .login-header {
            background: var(--primary-color);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .login-header h2 {
            margin: 0;
            font-weight: 600;
        }

        .login-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
        }

        .login-body {
            padding: 2rem;
        }

        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(15, 52, 96, 0.25);
        }

        .btn-login {
            background: var(--primary-color);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s;
            width: 100%;
        }

        .btn-login:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }

        .input-group-text {
            background: transparent;
            border: 2px solid #e9ecef;
            border-right: none;
            border-radius: 10px 0 0 10px;
        }

        .input-group .form-control {
            border-left: none;
            border-radius: 0 10px 10px 0;
        }

        .alert {
            border-radius: 10px;
            border: none;
        }

        .church-logo {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
        }

        .forgot-password {
            text-align: center;
            margin-top: 1rem;
        }

        .forgot-password a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9rem;
        }

        .forgot-password a:hover {
            color: var(--secondary-color);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="church-logo">
                <i class="bi bi-building"></i>
            </div>
            <h2>Eden Miracle Church</h2>
            <p>Administration Portal</p>
        </div>
        
        <div class="login-body">
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <?= htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <!-- CSRF protection -->
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token(); ?>">

                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control" id="username" name="username" required
                               value="<?= htmlspecialchars($username); ?>">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                            <i class="bi bi-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember">
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>

                <button type="submit" class="btn btn-primary btn-login">
                    <i class="bi bi-box-arrow-in-right me-2"></i> Sign In
                </button>
            </form>

            <div class="forgot-password">
                <a href="#" onclick="alert('Please contact the system administrator to reset your password.')">
                    Forgot your password?
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function () {
            const password = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            const isHidden = password.type === 'password';

            password.type = isHidden ? 'text' : 'password';
            toggleIcon.className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
        });

        // Focus on username field
        document.getElementById('username').focus();

        // Remember me functionality
        document.getElementById('remember').addEventListener('change', function () {
            const usernameField = document.getElementById('username');
            if (this.checked) {
                localStorage.setItem('remember_username', usernameField.value);
            } else {
                localStorage.removeItem('remember_username');
            }
        });

        // Load remembered username
        window.addEventListener('load', function () {
            const remembered = localStorage.getItem('remember_username');
            if (remembered) {
                document.getElementById('username').value = remembered;
                document.getElementById('remember').checked = true;
                document.getElementById('password').focus();
            }
        });
    </script>
</body>
</html>