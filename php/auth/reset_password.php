<?php $token = $_GET['token'] ?? ''; ?>
<div class="auth-card">
    <?php if (isset($_GET['error'])): ?>
        <div class="system-alert">
            <?php 
                if ($_GET['error'] == 'mismatch') echo "ERROR: Passwords do not match.";
                else if ($_GET['error'] == 'invalid_token') echo "CRITICAL: Reset token expired or invalid.";
                else if ($_GET['error'] == 'system_fail') echo "SYSTEM ERROR: Password update failed.";
            ?>
        </div>
    <?php endif; ?>

    <h2>New Password</h2>
    <form action="/shop/php/auth/process_reset.php" method="POST">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <div class="form-group">
            <label>New Password</label>
            <input type="password" name="password" placeholder="••••••••" required>
        </div>
        <div class="form-group">
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" placeholder="••••••••" required>
        </div>
        <button type="submit" class="btn-primary">Update Password</button>
    </form>
</div>