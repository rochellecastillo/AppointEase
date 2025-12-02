if (typeof lucide !== 'undefined') lucide.createIcons();

        // VALIDATION LOGIC
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('healthForm');

            form.addEventListener('submit', function(e) {
                let isValid = true;
                let messages = [];

                const height = document.getElementById('height');
                const weight = document.getElementById('weight');
                const phone = document.getElementById('emergency_contact_phone');

                // Remove previous error styles
                [height, weight, phone].forEach(el => el.classList.remove('error-border'));

                // 1. Validate Numeric Range for Vitals
                if (height.value && (height.value < 0 || height.value > 300)) {
                    isValid = false;
                    height.classList.add('error-border');
                    messages.push("Height seems invalid (0-300 cm).");
                }
                if (weight.value && (weight.value < 0 || weight.value > 600)) {
                    isValid = false;
                    weight.classList.add('error-border');
                    messages.push("Weight seems invalid.");
                }

                // 2. Validate Phone Format (if provided)
                if (phone.value && !/^(09|\+639)\d{9}$/.test(phone.value)) {
                    isValid = false;
                    phone.classList.add('error-border');
                    messages.push("Invalid Emergency Phone Number (use 09xxxxxxxxx).");
                }

                if (!isValid) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Check Inputs',
                        html: messages.join('<br>'),
                        confirmButtonColor: '#ef4444'
                    });
                }
            });
        });