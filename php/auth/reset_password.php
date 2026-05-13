<?php $token = $_GET['token'] ?? ''; ?>
<link rel="stylesheet" href="/shop/css/login_reg.css?v=1.2">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<div class="auth-card">
    <?php if (isset($_GET['error'])): ?>
        <div class="system-alert">
            <?php 
                if ($_GET['error'] == 'mismatch') echo "ERROR: Passwords do not match.";
                else if ($_GET['error'] == 'weak_password') echo "ERROR: Password does not meet requirements (min 8 chars, uppercase, lowercase, number, special character).";
                else if ($_GET['error'] == 'invalid_token') echo "CRITICAL: Reset token expired or invalid.";
                else if ($_GET['error'] == 'system_fail') echo "SYSTEM ERROR: Password update failed.";
            ?>
        </div>
    <?php endif; ?>

    <h2>New Password</h2>
    <form action="/shop/php/auth/process_reset.php" method="POST" id="resetPasswordForm">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

        <div class="form-group password-group">
            <label>New Password</label>
            <div class="password-wrapper">
                <input type="password" name="password" id="new_pass" placeholder="••••••••" required>
                <span class="toggle-password" onclick="togglePassword('new_pass', this)">
                    <i class="far fa-eye"></i>
                </span>
            </div>
            <div class="password-requirements" id="passwordRequirements">
                <small>Password must contain:</small>
                <ul>
                    <li id="req-length">✔️ At least 8 characters</li>
                    <li id="req-upper">✔️ One uppercase letter</li>
                    <li id="req-lower">✔️ One lowercase letter</li>
                    <li id="req-number">✔️ One number</li>
                    <li id="req-special">✔️ One special character (!@#$%^&*)</li>
                </ul>
            </div>
        </div>

        <div class="form-group password-group">
            <label>Confirm New Password</label>
            <div class="password-wrapper">
                <input type="password" name="confirm_password" id="con_new_pass" placeholder="••••••••" required>
                <span class="toggle-password" onclick="togglePassword('con_new_pass', this)">
                    <i class="far fa-eye"></i>
                </span>
            </div>
            <div id="passwordMatch" style="font-size: 12px; margin-top: 5px;"></div>
        </div>

        <button type="submit" class="btn-primary" id="resetBtn" disabled>Update Password</button>
    </form>
</div>
<script src="/shop/php/js/reset.js"></script>