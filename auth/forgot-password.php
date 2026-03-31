<?php
/**
 * Nouriq — Forgot Password
 */
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $email = sanitizeEmail($_POST['email'] ?? '');
        
        if (!validateEmail($email)) {
            $error = 'Please enter a valid email address.';
        } else {
            $db = getDB();
            $stmt = $db->prepare("SELECT id, username FROM users WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour
                
                $stmt = $db->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
                $stmt->execute([$token, $expires, $user['id']]);
                
                // In production, send email. Here we simulate.
                $resetLink = APP_URL . '/auth/reset-password.php?token=' . $token;
                
                $success = 'Password reset link has been generated. In a production environment, this would be emailed to you.<br><br>
                            <strong>Reset Link:</strong><br>
                            <a href="' . $resetLink . '" style="word-break:break-all;color:var(--accent-light)">' . htmlspecialchars($resetLink) . '</a>';
            } else {
                // Don't reveal if email exists
                $success = 'If an account exists with that email, a reset link has been sent.';
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
    <title>Forgot Password — Nouriq</title>
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
                    <h2>Reset Password</h2>
                    <p>Enter your email to receive a reset link</p>
                </div>

                <?php if ($error): ?>
                <div class="auth-alert error"><span>⚠️</span> <?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="auth-alert success"><span>✅</span> <div><?php echo $success; ?></div></div>
                <?php endif; ?>

                <form class="auth-form" method="POST" action="">
                    <?php echo csrfField(); ?>
                    <div class="form-group">
                        <label class="form-label" for="email">Email Address</label>
                        <div class="input-icon-wrapper">
                            <span class="input-icon">✉️</span>
                            <input type="email" id="email" name="email" class="form-input" 
                                   placeholder="your@email.com" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary auth-submit">Send Reset Link</button>
                </form>

                <div class="auth-footer">
                    <a href="login.php">← Back to Sign In</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
