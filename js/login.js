document.addEventListener('DOMContentLoaded', function () {
    if (typeof lucide !== 'undefined') lucide.createIcons();

    const loginForm = document.getElementById('loginForm');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const submitBtn = document.getElementById('submitBtn');
    const btnSpinner = document.getElementById('btnSpinner');
    const btnText = document.getElementById('btnText');
    const togglePasswordBtn = document.getElementById('togglePasswordBtn');
    const eyeIcon = document.getElementById('eyeIcon');

    const usernameError = document.getElementById('usernameError');
    const passwordError = document.getElementById('passwordError');

    const SERVER_ERROR = document.getElementById('server-error');
    const SERVER_SUCCESS = document.getElementById('server-success');

    function showFieldError(elInput, elError, msg) {
        if (!elInput) return;
        elInput.classList.add('input-error');
        if (elError) {
            elError.textContent = msg;
            elError.style.display = 'block';
        }
    }

    function clearFieldError(elInput, elError) {
        if (!elInput) return;
        elInput.classList.remove('input-error');
        if (elError) {
            // defensive: only access style if element exists
            try {
                elError.style.display = 'none';
                elError.textContent = '';
            } catch (err) {
                // ignore if style isn't available for some reason
            }
        }
    }

    // only wire up listeners when inputs exist
    const inputs = [];
    if (usernameInput) inputs.push(usernameInput);
    if (passwordInput) inputs.push(passwordInput);

    inputs.forEach((el) => {
        el.addEventListener('input', () => {
            if (el === usernameInput) clearFieldError(usernameInput, usernameError);
            if (el === passwordInput) clearFieldError(passwordInput, passwordError);

            if (SERVER_ERROR) SERVER_ERROR.style.display = 'none';
            if (SERVER_SUCCESS) SERVER_SUCCESS.style.display = 'none';
        });
    });

    // toggle password button - only if present
    if (togglePasswordBtn && passwordInput && eyeIcon) {
        togglePasswordBtn.addEventListener('click', (ev) => {
            ev.preventDefault();
            const hidden = passwordInput.type === 'password';
            passwordInput.type = hidden ? 'text' : 'password';
            togglePasswordBtn.setAttribute('aria-pressed', hidden ? 'true' : 'false');
            eyeIcon.setAttribute('data-lucide', hidden ? 'eye-off' : 'eye');
            if (typeof lucide !== 'undefined') lucide.createIcons();
            passwordInput.focus();
        });
    }

    let submitting = false;

    if (loginForm) {
        loginForm.addEventListener('submit', (e) => {
            let valid = true;

            if (!usernameInput || !usernameInput.value.trim()) {
                showFieldError(usernameInput, usernameError, 'Please enter your username');
                valid = false;
            }
            if (!passwordInput || !passwordInput.value) {
                showFieldError(passwordInput, passwordError, 'Please enter your password');
                valid = false;
            }

            if (!valid) {
                e.preventDefault();
                return;
            }

            if (submitting) {
                e.preventDefault();
                return;
            }

            submitting = true;
            if (submitBtn) submitBtn.disabled = true;
            if (btnSpinner) btnSpinner.classList.remove('hidden');
            if (btnText) btnText.textContent = 'Signing in...';
        });
    }

    function fadeOut(el, ms = 600) {
        if (!el) return;
        el.style.transition = `opacity ${ms}ms`;
        el.style.opacity = 0;
        setTimeout(() => {
            if (el && el.parentNode) el.remove();
        }, ms + 50);
    }

    if (SERVER_ERROR) setTimeout(() => fadeOut(SERVER_ERROR), 6000);
    if (SERVER_SUCCESS) setTimeout(() => fadeOut(SERVER_SUCCESS), 5000);

    // Focus logic if server returned error
    const hasServerError = document.body.getAttribute('data-server-error') === '1';
    if (hasServerError) {
        if (usernameInput && usernameInput.value.trim()) {
            if (passwordInput) passwordInput.focus();
        } else {
            if (usernameInput) usernameInput.focus();
        }
    }

    if (typeof lucide !== 'undefined') lucide.createIcons();
});