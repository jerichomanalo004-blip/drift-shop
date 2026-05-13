<link rel="stylesheet" href="/shop/css/login_reg.css?v=1.2">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Get form data from session if available
$formData = $_SESSION['form_data'] ?? [];
// Clear the session data after retrieving it
unset($_SESSION['form_data']);
?>
<div class="auth-card register-crosswise">
    <?php if (isset($_GET['error'])): ?>
        <div class="system-alert">
            <?php 
                if ($_GET['error'] == 'email_exists') echo "CRITICAL: Email already registered.";
                else if ($_GET['error'] == 'underage') echo "ACCESS DENIED: You must be 18 or older.";
                else if ($_GET['error'] == 'invalid_age') echo "ERROR: Invalid birthdate (age out of range).";
                else if ($_GET['error'] == 'invalid_contact') echo "ERROR: Contact number must be exactly 11 digits.";
                else if ($_GET['error'] == 'password_mismatch') echo "ERROR: Passwords do not match.";
                else if ($_GET['error'] == 'weak_password') echo "ERROR: Password does not meet requirements (min 8 chars, uppercase, lowercase, number, special character).";
                else if ($_GET['error'] == 'system_fail') echo "SYSTEM ERROR: Action could not be completed.";
            ?>
        </div>
    <?php endif; ?>
    
    <h2>Join the Collection</h2>
    <form action="/shop/php/auth/process_register.php" method="POST" id="registerForm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Core\CSRF::token()) ?>">
        <div class="form-row">
            <div class="form-group">
                <label>First Name</label>
                <input type="text" name="first_name" value="<?php echo htmlspecialchars($formData['first_name'] ?? ''); ?>" placeholder="First name" required>
            </div>
            <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="last_name" value="<?php echo htmlspecialchars($formData['last_name'] ?? ''); ?>" placeholder="Last name" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Birthdate</label>
                <div class="date-wrapper">
                    <input type="text" name="birthdate" id="birthdate" value="<?php echo htmlspecialchars($formData['birthdate'] ?? ''); ?>" placeholder="Select your birthdate" required readonly>
                    <i class="far fa-calendar-alt date-icon"></i>
                </div>
                <small style="font-size: 10px; color: #888;">Must be 18 years or older</small>
            </div>
            <div class="form-group">
                <label>Gender</label>
                <select name="gender" class="form-input" required>
                    <option value="" disabled <?php echo (empty($formData['gender'] ?? '')) ? 'selected' : ''; ?>>Select Gender</option>
                    <option value="Male" <?php echo (($formData['gender'] ?? '') == 'Male') ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?php echo (($formData['gender'] ?? '') == 'Female') ? 'selected' : ''; ?>>Female</option>
                    <option value="Other" <?php echo (($formData['gender'] ?? '') == 'Other') ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Contact Number</label>
            <input type="tel" 
                name="contact" 
                value="<?php echo htmlspecialchars($formData['contact'] ?? ''); ?>"
                placeholder="09XXXXXXXXX" 
                pattern="\d{11}" 
                maxlength="11" 
                title="Please enter exactly 11 digits (e.g., 09123456789)" 
                required>
        </div>

        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>" placeholder="email@example.com" required>
        </div>

        <!-- Password field with toggle -->
        <div class="form-group password-group">
            <label>Password</label>
            <div class="password-wrapper">
                <input type="password" name="password" id="password" placeholder="Create a strong password" required>
                <span class="toggle-password" onclick="togglePassword('password', this)">
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

        <!-- Confirm Password with toggle -->
        <div class="form-group password-group">
            <label>Confirm Password</label>
            <div class="password-wrapper">
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm your password" required>
                <span class="toggle-password" onclick="togglePassword('confirm_password', this)">
                    <i class="far fa-eye"></i>
                </span>
            </div>
            <div id="passwordMatch" style="font-size: 12px; margin-top: 5px;"></div>
        </div>

        <button type="submit" class="btn-primary" id="submitBtn" disabled>Create Account</button>
    </form>
    
    <div class="footer-text">
        Already a member? <a href="/shop/php/index.php?page=login">Login</a>
    </div>
</div>

<script src="/shop/php/js/login_reg.js"></script>