if (typeof lucide !== 'undefined') lucide.createIcons();

        function previewImage(input) {
            const preview = document.getElementById('preview_img');
            const file = input.files[0];
            if (file) {
                if(file.size > 5*1024*1024){ Swal.fire({icon:'error', title:'File Too Large', text:'Max 5MB'}); input.value=''; return; }
                const reader = new FileReader();
                reader.onload = function(e){ preview.src = e.target.result; }
                reader.readAsDataURL(file);
            }
        }

        document.addEventListener('DOMContentLoaded', ()=>{
            const form = document.getElementById('editPatientForm');
            const fields = document.querySelectorAll('.validation-field');

            const patterns = {
                first_name:/^[a-zA-Z\s.-]{2,50}$/,
                last_name:/^[a-zA-Z\s.-]{2,50}$/,
                contact:/^(09|\+639)\d{9}$/,
                address:/.+/
            };

            const messages = {
                first_name:"Please enter a valid name.",
                last_name:"Please enter a valid last name.",
                contact:"Enter valid PH number.",
                address:"Address is required.",
                bdate:"Birth date required.",
                gender:"Select gender."
            };

            const validateField = (input)=>{
                const name=input.name,val=input.value.trim();
                const group=input.closest('.form-group');
                const errorSpan=group.querySelector('.error-msg');
                let isValid=true,errorMsg="";
                if(val===""){isValid=false; errorMsg="This field is required.";}
                else if(patterns[name]&&!patterns[name].test(val)){isValid=false; errorMsg=messages[name];}
                if(!isValid){input.classList.add('error-border','bg-red-50'); if(errorSpan){errorSpan.textContent=errorMsg; errorSpan.classList.add('error-text');}}
                else{input.classList.remove('error-border','bg-red-50'); if(errorSpan){errorSpan.textContent='';}}
                return isValid;
            };

            fields.forEach(f=>{f.addEventListener('blur',()=>validateField(f)); f.addEventListener('input',()=>validateField(f));});

            form.addEventListener('submit',(e)=>{
                let valid=true;
                fields.forEach(f=>{if(!validateField(f)) valid=false;});
                const genderField=document.getElementById('gender');
                if(genderField.value===""){valid=false; genderField.classList.add('error-border'); Swal.fire({icon:'warning', title:'Validation Error', text:messages.gender, confirmButtonColor:'#ef4444'});}
                if(!valid){e.preventDefault(); Swal.fire({icon:'warning', title:'Validation Error', text:'Please correct errors.', confirmButtonColor:'#ef4444'});}
            });
        });