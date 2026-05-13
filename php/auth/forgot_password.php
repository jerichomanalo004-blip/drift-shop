<?php
require_once __DIR__ . '/../../config/autoload.php';

use Core\CSRF;
use Core\SessionManager;

SessionManager::start();

$hasVerifyStep = SessionManager::has('reset_verify_email') && SessionManager::has('reset_verify_question');
$questionKey = SessionManager::get('reset_verify_question');
$questionLabel = '';
if ($questionKey === 'birthdate') {
    $questionLabel = 'Birthdate';
} elseif ($questionKey === 'contact_number') {
    $questionLabel = 'Contact Number';
}
?>
<link rel="stylesheet" href="/shop/css/login_reg.css?v=1.2">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<div class="auth-card">
    <?php if (isset($_GET['error'])): ?>
        <div class="system-alert">
            <?php
                if ($_GET['error'] == 'not_found') echo "ERROR: No account associated with this email.";
                else if ($_GET['error'] == 'invalid_answer') echo "ERROR: Verification answer does not match our records.";
                else if ($_GET['error'] == 'invalid_csrf') echo "ERROR: Invalid form submission. Please try again.";
                else if ($_GET['error'] == 'no_question') echo "ERROR: Verification session expired. Start over.";
                else if ($_GET['error'] == 'system_fail') echo "SYSTEM ERROR: Could not generate reset token.";
            ?>
        </div>
    <?php endif; ?>

    <h2>Reset Password</h2>
    <?php if ($hasVerifyStep): ?>
        <p class="footer-text" style="margin-top: 0; margin-bottom: 20px; text-align: center;">
            We found your account. Answer the security question below to continue.
        </p>

        <form action="/shop/php/auth/process_forgot.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(CSRF::token()) ?>">
            <div class="form-group">
                <label><?= htmlspecialchars($questionLabel) ?></label>
                <?php if ($questionKey === 'birthdate'): ?>
                    <input type="date" name="security_answer" required>
                <?php else: ?>
                    <input type="text" name="security_answer" placeholder="Enter your <?= htmlspecialchars($questionLabel) ?>" required>
                <?php endif; ?>
            </div>
            <button type="submit" class="btn-primary">Verify and Send Reset Link</button>
        </form>
    <?php else: ?>
        <p class="footer-text" style="margin-top: 0; margin-bottom: 20px; text-align: center;">
            Enter your email and we'll ask a verification question before sending a reset link.
        </p>

        <form action="/shop/php/auth/process_forgot.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(CSRF::token()) ?>">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="email@example.com" required>
            </div>
            <button type="submit" class="btn-primary">Continue</button>
        </form>
    <?php endif; ?>

    <div class="footer-text">
        Suddenly remembered? <a href="/shop/php/index.php?page=login">Back to Login</a>
    </div>
</div>