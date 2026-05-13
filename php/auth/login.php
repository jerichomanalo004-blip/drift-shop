<link rel="stylesheet" href="/shop/css/login_reg.css?v=1.2">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Get form data from session if available
$formData = $_SESSION['form_data'] ?? [];
// Clear the session data after retrieving it
unset($_SESSION['form_data']);
?>
<div class="auth-card">
    <?php if(isset($_GET['success']) && $_GET['success'] == '1'): ?>
        <div class="system-alert" style="border-color: #2ecc71; color: #2ecc71;">
            AUTHENTICATION: Account created. Access granted.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="system-alert">
            <?php 
                if ($_GET['error'] == 'invalid_credentials') echo "CRITICAL: Invalid email or password.";
                else if ($_GET['error'] == 'not_found') echo "ERROR: No account associated with this identifier.";
                else if ($_GET['error'] == 'system_fail') echo "SYSTEM ERROR: Authentication server unreachable.";
            ?>
        </div>
    <?php endif; ?>

    <h2>Login</h2>
    <form action="/shop/php/auth/process_login.php" method="POST">
        <div class="form-group">
            <label for="login_email">Email Address</label>
            <input type="text" id="login_email" name="email" value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>" placeholder="Enter credentials" required>
        </div>
        <div class="form-group password-group">
            <label for="login_pass">Password</label>
            <div class="password-wrapper">
                <input type="password" id="login_pass" name="password" placeholder="••••••••" required>
                <span class="toggle-password" onclick="togglePassword('login_pass', this)">
                    <i class="far fa-eye"></i>
                </span>
            </div>
            <div class="forgot-wrapper">
                <a href="/shop/php/index.php?page=forgot-password" class="forgot-link">Forgot Password?</a>
            </div>
        </div>
        <button type="submit" class="btn-primary">Sign In</button>
    </form>
    <div class="footer-text">
        New to DRIFT? <a href="/shop/php/index.php?page=register">Register</a>
    </div>
</div>

<script src="/shop/php/js/login_reg.js"></script>