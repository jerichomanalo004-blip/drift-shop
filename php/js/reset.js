
const newPass = document.getElementById('new_pass');
const conNewPass = document.getElementById('con_new_pass');
const resetBtn = document.getElementById('resetBtn');
const reqLength = document.getElementById('req-length');
const reqUpper = document.getElementById('req-upper');
const reqLower = document.getElementById('req-lower');
const reqNumber = document.getElementById('req-number');
const reqSpecial = document.getElementById('req-special');
const passwordMatch = document.getElementById('passwordMatch');

const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/;

function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

function updateRequirement(element, condition) {
    element.style.color = condition ? '#28a745' : '#e74c3c';
}

function validatePasswordInputs() {
    const value = newPass.value;
    const hasLength = value.length >= 8;
    const hasUpper = /[A-Z]/.test(value);
    const hasLower = /[a-z]/.test(value);
    const hasNumber = /\d/.test(value);
    const hasSpecial = /[\W_]/.test(value);

    updateRequirement(reqLength, hasLength);
    updateRequirement(reqUpper, hasUpper);
    updateRequirement(reqLower, hasLower);
    updateRequirement(reqNumber, hasNumber);
    updateRequirement(reqSpecial, hasSpecial);

    const passwordsMatch = value === conNewPass.value && value !== '';
    passwordMatch.textContent = passwordsMatch ? 'Passwords match' : 'Passwords do not match';
    passwordMatch.style.color = passwordsMatch ? '#28a745' : '#e74c3c';

    resetBtn.disabled = !(hasLength && hasUpper && hasLower && hasNumber && hasSpecial && passwordsMatch);
}

newPass.addEventListener('input', validatePasswordInputs);
conNewPass.addEventListener('input', validatePasswordInputs);