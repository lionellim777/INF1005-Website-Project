/**
 * auth.js – Client-side validation & UX enhancements
 * Covers: password strength meter, toggle visibility, form validation.
 */

document.addEventListener('DOMContentLoaded', () => {

    // =====================================================================
    // PASSWORD VISIBILITY TOGGLE
    // =====================================================================
    document.querySelectorAll('.toggle-pw').forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-target');
            const input    = document.getElementById(targetId);
            if (!input) return;

            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        });
    });

    // =====================================================================
    // PASSWORD STRENGTH METER (registration page)
    // =====================================================================
    const pwInput       = document.getElementById('password');
    const strengthBar   = document.getElementById('pwStrengthBar');
    const strengthText  = document.getElementById('pwStrengthText');

    if (pwInput && strengthBar) {
        pwInput.addEventListener('input', () => {
            const val   = pwInput.value;
            let score   = 0;
            let label   = '';
            let color   = '';

            if (val.length >= 8)                                 score++;
            if (val.length >= 12)                                score++;
            if (/[A-Z]/.test(val))                               score++;
            if (/[a-z]/.test(val))                               score++;
            if (/\d/.test(val))                                  score++;
            if (/[!@#$%^&*()_+\-=\[\]{}|;:,.<>?]/.test(val))   score++;

            if (val.length === 0)      { label = '';           color = ''; }
            else if (score <= 2)       { label = 'Weak';       color = '#dc3545'; }
            else if (score <= 4)       { label = 'Fair';       color = '#ffc107'; }
            else if (score <= 5)       { label = 'Good';       color = '#198754'; }
            else                       { label = 'Strong';     color = '#0d6efd'; }

            const pct = val.length === 0 ? 0 : Math.min(100, (score / 6) * 100);

            strengthBar.style.width           = pct + '%';
            strengthBar.style.backgroundColor = color;
            if (strengthText) {
                strengthText.textContent = label;
                strengthText.style.color = color;
            }
        });
    }

    // =====================================================================
    // CLIENT-SIDE FORM VALIDATION (Bootstrap style)
    // =====================================================================

    /**
     * Validate registration form before submit.
     */
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', (e) => {
            let valid = true;
            clearCustomValidity(registerForm);

            const firstName = registerForm.querySelector('#first_name');
            const lastName  = registerForm.querySelector('#last_name');
            const email     = registerForm.querySelector('#email');
            const pw        = registerForm.querySelector('#password');
            const cpw       = registerForm.querySelector('#confirm_password');

            if (firstName && firstName.value.trim().length < 2) {
                setInvalid(firstName, 'At least 2 characters required.');
                valid = false;
            }
            if (lastName && lastName.value.trim().length < 2) {
                setInvalid(lastName, 'At least 2 characters required.');
                valid = false;
            }
            if (email && !isValidEmail(email.value)) {
                setInvalid(email, 'Please enter a valid email.');
                valid = false;
            }
            if (pw) {
                const pwErrors = validatePassword(pw.value);
                if (pwErrors.length) {
                    setInvalid(pw, pwErrors[0]);
                    valid = false;
                }
            }
            if (cpw && pw && cpw.value !== pw.value) {
                setInvalid(cpw, 'Passwords do not match.');
                valid = false;
            }

            if (!valid) {
                e.preventDefault();
                registerForm.classList.add('was-validated');
            }
        });
    }

    /**
     * Validate login form.
     */
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', (e) => {
            let valid = true;
            clearCustomValidity(loginForm);
            clearLoginAlert();

            const email = loginForm.querySelector('#email');
            const pw    = loginForm.querySelector('#password');
            const csrf  = loginForm.querySelector('input[name="csrf_token"]');
            const submitBtn = loginForm.querySelector('button[type="submit"]');

            if (!csrf || !csrf.value) {
                e.preventDefault();
                showLoginAlert('Login cannot proceed because the CSRF token is missing. Ensure this page is loaded through your PHP server.');
                return;
            }

            if (email && !email.value.trim()) {
                setInvalid(email, 'Email is required.');
                valid = false;
            }
            if (pw && !pw.value) {
                setInvalid(pw, 'Password is required.');
                valid = false;
            }

            if (!valid) {
                e.preventDefault();
                loginForm.classList.add('was-validated');
                showLoginAlert('Please complete the required login fields.');
            } else if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.dataset.originalText = submitBtn.textContent;
                submitBtn.textContent = 'Signing In...';
            }
        });
    }

    // =====================================================================
    // HELPERS
    // =====================================================================

    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.trim());
    }

    function validatePassword(pw) {
        const errors = [];
        if (pw.length < 8)                                       errors.push('At least 8 characters.');
        if (!/[A-Z]/.test(pw))                                   errors.push('At least one uppercase letter.');
        if (!/[a-z]/.test(pw))                                   errors.push('At least one lowercase letter.');
        if (!/\d/.test(pw))                                      errors.push('At least one number.');
        if (!/[!@#$%^&*()_+\-=\[\]{}|;:,.<>?]/.test(pw))        errors.push('At least one special character.');
        return errors;
    }

    function setInvalid(el, msg) {
        el.setCustomValidity(msg);
        const fb = el.closest('.mb-3, .col-6, .col-md-6')?.querySelector('.invalid-feedback');
        if (fb) fb.textContent = msg;
        el.classList.add('is-invalid');
    }

    function clearCustomValidity(form) {
        form.querySelectorAll('input, select, textarea').forEach(el => {
            el.setCustomValidity('');
            el.classList.remove('is-invalid');
        });
    }

    function showLoginAlert(message) {
        const alertEl = document.getElementById('clientLoginAlert');
        if (!alertEl) return;
        alertEl.textContent = message;
        alertEl.classList.remove('d-none');
    }

    function clearLoginAlert() {
        const alertEl = document.getElementById('clientLoginAlert');
        if (!alertEl) return;
        alertEl.textContent = '';
        alertEl.classList.add('d-none');
    }
});
