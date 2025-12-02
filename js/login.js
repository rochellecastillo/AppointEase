document.addEventListener('DOMContentLoaded', function () {
    if (typeof lucide !== 'undefined') lucide.createIcons();

    const loginForm = document.getElementById('loginForm');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const rememberCheckbox = document.getElementById('remember');
    const submitBtn = document.getElementById('submitBtn');
    const btnSpinner = document.getElementById('btnSpinner');
    const btnText = document.getElementById('btnText');
    const togglePasswordBtn = document.getElementById('togglePasswordBtn');
    const eyeIcon = document.getElementById('eyeIcon');

    const usernameError = document.getElementById('usernameError');
    const passwordError = document.getElementById('passwordError');

    const SERVER_ERROR = document.getElementById('server-error');
    const SERVER_SUCCESS = document.getElementById('server-success');

    // Restore saved username
    try {
        const saved = JSON.parse(localStorage.getItem('appointease_remember') || 'null');
        if (saved && saved.username) {
            usernameInput.value = saved.username;
            rememberCheckbox.checked = true;
        }
    } catch (e) {}

    function showFieldError(elInput, elError, msg) {
        elInput.classList.add('input-error');
        elError.textContent = msg;
        elError.style.display = 'block';
    }

    function clearFieldError(elInput, elError) {
        elInput.classList.remove('input-error');
        elError.style.display = 'none';
    }

    [usernameInput, passwordInput].forEach((el) => {
        el.addEventListener('input', () => {
            if (el === usernameInput) clearFieldError(usernameInput, usernameError);
            if (el === passwordInput) clearFieldError(passwordInput, passwordError);

            if (SERVER_ERROR) SERVER_ERROR.style.display = 'none';
            if (SERVER_SUCCESS) SERVER_SUCCESS.style.display = 'none';
        });
    });

    togglePasswordBtn.addEventListener('click', (ev) => {
        ev.preventDefault();
        const hidden = passwordInput.type === 'password';
        passwordInput.type = hidden ? 'text' : 'password';
        togglePasswordBtn.setAttribute('aria-pressed', hidden ? 'true' : 'false');
        eyeIcon.setAttribute('data-lucide', hidden ? 'eye-off' : 'eye');
        lucide.createIcons();
        passwordInput.focus();
    });

    let submitting = false;

    loginForm.addEventListener('submit', (e) => {
        let valid = true;

        if (!usernameInput.value.trim()) {
            showFieldError(usernameInput, usernameError, 'Please enter your username');
            valid = false;
        }
        if (!passwordInput.value) {
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

        if (rememberCheckbox.checked) {
            localStorage.setItem('appointease_remember', JSON.stringify({
                username: usernameInput.value.trim(),
                saved_at: Date.now()
            }));
        } else {
            localStorage.removeItem('appointease_remember');
        }

        submitting = true;
        submitBtn.disabled = true;
        btnSpinner.classList.remove('hidden');
        btnText.textContent = 'Signing in...';
    });

    function fadeOut(el, ms = 600) {
        if (!el) return;
        el.style.transition = `opacity ${ms}ms`;
        el.style.opacity = 0;
        setTimeout(() => el.remove(), ms + 50);
    }

    if (SERVER_ERROR) setTimeout(() => fadeOut(SERVER_ERROR), 6000);
    if (SERVER_SUCCESS) setTimeout(() => fadeOut(SERVER_SUCCESS), 5000);

    // Focus logic if server returned error
    const hasServerError = document.body.getAttribute('data-server-error') === '1';
    if (hasServerError) {
        if (usernameInput.value.trim()) passwordInput.focus();
        else usernameInput.focus();
    }

    lucide.createIcons();
});