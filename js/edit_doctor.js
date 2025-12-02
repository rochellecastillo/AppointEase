if (typeof lucide !== 'undefined') lucide.createIcons();

        function previewImage(input) {
            const preview = document.getElementById('preview_img');
            const file = input.files[0];
            
            if (file) {
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
                if (!allowedTypes.includes(file.type)) {
                    Swal.fire({ icon: 'error', title: 'Invalid File', text: 'Please select a valid image file (JPG, PNG, GIF).', confirmButtonColor: '#9333ea' });
                    input.value = '';
                    return;
                }

                if (file.size > 5 * 1024 * 1024) {
                    Swal.fire({ icon: 'error', title: 'File Too Large', text: 'Image size should not exceed 5MB.', confirmButtonColor: '#9333ea' });
                    input.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (e) { preview.src = e.target.result; }
                reader.readAsDataURL(file);
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('editDoctorForm');
            const fields = document.querySelectorAll('.validation-field');

            const patterns = {
                first_name: /^[a-zA-Z\s.-]{2,50}$/, 
                middle_name: /^[a-zA-Z\s.-]*$/, 
                last_name: /^[a-zA-Z\s.-]{2,50}$/,
                contact: /^(09|\+639)\d{9}$/,
                address: /.+/,
                specialization: /.+/
            };

            const errorMessages = {
                first_name: "Please enter a valid name (letters only).",
                middle_name: "Please enter a valid middle name (letters only).",
                last_name: "Please enter a valid last name.",
                contact: "Enter valid PH number (e.g., 09123456789).",
                address: "Address is required.",
                bdate: "Date of birth is required.",
                gender: "Please select a gender.",
                specialization: "Please select a specialization."
            };

            const showError = (input, message) => {
                const group = input.closest('.form-group');
                const errorSpan = group.querySelector('.error-msg');
                input.classList.add('error-border', 'bg-red-50');
                if(errorSpan) { errorSpan.textContent = message; errorSpan.classList.add('error-text'); }
                return false;
            };

            const clearError = (input) => {
                const group = input.closest('.form-group');
                const errorSpan = group.querySelector('.error-msg');
                input.classList.remove('error-border','bg-red-50');
                if(errorSpan) { errorSpan.textContent = ''; }
                return true;
            };

            const validateField = (input) => {
                const name = input.name;
                const value = input.value.trim();
                if (value === '' && name !== 'middle_name') return showError(input, errorMessages[name] || "This field is required.");
                if (patterns[name] && value !== '' && !patterns[name].test(value)) return showError(input, errorMessages[name]);
                if (name === 'bdate') {
                    const dob = new Date(value), today = new Date();
                    let age = today.getFullYear() - dob.getFullYear();
                    if(today.getMonth()-dob.getMonth()<0||(today.getMonth()-dob.getMonth()===0 && today.getDate()<dob.getDate())) age--;
                    if(age < 20) return showError(input, "Doctor must be at least 20 years old.");
                }
                return clearError(input);
            };

            fields.forEach(input => input.addEventListener('input', () => validateField(input)));

            form.addEventListener('submit', e => {
                let valid = true;
                fields.forEach(input => { if(!validateField(input)) valid=false; });
                const genderField = document.getElementById('gender');
                if(genderField.value === '') { showError(genderField,errorMessages['gender']); valid=false; } else clearError(genderField);
                if(!valid) { e.preventDefault(); Swal.fire({icon:'error', title:'Fix Errors', text:'Please fix all errors before submitting.', confirmButtonColor:'#9333ea'}); }
            });
        });