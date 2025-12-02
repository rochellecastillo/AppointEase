if (typeof lucide !== 'undefined') lucide.createIcons();

    function previewImage(input) {
        const preview = document.getElementById('preview');
        const placeholder = document.getElementById('placeholder');
        const file = input.files[0];
        
        if (file) {
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
            if (!allowedTypes.includes(file.type)) {
                Swal.fire({ icon: 'error', title: 'Invalid File', text: 'Please use JPG, PNG, or GIF.', confirmButtonColor: '#ef4444' });
                input.value = '';
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                Swal.fire({ icon: 'error', title: 'File Too Large', text: 'Max size is 5MB.', confirmButtonColor: '#ef4444' });
                input.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = function (e) {
                preview.src = e.target.result;
                preview.classList.remove('hidden');
                placeholder.classList.add('hidden');
            }
            reader.readAsDataURL(file);
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('addPatientForm');
        const fields = document.querySelectorAll('.validation-field');

        const patterns = {
            first_name: /^[a-zA-Z\s.-]{2,50}$/,
            middle_name: /^[a-zA-Z\s.-]*$/,
            last_name: /^[a-zA-Z\s.-]{2,50}$/,
            username: /^[a-zA-Z0-9_]{4,20}$/,
            contact: /^(09|\+639)\d{9}$/,
            address: /.+/,
            bdate: /.+/,
            gender: /.+/,
            password: /^.{8,}$/
        };

        const messages = {
            first_name: "Letters only, min 2 chars.",
            middle_name: "Letters only.",
            last_name: "Letters only, min 2 chars.",
            username: "Alphanumeric, 4-20 chars.",
            contact: "Invalid PH Number (e.g. 09123456789).",
            address: "Required.",
            bdate: "Required.",
            gender: "Required.",
            password: "Min 8 characters."
        };

        const showError = (input, msg) => {
            input.classList.add('error-border');
            const group = input.closest('.form-group');
            if(group){
                let span = group.querySelector('.error-msg');
                if(!span){ span = document.createElement('span'); span.className='error-msg error-text'; group.appendChild(span); }
                span.textContent = msg;
            }
            return false;
        };

        const clearError = (input) => {
            input.classList.remove('error-border');
            const group = input.closest('.form-group');
            if(group){
                const span = group.querySelector('.error-msg');
                if(span) span.textContent = '';
            }
            return true;
        };

        const validate = (input) => {
            const name = input.id;
            const val = input.value.trim();
            if(input.hasAttribute('required') && val==='') return showError(input,"Required.");
            if(val!=='' && patterns[name] && !patterns[name].test(val)) return showError(input,messages[name]);
            return clearError(input);
        };

        fields.forEach(f=>{ f.addEventListener('input',()=>validate(f)); f.addEventListener('blur',()=>validate(f)); });
        form.addEventListener('submit', (e)=>{
            let valid=true;
            fields.forEach(f=>{ if(!validate(f)) valid=false; });
            if(!valid){
                e.preventDefault();
                Swal.fire({icon:'warning', title:'Validation Error', text:'Please correct the highlighted errors.', confirmButtonColor:'#ef4444'});
            }
        });
    });