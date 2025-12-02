document.addEventListener('DOMContentLoaded', () => {
    // Initialize icons
    if (typeof lucide !== 'undefined') lucide.createIcons();

    // ----------------------------------------------------
    // 1. GET CONFIGURATION FROM DOM
    // ----------------------------------------------------
    // We retrieve the appointment ID from a data attribute on the body tag
    const apptId = document.body.dataset.apptId; 
    
    if (!apptId) {
        console.error("Appointment ID not found. Ensure body has data-appt-id attribute.");
        return;
    }

    // ----------------------------------------------------
    // 2. DOM ELEMENTS
    // ----------------------------------------------------
    const calendarDays = document.getElementById('calendarDays');
    const monthDisplay = document.getElementById('currentMonthYear');
    const selectedDateInput = document.getElementById('selectedDateInput');
    const selectedTimeInput = document.getElementById('selectedTimeInput');
    const slotsGrid = document.getElementById('slotsGrid');
    const slotEmptyState = document.getElementById('slotEmptyState');
    const slotLoading = document.getElementById('slotLoading');
    const submitArea = document.getElementById('submitArea');
    const summary = document.getElementById('selectionSummary');
    const prevMonthBtn = document.getElementById('prevMonthBtn');
    const nextMonthBtn = document.getElementById('nextMonthBtn');
    const todayBtn = document.getElementById('todayBtn');

    // ----------------------------------------------------
    // 3. STATE VARIABLES
    // ----------------------------------------------------
    let currentDate = new Date();
    let selectedDate = null;
    let doctorWorkingDays = [];
    let doctorLeaves = [];

    // ----------------------------------------------------
    // 4. INITIALIZATION
    // ----------------------------------------------------
    loadDoctorSchedule();

    // Event Listeners for Navigation
    if (prevMonthBtn) {
        prevMonthBtn.addEventListener('click', () => {
            currentDate.setMonth(currentDate.getMonth() - 1);
            loadDoctorSchedule();
        });
    }

    if (nextMonthBtn) {
        nextMonthBtn.addEventListener('click', () => {
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

    // ----------------------------------------------------
    // 5. CORE FUNCTIONS
    // ----------------------------------------------------

    async function loadDoctorSchedule() {
        const month = currentDate.getMonth() + 1;
        const year = currentDate.getFullYear();
        
        // Update header immediately for better UX
        if (monthDisplay) {
            monthDisplay.textContent = new Intl.DateTimeFormat('en-US', { month: 'long', year: 'numeric' }).format(currentDate);
        }

        try {
            const res = await fetch(`?action=get_monthly_status&month=${month}&year=${year}&id=${apptId}`);
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            
            const data = await res.json();
            
            if(data.status === 'success') {
                doctorWorkingDays = data.working_days.map(Number);
                doctorLeaves = data.leaves;
                renderCalendar();
            } else {
                console.error(data.message);
            }
        } catch(e) { 
            console.error(e); 
            if(calendarDays) calendarDays.innerHTML = `<p class="col-span-7 text-center text-red-500 py-4">Error loading schedule.</p>`;
        }
    }

    function renderCalendar() {
        if (!calendarDays) return;

        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();
        
        calendarDays.innerHTML = '';
        
        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const today = new Date();
        today.setHours(0,0,0,0);

        // Empty slots for previous month days
        for(let i=0; i<firstDay; i++) {
            calendarDays.appendChild(document.createElement('div'));
        }

        for(let d=1; d<=daysInMonth; d++) {
            const dateObj = new Date(year, month, d);
            // Format date as YYYY-MM-DD
            const dateStr = [
                year, 
                String(month+1).padStart(2,'0'), 
                String(d).padStart(2,'0')
            ].join('-');
            
            const dayOfWeek = dateObj.getDay();
            
            const cell = document.createElement('div');
            cell.className = 'day-cell';
            cell.textContent = d;

            const isPast = dateObj <= today;
            const isLeave = checkLeave(dateStr);
            
            // Map JS 0 (Sun) to whatever your DB uses if needed. Assuming standard 0-6 or 1-7 match.
            // If DB uses 1=Mon...7=Sun, convert JS 0 to 7.
            // const dbDayOfWeek = (dayOfWeek === 0) ? 7 : dayOfWeek;
            
            // Using standard check assuming doctorWorkingDays matches JS getDay() 
            const isWorkingDay = doctorWorkingDays.includes(dayOfWeek);

            if(isPast) {
                cell.classList.add('day-past');
            } else if(isLeave) { 
                cell.classList.add('day-leave'); 
                cell.title = "Leave"; 
            } else if(!isWorkingDay) { 
                cell.classList.add('day-off'); 
                cell.title = "Off"; 
            } else {
                cell.classList.add('day-available');
                cell.onclick = () => selectDate(cell, dateStr);
                if(selectedDate === dateStr) cell.classList.add('day-selected');
            }
            calendarDays.appendChild(cell);
        }
    }

    function checkLeave(dateStr) {
        return doctorLeaves.some(leave => dateStr >= leave.date_start && dateStr <= leave.date_end);
    }

    function selectDate(cell, dateStr) {
        document.querySelectorAll('.day-selected').forEach(el => el.classList.remove('day-selected'));
        cell.classList.add('day-selected');
        
        selectedDate = dateStr;
        if(selectedDateInput) selectedDateInput.value = dateStr;
        
        loadSlots(dateStr);
    }

    async function loadSlots(dateStr) {
        if(slotEmptyState) slotEmptyState.classList.add('hidden');
        if(slotsGrid) slotsGrid.classList.add('hidden');
        if(submitArea) submitArea.classList.add('hidden');
        if(slotLoading) slotLoading.classList.remove('hidden');
        
        try {
            const res = await fetch(`?action=get_slots&date=${dateStr}&id=${apptId}`);
            const data = await res.json();
            
            if(slotLoading) slotLoading.classList.add('hidden');
            
            if(data.status === 'success') {
                renderSlots(data.slots);
            } else {
                if(slotsGrid) {
                    slotsGrid.innerHTML = `<div class="col-span-2 text-red-500 text-center py-4 text-sm bg-red-50 rounded border border-red-100">${data.message}</div>`;
                    slotsGrid.classList.remove('hidden');
                }
            }
        } catch(e) { console.error(e); }
    }

    function renderSlots(slots) {
        if (!slotsGrid) return;

        slotsGrid.innerHTML = '';
        slotsGrid.classList.remove('hidden');
        
        if(slots.length === 0) {
            slotsGrid.innerHTML = `<div class="col-span-2 text-gray-500 text-center py-4">No slots available.</div>`;
            return;
        }
        
        slots.forEach(slot => {
            const label = document.createElement('label');
            label.className = 'cursor-pointer block';
            
            if(slot.available) {
                label.innerHTML = `
                    <input type="radio" name="time_slot" value="${slot.time}" class="slot-radio sr-only">
                    <div class="py-2 px-4 rounded-lg border border-gray-200 text-center text-sm hover:border-purple-500 hover:text-purple-600 transition">
                        ${slot.display}
                    </div>`;
                
                // Add listener to the radio button inside
                const radio = label.querySelector('input');
                radio.addEventListener('change', () => {
                    if(selectedTimeInput) selectedTimeInput.value = slot.time;
                    if(submitArea) submitArea.classList.remove('hidden');
                    if(summary) summary.textContent = `Selected: ${slot.display} on ${selectedDate}`;
                });
            } else {
                label.innerHTML = `
                    <div class="py-2 px-4 rounded-lg border border-gray-100 bg-gray-50 text-gray-400 text-center text-sm line-through cursor-not-allowed">
                        ${slot.display}
                    </div>`;
            }
            slotsGrid.appendChild(label);
        });
    }
});