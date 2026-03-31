<?php
/**
 * Nouriq — Login Page
 */
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/dashboard/');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $login = sanitize($_POST['login'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        if (empty($login) || empty($password)) {
            $error = 'Please fill in all fields.';
        } else {
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM users WHERE (email = ? OR username = ?) AND is_active = 1");
            $stmt->execute([$login, $login]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Login success
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                
                // Remember Me
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    $stmt = $db->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                    $stmt->execute([$token, $user['id']]);
                    setcookie('nouriq_remember', $token, time() + REMEMBER_ME_LIFETIME, '/', '', false, true);
                }
                
                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header('Location: ' . APP_URL . '/admin/');
                } else {
                    header('Location: ' . APP_URL . '/dashboard/');
                }
                exit;
            } else {
                $error = 'Invalid email/username or password.';
            }
        }
    }
}

// Check for registration success message
if (isset($_GET['registered'])) {
    $success = 'Account created successfully! Please log in.';
}
if (isset($_GET['reset'])) {
    $success = 'Password reset successfully! Please log in with your new password.';
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sign in to Nouriq — Your Intelligent Nutrition Coach">
    <title>Sign In — Nouriq</title>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>main.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>auth.css">
</head>
<body>
    <div class="auth-page">
        <!-- Animated Background -->
        <div class="auth-bg">
            <div class="orb orb-1"></div>
            <div class="orb orb-2"></div>
            <div class="orb orb-3"></div>
        </div>

        <div class="auth-container">
            <div class="auth-card">
                <!-- Header -->
                <div class="auth-header">
                    <div class="auth-logo">
                        <div class="auth-logo-icon">🧬</div>
                        <span class="auth-logo-text">Nouriq</span>
                    </div>
                    <h2>Welcome back</h2>
                    <p>Sign in to your nutrition dashboard</p>
                </div>

                <!-- Alerts -->
                <?php if ($error): ?>
                <div class="auth-alert error">
                    <span>⚠️</span> <?php echo sanitize($error); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="auth-alert success">
                    <span>✅</span> <?php echo sanitize($success); ?>
                </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form class="auth-form" method="POST" action="" id="loginForm">
                    <?php echo csrfField(); ?>
                    
                    <div class="form-group">
                        <label class="form-label" for="login">Email or Username</label>
                        <div class="input-icon-wrapper">
                            <span class="input-icon">👤</span>
                            <input type="text" id="login" name="login" class="form-input" 
                                   placeholder="Enter your email or username"
                                   value="<?php echo sanitize($_POST['login'] ?? ''); ?>"
                                   autocomplete="username" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <div class="input-icon-wrapper">
                            <span class="input-icon">🔒</span>
                            <input type="password" id="password" name="password" class="form-input" 
                                   placeholder="Enter your password"
                                   autocomplete="current-password" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('password', this)">👁️</button>
                        </div>
                    </div>

                    <div class="auth-extras">
                        <label class="remember-me">
                            <input type="checkbox" name="remember" id="remember">
                            <span>Remember me</span>
                        </label>
                        <a href="forgot-password.php" class="forgot-link">Forgot password?</a>
                    </div>

                    <button type="submit" class="btn btn-primary auth-submit" id="loginBtn">
                        Sign In
                    </button>
                </form>

                <div class="auth-footer">
                    Don't have an account? <a href="register.php">Create one</a>
                </div>
            </div>
        </div>
    </div>

    <script>
    function togglePassword(fieldId, btn) {
        const field = document.getElementById(fieldId);
        if (field.type === 'password') {
            field.type = 'text';
            btn.textContent = '🔓';
        } else {
            field.type = 'password';
            btn.textContent = '👁️';
        }
    }

    // Form submission loading state
    document.getElementById('loginForm').addEventListener('submit', function() {
        const btn = document.getElementById('loginBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner spinner-sm"></span> Signing in...';
    });
    </script>
</body>
</html>
