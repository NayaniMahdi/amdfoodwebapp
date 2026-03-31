<?php
/**
 * Nouriq — Reset Password
 */
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();

$error = '';
$success = '';
$validToken = false;
$token = $_GET['token'] ?? $_POST['token'] ?? '';

if (!empty($token)) {
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW() AND is_active = 1");
    $stmt->execute([$token]);
    $validToken = (bool)$stmt->fetch();
    
    if (!$validToken) {
        $error = 'Invalid or expired reset link. Please request a new one.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        
        if (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
            $stmt = $db->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE reset_token = ?");
            $stmt->execute([$hash, $token]);
            
            header('Location: login.php?reset=1');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — Nouriq</title>
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
                    <h2>Set New Password</h2>
                    <p>Choose a strong password for your account</p>
                </div>

                <?php if ($error): ?>
                <div class="auth-alert error"><span>⚠️</span> <?php echo sanitize($error); ?></div>
                <?php endif; ?>

                <?php if ($validToken): ?>
                <form class="auth-form" method="POST" action="">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="token" value="<?php echo sanitize($token); ?>">
                    
                    <div class="form-group">
                        <label class="form-label" for="password">New Password</label>
                        <div class="input-icon-wrapper">
                            <span class="input-icon">🔒</span>
                            <input type="password" id="password" name="password" class="form-input" 
                                   placeholder="Minimum 8 characters" required minlength="8">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="confirm_password">Confirm Password</label>
                        <div class="input-icon-wrapper">
                            <span class="input-icon">🔒</span>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-input" 
                                   placeholder="Confirm your password" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary auth-submit">Reset Password</button>
                </form>
                <?php else: ?>
                <div class="auth-footer" style="margin-top:0">
                    <a href="forgot-password.php">← Request a new reset link</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
