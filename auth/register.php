<?php
/**
 * Nouriq — Registration Page
 */
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();

if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/dashboard/');
    exit;
}

$error = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $username = sanitize($_POST['username'] ?? '');
        $email = sanitizeEmail($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $firstName = sanitize($_POST['first_name'] ?? '');
        $lastName = sanitize($_POST['last_name'] ?? '');
        
        // Validation
        if (empty($username) || strlen($username) < 3) {
            $errors[] = 'Username must be at least 3 characters.';
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = 'Username can only contain letters, numbers, and underscores.';
        }
        if (!validateEmail($email)) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        }
        
        if (empty($errors)) {
            $db = getDB();
            
            // Check duplicate
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
            $stmt->execute([$email, $username]);
            if ($stmt->fetch()) {
                $errors[] = 'Email or username already exists.';
            }
        }
        
        if (empty($errors)) {
            $db = getDB();
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
            
            try {
                $db->beginTransaction();
                
                $stmt = $db->prepare("INSERT INTO users (email, username, password_hash) VALUES (?, ?, ?)");
                $stmt->execute([$email, $username, $hash]);
                $userId = $db->lastInsertId();
                
                // Create profile
                $stmt = $db->prepare("INSERT INTO profiles (user_id, first_name, last_name) VALUES (?, ?, ?)");
                $stmt->execute([$userId, $firstName, $lastName]);
                
                // Create points record
                $stmt = $db->prepare("INSERT INTO user_points (user_id, total_points, level) VALUES (?, 0, 1)");
                $stmt->execute([$userId]);
                
                // Welcome notification
                createNotification($userId, 'health_tip', 'Welcome to Nouriq! 🎉', 'Start by setting up your profile and logging your first meal. Your nutrition journey begins now!', '🧬');
                
                $db->commit();
                
                header('Location: login.php?registered=1');
                exit;
            } catch (Exception $e) {
                $db->rollBack();
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Create your Nouriq account — Start your smart nutrition journey">
    <title>Create Account — Nouriq</title>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>main.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>auth.css">
</head>
<body>
    <div class="auth-page">
        <div class="auth-bg">
            <div class="orb orb-1"></div>
            <div class="orb orb-2"></div>
            <div class="orb orb-3"></div>
        </div>

        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <div class="auth-logo">
                        <div class="auth-logo-icon">🧬</div>
                        <span class="auth-logo-text">Nouriq</span>
                    </div>
                    <h2>Create your account</h2>
                    <p>Start your intelligent nutrition journey</p>
                </div>

                <?php if ($error): ?>
                <div class="auth-alert error"><span>⚠️</span> <?php echo sanitize($error); ?></div>
                <?php endif; ?>
                
                <?php if (!empty($errors)): ?>
                <div class="auth-alert error">
                    <span>⚠️</span>
                    <div><?php echo implode('<br>', array_map('sanitize', $errors)); ?></div>
                </div>
                <?php endif; ?>

                <form class="auth-form" method="POST" action="" id="registerForm">
                    <?php echo csrfField(); ?>
                    
                    <div class="flex gap-md">
                        <div class="form-group" style="flex:1">
                            <label class="form-label" for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" class="form-input" 
                                   placeholder="John" value="<?php echo sanitize($_POST['first_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group" style="flex:1">
                            <label class="form-label" for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" class="form-input" 
                                   placeholder="Doe" value="<?php echo sanitize($_POST['last_name'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="username">Username *</label>
                        <div class="input-icon-wrapper">
                            <span class="input-icon">@</span>
                            <input type="text" id="username" name="username" class="form-input" 
                                   placeholder="Choose a username" required minlength="3"
                                   value="<?php echo sanitize($_POST['username'] ?? ''); ?>"
                                   autocomplete="username">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">Email Address *</label>
                        <div class="input-icon-wrapper">
                            <span class="input-icon">✉️</span>
                            <input type="email" id="email" name="email" class="form-input" 
                                   placeholder="you@example.com" required
                                   value="<?php echo sanitize($_POST['email'] ?? ''); ?>"
                                   autocomplete="email">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password *</label>
                        <div class="input-icon-wrapper">
                            <span class="input-icon">🔒</span>
                            <input type="password" id="password" name="password" class="form-input" 
                                   placeholder="Minimum 8 characters" required minlength="8"
                                   autocomplete="new-password">
                            <button type="button" class="password-toggle" onclick="togglePassword('password', this)">👁️</button>
                        </div>
                        <div class="password-strength" id="strengthBars">
                            <div class="strength-bar"></div>
                            <div class="strength-bar"></div>
                            <div class="strength-bar"></div>
                            <div class="strength-bar"></div>
                        </div>
                        <div class="password-strength-text" id="strengthText"></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirm_password">Confirm Password *</label>
                        <div class="input-icon-wrapper">
                            <span class="input-icon">🔒</span>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-input" 
                                   placeholder="Confirm your password" required
                                   autocomplete="new-password">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary auth-submit" id="registerBtn">
                        Create Account
                    </button>
                </form>

                <div class="auth-footer">
                    Already have an account? <a href="login.php">Sign in</a>
                </div>
            </div>
        </div>
    </div>

    <script>
    function togglePassword(fieldId, btn) {
        const field = document.getElementById(fieldId);
        if (field.type === 'password') { field.type = 'text'; btn.textContent = '🔓'; }
        else { field.type = 'password'; btn.textContent = '👁️'; }
    }

    // Password strength indicator
    document.getElementById('password').addEventListener('input', function() {
        const password = this.value;
        const bars = document.querySelectorAll('#strengthBars .strength-bar');
        const text = document.getElementById('strengthText');
        let strength = 0;
        
        if (password.length >= 8) strength++;
        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
        if (/\d/.test(password)) strength++;
        if (/[^a-zA-Z0-9]/.test(password)) strength++;
        
        const levels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
        const classes = ['', 'active-weak', 'active-medium', 'active-medium', 'active-strong'];
        const colors = ['', 'var(--danger)', 'var(--warning)', 'var(--warning)', 'var(--success)'];
        
        bars.forEach((bar, i) => {
            bar.className = 'strength-bar';
            if (i < strength) bar.classList.add(classes[strength]);
        });
        
        text.textContent = password.length > 0 ? levels[strength] : '';
        text.style.color = colors[strength];
    });

    document.getElementById('registerForm').addEventListener('submit', function() {
        const btn = document.getElementById('registerBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner spinner-sm"></span> Creating account...';
    });
    </script>
</body>
</html>
