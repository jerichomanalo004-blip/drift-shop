<link rel="stylesheet" href="/shop/css/login_reg.css?v=1.1">
<div class="auth-card register-crosswise">
    <?php if (isset($_GET['error'])): ?>
        <div class="system-alert">
            <?php 
                if ($_GET['error'] == 'email_exists') echo "CRITICAL: Email already registered.";
                else if ($_GET['error'] == 'underage') echo "ACCESS DENIED: You must be 18 or older to join DRIFT.";
                else if ($_GET['error'] == 'invalid_contact') echo "ERROR: Contact number must be exactly 11 digits.";
                else if ($_GET['error'] == 'password_mismatch') echo "ERROR: Passwords do not match.";
                else if ($_GET['error'] == 'system_fail') echo "SYSTEM ERROR: Action could not be completed.";
            ?>
        </div>
    <?php endif; ?>
    <h2>Join the Collection</h2>
    <form action="/shop/php/auth/process_register.php" method="POST">
        <div class="form-row">
            <div class="form-group">
                <label>First Name</label>
                <input type="text" name="first_name" placeholder="Enter first name" required>
            </div>
            <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="last_name" placeholder="Enter last name" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Age (must be 18 above)</label>
                <input type="number" name="age" placeholder="Age" min="18" required>
            </div>
            <div class="form-group">
                <label>Gender</label>
                <select name="gender" class="form-input" required>
                    <option value="" disabled selected>Select Gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>Contact Number</label>
            <input type="text" name="contact" placeholder="09123456789" pattern="\d{11}" maxlength="11" title="Please enter exactly 11 digits" required>
        </div>
        <div class="form-group">
            <label>Address</label>
            <textarea name="address" rows="2" placeholder="Street, City, Province..." required></textarea>
        </div>
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" placeholder="email@example.com" required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="••••••••" required>
        </div>
        <div class="form-group">
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" placeholder="••••••••" required>
        </div>
        <button type="submit" class="btn-primary">Create Account</button>
    </form>
    <div class="footer-text">
        Already a member? <a href="/shop/public/index.php?page=login">Login</a>
    </div>
</div>