(function () {
        if (typeof lucide !== 'undefined') lucide.createIcons();

        const form = document.getElementById('forgotForm');
        const input = document.getElementById('username_or_contact');
        const fieldError = document.getElementById('fieldError');
        const submitBtn = document.getElementById('submitBtn');
        const btnSpinner = document.getElementById('btnSpinner');
        const btnText = document.getElementById('btnText');
        const serverMessage = document.getElementById('server-message');
        const toastContainer = document.getElementById('toastContainer');

        // Utilities
        function normalizePhonePH(raw) {
            if (!raw) return '';
            let v = String(raw).trim();
            // Remove spaces, dashes, parentheses
            v = v.replace(/[\s\-\(\)]/g, '');
            // Remove leading + if present
            if (v.startsWith('+')) v = v.slice(1);
            // If starts with '63' and followed by 9xx -> convert to 09xx...
            if (/^63(9\d{9})$/.test(v)) {
                return '0' + v.slice(2);
            }
            // If starts with '9' and 10 digits total -> add leading 0
            if (/^9\d{9}$/.test(v)) {
                return '0' + v;
            }
            // If starts with '09' and length 11 it's ok
            if (/^09\d{9}$/.test(v)) {
                return v;
            }
            // otherwise return original trimmed
            return v;
        }

        function isLikelyPhone(v) {
            const n = normalizePhonePH(v);
            return /^09\d{9}$/.test(n);
        }

        function showFieldError(msg) {
            fieldError.textContent = msg;
            fieldError.style.display = 'block';
            input.classList.add('input-error');
            input.setAttribute('aria-invalid', 'true');
            input.focus();
        }

        function clearFieldError() {
            fieldError.textContent = '';
            fieldError.style.display = 'none';
            input.classList.remove('input-error');
            input.removeAttribute('aria-invalid');
        }

        function showToast(message, type = 'info', timeout = 4000) {
            const el = document.createElement('div');
            el.className = 'bg-white p-3 rounded-xl shadow-lg border flex items-start gap-3 mb-3';
            if (type === 'error') el.classList.add('border-red-200');
            el.innerHTML = `
                <div class="flex-shrink-0">
                    ${ type === 'error' ? '<i data-lucide="x-circle" class="w-5 h-5 text-red-500"></i>' : '<i data-lucide="check-circle" class="w-5 h-5 text-green-500"></i>' }
                </div>
                <div class="text-sm text-gray-700">${message}</div>
            `;
            toastContainer.appendChild(el);
            if (typeof lucide !== 'undefined') lucide.createIcons();
            setTimeout(() => {
                el.style.transition = 'opacity 300ms ease, transform 300ms ease';
                el.style.opacity = '0';
                el.style.transform = 'translateY(8px)';
                setTimeout(() => el.remove(), 320);
            }, timeout);
        }

        // If server returned a message, fade it after 5s and also show a toast (so it doesn't disappear abruptly)
        if (serverMessage) {
            setTimeout(() => {
                try { // mirror the server message into a toast for consistent UI
                    const text = serverMessage.innerText || serverMessage.textContent;
                    if (text && text.trim()) showToast(text.trim(), 'error', 5000);
                } catch (e) {}
                serverMessage.style.transition = 'opacity 600ms ease';
                serverMessage.style.opacity = '0';
                setTimeout(() => serverMessage.remove(), 650);
            }, 4200);
        }

        // Prevent double submit & show spinner
        let submitting = false;
        form.addEventListener('submit', function (ev) {
            clearFieldError();

            const val = input.value.trim();

            if (!val) {
                ev.preventDefault();
                showFieldError('Please enter your username or mobile number.');
                return;
            }

            // If it looks like a phone, normalize and overwrite the input value so server receives normalized form
            if (isLikelyPhone(val)) {
                input.value = normalizePhonePH(val);
            }

            // Simple client-side sanitization/checks (username can be alnum + symbols, so we only enforce not-empty)
            // Prevent double-submits
            if (submitting) {
                ev.preventDefault();
                return;
            }

            // UI: set button to busy
            submitting = true;
            submitBtn.disabled = true;
            btnSpinner.classList.remove('hidden');
            btnText.textContent = 'Sending...';

            // Let the form submit normally — server will redirect or return HTML
            // As a fallback, re-enable after 10s if no response (network issue)
            setTimeout(function () {
                if (submitting) {
                    submitting = false;
                    submitBtn.disabled = false;
                    btnSpinner.classList.add('hidden');
                    btnText.textContent = 'Send Reset Code';
                    showToast('The request is taking longer than expected. Please check your connection.', 'error', 5000);
                }
            }, 10000);
        });

        // Input tweaks
        input.addEventListener('input', function () {
            clearFieldError();
        });

        // Helpful hint: format phone while typing (non-intrusive)
        let typingTimer;
        input.addEventListener('keyup', function () {
            clearTimeout(typingTimer);
            typingTimer = setTimeout(() => {
                const raw = input.value.trim();
                // only attempt to normalize if it starts with +63/63/09/9 and contains digits
                if (/^(\+?63|0?9|9)/.test(raw)) {
                    const normalized = normalizePhonePH(raw);
                    // if normalized is 11-digit 09..., show it but don't overwrite if user typed a username (we detect by presence of letters)
                    if (/^09\d{9}$/.test(normalized) && !/[A-Za-z]/.test(raw)) {
                        input.value = normalized;
                    }
                }
            }, 500);
        });

        // Accessibility: focus input on load
        if (document.readyState === 'complete' || document.readyState === 'interactive') {
            input.focus();
        } else {
            window.addEventListener('load', () => input.focus());
        }

        // Re-render icons if any dynamic content was added
        if (typeof lucide !== 'undefined') lucide.createIcons();
    })();