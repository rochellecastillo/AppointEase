if (typeof lucide !== 'undefined') lucide.createIcons();

    // -------------------- TAB LOGIC --------------------
    document.addEventListener('DOMContentLoaded', () => {
        const tabs = document.querySelectorAll('.tab-button');
        const contents = document.querySelectorAll('.tab-content');
        const activeClass = 'border-purple-600 text-purple-600 font-semibold';
        const inactiveClass = 'border-transparent text-gray-500 font-medium hover:text-gray-700 hover:border-gray-300';

        function showTab(tabId) {
            tabs.forEach(tab => {
                if (tab.getAttribute('data-tab-id') === tabId) {
                    tab.className = 'tab-button inline-flex items-center gap-2 py-4 px-1 border-b-2 text-sm transition duration-200 ' + activeClass;
                } else {
                    tab.className = 'tab-button inline-flex items-center gap-2 py-4 px-1 border-b-2 text-sm transition duration-200 ' + inactiveClass;
                }
            });
            contents.forEach(content => {
                content.id === tabId ? content.classList.remove('hidden') : content.classList.add('hidden');
            });
        }

        tabs.forEach(tab => {
            tab.addEventListener('click', (e) => showTab(e.currentTarget.getAttribute('data-tab-id')));
        });

        // Auto-switch tab if error related to password
        const hasError = "<?= $error ?>";
        if (hasError && (hasError.includes('password') || hasError.includes('weak'))) {
            showTab('security');
        } else {
            showTab('profile');
        }

        // -------------------- VALIDATION LOGIC --------------------
        
        // Helper: Show/Clear Error
        const showError = (input, msg) => {
            input.classList.add('error-border');
            const group = input.closest('.form-group');
            if(group) {
                const span = group.querySelector('.error-msg');
                if(span) { span.textContent = msg; span.classList.add('error-text'); }
            }
            return false;
        };

        const clearError = (input) => {
            input.classList.remove('error-border');
            const group = input.closest('.form-group');
            if(group) {
                const span = group.querySelector('.error-msg');
                if(span) { span.textContent = ''; }
            }
            return true;
        };

        // Validators
        const validateProfile = () => {
            let valid = true;
            
            const fname = document.getElementById('first_name');
            if(!/^[a-zA-Z\s.-]+$/.test(fname.value.trim())) valid = showError(fname, "Letters only."); else clearError(fname);

            const lname = document.getElementById('last_name');
            if(!/^[a-zA-Z\s.-]+$/.test(lname.value.trim())) valid = showError(lname, "Letters only."); else clearError(lname);

            const phone = document.getElementById('phone');
            if(!/^(09|\+639)\d{9}$/.test(phone.value.trim())) valid = showError(phone, "Invalid PH format."); else clearError(phone);

            return valid;
        };

        const validatePassword = () => {
            let valid = true;
            const current = document.getElementById('current_password');
            const newP = document.getElementById('new_password');
            const confP = document.getElementById('confirm_password');

            if(!current.value) valid = showError(current, "Required."); else clearError(current);

            // Strong Password Regex
            const strongRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d\W_]{8,}$/;
            if(!strongRegex.test(newP.value)) valid = showError(newP, "Too weak."); else clearError(newP);

            if(newP.value !== confP.value) valid = showError(confP, "Passwords do not match."); else clearError(confP);

            return valid;
        };

        // Attach Listeners
        document.getElementById('profileForm').addEventListener('submit', (e) => {
            if(!validateProfile()) {
                e.preventDefault();
                Swal.fire({ icon: 'warning', title: 'Check Inputs', text: 'Please correct the errors in the form.', confirmButtonColor: '#ef4444' });
            }
        });

        document.getElementById('passwordForm').addEventListener('submit', (e) => {
            if(!validatePassword()) {
                e.preventDefault();
                Swal.fire({ icon: 'warning', title: 'Check Inputs', text: 'Please ensure passwords match and meet requirements.', confirmButtonColor: '#ef4444' });
            }
        });

        // Real-time feedback
        document.querySelectorAll('.validation-field').forEach(el => {
            el.addEventListener('input', (e) => clearError(e.target));
        });
    });