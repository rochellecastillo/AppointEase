if (typeof lucide !== 'undefined') lucide.createIcons();
        const modal = document.getElementById('profileModal');
        const form = document.getElementById('updateForm');

        function openModal(data) {
            // Basic Info
            document.getElementById('modalPatientId').value = data.id;
            document.getElementById('modalPatientName').textContent = data.name;

            // Vitals
            document.getElementById('mBlood').value = data.blood_type || '';
            document.getElementById('mHeight').value = data.height || '';
            document.getElementById('mWeight').value = data.weight || '';

            // Emergency
            document.getElementById('mEcName').value = data.ec_name || '';
            document.getElementById('mEcPhone').value = data.ec_phone || '';

            // Medical
            document.getElementById('mAllergies').value = data.allergies || '';
            document.getElementById('mChronic').value = data.chronic || '';
            document.getElementById('mMeds').value = data.meds || '';
            document.getElementById('mSurgeries').value = data.surgery || '';
            document.getElementById('mFamily').value = data.family || '';

            modal.classList.remove('hidden');
        }

        function closeModal() {
            modal.classList.add('hidden');
        }

        // Validation Logic
        form.addEventListener('submit', function(e) {
            let isValid = true;
            let messages = [];

            const height = document.getElementById('mHeight');
            const weight = document.getElementById('mWeight');
            const phone = document.getElementById('mEcPhone');

            // Reset previous errors
            [height, weight, phone].forEach(el => el.classList.remove('error-border'));

            // Validate Numeric Height/Weight
            if (height.value && (height.value < 0 || height.value > 300)) {
                isValid = false;
                height.classList.add('error-border');
                messages.push("Height seems invalid (0-300 cm).");
            }
            if (weight.value && (weight.value < 0 || weight.value > 500)) {
                isValid = false;
                weight.classList.add('error-border');
                messages.push("Weight seems invalid.");
            }

            // Validate Phone if entered
            if (phone.value && !/^(09|\+639)\d{9}$/.test(phone.value)) {
                isValid = false;
                phone.classList.add('error-border');
                messages.push("Invalid Phone Format (use 09xxxxxxxxx).");
            }

            if (!isValid) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Check Input',
                    html: messages.join('<br>'),
                    confirmButtonColor: '#ef4444'
                });
            }
        });

        // Mobile Sidebar Toggle
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const sidebar = document.getElementById('sidebar');
        if(mobileMenuBtn && sidebar) {
            mobileMenuBtn.addEventListener('click', () => {
                sidebar.classList.toggle('-translate-x-full');
                sidebar.classList.toggle('fixed');
                sidebar.classList.toggle('inset-0');
                sidebar.classList.toggle('z-50');
            });
        }