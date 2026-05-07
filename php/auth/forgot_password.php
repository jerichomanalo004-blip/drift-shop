<div class="auth-card">
    <?php if (isset($_GET['error'])): ?>
        <div class="system-alert">
            <?php 
                if ($_GET['error'] == 'not_found') echo "ERROR: No account associated with this email.";
                else if ($_GET['error'] == 'system_fail') echo "SYSTEM ERROR: Could not generate reset token.";
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['success'])): ?>
        <div class="system-alert" style="border-color: #2ecc71; color: #2ecc71;">
            SUCCESS: Reset link dispatched to your inbox.
        </div>
    <?php endif; ?>

    <h2>Reset Password</h2>
    <p class="footer-text" style="margin-top: 0; margin-bottom: 20px; text-align: center;">
        Enter your email and we'll send you a link to get back into your account.
    </p>

    <form action="/shop/php/auth/process_forgot.php" method="POST">
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" placeholder="email@example.com" required>
        </div>
        <button type="submit" class="btn-primary">Send Reset Link</button>
    </form>

    <div class="footer-text">
        Suddenly remembered? <a href="/shop/php/index.php?page=login">Back to Login</a>
    </div>
</div>