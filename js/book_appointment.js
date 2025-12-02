document.addEventListener('DOMContentLoaded', () => {
    // Initialize Lucide Icons
    if (typeof lucide !== 'undefined') lucide.createIcons();

    // -------------------------
    // DOM ELEMENTS
    // -------------------------
    const calendarContainer = document.getElementById('calendarContainer');
    const calendarDays = document.getElementById('calendarDays');
    const monthDisplay = document.getElementById('currentMonthYear');
    const doctorSelect = document.getElementById('doctorSelect');
    const selectedDateInput = document.getElementById('selectedDateInput');
    const selectedTimeInput = document.getElementById('selectedTimeInput');
    
    const slotsGrid = document.getElementById('slotsGrid');
    const slotEmptyState = document.getElementById('slotEmptyState');
    const slotLoading = document.getElementById('slotLoading');
    const submitArea = document.getElementById('submitArea');
    const summary = document.getElementById('selectionSummary');

    // -------------------------
    // STATE VARIABLES
    // -------------------------
    let currentDate = new Date();
    let selectedDate = null;
    let doctorWorkingDays = [];
    let doctorLeaves = [];

    // -------------------------
    // INITIALIZATION & EVENT LISTENERS
    // -------------------------
    
    // Check for SweetAlert messages from URL params
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('booking') === 'success') {
        Swal.fire({
            icon: 'success',
            title: 'Appointment Confirmed!',
            text: 'Your request has been sent for approval.',
            confirmButtonColor: '#7c3aed',
            confirmButtonText: 'View My Appointments'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'client_appointments.php';
            } else {
                window.history.replaceState(null, null, window.location.pathname);
            }
        });
    }

    // Initial Load Logic
    if(doctorSelect && doctorSelect.value) {
        calendarContainer.classList.remove('opacity-50', 'pointer-events-none');
        loadDoctorSchedule();
    }

    // Doctor Selection Change
    if (doctorSelect) {
        doctorSelect.addEventListener('change', () => {
            if(doctorSelect.value) {
                calendarContainer.classList.remove('opacity-50', 'pointer-events-none');
                // Reset selection when doctor changes
                selectedDate = null;
                selectedDateInput.value = '';
                selectedTimeInput.value = '';
                slotEmptyState.classList.remove('hidden');
                slotsGrid.classList.add('hidden');
                submitArea.classList.add('hidden');
                
                loadDoctorSchedule();
            }
        });
    }

    // Calendar Navigation Buttons
    const prevBtn = document.getElementById('prevMonthBtn');
    const nextBtn = document.getElementById('nextMonthBtn');
    const todayBtn = document.getElementById('todayBtn');

    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            currentDate.setMonth(currentDate.getMonth() - 1);
            loadDoctorSchedule();
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            currentDate.setMonth(currentDate.getMonth() + 1);
            loadDoctorSchedule();
        });
    }

    if (todayBtn) {
        todayBtn.addEventListener('click', () => {
            currentDate = new Date();
            loadDoctorSchedule();
        });
    }

    // Mobile Sidebar Toggle
    const mobileBtn = document.getElementById('mobileMenuBtn');
    if (mobileBtn) {
        mobileBtn.addEventListener('click', () => {
            const sidebar = document.getElementById('sidebar');
            if (sidebar) sidebar.classList.toggle('-translate-x-full');
        });
    }

    // -------------------------
    // CORE FUNCTIONS
    // -------------------------

    async function loadDoctorSchedule() {
        const docId = doctorSelect.value;
        const month = currentDate.getMonth() + 1;
        const year = currentDate.getFullYear();

        try {
            const res = await fetch(`?action=get_monthly_status&doctor_id=${docId}&month=${month}&year=${year}`);
            const data = await res.json();
            
            if(data.status === 'success') {
                doctorWorkingDays = data.working_days.map(Number);
                doctorLeaves = data.leaves;
                renderCalendar();
            } else {
                Swal.fire({ icon: 'error', title: 'Schedule Error', text: data.message });
            }
        } catch(e) { 
            console.error(e);
            Swal.fire({ icon: 'error', title: 'Network Error', text: 'Failed to connect to the server.' });
        }
    }

    function renderCalendar() {
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();
        
        if (monthDisplay) {
            monthDisplay.textContent = new Intl.DateTimeFormat('en-US', { month: 'long', year: 'numeric' }).format(currentDate);
        }
        
        if (calendarDays) {
            calendarDays.innerHTML = '';

            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const today = new Date();
            today.setHours(0,0,0,0);

            // Empty cells for days before the 1st of the month
            for(let i = 0; i < firstDay; i++) {
                calendarDays.appendChild(document.createElement('div'));
            }

            // Days of the month
            for(let d = 1; d <= daysInMonth; d++) {
                const dateObj = new Date(year, month, d);
                const dateString = `${year}-${String(month+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
                const dayOfWeek = dateObj.getDay();

                const cell = document.createElement('div');
                cell.className = 'day-cell';
                cell.textContent = d;

                const isPastOrToday = dateObj <= today;
                const isWorkingDay = doctorWorkingDays.includes(dayOfWeek);
                const isLeave = checkLeave(dateString);

                if(isPastOrToday) {
                    cell.classList.add('day-past');
                } else if(isLeave) {
                    cell.classList.add('day-leave');
                    cell.title = "Doctor on Leave";
                } else if(!isWorkingDay) {
                    cell.classList.add('day-off');
                    cell.title = "Doctor's Day Off";
                } else {
                    cell.classList.add('day-available');
                    cell.onclick = () => selectDate(cell, dateString);
                    if(selectedDate === dateString) cell.classList.add('day-selected');
                }
                calendarDays.appendChild(cell);
            }
        }
    }

    function checkLeave(dateStr) {
        return doctorLeaves.some(leave => dateStr >= leave.date_start && dateStr <= leave.date_end);
    }

    function selectDate(cell, dateStr) {
        document.querySelectorAll('.day-selected').forEach(el => el.classList.remove('day-selected'));
        cell.classList.add('day-selected');
        selectedDate = dateStr;
        selectedDateInput.value = dateStr;
        loadSlots(dateStr);
    }

    async function loadSlots(dateStr) {
        slotEmptyState.classList.add('hidden');
        slotsGrid.classList.add('hidden');
        submitArea.classList.add('hidden');
        slotLoading.classList.remove('hidden');
        
        const docId = doctorSelect.value;
        
        try {
            const res = await fetch(`?action=get_slots&doctor_id=${docId}&date=${dateStr}`);
            const data = await res.json();
            
            slotLoading.classList.add('hidden');
            
            if(data.status === 'success') {
                renderSlots(data.slots);
            } else {
                slotsGrid.innerHTML = `<div class="col-span-2 text-red-500 text-center py-4 bg-red-50 rounded-lg border border-red-100 text-sm p-4">${data.message}</div>`;
                slotsGrid.classList.remove('hidden');
            }
        } catch(e) { console.error(e); }
    }

    function renderSlots(slots) {
        slotsGrid.innerHTML = '';
        slotsGrid.classList.remove('hidden');
        
        if(slots.length === 0) {
            slotsGrid.innerHTML = `<div class="col-span-2 text-gray-500 text-center py-4">No available slots for this date.</div>`;
            return;
        }

        slots.forEach(slot => {
            const label = document.createElement('label');
            label.className = 'cursor-pointer block';
            
            let content = '';
            if(slot.available) {
                content = `
                    <input type="radio" name="time_slot" value="${slot.time}" class="slot-radio sr-only">
                    <div class="py-2 px-4 rounded-lg border border-gray-200 text-center text-sm hover:border-purple-500 hover:text-purple-600 transition">
                        ${slot.display}
                    </div>
                `;
                label.innerHTML = content;
                // Add change listener to the radio input created inside the label
                const radio = label.querySelector('input');
                radio.addEventListener('change', () => {
                    selectedTimeInput.value = slot.time;
                    submitArea.classList.remove('hidden');
                    summary.textContent = `Selected: ${slot.display} on ${selectedDate}`;
                });
            } else {
                content = `
                    <div class="py-2 px-4 rounded-lg border border-gray-100 bg-gray-50 text-gray-400 text-center text-sm line-through cursor-not-allowed">
                        ${slot.display}
                    </div>
                `;
                label.innerHTML = content;
            }
            slotsGrid.appendChild(label);
        });
    }
});