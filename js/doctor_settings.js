if (typeof lucide !== 'undefined') lucide.createIcons();
        
        // 1. Avatar Preview
        function previewAndSubmit(input) {
            const file = input.files[0];
            if(file) {
                if (file.size > 2 * 1024 * 1024) {
                    Swal.fire({ icon: 'error', title: 'File Too Large', text: 'Max size is 2MB.' });
                    return;
                }
                const reader = new FileReader();
                reader.onload = function (e) { document.getElementById('avatarPreview').src = e.target.result; }
                reader.readAsDataURL(file);
                document.getElementById('avatarForm').submit();
            }
        }

        // 2. Validation Logic
        document.addEventListener('DOMContentLoaded', () => {
            const showError = (input, msg) => {
                input.classList.add('error-border');
                const group = input.closest('.form-group');
                if(group) {
                    let span = group.querySelector('.error-msg');
                    if(!span) { span = document.createElement('span'); span.className='error-msg error-text'; group.appendChild(span); }
                    span.textContent = msg;
                    span.classList.add('error-text');
                }
                return false;
            };

            const clearError = (input) => {
                input.classList.remove('error-border');
                const group = input.closest('.form-group');
                if(group) {
                    const span = group.querySelector('.error-msg');
                    if(span) span.textContent = '';
                }
                return true;
            };

            // Profile Validation
            const profileForm = document.getElementById('profileForm');
            if(profileForm) {
                profileForm.addEventListener('submit', (e) => {
                    let valid = true;
                    
                    const fname = document.getElementById('first_name');
                    if(!/^[a-zA-Z\s.-]+$/.test(fname.value.trim())) valid = showError(fname, "Invalid format (letters only).");
                    else clearError(fname);

                    const lname = document.getElementById('last_name');
                    if(!/^[a-zA-Z\s.-]+$/.test(lname.value.trim())) valid = showError(lname, "Invalid format (letters only).");
                    else clearError(lname);

                    const contact = document.getElementById('contact');
                    if(!/^(09|\+639)\d{9}$/.test(contact.value.trim())) valid = showError(contact, "Invalid PH mobile number.");
                    else clearError(contact);

                    const spec = document.getElementById('specialization');
                    if(!spec.value) valid = showError(spec, "Required."); else clearError(spec);

                    if(!valid) {
                        e.preventDefault();
                        Swal.fire({ icon: 'warning', title: 'Check Inputs', text: 'Please correct errors in the form.' });
                    }
                });
            }

            // Password Validation
            const passForm = document.getElementById('passwordForm');
            if(passForm) {
                passForm.addEventListener('submit', (e) => {
                    let valid = true;
                    const current = document.getElementById('current_password');
                    const newP = document.getElementById('new_password');
                    const confP = document.getElementById('confirm_password');

                    if(!current.value) valid = showError(current, "Required."); else clearError(current);

                    if(newP.value.length < 8) valid = showError(newP, "Min 8 chars."); else clearError(newP);

                    if(newP.value !== confP.value) valid = showError(confP, "Passwords do not match."); else clearError(confP);

                    if(!valid) {
                        e.preventDefault();
                        Swal.fire({ icon: 'warning', title: 'Check Password', text: 'Please fix password errors.' });
                    }
                });
            }

            // Real-time clearing
            document.querySelectorAll('.validation-field').forEach(el => {
                el.addEventListener('input', () => clearError(el));
            });
        });

        document.getElementById('mobileMenuBtn')?.addEventListener('click', () => {
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
        });