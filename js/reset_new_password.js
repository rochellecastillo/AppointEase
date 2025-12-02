if (typeof lucide !== 'undefined') lucide.createIcons();

        function togglePassword(id) {
            const input = document.getElementById(id);
            const icon = document.getElementById('eye-' + id);
            if (input.type === 'password') {
                input.type = 'text';
                icon.setAttribute('data-lucide', 'eye-off');
            } else {
                input.type = 'password';
                icon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }

        // Real-time Validation Logic
        const pass = document.getElementById('new_password');
        const conf = document.getElementById('confirm_password');
        
        if(pass && conf) {
            pass.addEventListener('input', validate);
            conf.addEventListener('input', checkMatch);
        }

        function validate() {
            const v = pass.value;
            updateCheck('req-len', v.length >= 8);
            updateCheck('req-up', /[A-Z]/.test(v));
            updateCheck('req-low', /[a-z]/.test(v));
            updateCheck('req-num', /[0-9]/.test(v));
            checkMatch();
        }

        function updateCheck(id, valid) {
            const el = document.getElementById(id);
            const icon = el.querySelector('i'); 
            
            if (valid) {
                el.classList.remove('text-gray-500');
                el.classList.add('text-green-600', 'font-medium');
                icon.setAttribute('data-lucide', 'check-circle');
                icon.classList.add('text-green-600');
            } else {
                el.classList.add('text-gray-500');
                el.classList.remove('text-green-600', 'font-medium');
                icon.setAttribute('data-lucide', 'circle');
                icon.classList.remove('text-green-600');
            }
            lucide.createIcons();
        }

        function checkMatch() {
            const msg = document.getElementById('match-msg');
            if (!conf.value) {
                msg.classList.add('hidden');
                return;
            }
            msg.classList.remove('hidden');
            if (pass.value === conf.value) {
                msg.innerHTML = '<i data-lucide="check" class="w-3 h-3 mr-1"></i> Passwords match';
                msg.className = "text-xs mt-2 flex items-center font-medium text-green-600";
            } else {
                msg.innerHTML = '<i data-lucide="x" class="w-3 h-3 mr-1"></i> Passwords do not match';
                msg.className = "text-xs mt-2 flex items-center font-medium text-red-500";
            }
            lucide.createIcons();
        }