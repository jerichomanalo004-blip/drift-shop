// login_reg.js

// Password visibility toggle (works for any field)
function togglePassword(fieldId, element) {
    const field = document.getElementById(fieldId);
    if (!field) return;
    const icon = element.querySelector('i');
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// ---- Registration form validation (only if elements exist) ----
const passwordField = document.getElementById('password');
const confirmField = document.getElementById('confirm_password');
const submitBtn = document.getElementById('submitBtn');
const reqLength = document.getElementById('req-length');
const reqUpper = document.getElementById('req-upper');
const reqLower = document.getElementById('req-lower');
const reqNumber = document.getElementById('req-number');
const reqSpecial = document.getElementById('req-special');
const matchDiv = document.getElementById('passwordMatch');

const newPass = document.getElementById('new_pass');
const conNewPass = document.getElementById('con_new_pass');

if (passwordField && confirmField && submitBtn) {
    function validatePassword() {
        const pwd = passwordField.value;
        let valid = true;
        
        const lengthOk = pwd.length >= 8;
        if (reqLength) reqLength.style.color = lengthOk ? '#2ecc71' : '#e74c3c';
        if (!lengthOk) valid = false;
        
        const upperOk = /[A-Z]/.test(pwd);
        if (reqUpper) reqUpper.style.color = upperOk ? '#2ecc71' : '#e74c3c';
        if (!upperOk) valid = false;
        
        const lowerOk = /[a-z]/.test(pwd);
        if (reqLower) reqLower.style.color = lowerOk ? '#2ecc71' : '#e74c3c';
        if (!lowerOk) valid = false;
        
        const numberOk = /\d/.test(pwd);
        if (reqNumber) reqNumber.style.color = numberOk ? '#2ecc71' : '#e74c3c';
        if (!numberOk) valid = false;
        
        const specialOk = /[!@#$%^&*]/.test(pwd);
        if (reqSpecial) reqSpecial.style.color = specialOk ? '#2ecc71' : '#e74c3c';
        if (!specialOk) valid = false;
        
        return valid;
    }

    function checkPasswordMatch() {
        const pwd = passwordField.value;
        const confirm = confirmField.value;
        if (!matchDiv) return false;
        if (confirm === '') {
            matchDiv.innerHTML = '';
            return false;
        }
        if (pwd === confirm) {
            matchDiv.innerHTML = '✅ Passwords match';
            matchDiv.style.color = '#2ecc71';
            return true;
        } else {
            matchDiv.innerHTML = '❌ Passwords do not match';
            matchDiv.style.color = '#e74c3c';
            return false;
        }
    }

    function updateSubmitButton() {
        const pwdValid = validatePassword();
        const matchValid = checkPasswordMatch();
        const pwdNotEmpty = passwordField.value.length > 0;
        const confirmNotEmpty = confirmField.value.length > 0;
        submitBtn.disabled = !(pwdValid && matchValid && pwdNotEmpty && confirmNotEmpty);
    }

    passwordField.addEventListener('input', () => {
        validatePassword();
        updateSubmitButton();
    });
    confirmField.addEventListener('input', () => {
        checkPasswordMatch();
        updateSubmitButton();
    });
    
    // initial state
    validatePassword();
    updateSubmitButton();
}

flatpickr("#birthdate", {
    dateFormat: "Y-m-d",
    maxDate: new Date().fp_incr(-18 * 365), // roughly 18 years ago (more accurate below)
    onChange: function(selectedDates, dateStr, instance) {
        // Additional client‑side age validation (optional)
        const selected = selectedDates[0];
        if (selected) {
            const today = new Date();
            let age = today.getFullYear() - selected.getFullYear();
            const m = today.getMonth() - selected.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < selected.getDate())) age--;
            if (age < 18) {
                alert("You must be at least 18 years old.");
                instance.clear();
            }
        }
    }
});

// Get today's date
let today = new Date();

// Max date: 18 years ago (user must be at least 18)
let maxDate = new Date(today);
maxDate.setFullYear(today.getFullYear() - 18);

// Min date: 150 years ago (max age 150 years)
let minDate = new Date(today);
minDate.setFullYear(today.getFullYear() - 150);

flatpickr("#birthdate", {
    dateFormat: "Y-m-d",
    maxDate: maxDate,
    minDate: minDate,
    onChange: function(selectedDates, dateStr, instance) {
        const selected = selectedDates[0];
        if (selected) {
            let age = today.getFullYear() - selected.getFullYear();
            const m = today.getMonth() - selected.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < selected.getDate())) age--;
            if (age < 18) {
                alert("You must be at least 18 years old.");
                instance.clear();
            } else if (age > 150) {
                alert("Please select a valid birthdate (max 150 years).");
                instance.clear();
            }
        }
    }
});